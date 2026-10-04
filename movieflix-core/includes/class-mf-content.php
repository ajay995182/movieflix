<?php
/**
 * Post types and taxonomies.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Content {

	/** Hook everything. */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 0 );
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 1 );
		add_action( 'save_post', array( __CLASS__, 'sync_year_term' ), 20, 2 );
		add_filter( 'show_admin_bar', array( __CLASS__, 'admin_bar' ) );
		add_filter( 'get_avatar_url', array( __CLASS__, 'avatar_url' ), 10, 3 );
	}

	/** Register Movie, Series, Episode and Person post types. */
	public static function register_post_types() {
		$show = current_user_can( 'edit_posts' ) || ! is_admin() ? 'movieflix' : false;
		$base = array(
			'public'       => true,
			'show_in_rest' => true,
			'show_in_menu' => 'movieflix',
			'menu_icon'    => 'dashicons-video-alt3',
		);
		$labels = function ( $s, $p ) {
			return array(
				'name'          => $p,
				'singular_name' => $s,
				'add_new_item'  => "Add New $s",
				'edit_item'     => "Edit $s",
				'new_item'      => "New $s",
				'view_item'     => "View $s",
				'search_items'  => "Search $p",
				'not_found'     => "No $p found",
				'all_items'     => "All $p",
			);
		};
		register_post_type( 'movie', $base + array(
			'labels'      => $labels( 'Movie', 'Movies' ),
			'has_archive' => 'movies',
			'rewrite'     => array( 'slug' => sanitize_title( mf_opt( 'slug_movie', 'movie' ) ), 'with_front' => false ),
			'supports'    => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'author' ),
			'taxonomies'  => array( 'post_tag' ),
		) );
		register_post_type( 'series', $base + array(
			'labels'      => $labels( 'Series', 'Series' ),
			'has_archive' => 'series',
			'rewrite'     => array( 'slug' => 'series', 'with_front' => false ),
			'supports'    => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'author' ),
			'taxonomies'  => array( 'post_tag' ),
		) );
		register_post_type( 'episode', $base + array(
			'labels'      => $labels( 'Episode', 'Episodes' ),
			'has_archive' => false,
			'rewrite'     => array( 'slug' => 'episode', 'with_front' => false ),
			'supports'    => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
		) );
		register_post_type( 'person', $base + array(
			'labels'      => $labels( 'Person', 'People' ),
			'has_archive' => 'people',
			'rewrite'     => array( 'slug' => 'person', 'with_front' => false ),
			'supports'    => array( 'title', 'editor', 'thumbnail' ),
		) );
	}

	/** Register taxonomies. */
	public static function register_taxonomies() {
		$types = array( 'movie', 'series' );
		$defs  = array(
			'genre'          => array( 'Genres', 'Genre', sanitize_title( mf_opt( 'slug_genre', 'genre' ) ), true ),
			'mf_language'    => array( 'Languages', 'Language', 'language', false ),
			'mf_country'     => array( 'Countries', 'Country', 'country', false ),
			'content_rating' => array( 'Content Ratings', 'Content Rating', 'content-rating', false ),
			'release_year'   => array( 'Years', 'Year', 'year', false ),
			'quality'        => array( 'Qualities', 'Quality', 'quality', false ),
		);
		foreach ( $defs as $tax => $d ) {
			register_taxonomy( $tax, $types, array(
				'labels'            => array( 'name' => $d[0], 'singular_name' => $d[1], 'menu_name' => $d[0], 'add_new_item' => 'Add New ' . $d[1] ),
				'hierarchical'      => false,
				'public'            => true,
				'show_admin_column' => 'genre' === $tax,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => $d[2], 'with_front' => false ),
			) );
		}
	}

	/** Seed default terms (only if the taxonomy is empty). */
	public static function seed_terms() {
		$seed = array(
			'genre'          => array( 'Action', 'Adventure', 'Comedy', 'Drama', 'Horror', 'Romance', 'Thriller', 'Sci-Fi', 'Fantasy', 'Animation', 'Documentary', 'Crime', 'Mystery', 'Family', 'Kids', 'Sports', 'Musical', 'War', 'Biography', 'History', 'Reality', 'International', 'Music' ),
			'quality'        => array( 'SD', 'HD', 'Full HD', '4K' ),
			'content_rating' => array( 'G', 'PG', 'PG-13', 'R', 'TV-Y7', 'TV-14', 'TV-MA' ),
			'mf_language'    => array( 'English', 'Hindi', 'Telugu', 'Tamil', 'Spanish', 'French', 'Japanese', 'Korean', 'Mandarin', 'Arabic', 'German', 'Portuguese' ),
			'mf_country'     => array( 'United States', 'India', 'United Kingdom', 'France', 'Japan', 'South Korea', 'Spain', 'China', 'Germany', 'Brazil', 'Italy', 'Canada' ),
		);
		foreach ( $seed as $tax => $names ) {
			if ( taxonomy_exists( $tax ) && 0 === (int) wp_count_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) ) ) {
				foreach ( $names as $n ) {
					wp_insert_term( $n, $tax );
				}
			}
		}
	}

	/** Keep the Year taxonomy in sync with the release year field. */
	public static function sync_year_term( $post_id, $post ) {
		if ( ! in_array( $post->post_type, mf_title_types(), true ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		$year = (int) get_post_meta( $post_id, '_mf_year', true );
		if ( $year > 1800 && $year < 2200 ) {
			wp_set_object_terms( $post_id, (string) $year, 'release_year' );
		}
		foreach ( array( '_mf_views' => 0, '_mf_sort_rating' => 0 ) as $k => $v ) {
			if ( '' === get_post_meta( $post_id, $k, true ) ) {
				update_post_meta( $post_id, $k, $v );
			}
		}
	}

	/** Hide the admin bar for non-staff users. */
	public static function admin_bar( $show ) {
		return current_user_can( 'edit_posts' ) ? $show : false;
	}

	/** Use uploaded profile picture as avatar. */
	public static function avatar_url( $url, $id_or_email, $args ) {
		$user = false;
		if ( is_numeric( $id_or_email ) ) {
			$user = get_user_by( 'id', (int) $id_or_email );
		} elseif ( $id_or_email instanceof WP_User ) {
			$user = $id_or_email;
		} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
		}
		if ( $user ) {
			$att = (int) get_user_meta( $user->ID, 'mf_avatar', true );
			if ( $att ) {
				$src = wp_get_attachment_image_url( $att, array( 128, 128 ) );
				if ( $src ) {
					return $src;
				}
			}
		}
		return $url;
	}
}
