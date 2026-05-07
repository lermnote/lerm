# Lerm Admin Config

Schema-driven WordPress admin configuration infrastructure.

## Goals

- Keep PHP schema as the single source of truth.
- Support both plugin-install mode and embedded mode.
- Use one isolated runtime per integration across options pages, metaboxes, taxonomy/profile/comment screens, and network settings.
- Separate core runtime concerns from commerce concerns such as licensing and updates.

## Current slice

This repository now contains the first extraction slice:

- `src/Compiler`: schema compilation and compiled metadata payloads
- `src/Registry`: runtime schema registry
- `src/Framework`: bundled field/store/admin framework used by the runtime
- `src/Stores`: store resolution for WordPress option/meta backends
- `src/WordPress`: embedded bootstrap, plugin bootstrap, runtime, REST endpoints, and container adapters
- `resources`: JavaScript source entry points and client boundaries for core,
  controls, store, classic admin, and the future block-editor panel
- `assets/build`: generated WordPress script bundles and `*.asset.php` metadata;
  ignored in source control and produced by `npm run build`
- `assets`: reusable admin UI styles plus packaged classic-admin script fallback
- `lerm-admin-config.php`: plugin entry point for standalone installs
- `examples/schema-demo-plugin`: reference plugin using the package in plugin-install mode
- `examples/minimal-extension-plugin`: smallest runnable extension-author example
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

Each bootstrapped runtime exposes explicit extension methods for third-party integrations:

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

See [docs/quick-start.md](/packages/AdminConfig/docs/quick-start.md) for the copyable onboarding path, [docs/extension-api.md](/packages/AdminConfig/docs/extension-api.md) for the extension surface, [docs/extension-recipes.md](/packages/AdminConfig/docs/extension-recipes.md) for minimal custom field/validator/data-source snippets, [docs/rest-api.md](/packages/AdminConfig/docs/rest-api.md) for the REST transport contract, [docs/block-editor-migration.md](/packages/AdminConfig/docs/block-editor-migration.md) for the eventual Gutenberg migration path, and [docs/smoke-checklist.md](/packages/AdminConfig/docs/smoke-checklist.md) for the current manual regression pass.

## Recommended lifecycle

The recommended integration path is now:

1. bootstrap the runtime
2. register schemas inside the bootstrap callback
3. let the bootstrap auto-call `runtime->boot()` in wp-admin

Plugin-install mode:

```php
use Lerm\AdminConfig\WordPress\PluginBootstrap;
use Lerm\AdminConfig\WordPress\Runtime;

$runtime = PluginBootstrap::boot(
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

The bootstrap callback runs before auto-mounting, so the runtime sees the full schema set during the initial `boot()`. Keep the returned runtime when you need to read values later, or observe `lerm_admin_config_booted` when another integration owns the bootstrap.

Embedded mode follows the same shape through `EmbeddedBootstrap::boot(...)`.

In plugin-install mode, the asset resolver uses the passed plugin file when
that plugin bundles the AdminConfig assets. Built script bundles are loaded from
`assets/build/*.js` with `*.asset.php` metadata when present. Source checkouts
fall back to `assets/admin-config.js` for classic admin screens before a build
has run; block-editor panel work requires `npm run build` so
`assets/build/block-panel.js` exists.
Extension/demo plugins that only register schemas fall back to the package
assets.

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
- `composer lint:js` validates `resources/`, the packaged script fallback,
  webpack config, and Playwright specs
- `composer test` runs the PHPUnit unit and smoke suites
- `composer test:integration` runs the real-WordPress integration suite when a
  reachable `wp-load.php` is available
- `composer ci` runs the default local gate
- `composer analyse:phpstan` runs PHPStan when the binary is available
- `npm run build` compiles `resources/admin/index.js` and
  `resources/block-panel/index.js` to `assets/build/*.js` plus asset metadata
- `npm run build:check` rebuilds and verifies generated assets and metadata
- `npm run test:js-runtime` checks the core schema state, context, error, and
  block-panel runtime helpers
- `npm run check:phase2` runs build verification, legacy Ajax removal, and JS runtime checks

After a fresh source checkout, run:

```bash
npm ci
npm run build
```

Release archives and CI browser jobs must include `assets/build/`; the directory
is intentionally not committed to git.

The PHPCS and PHPStan runners prepend `tools/wp-tool-stubs.php`, so they can be
executed from an embedded theme workspace without fatalling on eager theme
autoloads that call WordPress functions.

For browser and container-level coverage the package also ships with `wp-env`
and Playwright scaffolding:

- `npm install` installs `@wordpress/env` and `@playwright/test`
- `npm run test:integration` starts `wp-env`, activates the package plugin, the
  bundled schema demo plugin, and the embedded fixture theme, then runs
  `phpunit.integration.xml.dist` inside the WordPress container
- `npm run test:integration:multisite` boots a multisite `wp-env` instance on
  ports `8890/8891` and runs the same integration suite with the multisite-only
  assertions enabled
- `npm run test:e2e` runs the Playwright smoke suite against plugin mode,
  embedded mode, and the classic metabox/profile/taxonomy/comment containers
- `npm run test:e2e:multisite` runs the multisite network settings smoke suite
- `npm run test:wp:rest-contract` runs REST contract smoke coverage for
  single-site and multisite AdminConfig actions

The `tests/fixtures/wp-env/` directory contains the setup script and fixture
theme used by those jobs. The fixture bootstrap also creates deterministic page,
post, comment, and category records so the browser smoke specs can navigate
classic admin screens without extra manual setup. `wp-env` requires Docker; when
you already have a local WordPress checkout available, `composer test:integration`
can run directly against that install instead.

The multisite wrapper isolates its Docker Compose project and `WP_ENV_HOME`
under `.wp-env-multisite/`. It temporarily writes `.wp-env.override.json` while
delegating to `wp-env`, then restores or removes that file on exit. If a local
run is interrupted, remove the generated override before starting a default
single-site `wp-env` run.

For local Playwright runs against an existing WordPress install, set:

- `LERM_ADMIN_CONFIG_BASE_URL`
- `LERM_ADMIN_CONFIG_ADMIN_USER`
- `LERM_ADMIN_CONFIG_ADMIN_PASS`

Then run `npm run test:e2e:local`. This path skips `wp-env` and points the same
specs at your chosen site. To point the network smoke spec at a local multisite
admin, also set `LERM_ADMIN_CONFIG_MULTISITE=1` and pass the spec path after `--`.

## Support and Versioning

- PHP support starts at `8.0`
- The package targets modern WordPress admin APIs and ships integration/E2E
  scaffolding against the default stable `wp-env` environment
- Release channel:
  - `0.x`: alpha/beta extraction phase, public APIs may still tighten
  - `1.0.0+`: Semantic Versioning for runtime, extension, and schema-facing APIs
- Breaking runtime changes should land with changelog notes, migration guidance,
  and updated examples

See [docs/support-matrix.md](/packages/AdminConfig/docs/support-matrix.md) for the compatibility snapshot, [docs/rest-api.md](/packages/AdminConfig/docs/rest-api.md) for the REST API contract, [docs/ajax-retirement.md](/packages/AdminConfig/docs/ajax-retirement.md) for the completed legacy Ajax removal notes, [docs/block-editor-migration.md](/packages/AdminConfig/docs/block-editor-migration.md) for the block-editor migration path, [CONTRIBUTING.md](/packages/AdminConfig/CONTRIBUTING.md) for the local development flow, and [release-checklist.md](/packages/AdminConfig/docs/release-checklist.md) for the alpha cut process.

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

## Minimal extension example

See `examples/minimal-extension-plugin/` for the smallest runnable plugin that demonstrates:

- `register_field_type()`
- `register_validator()`
- `register_data_source()`
- one schema that consumes those registrations on a standard options page

## Example theme embed

See `examples/embedded-theme-demo/` for an embedded-mode reference that boots the runtime from a theme, registers an options page, and adds a metabox through the same package.

## Next milestones

1. Expand the extension guides for custom field types, validators, stores, and container adapters.
2. Broaden browser coverage from smoke flows into richer advanced-field interaction regressions.
3. Add a no-Docker contributor path for browser automation where `wp-env` can use Playground.
4. Keep commerce concerns such as licensing and updates in a separate layer on top of the runtime.
