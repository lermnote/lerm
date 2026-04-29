<?php
/**
 * Extended primitive field module.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Contracts\FieldModule;
use Lerm\AdminConfig\Framework\FieldTypes\ExtendedPrimitiveFieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ExtendedFieldsModule implements FieldModule {

	public function id(): string {
		return 'extended';
	}

	public function field_types(): array {
		return array_keys( $this->definitions() );
	}

	public function definitions(): array {
		return ExtendedPrimitiveFieldTypes::definitions();
	}

	public function enabled_by_default(): bool {
		return false;
	}
}
