<?php
/**
 * Registration — email + Google + age/terms.
 *
 * @package MovieFlix
 */
if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}
get_header();
$min_age = (int) mf_opt( 'age_gate_min', 13 );
$terms_id = (int) mf_opt( 'terms_page_id', 0 );
$priv_id  = (int) mf_opt( 'privacy_page_id', 0 );
?>
<div class="mf-container mf-auth">
	<div class="mf-auth__card">
		<h1><?php esc_html_e( 'Create account', 'movieflix' ); ?></h1>
<p class="mf-hint mf-auth__hint"><?php esc_html_e( 'Join free and open your personal streaming dashboard.', 'movieflix' ); ?></p>
		<?php movieflix_notice(); ?>
		<?php if ( ! mf_opt( 'enable_registration', 1 ) ) : ?>
			<?php movieflix_empty( __( 'Registration is closed', 'movieflix' ), __( 'New accounts are not being accepted right now.', 'movieflix' ), __( 'Sign in', 'movieflix' ), mf_page_url( 'login' ) ); ?>
		<?php else : ?>
			<?php movieflix_google_button( home_url( '/' ) ); ?>
			<form class="mf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mf_register">
				<?php wp_nonce_field( 'mf_register', '_mf' ); ?>
				<p class="mf-hp" aria-hidden="true"><label>Website <input type="text" name="mf_website" tabindex="-1" autocomplete="off"></label></p>
				<label for="display_name"><?php esc_html_e( 'Display name', 'movieflix' ); ?></label>
				<input type="text" id="display_name" name="display_name" autocomplete="name">
				<label for="user_login"><?php esc_html_e( 'Username', 'movieflix' ); ?></label>
				<input type="text" id="user_login" name="user_login" autocomplete="username" required minlength="3">
				<label for="user_email"><?php esc_html_e( 'Email', 'movieflix' ); ?></label>
				<input type="email" id="user_email" name="user_email" autocomplete="email" required>
				<label for="user_password"><?php esc_html_e( 'Password (min. 8 characters)', 'movieflix' ); ?></label>
				<input type="password" id="user_password" name="user_password" autocomplete="new-password" required minlength="8">
				<?php if ( mf_opt( 'enable_age_gate', 1 ) ) : ?>
					<label class="mf-check"><input type="checkbox" name="mf_age_confirm" value="1" required> <?php echo esc_html( sprintf( __( 'I confirm I am %d years of age or older', 'movieflix' ), $min_age ) ); ?></label>
				<?php endif; ?>
				<?php if ( mf_opt( 'enable_terms_check', 1 ) ) : ?>
					<label class="mf-check"><input type="checkbox" name="mf_terms" value="1" required>
						<?php
						$terms_link = $terms_id ? '<a href="' . esc_url( get_permalink( $terms_id ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Terms', 'movieflix' ) . '</a>' : esc_html__( 'Terms', 'movieflix' );
						$priv_link  = $priv_id ? '<a href="' . esc_url( get_permalink( $priv_id ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Privacy Policy', 'movieflix' ) . '</a>' : esc_html__( 'Privacy Policy', 'movieflix' );
						echo wp_kses( sprintf( __( 'I agree to the %1$s and %2$s', 'movieflix' ), $terms_link, $priv_link ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) );
						?>
					</label>
				<?php endif; ?>
				<button type="submit" class="mf-btn mf-btn--primary mf-btn--block"><?php esc_html_e( 'Create account & watch', 'movieflix' ); ?></button>
			</form>
			<p class="mf-auth__links"><?php esc_html_e( 'Already have an account?', 'movieflix' ); ?> <a href="<?php echo esc_url( mf_page_url( 'login' ) ); ?>"><?php esc_html_e( 'Sign in', 'movieflix' ); ?></a></p>
		<?php endif; ?>
	</div>
</div>
<?php get_footer(); ?>
