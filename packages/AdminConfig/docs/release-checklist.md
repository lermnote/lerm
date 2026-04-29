# Release Checklist

Use this checklist before cutting an alpha, beta, or stable package tag.

## Local Quality Gate

Run from the package root:

```bash
composer validate --strict
composer ci
npm ci
npm run build:check
composer test:integration
```

When Docker is available, also run:

```bash
npm run test:integration
npm run test:e2e
npm run test:wp:multisite
npm run test:wp:rest-only
```

## Manual Sanity Pass

- Confirm the example plugin still boots in plugin-install mode.
- Confirm the embedded fixture theme still boots in embedded mode.
- Confirm WordPress loads `assets/build/admin-config.js` and falls back only
  when the built asset is intentionally absent in a source checkout.
- Confirm the built asset metadata includes `wp-api-fetch` after transport
  changes.
- Check options-page global save across multiple sections.
- Check reset current page and reset all tabs.
- Check import/export on the schema demo plugin.
- Check one validation failure path each for metabox, profile, taxonomy, comment, and network settings.
- Check at least one async field, one typography field, one accordion field, and one tabbed field.

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
