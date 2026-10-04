<?php
/**
 * "Continue with Google" using the OAuth 2.0 authorization-code flow.
 * No libraries needed. Requires a Google Cloud OAuth client (see documentation).
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Google {

	public static function init() {
		foreach ( array( 'start', 'callback' ) as $a ) {
			add_action( 'admin_post_nopriv_mf_google_' . $a, array( __CLASS__, $a ) );
			add_action( 'admin_post_mf_google_' . $a, array( __CLASS__, $a ) );
		}
		add_filter( 'get_avatar_url', array( __CLASS__, 'avatar' ), 20, 3 );
	}

	public static function enabled() {
		return (bool) mf_opt( 'enable_google', 0 ) && mf_opt( 'google_client_id', '' ) && mf_opt( 'google_client_secret', '' );
	}

	/** The exact URI that must be added in Google Cloud → Authorized redirect URIs. */
	public static function redirect_uri() {
		return admin_url( 'admin-post.php?action=mf_google_callback' );
	}

	public static function start_url( $redirect = '' ) {
		$url = admin_url( 'admin-post.php?action=mf_google_start' );
		return $redirect ? $url . '&redirect_to=' . rawurlencode( $redirect ) : $url;
	}

	private static function fail( $code ) {
		wp_safe_redirect( mf_redirect_url( mf_page_url( 'login' ), $code ) );
		exit;
	}

	public static function start() {
		if ( ! self::enabled() ) {
			self::fail( 'google_disabled' );
		}
		$redir = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : ''; // phpcs:ignore
		$redir = $redir ? wp_validate_redirect( $redir, '' ) : '';
		$state = wp_generate_password( 32, false, false );
		set_transient( 'mf_gs_' . $state, array( 'redirect' => $redir ), 10 * MINUTE_IN_SECONDS );
		setcookie( 'mf_gstate', hash( 'sha256', $state . wp_salt() ), time() + 600, '/', '', is_ssl(), true );
		$url = add_query_arg(
			array(
				'client_id'     => mf_opt( 'google_client_id' ),
				'redirect_uri'  => self::redirect_uri(),
				'response_type' => 'code',
				'scope'         => 'openid email profile',
				'state'         => $state,
				'prompt'        => 'select_account',
			),
			'https://accounts.google.com/o/oauth2/v2/auth'
		);
		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect -- fixed Google host.
		exit;
	}

	public static function callback() {
		if ( ! self::enabled() ) {
			self::fail( 'google_disabled' );
		}
		if ( isset( $_GET['error'] ) || empty( $_GET['code'] ) || empty( $_GET['state'] ) ) { // phpcs:ignore
			self::fail( 'google_failed' );
		}
		$state  = sanitize_text_field( wp_unslash( $_GET['state'] ) ); // phpcs:ignore
		$code   = sanitize_text_field( wp_unslash( $_GET['code'] ) ); // phpcs:ignore
		$data   = get_transient( 'mf_gs_' . $state );
		$cookie = isset( $_COOKIE['mf_gstate'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['mf_gstate'] ) ) : '';
		delete_transient( 'mf_gs_' . $state );
		setcookie( 'mf_gstate', '', time() - 3600, '/', '', is_ssl(), true );
		if ( ! $data || ! hash_equals( hash( 'sha256', $state . wp_salt() ), $cookie ) ) {
			self::fail( 'google_state' );
		}
		$tok = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
			'timeout' => 15,
			'body'    => array(
				'code'          => $code,
				'client_id'     => mf_opt( 'google_client_id' ),
				'client_secret' => mf_opt( 'google_client_secret' ),
				'redirect_uri'  => self::redirect_uri(),
				'grant_type'    => 'authorization_code',
			),
		) );
		if ( is_wp_error( $tok ) || 200 !== (int) wp_remote_retrieve_response_code( $tok ) ) {
			self::fail( 'google_failed' );
		}
		$access = json_decode( wp_remote_retrieve_body( $tok ), true );
		if ( empty( $access['access_token'] ) ) {
			self::fail( 'google_failed' );
		}
		$res = wp_remote_get( 'https://openidconnect.googleapis.com/v1/userinfo', array(
			'timeout' => 15,
			'headers' => array( 'Authorization' => 'Bearer ' . $access['access_token'] ),
		) );
		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			self::fail( 'google_failed' );
		}
		$info  = json_decode( wp_remote_retrieve_body( $res ), true );
		$sub   = isset( $info['sub'] ) ? sanitize_text_field( $info['sub'] ) : '';
		$email = isset( $info['email'] ) ? sanitize_email( $info['email'] ) : '';
		$ok    = isset( $info['email_verified'] ) && ( true === $info['email_verified'] || 'true' === $info['email_verified'] );
		if ( '' === $sub || ! is_email( $email ) || ! $ok ) {
			self::fail( 'google_unverified' );
		}
		$name = isset( $info['name'] ) ? sanitize_text_field( $info['name'] ) : '';
		$pic  = isset( $info['picture'] ) ? esc_url_raw( $info['picture'] ) : '';

		$found = get_users( array( 'meta_key' => 'mf_google_sub', 'meta_value' => $sub, 'number' => 1 ) ); // phpcs:ignore
		$user  = $found ? $found[0] : get_user_by( 'email', $email );
		$new   = false;
		if ( $user ) {
			if ( user_can( $user, 'edit_posts' ) ) { // Never sign staff in through an email match.
				self::fail( 'google_privileged' );
			}
		} else {
			if ( ! mf_opt( 'enable_registration', 1 ) ) {
				self::fail( 'reg_disabled' );
			}
			$base = sanitize_user( strstr( $email, '@', true ), true );
			$base = $base ? $base : 'user';
			$login = $base;
			$i     = 1;
			while ( username_exists( $login ) ) {
				$login = $base . $i++;
			}
			$uid = wp_insert_user( array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 32 ),
				'display_name' => $name ? $name : $login,
				'role'         => 'subscriber',
			) );
			if ( is_wp_error( $uid ) ) {
				self::fail( 'reg_failed' );
			}
			$user = get_user_by( 'id', $uid );
			$new  = true;
		}
		update_user_meta( $user->ID, 'mf_google_sub', $sub );
		if ( $pic ) {
			update_user_meta( $user->ID, 'mf_google_picture', $pic );
		}
		update_user_meta( $user->ID, 'mf_email_verified', 1 ); // Google already verified the email.
		update_user_meta( $user->ID, 'mf_last_seen', time() );
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );
		MF_Activity::$skip_login = true;
		do_action( 'wp_login', $user->user_login, $user );
		MF_Activity::$skip_login = false;
		if ( $new ) {
			MF_Activity::log( 'register', 0, array( 'via' => 'google' ), $user->ID );
		}
		MF_Activity::log( 'google_login', 0, array(), $user->ID );
		$to = ! empty( $data['redirect'] ) ? $data['redirect'] : home_url( '/' );
		wp_safe_redirect( $to );
		exit;
	}

	/** Use the Google profile picture when the member has no uploaded avatar. */
	public static function avatar( $url, $id_or_email, $args ) {
		$uid = 0;
		if ( is_numeric( $id_or_email ) ) {
			$uid = (int) $id_or_email;
		} elseif ( $id_or_email instanceof WP_User ) {
			$uid = $id_or_email->ID;
		} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$u   = get_user_by( 'email', $id_or_email );
			$uid = $u ? $u->ID : 0;
		}
		if ( $uid && ! get_user_meta( $uid, 'mf_avatar', true ) ) {
			$pic = get_user_meta( $uid, 'mf_google_picture', true );
			if ( $pic ) {
				return $pic;
			}
		}
		return $url;
	}
}
