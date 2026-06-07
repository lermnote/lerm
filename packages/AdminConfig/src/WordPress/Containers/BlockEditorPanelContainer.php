<?php
/**
 * WordPress block editor panel container for admin-config schemas.
 *
 * Mounted schemas appear as side-panel controls in the Gutenberg editor
 * via the shared block-panel JavaScript bundle. Unlike MetaboxContainer,
 * this container only activates when the block editor is in use — it never
 * renders classic meta boxes.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Containers;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Contracts\Container;
use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\WordPress\Support\HasBlockEditorPanel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BlockEditorPanelContainer implements Container {

	use HasBlockEditorPanel;

	/**
	 * @var array<string, CompiledSchema>
	 */
	private array $schemas = array();

	private bool $hooks_registered = false;

	public function __construct(
		private Framework $framework
	) {
	}

	public function type(): string {
		return 'block_editor_panel';
	}

	public function mount( CompiledSchema $schema ): void {
		$this->schemas[ $schema->id() ] = $schema;

		if ( $this->hooks_registered ) {
			return;
		}

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		$this->hooks_registered = true;
	}

	private function containerTypeForBlockPanel(): string {
		return 'block_editor_panel';
	}
}
