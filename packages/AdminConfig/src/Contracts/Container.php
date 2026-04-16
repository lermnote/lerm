<?php
/**
 * Contract for admin-config UI containers.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Contracts;

use Lerm\AdminConfig\Compiler\CompiledSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Container {

	public function type(): string;

	public function mount( CompiledSchema $schema ): void;
}
