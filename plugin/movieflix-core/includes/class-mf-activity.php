<?php
/**
 * User activity log: who did what and when. Powers the admin "Activity" screen
 * and the "My Activity" tab on the member profile.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Activity {

	/** @var bool Set while a Google login fires wp_login so it is not logged twice. */
	public static $skip_login = false;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 5 );
		add_action( 'init', array( __CLASS__, 'touch_user' ) );
		add_action( 'wp_login', array( __CLASS__, 'on_login' ), 10, 2 );
		add_action( 'wp_logout', array( __CLASS__, 'on_logout' ) );
		add_action( 'user_register', array( __CLASS__, 'on_register' ) );
		add_action( 'delete_user', array( __CLASS__, 'on_delete_user' ) );
		add_action( 'template_redirect', array( __CLASS__, 'track_front' ) );
		add_action( 'admin_post_mf_clear_activity', array( __CLASS__, 'clear_mine' ) );
		add_action( 'mf_purge_activity', array( __CLASS__, 'purge' ) );
		add_action( 'admin_init', array( __CLASS__, 'privacy_text' ) );
		if ( ! wp_next_scheduled( 'mf_purge_activity' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mf_purge_activity' );
		}
	}

	/** Add the activity table on plugin update (activation does not run on update). */
	public static function maybe_upgrade() {
		if ( get_option( 'movieflix_db_version' ) !== MF_VERSION ) {
			MF_Install::create_tables();
		}
	}

	/** @return array action => [label, icon] */
	public static function actions() {
		return array(
			'login'          => array( __( 'Logged in', 'movieflix' ), '🔑' ),
			'google_login'   => array( __( 'Logged in with Google', 'movieflix' ), '🅖' ),
			'logout'         => array( __( 'Logged out', 'movieflix' ), '🚪' ),
			'register'       => array( __( 'Registered', 'movieflix' ), '🆕' ),
			'view'           => array( __( 'Viewed title', 'movieflix' ), '👁' ),
			'search'         => array( __( 'Searched', 'movieflix' ), '🔎' ),
			'watch_start'    => array( __( 'Started watching', 'movieflix' ), '▶' ),
			'watch_complete' => array( __( 'Finished watching', 'movieflix' ), '✅' ),
			'list_add'       => array( __( 'Added to My List', 'movieflix' ), '➕' ),
			'list_remove'    => array( __( 'Removed from My List', 'movieflix' ), '➖' ),
			'rate'           => array( __( 'Rated', 'movieflix' ), '⭐' ),
			'review'         => array( __( 'Wrote a review', 'movieflix' ), '💬' ),
			'profile_update' => array( __( 'Updated profile', 'movieflix' ), '👤' ),
		);
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'movieflix_activity';
	}

	/**
	 * Record an event.
	 *
	 * @param string   $action    Key from actions().
	 * @param int      $object_id Related post ID (optional).
	 * @param array    $meta      Extra data (search term, rating...).
	 * @param int|null $user_id   Defaults to the current user (0 = guest).
	 */
	public static function log( $action, $object_id = 0, $meta = array(), $user_id = null ) {
		if ( ! mf_opt( 'activity_enabled', 1 ) ) {
			return;
		}
		global $wpdb;
		$uid = null === $user_id ? get_current_user_id() : (int) $user_id;
		$ip  = '';
		if ( mf_opt( 'activity_ip', 0 ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = substr( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ), 0, 45 );
		}
		$wpdb->insert(
			self::table(),
			array(
				'user_id'    => $uid,
				'action'     => sanitize_key( $action ),
				'object_id'  => (int) $object_id,
				'meta'       => $meta ? wp_json_encode( $meta ) : '',
				'ip'         => $ip,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/** "Last seen" timestamp for online-now. Written at most once a minute per user. */
	public static function touch_user() {
		if ( ! is_user_logged_in() || wp_doing_cron() ) {
			return;
		}
		$uid  = get_current_user_id();
		$last = (int) get_user_meta( $uid, 'mf_last_seen', true );
		if ( time() - $last > 60 ) {
			update_user_meta( $uid, 'mf_last_seen', time() );
		}
	}

	public static function on_login( $login, $user ) {
		if ( self::$skip_login ) {
			return;
		}
		self::log( 'login', 0, array(), $user->ID );
		update_user_meta( $user->ID, 'mf_last_seen', time() );
	}

	public static function on_logout( $user_id = 0 ) {
		self::log( 'logout', 0, array(), $user_id ? (int) $user_id : null );
	}

	public static function on_register( $user_id ) {
		self::log( 'register', 0, array(), $user_id );
	}

	public static function on_delete_user( $user_id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'user_id' => (int) $user_id ), array( '%d' ) );
	}

	/** Title views (members only) and searches (everyone), throttled. */
	public static function track_front() {
		if ( is_admin() || wp_doing_ajax() || is_feed() || is_robots() ) {
			return;
		}
		$uid = get_current_user_id();
		if ( $uid && is_singular( array( 'movie', 'series' ) ) ) {
			$key = 'mf_av_' . $uid . '_' . get_queried_object_id();
			if ( ! get_transient( $key ) ) {
				set_transient( $key, 1, 30 * MINUTE_IN_SECONDS );
				self::log( 'view', get_queried_object_id() );
			}
		}
		$term = isset( $_GET['s'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : ''; // phpcs:ignore
		if ( '' !== $term && ( is_search() || is_page( 'search' ) ) ) {
			$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$key = 'mf_as_' . md5( $uid . '|' . $ip . '|' . $term );
			if ( ! get_transient( $key ) ) {
				set_transient( $key, 1, MINUTE_IN_SECONDS );
				self::log( 'search', 0, array( 'term' => mb_substr( $term, 0, 80 ) ) );
			}
		}
	}

	/** Members can wipe their own activity. */
	public static function clear_mine() {
		if ( ! is_user_logged_in() || ! isset( $_POST['_mf'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_mf'] ) ), 'mf_clear_activity' ) ) {
			wp_safe_redirect( mf_redirect_url( mf_page_url( 'profile' ), 'bad_nonce' ) );
			exit;
		}
		global $wpdb;
		$wpdb->delete( self::table(), array( 'user_id' => get_current_user_id() ), array( '%d' ) );
		wp_safe_redirect( mf_redirect_url( add_query_arg( 'tab', 'activity', mf_page_url( 'profile' ) ), 'activity_cleared' ) );
		exit;
	}

	/** Retention. */
	public static function purge() {
		$days = (int) mf_opt( 'activity_days', 90 );
		if ( $days < 1 ) {
			return;
		}
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE created_at < %s', gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ) ); // phpcs:ignore
	}

	public static function privacy_text() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content( 'MovieFlix', wp_kses_post( '<p>' . __( 'When you are signed in, this site records activity such as logins, titles you view or watch, searches, list changes, ratings and reviews. Data is kept for the retention period set by the site owner and is deleted when your account is deleted. You can clear your own activity from your profile. If you sign in with Google, we receive your name, email address and profile picture from Google.', 'movieflix' ) . '</p>' ) );
		}
	}

	/** @return array Latest rows for one user (profile tab). */
	public static function user_recent( $user_id, $limit = 50 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE user_id=%d ORDER BY id DESC LIMIT %d', $user_id, $limit ) ); // phpcs:ignore
	}

	/** @return string Human description of a row's target/details (plain text + optional link). Returns array(text,url). */
	public static function detail( $row ) {
		$meta = $row->meta ? json_decode( $row->meta, true ) : array();
		$text = '';
		$url  = '';
		if ( $row->object_id && get_post( $row->object_id ) ) {
			$text = get_the_title( $row->object_id );
			$url  = get_permalink( $row->object_id );
		}
		if ( 'search' === $row->action && ! empty( $meta['term'] ) ) {
			$text = '“' . $meta['term'] . '”';
		}
		if ( 'rate' === $row->action && ! empty( $meta['rating'] ) ) {
			$text .= ( $text ? ' — ' : '' ) . (int) $meta['rating'] . '★';
		}
		return array( $text, $url );
	}
}
