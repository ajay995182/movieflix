<?php
/** Generic page. @package MovieFlix */
get_header();
while ( have_posts() ) :
	the_post();
	?>
<div class="mf-container mf-page"><h1><?php the_title(); ?></h1><div class="mf-prose"><?php the_content(); ?></div></div>
<?php endwhile; get_footer(); ?>
