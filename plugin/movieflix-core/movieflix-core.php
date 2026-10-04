<?php
/**
 * Plugin Name: MovieFlix Core
 * Description: Movies, series, people, watchlist, continue watching, ratings, reviews and admin tools for the MovieFlix streaming theme.
 * Version: 1.5.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: MovieFlix
 * License: GPL-2.0-or-later
 * Text Domain: movieflix
 *
 * @package MovieFlixCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MF_VERSION', '1.5.0' );
define( 'MF_FILE', __FILE__ );
define( 'MF_DIR', plugin_dir_path( __FILE__ ) );
define( 'MF_URL', plugin_dir_url( __FILE__ ) );

require_once MF_DIR . 'includes/functions.php';
require_once MF_DIR . 'includes/class-mf-install.php';
require_once MF_DIR . 'includes/class-mf-content.php';
require_once MF_DIR . 'includes/class-mf-query.php';
require_once MF_DIR . 'includes/class-mf-demo.php';
require_once MF_DIR . 'includes/class-mf-activity.php';
require_once MF_DIR . 'includes/class-mf-google.php';
require_once MF_DIR . 'includes/class-mf-extras.php';
require_once MF_DIR . 'includes/class-mf-notify.php';
if ( file_exists( MF_DIR . 'public/class-mf-routes.php' ) ) {
	require_once MF_DIR . 'public/class-mf-routes.php';
} else {
	// Fail-soft: define a no-op stub so the site does not white-screen.
	class MF_Routes {
		public static function init() {}
		public static function add_rules() {}
	}
}

require_once MF_DIR . 'public/class-mf-ajax.php';
require_once MF_DIR . 'public/class-mf-forms.php';

register_activation_hook( __FILE__, array( 'MF_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MF_Install', 'deactivate' ) );
register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'mf_purge_activity' );
} );

/**
 * Boot the plugin.
 */
function mf_boot() {
	MF_Content::init();
	MF_Query::init();
	MF_Routes::init();
	MF_Ajax::init();
	MF_Forms::init();
	MF_Activity::init();
	MF_Google::init();
	MF_Extras::init();
	MF_Notify::init();
	if ( is_admin() ) {
		require_once MF_DIR . 'admin/class-mf-metaboxes.php';
		require_once MF_DIR . 'admin/class-mf-upload.php';
		require_once MF_DIR . 'admin/class-mf-admin.php';
		require_once MF_DIR . 'admin/class-mf-activity-admin.php';
		require_once MF_DIR . 'admin/class-mf-analytics-admin.php';
		require_once MF_DIR . 'admin/class-mf-import.php';
		MF_Metaboxes::init();
		MF_Upload::init();
		MF_Admin::init();
		MF_Activity_Admin::init();
		MF_Analytics_Admin::init();
		MF_Import::init();
	}
	// Flush rewrite rules once when slugs change (never on every request).
	add_action( 'init', function () {
		if ( get_option( 'movieflix_flush' ) ) {
			flush_rewrite_rules();
			delete_option( 'movieflix_flush' );
		}
	}, 99 );
}
add_action( 'plugins_loaded', 'mf_boot' );
