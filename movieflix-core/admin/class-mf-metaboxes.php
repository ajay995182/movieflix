<?php
/**
 * Movie / Series / Episode / Person editors (tabbed metaboxes), admin columns,
 * duplicate action and bulk flag actions.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Metaboxes {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_mf_add_episode', array( __CLASS__, 'ajax_add_episode' ) );
		add_action( 'wp_ajax_mf_list_episodes', array( __CLASS__, 'ajax_list_episodes' ) );
		foreach ( array( 'movie', 'series' ) as $t ) {
			add_filter( "manage_{$t}_posts_columns", array( __CLASS__, 'columns' ) );
			add_action( "manage_{$t}_posts_custom_column", array( __CLASS__, 'column_content' ), 10, 2 );
			add_filter( "bulk_actions-edit-{$t}", array( __CLASS__, 'bulk_actions' ) );
			add_filter( "handle_bulk_actions-edit-{$t}", array( __CLASS__, 'handle_bulk' ), 10, 3 );
		}
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'admin_post_mf_duplicate', array( __CLASS__, 'duplicate' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	/** @return array Field definitions grouped by section for a post type. */
	public static function fields( $type ) {
		$video = array(
			'video_url'     => array( 'Main video / stream URL', 'media', 'Paste an MP4/WebM/HLS URL <strong>or</strong> use the <strong>Upload</strong> tab below to upload a video file from your computer. You can also pick a file from the Media Library with the button.' ),
			'video_type'    => array( 'Video source type', 'select', '', array( 'auto' => 'Auto-detect', 'mp4' => 'MP4', 'webm' => 'WebM', 'hls' => 'HLS (.m3u8)' ) ),
			'video_sources' => array( 'Quality sources (optional)', 'textarea', 'One per line: <code>Label|URL</code> e.g. <code>1080p|https://cdn.example/movie-1080.mp4</code>. Enables the quality selector for MP4/WebM.' ),
			'subtitles'     => array( 'Subtitle tracks (optional)', 'textarea', 'One per line: <code>Label|code|URL(.vtt)</code> e.g. <code>English|en|https://…/en.vtt</code>. WebVTT only.' ),
			'audio_tracks'  => array( 'Alternate audio tracks (optional)', 'textarea', 'One per line: <code>Label|URL</code> e.g. <code>English|https://…/en.mp4</code> or <code>Hindi|https://…/hi.m3u8</code>. Switches the whole media source.' ),
			'intro_start'   => array( 'Intro start (seconds)', 'number', 'Used for Skip Intro. Example: 10' ),
			'intro_end'     => array( 'Intro end (seconds)', 'number', 'Skip jumps to this time. Example: 85' ),
			'members_only'  => array( 'Members only (login required to watch)', 'checkbox' ),
		);
		if ( 'channel' === $type ) {
			return array(
				'Channel' => array(
					'channel_logo'     => array( 'Channel logo', 'media', 'Leave empty to use the Featured Image.' ),
					'channel_number'   => array( 'Channel number', 'text' ),
					'channel_category' => array( 'Category', 'text', 'Optional, for example News or Sports.' ),
					'short_desc'       => array( 'Short description', 'textarea', 'Shown on the channel card and watch page.' ),
				),
				'Live Stream' => array(
					'video_url'    => array( 'Live stream URL', 'media', 'MP4/WebM or HLS (.m3u8) stream URL you are licensed to distribute.' ),
					'video_type'   => array( 'Stream type', 'select', '', array( 'auto' => 'Auto-detect', 'mp4' => 'MP4', 'webm' => 'WebM', 'hls' => 'HLS (.m3u8)' ) ),
					'members_only' => array( 'Members only (login required to watch)', 'checkbox' ),
				),
				'Upload' => array(
					'uploader' => array( 'Upload video / image file from your computer', 'uploader' ),
				),
			);
		}
		if ( 'episode' === $type ) {
			return array(
				'Episode' => array(
					'series'    => array( 'Series', 'series' ),
					'season'    => array( 'Season number', 'number' ),
					'episode'   => array( 'Episode number', 'number' ),
					'runtime'   => array( 'Runtime (minutes)', 'number' ),
					'backdrop_url' => array( 'Thumbnail / backdrop', 'media' ),
				),
				'Streaming' => $video,
				'Upload' => array(
					'uploader' => array( 'Upload video / image file from your computer', 'uploader' ),
				),
			);
		}
		if ( 'person' === $type ) {
			return array(
				'Person' => array(
					'poster_url' => array( 'Photo', 'media' ),
					'known_for'  => array( 'Known for (short text)', 'text' ),
					'birth_date' => array( 'Birth date', 'text', 'Free text, e.g. 12 March 1975' ),
					'birthplace' => array( 'Birthplace', 'text' ),
				),
			);
		}
		if ( 'series' === $type ) {
			return array(
				'Basic Information' => array(
					'short_desc'   => array( 'Short description', 'textarea', 'Shown on the hero and cards. The main description is the editor above.' ),
					'year'         => array( 'Release year', 'number' ),
					'age_rating'   => array( 'Age rating label', 'text', 'e.g. TV-14 (also set the Content Rating taxonomy for filtering)' ),
					'content_type' => array( 'Content type', 'select', '', array( 'series' => 'TV Series', 'miniseries' => 'Mini-series', 'anime' => 'Anime', 'documentary' => 'Documentary series' ) ),
					'imdb_rating'  => array( 'IMDb-style rating (0-10)', 'decimal' ),
					'custom_rating' => array( 'Custom rating (0-10)', 'decimal' ),
					'audio_language' => array( 'Audio language(s)', 'text' ),
				),
				'Media' => array(
					'poster_url'   => array( 'Poster (2:3)', 'media', 'Leave empty to use the Featured Image.' ),
					'backdrop_url' => array( 'Backdrop (16:9)', 'media' ),
					'trailer_url'  => array( 'Trailer URL (MP4/WebM/YouTube/Vimeo)', 'media' ),
				),
				'Episodes' => array(
					'episodes_manager' => array( 'Seasons & episodes', 'episodes_manager' ),
				),
				'Cast & Crew' => array(
					'cast'      => array( 'Cast (hold Ctrl/Cmd to select several)', 'people' ),
					'directors' => array( 'Director(s)', 'people' ),
					'writers'   => array( 'Writer(s)', 'people' ),
					'producers' => array( 'Producer(s)', 'people' ),
				),
				'Streaming defaults' => array(
					'subtitles'    => array( 'Default subtitle tracks', 'textarea', 'Applied as a template; each episode can override. One per line: Label|code|URL(.vtt)' ),
					'members_only' => array( 'Members only (login required to watch)', 'checkbox' ),
				),
				'Upload' => array(
					'uploader' => array( 'Upload poster, backdrop or trailer file', 'uploader' ),
				),
				'SEO' => array(
					'seo_title' => array( 'SEO title', 'text' ),
					'seo_desc'  => array( 'Meta description', 'textarea', 'Up to ~155 characters.' ),
				),
				'Display Options' => array(
					'featured'    => array( 'Featured (hero candidate)', 'checkbox' ),
					'trending'    => array( 'Trending', 'checkbox' ),
					'popular'     => array( 'Popular', 'checkbox' ),
					'new_release' => array( 'New release', 'checkbox' ),
				),
			);
		}
		return array(
			'Basic Information' => array(
				'short_desc'   => array( 'Short description', 'textarea', 'Shown on the hero and cards. The main description is the editor above.' ),
				'year'         => array( 'Release year', 'number' ),
				'runtime'      => array( 'Runtime (minutes)', 'number' ),
				'age_rating'   => array( 'Age rating label', 'text', 'e.g. PG-13 (also set the Content Rating taxonomy for filtering)' ),
				'content_type' => array( 'Content type', 'select', '', array( 'movie' => 'Feature film', 'documentary' => 'Documentary', 'short' => 'Short film', 'anime' => 'Anime', 'special' => 'Special' ) ),
				'imdb_rating'  => array( 'IMDb-style rating (0-10)', 'decimal' ),
				'custom_rating' => array( 'Custom rating (0-10)', 'decimal' ),
				'audio_language' => array( 'Audio language(s)', 'text' ),
			),
			'Media' => array(
				'poster_url'   => array( 'Poster (2:3)', 'media', 'Leave empty to use the Featured Image.' ),
				'backdrop_url' => array( 'Backdrop (16:9)', 'media' ),
				'trailer_url'  => array( 'Trailer URL (MP4/WebM/YouTube/Vimeo)', 'media' ),
			),
			'Movie Details' => array(
				'resolution'        => array( 'Resolution', 'select', '', array( '' => '—', '480p' => '480p', '720p' => '720p', '1080p' => '1080p', '1440p' => '1440p', '2160p' => '4K (2160p)' ) ),
				'subtitles_available' => array( 'Subtitles available', 'checkbox' ),
				'download_enabled'  => array( 'Allow downloads', 'checkbox' ),
				'download_url'      => array( 'Download URL', 'url' ),
			),
			'Cast & Crew' => array(
				'cast'      => array( 'Cast (hold Ctrl/Cmd to select several)', 'people' ),
				'directors' => array( 'Director(s)', 'people' ),
				'writers'   => array( 'Writer(s)', 'people' ),
				'producers' => array( 'Producer(s)', 'people' ),
			),
			'Streaming' => $video,
			'Upload' => array(
				'uploader' => array( 'Upload video / image file from your computer', 'uploader' ),
			),
			'SEO' => array(
				'seo_title' => array( 'SEO title', 'text' ),
				'seo_desc'  => array( 'Meta description', 'textarea', 'Up to ~155 characters.' ),
			),
			'Display Options' => array(
				'featured'    => array( 'Featured (hero candidate)', 'checkbox' ),
				'trending'    => array( 'Trending', 'checkbox' ),
				'popular'     => array( 'Popular', 'checkbox' ),
				'new_release' => array( 'New release', 'checkbox' ),
			),
		);
	}

	public static function add() {
		foreach ( array( 'movie' => 'Movie Editor', 'series' => 'Series Editor', 'episode' => 'Episode Details', 'person' => 'Person Details', 'channel' => 'Channel Details' ) as $t => $title ) {
			add_meta_box( 'mf_fields', 'MovieFlix — ' . $title, array( __CLASS__, 'render' ), $t, 'normal', 'high' );
		}
	}

	public static function assets( $hook ) {
		$screen = get_current_screen();
		if ( $screen && in_array( $screen->post_type, array( 'movie', 'series', 'episode', 'person', 'channel' ), true ) || false !== strpos( (string) $hook, 'movieflix' ) ) {
			wp_enqueue_media();
			wp_enqueue_style( 'mf-admin', MF_URL . 'assets/css/admin.css', array(), MF_VERSION );
			wp_enqueue_script( 'mf-admin', MF_URL . 'assets/js/admin.js', array( 'jquery' ), MF_VERSION, true );
			wp_localize_script( 'mf-admin', 'mfAdmin', array( 'nonce' => wp_create_nonce( 'mf_upload' ) ) );
		}
	}

	/** Render the tabbed editor. */
	public static function render( $post ) {
		wp_nonce_field( 'mf_save_' . $post->ID, 'mf_meta_nonce' );
		$sections = self::fields( $post->post_type );
		echo '<div class="mf-tabs"><ul class="mf-tab-nav">';
		$i = 0;
		foreach ( array_keys( $sections ) as $name ) {
			printf( '<li><a href="#mf-tab-%1$d" class="%3$s">%2$s</a></li>', $i, esc_html( $name ), 0 === $i ? 'active' : '' );
			$i++;
		}
		echo '</ul>';
		$i = 0;
		foreach ( $sections as $name => $fields ) {
			echo '<div class="mf-tab-panel" id="mf-tab-' . (int) $i . '"' . ( $i ? ' style="display:none"' : '' ) . '>';
			foreach ( $fields as $key => $f ) {
				self::field( $post->ID, $key, $f );
			}
			echo '</div>';
			$i++;
		}
		echo '</div>';
	}

	private static function field( $post_id, $key, $f ) {
		$val  = get_post_meta( $post_id, '_mf_' . $key, true );
		$name = 'mf[' . $key . ']';
		$id   = 'mf_' . $key;
		echo '<div class="mf-field"><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $f[0] ) . '</strong></label>';
		switch ( $f[1] ) {
			case 'textarea':
				printf( '<textarea id="%s" name="%s" rows="4">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $val ) );
				break;
			case 'number':
				printf( '<input type="number" min="0" step="1" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $val ) );
				break;
			case 'decimal':
				printf( '<input type="number" min="0" max="10" step="0.1" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $val ) );
				break;
			case 'checkbox':
				printf( '<input type="checkbox" id="%s" name="%s" value="1" %s>', esc_attr( $id ), esc_attr( $name ), checked( '1', (string) $val, false ) );
				break;
			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
				foreach ( $f[3] as $k => $label ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( (string) $val, (string) $k, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;
			case 'media':
				printf( '<span class="mf-media"><input type="text" class="widefat" id="%s" name="%s" value="%s" placeholder="https://… or pick from Media Library"> <button type="button" class="button mf-media-btn" data-target="%s">Media Library</button></span>', esc_attr( $id ), esc_attr( $name ), esc_attr( $val ), esc_attr( $id ) );
				break;
			case 'people':
				$sel = array_map( 'strval', (array) $val );
				echo '<select multiple size="6" class="widefat" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '[]">';
				foreach ( self::people() as $pid => $title ) {
					printf( '<option value="%d" %s>%s</option>', (int) $pid, selected( in_array( (string) $pid, $sel, true ), true, false ), esc_html( $title ) );
				}
				echo '</select><p class="description"><a href="' . esc_url( admin_url( 'post-new.php?post_type=person' ) ) . '" target="_blank">Add a new person</a> (reload this page afterwards).</p>';
				break;
			case 'series':
				if ( '' === $val && isset( $_GET['mf_series'] ) ) { // phpcs:ignore
					$val = absint( $_GET['mf_series'] ); // phpcs:ignore
				}
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"><option value="">— Select series —</option>';
				foreach ( get_posts( array( 'post_type' => 'series', 'post_status' => array( 'publish', 'draft', 'private' ), 'posts_per_page' => 300, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true ) ) as $s ) {
					printf( '<option value="%d" %s>%s</option>', (int) $s->ID, selected( (int) $val, $s->ID, false ), esc_html( $s->post_title ) );
				}
				echo '</select>';
				break;
			case 'episodes_manager':
				self::render_episodes_manager( $post_id );
				break;
			case 'uploader':
				$targets = '';
				foreach ( array( 'video_url' => 'Video / Stream URL', 'poster_url' => 'Poster', 'backdrop_url' => 'Backdrop', 'trailer_url' => 'Trailer', 'channel_logo' => 'Channel logo' ) as $k => $label ) {
					$targets .= sprintf( '<option value="mf_%s">%s</option>', esc_attr( $k ), esc_html( $label ) );
				}
				echo '<div class="mf-uploader" data-nonce="' . esc_attr( wp_create_nonce( 'mf_upload' ) ) . '">';
				echo '<p class="description" style="margin-top:0"><strong>Upload a video or image file</strong> from your computer. Large video files are uploaded in chunks so they will not time out. After upload finishes, the URL is filled into the field you select below.</p>';
				echo '<div class="mf-up-targets"><label>Fill this field after upload: <select class="mf-up-target">' . $targets . '</select></label></div>';
				echo '<div class="mf-up-row"><input type="file" class="mf-up-file" accept="video/*,image/*,.vtt,.srt,.m3u8"> <button type="button" class="button button-primary mf-up-start">Upload file</button></div>';
				echo '<div class="mf-up-progress" style="display:none"><div class="mf-up-barwrap"><div class="mf-up-bar"></div><span class="mf-up-pct">0%</span></div><div class="mf-up-status"></div><div class="mf-up-name"></div></div>';
				echo '</div>';
				break;
			default:
				printf( '<input type="%s" class="widefat" id="%s" name="%s" value="%s">', 'url' === $f[1] ? 'url' : 'text', esc_attr( $id ), esc_attr( $name ), esc_attr( $val ) );
		}
		if ( ! empty( $f[2] ) ) {
			echo '<p class="description">' . wp_kses_post( $f[2] ) . '</p>';
		}
		echo '</div>';
	}

	/** @return array id => title of all people (cached per request). */
	private static function people() {
		static $people = null;
		if ( null === $people ) {
			$people = array();
			foreach ( get_posts( array( 'post_type' => 'person', 'post_status' => 'publish', 'posts_per_page' => 500, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true, 'update_post_meta_cache' => false ) ) as $p ) {
				$people[ $p->ID ] = $p->post_title;
			}
		}
		return $people;
	}

	/** Save metabox values with per-field sanitization. */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['mf_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mf_meta_nonce'] ) ), 'mf_save_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['mf'] ) ? wp_unslash( (array) $_POST['mf'] ) : array(); // phpcs:ignore
		foreach ( self::fields( $post->post_type ) as $fields ) {
			foreach ( $fields as $key => $f ) {
				if ( in_array( $f[1], array( 'uploader', 'episodes_manager' ), true ) ) { continue; }
			$raw = isset( $in[ $key ] ) ? $in[ $key ] : '';
				switch ( $f[1] ) {
					case 'checkbox':
						$v = isset( $in[ $key ] ) ? '1' : '0';
						break;
					case 'number':
					case 'series':
						$v = '' === $raw ? '' : absint( $raw );
						break;
					case 'decimal':
						$v = '' === $raw ? '' : min( 10, max( 0, round( (float) $raw, 1 ) ) );
						break;
					case 'textarea':
						$v = in_array( $key, array( 'video_sources', 'subtitles', 'audio_tracks' ), true ) ? self::clean_lines( $raw ) : sanitize_textarea_field( $raw );
						break;
					case 'media':
					case 'url':
						$v = ( 0 === strpos( (string) $raw, 'demo:' ) ) ? sanitize_text_field( $raw ) : esc_url_raw( $raw );
						break;
					case 'people':
						$v = array_values( array_unique( array_map( 'strval', array_filter( array_map( 'absint', (array) $raw ) ) ) ) );
						break;
					case 'select':
						$v = isset( $f[3][ $raw ] ) ? sanitize_text_field( $raw ) : '';
						break;
					default:
						$v = sanitize_text_field( $raw );
				}
				update_post_meta( $post_id, '_mf_' . $key, $v );
			}
		}
		if ( 'episode' === $post->post_type ) {
			$s = (int) get_post_meta( $post_id, '_mf_season', true );
			$e = (int) get_post_meta( $post_id, '_mf_episode', true );
			update_post_meta( $post_id, '_mf_ep_order', $s * 1000 + $e );
		}
		if ( in_array( $post->post_type, mf_title_types(), true ) ) {
			mf_update_sort_rating( $post_id );
			delete_transient( 'mf_sim_' . $post_id );
		}
	}

	/** Keep only "a|b|c" style lines with sanitized parts. */
	private static function clean_lines( $raw ) {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
			$parts = array_map( 'trim', explode( '|', $line ) );
			if ( count( $parts ) < 2 || '' === $parts[0] ) {
				continue;
			}
			$url = array_pop( $parts );
			$url = ( 0 === strpos( $url, 'demo:' ) ) ? sanitize_text_field( $url ) : esc_url_raw( $url );
			if ( $url ) {
				$out[] = implode( '|', array_map( 'sanitize_text_field', $parts ) ) . '|' . $url;
			}
		}
		return implode( "\n", $out );
	}

	/* ---------- Admin list columns ---------- */

	public static function columns( $cols ) {
		$new = array( 'cb' => $cols['cb'], 'mf_poster' => 'Poster' );
		unset( $cols['cb'], $cols['comments'] );
		$new += $cols;
		$new['mf_year']  = 'Year';
		$new['mf_flags'] = 'Flags';
		$new['mf_views'] = 'Views';
		return $new;
	}

	public static function column_content( $col, $post_id ) {
		if ( 'mf_poster' === $col ) {
			echo '<img src="' . esc_url( mf_img( $post_id, 'poster', 'thumbnail' ) ) . '" alt="" style="width:46px;height:69px;object-fit:cover;border-radius:4px">';
		} elseif ( 'mf_year' === $col ) {
			echo esc_html( mf_meta( $post_id, 'year', '—' ) );
		} elseif ( 'mf_views' === $col ) {
			echo (int) mf_meta( $post_id, 'views', 0 );
		} elseif ( 'mf_flags' === $col ) {
			$labels = array( 'featured' => 'Featured', 'trending' => 'Trending', 'popular' => 'Popular', 'new_release' => 'New' );
			foreach ( $labels as $k => $l ) {
				if ( '1' === (string) get_post_meta( $post_id, '_mf_' . $k, true ) ) {
					echo '<span class="mf-flag">' . esc_html( $l ) . '</span> ';
				}
			}
		}
	}

	public static function bulk_actions( $a ) {
		$a['mf_feature']   = 'MovieFlix: Mark Featured';
		$a['mf_unfeature'] = 'MovieFlix: Remove Featured';
		$a['mf_trend']     = 'MovieFlix: Mark Trending';
		$a['mf_untrend']   = 'MovieFlix: Remove Trending';
		$a['mf_popular']   = 'MovieFlix: Mark Popular';
		$a['mf_new']       = 'MovieFlix: Mark New Release';
		return $a;
	}

	public static function handle_bulk( $redirect, $action, $ids ) {
		$map = array( 'mf_feature' => array( 'featured', '1' ), 'mf_unfeature' => array( 'featured', '0' ), 'mf_trend' => array( 'trending', '1' ), 'mf_untrend' => array( 'trending', '0' ), 'mf_popular' => array( 'popular', '1' ), 'mf_new' => array( 'new_release', '1' ) );
		if ( ! isset( $map[ $action ] ) ) {
			return $redirect;
		}
		$n = 0;
		foreach ( $ids as $id ) {
			if ( current_user_can( 'edit_post', $id ) ) {
				update_post_meta( $id, '_mf_' . $map[ $action ][0], $map[ $action ][1] );
				$n++;
			}
		}
		return add_query_arg( 'mf_bulk', $n, $redirect );
	}

	public static function notices() {
		if ( isset( $_GET['mf_bulk'] ) ) { // phpcs:ignore
			echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'MovieFlix: %d item(s) updated.', 'movieflix' ), absint( $_GET['mf_bulk'] ) ) . '</p></div>'; // phpcs:ignore
		}
		if ( isset( $_GET['mf_dup'] ) ) { // phpcs:ignore
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Duplicated as a draft.', 'movieflix' ) . '</p></div>';
		}
	}

	/* ---------- Duplicate ---------- */

	public static function row_actions( $actions, $post ) {
		if ( in_array( $post->post_type, array( 'movie', 'series', 'episode', 'person', 'channel' ), true ) && current_user_can( 'edit_post', $post->ID ) ) {
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=mf_duplicate&post=' . $post->ID ), 'mf_dup_' . $post->ID );
			$actions['mf_dup'] = '<a href="' . esc_url( $url ) . '">Duplicate</a>';
		}
		return $actions;
	}

	public static function duplicate() {
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( 'mf_dup_' . $id );
		$src = get_post( $id );
		if ( ! $src || ! current_user_can( 'edit_post', $id ) ) {
			wp_die( esc_html__( 'You are not allowed to duplicate this item.', 'movieflix' ) );
		}
		$new = wp_insert_post( array(
			'post_type'    => $src->post_type,
			'post_title'   => $src->post_title . ' (Copy)',
			'post_content' => $src->post_content,
			'post_excerpt' => $src->post_excerpt,
			'post_status'  => 'draft',
			'post_author'  => get_current_user_id(),
		) );
		if ( $new && ! is_wp_error( $new ) ) {
			foreach ( get_post_meta( $id ) as $k => $vals ) {
				if ( 0 === strpos( $k, '_mf_' ) && ! in_array( $k, array( '_mf_views', '_mf_rating_avg', '_mf_rating_count', '_mf_demo' ), true ) ) {
					foreach ( $vals as $v ) {
						add_post_meta( $new, $k, maybe_unserialize( $v ) );
					}
				}
			}
			foreach ( get_object_taxonomies( $src->post_type ) as $tax ) {
				wp_set_object_terms( $new, wp_get_object_terms( $id, $tax, array( 'fields' => 'ids' ) ), $tax );
			}
			if ( has_post_thumbnail( $id ) ) {
				set_post_thumbnail( $new, get_post_thumbnail_id( $id ) );
			}
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . $src->post_type . '&mf_dup=1' ) );
		exit;
	}

	/** Render season/episode manager on the Series edit screen. */
	public static function render_episodes_manager( $series_id ) {
		$series_id = (int) $series_id;
		if ( $series_id < 1 ) {
			echo '<p class="description">' . esc_html__( 'Save this series first, then you can add seasons and episodes here.', 'movieflix' ) . '</p>';
			return;
		}
		$eps = get_posts( array(
			'post_type'      => 'episode',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 500,
			'meta_key'       => '_mf_series',
			'meta_value'     => $series_id,
			'orderby'        => 'meta_value_num',
			'meta_query'     => array( // phpcs:ignore
				'relation' => 'AND',
				array( 'key' => '_mf_series', 'value' => $series_id ),
			),
			'no_found_rows'  => true,
		) );
		// Sort by season then episode.
		usort( $eps, function ( $a, $b ) {
			$sa = (int) get_post_meta( $a->ID, '_mf_season', true );
			$sb = (int) get_post_meta( $b->ID, '_mf_season', true );
			if ( $sa !== $sb ) {
				return $sa - $sb;
			}
			return (int) get_post_meta( $a->ID, '_mf_episode', true ) - (int) get_post_meta( $b->ID, '_mf_episode', true );
		} );

		$by_season = array();
		foreach ( $eps as $ep ) {
			$s = max( 1, (int) get_post_meta( $ep->ID, '_mf_season', true ) );
			$by_season[ $s ][] = $ep;
		}
		ksort( $by_season );

		echo '<div class="mf-ep-manager" data-series="' . esc_attr( $series_id ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'mf_upload' ) ) . '">';
		echo '<p class="description" style="margin-top:0">' . esc_html__( 'Add episodes by season. Each episode can have its own video file or stream URL (edit the episode to upload video).', 'movieflix' ) . '</p>';

		echo '<div class="mf-ep-add">';
		echo '<strong>' . esc_html__( 'Quick add episode', 'movieflix' ) . '</strong> ';
		echo '<label>' . esc_html__( 'Season', 'movieflix' ) . ' <input type="number" class="mf-ep-season" min="1" value="1" style="width:70px"></label> ';
		echo '<label>' . esc_html__( 'Episode', 'movieflix' ) . ' <input type="number" class="mf-ep-num" min="1" value="' . esc_attr( self::next_episode_number( $series_id, 1 ) ) . '" style="width:70px"></label> ';
		echo '<label>' . esc_html__( 'Title', 'movieflix' ) . ' <input type="text" class="mf-ep-title" placeholder="' . esc_attr__( 'Episode title', 'movieflix' ) . '" style="min-width:180px"></label> ';
		echo '<button type="button" class="button button-primary mf-ep-create">' . esc_html__( 'Add episode', 'movieflix' ) . '</button>';
		echo '<span class="mf-ep-status" style="margin-left:8px;color:#646970"></span>';
		echo '</div>';

		echo '<div class="mf-ep-list">';
		if ( empty( $by_season ) ) {
			echo '<p class="mf-ep-empty">' . esc_html__( 'No episodes yet. Use the form above to add the first one.', 'movieflix' ) . '</p>';
		} else {
			foreach ( $by_season as $season => $list ) {
				echo '<div class="mf-ep-season-block"><h4>' . esc_html( sprintf( __( 'Season %d', 'movieflix' ), $season ) ) . ' <span class="mf-ep-count">(' . count( $list ) . ')</span></h4><table class="widefat striped mf-ep-table"><thead><tr><th style="width:60px">#</th><th>' . esc_html__( 'Title', 'movieflix' ) . '</th><th style="width:90px">' . esc_html__( 'Runtime', 'movieflix' ) . '</th><th style="width:100px">' . esc_html__( 'Video', 'movieflix' ) . '</th><th style="width:120px">' . esc_html__( 'Actions', 'movieflix' ) . '</th></tr></thead><tbody>';
				foreach ( $list as $ep ) {
					$enum = (int) get_post_meta( $ep->ID, '_mf_episode', true );
					$rt   = (int) get_post_meta( $ep->ID, '_mf_runtime', true );
					$vid  = (string) get_post_meta( $ep->ID, '_mf_video_url', true );
					$has  = '' !== $vid;
					echo '<tr>';
					echo '<td>E' . (int) $enum . '</td>';
					echo '<td><strong>' . esc_html( $ep->post_title ) . '</strong></td>';
					echo '<td>' . ( $rt ? esc_html( $rt . ' min' ) : '—' ) . '</td>';
					echo '<td>' . ( $has ? '<span style="color:#00a32a">● ' . esc_html__( 'Set', 'movieflix' ) . '</span>' : '<span style="color:#d63638">○ ' . esc_html__( 'Missing', 'movieflix' ) . '</span>' ) . '</td>';
					echo '<td><a class="button button-small" href="' . esc_url( get_edit_post_link( $ep->ID, 'raw' ) ) . '">' . esc_html__( 'Edit / upload video', 'movieflix' ) . '</a></td>';
					echo '</tr>';
				}
				echo '</tbody></table></div>';
			}
		}
		echo '</div>';
		echo '<p style="margin-top:12px"><a class="button" href="' . esc_url( admin_url( 'post-new.php?post_type=episode&mf_series=' . $series_id ) ) . '">' . esc_html__( 'Open full episode editor', 'movieflix' ) . '</a> ';
		echo '<a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=episode&mf_series_filter=' . $series_id ) ) . '">' . esc_html__( 'All episodes list', 'movieflix' ) . '</a></p>';
		echo '</div>';
	}

	/** Next episode number suggestion for a season. */
	private static function next_episode_number( $series_id, $season ) {
		$eps = get_posts( array(
			'post_type'      => 'episode',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore
				array( 'key' => '_mf_series', 'value' => (int) $series_id ),
				array( 'key' => '_mf_season', 'value' => (int) $season ),
			),
			'no_found_rows'  => true,
		) );
		$max = 0;
		foreach ( $eps as $id ) {
			$max = max( $max, (int) get_post_meta( $id, '_mf_episode', true ) );
		}
		return $max + 1;
	}

	/** AJAX: create a new episode under a series. */
	public static function ajax_add_episode() {
		check_ajax_referer( 'mf_upload', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'movieflix' ) ), 403 );
		}
		$series  = isset( $_POST['series'] ) ? absint( $_POST['series'] ) : 0;
		$season  = isset( $_POST['season'] ) ? max( 1, absint( $_POST['season'] ) ) : 1;
		$enum    = isset( $_POST['episode'] ) ? max( 1, absint( $_POST['episode'] ) ) : 1;
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( ! $series || 'series' !== get_post_type( $series ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid series.', 'movieflix' ) ), 400 );
		}
		if ( ! current_user_can( 'edit_post', $series ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'movieflix' ) ), 403 );
		}
		$series_title = get_the_title( $series );
		if ( '' === $title ) {
			$title = sprintf( '%s S%02dE%02d', $series_title, $season, $enum );
		}
		$eid = wp_insert_post( array(
			'post_type'   => 'episode',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_content'=> '',
		), true );
		if ( is_wp_error( $eid ) ) {
			wp_send_json_error( array( 'message' => $eid->get_error_message() ), 500 );
		}
		update_post_meta( $eid, '_mf_series', $series );
		update_post_meta( $eid, '_mf_season', $season );
		update_post_meta( $eid, '_mf_episode', $enum );
		update_post_meta( $eid, '_mf_ep_order', $season * 1000 + $enum );
		update_post_meta( $eid, '_mf_runtime', 45 );
		wp_send_json_success( array(
			'message'  => __( 'Episode created. Reload or edit it to upload video.', 'movieflix' ),
			'edit_url' => get_edit_post_link( $eid, 'raw' ),
			'id'       => $eid,
		) );
	}

	/** AJAX: refresh episode list HTML (optional). */
	public static function ajax_list_episodes() {
		check_ajax_referer( 'mf_upload', 'nonce' );
		$series = isset( $_POST['series'] ) ? absint( $_POST['series'] ) : 0;
		if ( ! $series || ! current_user_can( 'edit_post', $series ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'movieflix' ) ), 403 );
		}
		ob_start();
		self::render_episodes_manager( $series );
		wp_send_json_success( array( 'html' => ob_get_clean() ) );
	}

}
