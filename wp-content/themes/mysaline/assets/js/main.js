/**
 * MySaline front-end behaviour.
 * Vanilla JS, no dependencies. Handles the mobile menu, search panel and
 * submenu accessibility.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var MOBILE = 782;
		var menuToggle = document.querySelector( '.ms-menu-toggle' );
		var panel = document.getElementById( 'ms-nav-panel' );
		var menu = document.getElementById( 'ms-primary-menu' );
		var scrim = document.querySelector( '.ms-nav__scrim' );

		function isMobile() {
			return window.innerWidth <= MOBILE;
		}

		function closeMenu( refocus ) {
			if ( ! panel ) {
				return;
			}
			panel.classList.remove( 'is-open' );
			document.body.classList.remove( 'ms-nav-open' );
			if ( scrim ) {
				scrim.hidden = true;
			}
			if ( menuToggle ) {
				menuToggle.setAttribute( 'aria-expanded', 'false' );
				if ( refocus ) {
					menuToggle.focus();
				}
			}
		}

		function openMenu() {
			if ( ! panel ) {
				return;
			}
			panel.classList.add( 'is-open' );
			// Locking the body stops the page behind the drawer scrolling with it,
			// which on a phone reads as the drawer being broken.
			document.body.classList.add( 'ms-nav-open' );
			if ( scrim ) {
				scrim.hidden = false;
			}
			if ( menuToggle ) {
				menuToggle.setAttribute( 'aria-expanded', 'true' );
			}
		}

		if ( menuToggle && panel ) {
			menuToggle.addEventListener( 'click', function () {
				if ( panel.classList.contains( 'is-open' ) ) {
					closeMenu( false );
				} else {
					openMenu();
				}
			} );
		}

		if ( scrim ) {
			scrim.addEventListener( 'click', function () {
				closeMenu( false );
			} );
		}

		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && panel && panel.classList.contains( 'is-open' ) ) {
				closeMenu( true );
			}
		} );

		// A drawer left open while the window grows into the desktop layout would
		// otherwise keep the body locked with no visible way to close it.
		window.addEventListener( 'resize', function () {
			if ( ! isMobile() ) {
				closeMenu( false );
			}
		} );

		/*
		 * Accordion.
		 *
		 * Every submenu used to be permanently expanded on mobile, so opening the
		 * menu meant scrolling past thirty-one links to reach the last section.
		 * Each parent now gets its own disclosure button, kept separate from the
		 * link so tapping "News" still goes to News and only the chevron expands.
		 *
		 * The buttons are added here rather than in the markup so that without
		 * JavaScript the CSS leaves every submenu open and nothing is unreachable.
		 */
		if ( menu ) {
			menu.classList.add( 'is-accordion' );

			menu.querySelectorAll( '.menu-item-has-children' ).forEach( function ( li, i ) {
				var link = li.querySelector( ':scope > a' );
				var sub = li.querySelector( ':scope > .sub-menu' );
				if ( ! link || ! sub ) {
					return;
				}

				var id = sub.id || 'ms-submenu-' + i;
				sub.id = id;

				var btn = document.createElement( 'button' );
				btn.className = 'ms-submenu-toggle';
				btn.type = 'button';
				btn.setAttribute( 'aria-expanded', 'false' );
				btn.setAttribute( 'aria-controls', id );
				btn.innerHTML =
					'<span class="screen-reader-text">' +
					( window.mysalineNavStrings ? window.mysalineNavStrings.expand : 'Show submenu' ) +
					' ' + link.textContent.trim() + '</span>' +
					'<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">' +
					'<path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.2" ' +
					'stroke-linecap="round" stroke-linejoin="round"/></svg>';

				btn.addEventListener( 'click', function () {
					var open = li.classList.toggle( 'is-open' );
					btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				} );

				link.insertAdjacentElement( 'afterend', btn );
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

		// Dismissable sticky mobile ad — stays closed for the session.
		var sticky = document.getElementById( 'ms-ad-sticky' );
		if ( sticky ) {
			try {
				if ( window.sessionStorage.getItem( 'msAdStickyClosed' ) === '1' ) {
					sticky.classList.add( 'is-closed' );
				}
			} catch ( e ) {}
			var stickyClose = sticky.querySelector( '.ms-ad-sticky__close' );
			if ( stickyClose ) {
				stickyClose.addEventListener( 'click', function () {
					sticky.classList.add( 'is-closed' );
					try {
						window.sessionStorage.setItem( 'msAdStickyClosed', '1' );
					} catch ( e ) {}
				} );
			}
		}

		// Close the drawer when a destination is chosen.
		if ( panel ) {
			panel.addEventListener( 'click', function ( e ) {
				var link = e.target.closest ? e.target.closest( 'a' ) : null;
				if ( link && isMobile() ) {
					closeMenu( false );
				}
			} );
		}
	} );
}() );
