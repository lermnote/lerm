# Schema Demo Plugin

Reference plugin demonstrating how to consume `lerm/admin-config` as a WordPress plugin.

It follows the recommended package lifecycle: bootstrap once, register schemas in
the bootstrap callback, and let the runtime auto-mount in `wp-admin`.

For the smallest copyable setup, start with [docs/quick-start.md](/D:/xampp/htdocs/lerm/wp-content/themes/lerm/packages/AdminConfig/docs/quick-start.md), then use this example when you want a fuller end-to-end reference.

If you only need the smallest public-extension example, start with [examples/minimal-extension-plugin/README.md](/D:/xampp/htdocs/lerm/wp-content/themes/lerm/packages/AdminConfig/examples/minimal-extension-plugin/README.md) first.

## What it registers

- a regular admin options page
- a post/page metabox
- a comment edit-screen meta box
- a user profile section
- a category term settings screen
- a multisite network options page
- advanced field examples for `typography`, `icon`, `accordion`, and `tabbed`
- a custom `slug_text` field type registered through the runtime
- a field validator registered through the runtime
- a named data source used to resolve select choices before schema registration

## Autoload resolution

The plugin looks for Composer autoload files in three places:

1. its own `vendor/`
2. the package root `packages/AdminConfig/vendor/`
3. the theme root `vendor/`

That makes it usable both inside this monorepo and as a standalone example after packaging.

## Advanced field coverage

The site options page includes:

- a `typography` schema showing compiled defaults and grouped controls
- an `icon` schema using curated Dashicons choices
- an `accordion` schema with per-panel nested fields
- a `tabbed` schema with alternate content recipes

The plugin example as a whole now covers every built-in container adapter shipped in the package: site options, post meta, comment meta, user meta, term meta, and multisite network options.

## Extension API coverage

The example plugin also demonstrates:

- `register_field_type()` with a minimal custom slug input
- `register_validator()` for custom field-level validation
- `register_data_source()` and `resolve_data_source()` for reusable choices
- the same custom field type reused across options, metabox, comment, profile, taxonomy, and network workflows
