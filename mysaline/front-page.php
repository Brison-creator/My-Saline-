<?php
/**
 * Homepage.
 *
 * Assembles editable, Customizer-driven blocks:
 *   1. Featured hero
 *   2. Ad (homepage top)
 *   3. Latest news grid
 *   4. Configurable category sections (with ad between)
 *   5. Upcoming events
 *   6. Recent obituaries
 *   7. Business spotlight
 *
 * If a static page is assigned as the front page, its content shows first.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php
// If the owner set a static front page with content, honor it above the feeds.
if ( 'page' === get_option( 'show_on_front' ) && get_the_content() ) :
	while ( have_posts() ) :
		the_post();
		if ( trim( get_the_content() ) ) :
			?>
			<div class="ms-static-front ms-article__content"><?php the_content(); ?></div>
			<?php
		endif;
	endwhile;
endif;
?>

<?php
/* 1. Featured hero ------------------------------------------------------- */
if ( get_theme_mod( 'mysaline_hero_enable', true ) ) {
	get_template_part( 'template-parts/featured-hero' );
}
?>

<?php
/* Quick-link callout cards ---------------------------------------------- */
get_template_part( 'template-parts/quick-links' );
?>

<?php
/* 2. Ad below hero ------------------------------------------------------- */
mysaline_ad( 'homepage_top', array( 'class' => 'ms-ad--leaderboard' ) );
?>

<div class="ms-content-sidebar">
	<div class="ms-primary-col">

		<?php
		/* 3. Latest news grid -------------------------------------------- */
		if ( get_theme_mod( 'mysaline_home_show_latest', true ) ) :
			$mysaline_exclude = mysaline_hero_post_ids();
			$mysaline_latest  = new WP_Query(
				array(
					'posts_per_page'      => 6,
					'post__not_in'        => $mysaline_exclude,
					'ignore_sticky_posts' => true,
					'post_status'         => 'publish',
				)
			);
			if ( $mysaline_latest->have_posts() ) :
				?>
				<section class="ms-section ms-latest">
					<div class="ms-section-head">
						<h2><?php esc_html_e( 'Latest News', 'mysaline' ); ?></h2>
						<a class="ms-section-head__link" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/?post_type=post' ) ); ?>"><?php esc_html_e( 'More', 'mysaline' ); ?></a>
					</div>
					<div class="ms-grid ms-grid--3">
						<?php
						while ( $mysaline_latest->have_posts() ) :
							$mysaline_latest->the_post();
							get_template_part( 'template-parts/content-card' );
						endwhile;
						?>
					</div>
				</section>
				<?php
			endif;
			wp_reset_postdata();
		endif;
		?>

		<?php
		/* 4. Configurable homepage sections ------------------------------ */
		for ( $mysaline_i = 1; $mysaline_i <= MYSALINE_HOMEPAGE_SECTIONS; $mysaline_i++ ) :
			mysaline_render_homepage_section( $mysaline_i );
			// Drop a mid-page ad after the first section.
			if ( 1 === $mysaline_i ) {
				mysaline_ad( 'homepage_mid', array( 'class' => 'ms-ad--leaderboard' ) );
			}
		endfor;
		?>

		<?php
		/* 5. Upcoming events --------------------------------------------- */
		if ( get_theme_mod( 'mysaline_home_show_events', true ) ) :
			$mysaline_events = mysaline_upcoming_events( 4 );
			if ( $mysaline_events->have_posts() ) :
				?>
				<section class="ms-section ms-home-events">
					<div class="ms-section-head">
						<h2><?php esc_html_e( 'Upcoming Events', 'mysaline' ); ?></h2>
						<a class="ms-section-head__link" href="<?php echo esc_url( get_post_type_archive_link( 'ms_event' ) ); ?>"><?php esc_html_e( 'All events', 'mysaline' ); ?></a>
					</div>
					<div class="ms-grid ms-grid--2">
						<?php
						while ( $mysaline_events->have_posts() ) :
							$mysaline_events->the_post();
							get_template_part( 'template-parts/content', 'event-card' );
						endwhile;
						?>
					</div>
				</section>
				<?php
			endif;
			wp_reset_postdata();
		endif;
		?>

		<?php
		/* 6. Recent obituaries ------------------------------------------- */
		if ( get_theme_mod( 'mysaline_home_show_obits', true ) ) :
			$mysaline_obits = new WP_Query(
				array(
					'post_type'      => 'ms_obituary',
					'posts_per_page' => 4,
					'post_status'    => 'publish',
				)
			);
			if ( $mysaline_obits->have_posts() ) :
				?>
				<section class="ms-section ms-home-obits">
					<div class="ms-section-head">
						<h2><?php esc_html_e( 'Recent Obituaries', 'mysaline' ); ?></h2>
						<a class="ms-section-head__link" href="<?php echo esc_url( get_post_type_archive_link( 'ms_obituary' ) ); ?>"><?php esc_html_e( 'All obituaries', 'mysaline' ); ?></a>
					</div>
					<div class="ms-grid ms-grid--4">
						<?php
						while ( $mysaline_obits->have_posts() ) :
							$mysaline_obits->the_post();
							get_template_part( 'template-parts/content', 'obituary-card' );
						endwhile;
						?>
					</div>
				</section>
				<?php
			endif;
			wp_reset_postdata();
		endif;
		?>

		<?php
		/* 7. Business spotlight ------------------------------------------ */
		if ( get_theme_mod( 'mysaline_home_show_businesses', true ) ) :
			$mysaline_biz = new WP_Query(
				array(
					'post_type'      => 'ms_business',
					'posts_per_page' => 3,
					'post_status'    => 'publish',
					'meta_key'       => '_ms_biz_featured', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);
			// Fall back to any businesses if none are flagged as featured.
			if ( ! $mysaline_biz->have_posts() ) {
				wp_reset_postdata();
				$mysaline_biz = new WP_Query(
					array(
						'post_type'      => 'ms_business',
						'posts_per_page' => 3,
						'post_status'    => 'publish',
					)
				);
			}
			if ( $mysaline_biz->have_posts() ) :
				?>
				<section class="ms-section ms-home-biz">
					<div class="ms-section-head">
						<h2><?php esc_html_e( 'Business Spotlight', 'mysaline' ); ?></h2>
						<a class="ms-section-head__link" href="<?php echo esc_url( get_post_type_archive_link( 'ms_business' ) ); ?>"><?php esc_html_e( 'Directory', 'mysaline' ); ?></a>
					</div>
					<div class="ms-grid ms-grid--3">
						<?php
						while ( $mysaline_biz->have_posts() ) :
							$mysaline_biz->the_post();
							get_template_part( 'template-parts/content', 'business-card' );
						endwhile;
						?>
					</div>
				</section>
				<?php
			endif;
			wp_reset_postdata();
		endif;
		?>

	</div><!-- .ms-primary-col -->

	<?php get_sidebar( 'home' ); ?>
</div><!-- .ms-content-sidebar -->

<?php
get_footer();
