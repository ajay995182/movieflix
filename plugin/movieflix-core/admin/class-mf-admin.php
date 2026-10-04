<?php
/**
 * MovieFlix admin area: dashboard, settings, demo import.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 1 );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_mf_import_demo', array( __CLASS__, 'import_demo' ) );
		add_action( 'admin_post_mf_clear_cache', array( __CLASS__, 'clear_cache' ) );
		add_action( 'wp_dashboard_setup', function () {
			wp_add_dashboard_widget( 'mf_widget', 'MovieFlix', array( __CLASS__, 'wp_widget' ) );
		} );
	}

	public static function menu() {
		add_menu_page( 'MovieFlix', 'MovieFlix', 'edit_posts', 'movieflix', array( __CLASS__, 'dashboard' ), 'dashicons-video-alt3', 26 );
		add_submenu_page( 'movieflix', 'Dashboard', 'Dashboard', 'edit_posts', 'movieflix', array( __CLASS__, 'dashboard' ) );
		add_action( 'admin_menu', function () {
			add_submenu_page( 'movieflix', 'Reviews', 'Reviews', 'moderate_comments', 'edit-comments.php?comment_type=review' );
			add_submenu_page( 'movieflix', 'Users', 'Users', 'list_users', 'users.php' );
			add_submenu_page( 'movieflix', 'MovieFlix Settings', 'Settings', 'manage_options', 'movieflix-settings', array( __CLASS__, 'settings_page' ) );
		}, 99 );
	}

	/** @return int Count helper for custom tables. */
	private static function count_table( $table ) {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}movieflix_{$table}" ); // phpcs:ignore
	}

	public static function dashboard() {
		global $wpdb;
		$m   = wp_count_posts( 'movie' );
		$reviews = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type='review'" );
		$cards = array(
			'Total Movies'      => (int) $m->publish + (int) $m->draft + (int) $m->pending + (int) $m->private,
			'Published Movies'  => (int) $m->publish,
			'Draft Movies'      => (int) $m->draft,
			'Total Users'       => (int) count_users()['total_users'],
			'Watchlist Items'   => self::count_table( 'watchlist' ),
			'Total Reviews'     => $reviews,
			'Total Ratings'     => self::count_table( 'ratings' ),
		);
		echo '<div class="wrap"><h1>MovieFlix Dashboard</h1><div class="mf-cards">';
		foreach ( $cards as $l => $n ) {
			echo '<div class="mf-card"><b>' . esc_html( number_format_i18n( $n ) ) . '</b><span>' . esc_html( $l ) . '</span></div>';
		}
		echo '</div><p class="mf-quick">';
		$links = array(
			'Add Movie'     => 'post-new.php?post_type=movie',
			'Manage Movies' => 'edit.php?post_type=movie',
			'Add Series'    => 'post-new.php?post_type=series',
			'Add Episode'   => 'post-new.php?post_type=episode',
			'Add Person'    => 'post-new.php?post_type=person',
			'Manage Genres' => 'edit-tags.php?taxonomy=genre&post_type=movie',
			'Manage Users'  => 'users.php',
			'Manage Reviews' => 'edit-comments.php?comment_type=review',
			'Settings'      => 'admin.php?page=movieflix-settings',
		);
		foreach ( $links as $l => $u ) {
			echo '<a class="button button-primary" href="' . esc_url( admin_url( $u ) ) . '">' . esc_html( $l ) . '</a>';
		}
		echo '</p>';
		if ( isset( $_GET['mf_done'] ) ) { // phpcs:ignore
			echo '<div class="notice notice-success"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['mf_done'] ) ) ) . '</p></div>'; // phpcs:ignore
		}
		echo '<div class="mf-demo-panel"><h2>⚡ Demo Content</h2><p style="color:#646970">One-click import of 32 movies, 8 TV series (with episodes), 10 live channels, 20 cast members and extra genres — with unique poster & backdrop images for every title.</p><button type="button" class="button button-primary" id="mf-run-demo">⚡ Hydrate Site (Import Demo)</button> <button type="button" class="button" id="mf-remove-demo">Remove Demo Data</button></div>';
		echo '<div class="mf-cols">';
		self::list_box( 'Most Watched', get_posts( array( 'post_type' => array( 'movie', 'series' ), 'posts_per_page' => 8, 'meta_key' => '_mf_views', 'orderby' => 'meta_value_num', 'order' => 'DESC', 'no_found_rows' => true ) ), 'views' );
		self::list_box( 'Trending Movies', MF_Query::by_ids( MF_Query::trending_ids( 8 ), 8 )->posts, 'views' );
		self::list_box( 'Recently Added', get_posts( array( 'post_type' => array( 'movie', 'series' ), 'posts_per_page' => 8, 'post_status' => array( 'publish', 'draft' ), 'no_found_rows' => true ) ), 'date' );
		echo '</div><p style="margin-top:20px">';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mf_import_demo' ), 'mf_import_demo' ) ) . '">Import demo content</a> ';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mf_clear_cache' ), 'mf_clear_cache' ) ) . '">Refresh trending &amp; recommendation cache</a></p></div>';
	}

	private static function list_box( $title, $posts, $meta ) {
		echo '<div class="mf-box"><h2>' . esc_html( $title ) . '</h2>';
		if ( ! $posts ) {
			echo '<p>Nothing here yet.</p></div>';
			return;
		}
		echo '<table>';
		foreach ( $posts as $p ) {
			$val = 'views' === $meta ? (int) mf_meta( $p->ID, 'views', 0 ) . ' views' : get_the_date( '', $p );
			echo '<tr><td><a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a></td><td style="text-align:right;color:#646970">' . esc_html( $val ) . '</td></tr>';
		}
		echo '</table></div>';
	}

	public static function wp_widget() {
		$m = wp_count_posts( 'movie' );
		echo '<p>' . (int) $m->publish . ' published movies · ' . (int) self::count_table( 'watchlist' ) . ' list items.</p><p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=movieflix' ) ) . '">Open MovieFlix</a></p>';
	}

	public static function import_demo() {
		check_admin_referer( 'mf_import_demo' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'movieflix' ) );
		}
		$res = MF_Demo::import();
		wp_safe_redirect( add_query_arg( 'mf_done', rawurlencode( $res ), admin_url( 'admin.php?page=movieflix' ) ) );
		exit;
	}

	public static function clear_cache() {
		check_admin_referer( 'mf_clear_cache' );
		if ( current_user_can( 'edit_posts' ) ) {
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_mf\_sim\_%' OR option_name LIKE '\_transient\_timeout\_mf\_sim\_%'" ); // phpcs:ignore
			delete_transient( 'mf_trending_auto' );
		}
		wp_safe_redirect( add_query_arg( 'mf_done', rawurlencode( 'Caches refreshed.' ), admin_url( 'admin.php?page=movieflix' ) ) );
		exit;
	}

	/* ---------------- Settings ---------------- */

	public static function register_settings() {
		register_setting( 'movieflix', 'movieflix_settings', array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) ) );
	}

	/** @return array Setting field definitions: key => [label, type, help, options]. */
	private static function fields() {
		return array(
			'General'  => array(
				'site_name'        => array( 'Website name', 'text' ),
				'logo'             => array( 'Logo', 'media' ),
				'favicon'          => array( 'Favicon', 'media' ),
				'primary_color'    => array( 'Primary color', 'color' ),
				'secondary_color'  => array( 'Secondary color', 'color' ),
				'hero_movie'       => array( 'Homepage hero movie', 'movie', 'Shown first in the homepage slider. Leave on "Auto" to use featured titles only.' ),
				'hero_slides'      => array( 'Homepage slider length', 'select', 'Number of featured titles that rotate in the hero banner.', array( '1' => '1 (no slider)', '3' => '3', '5' => '5', '8' => '8' ) ),
				'default_poster'   => array( 'Default poster', 'media' ),
				'default_backdrop' => array( 'Default backdrop', 'media' ),
			),
			'Player'   => array(
				'autoplay'      => array( 'Autoplay on Watch page', 'checkbox', 'Browsers may block autoplay with sound.' ),
				'default_speed' => array( 'Default playback speed', 'select', '', array( '0.75' => '0.75x', '1' => '1x', '1.25' => '1.25x', '1.5' => '1.5x' ) ),
			),
			'Features' => array(
				'enable_ratings'      => array( 'Enable ratings', 'checkbox' ),
				'enable_reviews'      => array( 'Enable user reviews', 'checkbox' ),
				'moderate_reviews'    => array( 'Hold new reviews for moderation', 'checkbox' ),
				'enable_watchlist'    => array( 'Enable watchlist (My List)', 'checkbox' ),
				'enable_continue'     => array( 'Enable Continue Watching / history', 'checkbox' ),
				'enable_registration' => array( 'Enable registration', 'checkbox' ),
				'trending_mode'       => array( 'Trending mode', 'select', 'Auto uses views, completions and list additions of the last 14 days.', array( 'manual' => 'Manual (admin flag only)', 'auto' => 'Automatic only', 'both' => 'Manual first, then automatic' ) ),
			),
			'URLs'     => array(
				'slug_movie' => array( 'Movie URL base', 'text', 'Default: movie → /movie/inception/' ),
				'slug_genre' => array( 'Genre URL base', 'text', 'Default: genre' ),
				'slug_watch' => array( 'Watch URL base', 'text', 'Default: watch' ),
			),
			'Footer & Contact' => array(
				'footer_text'      => array( 'Footer text', 'text' ),
				'social_facebook'  => array( 'Facebook URL', 'url' ),
				'social_twitter'   => array( 'X / Twitter URL', 'url' ),
				'social_instagram' => array( 'Instagram URL', 'url' ),
				'social_youtube'   => array( 'YouTube URL', 'url' ),
				'contact_email'    => array( 'Contact email', 'text' ),
				'contact_phone'    => array( 'Contact phone', 'text' ),
				'contact_address'  => array( 'Contact address', 'text' ),
			),
			'Google Sign-In' => array(
				'enable_google'        => array( 'Enable "Continue with Google"', 'checkbox', 'Needs the two values below from Google Cloud Console (see documentation/GOOGLE-LOGIN.md).' ),
				'google_client_id'     => array( 'Google Client ID', 'text', 'Authorized redirect URI to add in Google Cloud: ' . MF_Google::redirect_uri() ),
				'google_client_secret' => array( 'Google Client Secret', 'password', 'Keep this private. Only administrators can see this page.' ),
			),
			'Accounts & Safety' => array(
				'enable_email_verify' => array( 'Require email verification on register', 'checkbox' ),
				'enable_age_gate'     => array( 'Require age confirmation on register', 'checkbox' ),
				'age_gate_min'        => array( 'Minimum age (label only)', 'text', 'Shown on the register form, e.g. 13 or 18.' ),
				'enable_terms_check'  => array( 'Require Terms & Privacy checkbox', 'checkbox' ),
				'terms_page_id'       => array( 'Terms page ID', 'text', 'WordPress page ID for Terms of Service.' ),
				'privacy_page_id'     => array( 'Privacy page ID', 'text', 'WordPress page ID for Privacy Policy.' ),
				'enable_parental'     => array( 'Parental PIN for mature ratings', 'checkbox', 'Users can set a PIN; R / TV-MA titles ask for it.' ),
				'enable_profiles'     => array( 'Multiple profiles per account', 'checkbox', 'Up to 5 profiles, including Kids profiles.' ),
				'force_login_watch'   => array( 'Require login to play videos', 'checkbox', 'Guests cannot open the watch player.' ),
				'force_login_titles'  => array( 'Require login to open movie/series pages', 'checkbox', 'Deep links like /movie/... redirect guests to login.' ),
				'enable_membership'   => array( 'Enable membership flag (manual Premium)', 'checkbox', 'Mark users as Premium in user profile; gates members-only titles.' ),
				'membership_label'    => array( 'Membership label', 'text', 'e.g. Premium' ),

			),
			'Live TV, Ads & PWA' => array(
				'enable_channels'     => array( 'Enable Live TV / Channels', 'checkbox' ),
				'enable_notify_email' => array( 'Email users about new titles (daily digest)', 'checkbox' ),
				'enable_ads'          => array( 'Enable ad slots', 'checkbox', 'Paste HTML/JS ad code below. Not a full ad server.' ),
				'ad_head'             => array( 'Ad code in site head', 'text' ),
				'ad_player_pre'       => array( 'Ad HTML before player', 'text' ),
				'ad_sidebar'          => array( 'Ad HTML sidebar / detail', 'text' ),
				'enable_pwa'          => array( 'Enable PWA (installable web app)', 'checkbox' ),
				'enable_comments_ui'  => array( 'Rich comments UI on titles', 'checkbox' ),
			),
			'Video delivery' => array(
				'cdn_base_url'       => array( 'CDN base URL (optional)', 'url', 'If set, relative video paths are prefixed with this CDN host. Transcoding is external.' ),
				'enable_signed_urls' => array( 'Soft-signed video URLs', 'checkbox', 'HMAC expiry tokens. Not a substitute for real DRM (Widevine/FairPlay).' ),
			),
			'Activity & Privacy' => array(
				'activity_enabled' => array( 'Record user activity', 'checkbox', 'Logins, views, plays, searches, list changes, ratings, reviews. Shown in MovieFlix → Activity.' ),
				'activity_ip'      => array( 'Also store IP addresses', 'checkbox', 'Off by default. IPs are personal data in many countries; enable only if you need it and mention it in your privacy policy.' ),
				'activity_days'    => array( 'Keep activity for', 'select', 'Older rows are deleted automatically every day.', array( '30' => '30 days', '90' => '90 days', '180' => '180 days', '365' => '1 year', '0' => 'Forever' ) ),
			),
			'Data'     => array(
				'delete_on_uninstall' => array( 'Delete all MovieFlix tables and settings when the plugin is deleted', 'checkbox' ),
			),
		);
	}

	public static function sanitize( $in ) {
		$old = wp_parse_args( (array) get_option( 'movieflix_settings', array() ), mf_default_settings() );
		$out = $old;
		foreach ( self::fields() as $fields ) {
			foreach ( $fields as $k => $f ) {
				$v = isset( $in[ $k ] ) ? wp_unslash( $in[ $k ] ) : ''; // phpcs:ignore
				switch ( $f[1] ) {
					case 'checkbox':
						$out[ $k ] = isset( $in[ $k ] ) ? 1 : 0;
						break;
					case 'color':
						$out[ $k ] = sanitize_hex_color( $v ) ? sanitize_hex_color( $v ) : $old[ $k ];
						break;
					case 'media':
					case 'url':
						$out[ $k ] = ( 0 === strpos( (string) $v, 'demo:' ) ) ? sanitize_text_field( $v ) : esc_url_raw( $v );
						break;
					case 'movie':
						$out[ $k ] = absint( $v );
						break;
					case 'select':
						$out[ $k ] = isset( $f[3][ $v ] ) ? $v : $old[ $k ];
						break;
					default:
						$out[ $k ] = 'footer_text' === $k ? wp_kses_post( $v ) : sanitize_text_field( $v );
						if ( 0 === strpos( $k, 'slug_' ) ) {
							$out[ $k ] = sanitize_title( $out[ $k ] ) ? sanitize_title( $out[ $k ] ) : $old[ $k ];
						}
				}
			}
		}
		$out['pages'] = $old['pages'];
		if ( $out['slug_movie'] !== $old['slug_movie'] || $out['slug_genre'] !== $old['slug_genre'] || $out['slug_watch'] !== $old['slug_watch'] ) {
			update_option( 'movieflix_flush', 1 );
		}
		do_action( 'mf_settings_saved' );
		return $out;
	}

	public static function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = wp_parse_args( (array) get_option( 'movieflix_settings', array() ), mf_default_settings() );
		echo '<div class="wrap"><h1>MovieFlix Settings</h1>';
		if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore
			echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
		}
		echo '<form method="post" action="options.php">';
		settings_fields( 'movieflix' );
		foreach ( self::fields() as $section => $fields ) {
			echo '<h2>' . esc_html( $section ) . '</h2><table class="form-table" role="presentation">';
			foreach ( $fields as $k => $f ) {
				$name = 'movieflix_settings[' . $k . ']';
				$id   = 'mfs_' . $k;
				$v    = $s[ $k ];
				echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
				switch ( $f[1] ) {
					case 'checkbox':
						printf( '<input type="checkbox" id="%s" name="%s" value="1" %s>', esc_attr( $id ), esc_attr( $name ), checked( 1, (int) $v, false ) );
						break;
					case 'color':
						printf( '<input type="color" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $v ) );
						break;
					case 'media':
						printf( '<span class="mf-media"><input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s"> <button type="button" class="button mf-media-btn" data-target="%1$s">Media Library</button></span>', esc_attr( $id ), esc_attr( $name ), esc_attr( $v ) );
						break;
					case 'select':
						echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
						foreach ( $f[3] as $ok => $ol ) {
							printf( '<option value="%s" %s>%s</option>', esc_attr( $ok ), selected( (string) $v, (string) $ok, false ), esc_html( $ol ) );
						}
						echo '</select>';
						break;
					case 'movie':
						echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"><option value="0">Auto</option>';
						foreach ( get_posts( array( 'post_type' => array( 'movie', 'series' ), 'post_status' => 'publish', 'posts_per_page' => 300, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true ) ) as $p ) {
							printf( '<option value="%d" %s>%s</option>', (int) $p->ID, selected( (int) $v, $p->ID, false ), esc_html( $p->post_title ) );
						}
						echo '</select>';
						break;
					default:
						printf( '<input type="%s" class="regular-text" id="%s" name="%s" value="%s">', 'url' === $f[1] ? 'url' : ( 'password' === $f[1] ? 'password' : 'text' ), esc_attr( $id ), esc_attr( $name ), esc_attr( $v ) );
				}
				if ( ! empty( $f[2] ) ) {
					echo '<p class="description">' . esc_html( $f[2] ) . '</p>';
				}
				echo '</td></tr>';
			}
			echo '</table>';
		}
		submit_button();
		echo '</form></div>';
	}
}
