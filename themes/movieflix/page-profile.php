<?php
/** User dashboard. @package MovieFlix */
movieflix_require_login();
get_header();
global $wpdb;
$mf_user = wp_get_current_user();
$mf_uid  = $mf_user->ID;
$mf_tabs = array( 'overview' => __( 'Overview', 'movieflix' ), 'list' => __( 'My List', 'movieflix' ), 'continue' => __( 'Continue Watching', 'movieflix' ), 'history' => __( 'Watch History', 'movieflix' ), 'activity' => __( 'My Activity', 'movieflix' ), 'reviews' => __( 'My Reviews', 'movieflix' ), 'ratings' => __( 'My Ratings', 'movieflix' ), 'settings' => __( 'Profile Settings', 'movieflix' ) );
$mf_tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview'; // phpcs:ignore
if ( ! isset( $mf_tabs[ $mf_tab ] ) ) {
	$mf_tab = 'overview';
}
?>
<div class="mf-container mf-page mf-profile">
	<aside class="mf-profile__nav" aria-label="<?php esc_attr_e( 'Account', 'movieflix' ); ?>">
		<div class="mf-profile__user"><?php echo get_avatar( $mf_uid, 72, '', '', array( 'class' => 'mf-avatar mf-avatar--lg' ) ); // phpcs:ignore ?><strong><?php echo esc_html( $mf_user->display_name ); ?></strong><span><?php echo esc_html( $mf_user->user_email ); ?></span></div>
		<ul>
			<?php foreach ( $mf_tabs as $k => $l ) : ?>
				<li><a href="<?php echo esc_url( add_query_arg( 'tab', $k, get_permalink() ) ); ?>"<?php echo $k === $mf_tab ? ' aria-current="page" class="is-active"' : ''; ?>><?php echo esc_html( $l ); ?></a></li>
			<?php endforeach; ?>
			<li><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Logout', 'movieflix' ); ?></a></li>
		</ul>
	</aside>
	<section class="mf-profile__main">
		<h1><?php echo esc_html( $mf_tabs[ $mf_tab ] ); ?></h1>
		<?php movieflix_notice(); ?>
		<?php
		switch ( $mf_tab ) {
			case 'list':
				$ids = mf_user_list_ids();
				$ids ? movieflix_grid( MF_Query::by_ids( $ids, 100 ) ) : movieflix_empty( __( 'Your list is empty', 'movieflix' ), __( 'Save movies to watch later.', 'movieflix' ), __( 'Browse movies', 'movieflix' ), get_post_type_archive_link( 'movie' ) );
				break;
			case 'continue':
				$rows = MF_Query::continue_rows( $mf_uid, 40 );
				$rows ? movieflix_grid( MF_Query::by_ids( array_keys( $rows ), 40 ), array( 'progress' => movieflix_progress_map( $rows ), 'remove' => true ) ) : movieflix_empty( __( 'Nothing to continue', 'movieflix' ), __( 'Titles you start will appear here.', 'movieflix' ) );
				break;
			case 'history':
				$rows = MF_Query::history_rows( $mf_uid, 60 );
				if ( $rows ) {
					echo '<p><button type="button" class="mf-btn mf-btn--ghost mf-clear-history">' . esc_html__( 'Clear watch history', 'movieflix' ) . '</button></p>';
					movieflix_grid( MF_Query::by_ids( array_keys( $rows ), 60 ) );
				} else {
					movieflix_empty( __( 'You haven\'t watched anything yet', 'movieflix' ), __( 'Your viewing history will show up here.', 'movieflix' ), __( 'Start watching', 'movieflix' ), get_post_type_archive_link( 'movie' ) );
				}
				break;
			case 'activity':
				$acts = MF_Activity::actions();
				$rows = MF_Activity::user_recent( $mf_uid, 60 );
				if ( $rows ) {
					echo '<ul class="mf-timeline">';
					foreach ( $rows as $r ) {
						$a = isset( $acts[ $r->action ] ) ? $acts[ $r->action ] : array( $r->action, '•' );
						list( $text, $url ) = MF_Activity::detail( $r );
						echo '<li><span class="mf-timeline__ico" aria-hidden="true">' . esc_html( $a[1] ) . '</span><div><strong>' . esc_html( $a[0] ) . '</strong>';
						if ( $text ) {
							echo ' — ' . ( $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>' : esc_html( $text ) );
						}
						echo '<small>' . esc_html( sprintf( __( '%s ago', 'movieflix' ), human_time_diff( strtotime( $r->created_at . ' UTC' ), time() ) ) ) . '</small></div></li>';
					}
					echo '</ul>';
					echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'' . esc_js( __( 'Delete all your activity history?', 'movieflix' ) ) . '\')"><input type="hidden" name="action" value="mf_clear_activity">';
					wp_nonce_field( 'mf_clear_activity', '_mf' );
					echo '<button class="mf-btn mf-btn--ghost" type="submit">' . esc_html__( 'Clear my activity', 'movieflix' ) . '</button></form>';
				} else {
					movieflix_empty( __( 'No activity yet', 'movieflix' ), __( 'What you watch, rate and search will appear here.', 'movieflix' ) );
				}
				break;
			case 'reviews':
				$revs = get_comments( array( 'user_id' => $mf_uid, 'type' => 'review', 'status' => 'all', 'number' => 50 ) );
				if ( $revs ) {
					echo '<ul class="mf-review-list">';
					foreach ( $revs as $r ) {
						echo '<li class="mf-review"><div><strong><a href="' . esc_url( get_permalink( $r->comment_post_ID ) . '#reviews' ) . '">' . esc_html( get_the_title( $r->comment_post_ID ) ) . '</a></strong>';
						echo '0' === $r->comment_approved ? ' <span class="mf-pill">' . esc_html__( 'Pending', 'movieflix' ) . '</span>' : '';
						echo '<p>' . esc_html( $r->comment_content ) . '</p></div></li>';
					}
					echo '</ul>';
				} else {
					movieflix_empty( __( 'No reviews yet', 'movieflix' ), __( 'Open a movie page to write your first review.', 'movieflix' ) );
				}
				break;
			case 'ratings':
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT movie_id, rating FROM {$wpdb->prefix}movieflix_ratings WHERE user_id=%d ORDER BY updated_at DESC LIMIT 100", $mf_uid ) );
				if ( $rows ) {
					echo '<ul class="mf-rated">';
					foreach ( $rows as $r ) {
						if ( 'publish' === get_post_status( $r->movie_id ) ) {
							echo '<li><a href="' . esc_url( get_permalink( $r->movie_id ) ) . '">' . esc_html( get_the_title( $r->movie_id ) ) . '</a> ' . wp_kses_post( movieflix_stars( (int) $r->rating ) ) . '</li>';
						}
					}
					echo '</ul>';
				} else {
					movieflix_empty( __( 'No ratings yet', 'movieflix' ), __( 'Rate movies with 1–5 stars on their pages.', 'movieflix' ) );
				}
				break;
			case 'settings':
				?>
				<form class="mf-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mf_profile">
					<?php wp_nonce_field( 'mf_profile', '_mf' ); ?>
					<p class="mf-hp" aria-hidden="true"><label>Website <input type="text" name="mf_website" tabindex="-1" autocomplete="off"></label></p>
					<label for="mf_avatar"><?php esc_html_e( 'Avatar (JPG/PNG/WebP, max 2 MB)', 'movieflix' ); ?></label>
					<input type="file" id="mf_avatar" name="mf_avatar" accept="image/jpeg,image/png,image/webp">
					<label for="display_name"><?php esc_html_e( 'Display name', 'movieflix' ); ?></label>
					<input type="text" id="display_name" name="display_name" value="<?php echo esc_attr( $mf_user->display_name ); ?>" required>
					<label for="user_email"><?php esc_html_e( 'Email', 'movieflix' ); ?></label>
					<input type="email" id="user_email" name="user_email" value="<?php echo esc_attr( $mf_user->user_email ); ?>" required>
					<label for="new_password"><?php esc_html_e( 'New password (leave blank to keep current)', 'movieflix' ); ?></label>
					<input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8">
					<label for="confirm_password"><?php esc_html_e( 'Confirm new password', 'movieflix' ); ?></label>
					<input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
					<button class="mf-btn mf-btn--primary" type="submit"><?php esc_html_e( 'Save changes', 'movieflix' ); ?></button>
				</form>
				<?php
				break;
			default:
				$n_list = count( mf_user_list_ids() );
				$n_hist = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}movieflix_watch_history WHERE user_id=%d", $mf_uid ) );
				$n_rat  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}movieflix_ratings WHERE user_id=%d", $mf_uid ) );
				$n_rev  = (int) get_comments( array( 'user_id' => $mf_uid, 'type' => 'review', 'status' => 'all', 'count' => true ) );
				echo '<div class="mf-stats">';
				foreach ( array( __( 'In My List', 'movieflix' ) => $n_list, __( 'Titles watched', 'movieflix' ) => $n_hist, __( 'Ratings', 'movieflix' ) => $n_rat, __( 'Reviews', 'movieflix' ) => $n_rev ) as $l => $n ) {
					echo '<div class="mf-stat"><b>' . (int) $n . '</b><span>' . esc_html( $l ) . '</span></div>';
				}
				echo '</div><p class="mf-hint">' . esc_html( sprintf( __( 'Member since %s', 'movieflix' ), mysql2date( get_option( 'date_format' ), $mf_user->user_registered ) ) ) . '</p>';
				$rows = MF_Query::continue_rows( $mf_uid, 10 );
				if ( $rows ) {
					movieflix_row( __( 'Continue Watching', 'movieflix' ), MF_Query::by_ids( array_keys( $rows ), 10 ), array( 'progress' => movieflix_progress_map( $rows ), 'remove' => true ) );
				}
		}
		?>
	</section>
</div>
<?php get_footer(); ?>
