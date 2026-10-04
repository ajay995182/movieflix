<?php
/**
 * Demo content importer (fictional titles + unique poster/backdrop images).
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Demo {

	/**
	 * Build image URL. Accepts full http(s) URL, TMDB path (/xxx.jpg), or seed for placeholder.
	 *
	 * @param string $seed_or_path Title seed, TMDB path, or full URL.
	 * @param int    $w            Width (placeholder only).
	 * @param int    $h            Height (placeholder only).
	 * @param string $size         TMDB size key (w500, original, w1280…).
	 * @return string
	 */
	private static function img( $seed_or_path, $w = 400, $h = 600, $size = 'w500' ) {
		$s = (string) $seed_or_path;
		if ( 0 === strpos( $s, 'http://' ) || 0 === strpos( $s, 'https://' ) ) {
			return $s;
		}
		if ( 0 === strpos( $s, '/' ) && false !== strpos( $s, '.jpg' ) ) {
			return 'https://image.tmdb.org/t/p/' . $size . $s;
		}
		$key = sanitize_title( $s );
		if ( '' === $key ) {
			$key = 'movieflix';
		}
		return 'https://picsum.photos/seed/' . rawurlencode( $key ) . '/' . (int) $w . '/' . (int) $h;
	}

	/** @return string Result message. Safe to run twice (skips if demo already imported). */
	public static function import() {
		MF_Content::register_post_types();
		MF_Content::register_taxonomies();
		if ( class_exists( 'MF_Extras' ) ) {
			MF_Extras::register_channel();
		}
		MF_Content::seed_terms();

		if ( get_posts( array(
			'post_type'      => 'movie',
			'meta_key'       => '_mf_demo',
			'meta_value'     => '1',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) ) ) {
			return 'Demo content already imported.';
		}

		$d      = include MF_DIR . 'includes/demo-data.php';
		$subs   = 'English|en|demo:sample-en.vtt';
		$people = array();
		$person_count = 0;

		foreach ( $d['people'] as $i => $name ) {
			$id = wp_insert_post( array(
				'post_type'    => 'person',
				'post_status'  => 'publish',
				'post_title'   => $name,
				'post_content' => $name . ' is a fictional demo person created by MovieFlix to show cast and crew pages.',
			) );
			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_mf_poster_url', self::img( 'person-' . $name, 400, 400 ) );
			update_post_meta( $id, '_mf_known_for', 'Demo titles' );
			update_post_meta( $id, '_mf_demo', '1' );
			$people[ $i ] = $id;
			$person_count++;
		}

		$movie_count   = 0;
		$series_count  = 0;
		$episode_count = 0;
		$channel_count = 0;

		foreach ( $d['movies'] as $i => $m ) {
			// title, year, runtime, genres, lang, country, rating, quality, desc, cast, dir, imdb, flags, video [, poster_path, backdrop_path]
			$title   = $m[0];
			$year    = $m[1];
			$runtime = $m[2];
			$genres  = $m[3];
			$lang    = $m[4];
			$country = $m[5];
			$crating = $m[6];
			$quality = $m[7];
			$desc    = $m[8];
			$cast    = $m[9];
			$dir     = $m[10];
			$imdb    = $m[11];
			$flags   = $m[12];
			$video   = $m[13];
			$poster  = isset( $m[14] ) ? $m[14] : ( $title . '-poster' );
			$back    = isset( $m[15] ) ? $m[15] : ( $title . '-backdrop' );
			$id = wp_insert_post( array(
				'post_type'    => 'movie',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => $desc . "\n\nDemo content bundled with MovieFlix for preview.",
				'post_excerpt' => $desc,
			) );
			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}
			$cast_ids = array();
			foreach ( (array) $cast as $k ) {
				if ( isset( $people[ $k ] ) ) {
					$cast_ids[] = (string) $people[ $k ];
				}
			}
			$dir_id = isset( $people[ $dir ] ) ? (string) $people[ $dir ] : '';
			$w_id   = isset( $people[ ( $dir + 1 ) % max( 1, count( $people ) ) ] ) ? (string) $people[ ( $dir + 1 ) % count( $people ) ] : '';
			$p_id   = isset( $people[ ( $dir + 2 ) % max( 1, count( $people ) ) ] ) ? (string) $people[ ( $dir + 2 ) % count( $people ) ] : '';

			$meta = array(
				'year'                 => $year,
				'runtime'              => $runtime,
				'short_desc'           => $desc,
				'age_rating'           => $crating,
				'imdb_rating'          => $imdb,
				'poster_url'           => self::img( $poster, 400, 600, 'w500' ),
				'backdrop_url'         => self::img( $back, 1280, 720, 'w1280' ),
				'video_url'            => $video,
				'video_type'           => ( false !== strpos( $video, '.m3u8' ) ) ? 'hls' : 'mp4',
				'subtitles'            => $subs,
				'subtitles_available'  => '1',
				'audio_language'       => $lang,
				'resolution'           => '1080p',
				'content_type'         => 'movie',
				'cast'                 => $cast_ids,
				'directors'            => $dir_id ? array( $dir_id ) : array(),
				'writers'              => $w_id ? array( $w_id ) : array(),
				'producers'            => $p_id ? array( $p_id ) : array(),
				'views'                => 200 - $i * 5,
				'demo'                 => '1',
				'featured'             => in_array( 'featured', $flags, true ) ? '1' : '0',
				'trending'             => in_array( 'trending', $flags, true ) ? '1' : '0',
				'popular'              => in_array( 'popular', $flags, true ) ? '1' : '0',
				'new_release'          => in_array( 'new_release', $flags, true ) ? '1' : '0',
			);
			foreach ( $meta as $k => $v ) {
				update_post_meta( $id, '_mf_' . $k, $v );
			}
			if ( function_exists( 'mf_update_sort_rating' ) ) {
				mf_update_sort_rating( $id );
			}
			wp_set_object_terms( $id, $genres, 'genre' );
			wp_set_object_terms( $id, array( $lang ), 'mf_language' );
			wp_set_object_terms( $id, array( $country ), 'mf_country' );
			wp_set_object_terms( $id, array( $crating ), 'content_rating' );
			wp_set_object_terms( $id, array( $quality ), 'quality' );
			wp_set_object_terms( $id, (string) $year, 'release_year' );
			$movie_count++;
		}

		foreach ( $d['series'] as $si => $s ) {
			// title, year, genres, lang, country, rating, quality, desc, episodes, cast, dir, imdb, flags [, poster, backdrop]
			$title    = $s[0];
			$year     = $s[1];
			$genres   = $s[2];
			$lang     = $s[3];
			$country  = $s[4];
			$crating  = $s[5];
			$quality  = $s[6];
			$desc     = $s[7];
			$episodes = $s[8];
			$cast     = $s[9];
			$dir      = $s[10];
			$imdb     = $s[11];
			$flags    = $s[12];
			$poster   = isset( $s[13] ) ? $s[13] : ( $title . '-poster' );
			$back     = isset( $s[14] ) ? $s[14] : ( $title . '-backdrop' );
			$sid = wp_insert_post( array(
				'post_type'    => 'series',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => $desc . "\n\nDemo content bundled with MovieFlix for preview.",
				'post_excerpt' => $desc,
			) );
			if ( ! $sid || is_wp_error( $sid ) ) {
				continue;
			}
			$cast_ids = array();
			foreach ( (array) $cast as $k ) {
				if ( isset( $people[ $k ] ) ) {
					$cast_ids[] = (string) $people[ $k ];
				}
			}
			$dir_id = isset( $people[ $dir ] ) ? (string) $people[ $dir ] : '';

			$series_meta = array(
				'year'         => $year,
				'short_desc'   => $desc,
				'age_rating'   => $crating,
				'imdb_rating'  => $imdb,
				'poster_url'   => self::img( $poster, 400, 600, 'w500' ),
				'backdrop_url' => self::img( $back, 1280, 720, 'w1280' ),
				'featured'     => in_array( 'featured', $flags, true ) ? '1' : '0',
				'popular'      => in_array( 'popular', $flags, true ) ? '1' : '0',
				'trending'     => in_array( 'trending', $flags, true ) ? '1' : '0',
				'new_release'  => in_array( 'new_release', $flags, true ) ? '1' : '0',
				'demo'         => '1',
				'views'        => 150 - $si * 8,
				'content_type' => 'series',
				'cast'         => $cast_ids,
				'directors'    => $dir_id ? array( $dir_id ) : array(),
			);
			foreach ( $series_meta as $k => $v ) {
				update_post_meta( $sid, '_mf_' . $k, $v );
			}
			if ( function_exists( 'mf_update_sort_rating' ) ) {
				mf_update_sort_rating( $sid );
			}
			wp_set_object_terms( $sid, $genres, 'genre' );
			wp_set_object_terms( $sid, array( $lang ), 'mf_language' );
			wp_set_object_terms( $sid, array( $country ), 'mf_country' );
			wp_set_object_terms( $sid, array( $crating ), 'content_rating' );
			wp_set_object_terms( $sid, array( $quality ), 'quality' );
			wp_set_object_terms( $sid, (string) $year, 'release_year' );
			$series_count++;

			foreach ( $episodes as $e ) {
				$eid = wp_insert_post( array(
					'post_type'    => 'episode',
					'post_status'  => 'publish',
					'post_title'   => $title . ' S' . $e[0] . 'E' . $e[1] . ' – ' . $e[2],
					'post_content' => 'Demo episode of ' . $title . '.',
					'post_excerpt' => $e[2],
				) );
				if ( ! $eid || is_wp_error( $eid ) ) {
					continue;
				}
				foreach ( array(
					'series'       => $sid,
					'season'       => $e[0],
					'episode'      => $e[1],
					'ep_order'     => $e[0] * 1000 + $e[1],
					'runtime'      => 45,
					'video_url'    => $e[3],
					'video_type'   => ( false !== strpos( $e[3], '.m3u8' ) ) ? 'hls' : 'mp4',
					'subtitles'    => $subs,
					'backdrop_url' => self::img( $title . '-ep-' . $e[0] . '-' . $e[1], 1280, 720 ),
					'demo'         => '1',
				) as $k => $v ) {
					update_post_meta( $eid, '_mf_' . $k, $v );
				}
				$episode_count++;
			}
		}

		if ( ! empty( $d['channels'] ) && post_type_exists( 'channel' ) ) {
			foreach ( $d['channels'] as $ci => $ch ) {
				list( $ctitle, $cat, $num, $cdesc, $stream, $seed ) = $ch;
				$cid = wp_insert_post( array(
					'post_type'    => 'channel',
					'post_status'  => 'publish',
					'post_title'   => $ctitle,
					'post_content' => $cdesc . "\n\nThis is a fictional Live TV demo channel.",
					'post_excerpt' => $cdesc,
				) );
				if ( ! $cid || is_wp_error( $cid ) ) {
					continue;
				}
				$is_hls = ( false !== strpos( $stream, '.m3u8' ) );
				foreach ( array(
					'channel_logo'     => self::img( 'channel-' . $seed, 400, 400 ),
					'channel_number'   => $num,
					'channel_category' => $cat,
					'short_desc'       => $cdesc,
					'video_url'        => $stream,
					'video_type'       => $is_hls ? 'hls' : 'mp4',
					'poster_url'       => self::img( 'channel-poster-' . $seed, 400, 600 ),
					'backdrop_url'     => self::img( 'channel-bg-' . $seed, 1280, 720 ),
					'demo'             => '1',
					'views'            => 80 - $ci * 3,
				) as $k => $v ) {
					update_post_meta( $cid, '_mf_' . $k, $v );
				}
				$channel_count++;
			}
		}

		delete_transient( 'mf_trending_auto' );
		return sprintf(
			'Imported %d movies, %d series, %d episodes, %d channels and %d people.',
			$movie_count,
			$series_count,
			$episode_count,
			$channel_count,
			$person_count
		);
	}

	/** @return string Result message. */
	public static function remove() {
		$ids = get_posts( array(
			'post_type'      => array( 'movie', 'series', 'episode', 'person', 'channel' ),
			'meta_key'       => '_mf_demo',
			'meta_value'     => '1',
			'posts_per_page' => 1000,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		$n = 0;
		foreach ( $ids as $id ) {
			wp_delete_post( $id, true );
			$n++;
		}
		delete_transient( 'mf_trending_auto' );
		return sprintf( 'Removed %d demo items.', $n );
	}
}
