<?php
/**
 * WordPress Customizer integration.
 *
 * All global, code-free site controls live here under a single
 * "MySaline Options" panel:
 *   - Branding & colors
 *   - Top bar
 *   - Breaking news
 *   - Homepage hero (featured stories)
 *   - Homepage sections (repeatable, category-driven)
 *   - Newsletter signup
 *   - Social links
 *   - Advertisements (global)
 *   - Footer
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Number of configurable homepage section slots.
 */
if ( ! defined( 'MYSALINE_HOMEPAGE_SECTIONS' ) ) {
	define( 'MYSALINE_HOMEPAGE_SECTIONS', 4 );
}

/* -------------------------------------------------------------------------
 * Sanitizers
 * ---------------------------------------------------------------------- */

/**
 * Boolean checkbox sanitizer.
 *
 * @param mixed $value Raw.
 * @return bool
 */
function mysaline_sanitize_checkbox( $value ) {
	return ( isset( $value ) && true === (bool) $value );
}

/**
 * Select sanitizer against allowed choices.
 *
 * @param string               $value   Raw value.
 * @param WP_Customize_Setting $setting Setting.
 * @return string
 */
function mysaline_sanitize_select( $value, $setting ) {
	$control = $setting->manager->get_control( $setting->id );
	$choices = $control ? $control->choices : array();
	return array_key_exists( $value, $choices ) ? $value : $setting->default;
}

/**
 * Positive integer sanitizer.
 *
 * @param mixed $value Raw.
 * @return int
 */
function mysaline_sanitize_int( $value ) {
	return absint( $value );
}

/**
 * Category / term id sanitizer (0 allowed = "any / latest").
 *
 * @param mixed $value Raw.
 * @return int
 */
function mysaline_sanitize_term_id( $value ) {
	return absint( $value );
}

/**
 * Build a select list of categories keyed by term id.
 *
 * @return array
 */
function mysaline_category_choices() {
	$choices = array( 0 => esc_html__( '— Latest posts (any category) —', 'mysaline' ) );
	$cats    = get_categories( array( 'hide_empty' => false ) );
	foreach ( $cats as $cat ) {
		$choices[ $cat->term_id ] = $cat->name;
	}
	return $choices;
}

/* -------------------------------------------------------------------------
 * Registration
 * ---------------------------------------------------------------------- */

/**
 * Register the MySaline Customizer panel and all sections.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mysaline_customize_register( $wp_customize ) {

	// Make core Site Identity controls live-preview nicely.
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	if ( $wp_customize->get_setting( 'custom_logo' ) ) {
		$wp_customize->get_setting( 'custom_logo' )->transport = 'refresh';
	}

	$wp_customize->add_panel(
		'mysaline_panel',
		array(
			'title'       => __( 'MySaline Options', 'mysaline' ),
			'description' => __( 'Control every part of the MySaline theme — no code required.', 'mysaline' ),
			'priority'    => 20,
		)
	);

	mysaline_customize_branding( $wp_customize );
	mysaline_customize_topbar( $wp_customize );
	mysaline_customize_breaking( $wp_customize );
	mysaline_customize_homepage( $wp_customize );
	mysaline_customize_quicklinks( $wp_customize );
	mysaline_customize_sections( $wp_customize );
	mysaline_customize_newsletter( $wp_customize );
	mysaline_customize_social( $wp_customize );
	mysaline_customize_ads( $wp_customize );
	mysaline_customize_footer( $wp_customize );
}
add_action( 'customize_register', 'mysaline_customize_register' );

/**
 * Branding & colors.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mysaline_customize_branding( $wp_customize ) {
	$wp_customize->add_section(
		'mysaline_branding',
		array(
			'title'       => __( 'Branding & Colors', 'mysaline' ),
			'panel'       => 'mysaline_panel',
			'description' => __( 'The logo lives under Site Identity. Set your brand colors here.', 'mysaline' ),
		)
	);

	$wp_customize->add_setting(
		'mysaline_color_primary',
		array(
			'default'           => '#0b2545',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'mysaline_color_primary',
			array(
				'label'   => __( 'Primary color (header, nav, headings)', 'mysaline' ),
				'section' => 'mysaline_branding',
			)
		)
	);

	$wp_customize->add_setting(
		'mysaline_color_accent',
		array(
			'default'           => '#c8102e',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'mysaline_color_accent',
			array(
				'label'   => __( 'Accent color (buttons, breaking news, links)', 'mysaline' ),
				'section' => 'mysaline_branding',
			)
		)
	);

	$wp_customize->add_setting(
		'mysaline_show_tagline',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_show_tagline',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Show tagline next to logo/title', 'mysaline' ),
			'section' => 'mysaline_branding',
		)
	);
}

/**
 * Top bar (date, contact, quick links).
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mysaline_customize_topbar( $wp_customize ) {
	$wp_customize->add_section(
		'mysaline_topbar',
		array(
			'title' => __( 'Top Bar', 'mysaline' ),
			'panel' => 'mysaline_panel',
		)
	);

	$wp_customize->add_setting(
		'mysaline_topbar_enable',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_topbar_enable',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Show the top bar', 'mysaline' ),
			'section' => 'mysaline_topbar',
		)
	);

	$wp_customize->add_setting(
		'mysaline_topbar_show_date',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_topbar_show_date',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Show today’s date', 'mysaline' ),
			'section' => 'mysaline_topbar',
		)
	);

	$wp_customize->add_setting(
		'mysaline_topbar_text',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'mysaline_topbar_text',
		array(
			'type'        => 'text',
			'label'       => __( 'Top bar message (optional)', 'mysaline' ),
			'description' => __( 'e.g. a phone number or short welcome line.', 'mysaline' ),
			'section'     => 'mysaline_topbar',
		)
	);
}

/**
 * Breaking news bar.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mysaline_customize_breaking( $wp_customize ) {
	$wp_customize->add_section(
		'mysaline_breaking',
		array(
			'title'       => __( 'Breaking News', 'mysaline' ),
			'panel'       => 'mysaline_panel',
			'description' => __( 'Show a red alert bar under the menu.', 'mysaline' ),
		)
	);

	$wp_customize->add_setting(
		'mysaline_breaking_enable',
		array(
			'default'           => false,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_breaking_enable',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Enable breaking news bar', 'mysaline' ),
			'section' => 'mysaline_breaking',
		)
	);

	$wp_customize->add_setting(
		'mysaline_breaking_source',
		array(
			'default'           => 'manual',
			'sanitize_callback' => 'mysaline_sanitize_select',
		)
	);
	$wp_customize->add_control(
		'mysaline_breaking_source',
		array(
			'type'    => 'select',
			'label'   => __( 'What should it show?', 'mysaline' ),
			'section' => 'mysaline_breaking',
			'choices' => array(
				'manual'   => __( 'A custom message I type below', 'mysaline' ),
				'category' => __( 'Latest posts from a category', 'mysaline' ),
			),
		)
	);

	$wp_customize->add_setting(
		'mysaline_breaking_label',
		array(
			'default'           => __( 'Breaking', 'mysaline' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'mysaline_breaking_label',
		array(
			'type'    => 'text',
			'label'   => __( 'Bar label', 'mysaline' ),
			'section' => 'mysaline_breaking',
		)
	);

	$wp_customize->add_setting(
		'mysaline_breaking_text',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'mysaline_breaking_text',
		array(
			'type'        => 'text',
			'label'       => __( 'Custom message', 'mysaline' ),
			'description' => __( 'Used when "custom message" is selected above.', 'mysaline' ),
			'section'     => 'mysaline_breaking',
		)
	);

	$wp_customize->add_setting(
		'mysaline_breaking_link',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		'mysaline_breaking_link',
		array(
			'type'    => 'url',
			'label'   => __( 'Custom message link (optional)', 'mysaline' ),
			'section' => 'mysaline_breaking',
		)
	);

	$wp_customize->add_setting(
		'mysaline_breaking_cat',
		array(
			'default'           => 0,
			'sanitize_callback' => 'mysaline_sanitize_term_id',
		)
	);
	$wp_customize->add_control(
		'mysaline_breaking_cat',
		array(
			'type'    => 'select',
			'label'   => __( 'Pull from this category', 'mysaline' ),
			'section' => 'mysaline_breaking',
			'choices' => mysaline_category_choices(),
		)
	);
}

/**
 * Homepage hero (featured stories).
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mysaline_customize_homepage( $wp_customize ) {
	$wp_customize->add_section(
		'mysaline_homepage',
		array(
			'title'       => __( 'Homepage Hero', 'mysaline' ),
			'panel'       => 'mysaline_panel',
			'description' => __( 'The top "featured stories" area. Mark posts as featured from the post editor, or the newest posts are used automatically.', 'mysaline' ),
		)
	);

	$wp_customize->add_setting(
		'mysaline_hero_enable',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_hero_enable',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Show the featured hero', 'mysaline' ),
			'section' => 'mysaline_homepage',
		)
	);

	$wp_customize->add_setting(
		'mysaline_hero_source',
		array(
			'default'           => 'featured',
			'sanitize_callback' => 'mysaline_sanitize_select',
		)
	);
	$wp_customize->add_control(
		'mysaline_hero_source',
		array(
			'type'    => 'select',
			'label'   => __( 'Which stories?', 'mysaline' ),
			'section' => 'mysaline_homepage',
			'choices' => array(
				'featured' => __( 'Posts I marked as Featured', 'mysaline' ),
				'latest'   => __( 'Most recent posts', 'mysaline' ),
				'category' => __( 'Newest from a category', 'mysaline' ),
			),
		)
	);

	$wp_customize->add_setting(
		'mysaline_hero_cat',
		array(
			'default'           => 0,
			'sanitize_callback' => 'mysaline_sanitize_term_id',
		)
	);
	$wp_customize->add_control(
		'mysaline_hero_cat',
		array(
			'type'    => 'select',
			'label'   => __( 'Hero category (if selected above)', 'mysaline' ),
			'section' => 'mysaline_homepage',
			'choices' => mysaline_category_choices(),
		)
	);

	$wp_customize->add_setting(
		'mysaline_hero_count',
		array(
			'default'           => 5,
			'sanitize_callback' => 'mysaline_sanitize_int',
		)
	);
	$wp_customize->add_control(
		'mysaline_hero_count',
		array(
			'type'        => 'number',
			'label'       => __( 'How many stories in the hero', 'mysaline' ),
			'description' => __( '1 large + the rest as a side list. 4–5 recommended.', 'mysaline' ),
			'input_attrs' => array( 'min' => 1, 'max' => 8 ),
			'section'     => 'mysaline_homepage',
		)
	);

	$wp_customize->add_setting(
		'mysaline_home_show_latest',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_home_show_latest',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Show a "Latest News" grid under the hero', 'mysaline' ),
			'section' => 'mysaline_homepage',
		)
	);

	$wp_customize->add_setting(
		'mysaline_home_show_events',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_home_show_events',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Show an "Upcoming Events" block', 'mysaline' ),
			'section' => 'mysaline_homepage',
		)
	);

	$wp_customize->add_setting(
		'mysaline_home_show_obits',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_home_show_obits',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Show a "Recent Obituaries" block', 'mysaline' ),
			'section' => 'mysaline_homepage',
		)
	);

	$wp_customize->add_setting(
		'mysaline_home_show_businesses',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_home_show_businesses',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Show a "Business Spotlight" block', 'mysaline' ),
			'section' => 'mysaline_homepage',
		)
	);
}

/**
 * Number of homepage quick-link callout cards.
 */
if ( ! defined( 'MYSALINE_QUICKLINKS' ) ) {
	define( 'MYSALINE_QUICKLINKS', 4 );
}

/**
 * Homepage quick-link callout cards (e.g. Advertise, Elected Officials,
 * Yard Sales, Games) — the modern version of the current site's callout boxes.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mysaline_customize_quicklinks( $wp_customize ) {
	$wp_customize->add_section(
		'mysaline_quicklinks',
		array(
			'title'       => __( 'Homepage Quick Links', 'mysaline' ),
			'panel'       => 'mysaline_panel',
			'description' => __( 'A row of callout cards near the top of the homepage. Great for Advertise, Elected Officials, Yard Sales, Events, etc. Leave a title blank to hide that card.', 'mysaline' ),
		)
	);

	$wp_customize->add_setting(
		'mysaline_quicklinks_enable',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_quicklinks_enable',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Show the quick-link cards', 'mysaline' ),
			'section' => 'mysaline_quicklinks',
		)
	);

	$defaults = array(
		1 => array( 'Advertise with us', '📣' ),
		2 => array( 'Elected Officials', '🏛️' ),
		3 => array( 'Yard Sales', '🏷️' ),
		4 => array( 'Community Events', '📅' ),
	);

	for ( $i = 1; $i <= MYSALINE_QUICKLINKS; $i++ ) {
		$title_default = isset( $defaults[ $i ][0] ) ? $defaults[ $i ][0] : '';
		$icon_default  = isset( $defaults[ $i ][1] ) ? $defaults[ $i ][1] : '⭐';

		$wp_customize->add_setting(
			"mysaline_quicklink_{$i}_title",
			array(
				'default'           => $title_default,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			"mysaline_quicklink_{$i}_title",
			array(
				'type'    => 'text',
				/* translators: %d: card number. */
				'label'   => sprintf( __( 'Card %d — title', 'mysaline' ), $i ),
				'section' => 'mysaline_quicklinks',
			)
		);

		$wp_customize->add_setting(
			"mysaline_quicklink_{$i}_icon",
			array(
				'default'           => $icon_default,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			"mysaline_quicklink_{$i}_icon",
			array(
				'type'        => 'text',
				/* translators: %d: card number. */
				'label'       => sprintf( __( 'Card %d — icon (emoji)', 'mysaline' ), $i ),
				'section'     => 'mysaline_quicklinks',
			)
		);

		$wp_customize->add_setting(
			"mysaline_quicklink_{$i}_url",
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			"mysaline_quicklink_{$i}_url",
			array(
				'type'    => 'url',
				/* translators: %d: card number. */
				'label'   => sprintf( __( 'Card %d — link', 'mysaline' ), $i ),
				'section' => 'mysaline_quicklinks',
			)
		);
	}
}

/**
 * Repeatable homepage content sections.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mysaline_customize_sections( $wp_customize ) {
	$wp_customize->add_section(
		'mysaline_sections',
		array(
			'title'       => __( 'Homepage Sections', 'mysaline' ),
			'panel'       => 'mysaline_panel',
			'description' => __( 'Add category-driven news sections to the homepage. Each one pulls the latest posts from a category you pick.', 'mysaline' ),
		)
	);

	for ( $i = 1; $i <= MYSALINE_HOMEPAGE_SECTIONS; $i++ ) {
		$wp_customize->add_setting(
			"mysaline_section_{$i}_enable",
			array(
				'default'           => ( $i <= 2 ),
				'sanitize_callback' => 'mysaline_sanitize_checkbox',
			)
		);
		$wp_customize->add_control(
			"mysaline_section_{$i}_enable",
			array(
				'type'    => 'checkbox',
				/* translators: %d: section number. */
				'label'   => sprintf( __( 'Enable section %d', 'mysaline' ), $i ),
				'section' => 'mysaline_sections',
			)
		);

		$wp_customize->add_setting(
			"mysaline_section_{$i}_title",
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			"mysaline_section_{$i}_title",
			array(
				'type'        => 'text',
				'label'       => __( 'Heading', 'mysaline' ),
				'description' => __( 'Leave blank to use the category name.', 'mysaline' ),
				'section'     => 'mysaline_sections',
			)
		);

		$wp_customize->add_setting(
			"mysaline_section_{$i}_cat",
			array(
				'default'           => 0,
				'sanitize_callback' => 'mysaline_sanitize_term_id',
			)
		);
		$wp_customize->add_control(
			"mysaline_section_{$i}_cat",
			array(
				'type'    => 'select',
				'label'   => __( 'Category', 'mysaline' ),
				'section' => 'mysaline_sections',
				'choices' => mysaline_category_choices(),
			)
		);

		$wp_customize->add_setting(
			"mysaline_section_{$i}_layout",
			array(
				'default'           => 'grid-3',
				'sanitize_callback' => 'mysaline_sanitize_select',
			)
		);
		$wp_customize->add_control(
			"mysaline_section_{$i}_layout",
			array(
				'type'    => 'select',
				'label'   => __( 'Layout', 'mysaline' ),
				'section' => 'mysaline_sections',
				'choices' => array(
					'grid-3' => __( '3-column card grid', 'mysaline' ),
					'grid-2' => __( '2-column card grid', 'mysaline' ),
					'list'   => __( 'Compact list', 'mysaline' ),
					'mixed'  => __( 'Lead story + list', 'mysaline' ),
				),
			)
		);

		$wp_customize->add_setting(
			"mysaline_section_{$i}_count",
			array(
				'default'           => 3,
				'sanitize_callback' => 'mysaline_sanitize_int',
			)
		);
		$wp_customize->add_control(
			"mysaline_section_{$i}_count",
			array(
				'type'        => 'number',
				'label'       => __( 'Number of posts', 'mysaline' ),
				'input_attrs' => array( 'min' => 1, 'max' => 12 ),
				'section'     => 'mysaline_sections',
			)
		);
	}
}

/**
 * Newsletter signup.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mysaline_customize_newsletter( $wp_customize ) {
	$wp_customize->add_section(
		'mysaline_newsletter',
		array(
			'title'       => __( 'Newsletter', 'mysaline' ),
			'panel'       => 'mysaline_panel',
			'description' => __( 'Paste your email provider form action (Mailchimp, Constant Contact, etc.). No code needed.', 'mysaline' ),
		)
	);

	$wp_customize->add_setting(
		'mysaline_news_enable',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_news_enable',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Show newsletter signup in the footer', 'mysaline' ),
			'section' => 'mysaline_newsletter',
		)
	);

	$wp_customize->add_setting(
		'mysaline_news_title',
		array(
			'default'           => __( 'Get the MySaline newsletter', 'mysaline' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'mysaline_news_title',
		array(
			'type'    => 'text',
			'label'   => __( 'Heading', 'mysaline' ),
			'section' => 'mysaline_newsletter',
		)
	);

	$wp_customize->add_setting(
		'mysaline_news_text',
		array(
			'default'           => __( 'Saline County news in your inbox. Free, and no spam.', 'mysaline' ),
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control(
		'mysaline_news_text',
		array(
			'type'    => 'textarea',
			'label'   => __( 'Description', 'mysaline' ),
			'section' => 'mysaline_newsletter',
		)
	);

	$wp_customize->add_setting(
		'mysaline_news_action',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		'mysaline_news_action',
		array(
			'type'        => 'url',
			'label'       => __( 'Form action URL', 'mysaline' ),
			'description' => __( 'From your email provider embed code (the form "action" address). The email field is named "EMAIL" (Mailchimp default).', 'mysaline' ),
			'section'     => 'mysaline_newsletter',
		)
	);

	$wp_customize->add_setting(
		'mysaline_news_email_field',
		array(
			'default'           => 'EMAIL',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'mysaline_news_email_field',
		array(
			'type'        => 'text',
			'label'       => __( 'Email field name', 'mysaline' ),
			'description' => __( 'Mailchimp uses EMAIL. Change only if your provider differs.', 'mysaline' ),
			'section'     => 'mysaline_newsletter',
		)
	);

	$wp_customize->add_setting(
		'mysaline_news_button',
		array(
			'default'           => __( 'Subscribe', 'mysaline' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'mysaline_news_button',
		array(
			'type'    => 'text',
			'label'   => __( 'Button text', 'mysaline' ),
			'section' => 'mysaline_newsletter',
		)
	);
}

/**
 * Social links.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mysaline_customize_social( $wp_customize ) {
	$wp_customize->add_section(
		'mysaline_social',
		array(
			'title'       => __( 'Social Links', 'mysaline' ),
			'panel'       => 'mysaline_panel',
			'description' => __( 'Paste the full URL for each network. Blank fields are hidden.', 'mysaline' ),
		)
	);

	foreach ( mysaline_social_networks() as $key => $label ) {
		$wp_customize->add_setting(
			"mysaline_social_{$key}",
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			"mysaline_social_{$key}",
			array(
				'type'    => 'url',
				'label'   => $label,
				'section' => 'mysaline_social',
			)
		);
	}
}

/**
 * Global advertisement settings.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mysaline_customize_ads( $wp_customize ) {
	$wp_customize->add_section(
		'mysaline_ads',
		array(
			'title'       => __( 'Advertisements', 'mysaline' ),
			'panel'       => 'mysaline_panel',
			'description' => __( 'Create individual ads under the "Advertisements" menu. Global options below.', 'mysaline' ),
		)
	);

	$wp_customize->add_setting(
		'mysaline_ads_enable',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_ads_enable',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Show advertisements', 'mysaline' ),
			'section' => 'mysaline_ads',
		)
	);

	$wp_customize->add_setting(
		'mysaline_ads_label',
		array(
			'default'           => __( 'Advertisement', 'mysaline' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'mysaline_ads_label',
		array(
			'type'    => 'text',
			'label'   => __( 'Small label above each ad', 'mysaline' ),
			'section' => 'mysaline_ads',
		)
	);

	$wp_customize->add_setting(
		'mysaline_ads_incontent',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_ads_incontent',
		array(
			'type'        => 'checkbox',
			'label'       => __( 'Insert an ad inside single articles', 'mysaline' ),
			'description' => __( 'Places an ad from the "In-Content" zone partway through each post.', 'mysaline' ),
			'section'     => 'mysaline_ads',
		)
	);
}

/**
 * Footer options.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mysaline_customize_footer( $wp_customize ) {
	$wp_customize->add_section(
		'mysaline_footer',
		array(
			'title' => __( 'Footer', 'mysaline' ),
			'panel' => 'mysaline_panel',
		)
	);

	$wp_customize->add_setting(
		'mysaline_footer_about',
		array(
			'default'           => __( 'MySaline is the most-read news source in Saline County, Arkansas — local news, events, obituaries and community since 2007.', 'mysaline' ),
			'sanitize_callback' => 'wp_kses_post',
		)
	);
	$wp_customize->add_control(
		'mysaline_footer_about',
		array(
			'type'    => 'textarea',
			'label'   => __( 'Footer "about" text', 'mysaline' ),
			'section' => 'mysaline_footer',
		)
	);

	$wp_customize->add_setting(
		'mysaline_footer_copyright',
		array(
			'default'           => '',
			'sanitize_callback' => 'wp_kses_post',
		)
	);
	$wp_customize->add_control(
		'mysaline_footer_copyright',
		array(
			'type'        => 'text',
			'label'       => __( 'Copyright line', 'mysaline' ),
			'description' => __( 'Leave blank for "© {year} {site name}".', 'mysaline' ),
			'section'     => 'mysaline_footer',
		)
	);

	// Contact block (mirrors the current site: PO Box, phone, email).
	$wp_customize->add_setting(
		'mysaline_contact_address',
		array(
			'default'           => __( 'PO Box 165 · Benton, AR 72018', 'mysaline' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'mysaline_contact_address',
		array(
			'type'    => 'text',
			'label'   => __( 'Mailing address', 'mysaline' ),
			'section' => 'mysaline_footer',
		)
	);

	$wp_customize->add_setting(
		'mysaline_contact_phone',
		array(
			'default'           => '501-303-4010',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'mysaline_contact_phone',
		array(
			'type'    => 'text',
			'label'   => __( 'Phone number', 'mysaline' ),
			'section' => 'mysaline_footer',
		)
	);

	$wp_customize->add_setting(
		'mysaline_contact_email',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_email',
		)
	);
	$wp_customize->add_control(
		'mysaline_contact_email',
		array(
			'type'        => 'email',
			'label'       => __( 'Contact email', 'mysaline' ),
			'section'     => 'mysaline_footer',
		)
	);
}

/**
 * The social networks we support (key => label). SVG icons defined in template-parts.
 *
 * @return array
 */
function mysaline_social_networks() {
	return array(
		'facebook'  => __( 'Facebook URL', 'mysaline' ),
		'instagram' => __( 'Instagram URL', 'mysaline' ),
		'twitter'   => __( 'X / Twitter URL', 'mysaline' ),
		'youtube'   => __( 'YouTube URL', 'mysaline' ),
		'tiktok'    => __( 'TikTok URL', 'mysaline' ),
		'linkedin'  => __( 'LinkedIn URL', 'mysaline' ),
		'rss'       => __( 'RSS Feed URL', 'mysaline' ),
	);
}
