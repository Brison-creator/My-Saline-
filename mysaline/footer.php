<?php
/**
 * Site footer: newsletter, footer widgets, copyright.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
			<?php
			// Shown only on paper: where this page came from, so a printed
			// obituary or event still carries its source.
			if ( is_singular() ) {
				printf(
					'<p class="ms-print-source">%s</p>',
					esc_html(
						sprintf(
							/* translators: 1: site name, 2: URL, 3: date printed. */
							__( 'Printed from %1$s — %2$s (%3$s)', 'mysaline' ),
							get_bloginfo( 'name' ),
							get_permalink(),
							date_i18n( get_option( 'date_format' ) )
						)
					)
				);
			}
			?>
		</div><!-- .ms-container -->
	</main><!-- #ms-main -->

	<?php mysaline_ad( 'footer', array( 'class' => 'ms-ad--leaderboard' ) ); ?>

	<?php if ( get_theme_mod( 'mysaline_news_enable', true ) && get_theme_mod( 'mysaline_news_action', '' ) ) : ?>
		<section class="ms-newsletter-band" aria-label="<?php esc_attr_e( 'Newsletter', 'mysaline' ); ?>">
			<div class="ms-container">
				<?php
				get_template_part( 'template-parts/newsletter' );
				// Newsletter sponsor slot — the list is sold inventory too.
				mysaline_ad( 'newsletter' );
				?>
			</div>
		</section>
	<?php endif; ?>

	<footer class="ms-footer" role="contentinfo">
		<div class="ms-container">
			<div class="ms-footer__widgets">
				<div class="ms-footer__about">
					<?php
					if ( has_custom_logo() ) {
						the_custom_logo();
					} else {
						echo '<p class="ms-brand__title" style="color:#fff">' . esc_html( get_bloginfo( 'name' ) ) . '</p>';
					}
					$about = get_theme_mod( 'mysaline_footer_about', '' );
					if ( $about ) {
						echo '<p>' . wp_kses_post( $about ) . '</p>';
					}

					// Contact block (address / phone / email).
					$ms_addr  = get_theme_mod( 'mysaline_contact_address', '' );
					$ms_phone = get_theme_mod( 'mysaline_contact_phone', '' );
					$ms_email = get_theme_mod( 'mysaline_contact_email', '' );
					if ( $ms_addr || $ms_phone || $ms_email ) {
						echo '<address class="ms-footer__contact">';
						if ( $ms_addr ) {
							echo esc_html( $ms_addr ) . '<br>';
						}
						if ( $ms_phone ) {
							echo '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $ms_phone ) ) . '">' . esc_html( $ms_phone ) . '</a>';
						}
						if ( $ms_phone && $ms_email ) {
							echo ' · ';
						}
						if ( $ms_email ) {
							echo '<a href="mailto:' . esc_attr( $ms_email ) . '">' . esc_html( $ms_email ) . '</a>';
						}
						echo '</address>';
					}

					get_template_part( 'template-parts/social-links' );
					?>
				</div>

				<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
					<?php if ( is_active_sidebar( 'footer-' . $i ) ) : ?>
						<div class="ms-footer__col">
							<?php dynamic_sidebar( 'footer-' . $i ); ?>
						</div>
					<?php else : ?>
						<?php if ( 1 === $i ) : ?>
							<div class="ms-footer__col">
								<h2 class="ms-widget-title"><?php esc_html_e( 'Sections', 'mysaline' ); ?></h2>
								<?php
								if ( has_nav_menu( 'footer' ) ) {
									wp_nav_menu(
										array(
											'theme_location' => 'footer',
											'container'      => false,
											'depth'          => 1,
											'fallback_cb'    => false,
										)
									);
								} else {
									wp_list_categories(
										array(
											'title_li' => '',
											'number'   => 8,
											'orderby'  => 'count',
											'order'    => 'DESC',
										)
									);
								}
								?>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				<?php endfor; ?>

				<div class="ms-footer__col">
					<?php if ( is_active_sidebar( 'footer-4' ) ) : ?>
						<?php dynamic_sidebar( 'footer-4' ); ?>
					<?php else : ?>
						<h2 class="ms-widget-title"><?php esc_html_e( 'Community', 'mysaline' ); ?></h2>
						<ul>
							<li><a href="<?php echo esc_url( get_post_type_archive_link( 'ms_event' ) ); ?>"><?php esc_html_e( 'Community Events', 'mysaline' ); ?></a></li>
							<li><a href="<?php echo esc_url( get_post_type_archive_link( 'ms_obituary' ) ); ?>"><?php esc_html_e( 'Obituaries', 'mysaline' ); ?></a></li>
							<li><a href="<?php echo esc_url( get_post_type_archive_link( 'ms_business' ) ); ?>"><?php esc_html_e( 'Business Directory', 'mysaline' ); ?></a></li>
						</ul>
					<?php endif; ?>
				</div>
			</div>

			<div class="ms-footer__bottom">
				<div class="ms-footer__copyright">
					<?php
					$copyright = get_theme_mod( 'mysaline_footer_copyright', '' );
					if ( $copyright ) {
						echo wp_kses_post( $copyright );
					} else {
						printf(
							/* translators: 1: year, 2: site name. */
							esc_html__( '© %1$s %2$s. All rights reserved.', 'mysaline' ),
							esc_html( date_i18n( 'Y' ) ),
							esc_html( get_bloginfo( 'name' ) )
						);
					}
					?>
				</div>
				<div class="ms-footer__credit">
					<?php esc_html_e( 'Powered by WordPress · MySaline theme', 'mysaline' ); ?>
				</div>
			</div>
		</div>
	</footer>
</div><!-- #page -->

<?php mysaline_ad_sticky_mobile(); ?>

<?php wp_footer(); ?>
</body>
</html>
