<?php
/**
 * Saline County Favorites ballot.
 *
 * Design goals, in response to the old 155-question linear form:
 *   - Search box jumps straight to a category ("plumb" → Best Plumber).
 *   - Section tabs so you can work one section at a time.
 *   - Sticky progress meter that shows exactly how close you are to the
 *     prize threshold, per section.
 *   - "Hide the ones I've done" to shrink a 155-item ballot as you go.
 *   - Picks autosave on this device, so nothing is lost.
 *   - Skip anything; nothing is required.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ms_ballot = mysaline_fav_get_ballot();
$ms_status = mysaline_fav_status();
$ms_total  = mysaline_fav_total_categories();

// Post-submit confirmation.
$ms_flag = isset( $_GET['fav'] ) ? sanitize_key( $_GET['fav'] ) : '';
if ( $ms_flag ) {
	$ms_messages = array(
		'thanks'   => get_theme_mod( 'mysaline_fav_thanks', __( 'Thanks for voting! Your ballot is in.', 'mysaline' ) ),
		'closed'   => __( 'Voting is closed, so that ballot could not be counted.', 'mysaline' ),
		'empty'    => __( 'No picks were selected, so nothing was saved.', 'mysaline' ),
		'toofast'  => __( 'That was a little too quick — please wait a moment and try again.', 'mysaline' ),
		'badnonce' => __( 'Your session expired. Please reload the page and vote again.', 'mysaline' ),
	);
	if ( isset( $ms_messages[ $ms_flag ] ) ) {
		$ms_ok = ( 'thanks' === $ms_flag );
		printf(
			'<div class="ms-fav-notice %1$s" role="status"><p>%2$s</p></div>',
			$ms_ok ? 'is-success' : 'is-warning',
			esc_html( $ms_messages[ $ms_flag ] )
		);
	}
}

if ( empty( $ms_ballot ) ) {
	echo '<p>' . esc_html__( 'The ballot is being prepared. Check back soon!', 'mysaline' ) . '</p>';
	return;
}

$ms_open     = mysaline_fav_is_open();
$ms_min_cats = (int) get_theme_mod( 'mysaline_fav_min_cats', 20 );
$ms_min_sect = (int) get_theme_mod( 'mysaline_fav_min_sects', 4 );
$ms_intro    = get_theme_mod( 'mysaline_fav_intro', '' );
$ms_prize    = get_theme_mod( 'mysaline_fav_prize', '' );
?>

<div class="ms-fav" id="ms-fav"
	data-min-cats="<?php echo esc_attr( $ms_min_cats ); ?>"
	data-min-sections="<?php echo esc_attr( $ms_min_sect ); ?>"
	data-total="<?php echo esc_attr( $ms_total ); ?>"
	data-year="<?php echo esc_attr( mysaline_fav_year() ); ?>">

	<?php if ( $ms_intro || $ms_prize || $ms_status['message'] ) : ?>
		<div class="ms-fav__intro">
			<?php if ( $ms_intro ) : ?>
				<p class="ms-fav__intro-line"><?php echo esc_html( $ms_intro ); ?></p>
			<?php endif; ?>
			<?php if ( $ms_prize ) : ?>
				<p class="ms-fav__prize"><?php echo esc_html( $ms_prize ); ?></p>
			<?php endif; ?>
			<?php if ( $ms_status['message'] ) : ?>
				<p class="ms-fav__window"><?php echo esc_html( $ms_status['message'] ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! $ms_open ) : ?>
		<div class="ms-fav-notice is-warning" role="status">
			<p><?php echo esc_html( $ms_status['message'] ? $ms_status['message'] : __( 'Voting is not open right now.', 'mysaline' ) ); ?></p>
		</div>
		<?php if ( 'closed' === $ms_status['state'] ) : ?>
			<p><a class="ms-btn" href="#ms-fav-list"><?php esc_html_e( 'See who was nominated', 'mysaline' ); ?></a></p>
		<?php endif; ?>
	<?php endif; ?>

	<form class="ms-fav__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="mysaline_fav_vote" />
		<?php wp_nonce_field( 'mysaline_fav_vote', 'mysaline_fav_vote_nonce' ); ?>

		<!-- Sticky control bar: progress + search + filters -->
		<div class="ms-fav__bar" id="ms-fav-bar">
			<div class="ms-fav__progress">
				<div class="ms-fav__progress-head">
					<strong class="ms-fav__count">
						<span data-fav-voted>0</span>
						<?php
						printf(
							/* translators: %d: minimum categories needed. */
							esc_html__( 'of %d needed', 'mysaline' ),
							(int) $ms_min_cats
						);
						?>
					</strong>
					<span class="ms-fav__qualify" data-fav-qualify hidden>
						<?php esc_html_e( '✓ You qualify for the drawing', 'mysaline' ); ?>
					</span>
				</div>
				<div class="ms-fav__meter" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $ms_min_cats ); ?>" aria-valuenow="0" data-fav-meter>
					<span class="ms-fav__meter-fill" data-fav-fill></span>
				</div>
				<div class="ms-fav__sections-done" data-fav-section-chips>
					<?php foreach ( $ms_ballot as $ms_section ) : ?>
						<span class="ms-fav__chip" data-fav-chip="<?php echo esc_attr( $ms_section['slug'] ); ?>">
							<?php echo esc_html( $ms_section['name'] ); ?>
						</span>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="ms-fav__tools">
				<label class="screen-reader-text" for="ms-fav-search"><?php esc_html_e( 'Search categories', 'mysaline' ); ?></label>
				<input type="search" id="ms-fav-search" class="ms-fav__search" data-fav-search
					placeholder="<?php esc_attr_e( 'Search categories — try “barbecue” or “plumber”', 'mysaline' ); ?>"
					autocomplete="off" />
				<label class="ms-fav__toggle">
					<input type="checkbox" data-fav-hide-done />
					<span><?php esc_html_e( 'Hide ones I’ve done', 'mysaline' ); ?></span>
				</label>
			</div>

			<nav class="ms-fav__tabs" aria-label="<?php esc_attr_e( 'Ballot sections', 'mysaline' ); ?>">
				<button type="button" class="ms-fav__tab is-active" data-fav-tab="all"><?php esc_html_e( 'All', 'mysaline' ); ?></button>
				<?php foreach ( $ms_ballot as $ms_section ) : ?>
					<button type="button" class="ms-fav__tab" data-fav-tab="<?php echo esc_attr( $ms_section['slug'] ); ?>">
						<?php echo esc_html( $ms_section['name'] ); ?>
						<span class="ms-fav__tab-count"><?php echo esc_html( count( $ms_section['categories'] ) ); ?></span>
					</button>
				<?php endforeach; ?>
			</nav>
		</div>

		<p class="ms-fav__saved" data-fav-saved hidden><?php esc_html_e( 'Your picks are saved on this device.', 'mysaline' ); ?></p>
		<p class="ms-fav__noresults" data-fav-noresults hidden><?php esc_html_e( 'No categories match that search.', 'mysaline' ); ?></p>

		<!-- Ballot -->
		<div class="ms-fav__list" id="ms-fav-list">
			<?php foreach ( $ms_ballot as $ms_section ) : ?>
				<section class="ms-fav__section" data-fav-section="<?php echo esc_attr( $ms_section['slug'] ); ?>">
					<h2 class="ms-fav__section-title" id="ms-fav-<?php echo esc_attr( $ms_section['slug'] ); ?>">
						<?php echo esc_html( $ms_section['name'] ); ?>
					</h2>

					<?php foreach ( $ms_section['categories'] as $ms_cat ) : ?>
						<fieldset class="ms-fav__cat"
							data-fav-cat="<?php echo esc_attr( $ms_cat['id'] ); ?>"
							data-fav-cat-section="<?php echo esc_attr( $ms_section['slug'] ); ?>"
							data-fav-search-text="<?php echo esc_attr( strtolower( $ms_cat['title'] ) ); ?>">

							<legend class="ms-fav__cat-title">
								<?php echo esc_html( $ms_cat['title'] ); ?>
								<span class="ms-fav__cat-done" aria-hidden="true">✓</span>
							</legend>

							<div class="ms-fav__options">
								<?php foreach ( $ms_cat['nominees'] as $ms_i => $ms_nominee ) : ?>
									<?php $ms_field_id = 'fav-' . (int) $ms_cat['id'] . '-' . (int) $ms_i; ?>
									<label class="ms-fav__option" for="<?php echo esc_attr( $ms_field_id ); ?>">
										<input type="radio"
											id="<?php echo esc_attr( $ms_field_id ); ?>"
											name="vote[<?php echo esc_attr( $ms_cat['id'] ); ?>]"
											value="<?php echo esc_attr( $ms_nominee['label'] ); ?>"
											<?php disabled( ! $ms_open ); ?> />
										<span class="ms-fav__option-label"><?php echo esc_html( $ms_nominee['label'] ); ?></span>
										<?php if ( $ms_nominee['url'] ) : ?>
											<a class="ms-fav__option-link" href="<?php echo esc_url( $ms_nominee['url'] ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: nominee name. */ __( 'Visit %s', 'mysaline' ), $ms_nominee['label'] ) ); ?>">↗</a>
										<?php endif; ?>
									</label>
								<?php endforeach; ?>
							</div>

							<button type="button" class="ms-fav__clear" data-fav-clear="<?php echo esc_attr( $ms_cat['id'] ); ?>">
								<?php esc_html_e( 'Clear', 'mysaline' ); ?>
							</button>
						</fieldset>
					<?php endforeach; ?>
				</section>
			<?php endforeach; ?>
		</div>

		<?php if ( $ms_open ) : ?>
			<div class="ms-fav__submit">
				<p class="ms-fav__email">
					<label for="ms-fav-email"><?php esc_html_e( 'Email (only if you want to enter the drawing)', 'mysaline' ); ?></label>
					<input type="email" id="ms-fav-email" name="voter_email" placeholder="<?php esc_attr_e( 'you@example.com', 'mysaline' ); ?>" />
					<span class="ms-fav__email-note"><?php esc_html_e( 'Optional. Used only to contact the drawing winner.', 'mysaline' ); ?></span>
				</p>
				<button type="submit" class="ms-btn ms-fav__submit-btn">
					<?php esc_html_e( 'Submit my ballot', 'mysaline' ); ?>
					<span data-fav-submit-count></span>
				</button>
				<p class="ms-fav__resubmit"><?php esc_html_e( 'You can come back and change your picks any time before voting closes — only your latest pick in each category counts.', 'mysaline' ); ?></p>
			</div>
		<?php endif; ?>
	</form>
</div>
