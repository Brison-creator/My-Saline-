<?php
/**
 * Template Name: Saline County Favorites Ballot
 * Description: Full-width voting ballot page. Assign this to your voting page
 * (e.g. /scf-2026-vote/) — no shortcode needed.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="ms-fav-page">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<header class="ms-article__header">
			<h1 class="ms-article__title"><?php the_title(); ?></h1>
		</header>

		<?php if ( trim( get_the_content() ) ) : ?>
			<div class="ms-article__content"><?php the_content(); ?></div>
		<?php endif; ?>
		<?php
	endwhile;

	// Render the ballot unless the page content already includes the shortcode.
	$ms_post = get_post();
	if ( ! $ms_post || ! has_shortcode( $ms_post->post_content, 'mysaline_favorites_ballot' ) ) {
		get_template_part( 'template-parts/favorites-ballot' );
	}
	?>
</div>

<?php
get_footer();
