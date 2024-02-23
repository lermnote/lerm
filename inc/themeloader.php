<?php
/**
 * Autoloader PHP files use this function.
 *
 * @package LERM
 */
namespace Lerm\Inc;

require_once __DIR__ . '/../vendor/autoload.php';

require_once LERM_DIR . 'inc/admin/codestar-framework.php';

// loader function files
require_once LERM_DIR . 'inc/functions/functions-opengraph.php';

require_once LERM_DIR . 'inc/functions/function-login.php';
require_once LERM_DIR . 'inc/functions/functions-icon.php';
require_once LERM_DIR . 'inc/functions/functions-layout.php';

/**
 * Custom template tags for this theme.
 */
require LERM_DIR . 'inc/template-tags.php';
require LERM_DIR . 'inc/customizer.php';
// require LERM_DIR . '/inc/classes/user/ajax-login.php';
