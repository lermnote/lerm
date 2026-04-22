# Support Matrix

## Runtime Targets

- PHP: `>= 8.0`
- WordPress: modern admin screens plus native capability / nonce APIs
- Modes: plugin-install mode and embedded mode

## Automated Matrix

- PHP quality gate: PHP `8.0`, `8.1`, `8.2`, `8.3`
- WordPress integration: default stable `wp-env` environment on PHP `8.2`
- Browser smoke coverage: Playwright against plugin mode and embedded mode in `wp-env`

## Admin Surfaces Covered

- Options pages
- Network options pages
- Post/Page metaboxes
- Taxonomy term screens
- User profile screens
- Comment edit screens

## Built-In Field Coverage

- Core primitives
- Extended primitives and presentation fields
- Design fields
- Advanced fields
- Structured fields
- Async data-source fields
- Backup / import-export tools

## Validation and Storage Guarantees

- PHP schema remains the source of truth for defaults and sanitization
- AJAX and non-JS saves share the same store validation path
- Meta-backed reads fall back to compiled defaults when context is missing through `Runtime::all()` and `Runtime::get()`

## Current Testing Coverage

- Recursive PHP syntax checks
- JavaScript syntax checks
- WPCS and PHPStan gates
- PHPUnit unit coverage for compiler, schema helpers, registries, and diagnostics
- PHPUnit smoke coverage for the bundled plugin and embedded examples
- Real-WordPress integration tests for bootstraps and option/meta/site-option stores
- Playwright smoke coverage for plugin-mode and embedded-mode options pages

## Release Policy

- `0.x` is the extraction and hardening phase; expect fast iteration and occasional API tightening
- `1.0.0` starts the SemVer guarantee for public runtime, schema, and extension APIs
- WordPress remains a runtime dependency rather than a Composer hard dependency

## Planned Hardening

- Broader browser regression coverage for advanced fields and validation failure UX
- Multisite-specific automation for `network_options_page`
- Higher-level relationship and remote-library field packages built on the async transport layer
