<?php
/**
 * Photo galleries.
 *
 * MySaline has been publishing photographs since 2007, and every one of them is
 * already sitting in the WordPress media library. Nothing needs importing — the
 * pictures only ever needed somewhere to be seen. So a gallery is a thin wrapper
 * around images the site already owns: attachments on the gallery post, plus
 * anything referenced by gallery blocks in its content.
 *
 * Images are never fetched from anywhere else. Photographs on a news site carry
 * a licence, and a photo whose licence nobody can point to is a liability.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every image belonging to a gallery, in menu order.
 *
 * Attachments and block-referenced images are merged, because an editor may
 * reasonably do either — upload straight onto the post, or pick from the
 * library with a Gallery block.
 *
 * @param int|WP_Post|null $post  Gallery.
 * @param int              $limit Maximum images (0 for all).
 * @return int[] Attachment IDs.
 */
function mysaline_gallery_image_ids( $post = null, $limit = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}

	$ids = get_posts(
		array(
			'post_parent'    => $post->ID,
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	// Images picked from the library sit in block attributes, not as children.
	if ( has_blocks( $post->post_content ) ) {
		foreach ( parse_blocks( $post->post_content ) as $block ) {
			if ( ! empty( $block['attrs']['ids'] ) && is_array( $block['attrs']['ids'] ) ) {
				$ids = array_merge( $ids, array_map( 'absint', $block['attrs']['ids'] ) );
			} elseif ( ! empty( $block['attrs']['id'] ) && 'core/image' === $block['blockName'] ) {
				$ids[] = absint( $block['attrs']['id'] );
			}
		}
	}

	$ids = array_values( array_unique( array_filter( $ids ) ) );

	// The featured image is the cover, so it is not repeated inside the grid.
	$cover = (int) get_post_thumbnail_id( $post );
	if ( $cover ) {
		$ids = array_values( array_diff( $ids, array( $cover ) ) );
	}

	return $limit > 0 ? array_slice( $ids, 0, (int) $limit ) : $ids;
}

/**
 * How many photographs a gallery holds, cover included.
 *
 * @param int|WP_Post|null $post Gallery.
 * @return int
 */
function mysaline_gallery_count( $post = null ) {
	$post  = get_post( $post );
	$count = count( mysaline_gallery_image_ids( $post ) );

	return $count + ( get_post_thumbnail_id( $post ) ? 1 : 0 );
}

/**
 * Recent galleries.
 *
 * @param int $number How many.
 * @return WP_Post[]
 */
function mysaline_get_galleries( $number = 6 ) {
	return get_posts(
		array(
			'post_type'      => 'ms_gallery',
			'posts_per_page' => (int) $number,
		)
	);
}

/**
 * Photographs already published across the site, newest first.
 *
 * This is what makes a Photos section useful on day one: it draws on every
 * featured image the newsroom has ever set, so the archive is populated the
 * moment the theme is switched on rather than waiting for new galleries.
 *
 * @param int $number How many.
 * @return array[] Each: {id, url, alt, permalink, title, date}.
 */
function mysaline_recent_photos( $number = 12 ) {
	$posts = get_posts(
		array(
			'post_type'      => array( 'post', 'ms_event', 'ms_gallery' ),
			'posts_per_page' => (int) $number,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	$out = array();
	foreach ( $posts as $p ) {
		$id  = get_post_thumbnail_id( $p );
		$src = $id ? wp_get_attachment_image_src( $id, 'medium_large' ) : false;
		if ( ! $src ) {
			continue;
		}
		$out[] = array(
			'id'        => $id,
			'url'       => $src[0],
			'alt'       => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			'permalink' => get_permalink( $p ),
			'title'     => get_the_title( $p ),
			'date'      => get_the_date( '', $p ),
		);
	}

	return $out;
}
