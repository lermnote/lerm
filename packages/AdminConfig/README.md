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

The package intentionally does not ship a Codestar compatibility layer. Schemas are expected to use the native `label`, `description`, `group_heading`, `choices`, `groups`, `container`, and `store` keys directly.

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
- one multisite network settings page
- advanced field examples driven by the same runtime

It is useful as the starting point for plugin authors consuming this package directly.

## Example theme embed

See `examples/embedded-theme-demo/` for an embedded-mode reference that boots the runtime from a theme, registers an options page, and adds a metabox through the same package.

## Next milestones

1. Add first-class extension guides for custom field types, validators, stores, and container adapters.
2. Expand examples and smoke coverage for profile, taxonomy, comment, and network workflows.
3. Introduce async data-source hooks and richer validation pipelines for AJAX-backed fields.
4. Keep commerce concerns such as licensing and updates in a separate layer on top of the runtime.
