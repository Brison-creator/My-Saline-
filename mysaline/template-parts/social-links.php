<?php
/**
 * Social links row. Uses Customizer URLs and inline SVG icons.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mysaline_links = mysaline_get_social_links();
if ( empty( $mysaline_links ) ) {
	return;
}

$mysaline_icons = mysaline_social_icons();
$mysaline_labels = array(
	'facebook'  => 'Facebook',
	'instagram' => 'Instagram',
	'twitter'   => 'X',
	'youtube'   => 'YouTube',
	'tiktok'    => 'TikTok',
	'linkedin'  => 'LinkedIn',
	'rss'       => 'RSS',
);
?>
<div class="ms-social">
	<?php foreach ( $mysaline_links as $mysaline_key => $mysaline_url ) : ?>
		<a href="<?php echo esc_url( $mysaline_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( isset( $mysaline_labels[ $mysaline_key ] ) ? $mysaline_labels[ $mysaline_key ] : $mysaline_key ); ?>">
			<?php
			// Icons are hand-written, trusted SVG strings.
			echo isset( $mysaline_icons[ $mysaline_key ] ) ? $mysaline_icons[ $mysaline_key ] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</a>
	<?php endforeach; ?>
</div>
