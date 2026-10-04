<?php
/** Search results (title, genre, actor, director, language, year, keyword). @package MovieFlix */
get_header();
$mf_s = get_search_query();
get_template_part( 'template-parts/browse', null, array(
	'title'  => '' !== trim( $mf_s ) ? sprintf( __( 'Results for “%s”', 'movieflix' ), $mf_s ) : __( 'Search', 'movieflix' ),
	'desc'   => '',
	'action' => home_url( '/' ),
) );
get_footer();
