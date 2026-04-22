# Contributing

Thanks for helping improve `lerm/admin-config`.

## Development Flow

1. Make focused changes inside `src/`, `assets/`, `docs/`, `examples/`, or `tests/`.
2. Run the package checks from the package root:

```bash
composer validate --strict
composer ci
```

3. When you touch container mounting, backends, or admin flows, also run one of:

```bash
composer test:integration
npm run test:integration
npm run test:e2e
```

- `composer test:integration` works against an already-available WordPress install
- `npm run test:integration` and `npm run test:e2e` use `wp-env` and therefore need Docker

4. Update docs or examples whenever the public API changes.
5. Add or extend tests for compiler behavior, runtime contracts, bundled example registrations, or WordPress integration flows as appropriate.

## Pull Request Expectations

- Keep changes scoped to the package.
- Prefer schema-driven behavior over special-case theme logic.
- Preserve PHP as the source of truth for defaults, validation, and capability checks.
- Document new field types, store adapters, or extension points in `README.md` and `docs/`.

## Coding Notes

- Follow the existing package style and keep comments short.
- Prefer extending registries and modules over adding hard-coded branching.
- For async or client-side behavior, keep the authoritative rules in PHP and treat JavaScript as transport and UI.

## Release Hygiene

- Update `CHANGELOG.md` for user-visible changes.
- Reflect new compatibility guarantees in `docs/support-matrix.md`.
- Keep examples runnable and aligned with the public onboarding flow.
- Keep `.wp-env.json`, `tests/fixtures/wp-env/`, and Playwright smoke coverage aligned with the example plugin and embedded fixture theme.
