# Release Checklist

Use this checklist before cutting an alpha, beta, or stable package tag.

## Local Quality Gate

Run from the package root:

```bash
composer validate --strict
composer ci
npm ci
npm run check
composer test:integration
```

When Docker is available, also run:

```bash
npm run test:integration
npm run test:e2e
npm run test:wp:multisite
npm run test:wp:rest-contract
npm run test:e2e:block-editor
```

## Manual Sanity Pass

- Confirm the example plugin still boots in plugin-install mode.
- Confirm the embedded fixture theme still boots in embedded mode.
- Confirm WordPress loads `assets/build/admin-config.js` and falls back only
  when the built asset is intentionally absent in a source checkout.
- Confirm the release archive or GitHub Release attachment includes
  `assets/build/admin-config.js`, `assets/build/admin-config.asset.php`,
  `assets/build/block-panel.js`, and `assets/build/block-panel.asset.php`.
- Confirm the built asset metadata includes `wp-api-fetch` after transport
  changes.
- Confirm `npm run test:js-runtime` covers block-panel runtime load/save
  behavior when front-end runtime helpers change.
- Check options-page global save across multiple sections.
- Check reset current page and reset all tabs.
- Check import/export on the schema demo plugin.
- Check one validation failure path each for metabox, profile, taxonomy, comment, and network settings.
- Check at least one async field, one typography field, one accordion field, and one tabbed field.
- Confirm browser traces contain no AdminConfig `admin-ajax.php` requests.
- Confirm schema protocol examples still match `GET /schemas`,
  `GET /schemas/{schema_id}`, and `GET|POST /schemas/{schema_id}/values`.

## Docs and Examples

- Update `README.md` when public behavior, scripts, or support expectations change.
- Update `docs/support-matrix.md` when CI coverage or compatibility guarantees change.
- Update `CHANGELOG.md` with user-visible behavior changes.
- Keep `examples/schema-demo-plugin/` and `examples/embedded-theme-demo/` aligned with the supported onboarding path.

## Release Notes

- Mark the release channel clearly (`alpha`, `beta`, or `stable`).
- Call out any schema-facing or runtime-facing breaking changes.
- Link migration guidance when behavior or naming changed.
- Mention known limitations that remain intentionally out of scope for the release.
- For 0.3.0 and later, call out that AdminConfig `admin-ajax.php` transport
  actions were removed and JavaScript clients must use REST.
- For schema protocol changes, call out the protocol version, route aliases,
  and any field payload additions.
- For source releases, call out that contributors must run `npm ci` and
  `npm run build` before using block-editor panel assets locally.
