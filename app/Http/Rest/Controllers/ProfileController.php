<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use Lerm\Http\Rest\Middleware;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Profile endpoint controller.
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class ProfileController {

	private const ALLOWED_MIME = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
	private const AVATAR_SIZE  = 128;

	public static function get( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$check = Middleware::require_login();
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		return new WP_REST_Response( self::format_user( wp_get_current_user() ), 200 );
	}

	public static function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$check = Middleware::chain(
			fn() => Middleware::require_login(),
			fn() => Middleware::verify_nonce( $request, 'lerm_profile' )
		);
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$user = wp_get_current_user();

		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return new WP_Error(
				'forbidden',
				__( 'You are not allowed to edit this profile.', 'lerm' ),
				array( 'status' => 403 )
			);
		}

		$pass1 = self::get_request_param( $request, array( 'pass1', 'password' ) );
		$pass2 = self::get_request_param( $request, array( 'pass2', 'password_confirm', 'confirm_password' ) );

		if ( '' !== $pass1 || '' !== $pass2 ) {
			if ( $pass1 !== $pass2 ) {
				return new WP_Error(
					'password_mismatch',
					__( 'The passwords you entered do not match.', 'lerm' ),
					array( 'status' => 400 )
				);
			}

			if ( strlen( $pass1 ) < 8 ) {
				return new WP_Error(
					'password_too_short',
					__( 'Password must be at least 8 characters long.', 'lerm' ),
					array( 'status' => 400 )
				);
			}

			wp_update_user(
				array(
					'ID'        => $user->ID,
					'user_pass' => $pass1,
				)
			);
		}

		$update_data = array( 'ID' => $user->ID );
		$field_map   = array(
			'nickname'    => array( 'nickname' ),
			'first_name'  => array( 'first_name', 'first-name' ),
			'last_name'   => array( 'last_name', 'last-name' ),
			'description' => array( 'description' ),
		);

		foreach ( $field_map as $wp_field => $keys ) {
			$value = self::get_optional_param( $request, $keys );
			if ( null === $value ) {
				continue;
			}

			$update_data[ $wp_field ] = 'description' === $wp_field
				? sanitize_textarea_field( $value )
				: sanitize_text_field( $value );
		}

		$email = self::get_optional_param( $request, array( 'email', 'user_email' ) );
		if ( null !== $email ) {
			$email = sanitize_email( $email );
			if ( ! is_email( $email ) ) {
				return new WP_Error(
					'invalid_email',
					__( 'Invalid email address.', 'lerm' ),
					array( 'status' => 400 )
				);
			}

			$owner = email_exists( $email );
			if ( $owner && (int) $owner !== $user->ID ) {
				return new WP_Error(
					'email_exists',
					__( 'This email is already used by another account.', 'lerm' ),
					array( 'status' => 409 )
				);
			}

			$update_data['user_email'] = $email;
		}

		$user_url = self::get_optional_param( $request, array( 'user_url', 'website' ) );
		if ( null !== $user_url ) {
			$update_data['user_url'] = esc_url_raw( $user_url );
		}

		if ( count( $update_data ) > 1 ) {
			$result = wp_update_user( $update_data );
			if ( is_wp_error( $result ) ) {
				return new WP_Error(
					'update_failed',
					$result->get_error_message(),
					array( 'status' => 500 )
				);
			}
		}

		$gender = self::get_optional_param( $request, array( 'gender' ) );
		if ( null !== $gender ) {
			$gender = sanitize_key( $gender );
			if ( ! in_array( $gender, array( '', 'female', 'male', 'other' ), true ) ) {
				$gender = '';
			}

			if ( '' === $gender ) {
				delete_user_meta( $user->ID, 'gender' );
			} else {
				update_user_meta( $user->ID, 'gender', $gender );
			}
		}

		$address = self::get_optional_param( $request, array( 'address' ) );
		if ( null !== $address ) {
			$address = sanitize_text_field( $address );
			if ( '' === $address ) {
				delete_user_meta( $user->ID, 'address' );
			} else {
				update_user_meta( $user->ID, 'address', $address );
			}
		}

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
			'message'  => __( 'Profile updated successfully.', 'lerm' ),
			'user'     => self::format_user( get_userdata( $user->ID ) ),
			'redirect' => function_exists( 'lerm_get_frontend_account_page_url' ) ? lerm_get_frontend_account_page_url() : home_url( '/' ),
		);

		if ( $avatar_url ) {
			$response['avatar_url'] = $avatar_url;
		}

		return new WP_REST_Response( $response, 200 );
	}

	private static function handle_avatar_upload( array $file, int $user_id ): string|WP_Error {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$file_type = wp_check_filetype( $file['name'] );
		if ( ! in_array( $file_type['type'], self::ALLOWED_MIME, true ) ) {
			return new WP_Error(
				'invalid_file_type',
				__( 'Only JPEG, PNG, GIF and WebP images are allowed.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		$uploaded = wp_handle_upload( $file, array( 'test_form' => false ) );
		if ( ! $uploaded || isset( $uploaded['error'] ) ) {
			return new WP_Error(
				'upload_failed',
				$uploaded['error'] ?? __( 'Upload failed.', 'lerm' ),
				array( 'status' => 500 )
			);
		}

		$editor = wp_get_image_editor( $uploaded['file'] );
		if ( ! is_wp_error( $editor ) ) {
			$editor->resize( self::AVATAR_SIZE, self::AVATAR_SIZE, true );
			$editor->save( $uploaded['file'] );
		}

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
			return new WP_Error(
				'attachment_failed',
				$attachment_id->get_error_message(),
				array( 'status' => 500 )
			);
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );
		if ( $metadata ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		$old_id = (int) get_user_meta( $user_id, 'avatar_id', true );
		if ( $old_id > 0 ) {
			wp_delete_attachment( $old_id, true );
		}

		update_user_meta( $user_id, 'avatar_id', $attachment_id );

		return (string) wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
	}

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
			'gender'      => (string) get_user_meta( $user->ID, 'gender', true ),
			'address'     => (string) get_user_meta( $user->ID, 'address', true ),
			'avatar_url'  => esc_url( $avatar_url ),
		);
	}

	private static function get_request_param( WP_REST_Request $request, array $keys ): string {
		foreach ( $keys as $key ) {
			$value = $request->get_param( $key );
			if ( null !== $value ) {
				return is_scalar( $value ) ? trim( (string) $value ) : '';
			}
		}

		return '';
	}

	private static function get_optional_param( WP_REST_Request $request, array $keys ): ?string {
		foreach ( $keys as $key ) {
			$value = $request->get_param( $key );
			if ( null !== $value ) {
				return is_scalar( $value ) ? trim( (string) $value ) : '';
			}
		}

		return null;
	}
}
