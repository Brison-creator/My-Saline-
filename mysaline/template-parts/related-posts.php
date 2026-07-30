<?php
/**
 * Related posts (same primary category), shown under single posts.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mysaline_cats = get_the_category();
if ( empty( $mysaline_cats ) ) {
	return;
}

$mysaline_related = new WP_Query(
	array(
		'cat'                 => $mysaline_cats[0]->term_id,
		'posts_per_page'      => 3,
		'post__not_in'        => array( get_the_ID() ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post_status'         => 'publish',
	)
);

if ( ! $mysaline_related->have_posts() ) {
	wp_reset_postdata();
	return;
}
?>
<section class="ms-related">
	<div class="ms-section-head">
		<h2><?php esc_html_e( 'More like this', 'mysaline' ); ?></h2>
	</div>
	<div class="ms-grid ms-grid--3">
		<?php
		while ( $mysaline_related->have_posts() ) :
			$mysaline_related->the_post();
			get_template_part( 'template-parts/content-card' );
		endwhile;
		?>
	</div>
</section>
<?php
wp_reset_postdata();
