<?php
/**
 * Single obituary.
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
			$mysaline_id        = get_the_ID();
			$mysaline_born      = get_post_meta( $mysaline_id, '_ms_obit_born', true );
			$mysaline_died      = get_post_meta( $mysaline_id, '_ms_obit_died', true );
			$mysaline_age       = get_post_meta( $mysaline_id, '_ms_obit_age', true );
			$mysaline_city      = get_post_meta( $mysaline_id, '_ms_obit_city', true );
			$mysaline_service   = get_post_meta( $mysaline_id, '_ms_obit_service', true );
			$mysaline_location  = get_post_meta( $mysaline_id, '_ms_obit_location', true );
			$mysaline_home      = get_post_meta( $mysaline_id, '_ms_obit_home', true );
			$mysaline_home_link = get_post_meta( $mysaline_id, '_ms_obit_home_link', true );
			$mysaline_fmt       = get_option( 'date_format' );
			?>
			<article <?php post_class( 'ms-article ms-single-obit' ); ?> style="text-align:center">
				<header class="ms-article__header">
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'mysaline-square', array( 'class' => 'ms-obit-single__portrait' ) );
					}
					?>
					<h1 class="ms-article__title"><?php the_title(); ?></h1>
					<?php if ( $mysaline_born || $mysaline_died ) : ?>
						<p class="ms-dates" style="color:var(--ms-muted);font-size:1.05rem">
							<?php
							echo esc_html( $mysaline_born ? date_i18n( $mysaline_fmt, strtotime( $mysaline_born ) ) : '' );
							if ( $mysaline_born && $mysaline_died ) {
								echo ' — ';
							}
							echo esc_html( $mysaline_died ? date_i18n( $mysaline_fmt, strtotime( $mysaline_died ) ) : '' );
							if ( $mysaline_age ) {
								/* translators: %s: age. */
								echo ' · ' . esc_html( sprintf( __( 'Age %s', 'mysaline' ), $mysaline_age ) );
							}
							?>
						</p>
					<?php endif; ?>
					<?php if ( $mysaline_city ) : ?>
						<p style="color:var(--ms-muted)"><?php echo esc_html( $mysaline_city ); ?></p>
					<?php endif; ?>
				</header>

				<div class="ms-article__content" style="text-align:left;max-width:720px;margin:0 auto"><?php the_content(); ?></div>

				<?php if ( $mysaline_service || $mysaline_location || $mysaline_home ) : ?>
					<div class="ms-event-meta" style="text-align:left;max-width:720px;margin:2rem auto 0">
						<h2 style="font-size:1.2rem;margin:0 0 .5rem"><?php esc_html_e( 'Service arrangements', 'mysaline' ); ?></h2>
						<?php
						if ( $mysaline_service ) {
							echo '<div class="ms-event-meta__row"><strong>' . esc_html__( 'Service', 'mysaline' ) . '</strong><span>' . esc_html( $mysaline_service ) . '</span></div>';
						}
						if ( $mysaline_location ) {
							echo '<div class="ms-event-meta__row"><strong>' . esc_html__( 'Location', 'mysaline' ) . '</strong><span>' . esc_html( $mysaline_location ) . '</span></div>';
						}
						if ( $mysaline_home ) {
							$mysaline_home_out = $mysaline_home_link
								? '<a href="' . esc_url( $mysaline_home_link ) . '" target="_blank" rel="noopener">' . esc_html( $mysaline_home ) . '</a>'
								: esc_html( $mysaline_home );
							echo '<div class="ms-event-meta__row"><strong>' . esc_html__( 'Funeral home', 'mysaline' ) . '</strong><span>' . wp_kses_post( $mysaline_home_out ) . '</span></div>';
						}
						?>
					</div>
				<?php endif; ?>
			</article>
			<?php
		endwhile;
		?>
	</div>

	<?php get_sidebar(); ?>
</div>

<?php
get_footer();
