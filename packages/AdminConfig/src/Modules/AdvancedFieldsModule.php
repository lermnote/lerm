<?php
/**
 * Advanced field module.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Framework\FieldTypes\AdvancedFieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdvancedFieldsModule extends DelegatingFieldModule {

	public function __construct() {
		parent::__construct( 'advanced', AdvancedFieldTypes::class );
	}
}
