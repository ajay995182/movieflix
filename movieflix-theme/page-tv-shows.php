<?php
/**
 * TV Shows hub (series).
 *
 * @package MovieFlix
 */
get_header();
?>
<div class="mf-container mf-page">
		<header class="mf-page__head">
		<h1><?php esc_html_e( 'TV Shows', 'movieflix' ); ?></h1>
		<p class="mf-page__desc"><?php esc_html_e( 'Browse series and episodes.', 'movieflix' ); ?></p>
		</header>
	<?php
	$mf_featured_series = new WP_Query( array(
		'post_type'      => 'series',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'no_found_rows'  => true,
		'meta_query'     => array( array( 'key' => '_mf_featured', 'value' => '1' ) ), // phpcs:ignore
	) );
	movieflix_row( __( 'Featured Series', 'movieflix' ), $mf_featured_series );
	movieflix_row( __( 'Trending Shows', 'movieflix' ), MF_Query::row( 'trending', 14, 'series' ) );
	movieflix_row( __( 'Popular This Week', 'movieflix' ), MF_Query::row( 'popular', 14, 'series' ) );
	movieflix_row( __( 'New Series', 'movieflix' ), MF_Query::row( 'new', 14, 'series' ) );
	?>
		<h2 class="mf-subsection-title"><?php esc_html_e( 'Explore all shows', 'movieflix' ); ?></h2>
	<?php
	$q = new WP_Query( MF_Query::browse_args( array(
		'post_type'      => 'series',
		'posts_per_page' => 24,
		'paged'          => max( 1, (int) get_query_var( 'paged' ) ),
	) ) );
	movieflix_filters( get_permalink() );
	if ( $q->have_posts() ) {
		echo '<p class="mf-count">' . esc_html( sprintf( _n( '%s show', '%s shows', (int) $q->found_posts, 'movieflix' ), number_format_i18n( (int) $q->found_posts ) ) ) . '</p>';
		echo '<div class="mf-grid">';
		foreach ( $q->posts as $p ) {
			movieflix_card( $p );
		}
		echo '</div>';
		movieflix_pagination( $q, true );
	} else {
		movieflix_empty( __( 'No TV shows yet', 'movieflix' ), __( 'Series will appear here once published.', 'movieflix' ) );
	}
	wp_reset_postdata();
	?>
</div>
<?php get_footer(); ?>
