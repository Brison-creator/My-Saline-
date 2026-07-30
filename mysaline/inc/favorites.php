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
define( 'MYSALINE_FAV_DB_VERSION', '1.1.0' );

/**
 * How long a confirmation link stays valid (seconds).
 */
define( 'MYSALINE_FAV_TOKEN_TTL', 48 * HOUR_IN_SECONDS );

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
 * Fully-qualified votes table name. Holds CONFIRMED votes only, so every tally
 * and export counts verified ballots and nothing else.
 *
 * @return string
 */
function mysaline_fav_table() {
	global $wpdb;
	return $wpdb->prefix . 'mysaline_fav_votes';
}

/**
 * Pending (unconfirmed) ballots, one row per submission awaiting an email click.
 *
 * @return string
 */
function mysaline_fav_pending_table() {
	global $wpdb;
	return $wpdb->prefix . 'mysaline_fav_pending';
}

/**
 * Create/upgrade the tables.
 */
function mysaline_fav_install_table() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$collate = $wpdb->get_charset_collate();
	$votes   = mysaline_fav_table();
	$pending = mysaline_fav_pending_table();

	$sql = "CREATE TABLE {$votes} (
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

	// Tokens are stored hashed, so a database leak cannot be replayed as votes.
	$sql = "CREATE TABLE {$pending} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		token_hash char(64) NOT NULL,
		ballot_year smallint(5) unsigned NOT NULL,
		voter_hash char(64) NOT NULL,
		voter_email varchar(191) NOT NULL DEFAULT '',
		payload longtext NOT NULL,
		return_url text NOT NULL,
		created_at datetime NOT NULL,
		expires_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY token_hash (token_hash),
		KEY expires_at (expires_at),
		KEY voter_hash (voter_hash)
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

	// Email confirmation (double opt-in) — strongly recommended.
	$wp_customize->add_setting(
		'mysaline_fav_confirm_email',
		array(
			'default'           => true,
			'sanitize_callback' => 'mysaline_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mysaline_fav_confirm_email',
		array(
			'type'        => 'checkbox',
			'label'       => __( 'Require email confirmation before votes count', 'mysaline' ),
			'description' => __( 'Recommended. Voters get a link by email; votes only count once they click it. This is what keeps one person from voting many times.', 'mysaline' ),
			'section'     => 'mysaline_favorites',
		)
	);

	$wp_customize->add_setting(
		'mysaline_fav_email_subject',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'mysaline_fav_email_subject',
		array(
			'type'        => 'text',
			'label'       => __( 'Confirmation email subject', 'mysaline' ),
			'description' => __( 'Leave blank to use “Confirm your {site} Favorites ballot”.', 'mysaline' ),
			'section'     => 'mysaline_favorites',
		)
	);

	$wp_customize->add_setting(
		'mysaline_fav_email_body',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control(
		'mysaline_fav_email_body',
		array(
			'type'        => 'textarea',
			'label'       => __( 'Confirmation email opening line', 'mysaline' ),
			'description' => __( 'The confirmation link, vote count and expiry note are added automatically.', 'mysaline' ),
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
 * Stable voter identity derived from a confirmed email address.
 * One confirmed email = one ballot.
 *
 * @param string $email Email address.
 * @return string 64-char hash, or '' when no email given.
 */
function mysaline_fav_voter_hash( $email = '' ) {
	$email = strtolower( trim( (string) $email ) );
	if ( '' === $email ) {
		return '';
	}
	return hash( 'sha256', 'ms-fav-email|' . $email . '|' . wp_salt( 'auth' ) );
}

/**
 * Whether email confirmation is required before votes count.
 *
 * @return bool
 */
function mysaline_fav_requires_confirmation() {
	return (bool) get_theme_mod( 'mysaline_fav_confirm_email', true );
}

/* ---- "already confirmed on this browser" cookie ---------------------- */

/**
 * Cookie name for the trusted-voter token.
 *
 * @return string
 */
function mysaline_fav_cookie_name() {
	return 'mysaline_fav_voter';
}

/**
 * Issue the trusted-voter cookie after a successful confirmation, so the voter
 * can revise their picks without a fresh email each time.
 *
 * @param string $hash  Voter hash.
 * @param string $email Voter email.
 */
function mysaline_fav_set_trust_cookie( $hash, $email ) {
	$payload = $hash . '|' . rawurlencode( $email );
	$sig     = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
	$value   = $payload . '|' . $sig;

	// Not HttpOnly-sensitive beyond identity; still marked HttpOnly + Secure.
	setcookie(
		mysaline_fav_cookie_name(),
		$value,
		array(
			'expires'  => time() + YEAR_IN_SECONDS,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

/**
 * Read and verify the trusted-voter cookie.
 *
 * Identity comes from the cookie, never from the submitted email field — that is
 * what stops one person overwriting another person's ballot by typing their
 * address.
 *
 * @return array|null { hash, email } or null when absent/invalid.
 */
function mysaline_fav_trusted_voter() {
	$name = mysaline_fav_cookie_name();
	if ( empty( $_COOKIE[ $name ] ) ) {
		return null;
	}

	$raw   = sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) );
	$parts = explode( '|', $raw );
	if ( 3 !== count( $parts ) ) {
		return null;
	}

	list( $hash, $email_enc, $sig ) = $parts;
	$expected = hash_hmac( 'sha256', $hash . '|' . $email_enc, wp_salt( 'auth' ) );
	if ( ! hash_equals( $expected, $sig ) ) {
		return null;
	}
	if ( ! preg_match( '/^[a-f0-9]{64}$/', $hash ) ) {
		return null;
	}

	return array(
		'hash'  => $hash,
		'email' => sanitize_email( rawurldecode( $email_enc ) ),
	);
}

/* ---- Writing votes --------------------------------------------------- */

/**
 * Validate a raw picks array against the real finalist lists.
 *
 * @param array $raw category_id => nominee.
 * @return array Clean category_id => nominee.
 */
function mysaline_fav_sanitize_votes( $raw ) {
	$clean = array();
	foreach ( (array) $raw as $category_id => $choice ) {
		$category_id = absint( $category_id );
		$choice      = sanitize_text_field( $choice );
		if ( ! $category_id || '' === $choice ) {
			continue;
		}
		// Never trust the client: the pick must be a real finalist here.
		$allowed = wp_list_pluck( mysaline_fav_get_nominees( $category_id ), 'label' );
		if ( ! in_array( $choice, $allowed, true ) ) {
			continue;
		}
		$clean[ $category_id ] = $choice;
	}
	return $clean;
}

/**
 * Write confirmed votes for a voter, replacing their previous picks.
 *
 * @param string $hash  Voter hash.
 * @param string $email Voter email.
 * @param array  $votes Clean category_id => nominee.
 * @return int Number of categories saved.
 */
function mysaline_fav_apply_votes( $hash, $email, $votes ) {
	global $wpdb;

	$table = mysaline_fav_table();
	$year  = mysaline_fav_year();
	$now   = current_time( 'mysql' );
	$saved = 0;

	foreach ( $votes as $category_id => $choice ) {
		// UNIQUE(voter_hash, ballot_year, category_id) keeps one row per voter per
		// category; re-voting overwrites, matching "most recent pick counts".
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"INSERT INTO {$table} (ballot_year, category_id, nominee, voter_hash, voter_email, created_at)
				 VALUES (%d, %d, %s, %s, %s, %s)
				 ON DUPLICATE KEY UPDATE nominee = VALUES(nominee), voter_email = VALUES(voter_email), created_at = VALUES(created_at)",
				$year,
				absint( $category_id ),
				$choice,
				$hash,
				$email,
				$now
			)
		);
		$saved++;
	}

	return $saved;
}

/* ---- Pending ballots + confirmation email ---------------------------- */

/**
 * Store a pending ballot and return the raw (un-hashed) token for emailing.
 *
 * @param string $email      Voter email.
 * @param string $hash       Voter hash.
 * @param array  $votes      Clean votes.
 * @param string $return_url Where to send them after confirming.
 * @return string Raw token.
 */
function mysaline_fav_store_pending( $email, $hash, $votes, $return_url ) {
	global $wpdb;

	$token = bin2hex( random_bytes( 24 ) );

	// Supersede any earlier unconfirmed ballot from this voter.
	$wpdb->delete( mysaline_fav_pending_table(), array( 'voter_hash' => $hash, 'ballot_year' => mysaline_fav_year() ), array( '%s', '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		mysaline_fav_pending_table(),
		array(
			'token_hash'  => hash( 'sha256', $token ),
			'ballot_year' => mysaline_fav_year(),
			'voter_hash'  => $hash,
			'voter_email' => $email,
			'payload'     => wp_json_encode( $votes ),
			'return_url'  => $return_url,
			'created_at'  => current_time( 'mysql' ),
			'expires_at'  => gmdate( 'Y-m-d H:i:s', time() + MYSALINE_FAV_TOKEN_TTL ),
		),
		array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
	);

	return $token;
}

/**
 * Email the confirmation link.
 *
 * @param string $email Recipient.
 * @param string $token Raw token.
 * @param int    $count Number of categories on the ballot.
 * @return bool Whether wp_mail accepted the message.
 */
function mysaline_fav_send_confirmation( $email, $token, $count ) {
	$link = add_query_arg( 'ms_fav_confirm', $token, home_url( '/' ) );
	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = get_theme_mod(
		'mysaline_fav_email_subject',
		/* translators: %s: site name. */
		sprintf( __( 'Confirm your %s Favorites ballot', 'mysaline' ), $site )
	);

	$intro = get_theme_mod(
		'mysaline_fav_email_body',
		__( 'Thanks for voting in Saline County Favorites! Click the link below to confirm your ballot. Your votes are not counted until you do.', 'mysaline' )
	);

	$body  = $intro . "\n\n";
	/* translators: %d: number of categories voted in. */
	$body .= sprintf( _n( 'You voted in %d category.', 'You voted in %d categories.', $count, 'mysaline' ), $count ) . "\n\n";
	$body .= __( 'Confirm your ballot:', 'mysaline' ) . "\n" . $link . "\n\n";
	$body .= __( 'This link expires in 48 hours. If you did not vote, you can ignore this email — nothing will be counted.', 'mysaline' ) . "\n\n";
	$body .= $site . "\n" . home_url( '/' ) . "\n";

	return (bool) wp_mail( $email, $subject, $body );
}

/**
 * Handle a confirmation-link click.
 */
function mysaline_fav_handle_confirm() {
	if ( empty( $_GET['ms_fav_confirm'] ) ) {
		return;
	}

	global $wpdb;

	$token = sanitize_text_field( wp_unslash( $_GET['ms_fav_confirm'] ) );
	if ( ! preg_match( '/^[a-f0-9]{48}$/', $token ) ) {
		wp_safe_redirect( add_query_arg( 'fav', 'expired', home_url( '/' ) ) );
		exit;
	}

	$table = mysaline_fav_pending_table();
	$row   = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare( "SELECT * FROM {$table} WHERE token_hash = %s", hash( 'sha256', $token ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		ARRAY_A
	);

	$fallback = home_url( '/' );

	if ( ! $row ) {
		wp_safe_redirect( add_query_arg( 'fav', 'expired', $fallback ) );
		exit;
	}

	$return = ! empty( $row['return_url'] ) ? $row['return_url'] : $fallback;

	// Expired?
	if ( strtotime( $row['expires_at'] . ' UTC' ) < time() ) {
		$wpdb->delete( $table, array( 'id' => (int) $row['id'] ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		wp_safe_redirect( add_query_arg( 'fav', 'expired', $return ) );
		exit;
	}

	// Re-validate the picks at confirm time, in case finalists changed since.
	$votes = json_decode( $row['payload'], true );
	$votes = mysaline_fav_sanitize_votes( is_array( $votes ) ? $votes : array() );

	$count = 0;
	if ( ! empty( $votes ) ) {
		$count = mysaline_fav_apply_votes( $row['voter_hash'], $row['voter_email'], $votes );
	}

	// Single-use token.
	$wpdb->delete( $table, array( 'id' => (int) $row['id'] ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// Trust this browser from now on.
	mysaline_fav_set_trust_cookie( $row['voter_hash'], $row['voter_email'] );

	wp_safe_redirect(
		add_query_arg(
			array(
				'fav'   => 'confirmed',
				'count' => $count,
			),
			$return
		)
	);
	exit;
}
add_action( 'template_redirect', 'mysaline_fav_handle_confirm' );

/**
 * Delete expired pending ballots daily.
 */
function mysaline_fav_cleanup_pending() {
	global $wpdb;
	$table = mysaline_fav_pending_table();
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->prepare( "DELETE FROM {$table} WHERE expires_at < %s", gmdate( 'Y-m-d H:i:s' ) )
	);
}
add_action( 'mysaline_fav_cleanup', 'mysaline_fav_cleanup_pending' );

/**
 * Schedule the cleanup job.
 */
function mysaline_fav_schedule_cleanup() {
	if ( ! wp_next_scheduled( 'mysaline_fav_cleanup' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mysaline_fav_cleanup' );
	}
}
add_action( 'after_switch_theme', 'mysaline_fav_schedule_cleanup' );
add_action( 'admin_init', 'mysaline_fav_schedule_cleanup' );

/**
 * Clear the schedule when the theme is switched away.
 */
function mysaline_fav_unschedule_cleanup() {
	$ts = wp_next_scheduled( 'mysaline_fav_cleanup' );
	if ( $ts ) {
		wp_unschedule_event( $ts, 'mysaline_fav_cleanup' );
	}
}
add_action( 'switch_theme', 'mysaline_fav_unschedule_cleanup' );

/* ---- Submission ------------------------------------------------------ */

/**
 * Handle a submitted ballot.
 *
 * Trusted browser (already confirmed once) → votes apply immediately.
 * Otherwise → stored as pending and a confirmation email is sent.
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

	$votes = mysaline_fav_sanitize_votes(
		isset( $_POST['vote'] ) && is_array( $_POST['vote'] ) ? wp_unslash( $_POST['vote'] ) : array()
	);
	if ( empty( $votes ) ) {
		wp_safe_redirect( add_query_arg( 'fav', 'empty', $redirect ) );
		exit;
	}

	$trusted = mysaline_fav_trusted_voter();
	$confirm = mysaline_fav_requires_confirmation();

	/* Already-confirmed browser: identity comes from the cookie, so a submitted
	   email can never be used to overwrite someone else's ballot. */
	if ( $trusted ) {
		if ( get_transient( 'ms_fav_rl_' . $trusted['hash'] ) ) {
			wp_safe_redirect( add_query_arg( 'fav', 'toofast', $redirect ) );
			exit;
		}
		set_transient( 'ms_fav_rl_' . $trusted['hash'], 1, 15 );

		$saved = mysaline_fav_apply_votes( $trusted['hash'], $trusted['email'], $votes );
		wp_safe_redirect(
			add_query_arg( array( 'fav' => 'updated', 'count' => $saved ), $redirect )
		);
		exit;
	}

	$email = isset( $_POST['voter_email'] ) ? sanitize_email( wp_unslash( $_POST['voter_email'] ) ) : '';

	// Confirmation mode requires a valid address.
	if ( $confirm && ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'fav', 'noemail', $redirect ) );
		exit;
	}

	// Confirmation off and no email given: count it straight away, keyed on a
	// salted IP+agent hash (weaker, but the owner opted out of confirmation).
	if ( ! $confirm && ! is_email( $email ) ) {
		$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$agent  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$anon   = hash( 'sha256', 'ms-fav-anon|' . $ip . '|' . $agent . '|' . wp_salt( 'auth' ) );
		if ( get_transient( 'ms_fav_rl_' . $anon ) ) {
			wp_safe_redirect( add_query_arg( 'fav', 'toofast', $redirect ) );
			exit;
		}
		set_transient( 'ms_fav_rl_' . $anon, 1, 15 );
		$saved = mysaline_fav_apply_votes( $anon, '', $votes );
		wp_safe_redirect( add_query_arg( array( 'fav' => 'thanks', 'count' => $saved ), $redirect ) );
		exit;
	}

	$hash = mysaline_fav_voter_hash( $email );

	/* Per-address throttle. Without this the form could be used to mail-bomb
	   somebody by submitting their address over and over. */
	$mail_gate  = 'ms_fav_mail_' . $hash;
	$mail_count = (int) get_transient( $mail_gate );
	if ( $mail_count >= 3 ) {
		wp_safe_redirect( add_query_arg( 'fav', 'toomany', $redirect ) );
		exit;
	}

	// Per-IP throttle, so one machine cannot cycle through many addresses.
	$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$ip_gate = 'ms_fav_ip_' . hash( 'sha256', $ip . wp_salt( 'auth' ) );
	if ( (int) get_transient( $ip_gate ) >= 10 ) {
		wp_safe_redirect( add_query_arg( 'fav', 'toomany', $redirect ) );
		exit;
	}

	$token = mysaline_fav_store_pending( $email, $hash, $votes, $redirect );
	$sent  = mysaline_fav_send_confirmation( $email, $token, count( $votes ) );

	if ( ! $sent ) {
		wp_safe_redirect( add_query_arg( 'fav', 'emailfail', $redirect ) );
		exit;
	}

	set_transient( $mail_gate, $mail_count + 1, HOUR_IN_SECONDS );
	set_transient( $ip_gate, (int) get_transient( $ip_gate ) + 1, HOUR_IN_SECONDS );

	wp_safe_redirect(
		add_query_arg( array( 'fav' => 'check_email', 'count' => count( $votes ) ), $redirect )
	);
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
 * Ballots still waiting on an email click.
 *
 * @param int $year Ballot year.
 * @return int
 */
function mysaline_fav_pending_count( $year = 0 ) {
	global $wpdb;
	$table = mysaline_fav_pending_table();
	$year  = $year ? (int) $year : mysaline_fav_year();

	return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE ballot_year = %d AND expires_at >= %s", $year, gmdate( 'Y-m-d H:i:s' ) ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
				/* translators: 1: ballot year, 2: number of confirmed ballots. */
				esc_html__( 'Ballot year %1$s · %2$s confirmed ballots', 'mysaline' ),
				esc_html( $year ),
				esc_html( number_format_i18n( $voters ) )
			);

			if ( mysaline_fav_requires_confirmation() ) {
				$pending = mysaline_fav_pending_count( $year );
				echo ' · ';
				printf(
					/* translators: %s: number of ballots awaiting email confirmation. */
					esc_html__( '%s awaiting email confirmation (not counted)', 'mysaline' ),
					esc_html( number_format_i18n( $pending ) )
				);
			}
			?>
		</p>
		<?php if ( ! mysaline_fav_requires_confirmation() ) : ?>
			<div class="notice notice-warning inline"><p>
				<?php esc_html_e( 'Email confirmation is off, so anyone can vote repeatedly from different browsers. Turn it on under Customize → MySaline Options → Saline County Favorites.', 'mysaline' ); ?>
			</p></div>
		<?php endif; ?>
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
