<?php
/**
 * Reviews list + form. Args: id.
 *
 * @package MovieFlix
 */
if ( ! mf_opt( 'enable_reviews', 1 ) ) {
	return;
}
$id      = (int) $args['id'];
$uid     = get_current_user_id();
$reviews = get_comments( array( 'post_id' => $id, 'type' => 'review', 'status' => 'approve', 'number' => 40, 'orderby' => 'comment_date_gmt', 'order' => 'DESC' ) );
$mine    = $uid ? get_comments( array( 'post_id' => $id, 'type' => 'review', 'user_id' => $uid, 'status' => 'all', 'number' => 1 ) ) : array();
$mine    = $mine ? $mine[0] : null;
?>
<section class="mf-reviews" id="reviews" aria-labelledby="reviews-title">
	<h2 id="reviews-title"><?php esc_html_e( 'Reviews', 'movieflix' ); ?></h2>
	<?php movieflix_notice(); ?>
	<?php if ( $uid ) : ?>
		<form class="mf-form mf-review-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mf_review_save">
			<input type="hidden" name="post_id" value="<?php echo (int) $id; ?>">
			<?php wp_nonce_field( 'mf_review_' . $id, '_mf' ); ?>
			<p class="mf-hp" aria-hidden="true"><label>Website <input type="text" name="mf_website" tabindex="-1" autocomplete="off"></label></p>
			<label for="review_content"><?php echo $mine ? esc_html__( 'Edit your review', 'movieflix' ) : esc_html__( 'Write a review', 'movieflix' ); ?></label>
			<?php if ( $mine && '0' === $mine->comment_approved ) : ?><p class="mf-hint"><?php esc_html_e( 'Your review is awaiting moderation.', 'movieflix' ); ?></p><?php endif; ?>
			<textarea id="review_content" name="review_content" rows="4" maxlength="3000" required><?php echo $mine ? esc_textarea( $mine->comment_content ) : ''; ?></textarea>
			<button type="submit" class="mf-btn mf-btn--primary"><?php echo $mine ? esc_html__( 'Update review', 'movieflix' ) : esc_html__( 'Submit review', 'movieflix' ); ?></button>
		</form>
		<?php if ( $mine ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mf-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'Delete your review?', 'movieflix' ) ); ?>')">
				<input type="hidden" name="action" value="mf_review_delete"><input type="hidden" name="comment_id" value="<?php echo (int) $mine->comment_ID; ?>">
				<?php wp_nonce_field( 'mf_review_del_' . $mine->comment_ID, '_mf' ); ?>
				<button type="submit" class="mf-link-btn"><?php esc_html_e( 'Delete my review', 'movieflix' ); ?></button>
			</form>
		<?php endif; ?>
	<?php else : ?>
		<p><a class="mf-btn mf-btn--ghost" href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( get_permalink( $id ) . '#reviews' ), mf_page_url( 'login' ) ) ); ?>"><?php esc_html_e( 'Log in to write a review', 'movieflix' ); ?></a></p>
	<?php endif; ?>
	<?php if ( $reviews ) : ?>
		<ul class="mf-review-list">
			<?php foreach ( $reviews as $r ) : ?>
				<li class="mf-review">
					<?php echo get_avatar( $r->user_id ? (int) $r->user_id : $r->comment_author_email, 44, '', '', array( 'class' => 'mf-avatar' ) ); // phpcs:ignore ?>
					<div>
						<strong><?php echo esc_html( $r->comment_author ); ?></strong>
						<?php $rr = mf_user_rating( $id, (int) $r->user_id ); if ( $rr ) { echo wp_kses_post( movieflix_stars( $rr ) ); } ?>
						<time datetime="<?php echo esc_attr( mysql2date( 'c', $r->comment_date_gmt ) ); ?>"><?php echo esc_html( mysql2date( get_option( 'date_format' ), $r->comment_date ) ); ?></time>
						<p><?php echo wp_kses_post( nl2br( esc_html( $r->comment_content ) ) ); ?></p>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<?php movieflix_empty( __( 'No reviews yet', 'movieflix' ), __( 'Be the first to share what you thought.', 'movieflix' ) ); ?>
	<?php endif; ?>
</section>
