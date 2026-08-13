<?php
/**
 * Main fallback template (blog index / home).
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php mysaline_breadcrumbs(); ?>

<?php if ( is_home() && ! is_front_page() ) : ?>
	<header class="ms-page-header ms-page-header--bleed">
		<div class="ms-container">
			<h1><?php single_post_title(); ?></h1>
		</div>
	</header>
<?php endif; ?>

<div class="ms-content-sidebar">
	<div class="ms-primary-col">
		<?php if ( have_posts() ) : ?>
			<div class="ms-post-list ms-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', get_post_format() );
				endwhile;
				?>
			</div>
			<?php mysaline_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>

	<?php get_sidebar(); ?>
</div>

<?php
get_footer();
