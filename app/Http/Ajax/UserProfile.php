<?php // phpcs:disable WordPress.Files.FileName
/**
 * user center handle
 */
namespace Lerm\Http\Ajax;

use Lerm\Traits\Singleton;

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
	check_ajax_referer( 'profile_nonce', 'security', true );

	$current_user = wp_get_current_user();
	if ( ! $current_user || 0 === (int) $current_user->ID ) {
		self::error( __( 'You must be logged in.', 'lerm' ) );
	}

	if ( ! current_user_can( 'edit_user', $current_user->ID ) ) {
		self::error( __( 'You are not allowed to edit this profile.', 'lerm' ) );
	}

	self::$user_id = (int) $current_user->ID;

	// Password update.
	if ( ! empty( $_POST['pass1'] ) || ! empty( $_POST['pass2'] ) ) {
		$pass1 = wp_unslash( $_POST['pass1'] ?? '' );
		$pass2 = wp_unslash( $_POST['pass2'] ?? '' );

		if ( $pass1 !== $pass2 ) {
			self::error( __( 'The passwords you entered do not match.', 'lerm' ) );
		}

		if ( strlen( $pass1 ) < 8 ) {
			self::error( __( 'Password must be at least 8 characters long.', 'lerm' ) );
		}

		wp_update_user(
			array(
				'ID'        => $current_user->ID,
				'user_pass' => $pass1,
			)
		);
	}

	if ( isset( $_POST['description'] ) ) {
		update_user_meta(
			$current_user->ID,
			'description',
			sanitize_textarea_field( wp_unslash( $_POST['description'] ) )
		);
	}

	$fields_map = array(
		'user_url'   => 'user_url',
		'email'      => 'user_email',
		'first-name' => 'first_name',
		'last-name'  => 'last_name',
		'nickname'   => 'nickname',
	);

	$update_data = array(
		'ID' => $current_user->ID,
	);

	foreach ( $fields_map as $input_key => $user_field ) {
		if ( ! isset( $_POST[ $input_key ] ) ) {
			continue;
		}

		$raw = wp_unslash( $_POST[ $input_key ] );

		switch ( $input_key ) {
			case 'email':
				$value = sanitize_email( $raw );
				if ( empty( $value ) || ! is_email( $value ) ) {
					self::error( __( 'Invalid email address.', 'lerm' ) );
				}

				$email_owner = email_exists( $value );
				if ( $email_owner && (int) $email_owner !== (int) $current_user->ID ) {
					self::error( __( 'This email is already used by another user.', 'lerm' ) );
				}
				break;

			case 'user_url':
				$value = esc_url_raw( $raw );
				break;

			default:
				$value = sanitize_text_field( $raw );
				break;
		}

		$update_data[ $user_field ] = $value;
	}

	if ( count( $update_data ) > 1 ) {
		$result = wp_update_user( $update_data );
		if ( is_wp_error( $result ) ) {
			self::error( $result->get_error_message() );
		}
	}

	if ( isset( $_FILES['avatar'] ) && ! empty( $_FILES['avatar']['name'] ) ) {
		$avatar_id = self::handle_avatar_upload( $_FILES['avatar'], $current_user->ID );

		if ( $avatar_id ) {
			update_user_meta( $current_user->ID, 'avatar_id', (int) $avatar_id );

			$avatar_url = wp_get_attachment_image_url( $avatar_id, 'thumbnail' );

			self::success(
				array(
					'message'   => __( 'Profile updated successfully.', 'lerm' ),
					'avatarUrl' => $avatar_url ? esc_url_raw( $avatar_url ) : '',
				)
			);
		}
	}

	do_action( 'edit_user_profile_update', $current_user->ID );

	self::success(
		array(
			'message' => __( 'Profile updated successfully.', 'lerm' ),
		)
	);
}

public static function lerm_get_avatar( $avatar, $id_or_email, $args ) {
	static $cached_user_meta = array();

	$user = false;

	if ( is_numeric( $id_or_email ) ) {
		$user = get_user_by( 'id', (int) $id_or_email );
	} elseif ( $id_or_email instanceof \WP_User ) {
		$user = $id_or_email;
	} elseif ( $id_or_email instanceof \WP_Comment ) {
		$user = get_user_by( 'id', (int) $id_or_email->user_id );
	}

	if ( $user ) {
		if ( ! isset( $cached_user_meta[ $user->ID ] ) ) {
			$cached_user_meta[ $user->ID ] = (int) get_user_meta( $user->ID, 'avatar_id', true );
		}

		$avatar_id = $cached_user_meta[ $user->ID ];
		if ( $avatar_id ) {
			$local_avatar = wp_get_attachment_image_url( $avatar_id, 'thumbnail' );
			if ( $local_avatar ) {
				$avatar = sprintf(
					'<img alt="%1$s" src="%2$s" class="avatar avatar-%3$d photo" height="%4$d" width="%5$d" />',
					esc_attr( $args['alt'] ?? '' ),
					esc_url( $local_avatar ),
					(int) ( $args['size'] ?? 96 ),
					(int) ( $args['height'] ?? 96 ),
					(int) ( $args['width'] ?? 96 )
				);
			}
		}
	}

	return $avatar;
}

private static function handle_avatar_upload( $file, $user_id ) {
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$allowed_file_types = array( 'image/jpg', 'image/jpeg', 'image/png', 'image/gif' );
	$file_type          = wp_check_filetype( $file['name'] );

	if ( ! in_array( $file_type['type'], $allowed_file_types, true ) ) {
		self::error( __( 'Invalid file type. Please upload an image file.', 'lerm' ) );
	}

	$old_avatar_id = (int) get_user_meta( $user_id, 'avatar_id', true );

	$upload_overrides = array( 'test_form' => false );
	$uploaded_file    = wp_handle_upload( $file, $upload_overrides );

	if ( ! $uploaded_file || isset( $uploaded_file['error'] ) ) {
		self::error( $uploaded_file['error'] ?? __( 'Upload failed.', 'lerm' ) );
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $file_type['type'],
			'post_title'     => sanitize_file_name( wp_basename( $uploaded_file['file'] ) ),
			'post_status'    => 'inherit',
		),
		$uploaded_file['file']
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		self::error( __( 'Failed to create attachment.', 'lerm' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';

	$editor = wp_get_image_editor( $uploaded_file['file'] );
	if ( ! is_wp_error( $editor ) ) {
		$editor->resize( 128, 128, true );
		$editor->save( $uploaded_file['file'] );
	}

	$metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded_file['file'] );
	if ( ! empty( $metadata ) ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	if ( $old_avatar_id > 0 ) {
		wp_delete_attachment( $old_avatar_id, true );
	}

	return (int) $attachment_id;
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



