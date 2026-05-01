# Phase 2 Stabilization

Date: 2026-05-01

This stabilization pass records the current mainline state after the Phase 1
REST work and Phase 2 JavaScript runtime work landed through the stacked PRs.

## Mainline Audit

Audited merge range:

```text
724466e..06aec4c
```

AdminConfig changes in the range include:

- `resources/` JavaScript source layout with `core`, `controls`, `store`,
  `admin`, and `block-panel` boundaries.
- `assets/build/block-panel.js` and `assets/build/block-panel.asset.php`.
- Block-panel runtime helpers for schema loading, local value updates, save
  payloads, context query strings, and REST validation-error replay.
- `npm run check:phase2`, including build drift, legacy Ajax reference audit,
  and JavaScript runtime contract tests.

Non-AdminConfig files also changed in the same merge range:

- `app/Theme/AdminConfig/config/sections/appearance.php`
- `assets/dist/bundle.js`
- `assets/dist/main.css`
- `assets/resources/css/main.css`
- `assets/resources/js/components/navigation.js`
- `assets/resources/js/components/themeOptions.js`
- `template-parts/layout/site-brand.php`
- `template-parts/layout/site-nav.php`

These theme-side changes are outside the AdminConfig package boundary. They do
not affect the AdminConfig package build, REST contract, or `resources/`
runtime checks. Before the next theme release, a theme-side review should
confirm whether those files were intended to ship with the AdminConfig stack.

## Phase 2 Stable Point

The AdminConfig Phase 2 baseline is considered stable when these commands pass
from `packages/AdminConfig`:

```sh
composer ci
composer validate --strict
php tools/sync-version.php --check
npm run check:phase2
npm run test:wp:rest-only
npm run test:wp
npm run test:wp:multisite
```

Local verification on 2026-05-01 passed this full set. The wp-env coverage
included REST-only single-site, REST-only multisite, default single-site, and
default multisite runs.

Passing this set means:

- Classic admin still builds and runs from `resources/admin/index.js`.
- The build-only block-panel bundle is reproducible.
- Legacy Ajax references remain isolated to the approved compatibility surface.
- Core JavaScript runtime helpers are covered by lightweight contract tests.
- REST-only, default single-site, and multisite wp-env rehearsals remain green.

## Next Gate

Phase 3 should start only after this stable point is green on `main`. The first
Phase 3 PR should mount the block-panel script in the editor and create the
runtime with `post_id` context, but it should not migrate field controls yet.
