<?php
/**
 * Social sharing metadata and structured data.
 *
 * A local news site lives on shares. Without Open Graph tags a link posted to
 * Facebook renders as a bare URL with no headline, no image and no description,
 * which costs clicks on every single post. This adds:
 *
 *   - Open Graph + Twitter Card tags (headline, description, image, author)
 *   - Canonical URLs
 *   - JSON-LD structured data: NewsArticle, Event, LocalBusiness,
 *     NewsMediaOrganization and BreadcrumbList
 *
 * Everything here stands down automatically if an SEO plugin is active, so the
 * theme never emits duplicate or competing tags.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether an SEO plugin is already handling meta output.
 *
 * Checked against the markers each plugin defines, so the theme yields rather
 * than fighting Yoast, Rank Math, AIOSEO, SEOPress or Slim SEO.
 *
 * @return bool
 */
function mysaline_seo_plugin_active() {
	$markers = array(
		defined( 'WPSEO_VERSION' ),                 // Yoast.
		defined( 'RANK_MATH_VERSION' ),             // Rank Math.
		defined( 'AIOSEO_VERSION' ),                // All in One SEO.
		defined( 'SEOPRESS_VERSION' ),              // SEOPress.
		defined( 'SLIM_SEO_VERSION' ),              // Slim SEO.
		class_exists( 'The_SEO_Framework\\Load' ),  // The SEO Framework.
	);

	foreach ( $markers as $present ) {
		if ( $present ) {
			return true;
		}
	}

	/**
	 * Force the theme's meta output off (or on) regardless of detection.
	 *
	 * @param bool $active Whether a plugin is considered active.
	 */
	return (bool) apply_filters( 'mysaline_seo_plugin_active', false );
}

/**
 * Best available description for the current view.
 *
 * @return string
 */
function mysaline_meta_description() {
	if ( is_singular() ) {
		$post = get_post();
		if ( ! $post ) {
			return get_bloginfo( 'description' );
		}
		$text = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		return trim( wp_html_excerpt( $text, 200, '…' ) );
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$desc = wp_strip_all_tags( term_description() );
		if ( $desc ) {
			return trim( wp_html_excerpt( $desc, 200, '…' ) );
		}
		return sprintf(
			/* translators: 1: term name, 2: site name. */
			__( '%1$s news and updates from %2$s.', 'mysaline' ),
			single_term_title( '', false ),
			get_bloginfo( 'name' )
		);
	}

	if ( is_author() ) {
		$bio = get_the_author_meta( 'description' );
		if ( $bio ) {
			return trim( wp_html_excerpt( wp_strip_all_tags( $bio ), 200, '…' ) );
		}
	}

	if ( is_search() ) {
		/* translators: %s: search term. */
		return sprintf( __( 'Search results for “%s”.', 'mysaline' ), get_search_query() );
	}

	return get_bloginfo( 'description' );
}

/**
 * Sharing image for the current view: featured image, else the custom logo.
 *
 * @return array{url:string,width:int,height:int,alt:string}|null
 */
function mysaline_share_image() {
	$id = 0;

	if ( is_singular() && has_post_thumbnail() ) {
		$id = get_post_thumbnail_id();
	} elseif ( has_custom_logo() ) {
		$id = (int) get_theme_mod( 'custom_logo' );
	}

	if ( ! $id ) {
		return null;
	}

	// 1200x630 is the size Facebook and X render largest.
	$src = wp_get_attachment_image_src( $id, 'mysaline-hero' );
	if ( ! $src ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
	}
	if ( ! $src ) {
		return null;
	}

	return array(
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
		'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
	);
}

/**
 * Canonical URL for the current view.
 *
 * @return string
 */
function mysaline_canonical_url() {
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? home_url( '/' ) : $link;
	}
	if ( is_author() ) {
		return get_author_posts_url( get_queried_object_id() );
	}
	if ( is_post_type_archive() ) {
		return (string) get_post_type_archive_link( get_post_type() );
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_home() ) {
		$page = (int) get_option( 'page_for_posts' );
		return $page ? get_permalink( $page ) : home_url( '/' );
	}
	return home_url( add_query_arg( array() ) );
}

/**
 * Output Open Graph, Twitter Card and canonical tags.
 */
function mysaline_social_meta() {
	if ( mysaline_seo_plugin_active() || is_404() ) {
		return;
	}

	$title = wp_get_document_title();
	$desc  = mysaline_meta_description();
	$url   = mysaline_canonical_url();
	$image = mysaline_share_image();
	$type  = is_singular() && ! is_front_page() ? 'article' : 'website';

	echo "\n<!-- MySaline social meta -->\n";
	printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );

	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );

	printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( str_replace( '-', '_', get_bloginfo( 'language' ) ) ) );

	if ( $image ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image['url'] ) );
		printf( '<meta property="og:image:width" content="%d">' . "\n", (int) $image['width'] );
		printf( '<meta property="og:image:height" content="%d">' . "\n", (int) $image['height'] );
		if ( $image['alt'] ) {
			printf( '<meta property="og:image:alt" content="%s">' . "\n", esc_attr( $image['alt'] ) );
		}
	}

	if ( 'article' === $type ) {
		$post = get_post();
		printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_the_date( DATE_W3C, $post ) ) );
		printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_the_modified_date( DATE_W3C, $post ) ) );

		$author = get_the_author_meta( 'display_name', $post->post_author );
		if ( $author ) {
			printf( '<meta property="article:author" content="%s">' . "\n", esc_attr( $author ) );
		}

		$cats = get_the_category( $post->ID );
		if ( ! empty( $cats ) ) {
			printf( '<meta property="article:section" content="%s">' . "\n", esc_attr( $cats[0]->name ) );
		}

		$tags = get_the_tags( $post->ID );
		if ( $tags && ! is_wp_error( $tags ) ) {
			foreach ( $tags as $tag ) {
				printf( '<meta property="article:tag" content="%s">' . "\n", esc_attr( $tag->name ) );
			}
		}
	}

	// Twitter / X.
	printf(
		'<meta name="twitter:card" content="%s">' . "\n",
		$image ? 'summary_large_image' : 'summary'
	);
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $desc ) );
	if ( $image ) {
		printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image['url'] ) );
	}

	$handle = get_theme_mod( 'mysaline_social_twitter', '' );
	if ( $handle ) {
		$user = trim( (string) wp_parse_url( $handle, PHP_URL_PATH ), '/' );
		if ( $user ) {
			printf( '<meta name="twitter:site" content="@%s">' . "\n", esc_attr( $user ) );
		}
	}
	echo "<!-- /MySaline social meta -->\n\n";
}
add_action( 'wp_head', 'mysaline_social_meta', 5 );

/* -------------------------------------------------------------------------
 * Structured data (JSON-LD)
 * ---------------------------------------------------------------------- */

/**
 * Publisher node, reused across schema types.
 *
 * @return array
 */
function mysaline_schema_publisher() {
	$node = array(
		'@type' => 'NewsMediaOrganization',
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);

	if ( has_custom_logo() ) {
		$src = wp_get_attachment_image_src( (int) get_theme_mod( 'custom_logo' ), 'full' );
		if ( $src ) {
			$node['logo'] = array(
				'@type'  => 'ImageObject',
				'url'    => $src[0],
				'width'  => (int) $src[1],
				'height' => (int) $src[2],
			);
		}
	}

	$social = array_values( mysaline_get_social_links() );
	if ( $social ) {
		$node['sameAs'] = $social;
	}

	$phone = get_theme_mod( 'mysaline_contact_phone', '' );
	if ( $phone ) {
		$node['telephone'] = $phone;
	}
	$email = get_theme_mod( 'mysaline_contact_email', '' );
	if ( $email ) {
		$node['email'] = $email;
	}

	return $node;
}

/**
 * Breadcrumb schema so Google shows the section path under the headline.
 *
 * @return array|null
 */
function mysaline_schema_breadcrumbs() {
	if ( is_front_page() ) {
		return null;
	}

	$items = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => __( 'Home', 'mysaline' ),
			'item'     => home_url( '/' ),
		),
	);

	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( ! empty( $cats ) ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => $cats[0]->name,
				'item'     => get_category_link( $cats[0]->term_id ),
			);
		}
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => count( $items ) + 1,
			'name'     => get_the_title(),
			'item'     => get_permalink(),
		);
	} elseif ( is_singular() ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => get_the_title(),
			'item'     => get_permalink(),
		);
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => single_term_title( '', false ),
			'item'     => mysaline_canonical_url(),
		);
	} else {
		return null;
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
}

/**
 * Build and print the JSON-LD graph for the current view.
 */
function mysaline_structured_data() {
	if ( mysaline_seo_plugin_active() || is_404() ) {
		return;
	}

	$graph = array();

	// Site-level identity, always present.
	$graph[] = mysaline_schema_publisher();

	$crumbs = mysaline_schema_breadcrumbs();
	if ( $crumbs ) {
		$graph[] = $crumbs;
	}

	if ( is_singular( 'post' ) ) {
		$node = array(
			'@type'            => 'NewsArticle',
			'headline'         => wp_html_excerpt( get_the_title(), 110, '' ),
			'mainEntityOfPage' => get_permalink(),
			'datePublished'    => get_the_date( DATE_W3C ),
			'dateModified'     => get_the_modified_date( DATE_W3C ),
			'description'      => mysaline_meta_description(),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author(),
				'url'   => get_author_posts_url( (int) get_post_field( 'post_author' ) ),
			),
			'publisher'        => mysaline_schema_publisher(),
		);
		$image = mysaline_share_image();
		if ( $image ) {
			$node['image'] = array( $image['url'] );
		}
		$cats = get_the_category();
		if ( ! empty( $cats ) ) {
			$node['articleSection'] = $cats[0]->name;
		}
		$graph[] = $node;

	} elseif ( is_singular( 'ms_event' ) ) {
		$id    = get_the_ID();
		$start = get_post_meta( $id, '_ms_event_start', true );
		$end   = get_post_meta( $id, '_ms_event_end', true );
		$node  = array(
			'@type'       => 'Event',
			'name'        => get_the_title(),
			'url'         => get_permalink(),
			'description' => mysaline_meta_description(),
		);
		if ( $start ) {
			$node['startDate'] = $start;
		}
		if ( $end ) {
			$node['endDate'] = $end;
		}
		$venue   = get_post_meta( $id, '_ms_event_venue', true );
		$address = get_post_meta( $id, '_ms_event_address', true );
		if ( $venue || $address ) {
			$node['location'] = array_filter(
				array(
					'@type'   => 'Place',
					'name'    => $venue ? $venue : null,
					'address' => $address ? $address : null,
				)
			);
		}
		$cost = get_post_meta( $id, '_ms_event_cost', true );
		if ( $cost ) {
			$node['offers'] = array(
				'@type' => 'Offer',
				'price' => $cost,
				'url'   => get_post_meta( $id, '_ms_event_link', true ) ? get_post_meta( $id, '_ms_event_link', true ) : get_permalink(),
			);
		}
		$org = get_post_meta( $id, '_ms_event_organizer', true );
		if ( $org ) {
			$node['organizer'] = array(
				'@type' => 'Organization',
				'name'  => $org,
			);
		}
		$image = mysaline_share_image();
		if ( $image ) {
			$node['image'] = array( $image['url'] );
		}
		$graph[] = $node;

	} elseif ( is_singular( 'ms_business' ) ) {
		$id   = get_the_ID();
		$node = array(
			'@type'       => 'LocalBusiness',
			'name'        => get_the_title(),
			'url'         => get_permalink(),
			'description' => mysaline_meta_description(),
		);
		$phone = get_post_meta( $id, '_ms_biz_phone', true );
		if ( $phone ) {
			$node['telephone'] = $phone;
		}
		$email = get_post_meta( $id, '_ms_biz_email', true );
		if ( $email ) {
			$node['email'] = $email;
		}
		$address = get_post_meta( $id, '_ms_biz_address', true );
		if ( $address ) {
			$node['address'] = array(
				'@type'         => 'PostalAddress',
				'streetAddress' => str_replace( array( "\r\n", "\n" ), ', ', $address ),
			);
		}
		$site = get_post_meta( $id, '_ms_biz_website', true );
		if ( $site ) {
			$node['sameAs'] = array( $site );
		}
		$hours = get_post_meta( $id, '_ms_biz_hours', true );
		if ( $hours ) {
			$node['openingHours'] = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $hours ) ) ) );
		}
		$image = mysaline_share_image();
		if ( $image ) {
			$node['image'] = array( $image['url'] );
		}
		$graph[] = $node;
	}

	if ( empty( $graph ) ) {
		return;
	}

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo '<script type="application/ld+json">'
		// JSON_UNESCAPED_SLASHES keeps URLs readable; the output is JSON, not HTML.
		. wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. "</script>\n";
}
add_action( 'wp_head', 'mysaline_structured_data', 6 );

/**
 * Tell browsers the site's theme colour, so mobile chrome matches the header.
 */
function mysaline_theme_color_meta() {
	printf(
		'<meta name="theme-color" content="%s">' . "\n",
		esc_attr( get_theme_mod( 'mysaline_color_primary', '#0b2545' ) )
	);
}
add_action( 'wp_head', 'mysaline_theme_color_meta', 4 );
