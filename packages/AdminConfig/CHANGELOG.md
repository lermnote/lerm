# Changelog

All notable changes to `lerm/admin-config` are documented in this file.

The format follows Keep a Changelog and the package uses Semantic Versioning once the extracted package reaches its first public release.

## [Unreleased]

### Added
- REST contract browser smoke coverage that exercises save, reset, import,
  export, and async data-source requests without toggling legacy Ajax.
- Package-local test bootstrap, unit coverage, and smoke coverage for the bundled plugin and embedded examples.
- Portable CI entry points for recursive PHP syntax checks, JavaScript syntax checks, and example registration smoke tests.
- Contributor-facing release docs, support matrix, and workflow notes for the extracted package.
- Async `ajax_select` fields powered by the REST data-source registry.
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
- `@wordpress/scripts` build pipeline for the classic admin script and generated
  `assets/build` output with asset metadata.
- Initial client-side boundaries for config resolution, REST transport, and classic form-state tracking ahead of the block-editor migration.
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
- Block editor panel unsupported-control notices so advanced or structured
  fields do not silently disappear from the editor panel.
- Block editor field-status matrix and editable coverage for `date`, `slider`,
  and `spinner` fields.
- Schema protocol v1 documents at canonical `/schemas/{schema_id}` REST routes,
  plus a `/schemas` index for schemas available to the current user.
- Block editor panel media controls for `upload`, `media`, and `gallery`
  fields, including media-library selection, REST save serialization, and
  browser persistence coverage.
- Block editor panel structured controls for `fieldset`, `group`,
  `dimensions`, and `spacing`, including nested value paths and validation-error
  replay coverage.
- Block editor panel design controls for `border` and `link_color`, including
  composite color/style editing and REST persistence coverage.
- Block editor panel typography control for family, weight, style, size, unit,
  line height, letter spacing, alignment, and color values.
- Block editor panel background control for color, gradient, image, CSS choice
  values, REST serialization, and browser persistence coverage.
- Block editor panel visual choice controls for `palette`, `image_select`, and
  `icon` fields, including discard/save/reload browser coverage.
- Block editor panel async `ajax_select` controls backed by the REST
  data-source endpoint, including selected-value hydration and browser
  persistence coverage.

### Changed
- The package is now documented as an open-source runtime with a clearer contributor onboarding path and explicit support expectations.
- The extension docs, quick start guide, and smoke checklist now cover async fields and runtime debugging.
- The CI workflow now adds caching, wp-env log artifacts, and a dedicated multisite automation job on top of the PHP/integration/browser split.
- Plugin and embedded bootstraps now create isolated `Runtime` instances instead of sharing a process-wide singleton.
- CI `wp-env` jobs now wait for the WordPress login screen before fixture setup or browser checks run.
- Plugin-mode asset resolution now falls back to the package assets when an extension/demo plugin does not bundle its own `assets/` directory.
- REST routes now dispatch by schema ID across isolated runtimes instead of binding the global route table to whichever runtime registered first.
- AdminConfig 0.3.0 now uses REST as the only enhanced JavaScript transport for
  classic admin screens.
- REST contract smoke coverage replaces the legacy Ajax disable/enable rehearsal
  scripts; `test:wp:rest-only` remains a temporary alias for
  `test:wp:rest-contract`.
- The Ajax retirement plan now has documented `0.3.0` removal criteria and deletion candidates.
- Admin asset localization, asset file path resolution, and schema field
  metadata copying now use smaller shared helpers instead of page-local
  duplicate logic.
- The block editor field matrix now separates Phase 4 collection fields from
  read-only fields and is checked against JS/PHP field contracts.
- Admin pages now prefer the built `assets/build/admin-config.js` bundle while retaining a packaged browser-file fallback for source checkouts.
- The REST client path now uses WordPress `@wordpress/api-fetch` through the build dependency extraction pipeline.
- The admin script source now builds from `resources/admin/index.js`, and the
  build pipeline emits a `block-panel` bundle for the future editor panel.
- CI asset verification now runs the Phase 2 JavaScript runtime contract check
  alongside generated asset validation and legacy Ajax reference audits.
- The client schema payload now includes section metadata and client-safe field
  control metadata for the block editor panel.
- Block editor controls now normalize WordPress component change events before
  values enter client state.
- Block editor JavaScript now shares record coercion helpers across runtime,
  controls, and schema-state modules.
- Block editor discard now asks for confirmation before reverting dirty values.
- REST errors now use a single canonical nested data payload instead of
  duplicating validation metadata at two levels.
- Block editor schema state now preserves array containers when setting nested
  values by path.
- Block editor field metadata now exposes media control labels and library
  constraints through the client schema payload.
- The client schema payload now exposes nested field metadata and design-control
  flags needed by structured block-panel controls.
- Nested block editor controls now receive full dotted paths, so composite
  controls remain saveable inside `fieldset` and `group` containers.
- The client schema payload now exposes typography flags and placeholders used
  by the block editor panel.
- Block editor color/date/range controls now avoid duplicate input/change state
  updates.
- The client schema payload now exposes background flags and image picker labels
  used by the block editor panel.
- Palette fields now preserve swatch arrays in the client schema protocol
  instead of dropping non-scalar `choices` values.
- Block editor number controls now preserve an explicitly cleared empty value
  instead of redisplaying the field default before save.
- Block editor metabox schemas now skip classic metabox registration on
  Gutenberg screens so normal post saves cannot overwrite REST panel changes.
- Schema compilation now merges registered field-type client metadata into
  each field's client payload, with per-field client config still taking
  precedence.
- Classic admin and block editor panels now share the `dependency` metadata
  format for conditional field visibility.
- AdminConfig 0.3.0 removes the legacy Ajax compatibility gate and requires REST
  for enhanced JavaScript clients.
- REST clients now use canonical plural `/schemas/*` routes. Singular
  `/schema/*` routes remain compatibility aliases for 0.2.x clients.
- REST values responses now include client-safe defaults and action flags, and
  validation errors include a stable `target.section/group` pointer alongside
  classic `tab/subsection` aliases.
- Generated `assets/build/` bundles are now ignored in git and produced by
  `npm run build`; CI uploads them as build artifacts for WordPress jobs.

### Removed
- The AdminConfig `admin-ajax.php` JavaScript transport and all package
  `wp_ajax_lerm_admin_config_*` registrations.
- `src/WordPress/LegacyAjax.php` and the old options-page/data-source Ajax
  fallback handlers.
- Ajax-only localized client keys: `ajaxUrl`, `legacyAjaxEnabled`,
  `saveAction`, `resetAction`, `exportAction`, `importAction`,
  `dataSourceAction`, and `dataSourceNonce`.
- The wp-env fixtures that disabled and re-enabled legacy Ajax for rehearsals.
- The deprecated `Runtime::instance()` and test-only `Runtime::reset_instance()` singleton helpers.
- The unused legacy single-config block-panel global fallback.
