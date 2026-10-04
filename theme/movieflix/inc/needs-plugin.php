<?php
/** Shown when MovieFlix Core is not active. @package MovieFlix */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MovieFlix</title><?php wp_head(); ?></head>
<body style="background:#0b0b12;color:#eee;font-family:sans-serif;display:grid;place-items:center;min-height:100vh;text-align:center">
<div><h1>MovieFlix</h1><p>The <strong>MovieFlix Core</strong> plugin must be activated for this theme to work.</p>
<?php if ( current_user_can( 'activate_plugins' ) ) : ?><p><a style="color:#e50f5a" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">Go to Plugins</a></p><?php endif; ?></div><?php wp_footer(); ?></body></html>
