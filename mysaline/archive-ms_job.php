<?php
/**
 * Local jobs board.
 *
 * Closed listings are filtered out in inc/jobs.php rather than here, so the
 * archive, the taxonomy pages and the widget all agree on what "open" means.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<header class="ms-page-header">
	<div class="ms-container">
		<?php mysaline_breadcrumbs(); ?>
		<h1><?php esc_html_e( 'Local Jobs', 'mysaline' ); ?></h1>
		<p><?php esc_html_e( 'Who is hiring in Saline County right now. Listings come down on their closing date.', 'mysaline' ); ?></p>
		<?php if ( mysaline_job_submit_enabled() ) : ?>
			<p class="ms-jobs-cta">
				<a class="ms-btn" href="<?php echo esc_url( home_url( '/post-a-job/' ) ); ?>">
					<?php
					printf(
						/* translators: %s: price line, e.g. "$10 for 30 days". */
						esc_html__( 'Post a job — %s', 'mysaline' ),
						esc_html( mysaline_job_price_line() )
					);
					?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</header>

<div class="ms-container ms-site-main">
	<?php
	$ms_job_cats = get_terms(
		array(
			'taxonomy'   => 'ms_job_cat',
			'hide_empty' => true,
		)
	);
	if ( $ms_job_cats && ! is_wp_error( $ms_job_cats ) ) :
		?>
		<nav class="ms-chips" aria-label="<?php esc_attr_e( 'Job categories', 'mysaline' ); ?>">
			<a class="ms-chip<?php echo is_post_type_archive( 'ms_job' ) ? ' is-current' : ''; ?>"
				href="<?php echo esc_url( get_post_type_archive_link( 'ms_job' ) ); ?>"><?php esc_html_e( 'All jobs', 'mysaline' ); ?></a>
			<?php foreach ( $ms_job_cats as $ms_cat ) : ?>
				<a class="ms-chip<?php echo is_tax( 'ms_job_cat', $ms_cat->term_id ) ? ' is-current' : ''; ?>"
					href="<?php echo esc_url( get_term_link( $ms_cat ) ); ?>"><?php echo esc_html( $ms_cat->name ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<div class="ms-content-sidebar">
		<div class="ms-primary">
			<?php if ( have_posts() ) : ?>
				<ul class="ms-jobs">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content', 'job' );
					endwhile;
					?>
				</ul>
				<?php mysaline_pagination(); ?>
			<?php else : ?>
				<div class="ms-empty">
					<h2><?php esc_html_e( 'No openings listed right now', 'mysaline' ); ?></h2>
					<p><?php esc_html_e( 'Hiring in Saline County? Get in touch and we will list it.', 'mysaline' ); ?></p>
					<a class="ms-btn" href="<?php echo esc_url( home_url( '/post-a-job/' ) ); ?>">
						<?php
						printf(
							/* translators: %s: price line. */
							esc_html__( 'Post a job — %s', 'mysaline' ),
							esc_html( mysaline_job_price_line() )
						);
						?>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php get_sidebar(); ?>
	</div>
</div>

<?php
get_footer();
