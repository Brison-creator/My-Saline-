<?php
/**
 * A single photo gallery.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$ms_ids = mysaline_gallery_image_ids();
	?>
	<div class="ms-container ms-site-main">
		<article <?php post_class( 'ms-article ms-gallery' ); ?>>
			<header class="ms-article__header">
				<?php mysaline_breadcrumbs(); ?>
				<p class="ms-eyebrow"><?php esc_html_e( 'Photos', 'mysaline' ); ?></p>
				<h1 class="ms-article__title"><?php the_title(); ?></h1>
				<p class="ms-article__meta">
					<?php echo esc_html( get_the_date() ); ?>
					<?php
					$ms_total = mysaline_gallery_count();
					if ( $ms_total ) {
						echo ' <span aria-hidden="true">·</span> ';
						printf(
							/* translators: %s: number of photographs. */
							esc_html( _n( '%s photo', '%s photos', $ms_total, 'mysaline' ) ),
							esc_html( number_format_i18n( $ms_total ) )
						);
					}
					?>
				</p>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="ms-featured-media"><?php the_post_thumbnail( 'mysaline-hero' ); ?></figure>
			<?php endif; ?>

			<div class="ms-article__content"><?php the_content(); ?></div>

			<?php if ( $ms_ids ) : ?>
				<div class="ms-gallery__grid">
					<?php foreach ( $ms_ids as $ms_id ) : ?>
						<figure class="ms-gallery__item">
							<?php echo wp_get_attachment_image( $ms_id, 'large', false, array( 'loading' => 'lazy' ) ); ?>
							<?php
							$ms_caption = wp_get_attachment_caption( $ms_id );
							if ( $ms_caption ) {
								echo '<figcaption>' . esc_html( $ms_caption ) . '</figcaption>';
							}
							?>
						</figure>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<p class="ms-job__back">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'ms_gallery' ) ); ?>">&larr; <?php esc_html_e( 'All photo galleries', 'mysaline' ); ?></a>
			</p>
		</article>
	</div>
	<?php
endwhile;

get_footer();
