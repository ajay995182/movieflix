<?php
/** Person page: photo, bio, filmography. @package MovieFlix */
get_header();
while ( have_posts() ) :
	the_post();
	$id = get_the_ID();
	?>
<div class="mf-container mf-page">
	<div class="mf-person-head">
		<img src="<?php echo esc_url( mf_img( $id, 'poster', 'medium' ) ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" width="220" height="220">
		<div>
			<h1><?php the_title(); ?></h1>
			<p class="mf-meta">
				<?php if ( mf_meta( $id, 'known_for' ) ) : ?><span><?php esc_html_e( 'Known for:', 'movieflix' ); ?> <?php echo esc_html( mf_meta( $id, 'known_for' ) ); ?></span><?php endif; ?>
				<?php if ( mf_meta( $id, 'birth_date' ) ) : ?><span><?php esc_html_e( 'Born:', 'movieflix' ); ?> <?php echo esc_html( mf_meta( $id, 'birth_date' ) ); ?></span><?php endif; ?>
				<?php if ( mf_meta( $id, 'birthplace' ) ) : ?><span><?php echo esc_html( mf_meta( $id, 'birthplace' ) ); ?></span><?php endif; ?>
			</p>
			<div class="mf-prose"><?php the_content(); ?></div>
		</div>
	</div>
	<?php $titles = MF_Query::person_titles( $id ); ?>
	<h2><?php esc_html_e( 'Movies & Series', 'movieflix' ); ?></h2>
	<?php
	if ( $titles->have_posts() ) {
		movieflix_grid( $titles );
	} else {
		movieflix_empty( __( 'No titles yet', 'movieflix' ), __( 'Titles featuring this person will appear here.', 'movieflix' ) );
	}
	?>
</div>
<?php endwhile; get_footer(); ?>
