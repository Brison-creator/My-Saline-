<?php
/**
 * Homepage quick-link callout cards.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! get_theme_mod( 'mysaline_quicklinks_enable', true ) ) {
	return;
}

$mysaline_cards = array();
$mysaline_total = defined( 'MYSALINE_QUICKLINKS' ) ? MYSALINE_QUICKLINKS : 4;

for ( $mysaline_i = 1; $mysaline_i <= $mysaline_total; $mysaline_i++ ) {
	$mysaline_title = get_theme_mod( "mysaline_quicklink_{$mysaline_i}_title", '' );
	if ( ! $mysaline_title ) {
		continue;
	}
	$mysaline_cards[] = array(
		'title' => $mysaline_title,
		'icon'  => get_theme_mod( "mysaline_quicklink_{$mysaline_i}_icon", '⭐' ),
		'url'   => get_theme_mod( "mysaline_quicklink_{$mysaline_i}_url", '' ),
	);
}

if ( empty( $mysaline_cards ) ) {
	return;
}
?>
<nav class="ms-quicklinks" aria-label="<?php esc_attr_e( 'Quick links', 'mysaline' ); ?>">
	<?php foreach ( $mysaline_cards as $mysaline_card ) : ?>
		<a class="ms-quicklink" href="<?php echo esc_url( $mysaline_card['url'] ? $mysaline_card['url'] : '#' ); ?>">
			<span class="ms-quicklink__icon" aria-hidden="true"><?php echo esc_html( $mysaline_card['icon'] ); ?></span>
			<span class="ms-quicklink__title"><?php echo esc_html( $mysaline_card['title'] ); ?></span>
		</a>
	<?php endforeach; ?>
</nav>
