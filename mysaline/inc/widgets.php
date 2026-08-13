<?php
/**
 * Custom widgets: Ad Zone, Newsletter, Social Links, Recent Posts (with
 * thumbnails) and Upcoming Events. Registered so the owner can drag them into
 * any sidebar or footer column.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ad zone widget — drops any ad zone into a sidebar/footer.
 */
class MySaline_Ad_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'mysaline_ad',
			__( 'MySaline: Advertisement', 'mysaline' ),
			array( 'description' => __( 'Shows an ad from a chosen zone.', 'mysaline' ) )
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Sidebar args.
	 * @param array $instance Settings.
	 */
	public function widget( $args, $instance ) {
		$zone = ! empty( $instance['zone'] ) ? $instance['zone'] : 'sidebar';
		if ( empty( mysaline_get_ads( $zone, 1 ) ) ) {
			return;
		}
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		mysaline_ad( $zone );
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Settings form.
	 *
	 * @param array $instance Settings.
	 */
	public function form( $instance ) {
		$zone  = ! empty( $instance['zone'] ) ? $instance['zone'] : 'sidebar';
		$zones = mysaline_ad_zone_choices();
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'zone' ) ); ?>"><?php esc_html_e( 'Ad zone:', 'mysaline' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'zone' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'zone' ) ); ?>">
				<?php foreach ( $zones as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $zone, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Save.
	 *
	 * @param array $new New settings.
	 * @param array $old Old settings.
	 * @return array
	 */
	public function update( $new, $old ) {
		return array( 'zone' => sanitize_key( $new['zone'] ) );
	}
}

/**
 * Newsletter signup widget (uses Customizer newsletter settings).
 */
class MySaline_Newsletter_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'mysaline_newsletter',
			__( 'MySaline: Newsletter Signup', 'mysaline' ),
			array( 'description' => __( 'Newsletter form using your Customizer settings.', 'mysaline' ) )
		);
	}

	/**
	 * Output.
	 *
	 * @param array $args     Sidebar args.
	 * @param array $instance Settings.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		get_template_part( 'template-parts/newsletter' );
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Form.
	 *
	 * @param array $instance Settings.
	 */
	public function form( $instance ) {
		echo '<p>' . esc_html__( 'Configure text & provider under Customize → MySaline Options → Newsletter.', 'mysaline' ) . '</p>';
	}
}

/**
 * Social links widget.
 */
class MySaline_Social_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'mysaline_social',
			__( 'MySaline: Social Links', 'mysaline' ),
			array( 'description' => __( 'Your social icons from the Customizer.', 'mysaline' ) )
		);
	}

	/**
	 * Output.
	 *
	 * @param array $args     Sidebar args.
	 * @param array $instance Settings.
	 */
	public function widget( $args, $instance ) {
		if ( empty( mysaline_get_social_links() ) ) {
			return;
		}
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Follow Us', 'mysaline' );
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		get_template_part( 'template-parts/social-links' );
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Form.
	 *
	 * @param array $instance Settings.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Follow Us', 'mysaline' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'mysaline' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}

	/**
	 * Save.
	 *
	 * @param array $new New.
	 * @param array $old Old.
	 * @return array
	 */
	public function update( $new, $old ) {
		return array( 'title' => sanitize_text_field( $new['title'] ) );
	}
}

/**
 * Recent posts with thumbnails.
 */
class MySaline_Recent_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'mysaline_recent',
			__( 'MySaline: Recent Posts (with photos)', 'mysaline' ),
			array( 'description' => __( 'Latest stories with thumbnails.', 'mysaline' ) )
		);
	}

	/**
	 * Output.
	 *
	 * @param array $args     Sidebar args.
	 * @param array $instance Settings.
	 */
	public function widget( $args, $instance ) {
		$title  = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Recent News', 'mysaline' );
		$number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$cat    = ! empty( $instance['cat'] ) ? absint( $instance['cat'] ) : 0;

		$q_args = array(
			'posts_per_page'      => $number,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
		);
		if ( $cat ) {
			$q_args['cat'] = $cat;
		}
		$q = new WP_Query( $q_args );
		if ( ! $q->have_posts() ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="ms-recent-list">';
		while ( $q->have_posts() ) {
			$q->the_post();
			echo '<article class="ms-list-item">';
			mysaline_thumbnail( 'mysaline-thumb' );
			echo '<div><h4><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h4>';
			mysaline_post_meta( array( 'comments' => false, 'author' => false ) );
			echo '</div></article>';
		}
		echo '</div>';
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_reset_postdata();
	}

	/**
	 * Form.
	 *
	 * @param array $instance Settings.
	 */
	public function form( $instance ) {
		$title  = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Recent News', 'mysaline' );
		$number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$cat    = ! empty( $instance['cat'] ) ? absint( $instance['cat'] ) : 0;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'mysaline' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_html_e( 'Number of posts:', 'mysaline' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" min="1" max="15" value="<?php echo esc_attr( $number ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'cat' ) ); ?>"><?php esc_html_e( 'Limit to category:', 'mysaline' ); ?></label>
			<?php
			wp_dropdown_categories(
				array(
					'show_option_all' => __( 'All categories', 'mysaline' ),
					'selected'        => $cat,
					'name'            => $this->get_field_name( 'cat' ),
					'id'              => $this->get_field_id( 'cat' ),
					'class'           => 'widefat',
					'hide_empty'      => false,
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Save.
	 *
	 * @param array $new New.
	 * @param array $old Old.
	 * @return array
	 */
	public function update( $new, $old ) {
		return array(
			'title'  => sanitize_text_field( $new['title'] ),
			'number' => absint( $new['number'] ),
			'cat'    => absint( $new['cat'] ),
		);
	}
}

/**
 * Upcoming events widget.
 */
class MySaline_Events_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'mysaline_events',
			__( 'MySaline: Upcoming Events', 'mysaline' ),
			array( 'description' => __( 'Next community events by date.', 'mysaline' ) )
		);
	}

	/**
	 * Output.
	 *
	 * @param array $args     Sidebar args.
	 * @param array $instance Settings.
	 */
	public function widget( $args, $instance ) {
		$title  = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Upcoming Events', 'mysaline' );
		$number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 4;
		$q      = mysaline_upcoming_events( $number );
		if ( ! $q->have_posts() ) {
			return;
		}
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		while ( $q->have_posts() ) {
			$q->the_post();
			$parts = mysaline_event_date_parts();
			echo '<article class="ms-event-list-item">';
			if ( $parts ) {
				echo '<span class="ms-event-card__date"><span class="ms-day">' . esc_html( $parts['day'] ) . '</span><span class="ms-mon">' . esc_html( $parts['mon'] ) . '</span></span>';
			}
			echo '<div><h4><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h4>';
			$venue = get_post_meta( get_the_ID(), '_ms_event_venue', true );
			if ( $venue ) {
				echo '<span class="ms-card__meta">' . esc_html( $venue ) . '</span>';
			}
			echo '</div></article>';
		}
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_reset_postdata();
	}

	/**
	 * Form.
	 *
	 * @param array $instance Settings.
	 */
	public function form( $instance ) {
		$title  = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Upcoming Events', 'mysaline' );
		$number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 4;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'mysaline' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_html_e( 'Number to show:', 'mysaline' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" min="1" max="10" value="<?php echo esc_attr( $number ); ?>" />
		</p>
		<?php
	}

	/**
	 * Save.
	 *
	 * @param array $new New.
	 * @param array $old Old.
	 * @return array
	 */
	public function update( $new, $old ) {
		return array(
			'title'  => sanitize_text_field( $new['title'] ),
			'number' => absint( $new['number'] ),
		);
	}
}

/**
 * Register all custom widgets.
 */

/**
 * Local weather widget.
 */
class MySaline_Weather_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'mysaline_weather',
			__( 'MySaline: Local Weather', 'mysaline' ),
			array( 'description' => __( 'Current conditions and forecast from the National Weather Service.', 'mysaline' ) )
		);
	}

	/**
	 * Output.
	 *
	 * @param array $args     Sidebar args.
	 * @param array $instance Settings.
	 */
	public function widget( $args, $instance ) {
		$card = mysaline_weather_card();

		// A widget with nothing to say prints nothing, not an empty box.
		if ( '' === $card ) {
			return;
		}

		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Local Weather', 'mysaline' );

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo wp_kses( $card, mysaline_weather_kses() );
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Form.
	 *
	 * @param array $instance Settings.
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Local Weather', 'mysaline' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'mysaline' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
				value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p class="description"><?php esc_html_e( 'Set the location under Customize → MySaline Options → Local Weather.', 'mysaline' ); ?></p>
		<?php
	}

	/**
	 * Save.
	 *
	 * @param array $new Submitted values.
	 * @param array $old Previous values.
	 * @return array
	 */
	public function update( $new, $old ) {
		return array( 'title' => sanitize_text_field( isset( $new['title'] ) ? $new['title'] : '' ) );
	}
}

function mysaline_register_widgets() {
	register_widget( 'MySaline_Ad_Widget' );
	register_widget( 'MySaline_Newsletter_Widget' );
	register_widget( 'MySaline_Social_Widget' );
	register_widget( 'MySaline_Recent_Widget' );
	register_widget( 'MySaline_Events_Widget' );
	register_widget( 'MySaline_Weather_Widget' );
}
add_action( 'widgets_init', 'mysaline_register_widgets' );

/**
 * Get non-empty social links (key => url). Used by widget + template parts.
 *
 * @return array
 */
function mysaline_get_social_links() {
	$links = array();
	foreach ( array_keys( mysaline_social_networks() ) as $key ) {
		$url = get_theme_mod( "mysaline_social_{$key}", '' );
		if ( $url ) {
			$links[ $key ] = $url;
		}
	}
	return $links;
}
