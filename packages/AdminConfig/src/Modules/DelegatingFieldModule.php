<?php
/**
 * Generic delegating field module.
 *
 * Replaces the seven near-identical XxxFieldsModule classes with a single
 * configurable implementation backed by a static definitions class.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Contracts\FieldModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DelegatingFieldModule implements FieldModule {

	private string $id;
	private string $definitions_class;
	private bool $enabled_by_default;

	/**
	 * @param string $id                Module identifier (e.g. 'core', 'design').
	 * @param string $definitions_class Fully-qualified class with a static definitions() method.
	 * @param bool   $enabled_by_default Whether the module is active without explicit opt-in.
	 */
	public function __construct( string $id, string $definitions_class, bool $enabled_by_default = false ) {
		$this->id                 = $id;
		$this->definitions_class  = $definitions_class;
		$this->enabled_by_default = $enabled_by_default;
	}

	public function id(): string {
		return $this->id;
	}

	public function field_types(): array {
		return array_keys( $this->definitions() );
	}

	public function definitions(): array {
		return ( $this->definitions_class )::definitions();
	}

	public function enabled_by_default(): bool {
		return $this->enabled_by_default;
	}
}
