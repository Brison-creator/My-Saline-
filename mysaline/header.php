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
					// Renders nothing at all if the forecast is unavailable.
					echo wp_kses( mysaline_weather_chip(), mysaline_weather_kses() );
					?>
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
				<button class="ms-search-toggle" aria-expanded="false" aria-controls="ms-search-panel">
					<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">
						<circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2"/>
						<path d="M15.4 15.4 L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
					<span class="screen-reader-text"><?php esc_html_e( 'Search', 'mysaline' ); ?></span>
				</button>
			</div>
		</div>

		<?php
		/*
		 * The leaderboard gets its own centred band under the masthead. Running
		 * it inline beside the logo squeezed the brand down to a fraction of the
		 * header, which is exactly backwards for the most valuable real estate
		 * on the page.
		 */
		if ( ! empty( mysaline_get_ads( 'header', 1 ) ) ) :
			?>
			<div class="ms-header__promo">
				<div class="ms-container"><?php mysaline_ad( 'header' ); ?></div>
			</div>
			<?php
		endif;
		?>
	</header>

	<nav class="ms-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Menu', 'mysaline' ); ?>">
		<div class="ms-container">
			<button class="ms-menu-toggle" aria-expanded="false" aria-controls="ms-nav-panel">
				<span class="ms-burger" aria-hidden="true"><span></span><span></span><span></span></span>
				<span class="ms-menu-toggle__label"><?php esc_html_e( 'Menu', 'mysaline' ); ?></span>
			</button>

			<div class="ms-nav__panel" id="ms-nav-panel">
				<?php
				/*
				 * Search sits at the top of the drawer on a phone. It is a top task
				 * on a news site, and the magnifier in the masthead is a small
				 * target competing with the logo for the same corner.
				 */
				?>
				<div class="ms-nav__search"><?php get_search_form(); ?></div>

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

				<div class="ms-nav__foot">
					<?php get_template_part( 'template-parts/social-links' ); ?>
				</div>
			</div>
		</div>
	</nav>
	<div class="ms-nav__scrim" hidden></div>

	<div id="ms-search-panel" class="ms-search-panel">
		<div class="ms-container">
			<?php get_search_form(); ?>
		</div>
	</div>

	<?php get_template_part( 'template-parts/breaking-news' ); ?>

	<main id="ms-main" class="ms-site-main" role="main">
		<div class="ms-container">
