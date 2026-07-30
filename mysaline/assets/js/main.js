/**
 * MySaline front-end behaviour.
 * Vanilla JS, no dependencies. Handles the mobile menu, search panel and
 * submenu accessibility.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		// Mobile menu toggle.
		var menuToggle = document.querySelector( '.ms-menu-toggle' );
		var menu = document.getElementById( 'ms-primary-menu' );
		if ( menuToggle && menu ) {
			menuToggle.addEventListener( 'click', function () {
				var open = menu.classList.toggle( 'is-open' );
				menuToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			} );
		}

		// Search panel toggle.
		var searchToggle = document.querySelector( '.ms-search-toggle' );
		var searchPanel = document.getElementById( 'ms-search-panel' );
		if ( searchToggle && searchPanel ) {
			searchToggle.addEventListener( 'click', function () {
				var open = searchPanel.classList.toggle( 'is-open' );
				searchToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				if ( open ) {
					var field = searchPanel.querySelector( 'input[type="search"]' );
					if ( field ) {
						field.focus();
					}
				}
			} );
		}

		// Allow keyboard users to open submenus on focus (CSS handles hover).
		var parents = document.querySelectorAll( '.ms-menu .menu-item-has-children > a' );
		parents.forEach( function ( link ) {
			link.addEventListener( 'focus', function () {
				var li = link.parentNode;
				if ( li ) {
					li.classList.add( 'is-focus' );
				}
			} );
		} );

		// Close mobile menu when a link is chosen.
		if ( menu ) {
			menu.addEventListener( 'click', function ( e ) {
				if ( e.target.tagName === 'A' && window.innerWidth <= 782 && ! e.target.parentNode.classList.contains( 'menu-item-has-children' ) ) {
					menu.classList.remove( 'is-open' );
				}
			} );
		}
	} );
}() );
