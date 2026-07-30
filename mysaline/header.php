<?php
/**
 * Site header: topbar, branding, primary nav, breaking news.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#ms-main"><?php esc_html_e( 'Skip to content', 'mysaline' ); ?></a>

<div id="page" class="ms-site">

	<?php if ( get_theme_mod( 'mysaline_topbar_enable', true ) ) : ?>
		<div class="ms-topbar">
			<div class="ms-container">
				<div class="ms-topbar__left">
					<?php if ( get_theme_mod( 'mysaline_topbar_show_date', true ) ) : ?>
						<span class="ms-topbar__date"><?php echo esc_html( date_i18n( 'l, F j, Y' ) ); ?></span>
					<?php endif; ?>
					<?php
					$topbar_text = get_theme_mod( 'mysaline_topbar_text', '' );
					if ( $topbar_text ) {
						echo ' <span class="ms-topbar__text">' . esc_html( $topbar_text ) . '</span>';
					}
					?>
				</div>
				<div class="ms-topbar__right">
					<?php
					if ( has_nav_menu( 'secondary' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'secondary',
								'container'      => false,
								'menu_class'     => 'ms-topbar__menu',
								'depth'          => 1,
								'fallback_cb'    => false,
							)
						);
					}
					get_template_part( 'template-parts/social-links' );
					?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<header class="ms-header" role="banner">
		<div class="ms-container ms-header__inner">
			<div class="ms-brand">
				<?php if ( has_custom_logo() ) : ?>
					<span class="ms-brand__logo"><?php the_custom_logo(); ?></span>
				<?php else : ?>
					<div class="ms-brand__text">
						<p class="ms-brand__title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
						<?php
						if ( get_theme_mod( 'mysaline_show_tagline', true ) ) {
							$desc = get_bloginfo( 'description', 'display' );
							if ( $desc ) {
								echo '<p class="ms-brand__tagline">' . esc_html( $desc ) . '</p>';
							}
						}
						?>
					</div>
				<?php endif; ?>
			</div>

			<div class="ms-header__actions">
				<?php mysaline_ad( 'header' ); ?>
				<button class="ms-search-toggle" aria-expanded="false" aria-controls="ms-search-panel">
					<span class="dashicons-before dashicons-search" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Search', 'mysaline' ); ?></span>
				</button>
			</div>
		</div>
	</header>

	<nav class="ms-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Menu', 'mysaline' ); ?>">
		<div class="ms-container">
			<button class="ms-menu-toggle" aria-expanded="false" aria-controls="ms-primary-menu">
				<span class="dashicons-before dashicons-menu" aria-hidden="true"></span> <?php esc_html_e( 'Menu', 'mysaline' ); ?>
			</button>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_id'        => 'ms-primary-menu',
					'menu_class'     => 'ms-menu',
					'fallback_cb'    => 'mysaline_primary_menu_fallback',
				)
			);
			?>
		</div>
	</nav>

	<div id="ms-search-panel" class="ms-search-panel">
		<div class="ms-container">
			<?php get_search_form(); ?>
		</div>
	</div>

	<?php get_template_part( 'template-parts/breaking-news' ); ?>

	<main id="ms-main" class="ms-site-main" role="main">
		<div class="ms-container">
