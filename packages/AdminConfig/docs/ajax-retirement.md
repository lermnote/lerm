# Ajax Retirement

AdminConfig 0.3.0 removed its `admin-ajax.php` compatibility transport. The
supported JavaScript transport is now the REST namespace documented in
`docs/rest-api.md`.

## Status

- `0.2.x`: REST became the preferred JavaScript transport while legacy Ajax
  remained available as a deprecated fallback.
- `0.3.0`: AdminConfig removed its `wp_ajax_*` handlers, localized Ajax action
  keys, JavaScript fallback branches, and wp-env legacy Ajax toggle fixtures.

The no-JavaScript `admin-post.php` save path remains available for classic
options pages. It is not part of the Ajax transport and is kept for accessibility
and non-JavaScript admin usage.

## Migration

JavaScript clients must use REST:

- Base URL: localized as `lermAdminConfig.restUrl`
- Nonce: localized as `lermAdminConfig.restNonce`
- Client helper: `resources/core/rest-client.js`
- Preferred WordPress transport: `@wordpress/api-fetch`

Do not rely on these removed localized keys:

- `ajaxUrl`
- `legacyAjaxEnabled`
- `saveAction`
- `resetAction`
- `exportAction`
- `importAction`
- `dataSourceAction`
- `dataSourceNonce`

Use fixed REST endpoints instead: `/schema/{id}/save`, `/reset`, `/export`,
`/import`, and `/data-source`.

## Verification

Run the production reference audit before changing transport code:

```sh
npm run audit:ajax
```

The audit scans `src/` and `resources/` and fails if any AdminConfig legacy Ajax
transport reference returns. The dedicated REST contract smoke now runs without
flipping a legacy Ajax switch:

```sh
npm run test:wp:rest-contract
```

`npm run test:wp:rest-only` is kept as a temporary alias for downstream CI, but
it no longer disables or re-enables Ajax. New automation should use
`test:wp:rest-contract`.

## 0.3.0 Removal Checklist

- `src/WordPress/LegacyAjax.php` is deleted.
- No AdminConfig `wp_ajax_*` action is registered.
- Classic admin save, reset, import, export, and async data-source requests use
  REST only.
- Browser tests assert that AdminConfig operations do not issue
  `admin-ajax.php` requests.
- REST, classic admin, block editor panel, multisite, import/export, and async
  data-source coverage remains green.
