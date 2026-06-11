<?php // phpcs:disable WordPress.Files.FileName
/**
 * Storage backend backed by WordPress user meta.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UserMetaBackend extends MetaBackend {

	public function __construct( int $user_id, string $meta_key ) {
		parent::__construct( $user_id, $meta_key, 'user' );
	}
}
