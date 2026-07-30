<?php
/**
 * Default post layout used in the blog index / home.php loop.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article <?php post_class( 'ms-post-row ms-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="ms-card__media">
			<?php mysaline_thumbnail( 'mysaline-card' ); ?>
			<?php mysaline_category_badge(); ?>
		</div>
	<?php endif; ?>
	<div class="ms-card__body">
		<?php if ( ! has_post_thumbnail() ) { mysaline_category_badge(); } ?>
		<h2 class="ms-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<?php mysaline_post_meta(); ?>
		<p class="ms-card__excerpt"><?php echo mysaline_excerpt( 34 ); ?></p>
		<a class="ms-btn ms-btn--ghost" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read more', 'mysaline' ); ?></a>
	</div>
</article>
