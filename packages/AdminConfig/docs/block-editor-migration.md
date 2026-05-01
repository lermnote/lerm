# Block Editor Migration Plan

AdminConfig should reach the block editor through the REST contract, not through
the current classic-admin DOM implementation. Phase 1 keeps the PHP rendering
stable while Phase 2 moves JavaScript source under `resources/` and carves out
client boundaries that a React/Gutenberg client can reuse.

## Target Shape

- REST API remains the only data contract for JavaScript clients.
- PHP schema compilation and storage stay server-side sources of truth.
- Classic admin screens keep working until the block-editor UI reaches feature
  parity.
- Block-editor clients consume `client_config` plus values from
  `GET /schema/{id}` and submit mutations through the same REST endpoints as
  classic admin.

## Current Client Boundaries

- `resources/core/config.js`: resolves the localized runtime config for a screen.
- `resources/core/rest-client.js`: reusable REST client built on WordPress
  `@wordpress/api-fetch`; this is the client boundary for classic admin and the
  future block-editor entry.
- `resources/admin/transport.js`: maps classic admin form actions to REST endpoints
  and owns the deprecated Ajax fallback for the `0.2.x` compatibility window.
- `resources/admin/form-state.js`: reads and compares classic form values. This is a
  temporary bridge until React state owns values directly.
- `resources/admin/admin-config.js`: classic admin mounting, field widgets, DOM
  binding, dirty tracking, and tab/subsection behavior.
- `resources/core/schema-state.js`: pure schema/value state helpers for
  hydration, local value updates, save payload serialization, and REST
  validation-error replay.
- `resources/core/context.js`: canonical context helpers for object-backed
  stores such as `post_id`, `term_id`, `user_id`, `comment_id`, and
  `network_id`.
- `resources/block-panel/index.js`: editor-panel runtime entry for Phase 2. It
  can create a runtime, load a schema, update local values, and save through
  REST. Phase 3 will attach the first React UI here.

## Phase 2 Order

1. Create a state adapter that maps `schema + values` into field state without
   reading DOM forms.
2. Move field renderers behind a registry that can mount either classic DOM
   widgets or React components.
3. Add a small block-editor package entry that imports the REST client and state
   adapter, then renders one schema in an editor sidebar or settings panel. The
   editor entry should import `resources/core/rest-client.js` directly instead
   of importing classic admin transport fallback code.
4. Keep classic admin E2E as regression coverage while adding a small editor
   smoke test for schema load, save, validation error replay, and reset.
5. Remove the Ajax fallback in `0.3.0` only after REST-only CI is consistently
   green and the block-editor entry no longer references localized Ajax keys.

## Non-Goals For Phase 1

- Do not rewrite classic field markup in React yet.
- Do not change PHP schema definitions to fit Gutenberg-specific components.
- Do not remove `admin-post.php` no-JavaScript save handling as part of Ajax
  retirement.
