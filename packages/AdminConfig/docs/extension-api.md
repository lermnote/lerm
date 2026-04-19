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
- `defaults( string $schema_id )`
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

When a field uses callable `choices`, remember that the callable may be invoked
multiple times across rendering, sanitization, and validation. Expensive choice
builders should either resolve once before schema registration or memoize their
result:

```php
'choices' => static function (): array {
	static $choices = null;

	if ( null !== $choices ) {
		return $choices;
	}

	$choices = my_expensive_choice_builder();

	return $choices;
},
```

For reusable lists shared across multiple schemas, prefer `register_data_source()`
plus `resolve_data_source()` so the expensive lookup stays outside the field
render path.

## Containers

Custom containers can be registered through `register_container()`. The runtime supports late registration, so if a schema was already compiled before its container becomes available, the matching schemas will mount when the container is registered.

## Field modules

`field_modules()` returns the module registry used by the runtime. Besides
automatic activation through `enable_for_definition()`, the public registry now
also exposes:

- `field_types_for_definition( array $definition )`
- `modules_for_definition( array $definition )`
- `module_for_field_type( string $field_type )`
- `modules_for_field_types( array $field_types )`
- `enable_for_field_types( array $field_types )`
- `enable_all()`

That matters when field types are assembled dynamically outside the schema array
available at registration time. In those cases, pre-enable the needed modules
before you register or render the schema:

```php
$runtime->field_modules()->enable_for_field_types(
	array( 'typography', 'icon', 'accordion' )
);
```

Or, when a host intentionally wants every bundled field module available:

```php
$runtime->field_modules()->enable_all();
```

## Examples

See:

- `examples/schema-demo-plugin/`
- `examples/embedded-theme-demo/`
