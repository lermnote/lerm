<?php
/**
 * Structured/admin UI field module.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Framework\FieldTypes\StructuredFieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StructuredFieldsModule extends DelegatingFieldModule {

	public function __construct() {
		parent::__construct( 'structured', StructuredFieldTypes::class );
	}
}
