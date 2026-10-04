<?php
/**
 * Branded lost password request.
 *
 * @package MovieFlix
 */
if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}
get_header();
?>
<div class="mf-container mf-auth">
	<div class="mf-auth__card">
		<h1><?php esc_html_e( 'Forgot password?', 'movieflix' ); ?></h1>
<p class="mf-hint mf-auth__hint"><?php esc_html_e( 'Enter your username or email and we will send a reset link.', 'movieflix' ); ?></p>
		<?php movieflix_notice(); ?>
		<form class="mf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mf_lostpass">
			<?php wp_nonce_field( 'mf_lostpass', '_mf' ); ?>
			<label for="user_login"><?php esc_html_e( 'Username or email', 'movieflix' ); ?></label>
			<input type="text" id="user_login" name="user_login" required autocomplete="username">
			<button type="submit" class="mf-btn mf-btn--primary mf-btn--block"><?php esc_html_e( 'Send reset link', 'movieflix' ); ?></button>
		</form>
		<p class="mf-auth__links"><a href="<?php echo esc_url( mf_page_url( 'login' ) ); ?>"><?php esc_html_e( 'Back to sign in', 'movieflix' ); ?></a></p>
	</div>
</div>
<?php get_footer(); ?>
