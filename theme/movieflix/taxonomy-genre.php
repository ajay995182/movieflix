<?php
/** Genre (and other taxonomy) archives: title, description, filters, grid, pagination. @package MovieFlix */
get_header();
get_template_part( 'template-parts/browse', null, array( 'title' => single_term_title( '', false ), 'desc' => term_description() ) );
get_footer();
