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

	// Print styles: obituaries and event details get printed, so they get a
	// stylesheet rather than whatever the browser improvises.
	wp_enqueue_style( 'mysaline-print', MYSALINE_URI . 'assets/css/print.css', array( 'mysaline-style' ), MYSALINE_VERSION, 'print' );

	// Front-end behaviour (mobile menu, search toggle, ad rotation).
	wp_enqueue_script( 'mysaline-main', MYSALINE_URI . 'assets/js/main.js', array(), MYSALINE_VERSION, true );

	// Threaded comments.
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// Favorites ballot script — only on pages that actually render the ballot.
	if ( mysaline_has_favorites_ballot() ) {
		wp_enqueue_script( 'mysaline-favorites', MYSALINE_URI . 'assets/js/favorites.js', array(), MYSALINE_VERSION, true );
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
	$primary = get_theme_mod( 'mysaline_color_primary', '#0f2b4e' );
	$accent  = get_theme_mod( 'mysaline_color_accent', '#b2452f' );

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

/**
 * Stop WordPress rewriting emoji as remote images.
 *
 * The theme invites emoji as section-hub and quick-link icons, which is the
 * simplest icon picker an editor can be given. Core's emoji script replaces
 * each one with an <img> served from s.w.org — so every icon becomes a
 * third-party request that fails behind a firewall, in an offline preview or
 * on a static export, leaving a broken-image glyph in the navigation.
 *
 * Every browser the site supports renders emoji natively, so the replacement
 * buys nothing and costs a script, a stylesheet and a DNS prefetch on every
 * page load.
 */
function mysaline_disable_emoji_images() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'mysaline_disable_emoji_images' );

/**
 * Drop the emoji DNS prefetch left behind once the script is gone.
 *
 * @param array  $urls          Prefetch URLs.
 * @param string $relation_type Link relation.
 * @return array
 */
function mysaline_remove_emoji_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' !== $relation_type ) {
		return $urls;
	}

	$emoji = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/' );

	return array_filter(
		$urls,
		static function ( $url ) use ( $emoji ) {
			return ! ( is_string( $url ) && false !== strpos( $url, (string) wp_parse_url( $emoji, PHP_URL_HOST ) ) );
		}
	);
}
add_filter( 'wp_resource_hints', 'mysaline_remove_emoji_prefetch', 10, 2 );
