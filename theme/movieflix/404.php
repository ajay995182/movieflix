<?php
/** 404. @package MovieFlix */
get_header();
?>
<div class="mf-container mf-page">
	<?php movieflix_empty( __( 'Page not found', 'movieflix' ), __( 'The movie or page you are looking for does not exist or was removed.', 'movieflix' ), __( 'Back to home', 'movieflix' ), home_url( '/' ) ); ?>
	<nav class="mf-404__actions" aria-label="<?php esc_attr_e( 'Browse the catalog', 'movieflix' ); ?>">
		<a class="mf-btn mf-btn--ghost" href="<?php echo esc_url( mf_page_url( 'search' ) ); ?>"><?php esc_html_e( 'Search titles', 'movieflix' ); ?></a>
		<a class="mf-btn mf-btn--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'movie' ) ); ?>"><?php esc_html_e( 'Browse movies', 'movieflix' ); ?></a>
		<a class="mf-btn mf-btn--ghost" href="<?php echo esc_url( mf_page_url( 'genres' ) ); ?>"><?php esc_html_e( 'Explore genres', 'movieflix' ); ?></a>
	</nav>
	<?php movieflix_row( __( 'Trending Now', 'movieflix' ), MF_Query::row( 'trending', 12 ) ); ?>
</div>
<?php get_footer(); ?>
