<?php
/**
 * Activation, deactivation and database installation.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Install {

	/** Run on plugin activation. */
	public static function activate() {
		self::create_tables();
		if ( false === get_option( 'movieflix_settings' ) ) {
			add_option( 'movieflix_settings', mf_default_settings() );
		}
		MF_Content::register_post_types();
		MF_Content::register_taxonomies();
		MF_Routes::add_rules();
		self::create_pages();
		MF_Content::seed_terms();
		update_option( 'movieflix_flush', 1 );
		flush_rewrite_rules();
	}

	/** Run on plugin deactivation. */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/** Create custom tables with dbDelta(). */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix . 'movieflix_';
		dbDelta( "CREATE TABLE {$p}watch_history (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			movie_id BIGINT(20) UNSIGNED NOT NULL,
			position INT(10) UNSIGNED NOT NULL DEFAULT 0,
			duration INT(10) UNSIGNED NOT NULL DEFAULT 0,
			completed TINYINT(1) NOT NULL DEFAULT 0,
			hidden TINYINT(1) NOT NULL DEFAULT 0,
			last_watched DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_movie (user_id,movie_id),
			KEY user_last (user_id,last_watched)
		) $c;" );
		dbDelta( "CREATE TABLE {$p}watchlist (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			movie_id BIGINT(20) UNSIGNED NOT NULL,
			added_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_movie (user_id,movie_id),
			KEY movie_id (movie_id)
		) $c;" );
		dbDelta( "CREATE TABLE {$p}ratings (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			movie_id BIGINT(20) UNSIGNED NOT NULL,
			rating TINYINT(3) UNSIGNED NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_movie (user_id,movie_id),
			KEY movie_id (movie_id)
		) $c;" );
		dbDelta( "CREATE TABLE {$p}views (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			movie_id BIGINT(20) UNSIGNED NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			event VARCHAR(10) NOT NULL DEFAULT 'start',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY movie_created (movie_id,created_at),
			KEY created_at (created_at)
		) $c;" );
		dbDelta( "CREATE TABLE {$p}activity (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			action VARCHAR(30) NOT NULL,
			object_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			meta TEXT NULL,
			ip VARCHAR(45) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY user_created (user_id,created_at),
			KEY action_created (action,created_at),
			KEY created_at (created_at)
		) $c;" );
		update_option( 'movieflix_db_version', MF_VERSION );
	}

	/** Create the front-end pages (My List, Profile, Login...) once. Templates are slug-based. */
	public static function create_pages() {
		$pages = array(
			'my-list'           => 'My List',
			'continue-watching' => 'Continue Watching',
			'profile'           => 'Profile',
			'login'             => 'Login',
			'register'          => 'Register',
			'search'            => 'Search',
			'lost-password'     => 'Lost Password',
			'reset-password'    => 'Reset Password',
			'profiles'          => 'Profiles',
			'channels'          => 'Live TV',
			'new-popular'       => 'New & Popular',
			'tv-shows'          => 'TV Shows',
			'genres'            => 'Genres',
		);
		$s = get_option( 'movieflix_settings', array() );
		foreach ( $pages as $slug => $title ) {
			$existing = get_page_by_path( $slug );
			if ( $existing ) {
				$s['pages'][ $slug ] = $existing->ID;
				continue;
			}
			$id = wp_insert_post( array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_name'   => $slug,
			) );
			if ( $id && ! is_wp_error( $id ) ) {
				$s['pages'][ $slug ] = $id;
			}
		}
		update_option( 'movieflix_settings', $s );
	}

	/** Add the Genres landing page to sites where Core was already active. */
	public static function ensure_genres_page() {
		if ( ! current_user_can( 'manage_options' ) || get_option( 'movieflix_genres_page_v1' ) ) {
			return;
		}
		$page = get_page_by_path( 'genres' );
		if ( ! $page ) {
			$id = wp_insert_post( array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => __( 'Genres', 'movieflix' ),
				'post_name'   => 'genres',
			) );
			if ( ! $id || is_wp_error( $id ) ) {
				return;
			}
			$page = get_post( $id );
		}
		if ( $page && 'publish' === $page->post_status ) {
			$settings = get_option( 'movieflix_settings', array() );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}
			if ( empty( $settings['pages'] ) || ! is_array( $settings['pages'] ) ) {
				$settings['pages'] = array();
			}
			$settings['pages']['genres'] = (int) $page->ID;
			update_option( 'movieflix_settings', $settings );
			update_option( 'movieflix_genres_page_v1', 1, false );
		}
	}
}

add_action( 'admin_init', array( 'MF_Install', 'ensure_genres_page' ) );
