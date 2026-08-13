/**
 * Saline County Favorites ballot behaviour.
 *
 * Makes a 155-category ballot finishable:
 *   - instant search across category names
 *   - section tabs
 *   - live progress toward the prize threshold (categories + sections)
 *   - "hide ones I've done" to shrink the list as you go
 *   - autosave picks to localStorage so nothing is lost
 *
 * Vanilla JS, no dependencies.
 */
( function () {
	'use strict';

	var root = document.getElementById( 'ms-fav' );
	if ( ! root ) {
		return;
	}

	var minCats = parseInt( root.getAttribute( 'data-min-cats' ), 10 ) || 0;
	var minSections = parseInt( root.getAttribute( 'data-min-sections' ), 10 ) || 0;
	var year = root.getAttribute( 'data-year' ) || 'x';
	var storeKey = 'mysaline_favorites_' + year;

	var cats = Array.prototype.slice.call( root.querySelectorAll( '[data-fav-cat]' ) );
	var sections = Array.prototype.slice.call( root.querySelectorAll( '[data-fav-section]' ) );
	var chips = Array.prototype.slice.call( root.querySelectorAll( '[data-fav-chip]' ) );
	var tabs = Array.prototype.slice.call( root.querySelectorAll( '[data-fav-tab]' ) );

	var searchEl = root.querySelector( '[data-fav-search]' );
	var hideDoneEl = root.querySelector( '[data-fav-hide-done]' );
	var votedEl = root.querySelector( '[data-fav-voted]' );
	var fillEl = root.querySelector( '[data-fav-fill]' );
	var meterEl = root.querySelector( '[data-fav-meter]' );
	var qualifyEl = root.querySelector( '[data-fav-qualify]' );
	var savedEl = root.querySelector( '[data-fav-saved]' );
	var noResultsEl = root.querySelector( '[data-fav-noresults]' );
	var submitCountEl = root.querySelector( '[data-fav-submit-count]' );

	var activeTab = 'all';

	/* ---------------------------------------------------------------- store */

	function readStore() {
		try {
			return JSON.parse( window.localStorage.getItem( storeKey ) ) || {};
		} catch ( e ) {
			return {};
		}
	}

	function writeStore( data ) {
		try {
			window.localStorage.setItem( storeKey, JSON.stringify( data ) );
			if ( savedEl && Object.keys( data ).length ) {
				savedEl.hidden = false;
			}
		} catch ( e ) {
			/* Storage unavailable (private mode) — voting still works. */
		}
	}

	/** Restore saved picks into the radio inputs. */
	function restore() {
		var saved = readStore();
		Object.keys( saved ).forEach( function ( catId ) {
			var value = saved[ catId ];
			var inputs = root.querySelectorAll( 'input[name="vote[' + catId + ']"]' );
			Array.prototype.forEach.call( inputs, function ( input ) {
				if ( input.value === value ) {
					input.checked = true;
				}
			} );
		} );
	}

	/* ------------------------------------------------------------- progress */

	function votedCatIds() {
		return cats.filter( function ( cat ) {
			return !! cat.querySelector( 'input[type="radio"]:checked' );
		} );
	}

	function updateProgress() {
		var done = votedCatIds();
		var count = done.length;

		// Which sections have at least one vote.
		var sectionsDone = {};
		done.forEach( function ( cat ) {
			sectionsDone[ cat.getAttribute( 'data-fav-cat-section' ) ] = true;
		} );
		var sectionCount = Object.keys( sectionsDone ).length;

		if ( votedEl ) {
			votedEl.textContent = String( count );
		}

		var pct = minCats > 0 ? Math.min( 100, ( count / minCats ) * 100 ) : 0;
		if ( fillEl ) {
			fillEl.style.width = pct + '%';
		}
		if ( meterEl ) {
			meterEl.setAttribute( 'aria-valuenow', String( count ) );
		}

		// Section chips light up as each section gets a vote.
		chips.forEach( function ( chip ) {
			var slug = chip.getAttribute( 'data-fav-chip' );
			chip.classList.toggle( 'is-done', !! sectionsDone[ slug ] );
		} );

		// Per-category done state (drives the ✓ and hide-done filter).
		cats.forEach( function ( cat ) {
			cat.classList.toggle( 'is-done', !! cat.querySelector( 'input[type="radio"]:checked' ) );
		} );

		var qualifies = count >= minCats && sectionCount >= minSections;
		if ( qualifyEl ) {
			qualifyEl.hidden = ! qualifies;
		}
		root.classList.toggle( 'is-qualified', qualifies );

		if ( submitCountEl ) {
			submitCountEl.textContent = count ? '(' + count + ')' : '';
		}

		if ( hideDoneEl && hideDoneEl.checked ) {
			applyFilters();
		}
	}

	/* --------------------------------------------------------------- filters */

	function applyFilters() {
		var term = searchEl ? searchEl.value.trim().toLowerCase() : '';
		var hideDone = hideDoneEl ? hideDoneEl.checked : false;
		var visible = 0;

		cats.forEach( function ( cat ) {
			var text = cat.getAttribute( 'data-fav-search-text' ) || '';
			var slug = cat.getAttribute( 'data-fav-cat-section' );
			var isDone = cat.classList.contains( 'is-done' );

			var show = true;
			if ( term && text.indexOf( term ) === -1 ) {
				show = false;
			}
			if ( show && activeTab !== 'all' && slug !== activeTab ) {
				show = false;
			}
			if ( show && hideDone && isDone ) {
				show = false;
			}

			cat.hidden = ! show;
			if ( show ) {
				visible++;
			}
		} );

		// Hide a section heading when nothing under it is visible.
		sections.forEach( function ( section ) {
			var any = !! section.querySelector( '[data-fav-cat]:not([hidden])' );
			section.hidden = ! any;
		} );

		if ( noResultsEl ) {
			noResultsEl.hidden = visible !== 0;
		}
	}

	/* ---------------------------------------------------------------- events */

	root.addEventListener( 'change', function ( e ) {
		var target = e.target;

		if ( target.type === 'radio' && target.name.indexOf( 'vote[' ) === 0 ) {
			var catId = target.name.replace( 'vote[', '' ).replace( ']', '' );
			var store = readStore();
			store[ catId ] = target.value;
			writeStore( store );
			updateProgress();
		}

		if ( target === hideDoneEl ) {
			applyFilters();
		}
	} );

	root.addEventListener( 'click', function ( e ) {
		var clear = e.target.closest ? e.target.closest( '[data-fav-clear]' ) : null;
		if ( clear ) {
			var catId = clear.getAttribute( 'data-fav-clear' );
			var inputs = root.querySelectorAll( 'input[name="vote[' + catId + ']"]' );
			Array.prototype.forEach.call( inputs, function ( input ) {
				input.checked = false;
			} );
			var store = readStore();
			delete store[ catId ];
			writeStore( store );
			updateProgress();
			applyFilters();
			return;
		}

		var tab = e.target.closest ? e.target.closest( '[data-fav-tab]' ) : null;
		if ( tab ) {
			activeTab = tab.getAttribute( 'data-fav-tab' );
			tabs.forEach( function ( t ) {
				t.classList.toggle( 'is-active', t === tab );
			} );
			applyFilters();
			var list = document.getElementById( 'ms-fav-list' );
			if ( list ) {
				list.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			}
		}
	} );

	if ( searchEl ) {
		var timer = null;
		searchEl.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( applyFilters, 120 );
		} );
		// Escape clears the search.
		searchEl.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				searchEl.value = '';
				applyFilters();
			}
		} );
	}

	// Warn before leaving with unsubmitted picks.
	var submitted = false;
	var form = root.querySelector( '.ms-fav__form' );
	if ( form ) {
		form.addEventListener( 'submit', function () {
			submitted = true;
		} );
	}
	window.addEventListener( 'beforeunload', function ( e ) {
		if ( ! submitted && votedCatIds().length > 0 ) {
			e.preventDefault();
			e.returnValue = '';
			return '';
		}
	} );

	/* ----------------------------------------------------------------- init */

	restore();
	updateProgress();
	applyFilters();
}() );
