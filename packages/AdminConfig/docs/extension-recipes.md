# Extension Recipes

Use this guide when you are already comfortable registering schemas and now need
the smallest possible examples for extending the runtime.

If you want a runnable plugin instead of isolated snippets, start with
[examples/minimal-extension-plugin/README.md](/packages/AdminConfig/examples/minimal-extension-plugin/README.md).

## Recipe 1: register a minimal custom field

```php
use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\WordPress\Runtime;

$runtime->register_field_type(
	'badge_text',
	array(
		'render'   => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
			printf(
				'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text">',
				esc_attr( (string) ( $field['id'] ?? '' ) ),
				esc_attr( $field_name ),
				esc_attr( is_scalar( $value ) ? (string) $value : '' )
			);
		},
		'sanitize' => static function ( array $field, $value, bool $strict, OptionStore $store ): string {
			return sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
		},
		'client'   => array(
			'control' => 'badge_text',
		),
	)
);
```

Use this when the built-in field list is close, but not quite your shape.

## Recipe 2: add validation that surfaces inline in every container

```php
$runtime->register_validator(
	'badge_text',
	static function ( array $field, $value, bool $strict, OptionStore $store ) {
		$label = trim( is_scalar( $value ) ? (string) $value : '' );

		if ( '' === $label ) {
			return new WP_Error( 'badge_required', 'Please enter a badge label.' );
		}

		if ( strlen( $label ) < 3 ) {
			return new WP_Error( 'badge_too_short', 'Badge labels must be at least 3 characters long.' );
		}

		return $label;
	}
);
```

The important bit is that validation stays in PHP. The same error will flow
through options pages, metaboxes, profile screens, taxonomy screens, comment
screens, and network settings.

## Recipe 3: centralize reusable choices in a named data source

```php
$runtime->register_data_source(
	'badge_tones',
	static fn (): array => array(
		'neutral' => 'Neutral',
		'bold'    => 'Bold',
		'calm'    => 'Calm',
	)
);
```

Then consume it in the schema:

```php
array(
	'id'      => 'badge_tone',
	'type'    => 'select',
	'label'   => 'Badge tone',
	'choices' => $runtime->resolve_data_source( 'badge_tones' ),
	'default' => 'neutral',
)
```

This is the easiest way to keep shared lists out of your field definitions.

## Recipe 4: use the same registry for `ajax_select`

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
)
```

The same runtime capability checks and context plumbing apply automatically.

## Recipe 5: pre-enable modules when fields are assembled dynamically

If your final field list is built from multiple sources outside the schema array
you hand to `register()`, enable the modules first:

```php
$runtime->field_modules()->enable_for_field_types(
	array( 'typography', 'icon', 'accordion', 'tabbed' )
);
```

Use this only when the runtime cannot infer the needed modules from the schema
definition at registration time.

## Good defaults

- keep defaults, sanitize rules, and validation in PHP
- prefer `register_data_source()` over repeating large `choices` arrays
- use a custom type when you need a custom validator, instead of attaching a
  global validator to a broad built-in type like `text`
- start from the minimal extension plugin, then grow into
  `examples/schema-demo-plugin/` if you need async fields or more containers
