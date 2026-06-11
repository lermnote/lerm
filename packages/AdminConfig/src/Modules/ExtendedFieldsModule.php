<?php
/**
 * Extended primitive field module.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Framework\FieldTypes\ExtendedPrimitiveFieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ExtendedFieldsModule extends DelegatingFieldModule {

	public function __construct() {
		parent::__construct( 'extended', ExtendedPrimitiveFieldTypes::class );
	}
}
