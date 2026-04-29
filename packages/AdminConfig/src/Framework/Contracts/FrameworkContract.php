<?php
/**
 * Contract for framework lifecycle services consumed by storage.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface FrameworkContract {

	/**
	 * Fire a framework lifecycle hook.
	 *
	 * @param string               $hook    Short hook name.
	 * @param string               $page_id The page / store identifier.
	 * @param array<string, mixed> $data    Data being saved.
	 */
	public function fire( string $hook, string $page_id, array $data ): void;
}
