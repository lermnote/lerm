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

The package is split into two layers that happen to reuse a few directory names:

- `src/Framework/*`: the low-level reusable engine. This is where field registration, admin rendering, storage backends, and the normalized option store live.
- `src/Compiler`, `src/Registry`, `src/Stores`, `src/WordPress`: the package/runtime orchestration layer around that engine.

The similarly named directories are related like this:

- `src/Framework/Registry` = field-type registry and field definition catalogs
- `src/Registry` = runtime registries for compiled schemas, containers, and field modules
- `src/Framework/Stores` = the actual normalized store implementation used by pages and containers
- `src/Stores` = the resolver that maps compiled `store.type` config to concrete WordPress backends

So the inner `Framework/*` directories are framework internals, while the outer directories wire those internals into the schema runtime.

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

Field modules in the current slice:

- `core`: always-on primitive fields
- `extended`: extra primitive and presentation fields
- `design`: composite design fields
- `advanced`: typography, icon, accordion, and tabbed fields
- `structured`: fieldset/group/media/gallery/editor/sorter fields
- `tools`: backup/import-export helpers

Modules are activated on demand from schema field usage, so a schema that only uses primitive fields does not need to load the structured or tools definitions.

Schemas are expected to use the native `label`, `description`, `group_heading`, `choices`, `groups`, `container`, and `store` keys directly.

Validation now follows the same PHP-first path across containers: validators can return `WP_Error`, `OptionStore` collects field errors, options pages surface them over AJAX/non-JS fallback, and profile/comment/taxonomy/metabox containers replay the same errors after redirect instead of silently partially saving.

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
		'sanitize' => static fn ( array $field, $value, bool $strict, \Lerm\AdminConfig\Framework\Stores\OptionStore $store ) => sanitize_title( (string) $value ),
	)
);

$runtime->register_validator(
	'slug_text',
	static function ( array $field, $value, bool $strict, \Lerm\AdminConfig\Framework\Stores\OptionStore $store ) {
		return strlen( (string) $value ) >= 3 ? $value : new WP_Error( 'slug_too_short' );
	}
);
```

Late registration is supported: if you register a schema after `boot()`, or register a container after some schemas were already compiled, the runtime will mount matching schemas when the needed container becomes available.

See [docs/extension-api.md](/D:/xampp/htdocs/lerm/wp-content/themes/lerm/packages/AdminConfig/docs/extension-api.md) for the first extension guide and [docs/smoke-checklist.md](/D:/xampp/htdocs/lerm/wp-content/themes/lerm/packages/AdminConfig/docs/smoke-checklist.md) for the current manual regression pass.

## Boot modes

### Embedded mode

Themes or bundled packages boot the runtime with:

```php
use Lerm\AdminConfig\WordPress\EmbeddedBootstrap;

$runtime = EmbeddedBootstrap::boot(
	trailingslashit( get_template_directory_uri() ) . 'packages/AdminConfig/assets',
	'LERM_VERSION'
);
```

### Plugin-install mode

Standalone plugin builds boot the runtime with:

```php
use Lerm\AdminConfig\WordPress\PluginBootstrap;

$runtime = PluginBootstrap::boot( __FILE__ );
```

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
2. Expand examples and smoke coverage for profile, taxonomy, comment, and network workflows.
3. Introduce async data-source hooks and richer validation pipelines for AJAX-backed fields.
4. Keep commerce concerns such as licensing and updates in a separate layer on top of the runtime.
