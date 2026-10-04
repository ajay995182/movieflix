<?php
/**
 * MovieFlix theme bootstrap.
 *
 * @package MovieFlix
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MOVIEFLIX_THEME_VERSION', '2.0.4' );

require_once get_template_directory() . '/inc/setup.php';

/** True when the MovieFlix Core plugin is active. */
function movieflix_ready() {
	return defined( 'MF_VERSION' ) && function_exists( 'mf_opt' );
}

if ( movieflix_ready() ) {
	require_once get_template_directory() . '/inc/template-tags.php';
	require_once get_template_directory() . '/inc/seo.php';
} else {
	add_filter( 'template_include', function () {
		return get_template_directory() . '/inc/needs-plugin.php';
	}, 100 );
	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-error"><p><strong>MovieFlix theme:</strong> please activate the <em>MovieFlix Core</em> plugin.</p></div>';
	} );
}
