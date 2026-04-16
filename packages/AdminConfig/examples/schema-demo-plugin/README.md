# Schema Demo Plugin

Reference plugin demonstrating how to consume `lerm/admin-config` as a WordPress plugin.

## What it registers

- a regular admin options page
- a comment edit-screen meta box
- a multisite network options page
- advanced field examples for `typography`, `icon`, `accordion`, and `tabbed`

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
