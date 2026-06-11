<?php
/**
 * Design-oriented field module.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Framework\FieldTypes\DesignFieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DesignFieldsModule extends DelegatingFieldModule {

	public function __construct() {
		parent::__construct( 'design', DesignFieldTypes::class );
	}
}
