<?php
/**
 * Live TV / Channels listing with category chips and favourites.
 *
 * @package MovieFlix
 */
get_header();
$fav_ids = ( is_user_logged_in() && class_exists( 'MF_Extras' ) ) ? MF_Extras::fav_channel_ids() : array();
$cat_filter = isset( $_GET['ch_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['ch_cat'] ) ) : ''; // phpcs:ignore
$show_favs  = isset( $_GET['ch_fav'] ) && '1' === $_GET['ch_fav'] && $fav_ids; // phpcs:ignore

// Collect categories from channels.
$all_channels = get_posts( array(
	'post_type'      => 'channel',
	'posts_per_page' => 200,
	'post_status'    => 'publish',
	'fields'         => 'ids',
	'no_found_rows'  => true,
) );
$categories = array();
foreach ( $all_channels as $cid ) {
	$c = trim( (string) mf_meta( $cid, 'channel_category' ) );
	if ( $c ) {
		$categories[ $c ] = ( $categories[ $c ] ?? 0 ) + 1;
	}
}
ksort( $categories );
?>
<div class="mf-container mf-page">
	<header class="mf-page__head">
		<h1><?php esc_html_e( 'Live TV', 'movieflix' ); ?></h1>
		<p class="mf-page__desc"><?php esc_html_e( 'Live channels and streams available on this platform.', 'movieflix' ); ?></p>
	</header>
	<?php
	if ( ! mf_opt( 'enable_channels', 1 ) ) {
		movieflix_empty( __( 'Live TV is off', 'movieflix' ), __( 'The site admin has disabled channels.', 'movieflix' ) );
	} else {
		// Chips.
		echo '<nav class="mf-chips mf-channel-chips" aria-label="' . esc_attr__( 'Channel categories', 'movieflix' ) . '">';
		$base = mf_page_url( 'channels' ) ?: get_post_type_archive_link( 'channel' );
		echo '<a class="mf-chip' . ( ! $cat_filter && ! $show_favs ? ' is-active' : '' ) . '" href="' . esc_url( $base ) . '">' . esc_html__( 'All', 'movieflix' ) . '</a>';
		if ( is_user_logged_in() ) {
			echo '<a class="mf-chip' . ( $show_favs ? ' is-active' : '' ) . '" href="' . esc_url( add_query_arg( 'ch_fav', '1', $base ) ) . '">★ ' . esc_html__( 'Favourites', 'movieflix' ) . '</a>';
		}
		foreach ( $categories as $cname => $cnt ) {
			$active = ( $cat_filter === $cname );
			echo '<a class="mf-chip' . ( $active ? ' is-active' : '' ) . '" href="' . esc_url( add_query_arg( 'ch_cat', $cname, $base ) ) . '">' . esc_html( $cname ) . ' <span class="mf-chip__count">' . (int) $cnt . '</span></a>';
		}
		echo '</nav>';

		$args = array(
			'post_type'      => 'channel',
			'posts_per_page' => 48,
			'post_status'    => 'publish',
			'paged'          => max( 1, (int) get_query_var( 'paged' ) ),
		);
		if ( $show_favs ) {
			$args['post__in'] = $fav_ids ? $fav_ids : array( 0 );
			$args['orderby']  = 'post__in';
		}
		$q = new WP_Query( $args );
		$posts = $q->posts;
		if ( $cat_filter && $posts ) {
			$posts = array_values( array_filter( $posts, function ( $p ) use ( $cat_filter ) {
				return strcasecmp( (string) mf_meta( $p->ID, 'channel_category' ), $cat_filter ) === 0;
			} ) );
		}
		if ( $posts ) {
			echo '<div class="mf-grid mf-channel-grid">';
			foreach ( $posts as $p ) {
				$thumb       = mf_meta( $p->ID, 'channel_logo' ) ? mf_resolve_url( mf_meta( $p->ID, 'channel_logo' ) ) : mf_img( $p->ID, 'poster', 'movieflix-poster' );
				$stream      = trim( (string) mf_meta( $p->ID, 'video_url' ) );
				$channel_num = mf_meta( $p->ID, 'channel_number' );
				$category    = mf_meta( $p->ID, 'channel_category' );
				$members     = '1' === (string) mf_meta( $p->ID, 'members_only' );
				$channel_url = get_permalink( $p );
				$is_fav      = in_array( (int) $p->ID, $fav_ids, true );
				echo '<article class="mf-channel-card" data-id="' . (int) $p->ID . '">';
				echo '<a class="mf-channel-card__art" href="' . esc_url( $channel_url ) . '" aria-label="' . esc_attr( sprintf( __( 'Open channel %s', 'movieflix' ), get_the_title( $p ) ) ) . '">';
				if ( $thumb ) {
					echo '<img src="' . esc_url( $thumb ) . '" alt="" width="300" height="180" loading="lazy" decoding="async">';
				} else {
					echo '<span class="mf-channel-card__letter" aria-hidden="true">' . esc_html( strtoupper( substr( get_the_title( $p ), 0, 1 ) ) ) . '</span>';
				}
				echo '<span class="mf-channel-card__state' . ( $stream ? ' is-ready' : ' is-unavailable' ) . '"><span aria-hidden="true"></span>' . esc_html( $stream ? __( 'Live', 'movieflix' ) : __( 'Unavailable', 'movieflix' ) ) . '</span></a>';
				echo '<div class="mf-channel-card__body"><div class="mf-channel-card__title"><div><h2><a href="' . esc_url( $channel_url ) . '">' . esc_html( get_the_title( $p ) ) . '</a></h2>';
				$details = array_filter( array( $channel_num ? sprintf( __( 'Ch %s', 'movieflix' ), $channel_num ) : '', $category ) );
				if ( $details ) {
					echo '<p class="mf-channel-card__meta">' . esc_html( implode( ' · ', $details ) ) . '</p>';
				}
				echo '</div>';
				if ( $members ) {
					echo '<span class="mf-badge mf-badge--members">' . esc_html__( 'Members', 'movieflix' ) . '</span>';
				}
				echo '</div>';
				$summary = mf_meta( $p->ID, 'short_desc' );
				if ( ! $summary && has_excerpt( $p ) ) {
					$summary = get_the_excerpt( $p );
				}
				if ( $summary ) {
					echo '<p class="mf-channel-card__desc">' . esc_html( wp_trim_words( $summary, 20 ) ) . '</p>';
				}
				echo '<div class="mf-channel-card__actions">';
				echo '<a class="mf-btn mf-btn--primary mf-btn--sm" href="' . esc_url( $channel_url ) . '">' . esc_html( $stream ? __( 'Watch', 'movieflix' ) : __( 'Details', 'movieflix' ) ) . '</a>';
				if ( is_user_logged_in() ) {
					echo '<button type="button" class="mf-btn mf-btn--ghost mf-btn--sm mf-fav-channel' . ( $is_fav ? ' is-active' : '' ) . '" data-id="' . (int) $p->ID . '" aria-pressed="' . ( $is_fav ? 'true' : 'false' ) . '">' . ( $is_fav ? '★' : '☆' ) . ' ' . esc_html__( 'Fav', 'movieflix' ) . '</button>';
				}
				echo '</div></div></article>';
			}
			echo '</div>';
			if ( ! $cat_filter && ! $show_favs ) {
				movieflix_pagination( $q );
			}
		} else {
			movieflix_empty(
				$show_favs ? __( 'No favourite channels', 'movieflix' ) : __( 'No channels yet', 'movieflix' ),
				$show_favs ? __( 'Tap the star on a channel to save it here.', 'movieflix' ) : ( current_user_can( 'edit_posts' ) ? __( 'Add channels in MovieFlix → Channels.', 'movieflix' ) : __( 'Channels will appear when published.', 'movieflix' ) )
			);
		}
		wp_reset_postdata();
	}
	?>
</div>
<?php get_footer(); ?>
