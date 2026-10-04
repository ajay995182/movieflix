<?php
/**
 * Shared helper functions (prefixed mf_). Used by plugin and theme.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return array Default settings. */
function mf_default_settings() {
	return array(
		'site_name'           => 'MovieFlix',
		'logo'                => '',
		'favicon'             => '',
		'primary_color'       => '#e50f5a',
		'secondary_color'     => '#7c3aed',
		'hero_movie'          => 0,
		'default_poster'      => '',
		'default_backdrop'    => '',
		'autoplay'            => 0,
		'default_speed'       => '1',
		'enable_ratings'      => 1,
		'enable_reviews'      => 1,
		'enable_watchlist'    => 1,
		'enable_continue'     => 1,
		'enable_registration' => 1,
		'moderate_reviews'    => 1,
		'trending_mode'       => 'both',
		'slug_movie'          => 'movie',
		'slug_genre'          => 'genre',
		'slug_watch'          => 'watch',
		'footer_text'         => 'MovieFlix. All rights reserved.',
		'social_facebook'     => '',
		'social_twitter'      => '',
		'social_instagram'    => '',
		'social_youtube'      => '',
		'contact_email'       => '',
		'contact_phone'       => '',
		'contact_address'     => '',
		'delete_on_uninstall' => 0,
		'hero_slides'         => '5',
		'enable_google'       => 0,
		'google_client_id'    => '',
		'google_client_secret' => '',
		'activity_enabled'    => 1,
		'activity_ip'         => 0,
		'activity_days'       => '90',
		'enable_email_verify' => 0,
		'enable_age_gate'     => 1,
		'age_gate_min'        => '13',
		'enable_terms_check'  => 1,
		'terms_page_id'       => 0,
		'privacy_page_id'     => 0,
		'enable_parental'     => 1,
		'enable_profiles'     => 1,
		'enable_channels'     => 1,
		'enable_notify_email' => 0,
		'enable_ads'          => 0,
		'ad_head'             => '',
		'ad_player_pre'       => '',
		'ad_sidebar'          => '',
		'enable_pwa'          => 1,
		'enable_signed_urls'  => 0,
		'force_login_watch'   => 1,
		'force_login_titles'  => 0,
		'enable_membership'   => 0,
		'membership_label'    => 'Premium',

		'cdn_base_url'        => '',
		'enable_comments_ui'  => 1,
		'pages'               => array(),
	);
}

/**
 * Get a setting.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function mf_opt( $key, $default = '' ) {
	static $settings = null;
	if ( null === $settings || did_action( 'mf_settings_saved' ) ) {
		$settings = wp_parse_args( (array) get_option( 'movieflix_settings', array() ), mf_default_settings() );
	}
	if ( ! isset( $settings[ $key ] ) ) {
		return $default;
	}
	$v = $settings[ $key ];
	return ( '' === $v && '' !== $default ) ? $default : $v;
}

/** @return array Public "title" post types. */
function mf_title_types() {
	return array( 'movie', 'series' );
}

/**
 * Resolve a media URL; supports "demo:file.svg" placeholders bundled in the plugin.
 *
 * @param string $url URL or demo token.
 * @return string
 */
function mf_resolve_url( $url ) {
	if ( 0 === strpos( (string) $url, 'demo:' ) ) {
		return MF_URL . 'assets/demo/' . sanitize_file_name( substr( $url, 5 ) );
	}
	return (string) $url;
}

/**
 * Resolve a video URL against the configured CDN, then apply optional soft signing.
 *
 * @param string $url Video URL or relative path.
 * @param int    $ttl Signature lifetime in seconds.
 * @return string
 */
function mf_video_url( $url, $ttl = 3600 ) {
	$url = trim( mf_resolve_url( (string) $url ) );
	if ( '' === $url ) {
		return '';
	}

	if ( 0 === strpos( $url, '//' ) ) {
		$url = ( is_ssl() ? 'https:' : 'http:' ) . $url;
	}

	$parts = wp_parse_url( $url );
	if ( $parts && ! empty( $parts['scheme'] ) ) {
		$url = esc_url_raw( $url, array( 'http', 'https' ) );
	} else {
		$base = esc_url_raw( trim( (string) mf_opt( 'cdn_base_url', '' ) ), array( 'http', 'https' ) );
		$url  = $base
			? trailingslashit( $base ) . ltrim( $url, '/' )
			: home_url( '/' . ltrim( $url, '/' ) );
		$url = esc_url_raw( $url, array( 'http', 'https' ) );
	}

	if ( $url && class_exists( 'MF_Extras' ) ) {
		$url = MF_Extras::signed_video_url( $url, $ttl );
	}

	return $url;
}

/**
 * Poster or backdrop URL for a post.
 *
 * @param int    $post_id Post ID.
 * @param string $type    poster|backdrop.
 * @param string $size    Image size for featured images.
 * @return string
 */
function mf_img( $post_id, $type = 'poster', $size = 'medium_large' ) {
	$meta = get_post_meta( $post_id, '_mf_' . $type . '_url', true );
	if ( $meta ) {
		return esc_url_raw( mf_resolve_url( $meta ) );
	}
	if ( 'poster' === $type && has_post_thumbnail( $post_id ) ) {
		return (string) get_the_post_thumbnail_url( $post_id, $size );
	}
	$fallback = mf_opt( 'default_' . $type, '' );
	if ( $fallback ) {
		return esc_url_raw( mf_resolve_url( $fallback ) );
	}
	return MF_URL . 'assets/demo/default-' . $type . '.svg';
}

/** @return mixed One meta value (key without the _mf_ prefix). */
function mf_meta( $post_id, $key, $default = '' ) {
	$v = get_post_meta( $post_id, '_mf_' . $key, true );
	return ( '' === $v || false === $v ) ? $default : $v;
}

/** @return string URL of the watch page for a post (movie, episode or series). */
function mf_watch_url( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return home_url( '/' );
	}
	if ( 'series' === $post->post_type ) {
		$first = MF_Query::first_episode( $post->ID );
		if ( ! $first ) {
			return get_permalink( $post );
		}
		$post = $first;
	}
	return home_url( '/' . trim( mf_opt( 'slug_watch', 'watch' ), '/' ) . '/' . $post->post_name . '/' );
}

/** @return string URL of a plugin-created page (falls back to a guessed URL). */
function mf_page_url( $slug ) {
	$pages = mf_opt( 'pages', array() );
	if ( ! empty( $pages[ $slug ] ) && 'publish' === get_post_status( $pages[ $slug ] ) ) {
		return get_permalink( $pages[ $slug ] );
	}
	return home_url( '/' . $slug . '/' );
}

/** @return int[] Post IDs in the user's list (newest first). */
function mf_user_list_ids( $user_id = 0 ) {
	global $wpdb;
	$user_id = $user_id ? $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return array();
	}
	static $cache = array();
	if ( ! isset( $cache[ $user_id ] ) ) {
		$cache[ $user_id ] = array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
			"SELECT movie_id FROM {$wpdb->prefix}movieflix_watchlist WHERE user_id = %d ORDER BY added_at DESC",
			$user_id
		) ) );
	}
	return $cache[ $user_id ];
}

/** @return bool Is the post in the current user's list. */
function mf_in_list( $post_id ) {
	return in_array( (int) $post_id, mf_user_list_ids(), true );
}

/** @return array{avg:float,count:int} Rating stats cached in post meta. */
function mf_rating_stats( $post_id ) {
	return array(
		'avg'   => (float) get_post_meta( $post_id, '_mf_rating_avg', true ),
		'count' => (int) get_post_meta( $post_id, '_mf_rating_count', true ),
	);
}

/** @return int The user's rating for a post (0 if none). */
function mf_user_rating( $post_id, $user_id = 0 ) {
	global $wpdb;
	$user_id = $user_id ? $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return 0;
	}
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT rating FROM {$wpdb->prefix}movieflix_ratings WHERE user_id=%d AND movie_id=%d",
		$user_id,
		$post_id
	) );
}

/** Recalculate and cache the rating average for a post. */
function mf_recalc_rating( $post_id ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare(
		"SELECT AVG(rating) a, COUNT(*) c FROM {$wpdb->prefix}movieflix_ratings WHERE movie_id=%d",
		$post_id
	) );
	update_post_meta( $post_id, '_mf_rating_avg', $row ? round( (float) $row->a, 2 ) : 0 );
	update_post_meta( $post_id, '_mf_rating_count', $row ? (int) $row->c : 0 );
}

/** @return string Card rating on a 10-point scale (user average, else IMDb-style field). */
function mf_display_rating( $post_id ) {
	$st = mf_rating_stats( $post_id );
	if ( $st['count'] > 0 ) {
		return number_format_i18n( $st['avg'] * 2, 1 );
	}
	$imdb = mf_meta( $post_id, 'imdb_rating' );
	return $imdb ? number_format_i18n( (float) $imdb, 1 ) : '';
}

/** @return array Friendly notice messages keyed by code: [type, text]. */
function mf_messages() {
	return array(
		'login_required' => array( 'error', __( 'Please log in to continue.', 'movieflix' ) ),
		'login_failed'   => array( 'error', __( 'Incorrect username or password.', 'movieflix' ) ),
		'reg_disabled'   => array( 'error', __( 'Registration is currently closed.', 'movieflix' ) ),
		'reg_email'      => array( 'error', __( 'Please enter a valid email address.', 'movieflix' ) ),
		'reg_exists'     => array( 'error', __( 'That username or email is already registered.', 'movieflix' ) ),
		'reg_user'       => array( 'error', __( 'Please choose a valid username (letters, numbers, . _ -).', 'movieflix' ) ),
		'reg_pass'       => array( 'error', __( 'Password must be at least 8 characters.', 'movieflix' ) ),
		'reg_failed'     => array( 'error', __( 'Registration failed. Please try again.', 'movieflix' ) ),
		'reg_ok'         => array( 'success', __( 'Welcome! Your account is ready.', 'movieflix' ) ),
		'bad_nonce'      => array( 'error', __( 'Security check failed. Please reload the page and try again.', 'movieflix' ) ),
		'review_empty'   => array( 'error', __( 'Please write a review before submitting.', 'movieflix' ) ),
		'review_error'   => array( 'error', __( 'Your review could not be saved. Please try again.', 'movieflix' ) ),
		'review_saved'   => array( 'success', __( 'Thanks! Your review has been saved.', 'movieflix' ) ),
		'review_pending' => array( 'success', __( 'Thanks! Your review is awaiting moderation.', 'movieflix' ) ),
		'review_deleted' => array( 'success', __( 'Your review was deleted.', 'movieflix' ) ),
		'profile_saved'  => array( 'success', __( 'Profile updated.', 'movieflix' ) ),
		'profile_error'  => array( 'error', __( 'Could not update profile. Check your details and try again.', 'movieflix' ) ),
		'google_disabled'   => array( 'error', __( 'Google sign-in is not enabled on this site.', 'movieflix' ) ),
		'google_failed'     => array( 'error', __( 'Google sign-in was cancelled or failed. Please try again.', 'movieflix' ) ),
		'google_state'      => array( 'error', __( 'Google sign-in expired. Please try again.', 'movieflix' ) ),
		'google_unverified' => array( 'error', __( 'Your Google email address is not verified.', 'movieflix' ) ),
		'google_privileged' => array( 'error', __( 'Staff accounts must sign in with a password.', 'movieflix' ) ),
		'activity_cleared'  => array( 'success', __( 'Your activity history was cleared.', 'movieflix' ) ),
		'unauthorized'   => array( 'error', __( 'You do not have access to that page.', 'movieflix' ) ),
		'verify_sent'    => array( 'success', __( 'Account created. Please check your email to verify before signing in.', 'movieflix' ) ),
		'verify_ok'      => array( 'success', __( 'Email verified. You can sign in now.', 'movieflix' ) ),
		'verify_fail'    => array( 'error', __( 'Verification link is invalid or expired.', 'movieflix' ) ),
		'reg_age'        => array( 'error', __( 'You must confirm you meet the minimum age.', 'movieflix' ) ),
		'reg_terms'      => array( 'error', __( 'You must accept the Terms and Privacy Policy.', 'movieflix' ) ),
		'lost_sent'      => array( 'success', __( 'If that account exists, a reset link has been sent.', 'movieflix' ) ),
		'lost_fail'      => array( 'error', __( 'Could not send reset email. Try again later.', 'movieflix' ) ),
		'reset_ok'       => array( 'success', __( 'Password updated. Please sign in.', 'movieflix' ) ),
		'reset_invalid'  => array( 'error', __( 'Reset link is invalid or expired.', 'movieflix' ) ),
		'profiles_saved' => array( 'success', __( 'Profiles saved.', 'movieflix' ) ),
		'pin_saved'      => array( 'success', __( 'Parental PIN saved.', 'movieflix' ) ),
		'pin_invalid'    => array( 'error', __( 'PIN must be 4–8 digits.', 'movieflix' ) ),
		'pin_wrong'      => array( 'error', __( 'Incorrect PIN.', 'movieflix' ) ),
	);
}

/** Keep the sortable rating (10-point scale) in sync: user average if rated, else IMDb-style field. */
function mf_update_sort_rating( $post_id ) {
	$st  = mf_rating_stats( $post_id );
	$val = $st['count'] > 0 ? $st['avg'] * 2 : (float) get_post_meta( $post_id, '_mf_imdb_rating', true );
	update_post_meta( $post_id, '_mf_sort_rating', round( $val, 2 ) );
}

/** Add a notice code to a URL. */
function mf_redirect_url( $url, $code ) {
	return add_query_arg( 'mf_msg', rawurlencode( $code ), $url );
}
