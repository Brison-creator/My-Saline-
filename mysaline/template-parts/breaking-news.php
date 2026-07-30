<?php
/**
 * Breaking-news bar.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! mysaline_has_breaking() ) {
	return;
}

$mysaline_items = mysaline_get_breaking_items();
$mysaline_label = get_theme_mod( 'mysaline_breaking_label', __( 'Breaking', 'mysaline' ) );
?>
<div class="ms-breaking" role="alert">
	<span class="ms-breaking__label"><?php echo esc_html( $mysaline_label ); ?></span>
	<div class="ms-breaking__track">
		<div class="ms-breaking__items">
			<?php
			// Print items twice so the marquee animation loops seamlessly.
			for ( $mysaline_pass = 0; $mysaline_pass < 2; $mysaline_pass++ ) :
				foreach ( $mysaline_items as $mysaline_item ) :
					if ( ! empty( $mysaline_item['url'] ) ) :
						?>
						<span class="ms-breaking__item"><a href="<?php echo esc_url( $mysaline_item['url'] ); ?>"><?php echo esc_html( $mysaline_item['text'] ); ?></a></span>
						<?php
					else :
						?>
						<span class="ms-breaking__item"><?php echo esc_html( $mysaline_item['text'] ); ?></span>
						<?php
					endif;
				endforeach;
			endfor;
			?>
		</div>
	</div>
</div>
