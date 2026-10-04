<?php
/**
 * Front-end rewrite routes (watch player URL).
 *
 * Handles pretty URLs like /watch/{post-slug}/ and loads the watch template.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Routes {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'resolve_watch' ), 1 );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 20 );
	}

	/** Register the /{slug_watch}/{post-name}/ rewrite rule. */
	public static function add_rules() {
		$base = trim( (string) mf_opt( 'slug_watch', 'watch' ), '/' );
		if ( '' === $base ) {
			$base = 'watch';
		}
		// e.g. watch/my-movie-slug/
		add_rewrite_rule(
			'^' . preg_quote( $base, '/' ) . '/([^/]+)/?$',
			'index.php?mf_watch=$matches[1]',
			'top'
		);
	}

	/** Allow mf_watch as a public query var. */
	public static function query_vars( $vars ) {
		$vars[] = 'mf_watch';
		return $vars;
	}

	/**
	 * Resolve the post from ?mf_watch=slug and stash it for the theme.
	 * Runs early on template_redirect so force-login and other locks can see the query var.
	 */
	public static function resolve_watch() {
		$slug = get_query_var( 'mf_watch' );
		if ( ! $slug ) {
			return;
		}
		$slug = sanitize_title( $slug );

		$types = array( 'movie', 'series', 'episode' );
		$post  = get_page_by_path( $slug, OBJECT, $types );

		// Fallback: search by name among title types if path lookup fails.
		if ( ! $post ) {
			$q = new WP_Query( array(
				'name'           => $slug,
				'post_type'      => $types,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			) );
			if ( $q->have_posts() ) {
				$post = $q->posts[0];
			}
		}

		if ( ! $post || 'publish' !== $post->post_status ) {
			// Let WordPress show a normal 404.
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return;
		}

		$GLOBALS['mf_watch_post'] = $post;
	}

	/**
	 * Load the theme's page-watch.php template for watch URLs.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public static function template_include( $template ) {
		if ( ! get_query_var( 'mf_watch' ) ) {
			return $template;
		}
		if ( empty( $GLOBALS['mf_watch_post'] ) ) {
			// 404 already set in resolve_watch.
			return $template;
		}
		$watch = locate_template( array( 'page-watch.php' ) );
		if ( $watch ) {
			return $watch;
		}
		return $template;
	}
}
