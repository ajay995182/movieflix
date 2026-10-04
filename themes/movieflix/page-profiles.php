<?php
/**
 * Multi-profile picker / manager.
 *
 * @package MovieFlix
 */
movieflix_require_login();
get_header();
$uid      = get_current_user_id();
$profiles = class_exists( 'MF_Extras' ) ? MF_Extras::get_profiles( $uid ) : array();
$active   = class_exists( 'MF_Extras' ) ? MF_Extras::active_profile_id( $uid ) : 'default';
?>
<div class="mf-container mf-page">
	<div class="mf-page__head">
		<h1><?php esc_html_e( 'Who is watching?', 'movieflix' ); ?></h1>
		<p class="mf-page__desc"><?php esc_html_e( 'Choose a profile or manage up to 5 profiles (including Kids).', 'movieflix' ); ?></p>
	</div>
	<?php movieflix_notice(); ?>
	<?php if ( ! mf_opt( 'enable_profiles', 1 ) ) : ?>
		<p><?php esc_html_e( 'Profiles are disabled by the site admin.', 'movieflix' ); ?></p>
	<?php else : ?>
	<div class="mf-profiles-pick">
		<?php foreach ( $profiles as $pr ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mf-profile-switch">
				<input type="hidden" name="action" value="mf_switch_profile">
				<input type="hidden" name="profile_id" value="<?php echo esc_attr( $pr['id'] ); ?>">
				<?php wp_nonce_field( 'mf_switch_profile', '_mf' ); ?>
				<button type="submit" class="mf-profile-tile" aria-current="<?php echo $pr['id'] === $active ? 'true' : 'false'; ?>">
					<?php if ( ! empty( $pr['avatar'] ) ) : ?><img class="mf-profile-tile__avatar" src="<?php echo esc_url( $pr['avatar'] ); ?>" alt="" width="84" height="84" loading="lazy"><?php else : ?><span class="mf-profile-tile__avatar" aria-hidden="true"><?php echo esc_html( mb_substr( $pr['name'], 0, 1 ) ); ?></span><?php endif; ?>
					<strong><?php echo esc_html( $pr['name'] ); ?></strong>
					<?php if ( ! empty( $pr['kids'] ) ) : ?><small><?php esc_html_e( 'Kids profile', 'movieflix' ); ?></small><?php endif; ?>
					<?php if ( $pr['id'] === $active ) : ?><small><?php esc_html_e( 'Currently active', 'movieflix' ); ?></small><?php endif; ?>
				</button>
			</form>
		<?php endforeach; ?>
	</div>
	<h2><?php esc_html_e( 'Manage profiles', 'movieflix' ); ?></h2>
	<form class="mf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="mf_save_profiles">
		<?php wp_nonce_field( 'mf_profiles', '_mf' ); ?>
		<?php
		for ( $i = 0; $i < 5; $i++ ) :
			$row = isset( $profiles[ $i ] ) ? $profiles[ $i ] : array( 'id' => '', 'name' => '', 'kids' => 0 );
			?>
			<div class="mf-profile-manage">
				<div>
					<label for="mf-profile-name-<?php echo (int) $i; ?>"><?php echo esc_html( sprintf( __( 'Profile %d name', 'movieflix' ), $i + 1 ) ); ?></label>
					<input type="hidden" name="profiles[<?php echo (int) $i; ?>][id]" value="<?php echo esc_attr( $row['id'] ); ?>">
					<input type="text" id="mf-profile-name-<?php echo (int) $i; ?>" name="profiles[<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $row['name'] ); ?>" maxlength="40">
				</div>
				<label class="mf-check"><input type="checkbox" name="profiles[<?php echo (int) $i; ?>][kids]" value="1" <?php checked( ! empty( $row['kids'] ) ); ?>> <?php esc_html_e( 'Kids profile', 'movieflix' ); ?></label>
			</div>
		<?php endfor; ?>
		<button type="submit" class="mf-btn mf-btn--primary"><?php esc_html_e( 'Save profiles', 'movieflix' ); ?></button>
	</form>
	<?php endif; ?>
</div>
<?php get_footer(); ?>
