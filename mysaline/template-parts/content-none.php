<?php
/**
 * Shown when no posts are found.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="ms-no-results">
	<h1><?php esc_html_e( 'Nothing found', 'mysaline' ); ?></h1>
	<?php if ( is_search() ) : ?>
		<p><?php esc_html_e( 'Sorry, no results matched your search. Try different keywords.', 'mysaline' ); ?></p>
		<?php get_search_form(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'It looks like nothing was found here. Try a search?', 'mysaline' ); ?></p>
		<?php get_search_form(); ?>
	<?php endif; ?>
</section>
