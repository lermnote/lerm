# Extension API

`Lerm\AdminConfig\WordPress\Runtime` is the public integration surface for extending Admin Config.

If you are wiring the package into a plugin or theme for the first time, start with [quick-start.md](/packages/AdminConfig/docs/quick-start.md) and come back here when you need custom field types, stores, containers, or data sources.

For the smallest runnable example, see [examples/minimal-extension-plugin/README.md](/packages/AdminConfig/examples/minimal-extension-plugin/README.md). For copyable focused snippets, see [extension-recipes.md](/packages/AdminConfig/docs/extension-recipes.md).

## Runtime methods

- `register_field_type( string $type, array $definition = array() )`
- `register_validator( string $type, callable $validator )`
- `register_field_module( FieldModule $module )`
- `register_store_factory( string $type, callable $factory )`
- `register_container( Container $container )`
- `register_data_source( string $source_id, callable $resolver )`
- `register_many( array $schemas )`
- `has_data_source( string $source_id )`
- `resolve_data_source( string $source_id, array $args = array() )`
- `defaults( string $schema_id )`
- `is_booted()`
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

Named data sources are small runtime registries for schema helpers and async fields.

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

### Async select fields

The built-in `ajax_select` field uses the same data-source registry at request
time. The browser uses the REST data-source endpoint first; the deprecated
`admin-ajax.php` path is only a rollout fallback.

```php
$runtime->register_data_source(
	'campaign_library',
	static function ( array $args = array() ): array {
		$items = array(
			array( 'value' => 'spring-launch', 'label' => 'Spring Launch' ),
			array( 'value' => 'creator-series', 'label' => 'Creator Series' ),
			array( 'value' => 'audio-week', 'label' => 'Audio Week' ),
		);

		$search = strtolower( trim( (string) ( $args['search'] ?? '' ) ) );

		if ( '' !== $search ) {
			$items = array_values(
				array_filter(
					$items,
					static fn ( array $item ): bool => str_contains(
						strtolower( $item['label'] . ' ' . $item['value'] ),
						$search
					)
				)
			);
		}

		return array(
			'items' => $items,
			'more'  => false,
		);
	}
);
```

```php
array(
	'id'                => 'featured_campaign',
	'type'              => 'ajax_select',
	'source'            => 'campaign_library',
	'label'             => 'Featured campaign',
	'placeholder'       => 'Search campaigns...',
	'min_search_length' => 1,
	'per_page'          => 10,
	'default'           => 'spring-launch',
)
```

Resolver callbacks receive an `$args` array with these keys when the field is
queried over the async transport:

- `search`
- `page`
- `per_page`
- `selected`
- `context`
- `field`
- `schema`
- `schema_id`

`context` carries object IDs for meta-backed screens when available, such as
`post_id`, `term_id`, `user_id`, `comment_id`, or `network_id`.

Resolvers can return any of these shapes:

- associative array of `value => label`
- list of arrays like `array( 'value' => 'x', 'label' => 'X' )`
- paginated payload like `array( 'items' => ..., 'more' => true )`

The runtime normalizes all three forms to the same client payload and enforces
the owning schema/container capability before the request resolves.

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

- `examples/minimal-extension-plugin/`
- `examples/schema-demo-plugin/`
- `examples/embedded-theme-demo/`
- `docs/extension-recipes.md`
