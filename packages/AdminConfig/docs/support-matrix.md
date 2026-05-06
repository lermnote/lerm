# Support Matrix

## Runtime Targets

- PHP: `>= 8.0`
- WordPress: modern admin screens plus native capability / nonce APIs
- Modes: plugin-install mode and embedded mode
- Runtime lifecycle: one isolated `Runtime` per bootstrap call; no process-wide runtime singleton

## Automated Matrix

- PHP quality gate: PHP `8.0`, `8.1`, `8.2`, `8.3`
- Asset build gate: `@wordpress/scripts` compiles `resources/admin/index.js` and `resources/block-panel/index.js`, verifies committed `assets/build` output, and runs the legacy Ajax removal audit
- WordPress integration: default stable `wp-env` environment on PHP `8.2`
- WordPress multisite automation: dedicated `wp-env` run on ports `8890/8891`
- Browser smoke coverage: Playwright against plugin mode, embedded mode, classic admin containers, and multisite network settings in `wp-env`
- REST contract browser coverage: Playwright single-site and multisite smoke
  runs exercise REST save, import, export, reset, and async data-source paths
- Local browser smoke coverage can target an existing site through `LERM_ADMIN_CONFIG_BASE_URL` plus admin credentials
- REST contract browser smoke coverage asserts that package actions avoid
  AdminConfig `admin-ajax.php` requests

## Admin Surfaces Covered

- Options pages
- Network options pages
- Post/Page metaboxes
- Taxonomy term screens
- User profile screens
- Comment edit screens
- Block editor document settings panel for post/page metabox schemas

## Package-Level Built-In Field Coverage

- Core primitives
- Extended primitives and presentation fields
- Design fields
- Advanced fields
- Structured fields
- Async data-source fields
- Backup / import-export tools

## Validation and Storage Guarantees

- PHP schema remains the source of truth for defaults and sanitization
- REST endpoints expose schema/value reads plus save, reset, import/export, and async data-source operations
- REST and non-JS saves share the same store validation path
- Meta-backed runtime reads fall back to compiled defaults when context is missing through `Runtime::all()` and `Runtime::get()`; REST read endpoints return `missing_store_context` so JavaScript clients cannot silently hydrate against the wrong object
- `admin-post.php` remains the supported no-JavaScript fallback for options pages
- AdminConfig 0.3.0 removed its `admin-ajax.php` JavaScript transport; enhanced
  admin clients use REST while `admin-post.php` remains the no-JavaScript save
  path

## Current Testing Coverage

- Recursive PHP syntax checks
- JavaScript syntax checks
- JavaScript runtime contract checks for core schema state, context, error,
  default controls, dirty tracking, and block-panel REST orchestration helpers
- Reproducible admin script build checks
- Legacy Ajax removal audit through `npm run audit:ajax`
- Built asset dependency extraction for `wp-api-fetch`
- WPCS and PHPStan gates
- PHPUnit unit coverage for compiler, schema helpers, registries, and diagnostics
- PHPUnit smoke coverage for the bundled plugin and embedded examples
- Real-WordPress integration tests for bootstraps, option/meta/site-option stores, and multisite network schema persistence
- Playwright smoke coverage for plugin/embedded options pages, classic metabox/profile/taxonomy/comment screens, and the multisite network settings page
- Playwright smoke coverage for block editor panel schema loading, primitive
  editing, choice/color controls, read-only/unsupported-control notices, local
  discard, validation-error replay, REST save persistence with `post_id`
  context, and no AdminConfig `admin-ajax.php` requests
- Block editor field-status matrix in `docs/block-editor-field-matrix.md`
- REST contract Playwright coverage for plugin-mode actions and multisite network settings
- Phase 2 mainline stabilization notes in `docs/phase-2-stabilization.md`

## Release Policy

- `0.x` is the extraction and hardening phase; expect fast iteration and occasional API tightening
- `1.0.0` starts the SemVer guarantee for public runtime, schema, and extension APIs
- WordPress remains a runtime dependency rather than a Composer hard dependency

## Planned Hardening

- Broader browser regression coverage for richer advanced-field permutations and import/reset edge cases
- A lighter no-Docker browser harness for contributors who cannot run `wp-env`
- Higher-level relationship and remote-library field packages built on the async transport layer
- Broader REST contract coverage for richer advanced-field permutations and import/reset edge cases
