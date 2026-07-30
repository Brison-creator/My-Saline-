<?php
/**
 * Theme setup: supports, menus, image sizes, sidebars.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core theme supports. Registered on after_setup_theme.
 */
function mysaline_setup() {
	// Let WordPress manage the document title.
	add_theme_support( 'title-tag' );

	// Featured images for posts, pages and all custom post types.
	add_theme_support( 'post-thumbnails' );

	// Editable logo via Customizer (Appearance → Customize → Branding).
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 320,
			'flex-height' => true,
			'flex-width'  => true,
			'header-text' => array( 'site-title', 'site-description' ),
		)
	);

	// Modern markup and editor niceties.
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );
	add_theme_support( 'wp-block-styles' );

	// Custom, editor-visible image sizes used across templates.
	set_post_thumbnail_size( 1200, 675, true );
	add_image_size( 'mysaline-hero', 1280, 720, true );
	add_image_size( 'mysaline-card', 640, 360, true );
	add_image_size( 'mysaline-thumb', 160, 120, true );
	add_image_size( 'mysaline-square', 400, 400, true );

	// Navigation menu locations — owner manages these in Appearance → Menus.
	register_nav_menus(
		array(
			'primary'   => __( 'Primary Menu', 'mysaline' ),
			'secondary' => __( 'Top Bar Menu', 'mysaline' ),
			'footer'    => __( 'Footer Menu', 'mysaline' ),
			'social'    => __( 'Social Links Menu (optional)', 'mysaline' ),
		)
	);

	// Translation ready.
	load_theme_textdomain( 'mysaline', MYSALINE_DIR . 'languages' );
}
add_action( 'after_setup_theme', 'mysaline_setup' );

/**
 * Expose selectable image sizes in the editor's dropdown.
 *
 * @param array $sizes Existing sizes.
 * @return array
 */
function mysaline_custom_image_sizes( $sizes ) {
	return array_merge(
		$sizes,
		array(
			'mysaline-hero'  => __( 'MySaline Hero', 'mysaline' ),
			'mysaline-card'  => __( 'MySaline Card', 'mysaline' ),
			'mysaline-thumb' => __( 'MySaline Thumbnail', 'mysaline' ),
		)
	);
}
add_filter( 'image_size_names_choose', 'mysaline_custom_image_sizes' );

/**
 * Register widget areas (sidebars).
 */
function mysaline_widgets_init() {
	$defaults = array(
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="ms-widget-title">',
		'after_title'   => '</h2>',
	);

	register_sidebar(
		array_merge(
			$defaults,
			array(
				'name'        => __( 'Main Sidebar', 'mysaline' ),
				'id'          => 'sidebar-main',
				'description' => __( 'Shown beside posts and archives.', 'mysaline' ),
			)
		)
	);

	register_sidebar(
		array_merge(
			$defaults,
			array(
				'name'        => __( 'Homepage Sidebar', 'mysaline' ),
				'id'          => 'sidebar-home',
				'description' => __( 'Shown in the homepage sidebar column.', 'mysaline' ),
			)
		)
	);

	// Four footer columns.
	for ( $i = 1; $i <= 4; $i++ ) {
		register_sidebar(
			array_merge(
				$defaults,
				array(
					/* translators: %d: footer column number. */
					'name'        => sprintf( __( 'Footer Column %d', 'mysaline' ), $i ),
					'id'          => 'footer-' . $i,
					'description' => __( 'Footer widget column.', 'mysaline' ),
				)
			)
		);
	}
}
add_action( 'widgets_init', 'mysaline_widgets_init' );

/**
 * Add a helpful "MySaline" theme dashboard pointer under Appearance.
 */
function mysaline_admin_menu() {
	add_theme_page(
		__( 'MySaline Setup Guide', 'mysaline' ),
		__( 'MySaline Setup', 'mysaline' ),
		'edit_theme_options',
		'mysaline-setup',
		'mysaline_render_setup_page'
	);
}
add_action( 'admin_menu', 'mysaline_admin_menu' );

/**
 * Render the in-dashboard setup / help screen so the owner can find every control.
 */
function mysaline_render_setup_page() {
	$customize = admin_url( 'customize.php' );
	?>
	<div class="wrap mysaline-setup">
		<h1><?php esc_html_e( 'MySaline Theme — Content Control Guide', 'mysaline' ); ?></h1>
		<p><?php esc_html_e( 'Everything on the site is editable from the dashboard — no code required. Here is where each control lives:', 'mysaline' ); ?></p>
		<table class="widefat striped" style="max-width:900px">
			<thead><tr><th><?php esc_html_e( 'You want to edit…', 'mysaline' ); ?></th><th><?php esc_html_e( 'Go here', 'mysaline' ); ?></th></tr></thead>
			<tbody>
				<tr><td><?php esc_html_e( 'Logo, colors & branding', 'mysaline' ); ?></td><td><a href="<?php echo esc_url( $customize . '?autofocus[panel]=mysaline_panel' ); ?>"><?php esc_html_e( 'Customize → MySaline Options → Branding', 'mysaline' ); ?></a></td></tr>
				<tr><td><?php esc_html_e( 'Breaking news bar', 'mysaline' ); ?></td><td><?php esc_html_e( 'Customize → MySaline Options → Breaking News', 'mysaline' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Featured stories (homepage hero)', 'mysaline' ); ?></td><td><?php esc_html_e( 'Edit any post → "Featured Story" box, or Customize → MySaline Options → Homepage', 'mysaline' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Homepage sections', 'mysaline' ); ?></td><td><?php esc_html_e( 'Customize → MySaline Options → Homepage Sections', 'mysaline' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Advertisements', 'mysaline' ); ?></td><td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ms_ad' ) ); ?>"><?php esc_html_e( 'Advertisements menu', 'mysaline' ); ?></a></td></tr>
				<tr><td><?php esc_html_e( 'Community events', 'mysaline' ); ?></td><td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ms_event' ) ); ?>"><?php esc_html_e( 'Events menu', 'mysaline' ); ?></a></td></tr>
				<tr><td><?php esc_html_e( 'Obituaries', 'mysaline' ); ?></td><td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ms_obituary' ) ); ?>"><?php esc_html_e( 'Obituaries menu', 'mysaline' ); ?></a></td></tr>
				<tr><td><?php esc_html_e( 'Business listings', 'mysaline' ); ?></td><td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ms_business' ) ); ?>"><?php esc_html_e( 'Businesses menu', 'mysaline' ); ?></a></td></tr>
				<tr><td><?php esc_html_e( 'Saline County Favorites — categories & finalists', 'mysaline' ); ?></td><td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ms_fav_category' ) ); ?>"><?php esc_html_e( 'Favorites menu', 'mysaline' ); ?></a> · <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ms_fav_category&page=mysaline-fav-import' ) ); ?>"><?php esc_html_e( 'Import Ballot', 'mysaline' ); ?></a></td></tr>
				<tr><td><?php esc_html_e( 'Favorites — voting window, prize rules', 'mysaline' ); ?></td><td><?php esc_html_e( 'Customize → MySaline Options → Saline County Favorites', 'mysaline' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Favorites — results & drawing entries', 'mysaline' ); ?></td><td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ms_fav_category&page=mysaline-fav-results' ) ); ?>"><?php esc_html_e( 'Favorites → Results', 'mysaline' ); ?></a></td></tr>
				<tr><td><?php esc_html_e( 'Navigation menus', 'mysaline' ); ?></td><td><a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php esc_html_e( 'Appearance → Menus', 'mysaline' ); ?></a></td></tr>
				<tr><td><?php esc_html_e( 'Newsletter signup', 'mysaline' ); ?></td><td><?php esc_html_e( 'Customize → MySaline Options → Newsletter', 'mysaline' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Social links', 'mysaline' ); ?></td><td><?php esc_html_e( 'Customize → MySaline Options → Social Links', 'mysaline' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Widgets (sidebars & footer)', 'mysaline' ); ?></td><td><a href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>"><?php esc_html_e( 'Appearance → Widgets', 'mysaline' ); ?></a></td></tr>
			</tbody>
		</table>
		<p style="margin-top:1.5rem"><a class="button button-primary" href="<?php echo esc_url( $customize ); ?>"><?php esc_html_e( 'Open the Customizer', 'mysaline' ); ?></a></p>
	</div>
	<?php
}
