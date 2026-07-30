<?php
/**
 * Business card (homepage spotlight / directory grid).
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mysaline_phone   = get_post_meta( get_the_ID(), '_ms_biz_phone', true );
$mysaline_website = get_post_meta( get_the_ID(), '_ms_biz_website', true );
$mysaline_terms   = get_the_term_list( get_the_ID(), 'ms_business_cat', '', ', ' );
?>
<article <?php post_class( 'ms-card ms-biz-card' ); ?>>
	<?php
	if ( has_post_thumbnail() ) {
		echo '<div>';
		the_post_thumbnail( 'mysaline-square', array( 'class' => 'ms-biz-card__logo', 'loading' => 'lazy', 'alt' => get_the_title() ) );
		echo '</div>';
	}
	?>
	<h3 class="ms-card__title" style="font-size:1.15rem"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
	<?php if ( $mysaline_terms && ! is_wp_error( $mysaline_terms ) ) : ?>
		<div class="ms-card__meta"><?php echo wp_kses_post( $mysaline_terms ); ?></div>
	<?php endif; ?>
	<p class="ms-card__excerpt"><?php echo mysaline_excerpt( 18 ); ?></p>
	<ul class="ms-biz-meta">
		<?php if ( $mysaline_phone ) : ?>
			<li><strong><?php esc_html_e( 'Phone', 'mysaline' ); ?></strong> <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $mysaline_phone ) ); ?>"><?php echo esc_html( $mysaline_phone ); ?></a></li>
		<?php endif; ?>
		<?php if ( $mysaline_website ) : ?>
			<li><strong><?php esc_html_e( 'Web', 'mysaline' ); ?></strong> <a href="<?php echo esc_url( $mysaline_website ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Visit site', 'mysaline' ); ?></a></li>
		<?php endif; ?>
	</ul>
</article>
