<?php
/**
 * Filter/search controls shown above CPT archives.
 *
 * Obituaries get a name search — the thing families actually do.
 * The directory gets a category filter plus a search, so readers stop
 * scrolling one long alphabetical list.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---- Obituaries ------------------------------------------------------- */
if ( is_post_type_archive( 'ms_obituary' ) ) :
	$ms_term = get_search_query();
	?>
	<div class="ms-archive-tools">
		<form class="ms-namesearch" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="ms-obit-search"><?php esc_html_e( 'Search obituaries by name', 'mysaline' ); ?></label>
			<input type="search" id="ms-obit-search" name="s" value="<?php echo esc_attr( $ms_term ); ?>"
				placeholder="<?php esc_attr_e( 'Search by name…', 'mysaline' ); ?>" />
			<input type="hidden" name="post_type" value="ms_obituary" />
			<button type="submit" class="ms-btn"><?php esc_html_e( 'Search', 'mysaline' ); ?></button>
		</form>
		<?php
		$ms_submit = get_theme_mod( 'mysaline_obit_submit_url', '' );
		if ( $ms_submit ) :
			?>
			<a class="ms-btn ms-btn--ghost" href="<?php echo esc_url( $ms_submit ); ?>"><?php esc_html_e( 'Submit an obituary', 'mysaline' ); ?></a>
		<?php endif; ?>
	</div>
	<?php
endif;

/* ---- Business directory ----------------------------------------------- */
if ( is_post_type_archive( 'ms_business' ) || is_tax( 'ms_business_cat' ) ) :
	$ms_terms = get_terms(
		array(
			'taxonomy'   => 'ms_business_cat',
			'hide_empty' => true,
		)
	);
	$ms_current = is_tax( 'ms_business_cat' ) ? get_queried_object_id() : 0;
	?>
	<div class="ms-archive-tools">
		<form class="ms-namesearch" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="ms-biz-search"><?php esc_html_e( 'Search businesses', 'mysaline' ); ?></label>
			<input type="search" id="ms-biz-search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>"
				placeholder="<?php esc_attr_e( 'Search businesses…', 'mysaline' ); ?>" />
			<input type="hidden" name="post_type" value="ms_business" />
			<button type="submit" class="ms-btn"><?php esc_html_e( 'Search', 'mysaline' ); ?></button>
		</form>
		<?php
		$ms_list = get_theme_mod( 'mysaline_biz_submit_url', '' );
		if ( $ms_list ) :
			?>
			<a class="ms-btn ms-btn--ghost" href="<?php echo esc_url( $ms_list ); ?>"><?php esc_html_e( 'List your business', 'mysaline' ); ?></a>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $ms_terms ) && ! is_wp_error( $ms_terms ) ) : ?>
		<nav class="ms-chips" aria-label="<?php esc_attr_e( 'Business categories', 'mysaline' ); ?>">
			<a class="ms-chip <?php echo $ms_current ? '' : 'is-active'; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'ms_business' ) ); ?>">
				<?php esc_html_e( 'All', 'mysaline' ); ?>
			</a>
			<?php foreach ( $ms_terms as $ms_t ) : ?>
				<a class="ms-chip <?php echo ( $ms_current === $ms_t->term_id ) ? 'is-active' : ''; ?>"
					href="<?php echo esc_url( get_term_link( $ms_t ) ); ?>">
					<?php echo esc_html( $ms_t->name ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>
	<?php
endif;
