<?php
/**
 * Site header.
 *
 * @package MovieFlix
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0b0b12">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'movieflix' ); ?></a>
<header class="mf-header" id="mf-header">
	<div class="mf-header__inner">
		<button type="button" class="mf-burger" aria-label="<?php esc_attr_e( 'Open menu', 'movieflix' ); ?>" aria-expanded="false" aria-controls="mf-nav"><span></span><span></span><span></span></button>
		<?php movieflix_logo(); ?>
		<nav class="mf-nav" id="mf-nav" aria-label="<?php esc_attr_e( 'Primary', 'movieflix' ); ?>"><?php movieflix_nav(); ?></nav>
		<div class="mf-header__tools">
			<button type="button" class="mf-icon-btn mf-search-toggle" aria-label="<?php esc_attr_e( 'Search', 'movieflix' ); ?>" aria-expanded="false" aria-controls="mf-search"><span class="screen-reader-text"><?php esc_html_e( 'Search', 'movieflix' ); ?></span></button>
			<?php if ( is_user_logged_in() ) : $mf_u = wp_get_current_user(); $mf_unread = class_exists( 'MF_Notify' ) ? MF_Notify::unread_count() : 0; ?>
				<div class="mf-notify" id="mf-notify">
					<button type="button" class="mf-icon-btn mf-notify__btn" id="mf-notify-btn" aria-label="<?php esc_attr_e( 'Notifications', 'movieflix' ); ?>" aria-expanded="false" aria-controls="mf-notify-panel">
						<span aria-hidden="true">🔔</span>
						<?php if ( $mf_unread ) : ?><span class="mf-notify__badge" id="mf-notify-badge"><?php echo (int) $mf_unread; ?></span><?php else : ?><span class="mf-notify__badge" id="mf-notify-badge" hidden>0</span><?php endif; ?>
					</button>
					<div class="mf-notify__panel" id="mf-notify-panel" hidden>
						<div class="mf-notify__head">
							<strong><?php esc_html_e( 'Notifications', 'movieflix' ); ?></strong>
							<button type="button" class="mf-notify__readall" id="mf-notify-readall"><?php esc_html_e( 'Mark all read', 'movieflix' ); ?></button>
						</div>
						<ul class="mf-notify__list" id="mf-notify-list"><li class="mf-notify__empty"><?php esc_html_e( 'Loading…', 'movieflix' ); ?></li></ul>
						<p class="mf-notify__hint"><?php esc_html_e( 'Enable browser alerts to get notified about new titles.', 'movieflix' ); ?>
							<button type="button" class="mf-btn mf-btn--ghost mf-btn--sm" id="mf-notify-enable"><?php esc_html_e( 'Enable', 'movieflix' ); ?></button>
						</p>
					</div>
				</div>

				<div class="mf-user">
					<button type="button" class="mf-user__btn" aria-haspopup="true" aria-expanded="false" aria-label="<?php esc_attr_e( 'Account menu', 'movieflix' ); ?>"><?php echo get_avatar( $mf_u->ID, 34, '', '', array( 'class' => 'mf-avatar' ) ); // phpcs:ignore ?></button>
					<ul class="mf-user__menu" aria-hidden="true">
						<li><span class="mf-user__name"><?php echo esc_html( $mf_u->display_name ); ?></span></li>
						<li><a href="<?php echo esc_url( mf_page_url( 'profile' ) ); ?>"><?php esc_html_e( 'Profile', 'movieflix' ); ?></a></li>
						<li><a href="<?php echo esc_url( mf_page_url( 'my-list' ) ); ?>"><?php esc_html_e( 'My List', 'movieflix' ); ?></a></li>
						<li><a href="<?php echo esc_url( mf_page_url( 'continue-watching' ) ); ?>"><?php esc_html_e( 'Continue Watching', 'movieflix' ); ?></a></li>
						<?php if ( current_user_can( 'edit_posts' ) ) : ?><li><a href="<?php echo esc_url( admin_url( 'admin.php?page=movieflix' ) ); ?>"><?php esc_html_e( 'Admin', 'movieflix' ); ?></a></li><?php endif; ?>
						<li><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Log out', 'movieflix' ); ?></a></li>
					</ul>
				</div>
			<?php else : ?>
				<a class="mf-btn mf-btn--ghost mf-hide-sm" href="<?php echo esc_url( mf_page_url( 'login' ) ); ?>"><?php esc_html_e( 'Login', 'movieflix' ); ?></a>
				<?php if ( mf_opt( 'enable_registration', 1 ) ) : ?><a class="mf-btn mf-btn--primary" href="<?php echo esc_url( mf_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Register', 'movieflix' ); ?></a><?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
	<div class="mf-search" id="mf-search" hidden>
		<form role="search" method="get" action="<?php echo esc_url( mf_page_url( 'search' ) ); ?>">
			<label class="screen-reader-text" for="mf-search-input"><?php esc_html_e( 'Search movies, actors, genres', 'movieflix' ); ?></label>
			<input type="search" id="mf-search-input" name="s" autocomplete="off" aria-autocomplete="list" aria-controls="mf-suggest" aria-expanded="false" placeholder="<?php esc_attr_e( 'Search movies, actors, directors, genres…', 'movieflix' ); ?>">
			<button type="submit" class="mf-btn mf-btn--primary"><?php esc_html_e( 'Search', 'movieflix' ); ?></button>
		</form>
		<ul class="mf-suggest" id="mf-suggest" role="listbox" aria-label="<?php esc_attr_e( 'Search suggestions', 'movieflix' ); ?>"></ul>
		<p class="mf-search-status screen-reader-text" id="mf-search-status" role="status" aria-live="polite"></p>
	</div>
</header>
<main id="main" class="mf-main">
