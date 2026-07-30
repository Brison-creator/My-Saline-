<?php
/**
 * MySaline theme bootstrap.
 *
 * Loads all theme modules. Each concern lives in its own file under /inc so the
 * theme stays maintainable. Nothing here talks to the network or to the live
 * site — everything is standard, self-contained WordPress code.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Theme version. Bumped on release; also used to cache-bust assets.
 */
if ( ! defined( 'MYSALINE_VERSION' ) ) {
	define( 'MYSALINE_VERSION', '1.0.0' );
}

define( 'MYSALINE_DIR', trailingslashit( get_template_directory() ) );
define( 'MYSALINE_URI', trailingslashit( get_template_directory_uri() ) );

/**
 * Load a module from the /inc directory.
 *
 * @param string $file Relative path inside /inc (without extension).
 */
function mysaline_require( $file ) {
	$path = MYSALINE_DIR . 'inc/' . $file . '.php';
	if ( file_exists( $path ) ) {
		require_once $path;
	}
}

$mysaline_modules = array(
	'theme-setup',        // add_theme_support, menus, image sizes, sidebars.
	'enqueue',            // Front-end + admin + editor assets.
	'template-tags',      // Reusable presentation helpers.
	'template-functions', // Body classes, excerpt tweaks, misc hooks.
	'customizer',         // Branding, colors, social, newsletter, breaking news, homepage, ads.
	'post-types',         // Registers Obituary, Event, Business, Advertisement CPTs + taxonomies.
	'meta-boxes',         // Featured-story flag, CPT detail fields.
	'widgets',            // Widget areas + custom widgets (ads, newsletter, social, spotlights).
	'ads',                // Advertisement zone helpers.
	'breaking-news',      // Breaking-news data helpers.
	'events',             // Event query/date helpers.
	'homepage',           // Featured hero + configurable homepage sections.
	'favorites',          // Saline County Favorites voting ballot.
);

foreach ( $mysaline_modules as $mysaline_module ) {
	mysaline_require( $mysaline_module );
}
unset( $mysaline_module );

/**
 * Content width for embeds / oEmbed. Standard WordPress global.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 800;
}
