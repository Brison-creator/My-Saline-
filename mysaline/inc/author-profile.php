<?php
/**
 * Author profile photos.
 *
 * WordPress avatars come from Gravatar, which is keyed to an email address the
 * writer had to register somewhere else. Community-newspaper contributors —
 * stringers, columnists, the person who covers the school board — almost never
 * have one, so every byline falls back to the grey mystery-person icon and the
 * author box looks broken.
 *
 * This adds a "Profile photo" field to the normal WordPress profile screen and
 * uses it wherever an avatar is requested. It is purely additive: users with a
 * Gravatar and no uploaded photo keep the Gravatar, and clearing the field
 * restores the previous behaviour.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MYSALINE_AVATAR_META = 'mysaline_profile_photo';
const MYSALINE_TITLE_META  = 'mysaline_role_title';

/**
 * Render the profile-photo field on the user profile screen.
 *
 * @param WP_User $user The user being edited.
 */
function mysaline_avatar_field( $user ) {
	if ( ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}

	$id  = (int) get_user_meta( $user->ID, MYSALINE_AVATAR_META, true );
	$src = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
	?>
	<h2><?php esc_html_e( 'MySaline profile', 'mysaline' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="mysaline-role-title"><?php esc_html_e( 'Title', 'mysaline' ); ?></label></th>
			<td>
				<input type="text" class="regular-text" id="mysaline-role-title" name="mysaline_role_title"
					value="<?php echo esc_attr( (string) get_user_meta( $user->ID, MYSALINE_TITLE_META, true ) ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. Publisher, Managing Editor, Sports Writer', 'mysaline' ); ?>" />
				<p class="description">
					<?php esc_html_e( 'Shown above the name on this writer’s page. Leave empty to fall back to the WordPress role.', 'mysaline' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><label for="mysaline-profile-photo"><?php esc_html_e( 'Photo', 'mysaline' ); ?></label></th>
			<td>
				<div class="mysaline-avatar-field">
					<img
						src="<?php echo esc_url( $src ); ?>"
						alt=""
						class="mysaline-avatar-preview"
						style="<?php echo $src ? 'width:96px;height:96px;object-fit:cover;border-radius:50%;display:block;margin-bottom:.6rem' : 'display:none'; ?>"
					/>
					<input type="hidden" id="mysaline-profile-photo" name="mysaline_profile_photo" value="<?php echo esc_attr( $id ? $id : '' ); ?>" />
					<button type="button" class="button mysaline-avatar-choose"><?php esc_html_e( 'Choose photo', 'mysaline' ); ?></button>
					<button type="button" class="button-link mysaline-avatar-clear" style="margin-left:.6rem<?php echo $id ? '' : ';display:none'; ?>"><?php esc_html_e( 'Remove', 'mysaline' ); ?></button>
					<p class="description">
						<?php esc_html_e( 'Shown on bylines, the author box and the columnist archive. If left empty, the Gravatar for this email address is used.', 'mysaline' ); ?>
					</p>
				</div>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'mysaline_avatar_field' );
add_action( 'edit_user_profile', 'mysaline_avatar_field' );

/**
 * Save the profile-photo field.
 *
 * @param int $user_id User being saved.
 * @return void
 */
function mysaline_avatar_save( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	// The profile screen supplies its own nonce; confirm it rather than trusting
	// the POST, so another form cannot overwrite the field.
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'update-user_' . $user_id ) ) {
		return;
	}

	if ( isset( $_POST['mysaline_role_title'] ) ) {
		$title = sanitize_text_field( wp_unslash( $_POST['mysaline_role_title'] ) );
		if ( '' !== $title ) {
			update_user_meta( $user_id, MYSALINE_TITLE_META, $title );
		} else {
			delete_user_meta( $user_id, MYSALINE_TITLE_META );
		}
	}

	if ( ! isset( $_POST['mysaline_profile_photo'] ) ) {
		return;
	}

	$id = absint( wp_unslash( $_POST['mysaline_profile_photo'] ) );

	// Only a real image attachment may be stored; anything else clears it.
	if ( $id && 'attachment' === get_post_type( $id ) && wp_attachment_is_image( $id ) ) {
		update_user_meta( $user_id, MYSALINE_AVATAR_META, $id );
	} else {
		delete_user_meta( $user_id, MYSALINE_AVATAR_META );
	}
}
add_action( 'personal_options_update', 'mysaline_avatar_save' );
add_action( 'edit_user_profile_update', 'mysaline_avatar_save' );

/**
 * Resolve the user ID behind whatever get_avatar() was handed.
 *
 * Callers pass a user ID, an email, a WP_User, a WP_Post or a WP_Comment, so
 * each has to be unwrapped before the meta lookup.
 *
 * @param mixed $id_or_email Avatar subject.
 * @return int User ID, or 0 when it cannot be resolved.
 */
function mysaline_avatar_user_id( $id_or_email ) {
	if ( is_numeric( $id_or_email ) ) {
		return (int) $id_or_email;
	}
	if ( $id_or_email instanceof WP_User ) {
		return (int) $id_or_email->ID;
	}
	if ( $id_or_email instanceof WP_Post ) {
		return (int) $id_or_email->post_author;
	}
	if ( $id_or_email instanceof WP_Comment ) {
		return (int) $id_or_email->user_id;
	}
	if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
		return $user ? (int) $user->ID : 0;
	}
	return 0;
}

/**
 * Swap in the uploaded photo when one is set.
 *
 * Hooked to pre_get_avatar_data rather than get_avatar_data: core builds the
 * Gravatar URL *before* firing the latter, so filtering there would either
 * arrive too late to matter or require clobbering a URL another plugin set.
 * Short-circuiting up front also means no Gravatar request is made at all.
 *
 * @param array $args        Avatar arguments.
 * @param mixed $id_or_email Avatar subject.
 * @return array
 */
function mysaline_avatar_data( $args, $id_or_email ) {
	// A caller that already forced a URL is left alone.
	if ( ! empty( $args['url'] ) ) {
		return $args;
	}

	$user_id = mysaline_avatar_user_id( $id_or_email );
	if ( ! $user_id ) {
		return $args;
	}

	$attachment = (int) get_user_meta( $user_id, MYSALINE_AVATAR_META, true );
	if ( ! $attachment ) {
		return $args;
	}

	$size = isset( $args['size'] ) ? (int) $args['size'] : 96;

	// Ask for a crop at roughly the requested size so a 400px author-box photo
	// is not served to a 40px byline.
	$src = wp_get_attachment_image_src( $attachment, array( $size, $size ) );
	if ( ! $src ) {
		return $args;
	}

	$args['url']          = $src[0];
	$args['found_avatar'] = true;

	return $args;
}
add_filter( 'pre_get_avatar_data', 'mysaline_avatar_data', 10, 2 );

/**
 * Load the media picker on the profile screen only.
 *
 * @param string $hook Current admin page.
 */
function mysaline_avatar_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'profile.php', 'user-edit.php' ), true ) ) {
		return;
	}

	wp_enqueue_media();
	wp_add_inline_script(
		'jquery-core',
		"jQuery(function($){
			var frame;
			$('.mysaline-avatar-choose').on('click', function(e){
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({
					title: '" . esc_js( __( 'Select a profile photo', 'mysaline' ) ) . "',
					library: { type: 'image' },
					button: { text: '" . esc_js( __( 'Use this photo', 'mysaline' ) ) . "' },
					multiple: false
				});
				frame.on('select', function(){
					var a = frame.state().get('selection').first().toJSON(),
						url = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
					$('#mysaline-profile-photo').val(a.id);
					$('.mysaline-avatar-preview').attr('src', url)
						.attr('style','width:96px;height:96px;object-fit:cover;border-radius:50%;display:block;margin-bottom:.6rem');
					$('.mysaline-avatar-clear').show();
				});
				frame.open();
			});
			$('.mysaline-avatar-clear').on('click', function(e){
				e.preventDefault();
				$('#mysaline-profile-photo').val('');
				$('.mysaline-avatar-preview').hide();
				$(this).hide();
			});
		});"
	);
}
add_action( 'admin_enqueue_scripts', 'mysaline_avatar_admin_assets' );
