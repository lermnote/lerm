# REST API

AdminConfig exposes a REST transport for JavaScript clients while keeping the PHP
schema and `OptionStore` validation path as the source of truth.

## Transport

- Namespace: `lerm-admin-config/v1`
- Auth: WordPress cookie auth with `X-WP-Nonce: wp_create_nonce( 'wp_rest' )`
- Client base URL: localized as `lermAdminConfig.restUrl`
- Client nonce: localized as `lermAdminConfig.restNonce`
- Classic admin client: `resources/admin/transport.js` uses the WordPress
  `@wordpress/api-fetch` package for REST requests.
- Legacy fallback: AdminConfig 0.3.0 removed its `admin-ajax.php` JavaScript
  transport. Clients must use REST for save, reset, import, export, and async
  data-source requests.

## Endpoints

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/schema/{id}` | Fetch client schema config and current values. |
| `GET` | `/schema/{id}/values` | Fetch current values in Ajax-compatible response shape. |
| `POST` | `/schema/{id}/save` | Save a full settings payload. |
| `POST` | `/schema/{id}/reset` | Reset a section, subsection, or all sections. |
| `GET` | `/schema/{id}/export` | Export current values as formatted JSON. |
| `POST` | `/schema/{id}/import` | Import values from a JSON snapshot. |
| `GET`/`POST` | `/schema/{id}/data-source` | Resolve async field options. |

## Payloads

Save accepts the future JSON shape:

```json
{
  "values": {
    "field_id": "value"
  }
}
```

It also accepts the current form shape keyed by the schema storage key:

```json
{
  "my_option_name": {
    "field_id": "value"
  }
}
```

Reset accepts:

- `section` or `lerm_settings_tab`
- `subsection` or `lerm_settings_subsection`
- `reset_scope`: `section`, `subsection`, `all`, or `fetch_only`

Import accepts `backup_json` or `json`.

Data-source requests accept `field_id`, `search`, `page`, `per_page`, and
`selected`. `per_page` defaults to `20` and is capped at `100`.

Object-backed stores can include context params either as top-level params or
inside `context`:

```json
{
  "context": {
    "post_id": 123
  }
}
```

Supported context keys are `post_id`, `term_id`, `user_id`, `comment_id`, and
`network_id`. Read endpoints and mutation endpoints share the same context
requirement; object-backed schema and values reads return `missing_store_context`
instead of silently falling back to defaults when the required object ID is
missing.

## Responses

`GET /schema/{id}` returns:

```json
{
  "schema": {},
  "values": {}
}
```

Mutation, values, export, import, reset, and data-source endpoints keep the
standard AdminConfig response envelope:

```json
{
  "success": true,
  "data": {}
}
```

Validation errors return `WP_Error` with HTTP status `422` and include
`fieldErrors`, `errors`, `tab`, and `subsection` in the error data. The
`fieldErrors` map is collapsed to top-level field IDs for client display, while
`errors` keeps the full dotted paths from the PHP validation layer.

REST errors use stable error codes and include `status`, `success: false`, and
`data.message` in the error data. Contract-covered codes include:

- `schema_not_found`: schema ID is missing or unregistered, `404`
- `forbidden`: current user cannot access the schema, `403`
- `missing_store_context`: object-backed store context is missing, `400`
- `invalid_import_json`: import payload is not valid JSON, `400`
- `validation_error`: save/import failed field validation, `422`

## Stable Response Shapes

These shapes are the Phase 1 contract for classic admin JavaScript and the
future block-editor client.

`GET /schema/{id}`:

```json
{
  "schema": {
    "id": "schema_id",
    "sections": []
  },
  "values": {
    "field_id": "value"
  }
}
```

Server-only authorization fields such as `capability` are intentionally omitted
from the client schema payload. Permission checks remain server-side through the
route `permission_callback`.

`GET /schema/{id}/values`:

```json
{
  "success": true,
  "data": {
    "values": {
      "field_id": "value"
    }
  }
}
```

`POST /schema/{id}/save`:

```json
{
  "success": true,
  "data": {
    "message": "Settings saved.",
    "values": {
      "field_id": "value"
    }
  }
}
```

`POST /schema/{id}/reset`:

```json
{
  "success": true,
  "data": {
    "message": "Settings reset.",
    "values": {
      "field_id": "default"
    }
  }
}
```

`GET /schema/{id}/export`:

```json
{
  "success": true,
  "data": {
    "json": "{\n    \"field_id\": \"value\"\n}"
  }
}
```

`POST /schema/{id}/import`:

```json
{
  "success": true,
  "data": {
    "message": "Settings imported.",
    "values": {
      "field_id": "value"
    }
  }
}
```

`GET` or `POST /schema/{id}/data-source`:

```json
{
  "success": true,
  "data": {
    "items": [
      {
        "value": "example",
        "label": "Example"
      }
    ],
    "more": false
  }
}
```

Validation failure:

```json
{
  "code": "validation_error",
  "message": "Validation failed.",
  "data": {
    "status": 422,
    "success": false,
    "data": {
      "message": "Validation failed.",
      "fieldErrors": {
        "field_id": "Required."
      },
      "errors": {
        "section.field_id": "Required."
      },
      "tab": "general",
      "subsection": ""
    }
  }
}
```

Client adapters should normalize direct success envelopes and `WP_Error` REST
envelopes into `{ success, data }` before updating UI state.

## Migration Notes

The REST layer is the contract for classic admin JavaScript, React-driven
options pages, and block-editor clients. Existing PHP admin pages continue to
render normally, but their enhanced save, reset, import, export, and async
data-source workflows now require the localized `restUrl` and `restNonce`.

Plugin and embedded bootstraps can own isolated `Runtime` instances. REST routes
remain global WordPress routes, so endpoint callbacks resolve the requested
schema ID across the registered runtime pool before handling reads or
mutations.

AdminConfig 0.3.0 is a breaking transport release: projects that called
AdminConfig `admin-ajax.php` actions directly must migrate to the REST routes
above. The no-JavaScript `admin-post.php` save path remains available for
classic options pages.

See `docs/ajax-retirement.md` for the completed removal checklist and migration
notes.
