<?php
/** Movies archive. @package MovieFlix */
get_header();
get_template_part( 'template-parts/browse', null, array( 'title' => post_type_archive_title( '', false ), 'desc' => '' ) );
get_footer();
