<?php
/**
 * Sidebar. Accepts an optional name so the homepage can use its own widget area.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mysaline_id = 'sidebar-main';
if ( isset( $args['id'] ) ) {
	$mysaline_id = $args['id'];
} elseif ( is_front_page() ) {
	$mysaline_id = 'sidebar-home';
}

// Nothing to show and no fallback needed on the homepage.
$mysaline_has_widgets = is_active_sidebar( $mysaline_id );
?>
<aside class="ms-sidebar" role="complementary">
	<div class="ms-sticky">
		<?php
		if ( $mysaline_has_widgets ) {
			dynamic_sidebar( $mysaline_id );
		} else {
			// Sensible defaults so the sidebar is never empty before setup.
			mysaline_ad( 'sidebar' );

			echo '<section class="widget"><h2 class="ms-widget-title">' . esc_html__( 'Recent News', 'mysaline' ) . '</h2><div class="ms-recent-list">';
			$mysaline_recent = new WP_Query(
				array(
					'posts_per_page'      => 5,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'post_status'         => 'publish',
				)
			);
			while ( $mysaline_recent->have_posts() ) {
				$mysaline_recent->the_post();
				get_template_part( 'template-parts/content-list' );
			}
			wp_reset_postdata();
			echo '</div></section>';

			$mysaline_events = mysaline_upcoming_events( 4 );
			if ( $mysaline_events->have_posts() ) {
				echo '<section class="widget"><h2 class="ms-widget-title">' . esc_html__( 'Upcoming Events', 'mysaline' ) . '</h2>';
				while ( $mysaline_events->have_posts() ) {
					$mysaline_events->the_post();
					$mysaline_parts = mysaline_event_date_parts();
					echo '<article class="ms-event-list-item">';
					if ( $mysaline_parts ) {
						echo '<span class="ms-event-card__date"><span class="ms-day">' . esc_html( $mysaline_parts['day'] ) . '</span><span class="ms-mon">' . esc_html( $mysaline_parts['mon'] ) . '</span></span>';
					}
					echo '<div><h4><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h4></div></article>';
				}
				wp_reset_postdata();
				echo '</section>';
			}
		}
		?>
	</div>
</aside>
