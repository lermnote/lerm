<?php // phpcs:disable WordPress.Files.FileName
/**
 * user center handle
 */
namespace Lerm\Inc\Ajax;

use Lerm\Inc\Traits\Singleton;

final class UserProfile extends BaseAjax {

	use singleton;

	protected const AJAX_ACTION = 'update_profile';
	protected const PUBLIC      = false;

	public static $args = array(
		'profile_enable'  => true,
		'update_redirect' => 'user-frofile',
	);

	public function __construct( $params = array() ) {
		parent::__construct( apply_filters( 'lerm_user_args', wp_parse_args( $params, self::$args ) ) );
		self::hooks();
	}

	public static function hooks() {
		add_filter( 'pre_get_avatar', array( __CLASS__, 'lerm_get_avatar' ), 10, 3 );
		add_filter( 'lerm_l10n_data', array( __CLASS__, 'ajax_l10n_data' ) );
	}

	private static $user_id;

	private static $is_login;

	public static function ajax_handle() {
		// Verify the nonce
		check_ajax_referer( 'profile_nonce', 'security', true );

		$current_user = wp_get_current_user();
		if ( ! current_user_can( 'edit_user', $current_user->ID ) ) {
			self::error( __( 'You are not allowed to edit this profile.', 'lerm' ) );
		}

		// Password update
		if ( ! empty( $_POST['pass1'] ) && ! empty( $_POST['pass2'] ) ) {
			if ( $_POST['pass1'] === $_POST['pass2'] ) {
				wp_update_user(
					array(
						'ID'        => $current_user->ID,
						'user_pass' => esc_attr( $_POST['pass1'] ),
					)
				);
			} else {
				self::error( __( 'The passwords you entered do not match.', 'lerm' ) );
			}
		}

		if ( ! empty( $_POST['description'] ) ) {
			update_user_meta( $current_user->ID, 'description', sanitize_textarea_field( $_POST['description'] ) );
		}

		// Update user fields
		$fields_to_update = array(
			'user_url',
			'email',
			'first-name' => 'first_name',
			'last-name'  => 'last_name',
			'nickname',
		);
		foreach ( $fields_to_update as $key => $meta_key ) {
			if ( is_int( $key ) ) {
				$key = $meta_key; // For fields with string keys
			}
			if ( ! empty( $_POST[ $key ] ) ) {
				$value = is_string( $meta_key ) ? sanitize_text_field( $_POST[ $key ] ) : sanitize_email( $_POST[ $key ] );
				if ( 'email' === $key && ( email_exists( $value ) && email_exists( $value ) !== $current_user->ID ) ) {
					self::error( __( 'This email is already used by another user.', 'lerm' ) );
				} else {
					wp_update_user(
						array(
							'ID'      => $current_user->ID,
							$meta_key => $value,
						)
					);
				}
			}
		}

		// Update avatar
		if ( isset( $_FILES['avatar'] ) && ! empty( $_FILES['avatar']['name'] ) ) {
			$avatar_id = self::handle_avatar_upload( $_FILES['avatar'] );

			if ( $avatar_id ) {
				$avatar_url = wp_get_attachment_image_src( $avatar_id, 'thumbnail' )[0];
				update_user_meta( $current_user->ID, 'avatar', $avatar_url );
				self::success(
					array(
						'message'   => __( 'Profile updated successfully.', 'lerm' ),
						'avatarUrl' => $avatar_url, // 将新的头像 URL 发送给前端
					)
				);
				update_user_meta( $current_user->ID, 'avatar', wp_get_attachment_image_src( $avatar_id, 'thumbnail' )[0] );
			}
		}
		self::success(
			array(
				'message' => __( 'Profile updated successfully.', 'lerm' ),
				// 'redirect' => wp_safe_redirect( get_permalink() . '?updated=true' ),
			)
		);
		do_action( 'edit_user_profile_update', $current_user->ID );
	}

	public static function lerm_get_avatar( $avatar, $id_or_email, $args ) {
		static $cached_user_meta = array();
		// Retrieve the user by ID, email or other input
		$user = get_user_by( 'id', $id_or_email );

		if ( $user ) {
			if ( ! isset( $cached_user_meta[ $user->ID ] ) ) {
				$cached_user_meta[ $user->ID ] = get_user_meta( $user->ID, 'avatar', true );
			}
			$local_avatar = $cached_user_meta[ $user->ID ];
			if ( $local_avatar ) {
				$avatar = "<img alt='{$args['alt']}' src='{$local_avatar }' class='avatar avatar-{$args['size']} photo' height='{$args['height']}' width='{$args['width']}'/>";
			}
		}
		return $avatar;
	}

	private static function handle_avatar_upload( $file ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$allowed_file_types = array( 'image/jpg', 'image/jpeg', 'image/png', 'image/gif' );
		$file_type          = wp_check_filetype( $file['name'] );
		if ( ! in_array( $file_type['type'], $allowed_file_types, true ) ) {
			wp_send_json_error( 'Invalid file type. Please upload an image file.' );
			return;
		}
		// Delete old avatar if exists
		$avatar_id = get_user_meta( self::$user_id, 'avatar', true );
		if ( $avatar_id ) {
			wp_delete_attachment( $avatar_id, true );
		}

		$upload_overrides = array( 'test_form' => false );
		$uploaded_file    = wp_handle_upload( $file, $upload_overrides );
		if ( $uploaded_file && ! isset( $uploaded_file['error'] ) ) {
			$attachment_id = wp_insert_attachment(
				array(
					'post_mime_type' => $file_type['type'],
					'post_title'     => sanitize_file_name( $uploaded_file['name'] ),
					'post_status'    => 'inherit',
				),
				$uploaded_file['file']
			);

			if ( ! is_wp_error( $attachment_id ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				wp_generate_attachment_metadata( $attachment_id, $uploaded_file['file'] );
				$editor = wp_get_image_editor( $uploaded_file['file'] );
				if ( ! is_wp_error( $editor ) ) {
					$editor->resize( 128, 128, true );
					$editor->save( $uploaded_file['file'] );
				}
				return $attachment_id;
			}
		}

		wp_send_json_error( $uploaded_file['error'] ?? 'Unknown error.' );
	}
	/**
	 * Generate AJAX localization data.
	 *
	 * @param array $l10n Existing localization data.
	 * @return array Localized data for AJAX requests.
	 */
	public static function ajax_l10n_data( $l10n ) {

		$data = array(
			'profile_nonce'  => wp_create_nonce( 'profile_nonce' ),
			'profile_action' => self::AJAX_ACTION,
			'redirect'       => home_url( self::$args['update_redirect'] ),
		);
		$data = wp_parse_args( $data, $l10n );
		return $data;
	}
}
