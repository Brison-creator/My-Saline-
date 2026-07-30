<?php
/**
 * Compact list row (sidebar-style) for a post.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article <?php post_class( 'ms-list-item' ); ?>>
	<?php mysaline_thumbnail( 'mysaline-thumb' ); ?>
	<div>
		<h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
		<?php mysaline_post_meta( array( 'comments' => false, 'author' => false ) ); ?>
	</div>
</article>
