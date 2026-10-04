<?php
/** My List. @package MovieFlix */
movieflix_require_login();
get_header();
$mf_ids = mf_user_list_ids();
?>
<div class="mf-container mf-page">
	<header class="mf-page__head">
		<span class="mf-eyebrow"><?php esc_html_e( 'Your library', 'movieflix' ); ?></span>
		<h1><?php esc_html_e( 'My List', 'movieflix' ); ?></h1>
		<?php if ( mf_opt( 'enable_watchlist', 1 ) ) : ?><p class="mf-page__desc"><?php echo esc_html( sprintf( _n( '%s saved title', '%s saved titles', count( $mf_ids ), 'movieflix' ), number_format_i18n( count( $mf_ids ) ) ) ); ?></p><?php endif; ?>
	</header>
	<?php movieflix_notice(); ?>
	<?php
	if ( ! mf_opt( 'enable_watchlist', 1 ) ) {
		movieflix_empty( __( 'My List is turned off', 'movieflix' ), '', __( 'Go home', 'movieflix' ), home_url( '/' ) );
	} elseif ( $mf_ids ) {
		movieflix_grid( MF_Query::by_ids( $mf_ids, 100 ) );
	} else {
		movieflix_empty( __( 'Your list is empty', 'movieflix' ), __( 'Tap + on any movie to save it here for later.', 'movieflix' ), __( 'Browse movies', 'movieflix' ), get_post_type_archive_link( 'movie' ) );
	}
	?>
</div>
<?php get_footer(); ?>
