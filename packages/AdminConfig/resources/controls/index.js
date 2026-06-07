// @ts-check

const { asRecord, asRecordArray } = require('../core/records');
const { __, sprintf } = require('../i18n');

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
 * @param {Record<string, unknown>} item
 * @returns {{ label: string, value: string }}
 */
const dataSourceOption = (item) => ({
	label: stringValue(item.label || item.value),
	value: stringValue(item.value),
});

/**
 * @param {unknown} response
 * @returns {Array<{ label: string, value: string }>}
 */
const dataSourceOptions = (response) => {
	const data = asRecord(response);
	const items = asArray(data.items);

	return items.map(asRecord).map(dataSourceOption).filter((option) => option.value && option.label);
};

/**
 * @param {Array<{ label: string, value: string }>} options
 * @param {Array<{ label: string, value: string }>} selected
 * @returns {Array<{ label: string, value: string }>}
 */
const mergeOptions = (options, selected = []) => {
	const seen = new Set();
	const merged = [];

	for (const option of [ ...selected, ...options ]) {
		if (!option.value || seen.has(option.value)) continue;
		seen.add(option.value);
		merged.push(option);
	}

	return merged;
};

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
 *   controls: { get: (type: string) => FieldControl|null },
 *   createElement: Function,
 *   dataSourceRequest: Function,
 *   error: string,
 *   errors: Record<string, string|string[]>,
 *   field: Record<string, unknown>,
 *   inputId: string,
 *   onChange: Function,
 *   onPathChange: Function,
 *   path: string[],
 *   value: unknown
 * }}
 */
const normalizeProps = (props) => ({
	components: /** @type {Record<string, Function>} */ (asRecord(props.components)),
	controls: /** @type {{ get: (type: string) => FieldControl|null }} */ (props.controls || { get: () => null }),
	createElement: /** @type {Function} */ (props.createElement),
	dataSourceRequest: /** @type {Function} */ (props.dataSourceRequest || (() => Promise.resolve({ success: false, data: { message: 'Data-source transport is unavailable.' } }))),
	error: errorMessage(props.error),
	errors: /** @type {Record<string, string|string[]>} */ (asRecord(props.errors)),
	field: asRecord(props.field),
	inputId: stringValue(props.inputId),
	onChange: /** @type {Function} */ (props.onChange),
	onPathChange: /** @type {Function} */ (props.onPathChange || (() => {})),
	path: pathTokens(props.path || asRecord(props.field).id),
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
 * @param {Record<string, unknown>} field
 * @returns {string}
 */
const fieldControlType = (field) => {
	const client = asRecord(field.client);

	return stringValue(field.control || client.control || field.type || 'text') || 'text';
};

/**
 * @param {Record<string, unknown>} field
 * @returns {Array<Record<string, unknown>>}
 */
const childFields = (field) => asRecordArray(field.fields);

/**
 * @param {string|string[]} path
 * @returns {string[]}
 */
const pathTokens = (path) => (Array.isArray(path) ? path : stringValue(path).split('.'))
	.map((token) => stringValue(token).trim())
	.filter(Boolean);

/**
 * @param {string[]} path
 * @returns {string}
 */
const pathKey = (path) => path.join('.');

/**
 * @param {Record<string, string|string[]>} errors
 * @param {string|string[]} path
 * @returns {string}
 */
const errorForPath = (errors, path) => errorMessage(errors[pathKey(pathTokens(path))]);

/**
 * @param {Record<string, unknown>} field
 * @param {unknown} value
 * @returns {unknown}
 */
const fieldValue = (field, value) => typeof value === 'undefined' ? field.default : value;

/**
 * @param {Record<string, unknown>} field
 * @param {Record<string, unknown>} parentValue
 * @returns {unknown}
 */
const childValue = (field, parentValue) => {
	const fieldId = stringValue(field.id);

	return Object.prototype.hasOwnProperty.call(parentValue, fieldId)
		? parentValue[fieldId]
		: field.default;
};

/**
 * @param {Array<Record<string, unknown>>} fields
 * @returns {Record<string, unknown>}
 */
const defaultNestedValue = (fields) => fields.reduce((values, field) => {
	const fieldId = stringValue(field.id);

	if (fieldId) {
		values[fieldId] = Object.prototype.hasOwnProperty.call(field, 'default') ? field.default : '';
	}

	return values;
}, {});

/**
 * @param {unknown} value
 * @returns {Array<Record<string, unknown>>}
 */
const groupItems = (value) => Array.isArray(value) ? value.map(asRecord) : [];

/**
 * @param {ReturnType<typeof normalizeProps>} normalized
 * @param {Record<string, unknown>} field
 * @param {string[]} path
 * @param {unknown} value
 * @returns {unknown}
 */
const renderNestedField = (normalized, field, path, value) => {
	const { components, controls, createElement, dataSourceRequest, errors, inputId, onPathChange } = normalized;
	const fieldId = stringValue(field.id);
	const controlType = fieldControlType(field);
	const control = controls.get(controlType);
	const exactPath = pathKey(path);
	const error = errorForPath(errors, path);
	const label = stringValue(field.label || fieldId);

	if (!fieldId) {
		return null;
	}

	if (typeof control !== 'function') {
		return createElement(
			'div',
			{
				className: 'lerm-admin-config-block-panel__nested-field lerm-admin-config-block-panel__nested-field--unavailable',
				'data-field-path': exactPath,
				key: exactPath,
				role: 'note',
			},
			[
				createElement('strong', { key: 'label' }, label),
				createElement('p', { key: 'message' }, sprintf(__('Field type "%s" is not available in this composite field yet.', 'lerm-admin-config'), controlType)),
				error
					? createElement('p', {
						className: 'lerm-admin-config-block-panel__field-error',
						'data-field-error': exactPath,
						key: 'error',
					}, error)
					: null,
			].filter(Boolean)
		);
	}

	return createElement(
		'div',
		{
			className: `lerm-admin-config-block-panel__nested-field${ error ? ' is-error' : '' }`,
			'data-control-status': 'editable',
			'data-field-path': exactPath,
			'data-field-type': controlType,
			key: exactPath,
		},
		[
			control({
				components,
				controls,
				createElement,
				dataSourceRequest,
				error,
				errors,
				field,
				inputId: `${ inputId }-${ path.map((token) => token.replace(/[^a-z0-9_-]+/gi, '-')).join('-') }`,
				onChange: (nextValue) => onPathChange(path, nextValue),
				onPathChange,
				path,
				value: fieldValue(field, value),
			}),
			error
				? createElement('p', {
					className: 'lerm-admin-config-block-panel__field-error',
					'data-field-error': exactPath,
					key: 'error',
				}, error)
				: null,
		].filter(Boolean)
	);
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
 * @param {ReturnType<typeof normalizeProps>} normalized
 * @returns {unknown}
 */
const AjaxSelectControlComponent = (normalized) => {
	const { components, createElement, dataSourceRequest, field, onChange, value } = normalized;
	const element = typeof window !== 'undefined' && window.wp ? asRecord(window.wp.element) : {};
	const useEffect = /** @type {Function|undefined} */ (element.useEffect);
	const useState = /** @type {Function|undefined} */ (element.useState);
	const Button = components.Button;
	const Spinner = components.Spinner;
	const fieldId = stringValue(field.id);
	const multiple = field.multiple === true;
	const allowClear = !Object.prototype.hasOwnProperty.call(field, 'allow_clear') || field.allow_clear !== false;
	const minLength = ajaxMinSearchLength(field);
	const perPage = ajaxPerPage(field);
	const selectedValues = ajaxSelectedValues(value, field);
	const selectedKey = selectedValues.join('\u0001');
	const label = stringValue(field.label || fieldId);
	const description = stringValue(field.description);
	const placeholder = stringValue(field.placeholder || 'Search...');
	const searchLabel = stringValue(field.search_label || `Search ${ label }`);
	const renderButton = (props, content) => typeof Button === 'function'
		? createElement(Button, props, content)
		: createElement('button', { ...props, type: 'button' }, content);

	if (typeof useState !== 'function' || typeof useEffect !== 'function') {
		return createElement('p', { className: 'lerm-admin-config-block-panel__field-description' }, 'Async select requires the WordPress element runtime.');
	}

	const [ search, setSearch ] = useState('');
	const [ options, setOptions ] = useState([]);
	const [ selectedOptions, setSelectedOptions ] = useState(
		() => selectedValues.map((item) => selectedOptionForValue(item, []))
	);
	const [ status, setStatus ] = useState(
		minLength > 0 ? `Type ${ minLength } or more characters to search.` : 'Start typing to search.'
	);
	const [ isLoading, setIsLoading ] = useState(false);

	useEffect(() => {
		let active = true;

		if (!selectedValues.length) {
			setSelectedOptions([]);
			return () => {
				active = false;
			};
		}

		dataSourceRequest({
			fieldId,
			page: 1,
			perPage: Math.max(perPage, selectedValues.length),
			search: '',
			selected: selectedValues,
		}).then((response) => {
			if (!active) return;

			const data = asRecord(response && response.data);
			const fetchedOptions = response && response.success ? dataSourceOptions(data) : [];

			setSelectedOptions(selectedValues.map((item) => selectedOptionForValue(item, fetchedOptions)));
		});

		return () => {
			active = false;
		};
	}, [ fieldId, perPage, selectedKey ]);

	useEffect(() => {
		const term = stringValue(search).trim();
		let active = true;

		if (term.length < minLength) {
			setOptions([]);
			setIsLoading(false);
			setStatus(minLength > 0 ? `Type ${ minLength } or more characters to search.` : 'Start typing to search.');
			return () => {
				active = false;
			};
		}

		setIsLoading(true);
		setStatus('Searching...');

		const timer = setTimeout(() => {
			dataSourceRequest({
				fieldId,
				page: 1,
				perPage,
				search: term,
				selected: selectedValues,
			}).then((response) => {
				if (!active) return;

				const data = asRecord(response && response.data);

				if (!response || !response.success) {
					setOptions([]);
					setStatus(stringValue(data.message || 'Unable to load options.'));
					return;
				}

				const nextOptions = dataSourceOptions(data);

				setOptions(nextOptions);
				setStatus(nextOptions.length ? 'Select an option.' : 'No options found.');
			}).finally(() => {
				if (active) setIsLoading(false);
			});
		}, 250);

		return () => {
			active = false;
			clearTimeout(timer);
		};
	}, [ fieldId, minLength, perPage, search, selectedKey ]);

	const selected = selectedValues.map((item) => selectedOptionForValue(item, selectedOptions));
	const chooseOption = (option) => {
		const nextSelectedOptions = mergeOptions([ option ], selectedOptions);

		setSelectedOptions(nextSelectedOptions);

		if (multiple) {
			onChange(Array.from(new Set([ ...selectedValues, option.value ])));
			return;
		}

		onChange(option.value);
		setSearch('');
		setOptions([]);
	};
	const removeValue = (item) => {
		if (multiple) {
			onChange(selectedValues.filter((candidate) => candidate !== item));
			return;
		}

		onChange('');
	};
	const resultOptions = options.filter((option) => !multiple || !selectedValues.includes(option.value));

	return createElement(
		'fieldset',
		{
			className: 'lerm-admin-config-block-panel__ajax-select',
		},
		[
			createElement('legend', { key: 'legend' }, label),
			description
				? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
				: null,
			createElement(
				'div',
				{
					className: 'lerm-admin-config-block-panel__ajax-select-selected',
					key: 'selected',
				},
				selected.length
					? selected.map((option) => (
						allowClear
							? renderButton(
								{
									'data-selected-value': option.value,
									key: option.value,
									onClick: () => removeValue(option.value),
									type: 'button',
									variant: 'secondary',
								},
								`${ option.label } x`
							)
							: createElement('span', { 'data-selected-value': option.value, key: option.value }, option.label)
					))
					: createElement('span', { key: 'empty' }, 'No option selected.')
			),
			createElement(
				'label',
				{
					className: 'lerm-admin-config-block-panel__ajax-select-search',
					key: 'search',
				},
				[
					createElement('span', { key: 'label' }, searchLabel),
					createElement('input', {
						'aria-label': searchLabel,
						autoComplete: 'off',
						key: 'input',
						onInput: (event) => setSearch(stringValue(changeValue(event))),
						placeholder,
						type: 'search',
						value: search,
					}),
				]
			),
			createElement(
				'p',
				{
					className: 'lerm-admin-config-block-panel__ajax-select-status',
					key: 'status',
					role: 'status',
				},
				[
					isLoading && typeof Spinner === 'function' ? createElement(Spinner, { key: 'spinner' }) : null,
					createElement('span', { key: 'text' }, status),
				].filter(Boolean)
			),
			resultOptions.length
				? createElement(
					'div',
					{
						className: 'lerm-admin-config-block-panel__ajax-select-results',
						key: 'results',
						role: 'listbox',
					},
					resultOptions.map((option) => renderButton(
						{
							'aria-selected': selectedValues.includes(option.value) ? 'true' : 'false',
							'data-value': option.value,
							key: option.value,
							onClick: () => chooseOption(option),
							role: 'option',
							type: 'button',
							variant: 'secondary',
						},
						option.label
					))
				)
				: null,
		].filter(Boolean)
	);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const AjaxSelectControl = (props) => {
	const normalized = normalizeProps(props);

	return normalized.createElement(AjaxSelectControlComponent, normalized);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const PaletteControl = (props) => {
	const normalized = normalizeProps(props);
	const { createElement } = normalized;
	const options = paletteOptions(normalized.field);

	return VisualChoiceControl(normalized, 'palette', options, (option) => [
		createElement(
			'span',
			{
				className: 'lerm-admin-config-block-panel__palette-swatches',
				key: 'swatches',
				style: {
					display: 'inline-flex',
					gap: '2px',
				},
			},
			option.colors.map((color) => createElement('span', {
				key: color,
				style: {
					backgroundColor: color,
					border: '1px solid rgba(0,0,0,.2)',
					borderRadius: '2px',
					display: 'inline-block',
					height: '16px',
					width: '16px',
				},
			}))
		),
		createElement('span', { key: 'label' }, option.label),
	]);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const ImageSelectControl = (props) => {
	const normalized = normalizeProps(props);
	const { createElement } = normalized;
	const options = imageSelectOptions(normalized.field);

	return VisualChoiceControl(normalized, 'image-select', options, (option) => [
		option.url
			? createElement('img', {
				alt: option.label,
				key: 'image',
				src: option.url,
			})
			: null,
		createElement('span', { key: 'label' }, option.label),
	].filter(Boolean));
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const IconControl = (props) => {
	const normalized = normalizeProps(props);
	const { createElement } = normalized;
	const options = iconOptions(normalized.field);

	return VisualChoiceControl(normalized, 'icon', options, (option) => [
		createElement('span', {
			'aria-hidden': 'true',
			className: `dashicons ${ option.value }`,
			key: 'icon',
		}),
		createElement('span', { key: 'label' }, option.label),
	]);
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
 * @param {Record<string, unknown>} field
 * @returns {string[]}
 */
const fieldUnits = (field) => {
	const unit = field.unit;

	if (typeof unit === 'string' && unit !== '') {
		return [ unit ];
	}

	const units = Array.isArray(field.units) ? field.units.map(stringValue).filter(Boolean) : [ 'px', '%', 'em' ];

	return units.length ? units : [ 'px' ];
};

/**
 * @param {Record<string, unknown>} field
 * @param {string} key
 * @param {boolean} fallback
 * @returns {boolean}
 */
const fieldFlag = (field, key, fallback) => Object.prototype.hasOwnProperty.call(field, key)
	? boolValue(field[key])
	: fallback;

const BORDER_STYLE_OPTIONS = [
	{ label: 'Solid', value: 'solid' },
	{ label: 'Dashed', value: 'dashed' },
	{ label: 'Dotted', value: 'dotted' },
	{ label: 'Double', value: 'double' },
	{ label: 'Inset', value: 'inset' },
	{ label: 'Outset', value: 'outset' },
	{ label: 'Groove', value: 'groove' },
	{ label: 'Ridge', value: 'ridge' },
	{ label: 'None', value: 'none' },
];

const LINK_COLOR_FIELDS = [
	{ fallback: true, key: 'color', label: 'Normal' },
	{ fallback: true, key: 'hover', label: 'Hover' },
	{ fallback: false, key: 'active', label: 'Active' },
	{ fallback: false, key: 'visited', label: 'Visited' },
	{ fallback: false, key: 'focus', label: 'Focus' },
];

const FONT_WEIGHT_OPTIONS = [
	{ label: '300', value: '300' },
	{ label: '400', value: '400' },
	{ label: '500', value: '500' },
	{ label: '600', value: '600' },
	{ label: '700', value: '700' },
	{ label: '800', value: '800' },
];

const FONT_STYLE_OPTIONS = [
	{ label: 'Normal', value: 'normal' },
	{ label: 'Italic', value: 'italic' },
];

const TEXT_TRANSFORM_OPTIONS = [
	{ label: 'None', value: 'none' },
	{ label: 'Uppercase', value: 'uppercase' },
	{ label: 'Lowercase', value: 'lowercase' },
	{ label: 'Capitalize', value: 'capitalize' },
];

const TEXT_ALIGN_OPTIONS = [
	{ label: 'Left', value: 'left' },
	{ label: 'Center', value: 'center' },
	{ label: 'Right', value: 'right' },
	{ label: 'Justify', value: 'justify' },
];

const BACKGROUND_GRADIENT_DIRECTION_OPTIONS = [
	{ label: 'Default', value: '' },
	{ label: 'Top to Bottom', value: 'to bottom' },
	{ label: 'Left to Right', value: 'to right' },
	{ label: 'Top Left to Bottom Right', value: '135deg' },
	{ label: 'Top Right to Bottom Left', value: '-135deg' },
];

const BACKGROUND_POSITION_OPTIONS = [
	{ label: 'Default', value: '' },
	{ label: 'Left Top', value: 'left top' },
	{ label: 'Left Center', value: 'left center' },
	{ label: 'Left Bottom', value: 'left bottom' },
	{ label: 'Center Top', value: 'center top' },
	{ label: 'Center Center', value: 'center center' },
	{ label: 'Center Bottom', value: 'center bottom' },
	{ label: 'Right Top', value: 'right top' },
	{ label: 'Right Center', value: 'right center' },
	{ label: 'Right Bottom', value: 'right bottom' },
];

const BACKGROUND_REPEAT_OPTIONS = [
	{ label: 'Default', value: '' },
	{ label: 'Repeat', value: 'repeat' },
	{ label: 'No Repeat', value: 'no-repeat' },
	{ label: 'Repeat Horizontally', value: 'repeat-x' },
	{ label: 'Repeat Vertically', value: 'repeat-y' },
];

const BACKGROUND_ATTACHMENT_OPTIONS = [
	{ label: 'Default', value: '' },
	{ label: 'Scroll', value: 'scroll' },
	{ label: 'Fixed', value: 'fixed' },
];

const BACKGROUND_SIZE_OPTIONS = [
	{ label: 'Default', value: '' },
	{ label: 'Cover', value: 'cover' },
	{ label: 'Contain', value: 'contain' },
	{ label: 'Auto', value: 'auto' },
];

const BACKGROUND_ORIGIN_OPTIONS = [
	{ label: 'Default', value: '' },
	{ label: 'Padding Box', value: 'padding-box' },
	{ label: 'Border Box', value: 'border-box' },
	{ label: 'Content Box', value: 'content-box' },
];

const BACKGROUND_CLIP_OPTIONS = [
	{ label: 'Default', value: '' },
	{ label: 'Border Box', value: 'border-box' },
	{ label: 'Padding Box', value: 'padding-box' },
	{ label: 'Content Box', value: 'content-box' },
];

const BACKGROUND_BLEND_MODE_OPTIONS = [
	{ label: 'Default', value: '' },
	{ label: 'Normal', value: 'normal' },
	{ label: 'Multiply', value: 'multiply' },
	{ label: 'Screen', value: 'screen' },
	{ label: 'Overlay', value: 'overlay' },
	{ label: 'Darken', value: 'darken' },
	{ label: 'Lighten', value: 'lighten' },
];

const DEFAULT_ICON_OPTIONS = [
	{ label: 'Site', value: 'dashicons-admin-site-alt3' },
	{ label: 'Appearance', value: 'dashicons-admin-appearance' },
	{ label: 'Settings', value: 'dashicons-admin-generic' },
	{ label: 'Users', value: 'dashicons-admin-users' },
	{ label: 'Comments', value: 'dashicons-admin-comments' },
	{ label: 'Links', value: 'dashicons-admin-links' },
	{ label: 'Image', value: 'dashicons-format-image' },
	{ label: 'Gallery', value: 'dashicons-format-gallery' },
	{ label: 'Audio', value: 'dashicons-format-audio' },
	{ label: 'Video', value: 'dashicons-format-video' },
	{ label: 'Star', value: 'dashicons-star-filled' },
	{ label: 'Heart', value: 'dashicons-heart' },
	{ label: 'Idea', value: 'dashicons-lightbulb' },
	{ label: 'Check', value: 'dashicons-yes-alt' },
	{ label: 'Warning', value: 'dashicons-warning' },
	{ label: 'Announcement', value: 'dashicons-megaphone' },
	{ label: 'Chart', value: 'dashicons-chart-bar' },
	{ label: 'Lifestyle', value: 'dashicons-palmtree' },
	{ label: 'Store', value: 'dashicons-store' },
	{ label: 'Portfolio', value: 'dashicons-portfolio' },
];

/**
 * @param {unknown} value
 * @param {string} fallback
 * @returns {string}
 */
const nativeColorValue = (value, fallback = '#000000') => {
	const color = stringValue(value).trim().toLowerCase();
	const shortHex = color.match(/^#([0-9a-f])([0-9a-f])([0-9a-f])$/i);

	if (shortHex) {
		return `#${ shortHex[1] }${ shortHex[1] }${ shortHex[2] }${ shortHex[2] }${ shortHex[3] }${ shortHex[3] }`.toLowerCase();
	}

	return /^#[0-9a-f]{6}$/i.test(color) ? color : fallback;
};

/**
 * @param {ReturnType<typeof normalizeProps>} normalized
 * @returns {string[]}
 */
const compositeBasePath = (normalized) => normalized.path.length
	? normalized.path
	: [ stringValue(normalized.field.id) ].filter(Boolean);

/**
 * @param {Record<string, unknown>} field
 * @param {Record<string, unknown>} current
 * @param {string} key
 * @param {unknown} fallback
 * @returns {unknown}
 */
const compositeValue = (field, current, key, fallback = '') => {
	if (Object.prototype.hasOwnProperty.call(current, key)) {
		return current[key];
	}

	const defaults = asRecord(field.default);

	return Object.prototype.hasOwnProperty.call(defaults, key) ? defaults[key] : fallback;
};

/**
 * @param {string[]} units
 * @returns {Record<string, string>}
 */
const choicesFromUnits = (units) => units.reduce((choices, unit) => ({
	...choices,
	[unit]: unit,
}), {});

/**
 * @param {Array<{ label: string, value: string }>} options
 * @returns {Record<string, string>}
 */
const choicesFromOptions = (options) => options.reduce((choices, option) => ({
	...choices,
	[option.value]: option.label,
}), {});

/**
 * @param {Record<string, unknown>} field
 * @param {string} key
 * @param {unknown} fallback
 * @returns {unknown}
 */
const typographyDefault = (field, key, fallback = '') => compositeValue(field, {}, key, fallback);

/**
 * @param {Record<string, unknown>} field
 * @param {string} key
 * @param {unknown} fallback
 * @returns {unknown}
 */
const backgroundDefault = (field, key, fallback = '') => compositeValue(field, {}, key, fallback);

/**
 * @param {Record<string, unknown>} field
 * @returns {Array<Record<string, unknown>>}
 */
const typographyFields = (field) => {
	const units = fieldUnits(field);
	const fields = [];

	if (fieldFlag(field, 'family', true)) {
		fields.push({
			default: typographyDefault(field, 'font-family'),
			id: 'font-family',
			label: 'Family',
			placeholder: stringValue(field.family_placeholder || 'Inter, system-ui, sans-serif'),
			type: 'text',
		});
	}

	if (fieldFlag(field, 'weight', true)) {
		fields.push({
			choices: choicesFromOptions(FONT_WEIGHT_OPTIONS),
			default: typographyDefault(field, 'font-weight', '400'),
			id: 'font-weight',
			label: 'Weight',
			type: 'select',
		});
	}

	if (fieldFlag(field, 'style', false)) {
		fields.push({
			choices: choicesFromOptions(FONT_STYLE_OPTIONS),
			default: typographyDefault(field, 'font-style', 'normal'),
			id: 'font-style',
			label: 'Style',
			type: 'button_set',
		});
	}

	if (fieldFlag(field, 'size', true)) {
		fields.push({
			default: typographyDefault(field, 'font-size'),
			id: 'font-size',
			label: 'Size',
			placeholder: stringValue(field.size_placeholder || '1'),
			type: 'text',
		});
	}

	if (fieldFlag(field, 'unit', true)) {
		fields.push({
			choices: choicesFromUnits(units),
			default: typographyDefault(field, 'unit', units[0] || 'px'),
			id: 'unit',
			label: 'Unit',
			type: 'select',
		});
	}

	if (fieldFlag(field, 'line_height', true)) {
		fields.push({
			default: typographyDefault(field, 'line-height'),
			id: 'line-height',
			label: 'Line height',
			placeholder: stringValue(field.line_height_placeholder || '1.5'),
			type: 'text',
		});
	}

	if (fieldFlag(field, 'letter_spacing', false)) {
		fields.push({
			default: typographyDefault(field, 'letter-spacing'),
			id: 'letter-spacing',
			label: 'Letter spacing',
			placeholder: stringValue(field.letter_spacing_placeholder || '0'),
			type: 'text',
		});
	}

	if (fieldFlag(field, 'transform', false)) {
		fields.push({
			choices: choicesFromOptions(TEXT_TRANSFORM_OPTIONS),
			default: typographyDefault(field, 'text-transform', 'none'),
			id: 'text-transform',
			label: 'Transform',
			type: 'select',
		});
	}

	if (fieldFlag(field, 'align', false)) {
		fields.push({
			choices: choicesFromOptions(TEXT_ALIGN_OPTIONS),
			default: typographyDefault(field, 'text-align', 'left'),
			id: 'text-align',
			label: 'Align',
			type: 'button_set',
		});
	}

	if (fieldFlag(field, 'color', true)) {
		fields.push({
			default: typographyDefault(field, 'color'),
			id: 'color',
			label: 'Color',
			type: 'color',
		});
	}

	return fields;
};

/**
 * @param {Record<string, unknown>} field
 * @returns {Array<Record<string, unknown>>}
 */
const backgroundFields = (field) => {
	const fields = [];
	const selectFields = [
		{
			fallback: true,
			id: 'background-position',
			key: 'background_position',
			label: 'Position',
			options: BACKGROUND_POSITION_OPTIONS,
		},
		{
			fallback: true,
			id: 'background-repeat',
			key: 'background_repeat',
			label: 'Repeat',
			options: BACKGROUND_REPEAT_OPTIONS,
		},
		{
			fallback: true,
			id: 'background-attachment',
			key: 'background_attachment',
			label: 'Attachment',
			options: BACKGROUND_ATTACHMENT_OPTIONS,
		},
		{
			fallback: true,
			id: 'background-size',
			key: 'background_size',
			label: 'Size',
			options: BACKGROUND_SIZE_OPTIONS,
		},
		{
			fallback: false,
			id: 'background-origin',
			key: 'background_origin',
			label: 'Origin',
			options: BACKGROUND_ORIGIN_OPTIONS,
		},
		{
			fallback: false,
			id: 'background-clip',
			key: 'background_clip',
			label: 'Clip',
			options: BACKGROUND_CLIP_OPTIONS,
		},
		{
			fallback: false,
			id: 'background-blend-mode',
			key: 'background_blend_mode',
			label: 'Blend Mode',
			options: BACKGROUND_BLEND_MODE_OPTIONS,
		},
	];

	if (fieldFlag(field, 'background_color', true)) {
		fields.push({
			default: backgroundDefault(field, 'background-color'),
			id: 'background-color',
			label: 'Color',
			type: 'color',
		});
	}

	if (fieldFlag(field, 'background_gradient', false) && fieldFlag(field, 'background_gradient_color', true)) {
		fields.push({
			default: backgroundDefault(field, 'background-gradient-color'),
			id: 'background-gradient-color',
			label: 'Gradient To',
			type: 'color',
		});
	}

	if (fieldFlag(field, 'background_gradient', false) && fieldFlag(field, 'background_gradient_direction', true)) {
		fields.push({
			choices: choicesFromOptions(BACKGROUND_GRADIENT_DIRECTION_OPTIONS),
			default: backgroundDefault(field, 'background-gradient-direction'),
			id: 'background-gradient-direction',
			label: 'Gradient Direction',
			type: 'select',
		});
	}

	if (fieldFlag(field, 'background_image', true)) {
		fields.push({
			button_text: stringValue(field.background_image_button_text || __('Choose image', 'lerm-admin-config')),
			default: backgroundDefault(field, 'background-image', {}),
			id: 'background-image',
			label: 'Image',
			library: 'image',
			remove_text: __('Remove image', 'lerm-admin-config'),
			type: 'media',
		});
	}

	for (const item of selectFields) {
		if (fieldFlag(field, item.key, item.fallback)) {
			fields.push({
				choices: choicesFromOptions(item.options),
				default: backgroundDefault(field, item.id),
				id: item.id,
				label: item.label,
				type: 'select',
			});
		}
	}

	return fields;
};

/**
 * @param {Record<string, unknown>} field
 * @returns {Array<{ colors: string[], label: string, value: string }>}
 */
const paletteOptions = (field) => Object.entries(asRecord(field.choices))
	.map(([ value, colors ]) => ({
		colors: asArray(colors).map(stringValue).filter(Boolean),
		label: stringValue(value),
		value: stringValue(value),
	}))
	.filter((option) => option.value && option.colors.length);

/**
 * @param {Record<string, unknown>} field
 * @returns {Array<{ label: string, url: string, value: string }>}
 */
const imageSelectOptions = (field) => Object.entries(asRecord(field.choices))
	.map(([ value, url ]) => ({
		label: stringValue(value),
		url: stringValue(url),
		value: stringValue(value),
	}))
	.filter((option) => option.value);

/**
 * @param {unknown} value
 * @param {Record<string, unknown>} field
 * @returns {string[]}
 */
const ajaxSelectedValues = (value, field) => field.multiple === true
	? asArray(value).map(stringValue).filter(Boolean)
	: [ stringValue(value) ].filter(Boolean);

/**
 * @param {Record<string, unknown>} field
 * @returns {number}
 */
const ajaxPerPage = (field) => {
	const perPage = Number.parseInt(stringValue(field.per_page || 20), 10);

	return Number.isFinite(perPage) && perPage > 0 ? perPage : 20;
};

/**
 * @param {Record<string, unknown>} field
 * @returns {number}
 */
const ajaxMinSearchLength = (field) => {
	const minLength = Number.parseInt(stringValue(field.min_search_length || 0), 10);

	return Number.isFinite(minLength) && minLength > 0 ? minLength : 0;
};

/**
 * @param {string} value
 * @param {Array<{ label: string, value: string }>} options
 * @returns {{ label: string, value: string }}
 */
const selectedOptionForValue = (value, options) => (
	options.find((option) => option.value === value) || { label: value, value }
);

/**
 * @param {Record<string, unknown>} field
 * @returns {Array<{ label: string, value: string }>}
 */
const iconOptions = (field) => {
	const configured = choiceOptions(field);

	return configured.length ? configured : DEFAULT_ICON_OPTIONS;
};

/**
 * @param {ReturnType<typeof normalizeProps>} normalized
 * @param {string} modifier
 * @param {Array<{ label: string, value: string }>} options
 * @param {(option: { label: string, value: string }) => unknown} renderPreview
 * @returns {unknown}
 */
const VisualChoiceControl = (normalized, modifier, options, renderPreview) => {
	const { components, createElement, field, onChange, value } = normalized;
	const Button = components.Button;
	const selected = stringValue(value);
	const label = stringValue(field.label || field.id);
	const description = stringValue(field.description);
	const buttons = options.map((option) => {
		const isPressed = selected === option.value;
		const buttonProps = {
			'aria-label': `${ label } ${ option.label }`,
			'aria-pressed': isPressed ? 'true' : 'false',
			'data-value': option.value,
			key: option.value,
			onClick: () => onChange(option.value),
			type: 'button',
		};
		const content = renderPreview(option);

		return typeof Button === 'function'
			? createElement(
				Button,
				{
					...buttonProps,
					isPressed,
					variant: isPressed ? 'primary' : 'secondary',
				},
				content
			)
			: createElement('button', buttonProps, content);
	});

	return createElement(
		'fieldset',
		{
			'aria-label': label,
			className: `lerm-admin-config-block-panel__visual-choice lerm-admin-config-block-panel__visual-choice--${ modifier }`,
		},
		[
			createElement('legend', { key: 'legend' }, label),
			description
				? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
				: null,
			createElement(
				'div',
				{
					className: 'lerm-admin-config-block-panel__visual-choice-options',
					key: 'options',
				},
				buttons
			),
		].filter(Boolean)
	);
};

/**
 * @param {ReturnType<typeof normalizeProps>} normalized
 * @param {Array<{ key: string, label: string }>} fields
 * @returns {unknown}
 */
const CompositeBoxControl = (normalized, fields) => {
	const { createElement, field, inputId, onPathChange, value } = normalized;
	const fieldId = stringValue(field.id);
	const basePath = compositeBasePath(normalized);
	const current = asRecord(value);
	const units = fieldUnits(field);
	const showUnits = fieldFlag(field, 'unit', true) && fieldFlag(field, 'show_units', true) && units.length > 1;
	const label = stringValue(field.label || fieldId);
	const description = stringValue(field.description);
	const children = [
		createElement('legend', { key: 'legend' }, label),
		description
			? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
			: null,
		...fields.map((item) => {
			const path = [ ...basePath, item.key ];
			const error = errorForPath(normalized.errors, path);

			return createElement(
				'label',
				{
					className: `lerm-admin-config-block-panel__composite-item${ error ? ' is-error' : '' }`,
					key: item.key,
				},
				[
					createElement('span', { key: 'label' }, item.label),
					createElement('input', {
						'aria-invalid': error ? 'true' : undefined,
						'aria-label': `${ label } ${ item.label }`,
						id: `${ inputId }-${ item.key }`,
						key: 'input',
						onInput: (event) => onPathChange(path, stringValue(changeValue(event))),
						step: 'any',
						type: 'number',
						value: stringValue(current[item.key]),
					}),
					error
						? createElement('span', {
							className: 'lerm-admin-config-block-panel__field-error',
							'data-field-error': pathKey(path),
							key: 'error',
						}, error)
						: null,
				].filter(Boolean)
			);
		}),
		showUnits
			? createElement(
				'label',
				{
					className: 'lerm-admin-config-block-panel__composite-item',
					key: 'unit',
				},
				[
					createElement('span', { key: 'label' }, 'Unit'),
					createElement(
						'select',
						{
							'aria-label': `${ label } unit`,
							id: `${ inputId }-unit`,
							key: 'select',
							onChange: (event) => onPathChange([ ...basePath, 'unit' ], stringValue(changeValue(event))),
							value: stringValue(current.unit || field.default?.unit || units[0]),
						},
						units.map((unit) => createElement('option', { key: unit, value: unit }, unit))
					),
				]
			)
			: null,
	];

	return createElement(
		'fieldset',
		{
			className: 'lerm-admin-config-block-panel__composite-field',
		},
		children.filter(Boolean)
	);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const DimensionsControl = (props) => {
	const normalized = normalizeProps(props);
	const fields = [
		fieldFlag(normalized.field, 'width', true) ? { key: 'width', label: 'Width' } : null,
		fieldFlag(normalized.field, 'height', true) ? { key: 'height', label: 'Height' } : null,
	].filter(Boolean);

	return CompositeBoxControl(normalized, fields);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const SpacingControl = (props) => {
	const normalized = normalizeProps(props);
	const fields = fieldFlag(normalized.field, 'all', false)
		? [ { key: 'all', label: 'All' } ]
		: [
			fieldFlag(normalized.field, 'top', true) ? { key: 'top', label: 'Top' } : null,
			fieldFlag(normalized.field, 'right', true) ? { key: 'right', label: 'Right' } : null,
			fieldFlag(normalized.field, 'bottom', true) ? { key: 'bottom', label: 'Bottom' } : null,
			fieldFlag(normalized.field, 'left', true) ? { key: 'left', label: 'Left' } : null,
		].filter(Boolean);

	return CompositeBoxControl(normalized, fields);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const BorderControl = (props) => {
	const normalized = normalizeProps(props);
	const { createElement, field, inputId, onPathChange, value } = normalized;
	const fieldId = stringValue(field.id);
	const basePath = compositeBasePath(normalized);
	const current = asRecord(value);
	const label = stringValue(field.label || fieldId);
	const description = stringValue(field.description);
	const unit = field.unit === false ? '' : stringValue(field.unit || 'px');
	const sideFields = fieldFlag(field, 'all', false)
		? [ { key: 'all', label: 'All' } ]
		: [
			fieldFlag(field, 'top', true) ? { key: 'top', label: 'Top' } : null,
			fieldFlag(field, 'right', true) ? { key: 'right', label: 'Right' } : null,
			fieldFlag(field, 'bottom', true) ? { key: 'bottom', label: 'Bottom' } : null,
			fieldFlag(field, 'left', true) ? { key: 'left', label: 'Left' } : null,
		].filter(Boolean);
	const children = [
		createElement('legend', { key: 'legend' }, label),
		description
			? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
			: null,
		...sideFields.map((item) => {
			const path = [ ...basePath, item.key ];
			const error = errorForPath(normalized.errors, path);

			return createElement(
				'label',
				{
					className: `lerm-admin-config-block-panel__composite-item${ error ? ' is-error' : '' }`,
					key: item.key,
				},
				[
					createElement('span', { key: 'label' }, item.label),
					createElement('input', {
						'aria-invalid': error ? 'true' : undefined,
						'aria-label': `${ label } ${ item.label }`,
						id: `${ inputId }-${ item.key }`,
						key: 'input',
						onInput: (event) => onPathChange(path, stringValue(changeValue(event))),
						step: 'any',
						type: 'number',
						value: stringValue(compositeValue(field, current, item.key)),
					}),
					unit ? createElement('span', { key: 'unit' }, unit) : null,
					error
						? createElement('span', {
							className: 'lerm-admin-config-block-panel__field-error',
							'data-field-error': pathKey(path),
							key: 'error',
						}, error)
						: null,
				].filter(Boolean)
			);
		}),
		fieldFlag(field, 'style', true)
			? createElement(
				'label',
				{
					className: 'lerm-admin-config-block-panel__composite-item',
					key: 'style',
				},
				[
					createElement('span', { key: 'label' }, 'Style'),
					createElement(
						'select',
						{
							'aria-label': `${ label } Style`,
							id: `${ inputId }-style`,
							key: 'select',
							onChange: (event) => onPathChange([ ...basePath, 'style' ], stringValue(changeValue(event))),
							value: stringValue(compositeValue(field, current, 'style', 'solid') || 'solid'),
						},
						BORDER_STYLE_OPTIONS.map((option) => createElement('option', { key: option.value, value: option.value }, option.label))
					),
				]
			)
			: null,
		fieldFlag(field, 'color', true)
			? createElement(
				'label',
				{
					className: 'lerm-admin-config-block-panel__composite-item',
					key: 'color',
				},
				[
					createElement('span', { key: 'label' }, 'Color'),
					createElement('input', {
						'aria-label': `${ label } Color`,
						id: `${ inputId }-color`,
						key: 'input',
						onInput: (event) => onPathChange([ ...basePath, 'color' ], stringValue(changeValue(event))),
						type: 'color',
						value: nativeColorValue(compositeValue(field, current, 'color')),
					}),
					createElement('code', { key: 'value' }, stringValue(compositeValue(field, current, 'color'))),
				]
			)
			: null,
	];

	return createElement(
		'fieldset',
		{
			className: 'lerm-admin-config-block-panel__composite-field',
		},
		children.filter(Boolean)
	);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const LinkColorControl = (props) => {
	const normalized = normalizeProps(props);
	const { createElement, field, inputId, onPathChange, value } = normalized;
	const fieldId = stringValue(field.id);
	const basePath = compositeBasePath(normalized);
	const current = asRecord(value);
	const label = stringValue(field.label || fieldId);
	const description = stringValue(field.description);
	const children = [
		createElement('legend', { key: 'legend' }, label),
		description
			? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
			: null,
		...LINK_COLOR_FIELDS
			.filter((item) => fieldFlag(field, item.key, item.fallback))
			.map((item) => {
				const path = [ ...basePath, item.key ];
				const error = errorForPath(normalized.errors, path);
				const currentValue = compositeValue(field, current, item.key);

				return createElement(
					'label',
					{
						className: `lerm-admin-config-block-panel__composite-item${ error ? ' is-error' : '' }`,
						key: item.key,
					},
					[
						createElement('span', { key: 'label' }, item.label),
						createElement('input', {
							'aria-invalid': error ? 'true' : undefined,
							'aria-label': `${ label } ${ item.label }`,
							id: `${ inputId }-${ item.key }`,
							key: 'input',
							onInput: (event) => onPathChange(path, stringValue(changeValue(event))),
							type: 'color',
							value: nativeColorValue(currentValue),
						}),
						createElement('code', { key: 'value' }, stringValue(currentValue)),
						error
							? createElement('span', {
								className: 'lerm-admin-config-block-panel__field-error',
								'data-field-error': pathKey(path),
								key: 'error',
							}, error)
							: null,
					].filter(Boolean)
				);
			}),
	];

	return createElement(
		'fieldset',
		{
			className: 'lerm-admin-config-block-panel__composite-field',
		},
		children.filter(Boolean)
	);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const TypographyControl = (props) => {
	const normalized = normalizeProps(props);
	const { createElement, field, value } = normalized;
	const fieldId = stringValue(field.id);
	const fields = typographyFields(field);
	const current = asRecord(value);
	const label = stringValue(field.label || fieldId);
	const description = stringValue(field.description);
	const basePath = compositeBasePath(normalized);

	return createElement(
		'fieldset',
		{
			className: 'lerm-admin-config-block-panel__fieldset lerm-admin-config-block-panel__typography',
		},
		[
			createElement('legend', { key: 'legend' }, label),
			description
				? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
				: null,
			...fields.map((child) => renderNestedField(
				normalized,
				child,
				[ ...basePath, stringValue(child.id) ],
				childValue(child, current)
			)),
		].filter(Boolean)
	);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const BackgroundControl = (props) => {
	const normalized = normalizeProps(props);
	const { createElement, field, value } = normalized;
	const fieldId = stringValue(field.id);
	const fields = backgroundFields(field);
	const current = asRecord(value);
	const label = stringValue(field.label || fieldId);
	const description = stringValue(field.description);
	const basePath = compositeBasePath(normalized);

	return createElement(
		'fieldset',
		{
			className: 'lerm-admin-config-block-panel__fieldset lerm-admin-config-block-panel__background',
		},
		[
			createElement('legend', { key: 'legend' }, label),
			description
				? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
				: null,
			...fields.map((child) => renderNestedField(
				normalized,
				child,
				[ ...basePath, stringValue(child.id) ],
				childValue(child, current)
			)),
		].filter(Boolean)
	);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const FieldsetControl = (props) => {
	const normalized = normalizeProps(props);
	const { createElement, field, value } = normalized;
	const fieldId = stringValue(field.id);
	const fields = childFields(field);
	const current = asRecord(value);
	const label = stringValue(field.label || fieldId);
	const description = stringValue(field.description);
	const basePath = compositeBasePath(normalized);

	return createElement(
		'fieldset',
		{
			className: 'lerm-admin-config-block-panel__fieldset',
		},
		[
			createElement('legend', { key: 'legend' }, label),
			description
				? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
				: null,
			...fields.map((child) => renderNestedField(
				normalized,
				child,
				[ ...basePath, stringValue(child.id) ],
				childValue(child, current)
			)),
		].filter(Boolean)
	);
};

/**
 * @param {Record<string, unknown>} props
 * @returns {unknown}
 */
const GroupControl = (props) => {
	const normalized = normalizeProps(props);
	const { components, createElement, field, onChange, value } = normalized;
	const Button = components.Button;
	const fieldId = stringValue(field.id);
	const fields = childFields(field);
	const items = groupItems(value);
	const label = stringValue(field.label || fieldId);
	const description = stringValue(field.description);
	const basePath = compositeBasePath(normalized);
	const addItem = () => onChange([ ...items, defaultNestedValue(fields) ]);
	const removeItem = (index) => onChange(items.filter((_, itemIndex) => itemIndex !== index));
	const renderActionButton = (props, text) => typeof Button === 'function'
		? createElement(Button, props, text)
		: createElement('button', { ...props, type: 'button' }, text);

	return createElement(
		'div',
		{
			className: 'lerm-admin-config-block-panel__group',
			role: 'group',
		},
		[
			createElement('strong', { key: 'label' }, label),
			description
				? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
				: null,
			...items.map((item, index) => createElement(
				'fieldset',
				{
					className: 'lerm-admin-config-block-panel__group-item',
					key: `${ fieldId }-${ index }`,
				},
				[
					createElement('legend', { key: 'legend' }, `Item ${ index + 1 }`),
					...fields.map((child) => renderNestedField(
						normalized,
						child,
						[ ...basePath, String(index), stringValue(child.id) ],
						childValue(child, item)
					)),
					renderActionButton(
						{
							key: 'remove',
							onClick: () => removeItem(index),
							variant: 'secondary',
						},
						__('Remove item', 'lerm-admin-config')
					),
				].filter(Boolean)
			)),
			renderActionButton(
				{
					key: 'add',
					onClick: addItem,
					variant: 'secondary',
				},
				__('Add item', 'lerm-admin-config')
			),
		].filter(Boolean)
	);
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
	const chooseText = stringValue(field.button_text || __('Choose file', 'lerm-admin-config'));
	const removeText = stringValue(field.remove_text || __('Remove', 'lerm-admin-config'));
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
	const chooseText = stringValue(field.button_text || __('Choose image', 'lerm-admin-config'));
	const removeText = stringValue(field.remove_text || __('Remove', 'lerm-admin-config'));
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
	const chooseText = stringValue(field.button_text || __('Choose images', 'lerm-admin-config'));
	const removeText = stringValue(field.remove_text || __('Clear gallery', 'lerm-admin-config'));
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
	ajax_select: AjaxSelectControl,
	background: BackgroundControl,
	button_set: ButtonSetControl,
	border: BorderControl,
	checkbox: CheckboxControl,
	checkbox_list: CheckboxListControl,
	color: ColorControl,
	date: DateControl,
	dimensions: DimensionsControl,
	fieldset: FieldsetControl,
	gallery: GalleryControl,
	group: GroupControl,
	icon: IconControl,
	image_select: ImageSelectControl,
	link_color: LinkColorControl,
	media: MediaControl,
	number: NumberControl,
	palette: PaletteControl,
	radio: RadioControl,
	select: SelectControl,
	slug_text: TextControl,
	slider: RangeControl,
	spinner: NumberControl,
	spacing: SpacingControl,
	switcher: ToggleControl,
	text: TextControl,
	textarea: TextareaControl,
	toggle: ToggleControl,
	typography: TypographyControl,
	upload: UploadControl,
	url: UrlControl,
});

module.exports = {
	createControlRegistry,
	createDefaultControlRegistry,
};
