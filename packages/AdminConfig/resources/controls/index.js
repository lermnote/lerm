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
 * @param {unknown} value
 * @param {Record<string, unknown>} field
 * @returns {string}
 */
const numericValue = (value, field) => {
	const current = stringValue(value);

	return current !== '' ? current : stringValue(field.default || 0);
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
		value: numericValue(value, field),
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
const RangeControl = (props) => {
	const normalized = normalizeProps(props);
	const { createElement, field, onChange, value } = normalized;
	const current = numericValue(value, field);
	const label = stringValue(field.label || field.id);
	const controlProps = {
		'aria-invalid': normalized.error ? 'true' : undefined,
		'aria-label': label,
		max: stringValue(field.max || 100),
		min: stringValue(field.min || 0),
		onChange: (event) => onChange(stringValue(changeValue(event))),
		onInput: (event) => onChange(stringValue(changeValue(event))),
		step: stringValue(field.step || 1),
		value: current,
	};

	return createElement(
		'label',
		{
			className: 'lerm-admin-config-block-panel__range',
		},
		[
			createElement('span', { key: 'label' }, label),
			createElement('input', {
				...controlProps,
				key: 'range',
				type: 'range',
			}),
			createElement('input', {
				...controlProps,
				key: 'number',
				type: 'number',
			}),
			stringValue(field.unit)
				? createElement('span', { key: 'unit' }, stringValue(field.unit))
				: null,
		].filter(Boolean)
	);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const DateControl = (props) => {
	const normalized = normalizeProps(props);
	const { createElement, field, onChange, value } = normalized;
	const label = stringValue(field.label || field.id);
	const current = field.from_to === true ? asRecord(value) : {};
	const inputProps = {
		'aria-invalid': normalized.error ? 'true' : undefined,
		type: 'date',
	};

	if (field.from_to === true) {
		const updateRange = (key, nextValue) => onChange({
			...current,
			[key]: stringValue(changeValue(nextValue)),
		});

		return createElement(
			'fieldset',
			{
				'aria-label': label,
				className: 'lerm-admin-config-block-panel__date-range',
			},
			[
				createElement('legend', { key: 'legend' }, label),
				createElement('label', { key: 'from' }, [
					createElement('span', { key: 'label' }, stringValue(field.text_from || 'From')),
					createElement('input', {
						...inputProps,
						key: 'input',
						onChange: (event) => updateRange('from', event),
						onInput: (event) => updateRange('from', event),
						value: stringValue(current.from),
					}),
				]),
				createElement('label', { key: 'to' }, [
					createElement('span', { key: 'label' }, stringValue(field.text_to || 'To')),
					createElement('input', {
						...inputProps,
						key: 'input',
						onChange: (event) => updateRange('to', event),
						onInput: (event) => updateRange('to', event),
						value: stringValue(current.to),
					}),
				]),
			]
		);
	}

	return createElement(
		'label',
		{
			className: 'lerm-admin-config-block-panel__date',
		},
		[
			createElement('span', { key: 'label' }, label),
			createElement('input', {
				...inputProps,
				'aria-label': label,
				key: 'input',
				onChange: (event) => onChange(stringValue(changeValue(event))),
				onInput: (event) => onChange(stringValue(changeValue(event))),
				value: stringValue(value),
			}),
		]
	);
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
const RadioControl = (props) => {
	const normalized = normalizeProps(props);
	const { createElement, field, onChange, value } = normalized;
	const selected = stringValue(value);
	const options = choiceOptions(field);
	const controlProps = {
		...sharedControlProps(normalized),
		'aria-invalid': normalized.error ? 'true' : undefined,
		onChange: (nextValue) => onChange(stringValue(changeValue(nextValue))),
		selected,
	};

	return createElement(
		'fieldset',
		{
			'aria-invalid': controlProps['aria-invalid'],
			'aria-label': controlProps.label,
			className: 'lerm-admin-config-block-panel__radio',
		},
		[
			createElement('legend', { key: 'legend' }, controlProps.label),
			...options.map((option) => createElement('label', { key: option.value }, [
				createElement('input', {
					checked: selected === option.value,
					key: `${ option.value }-input`,
					name: stringValue(field.id || controlProps.label),
					onChange: () => onChange(option.value),
					type: 'radio',
					value: option.value,
				}),
				` ${ option.label }`,
			])),
		]
	);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const ButtonSetControl = (props) => {
	const normalized = normalizeProps(props);
	const { components, createElement, field, onChange, value } = normalized;
	const Button = components.Button;
	const ButtonGroup = components.ButtonGroup;
	const selected = stringValue(value);
	const options = choiceOptions(field);
	const buttons = options.map((option) => {
		const isPressed = selected === option.value;
		const buttonProps = {
			'aria-pressed': isPressed ? 'true' : 'false',
			'data-value': option.value,
			key: option.value,
			onClick: () => onChange(option.value),
			type: 'button',
		};

		return typeof Button === 'function'
			? createElement(
				Button,
				{
					...buttonProps,
					isPressed,
					variant: isPressed ? 'primary' : 'secondary',
				},
				option.label
			)
			: createElement('button', buttonProps, option.label);
	});

	return createElement(
		'div',
		{
			'aria-label': stringValue(field.label || field.id),
			className: 'lerm-admin-config-block-panel__button-set',
			role: 'group',
		},
		[
			createElement('span', { key: 'label' }, stringValue(field.label || field.id)),
			typeof ButtonGroup === 'function'
				? createElement(ButtonGroup, { key: 'buttons' }, buttons)
				: createElement('div', { key: 'buttons' }, buttons),
		]
	);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const ColorControl = (props) => {
	const normalized = normalizeProps(props);
	const { createElement, field, onChange, value } = normalized;
	const current = stringValue(value || field.default || '#000000');
	const label = stringValue(field.label || field.id);

	return createElement(
		'label',
		{
			className: 'lerm-admin-config-block-panel__color',
		},
		[
			createElement('span', { key: 'label' }, label),
			createElement('input', {
				'aria-label': label,
				key: 'input',
				onChange: (event) => onChange(stringValue(changeValue(event))),
				onInput: (event) => onChange(stringValue(changeValue(event))),
				type: 'color',
				value: current,
			}),
			createElement('code', { key: 'value' }, current),
		]
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
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const CheckboxControl = (props) => {
	const normalized = normalizeProps(props);

	return choiceOptions(normalized.field).length ? CheckboxListControl(props) : ToggleControl(props);
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
	button_set: ButtonSetControl,
	checkbox: CheckboxControl,
	checkbox_list: CheckboxListControl,
	color: ColorControl,
	date: DateControl,
	number: NumberControl,
	radio: RadioControl,
	select: SelectControl,
	slug_text: TextControl,
	slider: RangeControl,
	spinner: NumberControl,
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
