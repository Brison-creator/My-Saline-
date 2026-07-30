<?php
/**
 * Breaking-news bar data helpers.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the breaking bar should render.
 *
 * @return bool
 */
function mysaline_has_breaking() {
	if ( ! get_theme_mod( 'mysaline_breaking_enable', false ) ) {
		return false;
	}
	return ! empty( mysaline_get_breaking_items() );
}

/**
 * Get the breaking-news items as an array of { text, url }.
 *
 * @return array
 */
function mysaline_get_breaking_items() {
	$source = get_theme_mod( 'mysaline_breaking_source', 'manual' );
	$items  = array();

	if ( 'category' === $source ) {
		$cat = (int) get_theme_mod( 'mysaline_breaking_cat', 0 );
		$args = array(
			'posts_per_page'         => 5,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'post_status'            => 'publish',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		if ( $cat ) {
			$args['cat'] = $cat;
		}
		$q = new WP_Query( $args );
		foreach ( $q->posts as $p ) {
			$items[] = array(
				'text' => get_the_title( $p ),
				'url'  => get_permalink( $p ),
			);
		}
		wp_reset_postdata();
	} else {
		$text = get_theme_mod( 'mysaline_breaking_text', '' );
		if ( $text ) {
			$items[] = array(
				'text' => $text,
				'url'  => get_theme_mod( 'mysaline_breaking_link', '' ),
			);
		}
	}

	return $items;
}
