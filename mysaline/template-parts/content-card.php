<?php
/**
 * Article card used in grids (homepage, archives).
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article <?php post_class( 'ms-card' ); ?>>
	<div class="ms-card__media">
		<?php mysaline_thumbnail( 'mysaline-card' ); ?>
		<?php mysaline_category_badge(); ?>
	</div>
	<div class="ms-card__body">
		<h3 class="ms-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p class="ms-card__excerpt"><?php echo mysaline_excerpt( 22 ); ?></p>
		<?php mysaline_post_meta( array( 'comments' => false ) ); ?>
	</div>
</article>
