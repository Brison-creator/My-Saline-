<?php
/**
 * Single community event.
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
			$mysaline_id       = get_the_ID();
			$mysaline_time     = get_post_meta( $mysaline_id, '_ms_event_time', true );
			$mysaline_venue    = get_post_meta( $mysaline_id, '_ms_event_venue', true );
			$mysaline_address  = get_post_meta( $mysaline_id, '_ms_event_address', true );
			$mysaline_cost     = get_post_meta( $mysaline_id, '_ms_event_cost', true );
			$mysaline_org      = get_post_meta( $mysaline_id, '_ms_event_organizer', true );
			$mysaline_link     = get_post_meta( $mysaline_id, '_ms_event_link', true );
			?>
			<article <?php post_class( 'ms-article ms-single-event' ); ?>>
				<header class="ms-article__header">
					<span class="ms-cat-badge"><?php esc_html_e( 'Event', 'mysaline' ); ?></span>
					<h1 class="ms-article__title"><?php the_title(); ?></h1>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="ms-featured-media"><?php the_post_thumbnail( 'mysaline-hero' ); ?></figure>
				<?php endif; ?>

				<div class="ms-event-meta">
					<?php
					$mysaline_range = mysaline_event_date_range();
					if ( $mysaline_range ) {
						echo '<div class="ms-event-meta__row"><strong>' . esc_html__( 'Date', 'mysaline' ) . '</strong><span>' . esc_html( $mysaline_range ) . '</span></div>';
					}
					if ( $mysaline_time ) {
						echo '<div class="ms-event-meta__row"><strong>' . esc_html__( 'Time', 'mysaline' ) . '</strong><span>' . esc_html( $mysaline_time ) . '</span></div>';
					}
					if ( $mysaline_venue || $mysaline_address ) {
						echo '<div class="ms-event-meta__row"><strong>' . esc_html__( 'Location', 'mysaline' ) . '</strong><span>' . esc_html( trim( $mysaline_venue . ( $mysaline_venue && $mysaline_address ? ' — ' : '' ) . $mysaline_address ) ) . '</span></div>';
					}
					if ( $mysaline_cost ) {
						echo '<div class="ms-event-meta__row"><strong>' . esc_html__( 'Cost', 'mysaline' ) . '</strong><span>' . esc_html( $mysaline_cost ) . '</span></div>';
					}
					if ( $mysaline_org ) {
						echo '<div class="ms-event-meta__row"><strong>' . esc_html__( 'Organizer', 'mysaline' ) . '</strong><span>' . esc_html( $mysaline_org ) . '</span></div>';
					}
					?>
					<?php if ( $mysaline_link ) : ?>
						<div class="ms-event-meta__row"><a class="ms-btn" href="<?php echo esc_url( $mysaline_link ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Tickets & info', 'mysaline' ); ?></a></div>
					<?php endif; ?>
				</div>

				<div class="ms-article__content"><?php the_content(); ?></div>
			</article>
			<?php
		endwhile;
		?>
	</div>

	<?php get_sidebar(); ?>
</div>

<?php
get_footer();
