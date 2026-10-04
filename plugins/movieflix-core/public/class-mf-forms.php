<?php
/**
 * Front-end form handlers (admin-post.php): login, register, profile, reviews.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Forms {

	public static function init() {
		add_action( 'admin_post_nopriv_mf_login', array( __CLASS__, 'login' ) );
		add_action( 'admin_post_mf_login', array( __CLASS__, 'login' ) );
		add_action( 'admin_post_nopriv_mf_register', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_mf_register', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_mf_profile', array( __CLASS__, 'profile' ) );
		add_action( 'admin_post_mf_review_save', array( __CLASS__, 'review_save' ) );
		add_action( 'admin_post_mf_review_delete', array( __CLASS__, 'review_delete' ) );
		add_action( 'admin_post_nopriv_mf_review_save', array( __CLASS__, 'review_need_login' ) );
		add_filter( 'pre_comment_approved', array( __CLASS__, 'review_approval' ), 10, 2 );
	}

	/** Redirect with a notice code. */
	private static function back( $url, $code ) {
		wp_safe_redirect( mf_redirect_url( $url, $code ) );
		exit;
	}

	/** Verify the form nonce or bounce back. */
	private static function check( $action, $fallback ) {
		if ( ! isset( $_POST['_mf'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_mf'] ) ), $action ) ) {
			self::back( $fallback, 'bad_nonce' );
		}
		if ( ! empty( $_POST['mf_website'] ) ) { // Honeypot.
			self::back( $fallback, 'bad_nonce' );
		}
	}

	private static function redirect_to( $default ) {
		$r = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
		return $r ? wp_validate_redirect( $r, $default ) : $default;
	}

	public static function login() {
		$login = mf_page_url( 'login' );
		self::check( 'mf_login', $login );
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'mf_login_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 10 ) {
			self::back( $login, 'login_failed' );
		}
		$user = wp_signon( array(
			'user_login'    => isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ) ) : '',
			'user_password' => isset( $_POST['user_password'] ) ? wp_unslash( $_POST['user_password'] ) : '', // phpcs:ignore -- passwords must not be altered.
			'remember'      => ! empty( $_POST['remember'] ),
		), is_ssl() );
		if ( is_wp_error( $user ) ) {
			set_transient( $key, $n + 1, 15 * MINUTE_IN_SECONDS );
			self::back( add_query_arg( 'redirect_to', rawurlencode( self::redirect_to( '' ) ), $login ), 'login_failed' );
		}
		delete_transient( $key );
		wp_safe_redirect( self::redirect_to( home_url( '/' ) ) );
		exit;
	}

	public static function register() {
		$reg = mf_page_url( 'register' );
		if ( ! mf_opt( 'enable_registration', 1 ) ) {
			self::back( $reg, 'reg_disabled' );
		}
		self::check( 'mf_register', $reg );
		$username = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ), true ) : '';
		$email    = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
		$pass     = isset( $_POST['user_password'] ) ? wp_unslash( $_POST['user_password'] ) : ''; // phpcs:ignore
		if ( strlen( $username ) < 3 || ! validate_username( $username ) ) {
			self::back( $reg, 'reg_user' );
		}
		if ( ! is_email( $email ) ) {
			self::back( $reg, 'reg_email' );
		}
		if ( strlen( $pass ) < 8 ) {
			self::back( $reg, 'reg_pass' );
		}
		if ( mf_opt( 'enable_age_gate', 1 ) ) {
			$age_ok = ! empty( $_POST['mf_age_confirm'] );
			if ( ! $age_ok ) {
				self::back( $reg, 'reg_age' );
			}
		}
		if ( mf_opt( 'enable_terms_check', 1 ) ) {
			if ( empty( $_POST['mf_terms'] ) ) {
				self::back( $reg, 'reg_terms' );
			}
		}
		if ( username_exists( $username ) || email_exists( $email ) ) {
			self::back( $reg, 'reg_exists' );
		}
		$uid = wp_insert_user( array(
			'user_login'   => $username,
			'user_email'   => $email,
			'user_pass'    => $pass,
			'display_name' => isset( $_POST['display_name'] ) && '' !== trim( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : $username,
			'role'         => 'subscriber',
		) );
		if ( is_wp_error( $uid ) ) {
			self::back( $reg, 'reg_failed' );
		}
		// Notify admin only (does not block registration if mail fails).
		wp_new_user_notification( $uid, null, 'admin' );

		// Always mark email as verified and log the user in immediately.
		// Email confirmation removed — outbound mail is not working on this site.
		update_user_meta( $uid, 'mf_email_verified', 1 );
		wp_set_current_user( $uid );
		wp_set_auth_cookie( $uid );
		wp_safe_redirect( add_query_arg( 'mf_msg', 'reg_ok', home_url( '/' ) ) );
		exit;
	}

	public static function profile() {
		$url = mf_page_url( 'profile' );
		if ( ! is_user_logged_in() ) {
			self::back( mf_page_url( 'login' ), 'login_required' );
		}
		self::check( 'mf_profile', add_query_arg( 'tab', 'settings', $url ) );
		$url  = add_query_arg( 'tab', 'settings', $url );
		$uid  = get_current_user_id();
		$data = array( 'ID' => $uid );
		if ( ! empty( $_POST['display_name'] ) ) {
			$data['display_name'] = sanitize_text_field( wp_unslash( $_POST['display_name'] ) );
		}
		if ( ! empty( $_POST['user_email'] ) ) {
			$email = sanitize_email( wp_unslash( $_POST['user_email'] ) );
			$owner = email_exists( $email );
			if ( ! is_email( $email ) || ( $owner && (int) $owner !== $uid ) ) {
				self::back( $url, 'profile_error' );
			}
			$data['user_email'] = $email;
		}
		if ( ! empty( $_POST['new_password'] ) ) {
			$np = wp_unslash( $_POST['new_password'] ); // phpcs:ignore
			if ( strlen( $np ) < 8 || ! isset( $_POST['confirm_password'] ) || $np !== wp_unslash( $_POST['confirm_password'] ) ) { // phpcs:ignore
				self::back( $url, 'reg_pass' );
			}
			$data['user_pass'] = $np;
		}
		$res = wp_update_user( $data );
		if ( is_wp_error( $res ) ) {
			self::back( $url, 'profile_error' );
		}
		if ( ! empty( $_FILES['mf_avatar']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			$mimes = array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' );
			$check = wp_check_filetype( sanitize_file_name( $_FILES['mf_avatar']['name'] ), $mimes ); // phpcs:ignore
			if ( ! $check['type'] || $_FILES['mf_avatar']['size'] > 2 * MB_IN_BYTES ) { // phpcs:ignore
				self::back( $url, 'profile_error' );
			}
			$att = media_handle_upload( 'mf_avatar', 0, array(), array( 'test_form' => false, 'mimes' => $mimes ) );
			if ( is_wp_error( $att ) ) {
				self::back( $url, 'profile_error' );
			}
			update_user_meta( $uid, 'mf_avatar', $att );
		}
		if ( ! empty( $data['user_pass'] ) ) { // wp_update_user logs the user out of other sessions; keep this one.
			wp_set_auth_cookie( $uid );
		}
		MF_Activity::log( 'profile_update' );
		self::back( $url, 'profile_saved' );
	}

	/** Review moderation rule. */
	public static function review_approval( $approved, $data ) {
		if ( isset( $data['comment_type'] ) && 'review' === $data['comment_type'] && ! is_wp_error( $approved ) ) {
			return ( mf_opt( 'moderate_reviews', 1 ) && ! current_user_can( 'moderate_comments' ) ) ? 0 : 1;
		}
		return $approved;
	}

	public static function review_need_login() {
		self::back( mf_page_url( 'login' ), 'login_required' );
	}

	public static function review_save() {
		$pid  = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$back = $pid ? get_permalink( $pid ) . '#reviews' : home_url( '/' );
		if ( ! is_user_logged_in() ) {
			self::back( mf_page_url( 'login' ), 'login_required' );
		}
		self::check( 'mf_review_' . $pid, $back );
		if ( ! mf_opt( 'enable_reviews', 1 ) || ! in_array( get_post_type( $pid ), mf_title_types(), true ) || 'publish' !== get_post_status( $pid ) ) {
			self::back( $back, 'review_error' );
		}
		$content = isset( $_POST['review_content'] ) ? trim( wp_unslash( $_POST['review_content'] ) ) : ''; // phpcs:ignore
		if ( strlen( $content ) < 3 ) {
			self::back( $back, 'review_empty' );
		}
		$content = mb_substr( $content, 0, 3000 );
		$user    = wp_get_current_user();
		$existing = get_comments( array( 'post_id' => $pid, 'user_id' => $user->ID, 'type' => 'review', 'status' => 'all', 'number' => 1 ) );
		$moder    = mf_opt( 'moderate_reviews', 1 ) && ! current_user_can( 'moderate_comments' );
		if ( $existing ) {
			$res = wp_update_comment( array( 'comment_ID' => $existing[0]->comment_ID, 'comment_content' => wp_kses_post( $content ), 'comment_approved' => $moder ? 0 : 1 ) );
			if ( ! $res && ! is_int( $res ) ) {
				self::back( $back, 'review_error' );
			}
			MF_Activity::log( 'review', $pid );
		self::back( $back, $moder ? 'review_pending' : 'review_saved' );
		}
		$id = wp_new_comment( array(
			'comment_post_ID'      => $pid,
			'comment_content'      => $content,
			'comment_type'         => 'review',
			'comment_parent'       => 0,
			'user_id'              => $user->ID,
			'comment_author'       => $user->display_name,
			'comment_author_email' => $user->user_email,
			'comment_author_url'   => '',
		), true );
		if ( is_wp_error( $id ) || ! $id ) {
			self::back( $back, 'review_error' );
		}
		MF_Activity::log( 'review', $pid );
		self::back( $back, $moder ? 'review_pending' : 'review_saved' );
	}

	public static function review_delete() {
		$cid = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
		$c   = $cid ? get_comment( $cid ) : null;
		if ( ! $c ) {
			self::back( home_url( '/' ), 'review_error' );
		}
		$back = get_permalink( $c->comment_post_ID ) . '#reviews';
		if ( ! is_user_logged_in() ) {
			self::back( mf_page_url( 'login' ), 'login_required' );
		}
		self::check( 'mf_review_del_' . $cid, $back );
		if ( 'review' !== $c->comment_type || ( (int) $c->user_id !== get_current_user_id() && ! current_user_can( 'moderate_comments' ) ) ) {
			self::back( $back, 'unauthorized' );
		}
		wp_delete_comment( $cid, true );
		self::back( isset( $_POST['redirect_to'] ) ? self::redirect_to( $back ) : $back, 'review_deleted' );
	}
}
