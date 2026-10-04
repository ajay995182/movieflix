<?php
/**
 * AJAX handlers: watchlist, progress, ratings, view events.
 * Every handler verifies a nonce; write handlers require login.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Ajax {

	public static function init() {
		foreach ( array( 'toggle_list', 'progress', 'remove_progress', 'rate', 'clear_history' ) as $a ) {
			add_action( 'wp_ajax_mf_' . $a, array( __CLASS__, $a ) );
		}
		add_action( 'wp_ajax_mf_event', array( __CLASS__, 'event' ) );
		add_action( 'wp_ajax_nopriv_mf_event', array( __CLASS__, 'event' ) );
		foreach ( array( 'toggle_list', 'progress', 'remove_progress', 'rate', 'clear_history' ) as $a ) {
			add_action( 'wp_ajax_nopriv_mf_' . $a, array( __CLASS__, 'need_login' ) );
		}
	}

	public static function need_login() {
		wp_send_json_error( array( 'code' => 'login_required', 'message' => __( 'Please log in to continue.', 'movieflix' ), 'login' => mf_page_url( 'login' ) ), 401 );
	}

	/** Verify nonce, return validated post ID. */
	private static function post_id( $types = array( 'movie', 'series', 'episode' ) ) {
		check_ajax_referer( 'mf_nonce', 'nonce' );
		$id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0; // phpcs:ignore
		if ( ! $id || 'publish' !== get_post_status( $id ) || ! in_array( get_post_type( $id ), $types, true ) ) {
			wp_send_json_error( array( 'message' => __( 'That title is unavailable.', 'movieflix' ) ), 404 );
		}
		return $id;
	}

	public static function toggle_list() {
		global $wpdb;
		$id = self::post_id( array( 'movie', 'series' ) );
		if ( ! mf_opt( 'enable_watchlist', 1 ) ) {
			wp_send_json_error( array( 'message' => __( 'My List is disabled.', 'movieflix' ) ), 403 );
		}
		$uid = get_current_user_id();
		$t   = $wpdb->prefix . 'movieflix_watchlist';
		$has = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE user_id=%d AND movie_id=%d", $uid, $id ) );
		if ( $has ) {
			$wpdb->delete( $t, array( 'user_id' => $uid, 'movie_id' => $id ), array( '%d', '%d' ) );
			MF_Activity::log( 'list_remove', $id );
			wp_send_json_success( array( 'in_list' => false, 'message' => __( 'Removed from My List', 'movieflix' ) ) );
		}
		$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $t (user_id,movie_id,added_at) VALUES (%d,%d,%s)", $uid, $id, current_time( 'mysql', true ) ) );
		delete_transient( 'mf_trending_auto' );
		MF_Activity::log( 'list_add', $id );
		wp_send_json_success( array( 'in_list' => true, 'message' => __( 'Added to My List', 'movieflix' ) ) );
	}

	public static function progress() {
		global $wpdb;
		$id = self::post_id( array( 'movie', 'episode' ) );
		if ( ! mf_opt( 'enable_continue', 1 ) ) {
			wp_send_json_success();
		}
		$pos = isset( $_POST['position'] ) ? max( 0, (int) $_POST['position'] ) : 0; // phpcs:ignore
		$dur = isset( $_POST['duration'] ) ? max( 0, (int) $_POST['duration'] ) : 0; // phpcs:ignore
		$done = ( $dur > 0 && $pos / $dur >= 0.95 ) ? 1 : 0;
		$t   = $wpdb->prefix . 'movieflix_watch_history';
		$wpdb->query( $wpdb->prepare(
			"INSERT INTO $t (user_id,movie_id,position,duration,completed,hidden,last_watched) VALUES (%d,%d,%d,%d,%d,0,%s)
			 ON DUPLICATE KEY UPDATE position=VALUES(position), duration=VALUES(duration), completed=VALUES(completed), hidden=0, last_watched=VALUES(last_watched)",
			get_current_user_id(),
			$id,
			$done ? 0 : $pos,
			$dur,
			$done,
			current_time( 'mysql', true )
		) );
		wp_send_json_success( array( 'completed' => (bool) $done ) );
	}

	public static function remove_progress() {
		global $wpdb;
		$id = self::post_id();
		$wpdb->update( $wpdb->prefix . 'movieflix_watch_history', array( 'hidden' => 1 ), array( 'user_id' => get_current_user_id(), 'movie_id' => $id ), array( '%d' ), array( '%d', '%d' ) );
		wp_send_json_success( array( 'message' => __( 'Removed from Continue Watching', 'movieflix' ) ) );
	}

	public static function clear_history() {
		global $wpdb;
		check_ajax_referer( 'mf_nonce', 'nonce' );
		$wpdb->delete( $wpdb->prefix . 'movieflix_watch_history', array( 'user_id' => get_current_user_id() ), array( '%d' ) );
		wp_send_json_success( array( 'message' => __( 'Watch history cleared', 'movieflix' ) ) );
	}

	public static function rate() {
		global $wpdb;
		$id = self::post_id( array( 'movie', 'series' ) );
		if ( ! mf_opt( 'enable_ratings', 1 ) ) {
			wp_send_json_error( array( 'message' => __( 'Ratings are disabled.', 'movieflix' ) ), 403 );
		}
		$r = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0; // phpcs:ignore
		if ( $r < 1 || $r > 5 ) {
			wp_send_json_error( array( 'message' => __( 'Choose between 1 and 5 stars.', 'movieflix' ) ), 400 );
		}
		$wpdb->query( $wpdb->prepare(
			"INSERT INTO {$wpdb->prefix}movieflix_ratings (user_id,movie_id,rating,updated_at) VALUES (%d,%d,%d,%s)
			 ON DUPLICATE KEY UPDATE rating=VALUES(rating), updated_at=VALUES(updated_at)",
			get_current_user_id(),
			$id,
			$r,
			current_time( 'mysql', true )
		) );
		mf_recalc_rating( $id );
		mf_update_sort_rating( $id );
		MF_Activity::log( 'rate', $id, array( 'rating' => $r ) );
		$st = mf_rating_stats( $id );
		wp_send_json_success( array( 'user' => $r, 'avg' => round( $st['avg'], 1 ), 'count' => $st['count'], 'message' => __( 'Thanks for rating!', 'movieflix' ) ) );
	}

	/** Playback events: start (counts a view) and complete. Guests allowed, rate limited. */
	public static function event() {
		global $wpdb;
		$id = self::post_id( array( 'movie', 'episode' ) );
		$ev = isset( $_POST['event'] ) && 'complete' === $_POST['event'] ? 'complete' : 'start'; // phpcs:ignore
		$target = $id;
		if ( 'episode' === get_post_type( $id ) ) {
			$target = (int) get_post_meta( $id, '_mf_series', true ) ?: $id;
		}
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'mf_ev_' . md5( $ip . '|' . get_current_user_id() . '|' . $id . '|' . $ev );
		if ( get_transient( $key ) ) {
			wp_send_json_success();
		}
		set_transient( $key, 1, 30 * MINUTE_IN_SECONDS );
		$wpdb->insert( $wpdb->prefix . 'movieflix_views', array( 'movie_id' => $target, 'user_id' => get_current_user_id(), 'event' => $ev, 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%d', '%s', '%s' ) );
		MF_Activity::log( 'start' === $ev ? 'watch_start' : 'watch_complete', $id );
		if ( 'start' === $ev ) {
			update_post_meta( $target, '_mf_views', (int) get_post_meta( $target, '_mf_views', true ) + 1 );
		}
		wp_send_json_success();
	}
}
