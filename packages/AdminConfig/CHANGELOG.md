# Changelog

All notable changes to `lerm/admin-config` are documented in this file.

The format follows Keep a Changelog and the package uses Semantic Versioning once the extracted package reaches its first public release.

## [Unreleased]

### Added
- Package-local test bootstrap, unit coverage, and smoke coverage for the bundled plugin and embedded examples.
- Portable CI entry points for recursive PHP syntax checks, JavaScript syntax checks, and example registration smoke tests.
- Contributor-facing release docs, support matrix, and workflow notes for the extracted package.
- AJAX-backed `ajax_select` fields powered by the runtime data-source registry.
- Debug-mode runtime panel with schema, store, module, and data-source summaries for options pages.
- Complete plugin-mode and embedded-mode examples for async data sources.
- PHPUnit-based package test runner with dedicated unit/smoke and integration configurations.
- Real-WordPress integration coverage for bootstraps plus option/meta/site-option stores.
- `wp-env` fixture setup, embedded fixture theme, and Playwright smoke specs for plugin mode and embedded mode.
- Composer-installed WordPress stubs, PHPStan memory tuning, and package-local Node tooling for CI.
- Local Playwright overrides via environment variables plus dedicated bootstrap/example unit tests.
- Deterministic `wp-env` fixtures for classic admin screens plus Playwright smoke coverage for metabox, profile, taxonomy, and comment containers.
- Multisite `wp-env` scripts, multisite integration coverage, and network settings browser smoke coverage.
- Contributor-facing alpha release checklist for package hardening and cut verification.
- A minimal runnable extension example plugin plus focused extension recipes for custom fields, validators, and data sources.
- REST contract coverage for permission errors, missing schema/context errors, validation envelopes, import JSON failures, and isolated-runtime dispatch.
- A centralized legacy Ajax gate for removal rehearsals, covering options-page actions and async data-source fallback registration.
- REST-only wp-env rehearsal scripts and browser coverage for legacy Ajax disabled mode.
- `@wordpress/scripts` build pipeline for the classic admin script, including committed `assets/build` output and asset metadata.
- A REST-only CI job that runs single-site and multisite browser rehearsals with legacy Ajax disabled.
- Initial client-side boundaries for config resolution, REST/Ajax transport, and classic form-state tracking ahead of the block-editor migration.
- A Phase 2 `resources/` JavaScript source tree with core, controls, store,
  classic admin, and block-panel entry boundaries.
- Block-panel runtime helpers for schema loading, local value updates, save
  payloads, context query strings, and REST validation-error replay.
- Lightweight JavaScript runtime contract checks for the Phase 2 core and
  block-panel helpers.
- Section-aware block editor panel controls for basic REST-safe field types,
  including dirty tracking and REST save persistence coverage.
- Block editor panel hardening for local discard, validation-error replay,
  stale-error clearing, and select/checkbox-list persistence coverage.
- Block editor panel controls and browser coverage for simple choice and color
  fields, including `radio`, `button_set`, and `color`.

### Changed
- The package is now documented as an open-source runtime with a clearer contributor onboarding path and explicit support expectations.
- The extension docs, quick start guide, and smoke checklist now cover async fields and runtime debugging.
- The CI workflow now adds caching, wp-env log artifacts, and a dedicated multisite automation job on top of the PHP/integration/browser split.
- Plugin and embedded bootstraps now create isolated `Runtime` instances instead of sharing a process-wide singleton.
- CI `wp-env` jobs now wait for the WordPress login screen before fixture setup or browser checks run.
- Plugin-mode asset resolution now falls back to the package assets when an extension/demo plugin does not bundle its own `assets/` directory.
- REST routes now dispatch by schema ID across isolated runtimes instead of binding the global route table to whichever runtime registered first.
- The deprecated async data-source AJAX fallback now uses the same schema-ID runtime dispatch path as REST.
- Legacy `admin-ajax.php` handlers now emit WordPress deprecation notices when used, and JavaScript stops falling back to `admin-ajax.php` when the legacy gate is disabled.
- The Ajax retirement plan now has documented `0.3.0` removal criteria and deletion candidates.
- Admin pages now prefer the built `assets/build/admin-config.js` bundle while retaining the legacy unbuilt script fallback for source checkouts.
- The REST client path now uses WordPress `@wordpress/api-fetch` through the build dependency extraction pipeline.
- The admin script source now builds from `resources/admin/index.js`, and the
  build pipeline emits a `block-panel` bundle for the future editor panel.
- CI asset verification now runs the Phase 2 JavaScript runtime contract check
  alongside build drift and legacy Ajax reference audits.
- The client schema payload now includes section metadata and client-safe field
  control metadata for the block editor panel.
- Block editor controls now normalize WordPress component change events before
  values enter client state.

### Removed
- The deprecated `Runtime::instance()` and test-only `Runtime::reset_instance()` singleton helpers.
