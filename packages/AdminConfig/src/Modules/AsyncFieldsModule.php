<?php
/**
 * Async field module.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Modules;

use Lerm\AdminConfig\Framework\FieldTypes\AsyncFieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AsyncFieldsModule extends DelegatingFieldModule {

	public function __construct() {
		parent::__construct( 'async', AsyncFieldTypes::class );
	}
}
