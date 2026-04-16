<?php
/**
 * WordPress network-options page container.
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

final class NetworkOptionsPageContainer implements Container {

	public function __construct(
		private Framework $framework,
		private StoreResolver $stores
	) {
	}

	public function type(): string {
		return 'network_options_page';
	}

	public function mount( CompiledSchema $schema ): void {
		$definition = $schema->definition();
		$menu       = is_array( $definition['menu'] ?? null ) ? $definition['menu'] : array();

		$menu['network_admin'] = true;
		$menu['parent_slug']   = (string) ( $menu['parent_slug'] ?? 'settings.php' );
		$definition['menu']    = $menu;

		$this->framework->mount_options_page(
			$definition,
			$this->stores->resolve_backend( $schema )
		);
	}
}
