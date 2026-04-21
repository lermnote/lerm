# Support Matrix

## Runtime Targets

- PHP: `>= 8.0`
- WordPress: current package targets modern WordPress admin screens and native capability / nonce APIs
- Modes: plugin-install mode and embedded mode

## Admin Surfaces Covered

- Options pages
- Network options pages
- Post/Page metaboxes
- Taxonomy term screens
- User profile screens
- Comment edit screens

## Built-In Field Coverage

- Core primitives
- Extended primitives and presentation fields
- Design fields
- Advanced fields
- Structured fields
- Async data-source fields
- Backup / import-export tools

## Validation and Storage Guarantees

- PHP schema remains the source of truth for defaults and sanitization
- AJAX and non-JS saves share the same store validation path
- Meta-backed reads fall back to compiled defaults when context is missing through `Runtime::all()` and `Runtime::get()`

## Current Testing Coverage

- Recursive PHP syntax checks
- JavaScript syntax checks
- Package-local unit coverage for compiler, schema helpers, registries, and example registration smoke flows

## Planned Hardening

- Broader browser regression coverage for advanced fields
- Static analysis and coding-standard checks in contributor environments
- Higher-level relationship and remote-library field packages built on the async transport layer
