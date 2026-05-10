<?php // phpcs:disable WordPress.Files.FileName
/**
 * Optional contract for resolving framework asset file paths.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface AssetPathResolver extends AssetResolver {

	/**
	 * Return the full filesystem path for a framework asset file.
	 *
	 * @param string $filename Asset filename relative to the framework assets directory.
	 */
	public function path( string $filename ): string;
}
