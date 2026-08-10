<?php
/**
 * Seeds the local development site with realistic demo content so every theme
 * feature is visible immediately: posts, featured stories, events, obituaries,
 * businesses, ads, the Favorites ballot, menus, pages and theme options.
 *
 * Run with:  npm run seed
 * (which is: wp-env run cli wp eval-file wp-content/mysaline-dev/seed-demo.php)
 *
 * Idempotent — safe to run repeatedly. Existing items are matched by title and
 * updated rather than duplicated.
 *
 * All names, businesses and headlines below are invented placeholder content.
 *
 * @package MySaline\Dev
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run through WP-CLI.\n";
	return;
}

if ( ! function_exists( 'mysaline_fav_run_import' ) ) {
	WP_CLI::error( 'The MySaline theme is not active. Run: npm run setup' );
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/**
 * Find-or-create a post by title + type.
 *
 * @param string $title   Title.
 * @param string $type    Post type.
 * @param array  $extra   Extra wp_insert_post args.
 * @return int Post ID.
 */
function ms_seed_post( $title, $type = 'post', $extra = array() ) {
	$existing = get_posts(
		array(
			'post_type'        => $type,
			'post_status'      => array( 'publish', 'draft' ),
			'title'            => $title,
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);

	$args = array_merge(
		array(
			'post_type'    => $type,
			'post_title'   => $title,
			'post_status'  => 'publish',
			'post_content' => '',
		),
		$extra
	);

	if ( ! empty( $existing ) ) {
		$args['ID'] = (int) $existing[0];
		wp_update_post( $args );
		return (int) $existing[0];
	}

	$id = wp_insert_post( $args );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Generate an abstract placeholder image with GD and attach it to a post.
 *
 * Keeps the demo looking like a real newspaper without shipping binary assets
 * in the repository.
 *
 * @param int    $post_id Post to attach to.
 * @param string $seed    Seed string for deterministic colour choice.
 * @param int    $w       Width.
 * @param int    $h       Height.
 * @return int Attachment ID (0 on failure).
 */
function ms_seed_image( $post_id, $seed, $w = 1200, $h = 675 ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return 0;
	}

	// Skip if this post already has a thumbnail.
	if ( has_post_thumbnail( $post_id ) ) {
		return (int) get_post_thumbnail_id( $post_id );
	}

	$palettes = array(
		array( array( 11, 37, 69 ), array( 200, 16, 46 ) ),
		array( array( 22, 56, 95 ), array( 242, 183, 5 ) ),
		array( array( 29, 74, 109 ), array( 200, 16, 46 ) ),
		array( array( 18, 58, 82 ), array( 242, 183, 5 ) ),
		array( array( 36, 64, 94 ), array( 120, 180, 220 ) ),
	);
	$p  = $palettes[ abs( crc32( $seed ) ) % count( $palettes ) ];
	$im = imagecreatetruecolor( $w, $h );

	// Vertical gradient base.
	for ( $y = 0; $y < $h; $y++ ) {
		$t = $y / max( 1, $h - 1 );
		$r = (int) ( $p[0][0] * ( 1 - $t * .45 ) );
		$g = (int) ( $p[0][1] * ( 1 - $t * .45 ) );
		$b = (int) ( $p[0][2] * ( 1 - $t * .45 ) );
		$c = imagecolorallocate( $im, $r, $g, $b );
		imageline( $im, 0, $y, $w, $y, $c );
	}

	// A few translucent accent shapes, deterministic per seed.
	$hash = md5( $seed );
	imagealphablending( $im, true );
	for ( $i = 0; $i < 5; $i++ ) {
		$hx  = hexdec( substr( $hash, $i * 4, 2 ) ) / 255;
		$hy  = hexdec( substr( $hash, $i * 4 + 2, 2 ) ) / 255;
		$rad = (int) ( $w * ( 0.16 + $hx * 0.3 ) );
		$col = imagecolorallocatealpha(
			$im,
			$p[1][0],
			$p[1][1],
			$p[1][2],
			100 + $i * 12
		);
		imagefilledellipse( $im, (int) ( $hx * $w ), (int) ( $hy * $h ), $rad, $rad, $col );
	}

	$upload = wp_upload_dir();
	$name   = 'mysaline-demo-' . substr( $hash, 0, 10 ) . '.jpg';
	$path   = trailingslashit( $upload['path'] ) . $name;
	imagejpeg( $im, $path, 82 );
	imagedestroy( $im );

	$attach_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => 'Demo image',
			'post_status'    => 'inherit',
		),
		$path,
		$post_id
	);

	if ( ! $attach_id || is_wp_error( $attach_id ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $path ) );
	set_post_thumbnail( $post_id, $attach_id );

	return (int) $attach_id;
}

/**
 * Paragraph filler that reads like article copy without pretending to be news.
 *
 * @param int $paras Number of paragraphs.
 * @return string
 */
function ms_seed_body( $paras = 4 ) {
	$bank = array(
		'This is sample article copy used to populate the development site. It stands in for real reporting so the layout, typography and spacing can be reviewed with realistic text lengths.',
		'Paragraphs run at a comfortable reading measure, with headings set in the theme’s serif and body copy in a system sans. Inline links, lists and pull quotes all inherit the same treatment.',
		'Editors write stories exactly as they do now — this theme changes how content is presented, not how it is entered. Categories, tags, authors and featured images all behave the way they always have.',
		'Longer stories can carry subheads, embedded images and an in-content advertisement placed automatically a few paragraphs in, so advertising stays part of the reading experience.',
		'Below each story the theme shows tags, an author box when a bio exists, previous and next navigation, and related stories drawn from the same category.',
	);
	$out = '';
	for ( $i = 0; $i < $paras; $i++ ) {
		$out .= $bank[ $i % count( $bank ) ] . "\n\n";
	}
	return trim( $out );
}

WP_CLI::log( 'Seeding MySaline demo content…' );

/* -------------------------------------------------------------------------
 * 1. Categories
 * ---------------------------------------------------------------------- */

$categories = array(
	'Saline County'  => 'County-wide news and government.',
	'Benton'         => 'News from Benton.',
	'Bryant'         => 'News from Bryant.',
	'Business News'  => 'Openings, closings, ribbon cuttings and local economy.',
	'Dining'         => 'Restaurants, food trucks and reviews.',
	'Elections'      => 'Candidates, filings and results.',
	'Schools'        => 'Districts, boards and student news.',
	'Community'      => 'Events, people and neighbourhood news.',
	'Sports'         => 'High school and youth athletics.',
	'Columnists'     => 'Recurring columns and opinion.',
	'Public Records' => 'Mugshots, 911 calls, court filings, marriage licenses and jobs.',
);

$cat_ids = array();
foreach ( $categories as $name => $desc ) {
	$term = term_exists( $name, 'category' );
	if ( ! $term ) {
		$term = wp_insert_term( $name, 'category', array( 'description' => $desc ) );
	}
	if ( ! is_wp_error( $term ) ) {
		$cat_ids[ $name ] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	}
}
WP_CLI::log( '  · ' . count( $cat_ids ) . ' categories' );

/* -------------------------------------------------------------------------
 * 2. Posts (some flagged as Featured for the hero)
 * ---------------------------------------------------------------------- */

$posts = array(
	array( 'County fair returns this weekend with a bigger midway and free parking', 'Saline County', true ),
	array( 'Benton council approves the downtown parking plan', 'Benton', true ),
	array( 'Bryant approves a new sidewalk plan for Reynolds Road', 'Bryant', true ),
	array( 'Who has filed so far for November’s municipal races', 'Elections', true ),
	array( 'Registration dates set for the coming school year', 'Schools', true ),
	array( 'Four new places to eat in Benton this summer', 'Dining', false ),
	array( 'Road work begins Monday on Highway 5 north', 'Saline County', false ),
	array( 'Library summer reading wraps with a record turnout', 'Community', false ),
	array( 'Chamber holds a ribbon cutting for a downtown shop', 'Business News', false ),
	array( 'This week’s job listings for Saline County', 'Business News', false ),
	array( 'Plans filed for a new retail center off Interstate 30', 'Business News', false ),
	array( 'Council sets the date for its budget hearing', 'Benton', false ),
	array( 'Fire department puts two new engines into service', 'Saline County', false ),
	array( 'Splash pad reopens after repairs', 'Community', false ),
	array( 'Food truck rally returns to the square in August', 'Dining', false ),
	array( 'School board reviews the transportation budget', 'Schools', false ),
	array( 'Salt Bowl set for late August at War Memorial', 'Sports', false ),
	array( 'Volleyball tryouts announced for both districts', 'Sports', false ),
	array( 'How the Ball Bounces: on youth sports and grown-ups', 'Columnists', false ),
	array( 'Court filings for the week of July 27', 'Public Records', false ),
	array( 'Marriage licenses issued the week of July 27', 'Public Records', false ),
	array( 'This week in 911 calls', 'Public Records', false ),
);

$made = 0;
foreach ( $posts as $i => $row ) {
	list( $title, $cat, $featured ) = $row;

	$id = ms_seed_post(
		$title,
		'post',
		array(
			'post_content' => ms_seed_body( 5 ),
			'post_excerpt' => 'Sample excerpt used to populate the development site so card and archive layouts can be reviewed with realistic text.',
			'post_date'    => gmdate( 'Y-m-d H:i:s', strtotime( '-' . $i . ' days' ) ),
		)
	);
	if ( ! $id ) {
		continue;
	}

	if ( isset( $cat_ids[ $cat ] ) ) {
		wp_set_post_categories( $id, array( $cat_ids[ $cat ] ) );
	}
	wp_set_post_tags( $id, array( 'saline county', 'local' ) );

	if ( $featured ) {
		update_post_meta( $id, '_ms_featured', '1' );
	}

	ms_seed_image( $id, 'post-' . $title );
	$made++;
}
WP_CLI::log( '  · ' . $made . ' posts (5 flagged as Featured)' );

/* -------------------------------------------------------------------------
 * 3. Community events
 * ---------------------------------------------------------------------- */

$events = array(
	array( 'Third Thursday on the Square', '+3 days', '5:00 PM – 9:00 PM', 'Downtown Benton', 'Free' ),
	array( 'Chamber Bingo Night', '+6 days', '6:30 PM', 'Community Center', '$10' ),
	array( 'Saturday Farmers Market', '+10 days', '8:00 AM – Noon', 'Riverside Park', 'Free' ),
	array( 'Back-to-School Bash', '+15 days', '4:00 PM', 'Bryant Athletic Complex', 'Free' ),
	array( 'Fall Craft Fair', '+24 days', '9:00 AM – 4:00 PM', 'The Depot', '$3' ),
);

foreach ( $events as $e ) {
	$id = ms_seed_post( $e[0], 'ms_event', array( 'post_content' => ms_seed_body( 2 ) ) );
	if ( ! $id ) {
		continue;
	}
	update_post_meta( $id, '_ms_event_start', gmdate( 'Y-m-d', strtotime( $e[1] ) ) );
	update_post_meta( $id, '_ms_event_time', $e[2] );
	update_post_meta( $id, '_ms_event_venue', $e[3] );
	update_post_meta( $id, '_ms_event_cost', $e[4] );
	update_post_meta( $id, '_ms_event_organizer', 'Sample Organizer' );
	ms_seed_image( $id, 'event-' . $e[0], 800, 450 );
}
WP_CLI::log( '  · ' . count( $events ) . ' events' );

/* -------------------------------------------------------------------------
 * 4. Obituaries
 * ---------------------------------------------------------------------- */

$obits = array(
	array( 'Sample Name One', '1941-03-12', '2026-07-24', 85, 'Benton' ),
	array( 'Sample Name Two', '1953-08-02', '2026-07-22', 72, 'Bryant' ),
	array( 'Sample Name Three', '1936-11-19', '2026-07-20', 89, 'Alexander' ),
	array( 'Sample Name Four', '1948-01-30', '2026-07-18', 78, 'Benton' ),
);

foreach ( $obits as $o ) {
	$id = ms_seed_post( $o[0], 'ms_obituary', array( 'post_content' => ms_seed_body( 3 ) ) );
	if ( ! $id ) {
		continue;
	}
	update_post_meta( $id, '_ms_obit_born', $o[1] );
	update_post_meta( $id, '_ms_obit_died', $o[2] );
	update_post_meta( $id, '_ms_obit_age', (string) $o[3] );
	update_post_meta( $id, '_ms_obit_city', $o[4] );
	update_post_meta( $id, '_ms_obit_service', 'Saturday at 2:00 PM' );
	update_post_meta( $id, '_ms_obit_location', 'Sample Memorial Chapel' );
	update_post_meta( $id, '_ms_obit_home', 'Sample Funeral Home' );
	ms_seed_image( $id, 'obit-' . $o[0], 600, 600 );
}
WP_CLI::log( '  · ' . count( $obits ) . ' obituaries' );

/* -------------------------------------------------------------------------
 * 5. Business directory
 * ---------------------------------------------------------------------- */

$biz_cats = array( 'Retail', 'Food & Dining', 'Health', 'Automotive', 'Home Services' );
foreach ( $biz_cats as $bc ) {
	if ( ! term_exists( $bc, 'ms_business_cat' ) ) {
		wp_insert_term( $bc, 'ms_business_cat' );
	}
}

$businesses = array(
	array( 'Sample Hardware Co.', 'Retail', '(501) 000-0001', 'Mon–Sat 7–6', true ),
	array( 'Sample Family Dental', 'Health', '(501) 000-0002', 'Mon–Thu 8–5', true ),
	array( 'Sample Auto Service', 'Automotive', '(501) 000-0003', 'Mon–Fri 7:30–5:30', true ),
	array( 'Sample Coffee House', 'Food & Dining', '(501) 000-0004', 'Daily 6–4', false ),
	array( 'Sample Lawn & Landscape', 'Home Services', '(501) 000-0005', 'Mon–Fri 8–5', false ),
	array( 'Sample Boutique', 'Retail', '(501) 000-0006', 'Tue–Sat 10–6', false ),
);

foreach ( $businesses as $b ) {
	$id = ms_seed_post( $b[0], 'ms_business', array( 'post_content' => ms_seed_body( 2 ) ) );
	if ( ! $id ) {
		continue;
	}
	wp_set_object_terms( $id, $b[1], 'ms_business_cat' );
	update_post_meta( $id, '_ms_biz_phone', $b[2] );
	update_post_meta( $id, '_ms_biz_hours', $b[3] );
	update_post_meta( $id, '_ms_biz_email', 'hello@example.com' );
	update_post_meta( $id, '_ms_biz_website', 'https://example.com' );
	update_post_meta( $id, '_ms_biz_address', "100 Sample Street\nBenton, AR 72015" );
	if ( $b[4] ) {
		update_post_meta( $id, '_ms_biz_featured', '1' );
	}
	ms_seed_image( $id, 'biz-' . $b[0], 600, 600 );
}
WP_CLI::log( '  · ' . count( $businesses ) . ' businesses in ' . count( $biz_cats ) . ' categories' );

/* -------------------------------------------------------------------------
 * 6. Advertisements (one per zone, using the code field so they render
 *    without needing uploaded creative)
 * ---------------------------------------------------------------------- */

$zones = array(
	'header'        => 'Header leaderboard',
	'homepage_top'  => 'Homepage — below hero',
	'homepage_mid'  => 'Homepage — between sections',
	'sidebar'       => 'Sidebar',
	'in_content'    => 'In-content',
	'below_content' => 'Below article',
	'footer'        => 'Footer',
);

foreach ( $zones as $zone => $label ) {
	$id = ms_seed_post( 'Demo ad — ' . $label, 'ms_ad' );
	if ( ! $id ) {
		continue;
	}
	update_post_meta( $id, '_ms_ad_zone', $zone );
	update_post_meta( $id, '_ms_ad_sponsor', 'Sample Sponsor' );
	update_post_meta( $id, '_ms_ad_link', 'https://example.com' );
	update_post_meta(
		$id,
		'_ms_ad_code',
		'<div style="background:repeating-linear-gradient(45deg,#eef1f6,#eef1f6 11px,#e3e8f0 11px,#e3e8f0 22px);'
			. 'border:1px dashed #cbd4e1;border-radius:4px;padding:1.5rem;color:#5b6472;font:600 14px/1.4 system-ui,sans-serif">'
			. esc_html( $label ) . ' ad zone &middot; managed under Advertisements</div>'
	);
}
WP_CLI::log( '  · ' . count( $zones ) . ' ads (one per zone)' );

/* -------------------------------------------------------------------------
 * 7. Saline County Favorites ballot — via the theme's own importer
 * ---------------------------------------------------------------------- */

$ballot = <<<'BALLOT'
## Food
Restaurant — BBQ
- Smokehouse on Main
- River Road BBQ
- The Pit Stop
Restaurant — Mexican
- Casa Verde
- Tres Amigos
- El Camino Grill
Restaurant — Asian
- Golden Bowl
- Saline Sushi
- Bangkok Garden
Food Truck
- Curbside Kitchen
- The Rolling Griddle
- Taco Tumbleweed
Bakery — Sweets
- The Sweet Spot
- Cake Corner
- Sugar & Flour
Caterer
- Southern Table Catering
- Two Pines Events
- Copper Spoon
Beverage Stop
- Bean There Coffee
- The Daily Grind
- Sip & Go
Fresh Produce
- Riverside Farmers Market
- Hillcrest Produce
- Green Acres Stand

## Businesses
Plumber
- Ace Plumbing Co.
- Bob's Pipes & Drains
- Clearwater Plumbing
Heating & Air
- Comfort Zone HVAC
- Cool Breeze Air
- Allied Heating
Auto Repair
- Main Street Motors
- Precision Auto Care
- Hometown Garage
Boutique
- Magnolia & Lace
- The Blue Door
- Prairie Rose
Salon
- Studio 5 Salon
- The Mane Room
- Bella Hair Co.
Barber Shop
- Classic Cuts
- The Barber Post
- Sharp Edge
Lawn Care
- GreenScape Lawn
- Cutting Edge Yards
- Evergreen Lawn Co.
Furniture Store
- Heritage Home
- The Furniture Barn
- Oak & Iron
Insurance Agency
- Shield Insurance Group
- Legacy Insurance
- Cornerstone Agency
Bank or Credit Union
- First Community Bank
- Saline Federal CU
- Heritage Bank
Veterinarian
- Paws & Claws Clinic
- Countryside Vet
- Animal Care Center

## People
Dentist
- Dr. A. Sample
- Dr. B. Sample
- Dr. C. Sample
Attorney
- J. Sample, Esq.
- K. Sample, Esq.
- L. Sample, Esq.
Realtor
- M. Sample
- N. Sample
- P. Sample
Teacher
- R. Sample
- S. Sample
- T. Sample
Nurse
- V. Sample, RN
- W. Sample, RN
- Y. Sample, RN
Photographer
- Sample Photography
- Golden Hour Studio
- Wildflower Photo
Coach
- Coach Sample (Football)
- Coach Sample (Softball)
- Coach Sample (Track)
Accountant
- D. Sample, CPA
- E. Sample, CPA
- F. Sample, CPA

## Places & Things
Park
- Riverside Park
- Tyndall Park
- Bishop Park
Trail
- Saline River Trail
- Cedar Ridge Loop
- Old Mill Path
Date Spot
- The Rooftop
- Riverwalk Bistro
- Starlight Cinema
Event Venue
- The Depot
- Magnolia Hall
- The Barn at Oak Creek
Swimming Spot
- City Aquatic Center
- Sandy Beach
- Cedar Springs
Photo Spot
- The Old Mill
- Downtown Mural Wall
- Riverbend Overlook
BALLOT;

$report = mysaline_fav_run_import( mysaline_fav_parse_import( $ballot ), true );
WP_CLI::log(
	sprintf(
		'  · Favorites ballot: %d created, %d updated, %d finalists, %d sections',
		$report['created'],
		$report['updated'],
		$report['nominees'],
		$report['sections']
	)
);

/* -------------------------------------------------------------------------
 * 8. Pages
 * ---------------------------------------------------------------------- */

$home_id = ms_seed_post( 'Home', 'page' );
$news_id = ms_seed_post( 'News', 'page' );

$pages = array(
	'About MySaline'        => 'Sample about page. The owner edits this like any other page.',
	'Advertise with us'     => 'Sample advertising information page.',
	'Contact Us'            => 'Sample contact page.',
	'Elected Officials'     => 'Sample reference page listing elected officials.',
	'Yard Sales'            => 'Sample yard sale listings page.',
	'Daily Puzzle'          => 'Sample puzzle page.',
	'Submit an Event'       => 'Sample event submission page.',
	'District & Ward Maps'  => 'Sample maps reference page.',
);
foreach ( $pages as $title => $body ) {
	ms_seed_post( $title, 'page', array( 'post_content' => $body ) );
}

/**
 * Look up a page ID by exact title.
 *
 * Avoids get_page_by_title(), which is deprecated as of WordPress 6.2 and would
 * emit notices with WP_DEBUG enabled in this environment.
 */
$page_by = function ( $title ) {
	$ids = get_posts(
		array(
			'post_type'        => 'page',
			'post_status'      => array( 'publish', 'draft' ),
			'title'            => $title,
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);
	return ! empty( $ids ) ? (int) $ids[0] : 0;
};

/*
 * Section hubs. Child pages become the hub's cards automatically, so this also
 * demonstrates how the owner builds one: create a parent, set the template,
 * then create children with an icon and an excerpt.
 */
$hubs = array(
	'Things To Do' => array(
		'icon'     => '🎪',
		'excerpt'  => 'Events, yard sales, dining and the daily puzzle — everything happening in Saline County.',
		'children' => array(
			array( 'Yard Sales', '🏷️', 'Weekend listings, with a form to add your own.' ),
			array( 'Daily Puzzle', '🧩', 'The crossword, refreshed every morning.' ),
			array( 'Submit an Event', '📝', 'Send us your event and we will add it to the calendar.' ),
		),
	),
	'Government'   => array(
		'icon'     => '🏛️',
		'excerpt'  => 'Elections, elected officials and district maps for Saline County.',
		'category' => isset( $cat_ids['Elections'] ) ? $cat_ids['Elections'] : 0,
		'children' => array(
			array( 'Elected Officials', '🏛️', 'Who represents you, with contact details.' ),
			array( 'District & Ward Maps', '🗺️', 'Districts, wards and voting zones.' ),
		),
	),
);

foreach ( $hubs as $hub_title => $hub ) {
	$hub_id = ms_seed_post(
		$hub_title,
		'page',
		array( 'post_excerpt' => $hub['excerpt'] )
	);
	if ( ! $hub_id ) {
		continue;
	}
	update_post_meta( $hub_id, '_wp_page_template', 'templates/hub.php' );
	update_post_meta( $hub_id, '_ms_hub_icon', $hub['icon'] );
	if ( ! empty( $hub['category'] ) ) {
		update_post_meta( $hub_id, '_ms_hub_category', (int) $hub['category'] );
	}

	foreach ( $hub['children'] as $order => $child ) {
		list( $c_title, $c_icon, $c_excerpt ) = $child;
		$child_id = ms_seed_post(
			$c_title,
			'page',
			array(
				'post_parent'  => $hub_id,
				'menu_order'   => $order,
				'post_excerpt' => $c_excerpt,
				'post_content' => 'Sample reference page.',
			)
		);
		if ( $child_id ) {
			update_post_meta( $child_id, '_ms_hub_icon', $c_icon );
		}
	}
}
WP_CLI::log( '  · 2 section hubs with child pages' );

// Give the long reference pages the full-width template.
foreach ( array( 'About MySaline', 'Advertise with us', 'Contact Us' ) as $fw ) {
	$fw_id = $page_by( $fw );
	if ( $fw_id ) {
		update_post_meta( $fw_id, '_wp_page_template', 'templates/full-width.php' );
	}
}

// Voting page uses the ballot template.
$vote_id = ms_seed_post(
	'Saline County Favorites — Vote',
	'page',
	array( 'post_content' => '' )
);
if ( $vote_id ) {
	update_post_meta( $vote_id, '_wp_page_template', 'templates/favorites.php' );
}

// Results page uses the shortcode.
ms_seed_post(
	'Saline County Favorites — Results',
	'page',
	array( 'post_content' => '[mysaline_favorites_results]' )
);

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );
update_option( 'page_for_posts', $news_id );
WP_CLI::log( '  · pages created; static homepage set' );

/* -------------------------------------------------------------------------
 * 9. Menus
 * ---------------------------------------------------------------------- */

/**
 * Build a menu, replacing any existing one of the same name.
 *
 * @param string $name     Menu name.
 * @param string $location Theme location.
 * @param array  $items    Items: { title, type, object_id|url, children[] }.
 */
function ms_seed_menu( $name, $location, $items ) {
	$existing = wp_get_nav_menu_object( $name );
	if ( $existing ) {
		wp_delete_nav_menu( $existing->term_id );
	}
	$menu_id = wp_create_nav_menu( $name );
	if ( is_wp_error( $menu_id ) ) {
		return;
	}

	$add = function ( $item, $parent = 0 ) use ( $menu_id, &$add ) {
		$args = array(
			'menu-item-title'     => $item['title'],
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent,
			// "mega" / "mega-3" make the theme lay the dropdown out in columns.
			'menu-item-classes'   => isset( $item['classes'] ) ? $item['classes'] : '',
		);
		if ( isset( $item['cat'] ) ) {
			$args['menu-item-type']      = 'taxonomy';
			$args['menu-item-object']    = 'category';
			$args['menu-item-object-id'] = $item['cat'];
		} elseif ( isset( $item['page'] ) ) {
			$args['menu-item-type']      = 'post_type';
			$args['menu-item-object']    = 'page';
			$args['menu-item-object-id'] = $item['page'];
		} else {
			$args['menu-item-type'] = 'custom';
			$args['menu-item-url']  = $item['url'];
		}
		$id = wp_update_nav_menu_item( $menu_id, 0, $args );
		if ( ! empty( $item['children'] ) && ! is_wp_error( $id ) ) {
			foreach ( $item['children'] as $child ) {
				$add( $child, $id );
			}
		}
	};

	foreach ( $items as $item ) {
		$add( $item );
	}

	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/*
 * The consolidated seven-section tree from docs/INFORMATION-ARCHITECTURE.md.
 * Grouped by reader intent rather than by content type, so ~30 destinations
 * become 7. The "mega"/"mega-3" classes flow big dropdowns into columns.
 *
 * Public Records and some Things To Do children point at placeholder pages on
 * the demo site; on production they map to the existing category archives.
 */
ms_seed_menu(
	'Primary',
	'primary',
	array(
		array(
			'title'    => 'News',
			'url'      => get_permalink( $news_id ),
			'classes'  => 'mega-3',
			'children' => array(
				array( 'title' => 'Saline County', 'cat' => $cat_ids['Saline County'] ),
				array( 'title' => 'Benton', 'cat' => $cat_ids['Benton'] ),
				array( 'title' => 'Bryant', 'cat' => $cat_ids['Bryant'] ),
				array( 'title' => 'Sports', 'cat' => $cat_ids['Sports'] ),
				array( 'title' => 'Schools', 'cat' => $cat_ids['Schools'] ),
				array( 'title' => 'Dining', 'cat' => $cat_ids['Dining'] ),
				array( 'title' => 'Community', 'cat' => $cat_ids['Community'] ),
				array( 'title' => 'Columnists', 'cat' => $cat_ids['Columnists'] ),
				array( 'title' => 'All Posts', 'url' => get_permalink( $news_id ) ),
			),
		),
		array(
			'title'    => 'Public Records',
			'url'      => get_category_link( $cat_ids['Public Records'] ),
			'classes'  => 'mega',
			'children' => array(
				array( 'title' => 'Mugshots Archive', 'cat' => $cat_ids['Public Records'] ),
				array( 'title' => 'Court Filings', 'cat' => $cat_ids['Public Records'] ),
				array( 'title' => '911 Calls', 'cat' => $cat_ids['Public Records'] ),
				array( 'title' => 'Marriage Licenses', 'cat' => $cat_ids['Public Records'] ),
				array( 'title' => 'Sex Offender Registry', 'cat' => $cat_ids['Public Records'] ),
				array( 'title' => 'Jobs Listings', 'cat' => $cat_ids['Public Records'] ),
			),
		),
		array( 'title' => 'Obituaries', 'url' => get_post_type_archive_link( 'ms_obituary' ) ),
		array(
			'title'    => 'Things To Do',
			'url'      => get_post_type_archive_link( 'ms_event' ),
			'classes'  => 'mega',
			'children' => array(
				array( 'title' => 'Events Calendar', 'url' => get_post_type_archive_link( 'ms_event' ) ),
				array( 'title' => 'Yard Sales', 'page' => $page_by( 'Yard Sales' ) ),
				array( 'title' => 'Dining Guide', 'cat' => $cat_ids['Dining'] ),
				array( 'title' => 'Daily Puzzle', 'page' => $page_by( 'Daily Puzzle' ) ),
				array( 'title' => 'Submit an Event', 'page' => $page_by( 'Submit an Event' ) ),
			),
		),
		array(
			'title'    => 'Business',
			'url'      => get_category_link( $cat_ids['Business News'] ),
			'children' => array(
				array( 'title' => 'Business News', 'cat' => $cat_ids['Business News'] ),
				array( 'title' => 'Business Directory', 'url' => get_post_type_archive_link( 'ms_business' ) ),
				array( 'title' => 'Advertise with us', 'page' => $page_by( 'Advertise with us' ) ),
			),
		),
		array(
			'title'    => 'Government',
			'url'      => get_category_link( $cat_ids['Elections'] ),
			'children' => array(
				/* Year is generated, so this label can never go stale. */
				array( 'title' => gmdate( 'Y' ) . ' Elections', 'cat' => $cat_ids['Elections'] ),
				array( 'title' => 'Elected Officials', 'page' => $page_by( 'Elected Officials' ) ),
				array( 'title' => 'District & Ward Maps', 'page' => $page_by( 'District & Ward Maps' ) ),
			),
		),
		array( 'title' => '⭐ Favorites', 'page' => $vote_id ),
	)
);

ms_seed_menu(
	'Top Bar',
	'secondary',
	array(
		array( 'title' => 'About', 'page' => $page_by( 'About MySaline' ) ),
		array( 'title' => 'Advertise', 'page' => $page_by( 'Advertise with us' ) ),
		array( 'title' => 'Contact', 'page' => $page_by( 'Contact Us' ) ),
	)
);

ms_seed_menu(
	'Footer',
	'footer',
	array(
		array( 'title' => 'Business News', 'cat' => $cat_ids['Business News'] ),
		array( 'title' => 'Dining', 'cat' => $cat_ids['Dining'] ),
		array( 'title' => 'Elections', 'cat' => $cat_ids['Elections'] ),
		array( 'title' => 'Community', 'cat' => $cat_ids['Community'] ),
	)
);
WP_CLI::log( '  · 3 menus assigned (primary with dropdowns, top bar, footer)' );

/* -------------------------------------------------------------------------
 * 10. Theme options
 * ---------------------------------------------------------------------- */

$mods = array(
	// Identity / branding.
	'mysaline_color_primary'        => '#0b2545',
	'mysaline_color_accent'         => '#c8102e',
	'mysaline_show_tagline'         => true,

	// Top bar.
	'mysaline_topbar_enable'        => true,
	'mysaline_topbar_show_date'     => true,
	'mysaline_topbar_text'          => '501-303-4010',

	// Breaking news.
	'mysaline_breaking_enable'      => true,
	'mysaline_breaking_source'      => 'manual',
	'mysaline_breaking_label'       => 'Breaking',
	'mysaline_breaking_text'        => 'Voting is open in Saline County Favorites through July 29',
	'mysaline_breaking_link'        => get_permalink( $vote_id ),

	// Homepage.
	'mysaline_hero_enable'          => true,
	'mysaline_hero_source'          => 'featured',
	'mysaline_hero_count'           => 5,
	'mysaline_home_show_latest'     => true,
	'mysaline_home_show_events'     => true,
	'mysaline_home_show_obits'      => true,
	'mysaline_home_show_businesses' => true,

	// Quick links.
	'mysaline_quicklinks_enable'    => true,
	'mysaline_quicklink_1_title'    => 'Advertise with us',
	'mysaline_quicklink_1_icon'     => '📣',
	'mysaline_quicklink_1_url'      => get_permalink( $page_by( 'Advertise with us' ) ),
	'mysaline_quicklink_2_title'    => 'Elected Officials',
	'mysaline_quicklink_2_icon'     => '🏛️',
	'mysaline_quicklink_2_url'      => get_permalink( $page_by( 'Elected Officials' ) ),
	'mysaline_quicklink_3_title'    => 'Yard Sales',
	'mysaline_quicklink_3_icon'     => '🏷️',
	'mysaline_quicklink_3_url'      => get_permalink( $page_by( 'Yard Sales' ) ),
	'mysaline_quicklink_4_title'    => 'Community Events',
	'mysaline_quicklink_4_icon'     => '📅',
	'mysaline_quicklink_4_url'      => get_post_type_archive_link( 'ms_event' ),

	// Homepage sections.
	'mysaline_section_1_enable'     => true,
	'mysaline_section_1_title'      => 'Business News',
	'mysaline_section_1_cat'        => $cat_ids['Business News'],
	'mysaline_section_1_layout'     => 'grid-3',
	'mysaline_section_1_count'      => 3,
	'mysaline_section_2_enable'     => true,
	'mysaline_section_2_title'      => 'Around the County',
	'mysaline_section_2_cat'        => $cat_ids['Saline County'],
	'mysaline_section_2_layout'     => 'mixed',
	'mysaline_section_2_count'      => 4,
	'mysaline_section_3_enable'     => true,
	'mysaline_section_3_title'      => 'Dining',
	'mysaline_section_3_cat'        => $cat_ids['Dining'],
	'mysaline_section_3_layout'     => 'grid-2',
	'mysaline_section_3_count'      => 2,

	// Newsletter (example provider endpoint — replace with the real one).
	'mysaline_news_enable'          => true,
	'mysaline_news_title'           => 'Get the MySaline newsletter',
	'mysaline_news_text'            => 'Saline County news in your inbox. Free, and no spam.',
	'mysaline_news_action'          => 'https://example.com/subscribe',
	'mysaline_news_email_field'     => 'EMAIL',
	'mysaline_news_button'          => 'Subscribe',

	// Social.
	'mysaline_social_facebook'      => 'https://facebook.com/MySaline',
	'mysaline_social_instagram'     => 'https://instagram.com/',
	'mysaline_social_twitter'       => 'https://x.com/',
	'mysaline_social_rss'           => home_url( '/feed/' ),

	// Ads.
	'mysaline_ads_enable'           => true,
	'mysaline_ads_label'            => 'Advertisement',
	'mysaline_ads_incontent'        => true,

	// Footer.
	'mysaline_footer_about'         => 'MySaline is the most-read news source in Saline County, Arkansas — local news, events, obituaries and community since 2007.',
	'mysaline_obit_submit_url'      => get_permalink( $page_by( 'Submit an Event' ) ), // Placeholder target on the demo site.
	'mysaline_biz_submit_url'       => get_permalink( $page_by( 'Advertise with us' ) ),
	'mysaline_contact_address'      => 'PO Box 165 · Benton, AR 72018',
	'mysaline_contact_phone'        => '501-303-4010',
	'mysaline_contact_email'        => 'hello@example.com',

	// Favorites — window open now so the ballot is testable immediately.
	'mysaline_fav_year'             => (int) gmdate( 'Y' ),
	'mysaline_fav_open'             => gmdate( 'Y-m-d H:i', strtotime( '-1 day' ) ),
	'mysaline_fav_close'            => gmdate( 'Y-m-d H:i', strtotime( '+30 days' ) ),
	'mysaline_fav_min_cats'         => 20,
	'mysaline_fav_min_sects'        => 4,
	'mysaline_fav_confirm_email'    => true,
	'mysaline_fav_show_results'     => false,
);

foreach ( $mods as $key => $value ) {
	set_theme_mod( $key, $value );
}
WP_CLI::log( '  · ' . count( $mods ) . ' theme options set' );

/* -------------------------------------------------------------------------
 * 11. Widgets — put an ad + recent posts in the main sidebar
 * ---------------------------------------------------------------------- */

update_option(
	'widget_mysaline_ad',
	array(
		2        => array( 'zone' => 'sidebar' ),
		'_multiwidget' => 1,
	)
);
update_option(
	'widget_mysaline_recent',
	array(
		2        => array( 'title' => 'Recent News', 'number' => 5, 'cat' => 0 ),
		'_multiwidget' => 1,
	)
);
update_option(
	'widget_mysaline_events',
	array(
		2        => array( 'title' => 'Upcoming Events', 'number' => 4 ),
		'_multiwidget' => 1,
	)
);
update_option(
	'widget_mysaline_social',
	array(
		2        => array( 'title' => 'Follow Us' ),
		'_multiwidget' => 1,
	)
);

$sidebars = get_option( 'sidebars_widgets', array() );
$sidebars['sidebar-main'] = array( 'mysaline_ad-2', 'mysaline_recent-2', 'mysaline_events-2' );
$sidebars['sidebar-home'] = array( 'mysaline_ad-2', 'mysaline_events-2' );
$sidebars['footer-4']     = array( 'mysaline_social-2' );
update_option( 'sidebars_widgets', $sidebars );
WP_CLI::log( '  · widgets placed in sidebars and footer' );

/* -------------------------------------------------------------------------
 * Done
 * ---------------------------------------------------------------------- */

flush_rewrite_rules();

WP_CLI::success( 'Demo content ready.' );
WP_CLI::log( '' );
WP_CLI::log( 'Site:      ' . home_url( '/' ) );
WP_CLI::log( 'Dashboard: ' . admin_url() . '  (admin / password)' );
WP_CLI::log( 'Ballot:    ' . get_permalink( $vote_id ) );
WP_CLI::log( 'Results:   ' . admin_url( 'edit.php?post_type=ms_fav_category&page=mysaline-fav-results' ) );
WP_CLI::log( 'Guide:     ' . admin_url( 'themes.php?page=mysaline-setup' ) );
WP_CLI::log( '' );
WP_CLI::log( 'Confirmation emails are captured to dev/mail.log (nothing is sent).' );
