<?php
/**
 * Author archive — the columnist's home.
 *
 * Recurring voices are a real part of this newsroom, so an author archive gets
 * a proper masthead (photo, bio, social, post count) rather than a bare list.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$mysaline_author = get_queried_object();
$mysaline_id     = $mysaline_author ? (int) $mysaline_author->ID : 0;
$mysaline_bio    = $mysaline_id ? get_the_author_meta( 'description', $mysaline_id ) : '';
$mysaline_site   = $mysaline_id ? get_the_author_meta( 'user_url', $mysaline_id ) : '';
$mysaline_count  = $mysaline_id ? count_user_posts( $mysaline_id, 'post', true ) : 0;
?>

<?php mysaline_breadcrumbs(); ?>

<header class="ms-author-header">
	<?php echo get_avatar( $mysaline_id, 96, '', '', array( 'class' => 'ms-author-header__avatar' ) ); ?>
	<div class="ms-author-header__body">
		<p class="ms-eyebrow"><?php esc_html_e( 'Columnist', 'mysaline' ); ?></p>
		<h1><?php echo esc_html( get_the_author_meta( 'display_name', $mysaline_id ) ); ?></h1>
		<?php if ( $mysaline_bio ) : ?>
			<p class="ms-author-header__bio"><?php echo esc_html( $mysaline_bio ); ?></p>
		<?php endif; ?>
		<p class="ms-author-header__meta">
			<?php
			printf(
				/* translators: %s: number of stories. */
				esc_html( _n( '%s story', '%s stories', $mysaline_count, 'mysaline' ) ),
				esc_html( number_format_i18n( $mysaline_count ) )
			);
			if ( $mysaline_site ) {
				echo ' · <a href="' . esc_url( $mysaline_site ) . '" rel="noopener">' . esc_html__( 'Website', 'mysaline' ) . '</a>';
			}
			?>
			· <a href="<?php echo esc_url( get_author_feed_link( $mysaline_id ) ); ?>"><?php esc_html_e( 'RSS', 'mysaline' ); ?></a>
		</p>
	</div>
</header>

<div class="ms-content-sidebar" style="margin-top:2rem">
	<div class="ms-primary-col">
		<?php if ( have_posts() ) : ?>
			<div class="ms-grid ms-grid--3">
				<?php
				$mysaline_i = 0;
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content-card' );
					mysaline_ad_in_feed( $mysaline_i, 6 );
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
