# REST API

AdminConfig exposes a REST transport for JavaScript clients while keeping the
PHP schema, permissions, storage, and validation path as the source of truth.

## Transport

- Namespace: `lerm-admin-config/v1`
- Auth: WordPress cookie auth with `X-WP-Nonce: wp_create_nonce( 'wp_rest' )`
- Client base URL: localized as `lermAdminConfig.restUrl`
- Client nonce: localized as `lermAdminConfig.restNonce`
- Classic admin client: `resources/admin/transport.js` uses the WordPress
  `@wordpress/api-fetch` package for REST requests.
- Block editor client: `resources/block-panel/index.js` reads the schema
  protocol document and values through the same REST namespace.
- Legacy fallback: AdminConfig 0.3.0 removed its `admin-ajax.php` JavaScript
  transport. Clients must use REST for save, reset, import, export, and async
  data-source requests.

## Canonical Routes

`/schemas/*` is the canonical route family.

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/schemas` | List schemas available to the current user. |
| `GET` | `/schemas/{schema_id}` | Fetch the schema protocol v1 document. |
| `GET` | `/schemas/{schema_id}/values` | Fetch current values and defaults. |
| `POST` | `/schemas/{schema_id}/values` | Save a full settings payload. |
| `POST` | `/schemas/{schema_id}/reset` | Reset a section, group, or all values. |
| `GET` | `/schemas/{schema_id}/export` | Export current values as formatted JSON. |
| `POST` | `/schemas/{schema_id}/import` | Import values from a JSON snapshot. |
| `GET`/`POST` | `/schemas/{schema_id}/data-source` | Resolve async field options. |

## Payloads

Save accepts the JSON shape used by React and block-editor clients:

```json
{
  "values": {
    "field_id": "value"
  }
}
```

It also accepts the classic form shape keyed by the schema storage key:

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

## Success Responses

All canonical routes use the AdminConfig success envelope:

```json
{
  "success": true,
  "data": {}
}
```

`GET /schemas`:

```json
{
  "success": true,
  "data": {
    "schemas": [
      {
        "id": "site_settings",
        "title": "Site Settings",
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
        }
      }
    ]
  }
}
```

`GET /schemas/{schema_id}` returns the schema protocol v1 document. See
`docs/schema-protocol.md` for the field payload contract.

```json
{
  "success": true,
  "data": {
    "protocolVersion": 1,
    "id": "site_settings",
    "schemaId": "site_settings",
    "title": "Site Settings",
    "sections": {},
    "fields": {},
    "defaults": {},
    "dependencies": {},
    "actions": {
      "read": true,
      "edit": true,
      "reset": true,
      "export": true,
      "import": true,
      "dataSource": true
    }
  }
}
```

`GET /schemas/{schema_id}/values`:

```json
{
  "success": true,
  "data": {
    "schemaId": "site_settings",
    "values": {
      "field_id": "value"
    },
    "defaults": {
      "field_id": "default"
    }
  }
}
```

`POST /schemas/{schema_id}/values`:

```json
{
  "success": true,
  "data": {
    "message": "Settings saved.",
    "schemaId": "site_settings",
    "values": {
      "field_id": "value"
    }
  }
}
```

`POST /schemas/{schema_id}/reset`:

```json
{
  "success": true,
  "data": {
    "message": "This section has been reset to defaults.",
    "scope": "section",
    "schemaId": "site_settings",
    "values": {
      "field_id": "default"
    }
  }
}
```

`GET /schemas/{schema_id}/export`:

```json
{
  "success": true,
  "data": {
    "message": "Current settings snapshot generated.",
    "json": "{\n    \"field_id\": \"value\"\n}"
  }
}
```

`POST /schemas/{schema_id}/import`:

```json
{
  "success": true,
  "data": {
    "message": "Settings imported successfully.",
    "schemaId": "site_settings",
    "values": {
      "field_id": "value"
    }
  }
}
```

`GET` or `POST /schemas/{schema_id}/data-source`:

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

## Error Responses

REST errors use stable error codes and include `status`, `success: false`, and
`data.message` in the `WP_Error` data payload. Contract-covered codes include:

- `schema_not_found`: schema ID is missing or unregistered, `404`
- `forbidden`: current user cannot access the schema, `403`
- `missing_store_context`: object-backed store context is missing, `400`
- `invalid_import_json`: import payload is not valid JSON, `400`
- `import_payload_too_large`: import payload exceeds the 1 MB limit, `413`
- `validation_error`: save/import failed field validation, `422`
- `data_source_error`: data-source callback raised an exception, `500`

Validation errors include:

- `fieldErrors`: collapsed top-level field ID to message map for client display.
- `errors`: full validation path to message list map from the PHP validation
  layer.
- `target`: stable `{ section, group }` pointer for React clients.
- `tab` and `subsection`: compatibility aliases for classic admin screens.

Validation failure:

```json
{
  "code": "validation_error",
  "message": "Please review the highlighted fields and try again.",
  "data": {
    "status": 422,
    "success": false,
    "data": {
      "message": "Please review the highlighted fields and try again.",
      "fieldErrors": {
        "field_id": "Required."
      },
      "errors": {
        "section.field_id": [
          "Required."
        ]
      },
      "target": {
        "section": "general",
        "group": ""
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

AdminConfig 0.3.0 removed its `admin-ajax.php` transport. JavaScript clients
must use the REST routes documented here.
