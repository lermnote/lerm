<?php
/**
 * Design-oriented field module.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Contracts\FieldModule;
use Lerm\AdminConfig\Framework\Registry\DesignFieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DesignFieldsModule implements FieldModule {

	public function id(): string {
		return 'design';
	}

	public function field_types(): array {
		return array_keys( $this->definitions() );
	}

	public function definitions(): array {
		return DesignFieldTypes::definitions();
	}

	public function enabled_by_default(): bool {
		return false;
	}
}
