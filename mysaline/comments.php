<?php
/**
 * Comments area.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	return;
}
?>
<div class="ms-comments-area">
	<?php if ( have_comments() ) : ?>
		<h2 class="ms-comments-title">
			<?php
			$mysaline_count = get_comments_number();
			if ( '1' === (string) $mysaline_count ) {
				esc_html_e( 'One response', 'mysaline' );
			} else {
				/* translators: %s: comment count. */
				printf( esc_html( _n( '%s response', '%s responses', $mysaline_count, 'mysaline' ) ), esc_html( number_format_i18n( $mysaline_count ) ) );
			}
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'avatar_size' => 48,
					'short_ping' => true,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text' => esc_html__( '← Older comments', 'mysaline' ),
				'next_text' => esc_html__( 'Newer comments →', 'mysaline' ),
			)
		);
		?>

		<?php if ( ! comments_open() ) : ?>
			<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'mysaline' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply'        => esc_html__( 'Join the conversation', 'mysaline' ),
			'class_submit'       => 'ms-btn',
			'title_reply_before' => '<h2 class="ms-comments-title">',
			'title_reply_after'  => '</h2>',
		)
	);
	?>
</div>
