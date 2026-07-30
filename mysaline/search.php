<?php
/**
 * Search results.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<header class="ms-page-header">
	<div class="ms-container">
		<h1>
			<?php
			/* translators: %s: search query. */
			printf( esc_html__( 'Search results for “%s”', 'mysaline' ), '<span>' . esc_html( get_search_query() ) . '</span>' );
			?>
		</h1>
		<p>
			<?php
			global $wp_query;
			/* translators: %d: number of results. */
			printf( esc_html( _n( '%d result', '%d results', (int) $wp_query->found_posts, 'mysaline' ) ), (int) $wp_query->found_posts );
			?>
		</p>
	</div>
</header>

<div class="ms-content-sidebar" style="margin-top:2rem">
	<div class="ms-primary-col">
		<?php if ( have_posts() ) : ?>
			<div class="ms-search-results">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article <?php post_class( 'ms-list-item' ); ?> style="grid-template-columns:120px 1fr">
						<?php mysaline_thumbnail( 'mysaline-thumb' ); ?>
						<div>
							<?php
							$mysaline_type = get_post_type_object( get_post_type() );
							if ( $mysaline_type && ! in_array( get_post_type(), array( 'post', 'page' ), true ) ) {
								echo '<span class="ms-cat-badge">' . esc_html( $mysaline_type->labels->singular_name ) . '</span>';
							}
							?>
							<h3 class="ms-card__title" style="font-size:1.2rem"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p class="ms-card__excerpt"><?php echo mysaline_excerpt( 30 ); ?></p>
						</div>
					</article>
					<?php
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
