# REST API

AdminConfig exposes a REST transport for JavaScript clients while keeping the PHP
schema and `OptionStore` validation path as the source of truth.

## Transport

- Namespace: `lerm-admin-config/v1`
- Auth: WordPress cookie auth with `X-WP-Nonce: wp_create_nonce( 'wp_rest' )`
- Client base URL: localized as `LermAdminConfig.restUrl`
- Legacy fallback: `admin-ajax.php` remains available for older admin screens
  during the rollout, but new clients should prefer REST.

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
`selected`.

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
`network_id`.

## Responses

`GET /schema/{id}` returns:

```json
{
  "schema": {},
  "values": {}
}
```

Mutation, values, export, import, reset, and data-source endpoints keep the
legacy Ajax-compatible envelope:

```json
{
  "success": true,
  "data": {}
}
```

Validation errors return `WP_Error` with HTTP status `422` and include
`fieldErrors`, `errors`, `tab`, and `subsection` in the error data.

## Migration Notes

The REST layer is the contract for the future React and block-editor clients.
During Phase 1, existing PHP admin pages continue to render normally and use
REST only when the localized `restUrl` and `restNonce` are present. If REST is
missing or blocked, the admin JavaScript falls back to the deprecated Ajax
transport.

Plugin and embedded bootstraps can own isolated `Runtime` instances. REST routes
remain global WordPress routes, so endpoint callbacks resolve the requested
schema ID across the registered runtime pool before handling reads or
mutations. The deprecated async data-source AJAX fallback uses the same
schema-ID runtime lookup while it remains available during the rollout.
