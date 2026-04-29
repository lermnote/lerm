<?php
/**
 * Minimal front-end template for the embedded-mode wp-env fixture.
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<main style="max-width: 48rem; margin: 3rem auto; padding: 0 1rem;">
	<h1><?php esc_html_e( 'Admin Config Embedded Fixture', 'admin-config-embedded' ); ?></h1>
	<p><?php esc_html_e( 'This theme exists to exercise the embedded-mode admin configuration runtime in wp-env.', 'admin-config-embedded' ); ?></p>
</main>
<?php wp_footer(); ?>
</body>
</html>
