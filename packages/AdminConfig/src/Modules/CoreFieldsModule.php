<?php
/**
 * Default core field module.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Framework\FieldTypes\BuiltinFieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CoreFieldsModule extends DelegatingFieldModule {

	public function __construct() {
		parent::__construct( 'core', BuiltinFieldTypes::class, true );
	}
}
