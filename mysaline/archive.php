<?php
/**
 * Generic archive (category, tag, author, date, taxonomy, CPT).
 *
 * A single, robust archive template so every existing category/tag/author/date
 * URL keeps working with a consistent, modern layout.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<header class="ms-page-header">
	<div class="ms-container">
		<?php mysaline_breadcrumbs(); ?>
		<h1>
			<?php
			if ( is_category() || is_tag() || is_tax() ) {
				single_term_title();
			} elseif ( is_author() ) {
				/* translators: %s: author name. */
				printf( esc_html__( 'Articles by %s', 'mysaline' ), esc_html( get_the_author() ) );
			} elseif ( is_post_type_archive() ) {
				post_type_archive_title();
			} elseif ( is_day() ) {
				echo esc_html( get_the_date() );
			} elseif ( is_month() ) {
				echo esc_html( get_the_date( 'F Y' ) );
			} elseif ( is_year() ) {
				echo esc_html( get_the_date( 'Y' ) );
			} else {
				esc_html_e( 'Archive', 'mysaline' );
			}
			?>
		</h1>
		<?php
		$mysaline_desc = term_description();
		if ( $mysaline_desc ) {
			echo '<p>' . wp_kses_post( $mysaline_desc ) . '</p>';
		} elseif ( is_author() && get_the_author_meta( 'description' ) ) {
			echo '<p>' . esc_html( get_the_author_meta( 'description' ) ) . '</p>';
		}
		?>
	</div>
</header>

<div class="ms-content-sidebar" style="margin-top:2rem">
	<div class="ms-primary-col">
		<?php if ( have_posts() ) : ?>
			<div class="ms-grid ms-grid--3">
				<?php
				$mysaline_i = 0;
				// Directory archives get their own ad zone; everything else in-feed.
				$mysaline_is_directory = ( is_post_type_archive( 'ms_business' ) || is_tax( 'ms_business_cat' ) );
				while ( have_posts() ) :
					the_post();
					// Use type-appropriate cards for CPT archives.
					if ( is_post_type_archive( 'ms_event' ) || is_tax( 'ms_event_cat' ) ) {
						get_template_part( 'template-parts/content-event-card' );
					} elseif ( is_post_type_archive( 'ms_obituary' ) ) {
						get_template_part( 'template-parts/content-obituary-card' );
					} elseif ( $mysaline_is_directory ) {
						get_template_part( 'template-parts/content-business-card' );
					} else {
						get_template_part( 'template-parts/content-card' );
					}
					mysaline_ad_in_feed(
						$mysaline_i,
						$mysaline_is_directory ? 9 : 6,
						$mysaline_is_directory ? 'directory' : 'in_feed'
					);
					$mysaline_i++;
				endwhile;
				?>
			</div>
			<?php mysaline_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>

	<?php get_sidebar(); ?>
</div>

<?php
get_footer();
