<?php
/**
 * Reusable template functions.
 *
 * @package MovieFlix
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return string Video type: mp4|webm|hls for a post. */
function movieflix_video_type( $post_id ) {
	$type = mf_meta( $post_id, 'video_type', 'auto' );
	$url  = (string) mf_meta( $post_id, 'video_url' );
	if ( 'auto' === $type || '' === $type ) {
		$path = strtolower( wp_parse_url( $url, PHP_URL_PATH ) ?: '' );
		if ( substr( $path, -5 ) === '.m3u8' ) {
			return 'hls';
		}
		return substr( $path, -5 ) === '.webm' ? 'webm' : 'mp4';
	}
	return $type;
}

/** @return array Parse "a|b|url" lines into arrays of parts. */
function movieflix_lines( $post_id, $key ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) mf_meta( $post_id, $key ) ) as $line ) {
		$parts = explode( '|', $line );
		if ( count( $parts ) >= 2 ) {
			$parts[ count( $parts ) - 1 ] = 'video_sources' === $key
				? mf_video_url( end( $parts ) )
				: mf_resolve_url( end( $parts ) );
			$out[] = $parts;
		}
	}
	return $out;
}

/** Print a notice from the ?mf_msg= code. */
function movieflix_notice() {
	if ( empty( $_GET['mf_msg'] ) ) { // phpcs:ignore
		return;
	}
	$code = sanitize_key( wp_unslash( $_GET['mf_msg'] ) ); // phpcs:ignore
	$msgs = mf_messages();
	if ( isset( $msgs[ $code ] ) ) {
		printf( '<div class="mf-notice mf-notice--%1$s" role="alert">%2$s</div>', esc_attr( $msgs[ $code ][0] ), esc_html( $msgs[ $code ][1] ) );
	}
}

/**
 * Attractive empty state.
 *
 * @param string $title Title.
 * @param string $text  Text.
 * @param string $cta   Button label.
 * @param string $url   Button URL.
 */
function movieflix_empty( $title, $text = '', $cta = '', $url = '' ) {
	echo '<div class="mf-empty"><div class="mf-empty__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" focusable="false"><path d="M4 5.5h16v13H4zM4 9h16M8 5.5l2.2 3.5m3.5-3.5L16 9" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></div><h2>' . esc_html( $title ) . '</h2>';
	if ( $text ) {
		echo '<p>' . esc_html( $text ) . '</p>';
	}
	if ( $cta && $url ) {
		echo '<a class="mf-btn mf-btn--primary" href="' . esc_url( $url ) . '">' . esc_html( $cta ) . '</a>';
	}
	echo '</div>';
}

/** @return string Stars markup for a 0-5 value. */
function movieflix_stars( $value ) {
	$pct = max( 0, min( 100, $value / 5 * 100 ) );
	return '<span class="mf-stars" role="img" aria-label="' . esc_attr( sprintf( __( '%s out of 5 stars', 'movieflix' ), number_format_i18n( $value, 1 ) ) ) . '"><span class="mf-stars__fill" style="width:' . (float) $pct . '%"></span></span>';
}

/**
 * Render one movie/series/episode card.
 *
 * @param WP_Post|int|null $post     Post.
 * @param array            $args     progress (0-100), remove (bool).
 */
function movieflix_card( $post = null, $args = array() ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$id     = $post->ID;
	$art_id = $id;
	$link   = get_permalink( $post );
	if ( 'episode' === $post->post_type ) {
		$parent = (int) mf_meta( $id, 'series' );
		if ( $parent && 'publish' === get_post_status( $parent ) ) {
			$art_id = $parent;
			$link   = get_permalink( $parent );
		}
	}
	$title   = get_the_title( $post );
	$year    = mf_meta( $art_id, 'year' );
	$rating  = mf_display_rating( $art_id );
	$quality = get_the_terms( $art_id, 'quality' );
	$q       = $quality && ! is_wp_error( $quality ) ? $quality[0]->name : '';
	$inlist  = mf_in_list( $art_id );
	$watch   = mf_watch_url( $id );
	echo '<article class="mf-card mf-card--' . esc_attr( $post->post_type ) . '">';
	$poster_src = esc_url( mf_img( $art_id, 'poster', 'movieflix-poster' ) );
	$poster_fb  = defined( 'MF_URL' ) ? esc_url( MF_URL . 'assets/demo/default-poster.svg' ) : '';
	echo '<a class="mf-card__poster" href="' . esc_url( $link ) . '" aria-label="' . esc_attr( sprintf( __( 'View details for %s', 'movieflix' ), $title ) ) . '"><img src="' . $poster_src . '" alt="" width="300" height="450" loading="lazy" decoding="async"';
	if ( $poster_fb ) {
		echo ' onerror="this.onerror=null;this.src=\'' . esc_js( $poster_fb ) . '\'"';
	}
	echo '></a>';
	if ( $q ) {
		echo '<span class="mf-badge">' . esc_html( $q ) . '</span>';
	}
	if ( '1' === (string) mf_meta( $art_id, 'members_only' ) ) {
		echo '<span class="mf-badge mf-badge--members">' . esc_html__( 'Members', 'movieflix' ) . '</span>';
	}
	if ( isset( $args['progress'] ) ) {
		echo '<div class="mf-progress" aria-hidden="true"><span style="width:' . (int) $args['progress'] . '%"></span></div>';
	}
	echo '<div class="mf-card__body"><h3 class="mf-card__title"><a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a></h3><p class="mf-card__meta">';
	echo esc_html( $year );
	if ( $rating ) {
		echo ' <span class="mf-rate" title="' . esc_attr__( 'Rating', 'movieflix' ) . '"><span aria-hidden="true">★</span> ' . esc_html( $rating ) . '</span>';
	}
	if ( isset( $args['remaining'] ) && (int) $args['remaining'] > 0 ) {
		echo ' <span class="mf-remaining">' . esc_html( sprintf( __( '%s min left', 'movieflix' ), number_format_i18n( (int) $args['remaining'] ) ) ) . '</span>';
	}
	echo '</p><div class="mf-card__actions">';
	echo '<a class="mf-icon-btn mf-icon-btn--play" href="' . esc_url( $watch ) . '" aria-label="' . esc_attr( sprintf( __( 'Watch %s', 'movieflix' ), $title ) ) . '"><svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true"><path d="m8 5 11 7-11 7z" fill="currentColor"/></svg></a>';
	if ( mf_opt( 'enable_watchlist', 1 ) && 'episode' !== $post->post_type ) {
		printf( '<button type="button" class="mf-icon-btn mf-list-btn%1$s" data-id="%2$d" aria-pressed="%3$s" aria-label="%4$s">%5$s</button>', $inlist ? ' is-active' : '', (int) $id, $inlist ? 'true' : 'false', esc_attr( $inlist ? __( 'Remove from My List', 'movieflix' ) : __( 'Add to My List', 'movieflix' ) ), $inlist ? '✓' : '+' ); // phpcs:ignore
	}
	echo '<a class="mf-icon-btn" href="' . esc_url( $link ) . '" aria-label="' . esc_attr( sprintf( __( 'Details for %s', 'movieflix' ), $title ) ) . '"><svg viewBox="0 0 24 24" width="17" height="17" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12 11v5m0-8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></a>';
	if ( ! empty( $args['remove'] ) ) {
		echo '<button type="button" class="mf-icon-btn mf-remove-progress" data-id="' . (int) $id . '" aria-label="' . esc_attr__( 'Remove from Continue Watching', 'movieflix' ) . '">×</button>';
	}
	echo '</div></div></article>';
}

/**
 * Render a horizontally scrolling row. Prints nothing if the query is empty.
 *
 * @param string   $title Row title.
 * @param WP_Query $q     Query.
 * @param array    $args  more (url), progress (id=>pct map), remove (bool).
 */
function movieflix_row( $title, $q, $args = array() ) {
	if ( ! $q->have_posts() ) {
		return;
	}
	$sid = 'row-' . sanitize_title( $title );
	echo '<section class="mf-row" aria-labelledby="' . esc_attr( $sid ) . '"><div class="mf-row__head"><h2 id="' . esc_attr( $sid ) . '">' . esc_html( $title ) . '</h2>';
	if ( ! empty( $args['more'] ) ) {
		echo '<a href="' . esc_url( $args['more'] ) . '">' . esc_html__( 'See all', 'movieflix' ) . ' ›</a>';
	}
	echo '</div><div class="mf-row__wrap"><button type="button" class="mf-row__nav mf-row__nav--prev" aria-label="' . esc_attr__( 'Scroll left', 'movieflix' ) . '">‹</button><div class="mf-row__track" tabindex="0">';
	foreach ( $q->posts as $p ) {
		$card = array( 'remove' => ! empty( $args['remove'] ) );
		if ( isset( $args['progress'][ $p->ID ] ) ) {
			$card['progress'] = $args['progress'][ $p->ID ];
		}
		if ( isset( $args['remaining'][ $p->ID ] ) ) {
			$card['remaining'] = $args['remaining'][ $p->ID ];
		}
		movieflix_card( $p, $card );
	}
	echo '</div><button type="button" class="mf-row__nav mf-row__nav--next" aria-label="' . esc_attr__( 'Scroll right', 'movieflix' ) . '">›</button></div></section>';
	wp_reset_postdata();
}

/** @return array Progress percentages [post_id => pct] from continue rows. */
function movieflix_progress_map( $rows ) {
	$map = array();
	foreach ( $rows as $id => $r ) {
		$map[ $id ] = $r['duration'] > 0 ? (int) min( 100, round( $r['position'] / $r['duration'] * 100 ) ) : 0;
	}
	return $map;
}

/** @return array Remaining minutes [post_id => minutes] for unfinished rows. */
function movieflix_remaining_map( $rows ) {
	$map = array();
	foreach ( $rows as $id => $row ) {
		$left = max( 0, (int) $row['duration'] - (int) $row['position'] );
		if ( $left > 0 ) {
			$map[ $id ] = (int) ceil( $left / 60 );
		}
	}
	return $map;
}

/** Paginate a query, preserving filters. */
function movieflix_pagination( $q, $custom = false ) {
	$total = (int) $q->max_num_pages;
	if ( $total < 2 ) {
		return;
	}
	$current = max( 1, (int) ( $custom ? ( isset( $_GET['paged'] ) ? $_GET['paged'] : 1 ) : get_query_var( 'paged', 1 ) ) ); // phpcs:ignore
	$keep    = array();
	foreach ( array( 's', 'mf_genre', 'mf_year', 'mf_lang', 'mf_country', 'mf_min', 'mf_quality', 'mf_crating', 'mf_sort' ) as $k ) {
		if ( ! empty( $_GET[ $k ] ) ) { // phpcs:ignore
			$keep[ $k ] = sanitize_text_field( wp_unslash( $_GET[ $k ] ) ); // phpcs:ignore
		}
	}
	$args = array( 'total' => $total, 'current' => $current, 'prev_text' => '‹ ' . __( 'Prev', 'movieflix' ), 'next_text' => __( 'Next', 'movieflix' ) . ' ›', 'add_args' => $keep, 'type' => 'list' );
	if ( $custom ) {
		$args['base']   = esc_url_raw( add_query_arg( 'paged', '%#%', get_permalink() ) );
		$args['format'] = '';
	}
	echo '<nav class="mf-pagination" aria-label="' . esc_attr__( 'Pagination', 'movieflix' ) . '">' . wp_kses_post( paginate_links( $args ) ) . '</nav>';
}

/** Filter + sort bar (GET form). */
function movieflix_filters( $action = '' ) {
	$action = $action ? $action : ( is_search() || is_page() ? ( is_page() ? get_permalink() : home_url( '/' ) ) : '' );
	$g      = function ( $k ) {
		return isset( $_GET[ $k ] ) ? sanitize_text_field( wp_unslash( $_GET[ $k ] ) ) : ''; // phpcs:ignore
	};
	$sel = function ( $name, $label, $options ) use ( $g ) {
		echo '<label class="screen-reader-text" for="f-' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label><select id="f-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"><option value="">' . esc_html( $label ) . '</option>';
		foreach ( $options as $v => $l ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $v ), selected( $g( $name ), (string) $v, false ), esc_html( $l ) );
		}
		echo '</select>';
	};
	$terms = function ( $tax ) {
		$t = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true ) );
		$o = array();
		if ( ! is_wp_error( $t ) ) {
			foreach ( $t as $term ) {
				$o[ $term->slug ] = $term->name;
			}
		}
		return $o;
	};
	$years = $terms( 'release_year' );
	krsort( $years );
	echo '<form class="mf-filters" method="get" action="' . esc_url( $action ) . '" role="search" aria-label="' . esc_attr__( 'Filter titles', 'movieflix' ) . '">';
	echo '<input type="search" name="s" value="' . esc_attr( $g( 's' ) ) . '" placeholder="' . esc_attr__( 'Title, actor, director, genre…', 'movieflix' ) . '" aria-label="' . esc_attr__( 'Search', 'movieflix' ) . '">';
	$sel( 'mf_genre', __( 'Genre', 'movieflix' ), $terms( 'genre' ) );
	$yr = array();
	foreach ( $years as $slug => $name ) {
		$yr[ $name ] = $name;
	}
	$sel( 'mf_year', __( 'Year', 'movieflix' ), $yr );
	$sel( 'mf_lang', __( 'Language', 'movieflix' ), $terms( 'mf_language' ) );
	$sel( 'mf_country', __( 'Country', 'movieflix' ), $terms( 'mf_country' ) );
	$sel( 'mf_min', __( 'Min rating', 'movieflix' ), array( '9' => '9+', '8' => '8+', '7' => '7+', '6' => '6+', '5' => '5+' ) );
	$sel( 'mf_quality', __( 'Quality', 'movieflix' ), $terms( 'quality' ) );
	$sel( 'mf_crating', __( 'Content rating', 'movieflix' ), $terms( 'content_rating' ) );
	echo '<select name="mf_sort" aria-label="' . esc_attr__( 'Sort by', 'movieflix' ) . '">';
	foreach ( array( 'newest' => __( 'Newest', 'movieflix' ), 'oldest' => __( 'Oldest', 'movieflix' ), 'rated' => __( 'Highest rated', 'movieflix' ), 'popular' => __( 'Most popular', 'movieflix' ), 'az' => 'A–Z', 'za' => 'Z–A' ) as $v => $l ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $v ), selected( $g( 'mf_sort' ) ? $g( 'mf_sort' ) : 'newest', $v, false ), esc_html( $l ) );
	}
	echo '</select><button class="mf-btn mf-btn--primary" type="submit">' . esc_html__( 'Apply', 'movieflix' ) . '</button> <a class="mf-btn" href="' . esc_url( $action ? $action : '?' ) . '">' . esc_html__( 'Reset', 'movieflix' ) . '</a></form>';
}

/** @return string Linked list of people for a meta key. */
function movieflix_people( $post_id, $key ) {
	$ids = array_filter( array_map( 'absint', (array) mf_meta( $post_id, $key, array() ) ) );
	if ( ! $ids ) {
		return '';
	}
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'person', 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => 50, 'no_found_rows' => true ) ) as $p ) {
		$out[] = '<a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( $p->post_title ) . '</a>';
	}
	return implode( ', ', $out );
}

/** @return string Term links for a taxonomy. */
function movieflix_terms( $post_id, $tax ) {
	$t = get_the_terms( $post_id, $tax );
	if ( ! $t || is_wp_error( $t ) ) {
		return '';
	}
	return implode( ', ', array_map( function ( $x ) {
		return '<a href="' . esc_url( get_term_link( $x ) ) . '">' . esc_html( $x->name ) . '</a>';
	}, $t ) );
}

/** @return string Embed URL for YouTube/Vimeo trailers, or ''. */
function movieflix_embed_url( $url ) {
	if ( preg_match( '#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,15})#', $url, $m ) ) {
		return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0';
	}
	if ( preg_match( '#vimeo\.com/(?:video/)?(\d+)#', $url, $m ) ) {
		return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1';
	}
	return '';
}

/** Logo markup (settings logo > custom logo > text). */
function movieflix_logo() {
	$name = mf_opt( 'site_name', 'MovieFlix' );
	echo '<a class="mf-logo" href="' . esc_url( home_url( '/' ) ) . '" aria-label="' . esc_attr( $name ) . '">';
	if ( mf_opt( 'logo' ) ) {
		echo '<img src="' . esc_url( mf_resolve_url( mf_opt( 'logo' ) ) ) . '" alt="' . esc_attr( $name ) . '" height="32">';
	} elseif ( has_custom_logo() ) {
		$logo = wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'full', false, array( 'class' => 'mf-logo__img', 'alt' => $name ) );
		echo wp_kses_post( $logo );
	} else {
		echo '<span class="mf-logo__text">' . esc_html( $name ) . '</span>';
	}
	echo '</a>';
}

/** Primary navigation (menu if assigned, otherwise sensible defaults). */
function movieflix_nav() {
	$kids = class_exists( 'MF_Extras' ) && MF_Extras::is_kids_profile();
	if ( has_nav_menu( 'primary' ) && ! $kids ) {
		wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'mf-menu', 'depth' => 2 ) );
		return;
	}
	echo '<ul class="mf-menu">';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'movieflix' ) . '</a></li>';
	if ( $kids ) {
		echo '<li><a href="' . esc_url( get_post_type_archive_link( 'movie' ) ) . '">' . esc_html__( 'Movies', 'movieflix' ) . '</a></li>';
		echo '<li><a href="' . esc_url( mf_page_url( 'tv-shows' ) ?: get_post_type_archive_link( 'series' ) ) . '">' . esc_html__( 'TV Shows', 'movieflix' ) . '</a></li>';
		$kids_genres = array( 'Kids', 'Family', 'Animation' );
		echo '<li class="menu-item-has-children"><a href="' . esc_url( mf_page_url( 'genres' ) ) . '">' . esc_html__( 'Kids genres', 'movieflix' ) . '</a><ul class="sub-menu">';
		foreach ( $kids_genres as $gn ) {
			$term = get_term_by( 'name', $gn, 'genre' );
			if ( $term && ! is_wp_error( $term ) ) {
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					echo '<li><a href="' . esc_url( $link ) . '">' . esc_html( $gn ) . '</a></li>';
				}
			}
		}
		echo '</ul></li>';
		echo '<li><a href="' . esc_url( mf_page_url( 'profiles' ) ) . '">' . esc_html__( 'Switch profile', 'movieflix' ) . '</a></li>';
	} else {
		echo '<li><a href="' . esc_url( get_post_type_archive_link( 'movie' ) ) . '">' . esc_html__( 'Movies', 'movieflix' ) . '</a></li>';
		echo '<li><a href="' . esc_url( mf_page_url( 'tv-shows' ) ?: get_post_type_archive_link( 'series' ) ) . '">' . esc_html__( 'TV Shows', 'movieflix' ) . '</a></li>';
		echo '<li><a href="' . esc_url( mf_page_url( 'new-popular' ) ) . '">' . esc_html__( 'New & Popular', 'movieflix' ) . '</a></li>';
		if ( mf_opt( 'enable_channels', 1 ) ) {
			echo '<li><a href="' . esc_url( mf_page_url( 'channels' ) ) . '">' . esc_html__( 'Live TV', 'movieflix' ) . '</a></li>';
		}
		$genres = get_terms( array( 'taxonomy' => 'genre', 'hide_empty' => true, 'number' => 20 ) );
		echo '<li class="menu-item-has-children"><a href="' . esc_url( mf_page_url( 'genres' ) ) . '"' . ( $genres && ! is_wp_error( $genres ) ? ' aria-haspopup="true"' : '' ) . '>' . esc_html__( 'Genres', 'movieflix' ) . '</a>';
		if ( $genres && ! is_wp_error( $genres ) ) {
			echo '<ul class="sub-menu">';
			foreach ( $genres as $g ) {
				$genre_link = get_term_link( $g );
				if ( ! is_wp_error( $genre_link ) ) {
					echo '<li><a href="' . esc_url( $genre_link ) . '">' . esc_html( $g->name ) . '</a></li>';
				}
			}
			echo '</ul>';
		}
		echo '</li>';
		if ( mf_opt( 'enable_watchlist', 1 ) ) {
			echo '<li><a href="' . esc_url( mf_page_url( 'my-list' ) ) . '">' . esc_html__( 'My List', 'movieflix' ) . '</a></li>';
		}
	}
	echo '</ul>';
}

/** Redirect guests to the login page (returning here afterwards). */
function movieflix_require_login() {
	if ( is_user_logged_in() ) {
		return;
	}
	$target = add_query_arg( array( 'mf_msg' => 'login_required', 'redirect_to' => rawurlencode( get_permalink() ) ), mf_page_url( 'login' ) );
	wp_safe_redirect( $target );
	exit;
}

/** Print a grid of cards from a WP_Query. */
function movieflix_grid( $q, $args = array() ) {
	echo '<div class="mf-grid">';
	foreach ( $q->posts as $p ) {
		$card = array( 'remove' => ! empty( $args['remove'] ) );
		if ( isset( $args['progress'][ $p->ID ] ) ) {
			$card['progress'] = $args['progress'][ $p->ID ];
		}
		if ( isset( $args['remaining'][ $p->ID ] ) ) {
			$card['remaining'] = $args['remaining'][ $p->ID ];
		}
		movieflix_card( $p, $card );
	}
	echo '</div>';
	wp_reset_postdata();
}

/** Print the "My List" toggle button (large). */
function movieflix_list_button( $post_id ) {
	if ( ! mf_opt( 'enable_watchlist', 1 ) ) {
		return;
	}
	$in = mf_in_list( $post_id );
	printf( '<button type="button" class="mf-btn mf-btn--ghost mf-list-btn%1$s" data-id="%2$d" data-large="1" aria-pressed="%3$s"><span class="mf-list-btn__icon" aria-hidden="true">%4$s</span> <span class="mf-list-btn__label">%5$s</span></button>', $in ? ' is-active' : '', (int) $post_id, $in ? 'true' : 'false', $in ? '−' : '+', esc_html( $in ? __( 'In My List', 'movieflix' ) : __( 'My List', 'movieflix' ) ) ); // phpcs:ignore
}

/** "Continue with Google" button (prints nothing unless configured). */
function movieflix_google_button( $redirect = '' ) {
	$svg = '<svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/><path fill="none" d="M0 0h48v48H0z"/></svg>';
	$enabled = class_exists( 'MF_Google' ) && MF_Google::enabled();
	echo '<div class="mf-google-wrap">';
	if ( $enabled ) {
		echo '<a class="mf-btn mf-btn--google mf-btn--block" href="' . esc_url( MF_Google::start_url( $redirect ) ) . '">' . $svg . ' <span>' . esc_html__( 'Continue with Google', 'movieflix' ) . '</span></a>'; // phpcs:ignore
	} else {
		// Still show the option so the login page always has Google UI.
		// When credentials are missing, point admins to settings; members see a clear message.
		$admin = current_user_can( 'manage_options' );
		echo '<button type="button" class="mf-btn mf-btn--google mf-btn--block" disabled aria-disabled="true">' . $svg . ' <span>' . esc_html__( 'Continue with Google', 'movieflix' ) . '</span></button>'; // phpcs:ignore
		if ( $admin ) {
			echo '<p class="mf-google-setup">' . esc_html__( 'Enable Google Sign-In under MovieFlix → Settings (Client ID + Secret).', 'movieflix' ) . '</p>';
		} else {
			echo '<p class="mf-google-setup">' . esc_html__( 'Google sign-in is not configured on this site yet.', 'movieflix' ) . '</p>';
		}
	}
	echo '<div class="mf-or" role="separator"><span>' . esc_html__( 'or', 'movieflix' ) . '</span></div>';
	echo '</div>';
}

/** Browse chips: languages then genres (Hotstar-style quick filters). */
function movieflix_chips() {
	$out = '';
	foreach ( array( 'mf_language' => 8, 'genre' => 8 ) as $tax => $n ) {
		$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => $n ) );
		if ( is_wp_error( $terms ) || ! $terms ) {
			continue;
		}
		foreach ( $terms as $t ) {
			$link = get_term_link( $t );
			if ( ! is_wp_error( $link ) ) {
				$out .= '<a class="mf-chip' . ( 'mf_language' === $tax ? ' mf-chip--lang' : '' ) . '" href="' . esc_url( $link ) . '">' . esc_html( $t->name ) . '</a>';
			}
		}
	}
	if ( $out ) {
		echo '<nav class="mf-chips mf-container" aria-label="' . esc_attr__( 'Browse by language and genre', 'movieflix' ) . '">' . $out . '</nav>'; // phpcs:ignore
	}
}

/** Row of titles in one taxonomy term. */
function movieflix_tax_row( $title, $taxonomy, $slug, $limit = 14 ) {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	$q    = new WP_Query( array(
		'post_type'      => array( 'movie', 'series' ),
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'no_found_rows'  => true,
		'tax_query'      => array( array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $slug ) ), // phpcs:ignore
	) );
	$link = $term ? get_term_link( $term ) : '';
	movieflix_row( $title, $q, array( 'more' => is_wp_error( $link ) ? '' : $link ) );
}

/** Numbered "Top 10" row based on plays in the last 7 days (falls back to total views). */
function movieflix_top10_row( $title = '' ) {
	global $wpdb;
	$ids = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT movie_id FROM {$wpdb->prefix}movieflix_views WHERE event='start' AND created_at > %s GROUP BY movie_id ORDER BY COUNT(*) DESC LIMIT 10", gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS ) ) ) );
	if ( count( $ids ) < 10 ) {
		$more = get_posts( array( 'post_type' => array( 'movie', 'series' ), 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => 10, 'post__not_in' => $ids ? $ids : array( 0 ), 'meta_key' => '_mf_views', 'orderby' => 'meta_value_num', 'order' => 'DESC', 'no_found_rows' => true ) ); // phpcs:ignore
		$ids  = array_slice( array_merge( $ids, $more ), 0, 10 );
	}
	$q = $ids ? new WP_Query( array( 'post_type' => array( 'movie', 'series' ), 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => 10, 'no_found_rows' => true ) ) : null;
	if ( ! $q || ! $q->have_posts() ) {
		return;
	}
	$title = $title ? $title : __( 'Top 10 This Week', 'movieflix' );
	echo '<section class="mf-row mf-top10" aria-labelledby="row-top10"><div class="mf-row__head"><h2 id="row-top10">' . esc_html( $title ) . '</h2></div><div class="mf-row__wrap"><button type="button" class="mf-row__nav mf-row__nav--prev" aria-label="' . esc_attr__( 'Scroll left', 'movieflix' ) . '">‹</button><div class="mf-row__track" tabindex="0">';
	$i = 1;
	foreach ( $q->posts as $p ) {
		echo '<div class="mf-top10__item"><span class="mf-top10__no" aria-hidden="true">' . (int) $i . '</span>';
		movieflix_card( $p );
		echo '</div>';
		$i++;
	}
	echo '</div><button type="button" class="mf-row__nav mf-row__nav--next" aria-label="' . esc_attr__( 'Scroll right', 'movieflix' ) . '">›</button></div></section>';
	wp_reset_postdata();
}

/** Titles for the hero slider: chosen hero first, then featured, then newest. */
function movieflix_hero_posts() {
	$max = max( 1, (int) mf_opt( 'hero_slides', 5 ) );
	$ids = array();
	$hid = (int) mf_opt( 'hero_movie', 0 );
	if ( $hid && 'publish' === get_post_status( $hid ) ) {
		$ids[] = $hid;
	}
	$feat = get_posts( array( 'post_type' => array( 'movie', 'series' ), 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => $max, 'no_found_rows' => true, 'post__not_in' => $ids ? $ids : array( 0 ), 'meta_query' => array( array( 'key' => '_mf_featured', 'value' => '1' ) ) ) ); // phpcs:ignore
	$ids  = array_merge( $ids, $feat );
	if ( count( $ids ) < $max ) {
		$fill = get_posts( array( 'post_type' => array( 'movie', 'series' ), 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => $max, 'no_found_rows' => true, 'post__not_in' => $ids ? $ids : array( 0 ) ) );
		$ids  = array_merge( $ids, $fill );
	}
	return array_map( 'get_post', array_slice( array_unique( $ids ), 0, $max ) );
}
