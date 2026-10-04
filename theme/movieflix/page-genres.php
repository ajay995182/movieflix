<?php
/**
 * Browse every populated genre with real title previews.
 *
 * @package MovieFlix
 */
get_header();
$mf_per_page  = 18;
$mf_page      = isset( $_GET['genre_page'] ) ? max( 1, absint( $_GET['genre_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
$mf_total     = wp_count_terms( 'genre', array( 'hide_empty' => true ) );
$mf_total     = is_wp_error( $mf_total ) ? 0 : (int) $mf_total;
$mf_total     = max( 0, $mf_total );
$mf_total_pages = max( 1, (int) ceil( $mf_total / $mf_per_page ) );
$mf_genres    = get_terms( array(
	'taxonomy'   => 'genre',
	'hide_empty' => true,
	'orderby'    => 'count',
	'order'      => 'DESC',
	'number'     => $mf_per_page,
	'offset'     => ( $mf_page - 1 ) * $mf_per_page,
) );
?>
<div class="mf-container mf-page">
	<header class="mf-page__head">
		<span class="mf-eyebrow"><?php esc_html_e( 'Browse by mood', 'movieflix' ); ?></span>
		<h1><?php esc_html_e( 'Genres', 'movieflix' ); ?></h1>
		<p class="mf-page__desc"><?php esc_html_e( 'Explore the catalog by genre, with previews drawn from published films and series.', 'movieflix' ); ?></p>
	</header>
	<?php if ( ! $mf_genres || is_wp_error( $mf_genres ) ) : ?>
		<?php movieflix_empty( __( 'No genres yet', 'movieflix' ), __( 'Genres will appear here after they are assigned to published titles.', 'movieflix' ) ); ?>
	<?php else : ?>
		<div class="mf-genre-grid">
			<?php foreach ( $mf_genres as $mf_genre ) :
				$mf_link = get_term_link( $mf_genre );
				if ( is_wp_error( $mf_link ) ) {
					continue;
				}
				$mf_preview = new WP_Query( array(
					'post_type'      => array( 'movie', 'series' ),
					'post_status'    => 'publish',
					'posts_per_page' => 4,
					'no_found_rows'  => true,
					'tax_query'      => array( array( 'taxonomy' => 'genre', 'field' => 'term_id', 'terms' => (int) $mf_genre->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				) );
				?>
				<article class="mf-genre-card">
					<header class="mf-genre-card__head">
						<div>
							<h2><a href="<?php echo esc_url( $mf_link ); ?>"><?php echo esc_html( $mf_genre->name ); ?></a></h2>
							<p><?php echo esc_html( sprintf( _n( '%s title', '%s titles', (int) $mf_genre->count, 'movieflix' ), number_format_i18n( (int) $mf_genre->count ) ) ); ?></p>
						</div>
						<a class="mf-link-btn" href="<?php echo esc_url( $mf_link ); ?>"><?php esc_html_e( 'View all', 'movieflix' ); ?><span aria-hidden="true"> →</span></a>
					</header>
					<?php if ( $mf_preview->have_posts() ) : ?>
						<div class="mf-genre-card__posters" aria-label="<?php echo esc_attr( sprintf( __( 'Preview titles in %s', 'movieflix' ), $mf_genre->name ) ); ?>">
							<?php foreach ( $mf_preview->posts as $mf_post ) : ?>
								<a href="<?php echo esc_url( get_permalink( $mf_post ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View %s', 'movieflix' ), get_the_title( $mf_post ) ) ); ?>">
									<img src="<?php echo esc_url( mf_img( $mf_post->ID, 'poster', 'movieflix-poster' ) ); ?>" alt="" width="120" height="180" loading="lazy" decoding="async">
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
		<?php if ( $mf_total_pages > 1 ) : ?>
			<nav class="mf-pagination" aria-label="<?php esc_attr_e( 'Genre pages', 'movieflix' ); ?>">
				<?php
				echo wp_kses_post( paginate_links( array(
					'base'      => esc_url_raw( add_query_arg( 'genre_page', '%#%', get_permalink() ) ),
					'format'    => '',
					'current'   => min( $mf_page, $mf_total_pages ),
					'total'     => $mf_total_pages,
					'prev_text' => '‹ ' . __( 'Prev', 'movieflix' ),
					'next_text' => __( 'Next', 'movieflix' ) . ' ›',
					'type'      => 'list',
				) ) );
				?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?php
get_footer();