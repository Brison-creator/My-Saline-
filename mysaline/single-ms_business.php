<?php
/**
 * Single business listing.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php mysaline_breadcrumbs(); ?>

<div class="ms-full-single">
		<?php
		while ( have_posts() ) :
			the_post();
			$mysaline_id      = get_the_ID();
			$mysaline_phone   = get_post_meta( $mysaline_id, '_ms_biz_phone', true );
			$mysaline_email   = get_post_meta( $mysaline_id, '_ms_biz_email', true );
			$mysaline_website = get_post_meta( $mysaline_id, '_ms_biz_website', true );
			$mysaline_address = get_post_meta( $mysaline_id, '_ms_biz_address', true );
			$mysaline_hours   = get_post_meta( $mysaline_id, '_ms_biz_hours', true );
			?>
			<article <?php post_class( 'ms-article ms-single-biz' ); ?>>
				<header class="ms-article__header" style="display:flex;gap:1.25rem;align-items:center">
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'mysaline-square', array( 'class' => 'ms-biz-card__logo', 'style' => 'width:90px;height:90px' ) );
					}
					?>
					<div>
						<span class="ms-cat-badge"><?php esc_html_e( 'Business', 'mysaline' ); ?></span>
						<h1 class="ms-article__title" style="margin:.3rem 0 0"><?php the_title(); ?></h1>
						<?php
						$mysaline_terms = get_the_term_list( $mysaline_id, 'ms_business_cat', '', ', ' );
						if ( $mysaline_terms && ! is_wp_error( $mysaline_terms ) ) {
							echo '<div class="ms-card__meta">' . wp_kses_post( $mysaline_terms ) . '</div>';
						}
						?>
					</div>
				</header>

				<div class="ms-content-sidebar" style="grid-template-columns:1fr 300px">
					<div class="ms-article__content"><?php the_content(); ?></div>

					<aside>
						<div class="ms-biz-card">
							<h2 class="ms-widget-title"><?php esc_html_e( 'Details', 'mysaline' ); ?></h2>
							<ul class="ms-biz-meta">
								<?php if ( $mysaline_address ) : ?>
									<li><strong><?php esc_html_e( 'Address', 'mysaline' ); ?></strong> <span><?php echo nl2br( esc_html( $mysaline_address ) ); ?></span></li>
								<?php endif; ?>
								<?php if ( $mysaline_phone ) : ?>
									<li><strong><?php esc_html_e( 'Phone', 'mysaline' ); ?></strong> <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $mysaline_phone ) ); ?>"><?php echo esc_html( $mysaline_phone ); ?></a></li>
								<?php endif; ?>
								<?php if ( $mysaline_email ) : ?>
									<li><strong><?php esc_html_e( 'Email', 'mysaline' ); ?></strong> <a href="mailto:<?php echo esc_attr( $mysaline_email ); ?>"><?php echo esc_html( $mysaline_email ); ?></a></li>
								<?php endif; ?>
								<?php if ( $mysaline_website ) : ?>
									<li><strong><?php esc_html_e( 'Website', 'mysaline' ); ?></strong> <a href="<?php echo esc_url( $mysaline_website ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://#', '', untrailingslashit( $mysaline_website ) ) ); ?></a></li>
								<?php endif; ?>
								<?php if ( $mysaline_hours ) : ?>
									<li><strong><?php esc_html_e( 'Hours', 'mysaline' ); ?></strong> <span><?php echo nl2br( esc_html( $mysaline_hours ) ); ?></span></li>
								<?php endif; ?>
							</ul>
							<?php if ( $mysaline_website ) : ?>
								<a class="ms-btn" style="margin-top:1rem;width:100%;text-align:center" href="<?php echo esc_url( $mysaline_website ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Visit website', 'mysaline' ); ?></a>
							<?php endif; ?>
						</div>
					</aside>
				</div>
			</article>
			<?php
		endwhile;
		?>
</div>

<?php
get_footer();
