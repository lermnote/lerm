# Minimal Extension Plugin

Smallest runnable example for extending `lerm/admin-config`.

Use this when you want a copyable plugin that demonstrates only the public
extension surface:

- `register_field_type()`
- `register_validator()`
- `register_data_source()`
- one schema that consumes those registrations

## What it does

- boots the runtime with `PluginBootstrap::boot(...)`
- registers a custom `badge_text` field
- validates that field with a `WP_Error`-returning validator
- resolves select choices from a named `badge_tones` data source
- mounts a single options page under `Settings`

## When to use this vs the bigger demo

- start here when you need the smallest copyable extension example
- use `examples/schema-demo-plugin/` when you want the full container matrix,
  async fields, advanced fields, and richer data-source examples
