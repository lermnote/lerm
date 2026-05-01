// @ts-check

/**
 * @typedef {(props: Record<string, unknown>) => unknown} FieldControl
 */

/**
 * @param {unknown} value
 * @returns {Record<string, unknown>}
 */
const asRecord = (value) => value && typeof value === 'object' && !Array.isArray(value)
	? /** @type {Record<string, unknown>} */ (value)
	: {};

/**
 * @param {unknown} value
 * @returns {Array<unknown>}
 */
const asArray = (value) => Array.isArray(value) ? value : [];

/**
 * @param {unknown} value
 * @returns {string}
 */
const stringValue = (value) => value === null || typeof value === 'undefined' ? '' : String(value);

/**
 * @param {unknown} value
 * @returns {boolean}
 */
const boolValue = (value) => ! [ false, 0, '', '0', 'false', null, undefined ].includes(
	/** @type {false|0|''|'0'|'false'|null|undefined} */ (value)
);

/**
 * @param {unknown} value
 * @returns {Record<string, unknown>}
 */
const eventTarget = (value) => {
	const record = asRecord(value);

	return asRecord(record.target || record.currentTarget || value);
};

/**
 * @param {unknown} value
 * @returns {unknown}
 */
const changeValue = (value) => {
	const target = eventTarget(value);

	return 'value' in target ? target.value : value;
};

/**
 * @param {unknown} value
 * @returns {boolean}
 */
const changeChecked = (value) => {
	const target = eventTarget(value);

	return 'checked' in target
		? boolValue(target.checked)
		: boolValue(value);
};

/**
 * @param {unknown} value
 * @param {boolean} multiple
 * @returns {string|string[]}
 */
const selectChangeValue = (value, multiple) => {
	const target = eventTarget(value);
	const selectedOptions = Array.from(
		/** @type {Iterable<{ value: string }> | undefined} */ (target.selectedOptions || [])
	);

	if (multiple && selectedOptions.length) {
		return selectedOptions.map((option) => option.value);
	}

	const changed = changeValue(value);

	return multiple ? asArray(changed).map(stringValue) : stringValue(changed);
};

/**
 * @param {Record<string, unknown>} field
 * @returns {Array<{ label: string, value: string }>}
 */
const choiceOptions = (field) => Object.entries(asRecord(field.choices)).map(([ value, label ]) => ({
	label: stringValue(label),
	value: stringValue(value),
}));

/**
 * @param {unknown} error
 * @returns {string}
 */
const errorMessage = (error) => asArray(error).length
	? asArray(error).map(stringValue).filter(Boolean).join(' ')
	: stringValue(error);

/**
 * @param {Record<string, unknown>} props
 * @returns {{
 *   components: Record<string, Function>,
 *   createElement: Function,
 *   error: string,
 *   field: Record<string, unknown>,
 *   inputId: string,
 *   onChange: Function,
 *   value: unknown
 * }}
 */
const normalizeProps = (props) => ({
	components: /** @type {Record<string, Function>} */ (asRecord(props.components)),
	createElement: /** @type {Function} */ (props.createElement),
	error: errorMessage(props.error),
	field: asRecord(props.field),
	inputId: stringValue(props.inputId),
	onChange: /** @type {Function} */ (props.onChange),
	value: props.value,
});

/**
 * @param {ReturnType<typeof normalizeProps>} props
 * @returns {Record<string, unknown>}
 */
const sharedControlProps = (props) => {
	const { error, field, inputId } = props;
	const description = stringValue(field.description);
	const label = stringValue(field.label || field.id);

	return {
		'aria-label': label,
		help: error || description,
		id: inputId,
		label,
	};
};

/**
 * @param {Record<string, unknown>} props
 * @param {string} type
 * @returns {unknown}
 */
const renderTextLikeControl = (props, type = 'text') => {
	const normalized = normalizeProps(props);
	const { components, createElement, field, onChange, value } = normalized;
	const TextControl = components.TextControl;
	const controlProps = {
		...sharedControlProps(normalized),
		'aria-invalid': normalized.error ? 'true' : undefined,
		onChange: (nextValue) => onChange(stringValue(changeValue(nextValue))),
		placeholder: stringValue(field.placeholder),
		type: stringValue(field.input_type || type),
		value: stringValue(value),
	};

	return typeof TextControl === 'function'
		? createElement(TextControl, controlProps)
		: createElement('input', {
			...controlProps,
			onInput: (event) => onChange(stringValue(changeValue(event))),
		});
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const TextControl = (props) => renderTextLikeControl(props, 'text');

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const UrlControl = (props) => renderTextLikeControl(props, 'url');

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const TextareaControl = (props) => {
	const normalized = normalizeProps(props);
	const { components, createElement, field, onChange, value } = normalized;
	const Component = components.TextareaControl;
	const controlProps = {
		...sharedControlProps(normalized),
		'aria-invalid': normalized.error ? 'true' : undefined,
		onChange: (nextValue) => onChange(stringValue(changeValue(nextValue))),
		placeholder: stringValue(field.placeholder),
		rows: Number.parseInt(stringValue(field.rows || 4), 10) || 4,
		value: stringValue(value),
	};

	return typeof Component === 'function'
		? createElement(Component, controlProps)
		: createElement('textarea', {
			...controlProps,
			onInput: (event) => onChange(stringValue(changeValue(event))),
		});
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const NumberControl = (props) => {
	const normalized = normalizeProps(props);
	const { components, createElement, field, onChange, value } = normalized;
	const TextControl = components.TextControl;
	const controlProps = {
		...sharedControlProps(normalized),
		'aria-invalid': normalized.error ? 'true' : undefined,
		max: stringValue(field.max),
		min: stringValue(field.min),
		onChange: (nextValue) => onChange(stringValue(changeValue(nextValue))),
		step: stringValue(field.step || 1),
		type: 'number',
		value: stringValue(value),
	};

	return typeof TextControl === 'function'
		? createElement(TextControl, controlProps)
		: createElement('input', {
			...controlProps,
			onInput: (event) => onChange(stringValue(changeValue(event))),
		});
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const ToggleControl = (props) => {
	const normalized = normalizeProps(props);
	const { components, createElement, onChange, value } = normalized;
	const Component = components.ToggleControl || components.CheckboxControl;
	const controlProps = {
		...sharedControlProps(normalized),
		checked: boolValue(value),
		onChange: (nextValue) => onChange(changeChecked(nextValue)),
	};

	return typeof Component === 'function'
		? createElement(Component, controlProps)
		: createElement('label', {}, [
			createElement('input', {
				checked: boolValue(value),
				key: 'input',
				onChange: (event) => onChange(changeChecked(event)),
				type: 'checkbox',
			}),
			` ${ controlProps.label }`,
		]);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const SelectControl = (props) => {
	const normalized = normalizeProps(props);
	const { components, createElement, field, onChange, value } = normalized;
	const Component = components.SelectControl;
	const multiple = field.multiple === true;
	const options = choiceOptions(field);
	const controlProps = {
		...sharedControlProps(normalized),
		'aria-invalid': normalized.error ? 'true' : undefined,
		multiple,
		onChange: (nextValue) => onChange(selectChangeValue(nextValue, multiple)),
		options,
		value: multiple ? asArray(value).map(stringValue) : stringValue(value),
	};

	return typeof Component === 'function'
		? createElement(Component, controlProps)
		: createElement(
			'select',
			{
				...controlProps,
				onChange: (event) => onChange(selectChangeValue(event, multiple)),
			},
			options.map((option) => createElement('option', { key: option.value, value: option.value }, option.label))
		);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const CheckboxListControl = (props) => {
	const normalized = normalizeProps(props);
	const { components, createElement, field, onChange, value } = normalized;
	const CheckboxControl = components.CheckboxControl;
	const selected = new Set(asArray(value).map(stringValue));
	const options = choiceOptions(field);
	const toggle = (optionValue, checked) => {
		const next = new Set(selected);

		if (checked) next.add(optionValue);
		else next.delete(optionValue);

		onChange(Array.from(next));
	};

	return createElement(
		'fieldset',
		{ className: 'lerm-admin-config-block-panel__checkbox-list' },
		[
			createElement('legend', { key: 'legend' }, stringValue(field.label || field.id)),
			...options.map((option) => {
				const checked = selected.has(option.value);

				return typeof CheckboxControl === 'function'
					? createElement(CheckboxControl, {
						checked,
						key: option.value,
						label: option.label,
						onChange: (nextValue) => toggle(option.value, changeChecked(nextValue)),
					})
					: createElement('label', { key: option.value }, [
						createElement('input', {
							checked,
							key: `${ option.value }-input`,
							onChange: (event) => toggle(option.value, changeChecked(event)),
							type: 'checkbox',
							value: option.value,
						}),
						` ${ option.label }`,
					]);
			}),
		]
	);
};

/**
 * @param {Record<string, FieldControl>} initialControls
 */
const createControlRegistry = (initialControls = {}) => {
	const controls = new Map(Object.entries(initialControls));

	return {
		/**
		 * @param {string} type
		 * @param {FieldControl} control
		 */
		register(type, control) {
			if (type && typeof control === 'function') {
				controls.set(type, control);
			}
		},

		/**
		 * @param {string} type
		 * @returns {FieldControl|null}
		 */
		get(type) {
			return controls.get(type) || null;
		},

		/**
		 * @returns {string[]}
		 */
		types() {
			return Array.from(controls.keys()).sort();
		},
	};
};

/**
 * @returns {ReturnType<typeof createControlRegistry>}
 */
const createDefaultControlRegistry = () => createControlRegistry({
	checkbox: ToggleControl,
	checkbox_list: CheckboxListControl,
	number: NumberControl,
	select: SelectControl,
	slug_text: TextControl,
	switcher: ToggleControl,
	text: TextControl,
	textarea: TextareaControl,
	toggle: ToggleControl,
	url: UrlControl,
});

module.exports = {
	createControlRegistry,
	createDefaultControlRegistry,
};
