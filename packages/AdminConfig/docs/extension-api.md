# Extension API

`Lerm\AdminConfig\WordPress\Runtime` is the public integration surface for extending Admin Config.

## Runtime methods

- `register_field_type( string $type, array $definition = array() )`
- `register_validator( string $type, callable $validator )`
- `register_field_module( FieldModule $module )`
- `register_store_factory( string $type, callable $factory )`
- `register_container( Container $container )`
- `register_data_source( string $source_id, callable $resolver )`
- `has_data_source( string $source_id )`
- `resolve_data_source( string $source_id, array $args = array() )`
- `data_sources()`
- `containers()`

## Field types

Field type definitions can provide:

- `render`
- `render_nested`
- `sanitize`
- `validate`
- `serialize`
- `client`
- `persist`

Field registrations are merged by default, so later calls can extend an existing type with extra validators or client metadata. Use `replace => true` to replace a custom type definition, or `override_builtin => true` to intentionally replace a built-in type.

## Validators

Validators receive:

```php
function ( array $field, $value, bool $strict, OptionStore $store )
```

Return the validated value on success. Returning `WP_Error` records the message in the store validation bag, aborts the current save/import request, and surfaces the error back to the active admin container.

## Data sources

Named data sources are small runtime registries for schema helpers and future async fields.

Typical use today:

```php
$runtime->register_data_source(
	'tone_presets',
	static fn (): array => array(
		'calm'  => 'Calm',
		'bold'  => 'Bold',
		'clean' => 'Clean',
	)
);

$choices = $runtime->resolve_data_source( 'tone_presets' );
```

That resolved payload can be injected into schema `choices`, custom field renderers, or other registration-time helpers.

## Containers

Custom containers can be registered through `register_container()`. The runtime supports late registration, so if a schema was already compiled before its container becomes available, the matching schemas will mount when the container is registered.

## Examples

See:

- `examples/schema-demo-plugin/`
- `examples/embedded-theme-demo/`
