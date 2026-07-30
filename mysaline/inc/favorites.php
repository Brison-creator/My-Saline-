<?php
/**
 * Saline County Favorites — on-site voting ballot.
 *
 * Replaces the external 155-question form with a ballot the reader can actually
 * finish: instant search, section tabs, a live progress meter tied to the prize
 * threshold, autosaved picks, and free skipping in any order.
 *
 * Data model
 *   - CPT  `ms_fav_category`  one voting category ("Best BBQ").
 *   - Tax  `ms_fav_section`   Food / Businesses / People / Places & Things.
 *   - Meta `_ms_fav_nominees` finalists, one per line ("Name" or "Name | URL").
 *   - Table `{prefix}mysaline_fav_votes` one row per (voter, year, category).
 *
 * The UNIQUE key on (voter_hash, ballot_year, category_id) reproduces the
 * existing contest rule that only a voter's most recent pick per category
 * counts, while still letting them come back and change their mind.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bump when the votes table schema changes.
 */
define( 'MYSALINE_FAV_DB_VERSION', '1.0.0' );

/* -------------------------------------------------------------------------
 * Registration
 * ---------------------------------------------------------------------- */

/**
 * Register the Favorites category post type and section taxonomy.
 */
function mysaline_fav_register() {
	register_post_type(
		'ms_fav_category',
		array(
			'labels'        => mysaline_cpt_labels( __( 'Favorites Category', 'mysaline' ), __( 'Favorites', 'mysaline' ) ),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'has_archive'   => false,
			'rewrite'       => false,
			'menu_icon'     => 'dashicons-awards',
			'menu_position' => 26,
			'supports'      => array( 'title', 'page-attributes' ),
			'show_in_rest'  => false,
		)
	);

	register_taxonomy(
		'ms_fav_section',
		'ms_fav_category',
		array(
			'labels'            => mysaline_tax_labels( __( 'Section', 'mysaline' ), __( 'Sections', 'mysaline' ) ),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'rewrite'           => false,
		)
	);
}
add_action( 'init', 'mysaline_fav_register' );

/**
 * The four ballot sections, created on activation if missing.
 *
 * @return array slug => label.
 */
function mysaline_fav_default_sections() {
	return array(
		'food'            => __( 'Food', 'mysaline' ),
		'businesses'      => __( 'Businesses', 'mysaline' ),
		'people'          => __( 'People', 'mysaline' ),
		'places-things'   => __( 'Places & Things', 'mysaline' ),
	);
}

/**
 * Seed the default sections so the owner never starts from an empty screen.
 */
function mysaline_fav_seed_sections() {
	foreach ( mysaline_fav_default_sections() as $slug => $label ) {
		if ( ! term_exists( $slug, 'ms_fav_section' ) ) {
			wp_insert_term( $label, 'ms_fav_section', array( 'slug' => $slug ) );
		}
	}
}

/* -------------------------------------------------------------------------
 * Votes table
 * ---------------------------------------------------------------------- */

/**
 * Fully-qualified votes table name.
 *
 * @return string
 */
function mysaline_fav_table() {
	global $wpdb;
	return $wpdb->prefix . 'mysaline_fav_votes';
}

/**
 * Create/upgrade the votes table.
 */
function mysaline_fav_install_table() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table   = mysaline_fav_table();
	$collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		ballot_year smallint(5) unsigned NOT NULL,
		category_id bigint(20) unsigned NOT NULL,
		nominee varchar(191) NOT NULL DEFAULT '',
		voter_hash char(64) NOT NULL,
		voter_email varchar(191) NOT NULL DEFAULT '',
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY voter_category (voter_hash,ballot_year,category_id),
		KEY category_year (category_id,ballot_year),
		KEY ballot_year (ballot_year)
	) {$collate};";

	dbDelta( $sql );
	update_option( 'mysaline_fav_db_version', MYSALINE_FAV_DB_VERSION );
}

/**
 * Install the table + seed sections on theme activation.
 */
function mysaline_fav_activate() {
	mysaline_fav_register();
	mysaline_fav_install_table();
	mysaline_fav_seed_sections();
}
add_action( 'after_switch_theme', 'mysaline_fav_activate' );

/**
 * Safety net: create the table if it is missing (e.g. theme copied in by FTP).
 */
function mysaline_fav_maybe_install() {
	if ( get_option( 'mysaline_fav_db_version' ) !== MYSALINE_FAV_DB_VERSION ) {
		mysaline_fav_install_table();
		mysaline_fav_seed_sections();
	}
}
add_action( 'admin_init', 'mysaline_fav_maybe_install' );

/* -------------------------------------------------------------------------
 * Nominees meta box
 * ---------------------------------------------------------------------- */

/**
 * Register the nominees meta box.
 */
function mysaline_fav_meta_box() {
	add_meta_box(
		'mysaline_fav_nominees',
		__( 'Finalists', 'mysaline' ),
		'mysaline_fav_render_meta_box',
		'ms_fav_category',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'mysaline_fav_meta_box' );

/**
 * Nominees textarea — one finalist per line, matching how the list is already
 * maintained. Optional "Name | https://site" adds a link on the ballot.
 *
 * @param WP_Post $post Post.
 */
function mysaline_fav_render_meta_box( $post ) {
	wp_nonce_field( 'mysaline_fav_save', 'mysaline_fav_nonce' );
	$value = get_post_meta( $post->ID, '_ms_fav_nominees', true );
	?>
	<p>
		<label for="ms_fav_nominees"><strong><?php esc_html_e( 'One finalist per line', 'mysaline' ); ?></strong></label>
	</p>
	<textarea id="ms_fav_nominees" name="_ms_fav_nominees" rows="8" style="width:100%;font-family:monospace" placeholder="<?php echo esc_attr( "Whole Hog Cafe\nWright's Barbecue | https://example.com" ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'Paste your finalist list straight in. To link a finalist, add a pipe and the URL: Name | https://example.com', 'mysaline' ); ?>
	</p>
	<?php
}

/**
 * Save nominees.
 *
 * @param int $post_id Post ID.
 */
function mysaline_fav_save_meta( $post_id ) {
	if ( ! isset( $_POST['mysaline_fav_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['mysaline_fav_nonce'] ), 'mysaline_fav_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['_ms_fav_nominees'] ) ) {
		$raw = sanitize_textarea_field( wp_unslash( $_POST['_ms_fav_nominees'] ) );
		if ( '' === trim( $raw ) ) {
			delete_post_meta( $post_id, '_ms_fav_nominees' );
		} else {
			update_post_meta( $post_id, '_ms_fav_nominees', $raw );
		}
	}
}
add_action( 'save_post_ms_fav_category', 'mysaline_fav_save_meta' );

/**
 * Parse a category's nominees into { label, url } rows.
 *
 * @param int $category_id Category post ID.
 * @return array
 */
function mysaline_fav_get_nominees( $category_id ) {
	$raw = get_post_meta( $category_id, '_ms_fav_nominees', true );
	if ( ! $raw ) {
		return array();
	}

	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$url = '';
		if ( false !== strpos( $line, '|' ) ) {
			$bits  = array_map( 'trim', explode( '|', $line, 2 ) );
			$line  = $bits[0];
			$url   = isset( $bits[1] ) ? esc_url_raw( $bits[1] ) : '';
		}
		if ( '' === $line ) {
			continue;
		}
		$out[] = array(
			'label' => $line,
			'url'   => $url,
		);
	}
	return $out;
}

/* -------------------------------------------------------------------------
 * Settings (Customizer)
 * ---------------------------------------------------------------------- */

/**
 * Add the Favorites section to the MySaline panel.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mysaline_fav_customize( $wp_customize ) {
	$wp_customize->add_section(
		'mysaline_favorites',
		array(
			'title'       => __( 'Saline County Favorites', 'mysaline' ),
			'panel'       => 'mysaline_panel',
			'description' => __( 'Voting window and prize rules. Manage categories and finalists under the "Favorites" menu.', 'mysaline' ),
		)
	);

	$fields = array(
		'mysaline_fav_year'      => array(
			'default'  => (int) current_time( 'Y' ),
			'label'    => __( 'Ballot year', 'mysaline' ),
			'type'     => 'number',
			'sanitize' => 'absint',
		),
		'mysaline_fav_open'      => array(
			'default'  => '',
			'label'    => __( 'Voting opens', 'mysaline' ),
			'type'     => 'text',
			'desc'     => __( 'Format: YYYY-MM-DD HH:MM (24-hour, site time). Blank = always open.', 'mysaline' ),
			'sanitize' => 'sanitize_text_field',
		),
		'mysaline_fav_close'     => array(
			'default'  => '',
			'label'    => __( 'Voting closes', 'mysaline' ),
			'type'     => 'text',
			'desc'     => __( 'Format: YYYY-MM-DD HH:MM. Blank = never closes.', 'mysaline' ),
			'sanitize' => 'sanitize_text_field',
		),
		'mysaline_fav_min_cats'  => array(
			'default'  => 20,
			'label'    => __( 'Categories needed to enter the drawing', 'mysaline' ),
			'type'     => 'number',
			'sanitize' => 'absint',
		),
		'mysaline_fav_min_sects' => array(
			'default'  => 4,
			'label'    => __( 'Sections that must each have a vote', 'mysaline' ),
			'type'     => 'number',
			'sanitize' => 'absint',
		),
		'mysaline_fav_prize'     => array(
			'default'  => __( 'Vote in 20+ categories, including at least one in every section, to enter the $100 drawing.', 'mysaline' ),
			'label'    => __( 'Prize / rules line', 'mysaline' ),
			'type'     => 'textarea',
			'sanitize' => 'sanitize_textarea_field',
		),
		'mysaline_fav_intro'     => array(
			'default'  => __( 'It’s free, no account needed, and your picks save as you go.', 'mysaline' ),
			'label'    => __( 'Intro line', 'mysaline' ),
			'type'     => 'textarea',
			'sanitize' => 'sanitize_textarea_field',
		),
		'mysaline_fav_thanks'    => array(
			'default'  => __( 'Thanks for voting! Your ballot is in.', 'mysaline' ),
			'label'    => __( 'Thank-you message', 'mysaline' ),
			'type'     => 'textarea',
			'sanitize' => 'sanitize_textarea_field',
		),
	);

	foreach ( $fields as $id => $f ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $f['default'],
				'sanitize_callback' => $f['sanitize'],
			)
		);
		$wp_customize->add_control(
			$id,
			array(
				'type'        => $f['type'],
				'label'       => $f['label'],
				'description' => isset( $f['desc'] ) ? $f['desc'] : '',
				'section'     => 'mysaline_favorites',
			)
		);
	}

	$wp_customize->add_setting(
		'mysaline_fav_show_results',
		array(
			'default'           => false,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_fav_show_results',
		array(
			'type'        => 'checkbox',
			'label'       => __( 'Publish results publicly', 'mysaline' ),
			'description' => __( 'Leave off until you announce the winners.', 'mysaline' ),
			'section'     => 'mysaline_favorites',
		)
	);
}
add_action( 'customize_register', 'mysaline_fav_customize' );

/* -------------------------------------------------------------------------
 * Voting window
 * ---------------------------------------------------------------------- */

/**
 * Current ballot year.
 *
 * @return int
 */
function mysaline_fav_year() {
	$year = (int) get_theme_mod( 'mysaline_fav_year', (int) current_time( 'Y' ) );
	return $year ? $year : (int) current_time( 'Y' );
}

/**
 * Whether voting is currently open.
 *
 * @return bool
 */
function mysaline_fav_is_open() {
	$now   = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
	$open  = trim( (string) get_theme_mod( 'mysaline_fav_open', '' ) );
	$close = trim( (string) get_theme_mod( 'mysaline_fav_close', '' ) );

	if ( $open ) {
		$ts = strtotime( $open );
		if ( $ts && $now < $ts ) {
			return false;
		}
	}
	if ( $close ) {
		$ts = strtotime( $close );
		if ( $ts && $now > $ts ) {
			return false;
		}
	}
	return true;
}

/**
 * Human status for the ballot header.
 *
 * @return array { state: before|open|closed, message: string }
 */
function mysaline_fav_status() {
	$now   = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
	$open  = trim( (string) get_theme_mod( 'mysaline_fav_open', '' ) );
	$close = trim( (string) get_theme_mod( 'mysaline_fav_close', '' ) );
	$fmt   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

	if ( $open ) {
		$ts = strtotime( $open );
		if ( $ts && $now < $ts ) {
			return array(
				'state'   => 'before',
				/* translators: %s: date and time voting opens. */
				'message' => sprintf( __( 'Voting opens %s.', 'mysaline' ), date_i18n( $fmt, $ts ) ),
			);
		}
	}
	if ( $close ) {
		$ts = strtotime( $close );
		if ( $ts && $now > $ts ) {
			return array(
				'state'   => 'closed',
				'message' => __( 'Voting has closed. Thanks to everyone who voted!', 'mysaline' ),
			);
		}
		if ( $ts ) {
			return array(
				'state'   => 'open',
				/* translators: %s: date and time voting closes. */
				'message' => sprintf( __( 'Voting closes %s.', 'mysaline' ), date_i18n( $fmt, $ts ) ),
			);
		}
	}
	return array( 'state' => 'open', 'message' => '' );
}

/* -------------------------------------------------------------------------
 * Ballot data
 * ---------------------------------------------------------------------- */

/**
 * All ballot categories grouped by section, in menu order.
 *
 * @return array section term_id => { term, categories[] }
 */
function mysaline_fav_get_ballot() {
	$cached = wp_cache_get( 'mysaline_fav_ballot' );
	if ( false !== $cached ) {
		return $cached;
	}

	$posts = get_posts(
		array(
			'post_type'        => 'ms_fav_category',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'orderby'          => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'suppress_filters' => false,
		)
	);

	$sections = array();
	foreach ( $posts as $post ) {
		$nominees = mysaline_fav_get_nominees( $post->ID );
		if ( empty( $nominees ) ) {
			continue; // A category with no finalists has nothing to vote on.
		}

		$terms = get_the_terms( $post->ID, 'ms_fav_section' );
		$term  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
		$key   = $term ? $term->term_id : 0;

		if ( ! isset( $sections[ $key ] ) ) {
			$sections[ $key ] = array(
				'term'       => $term,
				'name'       => $term ? $term->name : __( 'Other', 'mysaline' ),
				'slug'       => $term ? $term->slug : 'other',
				'categories' => array(),
			);
		}

		$sections[ $key ]['categories'][] = array(
			'id'       => $post->ID,
			'title'    => $post->post_title,
			'nominees' => $nominees,
		);
	}

	wp_cache_set( 'mysaline_fav_ballot', $sections, '', 300 );
	return $sections;
}

/**
 * Clear the ballot cache when categories change.
 */
function mysaline_fav_flush_cache() {
	wp_cache_delete( 'mysaline_fav_ballot' );
}
add_action( 'save_post_ms_fav_category', 'mysaline_fav_flush_cache' );
add_action( 'deleted_post', 'mysaline_fav_flush_cache' );

/**
 * Total number of votable categories.
 *
 * @return int
 */
function mysaline_fav_total_categories() {
	$total = 0;
	foreach ( mysaline_fav_get_ballot() as $section ) {
		$total += count( $section['categories'] );
	}
	return $total;
}

/* -------------------------------------------------------------------------
 * Vote submission
 * ---------------------------------------------------------------------- */

/**
 * Identify a voter: their email when given, otherwise a salted IP+agent hash.
 *
 * @param string $email Optional email.
 * @return string 64-char hash.
 */
function mysaline_fav_voter_hash( $email = '' ) {
	if ( $email ) {
		return hash( 'sha256', 'ms-fav-email|' . strtolower( trim( $email ) ) . '|' . wp_salt( 'auth' ) );
	}
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	return hash( 'sha256', 'ms-fav-anon|' . $ip . '|' . $agent . '|' . wp_salt( 'auth' ) );
}

/**
 * Handle a submitted ballot.
 */
function mysaline_fav_handle_submit() {
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

	if ( ! isset( $_POST['mysaline_fav_vote_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['mysaline_fav_vote_nonce'] ), 'mysaline_fav_vote' ) ) {
		wp_safe_redirect( add_query_arg( 'fav', 'badnonce', $redirect ) );
		exit;
	}

	if ( ! mysaline_fav_is_open() ) {
		wp_safe_redirect( add_query_arg( 'fav', 'closed', $redirect ) );
		exit;
	}

	// Light rate limit: one submission per identity per 20 seconds.
	$email = isset( $_POST['voter_email'] ) ? sanitize_email( wp_unslash( $_POST['voter_email'] ) ) : '';
	$hash  = mysaline_fav_voter_hash( $email );
	$gate  = 'ms_fav_rl_' . $hash;
	if ( get_transient( $gate ) ) {
		wp_safe_redirect( add_query_arg( 'fav', 'toofast', $redirect ) );
		exit;
	}
	set_transient( $gate, 1, 20 );

	$votes_in = isset( $_POST['vote'] ) && is_array( $_POST['vote'] ) ? wp_unslash( $_POST['vote'] ) : array();
	if ( empty( $votes_in ) ) {
		wp_safe_redirect( add_query_arg( 'fav', 'empty', $redirect ) );
		exit;
	}

	global $wpdb;
	$table = mysaline_fav_table();
	$year  = mysaline_fav_year();
	$now   = current_time( 'mysql' );
	$saved = 0;

	foreach ( $votes_in as $category_id => $choice ) {
		$category_id = absint( $category_id );
		$choice      = sanitize_text_field( $choice );
		if ( ! $category_id || '' === $choice ) {
			continue;
		}

		// Never trust the client: the pick must be a real finalist in this category.
		$allowed = wp_list_pluck( mysaline_fav_get_nominees( $category_id ), 'label' );
		if ( ! in_array( $choice, $allowed, true ) ) {
			continue;
		}

		// UNIQUE(voter_hash, ballot_year, category_id) keeps one row per voter per
		// category; re-voting overwrites, matching "most recent vote counts".
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"INSERT INTO {$table} (ballot_year, category_id, nominee, voter_hash, voter_email, created_at)
				 VALUES (%d, %d, %s, %s, %s, %s)
				 ON DUPLICATE KEY UPDATE nominee = VALUES(nominee), voter_email = VALUES(voter_email), created_at = VALUES(created_at)",
				$year,
				$category_id,
				$choice,
				$hash,
				$email,
				$now
			)
		);
		$saved++;
	}

	$args = array(
		'fav'   => 'thanks',
		'count' => $saved,
	);
	wp_safe_redirect( add_query_arg( $args, $redirect ) );
	exit;
}
add_action( 'admin_post_nopriv_mysaline_fav_vote', 'mysaline_fav_handle_submit' );
add_action( 'admin_post_mysaline_fav_vote', 'mysaline_fav_handle_submit' );

/* -------------------------------------------------------------------------
 * Results
 * ---------------------------------------------------------------------- */

/**
 * Tally a category: nominees ordered by vote count.
 *
 * @param int $category_id Category post ID.
 * @param int $year        Ballot year.
 * @return array rows of { nominee, votes }.
 */
function mysaline_fav_tally( $category_id, $year = 0 ) {
	global $wpdb;
	$table = mysaline_fav_table();
	$year  = $year ? (int) $year : mysaline_fav_year();

	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"SELECT nominee, COUNT(*) AS votes FROM {$table}
			 WHERE category_id = %d AND ballot_year = %d AND nominee <> ''
			 GROUP BY nominee ORDER BY votes DESC, nominee ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			absint( $category_id ),
			$year
		),
		ARRAY_A
	);

	return $rows ? $rows : array();
}

/**
 * Total distinct ballots cast this year.
 *
 * @param int $year Ballot year.
 * @return int
 */
function mysaline_fav_voter_count( $year = 0 ) {
	global $wpdb;
	$table = mysaline_fav_table();
	$year  = $year ? (int) $year : mysaline_fav_year();

	return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare( "SELECT COUNT(DISTINCT voter_hash) FROM {$table} WHERE ballot_year = %d", $year ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	);
}

/**
 * Voters who met the prize threshold, for the drawing.
 *
 * @param int $year Ballot year.
 * @return array rows of { voter_email, cats, sections }.
 */
function mysaline_fav_qualified_voters( $year = 0 ) {
	global $wpdb;
	$table     = mysaline_fav_table();
	$year      = $year ? (int) $year : mysaline_fav_year();
	$min_cats  = (int) get_theme_mod( 'mysaline_fav_min_cats', 20 );

	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"SELECT voter_email, COUNT(DISTINCT category_id) AS cats
			 FROM {$table}
			 WHERE ballot_year = %d AND voter_email <> ''
			 GROUP BY voter_hash, voter_email
			 HAVING cats >= %d
			 ORDER BY cats DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$year,
			$min_cats
		),
		ARRAY_A
	);

	return $rows ? $rows : array();
}

/* -------------------------------------------------------------------------
 * Shortcodes
 * ---------------------------------------------------------------------- */

/**
 * Whether the current request renders the ballot (so we only load its JS there).
 *
 * @return bool
 */
function mysaline_has_favorites_ballot() {
	if ( ! is_singular() ) {
		return false;
	}
	$post = get_post();
	if ( ! $post ) {
		return false;
	}
	if ( has_shortcode( $post->post_content, 'mysaline_favorites_ballot' ) ) {
		return true;
	}
	return is_page_template( 'templates/favorites.php' );
}

/**
 * [mysaline_favorites_ballot] — the voting ballot.
 *
 * @return string
 */
function mysaline_fav_ballot_shortcode() {
	ob_start();
	get_template_part( 'template-parts/favorites-ballot' );
	return ob_get_clean();
}
add_shortcode( 'mysaline_favorites_ballot', 'mysaline_fav_ballot_shortcode' );

/**
 * [mysaline_favorites_results] — published winners.
 *
 * @return string
 */
function mysaline_fav_results_shortcode() {
	if ( ! get_theme_mod( 'mysaline_fav_show_results', false ) && ! current_user_can( 'edit_posts' ) ) {
		return '<p>' . esc_html__( 'Results will be published when voting closes.', 'mysaline' ) . '</p>';
	}

	$ballot = mysaline_fav_get_ballot();
	if ( empty( $ballot ) ) {
		return '';
	}

	ob_start();
	echo '<div class="ms-fav-results">';
	foreach ( $ballot as $section ) {
		echo '<h2 class="ms-fav-results__section">' . esc_html( $section['name'] ) . '</h2>';
		foreach ( $section['categories'] as $cat ) {
			$rows = mysaline_fav_tally( $cat['id'] );
			if ( empty( $rows ) ) {
				continue;
			}
			echo '<div class="ms-fav-result">';
			echo '<h3>' . esc_html( $cat['title'] ) . '</h3>';
			echo '<ol class="ms-fav-result__list">';
			foreach ( $rows as $i => $row ) {
				printf(
					'<li%1$s><span class="ms-fav-result__name">%2$s</span></li>',
					0 === $i ? ' class="is-winner"' : '',
					esc_html( $row['nominee'] )
				);
			}
			echo '</ol></div>';
		}
	}
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'mysaline_favorites_results', 'mysaline_fav_results_shortcode' );

/* -------------------------------------------------------------------------
 * Admin: results screen + CSV export
 * ---------------------------------------------------------------------- */

/**
 * Add the results submenu under Favorites.
 */
function mysaline_fav_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=ms_fav_category',
		__( 'Favorites Results', 'mysaline' ),
		__( 'Results', 'mysaline' ),
		'edit_others_posts',
		'mysaline-fav-results',
		'mysaline_fav_render_results_page'
	);
}
add_action( 'admin_menu', 'mysaline_fav_admin_menu' );

/**
 * Render the admin results screen.
 */
function mysaline_fav_render_results_page() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to view results.', 'mysaline' ) );
	}

	$year   = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : mysaline_fav_year();
	$ballot = mysaline_fav_get_ballot();
	$voters = mysaline_fav_voter_count( $year );
	$export = wp_nonce_url(
		admin_url( 'admin-post.php?action=mysaline_fav_export&year=' . $year ),
		'mysaline_fav_export'
	);
	$draw   = wp_nonce_url(
		admin_url( 'admin-post.php?action=mysaline_fav_export_drawing&year=' . $year ),
		'mysaline_fav_export'
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Saline County Favorites — Results', 'mysaline' ); ?></h1>
		<p>
			<?php
			printf(
				/* translators: 1: ballot year, 2: number of ballots. */
				esc_html__( 'Ballot year %1$s · %2$s ballots cast', 'mysaline' ),
				esc_html( $year ),
				esc_html( number_format_i18n( $voters ) )
			);
			?>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( $export ); ?>"><?php esc_html_e( 'Export all results (CSV)', 'mysaline' ); ?></a>
			<a class="button" href="<?php echo esc_url( $draw ); ?>"><?php esc_html_e( 'Export drawing entries (CSV)', 'mysaline' ); ?></a>
		</p>

		<?php if ( empty( $ballot ) ) : ?>
			<p><?php esc_html_e( 'No categories with finalists yet.', 'mysaline' ); ?></p>
		<?php else : ?>
			<?php foreach ( $ballot as $section ) : ?>
				<h2><?php echo esc_html( $section['name'] ); ?></h2>
				<table class="widefat striped" style="max-width:820px;margin-bottom:1.5rem">
					<thead><tr>
						<th style="width:38%"><?php esc_html_e( 'Category', 'mysaline' ); ?></th>
						<th><?php esc_html_e( 'Leader', 'mysaline' ); ?></th>
						<th style="width:12%"><?php esc_html_e( 'Votes', 'mysaline' ); ?></th>
						<th style="width:12%"><?php esc_html_e( 'Total', 'mysaline' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $section['categories'] as $cat ) : ?>
						<?php
						$rows  = mysaline_fav_tally( $cat['id'], $year );
						$top   = ! empty( $rows ) ? $rows[0] : null;
						$total = 0;
						foreach ( $rows as $r ) {
							$total += (int) $r['votes'];
						}
						?>
						<tr>
							<td><strong><?php echo esc_html( $cat['title'] ); ?></strong></td>
							<td><?php echo $top ? esc_html( $top['nominee'] ) : '—'; ?></td>
							<td><?php echo $top ? esc_html( number_format_i18n( $top['votes'] ) ) : '0'; ?></td>
							<td><?php echo esc_html( number_format_i18n( $total ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Admin: bulk importer
 *
 * Setting up 155+ categories by hand is not realistic, so the whole ballot can
 * be pasted in one go using the plain-text shape the finalist lists are already
 * kept in.
 * ---------------------------------------------------------------------- */

/**
 * Add the import submenu.
 */
function mysaline_fav_import_menu() {
	add_submenu_page(
		'edit.php?post_type=ms_fav_category',
		__( 'Import Ballot', 'mysaline' ),
		__( 'Import Ballot', 'mysaline' ),
		'edit_others_posts',
		'mysaline-fav-import',
		'mysaline_fav_render_import_page'
	);
}
add_action( 'admin_menu', 'mysaline_fav_import_menu' );

/**
 * Parse a pasted ballot into structured sections/categories/nominees.
 *
 * Format (forgiving):
 *   ## Food                 → section
 *   Best BBQ                → category
 *   - Whole Hog Cafe        → nominee ("-", "*", or an indented line)
 *   - Wright's | https://…  → nominee with a link
 *
 * @param string $text Pasted text.
 * @return array list of { section, category, nominees[] }.
 */
function mysaline_fav_parse_import( $text ) {
	$lines   = preg_split( '/\r\n|\r|\n/', (string) $text );
	$out     = array();
	$section = '';
	$current = null;

	foreach ( $lines as $line ) {
		$trimmed = trim( $line );
		if ( '' === $trimmed ) {
			continue;
		}

		// Section heading: "## Food".
		if ( preg_match( '/^#{2,}\s*(.+)$/', $trimmed, $m ) ) {
			$section = trim( $m[1] );
			$current = null;
			continue;
		}

		// Nominee: bullet-prefixed, or indented under a category.
		$is_indented = (bool) preg_match( '/^[ \t]/', $line );
		if ( preg_match( '/^[-*•]\s*(.+)$/u', $trimmed, $m ) || ( $is_indented && $current ) ) {
			$nominee = isset( $m[1] ) ? trim( $m[1] ) : $trimmed;
			if ( '' !== $nominee && $current ) {
				$out[ $current ]['nominees'][] = $nominee;
			}
			continue;
		}

		// Otherwise it's a category name.
		$key = $section . '|' . $trimmed;
		if ( ! isset( $out[ $key ] ) ) {
			$out[ $key ] = array(
				'section'  => $section,
				'category' => $trimmed,
				'nominees' => array(),
			);
		}
		$current = $key;
	}

	return array_values( $out );
}

/**
 * Render + handle the import screen.
 */
function mysaline_fav_render_import_page() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to import.', 'mysaline' ) );
	}

	$report = null;

	if ( isset( $_POST['mysaline_fav_import_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['mysaline_fav_import_nonce'] ), 'mysaline_fav_import' ) ) {
		$raw     = isset( $_POST['ballot'] ) ? wp_unslash( $_POST['ballot'] ) : '';
		$replace = ! empty( $_POST['replace'] );
		$parsed  = mysaline_fav_parse_import( $raw );
		$report  = mysaline_fav_run_import( $parsed, $replace );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Import the Favorites Ballot', 'mysaline' ); ?></h1>

		<?php if ( $report ) : ?>
			<div class="notice notice-success"><p>
				<?php
				printf(
					/* translators: 1: categories created, 2: categories updated, 3: nominees, 4: sections. */
					esc_html__( 'Done — %1$d categories created, %2$d updated, %3$d finalists total, across %4$d sections.', 'mysaline' ),
					(int) $report['created'],
					(int) $report['updated'],
					(int) $report['nominees'],
					(int) $report['sections']
				);
				?>
			</p></div>
		<?php endif; ?>

		<p><?php esc_html_e( 'Paste the whole ballot at once. Use "##" for a section, a plain line for a category, and a dash for each finalist:', 'mysaline' ); ?></p>
		<pre style="background:#f6f7f7;padding:1rem;border-left:4px solid #2271b1;max-width:640px">## Food
Best BBQ
- Whole Hog Cafe
- Wright's Barbecue | https://example.com

Best Bakery — Sweets
- The Sweet Spot

## People
Best Dentist
- Dr. Jane Smith</pre>

		<form method="post">
			<?php wp_nonce_field( 'mysaline_fav_import', 'mysaline_fav_import_nonce' ); ?>
			<p>
				<label for="ballot"><strong><?php esc_html_e( 'Ballot text', 'mysaline' ); ?></strong></label><br />
				<textarea id="ballot" name="ballot" rows="18" style="width:100%;max-width:820px;font-family:monospace"></textarea>
			</p>
			<p>
				<label>
					<input type="checkbox" name="replace" value="1" />
					<?php esc_html_e( 'Replace the finalists on categories that already exist (otherwise new finalists are added to them)', 'mysaline' ); ?>
				</label>
			</p>
			<p>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Import ballot', 'mysaline' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'Existing categories are matched by name and updated — importing twice will not create duplicates.', 'mysaline' ); ?></p>
		</form>
	</div>
	<?php
}

/**
 * Create/update categories, sections and nominees from parsed import rows.
 *
 * @param array $rows    Parsed rows.
 * @param bool  $replace Replace existing nominee lists.
 * @return array Report counts.
 */
function mysaline_fav_run_import( $rows, $replace = false ) {
	$created  = 0;
	$updated  = 0;
	$nominees = 0;
	$sections = array();
	$order    = 0;

	foreach ( $rows as $row ) {
		if ( empty( $row['category'] ) ) {
			continue;
		}
		$order++;

		// Find an existing category by exact title.
		$existing = get_posts(
			array(
				'post_type'        => 'ms_fav_category',
				'post_status'      => array( 'publish', 'draft' ),
				'title'            => $row['category'],
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		if ( ! empty( $existing ) ) {
			$post_id = (int) $existing[0];
			$updated++;
		} else {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'ms_fav_category',
					'post_title'  => $row['category'],
					'post_status' => 'publish',
					'menu_order'  => $order,
				)
			);
			if ( is_wp_error( $post_id ) || ! $post_id ) {
				continue;
			}
			$created++;
		}

		// Section term.
		if ( ! empty( $row['section'] ) ) {
			$term = term_exists( $row['section'], 'ms_fav_section' );
			if ( ! $term ) {
				$term = wp_insert_term( $row['section'], 'ms_fav_section' );
			}
			if ( ! is_wp_error( $term ) ) {
				$term_id = isset( $term['term_id'] ) ? (int) $term['term_id'] : 0;
				if ( $term_id ) {
					wp_set_object_terms( $post_id, array( $term_id ), 'ms_fav_section' );
					$sections[ $row['section'] ] = true;
				}
			}
		}

		// Nominees.
		$incoming = array_values( array_filter( array_map( 'trim', (array) $row['nominees'] ) ) );
		if ( ! empty( $incoming ) ) {
			if ( $replace ) {
				$final = $incoming;
			} else {
				$current = array();
				foreach ( mysaline_fav_get_nominees( $post_id ) as $n ) {
					$current[] = $n['url'] ? $n['label'] . ' | ' . $n['url'] : $n['label'];
				}
				$final = array_values( array_unique( array_merge( $current, $incoming ) ) );
			}
			update_post_meta( $post_id, '_ms_fav_nominees', sanitize_textarea_field( implode( "\n", $final ) ) );
			$nominees += count( $final );
		}
	}

	mysaline_fav_flush_cache();

	return array(
		'created'  => $created,
		'updated'  => $updated,
		'nominees' => $nominees,
		'sections' => count( $sections ),
	);
}

/**
 * CSV export of every category tally.
 */
function mysaline_fav_export_csv() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'mysaline' ) );
	}
	check_admin_referer( 'mysaline_fav_export' );

	$year = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : mysaline_fav_year();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=saline-county-favorites-' . $year . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'Section', 'Category', 'Rank', 'Nominee', 'Votes' ) );

	foreach ( mysaline_fav_get_ballot() as $section ) {
		foreach ( $section['categories'] as $cat ) {
			$rank = 1;
			foreach ( mysaline_fav_tally( $cat['id'], $year ) as $row ) {
				fputcsv( $out, array( $section['name'], $cat['title'], $rank, $row['nominee'], $row['votes'] ) );
				$rank++;
			}
		}
	}
	fclose( $out );
	exit;
}
add_action( 'admin_post_mysaline_fav_export', 'mysaline_fav_export_csv' );

/**
 * CSV export of qualified drawing entries.
 */
function mysaline_fav_export_drawing() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'mysaline' ) );
	}
	check_admin_referer( 'mysaline_fav_export' );

	$year = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : mysaline_fav_year();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=favorites-drawing-entries-' . $year . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'Email', 'Categories voted' ) );
	foreach ( mysaline_fav_qualified_voters( $year ) as $row ) {
		fputcsv( $out, array( $row['voter_email'], $row['cats'] ) );
	}
	fclose( $out );
	exit;
}
add_action( 'admin_post_mysaline_fav_export_drawing', 'mysaline_fav_export_drawing' );
