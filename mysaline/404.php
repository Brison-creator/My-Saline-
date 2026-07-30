<?php
/**
 * 404 — not found.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="ms-content-sidebar">
	<div class="ms-primary-col">
		<section class="ms-no-results ms-404">
			<h1><?php esc_html_e( 'Page not found', 'mysaline' ); ?></h1>
			<p><?php esc_html_e( 'We couldn’t find that page. It may have moved. Try a search or browse recent news below.', 'mysaline' ); ?></p>
			<?php get_search_form(); ?>

			<div class="ms-section" style="margin-top:2.5rem">
				<div class="ms-section-head"><h2><?php esc_html_e( 'Recent News', 'mysaline' ); ?></h2></div>
				<div class="ms-grid ms-grid--3">
					<?php
					$mysaline_recent = new WP_Query(
						array(
							'posts_per_page'      => 3,
							'ignore_sticky_posts' => true,
							'no_found_rows'       => true,
							'post_status'         => 'publish',
						)
					);
					while ( $mysaline_recent->have_posts() ) :
						$mysaline_recent->the_post();
						get_template_part( 'template-parts/content-card' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
	</div>

	<?php get_sidebar(); ?>
</div>

<?php
get_footer();
