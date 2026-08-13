<?php
/**
 * Meta boxes: featured-story flag and the detail fields for each CPT.
 *
 * All fields are saved with nonce verification, capability checks and
 * per-type sanitizing.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field definitions per post type. Keeps rendering + saving in one place.
 *
 * type: text | textarea | url | email | date | time | number | select | checkbox
 *
 * @return array
 */
function mysaline_meta_fields() {
	return array(
		'post'        => array(
			'_ms_featured' => array(
				'label' => __( 'Mark as Featured Story (shows in the homepage hero)', 'mysaline' ),
				'type'  => 'checkbox',
				'box'   => 'featured',
			),
		),
		'ms_event'    => array(
			'_ms_event_start'     => array( 'label' => __( 'Start date', 'mysaline' ), 'type' => 'date' ),
			'_ms_event_end'       => array( 'label' => __( 'End date', 'mysaline' ), 'type' => 'date' ),
			'_ms_event_time'      => array( 'label' => __( 'Time', 'mysaline' ), 'type' => 'text', 'placeholder' => 'e.g. 6:00 PM – 9:00 PM' ),
			'_ms_event_venue'     => array( 'label' => __( 'Venue / location name', 'mysaline' ), 'type' => 'text' ),
			'_ms_event_address'   => array( 'label' => __( 'Address', 'mysaline' ), 'type' => 'text' ),
			'_ms_event_cost'      => array( 'label' => __( 'Cost', 'mysaline' ), 'type' => 'text', 'placeholder' => 'e.g. Free, $10' ),
			'_ms_event_organizer' => array( 'label' => __( 'Organizer', 'mysaline' ), 'type' => 'text' ),
			'_ms_event_link'      => array( 'label' => __( 'Tickets / info link', 'mysaline' ), 'type' => 'url' ),
		),
		'ms_obituary' => array(
			'_ms_obit_born'      => array( 'label' => __( 'Date of birth', 'mysaline' ), 'type' => 'date' ),
			'_ms_obit_died'      => array( 'label' => __( 'Date of passing', 'mysaline' ), 'type' => 'date' ),
			'_ms_obit_age'       => array( 'label' => __( 'Age', 'mysaline' ), 'type' => 'number' ),
			'_ms_obit_city'      => array( 'label' => __( 'City / town', 'mysaline' ), 'type' => 'text' ),
			'_ms_obit_service'   => array( 'label' => __( 'Service date & time', 'mysaline' ), 'type' => 'text' ),
			'_ms_obit_location'  => array( 'label' => __( 'Service location', 'mysaline' ), 'type' => 'text' ),
			'_ms_obit_home'      => array( 'label' => __( 'Funeral home', 'mysaline' ), 'type' => 'text' ),
			'_ms_obit_home_link' => array( 'label' => __( 'Funeral home website', 'mysaline' ), 'type' => 'url' ),
		),
		'ms_business' => array(
			'_ms_biz_phone'   => array( 'label' => __( 'Phone', 'mysaline' ), 'type' => 'text' ),
			'_ms_biz_email'   => array( 'label' => __( 'Email', 'mysaline' ), 'type' => 'email' ),
			'_ms_biz_website' => array( 'label' => __( 'Website', 'mysaline' ), 'type' => 'url' ),
			'_ms_biz_address' => array( 'label' => __( 'Address', 'mysaline' ), 'type' => 'textarea' ),
			'_ms_biz_hours'   => array( 'label' => __( 'Hours', 'mysaline' ), 'type' => 'textarea', 'placeholder' => "Mon–Fri 9–5\nSat 10–2" ),
			'_ms_biz_featured' => array( 'label' => __( 'Feature in the homepage Business Spotlight', 'mysaline' ), 'type' => 'checkbox' ),
		),
		'ms_job'      => array(
			'_ms_job_employer' => array( 'label' => __( 'Employer', 'mysaline' ), 'type' => 'text' ),
			'_ms_job_location' => array( 'label' => __( 'Location', 'mysaline' ), 'type' => 'text', 'placeholder' => 'e.g. Benton, AR' ),
			'_ms_job_type'     => array(
				'label'   => __( 'Employment type', 'mysaline' ),
				'type'    => 'select',
				'choices' => 'mysaline_job_type_choices',
			),
			'_ms_job_pay'      => array( 'label' => __( 'Pay', 'mysaline' ), 'type' => 'text', 'placeholder' => 'e.g. $18–$22/hr, or leave blank' ),
			'_ms_job_apply'    => array( 'label' => __( 'How to apply (link)', 'mysaline' ), 'type' => 'url' ),
			'_ms_job_email'    => array( 'label' => __( 'How to apply (email)', 'mysaline' ), 'type' => 'email' ),
			'_ms_job_closes'   => array( 'label' => __( 'Closing date', 'mysaline' ), 'type' => 'date' ),
			'_ms_job_featured' => array( 'label' => __( 'Feature this listing at the top of the jobs board', 'mysaline' ), 'type' => 'checkbox' ),
		),
		'ms_ad'       => array(
			'_ms_ad_link'    => array( 'label' => __( 'Click-through URL', 'mysaline' ), 'type' => 'url' ),
			'_ms_ad_zone'    => array(
				'label'   => __( 'Placement zone', 'mysaline' ),
				'type'    => 'select',
				'choices' => 'mysaline_ad_zone_choices',
			),
			'_ms_ad_sponsor' => array( 'label' => __( 'Sponsor name', 'mysaline' ), 'type' => 'text' ),
			'_ms_ad_start'   => array( 'label' => __( 'Start showing on', 'mysaline' ), 'type' => 'date' ),
			'_ms_ad_end'     => array( 'label' => __( 'Stop showing on', 'mysaline' ), 'type' => 'date' ),
			'_ms_ad_code'    => array(
				'label'       => __( 'Ad code (optional)', 'mysaline' ),
				'type'        => 'textarea',
				'placeholder' => __( 'Paste ad-network / AdSense code here to use instead of an image.', 'mysaline' ),
			),
			'_ms_ad_new_tab' => array( 'label' => __( 'Open in a new tab', 'mysaline' ), 'type' => 'checkbox' ),
		),
	);
}

/**
 * Register the meta boxes.
 */
function mysaline_add_meta_boxes() {
	add_meta_box( 'mysaline_featured', __( 'Featured Story', 'mysaline' ), 'mysaline_render_featured_box', 'post', 'side', 'high' );
	add_meta_box( 'mysaline_hub', __( 'Section Hub Options', 'mysaline' ), 'mysaline_render_hub_box', 'page', 'side', 'default' );
	add_meta_box( 'mysaline_event', __( 'Event Details', 'mysaline' ), 'mysaline_render_cpt_box', 'ms_event', 'normal', 'high' );
	add_meta_box( 'mysaline_obit', __( 'Obituary Details', 'mysaline' ), 'mysaline_render_cpt_box', 'ms_obituary', 'normal', 'high' );
	add_meta_box( 'mysaline_biz', __( 'Business Details', 'mysaline' ), 'mysaline_render_cpt_box', 'ms_business', 'normal', 'high' );
	add_meta_box( 'mysaline_job', __( 'Job Details', 'mysaline' ), 'mysaline_render_cpt_box', 'ms_job', 'normal', 'high' );
	add_meta_box( 'mysaline_ad', __( 'Advertisement Settings', 'mysaline' ), 'mysaline_render_cpt_box', 'ms_ad', 'normal', 'high' );
	add_meta_box( 'mysaline_ad_help', __( 'How this ad shows', 'mysaline' ), 'mysaline_render_ad_help', 'ms_ad', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'mysaline_add_meta_boxes' );

/**
 * The featured-story checkbox box (posts).
 *
 * @param WP_Post $post Post.
 */
function mysaline_render_featured_box( $post ) {
	wp_nonce_field( 'mysaline_save_meta', 'mysaline_meta_nonce' );
	$value = get_post_meta( $post->ID, '_ms_featured', true );
	?>
	<label style="display:flex;gap:.5rem;align-items:flex-start">
		<input type="checkbox" name="_ms_featured" value="1" <?php checked( $value, '1' ); ?> />
		<span><?php esc_html_e( 'Show this story in the homepage featured hero.', 'mysaline' ); ?></span>
	</label>
	<?php
}

/**
 * Section Hub options for pages: an icon and an optional category feed.
 *
 * Only meaningful on the "Section Hub" page template, so the box explains that
 * rather than appearing without context.
 *
 * @param WP_Post $post Post.
 */
function mysaline_render_hub_box( $post ) {
	wp_nonce_field( 'mysaline_save_meta', 'mysaline_meta_nonce' );
	$icon = get_post_meta( $post->ID, '_ms_hub_icon', true );
	$cat  = (int) get_post_meta( $post->ID, '_ms_hub_category', true );
	?>
	<p class="description" style="margin-top:0">
		<?php esc_html_e( 'Used when this page uses the “Section Hub” template, or when it appears as a card on a parent hub.', 'mysaline' ); ?>
	</p>
	<p>
		<label for="_ms_hub_icon"><strong><?php esc_html_e( 'Card icon (emoji)', 'mysaline' ); ?></strong></label>
		<input type="text" id="_ms_hub_icon" name="_ms_hub_icon" value="<?php echo esc_attr( $icon ); ?>" style="width:100%" placeholder="📅" />
	</p>
	<p>
		<label for="_ms_hub_category"><strong><?php esc_html_e( 'Show latest posts from', 'mysaline' ); ?></strong></label>
		<?php
		wp_dropdown_categories(
			array(
				'show_option_none' => __( '— No post feed —', 'mysaline' ),
				'option_none_value' => 0,
				'selected'         => $cat,
				'name'             => '_ms_hub_category',
				'id'               => '_ms_hub_category',
				'class'            => 'widefat',
				'hide_empty'       => false,
			)
		);
		?>
	</p>
	<?php
}

/**
 * Generic CPT detail box renderer, driven by mysaline_meta_fields().
 *
 * @param WP_Post $post Post.
 */
function mysaline_render_cpt_box( $post ) {
	wp_nonce_field( 'mysaline_save_meta', 'mysaline_meta_nonce' );
	$all    = mysaline_meta_fields();
	$fields = isset( $all[ $post->post_type ] ) ? $all[ $post->post_type ] : array();

	echo '<div class="mysaline-fields">';
	foreach ( $fields as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		$id    = esc_attr( $key );
		echo '<p class="mysaline-field mysaline-field--' . esc_attr( $field['type'] ) . '">';

		if ( 'checkbox' === $field['type'] ) {
			printf(
				'<label><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s /> %3$s</label>',
				$id,
				checked( $value, '1', false ),
				esc_html( $field['label'] )
			);
		} else {
			printf( '<label for="%1$s"><strong>%2$s</strong></label>', $id, esc_html( $field['label'] ) );

			$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

			switch ( $field['type'] ) {
				case 'textarea':
					printf(
						'<textarea id="%1$s" name="%1$s" rows="3" placeholder="%2$s" style="width:100%%">%3$s</textarea>',
						$id,
						esc_attr( $placeholder ),
						esc_textarea( $value )
					);
					break;
				case 'select':
					$choices = is_callable( $field['choices'] ) ? call_user_func( $field['choices'] ) : array();
					echo '<select id="' . $id . '" name="' . $id . '" style="width:100%">';
					foreach ( $choices as $ckey => $clabel ) {
						printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $ckey ), selected( $value, $ckey, false ), esc_html( $clabel ) );
					}
					echo '</select>';
					break;
				default:
					printf(
						'<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" placeholder="%4$s" style="width:100%%" />',
						esc_attr( $field['type'] ),
						$id,
						esc_attr( $value ),
						esc_attr( $placeholder )
					);
			}
		}
		echo '</p>';
	}
	echo '</div>';
}

/**
 * Small helper box on the ad editor explaining image vs code.
 */
function mysaline_render_ad_help() {
	echo '<p>' . esc_html__( 'Set a Photo (right sidebar) to show an image ad, or paste ad code below. Add a click-through URL for image ads.', 'mysaline' ) . '</p>';
	echo '<p>' . esc_html__( 'The zone decides where it appears. Leave start/stop dates blank to run indefinitely.', 'mysaline' ) . '</p>';
}

/**
 * Save handler for every meta box.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 */
function mysaline_save_meta( $post_id, $post ) {
	// Nonce.
	if ( ! isset( $_POST['mysaline_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['mysaline_meta_nonce'] ), 'mysaline_save_meta' ) ) {
		return;
	}
	// Autosave / capabilities.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Section Hub options live on pages and are handled separately, since they
	// are not part of the generic field table.
	if ( 'page' === $post->post_type ) {
		if ( isset( $_POST['_ms_hub_icon'] ) ) {
			$icon = sanitize_text_field( wp_unslash( $_POST['_ms_hub_icon'] ) );
			if ( '' === $icon ) {
				delete_post_meta( $post_id, '_ms_hub_icon' );
			} else {
				update_post_meta( $post_id, '_ms_hub_icon', $icon );
			}
		}
		if ( isset( $_POST['_ms_hub_category'] ) ) {
			$hub_cat = absint( $_POST['_ms_hub_category'] );
			if ( ! $hub_cat ) {
				delete_post_meta( $post_id, '_ms_hub_category' );
			} else {
				update_post_meta( $post_id, '_ms_hub_category', $hub_cat );
			}
		}
	}

	$all = mysaline_meta_fields();

	// Featured flag applies to standard posts.
	$fields = isset( $all[ $post->post_type ] ) ? $all[ $post->post_type ] : array();

	foreach ( $fields as $key => $field ) {
		$type = $field['type'];

		if ( 'checkbox' === $type ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, '1' );
			} else {
				delete_post_meta( $post_id, $key );
			}
			continue;
		}

		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}

		$raw = wp_unslash( $_POST[ $key ] );

		switch ( $type ) {
			case 'url':
				$clean = esc_url_raw( $raw );
				break;
			case 'email':
				$clean = sanitize_email( $raw );
				break;
			case 'number':
				$clean = '' === $raw ? '' : (string) floatval( $raw );
				break;
			case 'textarea':
				if ( '_ms_ad_code' === $key ) {
					// Ad-network / AdSense snippets need <script>. Mirror WordPress
					// core post-content behaviour: keep raw markup only for users
					// allowed unfiltered HTML; otherwise fall back to safe kses.
					$clean = current_user_can( 'unfiltered_html' ) ? $raw : wp_kses_post( $raw );
				} else {
					$clean = sanitize_textarea_field( $raw );
				}
				break;
			case 'date':
			case 'time':
			default:
				$clean = sanitize_text_field( $raw );
		}

		if ( '' === $clean ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $clean );
		}
	}
}
add_action( 'save_post', 'mysaline_save_meta', 10, 2 );

/**
 * Show a "Featured" column on the Posts list table.
 *
 * @param array $columns Columns.
 * @return array
 */
function mysaline_posts_featured_column( $columns ) {
	$columns['ms_featured'] = __( 'Featured', 'mysaline' );
	return $columns;
}
add_filter( 'manage_post_posts_columns', 'mysaline_posts_featured_column' );

/**
 * Render the featured column value.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function mysaline_posts_featured_column_value( $column, $post_id ) {
	if ( 'ms_featured' === $column && get_post_meta( $post_id, '_ms_featured', true ) ) {
		echo '<span aria-label="' . esc_attr__( 'Featured', 'mysaline' ) . '" title="' . esc_attr__( 'Featured', 'mysaline' ) . '" class="dashicons dashicons-star-filled" style="color:#e3a93c"></span>';
	}
}
add_action( 'manage_post_posts_custom_column', 'mysaline_posts_featured_column_value', 10, 2 );

/**
 * Employment types offered on a job listing.
 *
 * @return array
 */
function mysaline_job_type_choices() {
	return array(
		'full-time'  => __( 'Full time', 'mysaline' ),
		'part-time'  => __( 'Part time', 'mysaline' ),
		'seasonal'   => __( 'Seasonal', 'mysaline' ),
		'contract'   => __( 'Contract', 'mysaline' ),
		'internship' => __( 'Internship', 'mysaline' ),
		'volunteer'  => __( 'Volunteer', 'mysaline' ),
	);
}
