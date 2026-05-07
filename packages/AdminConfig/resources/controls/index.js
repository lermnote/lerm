// @ts-check

const { asRecord } = require('../core/records');

/**
 * @typedef {(props: Record<string, unknown>) => unknown} FieldControl
 */

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
const boolValue = (value) => value !== 'false' && value !== '0' && !!value;

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

	if (value === '') {
		return '';
	}

	return current !== '' ? current : stringValue(field.default || 0);
};

/**
 * @param {unknown} value
 * @returns {number}
 */
const positiveInteger = (value) => {
	const parsed = Number.parseInt(stringValue(value), 10);

	return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
};

/**
 * @param {unknown} value
 * @returns {number}
 */
const attachmentId = (value) => {
	if (Array.isArray(value)) {
		return attachmentId(value[0]);
	}

	const record = asRecord(value);

	return positiveInteger(record.id || record.ID || value);
};

/**
 * @param {unknown} value
 * @returns {Array<unknown>}
 */
const selectionItems = (value) => {
	if (Array.isArray(value)) {
		return value;
	}

	const record = asRecord(value);

	if (Array.isArray(record.models)) {
		return record.models.map((model) => {
			const modelRecord = asRecord(model);

			return typeof modelRecord.toJSON === 'function' ? modelRecord.toJSON() : model;
		});
	}

	if (typeof record.toJSON === 'function') {
		const json = record.toJSON();

		return Array.isArray(json) ? json : [ json ];
	}

	return Object.keys(record).length ? [ record ] : [];
};

/**
 * @param {unknown} value
 * @returns {Array<number>}
 */
const galleryIds = (value) => {
	let candidates = value;
	const record = asRecord(value);

	if (typeof value === 'string') {
		candidates = value.split(',');
	} else if (typeof record.ids === 'string') {
		candidates = record.ids.split(',');
	} else if (Array.isArray(record.ids)) {
		candidates = record.ids;
	}

	if (!Array.isArray(candidates)) {
		return [];
	}

	const ids = [];

	for (const candidate of candidates) {
		const id = attachmentId(candidate);

		if (id > 0 && !ids.includes(id)) {
			ids.push(id);
		}
	}

	return ids;
};

/**
 * @param {Record<string, unknown>} record
 * @returns {string}
 */
const attachmentPreviewUrl = (record) => {
	const mediaDetails = asRecord(record.media_details);
	const sizes = asRecord(mediaDetails.sizes || record.sizes);
	const thumbnail = asRecord(sizes.thumbnail);
	const medium = asRecord(sizes.medium);

	return stringValue(record.thumbnail || thumbnail.source_url || thumbnail.url || medium.source_url || medium.url || record.source_url || record.url);
};

/**
 * @param {unknown} value
 * @returns {Record<string, unknown>}
 */
const attachmentRecord = (value) => {
	const record = asRecord(value);
	const id = attachmentId(record);

	if (id <= 0) {
		return {};
	}

	const title = asRecord(record.title);
	const rawTitle = typeof record.title === 'string' ? record.title : '';

	return {
		...record,
		id,
		title: stringValue(title.rendered || rawTitle || record.filename || `#${ id }`),
		url: stringValue(record.url || record.source_url),
		thumbnail: attachmentPreviewUrl(record),
	};
};

/**
 * @param {unknown} value
 * @returns {Array<Record<string, unknown>>}
 */
const attachmentRecords = (value) => selectionItems(value)
	.map(attachmentRecord)
	.filter((record) => attachmentId(record) > 0);

const attachmentCache = new Map();

/**
 * @param {Array<Record<string, unknown>>} records
 */
const cacheAttachmentRecords = (records) => {
	for (const record of records) {
		const id = attachmentId(record);

		if (id > 0) {
			attachmentCache.set(id, record);
		}
	}
};

/**
 * @returns {Function|null}
 */
const apiFetch = () => {
	if (typeof window === 'undefined' || !window.wp || typeof window.wp.apiFetch !== 'function') {
		return null;
	}

	return window.wp.apiFetch;
};

/**
 * @returns {Record<string, Function>}
 */
const blockEditorComponents = () => {
	if (typeof window === 'undefined' || !window.wp) {
		return {};
	}

	return /** @type {Record<string, Function>} */ (asRecord(window.wp.blockEditor || window.wp.editor));
};

/**
 * @returns {Record<string, Function>}
 */
const wpElement = () => {
	if (typeof window === 'undefined' || !window.wp) {
		return {};
	}

	return /** @type {Record<string, Function>} */ (asRecord(window.wp.element));
};

/**
 * @param {number[]} ids
 * @returns {Array<number[]>}
 */
const attachmentIdChunks = (ids) => {
	const chunks = [];

	for (let index = 0; index < ids.length; index += 100) {
		chunks.push(ids.slice(index, index + 100));
	}

	return chunks;
};

/**
 * @param {Array<number>} ids
 * @param {Array<Record<string, unknown>>} inlineRecords
 * @returns {Array<Record<string, unknown>>}
 */
const useAttachmentRecords = (ids, inlineRecords = []) => {
	const element = wpElement();
	const useState = element.useState;
	const useEffect = element.useEffect;

	cacheAttachmentRecords(inlineRecords);

	const recordsForIds = () => ids
		.map((id) => attachmentCache.get(id) || {})
		.filter((record) => attachmentId(record) > 0);

	if (typeof useState !== 'function' || typeof useEffect !== 'function') {
		return recordsForIds();
	}

	const [ records, setRecords ] = useState(recordsForIds);

	useEffect(() => {
		let active = true;
		const fetch = apiFetch();
		const missingIds = ids.filter((id) => !attachmentCache.has(id));

		if (!missingIds.length || typeof fetch !== 'function') {
			setRecords(recordsForIds());
			return () => {
				active = false;
			};
		}

		Promise.all(attachmentIdChunks(missingIds).map((chunk) => fetch({
			path: `/wp/v2/media?include=${ chunk.join(',') }&orderby=include&per_page=${ chunk.length }&_fields=id,source_url,media_details,alt_text,title,mime_type`,
		}))).then((responses) => {
			if (!active) {
				return;
			}

			cacheAttachmentRecords(responses.flatMap((response) => attachmentRecords(response)));
			setRecords(recordsForIds());
		}).catch(() => {
			if (active) {
				setRecords(recordsForIds());
			}
		});

		return () => {
			active = false;
		};
	}, [ ids.join(',') ]);

	return records;
};

/**
 * @param {Record<string, unknown>} field
 * @param {string[]} fallback
 * @returns {string[]|undefined}
 */
const allowedMediaTypes = (field, fallback = []) => {
	const library = field.library;
	const values = Array.isArray(library) ? library : (stringValue(library) ? [ stringValue(library) ] : fallback);
	const allowed = values.map(stringValue).filter(Boolean);

	return allowed.length ? allowed : undefined;
};

/**
 * @param {Function|undefined} Button
 * @param {Function} createElement
 * @param {Record<string, unknown>} props
 * @param {string} label
 * @returns {unknown}
 */
const renderButton = (Button, createElement, props, label) => typeof Button === 'function'
	? createElement(Button, props, label)
	: createElement('button', { ...props, type: 'button' }, label);

/**
 * @param {Function} createElement
 * @param {string} url
 * @param {string} key
 * @returns {unknown}
 */
const renderUrlPreview = (createElement, url, key = 'preview') => {
	if (!url) {
		return null;
	}

	if (/\.(?:avif|gif|jpe?g|png|svg|webp)(?:\?.*)?$/i.test(url)) {
		return createElement('img', { alt: '', key, src: url });
	}

	return createElement('a', { href: url, key, rel: 'noopener noreferrer', target: '_blank' }, url.split('/').pop() || url);
};

/**
 * @param {Function} createElement
 * @param {Array<Record<string, unknown>>} records
 * @param {boolean} multiple
 * @returns {unknown}
 */
const renderAttachmentPreview = (createElement, records, multiple = false) => {
	const items = records.map((record) => {
		const id = attachmentId(record);
		const url = attachmentPreviewUrl(record);
		const label = stringValue(record.title || `Attachment ${ id }`);

		return createElement(
			'li',
			{
				className: 'lerm-admin-config-block-panel__media-preview-item',
				key: String(id),
			},
			url
				? createElement('img', { alt: label, src: url })
				: createElement('span', {}, label)
		);
	});

	if (!items.length) {
		return createElement(
			'p',
			{
				className: 'lerm-admin-config-block-panel__media-empty',
				key: 'empty',
			},
			multiple ? 'No images selected.' : 'No media selected.'
		);
	}

	return createElement(
		'ul',
		{
			className: 'lerm-admin-config-block-panel__media-preview',
			key: 'preview',
		},
		items
	);
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
						onInput: (event) => updateRange('from', event),
						value: stringValue(current.from),
					}),
				]),
				createElement('label', { key: 'to' }, [
					createElement('span', { key: 'label' }, stringValue(field.text_to || 'To')),
					createElement('input', {
						...inputProps,
						key: 'input',
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
 * @param {ReturnType<typeof normalizeProps>} normalized
 * @returns {unknown}
 */
const UploadControlComponent = (normalized) => {
	const { components, createElement, field, inputId, onChange, value } = normalized;
	const { MediaUpload, MediaUploadCheck } = blockEditorComponents();
	const Button = components.Button;
	const url = stringValue(value);
	const label = stringValue(field.label || field.id);
	const description = stringValue(field.description);
	const chooseText = stringValue(field.button_text || 'Choose file');
	const removeText = stringValue(field.remove_text || 'Remove');
	const fallbackInput = createElement('input', {
		'aria-label': label,
		className: 'lerm-admin-config-block-panel__media-url',
		id: inputId,
		key: 'input',
		onInput: (event) => onChange(stringValue(changeValue(event))),
		placeholder: stringValue(field.placeholder),
		type: 'url',
		value: url,
	});
	const preview = renderUrlPreview(createElement, url);
	const body = (open) => createElement(
		'div',
		{
			className: 'lerm-admin-config-block-panel__media-control lerm-admin-config-block-panel__media-control--upload',
		},
		[
			createElement('strong', { key: 'label' }, label),
			description
				? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
				: null,
			createElement('label', { key: 'url' }, [
				createElement('span', { key: 'label' }, 'URL'),
				fallbackInput,
			]),
			createElement(
				'div',
				{
					className: 'lerm-admin-config-block-panel__media-actions',
					key: 'actions',
				},
				[
					typeof open === 'function'
						? renderButton(Button, createElement, { key: 'choose', onClick: open, variant: 'secondary' }, chooseText)
						: null,
					url
						? renderButton(Button, createElement, { key: 'remove', onClick: () => onChange(''), variant: 'secondary' }, removeText)
						: null,
				].filter(Boolean)
			),
			preview
				? createElement(
					'div',
					{
						className: 'lerm-admin-config-block-panel__media-url-preview',
						key: 'preview',
					},
					preview
				)
				: null,
		].filter(Boolean)
	);

	if (typeof MediaUpload !== 'function') {
		return body(null);
	}

	const mediaUpload = createElement(MediaUpload, {
		allowedTypes: allowedMediaTypes(field),
		onSelect: (selection) => {
			const selected = attachmentRecord(selectionItems(selection)[0]);

			onChange(stringValue(selected.url || selected.source_url));
		},
		render: ({ open }) => body(open),
	});

	return typeof MediaUploadCheck === 'function'
		? createElement(MediaUploadCheck, {}, mediaUpload)
		: mediaUpload;
};

/**
 * @param {ReturnType<typeof normalizeProps>} normalized
 * @returns {unknown}
 */
const MediaControlComponent = (normalized) => {
	const { components, createElement, field, inputId, onChange, value } = normalized;
	const { MediaUpload, MediaUploadCheck } = blockEditorComponents();
	const Button = components.Button;
	const id = attachmentId(value);
	const inlineRecords = attachmentRecords(value);
	const records = useAttachmentRecords(id > 0 ? [ id ] : [], inlineRecords);
	const label = stringValue(field.label || field.id);
	const description = stringValue(field.description);
	const chooseText = stringValue(field.button_text || 'Choose image');
	const removeText = stringValue(field.remove_text || 'Remove');
	const body = (open) => createElement(
		'div',
		{
			className: 'lerm-admin-config-block-panel__media-control',
		},
		[
			createElement('strong', { key: 'label' }, label),
			description
				? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
				: null,
			renderAttachmentPreview(createElement, records, false),
			createElement(
				'div',
				{
					className: 'lerm-admin-config-block-panel__media-actions',
					key: 'actions',
				},
				[
					typeof open === 'function'
						? renderButton(Button, createElement, { key: 'choose', onClick: open, variant: 'secondary' }, chooseText)
						: null,
					id > 0
						? renderButton(Button, createElement, { key: 'remove', onClick: () => onChange({}), variant: 'secondary' }, removeText)
						: null,
				].filter(Boolean)
			),
			typeof open !== 'function'
				? createElement('label', { key: 'fallback' }, [
					createElement('span', { key: 'label' }, 'Attachment ID'),
					createElement('input', {
						'aria-label': `${ label } attachment ID`,
						id: inputId,
						key: 'input',
						min: '0',
						onInput: (event) => onChange({ id: positiveInteger(changeValue(event)) }),
						type: 'number',
						value: id > 0 ? String(id) : '',
					}),
				])
				: null,
		].filter(Boolean)
	);

	if (typeof MediaUpload !== 'function') {
		return body(null);
	}

	const mediaUpload = createElement(MediaUpload, {
		allowedTypes: allowedMediaTypes(field, [ 'image' ]),
		multiple: false,
		onSelect: (selection) => onChange(attachmentRecord(selectionItems(selection)[0])),
		render: ({ open }) => body(open),
		value: id > 0 ? id : undefined,
	});

	return typeof MediaUploadCheck === 'function'
		? createElement(MediaUploadCheck, {}, mediaUpload)
		: mediaUpload;
};

/**
 * @param {ReturnType<typeof normalizeProps>} normalized
 * @returns {unknown}
 */
const GalleryControlComponent = (normalized) => {
	const { components, createElement, field, inputId, onChange, value } = normalized;
	const { MediaUpload, MediaUploadCheck } = blockEditorComponents();
	const Button = components.Button;
	const ids = galleryIds(value);
	const inlineRecords = attachmentRecords(value);
	const records = useAttachmentRecords(ids, inlineRecords);
	const label = stringValue(field.label || field.id);
	const description = stringValue(field.description);
	const chooseText = stringValue(field.button_text || 'Choose images');
	const removeText = stringValue(field.remove_text || 'Clear gallery');
	const body = (open) => createElement(
		'div',
		{
			className: 'lerm-admin-config-block-panel__media-control lerm-admin-config-block-panel__media-control--gallery',
		},
		[
			createElement('strong', { key: 'label' }, label),
			description
				? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
				: null,
			renderAttachmentPreview(createElement, records, true),
			createElement(
				'div',
				{
					className: 'lerm-admin-config-block-panel__media-actions',
					key: 'actions',
				},
				[
					typeof open === 'function'
						? renderButton(Button, createElement, { key: 'choose', onClick: open, variant: 'secondary' }, chooseText)
						: null,
					ids.length
						? renderButton(Button, createElement, { key: 'remove', onClick: () => onChange([]), variant: 'secondary' }, removeText)
						: null,
				].filter(Boolean)
			),
			typeof open !== 'function'
				? createElement('label', { key: 'fallback' }, [
					createElement('span', { key: 'label' }, 'Attachment IDs'),
					createElement('input', {
						'aria-label': `${ label } attachment IDs`,
						id: inputId,
						key: 'input',
						onInput: (event) => onChange(galleryIds(changeValue(event))),
						type: 'text',
						value: ids.join(','),
					}),
				])
				: null,
		].filter(Boolean)
	);

	if (typeof MediaUpload !== 'function') {
		return body(null);
	}

	const mediaUpload = createElement(MediaUpload, {
		addToGallery: true,
		allowedTypes: allowedMediaTypes(field, [ 'image' ]),
		gallery: true,
		multiple: true,
		onSelect: (selection) => onChange(attachmentRecords(selection)),
		render: ({ open }) => body(open),
		value: ids,
	});

	return typeof MediaUploadCheck === 'function'
		? createElement(MediaUploadCheck, {}, mediaUpload)
		: mediaUpload;
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const UploadControl = (props) => {
	const normalized = normalizeProps(props);

	return normalized.createElement(UploadControlComponent, normalized);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const MediaControl = (props) => {
	const normalized = normalizeProps(props);

	return normalized.createElement(MediaControlComponent, normalized);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const GalleryControl = (props) => {
	const normalized = normalizeProps(props);

	return normalized.createElement(GalleryControlComponent, normalized);
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
	gallery: GalleryControl,
	media: MediaControl,
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
	upload: UploadControl,
	url: UrlControl,
});

module.exports = {
	createControlRegistry,
	createDefaultControlRegistry,
};
