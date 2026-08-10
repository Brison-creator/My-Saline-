<?php
/**
 * Accessibility refinements.
 *
 * The palette already meets WCAG AA on every text/background pair used by the
 * theme. This file covers the structural half: telling assistive technology
 * where the reader is, what changed, and what each control does.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mark the current page in navigation with aria-current.
 *
 * WordPress adds `current-menu-item` as a class, which is visual only. Screen
 * readers need aria-current to announce "current page".
 *
 * @param array   $atts Link attributes.
 * @param WP_Post $item Menu item.
 * @return array
 */
function mysaline_nav_aria_current( $atts, $item ) {
	$classes = isset( $item->classes ) && is_array( $item->classes ) ? $item->classes : array();

	if ( in_array( 'current-menu-item', $classes, true ) || in_array( 'current_page_item', $classes, true ) ) {
		$atts['aria-current'] = 'page';
	} elseif ( in_array( 'current-menu-ancestor', $classes, true ) || in_array( 'current-menu-parent', $classes, true ) ) {
		$atts['aria-current'] = 'true';
	}

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'mysaline_nav_aria_current', 10, 2 );

/**
 * Give submenu toggles a discoverable expanded state.
 *
 * Hover-only submenus are unusable by keyboard and touch, so parents get
 * aria-haspopup and the theme's CSS opens them on focus-within too.
 *
 * @param array   $atts Link attributes.
 * @param WP_Post $item Menu item.
 * @return array
 */
function mysaline_nav_haspopup( $atts, $item ) {
	$classes = isset( $item->classes ) && is_array( $item->classes ) ? $item->classes : array();
	if ( in_array( 'menu-item-has-children', $classes, true ) ) {
		$atts['aria-haspopup'] = 'true';
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'mysaline_nav_haspopup', 10, 2 );

/**
 * Restore list semantics stripped by `list-style: none`.
 *
 * Safari/VoiceOver stops announcing a list when its markers are removed, so
 * navigation lists carry an explicit role.
 *
 * @param string $nav_menu Menu HTML.
 * @param object $args     Menu args.
 * @return string
 */
function mysaline_nav_list_role( $nav_menu, $args ) {
	return str_replace( '<ul class="ms-menu"', '<ul role="list" class="ms-menu"', $nav_menu );
}
add_filter( 'wp_nav_menu', 'mysaline_nav_list_role', 10, 2 );

/**
 * Make the skip link the first thing in the tab order and give the main
 * landmark a focusable target.
 *
 * The link exists in header.php; this ensures the target can actually receive
 * focus in browsers that skip non-interactive elements.
 */
function mysaline_main_focusable() {
	?>
	<script>
	// Move focus to the main region when the skip link is used, so the next Tab
	// continues inside the content rather than returning to the navigation.
	document.addEventListener( 'DOMContentLoaded', function () {
		var skip = document.querySelector( '.skip-link' );
		var main = document.getElementById( 'ms-main' );
		if ( ! skip || ! main ) { return; }
		skip.addEventListener( 'click', function () {
			main.setAttribute( 'tabindex', '-1' );
			main.focus();
		} );
	} );
	</script>
	<?php
}
add_action( 'wp_footer', 'mysaline_main_focusable', 99 );

/**
 * Add an accessible name to the comment form's honeypot-free fields and
 * ensure the search form's submit is labelled. Defensive: only filters markup
 * that lacks a label.
 *
 * @param string $form Search form markup.
 * @return string
 */
function mysaline_search_form_label( $form ) {
	if ( false === strpos( $form, 'aria-label' ) && false === strpos( $form, '<label' ) ) {
		$form = str_replace( 'type="search"', 'type="search" aria-label="' . esc_attr__( 'Search', 'mysaline' ) . '"', $form );
	}
	return $form;
}
add_filter( 'get_search_form', 'mysaline_search_form_label' );
