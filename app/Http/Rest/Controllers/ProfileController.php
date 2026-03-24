<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Lerm\Http\Rest\Middleware;

/**
 * 个人资料接口
 *
 * GET  /lerm/v1/profile       — 读取当前用户资料
 * POST /lerm/v1/profile       — 更新资料（支持头像上传，multipart/form-data）
 *
 * POST 请求体字段（均可选，只发送要更改的）：
 *   email       string  邮箱
 *   first_name  string  名
 *   last_name   string  姓
 *   nickname    string  昵称
 *   user_url    string  个人网址
 *   description string  个人简介
 *   pass1       string  新密码（pass1 和 pass2 都填才更新）
 *   pass2       string  确认新密码
 *   avatar      file    头像图片（image/jpeg|png|gif|webp）
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class ProfileController {

	private const ALLOWED_MIME = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
	private const AVATAR_SIZE  = 128; // 头像裁剪尺寸（px）

	// -------------------------------------------------------------------------
	// GET — 读取资料
	// -------------------------------------------------------------------------

	public static function get( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$check = Middleware::require_login();
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$user = wp_get_current_user();

		return new WP_REST_Response( self::format_user( $user ), 200 );
	}

	// -------------------------------------------------------------------------
	// POST — 更新资料
	// -------------------------------------------------------------------------

	public static function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$check = Middleware::require_login();
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$user = wp_get_current_user();

		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return new WP_Error( 'forbidden', __( 'You are not allowed to edit this profile.', 'lerm' ), array( 'status' => 403 ) );
		}

		// --- 密码更新 ---
		$pass1 = (string) ( $request->get_param( 'pass1' ) ?? '' );
		$pass2 = (string) ( $request->get_param( 'pass2' ) ?? '' );

		if ( '' !== $pass1 || '' !== $pass2 ) {
			if ( $pass1 !== $pass2 ) {
				return new WP_Error( 'password_mismatch', __( 'The passwords you entered do not match.', 'lerm' ), array( 'status' => 400 ) );
			}
			if ( strlen( $pass1 ) < 8 ) {
				return new WP_Error( 'password_too_short', __( 'Password must be at least 8 characters long.', 'lerm' ), array( 'status' => 400 ) );
			}
			wp_update_user(
				array(
					'ID'        => $user->ID,
					'user_pass' => $pass1,
				)
			);
		}

		// --- 文本字段更新 ---
		$update_data = array( 'ID' => $user->ID );

		// 字段映射：请求参数名 → wp_update_user 字段名
		$field_map = array(
			'nickname'    => 'nickname',
			'first_name'  => 'first_name',
			'last_name'   => 'last_name',
			'user_url'    => 'user_url',
			'description' => 'description',
		);

		foreach ( $field_map as $param => $wp_field ) {
			$val = $request->get_param( $param );
			if ( null === $val ) {
				continue;
			}
			$update_data[ $wp_field ] = 'description' === $param
				? sanitize_textarea_field( (string) $val )
				: sanitize_text_field( (string) $val );
		}

		// 邮箱单独处理（需要唯一性检查）
		$email = $request->get_param( 'email' );
		if ( null !== $email ) {
			$email = sanitize_email( (string) $email );
			if ( ! is_email( $email ) ) {
				return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'lerm' ), array( 'status' => 400 ) );
			}
			$owner = email_exists( $email );
			if ( $owner && (int) $owner !== $user->ID ) {
				return new WP_Error( 'email_exists', __( 'This email is already used by another account.', 'lerm' ), array( 'status' => 409 ) );
			}
			$update_data['user_email'] = $email;
		}

		// 网址
		$user_url = $request->get_param( 'user_url' );
		if ( null !== $user_url ) {
			$update_data['user_url'] = esc_url_raw( (string) $user_url );
		}

		if ( count( $update_data ) > 1 ) {
			$result = wp_update_user( $update_data );
			if ( is_wp_error( $result ) ) {
				return new WP_Error( 'update_failed', $result->get_error_message(), array( 'status' => 500 ) );
			}
		}

		// --- 头像上传（multipart/form-data） ---
		$avatar_url = null;
		$files      = $request->get_file_params();

		if ( ! empty( $files['avatar']['name'] ) ) {
			$result = self::handle_avatar_upload( $files['avatar'], $user->ID );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$avatar_url = $result;
		}

		do_action( 'lerm_profile_updated', $user->ID );

		$response = array(
			'message' => __( 'Profile updated successfully.', 'lerm' ),
			'user'    => self::format_user( get_userdata( $user->ID ) ),
		);
		if ( $avatar_url ) {
			$response['avatar_url'] = $avatar_url;
		}

		return new WP_REST_Response( $response, 200 );
	}

	// -------------------------------------------------------------------------
	// 私有方法
	// -------------------------------------------------------------------------

	/**
	 * 上传并裁剪头像，返回 attachment URL 或 WP_Error
	 */
	private static function handle_avatar_upload( array $file, int $user_id ): string|WP_Error {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$file_type = wp_check_filetype( $file['name'] );
		if ( ! in_array( $file_type['type'], self::ALLOWED_MIME, true ) ) {
			return new WP_Error( 'invalid_file_type', __( 'Only JPEG, PNG, GIF and WebP images are allowed.', 'lerm' ), array( 'status' => 400 ) );
		}

		$uploaded = wp_handle_upload( $file, array( 'test_form' => false ) );
		if ( ! $uploaded || isset( $uploaded['error'] ) ) {
			return new WP_Error( 'upload_failed', $uploaded['error'] ?? __( 'Upload failed.', 'lerm' ), array( 'status' => 500 ) );
		}

		// 裁剪为正方形
		$editor = wp_get_image_editor( $uploaded['file'] );
		if ( ! is_wp_error( $editor ) ) {
			$editor->resize( self::AVATAR_SIZE, self::AVATAR_SIZE, true );
			$editor->save( $uploaded['file'] );
		}

		// 创建 attachment
		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $file_type['type'],
				'post_title'     => sanitize_file_name( wp_basename( $uploaded['file'] ) ),
				'post_status'    => 'inherit',
			),
			$uploaded['file'],
			0,
			true
		);

		if ( is_wp_error( $attachment_id ) ) {
			return new WP_Error( 'attachment_failed', $attachment_id->get_error_message(), array( 'status' => 500 ) );
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );
		if ( $metadata ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		// 删除旧头像
		$old_id = (int) get_user_meta( $user_id, 'avatar_id', true );
		if ( $old_id > 0 ) {
			wp_delete_attachment( $old_id, true );
		}

		update_user_meta( $user_id, 'avatar_id', $attachment_id );

		return (string) wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
	}

	/**
	 * 格式化用户数据（用于响应）
	 */
	private static function format_user( \WP_User $user ): array {
		$avatar_id  = (int) get_user_meta( $user->ID, 'avatar_id', true );
		$avatar_url = $avatar_id
			? (string) wp_get_attachment_image_url( $avatar_id, 'thumbnail' )
			: get_avatar_url( $user->ID );

		return array(
			'id'          => $user->ID,
			'username'    => $user->user_login,
			'email'       => $user->user_email,
			'nickname'    => $user->nickname,
			'first_name'  => $user->first_name,
			'last_name'   => $user->last_name,
			'user_url'    => $user->user_url,
			'description' => $user->description,
			'avatar_url'  => esc_url( $avatar_url ),
		);
	}
}
