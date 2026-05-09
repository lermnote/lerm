# Block Editor Field Matrix

Date: 2026-05-02

This matrix defines the Phase 3 block-panel contract for AdminConfig field
types. The block editor is not the limiting factor; the status describes how far
the AdminConfig block-panel runtime has migrated each field family.

Metabox and post-meta schema fields use custom Panel UI as the default block
editor surface. `InnerBlocks` remains reserved for future content-structure
features where values belong in block content rather than AdminConfig storage.

## Status Definitions

- `editable`: rendered in the block panel, updates client state, participates in
  dirty/discard/save, persists through REST, and is covered by browser tests.
- `read-only`: visible in the block panel as a non-editable notice. The schema
  field is acknowledged, but editing remains in the classic admin UI until the
  required React/state infrastructure lands.
- `unsupported`: not part of the Phase 3 capability contract. Unknown custom
  controls fall here unless an extension registers a block-panel control.

## Editable

| Field type | Notes |
| --- | --- |
| `text` | Scalar text input. |
| `url` | Scalar URL input. |
| `textarea` | Scalar multiline text input. |
| `number` | Scalar numeric input. |
| `slider` | Scalar numeric range input. |
| `spinner` | Scalar numeric input using the same state contract as `number`. |
| `date` | Scalar dates and simple `from_to` date ranges. |
| `color` | Hex color input. |
| `select` | Single and multiple choice values. |
| `radio` | Single choice values. |
| `button_set` | Single choice values. |
| `checkbox` | Boolean checkbox or choice-backed checkbox list. |
| `checkbox_list` | Multiple choice values. |
| `switcher` | Boolean toggle. |
| `toggle` | Boolean toggle alias. |
| `slug_text` | Custom scalar text control from the demo extension. |
| `upload` | URL-backed media-library selection. |
| `media` | Single attachment selection saved through REST by attachment ID. |
| `gallery` | Ordered attachment ID list with media-library selection. |
| `fieldset` | Nested object editing with child controls and dotted validation paths. |
| `group` | Repeatable object list editing with child controls. |
| `dimensions` | Width/height numeric object editing with unit selection. |
| `spacing` | Box-side numeric object editing with unit selection. |
| `border` | Box-side border width, style, and color object editing. |
| `link_color` | Normal/hover link color object editing. |
| `typography` | Typography object editing for family, weight, style, size, unit, line height, letter spacing, alignment, and color. |
| `background` | Background color, gradient, image, position, repeat, attachment, size, origin, clip, and blend-mode object editing. |
| `palette` | Swatch-backed palette choice editing. |
| `image_select` | Image-backed single choice editing. |
| `icon` | Dashicons choice editing. |

## Read-Only

| Field type | Reason |
| --- | --- |
| `heading`, `subheading`, `content`, `notice` | Presentation-only fields; no persisted value should enter the save payload. |
| `accordion`, `tabbed`, `sorter` | Needs structured collection state and ordering semantics. |
| `code_editor`, `wp_editor` | Needs editor-specific component integration and sanitization-aware UX. |
| `ajax_select` | Needs REST data-source search, pagination, and async selection UX in the block panel. |
| `backup_tools` | Options-page utility action; not suitable for block-panel editing. |

## Unsupported

Unknown field types or extension controls are shown as unsupported notices until
the extension registers a block-panel control or AdminConfig adds a first-party
mapping.

## Phase 3 Completion Boundary

Phase 3 is complete when:

- All built-in field families are classified in this matrix.
- Editable fields have browser coverage for render, dirty/discard/save, reload,
  validation-error replay, and no AdminConfig `admin-ajax.php` requests.
- Read-only and unsupported fields are visible in the panel instead of silently
  disappearing.
- Complex fields remain out of the editable contract until their specific state
  and UI infrastructure is implemented in later slices.
