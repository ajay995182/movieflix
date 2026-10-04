<?php
/**
 * Main fallback template.
 *
 * @package MovieFlix
 */
get_header();
?>
<div class="mf-container mf-page">
	<?php if ( have_posts() ) : ?>
		<header class="mf-section__head">
			<h1><?php esc_html_e( 'Latest', 'movieflix' ); ?></h1>
		</header>
		<div class="mf-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				if ( function_exists( 'movieflix_card' ) ) {
					movieflix_card( get_post() );
				} else {
					echo '<article class="mf-card"><a href="' . esc_url( get_permalink() ) . '"><h2>' . esc_html( get_the_title() ) . '</h2></a></article>';
				}
			endwhile;
			?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<?php
		if ( function_exists( 'movieflix_empty' ) ) {
			movieflix_empty( __( 'Nothing here yet', 'movieflix' ), __( 'No content was found.', 'movieflix' ), __( 'Home', 'movieflix' ), home_url( '/' ) );
		} else {
			echo '<p>' . esc_html__( 'No content was found.', 'movieflix' ) . '</p>';
		}
		?>
	<?php endif; ?>
</div>
<?php
get_footer();
