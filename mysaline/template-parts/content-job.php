<?php
/**
 * One row on the jobs board.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ms_featured = get_post_meta( get_the_ID(), '_ms_job_featured', true );
$ms_closes   = get_post_meta( get_the_ID(), '_ms_job_closes', true );
?>
<li class="ms-job<?php echo $ms_featured ? ' is-featured' : ''; ?>">
	<div class="ms-job__body">
		<?php if ( $ms_featured ) : ?>
			<p class="ms-eyebrow"><?php esc_html_e( 'Featured', 'mysaline' ); ?></p>
		<?php endif; ?>
		<h2 class="ms-job__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<?php
		$ms_line = mysaline_job_meta_line();
		if ( $ms_line ) {
			echo '<p class="ms-job__meta">' . wp_kses( $ms_line, array( 'strong' => array(), 'span' => array( 'aria-hidden' => true ) ) ) . '</p>';
		}
		?>
		<p class="ms-job__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26 ) ); ?></p>
	</div>
	<div class="ms-job__aside">
		<?php if ( $ms_closes ) : ?>
			<p class="ms-job__closes">
				<?php
				printf(
					/* translators: %s: closing date. */
					esc_html__( 'Closes %s', 'mysaline' ),
					esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ms_closes ) ) )
				);
				?>
			</p>
		<?php endif; ?>
		<a class="ms-btn" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View job', 'mysaline' ); ?></a>
	</div>
</li>
