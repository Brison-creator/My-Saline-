<?php
/**
 * Obituary card (homepage / grids).
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mysaline_born = get_post_meta( get_the_ID(), '_ms_obit_born', true );
$mysaline_died = get_post_meta( get_the_ID(), '_ms_obit_died', true );
$mysaline_fmt  = get_option( 'date_format' );
?>
<article <?php post_class( 'ms-card ms-obit-card' ); ?>>
	<div class="ms-card__body" style="align-items:center;text-align:center">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'mysaline-square', array( 'loading' => 'lazy', 'alt' => get_the_title() ) );
		} else {
			echo '<img src="data:image/svg+xml;utf8,' . rawurlencode( '<svg xmlns="http://www.w3.org/2000/svg" width="140" height="140"><rect width="140" height="140" fill="#eaeef4"/><text x="50%" y="52%" font-size="48" fill="#9aa6b6" text-anchor="middle" dominant-baseline="middle" font-family="Georgia">' . esc_html( mb_substr( get_the_title(), 0, 1 ) ) . '</text></svg>' ) . '" width="140" height="140" alt="" />';
		}
		?>
		<h3 class="ms-card__title" style="font-size:1.05rem;margin-top:.75rem"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<?php if ( $mysaline_born || $mysaline_died ) : ?>
			<p class="ms-dates">
				<?php
				echo esc_html( $mysaline_born ? date_i18n( $mysaline_fmt, strtotime( $mysaline_born ) ) : '' );
				if ( $mysaline_born && $mysaline_died ) {
					echo ' – ';
				}
				echo esc_html( $mysaline_died ? date_i18n( $mysaline_fmt, strtotime( $mysaline_died ) ) : '' );
				?>
			</p>
		<?php endif; ?>
	</div>
</article>
