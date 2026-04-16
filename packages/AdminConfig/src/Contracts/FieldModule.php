<?php
/**
 * Contract for admin-config field modules.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface FieldModule {

	public function id(): string;

	/**
	 * @return array<int, string>
	 */
	public function field_types(): array;

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function definitions(): array;

	public function enabled_by_default(): bool;
}
