<?php // phpcs:disable WordPress.Files.FileName
/**
 * Storage backend backed by WordPress post meta.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostMetaBackend extends MetaBackend {

	public function __construct( int $post_id, string $meta_key ) {
		parent::__construct( $post_id, $meta_key, 'post' );
	}
}
