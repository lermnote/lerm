<?php // phpcs:disable WordPress.Files.FileName
/**
 * Storage backend backed by WordPress comment meta.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CommentMetaBackend extends MetaBackend {

	public function __construct( int $comment_id, string $meta_key ) {
		parent::__construct( $comment_id, $meta_key, 'comment' );
	}
}
