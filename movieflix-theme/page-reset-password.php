<?php
/**
 * Branded password reset form.
 *
 * @package MovieFlix
 */
if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}
$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore
$login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : ''; // phpcs:ignore
get_header();
?>
<div class="mf-container mf-auth">
	<div class="mf-auth__card">
		<h1><?php esc_html_e( 'Set new password', 'movieflix' ); ?></h1>
		<?php movieflix_notice(); ?>
		<?php if ( ! $key || ! $login ) : ?>
			<p class="mf-hint"><?php esc_html_e( 'This reset link is invalid or incomplete.', 'movieflix' ); ?></p>
			<p class="mf-auth__links"><a href="<?php echo esc_url( mf_page_url( 'lost-password' ) ); ?>"><?php esc_html_e( 'Request a new link', 'movieflix' ); ?></a></p>
		<?php else : ?>
		<form class="mf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mf_resetpass">
			<input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>">
			<input type="hidden" name="login" value="<?php echo esc_attr( $login ); ?>">
			<?php wp_nonce_field( 'mf_resetpass', '_mf' ); ?>
			<label for="pass1"><?php esc_html_e( 'New password', 'movieflix' ); ?></label>
			<input type="password" id="pass1" name="pass1" required minlength="8" autocomplete="new-password">
			<label for="pass2"><?php esc_html_e( 'Confirm password', 'movieflix' ); ?></label>
			<input type="password" id="pass2" name="pass2" required minlength="8" autocomplete="new-password">
			<button type="submit" class="mf-btn mf-btn--primary mf-btn--block"><?php esc_html_e( 'Update password', 'movieflix' ); ?></button>
		</form>
		<?php endif; ?>
	</div>
</div>
<?php get_footer(); ?>
