<?php
/**
 * Simple analytics dashboard from activity table.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Analytics_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 25 );
	}

	public static function menu() {
		add_submenu_page(
			'movieflix',
			__( 'Analytics', 'movieflix' ),
			__( 'Analytics', 'movieflix' ),
			'manage_options',
			'movieflix-analytics',
			array( __CLASS__, 'render' )
		);
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30;
		if ( ! in_array( $days, array( 7, 30, 90 ), true ) ) {
			$days = 30;
		}
		$data  = class_exists( 'MF_Extras' ) ? MF_Extras::analytics_summary( $days ) : array();
		$total = 0;
		if ( is_array( $data ) ) {
			foreach ( array( 'view', 'play', 'login', 'google_login', 'register', 'list_add', 'rate', 'search' ) as $k ) {
				$total += isset( $data[ $k ] ) ? (int) $data[ $k ] : 0;
			}
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'MovieFlix Analytics', 'movieflix' ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'Activity is recorded when members view titles, start playback, log in, search, rate, or add to My List. Numbers refresh as traffic arrives.', 'movieflix' ) . '</p>';
		echo '<p>';
		foreach ( array( 7, 30, 90 ) as $d ) {
			$url = admin_url( 'admin.php?page=movieflix-analytics&days=' . $d );
			echo '<a class="button' . ( $d === $days ? ' button-primary' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( sprintf( __( '%d days', 'movieflix' ), $d ) ) . '</a> ';
		}
		echo '</p>';
		if ( ! is_array( $data ) || ( 0 === $total && empty( $data['top_titles'] ) ) ) {
			echo '<div class="notice notice-info inline"><p>';
			echo esc_html__( 'No activity data yet for this period. Browse the site while logged in, play a title, search, or add to My List — then return here. Ensure activity logging is enabled under MovieFlix → Settings.', 'movieflix' );
			echo '</p></div></div>';
			return;
		}
		$cards = array(
			'view'         => __( 'Title views', 'movieflix' ),
			'play'         => __( 'Plays', 'movieflix' ),
			'login'        => __( 'Logins', 'movieflix' ),
			'google_login' => __( 'Google logins', 'movieflix' ),
			'register'     => __( 'Registrations', 'movieflix' ),
			'list_add'     => __( 'My List adds', 'movieflix' ),
			'rate'         => __( 'Ratings', 'movieflix' ),
			'search'       => __( 'Searches', 'movieflix' ),
		);
		echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin:20px 0">';
		foreach ( $cards as $k => $label ) {
			$v = isset( $data[ $k ] ) ? (int) $data[ $k ] : 0;
			echo '<div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:16px;text-align:center;box-shadow:0 1px 1px rgba(0,0,0,.04)">';
			echo '<div style="font-size:1.8rem;font-weight:700;color:#1d2327">' . esc_html( $v ) . '</div>';
			echo '<div style="color:#646970;margin-top:4px">' . esc_html( $label ) . '</div></div>';
		}
		echo '</div>';
		echo '<h2>' . esc_html__( 'Top titles', 'movieflix' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Title', 'movieflix' ) . '</th><th>' . esc_html__( 'Views + plays', 'movieflix' ) . '</th></tr></thead><tbody>';
		if ( ! empty( $data['top_titles'] ) ) {
			foreach ( $data['top_titles'] as $row ) {
				$id    = (int) $row['object_id'];
				$title = get_the_title( $id );
				$link  = get_edit_post_link( $id );
				echo '<tr><td>';
				if ( $link ) {
					echo '<a href="' . esc_url( $link ) . '">' . esc_html( $title ? $title : ( '#' . $id ) ) . '</a>';
				} else {
					echo esc_html( $title ? $title : ( '#' . $id ) );
				}
				echo '</td><td>' . esc_html( (int) $row['c'] ) . '</td></tr>';
			}
		} else {
			echo '<tr><td colspan="2">' . esc_html__( 'No title activity in this period.', 'movieflix' ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<p style="margin-top:16px;color:#646970">' . esc_html__( 'Note: CDN/transcoding and real DRM are external services — configure CDN base URL and signed URLs under MovieFlix → Settings if needed.', 'movieflix' ) . '</p>';
		echo '</div>';
	}
}
