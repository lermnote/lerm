<?php
/**
 * Default core field module.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Contracts\FieldModule;
use Lerm\AdminConfig\Framework\Registry\BuiltinFieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CoreFieldsModule implements FieldModule {

	public function id(): string {
		return 'core';
	}

	public function field_types(): array {
		return array_keys( $this->definitions() );
	}

	public function definitions(): array {
		return BuiltinFieldTypes::definitions();
	}

	public function enabled_by_default(): bool {
		return true;
	}
}
