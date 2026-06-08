<?php // phpcs:disable WordPress.Files.FileName
/**
 * Storage backend backed by WordPress term meta.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TermMetaBackend extends MetaBackend {

	public function __construct( int $term_id, string $meta_key ) {
		parent::__construct( $term_id, $meta_key, 'term' );
	}
}
