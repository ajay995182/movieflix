<?php
/**
 * Hero banner / slider. Args: posts (WP_Post[]) or post (WP_Post).
 *
 * @package MovieFlix
 */
$slides = array();
if ( ! empty( $args['posts'] ) ) {
	$slides = array_filter( (array) $args['posts'] );
} elseif ( ! empty( $args['post'] ) ) {
	$slides = array( $args['post'] );
}
if ( ! $slides ) {
	return;
}
$slides = array_values( $slides );
$many   = count( $slides ) > 1;
?>
<section class="mf-hero<?php echo $many ? ' mf-hero--slider' : ''; ?>" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Featured', 'movieflix' ); ?>" data-autoplay="<?php echo $many ? '7000' : '0'; ?>">
<?php
foreach ( $slides as $i => $p ) :
	$id     = $p->ID;
	$genres = get_the_terms( $id, 'genre' );
	$rating = mf_display_rating( $id );
	?>
	<div class="mf-slide<?php echo 0 === $i ? ' is-active' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( sprintf( '%d / %d', $i + 1, count( $slides ) ) ); ?>">
		<img class="mf-hero__img" src="<?php echo esc_url( mf_img( $id, 'backdrop', 'movieflix-backdrop' ) ); ?>" alt="" <?php echo 0 === $i ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
		<div class="mf-hero__shade"></div>
		<div class="mf-hero__content">
			<span class="mf-eyebrow"><?php esc_html_e( 'Featured', 'movieflix' ); ?></span>
			<h1><?php echo esc_html( get_the_title( $p ) ); ?></h1>
			<p class="mf-meta">
				<?php if ( mf_meta( $id, 'year' ) ) : ?><span><?php echo esc_html( mf_meta( $id, 'year' ) ); ?></span><?php endif; ?>
				<?php if ( mf_meta( $id, 'runtime' ) ) : ?><span><?php echo esc_html( mf_meta( $id, 'runtime' ) ); ?> min</span><?php endif; ?>
					<?php if ( $rating ) : ?><span class="mf-rate"><span aria-hidden="true">★</span> <?php echo esc_html( $rating ); ?></span><?php endif; ?>
				<?php if ( $genres && ! is_wp_error( $genres ) ) : ?><span><?php echo esc_html( implode( ' • ', wp_list_pluck( $genres, 'name' ) ) ); ?></span><?php endif; ?>
			</p>
			<p class="mf-hero__desc"><?php echo esc_html( wp_trim_words( mf_meta( $id, 'short_desc', get_the_excerpt( $p ) ), 32 ) ); ?></p>
			<div class="mf-hero__actions">
				<a class="mf-btn mf-btn--primary mf-btn--lg" href="<?php echo esc_url( mf_watch_url( $id ) ); ?>"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m8 5 11 7-11 7z" fill="currentColor"/></svg> <?php esc_html_e( 'Play Now', 'movieflix' ); ?></a>
				<a class="mf-btn mf-btn--ghost mf-btn--lg" href="<?php echo esc_url( get_permalink( $p ) ); ?>"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M12 11v5m0-8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg> <?php esc_html_e( 'More Info', 'movieflix' ); ?></a>
				<?php movieflix_list_button( $id ); ?>
			</div>
		</div>
	</div>
<?php endforeach; ?>
<?php if ( $many ) : ?>
	<button type="button" class="mf-hero__nav mf-hero__nav--prev" aria-label="<?php esc_attr_e( 'Previous slide', 'movieflix' ); ?>"><span aria-hidden="true">‹</span></button>
	<button type="button" class="mf-hero__nav mf-hero__nav--next" aria-label="<?php esc_attr_e( 'Next slide', 'movieflix' ); ?>"><span aria-hidden="true">›</span></button>
	<div class="mf-hero__dots">
		<?php foreach ( $slides as $i => $p ) : ?>
			<button type="button" class="mf-dot<?php echo 0 === $i ? ' is-active' : ''; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'movieflix' ), $i + 1 ) ); ?>"></button>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
</section>
