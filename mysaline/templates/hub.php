<?php
/**
 * Template Name: Section Hub
 * Description: A landing page for a group of sections — Things To Do,
 * Government & Elections, or any cluster. Shows the page content, then the
 * child pages as cards, then the newest posts from a chosen category.
 *
 * Child pages become the cards automatically, so the owner builds a hub by
 * creating pages and setting their parent — no code, no shortcodes.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php mysaline_breadcrumbs(); ?>

<?php
while ( have_posts() ) :
	the_post();
	?>
	<header class="ms-page-header ms-hub-header">
		<h1><?php the_title(); ?></h1>
		<?php if ( has_excerpt() ) : ?>
			<p><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
	</header>

	<?php if ( trim( get_the_content() ) ) : ?>
		<div class="ms-article__content ms-hub-intro"><?php the_content(); ?></div>
	<?php endif; ?>

	<?php
	// Child pages become the hub cards.
	$mysaline_children = get_pages(
		array(
			'child_of'    => get_the_ID(),
			'parent'      => get_the_ID(),
			'sort_column' => 'menu_order,post_title',
		)
	);

	if ( ! empty( $mysaline_children ) ) :
		?>
		<nav class="ms-hubgrid" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
			<?php foreach ( $mysaline_children as $mysaline_child ) : ?>
				<?php
				$mysaline_icon = get_post_meta( $mysaline_child->ID, '_ms_hub_icon', true );
				$mysaline_desc = $mysaline_child->post_excerpt
					? $mysaline_child->post_excerpt
					: wp_trim_words( wp_strip_all_tags( $mysaline_child->post_content ), 16, '…' );
				?>
				<a class="ms-hub" href="<?php echo esc_url( get_permalink( $mysaline_child ) ); ?>">
					<?php if ( $mysaline_icon ) : ?>
						<b aria-hidden="true"><?php echo esc_html( $mysaline_icon ); ?></b>
					<?php endif; ?>
					<h2><?php echo esc_html( $mysaline_child->post_title ); ?></h2>
					<?php if ( $mysaline_desc ) : ?>
						<p><?php echo esc_html( $mysaline_desc ); ?></p>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	endif;
	?>

	<?php mysaline_ad( 'homepage_mid', array( 'class' => 'ms-ad--leaderboard' ) ); ?>

	<?php
	// Optional feed of recent posts from a category chosen on the page itself.
	$mysaline_hub_cat = (int) get_post_meta( get_the_ID(), '_ms_hub_category', true );
	if ( $mysaline_hub_cat ) :
		$mysaline_hub_q = new WP_Query(
			array(
				'cat'                 => $mysaline_hub_cat,
				'posts_per_page'      => 6,
				'ignore_sticky_posts' => true,
				'post_status'         => 'publish',
			)
		);
		if ( $mysaline_hub_q->have_posts() ) :
			$mysaline_term = get_term( $mysaline_hub_cat, 'category' );
			?>
			<section class="ms-section" style="margin-top:2.5rem">
				<div class="ms-section-head">
					<h2><?php esc_html_e( 'Latest coverage', 'mysaline' ); ?></h2>
					<?php if ( $mysaline_term && ! is_wp_error( $mysaline_term ) ) : ?>
						<a class="ms-section-head__link" href="<?php echo esc_url( get_category_link( $mysaline_hub_cat ) ); ?>"><?php esc_html_e( 'View all', 'mysaline' ); ?></a>
					<?php endif; ?>
				</div>
				<div class="ms-grid ms-grid--3">
					<?php
					$mysaline_hub_i = 0;
					while ( $mysaline_hub_q->have_posts() ) :
						$mysaline_hub_q->the_post();
						get_template_part( 'template-parts/content-card' );
						mysaline_ad_in_feed( $mysaline_hub_i, 3 );
						$mysaline_hub_i++;
					endwhile;
					?>
				</div>
			</section>
			<?php
		endif;
		wp_reset_postdata();
	endif;
	?>
	<?php
endwhile;
?>

<?php
get_footer();
