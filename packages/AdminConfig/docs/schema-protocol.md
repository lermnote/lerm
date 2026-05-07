# Schema Protocol

Schema protocol v1 is the stable client payload returned by
`GET /wp-json/lerm-admin-config/v1/schemas/{schema_id}`. It is designed for the
classic admin bridge, React admin page, block editor panel, and future editor
surfaces to consume the same server-owned schema definition.

PHP remains responsible for schema registration, permissions, storage,
validation, and data-source resolution. JavaScript clients render and edit only
the client-safe fields exposed in this protocol.

## Document Shape

```json
{
  "protocolVersion": 1,
  "id": "site_settings",
  "schemaId": "site_settings",
  "title": "Site Settings",
  "description": "",
  "container": {
    "type": "options_page",
    "surface": "admin",
    "context": {
      "kind": "site"
    }
  },
  "store": {
    "type": "option",
    "scope": "site",
    "key": "site_settings"
  },
  "actions": {
    "read": true,
    "edit": true,
    "reset": true,
    "export": true,
    "import": true,
    "dataSource": true
  },
  "defaults": {},
  "sections": {},
  "fields": {},
  "dependencies": {},
  "optionName": "site_settings",
  "links": {
    "self": "https://example.test/wp-json/lerm-admin-config/v1/schemas/site_settings",
    "values": "https://example.test/wp-json/lerm-admin-config/v1/schemas/site_settings/values"
  }
}
```

`id` and `schemaId` are both present during the v1 transition. New JavaScript
should prefer `schemaId`; `id` remains useful for concise list rendering and
compatibility with existing compiled config.

Server-only authorization details such as `capability` are intentionally
omitted. Permission decisions are exposed only as action flags and enforced on
each REST route server-side.

## Action Flags

`actions` reports what the current request context can do:

```json
{
  "read": true,
  "edit": true,
  "reset": true,
  "export": true,
  "import": true,
  "dataSource": true
}
```

These flags are hints for UI state. Clients may hide or disable controls from
them, but every operation must still handle a server-side `forbidden` error.

## Container And Store

`container.surface` groups where a schema naturally belongs:

- `admin`: regular wp-admin screens.
- `network-admin`: multisite network admin screens.
- `block-editor`: post editor sidebar/panel surfaces.

`container.context.kind` tells the client which object context is needed:

- `site`
- `network`
- `post`
- `term`
- `user`
- `comment`

`store.scope` tells the client whether values are site-wide, network-wide,
object-backed, or in-memory:

- `site`
- `network`
- `object`
- `memory`

## Field Payload

Each field is keyed by field ID in `fields`:

```json
{
  "site_logo": {
    "id": "site_logo",
    "path": "site_logo",
    "type": "media",
    "control": "media",
    "label": "Logo",
    "description": "",
    "default": null,
    "section": "appearance",
    "group": "brand",
    "choices": [],
    "dependency": null,
    "multiple": false,
    "readOnly": true,
    "supported": true,
    "ui": {},
    "client": {}
  }
}
```

Stable field keys:

- `id`: field identifier from PHP schema.
- `path`: storage path used by JavaScript state and save payloads.
- `type`: PHP field type.
- `control`: preferred client control name. Field-level `client.control`
  overrides registered field-type metadata; otherwise it falls back to `type`.
- `label` and `description`: display text.
- `default`: client-safe default value.
- `section` and `group`: field location for tabs, panels, and validation
  targeting.
- `choices`: normalized client-safe choices when present.
- `dependency`: compiled dependency metadata for conditional visibility.
- `multiple`: whether the field accepts multiple values.
- `readOnly`: the field is visible but not editable on the current client
  surface.
- `supported`: the protocol can describe the field. A client without a matching
  local control may still render it as unsupported.
- `ui`: client-safe UI metadata.
- `client`: client-safe field-type or field-level metadata.

Selected scalar schema keys are copied through when present and scalar:

- `source`
- `data_source`
- `input_type`
- `placeholder`
- `rows`
- `min`
- `max`
- `step`

## Read-Only And Unsupported

`readOnly` means the server can describe the field, but the current client slice
does not edit it yet. Block editor panels render read-only field notices for
advanced, media, structured, async, and layout controls until dedicated React
controls exist.

`supported: false` is reserved for payloads a client should not try to render as
an editable field. Most built-in fields currently use `supported: true`; local
client registries still decide whether a control is editable, read-only, or
unsupported in that specific surface.

## Sections And Dependencies

`sections` is the client-safe compiled section structure used by classic admin
and editor clients for grouping fields.

`dependencies` is the compiled dependency graph. New clients should evaluate
this graph instead of re-reading classic `dependency_field` markup. The old
schema-level `dependency_field` format is treated as legacy input and should not
be emitted as a client protocol primitive.

## Values

Values are intentionally separate from the schema document:

- `GET /schemas/{schema_id}` returns shape and defaults.
- `GET /schemas/{schema_id}/values` returns current values.
- `POST /schemas/{schema_id}/values` saves current values and returns the
  stored result.

Keeping values separate lets editor surfaces refresh values without refetching
the full schema document, and lets future clients cache schema documents more
aggressively.
