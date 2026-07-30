<?php
/**
 * Advertisement zones & rendering.
 *
 * Ads are the `ms_ad` custom post type. Each ad has a zone, an image (featured
 * image) or pasted code, a click URL and optional run dates. Templates call
 * mysaline_ad( 'zone' ) wherever an ad slot should appear.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The available ad placement zones.
 *
 * @return array zone key => human label.
 */
function mysaline_ad_zone_choices() {
	return apply_filters(
		'mysaline_ad_zones',
		array(
			'header'        => __( 'Header (leaderboard, top of page)', 'mysaline' ),
			'homepage_top'  => __( 'Homepage — below hero', 'mysaline' ),
			'homepage_mid'  => __( 'Homepage — between sections', 'mysaline' ),
			'sidebar'       => __( 'Sidebar', 'mysaline' ),
			'in_content'    => __( 'Inside articles (in-content)', 'mysaline' ),
			'below_content' => __( 'Below article content', 'mysaline' ),
			'footer'        => __( 'Footer', 'mysaline' ),
		)
	);
}

/**
 * Retrieve active ads for a zone, honoring run dates.
 *
 * @param string $zone  Zone key.
 * @param int    $limit Max ads to return.
 * @return WP_Post[] Ad posts.
 */
function mysaline_get_ads( $zone, $limit = 1 ) {
	if ( ! get_theme_mod( 'mysaline_ads_enable', true ) ) {
		return array();
	}

	$today = current_time( 'Y-m-d' );

	$query = new WP_Query(
		array(
			'post_type'              => 'ms_ad',
			'post_status'            => 'publish',
			'posts_per_page'         => max( 1, (int) $limit ) * 4, // Fetch extra, filter by date, then trim.
			'orderby'                => 'rand',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'   => '_ms_ad_zone',
					'value' => $zone,
				),
			),
		)
	);

	$ads = array();
	foreach ( $query->posts as $ad ) {
		$start = get_post_meta( $ad->ID, '_ms_ad_start', true );
		$end   = get_post_meta( $ad->ID, '_ms_ad_end', true );

		if ( $start && $start > $today ) {
			continue;
		}
		if ( $end && $end < $today ) {
			continue;
		}
		$ads[] = $ad;
		if ( count( $ads ) >= $limit ) {
			break;
		}
	}
	wp_reset_postdata();

	return $ads;
}

/**
 * Echo the ad(s) for a zone, wrapped in themed markup.
 *
 * @param string $zone    Zone key.
 * @param array  $args    Optional { limit, class }.
 */
function mysaline_ad( $zone, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'limit' => 1,
			'class' => '',
		)
	);

	$ads = mysaline_get_ads( $zone, $args['limit'] );
	if ( empty( $ads ) ) {
		return;
	}

	$label = get_theme_mod( 'mysaline_ads_label', __( 'Advertisement', 'mysaline' ) );

	foreach ( $ads as $ad ) {
		$code    = get_post_meta( $ad->ID, '_ms_ad_code', true );
		$link    = get_post_meta( $ad->ID, '_ms_ad_link', true );
		$sponsor = get_post_meta( $ad->ID, '_ms_ad_sponsor', true );
		$new_tab = get_post_meta( $ad->ID, '_ms_ad_new_tab', true );
		$target  = $new_tab ? ' target="_blank" rel="nofollow noopener sponsored"' : ' rel="nofollow sponsored"';

		echo '<div class="ms-ad ms-ad--' . esc_attr( $zone ) . ' ' . esc_attr( $args['class'] ) . '">';
		if ( $label ) {
			echo '<span class="ms-ad__label">' . esc_html( $label ) . '</span>';
		}

		if ( $code ) {
			// Owner-pasted ad-network markup. Allowed via wp_kses_post at save time.
			echo do_shortcode( $code ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( has_post_thumbnail( $ad->ID ) ) {
			$img = get_the_post_thumbnail(
				$ad->ID,
				'large',
				array(
					'alt'     => $sponsor ? $sponsor : get_the_title( $ad->ID ),
					'loading' => 'lazy',
				)
			);
			if ( $link ) {
				printf( '<a href="%1$s"%2$s>%3$s</a>', esc_url( $link ), $target, $img ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $img from core, $target static.
			} else {
				echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		echo '</div>';
	}
}

/**
 * Insert an in-content ad partway through single post content.
 *
 * @param string $content Post content.
 * @return string
 */
function mysaline_insert_incontent_ad( $content ) {
	if ( is_admin() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( ! get_theme_mod( 'mysaline_ads_incontent', true ) ) {
		return $content;
	}
	if ( empty( mysaline_get_ads( 'in_content', 1 ) ) ) {
		return $content;
	}

	// Insert after the 3rd paragraph (or at the end if shorter).
	$paragraphs = explode( '</p>', $content );
	$total      = count( $paragraphs );
	if ( $total < 3 ) {
		return $content;
	}
	$insert_at = min( 3, $total - 1 );

	ob_start();
	mysaline_ad( 'in_content' );
	$ad_markup = ob_get_clean();

	$paragraphs[ $insert_at ] .= $ad_markup;
	return implode( '</p>', $paragraphs );
}
add_filter( 'the_content', 'mysaline_insert_incontent_ad' );
