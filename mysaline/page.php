<?php
/**
 * Single page.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php mysaline_breadcrumbs(); ?>

<div class="ms-content-sidebar">
	<div class="ms-primary-col">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'ms-article ms-page' ); ?>>
				<header class="ms-article__header">
					<h1 class="ms-article__title"><?php the_title(); ?></h1>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="ms-featured-media"><?php the_post_thumbnail( 'mysaline-hero' ); ?></figure>
				<?php endif; ?>

				<div class="ms-article__content">
					<?php
					the_content();
					wp_link_pages(
						array(
							'before' => '<div class="ms-page-links">' . esc_html__( 'Pages:', 'mysaline' ),
							'after'  => '</div>',
						)
					);
					?>
				</div>

				<?php
				if ( comments_open() || get_comments_number() ) {
					echo '<div class="ms-comments">';
					comments_template();
					echo '</div>';
				}
				?>
			</article>
			<?php
		endwhile;
		?>
	</div>

	<?php
	if ( is_active_sidebar( 'sidebar-main' ) ) {
		get_sidebar();
	}
	?>
</div>

<?php
get_footer();
