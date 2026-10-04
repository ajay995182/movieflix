<?php
/** Continue Watching. @package MovieFlix */
movieflix_require_login();
get_header();
$mf_rows = mf_opt( 'enable_continue', 1 ) ? MF_Query::continue_rows( get_current_user_id(), 60 ) : array();
?>
<div class="mf-container mf-page">
	<header class="mf-page__head">
		<span class="mf-eyebrow"><?php esc_html_e( 'Pick up where you left off', 'movieflix' ); ?></span>
		<h1><?php esc_html_e( 'Continue Watching', 'movieflix' ); ?></h1>
		<?php if ( mf_opt( 'enable_continue', 1 ) ) : ?><p class="mf-page__desc"><?php esc_html_e( 'Your unfinished films and episodes are kept here.', 'movieflix' ); ?></p><?php endif; ?>
	</header>
	<?php
	if ( ! mf_opt( 'enable_continue', 1 ) ) {
		movieflix_empty( __( 'Continue Watching is off', 'movieflix' ), __( 'Progress tracking has been disabled on this site.', 'movieflix' ) );
	} elseif ( $mf_rows ) {
		movieflix_grid( MF_Query::by_ids( array_keys( $mf_rows ), 60 ), array( 'progress' => movieflix_progress_map( $mf_rows ), 'remaining' => movieflix_remaining_map( $mf_rows ), 'remove' => true ) );
	} else {
		movieflix_empty( __( 'Nothing to continue', 'movieflix' ), __( 'Start watching something and it will show up here so you can pick up where you left off.', 'movieflix' ), __( 'Find something to watch', 'movieflix' ), get_post_type_archive_link( 'movie' ) );
	}
	?>
</div>
<?php get_footer(); ?>
