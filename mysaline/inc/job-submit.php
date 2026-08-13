<?php
/**
 * Paid job submissions.
 *
 * Employers fill in a form, the listing is saved as *pending*, and they are
 * handed a payment link. Nothing publishes until the newsroom approves it.
 *
 * Why no card handling here: taking payment on-site means a payment plugin, a
 * merchant integration, and a small newspaper carrying PCI scope for the sake
 * of ten-dollar transactions. A Stripe Payment Link or a PayPal button costs
 * nothing, keeps card data entirely off this server, and the approval step is
 * the reconciliation — the listing goes live when the money has landed. It is
 * also how a person actually runs a small classifieds desk.
 *
 * The form is public, so it is treated as hostile: nonce, honeypot, a minimum
 * fill time, a per-IP rate limit, and every field sanitised on the way in. The
 * closing date is never taken from the form; it is computed from the purchased
 * run length so a submitter cannot buy thirty days and take a year.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether paid submissions are switched on and configured.
 *
 * @return bool
 */
function mysaline_job_submit_enabled() {
	return (bool) get_theme_mod( 'mysaline_job_submit_enable', true );
}

/**
 * The advertised price, as entered by the owner.
 *
 * @return string
 */
function mysaline_job_price() {
	return (string) get_theme_mod( 'mysaline_job_price', '$10' );
}

/**
 * How many days a paid listing runs.
 *
 * @return int
 */
function mysaline_job_run_days() {
	return max( 1, (int) get_theme_mod( 'mysaline_job_days', 30 ) );
}

/**
 * The short "$10 for 30 days" line used on buttons and headings.
 *
 * @return string
 */
function mysaline_job_price_line() {
	return sprintf(
		/* translators: 1: price, e.g. $10. 2: number of days. */
		_n( '%1$s for %2$d day', '%1$s for %2$d days', mysaline_job_run_days(), 'mysaline' ),
		mysaline_job_price(),
		mysaline_job_run_days()
	);
}

/**
 * Render the submission form.
 *
 * @return string
 */
function mysaline_job_submit_form() {
	if ( ! mysaline_job_submit_enabled() ) {
		return '';
	}

	$notice = '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag.
	$state = isset( $_GET['job'] ) ? sanitize_key( wp_unslash( $_GET['job'] ) ) : '';

	if ( 'thanks' === $state ) {
		$notice = '<div class="ms-fav-notice is-success"><p><strong>' .
			esc_html__( 'Listing received.', 'mysaline' ) . '</strong> ' .
			esc_html__( 'It goes live as soon as payment clears and we have had a look at it. If you closed the payment window, the link is in your confirmation email.', 'mysaline' ) .
			'</p></div>';
	} elseif ( 'error' === $state ) {
		$notice = '<div class="ms-fav-notice"><p>' .
			esc_html__( 'Something in the form was missing. Please check the required fields and try again.', 'mysaline' ) .
			'</p></div>';
	}

	ob_start();
	?>
	<div class="ms-jobform">
		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from escaped strings. ?>

		<div class="ms-jobform__price">
			<p class="ms-jobform__price-tag"><?php echo esc_html( mysaline_job_price_line() ); ?></p>
			<p class="ms-jobform__price-note">
				<?php esc_html_e( 'One flat price. Your listing appears on the jobs board and in the sidebar across the site, and comes down on its own when the run ends.', 'mysaline' ); ?>
			</p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ms-jobform__form">
			<input type="hidden" name="action" value="mysaline_submit_job" />
			<?php wp_nonce_field( 'mysaline_submit_job', 'mysaline_job_nonce' ); ?>
			<input type="hidden" name="ms_job_started" value="<?php echo esc_attr( time() ); ?>" />

			<?php // Honeypot. Real people never see it; bots fill everything in. ?>
			<div class="ms-jobform__hp" aria-hidden="true">
				<label for="ms-job-website"><?php esc_html_e( 'Leave this field empty', 'mysaline' ); ?></label>
				<input type="text" id="ms-job-website" name="ms_job_website" tabindex="-1" autocomplete="off" />
			</div>

			<p>
				<label for="ms-job-title"><?php esc_html_e( 'Job title', 'mysaline' ); ?> <span class="ms-req">*</span></label>
				<input type="text" id="ms-job-title" name="ms_job_title" required maxlength="120" />
			</p>
			<div class="ms-jobform__row">
				<p>
					<label for="ms-job-employer"><?php esc_html_e( 'Business name', 'mysaline' ); ?> <span class="ms-req">*</span></label>
					<input type="text" id="ms-job-employer" name="ms_job_employer" required maxlength="120" />
				</p>
				<p>
					<label for="ms-job-location"><?php esc_html_e( 'Location', 'mysaline' ); ?></label>
					<input type="text" id="ms-job-location" name="ms_job_location" placeholder="Benton, AR" maxlength="120" />
				</p>
			</div>
			<div class="ms-jobform__row">
				<p>
					<label for="ms-job-type"><?php esc_html_e( 'Employment type', 'mysaline' ); ?></label>
					<select id="ms-job-type" name="ms_job_type">
						<?php foreach ( mysaline_job_type_choices() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<label for="ms-job-pay"><?php esc_html_e( 'Pay (optional)', 'mysaline' ); ?></label>
					<input type="text" id="ms-job-pay" name="ms_job_pay" placeholder="$18–$22/hr" maxlength="80" />
				</p>
			</div>
			<p>
				<label for="ms-job-desc"><?php esc_html_e( 'Description', 'mysaline' ); ?> <span class="ms-req">*</span></label>
				<textarea id="ms-job-desc" name="ms_job_desc" rows="7" required maxlength="4000"></textarea>
			</p>
			<div class="ms-jobform__row">
				<p>
					<label for="ms-job-apply"><?php esc_html_e( 'Application link', 'mysaline' ); ?></label>
					<input type="url" id="ms-job-apply" name="ms_job_apply" placeholder="https://" />
				</p>
				<p>
					<label for="ms-job-applyemail"><?php esc_html_e( 'Or application email', 'mysaline' ); ?></label>
					<input type="email" id="ms-job-applyemail" name="ms_job_applyemail" />
				</p>
			</div>
			<p>
				<label for="ms-job-contact"><?php esc_html_e( 'Your email (for our records, not published)', 'mysaline' ); ?> <span class="ms-req">*</span></label>
				<input type="email" id="ms-job-contact" name="ms_job_contact" required />
			</p>

			<p class="ms-jobform__submit">
				<button type="submit" class="ms-btn">
					<?php
					printf(
						/* translators: %s: price line, e.g. "$10 for 30 days". */
						esc_html__( 'Continue to payment — %s', 'mysaline' ),
						esc_html( mysaline_job_price_line() )
					);
					?>
				</button>
			</p>
			<p class="ms-jobform__fineprint">
				<?php esc_html_e( 'Nothing is charged on this site. You will be sent to our payment page, and your listing is published once payment clears.', 'mysaline' ); ?>
			</p>
		</form>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'mysaline_post_a_job', 'mysaline_job_submit_form' );

/**
 * Handle a submitted listing.
 *
 * Everything is rejected by default: without a valid nonce, an untouched
 * honeypot, a plausible fill time and the required fields, nothing is stored.
 */
function mysaline_handle_job_submission() {
	$referer = wp_get_referer();
	$back    = $referer ? $referer : home_url( '/' );

	$fail = static function ( $reason ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'job', $reason, remove_query_arg( 'job', $back ) ) );
		exit;
	};

	if ( ! mysaline_job_submit_enabled() ) {
		$fail( 'error' );
	}

	if ( ! isset( $_POST['mysaline_job_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mysaline_job_nonce'] ) ), 'mysaline_submit_job' ) ) {
		$fail( 'error' );
	}

	// Honeypot: any content at all means a bot.
	if ( ! empty( $_POST['ms_job_website'] ) ) {
		$fail( 'thanks' ); // Told it succeeded, so the bot does not retry.
	}

	// A human cannot read and complete this form in under four seconds.
	$started = isset( $_POST['ms_job_started'] ) ? absint( $_POST['ms_job_started'] ) : 0;
	if ( ! $started || ( time() - $started ) < 4 ) {
		$fail( 'thanks' );
	}

	// One submission per address per five minutes.
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'ms_jobsub_' . md5( $ip );
	if ( $ip && get_transient( $key ) ) {
		$fail( 'error' );
	}

	$title    = isset( $_POST['ms_job_title'] ) ? sanitize_text_field( wp_unslash( $_POST['ms_job_title'] ) ) : '';
	$employer = isset( $_POST['ms_job_employer'] ) ? sanitize_text_field( wp_unslash( $_POST['ms_job_employer'] ) ) : '';
	$desc     = isset( $_POST['ms_job_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ms_job_desc'] ) ) : '';
	$contact  = isset( $_POST['ms_job_contact'] ) ? sanitize_email( wp_unslash( $_POST['ms_job_contact'] ) ) : '';

	if ( '' === $title || '' === $employer || '' === $desc || ! is_email( $contact ) ) {
		$fail( 'error' );
	}

	$location = isset( $_POST['ms_job_location'] ) ? sanitize_text_field( wp_unslash( $_POST['ms_job_location'] ) ) : '';
	$pay      = isset( $_POST['ms_job_pay'] ) ? sanitize_text_field( wp_unslash( $_POST['ms_job_pay'] ) ) : '';
	$apply    = isset( $_POST['ms_job_apply'] ) ? esc_url_raw( wp_unslash( $_POST['ms_job_apply'] ) ) : '';
	$email    = isset( $_POST['ms_job_applyemail'] ) ? sanitize_email( wp_unslash( $_POST['ms_job_applyemail'] ) ) : '';

	$type    = isset( $_POST['ms_job_type'] ) ? sanitize_key( wp_unslash( $_POST['ms_job_type'] ) ) : 'full-time';
	$choices = mysaline_job_type_choices();
	if ( ! isset( $choices[ $type ] ) ) {
		$type = 'full-time';
	}

	// Pending, always. Nothing a stranger types appears on the site unread.
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'ms_job',
			'post_status'  => 'pending',
			'post_title'   => $title,
			'post_content' => $desc,
			'post_excerpt' => sprintf(
				/* translators: 1: employer, 2: location. */
				__( '%1$s is hiring in %2$s.', 'mysaline' ),
				$employer,
				$location ? $location : __( 'Saline County', 'mysaline' )
			),
		),
		true
	);

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		$fail( 'error' );
	}

	// The run length is taken from the purchased package, never from the form.
	$closes = gmdate( 'Y-m-d', strtotime( '+' . mysaline_job_run_days() . ' days', current_time( 'timestamp' ) ) );

	update_post_meta( $post_id, '_ms_job_employer', $employer );
	update_post_meta( $post_id, '_ms_job_location', $location );
	update_post_meta( $post_id, '_ms_job_type', $type );
	update_post_meta( $post_id, '_ms_job_pay', $pay );
	update_post_meta( $post_id, '_ms_job_apply', $apply );
	update_post_meta( $post_id, '_ms_job_email', $email );
	update_post_meta( $post_id, '_ms_job_closes', $closes );
	update_post_meta( $post_id, '_ms_job_submitter', $contact );
	update_post_meta( $post_id, '_ms_job_paid', '0' );

	set_transient( $key, 1, 5 * MINUTE_IN_SECONDS );

	// Tell the newsroom there is something to approve.
	wp_mail(
		get_option( 'admin_email' ),
		sprintf(
			/* translators: %s: job title. */
			__( 'New paid job listing awaiting approval: %s', 'mysaline' ),
			$title
		),
		sprintf(
			/* translators: 1: title, 2: employer, 3: contact email, 4: price line, 5: edit link. */
			__( "A job listing has been submitted and is waiting for payment and approval.\n\nTitle: %1\$s\nEmployer: %2\$s\nContact: %3\$s\nPackage: %4\$s\n\nApprove or edit it here:\n%5\$s\n\nPublish it once the payment has landed.", 'mysaline' ),
			$title,
			$employer,
			$contact,
			mysaline_job_price_line(),
			get_edit_post_link( $post_id, 'raw' )
		)
	);

	// Off to whatever payment page the owner has set.
	$pay_url = trim( (string) get_theme_mod( 'mysaline_job_pay_url', '' ) );
	if ( $pay_url ) {
		wp_redirect( esc_url_raw( $pay_url ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- deliberately off-site, to the payment processor.
		exit;
	}

	wp_safe_redirect( add_query_arg( 'job', 'thanks', remove_query_arg( 'job', $back ) ) );
	exit;
}
add_action( 'admin_post_nopriv_mysaline_submit_job', 'mysaline_handle_job_submission' );
add_action( 'admin_post_mysaline_submit_job', 'mysaline_handle_job_submission' );

/**
 * Show payment state in the Jobs list, so approving is a one-glance decision.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function mysaline_job_columns( $columns ) {
	$columns['ms_job_paid']   = __( 'Paid', 'mysaline' );
	$columns['ms_job_closes'] = __( 'Closes', 'mysaline' );

	return $columns;
}
add_filter( 'manage_ms_job_posts_columns', 'mysaline_job_columns' );

/**
 * Fill the custom Jobs columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Job.
 */
function mysaline_job_column_content( $column, $post_id ) {
	if ( 'ms_job_paid' === $column ) {
		$submitted = get_post_meta( $post_id, '_ms_job_submitter', true );
		if ( ! $submitted ) {
			echo '—';
			return;
		}
		$paid = get_post_meta( $post_id, '_ms_job_paid', true );
		echo $paid
			? '<span style="color:#1c7a52;font-weight:700">' . esc_html__( 'Paid', 'mysaline' ) . '</span>'
			: '<span style="color:#b2452f;font-weight:700">' . esc_html__( 'Awaiting payment', 'mysaline' ) . '</span>';
		echo '<br /><small>' . esc_html( $submitted ) . '</small>';
	}

	if ( 'ms_job_closes' === $column ) {
		$closes = get_post_meta( $post_id, '_ms_job_closes', true );
		echo $closes ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $closes ) ) ) : '—';
	}
}
add_action( 'manage_ms_job_posts_custom_column', 'mysaline_job_column_content', 10, 2 );

/**
 * Mark a submitted listing paid when it is published.
 *
 * Publishing is the moment the newsroom confirms the money arrived, so it is
 * the honest place to record it rather than asking for a second click.
 *
 * @param string  $new Old status.
 * @param string  $old New status.
 * @param WP_Post $post Post.
 */
function mysaline_job_mark_paid_on_publish( $new, $old, $post ) {
	if ( 'ms_job' !== $post->post_type || 'publish' !== $new || 'publish' === $old ) {
		return;
	}

	if ( get_post_meta( $post->ID, '_ms_job_submitter', true ) ) {
		update_post_meta( $post->ID, '_ms_job_paid', '1' );
	}
}
add_action( 'transition_post_status', 'mysaline_job_mark_paid_on_publish', 10, 3 );
