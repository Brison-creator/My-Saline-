<?php
/**
 * Community event query & date helpers.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query upcoming events (start date today or later), soonest first.
 *
 * @param int $limit Number to fetch.
 * @return WP_Query
 */
function mysaline_upcoming_events( $limit = 5 ) {
	$today = current_time( 'Y-m-d' );

	return new WP_Query(
		array(
			'post_type'      => 'ms_event',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $limit,
			'meta_key'       => '_ms_event_start', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => '_ms_event_start',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
				array(
					'key'     => '_ms_event_end',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		)
	);
}

/**
 * Return a { day, mon, year } array for an event's start date.
 *
 * @param int|WP_Post|null $post Optional post.
 * @return array|null
 */
function mysaline_event_date_parts( $post = null ) {
	$post  = get_post( $post );
	$start = $post ? get_post_meta( $post->ID, '_ms_event_start', true ) : '';
	if ( ! $start ) {
		return null;
	}
	$ts = strtotime( $start );
	if ( ! $ts ) {
		return null;
	}
	return array(
		'day'  => gmdate( 'j', $ts ),
		'mon'  => gmdate( 'M', $ts ),
		'year' => gmdate( 'Y', $ts ),
		'full' => date_i18n( get_option( 'date_format' ), $ts ),
	);
}

/**
 * Human-readable event date range.
 *
 * @param int|WP_Post|null $post Optional.
 * @return string
 */
function mysaline_event_date_range( $post = null ) {
	$post  = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$start = get_post_meta( $post->ID, '_ms_event_start', true );
	$end   = get_post_meta( $post->ID, '_ms_event_end', true );
	$fmt   = get_option( 'date_format' );

	if ( ! $start ) {
		return '';
	}
	$out = date_i18n( $fmt, strtotime( $start ) );
	if ( $end && $end !== $start ) {
		$out .= ' – ' . date_i18n( $fmt, strtotime( $end ) );
	}
	return $out;
}
