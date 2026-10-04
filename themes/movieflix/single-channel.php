<?php
/**
 * Channel watch page.
 *
 * @package MovieFlix
 */
get_header();
?>
<div class="mf-container mf-page mf-channel-page">
	<?php
	while ( have_posts() ) :
		the_post();
		$mf_channel_id = get_the_ID();
		$mf_stream_raw = trim( (string) mf_meta( $mf_channel_id, 'video_url' ) );
		$mf_stream_url = $mf_stream_raw ? mf_video_url( $mf_stream_raw ) : '';
		$mf_type        = movieflix_video_type( $mf_channel_id );
		$mf_logo_value  = mf_meta( $mf_channel_id, 'channel_logo' );
		$mf_logo        = $mf_logo_value ? mf_resolve_url( $mf_logo_value ) : mf_img( $mf_channel_id, 'poster', 'movieflix-poster' );
		$mf_members     = '1' === (string) mf_meta( $mf_channel_id, 'members_only' );
		$mf_can_watch   = ! $mf_members || ( is_user_logged_in() && ( ! mf_opt( 'enable_membership', 0 ) || ( class_exists( 'MF_Extras' ) && MF_Extras::user_is_member() ) ) );
		$mf_category    = mf_meta( $mf_channel_id, 'channel_category' );
		$mf_number      = mf_meta( $mf_channel_id, 'channel_number' );
		?>
		<header class="mf-channel-page__head">
			<a class="mf-link-btn" href="<?php echo esc_url( mf_page_url( 'channels' ) ); ?>">‹ <?php esc_html_e( 'All channels', 'movieflix' ); ?></a>
			<div class="mf-channel-page__title">
				<?php if ( $mf_logo ) : ?><img src="<?php echo esc_url( $mf_logo ); ?>" alt="" width="96" height="96"><?php endif; ?>
				<div>
					<span class="mf-eyebrow"><?php echo esc_html( $mf_category ? $mf_category : __( 'Live TV', 'movieflix' ) ); ?></span>
					<h1><?php echo esc_html( get_the_title() ); ?></h1>
					<div class="mf-meta">
						<?php if ( $mf_number ) : ?><span><?php echo esc_html( sprintf( __( 'Channel %s', 'movieflix' ), $mf_number ) ); ?></span><?php endif; ?>
						<span class="mf-channel-card__state<?php echo $mf_stream_url ? ' is-ready' : ' is-unavailable'; ?>"><span aria-hidden="true"></span><?php echo esc_html( $mf_stream_url ? __( 'Stream configured', 'movieflix' ) : __( 'Stream unavailable', 'movieflix' ) ); ?></span>
						<?php if ( $mf_members ) : ?><span class="mf-badge mf-badge--members"><?php esc_html_e( 'Members', 'movieflix' ); ?></span><?php endif; ?>
					</div>
				</div>
			</div>
		</header>

		<?php if ( $mf_members && ! $mf_can_watch ) : ?>
			<?php
			if ( ! is_user_logged_in() ) {
				movieflix_empty(
					__( 'Login required', 'movieflix' ),
					__( 'This channel is for members only. Sign in to check your access.', 'movieflix' ),
					__( 'Log in', 'movieflix' ),
					add_query_arg( array( 'mf_msg' => 'login_required', 'redirect_to' => rawurlencode( get_permalink() ) ), mf_page_url( 'login' ) )
				);
			} else {
				movieflix_empty(
					__( 'Members only', 'movieflix' ),
					sprintf( __( 'This channel requires a %s membership. Contact the site admin.', 'movieflix' ), mf_opt( 'membership_label', 'Premium' ) ),
					__( 'Back to channels', 'movieflix' ),
					mf_page_url( 'channels' )
				);
			}
			?>
		<?php elseif ( $mf_stream_url ) : ?>
			<div class="mf-channel-player" data-mf-channel-player data-type="<?php echo esc_attr( $mf_type ); ?>">
				<video class="mf-channel-player__video" controls playsinline preload="metadata" poster="<?php echo esc_url( $mf_logo ); ?>" aria-label="<?php echo esc_attr( sprintf( __( '%s live stream', 'movieflix' ), get_the_title() ) ); ?>">
					<source src="<?php echo esc_url( $mf_stream_url ); ?>" type="<?php echo esc_attr( 'hls' === $mf_type ? 'application/vnd.apple.mpegurl' : ( 'webm' === $mf_type ? 'video/webm' : 'video/mp4' ) ); ?>">
				</video>
				<p class="mf-channel-player__status" data-mf-channel-status role="status" aria-live="polite"><?php esc_html_e( 'Stream ready. Press play to start.', 'movieflix' ); ?></p>
			</div>
		<?php else : ?>
			<div class="mf-channel-unavailable">
				<h2><?php esc_html_e( 'Stream not configured', 'movieflix' ); ?></h2>
				<p><?php esc_html_e( 'The site admin has not added a stream URL for this channel yet.', 'movieflix' ); ?></p>
			</div>
		<?php endif; ?>

		<?php
		$mf_summary = mf_meta( $mf_channel_id, 'short_desc' );
		if ( $mf_summary ) :
			?>
			<div class="mf-channel-page__description"><?php echo wpautop( esc_html( $mf_summary ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php elseif ( get_the_content() ) : ?>
			<div class="mf-channel-page__description"><?php the_content(); ?></div>
		<?php endif; ?>
	<?php endwhile; ?>
</div>
<?php get_footer(); ?>