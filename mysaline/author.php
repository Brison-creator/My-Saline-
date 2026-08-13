<?php
/**
 * Author archive — the columnist's home.
 *
 * Recurring voices are a real part of this newsroom, so an author archive gets
 * a proper masthead (photo, bio, social, post count) rather than a bare list.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$mysaline_author = get_queried_object();
$mysaline_id     = $mysaline_author ? (int) $mysaline_author->ID : 0;
$mysaline_bio    = $mysaline_id ? get_the_author_meta( 'description', $mysaline_id ) : '';
$mysaline_site   = $mysaline_id ? get_the_author_meta( 'user_url', $mysaline_id ) : '';
$mysaline_count  = $mysaline_id ? count_user_posts( $mysaline_id, 'post', true ) : 0;

/*
 * Label the writer by the role they actually hold. Calling the managing editor
 * a "Columnist" on their own archive page is the kind of small wrongness a
 * reader notices immediately.
 */
$mysaline_roles = array(
	'administrator' => __( 'Editor', 'mysaline' ),
	'editor'        => __( 'Editor', 'mysaline' ),
	'author'        => __( 'Staff writer', 'mysaline' ),
	'contributor'   => __( 'Contributor', 'mysaline' ),
);
$mysaline_label = __( 'Staff writer', 'mysaline' );

// An explicit title always wins: "Publisher" is a fact about the person, while
// the WordPress role is only ever a guess derived from their permissions.
$mysaline_own_title = $mysaline_id ? trim( (string) get_user_meta( $mysaline_id, 'mysaline_role_title', true ) ) : '';

if ( '' === $mysaline_own_title && $mysaline_author instanceof WP_User ) {
	foreach ( (array) $mysaline_author->roles as $mysaline_role ) {
		if ( isset( $mysaline_roles[ $mysaline_role ] ) ) {
			$mysaline_label = $mysaline_roles[ $mysaline_role ];
			break;
		}
	}
}

if ( '' !== $mysaline_own_title ) {
	$mysaline_label = $mysaline_own_title;
}

/**
 * Change the role label shown above an author's name.
 *
 * @param string $label   Label to display.
 * @param int    $user_id Author being shown.
 */
$mysaline_label = apply_filters( 'mysaline_author_role_label', $mysaline_label, $mysaline_id );
?>

<?php mysaline_breadcrumbs(); ?>

<header class="ms-author-header">
	<?php echo get_avatar( $mysaline_id, 96, '', '', array( 'class' => 'ms-author-header__avatar' ) ); ?>
	<div class="ms-author-header__body">
		<?php if ( $mysaline_label ) : ?>
			<p class="ms-eyebrow"><?php echo esc_html( $mysaline_label ); ?></p>
		<?php endif; ?>
		<h1><?php echo esc_html( get_the_author_meta( 'display_name', $mysaline_id ) ); ?></h1>
		<?php if ( $mysaline_bio ) : ?>
			<p class="ms-author-header__bio"><?php echo esc_html( $mysaline_bio ); ?></p>
		<?php endif; ?>
		<p class="ms-author-header__meta">
			<?php
			printf(
				/* translators: %s: number of stories. */
				esc_html( _n( '%s story', '%s stories', $mysaline_count, 'mysaline' ) ),
				esc_html( number_format_i18n( $mysaline_count ) )
			);
			if ( $mysaline_site ) {
				echo ' · <a href="' . esc_url( $mysaline_site ) . '" rel="noopener">' . esc_html__( 'Website', 'mysaline' ) . '</a>';
			}
			?>
			· <a href="<?php echo esc_url( get_author_feed_link( $mysaline_id ) ); ?>"><?php esc_html_e( 'RSS', 'mysaline' ); ?></a>
		</p>
	</div>
</header>

<div class="ms-content-sidebar" style="margin-top:2rem">
	<div class="ms-primary-col">
		<?php if ( have_posts() ) : ?>
			<div class="ms-grid ms-grid--3">
				<?php
				$mysaline_i = 0;
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content-card' );
					mysaline_ad_in_feed( $mysaline_i, 6 );
					$mysaline_i++;
				endwhile;
				?>
			</div>
			<?php mysaline_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>

	<?php get_sidebar(); ?>
</div>

<?php
get_footer();
