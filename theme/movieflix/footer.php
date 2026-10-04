<?php
/**
 * Site footer.
 *
 * @package MovieFlix
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<footer class="mf-footer">
	<div class="mf-footer__inner">
		<div class="mf-footer__brand">
			<?php movieflix_logo(); ?>
			<?php
			$mf_social = array( 'facebook' => 'Facebook', 'twitter' => 'X', 'instagram' => 'Instagram', 'youtube' => 'YouTube' );
			echo '<p class="mf-social">';
			foreach ( $mf_social as $k => $l ) {
				if ( mf_opt( 'social_' . $k ) ) {
					echo '<a href="' . esc_url( mf_opt( 'social_' . $k ) ) . '" rel="noopener" target="_blank">' . esc_html( $l ) . '</a> ';
				}
			}
			echo '</p>';
			?>
		</div>
		<div class="mf-footer__col">
			<?php if ( has_nav_menu( 'footer' ) ) : wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'depth' => 1 ) ); else : ?>
			<ul>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'movie' ) ); ?>"><?php esc_html_e( 'Movies', 'movieflix' ); ?></a></li>
				<li><a href="<?php echo esc_url( mf_page_url( 'tv-shows' ) ); ?>"><?php esc_html_e( 'TV Shows', 'movieflix' ); ?></a></li>
				<li><a href="<?php echo esc_url( mf_page_url( 'search' ) ); ?>"><?php esc_html_e( 'Search', 'movieflix' ); ?></a></li>
				<li><a href="<?php echo esc_url( mf_page_url( 'my-list' ) ); ?>"><?php esc_html_e( 'My List', 'movieflix' ); ?></a></li>
				<?php if ( mf_opt( 'enable_channels', 1 ) ) : ?><li><a href="<?php echo esc_url( mf_page_url( 'channels' ) ); ?>"><?php esc_html_e( 'Live TV', 'movieflix' ); ?></a></li><?php endif; ?>
				<?php if ( mf_opt( 'terms_page_id' ) && 'publish' === get_post_status( (int) mf_opt( 'terms_page_id' ) ) ) : ?><li><a href="<?php echo esc_url( get_permalink( (int) mf_opt( 'terms_page_id' ) ) ); ?>"><?php esc_html_e( 'Terms', 'movieflix' ); ?></a></li><?php endif; ?>
				<?php if ( mf_opt( 'privacy_page_id' ) && 'publish' === get_post_status( (int) mf_opt( 'privacy_page_id' ) ) ) : ?><li><a href="<?php echo esc_url( get_permalink( (int) mf_opt( 'privacy_page_id' ) ) ); ?>"><?php esc_html_e( 'Privacy', 'movieflix' ); ?></a></li><?php endif; ?>
			</ul>
			<?php endif; ?>
		</div>
		<div class="mf-footer__col">
			<?php if ( is_active_sidebar( 'footer-1' ) ) { dynamic_sidebar( 'footer-1' ); } ?>
			<?php if ( mf_opt( 'contact_email' ) || mf_opt( 'contact_phone' ) || mf_opt( 'contact_address' ) ) : ?>
				<address>
					<?php if ( mf_opt( 'contact_email' ) ) : ?><a href="mailto:<?php echo esc_attr( antispambot( mf_opt( 'contact_email' ) ) ); ?>"><?php echo esc_html( mf_opt( 'contact_email' ) ); ?></a><br><?php endif; ?>
					<?php if ( mf_opt( 'contact_phone' ) ) : echo esc_html( mf_opt( 'contact_phone' ) ); ?><br><?php endif; ?>
					<?php echo esc_html( mf_opt( 'contact_address' ) ); ?>
				</address>
			<?php endif; ?>
		</div>
	</div>
	<p class="mf-footer__copy">© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo wp_kses_post( mf_opt( 'footer_text' ) ); ?></p>
</footer>

<?php
$mf_req = trailingslashit( home_url( add_query_arg( array() ) ) );
$mf_is  = function ( $slug ) use ( $mf_req ) {
	$url = mf_page_url( $slug );
	if ( ! $url ) {
		return false;
	}
	return 0 === strpos( $mf_req, trailingslashit( $url ) ) || untrailingslashit( $mf_req ) === untrailingslashit( $url );
};
?>
<nav class="mf-bottom-nav" aria-label="<?php esc_attr_e( 'Mobile primary', 'movieflix' ); ?>">
	<a class="mf-bottom-nav__item<?php echo is_front_page() ? ' is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<span class="mf-bottom-nav__icon" aria-hidden="true">⌂</span>
		<span class="mf-bottom-nav__label"><?php esc_html_e( 'Home', 'movieflix' ); ?></span>
	</a>
	<a class="mf-bottom-nav__item<?php echo ( is_post_type_archive( 'movie' ) || is_singular( 'movie' ) ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'movie' ) ); ?>">
		<span class="mf-bottom-nav__icon" aria-hidden="true">▶</span>
		<span class="mf-bottom-nav__label"><?php esc_html_e( 'Movies', 'movieflix' ); ?></span>
	</a>
	<a class="mf-bottom-nav__item<?php echo ( $mf_is( 'tv-shows' ) || is_post_type_archive( 'series' ) || is_singular( 'series' ) ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( mf_page_url( 'tv-shows' ) ?: get_post_type_archive_link( 'series' ) ); ?>">
		<span class="mf-bottom-nav__icon" aria-hidden="true">▤</span>
		<span class="mf-bottom-nav__label"><?php esc_html_e( 'TV', 'movieflix' ); ?></span>
	</a>
	<?php if ( mf_opt( 'enable_channels', 1 ) && ! ( class_exists( 'MF_Extras' ) && MF_Extras::is_kids_profile() ) ) : ?>
	<a class="mf-bottom-nav__item<?php echo ( $mf_is( 'channels' ) || is_singular( 'channel' ) ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( mf_page_url( 'channels' ) ); ?>">
		<span class="mf-bottom-nav__icon" aria-hidden="true">◉</span>
		<span class="mf-bottom-nav__label"><?php esc_html_e( 'Live', 'movieflix' ); ?></span>
	</a>
	<?php endif; ?>
	<a class="mf-bottom-nav__item<?php echo ( $mf_is( 'profile' ) || $mf_is( 'my-list' ) || $mf_is( 'continue-watching' ) || $mf_is( 'profiles' ) ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( is_user_logged_in() ? mf_page_url( 'profile' ) : mf_page_url( 'login' ) ); ?>">
		<span class="mf-bottom-nav__icon" aria-hidden="true"><?php echo is_user_logged_in() ? '👤' : '👤'; ?></span>
		<span class="mf-bottom-nav__label"><?php echo is_user_logged_in() ? esc_html__( 'Profile', 'movieflix' ) : esc_html__( 'Login', 'movieflix' ); ?></span>
	</a>
</nav>
<?php wp_footer(); ?>
</body>
</html>
