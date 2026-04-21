# Contributing

Thanks for helping improve `lerm/admin-config`.

## Development Flow

1. Make focused changes inside `src/`, `assets/`, `docs/`, `examples/`, or `tests/`.
2. Run the package checks from the package root:

```bash
composer validate --strict
php tools/lint-php.php
node --check assets/admin-config.js
php tests/run.php
```

3. Update docs or examples whenever the public API changes.
4. Add or extend tests for compiler behavior, runtime contracts, or bundled example registrations.

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
