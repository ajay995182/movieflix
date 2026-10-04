<?php
/**
 * Browse grid with filters. Args: title, desc, q (WP_Query|null = main), custom (bool), action.
 *
 * @package MovieFlix
 */
global $wp_query;
$q      = isset( $args['q'] ) && $args['q'] ? $args['q'] : $wp_query;
$custom = ! empty( $args['custom'] );
?>
<div class="mf-container mf-page">
	<header class="mf-page__head">
		<h1><?php echo esc_html( $args['title'] ); ?></h1>
		<?php if ( ! empty( $args['desc'] ) ) : ?><div class="mf-page__desc"><?php echo wp_kses_post( $args['desc'] ); ?></div><?php endif; ?>
	</header>
	<?php movieflix_notice(); ?>
	<?php movieflix_filters( isset( $args['action'] ) ? $args['action'] : '' ); ?>
	<p class="mf-count" aria-live="polite"><?php echo esc_html( sprintf( _n( '%s result', '%s results', (int) $q->found_posts, 'movieflix' ), number_format_i18n( (int) $q->found_posts ) ) ); ?></p>
	<?php
	if ( $q->have_posts() && ( empty( $q->query_vars['post__in'] ) || array( 0 ) !== $q->query_vars['post__in'] ) ) {
		movieflix_grid( $q );
		movieflix_pagination( $q, $custom );
	} else {
		movieflix_empty( __( 'No movies found', 'movieflix' ), __( 'Try different keywords or clear some filters.', 'movieflix' ), __( 'Browse all movies', 'movieflix' ), get_post_type_archive_link( 'movie' ) );
	}
	wp_reset_postdata();
	?>
</div>
