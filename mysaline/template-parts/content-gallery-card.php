<?php
/**
 * Gallery cover card.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ms_count = mysaline_gallery_count();
?>
<article <?php post_class( 'ms-card ms-card--gallery' ); ?>>
	<a class="ms-card__media" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'mysaline-card', array( 'loading' => 'lazy' ) ); ?>
		<?php endif; ?>
		<?php if ( $ms_count ) : ?>
			<span class="ms-card__count">
				<?php
				printf(
					/* translators: %s: number of photographs. */
					esc_html( _n( '%s photo', '%s photos', $ms_count, 'mysaline' ) ),
					esc_html( number_format_i18n( $ms_count ) )
				);
				?>
			</span>
		<?php endif; ?>
	</a>
	<div class="ms-card__body">
		<h3 class="ms-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p class="ms-card__meta"><?php echo esc_html( get_the_date() ); ?></p>
	</div>
</article>
