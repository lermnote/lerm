# Ajax Retirement

AdminConfig now treats `admin-ajax.php` as a deprecated compatibility fallback.
The long-term transport contract is the REST namespace documented in
`docs/rest-api.md`.

## Timeline

- `0.2.x`: REST is the preferred JavaScript transport. Legacy Ajax remains
  enabled by default for existing admin screens and emits deprecation notices
  when a fallback handler is used.
- `0.2.x`: Projects and CI can disable legacy Ajax with
  `LERM_ADMIN_CONFIG_ENABLE_LEGACY_AJAX=false` or the
  `lerm_admin_config_legacy_ajax_enabled` filter.
- `0.2.x`: CI runs a dedicated REST-only browser job for single-site and
  multisite so fallback removal cannot regress quietly.
- `0.3.0`: Remove the legacy `wp_ajax_*` handlers and JavaScript fallback code
  after REST-only rehearsals are consistently green.

## REST-Only Rehearsal

Use the package-local wp-env scripts to run the browser suite with AdminConfig
legacy Ajax disabled:

```sh
npm run test:wp:rest-only
```

This runs plugin-mode and multisite network smoke coverage with the wp-env-only
REST rehearsal gate enabled. The tests assert that AdminConfig operations use
REST routes and do not issue AdminConfig `admin-ajax.php` requests.

The GitHub Actions job `WordPress REST-only` now runs this command on every
AdminConfig push and pull request that touches the package.

## Legacy Reference Audit

Run the production legacy Ajax reference audit before adding or moving client
transport code:

```sh
npm run audit:ajax
```

The audit scans `src/` and `assets/src/` and fails when `admin-ajax.php`,
`wp_ajax_lerm_admin_config_*`, or localized Ajax fallback keys appear outside
the approved compatibility surface. Until the `0.3.0` deletion pass, approved
production references are limited to:

- `assets/src/admin-config.js`
- `assets/src/transport.js`
- `src/Framework/Admin/OptionsPage.php`
- `src/WordPress/LegacyAjax.php`
- `src/WordPress/Runtime.php`

## Removal Criteria

Before removing the fallback in `0.3.0`, all of these must be true:

- `composer ci` passes.
- `npm run check:phase1` passes.
- `npm run test:wp` passes with the default compatibility mode.
- `npm run test:wp:multisite` passes with the default compatibility mode.
- `npm run test:wp:rest-only` passes with legacy Ajax disabled.
- No package E2E test waits for an AdminConfig `admin-ajax.php` response.
- The REST API docs list stable response shapes for save, reset, import,
  export, values, and data-source operations.
- Release notes call out the removal and the required client migration path.

## Deletion Candidates

When the removal criteria are met, the following pieces can be deleted together:

- `OptionsPage::handle_ajax_save()`
- `OptionsPage::handle_ajax_reset()`
- `OptionsPage::handle_ajax_export()`
- `OptionsPage::handle_ajax_import()`
- `Runtime::handle_ajax_data_source()`
- `Runtime::handle_ajax_data_source_for_schema()`
- `LegacyAjax`
- localized fallback keys: `ajaxUrl`, `legacyAjaxEnabled`, `saveAction`,
  `resetAction`, `exportAction`, `importAction`, `dataSourceAction`, and
  `dataSourceNonce`
- JavaScript `hasLegacyAjaxTransport()` and `fetch( cfg.ajaxUrl, ... )`
  fallback branches
- legacy Ajax-specific E2E helper names and tests

## 0.3.0 Deletion Pass

Use one pull request for the removal so the compatibility surface disappears as
one reviewed unit:

- Delete `src/WordPress/LegacyAjax.php`.
- Remove every `LegacyAjax::enabled()` branch and every `add_action(
  'wp_ajax_lerm_admin_config_*', ... )` registration.
- Remove localized Ajax-only client keys from `OptionsPage::enqueue_support_assets()`.
- Remove `hasLegacyAjaxTransport()` and `requestLegacyAjax()` from
  `assets/src/transport.js`.
- Remove the REST-only fixture that disables Ajax and replace the browser test
  expectation with an assertion that no AdminConfig `admin-ajax.php` fallback
  route is registered.
- Keep `admin-post.php` no-JavaScript save handling unless a separate
  accessibility review explicitly replaces it.
- Update this file, `docs/rest-api.md`, `CHANGELOG.md`, and release notes to
  describe the removed fallback and required client migration.
