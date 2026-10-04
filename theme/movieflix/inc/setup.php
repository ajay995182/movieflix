<?php
/**
 * Theme setup, assets, widgets.
 *
 * @package MovieFlix
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'movieflix', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 60, 'width' => 220, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'automatic-feed-links' );
	set_post_thumbnail_size( 400, 600, true );
	add_image_size( 'movieflix-poster', 400, 600, true );
	add_image_size( 'movieflix-backdrop', 1600, 900, true );
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'movieflix' ),
		'footer'  => __( 'Footer Menu', 'movieflix' ),
	) );
} );

add_action( 'widgets_init', function () {
	register_sidebar( array(
		'name'          => __( 'Footer Widgets', 'movieflix' ),
		'id'            => 'footer-1',
		'before_widget' => '<div class="mf-widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="mf-widget__title">',
		'after_title'   => '</h3>',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	$v = MOVIEFLIX_THEME_VERSION;
	wp_enqueue_style( 'movieflix', get_stylesheet_uri(), array(), $v );
	if ( ! movieflix_ready() ) {
		return;
	}
	$p = esc_attr( mf_opt( 'primary_color', '#e50f5a' ) );
	$s = esc_attr( mf_opt( 'secondary_color', '#7c3aed' ) );
	wp_add_inline_style( 'movieflix', ":root{--mf-primary:{$p};--mf-secondary:{$s}}" );
	wp_enqueue_script( 'movieflix-app', get_template_directory_uri() . '/assets/js/app.js', array(), $v, true );
	wp_localize_script( 'movieflix-app', 'MF', array(
		'ajax'      => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'mf_nonce' ),
		'loggedIn'  => is_user_logged_in(),
		'loginUrl'  => mf_page_url( 'login' ),
		'i18n'      => array(
			'add'               => __( 'Add to My List', 'movieflix' ),
			'remove'            => __( 'Remove from My List', 'movieflix' ),
			'error'             => __( 'Something went wrong. Please try again.', 'movieflix' ),
			'streamPlaying'     => __( 'Playing live stream.', 'movieflix' ),
			'streamBuffering'   => __( 'Buffering live stream…', 'movieflix' ),
			'streamError'       => __( 'The stream could not be loaded. Please try again later.', 'movieflix' ),
			'streamUnavailable' => __( 'The live stream is unavailable right now.', 'movieflix' ),
			'hlsUnsupported'    => __( 'This browser cannot play the HLS stream.', 'movieflix' ),
		),
	) );
	if ( get_query_var( 'mf_watch' ) ) {
		$post = isset( $GLOBALS['mf_watch_post'] ) ? $GLOBALS['mf_watch_post'] : null;
		$deps = array( 'movieflix-app' );
		if ( $post && 'hls' === movieflix_video_type( $post->ID ) ) {
			wp_enqueue_script( 'hlsjs', 'https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js', array(), '1.5.13', true );
			$deps[] = 'hlsjs';
		}
		wp_enqueue_script( 'movieflix-player', get_template_directory_uri() . '/assets/js/player.js', $deps, $v, true );
		wp_localize_script( 'movieflix-player', 'MFPlayer', array(
			'speed'    => (float) mf_opt( 'default_speed', '1' ),
			'autoplay' => (bool) mf_opt( 'autoplay', 0 ),
		) );
	}
	if ( is_singular( 'channel' ) ) {
		$channel = get_queried_object();
		$deps    = array( 'movieflix-app' );
		if ( $channel && 'hls' === movieflix_video_type( $channel->ID ) ) {
			wp_enqueue_script( 'hlsjs', 'https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js', array(), '1.5.13', true );
			$deps[] = 'hlsjs';
		}
		wp_enqueue_script( 'movieflix-channel-player', get_template_directory_uri() . '/assets/js/channel-player.js', $deps, $v, true );
	}
} );

/** Favicon from settings (falls back to WordPress Site Icon). */
add_action( 'wp_head', function () {
	if ( movieflix_ready() && mf_opt( 'favicon' ) && ! has_site_icon() ) {
		echo '<link rel="icon" href="' . esc_url( mf_resolve_url( mf_opt( 'favicon' ) ) ) . '">' . "\n";
	}
}, 5 );

add_filter( 'body_class', function ( $c ) {
	$c[] = 'mf-dark';
	if ( get_query_var( 'mf_watch' ) ) {
		$c[] = 'mf-watch';
	}
	return $c;
} );

/** Use plain scripts defer for the app bundle. */
add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	return in_array( $handle, array( 'movieflix-app', 'movieflix-player', 'movieflix-channel-player' ), true ) ? str_replace( ' src=', ' defer src=', $tag ) : $tag;
}, 10, 2 );

/** PWA manifest + service worker when enabled. */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'mf_opt' ) || ! mf_opt( 'enable_pwa', 1 ) ) {
		return;
	}
	$manifest = array(
		'name'             => get_bloginfo( 'name' ),
		'short_name'       => get_bloginfo( 'name' ),
		'start_url'        => home_url( '/' ),
		'display'          => 'standalone',
		'background_color' => '#0a0e17',
		'theme_color'      => '#0a0e17',
		'description'      => get_bloginfo( 'description' ),
	);
	echo '<link rel="manifest" href="' . esc_url( rest_url( 'movieflix/v1/manifest' ) ) . '">' . "\n";
	echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
}, 6 );

add_action( 'wp_footer', function () {
	if ( ! function_exists( 'mf_opt' ) || ! mf_opt( 'enable_pwa', 1 ) ) {
		return;
	}
	$sw = get_template_directory_uri() . '/assets/js/sw.js';
	echo '<script>if("serviceWorker" in navigator){navigator.serviceWorker.register("' . esc_url( $sw ) . '",{scope:"/"}).catch(function(){})}</script>' . "\n";
}, 99 );

/** Ad head slot */
add_action( 'wp_head', function () {
	if ( function_exists( 'mf_opt' ) && mf_opt( 'enable_ads', 0 ) && mf_opt( 'ad_head' ) ) {
		echo mf_opt( 'ad_head' ); // phpcs:ignore WordPress.Security.EscapeOutput -- admin-provided ad HTML/JS
	}
}, 99 );
