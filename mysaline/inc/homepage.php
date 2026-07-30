<?php
/**
 * Homepage query helpers: featured hero + configurable sections.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build the WP_Query for the featured hero based on Customizer settings.
 *
 * @return WP_Query
 */
function mysaline_hero_query() {
	$source = get_theme_mod( 'mysaline_hero_source', 'featured' );
	$count  = max( 1, (int) get_theme_mod( 'mysaline_hero_count', 5 ) );

	$args = array(
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'post_status'         => 'publish',
	);

	if ( 'featured' === $source ) {
		$args['meta_key']   = '_ms_featured'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$args['meta_value'] = '1'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	} elseif ( 'category' === $source ) {
		$cat = (int) get_theme_mod( 'mysaline_hero_cat', 0 );
		if ( $cat ) {
			$args['cat'] = $cat;
		}
	}

	$query = new WP_Query( $args );

	// If "featured" is selected but nothing is flagged yet, fall back to latest
	// so the homepage never looks broken on a fresh install.
	if ( 'featured' === $source && ! $query->have_posts() ) {
		$query = new WP_Query(
			array(
				'posts_per_page'      => $count,
				'ignore_sticky_posts' => true,
				'post_status'         => 'publish',
			)
		);
	}

	return $query;
}

/**
 * IDs currently used in the hero, so other homepage blocks can exclude them.
 *
 * @return int[]
 */
function mysaline_hero_post_ids() {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}
	$ids = array();
	if ( ! get_theme_mod( 'mysaline_hero_enable', true ) ) {
		return $ids;
	}
	$q = mysaline_hero_query();
	foreach ( $q->posts as $p ) {
		$ids[] = $p->ID;
	}
	wp_reset_postdata();
	return $ids;
}

/**
 * Render one configurable homepage section (1..MYSALINE_HOMEPAGE_SECTIONS).
 *
 * @param int $index Section number.
 */
function mysaline_render_homepage_section( $index ) {
	if ( ! get_theme_mod( "mysaline_section_{$index}_enable", ( $index <= 2 ) ) ) {
		return;
	}

	$cat    = (int) get_theme_mod( "mysaline_section_{$index}_cat", 0 );
	$layout = get_theme_mod( "mysaline_section_{$index}_layout", 'grid-3' );
	$count  = max( 1, (int) get_theme_mod( "mysaline_section_{$index}_count", 3 ) );
	$title  = get_theme_mod( "mysaline_section_{$index}_title", '' );

	// A section needs a category to be meaningful.
	if ( ! $cat ) {
		return;
	}

	if ( ! $title ) {
		$term  = get_term( $cat, 'category' );
		$title = ( $term && ! is_wp_error( $term ) ) ? $term->name : __( 'News', 'mysaline' );
	}

	$args = array(
		'cat'                 => $cat,
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'post_status'         => 'publish',
	);

	$query = new WP_Query( $args );
	if ( ! $query->have_posts() ) {
		wp_reset_postdata();
		return;
	}

	$archive_link = get_category_link( $cat );
	?>
	<section class="ms-section ms-home-section ms-home-section--<?php echo esc_attr( $layout ); ?>">
		<div class="ms-section-head">
			<h2><?php echo esc_html( $title ); ?></h2>
			<a class="ms-section-head__link" href="<?php echo esc_url( $archive_link ); ?>"><?php esc_html_e( 'View all', 'mysaline' ); ?></a>
		</div>
		<?php
		if ( 'list' === $layout ) {
			echo '<div class="ms-section-list">';
			while ( $query->have_posts() ) {
				$query->the_post();
				get_template_part( 'template-parts/content-list' );
			}
			echo '</div>';
		} elseif ( 'mixed' === $layout ) {
			mysaline_render_mixed_layout( $query );
		} else {
			$grid = ( 'grid-2' === $layout ) ? 'ms-grid--2' : 'ms-grid--3';
			echo '<div class="ms-grid ' . esc_attr( $grid ) . '">';
			while ( $query->have_posts() ) {
				$query->the_post();
				get_template_part( 'template-parts/content-card' );
			}
			echo '</div>';
		}
		?>
	</section>
	<?php
	wp_reset_postdata();
}

/**
 * "Lead story + list" layout for a section query: first post large, rest listed.
 *
 * @param WP_Query $query The section query.
 */
function mysaline_render_mixed_layout( $query ) {
	echo '<div class="ms-mixed">';
	$index = 0;
	echo '<div class="ms-mixed__lead">';
	$rest_open = false;
	while ( $query->have_posts() ) {
		$query->the_post();
		if ( 0 === $index ) {
			get_template_part( 'template-parts/content-card' );
			echo '</div><div class="ms-mixed__list">';
			$rest_open = true;
		} else {
			get_template_part( 'template-parts/content-list' );
		}
		$index++;
	}
	// Close whichever wrapper is currently open.
	echo '</div>'; // .ms-mixed__lead or .ms-mixed__list
	echo '</div>'; // .ms-mixed
}
