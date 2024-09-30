<?php
/**
 * user center handle
 */

namespace Lerm\Inc\Ajax;

class UserProfile {

	private static $action;

	private static $user_id;

	private static $is_login;

	private static $user_data = array();

	private static $args = array(
		'username'    => '',
		'description' => '',
		'email'       => '',
		'gender'      => '',
		'website'     => '',
		'avatar'      => '',
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_user_', wp_parse_args( $params, self::$args ) );
		// 检查用户是否已登录
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			self::$is_login = true;
			self::$user_id  = $user_id;
		} else {
			self::$is_login = false;
		}
		if ( isset( $_POST['update_profile'] ) ) {
			self::update_user_meta_data();}
	}

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	public static function get_user_meta_data() {
		if ( ! self::$is_login ) {
			return false;
		}

		foreach ( self::$args as $key => $arg ) {
			self::$user_data[ $key ] = get_user_meta( self::$user_id, $key, true );
		}
		// wp_send_json_success( self::$user_data );
		return self::$user_data;
	}

	public static function update_user_meta_data( $user_data = array() ) {
		if ( ! self::$is_login ) {
			return false;
		}
		// 检查安全性标记
		// if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'ajax_nonce' ) ) {
		// wp_send_json_error( 'Invalid security token' );
		// }
		$user_data = wp_unslash( $_POST );

		// 过滤和清理用户输入的数据
		foreach ( $user_data as $key => $data ) {
			$user_data[ $key ] = sanitize_user( $data );
		}
		var_dump( $user_data );
		// 更新用户元数据
		foreach ( $user_data as $key => $data ) {
			if ( 'avatar' === $key ) {
				$avatar_id = self::upload_avatar( $data );
				var_dump( $avatar_id );
				if ( $avatar_id ) {
					self::$args['avatar'] = wp_get_attachment_image_src( $avatar_id, 'thumbnail' )[0];
					update_user_meta( self::$user_id, 'avatar', self::$args['avatar'] );
				} else {
					self::$args['avatar'] = get_avatar_url( self::$user_id );
				}
			} else {
				update_user_meta( self::$user_id, $key, $data );
				self::$args[ $key ] = $data;
			}
		}

		// wp_send_json_success( 'User profile updated successfully' );
	}

	public static function upload_avatar( $data ) {

		// Check if the file is an image
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$file_type = wp_check_filetype( $data['name'] );
		if ( ! in_array( $file_type['type'], array( 'image/jpg', 'image/jpeg', 'image/png', 'image/gif' ), true ) ) {
			wp_send_json_error( 'Invalid file type. Please upload an image file.' );
		}
		// Delete old avatar if exists
		$avatar_id = get_user_meta( self::$user_id, 'avatar', true );
		if ( $avatar_id ) {
			wp_delete_attachment( $avatar_id );
		}
		// Upload the file
		$upload_overrides = array( 'test_form' => false );
		$uploaded_file    = wp_handle_upload( $data, $upload_overrides );
		if ( $uploaded_file && ! isset( $uploaded_file['error'] ) ) {
			// Save the file as attachment
			$file_path     = $uploaded_file['file'];
			$file_name     = basename( $file_path );
			$attachment    = array(
				'post_mime_type' => $file_type['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $file_name ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);
			$attachment_id = wp_insert_attachment( $attachment, $file_path );
			if ( ! is_wp_error( $attachment_id ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
				wp_update_attachment_metadata( $attachment_id, $attachment_data );

				// Resize and crop the image
				$editor = wp_get_image_editor( $file_path );
				if ( ! is_wp_error( $editor ) ) {
					$editor->resize( 128, 128, true );
					$editor->crop( 128, 128 );
					$editor->save( $file_path );
				}
				// wp_send_json_success( __( 'File is valid, and was successfully uploaded.', 'lerm' ) );

				return $attachment_id;
			}
		}
		wp_send_json_error( $uploaded_file['error'] );
	}
}
