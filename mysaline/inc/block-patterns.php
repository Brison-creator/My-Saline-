<?php
/**
 * Block patterns.
 *
 * Ready-made layouts the owner can drop into any page from the editor's
 * pattern picker, so building an About or Advertise page doesn't mean
 * assembling columns block by block. Everything uses core blocks and the
 * theme's palette, so patterns keep working even if the theme changes.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the MySaline pattern category and its patterns.
 */
function mysaline_register_block_patterns() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	if ( function_exists( 'register_block_pattern_category' ) ) {
		register_block_pattern_category(
			'mysaline',
			array( 'label' => __( 'MySaline', 'mysaline' ) )
		);
	}

	/* ---- Contact / staff details ------------------------------------- */
	register_block_pattern(
		'mysaline/contact-details',
		array(
			'title'       => __( 'Contact details', 'mysaline' ),
			'description' => __( 'Address, phone and email in three columns.', 'mysaline' ),
			'categories'  => array( 'mysaline' ),
			'content'     => '<!-- wp:columns {"className":"ms-pattern-contact"} -->
<div class="wp-block-columns ms-pattern-contact"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3>Mail</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>PO Box 165<br>Benton, AR 72018</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3>Phone</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><a href="tel:5013034010">501-303-4010</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3>Email</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><a href="mailto:hello@example.com">hello@example.com</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->',
		)
	);

	/* ---- Advertising rate card --------------------------------------- */
	register_block_pattern(
		'mysaline/rate-card',
		array(
			'title'       => __( 'Advertising rate card', 'mysaline' ),
			'description' => __( 'A table of placements and prices for the Advertise page.', 'mysaline' ),
			'categories'  => array( 'mysaline' ),
			'content'     => '<!-- wp:heading -->
<h2>Placements &amp; rates</h2>
<!-- /wp:heading -->
<!-- wp:table {"className":"is-style-stripes"} -->
<figure class="wp-block-table is-style-stripes"><table><thead><tr><th>Placement</th><th>Where it appears</th><th>Per week</th></tr></thead><tbody>
<tr><td>Homepage leaderboard</td><td>Top of the homepage</td><td>$—</td></tr>
<tr><td>Run-of-site leaderboard</td><td>Every page</td><td>$—</td></tr>
<tr><td>In-feed native</td><td>Between stories, homepage and archives</td><td>$—</td></tr>
<tr><td>Sidebar rail</td><td>Beside articles, sticky</td><td>$—</td></tr>
<tr><td>In-article</td><td>Inside every story</td><td>$—</td></tr>
<tr><td>Directory sponsor</td><td>Between business listings</td><td>$—</td></tr>
<tr><td>Newsletter sponsor</td><td>Beside the signup block</td><td>$—</td></tr>
<tr><td>Mobile anchor</td><td>Pinned bottom bar on phones</td><td>$—</td></tr>
</tbody></table><figcaption class="wp-element-caption">Replace the dashes with your rates.</figcaption></figure>
<!-- /wp:table -->',
		)
	);

	/* ---- Audience stats ---------------------------------------------- */
	register_block_pattern(
		'mysaline/audience-stats',
		array(
			'title'       => __( 'Audience numbers', 'mysaline' ),
			'description' => __( 'Four stat tiles for the Advertise or About page.', 'mysaline' ),
			'categories'  => array( 'mysaline' ),
			'content'     => '<!-- wp:columns {"className":"ms-pattern-stats"} -->
<div class="wp-block-columns ms-pattern-stats"><!-- wp:column {"backgroundColor":"primary","textColor":"white"} -->
<div class="wp-block-column has-white-color has-primary-background-color has-text-color has-background"><!-- wp:heading {"level":3} -->
<h3>300k</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>page views per month</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column {"backgroundColor":"primary","textColor":"white"} -->
<div class="wp-block-column has-white-color has-primary-background-color has-text-color has-background"><!-- wp:heading {"level":3} -->
<h3>115k</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>social followers</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column {"backgroundColor":"primary","textColor":"white"} -->
<div class="wp-block-column has-white-color has-primary-background-color has-text-color has-background"><!-- wp:heading {"level":3} -->
<h3>2,500</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>newsletter subscribers</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column {"backgroundColor":"primary","textColor":"white"} -->
<div class="wp-block-column has-white-color has-primary-background-color has-text-color has-background"><!-- wp:heading {"level":3} -->
<h3>Since 2007</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>serving Saline County</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->',
		)
	);

	/* ---- Call-out box ------------------------------------------------- */
	register_block_pattern(
		'mysaline/callout',
		array(
			'title'       => __( 'Highlighted call-out', 'mysaline' ),
			'description' => __( 'A tinted box for submission instructions or a notice.', 'mysaline' ),
			'categories'  => array( 'mysaline' ),
			'content'     => '<!-- wp:group {"backgroundColor":"surface","className":"ms-pattern-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group ms-pattern-callout has-surface-background-color has-background"><!-- wp:heading {"level":3} -->
<h3>Submitting an obituary</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Email the full text and a photo and we will publish it, usually the same day. There is no charge for a standard listing.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Email us</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->',
		)
	);

	/* ---- Staff / columnist row ---------------------------------------- */
	register_block_pattern(
		'mysaline/staff-row',
		array(
			'title'       => __( 'Staff or columnist row', 'mysaline' ),
			'description' => __( 'Photo beside a name, role and short bio.', 'mysaline' ),
			'categories'  => array( 'mysaline' ),
			'content'     => '<!-- wp:media-text {"mediaType":"image","mediaWidth":22,"className":"ms-pattern-staff"} -->
<div class="wp-block-media-text is-stacked-on-mobile ms-pattern-staff" style="grid-template-columns:22% auto"><figure class="wp-block-media-text__media"></figure><div class="wp-block-media-text__content"><!-- wp:heading {"level":3} -->
<h3>Name</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong>Role or column name</strong></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>A sentence or two about what they cover.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:media-text -->',
		)
	);
}
add_action( 'init', 'mysaline_register_block_patterns' );

/**
 * Remove WordPress's bundled remote patterns.
 *
 * The core pattern directory pulls dozens of generic marketing layouts that
 * don't match this site and make the picker harder to use.
 */
function mysaline_remove_core_patterns() {
	remove_theme_support( 'core-block-patterns' );
}
add_action( 'after_setup_theme', 'mysaline_remove_core_patterns', 20 );
