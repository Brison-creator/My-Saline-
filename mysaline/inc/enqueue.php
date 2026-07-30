<?php
/**
 * Asset loading (front-end, admin, block editor) and dynamic CSS variables.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end styles & scripts.
 */
function mysaline_enqueue_assets() {
	// Main stylesheet (the theme header + all styles live in style.css).
	wp_enqueue_style( 'mysaline-style', get_stylesheet_uri(), array(), MYSALINE_VERSION );

	// Front-end behaviour (mobile menu, search toggle, ad rotation).
	wp_enqueue_script( 'mysaline-main', MYSALINE_URI . 'assets/js/main.js', array(), MYSALINE_VERSION, true );

	// Threaded comments.
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// Inject the Customizer-driven color palette as CSS variables.
	wp_add_inline_style( 'mysaline-style', mysaline_dynamic_css() );
}
add_action( 'wp_enqueue_scripts', 'mysaline_enqueue_assets' );

/**
 * Build the :root override from Customizer color choices.
 *
 * @return string CSS.
 */
function mysaline_dynamic_css() {
	$primary = get_theme_mod( 'mysaline_color_primary', '#0b2545' );
	$accent  = get_theme_mod( 'mysaline_color_accent', '#c8102e' );

	$primary_dark  = mysaline_adjust_brightness( $primary, -22 );
	$primary_light = mysaline_adjust_brightness( $primary, 30 );
	$accent_dark   = mysaline_adjust_brightness( $accent, -20 );

	$css = ':root{';
	$css .= '--ms-primary:' . sanitize_hex_color( $primary ) . ';';
	$css .= '--ms-primary-dark:' . $primary_dark . ';';
	$css .= '--ms-primary-light:' . $primary_light . ';';
	$css .= '--ms-accent:' . sanitize_hex_color( $accent ) . ';';
	$css .= '--ms-accent-dark:' . $accent_dark . ';';
	$css .= '}';

	return $css;
}

/**
 * Lighten/darken a hex color by a percentage-ish step.
 *
 * @param string $hex   Hex color (#rgb or #rrggbb).
 * @param int    $steps Positive to lighten, negative to darken (-255..255).
 * @return string Hex color.
 */
function mysaline_adjust_brightness( $hex, $steps ) {
	$hex = sanitize_hex_color( $hex );
	if ( ! $hex ) {
		return '#000000';
	}
	$steps = max( -255, min( 255, $steps ) );
	$hex   = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$parts = array();
	for ( $i = 0; $i < 3; $i++ ) {
		$color   = hexdec( substr( $hex, $i * 2, 2 ) );
		$color   = max( 0, min( 255, $color + $steps ) );
		$parts[] = str_pad( dechex( $color ), 2, '0', STR_PAD_LEFT );
	}
	return '#' . implode( '', $parts );
}

/**
 * Admin styles (setup screen + meta boxes).
 *
 * @param string $hook Current admin page.
 */
function mysaline_admin_assets( $hook ) {
	wp_enqueue_style( 'mysaline-admin', MYSALINE_URI . 'assets/css/admin.css', array(), MYSALINE_VERSION );
	// Media picker used by ad/branding meta boxes.
	wp_enqueue_media();
	wp_enqueue_script( 'mysaline-admin', MYSALINE_URI . 'assets/js/admin.js', array( 'jquery' ), MYSALINE_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'mysaline_admin_assets' );

/**
 * Customizer preview live-refresh helper.
 */
function mysaline_customize_preview_js() {
	wp_enqueue_script( 'mysaline-customizer', MYSALINE_URI . 'assets/js/customizer.js', array( 'customize-preview' ), MYSALINE_VERSION, true );
}
add_action( 'customize_preview_init', 'mysaline_customize_preview_js' );

/**
 * Block editor styles so the backend roughly matches the front-end.
 */
function mysaline_editor_assets() {
	wp_enqueue_style( 'mysaline-editor', MYSALINE_URI . 'assets/css/editor.css', array(), MYSALINE_VERSION );
}
add_action( 'enqueue_block_editor_assets', 'mysaline_editor_assets' );
