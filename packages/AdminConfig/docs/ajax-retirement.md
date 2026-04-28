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

## Removal Criteria

Before removing the fallback in `0.3.0`, all of these must be true:

- `composer ci` passes.
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
