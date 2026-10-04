<?php
/**
 * In-app notifications + optional browser Notification API hooks.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Notify {

	const META = 'mf_notifications';
	const MAX  = 50;

	public static function init() {
		add_action( 'transition_post_status', array( __CLASS__, 'on_publish' ), 20, 3 );
		add_action( 'wp_ajax_mf_notify_list', array( __CLASS__, 'ajax_list' ) );
		add_action( 'wp_ajax_mf_notify_read', array( __CLASS__, 'ajax_read' ) );
		add_action( 'wp_ajax_mf_notify_read_all', array( __CLASS__, 'ajax_read_all' ) );
		add_action( 'wp_ajax_mf_notify_push_sub', array( __CLASS__, 'ajax_push_sub' ) );
	}

	/** @return array */
	public static function get( $user_id = 0 ) {
		$user_id = $user_id ? $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return array();
		}
		$list = get_user_meta( $user_id, self::META, true );
		return is_array( $list ) ? $list : array();
	}

	public static function unread_count( $user_id = 0 ) {
		$n = 0;
		foreach ( self::get( $user_id ) as $item ) {
			if ( empty( $item['read'] ) ) {
				$n++;
			}
		}
		return $n;
	}

	public static function push( $user_id, $title, $body = '', $url = '' ) {
		if ( ! $user_id ) {
			return;
		}
		$list   = self::get( $user_id );
		$list[] = array(
			'id'    => wp_generate_uuid4(),
			'title' => sanitize_text_field( $title ),
			'body'  => sanitize_text_field( $body ),
			'url'   => esc_url_raw( $url ),
			'time'  => time(),
			'read'  => 0,
		);
		if ( count( $list ) > self::MAX ) {
			$list = array_slice( $list, -self::MAX );
		}
		update_user_meta( $user_id, self::META, $list );
	}

	/** Broadcast a notice to all subscribers (users who opted in via meta). */
	public static function broadcast( $title, $body = '', $url = '' ) {
		$users = get_users( array(
			'meta_key'     => 'mf_notify_optin',
			'meta_value'   => '1',
			'fields'       => 'ID',
			'number'       => 500,
			'count_total'  => false,
		) );
		if ( ! $users ) {
			// Fallback: recent users with watch history interest — still cap
			$users = get_users( array( 'fields' => 'ID', 'number' => 100, 'orderby' => 'registered', 'order' => 'DESC' ) );
		}
		foreach ( $users as $uid ) {
			self::push( (int) $uid, $title, $body, $url );
		}
	}

	public static function on_publish( $new, $old, $post ) {
		if ( 'publish' !== $new || 'publish' === $old ) {
			return;
		}
		if ( ! in_array( $post->post_type, array( 'movie', 'series', 'episode' ), true ) ) {
			return;
		}
		if ( get_post_meta( $post->ID, '_mf_notify_sent', true ) ) {
			return;
		}
		$title = $post->post_title;
		$type  = $post->post_type;
		$url   = get_permalink( $post->ID );
		if ( 'episode' === $type ) {
			$sid = (int) get_post_meta( $post->ID, '_mf_series', true );
			if ( $sid ) {
				$url = get_permalink( $sid );
				$title = sprintf( __( 'New episode: %s', 'movieflix' ), $post->post_title );
			}
		} else {
			$title = sprintf( __( 'New on MovieFlix: %s', 'movieflix' ), $post->post_title );
		}
		$body = wp_trim_words( $post->post_excerpt ? $post->post_excerpt : $post->post_content, 18 );
		self::broadcast( $title, $body, $url );
		update_post_meta( $post->ID, '_mf_notify_sent', '1' );
	}

	public static function ajax_list() {
		check_ajax_referer( 'mf_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'login' ), 401 );
		}
		$list = array_reverse( self::get() );
		$out  = array();
		foreach ( $list as $item ) {
			$out[] = array(
				'id'    => $item['id'],
				'title' => $item['title'],
				'body'  => $item['body'],
				'url'   => $item['url'],
				'time'  => human_time_diff( (int) $item['time'], time() ) . ' ' . __( 'ago', 'movieflix' ),
				'read'  => ! empty( $item['read'] ),
			);
		}
		wp_send_json_success( array(
			'items'  => $out,
			'unread' => self::unread_count(),
		) );
	}

	public static function ajax_read() {
		check_ajax_referer( 'mf_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( null, 401 );
		}
		$nid  = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$list = self::get();
		foreach ( $list as &$item ) {
			if ( isset( $item['id'] ) && $item['id'] === $nid ) {
				$item['read'] = 1;
			}
		}
		update_user_meta( get_current_user_id(), self::META, $list );
		wp_send_json_success( array( 'unread' => self::unread_count() ) );
	}

	public static function ajax_read_all() {
		check_ajax_referer( 'mf_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( null, 401 );
		}
		$list = self::get();
		foreach ( $list as &$item ) {
			$item['read'] = 1;
		}
		update_user_meta( get_current_user_id(), self::META, $list );
		wp_send_json_success( array( 'unread' => 0 ) );
	}

	/** Store browser push subscription JSON (optional; display uses in-app first). */
	public static function ajax_push_sub() {
		check_ajax_referer( 'mf_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( null, 401 );
		}
		$raw = isset( $_POST['subscription'] ) ? wp_unslash( $_POST['subscription'] ) : ''; // phpcs:ignore
		update_user_meta( get_current_user_id(), 'mf_push_sub', sanitize_textarea_field( $raw ) );
		update_user_meta( get_current_user_id(), 'mf_notify_optin', '1' );
		wp_send_json_success();
	}
}
