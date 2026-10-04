<?php
/**
 * New & Popular titles.
 *
 * @package MovieFlix
 */
get_header();
?>
<div class="mf-container mf-page">
	<div class="mf-page__head">
		<h1><?php esc_html_e( 'New & Popular', 'movieflix' ); ?></h1>
		<p class="mf-page__desc"><?php esc_html_e( 'Fresh releases and titles everyone is watching.', 'movieflix' ); ?></p>
	</div>
	<?php
	echo '<div class="mf-rows">';
	movieflix_row( __( 'Trending Now', 'movieflix' ), MF_Query::row( 'trending', 18 ) );
	movieflix_top10_row();
	movieflix_row( __( 'New Movie Releases', 'movieflix' ), MF_Query::row( 'new', 18, 'movie' ), array( 'more' => get_post_type_archive_link( 'movie' ) ) );
	movieflix_row( __( 'Popular Movies', 'movieflix' ), MF_Query::row( 'popular', 18, 'movie' ), array( 'more' => add_query_arg( 'mf_sort', 'popular', get_post_type_archive_link( 'movie' ) ) ) );
	movieflix_row( __( 'New Series', 'movieflix' ), MF_Query::row( 'new', 18, 'series' ), array( 'more' => mf_page_url( 'tv-shows' ) ) );
	movieflix_row( __( 'Popular TV Shows', 'movieflix' ), MF_Query::row( 'popular', 18, 'series' ), array( 'more' => mf_page_url( 'tv-shows' ) ) );
	if ( is_user_logged_in() ) {
		movieflix_row( __( 'Recommended for You', 'movieflix' ), MF_Query::row( 'recommended', 18 ) );
	}
	echo '</div>';
	?>
</div>
<?php get_footer(); ?>
