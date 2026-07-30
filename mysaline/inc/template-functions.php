<?php
/**
 * Non-visual hooks: body classes, excerpt behaviour, misc adjustments.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add helpful body classes for layout decisions.
 *
 * @param array $classes Body classes.
 * @return array
 */
function mysaline_body_classes( $classes ) {
	if ( ! is_active_sidebar( 'sidebar-main' ) && ! is_front_page() ) {
		$classes[] = 'ms-full-width';
	}
	if ( is_singular() && has_post_thumbnail() ) {
		$classes[] = 'ms-has-thumbnail';
	}
	$classes[] = 'ms-theme';
	return $classes;
}
add_filter( 'body_class', 'mysaline_body_classes' );

/**
 * Custom excerpt "read more" string.
 *
 * @return string
 */
function mysaline_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'mysaline_excerpt_more' );

/**
 * Excerpt length.
 *
 * @return int
 */
function mysaline_excerpt_length() {
	return 28;
}
add_filter( 'excerpt_length', 'mysaline_excerpt_length' );

/**
 * Add a wrapper + lazy loading to content images that lack it.
 * Kept light-touch to preserve existing content markup.
 *
 * @param array $attr Attributes.
 * @return array
 */
function mysaline_lazy_content_images( $attr ) {
	if ( ! isset( $attr['loading'] ) ) {
		$attr['loading'] = 'lazy';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'mysaline_lazy_content_images' );

/**
 * Pingback header for singular pages.
 */
function mysaline_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'mysaline_pingback_header' );

/**
 * Fallback for the primary menu before the owner builds one in Appearance → Menus.
 * Shows top-level pages so the site is never left without navigation.
 */
function mysaline_primary_menu_fallback() {
	echo '<ul id="ms-primary-menu" class="ms-menu">';
	echo '<li class="' . ( is_front_page() ? 'current-menu-item' : '' ) . '"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'mysaline' ) . '</a></li>';
	wp_list_pages(
		array(
			'title_li' => '',
			'depth'    => 1,
			'number'   => 6,
		)
	);
	echo '</ul>';
}

/**
 * Set a wider content width on the full-width page template.
 */
function mysaline_theme_class_content_width() {
	if ( is_page_template( 'templates/full-width.php' ) ) {
		$GLOBALS['content_width'] = 1100;
	}
}
add_action( 'template_redirect', 'mysaline_theme_class_content_width' );
