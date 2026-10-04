<?php
/**
 * Queries: rows, browse/filter/search, trending, recommendations.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Query {

	public static function init() {
		add_action( 'pre_get_posts', array( __CLASS__, 'main_query' ) );
		add_action( 'wp_ajax_mf_suggest', array( __CLASS__, 'ajax_suggest' ) );
		add_action( 'wp_ajax_nopriv_mf_suggest', array( __CLASS__, 'ajax_suggest' ) );
	}

	/** Apply filters/sorting to the main front-end queries. */
	public static function main_query( $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}
		$tax = array( 'genre', 'mf_language', 'mf_country', 'content_rating', 'release_year', 'quality' );
		if ( $q->is_search() || $q->is_post_type_archive( array( 'movie', 'series' ) ) || $q->is_tax( $tax ) ) {
			$args = self::browse_args();
			if ( $q->is_search() ) {
				$q->set( 'post_type', array( 'movie', 'series' ) );
			}
			foreach ( $args as $k => $v ) {
				if ( 'post_type' !== $k ) {
					$existing = $q->get( $k );
					if ( 'tax_query' === $k && $existing ) {
						$v = array_merge( (array) $existing, $v );
					}
					$q->set( $k, $v );
				}
			}
			$q->set( 'posts_per_page', 24 );
		}
	}

	/**
	 * Build WP_Query args from the sanitized GET filters.
	 *
	 * @param array $base Base args.
	 * @return array
	 */
	public static function browse_args( $base = array() ) {
		// phpcs:disable WordPress.Security.NonceVerification
		$g      = wp_unslash( $_GET );
		$args   = wp_parse_args( $base, array(
			'post_type'      => array( 'movie', 'series' ),
			'post_status'    => 'publish',
			'posts_per_page' => 24,
		) );
		$taxq   = array();
		$map    = array( 'mf_genre' => 'genre', 'mf_lang' => 'mf_language', 'mf_country' => 'mf_country', 'mf_crating' => 'content_rating', 'mf_quality' => 'quality' );
		foreach ( $map as $param => $tax ) {
			if ( ! empty( $g[ $param ] ) ) {
				$taxq[] = array( 'taxonomy' => $tax, 'field' => 'slug', 'terms' => sanitize_title( $g[ $param ] ) );
			}
		}
		if ( $taxq ) {
			$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $taxq );
		}
		$meta = array();
		if ( ! empty( $g['mf_year'] ) ) {
			$meta[] = array( 'key' => '_mf_year', 'value' => absint( $g['mf_year'] ), 'type' => 'NUMERIC' );
		}
		if ( ! empty( $g['mf_min'] ) ) {
			$meta[] = array( 'key' => '_mf_sort_rating', 'value' => (float) $g['mf_min'], 'compare' => '>=', 'type' => 'DECIMAL(4,2)' );
		}
		if ( $meta ) {
			$args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta );
		}
		$s = isset( $g['s'] ) ? trim( sanitize_text_field( $g['s'] ) ) : '';
		if ( '' !== $s ) {
			$args['post__in'] = self::search_ids( $s );
			$args['s']        = '';
		}
		$sort = isset( $g['mf_sort'] ) ? sanitize_key( $g['mf_sort'] ) : 'newest';
		switch ( $sort ) {
			case 'oldest':
				$args['orderby'] = 'date';
				$args['order']   = 'ASC';
				break;
			case 'rated':
				$args['meta_key'] = '_mf_sort_rating';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;
			case 'popular':
				$args['meta_key'] = '_mf_views';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;
			case 'az':
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
				break;
			case 'za':
				$args['orderby'] = 'title';
				$args['order']   = 'DESC';
				break;
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}
		if ( ! empty( $g['paged'] ) ) {
			$args['paged'] = absint( $g['paged'] );
		} elseif ( get_query_var( 'paged' ) ) {
			$args['paged'] = absint( get_query_var( 'paged' ) );
		}
		// phpcs:enable
		return $args;
	}

	/**
	 * Search titles, plot text, people (cast/crew) and taxonomy names. Returns matching IDs.
	 *
	 * @param string $term Search term.
	 * @return int[] IDs (array(0) if none so WP_Query returns nothing).
	 */
	public static function search_ids( $term ) {
		$types = array( 'movie', 'series' );
		$base  = array( 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => 200, 'no_found_rows' => true, 'update_post_meta_cache' => false, 'update_post_term_cache' => false );
		$ids   = get_posts( $base + array( 'post_type' => $types, 's' => $term ) );
		$people = get_posts( $base + array( 'post_type' => 'person', 's' => $term, 'posts_per_page' => 15 ) );
		if ( $people ) {
			$or = array( 'relation' => 'OR' );
			foreach ( $people as $pid ) {
				foreach ( array( '_mf_cast', '_mf_directors', '_mf_writers', '_mf_producers' ) as $key ) {
					$or[] = array( 'key' => $key, 'value' => '"' . (int) $pid . '"', 'compare' => 'LIKE' );
				}
			}
			$ids = array_merge( $ids, get_posts( $base + array( 'post_type' => $types, 'meta_query' => $or ) ) );
		}
		foreach ( array( 'genre', 'mf_language', 'release_year' ) as $tax ) {
			$terms = get_terms( array( 'taxonomy' => $tax, 'name__like' => $term, 'fields' => 'ids', 'number' => 5 ) );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$ids = array_merge( $ids, get_posts( $base + array( 'post_type' => $types, 'tax_query' => array( array( 'taxonomy' => $tax, 'terms' => $terms ) ) ) ) );
			}
		}
		$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
		return $ids ? $ids : array( 0 );
	}

	/** AJAX search suggestions — rich results (poster, year, type, rating, genres, people). */
	public static function ajax_suggest() {
		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : ''; // phpcs:ignore
		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}
		$out = array();
		// People matches first (cast photos).
		$people = get_posts( array(
			'post_type'      => 'person',
			'post_status'    => 'publish',
			's'              => $term,
			'posts_per_page' => 3,
			'no_found_rows'  => true,
		) );
		foreach ( $people as $person ) {
			$out[] = array(
				'title'  => get_the_title( $person ),
				'url'    => get_permalink( $person ),
				'year'   => '',
				'img'    => mf_img( $person->ID, 'poster', 'thumbnail' ),
				'type'   => 'person',
				'badge'  => __( 'Person', 'movieflix' ),
				'rating' => '',
				'meta'   => (string) mf_meta( $person->ID, 'known_for', '' ),
			);
		}
		$ids = array_slice( self::search_ids( $term ), 0, 8 );
		if ( $ids && 0 !== $ids[0] ) {
			$q = new WP_Query( array(
				'post_type'      => array( 'movie', 'series' ),
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => 8,
				'no_found_rows'  => true,
			) );
			foreach ( $q->posts as $p ) {
				$type_label = ( 'series' === $p->post_type ) ? __( 'Series', 'movieflix' ) : __( 'Movie', 'movieflix' );
				$genres     = wp_get_object_terms( $p->ID, 'genre', array( 'fields' => 'names' ) );
				$genre_str  = ( $genres && ! is_wp_error( $genres ) ) ? implode( ', ', array_slice( $genres, 0, 2 ) ) : '';
				$rating     = function_exists( 'mf_display_rating' ) ? mf_display_rating( $p->ID ) : mf_meta( $p->ID, 'imdb_rating' );
				$out[]      = array(
					'title'  => get_the_title( $p ),
					'url'    => get_permalink( $p ),
					'year'   => (string) mf_meta( $p->ID, 'year' ),
					'img'    => mf_img( $p->ID, 'poster', 'thumbnail' ),
					'type'   => $p->post_type,
					'badge'  => $type_label,
					'rating' => $rating ? (string) $rating : '',
					'meta'   => $genre_str,
				);
			}
		}
		wp_send_json_success( array_slice( $out, 0, 10 ) );
	}

	/**
	 * Row query by type.
	 *
	 * @param string          $type      trending|popular|new|featured|recommended|members|genre:{slug}.
	 * @param int             $limit     Number of items.
	 * @param string|string[] $post_type Optional movie/series content type filter.
	 * @return WP_Query
	 */
	public static function row( $type, $limit = 14, $post_type = array( 'movie', 'series' ) ) {
		$post_types = array_values( array_intersect( (array) $post_type, array( 'movie', 'series' ) ) );
		if ( ! $post_types ) {
			$post_types = array( 'movie', 'series' );
		}
		$args = array( 'post_type' => $post_types, 'post_status' => 'publish', 'posts_per_page' => $limit, 'no_found_rows' => true );
		if ( 'trending' === $type ) {
			$ids = self::trending_ids( max( 60, $limit ) );
			$ids = array_values( array_filter( $ids, static function ( $id ) use ( $post_types ) {
				return in_array( get_post_type( $id ), $post_types, true );
			} ) );
			$ids = array_slice( $ids, 0, $limit );
			if ( $ids ) {
				return new WP_Query( $args + array( 'post__in' => $ids, 'orderby' => 'post__in' ) );
			}
			$type = 'popular';
		}
		if ( 'popular' === $type ) {
			$args['meta_query'] = array( array( 'key' => '_mf_popular', 'value' => '1' ) );
			$q = new WP_Query( $args );
			if ( $q->have_posts() ) {
				return $q;
			}
			unset( $args['meta_query'] );
			$args['meta_key'] = '_mf_views';
			$args['orderby']  = 'meta_value_num';
			return new WP_Query( $args );
		}
		if ( 'new' === $type ) {
			$args['meta_query'] = array( array( 'key' => '_mf_new_release', 'value' => '1' ) );
			$q = new WP_Query( $args );
			if ( $q->have_posts() ) {
				return $q;
			}
			unset( $args['meta_query'] );
			return new WP_Query( $args );
		}
		if ( 'featured' === $type ) {
			$args['meta_query'] = array( array( 'key' => '_mf_featured', 'value' => '1' ) );
			return new WP_Query( $args );
		}
		if ( 'members' === $type ) {
			$args['meta_query'] = array( array( 'key' => '_mf_members_only', 'value' => '1' ) );
			return new WP_Query( $args );
		}
		if ( 'recommended' === $type ) {
			$ids = self::recommended_ids( get_current_user_id(), $limit );
			$ids = array_values( array_filter( $ids, static function ( $id ) use ( $post_types ) {
				return in_array( get_post_type( $id ), $post_types, true );
			} ) );
			return new WP_Query( $args + array( 'post__in' => $ids ? $ids : array( 0 ), 'orderby' => 'post__in' ) );
		}
		if ( 0 === strpos( $type, 'genre:' ) ) {
			$args['tax_query'] = array( array( 'taxonomy' => 'genre', 'field' => 'slug', 'terms' => sanitize_title( substr( $type, 6 ) ) ) );
			return new WP_Query( $args );
		}
		return new WP_Query( $args );
	}

	/**
	 * Trending IDs: manual flag, automatic score, or both (see settings).
	 *
	 * @param int $limit Limit.
	 * @return int[]
	 */
	public static function trending_ids( $limit = 14 ) {
		$mode = mf_opt( 'trending_mode', 'both' );
		$ids  = array();
		if ( 'auto' !== $mode ) {
			$ids = get_posts( array( 'post_type' => array( 'movie', 'series' ), 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => $limit, 'no_found_rows' => true, 'update_post_term_cache' => false, 'meta_query' => array( array( 'key' => '_mf_trending', 'value' => '1' ) ) ) );
		}
		if ( 'manual' !== $mode && count( $ids ) < $limit ) {
			$ids = array_merge( $ids, self::auto_trending() );
		}
		return array_slice( array_values( array_unique( array_map( 'intval', $ids ) ) ), 0, $limit );
	}

	/** Automatic trending score (cached 1 hour): starts + 3x completions + 2x list adds in the last 14 days. */
	public static function auto_trending() {
		$cached = get_transient( 'mf_trending_auto' );
		if ( false !== $cached ) {
			return $cached;
		}
		global $wpdb;
		$since  = gmdate( 'Y-m-d H:i:s', time() - 14 * DAY_IN_SECONDS );
		$scores = array();
		$rows   = $wpdb->get_results( $wpdb->prepare(
			"SELECT movie_id, SUM(CASE WHEN event='complete' THEN 3 ELSE 1 END) s FROM {$wpdb->prefix}movieflix_views WHERE created_at >= %s GROUP BY movie_id",
			$since
		) );
		foreach ( (array) $rows as $r ) {
			$scores[ (int) $r->movie_id ] = (int) $r->s;
		}
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT movie_id, COUNT(*)*2 s FROM {$wpdb->prefix}movieflix_watchlist WHERE added_at >= %s GROUP BY movie_id",
			$since
		) );
		foreach ( (array) $rows as $r ) {
			$scores[ (int) $r->movie_id ] = ( $scores[ (int) $r->movie_id ] ?? 0 ) + (int) $r->s;
		}
		arsort( $scores );
		$ids = array_slice( array_keys( $scores ), 0, 30 );
		set_transient( 'mf_trending_auto', $ids, HOUR_IN_SECONDS );
		return $ids;
	}

	/** Recommended IDs from the user's list + history (genres, cast, director); popular/top rated for guests. */
	public static function recommended_ids( $user_id, $limit = 14 ) {
		global $wpdb;
		$seen = array();
		if ( $user_id ) {
			$seen = array_merge(
				mf_user_list_ids( $user_id ),
				array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT movie_id FROM {$wpdb->prefix}movieflix_watch_history WHERE user_id=%d ORDER BY last_watched DESC LIMIT 20", $user_id ) ) )
			);
		}
		$base = array( 'post_type' => array( 'movie', 'series' ), 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => 60, 'no_found_rows' => true, 'meta_key' => '_mf_sort_rating', 'orderby' => 'meta_value_num', 'order' => 'DESC' );
		if ( ! $seen ) {
			return get_posts( $base + array( 'posts_per_page' => $limit ) );
		}
		$genres = wp_get_object_terms( array_slice( $seen, 0, 20 ), 'genre', array( 'fields' => 'ids' ) );
		$cand   = get_posts( $base + array( 'post__not_in' => $seen, 'tax_query' => array( array( 'taxonomy' => 'genre', 'terms' => is_wp_error( $genres ) || ! $genres ? array( 0 ) : $genres ) ) ) );
		$people = array();
		foreach ( array_slice( $seen, 0, 10 ) as $sid ) {
			$people = array_merge( $people, (array) get_post_meta( $sid, '_mf_cast', true ), (array) get_post_meta( $sid, '_mf_directors', true ) );
		}
		$people = array_count_values( array_filter( $people ) );
		$scored = array();
		foreach ( $cand as $i => $cid ) {
			$sc = 100 - $i;
			foreach ( array_merge( (array) get_post_meta( $cid, '_mf_cast', true ), (array) get_post_meta( $cid, '_mf_directors', true ) ) as $p ) {
				$sc += 15 * ( $people[ $p ] ?? 0 );
			}
			$scored[ $cid ] = $sc;
		}
		arsort( $scored );
		$ids = array_slice( array_keys( $scored ), 0, $limit );
		return $ids ? $ids : get_posts( $base + array( 'posts_per_page' => $limit ) );
	}

	/** "More like this": scored by shared genre, cast, director, language, tags. Cached 6 hours. */
	public static function similar_ids( $post_id, $limit = 12 ) {
		$key = 'mf_sim_' . $post_id;
		$ids = get_transient( $key );
		if ( false === $ids ) {
			$type   = get_post_type( $post_id );
			$genres = wp_get_object_terms( $post_id, 'genre', array( 'fields' => 'ids' ) );
			$langs  = wp_get_object_terms( $post_id, 'mf_language', array( 'fields' => 'ids' ) );
			$tags   = wp_get_object_terms( $post_id, 'post_tag', array( 'fields' => 'ids' ) );
			$tq     = array( 'relation' => 'OR' );
			foreach ( array( 'genre' => $genres, 'mf_language' => $langs, 'post_tag' => $tags ) as $tax => $t ) {
				if ( $t && ! is_wp_error( $t ) ) {
					$tq[] = array( 'taxonomy' => $tax, 'terms' => $t );
				}
			}
			$ids = array();
			if ( count( $tq ) > 1 ) {
				$cand = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'post__not_in' => array( $post_id ), 'fields' => 'ids', 'posts_per_page' => 60, 'no_found_rows' => true, 'tax_query' => $tq ) );
				$mine = array_merge( (array) get_post_meta( $post_id, '_mf_cast', true ), (array) get_post_meta( $post_id, '_mf_directors', true ) );
				$sc   = array();
				foreach ( $cand as $cid ) {
					$s  = 3 * count( array_intersect( $genres, (array) wp_get_object_terms( $cid, 'genre', array( 'fields' => 'ids' ) ) ) );
					$s += 2 * count( array_intersect( $mine, array_merge( (array) get_post_meta( $cid, '_mf_cast', true ), (array) get_post_meta( $cid, '_mf_directors', true ) ) ) );
					$s += count( array_intersect( $langs, (array) wp_get_object_terms( $cid, 'mf_language', array( 'fields' => 'ids' ) ) ) );
					$s += count( array_intersect( $tags, (array) wp_get_object_terms( $cid, 'post_tag', array( 'fields' => 'ids' ) ) ) );
					$sc[ $cid ] = $s;
				}
				arsort( $sc );
				$ids = array_keys( $sc );
			}
			set_transient( $key, $ids, 6 * HOUR_IN_SECONDS );
		}
		return array_slice( $ids, 0, $limit );
	}

	/** @return WP_Post[] Episodes of a series ordered by season/episode. */
	public static function episodes( $series_id ) {
		return get_posts( array( 'post_type' => 'episode', 'post_status' => 'publish', 'posts_per_page' => 200, 'no_found_rows' => true, 'meta_key' => '_mf_ep_order', 'orderby' => 'meta_value_num', 'order' => 'ASC', 'meta_query' => array( array( 'key' => '_mf_series', 'value' => (int) $series_id ) ) ) );
	}

	/** @return WP_Post|null First episode of a series. */
	public static function first_episode( $series_id ) {
		$e = self::episodes( $series_id );
		return $e ? $e[0] : null;
	}

	/** @return array Continue-watching rows: [ID => [position,duration]] newest first. */
	public static function continue_rows( $user_id, $limit = 20 ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT movie_id, position, duration FROM {$wpdb->prefix}movieflix_watch_history WHERE user_id=%d AND completed=0 AND hidden=0 AND position>=10 ORDER BY last_watched DESC LIMIT %d",
			$user_id,
			$limit
		) );
		$out = array();
		foreach ( (array) $rows as $r ) {
			$out[ (int) $r->movie_id ] = array( 'position' => (int) $r->position, 'duration' => (int) $r->duration );
		}
		return $out;
	}

	/** @return array History rows: [ID => [position,duration,completed,last_watched]]. */
	public static function history_rows( $user_id, $limit = 60 ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT movie_id, position, duration, completed, last_watched FROM {$wpdb->prefix}movieflix_watch_history WHERE user_id=%d ORDER BY last_watched DESC LIMIT %d",
			$user_id,
			$limit
		) );
		$out = array();
		foreach ( (array) $rows as $r ) {
			$out[ (int) $r->movie_id ] = (array) $r;
		}
		return $out;
	}

	/** @return WP_Query Posts (any title/episode type) for IDs in the given order. */
	public static function by_ids( $ids, $limit = 60 ) {
		$ids = $ids ? array_map( 'intval', $ids ) : array( 0 );
		return new WP_Query( array( 'post_type' => array( 'movie', 'series', 'episode' ), 'post_status' => 'publish', 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => $limit, 'no_found_rows' => true ) );
	}

	/** @return WP_Query Movies/series featuring a person (cast or crew). */
	public static function person_titles( $person_id ) {
		$or = array( 'relation' => 'OR' );
		foreach ( array( '_mf_cast', '_mf_directors', '_mf_writers', '_mf_producers' ) as $key ) {
			$or[] = array( 'key' => $key, 'value' => '"' . (int) $person_id . '"', 'compare' => 'LIKE' );
		}
		return new WP_Query( array( 'post_type' => array( 'movie', 'series' ), 'post_status' => 'publish', 'posts_per_page' => 48, 'no_found_rows' => true, 'meta_query' => $or ) );
	}
}
