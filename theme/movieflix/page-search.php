<?php
/** Search / browse page at /search/. @package MovieFlix */
get_header();
// phpcs:ignore WordPress.Security.NonceVerification
$mf_has_input = ! empty( $_GET['s'] ) || ! empty( $_GET['mf_genre'] ) || ! empty( $_GET['mf_year'] ) || ! empty( $_GET['mf_lang'] ) || ! empty( $_GET['mf_country'] ) || ! empty( $_GET['mf_min'] ) || ! empty( $_GET['mf_quality'] ) || ! empty( $_GET['mf_crating'] ) || ! empty( $_GET['mf_sort'] );
$mf_q = new WP_Query( MF_Query::browse_args() );
if ( $mf_has_input ) {
	get_template_part( 'template-parts/browse', null, array( 'title' => __( 'Search', 'movieflix' ), 'q' => $mf_q, 'custom' => true, 'action' => get_permalink() ) );
} else {
	?>
	<div class="mf-container mf-page">
		<h1><?php esc_html_e( 'Search', 'movieflix' ); ?></h1>
		<?php movieflix_filters( get_permalink() ); ?>
		<?php movieflix_empty( __( 'Start searching', 'movieflix' ), __( 'Search by title, actor, director, genre, language or year — or use the filters above.', 'movieflix' ) ); ?>
	</div>
	<?php
	movieflix_row( __( 'Trending Now', 'movieflix' ), MF_Query::row( 'trending', 14 ) );
}
get_footer();
