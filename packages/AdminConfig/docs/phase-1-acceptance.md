# Phase 1 Acceptance

Phase 1 completed when REST became the primary JavaScript contract, the legacy
Ajax fallback was isolated behind one removal gate, and the build pipeline
produced a reusable client boundary for the future block-editor UI. AdminConfig
0.3.0 has since removed that fallback.

## Scope

- REST owns all JavaScript settings operations: schema, values, save, reset,
  import, export, and async data-source requests.
- PHP schema compilation, storage resolution, validation, and permissions remain
  the source of truth.
- Classic admin screens keep working through REST-enhanced JavaScript and the
  no-JavaScript `admin-post.php` save path.
- Block-editor work starts from the shared REST client, not from classic-admin
  DOM form parsing.

## Acceptance Checklist

- REST routes return stable response shapes documented in `docs/rest-api.md`.
- REST permissions remain server-side and `client_config` omits server-only
  authorization keys such as `capability`.
- Object-backed stores return `missing_store_context` from read and mutation
  endpoints when required context is missing.
- Data-source pagination defaults to `20` and is capped at `100`.
- Multiple `Runtime` instances can register schemas without route callbacks
  leaking state across runtimes or tests.
- AdminConfig no longer registers package `wp_ajax_*` handlers.
- REST contract browser smoke passes for single-site and multisite.
- `@wordpress/scripts` builds `resources/admin/index.js` into
  `assets/build/admin-config.js` with `wp-api-fetch` listed in the asset
  metadata.
- `resources/core/rest-client.js` is the reusable REST client boundary for classic
  admin and the future block-editor entry.
- `npm run audit:ajax` passes and rejects production legacy Ajax references.

## Required Local Commands

Run from `packages/AdminConfig`:

```sh
composer validate --strict
php tools/sync-version.php --check
composer ci
npm run check:phase1
npm run test:wp:rest-contract
```

When touching classic admin behavior, also run:

```sh
npm run test:wp
npm run test:wp:multisite
```

## Exit State

After Phase 1, new JavaScript clients must use REST through
`resources/core/rest-client.js` or a small wrapper around it. New production code
must not call `admin-ajax.php` directly. AdminConfig 0.3.0 removed the remaining
Ajax fallback.
