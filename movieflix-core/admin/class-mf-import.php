<?php
/**
 * CSV import, TMDB import, and bulk episode tools.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Import {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 25 );
		add_action( 'admin_post_mf_import_csv', array( __CLASS__, 'handle_csv' ) );
		add_action( 'admin_post_mf_import_tmdb', array( __CLASS__, 'handle_tmdb' ) );
		add_action( 'admin_post_mf_bulk_episodes', array( __CLASS__, 'handle_bulk_episodes' ) );
	}

	public static function menu() {
		add_submenu_page(
			'movieflix',
			__( 'Import & Bulk Tools', 'movieflix' ),
			__( 'Import / Bulk', 'movieflix' ),
			'manage_options',
			'movieflix-import',
			array( __CLASS__, 'page' )
		);
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$done = isset( $_GET['mf_done'] ) ? sanitize_text_field( wp_unslash( $_GET['mf_done'] ) ) : ''; // phpcs:ignore
		$series = get_posts( array(
			'post_type'      => 'series',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import & Bulk Tools', 'movieflix' ); ?></h1>
			<?php if ( $done ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $done ); ?></p></div>
			<?php endif; ?>

			<div class="mf-demo-panel" style="margin-top:16px">
				<h2><?php esc_html_e( '1. CSV import (movies)', 'movieflix' ); ?></h2>
				<p><?php esc_html_e( 'Upload a CSV with header row. Columns (any order): title, year, runtime, genres, language, country, rating, quality, description, video_url, poster_url, backdrop_url, imdb_rating, featured, trending, popular, new_release', 'movieflix' ); ?></p>
				<p class="description"><?php esc_html_e( 'Genres: comma-separated. Flags featured/trending/popular/new_release: 1 or 0.', 'movieflix' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="mf_import_csv">
					<?php wp_nonce_field( 'mf_import_csv' ); ?>
					<p><input type="file" name="csv_file" accept=".csv,text/csv" required></p>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Import CSV', 'movieflix' ); ?></button></p>
				</form>
			</div>

			<div class="mf-demo-panel">
				<h2><?php esc_html_e( '2. TMDB import', 'movieflix' ); ?></h2>
				<p><?php esc_html_e( 'Import movie or TV metadata from The Movie Database. Requires a free TMDB API key (v3). Video files are not downloaded — only poster, backdrop, overview, year, genres, rating.', 'movieflix' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mf_import_tmdb">
					<?php wp_nonce_field( 'mf_import_tmdb' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="tmdb_key"><?php esc_html_e( 'TMDB API key', 'movieflix' ); ?></label></th>
							<td><input type="text" class="regular-text" id="tmdb_key" name="tmdb_key" value="<?php echo esc_attr( get_option( 'mf_tmdb_api_key', '' ) ); ?>" required></td>
						</tr>
						<tr>
							<th><label for="tmdb_type"><?php esc_html_e( 'Type', 'movieflix' ); ?></label></th>
							<td>
								<select name="tmdb_type" id="tmdb_type">
									<option value="movie"><?php esc_html_e( 'Movie', 'movieflix' ); ?></option>
									<option value="tv"><?php esc_html_e( 'TV series', 'movieflix' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="tmdb_id"><?php esc_html_e( 'TMDB ID', 'movieflix' ); ?></label></th>
							<td><input type="number" id="tmdb_id" name="tmdb_id" min="1" required> <span class="description"><?php esc_html_e( 'From themoviedb.org URL, e.g. /movie/550 → 550', 'movieflix' ); ?></span></td>
						</tr>
					</table>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Import from TMDB', 'movieflix' ); ?></button></p>
				</form>
			</div>

			<div class="mf-demo-panel">
				<h2><?php esc_html_e( '3. Bulk add episodes', 'movieflix' ); ?></h2>
				<p><?php esc_html_e( 'Create empty episode shells for a season so you can upload videos one by one.', 'movieflix' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mf_bulk_episodes">
					<?php wp_nonce_field( 'mf_bulk_episodes' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="bulk_series"><?php esc_html_e( 'Series', 'movieflix' ); ?></label></th>
							<td>
								<select name="series_id" id="bulk_series" required>
									<option value=""><?php esc_html_e( '— Select —', 'movieflix' ); ?></option>
									<?php foreach ( $series as $s ) : ?>
										<option value="<?php echo (int) $s->ID; ?>"><?php echo esc_html( $s->post_title ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="bulk_season"><?php esc_html_e( 'Season number', 'movieflix' ); ?></label></th>
							<td><input type="number" id="bulk_season" name="season" min="1" value="1" required></td>
						</tr>
						<tr>
							<th><label for="bulk_from"><?php esc_html_e( 'From episode #', 'movieflix' ); ?></label></th>
							<td><input type="number" id="bulk_from" name="from" min="1" value="1" required></td>
						</tr>
						<tr>
							<th><label for="bulk_to"><?php esc_html_e( 'To episode #', 'movieflix' ); ?></label></th>
							<td><input type="number" id="bulk_to" name="to" min="1" value="10" required></td>
						</tr>
					</table>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Create episodes', 'movieflix' ); ?></button></p>
				</form>
			</div>
		</div>
		<?php
	}

	public static function handle_csv() {
		check_admin_referer( 'mf_import_csv' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'movieflix' ) );
		}
		if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
			self::redirect( __( 'No CSV file uploaded.', 'movieflix' ) );
		}
		$fh = fopen( $_FILES['csv_file']['tmp_name'], 'r' ); // phpcs:ignore
		if ( ! $fh ) {
			self::redirect( __( 'Could not read CSV.', 'movieflix' ) );
		}
		$header = fgetcsv( $fh );
		if ( ! $header ) {
			fclose( $fh );
			self::redirect( __( 'Empty CSV.', 'movieflix' ) );
		}
		$header = array_map( function ( $h ) {
			return strtolower( trim( (string) $h ) );
		}, $header );
		$count = 0;
		while ( ( $row = fgetcsv( $fh ) ) !== false ) {
			if ( count( $row ) < 1 || '' === trim( (string) $row[0] ) ) {
				continue;
			}
			$data = array();
			foreach ( $header as $i => $key ) {
				$data[ $key ] = isset( $row[ $i ] ) ? trim( (string) $row[ $i ] ) : '';
			}
			$title = $data['title'] ?? '';
			if ( '' === $title ) {
				continue;
			}
			$id = wp_insert_post( array(
				'post_type'    => 'movie',
				'post_status'  => 'publish',
				'post_title'   => sanitize_text_field( $title ),
				'post_content' => sanitize_textarea_field( $data['description'] ?? '' ),
				'post_excerpt' => sanitize_textarea_field( $data['description'] ?? '' ),
			) );
			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}
			$map = array(
				'year' => 'year', 'runtime' => 'runtime', 'short_desc' => 'description',
				'age_rating' => 'rating', 'imdb_rating' => 'imdb_rating',
				'poster_url' => 'poster_url', 'backdrop_url' => 'backdrop_url',
				'video_url' => 'video_url', 'audio_language' => 'language',
			);
			foreach ( $map as $meta => $col ) {
				if ( ! empty( $data[ $col ] ) ) {
					$v = $data[ $col ];
					if ( in_array( $meta, array( 'poster_url', 'backdrop_url', 'video_url' ), true ) ) {
						$v = esc_url_raw( $v );
					} elseif ( in_array( $meta, array( 'year', 'runtime' ), true ) ) {
						$v = absint( $v );
					} elseif ( 'imdb_rating' === $meta ) {
						$v = min( 10, max( 0, round( (float) $v, 1 ) ) );
					} else {
						$v = sanitize_text_field( $v );
					}
					update_post_meta( $id, '_mf_' . $meta, $v );
				}
			}
			if ( ! empty( $data['video_url'] ) ) {
				$vu = $data['video_url'];
				update_post_meta( $id, '_mf_video_type', false !== strpos( $vu, '.m3u8' ) ? 'hls' : 'mp4' );
			}
			foreach ( array( 'featured', 'trending', 'popular', 'new_release' ) as $flag ) {
				update_post_meta( $id, '_mf_' . $flag, ! empty( $data[ $flag ] ) && '0' !== $data[ $flag ] ? '1' : '0' );
			}
			if ( ! empty( $data['genres'] ) ) {
				$gs = array_map( 'trim', explode( ',', $data['genres'] ) );
				wp_set_object_terms( $id, $gs, 'genre' );
			}
			if ( ! empty( $data['language'] ) ) {
				wp_set_object_terms( $id, array( $data['language'] ), 'mf_language' );
			}
			if ( ! empty( $data['country'] ) ) {
				wp_set_object_terms( $id, array( $data['country'] ), 'mf_country' );
			}
			if ( ! empty( $data['rating'] ) ) {
				wp_set_object_terms( $id, array( $data['rating'] ), 'content_rating' );
			}
			if ( ! empty( $data['quality'] ) ) {
				wp_set_object_terms( $id, array( $data['quality'] ), 'quality' );
			}
			if ( ! empty( $data['year'] ) ) {
				wp_set_object_terms( $id, (string) absint( $data['year'] ), 'release_year' );
			}
			if ( function_exists( 'mf_update_sort_rating' ) ) {
				mf_update_sort_rating( $id );
			}
			$count++;
		}
		fclose( $fh );
		self::redirect( sprintf( __( 'Imported %d movies from CSV.', 'movieflix' ), $count ) );
	}

	public static function handle_tmdb() {
		check_admin_referer( 'mf_import_tmdb' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'movieflix' ) );
		}
		$key  = isset( $_POST['tmdb_key'] ) ? sanitize_text_field( wp_unslash( $_POST['tmdb_key'] ) ) : '';
		$type = isset( $_POST['tmdb_type'] ) && 'tv' === $_POST['tmdb_type'] ? 'tv' : 'movie';
		$tid  = isset( $_POST['tmdb_id'] ) ? absint( $_POST['tmdb_id'] ) : 0;
		if ( ! $key || ! $tid ) {
			self::redirect( __( 'API key and TMDB ID are required.', 'movieflix' ) );
		}
		update_option( 'mf_tmdb_api_key', $key, false );
		$url = sprintf( 'https://api.themoviedb.org/3/%s/%d?api_key=%s&language=en-US', $type, $tid, rawurlencode( $key ) );
		$res = wp_remote_get( $url, array( 'timeout' => 20 ) );
		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			self::redirect( __( 'TMDB request failed. Check API key and ID.', 'movieflix' ) );
		}
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( empty( $data['id'] ) ) {
			self::redirect( __( 'TMDB returned no data.', 'movieflix' ) );
		}
		$title = $type === 'tv' ? ( $data['name'] ?? '' ) : ( $data['title'] ?? '' );
		$overview = $data['overview'] ?? '';
		$year = 0;
		if ( $type === 'tv' && ! empty( $data['first_air_date'] ) ) {
			$year = (int) substr( $data['first_air_date'], 0, 4 );
		} elseif ( ! empty( $data['release_date'] ) ) {
			$year = (int) substr( $data['release_date'], 0, 4 );
		}
		$poster = ! empty( $data['poster_path'] ) ? 'https://image.tmdb.org/t/p/w500' . $data['poster_path'] : '';
		$back   = ! empty( $data['backdrop_path'] ) ? 'https://image.tmdb.org/t/p/w1280' . $data['backdrop_path'] : '';
		$runtime = $type === 'movie' ? (int) ( $data['runtime'] ?? 0 ) : 0;
		$imdb = isset( $data['vote_average'] ) ? round( (float) $data['vote_average'], 1 ) : 0;
		$post_type = $type === 'tv' ? 'series' : 'movie';
		$id = wp_insert_post( array(
			'post_type'    => $post_type,
			'post_status'  => 'publish',
			'post_title'   => sanitize_text_field( $title ),
			'post_content' => sanitize_textarea_field( $overview ),
			'post_excerpt' => sanitize_textarea_field( $overview ),
		) );
		if ( ! $id || is_wp_error( $id ) ) {
			self::redirect( __( 'Could not create post.', 'movieflix' ) );
		}
		update_post_meta( $id, '_mf_year', $year );
		update_post_meta( $id, '_mf_runtime', $runtime );
		update_post_meta( $id, '_mf_short_desc', sanitize_textarea_field( $overview ) );
		update_post_meta( $id, '_mf_imdb_rating', $imdb );
		update_post_meta( $id, '_mf_poster_url', esc_url_raw( $poster ) );
		update_post_meta( $id, '_mf_backdrop_url', esc_url_raw( $back ) );
		update_post_meta( $id, '_mf_tmdb_id', $tid );
		update_post_meta( $id, '_mf_new_release', '1' );
		if ( ! empty( $data['genres'] ) && is_array( $data['genres'] ) ) {
			$names = array();
			foreach ( $data['genres'] as $g ) {
				if ( ! empty( $g['name'] ) ) {
					$names[] = $g['name'];
				}
			}
			if ( $names ) {
				wp_set_object_terms( $id, $names, 'genre' );
			}
		}
		if ( $year ) {
			wp_set_object_terms( $id, (string) $year, 'release_year' );
		}
		if ( function_exists( 'mf_update_sort_rating' ) ) {
			mf_update_sort_rating( $id );
		}
		$edit = get_edit_post_link( $id, 'raw' );
		self::redirect( sprintf( __( 'Imported “%1$s”. Edit it to add a video URL: %2$s', 'movieflix' ), $title, $edit ) );
	}

	public static function handle_bulk_episodes() {
		check_admin_referer( 'mf_bulk_episodes' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'movieflix' ) );
		}
		$series = isset( $_POST['series_id'] ) ? absint( $_POST['series_id'] ) : 0;
		$season = isset( $_POST['season'] ) ? max( 1, absint( $_POST['season'] ) ) : 1;
		$from   = isset( $_POST['from'] ) ? max( 1, absint( $_POST['from'] ) ) : 1;
		$to     = isset( $_POST['to'] ) ? max( $from, absint( $_POST['to'] ) ) : $from;
		if ( $to - $from > 50 ) {
			$to = $from + 50;
		}
		if ( ! $series || 'series' !== get_post_type( $series ) ) {
			self::redirect( __( 'Invalid series.', 'movieflix' ) );
		}
		$stitle = get_the_title( $series );
		$n = 0;
		for ( $e = $from; $e <= $to; $e++ ) {
			$eid = wp_insert_post( array(
				'post_type'   => 'episode',
				'post_status' => 'publish',
				'post_title'  => sprintf( '%s S%02dE%02d', $stitle, $season, $e ),
			) );
			if ( $eid && ! is_wp_error( $eid ) ) {
				update_post_meta( $eid, '_mf_series', $series );
				update_post_meta( $eid, '_mf_season', $season );
				update_post_meta( $eid, '_mf_episode', $e );
				update_post_meta( $eid, '_mf_ep_order', $season * 1000 + $e );
				update_post_meta( $eid, '_mf_runtime', 45 );
				$n++;
			}
		}
		self::redirect( sprintf( __( 'Created %d episodes for season %d.', 'movieflix' ), $n, $season ) );
	}

	private static function redirect( $msg ) {
		wp_safe_redirect( add_query_arg( 'mf_done', rawurlencode( $msg ), admin_url( 'admin.php?page=movieflix-import' ) ) );
		exit;
	}
}
