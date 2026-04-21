# Lerm Admin Config

Schema-driven WordPress admin configuration infrastructure.

## Goals

- Keep PHP schema as the single source of truth.
- Support both plugin-install mode and embedded mode.
- Share one runtime across options pages, metaboxes, taxonomy/profile/comment screens, and network settings.
- Separate core runtime concerns from commerce concerns such as licensing and updates.

## Current slice

This repository now contains the first extraction slice:

- `src/Compiler`: schema compilation and compiled metadata payloads
- `src/Registry`: runtime schema registry
- `src/Framework`: bundled field/store/admin framework used by the runtime
- `src/Stores`: store resolution for WordPress option/meta backends
- `src/WordPress`: embedded bootstrap, plugin bootstrap, shared runtime, and container adapters
- `assets`: reusable admin UI assets
- `lerm-admin-config.php`: plugin entry point for standalone installs
- `examples/schema-demo-plugin`: reference plugin using the package in plugin-install mode
- `examples/embedded-theme-demo`: reference embedded-mode bootstrap for themes

## Architecture layers

The package is split into two layers:

- `src/Framework/*`: the low-level reusable engine. This is where field registration, admin rendering, storage backends, and the normalized option store live.
- `src/Compiler`, `src/Registry`, `src/Stores`, `src/WordPress`: the package/runtime orchestration layer around that engine.

The main boundaries look like this:

- `src/Framework/FieldTypes` = field-type registry and field definition catalogs
- `src/Registry` = runtime registries for compiled schemas, containers, and field modules
- `src/Framework/Storage` = the actual normalized store implementation used by pages and containers
- `src/Stores` = the resolver that maps compiled `store.type` config to concrete WordPress backends

So the `Framework/*` directories are engine internals, while the outer directories wire those internals into the schema runtime.

Supported container types in the current slice:

- `options_page`
- `network_options_page`
- `metabox`
- `taxonomy`
- `profile`
- `comment`

Supported store types in the current slice:

- `option`
- `post_meta`
- `term_meta`
- `user_meta`
- `comment_meta`
- `site_option` / `network_option`

Built-in field coverage in the current slice:

- Core primitives: `text`, `url`, `textarea`, `number`, `color`, `switcher`, `button_set`, `radio`, `select`, `checkbox_list`
- Extended primitives: `checkbox`, `upload`, `date`, `slider`, `spinner`, `image_select`, `palette`
- Presentation fields: `heading`, `subheading`, `content`, `notice`
- Composite/design fields: `dimensions`, `spacing`, `border`, `link_color`, `background`
- Advanced fields: `typography`, `icon`, `accordion`, `tabbed`
- Existing structured fields: `fieldset`, `group`, `media`, `gallery`, `sorter`, `code_editor`, `wp_editor`, `backup_tools`
- Async fields: `ajax_select`

Field modules in the current slice:

- `core`: always-on primitive fields
- `extended`: extra primitive and presentation fields
- `design`: composite design fields
- `advanced`: typography, icon, accordion, and tabbed fields
- `structured`: fieldset/group/media/gallery/editor/sorter fields
- `tools`: backup/import-export helpers
- `async`: AJAX-backed select controls

Modules are activated on demand from schema field usage, so a schema that only uses primitive fields does not need to load the structured or tools definitions.

Schemas are expected to use the native `label`, `description`, `group_heading`, `choices`, `groups`, `container`, and `store` keys directly.

Validation now follows the same PHP-first path across containers: validators can return `WP_Error`, `OptionStore` collects dotted field-path errors, options pages surface them over AJAX/non-JS fallback, and profile/comment/taxonomy/metabox containers replay the same errors after redirect instead of silently partially saving.

Options pages also expose an opt-in runtime debug panel. It turns on automatically in `WP_DEBUG`, or per schema with `'view' => array( 'debug' => true )`.

## Public Extension API

The shared runtime now exposes explicit extension methods for third-party integrations:

- `register_field_type( $type, $definition )`
- `register_validator( $type, $validator )`
- `register_field_module( $module )`
- `register_store_factory( $type, $factory )`
- `register_container( $container )`
- `register_data_source( $source_id, $resolver )`
- `resolve_data_source( $source_id, $args = array() )`

These methods live on `Lerm\AdminConfig\WordPress\Runtime`, so plugin authors and embedded themes can stay on the package boundary instead of reaching into internals.

```php
use Lerm\AdminConfig\WordPress\Runtime;

$runtime->register_data_source(
	'tone_presets',
	static function (): array {
		return array(
			'calm'  => 'Calm',
			'bold'  => 'Bold',
			'clean' => 'Clean',
		);
	}
);

$runtime->register_field_type(
	'slug_text',
	array(
		'render' => static function ( array $field, $value, string $field_name, \Lerm\AdminConfig\Framework\Admin\OptionsPage $page ): void {
			printf(
				'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text">',
				esc_attr( (string) ( $field['id'] ?? '' ) ),
				esc_attr( $field_name ),
				esc_attr( is_scalar( $value ) ? (string) $value : '' )
			);
		},
		'sanitize' => static fn ( array $field, $value, bool $strict, \Lerm\AdminConfig\Framework\Storage\OptionStore $store ) => sanitize_title( (string) $value ),
	)
);

$runtime->register_validator(
	'slug_text',
	static function ( array $field, $value, bool $strict, \Lerm\AdminConfig\Framework\Storage\OptionStore $store ) {
		return strlen( (string) $value ) >= 3 ? $value : new WP_Error( 'slug_too_short' );
	}
);
```

Late registration is supported: if you register a schema after `boot()`, or register a container after some schemas were already compiled, the runtime will mount matching schemas when the needed container becomes available.

See [docs/quick-start.md](/D:/xampp/htdocs/lerm/wp-content/themes/lerm/packages/AdminConfig/docs/quick-start.md) for the copyable onboarding path, [docs/extension-api.md](/D:/xampp/htdocs/lerm/wp-content/themes/lerm/packages/AdminConfig/docs/extension-api.md) for the extension surface, and [docs/smoke-checklist.md](/D:/xampp/htdocs/lerm/wp-content/themes/lerm/packages/AdminConfig/docs/smoke-checklist.md) for the current manual regression pass.

## Recommended lifecycle

The recommended integration path is now:

1. bootstrap the runtime
2. register schemas inside the bootstrap callback
3. let the bootstrap auto-call `runtime->boot()` in wp-admin

Plugin-install mode:

```php
use Lerm\AdminConfig\WordPress\PluginBootstrap;
use Lerm\AdminConfig\WordPress\Runtime;

PluginBootstrap::boot(
	__FILE__,
	static function ( Runtime $runtime ): void {
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
								'id'      => 'enabled',
								'type'    => 'switcher',
								'label'   => 'Enable feature',
								'default' => 1,
							),
						),
					),
				),
			)
		);
	}
);
```

The bootstrap callback runs before auto-mounting, so the runtime sees the full schema set during the initial `boot()`.

Embedded mode follows the same shape through `EmbeddedBootstrap::boot(...)`.

## Boot modes

### Embedded mode

Themes or bundled packages boot the runtime with:

```php
use Lerm\AdminConfig\WordPress\EmbeddedBootstrap;
use Lerm\AdminConfig\WordPress\Runtime;

$runtime = EmbeddedBootstrap::boot(
	trailingslashit( get_template_directory_uri() ) . 'packages/AdminConfig/assets',
	'LERM_VERSION',
	static function ( Runtime $runtime ): void {
		// Register schemas here.
	}
);
```

Both embedded mode and plugin-install mode fire:

```php
do_action( 'lerm_admin_config_booted', $runtime, 'embedded' );
do_action( 'lerm_admin_config_booted', $runtime, 'plugin' );
```

So third-party extensions can wait for a ready runtime in either boot path.

### Plugin-install mode

Standalone plugin builds boot the runtime with:

```php
use Lerm\AdminConfig\WordPress\PluginBootstrap;
use Lerm\AdminConfig\WordPress\Runtime;

$runtime = PluginBootstrap::boot(
	__FILE__,
	static function ( Runtime $runtime ): void {
		// Register schemas here.
	}
);
```

Both bootstraps automatically call `runtime->boot()` in `wp-admin`, so late schema
registration still works and host integrations no longer need a separate final
mount step.

If one integration callback needs to wire several schemas at once, `Runtime::register_many()` accepts a plain array of schema definitions and returns the compiled results.

## Developer checks

The package now ships with a small QA toolchain that works both standalone and
when embedded inside a theme:

- `composer lint:php` runs the lightweight PHP syntax/import checker
- `composer lint:wpcs` runs WPCS through `tools/phpcs-runner.php`
- `composer lint:js` validates `assets/admin-config.js`
- `composer test` runs the package unit test harness
- `composer ci` runs the default local gate
- `composer analyse:phpstan` runs PHPStan when the binary is available

The PHPCS and PHPStan runners prepend `tools/wp-tool-stubs.php`, so they can be
executed from an embedded theme workspace without fatalling on eager theme
autoloads that call WordPress functions.

## Reading meta-backed schemas

Meta-backed stores such as `post_meta`, `term_meta`, `user_meta`, and `comment_meta`
need an object context when you want persisted data:

```php
$values = $runtime->all(
	'acme-entry-overrides',
	array( 'post_id' => get_the_ID() )
);
```

When `Runtime::all()` or `Runtime::get()` is called without the required object
context, the runtime now falls back to the compiled schema defaults and emits a
debug notice in `WP_DEBUG`. If you need strict behavior, call `store()` directly,
which still throws when the context is missing.

## Diagnostics policy

Admin Config now uses a consistent split between debug and production behavior:

- duplicate schema IDs: first registration wins, duplicate is ignored, `_doing_it_wrong()` in `WP_DEBUG`
- missing container adapters: schema is skipped, `_doing_it_wrong()` in `WP_DEBUG`
- invalid store configuration during mount: schema is skipped, `_doing_it_wrong()` in `WP_DEBUG`
- malformed field definitions without an `id`: field is ignored, `_doing_it_wrong()` in `WP_DEBUG`

Direct low-level API calls such as `store()` and registry getters still throw
exceptions, because those are explicit developer calls rather than passive
runtime mounting.

## Schema examples

### Network options page

```php
$runtime->register(
	array(
		'id'        => 'acme-network-settings',
		'title'     => 'Acme Network Settings',
		'container' => array(
			'type' => 'network_options_page',
		),
		'store'     => array(
			'type' => 'network_option',
			'key'  => 'acme_network_settings',
		),
		'menu'      => array(
			'page_title' => 'Acme Network Settings',
			'menu_title' => 'Acme Settings',
			'capability' => 'manage_network_options',
		),
		'sections'  => array(
			'general' => array(
				'title'  => 'General',
				'fields' => array(
					array(
						'id'      => 'feature_enabled',
						'type'    => 'switcher',
						'label'   => 'Enable feature',
						'default' => 1,
					),
				),
			),
		),
	)
);
```

### Comment meta box

```php
$runtime->register(
	array(
		'id'        => 'acme-comment-settings',
		'title'     => 'Comment Settings',
		'container' => array(
			'type'     => 'comment',
			'title'    => 'Comment Moderation Data',
			'context'  => 'normal',
			'priority' => 'default',
		),
		'store'     => array(
			'type' => 'comment_meta',
			'key'  => 'acme_comment_settings',
		),
		'sections'  => array(
			'moderation' => array(
				'title'  => 'Moderation',
				'fields' => array(
					array(
						'id'      => 'reviewed_by_staff',
						'type'    => 'switcher',
						'label'   => 'Reviewed by staff',
						'default' => 0,
					),
					array(
						'id'          => 'moderation_note',
						'type'        => 'textarea',
						'label'       => 'Internal note',
						'description' => 'Only visible in wp-admin.',
						'default'     => '',
					),
				),
			),
		),
	)
);
```

## Example plugin

See `examples/schema-demo-plugin/` for a runnable reference plugin that registers:

- one site-level options page
- one post/page metabox
- one comment meta box
- one user profile screen
- one taxonomy term settings screen
- one multisite network settings page
- advanced field examples driven by the same runtime
- a custom field type, validator, and named data source through the public runtime API

It is useful as the starting point for plugin authors consuming this package directly.

## Example theme embed

See `examples/embedded-theme-demo/` for an embedded-mode reference that boots the runtime from a theme, registers an options page, and adds a metabox through the same package.

## Next milestones

1. Expand the extension guides for custom field types, validators, stores, and container adapters.
2. Add automated smoke helpers around the example plugin and embedded demo so container regressions are easier to catch before release.
3. Introduce async data-source hooks and richer validation pipelines for AJAX-backed fields.
4. Keep commerce concerns such as licensing and updates in a separate layer on top of the runtime.
