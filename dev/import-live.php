<?php
/**
 * Pull real content from the live MySaline into the development site.
 *
 * This exists so the owner can be shown her own newsroom on the new design
 * rather than invented placeholder stories. It is strictly read-only against
 * mysaline.com: it reads the public REST API and public post pages, and issues
 * no writes of any kind to the live site.
 *
 * What comes across: headline, body, excerpt, publish date, slug, categories,
 * tags, byline and featured image. URLs are preserved, so a story that lives at
 * /some-headline/ on the live site lives at /some-headline/ here too.
 *
 * Deliberately a sample, not a mirror. The live site has ~18,000 posts and
 * ~24,000 media items; a few dozen recent stories fill every homepage section,
 * category archive and author page without copying an entire newspaper or
 * hammering someone else's server. Requests are spaced out for the same reason.
 *
 * Idempotent: every imported item records its source ID, so re-running updates
 * rather than duplicating.
 *
 * Run with:  wp eval-file dev/import-live.php [count]
 *
 * @package MySaline\Dev
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run through WP-CLI.\n";
	return;
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

const MS_LIVE_ROOT = 'https://www.mysaline.com';
const MS_LIVE_UA   = 'MySaline-redesign-preview/1.0 (staging import for the site owner)';

$ms_wanted = isset( $args[0] ) ? max( 1, (int) $args[0] ) : 60;

/**
 * Polite GET against the live site.
 *
 * @param string $url Absolute URL.
 * @return string|null Body, or null on failure.
 */
function ms_live_get( $url ) {
	// A live newspaper sits behind caching and rate limiting, so a single slow
	// response is normal and is not a reason to abandon the run.
	for ( $attempt = 1; $attempt <= 3; $attempt++ ) {
		// Spacing requests out: this is someone's production server.
		usleep( 350000 * $attempt );

		$r = wp_remote_get(
			$url,
			array(
				'timeout'    => 60,
				'user-agent' => MS_LIVE_UA,
				'headers'    => array( 'Accept' => 'application/json, text/html' ),
			)
		);

		if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
			return wp_remote_retrieve_body( $r );
		}
	}

	return null;
}

/**
 * Resolve an author ID to a real byline.
 *
 * The live site's users endpoint is closed, which is a sensible thing for a
 * newspaper to do, so the name is read from the byline markup on one of that
 * author's own posts and then cached for the rest of the run.
 *
 * @param int    $author_id Live author ID.
 * @param string $post_url  A post by that author.
 * @return array {name:string, slug:string}
 */
function ms_live_author( $author_id, $post_url ) {
	static $cache = array();

	if ( isset( $cache[ $author_id ] ) ) {
		return $cache[ $author_id ];
	}

	$name = '';
	$slug = '';
	$html = ms_live_get( $post_url );

	if ( $html ) {
		if ( preg_match( '~class="entry-author-name"[^>]*>([^<]{1,80})<~i', $html, $m ) ) {
			$name = trim( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
		} elseif ( preg_match( '~class="post-author"[^>]*>\s*By\s+([^<]{1,80})<~i', $html, $m ) ) {
			$name = trim( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
		}
		if ( preg_match( '~/author/([a-z0-9\-]+)/~i', $html, $m ) ) {
			$slug = $m[1];
		}
	}

	if ( '' === $name ) {
		$name = __( 'MySaline Staff', 'mysaline' );
		$slug = $slug ? $slug : 'mysaline-staff';
	}

	$cache[ $author_id ] = array( 'name' => $name, 'slug' => $slug );

	return $cache[ $author_id ];
}

/**
 * Find or create a local user for an imported byline.
 *
 * @param array $author {name, slug}.
 * @return int User ID.
 */
function ms_live_user( $author ) {
	static $map = array();

	$login = sanitize_user( $author['slug'] ? $author['slug'] : sanitize_title( $author['name'] ), true );
	$login = substr( $login, 0, 60 );

	if ( isset( $map[ $login ] ) ) {
		return $map[ $login ];
	}

	$id = username_exists( $login );
	if ( ! $id ) {
		$id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_pass'    => wp_generate_password( 24 ),
				'user_email'   => $login . '@mysaline.invalid',
				'display_name' => $author['name'],
				'first_name'   => trim( (string) strtok( $author['name'], ' ' ) ),
				'role'         => 'author',
			)
		);
	}

	if ( is_wp_error( $id ) ) {
		$id = 1;
	}

	$map[ $login ] = (int) $id;

	return (int) $id;
}

/**
 * Sideload the featured image for an imported post.
 *
 * @param array $embedded The post's _embedded block.
 * @param int   $post_id  Local post.
 * @return int Attachment ID (0 on failure).
 */
function ms_live_featured( $embedded, $post_id ) {
	if ( has_post_thumbnail( $post_id ) ) {
		return (int) get_post_thumbnail_id( $post_id );
	}

	$media = isset( $embedded['wp:featuredmedia'][0] ) ? $embedded['wp:featuredmedia'][0] : null;
	if ( ! $media || empty( $media['source_url'] ) ) {
		return 0;
	}

	// Prefer a large rendition over the untouched original: the full-size file
	// on a news site is regularly several megabytes.
	$url   = (string) $media['source_url'];
	$sizes = isset( $media['media_details']['sizes'] ) ? $media['media_details']['sizes'] : array();
	foreach ( array( 'large', 'medium_large', '1536x1536' ) as $pref ) {
		if ( ! empty( $sizes[ $pref ]['source_url'] ) ) {
			$url = (string) $sizes[ $pref ]['source_url'];
			break;
		}
	}

	usleep( 250000 );
	$att = media_sideload_image( $url, $post_id, null, 'id' );
	if ( is_wp_error( $att ) ) {
		return 0;
	}

	if ( ! empty( $media['alt_text'] ) ) {
		update_post_meta( $att, '_wp_attachment_image_alt', sanitize_text_field( $media['alt_text'] ) );
	}
	set_post_thumbnail( $post_id, $att );

	return (int) $att;
}

/**
 * Assign the real categories and tags a story carried.
 *
 * @param array $embedded The post's _embedded block.
 * @param int   $post_id  Local post.
 */
function ms_live_terms( $embedded, $post_id ) {
	$cats = array();
	$tags = array();

	foreach ( ( isset( $embedded['wp:term'] ) ? $embedded['wp:term'] : array() ) as $group ) {
		foreach ( (array) $group as $term ) {
			if ( empty( $term['name'] ) || empty( $term['taxonomy'] ) ) {
				continue;
			}
			if ( 'category' === $term['taxonomy'] ) {
				$existing = term_exists( $term['name'], 'category' );
				if ( ! $existing ) {
					$existing = wp_insert_term( $term['name'], 'category', array( 'slug' => sanitize_title( $term['slug'] ) ) );
				}
				if ( ! is_wp_error( $existing ) ) {
					$cats[] = (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
				}
			} elseif ( 'post_tag' === $term['taxonomy'] ) {
				$tags[] = $term['name'];
			}
		}
	}

	if ( $cats ) {
		wp_set_post_categories( $post_id, array_unique( $cats ) );
	}
	if ( $tags ) {
		wp_set_post_tags( $post_id, array_slice( array_unique( $tags ), 0, 12 ) );
	}
}

/* ---------------------------------------------------------------------------
 * Run
 * ------------------------------------------------------------------------ */

WP_CLI::log( 'Importing real content from ' . MS_LIVE_ROOT . ' (read-only)…' );

$ms_done    = 0;
$ms_page    = 1;
$ms_per     = 20;
$ms_authors = array();

while ( $ms_done < $ms_wanted ) {
	$body = ms_live_get(
		MS_LIVE_ROOT . '/wp-json/wp/v2/posts?' . http_build_query(
			array(
				'per_page' => min( $ms_per, $ms_wanted - $ms_done ),
				'page'     => $ms_page,
				'_embed'   => 1,
			)
		)
	);

	if ( ! $body ) {
		WP_CLI::warning( 'Could not read page ' . $ms_page . '; stopping.' );
		break;
	}

	$batch = json_decode( $body, true );
	if ( ! is_array( $batch ) || ! $batch ) {
		break;
	}

	foreach ( $batch as $remote ) {
		if ( $ms_done >= $ms_wanted ) {
			break;
		}

		$source_id = (int) $remote['id'];
		$title     = isset( $remote['title']['rendered'] ) ? $remote['title']['rendered'] : '';
		if ( '' === trim( wp_strip_all_tags( $title ) ) ) {
			continue;
		}

		// Idempotency: match on the source ID rather than the title, because
		// headlines repeat across years on a community paper.
		$found = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_ms_import_source_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $source_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$embedded = isset( $remote['_embedded'] ) ? $remote['_embedded'] : array();
		$author   = ms_live_author( (int) $remote['author'], (string) $remote['link'] );
		$ms_authors[ $author['name'] ] = isset( $ms_authors[ $author['name'] ] ) ? $ms_authors[ $author['name'] ] + 1 : 1;

		$postarr = array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => wp_strip_all_tags( $title ),
			'post_name'    => sanitize_title( $remote['slug'] ),
			'post_content' => isset( $remote['content']['rendered'] ) ? $remote['content']['rendered'] : '',
			'post_excerpt' => isset( $remote['excerpt']['rendered'] ) ? wp_strip_all_tags( $remote['excerpt']['rendered'] ) : '',
			'post_date'    => isset( $remote['date'] ) ? str_replace( 'T', ' ', $remote['date'] ) : current_time( 'mysql' ),
			'post_author'  => ms_live_user( $author ),
		);

		if ( $found ) {
			$postarr['ID'] = (int) $found[0];
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			WP_CLI::warning( 'Skipped source #' . $source_id );
			continue;
		}

		update_post_meta( $post_id, '_ms_import_source_id', $source_id );
		update_post_meta( $post_id, '_ms_import_source_url', esc_url_raw( $remote['link'] ) );

		ms_live_terms( $embedded, $post_id );
		ms_live_featured( $embedded, $post_id );

		$ms_done++;
		if ( 0 === $ms_done % 10 ) {
			WP_CLI::log( '  · ' . $ms_done . ' stories imported' );
		}
	}

	$ms_page++;
	if ( $ms_page > 30 ) {
		break;
	}
}

WP_CLI::log( '  · ' . $ms_done . ' stories imported in total' );
arsort( $ms_authors );
foreach ( $ms_authors as $ms_name => $ms_count ) {
	WP_CLI::log( '    byline: ' . $ms_name . ' (' . $ms_count . ')' );
}

/*
 * Featured stories drive the homepage hero, and the imported posts carry no
 * such flag, so the five most recent become the hero set.
 */
$ms_recent = get_posts(
	array(
		'post_type'      => 'post',
		'posts_per_page' => 5,
		'meta_key'       => '_ms_import_source_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	)
);
foreach ( $ms_recent as $ms_post ) {
	update_post_meta( $ms_post->ID, '_ms_featured', '1' );
}
WP_CLI::log( '  · ' . count( $ms_recent ) . ' newest stories flagged as Featured' );

WP_CLI::success( 'Live content imported. Nothing was written to mysaline.com.' );
