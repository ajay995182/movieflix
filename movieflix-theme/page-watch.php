<?php
/**
 * Watch page served at /watch/{slug}/ by MovieFlix Core.
 *
 * @package MovieFlix
 */
get_header();
global $wpdb;
$mf_post = isset( $GLOBALS['mf_watch_post'] ) ? $GLOBALS['mf_watch_post'] : null;
echo '<div class="mf-container mf-page mf-watch-page">';
if ( ! $mf_post ) {
	movieflix_empty( __( 'Movie not found', 'movieflix' ), __( 'We could not find that title. It may have been removed.', 'movieflix' ), __( 'Browse movies', 'movieflix' ), get_post_type_archive_link( 'movie' ) );
	echo '</div>';
	get_footer();
	return;
}
$mf_id     = $mf_post->ID;
$mf_is_ep  = 'episode' === $mf_post->post_type;
$mf_parent = $mf_is_ep ? (int) mf_meta( $mf_id, 'series' ) : 0;
$mf_art    = $mf_parent ? $mf_parent : $mf_id;
$mf_back   = get_permalink( $mf_art );
$mf_title  = $mf_is_ep && $mf_parent ? get_the_title( $mf_post ) : get_the_title( $mf_post );
if ( '1' === (string) mf_meta( $mf_id, 'members_only' ) ) {
	$mf_can = is_user_logged_in() && ( ! mf_opt( 'enable_membership', 0 ) || ( class_exists( 'MF_Extras' ) && MF_Extras::user_is_member() ) );
	if ( ! $mf_can ) {
		if ( ! is_user_logged_in() ) {
			movieflix_empty( __( 'Login required', 'movieflix' ), __( 'This title is for members only. Please log in to watch.', 'movieflix' ), __( 'Log in', 'movieflix' ), add_query_arg( array( 'mf_msg' => 'login_required', 'redirect_to' => rawurlencode( mf_watch_url( $mf_id ) ) ), mf_page_url( 'login' ) ) );
		} else {
			movieflix_empty( __( 'Members only', 'movieflix' ), sprintf( __( 'This title requires a %s membership. Contact the site admin.', 'movieflix' ), mf_opt( 'membership_label', 'Premium' ) ), __( 'Back', 'movieflix' ), $mf_back );
		}
		echo '</div>';
		get_footer();
		return;
	}
}
// Parental PIN for mature content
if ( class_exists( 'MF_Extras' ) && MF_Extras::needs_pin_for_title( $mf_art ) ) {
	echo '<div class="mf-auth__card mf-pin-card"><h2>' . esc_html__( 'Parental PIN', 'movieflix' ) . '</h2>';
	echo '<p class="mf-hint">' . esc_html__( 'Enter your PIN to watch mature titles.', 'movieflix' ) . '</p>';
	movieflix_notice();
	echo '<form class="mf-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="mf_check_pin">';
	echo '<input type="hidden" name="redirect_to" value="' . esc_url( mf_watch_url( $mf_id ) ) . '">';
	wp_nonce_field( 'mf_check_pin', '_mf' );
	echo '<label for="pin">' . esc_html__( 'PIN', 'movieflix' ) . '</label>';
	echo '<input type="password" id="pin" name="pin" inputmode="numeric" pattern="[0-9]*" required autocomplete="off">';
	echo '<button type="submit" class="mf-btn mf-btn--primary mf-btn--block">' . esc_html__( 'Unlock', 'movieflix' ) . '</button></form></div>';
	echo '</div>';
	get_footer();
	return;
}
$mf_url = mf_video_url( mf_meta( $mf_id, 'video_url' ) );
if ( ! $mf_url ) {
	movieflix_empty( __( 'Video unavailable', 'movieflix' ), __( 'No video has been set up for this title yet.', 'movieflix' ), __( 'Back to details', 'movieflix' ), $mf_back );
	echo '</div>';
	get_footer();
	return;
}
$mf_type = movieflix_video_type( $mf_id );
$mf_start = 0;
if ( is_user_logged_in() && mf_opt( 'enable_continue', 1 ) ) {
	$mf_row = $wpdb->get_row( $wpdb->prepare( "SELECT position, completed FROM {$wpdb->prefix}movieflix_watch_history WHERE user_id=%d AND movie_id=%d", get_current_user_id(), $mf_id ) );
	if ( $mf_row && ! $mf_row->completed && (int) $mf_row->position >= 10 ) {
		$mf_start = (int) $mf_row->position;
	}
}
$mf_qual = array();
foreach ( movieflix_lines( $mf_id, 'video_sources' ) as $l ) {
	$mf_qual[] = array( 'label' => $l[0], 'url' => $l[ count( $l ) - 1 ] );
}
$mf_next = '';
$mf_next_title = '';
$mf_next_poster = '';
if ( $mf_is_ep && $mf_parent ) {
	$eps = MF_Query::episodes( $mf_parent );
	foreach ( $eps as $i => $e ) {
		if ( (int) $e->ID === (int) $mf_id && isset( $eps[ $i + 1 ] ) ) {
			$mf_next = mf_watch_url( $eps[ $i + 1 ]->ID );
			$mf_next_title = get_the_title( $eps[ $i + 1 ] );
			$mf_next_poster = mf_img( $eps[ $i + 1 ]->ID, 'backdrop', 'movieflix-backdrop' );
			if ( ! $mf_next_poster ) {
				$mf_next_poster = mf_img( $mf_parent, 'backdrop', 'movieflix-backdrop' );
			}
		}
	}
}
$mf_intro_start = (int) mf_meta( $mf_id, 'intro_start', 0 );
$mf_intro_end   = (int) mf_meta( $mf_id, 'intro_end', 0 );
$mf_audio_tracks = array();
foreach ( movieflix_lines( $mf_id, 'audio_tracks' ) as $l ) {
	if ( count( $l ) >= 2 ) {
		$mf_audio_tracks[] = array( 'label' => $l[0], 'url' => end( $l ) );
	}
}
$mf_subs_json = array();
foreach ( movieflix_lines( $mf_id, 'subtitles' ) as $t ) {
	$mf_subs_json[] = array(
		'label' => $t[0],
		'code'  => isset( $t[2] ) ? $t[1] : 'en',
		'url'   => end( $t ),
	);
}
$mime = array( 'mp4' => 'video/mp4', 'webm' => 'video/webm', 'hls' => 'application/x-mpegURL' );
?>
<div class="mf-player" id="mf-player"
	data-id="<?php echo (int) $mf_id; ?>"
	data-start="<?php echo (int) $mf_start; ?>"
	data-type="<?php echo esc_attr( $mf_type ); ?>"
	data-src="<?php echo esc_url( $mf_url ); ?>"
	data-next="<?php echo esc_url( $mf_next ); ?>"
	data-next-title="<?php echo esc_attr( $mf_next_title ); ?>"
	data-next-poster="<?php echo esc_url( $mf_next_poster ); ?>"
	data-qualities="<?php echo esc_attr( wp_json_encode( $mf_qual ) ); ?>"
	data-subs="<?php echo esc_attr( wp_json_encode( $mf_subs_json ) ); ?>"
	data-audio="<?php echo esc_attr( wp_json_encode( $mf_audio_tracks ) ); ?>"
	data-intro-start="<?php echo (int) $mf_intro_start; ?>"
	data-intro-end="<?php echo (int) $mf_intro_end; ?>">
	<div class="mf-player__stage">
		<video id="mf-video" controls playsinline preload="metadata" crossorigin="anonymous" poster="<?php echo esc_url( mf_img( $mf_art, 'backdrop', 'movieflix-backdrop' ) ); ?>">
			<?php if ( 'hls' !== $mf_type ) : ?><source src="<?php echo esc_url( $mf_url ); ?>" type="<?php echo esc_attr( $mime[ $mf_type ] ?? 'video/mp4' ); ?>"><?php endif; ?>
			<?php foreach ( movieflix_lines( $mf_id, 'subtitles' ) as $i => $t ) : ?>
				<track kind="subtitles" label="<?php echo esc_attr( $t[0] ); ?>" srclang="<?php echo esc_attr( isset( $t[2] ) ? $t[1] : 'en' ); ?>" src="<?php echo esc_url( end( $t ) ); ?>"<?php echo 0 === $i ? ' default' : ''; ?>>
			<?php endforeach; ?>
			<?php esc_html_e( 'Your browser does not support HTML5 video.', 'movieflix' ); ?>
		</video>
		<button type="button" class="mf-skip-intro" id="mf-skip-intro" hidden><?php esc_html_e( 'Skip Intro', 'movieflix' ); ?></button>
		<div class="mf-next-overlay" id="mf-next-overlay" hidden>
			<div class="mf-next-overlay__card">
				<?php if ( $mf_next_poster ) : ?><img class="mf-next-overlay__poster" src="<?php echo esc_url( $mf_next_poster ); ?>" alt=""><?php endif; ?>
				<div class="mf-next-overlay__body">
					<p class="mf-next-overlay__label"><?php esc_html_e( 'Up next', 'movieflix' ); ?> <span id="mf-next-countdown">8</span>s</p>
					<strong class="mf-next-overlay__title"><?php echo esc_html( $mf_next_title ?: __( 'Next episode', 'movieflix' ) ); ?></strong>
					<div class="mf-next-overlay__actions">
						<a class="mf-btn mf-btn--primary" id="mf-next-go" href="<?php echo esc_url( $mf_next ); ?>"><?php esc_html_e( 'Play now', 'movieflix' ); ?></a>
						<button type="button" class="mf-btn mf-btn--ghost" id="mf-next-cancel"><?php esc_html_e( 'Cancel', 'movieflix' ); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="mf-player__bar">
		<label class="mf-player__control" for="mf-subs" id="mf-subs-wrap" hidden><?php esc_html_e( 'Subtitles', 'movieflix' ); ?>
			<select id="mf-subs"><option value="off"><?php esc_html_e( 'Off', 'movieflix' ); ?></option></select></label>
		<label class="mf-player__control" for="mf-audio" id="mf-audio-wrap" hidden><?php esc_html_e( 'Audio', 'movieflix' ); ?>
			<select id="mf-audio"></select></label>
		<label class="mf-player__control" for="mf-speed"><?php esc_html_e( 'Speed', 'movieflix' ); ?>
			<select id="mf-speed"><?php foreach ( array( '0.5', '0.75', '1', '1.25', '1.5', '2' ) as $s ) { printf( '<option value="%1$s">%1$sx</option>', esc_attr( $s ) ); } ?></select></label>
		<label class="mf-player__control" id="mf-quality-wrap" for="mf-quality" hidden><?php esc_html_e( 'Quality', 'movieflix' ); ?><select id="mf-quality"></select></label>
		<button type="button" class="mf-btn mf-btn--ghost" id="mf-pip" hidden><?php esc_html_e( 'PiP', 'movieflix' ); ?></button>
		<button type="button" class="mf-btn mf-btn--ghost" id="mf-cast" hidden title="<?php esc_attr_e( 'Cast', 'movieflix' ); ?>"><?php esc_html_e( 'Cast', 'movieflix' ); ?></button>
		<?php movieflix_list_button( $mf_art ); ?>
		<a class="mf-btn mf-btn--ghost" href="<?php echo esc_url( $mf_back ); ?>"><?php esc_html_e( 'Details', 'movieflix' ); ?></a>
		<?php if ( $mf_next ) : ?><a class="mf-btn mf-btn--primary" href="<?php echo esc_url( $mf_next ); ?>"><?php esc_html_e( 'Next episode', 'movieflix' ); ?> ›</a><?php endif; ?>
	</div>
	<p class="mf-player__msg" id="mf-msg" role="status" aria-live="polite"></p>
</div>
<h1 class="mf-watch-title"><?php echo esc_html( $mf_title ); ?></h1>
<p class="mf-meta">
	<?php if ( mf_meta( $mf_art, 'year' ) ) : ?><span><?php echo esc_html( mf_meta( $mf_art, 'year' ) ); ?></span><?php endif; ?>
	<?php if ( mf_meta( $mf_id, 'runtime', mf_meta( $mf_art, 'runtime' ) ) ) : ?><span><?php echo esc_html( mf_meta( $mf_id, 'runtime', mf_meta( $mf_art, 'runtime' ) ) ); ?> min</span><?php endif; ?>
	<?php if ( mf_display_rating( $mf_art ) ) : ?><span class="mf-rate">★ <?php echo esc_html( mf_display_rating( $mf_art ) ); ?></span><?php endif; ?>
</p>
<p class="mf-lead"><?php echo esc_html( mf_meta( $mf_art, 'short_desc', wp_trim_words( get_the_excerpt( $mf_post ), 40 ) ) ); ?></p>
<?php if ( ! is_user_logged_in() && mf_opt( 'enable_continue', 1 ) ) : ?><p class="mf-hint"><a href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( mf_watch_url( $mf_id ) ), mf_page_url( 'login' ) ) ); ?>"><?php esc_html_e( 'Log in', 'movieflix' ); ?></a> <?php esc_html_e( 'to save your progress and continue watching later.', 'movieflix' ); ?></p><?php endif; ?>
</div>
<?php
if ( $mf_art && ! $mf_is_ep ) {
	movieflix_row( __( 'More Like This', 'movieflix' ), MF_Query::by_ids( MF_Query::similar_ids( $mf_art, 14 ), 14 ) );
}
get_footer();
