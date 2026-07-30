<?php
/**
 * Newsletter signup form. Posts directly to the provider action URL set in the
 * Customizer (e.g. Mailchimp), so no server-side code or API keys are required.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mysaline_action = get_theme_mod( 'mysaline_news_action', '' );
if ( ! get_theme_mod( 'mysaline_news_enable', true ) || ! $mysaline_action ) {
	return;
}

$mysaline_title  = get_theme_mod( 'mysaline_news_title', __( 'Get the MySaline newsletter', 'mysaline' ) );
$mysaline_text   = get_theme_mod( 'mysaline_news_text', '' );
$mysaline_field  = get_theme_mod( 'mysaline_news_email_field', 'EMAIL' );
$mysaline_button = get_theme_mod( 'mysaline_news_button', __( 'Subscribe', 'mysaline' ) );
?>
<div class="ms-newsletter">
	<?php if ( $mysaline_title ) : ?>
		<h2><?php echo esc_html( $mysaline_title ); ?></h2>
	<?php endif; ?>
	<?php if ( $mysaline_text ) : ?>
		<p><?php echo esc_html( $mysaline_text ); ?></p>
	<?php endif; ?>
	<form class="ms-newsletter__form" action="<?php echo esc_url( $mysaline_action ); ?>" method="post" target="_blank" novalidate>
		<label class="screen-reader-text" for="ms-newsletter-email"><?php esc_html_e( 'Email address', 'mysaline' ); ?></label>
		<input id="ms-newsletter-email" type="email" name="<?php echo esc_attr( $mysaline_field ); ?>" placeholder="<?php esc_attr_e( 'you@example.com', 'mysaline' ); ?>" required />
		<button type="submit" class="ms-btn"><?php echo esc_html( $mysaline_button ); ?></button>
	</form>
</div>
