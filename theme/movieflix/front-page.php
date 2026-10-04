<?php
/**
 * Homepage.
 * Guests  → marketing landing (features + Watch Now → Login / Register)
 * Members → streaming dashboard (hero + rows)
 *
 * @package MovieFlix
 */

get_header();
movieflix_notice();

/* ---------- Guest marketing landing ---------- */
if ( ! is_user_logged_in() ) :
	$login_url    = mf_page_url( 'login' );
	$register_url = mf_page_url( 'register' );
	$reg_open     = (bool) mf_opt( 'enable_registration', 1 );
	$site_name    = get_bloginfo( 'name' );
	$guest_slides = movieflix_hero_posts();
	$guest_feature = $guest_slides ? $guest_slides[0] : null;
	$guest_art     = $guest_feature ? mf_img( $guest_feature->ID, 'backdrop', 'movieflix-backdrop' ) : '';
	?>
	<div class="mf-landing">

		<section class="mf-landing-hero">
			<?php if ( $guest_art ) : ?><img class="mf-landing-hero__image" src="<?php echo esc_url( $guest_art ); ?>" alt="" width="1920" height="1080" fetchpriority="high"><?php endif; ?>
			<div class="mf-landing-hero__bg" aria-hidden="true"></div>
			<div class="mf-landing-hero__inner">
				<span class="mf-eyebrow"><?php echo esc_html( $guest_feature ? ( 'series' === $guest_feature->post_type ? __( 'Featured series', 'movieflix' ) : __( 'Featured film', 'movieflix' ) ) : __( 'Premium Streaming', 'movieflix' ) ); ?></span>
				<h1><?php echo esc_html( $guest_feature ? get_the_title( $guest_feature ) : $site_name ); ?></h1>
				<?php if ( $guest_feature ) : ?>
					<div class="mf-meta">
						<?php if ( mf_meta( $guest_feature->ID, 'year' ) ) : ?><span><?php echo esc_html( mf_meta( $guest_feature->ID, 'year' ) ); ?></span><?php endif; ?>
						<?php if ( mf_meta( $guest_feature->ID, 'runtime' ) ) : ?><span><?php echo esc_html( sprintf( __( '%s min', 'movieflix' ), number_format_i18n( (int) mf_meta( $guest_feature->ID, 'runtime' ) ) ) ); ?></span><?php endif; ?>
						<?php if ( mf_display_rating( $guest_feature->ID ) ) : ?><span class="mf-rate">★ <?php echo esc_html( mf_display_rating( $guest_feature->ID ) ); ?></span><?php endif; ?>
					</div>
				<?php endif; ?>
				<p class="mf-landing-hero__lead">
					<?php echo esc_html( $guest_feature ? ( get_post_meta( $guest_feature->ID, '_mf_short_desc', true ) ?: wp_trim_words( wp_strip_all_tags( $guest_feature->post_content ), 28 ) ) : __( 'A considered collection of films and series, ready when you are. Sign in to continue watching and build a library of your own.', 'movieflix' ) ); ?>
				</p>
				<div class="mf-landing-cta">
					<?php if ( $reg_open ) : ?>
						<a class="mf-btn mf-btn--primary mf-btn--lg" href="<?php echo esc_url( $register_url ); ?>"><?php esc_html_e( 'Create a free account', 'movieflix' ); ?></a>
						<a class="mf-btn mf-btn--ghost mf-btn--lg" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Sign In', 'movieflix' ); ?></a>
					<?php else : ?>
						<a class="mf-btn mf-btn--primary mf-btn--lg" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Sign in to watch', 'movieflix' ); ?></a>
					<?php endif; ?>
					<a class="mf-btn mf-btn--ghost mf-btn--lg" href="<?php echo esc_url( get_post_type_archive_link( 'movie' ) ); ?>"><?php esc_html_e( 'Browse titles', 'movieflix' ); ?></a>
				</div>
				<p class="mf-landing-note">
					<?php if ( $reg_open ) : ?>
						<?php esc_html_e( 'New here?', 'movieflix' ); ?>
						<a href="<?php echo esc_url( $register_url ); ?>"><?php esc_html_e( 'Create a free account', 'movieflix' ); ?></a>
					<?php else : ?>
						<?php esc_html_e( 'Already a member?', 'movieflix' ); ?>
						<a href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Sign in to continue', 'movieflix' ); ?></a>
					<?php endif; ?>
				</p>
			</div>
		</section>

		<section class="mf-landing-features">
			<h2><?php esc_html_e( 'Everything you need to stream', 'movieflix' ); ?></h2>
			<p class="mf-section-sub"><?php esc_html_e( 'A modern streaming experience built for movies and series.', 'movieflix' ); ?></p>
			<div class="mf-features-grid">
				<article class="mf-feature">
				<div class="mf-feature__icon" aria-hidden="true"></div>
					<h3><?php esc_html_e( 'Huge library', 'movieflix' ); ?></h3>
					<p><?php esc_html_e( 'Movies, series, and episodes organized by genre, language, and popularity so you always find something to watch.', 'movieflix' ); ?></p>
				</article>
				<article class="mf-feature">
				<div class="mf-feature__icon" aria-hidden="true"></div>
					<h3><?php esc_html_e( 'Watch on any device', 'movieflix' ); ?></h3>
					<p><?php esc_html_e( 'Responsive design works on desktop, tablet, and mobile — pick up where you left off anytime.', 'movieflix' ); ?></p>
				</article>
				<article class="mf-feature">
				<div class="mf-feature__icon" aria-hidden="true"></div>
					<h3><?php esc_html_e( 'Continue watching', 'movieflix' ); ?></h3>
					<p><?php esc_html_e( 'Your progress is saved automatically so you can resume episodes and movies with one click.', 'movieflix' ); ?></p>
				</article>
				<article class="mf-feature">
				<div class="mf-feature__icon" aria-hidden="true"></div>
					<h3><?php esc_html_e( 'My List & ratings', 'movieflix' ); ?></h3>
					<p><?php esc_html_e( 'Save favorites to My List, rate titles, and get recommendations based on what you watch.', 'movieflix' ); ?></p>
				</article>
				<article class="mf-feature">
				<div class="mf-feature__icon" aria-hidden="true"></div>
					<h3><?php esc_html_e( 'Smart search', 'movieflix' ); ?></h3>
					<p><?php esc_html_e( 'Search by title, actor, director, or genre and filter by year, quality, language, and more.', 'movieflix' ); ?></p>
				</article>
				<article class="mf-feature">
				<div class="mf-feature__icon" aria-hidden="true"></div>
					<h3><?php esc_html_e( 'Secure accounts', 'movieflix' ); ?></h3>
					<p><?php esc_html_e( 'Sign up with email or Google. Your watch history and list stay private to your account.', 'movieflix' ); ?></p>
				</article>
			</div>
		</section>

		<section class="mf-landing-how">
			<h2><?php esc_html_e( 'How it works', 'movieflix' ); ?></h2>
			<div class="mf-steps">
				<div class="mf-step">
					<div class="mf-step__num">1</div>
					<h3><?php esc_html_e( 'Create account', 'movieflix' ); ?></h3>
					<p><?php esc_html_e( 'Register with email or continue with Google in seconds.', 'movieflix' ); ?></p>
				</div>
				<div class="mf-step">
					<div class="mf-step__num">2</div>
					<h3><?php esc_html_e( 'Browse & add', 'movieflix' ); ?></h3>
					<p><?php esc_html_e( 'Explore trending titles and build your personal My List.', 'movieflix' ); ?></p>
				</div>
				<div class="mf-step">
					<div class="mf-step__num">3</div>
					<h3><?php esc_html_e( 'Press play', 'movieflix' ); ?></h3>
					<p><?php esc_html_e( 'Stream instantly and continue later from any device.', 'movieflix' ); ?></p>
				</div>
			</div>
		</section>

		<?php
		/* Real title previews remain linked; the Core plugin controls access to protected detail and watch routes. */
		if ( class_exists( 'MF_Query' ) ) {
			$preview = MF_Query::row( 'trending', 10 );
			if ( $preview && $preview->have_posts() ) :
				?>
				<section class="mf-landing-preview">
					<h2><?php esc_html_e( 'Trending now', 'movieflix' ); ?></h2>
					<?php movieflix_row( '', $preview ); ?>
					<p class="mf-landing-lock">
						<?php esc_html_e( 'Sign in to play titles and continue to your personal home.', 'movieflix' ); ?>
						<a href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Sign in', 'movieflix' ); ?></a>
						<?php if ( $reg_open ) : ?>
							· <a href="<?php echo esc_url( $register_url ); ?>"><?php esc_html_e( 'Create account', 'movieflix' ); ?></a>
						<?php endif; ?>
					</p>
				</section>
				<?php
			endif;
		}
		?>

		<section class="mf-landing-cta-band">
			<h2><?php esc_html_e( 'Ready to watch?', 'movieflix' ); ?></h2>
			<p><?php esc_html_e( 'Join now and open your personal movies dashboard in one click.', 'movieflix' ); ?></p>
			<div class="mf-landing-cta">
				<?php if ( $reg_open ) : ?>
					<a class="mf-btn mf-btn--primary mf-btn--lg" href="<?php echo esc_url( $register_url ); ?>"><span aria-hidden="true">▶</span> <?php esc_html_e( 'Watch Now', 'movieflix' ); ?></a>
					<a class="mf-btn mf-btn--ghost mf-btn--lg" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Sign In', 'movieflix' ); ?></a>
				<?php else : ?>
					<a class="mf-btn mf-btn--primary mf-btn--lg" href="<?php echo esc_url( $login_url ); ?>"><span aria-hidden="true">▶</span> <?php esc_html_e( 'Sign In to Watch', 'movieflix' ); ?></a>
				<?php endif; ?>
			</div>
		</section>

	</div>
	<?php
	get_footer();
	return;
endif;

/* ---------- Logged-in streaming dashboard ---------- */
$mf_slides = movieflix_hero_posts();
$mf_hero   = $mf_slides ? $mf_slides[0] : null;

if ( ! $mf_hero ) {
	echo '<div class="mf-container mf-page">';
	movieflix_empty(
		__( 'No movies yet', 'movieflix' ),
		current_user_can( 'manage_options' )
			? __( 'Publish your first movie or series from MovieFlix → Dashboard to start building the catalog.', 'movieflix' )
			: __( 'Check back soon!', 'movieflix' ),
		current_user_can( 'manage_options' ) ? __( 'Open MovieFlix dashboard', 'movieflix' ) : '',
		admin_url( 'admin.php?page=movieflix' )
	);
	echo '</div>';
	get_footer();
	return;
}

get_template_part( 'template-parts/hero', null, array( 'posts' => $mf_slides ) );
movieflix_chips();
$mf_kids = class_exists( 'MF_Extras' ) && MF_Extras::is_kids_profile();
if ( $mf_kids ) {
	echo '<div class="mf-kids-banner mf-container"><strong>' . esc_html__( 'Kids mode', 'movieflix' ) . '</strong> <span>' . esc_html__( 'Only family-friendly titles are shown. Switch profile to exit.', 'movieflix' ) . '</span> <a class="mf-btn mf-btn--ghost mf-btn--sm" href="' . esc_url( mf_page_url( 'profiles' ) ) . '">' . esc_html__( 'Switch profile', 'movieflix' ) . '</a></div>';
}
echo '<div class="mf-rows' . ( $mf_kids ? ' mf-rows--kids' : '' ) . '">';


if ( mf_opt( 'enable_continue', 1 ) ) {
	$mf_rows = MF_Query::continue_rows( get_current_user_id(), 14 );
	if ( $mf_rows ) {
		movieflix_row(
			__( 'Continue Watching', 'movieflix' ),
			MF_Query::by_ids( array_keys( $mf_rows ), 14 ),
			array(
				'progress'  => movieflix_progress_map( $mf_rows ),
				'remaining' => movieflix_remaining_map( $mf_rows ),
				'remove'    => true,
				'more'      => mf_page_url( 'continue-watching' ),
			)
		);
	}
}

if ( $mf_kids ) {
	movieflix_row( __( 'Kids favourites', 'movieflix' ), MF_Query::row( 'genre:kids', 14 ) );
	movieflix_row( __( 'Family fun', 'movieflix' ), MF_Query::row( 'genre:family', 14 ) );
	movieflix_row( __( 'Animation', 'movieflix' ), MF_Query::row( 'genre:animation', 14 ) );
} else {
	movieflix_row( __( 'Trending Now', 'movieflix' ), MF_Query::row( 'trending', 14 ) );
}
movieflix_top10_row();

if ( mf_opt( 'enable_continue', 1 ) ) {
	$mf_hist = MF_Query::history_rows( get_current_user_id(), 1 );
	if ( $mf_hist ) {
		$mf_last = (int) key( $mf_hist );
		if ( 'episode' === get_post_type( $mf_last ) ) {
			$mf_last = (int) mf_meta( $mf_last, 'series' );
		}
		if ( $mf_last && 'publish' === get_post_status( $mf_last ) ) {
			$mf_sim = MF_Query::similar_ids( $mf_last, 14 );
			if ( $mf_sim && 0 !== $mf_sim[0] ) {
				movieflix_row( sprintf( __( 'Because you watched %s', 'movieflix' ), get_the_title( $mf_last ) ), MF_Query::by_ids( $mf_sim, 14 ) );
			}
		}
	}
}

if ( is_user_logged_in() ) {
	movieflix_row( __( 'Recommended for You', 'movieflix' ), MF_Query::row( 'recommended', 14 ) );
}
movieflix_row( __( 'Popular Movies', 'movieflix' ), MF_Query::row( 'popular', 14, 'movie' ), array( 'more' => add_query_arg( 'mf_sort', 'popular', get_post_type_archive_link( 'movie' ) ) ) );
movieflix_row( __( 'New Releases', 'movieflix' ), MF_Query::row( 'new', 14, 'movie' ), array( 'more' => get_post_type_archive_link( 'movie' ) ) );
movieflix_row( __( 'Popular TV Shows', 'movieflix' ), MF_Query::row( 'popular', 14, 'series' ), array( 'more' => mf_page_url( 'tv-shows' ) ) );

if ( mf_opt( 'enable_membership', 0 ) && class_exists( 'MF_Extras' ) && MF_Extras::user_is_member() ) {
	movieflix_row( __( 'Member Picks', 'movieflix' ), MF_Query::row( 'members', 14 ) );
}

if ( mf_opt( 'enable_watchlist', 1 ) ) {
	$mf_list = mf_user_list_ids();
	if ( $mf_list ) {
		movieflix_row( __( 'My List', 'movieflix' ), MF_Query::by_ids( array_slice( $mf_list, 0, 14 ), 14 ), array( 'more' => mf_page_url( 'my-list' ) ) );
	}
}

$mf_langs = get_terms( array( 'taxonomy' => 'mf_language', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 5 ) );
if ( $mf_langs && ! is_wp_error( $mf_langs ) ) {
	$mf_shown = 0;
	foreach ( $mf_langs as $mf_l ) {
		if ( $mf_shown >= 3 ) {
			continue;
		}
		/* translators: %s: language name */
		movieflix_tax_row( sprintf( __( '%s Movies & Shows', 'movieflix' ), $mf_l->name ), 'mf_language', $mf_l->slug );
		$mf_shown++;
	}
}

$mf_genres = get_terms( array( 'taxonomy' => 'genre', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 6 ) );
if ( $mf_genres && ! is_wp_error( $mf_genres ) ) {
	foreach ( $mf_genres as $mf_genre ) {
		$mf_genre_link = get_term_link( $mf_genre );
		movieflix_row( $mf_genre->name, MF_Query::row( 'genre:' . $mf_genre->slug, 14 ), array( 'more' => is_wp_error( $mf_genre_link ) ? '' : $mf_genre_link ) );
	}
}


echo '</div>';
get_footer();
