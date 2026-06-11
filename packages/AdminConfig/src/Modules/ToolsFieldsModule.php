<?php
/**
 * Tools/utility field module.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Framework\FieldTypes\ToolFieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ToolsFieldsModule extends DelegatingFieldModule {

	public function __construct() {
		parent::__construct( 'tools', ToolFieldTypes::class );
	}
}
