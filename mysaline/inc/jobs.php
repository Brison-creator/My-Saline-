<?php
/**
 * Local jobs board.
 *
 * A hiring listing has a shelf life, which is the whole reason jobs are a post
 * type with a closing date rather than posts in a category. A jobs page full of
 * positions that were filled in March is worse than having no jobs page: people
 * apply, hear nothing, and stop trusting the listings.
 *
 * So a listing with a closing date in the past drops out of the archive and the
 * widget automatically. It is not deleted — the employer's page still resolves,
 * and it is marked closed rather than pretending to still be open.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a listing's closing date has passed.
 *
 * Compared on date only, in site time: a job closing "today" should still be
 * applied for today, not disappear at midnight UTC.
 *
 * @param int|WP_Post|null $post Job.
 * @return bool
 */
function mysaline_job_is_closed( $post = null ) {
	$post   = get_post( $post );
	$closes = $post ? get_post_meta( $post->ID, '_ms_job_closes', true ) : '';

	if ( ! $closes ) {
		return false;
	}

	return strtotime( $closes . ' 23:59:59' ) < current_time( 'timestamp' );
}

/**
 * Meta query fragment matching listings that are still open.
 *
 * A listing with no closing date runs until it is taken down by hand, so the
 * NOT EXISTS and empty-string branches both have to count as open.
 *
 * @return array
 */
function mysaline_job_open_meta_query() {
	return array(
		'relation' => 'OR',
		array(
			'key'     => '_ms_job_closes',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_ms_job_closes',
			'value'   => '',
			'compare' => '=',
		),
		array(
			'key'     => '_ms_job_closes',
			'value'   => current_time( 'Y-m-d' ),
			'compare' => '>=',
			// Compared as text, not DATE. Dates are stored ISO-8601, so a string
			// comparison already sorts chronologically, and it avoids CAST(...
			// AS DATE) — which MySQL understands but SQLite does not, silently
			// matching nothing at all.
			'type'    => 'CHAR',
		),
	);
}

/**
 * Hide closed listings from the jobs archive, and float featured ones.
 *
 * @param WP_Query $query Query.
 */
function mysaline_job_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( ! $query->is_post_type_archive( 'ms_job' ) && ! $query->is_tax( 'ms_job_cat' ) ) {
		return;
	}

	$query->set( 'meta_query', mysaline_job_open_meta_query() );
	/*
	 * Ordered by date only, deliberately. Sorting by _ms_job_featured requires a
	 * meta_key, which WordPress joins to postmeta with an INNER JOIN — so every
	 * listing whose author never ticked the featured box vanishes from the
	 * board. Featured listings are lifted in PHP instead, where the absence of a
	 * value simply means "not featured".
	 */
	$query->set( 'orderby', 'date' );
	$query->set( 'order', 'DESC' );
	$query->set( 'posts_per_page', 20 );
}
add_action( 'pre_get_posts', 'mysaline_job_archive_query' );

/**
 * Open listings, newest first, featured first.
 *
 * @param int $number How many.
 * @return WP_Post[]
 */
function mysaline_get_open_jobs( $number = 5 ) {
	$jobs = get_posts(
		array(
			'post_type'      => 'ms_job',
			// Over-fetch so the featured lift below has something to reorder.
			'posts_per_page' => max( (int) $number * 3, 12 ),
			'meta_query'     => mysaline_job_open_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	// Stable partition: featured first, each group still newest-first.
	$featured = array();
	$rest     = array();
	foreach ( $jobs as $job ) {
		if ( get_post_meta( $job->ID, '_ms_job_featured', true ) ) {
			$featured[] = $job;
		} else {
			$rest[] = $job;
		}
	}

	return array_slice( array_merge( $featured, $rest ), 0, (int) $number );
}

/**
 * Human label for a stored employment type.
 *
 * @param string $type Stored value.
 * @return string
 */
function mysaline_job_type_label( $type ) {
	$choices = function_exists( 'mysaline_job_type_choices' ) ? mysaline_job_type_choices() : array();

	return isset( $choices[ $type ] ) ? $choices[ $type ] : '';
}

/**
 * The compact meta line shared by the job card and the single listing.
 *
 * @param int|WP_Post|null $post Job.
 * @return string
 */
function mysaline_job_meta_line( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}

	$bits = array();

	$employer = get_post_meta( $post->ID, '_ms_job_employer', true );
	if ( $employer ) {
		$bits[] = esc_html( $employer );
	}

	$location = get_post_meta( $post->ID, '_ms_job_location', true );
	if ( $location ) {
		$bits[] = esc_html( $location );
	}

	$type = mysaline_job_type_label( get_post_meta( $post->ID, '_ms_job_type', true ) );
	if ( $type ) {
		$bits[] = esc_html( $type );
	}

	$pay = get_post_meta( $post->ID, '_ms_job_pay', true );
	if ( $pay ) {
		$bits[] = '<strong>' . esc_html( $pay ) . '</strong>';
	}

	return implode( ' <span aria-hidden="true">·</span> ', $bits );
}

/**
 * Structured data so listings can surface in Google Jobs.
 *
 * Emitted only for an open listing with an employer: an incomplete JobPosting
 * is worse than none, because it can be flagged rather than simply ignored.
 */
function mysaline_job_schema() {
	if ( ! is_singular( 'ms_job' ) || mysaline_seo_plugin_active() ) {
		return;
	}

	$post     = get_post();
	$employer = get_post_meta( $post->ID, '_ms_job_employer', true );
	if ( ! $employer || mysaline_job_is_closed( $post ) ) {
		return;
	}

	$data = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'JobPosting',
		'title'       => get_the_title(),
		'description' => wp_strip_all_tags( get_the_content() ),
		'datePosted'  => get_the_date( DATE_W3C ),
		'hiringOrganization' => array(
			'@type' => 'Organization',
			'name'  => $employer,
		),
	);

	$closes = get_post_meta( $post->ID, '_ms_job_closes', true );
	if ( $closes ) {
		$data['validThrough'] = gmdate( DATE_W3C, strtotime( $closes . ' 23:59:59' ) );
	}

	$location = get_post_meta( $post->ID, '_ms_job_location', true );
	if ( $location ) {
		$data['jobLocation'] = array(
			'@type'   => 'Place',
			'address' => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => $location,
				'addressRegion'   => 'AR',
				'addressCountry'  => 'US',
			),
		);
	}

	$map = array(
		'full-time'  => 'FULL_TIME',
		'part-time'  => 'PART_TIME',
		'seasonal'   => 'TEMPORARY',
		'contract'   => 'CONTRACTOR',
		'internship' => 'INTERN',
		'volunteer'  => 'VOLUNTEER',
	);
	$type = get_post_meta( $post->ID, '_ms_job_type', true );
	if ( isset( $map[ $type ] ) ) {
		$data['employmentType'] = $map[ $type ];
	}

	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>' . "\n";
}
add_action( 'wp_head', 'mysaline_job_schema', 20 );
