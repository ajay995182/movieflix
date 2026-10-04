<?php
/**
 * Admin "Activity" monitor: live feed, online users, top titles, filters, CSV export.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Activity_Admin {

	const PER_PAGE = 50;

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 100 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_mf_activity_live', array( __CLASS__, 'ajax_live' ) );
		add_action( 'admin_post_mf_activity_csv', array( __CLASS__, 'csv' ) );
		add_action( 'admin_post_mf_activity_clear', array( __CLASS__, 'clear_all' ) );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'widget' ) );
		add_filter( 'manage_users_columns', array( __CLASS__, 'user_col' ) );
		add_filter( 'manage_users_custom_column', array( __CLASS__, 'user_col_val' ), 10, 3 );
	}

	public static function menu() {
		add_submenu_page( 'movieflix', 'User Activity', 'Activity', 'manage_options', 'movieflix-activity', array( __CLASS__, 'page' ) );
	}

	public static function assets( $hook ) {
		if ( false === strpos( $hook, 'movieflix-activity' ) ) {
			return;
		}
		wp_enqueue_style( 'mf-admin', MF_URL . 'assets/css/admin.css', array(), MF_VERSION );
		wp_enqueue_script( 'mf-activity', MF_URL . 'assets/js/activity.js', array(), MF_VERSION, true );
		wp_localize_script( 'mf-activity', 'MFAct', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'mf_activity_live' ) ) );
	}

	/** Parse filters from $_GET into a sanitised array. */
	private static function filters() {
		$g = function ( $k ) {
			return isset( $_GET[ $k ] ) ? sanitize_text_field( wp_unslash( $_GET[ $k ] ) ) : ''; // phpcs:ignore
		};
		$f = array( 'action' => sanitize_key( $g( 'act' ) ), 'user' => 0, 'from' => '', 'to' => '', 'q' => $g( 'q' ) );
		$u = $g( 'user' );
		if ( '' !== $u ) {
			if ( ctype_digit( $u ) ) {
				$f['user'] = (int) $u;
			} else {
				$obj = get_user_by( is_email( $u ) ? 'email' : 'login', $u );
				$f['user'] = $obj ? $obj->ID : -1;
			}
		}
		foreach ( array( 'from', 'to' ) as $k ) {
			$v = $g( $k );
			$f[ $k ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : '';
		}
		return $f;
	}

	/** @return array [where_sql, args] */
	private static function where( $f ) {
		global $wpdb;
		$w = array( '1=1' );
		$a = array();
		if ( $f['action'] ) {
			$w[] = 'action=%s';
			$a[] = $f['action'];
		}
		if ( $f['user'] ) {
			$w[] = 'user_id=%d';
			$a[] = max( 0, $f['user'] );
			if ( $f['user'] < 0 ) {
				$w[] = '1=0';
			}
		}
		if ( $f['from'] ) {
			$w[] = 'created_at >= %s';
			$a[] = $f['from'] . ' 00:00:00';
		}
		if ( $f['to'] ) {
			$w[] = 'created_at <= %s';
			$a[] = $f['to'] . ' 23:59:59';
		}
		if ( $f['q'] ) {
			$w[] = 'meta LIKE %s';
			$a[] = '%' . $wpdb->esc_like( $f['q'] ) . '%';
		}
		return array( implode( ' AND ', $w ), $a );
	}

	private static function cell_user( $row ) {
		if ( ! $row->user_id ) {
			return '<span class="mf-guest">' . esc_html__( 'Guest', 'movieflix' ) . '</span>';
		}
		$u = get_userdata( $row->user_id );
		if ( ! $u ) {
			return '<em>' . esc_html__( 'Deleted user', 'movieflix' ) . ' #' . (int) $row->user_id . '</em>';
		}
		return get_avatar( $u->ID, 24 ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=movieflix-activity&user=' . $u->ID ) ) . '">' . esc_html( $u->display_name ) . '</a> <small>' . esc_html( $u->user_email ) . '</small>';
	}

	/** One <tr> for a row. Shared by the page and the live endpoint. */
	public static function row_html( $r, $new = false ) {
		$acts = MF_Activity::actions();
		$a    = isset( $acts[ $r->action ] ) ? $acts[ $r->action ] : array( $r->action, '•' );
		list( $text, $url ) = MF_Activity::detail( $r );
		$ts   = strtotime( $r->created_at . ' UTC' );
		$out  = '<tr class="mf-act-row' . ( $new ? ' is-new' : '' ) . '" data-id="' . (int) $r->id . '">';
		$out .= '<td title="' . esc_attr( get_date_from_gmt( $r->created_at, 'Y-m-d H:i:s' ) ) . '">' . esc_html( sprintf( __( '%s ago', 'movieflix' ), human_time_diff( $ts, time() ) ) ) . '</td>';
		$out .= '<td>' . self::cell_user( $r ) . '</td>';
		$out .= '<td><span class="mf-act mf-act--' . esc_attr( $r->action ) . '">' . esc_html( $a[1] . ' ' . $a[0] ) . '</span></td>';
		$out .= '<td>' . ( $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $text ) . '</a>' : esc_html( $text ) ) . '</td>';
		$out .= '<td>' . esc_html( $r->ip ) . '</td></tr>';
		return $out;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $wpdb;
		$t     = MF_Activity::table();
		$f     = self::filters();
		list( $where, $args ) = self::where( $f );
		$paged = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore
		$off   = ( $paged - 1 ) * self::PER_PAGE;
		$sql_c = "SELECT COUNT(*) FROM $t WHERE $where";
		$total = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( $sql_c, $args ) ) : $wpdb->get_var( $sql_c ) ); // phpcs:ignore
		$sql_r = "SELECT * FROM $t WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d";
		$rows  = $wpdb->get_results( $wpdb->prepare( $sql_r, array_merge( $args, array( self::PER_PAGE, $off ) ) ) ); // phpcs:ignore
		$max   = $rows ? (int) $rows[0]->id : (int) $wpdb->get_var( "SELECT MAX(id) FROM $t" ); // phpcs:ignore
		$live  = ( 1 === $paged && ! $f['action'] && ! $f['user'] && ! $f['from'] && ! $f['to'] && ! $f['q'] );
		$day   = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		$week  = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );

		$online = self::online_users();
		$cards  = array(
			__( 'Online now', 'movieflix' )       => count( $online ),
			__( 'Active (24h)', 'movieflix' )     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM $t WHERE user_id>0 AND created_at>%s", $day ) ), // phpcs:ignore
			__( 'Events (24h)', 'movieflix' )     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE created_at>%s", $day ) ), // phpcs:ignore
			__( 'Plays (7 days)', 'movieflix' )   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE action='watch_start' AND created_at>%s", $week ) ), // phpcs:ignore
			__( 'Sign-ups (7 days)', 'movieflix' ) => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE action='register' AND created_at>%s", $week ) ), // phpcs:ignore
			__( 'Google logins (7d)', 'movieflix' ) => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE action='google_login' AND created_at>%s", $week ) ), // phpcs:ignore
		);

		echo '<div class="wrap mf-activity"><h1>' . esc_html__( 'User Activity', 'movieflix' ) . '</h1>';
		if ( ! mf_opt( 'activity_enabled', 1 ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Activity tracking is switched off in MovieFlix → Settings.', 'movieflix' ) . '</p></div>';
		}
		if ( isset( $_GET['cleared'] ) ) { // phpcs:ignore
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Activity log cleared.', 'movieflix' ) . '</p></div>';
		}
		echo '<div class="mf-cards">';
		foreach ( $cards as $l => $n ) {
			echo '<div class="mf-card"><b>' . esc_html( number_format_i18n( $n ) ) . '</b><span>' . esc_html( $l ) . '</span></div>';
		}
		echo '</div>';

		// Single-user header.
		if ( $f['user'] > 0 && get_userdata( $f['user'] ) ) {
			$u    = get_userdata( $f['user'] );
			$seen = (int) get_user_meta( $u->ID, 'mf_last_seen', true );
			echo '<div class="mf-userhead">' . get_avatar( $u->ID, 56 ) . '<div><h2>' . esc_html( $u->display_name ) . '</h2><p>' . esc_html( $u->user_email ) . ' · ' . esc_html( sprintf( __( 'Registered %s', 'movieflix' ), mysql2date( get_option( 'date_format' ), $u->user_registered ) ) ) . ' · ' . ( get_user_meta( $u->ID, 'mf_google_sub', true ) ? esc_html__( 'Google account linked', 'movieflix' ) . ' · ' : '' ) . ( $seen ? esc_html( sprintf( __( 'Last seen %s ago', 'movieflix' ), human_time_diff( $seen, time() ) ) ) : esc_html__( 'Never seen', 'movieflix' ) ) . '</p><p><a href="' . esc_url( get_edit_user_link( $u->ID ) ) . '">' . esc_html__( 'Edit user', 'movieflix' ) . '</a></p></div></div>';
		}

		// Filters.
		echo '<form method="get" class="mf-filterbar"><input type="hidden" name="page" value="movieflix-activity">';
		echo '<select name="act"><option value="">' . esc_html__( 'All actions', 'movieflix' ) . '</option>';
		foreach ( MF_Activity::actions() as $k => $a ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $f['action'], $k, false ), esc_html( $a[0] ) );
		}
		echo '</select>';
		printf( '<input type="text" name="user" placeholder="%s" value="%s">', esc_attr__( 'User ID, login or email', 'movieflix' ), esc_attr( isset( $_GET['user'] ) ? sanitize_text_field( wp_unslash( $_GET['user'] ) ) : '' ) ); // phpcs:ignore
		printf( '<input type="date" name="from" value="%s"> <input type="date" name="to" value="%s">', esc_attr( $f['from'] ), esc_attr( $f['to'] ) );
		printf( '<input type="search" name="q" placeholder="%s" value="%s">', esc_attr__( 'Search term / detail', 'movieflix' ), esc_attr( $f['q'] ) );
		echo '<button class="button button-primary">' . esc_html__( 'Filter', 'movieflix' ) . '</button> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=movieflix-activity' ) ) . '">' . esc_html__( 'Reset', 'movieflix' ) . '</a> ';
		$csv = wp_nonce_url( add_query_arg( array( 'action' => 'mf_activity_csv', 'act' => $f['action'], 'user' => isset( $_GET['user'] ) ? sanitize_text_field( wp_unslash( $_GET['user'] ) ) : '', 'from' => $f['from'], 'to' => $f['to'], 'q' => $f['q'] ), admin_url( 'admin-post.php' ) ), 'mf_activity_csv' ); // phpcs:ignore
		echo '<a class="button" href="' . esc_url( $csv ) . '">' . esc_html__( 'Export CSV', 'movieflix' ) . '</a>';
		if ( $live ) {
			echo ' <label class="mf-live"><input type="checkbox" id="mf-live"> ' . esc_html__( 'Live updates (every 10s)', 'movieflix' ) . '</label>';
		}
		echo '</form>';

		echo '<div class="mf-actgrid"><div class="mf-actmain">';
		echo '<table class="widefat striped mf-acttable"><thead><tr><th>' . esc_html__( 'When', 'movieflix' ) . '</th><th>' . esc_html__( 'User', 'movieflix' ) . '</th><th>' . esc_html__( 'Action', 'movieflix' ) . '</th><th>' . esc_html__( 'Details', 'movieflix' ) . '</th><th>IP</th></tr></thead><tbody id="mf-act-body" data-max="' . (int) $max . '" data-live="' . ( $live ? '1' : '0' ) . '">';
		if ( $rows ) {
			foreach ( $rows as $r ) {
				echo self::row_html( $r ); // phpcs:ignore -- escaped inside.
			}
		} else {
			echo '<tr class="mf-empty-row"><td colspan="5">' . esc_html__( 'No activity yet. Log in, watch something and refresh.', 'movieflix' ) . '</td></tr>';
		}
		echo '</tbody></table>';
		$pages = (int) ceil( $total / self::PER_PAGE );
		if ( $pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => $pages ) ) ) . '</div></div>';
		}
		echo '<p class="description">' . esc_html( sprintf( __( '%s events match.', 'movieflix' ), number_format_i18n( $total ) ) ) . '</p></div>';

		// Side panels.
		echo '<aside class="mf-actside">';
		echo '<div class="mf-box"><h2>' . esc_html__( 'Online now (last 5 min)', 'movieflix' ) . '</h2>';
		if ( $online ) {
			echo '<ul class="mf-mini">';
			foreach ( $online as $u ) {
				echo '<li>' . get_avatar( $u->ID, 24 ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=movieflix-activity&user=' . $u->ID ) ) . '">' . esc_html( $u->display_name ) . '</a></li>';
			}
			echo '</ul>';
		} else {
			echo '<p class="description">' . esc_html__( 'Nobody right now.', 'movieflix' ) . '</p>';
		}
		echo '</div>';
		$top = $wpdb->get_results( $wpdb->prepare( "SELECT object_id, COUNT(*) c FROM $t WHERE action='watch_start' AND object_id>0 AND created_at>%s GROUP BY object_id ORDER BY c DESC LIMIT 8", $week ) ); // phpcs:ignore
		echo '<div class="mf-box"><h2>' . esc_html__( 'Most watched (7 days)', 'movieflix' ) . '</h2>';
		if ( $top ) {
			echo '<ol class="mf-mini">';
			foreach ( $top as $x ) {
				echo '<li><a href="' . esc_url( get_permalink( $x->object_id ) ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( $x->object_id ) ) . '</a> <b>' . (int) $x->c . '</b></li>';
			}
			echo '</ol>';
		} else {
			echo '<p class="description">' . esc_html__( 'No plays yet.', 'movieflix' ) . '</p>';
		}
		echo '</div>';
		$act = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, COUNT(*) c FROM $t WHERE user_id>0 AND created_at>%s GROUP BY user_id ORDER BY c DESC LIMIT 8", $week ) ); // phpcs:ignore
		echo '<div class="mf-box"><h2>' . esc_html__( 'Most active users (7 days)', 'movieflix' ) . '</h2>';
		if ( $act ) {
			echo '<ol class="mf-mini">';
			foreach ( $act as $x ) {
				$u = get_userdata( $x->user_id );
				if ( $u ) {
					echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=movieflix-activity&user=' . $u->ID ) ) . '">' . esc_html( $u->display_name ) . '</a> <b>' . (int) $x->c . '</b></li>';
				}
			}
			echo '</ol>';
		} else {
			echo '<p class="description">' . esc_html__( 'No activity yet.', 'movieflix' ) . '</p>';
		}
		echo '</div>';
		$searches = $wpdb->get_results( $wpdb->prepare( "SELECT meta FROM $t WHERE action='search' AND created_at>%s ORDER BY id DESC LIMIT 200", $week ) ); // phpcs:ignore
		$terms    = array();
		foreach ( $searches as $s ) {
			$m = json_decode( $s->meta, true );
			if ( ! empty( $m['term'] ) ) {
				$k           = mb_strtolower( $m['term'] );
				$terms[ $k ] = isset( $terms[ $k ] ) ? $terms[ $k ] + 1 : 1;
			}
		}
		arsort( $terms );
		echo '<div class="mf-box"><h2>' . esc_html__( 'Top searches (7 days)', 'movieflix' ) . '</h2>';
		if ( $terms ) {
			echo '<ol class="mf-mini">';
			foreach ( array_slice( $terms, 0, 8, true ) as $k => $n ) {
				echo '<li>' . esc_html( $k ) . ' <b>' . (int) $n . '</b></li>';
			}
			echo '</ol>';
		} else {
			echo '<p class="description">' . esc_html__( 'No searches yet.', 'movieflix' ) . '</p>';
		}
		echo '</div>';
		$clear = wp_nonce_url( admin_url( 'admin-post.php?action=mf_activity_clear' ), 'mf_activity_clear' );
		echo '<p><a class="button" href="' . esc_url( $clear ) . '" onclick="return confirm(\'' . esc_js( __( 'Delete the entire activity log? This cannot be undone.', 'movieflix' ) ) . '\')">' . esc_html__( 'Clear entire log', 'movieflix' ) . '</a></p>';
		echo '</aside></div></div>';
	}

	/** @return WP_User[] Users seen in the last 5 minutes. */
	public static function online_users() {
		return get_users( array(
			'meta_key'     => 'mf_last_seen', // phpcs:ignore
			'meta_value'   => time() - 300,   // phpcs:ignore
			'meta_compare' => '>=',
			'meta_type'    => 'NUMERIC',
			'number'       => 50,
			'orderby'      => 'meta_value_num',
			'order'        => 'DESC',
		) );
	}

	public static function ajax_live() {
		check_ajax_referer( 'mf_activity_live', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		global $wpdb;
		$after = isset( $_GET['after'] ) ? absint( $_GET['after'] ) : 0;
		$rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . MF_Activity::table() . ' WHERE id > %d ORDER BY id DESC LIMIT 50', $after ) ); // phpcs:ignore
		$html  = '';
		foreach ( $rows as $r ) {
			$html .= self::row_html( $r, true );
		}
		wp_send_json_success( array( 'html' => $html, 'max' => $rows ? (int) $rows[0]->id : $after, 'online' => count( self::online_users() ) ) );
	}

	/** CSV export, formula-injection safe. */
	public static function csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'movieflix' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'mf_activity_csv' );
		global $wpdb;
		$f = self::filters();
		list( $where, $args ) = self::where( $f );
		$sql  = 'SELECT * FROM ' . MF_Activity::table() . " WHERE $where ORDER BY id DESC LIMIT 5000";
		$rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ) ) : $wpdb->get_results( $sql ); // phpcs:ignore
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="movieflix-activity-' . gmdate( 'Ymd-His' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'time_utc', 'user_id', 'user', 'email', 'action', 'title_or_detail', 'ip' ) );
		$safe = function ( $v ) {
			$v = (string) $v;
			return ( '' !== $v && in_array( $v[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) ? "'" . $v : $v;
		};
		foreach ( $rows as $r ) {
			$u = $r->user_id ? get_userdata( $r->user_id ) : false;
			list( $text ) = MF_Activity::detail( $r );
			fputcsv( $out, array( $r->created_at, $r->user_id, $safe( $u ? $u->display_name : 'Guest' ), $safe( $u ? $u->user_email : '' ), $r->action, $safe( $text ), $r->ip ) );
		}
		fclose( $out ); // phpcs:ignore
		exit;
	}

	public static function clear_all() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'movieflix' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'mf_activity_clear' );
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . MF_Activity::table() ); // phpcs:ignore
		wp_safe_redirect( admin_url( 'admin.php?page=movieflix-activity&cleared=1' ) );
		exit;
	}

	/** WordPress dashboard widget. */
	public static function widget() {
		if ( current_user_can( 'manage_options' ) ) {
			wp_add_dashboard_widget( 'mf_activity_widget', 'MovieFlix: Recent Activity', array( __CLASS__, 'widget_body' ) );
		}
	}

	public static function widget_body() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . MF_Activity::table() . ' ORDER BY id DESC LIMIT 8' ); // phpcs:ignore
		if ( ! $rows ) {
			echo '<p>' . esc_html__( 'No activity yet.', 'movieflix' ) . '</p>';
		} else {
			$acts = MF_Activity::actions();
			echo '<ul>';
			foreach ( $rows as $r ) {
				$a = isset( $acts[ $r->action ] ) ? $acts[ $r->action ] : array( $r->action, '•' );
				$u = $r->user_id ? get_userdata( $r->user_id ) : false;
				list( $text ) = MF_Activity::detail( $r );
				echo '<li>' . esc_html( $a[1] . ' ' . ( $u ? $u->display_name : __( 'Guest', 'movieflix' ) ) . ' — ' . $a[0] . ( $text ? ': ' . $text : '' ) ) . ' <small>(' . esc_html( human_time_diff( strtotime( $r->created_at . ' UTC' ), time() ) ) . ')</small></li>';
			}
			echo '</ul>';
		}
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=movieflix-activity' ) ) . '">' . esc_html__( 'Open full activity monitor →', 'movieflix' ) . '</a></p>';
	}

	public static function user_col( $cols ) {
		$cols['mf_seen'] = 'MovieFlix';
		return $cols;
	}

	public static function user_col_val( $val, $col, $uid ) {
		if ( 'mf_seen' !== $col ) {
			return $val;
		}
		$seen = (int) get_user_meta( $uid, 'mf_last_seen', true );
		$g    = get_user_meta( $uid, 'mf_google_sub', true ) ? ' <span title="Google">🅖</span>' : '';
		$txt  = $seen ? ( time() - $seen < 300 ? '<b style="color:#16a34a">● ' . esc_html__( 'Online', 'movieflix' ) . '</b>' : esc_html( sprintf( __( 'Seen %s ago', 'movieflix' ), human_time_diff( $seen, time() ) ) ) ) : '—';
		return $txt . $g . '<br><a href="' . esc_url( admin_url( 'admin.php?page=movieflix-activity&user=' . (int) $uid ) ) . '">' . esc_html__( 'Activity', 'movieflix' ) . '</a>';
	}
}
