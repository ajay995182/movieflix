<?php
/**
 * MovieFlix extras: email verify, profiles, parental PIN, channels,
 * notifications, analytics helpers, ads slots, age/terms, soft video tokens.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Extras {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_channel' ), 2 );
		add_action( 'admin_post_nopriv_mf_verify_email', array( __CLASS__, 'verify_email' ) );
		add_action( 'admin_post_mf_verify_email', array( __CLASS__, 'verify_email' ) );
		add_action( 'admin_post_nopriv_mf_lostpass', array( __CLASS__, 'lostpass_request' ) );
		add_action( 'admin_post_nopriv_mf_resetpass', array( __CLASS__, 'resetpass' ) );
		add_action( 'admin_post_mf_save_profiles', array( __CLASS__, 'save_profiles' ) );
		add_action( 'admin_post_mf_switch_profile', array( __CLASS__, 'switch_profile' ) );
		add_action( 'admin_post_mf_set_pin', array( __CLASS__, 'set_pin' ) );
		add_action( 'admin_post_mf_check_pin', array( __CLASS__, 'check_pin' ) );
		add_action( 'wp_ajax_mf_trailer', array( __CLASS__, 'ajax_trailer' ) );
		add_action( 'wp_ajax_nopriv_mf_trailer', array( __CLASS__, 'ajax_trailer' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'kids_pre_get_posts' ), 30 );
		add_action( 'wp_ajax_mf_fav_channel', array( __CLASS__, 'ajax_fav_channel' ) );
		add_filter( 'body_class', array( __CLASS__, 'kids_body_class' ) );
		add_action( 'transition_post_status', array( __CLASS__, 'notify_new_content' ), 10, 3 );
		add_action( 'mf_send_digest', array( __CLASS__, 'send_digest' ) );
		add_filter( 'authenticate', array( __CLASS__, 'block_unverified' ), 30, 3 );
		add_action( 'init', array( __CLASS__, 'maybe_schedule_digest' ) );
		add_action( 'template_redirect', array( __CLASS__, 'template_redirect_lock' ), 5 );
		add_filter( 'manage_users_columns', array( __CLASS__, 'user_col' ) );
		add_filter( 'manage_users_custom_column', array( __CLASS__, 'user_col_val' ), 10, 3 );
		add_action( 'show_user_profile', array( __CLASS__, 'user_membership_field' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'user_membership_field' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'save_user_membership' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_user_membership' ) );
		add_action( 'admin_notices', array( __CLASS__, 'permalinks_notice' ) );

		add_action( 'rest_api_init', function () {
			register_rest_route( 'movieflix/v1', '/manifest', array(
				'methods'  => 'GET',
				'permission_callback' => '__return_true',
				'callback' => function () {
					$icon = mf_opt( 'favicon', '' );
					if ( ! $icon ) {
						$icon = get_site_icon_url( 192 );
					}
					$icons = array();
					if ( $icon ) {
						$icons[] = array( 'src' => $icon, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable' );
						$icons[] = array( 'src' => $icon, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable' );
					}
					return array(
						'name'             => get_bloginfo( 'name' ) ?: 'MovieFlix',
						'short_name'       => get_bloginfo( 'name' ) ?: 'MovieFlix',
						'start_url'        => home_url( '/?utm_source=pwa' ),
						'scope'            => home_url( '/' ),
						'display'          => 'standalone',
						'orientation'      => 'any',
						'background_color' => '#0a0e17',
						'theme_color'      => '#0a0e17',
						'description'      => get_bloginfo( 'description' ) ?: 'Stream movies, TV shows and live channels',
						'icons'            => $icons,
						'categories'       => array( 'entertainment', 'video' ),
					);
				},
			) );
		} );
	}

	/** Live TV / Channel CPT */
	public static function register_channel() {
		if ( ! mf_opt( 'enable_channels', 1 ) ) {
			return;
		}
		register_post_type( 'channel', array(
			'labels' => array(
				'name'          => __( 'Channels', 'movieflix' ),
				'singular_name' => __( 'Channel', 'movieflix' ),
				'add_new_item'  => __( 'Add Channel', 'movieflix' ),
				'edit_item'     => __( 'Edit Channel', 'movieflix' ),
				'all_items'     => __( 'Live TV / Channels', 'movieflix' ),
			),
			'public'       => true,
			'show_in_menu' => 'movieflix',
			'menu_icon'    => 'dashicons-desktop',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'has_archive'  => 'channels',
			'rewrite'      => array( 'slug' => 'channel', 'with_front' => false ),
			'show_in_rest' => true,
		) );
	}

	/* ---------- Email verification ---------- */
	public static function send_verification( $user_id ) {
		// Feature kept for future use, but disabled by default (mail not working).
		if ( ! mf_opt( 'enable_email_verify', 0 ) ) {
			return;
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		$token = wp_generate_password( 32, false );
		update_user_meta( $user_id, 'mf_email_token', $token );
		// Do NOT force mf_email_verified = 0 here — registration already logs users in.
		$url  = admin_url( 'admin-post.php?action=mf_verify_email&uid=' . $user_id . '&token=' . rawurlencode( $token ) );
		$subj = sprintf( '[%s] %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), __( 'Verify your email', 'movieflix' ) );
		$body = sprintf(
			__( "Hi %1\$s,\n\nPlease verify your email by opening this link:\n%2\$s\n\nIf you did not register, ignore this message.\n", 'movieflix' ),
			$user->display_name,
			$url
		);
		wp_mail( $user->user_email, $subj, $body );
	}

	public static function verify_email() {
		$uid   = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0;
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		$saved = $uid ? get_user_meta( $uid, 'mf_email_token', true ) : '';
		if ( ! $uid || ! $token || ! hash_equals( (string) $saved, $token ) ) {
			wp_safe_redirect( add_query_arg( 'mf_msg', 'verify_fail', mf_page_url( 'login' ) ) );
			exit;
		}
		update_user_meta( $uid, 'mf_email_verified', 1 );
		delete_user_meta( $uid, 'mf_email_token' );
		wp_safe_redirect( add_query_arg( 'mf_msg', 'verify_ok', mf_page_url( 'login' ) ) );
		exit;
	}

	public static function block_unverified( $user, $username, $password ) {
		// Email confirmation is disabled (outbound mail not working).
		// Always allow login so new and previously-unverified accounts can sign in.
		return $user;
	}

	/* ---------- Lost / reset password ---------- */
	public static function lostpass_request() {
		$login = mf_page_url( 'login' );
		$back  = mf_page_url( 'lost-password' );
		if ( empty( $_POST['_mf'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_mf'] ) ), 'mf_lostpass' ) ) {
			wp_safe_redirect( add_query_arg( 'mf_msg', 'bad_nonce', $back ) );
			exit;
		}
		$user_login = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';
		$user       = strpos( $user_login, '@' ) ? get_user_by( 'email', $user_login ) : get_user_by( 'login', $user_login );
		if ( ! $user ) {
			wp_safe_redirect( add_query_arg( 'mf_msg', 'lost_sent', $back ) ); // Don't reveal existence.
			exit;
		}
		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			wp_safe_redirect( add_query_arg( 'mf_msg', 'lost_fail', $back ) );
			exit;
		}
		$url  = add_query_arg( array( 'key' => $key, 'login' => rawurlencode( $user->user_login ) ), mf_page_url( 'reset-password' ) );
		$subj = sprintf( '[%s] %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), __( 'Reset your password', 'movieflix' ) );
		$body = sprintf( __( "Hi %1\$s,\n\nReset your password here:\n%2\$s\n\nIf you did not request this, ignore this email.\n", 'movieflix' ), $user->display_name, $url );
		wp_mail( $user->user_email, $subj, $body );
		wp_safe_redirect( add_query_arg( 'mf_msg', 'lost_sent', $back ) );
		exit;
	}

	public static function resetpass() {
		$back = mf_page_url( 'reset-password' );
		if ( empty( $_POST['_mf'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_mf'] ) ), 'mf_resetpass' ) ) {
			wp_safe_redirect( add_query_arg( 'mf_msg', 'bad_nonce', $back ) );
			exit;
		}
		$login = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
		$key   = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$pass  = isset( $_POST['pass1'] ) ? wp_unslash( $_POST['pass1'] ) : ''; // phpcs:ignore
		$pass2 = isset( $_POST['pass2'] ) ? wp_unslash( $_POST['pass2'] ) : ''; // phpcs:ignore
		$user  = check_password_reset_key( $key, $login );
		if ( is_wp_error( $user ) ) {
			wp_safe_redirect( add_query_arg( 'mf_msg', 'reset_invalid', $back ) );
			exit;
		}
		if ( strlen( $pass ) < 8 || $pass !== $pass2 ) {
			wp_safe_redirect( add_query_arg( array( 'mf_msg' => 'reg_pass', 'key' => $key, 'login' => $login ), $back ) );
			exit;
		}
		reset_password( $user, $pass );
		wp_safe_redirect( add_query_arg( 'mf_msg', 'reset_ok', mf_page_url( 'login' ) ) );
		exit;
	}

	/* ---------- Profiles (multi-profile per account) ---------- */
	public static function get_profiles( $user_id ) {
		$p = get_user_meta( $user_id, 'mf_profiles', true );
		if ( ! is_array( $p ) || ! $p ) {
			$user = get_userdata( $user_id );
			$p    = array(
				array(
					'id'   => 'default',
					'name' => $user ? $user->display_name : __( 'Main', 'movieflix' ),
					'kids' => 0,
					'avatar' => '',
				),
			);
		}
		return $p;
	}

	public static function active_profile_id( $user_id = 0 ) {
		$user_id = $user_id ? $user_id : get_current_user_id();
		$id      = isset( $_COOKIE['mf_profile'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['mf_profile'] ) ) : 'default';
		$profiles = self::get_profiles( $user_id );
		foreach ( $profiles as $pr ) {
			if ( $pr['id'] === $id ) {
				return $id;
			}
		}
		return 'default';
	}

	/** Whether the active profile is a Kids profile. */
	public static function is_kids_profile( $user_id = 0 ) {
		$user_id  = $user_id ? $user_id : get_current_user_id();
		if ( ! $user_id || ! mf_opt( 'enable_profiles', 1 ) ) {
			return false;
		}
		$active   = self::active_profile_id( $user_id );
		$profiles = self::get_profiles( $user_id );
		foreach ( $profiles as $pr ) {
			if ( isset( $pr['id'] ) && $pr['id'] === $active ) {
				return ! empty( $pr['kids'] );
			}
		}
		return false;
	}

	/** Content ratings allowed in Kids mode. */
	public static function kids_allowed_ratings() {
		return array( 'G', 'PG', 'TV-Y', 'TV-Y7', 'TV-G', 'TV-PG' );
	}

	/** Whether a title is safe for kids mode. */
	public static function title_is_kids_safe( $post_id ) {
		$age = strtoupper( (string) mf_meta( $post_id, 'age_rating', '' ) );
		$terms = get_the_terms( $post_id, 'content_rating' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$age = strtoupper( $terms[0]->name );
		}
		if ( $age && in_array( $age, self::kids_allowed_ratings(), true ) ) {
			return true;
		}
		// Genre Kids/Family/Animation without mature rating → allow.
		$genres = wp_get_object_terms( $post_id, 'genre', array( 'fields' => 'names' ) );
		if ( $genres && ! is_wp_error( $genres ) ) {
			foreach ( $genres as $g ) {
				if ( in_array( $g, array( 'Kids', 'Family', 'Animation' ), true ) ) {
					if ( ! $age || ! in_array( $age, array( 'R', 'TV-MA', 'NC-17' ), true ) ) {
						return true;
					}
				}
			}
		}
		// Explicit kids flag on post.
		if ( '1' === (string) get_post_meta( $post_id, '_mf_kids', true ) ) {
			return true;
		}
		return false;
	}

	public static function save_profiles() {
		if ( ! is_user_logged_in() || empty( $_POST['_mf'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_mf'] ) ), 'mf_profiles' ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}
		$uid  = get_current_user_id();
		$raw  = isset( $_POST['profiles'] ) ? (array) wp_unslash( $_POST['profiles'] ) : array(); // phpcs:ignore
		$out  = array();
		$max  = 5;
		foreach ( $raw as $i => $row ) {
			if ( count( $out ) >= $max ) {
				break;
			}
			$name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
			if ( '' === $name ) {
				continue;
			}
			$id = isset( $row['id'] ) && $row['id'] ? sanitize_key( $row['id'] ) : 'p' . wp_generate_password( 6, false );
			$out[] = array(
				'id'     => $id,
				'name'   => $name,
				'kids'   => ! empty( $row['kids'] ) ? 1 : 0,
				'avatar' => isset( $row['avatar'] ) ? esc_url_raw( $row['avatar'] ) : '',
			);
		}
		if ( ! $out ) {
			$out = self::get_profiles( $uid );
		}
		update_user_meta( $uid, 'mf_profiles', $out );
		wp_safe_redirect( add_query_arg( 'mf_msg', 'profiles_saved', mf_page_url( 'profiles' ) ) );
		exit;
	}

	public static function switch_profile() {
		if ( ! is_user_logged_in() || empty( $_POST['_mf'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_mf'] ) ), 'mf_switch_profile' ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}
		$id = isset( $_POST['profile_id'] ) ? sanitize_key( wp_unslash( $_POST['profile_id'] ) ) : 'default';
		setcookie( 'mf_profile', $id, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	/* ---------- Parental PIN ---------- */
	public static function set_pin() {
		if ( ! is_user_logged_in() || empty( $_POST['_mf'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_mf'] ) ), 'mf_set_pin' ) ) {
			wp_safe_redirect( mf_page_url( 'profile' ) );
			exit;
		}
		$pin = isset( $_POST['pin'] ) ? preg_replace( '/\D/', '', wp_unslash( $_POST['pin'] ) ) : ''; // phpcs:ignore
		if ( strlen( $pin ) < 4 || strlen( $pin ) > 8 ) {
			wp_safe_redirect( add_query_arg( 'mf_msg', 'pin_invalid', mf_page_url( 'profile' ) ) );
			exit;
		}
		update_user_meta( get_current_user_id(), 'mf_parental_pin', wp_hash_password( $pin ) );
		wp_safe_redirect( add_query_arg( 'mf_msg', 'pin_saved', mf_page_url( 'profile' ) ) );
		exit;
	}

	public static function check_pin() {
		if ( ! is_user_logged_in() || empty( $_POST['_mf'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_mf'] ) ), 'mf_check_pin' ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}
		$pin  = isset( $_POST['pin'] ) ? preg_replace( '/\D/', '', wp_unslash( $_POST['pin'] ) ) : ''; // phpcs:ignore
		$user_id = get_current_user_id();
		$hash    = (string) get_user_meta( $user_id, 'mf_parental_pin', true );
		$redir = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );
		if ( $hash && wp_check_password( $pin, $hash ) ) {
			$expires = time() + HOUR_IN_SECONDS;
			$payload = $user_id . '.' . $expires;
			$token   = hash_hmac( 'sha256', $payload . '|' . $hash, wp_salt( 'auth' ) );
			setcookie( 'mf_pin_ok', $payload . '.' . $token, $expires, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			wp_safe_redirect( $redir );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'mf_msg', 'pin_wrong', $redir ) );
		exit;
	}

	public static function needs_pin_for_title( $post_id ) {
		if ( ! mf_opt( 'enable_parental', 1 ) || ! is_user_logged_in() ) {
			return false;
		}
		$ratings = get_the_terms( $post_id, 'content_rating' );
		if ( ! $ratings || is_wp_error( $ratings ) ) {
			return false;
		}
		$adult = array( 'R', 'TV-MA', 'NC-17' );
		foreach ( $ratings as $t ) {
			if ( in_array( strtoupper( $t->name ), $adult, true ) ) {
				$cookie = isset( $_COOKIE['mf_pin_ok'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['mf_pin_ok'] ) ) : '';
				$parts  = explode( '.', $cookie );
				if ( 3 !== count( $parts ) ) {
					return true;
				}

				$cookie_user = absint( $parts[0] );
				$expires     = absint( $parts[1] );
				$user_id     = get_current_user_id();
				$hash        = (string) get_user_meta( $user_id, 'mf_parental_pin', true );
				$payload     = $cookie_user . '.' . $expires;
				$expected    = hash_hmac( 'sha256', $payload . '|' . $hash, wp_salt( 'auth' ) );

				return ! $hash
					|| $cookie_user !== $user_id
					|| $expires < time()
					|| ! hash_equals( $expected, $parts[2] );
			}
		}
		return false;
	}

	/* ---------- Trailer AJAX ---------- */
	public static function ajax_trailer() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error();
		}
		$url = mf_meta( $id, 'trailer_url', '' );
		wp_send_json_success( array(
			'title'   => get_the_title( $id ),
			'trailer' => $url,
			'poster'  => mf_img( $id, 'backdrop', 'large' ),
		) );
	}

	/* ---------- New content email notifications ---------- */
	public static function notify_new_content( $new, $old, $post ) {
		if ( 'publish' !== $new || 'publish' === $old ) {
			return;
		}
		if ( ! in_array( $post->post_type, array( 'movie', 'series', 'episode', 'channel' ), true ) ) {
			return;
		}
		if ( ! mf_opt( 'enable_notify_email', 0 ) ) {
			return;
		}
		// Queue for digest instead of spamming every publish.
		$q = get_option( 'mf_notify_queue', array() );
		$q[] = $post->ID;
		update_option( 'mf_notify_queue', array_slice( array_unique( $q ), -50 ), false );
	}

	public static function maybe_schedule_digest() {
		if ( ! wp_next_scheduled( 'mf_send_digest' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mf_send_digest' );
		}
	}

	public static function send_digest() {
		if ( ! mf_opt( 'enable_notify_email', 0 ) ) {
			return;
		}
		$ids = get_option( 'mf_notify_queue', array() );
		if ( ! $ids ) {
			return;
		}
		delete_option( 'mf_notify_queue' );
		$lines = array();
		foreach ( $ids as $id ) {
			if ( 'publish' === get_post_status( $id ) ) {
				$lines[] = '• ' . get_the_title( $id ) . ' — ' . get_permalink( $id );
			}
		}
		if ( ! $lines ) {
			return;
		}
		$subj = sprintf( '[%s] %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), __( 'New titles available', 'movieflix' ) );
		$body = __( "New content on the platform:\n\n", 'movieflix' ) . implode( "\n", $lines ) . "\n";
		$users = get_users( array( 'fields' => array( 'user_email' ), 'number' => 500 ) );
		foreach ( $users as $u ) {
			if ( ! empty( $u->user_email ) ) {
				wp_mail( $u->user_email, $subj, $body );
			}
		}
	}

	/* ---------- Analytics helpers ---------- */
	public static function analytics_summary( $days = 30 ) {
		global $wpdb;
		// Table is created as {prefix}movieflix_activity by MF_Install.
		$t = $wpdb->prefix . 'movieflix_activity';
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ); // phpcs:ignore
		if ( $exists !== $t ) {
			// Fallback for older installs that used mf_activity.
			$t2 = $wpdb->prefix . 'mf_activity';
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t2 ) ) === $t2 ) { // phpcs:ignore
				$t = $t2;
			} else {
				return array();
			}
		}
		$since = gmdate( 'Y-m-d H:i:s', time() - max( 1, (int) $days ) * DAY_IN_SECONDS );
		// Map UI keys → actual logged action names.
		$map = array(
			'view'         => 'view',
			'play'         => 'watch_start',
			'login'        => 'login',
			'google_login' => 'google_login',
			'register'     => 'register',
			'list_add'      => 'list_add',
			'rate'         => 'rate',
			'search'       => 'search',
		);
		$out = array();
		foreach ( $map as $ui => $action ) {
			$out[ $ui ] = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM `$t` WHERE action = %s AND created_at >= %s",
				$action,
				$since
			) ); // phpcs:ignore
		}
		// Also count watch_complete under plays for a fuller picture.
		$complete = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `$t` WHERE action = %s AND created_at >= %s",
			'watch_complete',
			$since
		) ); // phpcs:ignore
		$out['play'] += $complete;

		$out['top_titles'] = $wpdb->get_results( $wpdb->prepare(
			"SELECT object_id, COUNT(*) AS c FROM `$t`
			 WHERE action IN ('view','watch_start','watch_complete') AND object_id > 0 AND created_at >= %s
			 GROUP BY object_id ORDER BY c DESC LIMIT 15",
			$since
		), ARRAY_A ); // phpcs:ignore
		$out['days'] = (int) $days;
		return $out;
	}

	/* ---------- Soft signed video URL (not real DRM) ---------- */
	public static function signed_video_url( $url, $ttl = 3600 ) {
		if ( ! mf_opt( 'enable_signed_urls', 0 ) || ! $url ) {
			return $url;
		}
		$exp = time() + max( 300, (int) $ttl );
		$sig = hash_hmac( 'sha256', $url . '|' . $exp, wp_salt( 'auth' ) );
		return add_query_arg( array( 'mf_exp' => $exp, 'mf_sig' => $sig ), $url );
	}

	/** Lock watch + title pages for guests when enabled. */
	public static function template_redirect_lock() {
		if ( is_user_logged_in() || is_admin() ) {
			return;
		}
		$login = mf_page_url( 'login' );
		$here  = ( is_ssl() ? 'https://' : 'http://' ) . ( $_SERVER['HTTP_HOST'] ?? '' ) . ( $_SERVER['REQUEST_URI'] ?? '' ); // phpcs:ignore
		$redir = add_query_arg( array( 'mf_msg' => 'login_required', 'redirect_to' => rawurlencode( $here ) ), $login );

		if ( mf_opt( 'force_login_watch', 1 ) && get_query_var( 'mf_watch' ) ) {
			wp_safe_redirect( $redir );
			exit;
		}
		if ( mf_opt( 'force_login_titles', 1 ) && ( is_singular( 'movie' ) || is_singular( 'series' ) || is_singular( 'episode' ) ) ) {
			wp_safe_redirect( $redir );
			exit;
		}
		// Membership gate for members_only when membership mode on
		if ( mf_opt( 'enable_membership', 0 ) && ( is_singular( array( 'movie', 'series', 'episode' ) ) || get_query_var( 'mf_watch' ) ) ) {
			// guests already redirected above if force login; members checked in theme/player
		}
	}

	public static function user_is_member( $user_id = 0 ) {
		$user_id = $user_id ? $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( user_can( $user_id, 'edit_posts' ) ) {
			return true;
		}
		return (bool) get_user_meta( $user_id, 'mf_is_member', true );
	}

	public static function user_col( $cols ) {
		$cols['mf_member'] = __( 'Membership', 'movieflix' );
		return $cols;
	}

	public static function user_col_val( $val, $col, $user_id ) {
		if ( 'mf_member' === $col ) {
			return get_user_meta( $user_id, 'mf_is_member', true ) ? '★ ' . esc_html( mf_opt( 'membership_label', 'Premium' ) ) : '—';
		}
		return $val;
	}

	public static function user_membership_field( $user ) {
		if ( ! current_user_can( 'edit_users' ) || ! mf_opt( 'enable_membership', 0 ) ) {
			return;
		}
		$on = (bool) get_user_meta( $user->ID, 'mf_is_member', true );
		echo '<h2>' . esc_html__( 'MovieFlix Membership', 'movieflix' ) . '</h2><table class="form-table"><tr><th>' . esc_html( mf_opt( 'membership_label', 'Premium' ) ) . '</th><td>';
		echo '<label><input type="checkbox" name="mf_is_member" value="1" ' . checked( $on, true, false ) . '> ' . esc_html__( 'Active member (can watch members-only titles)', 'movieflix' ) . '</label>';
		echo '</td></tr></table>';
	}

	public static function save_user_membership( $user_id ) {
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}
		update_user_meta( $user_id, 'mf_is_member', empty( $_POST['mf_is_member'] ) ? 0 : 1 );
	}

	public static function permalinks_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$struct = get_option( 'permalink_structure' );
		if ( $struct ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>MovieFlix:</strong> ' . esc_html__( 'Please set Permalinks to “Post name” (Settings → Permalinks) or movie/series/watch URLs will not work.', 'movieflix' ) . ' <a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . esc_html__( 'Open Permalinks', 'movieflix' ) . '</a></p></div>';
	}

	/** Restrict main queries to kids-safe titles when Kids profile is active. */
	public static function kids_pre_get_posts( $q ) {
		if ( is_admin() || ! $q->is_main_query() || ! self::is_kids_profile() ) {
			return;
		}
		if ( ! ( $q->is_home() || $q->is_front_page() || $q->is_search() || $q->is_post_type_archive( array( 'movie', 'series' ) ) || $q->is_tax() ) ) {
			return;
		}
		$allowed = self::kids_allowed_ratings();
		$tax_q   = $q->get( 'tax_query' );
		if ( ! is_array( $tax_q ) ) {
			$tax_q = array();
		}
		$kids_filter = array(
			'relation' => 'OR',
			array(
				'taxonomy' => 'content_rating',
				'field'    => 'name',
				'terms'    => $allowed,
			),
			array(
				'taxonomy' => 'genre',
				'field'    => 'name',
				'terms'    => array( 'Kids', 'Family', 'Animation' ),
			),
		);
		$tax_q[] = $kids_filter;
		if ( ! isset( $tax_q['relation'] ) ) {
			$tax_q['relation'] = 'AND';
		}
		$q->set( 'tax_query', $tax_q );
	}

	public static function kids_body_class( $classes ) {
		if ( self::is_kids_profile() ) {
			$classes[] = 'mf-kids-mode';
		}
		return $classes;
	}

	/** Toggle favourite live channel for current user. */
	public static function ajax_fav_channel() {
		check_ajax_referer( 'mf_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'login' ), 401 );
		}
		$cid = isset( $_POST['channel_id'] ) ? absint( $_POST['channel_id'] ) : 0;
		if ( ! $cid || 'channel' !== get_post_type( $cid ) ) {
			wp_send_json_error( array( 'message' => 'invalid' ), 400 );
		}
		$uid  = get_current_user_id();
		$favs = array_map( 'intval', (array) get_user_meta( $uid, 'mf_fav_channels', true ) );
		$favs = array_values( array_filter( $favs ) );
		if ( in_array( $cid, $favs, true ) ) {
			$favs = array_values( array_diff( $favs, array( $cid ) ) );
			$on   = false;
		} else {
			$favs[] = $cid;
			$on     = true;
		}
		update_user_meta( $uid, 'mf_fav_channels', $favs );
		wp_send_json_success( array( 'favourited' => $on, 'ids' => $favs ) );
	}

	/** @return int[] Favourite channel IDs. */
	public static function fav_channel_ids( $user_id = 0 ) {
		$user_id = $user_id ? $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return array();
		}
		return array_values( array_filter( array_map( 'intval', (array) get_user_meta( $user_id, 'mf_fav_channels', true ) ) ) );
	}


}
