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
function ms_seed_image( $post_id, $seed, $w = 1200, $h = 675, $scene = 'community' ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return 0;
	}

	// Skip if this post already has a thumbnail.
	if ( has_post_thumbnail( $post_id ) ) {
		return (int) get_post_thumbnail_id( $post_id );
	}

	$hash = md5( $seed );
	$im   = imagecreatetruecolor( $w, $h );
	imagealphablending( $im, true );

	// Palette, straight from the logo.
	$navy  = array( 15, 43, 78 );
	$deep  = array( 9, 28, 52 );
	$mid   = array( 46, 115, 150 );
	$blue  = array( 11, 92, 232 );
	$sky   = array( 159, 211, 240 );
	$soft  = array( 207, 231, 247 );
	$cream = array( 251, 247, 239 );
	$honey = array( 227, 169, 60 );
	$brick = array( 178, 69, 47 );

	$alloc = static function ( $c, $a = 0 ) use ( $im ) {
		return $a > 0
			? imagecolorallocatealpha( $im, $c[0], $c[1], $c[2], min( 127, $a ) )
			: imagecolorallocate( $im, $c[0], $c[1], $c[2] );
	};

	// Sky: a vertical wash from the deep navy down to the crystal blue.
	$top    = 'night' === $scene ? $deep : $navy;
	$bottom = 'night' === $scene ? $navy : $sky;
	for ( $y = 0; $y < $h; $y++ ) {
		$t = $y / max( 1, $h - 1 );
		imageline(
			$im, 0, $y, $w, $y,
			$alloc(
				array(
					(int) ( $top[0] + ( $bottom[0] - $top[0] ) * $t ),
					(int) ( $top[1] + ( $bottom[1] - $top[1] ) * $t ),
					(int) ( $top[2] + ( $bottom[2] - $top[2] ) * $t ),
				)
			)
		);
	}

	// A low sun, placed from the hash so no two images sit identically.
	$sun_x = (int) ( $w * ( 0.18 + ( hexdec( substr( $hash, 0, 2 ) ) / 255 ) * 0.64 ) );
	$sun_r = (int) ( $w * 0.13 );
	imagefilledellipse( $im, $sun_x, (int) ( $h * 0.42 ), $sun_r * 2, $sun_r * 2, $alloc( $cream, 96 ) );
	imagefilledellipse( $im, $sun_x, (int) ( $h * 0.42 ), $sun_r, $sun_r, $alloc( $cream, 78 ) );

	$horizon = (int) ( $h * 0.66 );

	// Rolling hills behind whatever the scene puts in front of them.
	$hill = static function ( $cx, $rw, $rh, $col ) use ( $im, $horizon ) {
		imagefilledellipse( $im, $cx, $horizon + (int) ( $rh * 0.35 ), $rw, $rh, $col );
	};
	$hill( (int) ( $w * 0.18 ), (int) ( $w * 0.75 ), (int) ( $h * 0.42 ), $alloc( $mid, 40 ) );
	$hill( (int) ( $w * 0.82 ), (int) ( $w * 0.8 ), (int) ( $h * 0.36 ), $alloc( $mid, 55 ) );

	// Ground.
	imagefilledrectangle( $im, 0, $horizon, $w, $h, $alloc( $deep ) );

	$ink   = $alloc( $navy );
	$light = $alloc( $sky );
	$warm  = $alloc( $honey );
	$hot   = $alloc( $brick );
	$pale  = $alloc( $soft );

	/*
	 * A silhouette per section, so a story about the county courthouse does not
	 * carry the same picture as one about the Salt Bowl. These are deliberately
	 * flat and graphic — stand-in art that looks chosen rather than a stock
	 * photo that is obviously wrong.
	 */
	switch ( $scene ) {
		case 'civic': // Courthouse: portico, columns, cupola.
			$bw = (int) ( $w * 0.42 );
			$bx = (int) ( ( $w - $bw ) / 2 );
			$by = (int) ( $h * 0.34 );
			imagefilledrectangle( $im, $bx, $by, $bx + $bw, $horizon, $ink );
			// Pediment.
			imagefilledpolygon( $im, array( $bx - 14, $by, $bx + $bw + 14, $by, (int) ( $w / 2 ), $by - (int) ( $h * 0.11 ) ), $ink );
			// Cupola.
			imagefilledrectangle( $im, (int) ( $w / 2 ) - 14, $by - (int) ( $h * 0.2 ), (int) ( $w / 2 ) + 14, $by - (int) ( $h * 0.1 ), $ink );
			imagefilledellipse( $im, (int) ( $w / 2 ), $by - (int) ( $h * 0.2 ), 46, 46, $ink );
			for ( $i = 0; $i < 6; $i++ ) {
				$cx = $bx + (int) ( $bw * ( 0.1 + $i * 0.16 ) );
				imagefilledrectangle( $im, $cx, $by + 18, $cx + 12, $horizon - 10, $alloc( $sky, 84 ) );
			}
			break;

		case 'sports': // Floodlit field with goal posts.
			imagefilledrectangle( $im, 0, $horizon, $w, $h, $alloc( array( 20, 66, 48 ) ) );
			for ( $i = 1; $i < 6; $i++ ) {
				$lx = (int) ( $w * $i / 6 );
				imagefilledrectangle( $im, $lx, $horizon, $lx + 3, $h, $alloc( $cream, 108 ) );
			}
			foreach ( array( 0.2, 0.8 ) as $px ) {
				$x = (int) ( $w * $px );
				imagefilledrectangle( $im, $x - 4, (int) ( $h * 0.3 ), $x + 4, $horizon, $ink );
				imagefilledellipse( $im, $x, (int) ( $h * 0.28 ), 78, 34, $alloc( $honey, 40 ) );
				imagefilledrectangle( $im, $x - 26, (int) ( $h * 0.27 ), $x + 26, (int) ( $h * 0.3 ), $ink );
			}
			$gx = (int) ( $w * 0.5 );
			imagefilledrectangle( $im, $gx - 3, (int) ( $h * 0.46 ), $gx + 3, $horizon, $warm );
			imagefilledrectangle( $im, $gx - 70, (int) ( $h * 0.46 ), $gx + 70, (int) ( $h * 0.48 ), $warm );
			imagefilledrectangle( $im, $gx - 70, (int) ( $h * 0.34 ), $gx - 64, (int) ( $h * 0.47 ), $warm );
			imagefilledrectangle( $im, $gx + 64, (int) ( $h * 0.34 ), $gx + 70, (int) ( $h * 0.47 ), $warm );
			break;

		case 'school': // Schoolhouse with a bell tower and a flag.
			$bw = (int) ( $w * 0.34 );
			$bx = (int) ( $w * 0.34 );
			$by = (int) ( $h * 0.42 );
			imagefilledrectangle( $im, $bx, $by, $bx + $bw, $horizon, $ink );
			imagefilledpolygon( $im, array( $bx - 10, $by, $bx + $bw + 10, $by, $bx + (int) ( $bw / 2 ), $by - (int) ( $h * 0.12 ) ), $ink );
			imagefilledrectangle( $im, $bx + (int) ( $bw / 2 ) - 16, $by - (int) ( $h * 0.24 ), $bx + (int) ( $bw / 2 ) + 16, $by - (int) ( $h * 0.1 ), $ink );
			for ( $r = 0; $r < 2; $r++ ) {
				for ( $c = 0; $c < 4; $c++ ) {
					imagefilledrectangle(
						$im,
						$bx + 26 + $c * (int) ( $bw / 4.6 ), $by + 34 + $r * 62,
						$bx + 60 + $c * (int) ( $bw / 4.6 ), $by + 78 + $r * 62,
						$alloc( $honey, 60 )
					);
				}
			}
			$fx = (int) ( $w * 0.78 );
			imagefilledrectangle( $im, $fx, (int) ( $h * 0.3 ), $fx + 4, $horizon, $ink );
			imagefilledrectangle( $im, $fx + 4, (int) ( $h * 0.3 ), $fx + 70, (int) ( $h * 0.37 ), $hot );
			break;

		case 'business': // A small-town main street of storefronts.
			$x = 0;
			$i = 0;
			while ( $x < $w ) {
				$bwid = (int) ( $w * ( 0.1 + ( hexdec( substr( $hash, ( $i % 12 ) * 2, 2 ) ) / 255 ) * 0.09 ) );
				$bhgt = (int) ( $h * ( 0.14 + ( hexdec( substr( $hash, ( ( $i + 3 ) % 12 ) * 2, 2 ) ) / 255 ) * 0.2 ) );
				$by   = $horizon - $bhgt;
				imagefilledrectangle( $im, $x, $by, $x + $bwid - 6, $horizon, $ink );
				// Awning.
				imagefilledrectangle( $im, $x, $by + (int) ( $bhgt * 0.55 ), $x + $bwid - 6, $by + (int) ( $bhgt * 0.68 ), 0 === $i % 2 ? $hot : $warm );
				for ( $wdw = 0; $wdw < 3; $wdw++ ) {
					imagefilledrectangle(
						$im,
						$x + 12 + $wdw * 26, $by + 14,
						$x + 30 + $wdw * 26, $by + (int) ( $bhgt * 0.42 ),
						$alloc( $sky, 92 )
					);
				}
				$x += $bwid;
				$i++;
			}
			break;

		case 'records': // Filing grid, for the public-records desk.
			imagefilledrectangle( $im, 0, $horizon, $w, $h, $alloc( $navy ) );
			$cols = 5;
			$rows = 3;
			$pw   = (int) ( $w * 0.13 );
			$ph   = (int) ( $h * 0.16 );
			for ( $r = 0; $r < $rows; $r++ ) {
				for ( $c = 0; $c < $cols; $c++ ) {
					$px = (int) ( $w * 0.09 ) + $c * (int) ( $w * 0.17 );
					$py = (int) ( $h * 0.16 ) + $r * (int) ( $h * 0.22 );
					imagefilledrectangle( $im, $px, $py, $px + $pw, $py + $ph, $alloc( $cream, 88 ) );
					for ( $l = 0; $l < 3; $l++ ) {
						imagefilledrectangle(
							$im, $px + 10, $py + 14 + $l * 16,
							$px + $pw - ( 10 + $l * 14 ), $py + 20 + $l * 16,
							$alloc( $navy, 40 )
						);
					}
				}
			}
			break;

		case 'road': // Highway running to the horizon.
			imagefilledpolygon(
				$im,
				array(
					(int) ( $w * 0.1 ), $h,
					(int) ( $w * 0.9 ), $h,
					(int) ( $w * 0.56 ), $horizon,
					(int) ( $w * 0.44 ), $horizon,
				),
				$alloc( array( 34, 40, 52 ) )
			);
			for ( $i = 0; $i < 6; $i++ ) {
				$t  = $i / 6;
				$yy = (int) ( $horizon + ( $h - $horizon ) * ( $t * $t ) );
				$hh = (int) ( 6 + 26 * $t );
				$ww = (int) ( 3 + 12 * $t );
				imagefilledrectangle( $im, (int) ( $w / 2 ) - $ww, $yy, (int) ( $w / 2 ) + $ww, $yy + $hh, $warm );
			}
			break;

		default: // Community: a stand of trees on the ridge.
			for ( $i = 0; $i < 9; $i++ ) {
				$tx = (int) ( $w * ( 0.05 + $i * 0.11 ) ) + (int) ( hexdec( substr( $hash, $i * 2, 2 ) ) / 8 );
				$th = (int) ( $h * ( 0.16 + ( hexdec( substr( $hash, ( $i + 2 ) * 2, 2 ) ) / 255 ) * 0.16 ) );
				imagefilledrectangle( $im, $tx - 5, $horizon - (int) ( $th * 0.25 ), $tx + 5, $horizon + 8, $ink );
				imagefilledellipse( $im, $tx, $horizon - $th, (int) ( $th * 1.1 ), (int) ( $th * 1.2 ), $ink );
			}
			break;
	}

	// A soft vignette at the foot, so white headline text always has something
	// darker to sit on in the hero.
	for ( $y = (int) ( $h * 0.72 ); $y < $h; $y++ ) {
		$t = ( $y - $h * 0.72 ) / max( 1, $h - $h * 0.72 );
		imageline( $im, 0, $y, $w, $y, $alloc( $deep, (int) ( 127 - 62 * $t ) ) );
	}
	unset( $light, $pale, $blue, $soft );

	$upload = wp_upload_dir();
	$name   = 'mysaline-demo-' . substr( $hash, 0, 10 ) . '.jpg';
	$path   = trailingslashit( $upload['path'] ) . $name;
	imagejpeg( $im, $path, 84 );
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
 * Map a category to one of the scene compositions.
 *
 * @param string $cat Category name.
 * @return string
 */
function ms_seed_scene_for( $cat ) {
	$map = array(
		'Saline County'  => 'civic',
		'Benton'         => 'civic',
		'Bryant'         => 'road',
		'Elections'      => 'civic',
		'Public Records' => 'records',
		'Sports'         => 'sports',
		'Schools'        => 'school',
		'Business News'  => 'business',
		'Dining'         => 'business',
		'Community'      => 'community',
		'Columnists'     => 'community',
	);

	return isset( $map[ $cat ] ) ? $map[ $cat ] : 'community';
}

/**
 * Generate a portrait-style placeholder and attach it as a writer's profile
 * photo, exercising the theme's own author-photo field.
 *
 * @param int    $user_id User to attach to.
 * @param string $initials Two letters to draw.
 * @param string $seed     Seed for deterministic colour choice.
 * @return int Attachment ID (0 on failure).
 */
function ms_seed_portrait( $user_id, $initials, $seed ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return 0;
	}
	if ( get_user_meta( $user_id, 'mysaline_profile_photo', true ) ) {
		return (int) get_user_meta( $user_id, 'mysaline_profile_photo', true );
	}

	$tones = array(
		array( 22, 69, 95 ),
		array( 46, 115, 150 ),
		array( 31, 88, 116 ),
		array( 13, 46, 64 ),
	);
	$tone = $tones[ abs( crc32( $seed ) ) % count( $tones ) ];

	$size = 400;
	$im   = imagecreatetruecolor( $size, $size );

	for ( $y = 0; $y < $size; $y++ ) {
		$t = $y / max( 1, $size - 1 );
		$c = imagecolorallocate(
			$im,
			(int) ( $tone[0] + ( 143 - $tone[0] ) * $t * .55 ),
			(int) ( $tone[1] + ( 205 - $tone[1] ) * $t * .55 ),
			(int) ( $tone[2] + ( 231 - $tone[2] ) * $t * .55 )
		);
		imageline( $im, 0, $y, $size, $y, $c );
	}

	// Initials, centred, in the cream from the palette. GD's built-in bitmap
	// fonts are 15px tall, so scaling one up to portrait size looks like a
	// screenshot of a screenshot; a TrueType face is used when one is present.
	$cream = imagecolorallocate( $im, 251, 247, 239 );
	$ttf   = '';
	foreach ( array(
		'/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf',
		'/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
		'/usr/share/fonts/truetype/freefont/FreeSerifBold.ttf',
	) as $candidate ) {
		if ( is_readable( $candidate ) ) {
			$ttf = $candidate;
			break;
		}
	}

	if ( $ttf && function_exists( 'imagettftext' ) ) {
		$pt  = $size * 0.3;
		$box = imagettfbbox( $pt, 0, $ttf, $initials );
		$tw  = abs( $box[4] - $box[0] );
		$th  = abs( $box[5] - $box[1] );
		imagettftext(
			$im,
			$pt,
			0,
			(int) ( ( $size - $tw ) / 2 ),
			(int) ( ( $size + $th ) / 2 ),
			$cream,
			$ttf,
			$initials
		);
	} else {
		$font = 5;
		$tw   = imagefontwidth( $font ) * strlen( $initials );
		$th   = imagefontheight( $font );
		$tmp  = imagecreatetruecolor( $tw, $th );
		imagesavealpha( $tmp, true );
		imagefill( $tmp, 0, 0, imagecolorallocatealpha( $tmp, 0, 0, 0, 127 ) );
		imagestring( $tmp, $font, 0, 0, $initials, imagecolorallocate( $tmp, 251, 247, 239 ) );
		$scale = (int) ( $size * 0.34 );
		imagecopyresampled(
			$im, $tmp,
			(int) ( ( $size - $scale * ( $tw / $th ) ) / 2 ), (int) ( ( $size - $scale ) / 2 ),
			0, 0, (int) ( $scale * ( $tw / $th ) ), $scale, $tw, $th
		);
		imagedestroy( $tmp );
	}

	$upload = wp_upload_dir();
	$name   = 'mysaline-portrait-' . substr( md5( $seed ), 0, 8 ) . '.jpg';
	$path   = trailingslashit( $upload['path'] ) . $name;
	imagejpeg( $im, $path, 88 );
	imagedestroy( $im );

	$attach_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => 'Profile photo',
			'post_status'    => 'inherit',
		),
		$path
	);
	if ( ! $attach_id || is_wp_error( $attach_id ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $path ) );
	update_user_meta( $user_id, 'mysaline_profile_photo', (int) $attach_id );

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

/*
 * A newsroom, not one account called "admin".
 *
 * The byline shows on every card, every single story and the author archive
 * masthead, so a single unnamed author makes the whole demo look unfinished —
 * and author.php's bio masthead has nothing to render. Each writer covers the
 * beats listed against them; anything uncovered falls to the first writer.
 */
$staff = array(
	'dhollis'  => array(
		'name'  => 'Dana Hollis',
		'first' => 'Dana',
		'last'  => 'Hollis',
		'role'  => 'editor',
		'bio'   => 'Dana Hollis is MySaline’s managing editor. She has covered city
			hall, county government and elections in Saline County since 2011, and
			edits the newsroom’s public records reporting.',
		'beats' => array( 'Benton', 'Bryant', 'Saline County', 'Elections', 'Public Records' ),
	),
	'mcallum'  => array(
		'name'  => 'Marcus Callum',
		'first' => 'Marcus',
		'last'  => 'Callum',
		'role'  => 'author',
		'bio'   => 'Marcus Callum writes about high school sports for MySaline. He
			has covered the Salt Bowl every year since 2014 and grew up two blocks
			from the field he now writes about.',
		'beats' => array( 'Sports' ),
	),
	'rgarrett' => array(
		'name'  => 'Rachel Garrett',
		'first' => 'Rachel',
		'last'  => 'Garrett',
		'role'  => 'author',
		'bio'   => 'Rachel Garrett covers business, dining and schools. She reports
			on new storefronts, restaurant openings and the two school districts
			that serve Saline County.',
		'beats' => array( 'Business News', 'Dining', 'Schools', 'Community' ),
	),
	'twhitley' => array(
		'name'  => 'Tom Whitley',
		'first' => 'Tom',
		'last'  => 'Whitley',
		'role'  => 'author',
		'bio'   => 'Tom Whitley writes the How the Ball Bounces column. He has been
			arguing about youth sports in print, and at the ballpark, for twenty
			years.',
		'beats' => array( 'Columnists' ),
	),
);

$beat_author = array();
$staff_ids   = array();

foreach ( $staff as $login => $person ) {
	$user_id = username_exists( $login );
	if ( ! $user_id ) {
		$user_id = wp_insert_user(
			array(
				'user_login' => $login,
				'user_pass'  => wp_generate_password( 24 ),
				'user_email' => $login . '@mysaline.example',
				'role'       => $person['role'],
			)
		);
	}
	if ( is_wp_error( $user_id ) ) {
		continue;
	}
	$user_id = (int) $user_id;

	wp_update_user(
		array(
			'ID'           => $user_id,
			'display_name' => $person['name'],
			'first_name'   => $person['first'],
			'last_name'    => $person['last'],
			// Collapse the seeded whitespace so bios read as one paragraph.
			'description'  => preg_replace( '/\s+/', ' ', trim( $person['bio'] ) ),
			'user_url'     => '',
		)
	);

	ms_seed_portrait( $user_id, strtoupper( substr( $person['first'], 0, 1 ) . substr( $person['last'], 0, 1 ) ), $login );

	$staff_ids[] = $user_id;
	foreach ( $person['beats'] as $beat ) {
		$beat_author[ $beat ] = $user_id;
	}
}

// WP-CLI runs with no current user, so post_author would otherwise default to 0
// and the byline would render empty.
$seed_author = ! empty( $staff_ids ) ? $staff_ids[0] : 1;

// The stock admin account still owns the install; stop it looking like a writer.
$seed_admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
if ( ! empty( $seed_admins ) ) {
	wp_update_user(
		array(
			'ID'           => (int) $seed_admins[0],
			'display_name' => 'MySaline Staff',
			'user_url'     => '',
		)
	);
}

WP_CLI::log( '  · ' . count( $staff_ids ) . ' newsroom accounts with bios' );

$made = 0;
foreach ( $posts as $i => $row ) {
	list( $title, $cat, $featured ) = $row;

	$id = ms_seed_post(
		$title,
		'post',
		array(
			'post_author'  => isset( $beat_author[ $cat ] ) ? $beat_author[ $cat ] : $seed_author,
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

	ms_seed_image( $id, 'post-' . $title, 1200, 675, ms_seed_scene_for( $cat ) );
	$made++;
}
WP_CLI::log( '  · ' . $made . ' posts (5 flagged as Featured)' );

// Remove WordPress's stock sample post so it stops turning up in Latest News.
$hello = get_posts(
	array(
		'post_type'      => 'post',
		'name'           => 'hello-world',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'post_status'    => array( 'publish', 'draft' ),
	)
);
if ( ! empty( $hello ) ) {
	wp_trash_post( (int) $hello[0] );
	WP_CLI::log( '  · trashed the stock "Hello world!" post' );
}

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

/*
 * Each zone gets a designed house ad rather than a dashed grey box. The demo
 * is shown to people deciding whether the site looks finished, and a page
 * topped by an "ad goes here" placeholder reads as unfinished no matter how
 * good everything under it is. These are invented sponsors in the theme
 * palette, sized to the shape of their zone.
 */
$zones = array(
	'header'        => array( 'Header leaderboard', 'wide',   'Saline Family Dental', 'Same-day appointments in Benton', 'Book a visit', '#0f2b4e' ),
	'homepage_top'  => array( 'Homepage — below hero', 'wide', 'Hurley Heat &amp; Air', 'Free system check through August', 'Schedule now', '#2e7396' ),
	'homepage_mid'  => array( 'Homepage — between sections', 'wide', 'First Community Bank', 'Local decisions, made locally', 'Open an account', '#0f2b4e' ),
	'sidebar'       => array( 'Sidebar', 'box',           'Bryant Auto Care', 'Oil change &amp; tire rotation, $59', 'Get directions', '#2e7396' ),
	'in_content'    => array( 'In-content', 'wide',       'Cornerstone Realty', 'Thinking of selling this fall?', 'Free home valuation', '#1f5874' ),
	'below_content' => array( 'Below article', 'wide',    'Saline County Fair', 'August 22–26 · Free parking', 'See the schedule', '#b2452f' ),
	'in_feed'       => array( 'In-feed', 'wide',          'The Sweet Spot Bakery', 'Fresh kolaches every morning', 'View the menu', '#8c3524' ),
	'directory'     => array( 'Directory listings', 'box','Benton Lawn &amp; Landscape', 'Weekly mowing from $40', 'Request a quote', '#1c7a52' ),
	'newsletter'    => array( 'Newsletter sponsor', 'wide','Riverside Insurance', 'Auto, home and farm coverage', 'Compare rates', '#0f2b4e' ),
	'sticky_mobile' => array( 'Mobile sticky anchor', 'thin', 'Casa Verde', 'Lunch specials until 3pm daily', 'Order online', '#b2452f' ),
	'footer'        => array( 'Footer', 'wide',           'Saline Memorial Chapel', 'Serving families since 1946', 'Contact us', '#1f5874' ),
);

foreach ( $zones as $zone => $spec ) {
	list( $label, $shape, $sponsor, $line, $cta, $color ) = $spec;

	$id = ms_seed_post( 'Demo ad — ' . $label, 'ms_ad' );
	if ( ! $id ) {
		continue;
	}
	update_post_meta( $id, '_ms_ad_zone', $zone );
	update_post_meta( $id, '_ms_ad_sponsor', wp_specialchars_decode( $sponsor ) );
	update_post_meta( $id, '_ms_ad_link', 'https://example.com' );

	$pad  = 'thin' === $shape ? '.6rem .9rem' : ( 'box' === $shape ? '1.5rem 1.25rem' : '1.15rem 1.35rem' );
	$name = 'thin' === $shape ? '1rem' : ( 'box' === $shape ? '1.35rem' : '1.25rem' );
	$dir  = 'box' === $shape ? 'column' : 'row';
	$alig = 'box' === $shape ? 'center' : 'center';
	$just = 'box' === $shape ? 'center' : 'space-between';
	$txt  = 'box' === $shape ? 'center' : 'left';

	update_post_meta(
		$id,
		'_ms_ad_code',
		'<div style="background:' . esc_attr( $color ) . ';color:#fff;border-radius:10px;padding:' . $pad . ';'
			. 'display:flex;flex-direction:' . $dir . ';align-items:' . $alig . ';justify-content:' . $just . ';'
			. 'gap:.85rem;text-align:' . $txt . ';font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif">'
			. '<div><div style="font:700 ' . $name . '/1.2 Georgia,serif;letter-spacing:.01em">' . $sponsor . '</div>'
			. '<div style="font-size:.86rem;opacity:.85;margin-top:.2rem">' . $line . '</div></div>'
			. '<span style="flex:none;background:#8fcde7;color:#0d2e40;font:700 .78rem/1 -apple-system,sans-serif;'
			. 'padding:.6rem .9rem;border-radius:999px;white-space:nowrap">' . $cta . '</span>'
			. '</div>'
	);
}
WP_CLI::log( '  · ' . count( $zones ) . ' house ads (one per zone)' );

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

// The tagline is the fallback meta description for the homepage and every
// archive, so a fresh install with WordPress's placeholder would ship a
// description reading "Just another WordPress site".
update_option( 'blogname', 'MySaline' );
update_option( 'blogdescription', 'Local News Worth Its Salt.' );

/*
 * Install the wordmark as the site logo.
 *
 * header.php only renders the text brand when there is no custom logo, so the
 * tagline is not repeated under a logo that already contains it.
 */
$logo_src = MYSALINE_DIR . 'assets/images/logo-mysaline.png';
if ( is_readable( $logo_src ) && ! get_theme_mod( 'custom_logo' ) ) {
	$upload    = wp_upload_dir();
	$logo_path = trailingslashit( $upload['path'] ) . 'mysaline-logo.png';
	copy( $logo_src, $logo_path );

	$logo_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => 'MySaline logo',
			'post_status'    => 'inherit',
		),
		$logo_path
	);

	if ( $logo_id && ! is_wp_error( $logo_id ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $logo_id, wp_generate_attachment_metadata( $logo_id, $logo_path ) );
		update_post_meta( $logo_id, '_wp_attachment_image_alt', 'MySaline — Local News Worth Its Salt.' );
		set_theme_mod( 'custom_logo', (int) $logo_id );
		WP_CLI::log( '  · site logo installed' );
	}
}
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
	'mysaline_color_primary'        => '#0f2b4e',
	'mysaline_color_accent'         => '#b2452f',
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
update_option(
	'widget_mysaline_weather',
	array(
		2        => array( 'title' => 'Saline County Weather' ),
		'_multiwidget' => 1,
	)
);

// Prime the forecast so the first page view has something to show. Normally a
// cron tick does this; the seeder calls it directly because a freshly seeded
// site is looked at immediately.
if ( function_exists( 'mysaline_weather_refresh' ) ) {
	$wx = mysaline_weather_refresh();
	WP_CLI::log( $wx && ! empty( $wx['temp'] )
		? '  · weather primed: ' . $wx['temp'] . '°' . $wx['unit'] . ' ' . $wx['summary'] . ' in ' . $wx['place']
		: '  · weather unavailable right now (the widget stays hidden until it is)' );
}

$sidebars = get_option( 'sidebars_widgets', array() );
$sidebars['sidebar-main'] = array( 'mysaline_weather-2', 'mysaline_ad-2', 'mysaline_recent-2', 'mysaline_events-2' );
$sidebars['sidebar-home'] = array( 'mysaline_weather-2', 'mysaline_ad-2', 'mysaline_events-2' );
// Footer columns already fall back to Sections / Community / More / Follow
// Us, so seeding a social widget here just duplicated the last one.
unset( $sidebars['footer-4'] );
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
