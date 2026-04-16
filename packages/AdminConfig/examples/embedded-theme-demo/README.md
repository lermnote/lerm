# Embedded Theme Demo

Reference embedded-mode example for `lerm/admin-config`.

## What it registers

- a theme-owned options page under `Appearance`
- a post/page metabox for per-entry hero overrides
- advanced field examples for `typography`, `icon`, `accordion`, and `tabbed`

## How to use it

1. Ensure Composer autoload is available from the theme root or `packages/AdminConfig/vendor/`.
2. Require `bootstrap.php` from your theme `functions.php`.
3. Keep the package assets in `packages/AdminConfig/assets` so the embedded bootstrap can resolve CSS and JS.

Minimal include:

```php
require_once get_template_directory() . '/packages/AdminConfig/examples/embedded-theme-demo/bootstrap.php';
```

## What the bootstrap does

- boots the runtime with `EmbeddedBootstrap::boot(...)`
- registers the demo schemas
- calls `$runtime->boot()` only in wp-admin

Use it as the template for a real theme integration: copy the schema class, rename IDs and option keys, then move the fields into your own namespace.
