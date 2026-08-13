<?php
/**
 * Plugin Name: MySaline Dev — offline embeds
 * Description: Stops rendering from blocking on third-party oEmbed lookups.
 *
 * Imported stories carry Facebook, YouTube and X links in their bodies. When
 * WordPress renders one it performs oEmbed provider discovery — an outbound
 * HTTP request per embed, per uncached post. On the live site those results are
 * cached and it never shows; on a fresh preview build every one is a cache
 * miss, and behind a restrictive network they hang rather than fail fast. A
 * single stuck render then blocks the whole development server.
 *
 * So remote embed resolution is short-circuited here and the URL is left as a
 * plain link. Dev-only: this lives in dev/ and never ships in the theme.
 *
 * @package MySaline\Dev
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'pre_oembed_result',
	static function ( $result, $url ) {
		return sprintf(
			'<p class="ms-embed-fallback"><a href="%1$s" rel="nofollow noopener">%1$s</a></p>',
			esc_url( $url )
		);
	},
	10,
	2
);

// Belt and braces: no HTTP request may leave the render path for a provider.
add_filter(
	'oembed_remote_get_args',
	static function ( $args ) {
		$args['timeout'] = 1;
		return $args;
	}
);

add_filter( 'embed_oembed_discover', '__return_false' );
