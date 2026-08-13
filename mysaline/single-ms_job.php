<?php
/**
 * A single job listing.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$ms_closed = mysaline_job_is_closed();
	$ms_apply  = get_post_meta( get_the_ID(), '_ms_job_apply', true );
	$ms_email  = get_post_meta( get_the_ID(), '_ms_job_email', true );
	$ms_closes = get_post_meta( get_the_ID(), '_ms_job_closes', true );
	?>
	<div class="ms-container ms-site-main">
		<div class="ms-content-sidebar">
			<article <?php post_class( 'ms-primary ms-article' ); ?>>
				<header class="ms-article__header">
					<?php mysaline_breadcrumbs(); ?>
					<p class="ms-eyebrow"><?php esc_html_e( 'Local Jobs', 'mysaline' ); ?></p>
					<h1 class="ms-article__title"><?php the_title(); ?></h1>
					<?php
					$ms_line = mysaline_job_meta_line();
					if ( $ms_line ) {
						echo '<p class="ms-job__meta">' . wp_kses( $ms_line, array( 'strong' => array(), 'span' => array( 'aria-hidden' => true ) ) ) . '</p>';
					}
					?>
				</header>

				<?php if ( $ms_closed ) : ?>
					<p class="ms-job__closed-notice">
						<?php esc_html_e( 'This listing has closed. It is kept here for reference.', 'mysaline' ); ?>
					</p>
				<?php endif; ?>

				<div class="ms-article__content"><?php the_content(); ?></div>

				<?php if ( ! $ms_closed && ( $ms_apply || $ms_email ) ) : ?>
					<div class="ms-job__apply">
						<h2><?php esc_html_e( 'How to apply', 'mysaline' ); ?></h2>
						<?php if ( $ms_closes ) : ?>
							<p class="ms-job__closes">
								<?php
								printf(
									/* translators: %s: closing date. */
									esc_html__( 'Applications close %s.', 'mysaline' ),
									esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ms_closes ) ) )
								);
								?>
							</p>
						<?php endif; ?>
						<p>
							<?php if ( $ms_apply ) : ?>
								<a class="ms-btn" href="<?php echo esc_url( $ms_apply ); ?>" rel="nofollow noopener"><?php esc_html_e( 'Apply online', 'mysaline' ); ?></a>
							<?php endif; ?>
							<?php if ( $ms_email ) : ?>
								<a class="ms-btn ms-btn--ghost" href="mailto:<?php echo esc_attr( $ms_email ); ?>"><?php esc_html_e( 'Apply by email', 'mysaline' ); ?></a>
							<?php endif; ?>
						</p>
					</div>
				<?php endif; ?>

				<p class="ms-job__back">
					<a href="<?php echo esc_url( get_post_type_archive_link( 'ms_job' ) ); ?>">&larr; <?php esc_html_e( 'All local jobs', 'mysaline' ); ?></a>
				</p>
			</article>
			<?php get_sidebar(); ?>
		</div>
	</div>
	<?php
endwhile;

get_footer();
