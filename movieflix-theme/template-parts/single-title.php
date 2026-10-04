<?php
/**
 * Movie / series details.
 *
 * @package MovieFlix
 */
while ( have_posts() ) :
	the_post();
	$id        = get_the_ID();
	$is_series = 'series' === get_post_type();
	$trailer   = mf_resolve_url( mf_meta( $id, 'trailer_url' ) );
	$embed     = $trailer ? movieflix_embed_url( $trailer ) : '';
	$genres    = movieflix_terms( $id, 'genre' );
	$cr        = get_the_terms( $id, 'content_rating' );
	$age       = mf_meta( $id, 'age_rating' ) ? mf_meta( $id, 'age_rating' ) : ( $cr && ! is_wp_error( $cr ) ? $cr[0]->name : '' );
	$quality   = get_the_terms( $id, 'quality' );
	?>
<article class="mf-single">
	<div class="mf-detail-hero">
		<img class="mf-detail-hero__bg" src="<?php echo esc_url( mf_img( $id, 'backdrop', 'movieflix-backdrop' ) ); ?>" alt="" fetchpriority="high">
		<div class="mf-hero__shade"></div>
		<div class="mf-container mf-detail-hero__inner">
			<img class="mf-detail-poster" src="<?php echo esc_url( mf_img( $id, 'poster', 'movieflix-poster' ) ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Poster of %s', 'movieflix' ), get_the_title() ) ); ?>" width="300" height="450">
			<div class="mf-detail-info">
				<?php movieflix_notice(); ?>
				<h1><?php echo esc_html( get_the_title() ); ?></h1>
				<p class="mf-meta">
					<?php if ( mf_meta( $id, 'year' ) ) : ?><span><?php echo esc_html( mf_meta( $id, 'year' ) ); ?></span><?php endif; ?>
					<?php if ( mf_meta( $id, 'runtime' ) ) : ?><span><?php echo esc_html( mf_meta( $id, 'runtime' ) ); ?> min</span><?php endif; ?>
					<?php if ( $age ) : ?><span class="mf-pill"><?php echo esc_html( $age ); ?></span><?php endif; ?>
					<?php if ( $quality && ! is_wp_error( $quality ) ) : ?><span class="mf-pill mf-pill--q"><?php echo esc_html( $quality[0]->name ); ?></span><?php endif; ?>
					<?php if ( mf_display_rating( $id ) ) : ?><span class="mf-rate">★ <?php echo esc_html( mf_display_rating( $id ) ); ?></span><?php endif; ?>
				</p>
				<?php if ( $genres ) : ?><p class="mf-genres"><?php echo wp_kses_post( $genres ); ?></p><?php endif; ?>
				<p class="mf-lead"><?php echo esc_html( mf_meta( $id, 'short_desc', wp_trim_words( get_the_excerpt(), 40 ) ) ); ?></p>
				<div class="mf-hero__actions">
					<a class="mf-btn mf-btn--primary mf-btn--lg" href="<?php echo esc_url( mf_watch_url( $id ) ); ?>"><span aria-hidden="true">▶</span> <?php esc_html_e( 'Watch Now', 'movieflix' ); ?></a>
					<?php if ( $trailer ) : ?><button type="button" class="mf-btn mf-btn--ghost mf-btn--lg mf-trailer-btn" data-embed="<?php echo esc_url( $embed ); ?>" data-src="<?php echo esc_url( $embed ? '' : $trailer ); ?>"><span aria-hidden="true">▷</span> <?php esc_html_e( 'Trailer', 'movieflix' ); ?></button><?php endif; ?>
					<?php movieflix_list_button( $id ); ?>
					<span class="mf-share-menu" data-url="<?php echo esc_url( get_permalink() ); ?>" data-title="<?php echo esc_attr( get_the_title() ); ?>">
					<button type="button" class="mf-btn mf-btn--ghost mf-share-btn" data-url="<?php echo esc_url( get_permalink() ); ?>" data-title="<?php echo esc_attr( get_the_title() ); ?>"><span aria-hidden="true">↗</span> <?php esc_html_e( 'Share', 'movieflix' ); ?></button>
					<button type="button" class="mf-btn mf-btn--ghost" data-share="copy"><?php esc_html_e( 'Copy link', 'movieflix' ); ?></button>
					<a class="mf-btn mf-btn--ghost" target="_blank" rel="noopener" href="https://wa.me/?text=<?php echo rawurlencode( get_the_title() . ' ' . get_permalink() ); ?>">WhatsApp</a>
					<a class="mf-btn mf-btn--ghost" target="_blank" rel="noopener" href="https://t.me/share/url?url=<?php echo rawurlencode( get_permalink() ); ?>&text=<?php echo rawurlencode( get_the_title() ); ?>">Telegram</a>
					<button type="button" class="mf-btn mf-btn--ghost" data-share="native"><?php esc_html_e( 'More', 'movieflix' ); ?></button>
				</span>
					<?php if ( '1' === (string) mf_meta( $id, 'download_enabled' ) && mf_meta( $id, 'download_url' ) ) : ?><a class="mf-btn mf-btn--ghost" href="<?php echo esc_url( mf_meta( $id, 'download_url' ) ); ?>" download rel="noopener"><span aria-hidden="true">↓</span> <?php esc_html_e( 'Download', 'movieflix' ); ?></a><?php endif; ?>
				</div>
				<?php get_template_part( 'template-parts/rating', null, array( 'id' => $id ) ); ?>
			</div>
		</div>
	</div>

	<div class="mf-container mf-detail-body">
		<div class="mf-detail-main">
			<h2><?php esc_html_e( 'Overview', 'movieflix' ); ?></h2>
			<div class="mf-prose"><?php the_content(); ?></div>

			<?php if ( $is_series ) : $eps = MF_Query::episodes( $id ); ?>
				<?php
				// Up Next binge strip — next unwatched or first episode.
				$up_next = null;
				$up_label = __( 'Start watching', 'movieflix' );
				if ( $eps ) {
					$up_next = $eps[0];
					if ( is_user_logged_in() && mf_opt( 'enable_continue', 1 ) ) {
						global $wpdb;
						$uid = get_current_user_id();
						foreach ( $eps as $e ) {
							$row = $wpdb->get_row( $wpdb->prepare(
								"SELECT position, completed FROM {$wpdb->prefix}movieflix_watch_history WHERE user_id=%d AND movie_id=%d",
								$uid,
								$e->ID
							) );
							if ( ! $row || ! $row->completed ) {
								$up_next = $e;
								$up_label = $row ? __( 'Continue episode', 'movieflix' ) : __( 'Play next', 'movieflix' );
								break;
							}
						}
						// If all complete, suggest first.
						if ( ! $up_next ) {
							$up_next = $eps[0];
							$up_label = __( 'Watch again', 'movieflix' );
						}
					}
				}
				if ( $up_next ) :
					$sn = (int) mf_meta( $up_next->ID, 'season', 1 );
					$en = (int) mf_meta( $up_next->ID, 'episode', 1 );
					?>
					<div class="mf-upnext">
						<div class="mf-upnext__art">
							<img src="<?php echo esc_url( mf_img( $up_next->ID, 'backdrop', 'movieflix-backdrop' ) ?: mf_img( $id, 'backdrop', 'movieflix-backdrop' ) ); ?>" alt="" loading="lazy">
						</div>
						<div class="mf-upnext__body">
							<p class="mf-upnext__eyebrow"><?php esc_html_e( 'Up Next', 'movieflix' ); ?></p>
							<strong><?php echo esc_html( sprintf( 'S%d E%d · %s', $sn, $en, get_the_title( $up_next ) ) ); ?></strong>
							<?php if ( get_the_excerpt( $up_next ) ) : ?><p><?php echo esc_html( wp_trim_words( get_the_excerpt( $up_next ), 22 ) ); ?></p><?php endif; ?>
							<a class="mf-btn mf-btn--primary" href="<?php echo esc_url( mf_watch_url( $up_next->ID ) ); ?>"><span aria-hidden="true">▶</span> <?php echo esc_html( $up_label ); ?></a>
						</div>
					</div>
				<?php endif; ?>

				<h2><?php esc_html_e( 'Episodes', 'movieflix' ); ?></h2>
				<?php if ( $eps ) : $season_groups = array(); ?>
					<?php foreach ( $eps as $e ) { $sn = (int) mf_meta( $e->ID, 'season', 1 ); $season_groups[ $sn ][] = $e; } ?>
					<?php $first_season = (int) key( $season_groups ); ?>
					<?php if ( count( $season_groups ) > 1 ) : ?>
						<label class="screen-reader-text" for="mf-season-select"><?php esc_html_e( 'Choose a season', 'movieflix' ); ?></label>
						<select class="mf-season-select" id="mf-season-select">
							<?php foreach ( $season_groups as $sn => $season_eps ) : ?><option value="<?php echo (int) $sn; ?>"><?php echo esc_html( sprintf( __( 'Season %d', 'movieflix' ), $sn ) ); ?></option><?php endforeach; ?>
						</select>
					<?php endif; ?>
					<?php foreach ( $season_groups as $sn => $season_eps ) : ?>
						<section class="mf-season-panel" data-season="<?php echo (int) $sn; ?>"<?php echo $sn !== $first_season ? ' hidden' : ''; ?>>
							<h3 class="mf-season"><?php echo esc_html( sprintf( __( 'Season %d', 'movieflix' ), $sn ) ); ?></h3>
							<ul class="mf-episodes">
								<?php foreach ( $season_eps as $e ) : ?>
									<li class="mf-episode-card">
										<a href="<?php echo esc_url( mf_watch_url( $e->ID ) ); ?>">
											<img class="mf-episode-card__thumb" src="<?php echo esc_url( mf_img( $e->ID, 'backdrop', 'movieflix-backdrop' ) ); ?>" alt="" width="160" height="90" loading="lazy" decoding="async">
											<span class="mf-ep-no"><?php echo (int) mf_meta( $e->ID, 'episode' ); ?></span>
											<span class="mf-episode-card__info"><strong><?php echo esc_html( get_the_title( $e ) ); ?></strong>
												<?php if ( mf_meta( $e->ID, 'runtime' ) ) : ?><small><?php echo esc_html( mf_meta( $e->ID, 'runtime' ) ); ?> min</small><?php endif; ?>
												<?php if ( get_the_excerpt( $e ) ) : ?><small><?php echo esc_html( wp_trim_words( get_the_excerpt( $e ), 24 ) ); ?></small><?php endif; ?>
											</span>
											<span class="mf-ep-play" aria-hidden="true">▶</span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endforeach; ?>
				<?php else : movieflix_empty( __( 'No episodes yet', 'movieflix' ), __( 'Episodes will appear here once added.', 'movieflix' ) ); endif; ?>
			<?php endif; ?>

			<?php
			$cast_ids = array_filter( array_map( 'absint', (array) mf_meta( $id, 'cast', array() ) ) );
			if ( $cast_ids ) :
				?>
				<h2><?php esc_html_e( 'Cast', 'movieflix' ); ?></h2>
				<div class="mf-cast">
					<?php foreach ( get_posts( array( 'post_type' => 'person', 'post__in' => $cast_ids, 'orderby' => 'post__in', 'posts_per_page' => 24, 'no_found_rows' => true ) ) as $person ) : ?>
						<a class="mf-person" href="<?php echo esc_url( get_permalink( $person ) ); ?>"><img src="<?php echo esc_url( mf_img( $person->ID, 'poster', 'thumbnail' ) ); ?>" alt="" loading="lazy" width="96" height="96"><span><?php echo esc_html( $person->post_title ); ?></span></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<aside class="mf-detail-side" aria-label="<?php esc_attr_e( 'Details', 'movieflix' ); ?>">
			<h2><?php esc_html_e( 'Details', 'movieflix' ); ?></h2>
			<dl class="mf-dl">
				<?php
				$rows = array(
					__( 'Director', 'movieflix' )  => movieflix_people( $id, 'directors' ),
					__( 'Writer', 'movieflix' )    => movieflix_people( $id, 'writers' ),
					__( 'Producer', 'movieflix' )  => movieflix_people( $id, 'producers' ),
					__( 'Language', 'movieflix' )  => movieflix_terms( $id, 'mf_language' ),
					__( 'Country', 'movieflix' )   => movieflix_terms( $id, 'mf_country' ),
					__( 'Audio', 'movieflix' )     => esc_html( mf_meta( $id, 'audio_language' ) ),
					__( 'Resolution', 'movieflix' ) => esc_html( mf_meta( $id, 'resolution' ) ),
					__( 'Subtitles', 'movieflix' ) => '1' === (string) mf_meta( $id, 'subtitles_available' ) ? esc_html__( 'Available', 'movieflix' ) : '',
					__( 'Tags', 'movieflix' )      => movieflix_terms( $id, 'post_tag' ),
				);
				foreach ( $rows as $label => $val ) {
					if ( $val ) {
						echo '<dt>' . esc_html( $label ) . '</dt><dd>' . wp_kses_post( $val ) . '</dd>';
					}
				}
				?>
			</dl>
		</aside>
	</div>

	<div class="mf-container"><?php get_template_part( 'template-parts/reviews', null, array( 'id' => $id ) ); ?></div>

	<?php
	$sim = MF_Query::similar_ids( $id, 14 );
	if ( $sim ) {
		movieflix_row( __( 'More Like This', 'movieflix' ), MF_Query::by_ids( $sim, 14 ) );
	}
	$first = get_the_terms( $id, 'genre' );
	if ( $first && ! is_wp_error( $first ) ) {
		$same = get_posts( array( 'post_type' => get_post_type(), 'post_status' => 'publish', 'post__not_in' => array( $id ), 'posts_per_page' => 14, 'fields' => 'ids', 'no_found_rows' => true, 'tax_query' => array( array( 'taxonomy' => 'genre', 'terms' => $first[0]->term_id ) ) ) );
		if ( $same ) {
			movieflix_row( sprintf( __( 'Similar: %s', 'movieflix' ), $first[0]->name ), MF_Query::by_ids( $same, 14 ), array( 'more' => get_term_link( $first[0] ) ) );
		}
	}
			movieflix_row( is_user_logged_in() ? __( 'Recommended for You', 'movieflix' ) : __( 'Popular Titles', 'movieflix' ), MF_Query::row( is_user_logged_in() ? 'recommended' : 'popular', 14 ) );
	?>
</article>
<div class="mf-modal" id="mf-trailer" hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Trailer', 'movieflix' ); ?>">
	<button type="button" class="mf-modal__close" aria-label="<?php esc_attr_e( 'Close trailer', 'movieflix' ); ?>">×</button>
	<div class="mf-modal__body"></div>
</div>
<?php endwhile; ?>
