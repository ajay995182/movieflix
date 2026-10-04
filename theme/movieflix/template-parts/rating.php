<?php
/**
 * Star rating widget. Args: id.
 *
 * @package MovieFlix
 */
if ( ! mf_opt( 'enable_ratings', 1 ) ) {
	return;
}
$id   = (int) $args['id'];
$st   = mf_rating_stats( $id );
$mine = mf_user_rating( $id );
?>
<div class="mf-rating" data-id="<?php echo (int) $id; ?>" data-mine="<?php echo (int) $mine; ?>">
	<div class="mf-rating__stars" role="radiogroup" aria-label="<?php esc_attr_e( 'Rate this title', 'movieflix' ); ?>">
		<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
			<button type="button" class="mf-star<?php echo $i <= $mine ? ' is-on' : ''; ?>" data-value="<?php echo (int) $i; ?>" role="radio" aria-checked="<?php echo $i === $mine ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( _n( '%d star', '%d stars', $i, 'movieflix' ), $i ) ); ?>">★</button>
		<?php endfor; ?>
	</div>
	<p class="mf-rating__text">
		<?php if ( $st['count'] ) : ?>
			<strong class="mf-rating__avg"><?php echo esc_html( number_format_i18n( $st['avg'], 1 ) ); ?></strong>/5 ·
			<span class="mf-rating__count"><?php echo esc_html( sprintf( _n( '%s rating', '%s ratings', $st['count'], 'movieflix' ), number_format_i18n( $st['count'] ) ) ); ?></span>
		<?php else : ?>
			<span class="mf-rating__count"><?php esc_html_e( 'No ratings yet', 'movieflix' ); ?></span>
		<?php endif; ?>
		<span class="mf-rating__mine"><?php echo $mine ? esc_html( sprintf( __( ' · Your rating: %d', 'movieflix' ), $mine ) ) : ''; ?></span>
	</p>
</div>
