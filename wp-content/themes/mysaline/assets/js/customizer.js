/**
 * Customizer live preview (postMessage) for instant, no-refresh updates on
 * site title, tagline and brand colors.
 */
( function ( $ ) {
	'use strict';

	if ( typeof wp === 'undefined' || ! wp.customize ) {
		return;
	}

	// Site title.
	wp.customize( 'blogname', function ( value ) {
		value.bind( function ( to ) {
			$( '.ms-brand__title a' ).text( to );
		} );
	} );

	// Tagline.
	wp.customize( 'blogdescription', function ( value ) {
		value.bind( function ( to ) {
			$( '.ms-brand__tagline' ).text( to );
		} );
	} );

	// Helper to write/update a :root CSS variable.
	function setVar( name, val ) {
		document.documentElement.style.setProperty( name, val );
	}

	wp.customize( 'mysaline_color_primary', function ( value ) {
		value.bind( function ( to ) {
			setVar( '--ms-primary', to );
		} );
	} );

	wp.customize( 'mysaline_color_accent', function ( value ) {
		value.bind( function ( to ) {
			setVar( '--ms-accent', to );
		} );
	} );
}( jQuery ) );
