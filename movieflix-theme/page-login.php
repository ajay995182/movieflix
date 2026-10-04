<?php
/**
 * Login — email + Google.
 *
 * @package MovieFlix
 */
if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}
get_header();
$mf_redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/' ); // phpcs:ignore
$mf_google_ok = class_exists( 'MF_Google' ) && MF_Google::enabled();
$mf_google_svg = '<svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>';
?>
<div class="mf-container mf-auth">
	<div class="mf-auth__card">
		<h1><?php esc_html_e( 'Sign in', 'movieflix' ); ?></h1>
		<p class="mf-hint mf-auth__hint"><?php esc_html_e( 'Access your movies dashboard and continue watching.', 'movieflix' ); ?></p>
		<?php movieflix_notice(); ?>

		<div class="mf-google-wrap">
			<?php if ( $mf_google_ok ) : ?>
				<a class="mf-btn mf-btn--google mf-btn--block" href="<?php echo esc_url( MF_Google::start_url( $mf_redirect ) ); ?>">
					<?php echo $mf_google_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php esc_html_e( 'Continue with Google', 'movieflix' ); ?></span>
				</a>
			<?php else : ?>
				<a class="mf-btn mf-btn--google mf-btn--block" href="<?php echo esc_url( admin_url( 'admin-post.php?action=mf_google_start&redirect_to=' . rawurlencode( $mf_redirect ) ) ); ?>">
					<?php echo $mf_google_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php esc_html_e( 'Continue with Google', 'movieflix' ); ?></span>
				</a>
			<?php endif; ?>
			<div class="mf-or" role="separator"><span><?php esc_html_e( 'or', 'movieflix' ); ?></span></div>
		</div>

		<form class="mf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mf_login">
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $mf_redirect ); ?>">
			<?php wp_nonce_field( 'mf_login', '_mf' ); ?>
			<p class="mf-hp" aria-hidden="true"><label>Website <input type="text" name="mf_website" tabindex="-1" autocomplete="off"></label></p>
			<label for="user_login"><?php esc_html_e( 'Username or email', 'movieflix' ); ?></label>
			<input type="text" id="user_login" name="user_login" autocomplete="username" required>
			<label for="user_password"><?php esc_html_e( 'Password', 'movieflix' ); ?></label>
			<input type="password" id="user_password" name="user_password" autocomplete="current-password" required>
			<label class="mf-check"><input type="checkbox" name="remember" value="1"> <?php esc_html_e( 'Remember me', 'movieflix' ); ?></label>
			<button type="submit" class="mf-btn mf-btn--primary mf-btn--block"><?php esc_html_e( 'Sign in', 'movieflix' ); ?></button>
		</form>
		<p class="mf-auth__links">
			<a href="<?php echo esc_url( mf_page_url( 'lost-password' ) ); ?>"><?php esc_html_e( 'Forgot password?', 'movieflix' ); ?></a>
			<?php if ( mf_opt( 'enable_registration', 1 ) ) : ?>
				· <a href="<?php echo esc_url( mf_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Create an account', 'movieflix' ); ?></a>
			<?php endif; ?>
		</p>
	</div>
</div>
<?php get_footer(); ?>
