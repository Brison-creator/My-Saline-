<?php
/**
 * Plugin Name: MySaline Dev Mail Catcher
 * Description: Development only. Captures outgoing email to dev/mail.log instead of sending, and surfaces Favorites confirmation links in the admin so the ballot can be tested without SMTP.
 * Version: 1.0.0
 *
 * IMPORTANT: this lives in /dev and is loaded only by the local wp-env
 * environment. It is never part of the packaged theme ZIP, so it can never
 * reach production. build.sh copies from /mysaline only.
 *
 * @package MySaline\Dev
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extra guard: refuse to do anything outside a local environment.
if ( function_exists( 'wp_get_environment_type' ) && 'local' !== wp_get_environment_type() ) {
	return;
}

/**
 * Path to the captured-mail log. Mapped to ./dev/mail.log on the host.
 *
 * @return string
 */
function mysaline_dev_mail_log() {
	return WP_CONTENT_DIR . '/mysaline-dev/mail.log';
}

/**
 * Short-circuit wp_mail and write the message to the log instead.
 *
 * Returning a non-null value from this filter makes wp_mail return it without
 * sending, so nothing leaves the machine.
 *
 * @param null|bool $short Whether to short-circuit.
 * @param array     $atts  wp_mail arguments.
 * @return bool Always true, so calling code sees a successful send.
 */
function mysaline_dev_capture_mail( $short, $atts ) {
	$to      = isset( $atts['to'] ) ? $atts['to'] : '';
	$subject = isset( $atts['subject'] ) ? $atts['subject'] : '';
	$message = isset( $atts['message'] ) ? $atts['message'] : '';

	if ( is_array( $to ) ) {
		$to = implode( ', ', $to );
	}

	$entry  = str_repeat( '=', 74 ) . "\n";
	$entry .= 'DATE:    ' . gmdate( 'Y-m-d H:i:s' ) . " UTC\n";
	$entry .= 'TO:      ' . $to . "\n";
	$entry .= 'SUBJECT: ' . $subject . "\n";
	$entry .= str_repeat( '-', 74 ) . "\n";
	$entry .= $message . "\n";

	// Pull any confirmation link out for quick copy/paste.
	if ( preg_match( '#https?://\S*ms_fav_confirm=\w+#', $message, $m ) ) {
		$entry .= "\n>>> CONFIRM LINK: " . $m[0] . "\n";
		set_transient( 'mysaline_dev_last_confirm', $m[0], DAY_IN_SECONDS );
	}
	$entry .= "\n";

	$dir = dirname( mysaline_dev_mail_log() );
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	file_put_contents( mysaline_dev_mail_log(), $entry, FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

	return true;
}
add_filter( 'pre_wp_mail', 'mysaline_dev_capture_mail', 10, 2 );

/**
 * Show the most recent Favorites confirmation link as an admin notice, so the
 * ballot's double opt-in can be exercised in one click during testing.
 */
function mysaline_dev_confirm_notice() {
	$link = get_transient( 'mysaline_dev_last_confirm' );
	if ( ! $link ) {
		return;
	}
	echo '<div class="notice notice-info is-dismissible"><p><strong>Dev mail catcher:</strong> latest Favorites confirmation link &mdash; ';
	echo '<a href="' . esc_url( $link ) . '">click to confirm that ballot</a>.';
	echo ' <em>Email is not actually sent locally; everything is logged to <code>dev/mail.log</code>.</em></p></div>';
}
add_action( 'admin_notices', 'mysaline_dev_confirm_notice' );

/**
 * Make it obvious in the admin bar that mail is being captured, not sent.
 *
 * @param WP_Admin_Bar $bar Admin bar.
 */
function mysaline_dev_admin_bar( $bar ) {
	$bar->add_node(
		array(
			'id'    => 'mysaline-dev-mail',
			'title' => '✉ Mail captured (dev)',
			'href'  => admin_url( 'edit.php?post_type=ms_fav_category&page=mysaline-fav-results' ),
			'meta'  => array( 'title' => 'Outgoing email is written to dev/mail.log instead of being sent.' ),
		)
	);
}
add_action( 'admin_bar_menu', 'mysaline_dev_admin_bar', 100 );
