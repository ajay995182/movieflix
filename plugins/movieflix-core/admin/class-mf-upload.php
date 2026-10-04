<?php
/**
 * Chunked media uploader for large video files.
 *
 * Handles chunked uploads via admin-ajax.php so multi-GB movie files do not
 * hit PHP max_execution_time or upload_max_filesize limits. Each chunk is
 * written to a temporary file and stitched together on the final chunk.
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MF_Upload {

	private static $chunk_dir;
	private static $allowed = array( 'mp4' => 'video/mp4', 'webm' => 'video/webm', 'm4v' => 'video/mp4', 'mov' => 'video/quicktime', 'mkv' => 'video/x-matroska', 'm3u8' => 'application/vnd.apple.mpegurl', 'vtt' => 'text/vtt', 'srt' => 'text/plain', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' );

	public static function init() {
		self::$chunk_dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'mf-chunks';
		add_action( 'wp_ajax_mf_upload_chunk', array( __CLASS__, 'upload_chunk' ) );
		add_action( 'wp_ajax_mf_remove_demo', array( __CLASS__, 'remove_demo' ) );
		add_action( 'wp_ajax_mf_import_demo_ajax', array( __CLASS__, 'import_demo_ajax' ) );
		add_action( 'admin_post_mf_remove_demo', array( __CLASS__, 'remove_demo_post' ) );
	}

	/** Validate the current user can upload media. */
	private static function can() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to upload files.', 'movieflix' ) ), 403 );
		}
	}

	/** Get a safe chunk directory path for an upload id. */
	private static function chunk_path( $id ) {
		$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $id );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid upload id.', 'movieflix' ) ), 400 );
		}
		return self::$chunk_dir . '/' . $id;
	}

	/**
	 * Receive one chunk, append it, and on the final chunk move the assembled
	 * file into the WordPress Media Library and return its attachment URL.
	 */
	public static function upload_chunk() {
		self::can();
		check_ajax_referer( 'mf_upload', 'nonce' );

		$id      = isset( $_POST['upload_id'] ) ? sanitize_text_field( wp_unslash( $_POST['upload_id'] ) ) : '';
		$index   = isset( $_POST['chunk_index'] ) ? absint( $_POST['chunk_index'] ) : 0;
		$total   = isset( $_POST['total_chunks'] ) ? absint( $_POST['total_chunks'] ) : 0;
		$fname   = isset( $_POST['file_name'] ) ? sanitize_file_name( wp_unslash( $_POST['file_name'] ) ) : '';
		$is_last = ! empty( $_POST['is_last'] );

		if ( ! $id || ! $total || ! $fname ) {
			wp_send_json_error( array( 'message' => __( 'Missing upload parameters.', 'movieflix' ) ), 400 );
		}

		$ext = strtolower( pathinfo( $fname, PATHINFO_EXTENSION ) );
		if ( ! isset( self::$allowed[ $ext ] ) ) {
			wp_send_json_error( array( 'message' => sprintf( __( 'File type "%s" is not allowed.', 'movieflix' ), esc_html( $ext ) ) ), 400 );
		}

		if ( empty( $_FILES['chunk'] ) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( array( 'message' => __( 'No chunk data received.', 'movieflix' ) ), 400 );
		}

		$dir = self::chunk_path( $id );
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		// Protect chunk directory.
		file_put_contents( $dir . '/index.php', "<?php // Silence is golden." );

		$chunk_file = $dir . '/part-' . $index;
		if ( ! move_uploaded_file( $_FILES['chunk']['tmp_name'], $chunk_file ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not save chunk.', 'movieflix' ) ), 500 );
		}

		if ( ! $is_last ) {
			wp_send_json_success( array( 'received' => $index, 'total' => $total, 'done' => false ) );
		}

		// Last chunk — assemble.
		$final_name = wp_unique_filename( $dir, $fname );
		$final_path = $dir . '/' . $final_name;
		$out = fopen( $final_path, 'wb' );
		if ( ! $out ) {
			wp_send_json_error( array( 'message' => __( 'Could not create output file.', 'movieflix' ) ), 500 );
		}
		for ( $i = 0; $i < $total; $i++ ) {
			$part = $dir . '/part-' . $i;
			if ( ! file_exists( $part ) ) {
				fclose( $out );
				wp_send_json_error( array( 'message' => sprintf( __( 'Missing chunk %d.', 'movieflix' ), $i ) ), 500 );
			}
			$in = fopen( $part, 'rb' );
			while ( ! feof( $in ) ) {
				fwrite( $out, fread( $in, 1024 * 1024 ) );
			}
			fclose( $in );
			@unlink( $part );
		}
		fclose( $out );

		// Insert into the Media Library.
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$file_array = array(
			'name'     => $final_name,
			'tmp_name' => $final_path,
		);
		$mime       = self::$allowed[ $ext ];
		$attach_id  = media_handle_sideload( $file_array, 0, $fname, array( 'test_form' => false ) );

		if ( is_wp_error( $attach_id ) ) {
			wp_send_json_error( array( 'message' => $attach_id->get_error_message() ), 500 );
		}

		$url = wp_get_attachment_url( $attach_id );

		// Clean up chunk directory.
		self::rrmdir( $dir );

		wp_send_json_success( array(
			'received'    => $index,
			'total'       => $total,
			'done'        => true,
			'attach_id'   => $attach_id,
			'url'         => $url,
			'file_name'   => $final_name,
			'mime'        => $mime,
			'message'     => __( 'Upload complete.', 'movieflix' ),
		) );
	}

	/** Recursively remove a directory. */
	private static function rrmdir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		foreach ( glob( $dir . '/*' ) as $f ) {
			if ( is_dir( $f ) ) {
				self::rrmdir( $f );
			} else {
				@unlink( $f );
			}
		}
		@rmdir( $dir );
	}

	/** AJAX: import demo content. */
	public static function import_demo_ajax() {
		check_ajax_referer( 'mf_upload', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'movieflix' ) ), 403 );
		}
		$msg = MF_Demo::import();
		wp_send_json_success( array( 'message' => $msg ) );
	}

	/** AJAX: remove all demo content. */
	public static function remove_demo() {
		check_ajax_referer( 'mf_upload', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'movieflix' ) ), 403 );
		}
		$msg = MF_Demo::remove();
		wp_send_json_success( array( 'message' => $msg ) );
	}

	/** admin-post handler: remove all demo content. */
	public static function remove_demo_post() {
		check_admin_referer( 'mf_remove_demo' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'movieflix' ) );
		}
		$res = MF_Demo::remove();
		wp_safe_redirect( add_query_arg( 'mf_done', rawurlencode( $res ), admin_url( 'admin.php?page=movieflix' ) ) );
		exit;
	}
}