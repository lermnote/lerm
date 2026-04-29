<?php // phpcs:disable WordPress.Files.FileName
/**
 * Contract for resolving asset URLs and version strings.
 *
 * Decouples OptionsPage from the Lerm theme constants so the framework
 * can be embedded in any host (theme, plugin, standalone) without path breakage.
 *
 * Implement this interface and pass the resolver to Framework::mount_options_page()
 * to override the default behavior.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface AssetResolver {

	/**
	 * Return the full URL for a framework asset file.
	 *
	 * @param string $filename Asset filename relative to the framework assets directory.
	 *                         E.g. 'admin-config.css'.
	 */
	public function url( string $filename ): string;

	/**
	 * Return the cache-busting version string for the asset handle.
	 */
	public function version(): string;
}
