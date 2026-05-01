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
  REST. Phase 3 attaches the first editor-only status panel here without
  migrating field controls yet.

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

## Current Status

As of 2026-05-01, Phase 2 has landed on `main` through the stacked AdminConfig
PRs:

- `resources/` source layout is in place.
- `resources/block-panel/index.js` builds into `assets/build/block-panel.js`.
- The block-panel runtime can load schema data, update local values, save via
  REST, and replay validation errors into state.
- `npm run check:phase2` verifies build drift, legacy Ajax references, and
  JavaScript runtime contracts.

See `docs/phase-2-stabilization.md` for the mainline audit and stable-point
verification set.

## Phase 3 Entry Criteria

The first Phase 3 PR should only mount the block-panel bundle in the editor and
create a runtime with editor context. It should not migrate the full field UI
yet.

## Phase 3 Current Step

The first Phase 3 slice mounts `assets/build/block-panel.js` from the metabox
container during `enqueue_block_editor_assets`. The PHP boot config includes the
matched schema ID, REST root, nonce, post type, and `post_id` context. The
editor entry registers a `PluginDocumentSettingPanel`, loads the schema through
REST, and exposes a ready/error status only.

Acceptance for this slice:

- The block editor requests `GET /schema/{id}?post_id={post_id}`.
- No AdminConfig `admin-ajax.php` request is made by the block-editor panel.
- The classic metabox and options-page E2E coverage still passes.
- Field controls, save buttons, reset, and import/export stay out of this slice.

## Non-Goals For Phase 1

- Do not rewrite classic field markup in React yet.
- Do not change PHP schema definitions to fit Gutenberg-specific components.
- Do not remove `admin-post.php` no-JavaScript save handling as part of Ajax
  retirement.
