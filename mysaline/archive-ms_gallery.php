<?php
/**
 * Photos: gallery archive, followed by recent photographs from across the site.
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
		<h1><?php esc_html_e( 'Photos', 'mysaline' ); ?></h1>
		<p><?php esc_html_e( 'Saline County as it happened — galleries, and the latest pictures from our reporting.', 'mysaline' ); ?></p>
	</div>
</header>

<div class="ms-container ms-site-main">
	<?php if ( have_posts() ) : ?>
		<div class="ms-grid ms-grid--3">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', 'gallery-card' );
			endwhile;
			?>
		</div>
		<?php mysaline_pagination(); ?>
	<?php endif; ?>

	<?php
	/*
	 * Whether or not any galleries exist yet, the newsroom's featured images do,
	 * so the section is never an empty page.
	 */
	$ms_photos = mysaline_recent_photos( 12 );
	if ( $ms_photos ) :
		?>
		<section class="ms-section" style="margin-top:3rem">
			<div class="ms-section-head"><h2><?php esc_html_e( 'Latest photographs', 'mysaline' ); ?></h2></div>
			<div class="ms-photostrip">
				<?php foreach ( $ms_photos as $ms_photo ) : ?>
					<a class="ms-photostrip__item" href="<?php echo esc_url( $ms_photo['permalink'] ); ?>">
						<img src="<?php echo esc_url( $ms_photo['url'] ); ?>" alt="<?php echo esc_attr( $ms_photo['alt'] ); ?>" loading="lazy" width="768" height="432" />
						<span class="ms-photostrip__caption"><?php echo esc_html( $ms_photo['title'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</div>

<?php
get_footer();
