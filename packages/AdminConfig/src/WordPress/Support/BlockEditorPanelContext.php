<?php
/**
 * Contract for containers that use the HasBlockEditorPanel trait.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Support;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Framework\Framework;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface BlockEditorPanelContext {

	/**
	 * @return array<string, CompiledSchema>
	 */
	public function schemas(): array;

	public function framework(): Framework;

	public function container_type_for_block_panel(): string;
}
