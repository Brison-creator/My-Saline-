<?php
/**
 * Featured hero: one large lead story + a side list of the rest.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mysaline_hero = mysaline_hero_query();
if ( ! $mysaline_hero->have_posts() ) {
	wp_reset_postdata();
	return;
}

$mysaline_has_side = ( $mysaline_hero->post_count > 1 );
?>
<section class="ms-hero" aria-label="<?php esc_attr_e( 'Featured stories', 'mysaline' ); ?>">
	<?php
	$mysaline_index = 0;
	while ( $mysaline_hero->have_posts() ) :
		$mysaline_hero->the_post();

		if ( 0 === $mysaline_index ) :
			?>
			<article <?php post_class( 'ms-hero__lead' ); ?>>
				<?php mysaline_thumbnail( 'mysaline-hero' ); ?>
				<div class="ms-hero__lead-body">
					<?php mysaline_category_badge(); ?>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="ms-card__meta" style="color:#d7deea">
						<?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( get_the_author() ); ?>
					</div>
				</div>
			</article>
			<?php
			if ( $mysaline_has_side ) :
				echo '<div class="ms-hero__side">';
			endif;
		else :
			?>
			<article class="ms-hero__side-item">
				<?php mysaline_thumbnail( 'mysaline-thumb' ); ?>
				<div>
					<?php mysaline_category_badge(); ?>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				</div>
			</article>
			<?php
		endif;

		$mysaline_index++;
	endwhile;

	if ( $mysaline_has_side ) {
		echo '</div>';
	}
	wp_reset_postdata();
	?>
</section>
