# Quick Start

Use this guide when you want the smallest copyable setup for `lerm/admin-config`.

## Recommended lifecycle

Every integration should follow the same shape:

1. bootstrap the runtime
2. register schemas inside the bootstrap callback
3. let the bootstrap auto-call `runtime->boot()` in `wp-admin`

That keeps schema registration and container mounting in one predictable path.

## Plugin-install mode

Minimal plugin entry:

```php
<?php
/**
 * Plugin Name: Acme Settings
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

\Lerm\AdminConfig\WordPress\PluginBootstrap::boot(
	__FILE__,
	static function ( \Lerm\AdminConfig\WordPress\Runtime $runtime ): void {
		$runtime->register(
			array(
				'id'        => 'acme-settings',
				'title'     => 'Acme Settings',
				'container' => array(
					'type' => 'options_page',
				),
				'store'     => array(
					'type' => 'option',
					'key'  => 'acme_settings',
				),
				'menu'      => array(
					'parent_slug' => 'options-general.php',
					'page_title'  => 'Acme Settings',
					'menu_title'  => 'Acme Settings',
					'capability'  => 'manage_options',
				),
				'sections'  => array(
					'general' => array(
						'title'  => 'General',
						'fields' => array(
							array(
								'id'      => 'enabled',
								'type'    => 'switcher',
								'label'   => 'Enable feature',
								'default' => 1,
							),
							array(
								'id'          => 'accent_color',
								'type'        => 'color',
								'label'       => 'Accent color',
								'description' => 'Used in admin previews.',
								'default'     => '#2271b1',
							),
						),
					),
				),
			)
		);
	}
);
```

Read back values later:

```php
$runtime = \Lerm\AdminConfig\WordPress\Runtime::instance();

$settings = $runtime->all( 'acme-settings' );
$enabled  = $runtime->get( 'acme-settings', 'enabled', '', 0 );
```

## Embedded mode

Minimal theme bootstrap:

```php
<?php

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/vendor/autoload.php';

add_action(
	'after_setup_theme',
	static function (): void {
		\Lerm\AdminConfig\WordPress\EmbeddedBootstrap::boot(
			trailingslashit( get_template_directory_uri() ) . 'packages/AdminConfig/assets',
			'LERM_VERSION',
			static function ( \Lerm\AdminConfig\WordPress\Runtime $runtime ): void {
				$runtime->register(
					array(
						'id'        => 'theme-appearance',
						'title'     => 'Theme Appearance',
						'container' => array(
							'type' => 'options_page',
						),
						'store'     => array(
							'type' => 'option',
							'key'  => 'theme_appearance_settings',
						),
						'menu'      => array(
							'parent_slug' => 'themes.php',
							'page_title'  => 'Theme Appearance',
							'menu_title'  => 'Theme Appearance',
							'capability'  => 'edit_theme_options',
						),
						'sections'  => array(
							'branding' => array(
								'title'  => 'Branding',
								'fields' => array(
									array(
										'id'      => 'logo_shape',
										'type'    => 'button_set',
										'label'   => 'Logo shape',
										'default' => 'rounded',
										'choices' => array(
											'rounded' => 'Rounded',
											'square'  => 'Square',
										),
									),
								),
							),
						),
					)
				);
			}
		);
	},
	20
);
```

## Meta-backed schemas

For `post_meta`, `term_meta`, `user_meta`, and `comment_meta`, pass the object ID context when reading stored values:

```php
$entry_settings = $runtime->all(
	'acme-entry-overrides',
	array( 'post_id' => get_the_ID() )
);

$badge = $runtime->get(
	'acme-entry-overrides',
	'badge_text',
	'',
	'',
	array( 'post_id' => get_the_ID() )
);
```

If you call `all()` or `get()` without the required object context, the runtime falls back to compiled defaults and emits a debug notice in `WP_DEBUG`. Use `store()` directly only when you want strict behavior and are ready to handle missing-context exceptions.

## Async select fields

Use `ajax_select` when a field should search a runtime data source without
reloading the page:

```php
$runtime->register_data_source(
	'campaign_library',
	static function ( array $args = array() ): array {
		$items = array(
			array( 'value' => 'spring-launch', 'label' => 'Spring Launch' ),
			array( 'value' => 'creator-series', 'label' => 'Creator Series' ),
			array( 'value' => 'audio-week', 'label' => 'Audio Week' ),
		);

		return array( 'items' => $items, 'more' => false );
	}
);

$runtime->register(
	array(
		'id'        => 'acme-settings',
		'title'     => 'Acme Settings',
		'container' => array( 'type' => 'options_page' ),
		'store'     => array( 'type' => 'option', 'key' => 'acme_settings' ),
		'menu'      => array(
			'parent_slug' => 'options-general.php',
			'page_title'  => 'Acme Settings',
			'menu_title'  => 'Acme Settings',
			'capability'  => 'manage_options',
		),
		'sections'  => array(
			'general' => array(
				'title'  => 'General',
				'fields' => array(
					array(
						'id'                => 'featured_campaign',
						'type'              => 'ajax_select',
						'source'            => 'campaign_library',
						'label'             => 'Featured campaign',
						'placeholder'       => 'Search campaigns...',
						'min_search_length' => 1,
						'default'           => 'spring-launch',
					),
				),
			),
		),
	)
);
```

## Batch registration

If one bootstrap callback needs to register several schemas, use `register_many()`:

```php
$runtime->register_many(
	array(
		$site_settings_schema,
		$profile_schema,
		$term_schema,
	)
);
```

## Boot hooks

Both boot paths fire the same ready hook:

```php
add_action(
	'lerm_admin_config_booted',
	static function ( \Lerm\AdminConfig\WordPress\Runtime $runtime, string $mode ): void {
		// Register extensions or observe the ready runtime.
	},
	10,
	2
);
```

`$mode` is either `plugin` or `embedded`.

## Debug vs production

- duplicate schema IDs: first registration wins, duplicate is ignored, `_doing_it_wrong()` in `WP_DEBUG`
- missing container adapters: schema is skipped, `_doing_it_wrong()` in `WP_DEBUG`
- invalid store configuration during mount: schema is skipped, `_doing_it_wrong()` in `WP_DEBUG`
- malformed field definitions without an `id`: field is ignored, `_doing_it_wrong()` in `WP_DEBUG`

Direct developer calls such as `store()` and registry getters still throw, because those are explicit integration-time failures rather than passive runtime mounting.

Options pages also expose a runtime summary panel in debug mode. It is enabled
automatically in `WP_DEBUG`, or explicitly per schema:

```php
'view' => array(
	'debug' => true,
),
```

## Next references

- `examples/schema-demo-plugin/`
- `examples/minimal-extension-plugin/`
- `examples/embedded-theme-demo/`
- `docs/extension-api.md`
- `docs/extension-recipes.md`
