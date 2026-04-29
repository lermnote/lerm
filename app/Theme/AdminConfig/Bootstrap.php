<?php // phpcs:disable WordPress.Files.FileName
/**
 * Register theme-owned admin-config schemas.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\Theme\AdminConfig;

use Lerm\AdminConfig\WordPress\Runtime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bootstrap {

	public static function register( ?Runtime $runtime = null ): void {
		$runtime = $runtime ?? Runtime::instance();

		ThemeOptionsSchema::register( $runtime );
		LayoutMetaboxSchema::register( $runtime );
		CategoryTaxonomySchema::register( $runtime );
		ProfileSettingsSchema::register( $runtime );
	}
}
