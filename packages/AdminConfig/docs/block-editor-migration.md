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
- Block-editor clients consume schema protocol v1 from
  `GET /schemas/{schema_id}` and submit mutations through the same REST
  endpoints as classic admin.

## Current Client Boundaries

- `resources/core/config.js`: resolves the localized runtime config for a screen.
- `resources/core/rest-client.js`: reusable REST client built on WordPress
  `@wordpress/api-fetch`; this is the client boundary for classic admin and the
  future block-editor entry.
- `resources/admin/transport.js`: maps classic admin form actions to REST
  endpoints.
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
- `resources/block-panel/index.js`: editor-panel runtime entry. It can create a
  runtime, load a schema, render basic field controls, track dirty state, save
  through REST, and replay validation errors into the panel state.

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
5. Keep the 0.3.0 REST transport contract green after removing localized
   Ajax keys and fallback code.

## Current Status

As of 2026-05-01, Phase 2 has landed on `main` through the stacked AdminConfig
PRs:

- `resources/` source layout is in place.
- `resources/block-panel/index.js` builds into `assets/build/block-panel.js`.
- The block-panel runtime can load schema data, update local values, save via
  REST, and replay validation errors into state.
- `npm run check:phase2` verifies generated assets, legacy Ajax removal, and
  JavaScript runtime contracts.

See `docs/phase-2-stabilization.md` for the mainline audit and stable-point
verification set.

## Phase 3 Entry Criteria

Phase 3 started by mounting the block-panel bundle in the editor and creating a
runtime with editor context before broadening field-control coverage. Later
Phase 3 slices can add controls only when they stay on the REST contract and keep
classic admin regression coverage green.

## Phase 3 Current Step

Phase 3 now has an editor-side editing slice for REST-safe fields. The
metabox container mounts `assets/build/block-panel.js` during
`enqueue_block_editor_assets`; the PHP boot config includes the matched schema
ID, REST root, nonce, post type, and `post_id` context. The editor entry
registers a `PluginDocumentSettingPanel`, loads the schema through REST, renders
section-aware controls, tracks dirty state, supports local discard, saves
through REST, replays validation errors, and rehydrates from the server response
after save.

Editable controls in this slice:

- `text`
- `url`
- `textarea`
- `number`
- `slider`
- `spinner`
- `date`
- `color`
- `select`
- `radio`
- `button_set`
- `slug_text`
- `switcher`
- `toggle`
- `checkbox`
- `checkbox_list`

See `docs/block-editor-field-matrix.md` for the complete
`editable` / `read-only` / `unsupported` status contract.

Acceptance for this slice:

- The block editor requests `GET /schemas/{schema_id}?post_id={post_id}` and
  `GET /schemas/{schema_id}/values?post_id={post_id}`.
- The panel can edit supported field values and save them with
  `POST /schemas/{schema_id}/values`.
- Validation failures keep the panel mounted, expose field errors, and clear the
  stale field error when the field changes.
- Local discard reverts unsaved edits to the last saved values without touching
  storage.
- Saved values persist after a block-editor reload.
- No AdminConfig `admin-ajax.php` request is made by the block-editor panel.
- Read-only and unsupported controls remain visible as field notices instead of
  silently disappearing from the panel.
- The classic metabox and options-page E2E coverage still passes.
- Advanced, structured, media, async, reset, and import/export controls remain
  outside the editable contract for this slice.

## Non-Goals For The Current Block-Panel Slice

- Do not rewrite classic field markup in React yet.
- Do not change PHP schema definitions to fit Gutenberg-specific components.
- Do not remove `admin-post.php` no-JavaScript save handling as part of Ajax
  retirement.
