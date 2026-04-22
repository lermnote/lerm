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

### Changed
- The package is now documented as an open-source runtime with a clearer contributor onboarding path and explicit support expectations.
- The extension docs, quick start guide, and smoke checklist now cover async fields and runtime debugging.
- The CI workflow now adds caching, wp-env log artifacts, and a dedicated multisite automation job on top of the PHP/integration/browser split.
