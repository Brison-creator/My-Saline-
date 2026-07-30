<?php
/**
 * Custom post types & taxonomies.
 *
 * These add NEW structured content types. They never touch existing posts,
 * pages, categories, tags, authors or their URLs. Rewrite slugs are filterable
 * so the owner can change them without editing code if they ever collide with
 * an existing page.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register all custom post types.
 */
function mysaline_register_post_types() {

	/* ---- Obituaries ------------------------------------------------- */
	register_post_type(
		'ms_obituary',
		array(
			'labels'        => mysaline_cpt_labels( __( 'Obituary', 'mysaline' ), __( 'Obituaries', 'mysaline' ) ),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-heart',
			'menu_position' => 22,
			'rewrite'       => array( 'slug' => apply_filters( 'mysaline_obituary_slug', 'obituaries' ), 'with_front' => false ),
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ),
			'show_in_rest'  => true,
		)
	);

	/* ---- Community events ------------------------------------------- */
	register_post_type(
		'ms_event',
		array(
			'labels'        => mysaline_cpt_labels( __( 'Event', 'mysaline' ), __( 'Community Events', 'mysaline' ) ),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-calendar-alt',
			'menu_position' => 23,
			'rewrite'       => array( 'slug' => apply_filters( 'mysaline_event_slug', 'events' ), 'with_front' => false ),
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ),
			'show_in_rest'  => true,
		)
	);

	/* ---- Business directory ----------------------------------------- */
	register_post_type(
		'ms_business',
		array(
			'labels'        => mysaline_cpt_labels( __( 'Business', 'mysaline' ), __( 'Businesses', 'mysaline' ) ),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-store',
			'menu_position' => 24,
			'rewrite'       => array( 'slug' => apply_filters( 'mysaline_business_slug', 'businesses' ), 'with_front' => false ),
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'show_in_rest'  => true,
		)
	);

	/* ---- Advertisements (not a public archive) ---------------------- */
	register_post_type(
		'ms_ad',
		array(
			'labels'              => mysaline_cpt_labels( __( 'Advertisement', 'mysaline' ), __( 'Advertisements', 'mysaline' ) ),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-megaphone',
			'menu_position'       => 25,
			'supports'            => array( 'title', 'thumbnail' ),
			'show_in_rest'        => false,
		)
	);
}
add_action( 'init', 'mysaline_register_post_types' );

/**
 * Register taxonomies for the directory / events.
 */
function mysaline_register_taxonomies() {
	register_taxonomy(
		'ms_business_cat',
		'ms_business',
		array(
			'labels'            => mysaline_tax_labels( __( 'Business Category', 'mysaline' ), __( 'Business Categories', 'mysaline' ) ),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => apply_filters( 'mysaline_business_cat_slug', 'business-category' ) ),
		)
	);

	register_taxonomy(
		'ms_event_cat',
		'ms_event',
		array(
			'labels'            => mysaline_tax_labels( __( 'Event Category', 'mysaline' ), __( 'Event Categories', 'mysaline' ) ),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => apply_filters( 'mysaline_event_cat_slug', 'event-category' ) ),
		)
	);
}
add_action( 'init', 'mysaline_register_taxonomies' );

/**
 * Generate a full CPT labels array from singular/plural names.
 *
 * @param string $singular Singular label.
 * @param string $plural   Plural label.
 * @return array
 */
function mysaline_cpt_labels( $singular, $plural ) {
	return array(
		'name'                  => $plural,
		'singular_name'         => $singular,
		/* translators: %s: singular post type name. */
		'add_new_item'          => sprintf( __( 'Add New %s', 'mysaline' ), $singular ),
		/* translators: %s: singular post type name. */
		'edit_item'             => sprintf( __( 'Edit %s', 'mysaline' ), $singular ),
		/* translators: %s: singular post type name. */
		'new_item'              => sprintf( __( 'New %s', 'mysaline' ), $singular ),
		/* translators: %s: singular post type name. */
		'view_item'             => sprintf( __( 'View %s', 'mysaline' ), $singular ),
		/* translators: %s: plural post type name. */
		'view_items'            => sprintf( __( 'View %s', 'mysaline' ), $plural ),
		/* translators: %s: plural post type name. */
		'search_items'          => sprintf( __( 'Search %s', 'mysaline' ), $plural ),
		/* translators: %s: plural post type name (lowercased). */
		'not_found'             => sprintf( __( 'No %s found', 'mysaline' ), strtolower( $plural ) ),
		/* translators: %s: plural post type name (lowercased). */
		'not_found_in_trash'    => sprintf( __( 'No %s found in Trash', 'mysaline' ), strtolower( $plural ) ),
		'all_items'             => $plural,
		'menu_name'             => $plural,
		'name_admin_bar'        => $singular,
		'featured_image'        => __( 'Photo', 'mysaline' ),
		'set_featured_image'    => __( 'Set photo', 'mysaline' ),
		'remove_featured_image' => __( 'Remove photo', 'mysaline' ),
		'use_featured_image'    => __( 'Use as photo', 'mysaline' ),
	);
}

/**
 * Generate taxonomy labels.
 *
 * @param string $singular Singular label.
 * @param string $plural   Plural label.
 * @return array
 */
function mysaline_tax_labels( $singular, $plural ) {
	return array(
		'name'          => $plural,
		'singular_name' => $singular,
		/* translators: %s: taxonomy singular name. */
		'add_new_item'  => sprintf( __( 'Add New %s', 'mysaline' ), $singular ),
		/* translators: %s: taxonomy singular name. */
		'edit_item'     => sprintf( __( 'Edit %s', 'mysaline' ), $singular ),
		'menu_name'     => $plural,
		/* translators: %s: taxonomy plural name. */
		'search_items'  => sprintf( __( 'Search %s', 'mysaline' ), $plural ),
		/* translators: %s: taxonomy plural name. */
		'all_items'     => sprintf( __( 'All %s', 'mysaline' ), $plural ),
	);
}

/**
 * Flush rewrite rules once when the theme is activated so CPT URLs work.
 */
function mysaline_rewrite_flush() {
	mysaline_register_post_types();
	mysaline_register_taxonomies();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'mysaline_rewrite_flush' );

/**
 * Include Events & Businesses in the main search alongside posts/pages.
 *
 * @param WP_Query $query The query.
 */
function mysaline_search_include_cpts( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}
	$query->set( 'post_type', array( 'post', 'page', 'ms_event', 'ms_business', 'ms_obituary' ) );
}
add_action( 'pre_get_posts', 'mysaline_search_include_cpts' );

/**
 * Order the Events archive by upcoming event date.
 *
 * @param WP_Query $query The query.
 */
function mysaline_events_archive_order( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_post_type_archive( 'ms_event' ) || $query->is_tax( 'ms_event_cat' ) ) {
		$query->set( 'meta_key', '_ms_event_start' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'ASC' );
	}
	if ( $query->is_post_type_archive( 'ms_business' ) || $query->is_tax( 'ms_business_cat' ) ) {
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'mysaline_events_archive_order' );
