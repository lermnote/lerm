<?php // phpcs:disable WordPress.Files.FileName
/**
 * Storage backend backed by WordPress options (get_option / update_option).
 *
 * This is the default backend used by the options framework for theme/plugin
 * settings pages.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\OptionsFramework\Backends;

use Lerm\OptionsFramework\Contracts\StorageBackend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionBackend implements StorageBackend {

	private string $option_name;

	public function __construct( string $option_name ) {
		$this->option_name = sanitize_key( $option_name );
	}

	public function read(): array {
		$data = get_option( $this->option_name, array() );
		return is_array( $data ) ? $data : array();
	}

	public function write( array $data ): bool {
		$result = update_option( $this->option_name, $data );

		if ( false === $result ) {
			// update_option returns false both on DB error AND when the value
			// hasn't changed. Distinguish the two by re-reading.
			$stored = get_option( $this->option_name );
			return is_array( $stored ) && $stored === $data;
		}

		return $result;
	}

	public function key(): string {
		return $this->option_name;
	}
}
