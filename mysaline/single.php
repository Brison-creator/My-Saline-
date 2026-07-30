<?php
/**
 * Single post.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php mysaline_breadcrumbs(); ?>

<div class="ms-content-sidebar">
	<div class="ms-primary-col">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'ms-article' ); ?>>
				<header class="ms-article__header">
					<?php mysaline_category_badge(); ?>
					<h1 class="ms-article__title"><?php the_title(); ?></h1>
					<div class="ms-article__meta">
						<?php echo get_avatar( get_the_author_meta( 'ID' ), 40, '', '', array( 'class' => 'ms-author-avatar' ) ); ?>
						<span><?php the_author_posts_link(); ?></span>
						<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<span><?php echo esc_html( mysaline_reading_time() ); ?></span>
						<?php if ( comments_open() ) : ?>
							<span><a href="#ms-comments"><?php comments_number( esc_html__( 'Leave a comment', 'mysaline' ), esc_html__( '1 comment', 'mysaline' ), esc_html__( '% comments', 'mysaline' ) ); ?></a></span>
						<?php endif; ?>
					</div>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="ms-featured-media">
						<?php the_post_thumbnail( 'mysaline-hero', array( 'alt' => the_title_attribute( array( 'echo' => false ) ) ) ); ?>
						<?php
						$mysaline_caption = get_the_post_thumbnail_caption();
						if ( $mysaline_caption ) {
							echo '<figcaption>' . esc_html( $mysaline_caption ) . '</figcaption>';
						}
						?>
					</figure>
				<?php endif; ?>

				<div class="ms-article__content">
					<?php
					the_content();
					wp_link_pages(
						array(
							'before' => '<div class="ms-page-links">' . esc_html__( 'Pages:', 'mysaline' ),
							'after'  => '</div>',
						)
					);
					?>
				</div>

				<?php mysaline_ad( 'below_content', array( 'class' => 'ms-ad--leaderboard' ) ); ?>

				<?php if ( has_tag() ) : ?>
					<div class="ms-tags">
						<?php the_tags( '', '' ); ?>
					</div>
				<?php endif; ?>

				<?php if ( get_the_author_meta( 'description' ) ) : ?>
					<div class="ms-author-box">
						<?php echo get_avatar( get_the_author_meta( 'ID' ), 72 ); ?>
						<div>
							<h3><?php the_author_posts_link(); ?></h3>
							<p><?php echo esc_html( get_the_author_meta( 'description' ) ); ?></p>
						</div>
					</div>
				<?php endif; ?>

				<nav class="ms-post-nav" aria-label="<?php esc_attr_e( 'Post navigation', 'mysaline' ); ?>">
					<?php
					$mysaline_prev = get_previous_post();
					$mysaline_next = get_next_post();
					if ( $mysaline_prev ) {
						echo '<a class="ms-prev" href="' . esc_url( get_permalink( $mysaline_prev ) ) . '"><span class="ms-post-nav__label">' . esc_html__( '← Previous', 'mysaline' ) . '</span>' . esc_html( get_the_title( $mysaline_prev ) ) . '</a>';
					} else {
						echo '<span></span>';
					}
					if ( $mysaline_next ) {
						echo '<a class="ms-next" href="' . esc_url( get_permalink( $mysaline_next ) ) . '"><span class="ms-post-nav__label">' . esc_html__( 'Next →', 'mysaline' ) . '</span>' . esc_html( get_the_title( $mysaline_next ) ) . '</a>';
					}
					?>
				</nav>

				<?php get_template_part( 'template-parts/related-posts' ); ?>

				<div id="ms-comments" class="ms-comments">
					<?php
					if ( comments_open() || get_comments_number() ) {
						comments_template();
					}
					?>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</div>

	<?php get_sidebar(); ?>
</div>

<?php
get_footer();
