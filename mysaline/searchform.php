<?php
/**
 * Search form.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form role="search" method="get" class="ms-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="ms-search-field"><?php esc_html_e( 'Search for:', 'mysaline' ); ?></label>
	<input type="search" id="ms-search-field" class="ms-search-field" placeholder="<?php esc_attr_e( 'Search MySaline…', 'mysaline' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
	<button type="submit" class="ms-btn"><?php esc_html_e( 'Search', 'mysaline' ); ?></button>
</form>
