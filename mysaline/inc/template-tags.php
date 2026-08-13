<?php
/**
 * Reusable template tags / presentation helpers.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output the post's primary category as a badge.
 *
 * @param int|WP_Post|null $post Optional post.
 */
function mysaline_category_badge( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$cats = get_the_category( $post->ID );
	if ( empty( $cats ) ) {
		return;
	}
	$cat = $cats[0];
	printf(
		'<span class="ms-cat-badge"><a href="%1$s">%2$s</a></span>',
		esc_url( get_category_link( $cat->term_id ) ),
		esc_html( $cat->name )
	);
}

/**
 * Print standard post meta (author, date, comments).
 *
 * @param array $args Which pieces to show.
 */
function mysaline_post_meta( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'author'   => true,
			'date'     => true,
			'comments' => true,
			'avatar'   => false,
		)
	);

	echo '<div class="ms-card__meta">';

	// A post whose author account was deleted reports ID 0 and an empty name.
	// Rendering that produced an empty link pointing at a broken /author/ URL,
	// so the byline is skipped entirely unless there is a real author.
	$mysaline_author_id   = (int) get_the_author_meta( 'ID' );
	$mysaline_author_name = trim( (string) get_the_author() );

	if ( $args['author'] && $mysaline_author_id && '' !== $mysaline_author_name ) {
		echo '<span class="ms-meta-author">';
		if ( $args['avatar'] ) {
			echo get_avatar( $mysaline_author_id, 40, '', '', array( 'class' => 'ms-author-avatar' ) );
			echo ' ';
		}
		echo '<a href="' . esc_url( get_author_posts_url( $mysaline_author_id ) ) . '">' . esc_html( $mysaline_author_name ) . '</a>';
		echo '</span>';
	}

	if ( $args['date'] ) {
		printf(
			'<time class="ms-meta-date" datetime="%1$s">%2$s</time>',
			esc_attr( get_the_date( DATE_W3C ) ),
			esc_html( get_the_date() )
		);
	}

	if ( $args['comments'] && comments_open() && ! post_password_required() ) {
		echo '<span class="ms-meta-comments">';
		comments_popup_link(
			esc_html__( 'Leave a comment', 'mysaline' ),
			esc_html__( '1 Comment', 'mysaline' ),
			esc_html__( '% Comments', 'mysaline' )
		);
		echo '</span>';
	}

	echo '</div>';
}

/**
 * Escaped featured image with a graceful placeholder when none is set.
 *
 * @param string           $size Image size.
 * @param int|WP_Post|null $post Optional post.
 * @param bool             $link Wrap in a permalink.
 */
function mysaline_thumbnail( $size = 'mysaline-card', $post = null, $link = true ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}

	if ( has_post_thumbnail( $post ) ) {
		$img = get_the_post_thumbnail(
			$post,
			$size,
			array(
				'loading' => 'lazy',
				'alt'     => the_title_attribute( array( 'echo' => false, 'post' => $post ) ),
			)
		);
	} else {
		$img = mysaline_placeholder_image();
	}

	if ( $link ) {
		printf( '<a href="%s" aria-hidden="true" tabindex="-1">%s</a>', esc_url( get_permalink( $post ) ), $img ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $img is escaped markup from core.
	} else {
		echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Inline SVG placeholder used when a post has no featured image.
 *
 * @return string SVG markup.
 */
function mysaline_placeholder_image() {
	$primary = esc_attr( get_theme_mod( 'mysaline_color_primary', '#0f2b4e' ) );
	return '<svg class="ms-placeholder" viewBox="0 0 640 360" width="640" height="360" role="img" aria-label="' . esc_attr__( 'MySaline', 'mysaline' ) . '" xmlns="http://www.w3.org/2000/svg" style="background:' . $primary . ';width:100%;height:100%">'
		. '<rect width="640" height="360" fill="' . $primary . '"/>'
		. '<text x="50%" y="50%" fill="rgba(255,255,255,.35)" font-family="Georgia,serif" font-size="40" font-weight="700" text-anchor="middle" dominant-baseline="middle">MySaline</text>'
		. '</svg>';
}

/**
 * Simple, accessible breadcrumb trail.
 */
function mysaline_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}
	echo '<nav class="ms-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'mysaline' ) . '">';
	echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'mysaline' ) . '</a>';

	if ( is_category() || is_tax() ) {
		echo ' &rsaquo; <span>' . esc_html( single_term_title( '', false ) ) . '</span>';
	} elseif ( is_tag() ) {
		echo ' &rsaquo; <span>' . esc_html( single_tag_title( '', false ) ) . '</span>';
	} elseif ( is_author() ) {
		echo ' &rsaquo; <span>' . esc_html( get_the_author() ) . '</span>';
	} elseif ( is_search() ) {
		echo ' &rsaquo; <span>' . esc_html__( 'Search', 'mysaline' ) . '</span>';
	} elseif ( is_post_type_archive() ) {
		echo ' &rsaquo; <span>' . esc_html( post_type_archive_title( '', false ) ) . '</span>';
	} elseif ( is_singular() ) {
		$cats = get_the_category();
		if ( ! empty( $cats ) ) {
			echo ' &rsaquo; <a href="' . esc_url( get_category_link( $cats[0]->term_id ) ) . '">' . esc_html( $cats[0]->name ) . '</a>';
		}
		echo ' &rsaquo; <span>' . esc_html( get_the_title() ) . '</span>';
	} elseif ( is_page() ) {
		echo ' &rsaquo; <span>' . esc_html( get_the_title() ) . '</span>';
	}
	echo '</nav>';
}

/**
 * Numbered pagination for archives / home.
 */
function mysaline_pagination() {
	$links = paginate_links(
		array(
			'mid_size'  => 1,
			'prev_text' => '&laquo; ' . esc_html__( 'Prev', 'mysaline' ),
			'next_text' => esc_html__( 'Next', 'mysaline' ) . ' &raquo;',
			'type'      => 'array',
		)
	);
	if ( empty( $links ) ) {
		return;
	}
	echo '<nav class="ms-pagination" aria-label="' . esc_attr__( 'Posts navigation', 'mysaline' ) . '">';
	echo implode( '', array_map( 'wp_kses_post', $links ) );
	echo '</nav>';
}

/**
 * Trimmed excerpt with a configurable length and safe fallback.
 *
 * @param int $words Word count.
 * @return string
 */
function mysaline_excerpt( $words = 24 ) {
	$text = has_excerpt() ? get_the_excerpt() : wp_strip_all_tags( get_the_content() );
	return esc_html( wp_trim_words( $text, $words, '&hellip;' ) );
}

/**
 * Inline SVG icons for social networks (trusted, hand-written markup).
 *
 * @return array key => svg string.
 */
function mysaline_social_icons() {
	return array(
		'facebook'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12a10 10 0 10-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.2c-1.2 0-1.6.8-1.6 1.6V12h2.7l-.4 2.9h-2.3v7A10 10 0 0022 12z"/></svg>',
		'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.3 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.3 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.3-2.2-.4a3.7 3.7 0 01-1.4-.9 3.7 3.7 0 01-.9-1.4c-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c.1-1.2.3-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2zm0 1.8c-3.1 0-3.5 0-4.7.1-1.1.1-1.7.2-2.1.4-.5.2-.9.4-1.3.8-.4.4-.6.8-.8 1.3-.2.4-.3 1-.4 2.1C2.6 9.9 2.6 10.3 2.6 12s0 2.1.1 3.3c.1 1.1.2 1.7.4 2.1.2.5.4.9.8 1.3.4.4.8.6 1.3.8.4.2 1 .3 2.1.4 1.2.1 1.6.1 4.7.1s3.5 0 4.7-.1c1.1-.1 1.7-.2 2.1-.4.5-.2.9-.4 1.3-.8.4-.4.6-.8.8-1.3.2-.4.3-1 .4-2.1.1-1.2.1-1.6.1-3.3s0-2.1-.1-3.3c-.1-1.1-.2-1.7-.4-2.1a3.5 3.5 0 00-.8-1.3 3.5 3.5 0 00-1.3-.8c-.4-.2-1-.3-2.1-.4-1.2-.1-1.6-.1-4.7-.1zm0 3.1a4.9 4.9 0 110 9.8 4.9 4.9 0 010-9.8zm0 8a3.1 3.1 0 100-6.2 3.1 3.1 0 000 6.2zm6.3-8.2a1.15 1.15 0 11-2.3 0 1.15 1.15 0 012.3 0z"/></svg>',
		'twitter'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.24 2.25h3.31l-7.23 8.26 8.5 11.24h-6.66l-5.22-6.82-5.97 6.82H1.66l7.73-8.83L1.25 2.25h6.83l4.71 6.23 5.45-6.23zm-1.16 17.52h1.83L7.01 4.13H5.05l12.03 15.64z"/></svg>',
		'youtube'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23 12s0-3.2-.4-4.7a2.5 2.5 0 00-1.8-1.8C19.3 5 12 5 12 5s-7.3 0-8.8.5A2.5 2.5 0 001.4 7.3C1 8.8 1 12 1 12s0 3.2.4 4.7a2.5 2.5 0 001.8 1.8C4.7 19 12 19 12 19s7.3 0 8.8-.5a2.5 2.5 0 001.8-1.8C23 15.2 23 12 23 12zM9.8 15.1V8.9l5.4 3.1-5.4 3.1z"/></svg>',
		'tiktok'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16.5 2h-3v13.2a2.6 2.6 0 11-2.2-2.6v-3a5.6 5.6 0 105.2 5.6V9.3a6.7 6.7 0 003.9 1.2V7.5a3.9 3.9 0 01-3.9-3.9V2z"/></svg>',
		'linkedin'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.98 3.5a2.5 2.5 0 11-.02 5 2.5 2.5 0 01.02-5zM3 8.98h4v12H3v-12zM9.5 8.98h3.83v1.64h.05a4.2 4.2 0 013.78-2.08c4.04 0 4.79 2.66 4.79 6.12v6.32h-4v-5.6c0-1.34-.02-3.06-1.86-3.06-1.87 0-2.16 1.46-2.16 2.96v5.7h-4v-12z"/></svg>',
		'rss'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.18 17.82a2.18 2.18 0 11-4.36 0 2.18 2.18 0 014.36 0zM2 9.86v2.87c4.63 0 8.4 3.77 8.4 8.4h2.87c0-6.22-5.05-11.27-11.27-11.27zM2 4v2.87c7.8 0 14.13 6.34 14.13 14.13H19C19 11.51 11.37 4 2 4z"/></svg>',
	);
}

/**
 * Estimated reading time.
 *
 * @param int|WP_Post|null $post Optional.
 * @return string
 */
function mysaline_reading_time( $post = null ) {
	$post    = get_post( $post );
	$words   = str_word_count( wp_strip_all_tags( $post->post_content ) );
	$minutes = max( 1, (int) ceil( $words / 200 ) );
	/* translators: %d: minutes to read. */
	return sprintf( _n( '%d min read', '%d min read', $minutes, 'mysaline' ), $minutes );
}
