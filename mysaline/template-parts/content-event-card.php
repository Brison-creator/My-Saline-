<?php
/**
 * Event card (homepage / grids).
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mysaline_parts = mysaline_event_date_parts();
$mysaline_venue = get_post_meta( get_the_ID(), '_ms_event_venue', true );
?>
<article <?php post_class( 'ms-card ms-event-card' ); ?>>
	<div class="ms-card__body" style="flex-direction:row;gap:1rem;align-items:flex-start">
		<?php if ( $mysaline_parts ) : ?>
			<span class="ms-event-card__date">
				<span class="ms-day"><?php echo esc_html( $mysaline_parts['day'] ); ?></span>
				<span class="ms-mon"><?php echo esc_html( $mysaline_parts['mon'] ); ?></span>
			</span>
		<?php endif; ?>
		<div>
			<h3 class="ms-card__title" style="font-size:1.1rem"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
			<div class="ms-card__meta">
				<?php
				$mysaline_time = get_post_meta( get_the_ID(), '_ms_event_time', true );
				if ( $mysaline_time ) {
					echo '<span>' . esc_html( $mysaline_time ) . '</span>';
				}
				if ( $mysaline_venue ) {
					echo '<span>' . esc_html( $mysaline_venue ) . '</span>';
				}
				?>
			</div>
			<p class="ms-card__excerpt"><?php echo mysaline_excerpt( 16 ); ?></p>
		</div>
	</div>
</article>
