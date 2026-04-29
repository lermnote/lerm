<?php
/**
 * WordPress options-page container.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Containers;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Contracts\Container;
use Lerm\AdminConfig\Stores\StoreResolver;
use Lerm\AdminConfig\Framework\Framework;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionsPageContainer implements Container {

	public function __construct(
		private Framework $framework,
		private StoreResolver $stores
	) {
	}

	public function type(): string {
		return 'options_page';
	}

	public function mount( CompiledSchema $schema ): void {
		$this->framework->mount_options_page(
			$schema->definition(),
			$this->stores->resolve_backend( $schema )
		);
	}
}
