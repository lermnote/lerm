<?php
/**
 * Structured/admin UI field module.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Contracts\FieldModule;
use Lerm\AdminConfig\Framework\Registry\StructuredFieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StructuredFieldsModule implements FieldModule {

	public function id(): string {
		return 'structured';
	}

	public function field_types(): array {
		return array_keys( $this->definitions() );
	}

	public function definitions(): array {
		return StructuredFieldTypes::definitions();
	}

	public function enabled_by_default(): bool {
		return false;
	}
}
