<?php
/**
 * SEO: meta description, Open Graph, Twitter cards, JSON-LD.
 *
 * @package MovieFlix
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Custom SEO title for titles. */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( is_singular( array( 'movie', 'series' ) ) ) {
		$t = mf_meta( get_the_ID(), 'seo_title' );
		if ( $t ) {
			$parts['title'] = $t;
		}
	}
	return $parts;
} );

/** @return string Description for the current view. */
function movieflix_meta_description() {
	if ( is_singular() ) {
		$id = get_the_ID();
		$d  = mf_meta( $id, 'seo_desc' );
		if ( ! $d ) {
			$d = mf_meta( $id, 'short_desc' );
		}
		if ( ! $d ) {
			$d = wp_strip_all_tags( get_the_excerpt( $id ) );
		}
		return wp_html_excerpt( $d, 155, '…' );
	}
	if ( is_tax() ) {
		$d = term_description();
		return $d ? wp_html_excerpt( wp_strip_all_tags( $d ), 155, '…' ) : sprintf( __( 'Browse %s on %s.', 'movieflix' ), single_term_title( '', false ), mf_opt( 'site_name' ) );
	}
	return sprintf( __( 'Watch movies and series on %s.', 'movieflix' ), mf_opt( 'site_name' ) );
}

add_action( 'wp_head', function () {
	if ( get_query_var( 'mf_watch' ) ) {
		return;
	}
	$desc  = movieflix_meta_description();
	$title = wp_get_document_title();
	$url   = is_singular() ? get_permalink() : ( is_tax() ? get_term_link( get_queried_object() ) : home_url( '/' ) );
	$img   = is_singular( array( 'movie', 'series' ) ) ? mf_img( get_the_ID(), 'backdrop', 'movieflix-backdrop' ) : '';
	if ( is_wp_error( $url ) ) {
		$url = home_url( '/' );
	}
	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	if ( is_search() || is_404() ) {
		echo '<meta name="robots" content="noindex,follow">' . "\n";
	}
	if ( ! is_singular() ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	}
	echo '<meta property="og:site_name" content="' . esc_attr( mf_opt( 'site_name' ) ) . '">' . "\n";
	echo '<meta property="og:type" content="' . ( is_singular( 'movie' ) ? 'video.movie' : ( is_singular( 'series' ) ? 'video.tv_show' : 'website' ) ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta name="twitter:card" content="' . ( $img ? 'summary_large_image' : 'summary' ) . '">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	if ( $img ) {
		echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";
	}
	if ( is_singular( array( 'movie', 'series' ) ) ) {
		$id     = get_the_ID();
		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'movie' === get_post_type() ? 'Movie' : 'TVSeries',
			'name'        => get_the_title(),
			'description' => $desc,
			'url'         => get_permalink(),
			'image'       => mf_img( $id, 'poster', 'movieflix-poster' ),
		);
		if ( mf_meta( $id, 'year' ) ) {
			$schema['datePublished'] = (string) mf_meta( $id, 'year' );
		}
		$g = get_the_terms( $id, 'genre' );
		if ( $g && ! is_wp_error( $g ) ) {
			$schema['genre'] = wp_list_pluck( $g, 'name' );
		}
		if ( mf_meta( $id, 'runtime' ) ) {
			$schema['duration'] = 'PT' . (int) mf_meta( $id, 'runtime' ) . 'M';
		}
		$st = mf_rating_stats( $id );
		if ( $st['count'] > 0 ) {
			$schema['aggregateRating'] = array( '@type' => 'AggregateRating', 'ratingValue' => round( $st['avg'], 1 ), 'bestRating' => 5, 'ratingCount' => $st['count'] );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>' . "\n"; // phpcs:ignore
	}
}, 1 );
