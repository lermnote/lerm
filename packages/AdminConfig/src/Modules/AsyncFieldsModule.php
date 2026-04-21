<?php
/**
 * Async field module.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Contracts\FieldModule;
use Lerm\AdminConfig\Framework\FieldTypes\AsyncFieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AsyncFieldsModule implements FieldModule {

	public function id(): string {
		return 'async';
	}

	public function field_types(): array {
		return array_keys( $this->definitions() );
	}

	public function definitions(): array {
		return AsyncFieldTypes::definitions();
	}

	public function enabled_by_default(): bool {
		return false;
	}
}
