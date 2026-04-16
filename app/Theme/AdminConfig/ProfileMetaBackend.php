<?php // phpcs:disable WordPress.Files.FileName
/**
 * Theme-specific compatibility backend for distributed profile user_meta keys.
 *
 * Keeps existing avatar/gender/address user_meta storage intact while the
 * admin configuration runtime takes over the profile editing UI.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\Theme\AdminConfig;

use Lerm\AdminConfig\Framework\Contracts\StorageBackend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProfileMetaBackend implements StorageBackend {

	public function __construct(
		private int $user_id
	) {
	}

	public function read(): array {
		return array(
			'avatar_id' => (int) get_user_meta( $this->user_id, 'avatar_id', true ),
			'gender'    => (string) get_user_meta( $this->user_id, 'gender', true ),
			'address'   => (string) get_user_meta( $this->user_id, 'address', true ),
		);
	}

	public function write( array $data ): bool {
		$avatar_id = absint( $data['avatar_id'] ?? 0 );
		$gender    = is_scalar( $data['gender'] ?? null ) ? (string) $data['gender'] : '';
		$address   = is_scalar( $data['address'] ?? null ) ? (string) $data['address'] : '';

		if ( $avatar_id > 0 ) {
			update_user_meta( $this->user_id, 'avatar_id', $avatar_id );
		} else {
			delete_user_meta( $this->user_id, 'avatar_id' );
		}

		if ( '' !== $gender ) {
			update_user_meta( $this->user_id, 'gender', $gender );
		} else {
			delete_user_meta( $this->user_id, 'gender' );
		}

		if ( '' !== $address ) {
			update_user_meta( $this->user_id, 'address', $address );
		} else {
			delete_user_meta( $this->user_id, 'address' );
		}

		return true;
	}

	public function key(): string {
		return 'lerm_profile_meta_' . $this->user_id;
	}

	public function delete(): bool {
		delete_user_meta( $this->user_id, 'avatar_id' );
		delete_user_meta( $this->user_id, 'gender' );
		delete_user_meta( $this->user_id, 'address' );

		return true;
	}
}
