=== Lerm Admin Config ===
Contributors: lermnote
Donate link: https://lerm.net
Tags: admin, options, schema, fields
Requires at least: 6.6
Requires PHP: 8.0
Tested up to: 6.8
Stable tag: 0.3.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Schema-driven WordPress admin configuration infrastructure for options pages,
metaboxes, taxonomy fields, profile screens, and comment meta.

== Description ==

Lerm Admin Config is a schema-driven WordPress admin configuration
infrastructure. Define your admin options pages, metaboxes, taxonomy fields,
and user profile panels entirely in PHP arrays — the runtime handles rendering,
validation, persistence, and REST API exposure.

Supports plugin-install mode (standalone plugin) and embedded mode (bundled
in a theme or another plugin).

== Installation ==

= Plugin mode =
1. Upload the plugin files to `/wp-content/plugins/lerm-admin-config/`.
2. Activate the plugin through the 'Plugins' screen.
3. Use `PluginBootstrap::boot()` to register your schemas.

= Embedded mode (Composer) =
1. `composer require lerm/admin-config`
2. Call `EmbeddedBootstrap::boot($assets_url)` from your theme or plugin.

== Frequently Asked Questions ==

= What field types are supported? =

Text, URL, textarea, number, color, switcher, select, radio, button set,
checkbox, checkbox list, media, gallery, code editor, WP editor, sorter,
image select, palette, slider, spinner, date, group, fieldset, notice,
heading, subheading, content, background, border, spacing, typography,
upload, backup, export, import, and custom types via the registry API.

= Does it work with the block editor? =

Yes, metaboxes registered via the schema render in both classic and block
editor contexts. A block-editor sidebar panel is also supported.

= Can I extend it with custom field types? =

Yes, use `FieldTypeRegistry::register()` to add custom types with render,
sanitize, validate, and serialize callbacks.

== Changelog ==

= 0.3.0 =
* Initial extraction slice: compiler, registry, framework, WordPress runtime.
* Field type registry with built-in, extended, async, design, and advanced catalogs.
* REST API endpoints for schemas and values.
* Plugin and embedded bootstrap modes.
* WordPress Playground compatibility.
