// @ts-check

const { fieldErrorsFromResponse, messageFromResponse } = require('./errors');
const { asRecord, asRecordArray } = require('./records');

/**
 * @typedef {{
 *   context: Record<string, number>,
 *   message: string,
 *   schema: Record<string, unknown>,
 *   schemaId: string,
 *   values: Record<string, unknown>,
 *   savedValues: Record<string, unknown>,
 *   errors: Record<string, string|string[]>,
 *   status: 'idle'|'loading'|'saving'|'error'|'ready'
 * }} SchemaState
 */

/**
 * @param {unknown} value
 * @returns {Record<string, unknown>}
 */
const cloneRecord = (value) => JSON.parse(JSON.stringify(asRecord(value)));

/**
 * @param {unknown} value
 * @returns {Record<string, unknown>|Array<unknown>}
 */
const cloneContainer = (value) => {
	if (Array.isArray(value)) {
		return JSON.parse(JSON.stringify(value));
	}

	return cloneRecord(value);
};

/**
 * @param {string|string[]} path
 * @returns {string[]}
 */
const pathTokens = (path) => (Array.isArray(path) ? path : String(path).split('.'))
	.map((token) => String(token).trim())
	.filter(Boolean);

/**
 * @param {Record<string, unknown>} values
 * @param {string|string[]} path
 * @param {unknown} value
 * @returns {Record<string, unknown>}
 */
const setValueAtPath = (values, path, value) => {
	const tokens = pathTokens(path);
	const nextValues = cloneRecord(values);

	if (!tokens.length) {
		return nextValues;
	}

	let cursor = nextValues;

	tokens.forEach((token, index) => {
		if (index === tokens.length - 1) {
			cursor[token] = value;
			return;
		}

		cursor[token] = cloneContainer(cursor[token]);
		cursor = /** @type {Record<string, unknown>} */ (cursor[token]);
	});

	return nextValues;
};

/**
 * @param {Record<string, string|string[]>} errors
 * @param {string|string[]} path
 * @returns {Record<string, string|string[]>}
 */
const withoutErrorAtPath = (errors, path) => {
	const tokens = pathTokens(path);
	const fieldId = tokens[0] || '';
	const exactPath = tokens.join('.');

	if (
		!fieldId ||
		(
			!Object.prototype.hasOwnProperty.call(errors, fieldId) &&
			!Object.prototype.hasOwnProperty.call(errors, exactPath)
		)
	) {
		return errors;
	}

	const nextErrors = { ...errors };
	delete nextErrors[fieldId];
	delete nextErrors[exactPath];

	return nextErrors;
};

/**
 * @param {unknown} value
 * @returns {number}
 */
const positiveInteger = (value) => {
	const parsed = Number.parseInt(String(value || ''), 10);

	return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
};

/**
 * @param {unknown} value
 * @returns {number}
 */
const mediaAttachmentId = (value) => {
	if (Array.isArray(value)) {
		return mediaAttachmentId(value[0]);
	}

	const record = asRecord(value);

	return positiveInteger(record.id || record.ID || value);
};

/**
 * @param {unknown} value
 * @returns {Array<number>}
 */
const galleryAttachmentIds = (value) => {
	let candidates = value;

	if (typeof value === 'string') {
		candidates = value.split(',');
	}

	const record = asRecord(value);

	if (typeof record.ids === 'string') {
		candidates = record.ids.split(',');
	} else if (Array.isArray(record.ids)) {
		candidates = record.ids;
	}

	if (!Array.isArray(candidates)) {
		candidates = [];
	}

	const ids = [];

	for (const candidate of candidates) {
		const id = mediaAttachmentId(candidate);

		if (id > 0 && !ids.includes(id)) {
			ids.push(id);
		}
	}

	return ids;
};

/**
 * @param {Record<string, unknown>} field
 * @returns {string}
 */
const fieldControlType = (field) => {
	const client = asRecord(field.client);

	return String(field.control || client.control || field.type || '');
};

/**
 * @param {Record<string, unknown>} field
 * @returns {Array<Record<string, unknown>>}
 */
const nestedFields = (field) => asRecordArray(field.fields);

/**
 * @param {Record<string, unknown>} field
 * @param {Record<string, unknown>} value
 * @returns {Record<string, unknown>}
 */
const serializeNestedRecord = (field, value) => {
	const fields = nestedFields(field);

	if (!fields.length) {
		return value;
	}

	const payload = { ...value };

	for (const child of fields) {
		const childId = String(child.id || '');

		if (!childId || !Object.prototype.hasOwnProperty.call(value, childId)) {
			continue;
		}

		payload[childId] = serializeFieldValue(child, value[childId]);
	}

	return payload;
};

/**
 * @param {Record<string, unknown>} field
 * @param {unknown} value
 * @returns {unknown}
 */
const serializeFieldValue = (field, value) => {
	switch (fieldControlType(field)) {
		case 'fieldset':
			return serializeNestedRecord(field, asRecord(value));

		case 'group':
			return Array.isArray(value)
				? value.map((item) => serializeNestedRecord(field, asRecord(item)))
				: [];

		case 'media':
			return { id: mediaAttachmentId(value) };

		case 'gallery':
			return galleryAttachmentIds(value);

		case 'upload':
			return typeof value === 'undefined' || value === null ? '' : String(value);

		default:
			return value;
	}
};

/**
 * @param {Record<string, unknown>} schema
 * @param {Record<string, unknown>} values
 * @returns {Record<string, unknown>}
 */
const serializeValuesForSchema = (schema, values) => {
	const fields = asRecord(schema.fields);

	if (!Object.keys(fields).length) {
		return values;
	}

	const payload = { ...values };

	for (const [ fieldId, field ] of Object.entries(fields)) {
		if (!Object.prototype.hasOwnProperty.call(values, fieldId)) {
			continue;
		}

		payload[fieldId] = serializeFieldValue(asRecord(field), values[fieldId]);
	}

	return payload;
};

/**
 * @param {Record<string, unknown>} schema
 * @param {Record<string, unknown>} values
 * @param {Record<string, number>} context
 * @param {string} schemaId
 * @returns {SchemaState}
 */
const createSchemaState = (schema = {}, values = {}, context = {}, schemaId = '') => ({
	context,
	message: '',
	schema,
	schemaId,
	values,
	savedValues: cloneRecord(values),
	errors: {},
	status: 'idle',
});

/**
 * @param {SchemaState} state
 * @param {SchemaState['status']} status
 * @param {string} [message]
 * @returns {SchemaState}
 */
const withStatus = (state, status, message = state.message) => ({
	...state,
	message,
	status,
});

/**
 * @param {SchemaState} state
 * @param {Record<string, unknown>} schema
 * @param {Record<string, unknown>} values
 * @param {Record<string, number>} context
 * @param {string} schemaId
 * @returns {SchemaState}
 */
const withSchema = (state, schema, values = {}, context = state.context, schemaId = state.schemaId) => ({
	...state,
	context,
	errors: {},
	message: '',
	schema,
	schemaId,
	values,
	savedValues: cloneRecord(values),
	status: 'ready',
});

/**
 * @param {SchemaState} state
 * @param {Record<string, unknown>} values
 * @returns {SchemaState}
 */
const withValues = (state, values) => ({
	...state,
	errors: {},
	message: '',
	values,
	savedValues: cloneRecord(values),
	status: 'ready',
});

/**
 * @param {SchemaState} state
 * @param {Record<string, string|string[]>} errors
 * @returns {SchemaState}
 */
const withErrors = (state, errors) => ({
	...state,
	errors,
	status: 'error',
});

/**
 * @param {SchemaState} state
 * @param {string|string[]} path
 * @param {unknown} value
 * @returns {SchemaState}
 */
const withFieldValue = (state, path, value) => {
	const errors = withoutErrorAtPath(state.errors, path);
	const hasErrors = Object.keys(errors).length > 0;

	return {
		...state,
		errors,
		message: state.status === 'error' && !hasErrors ? '' : state.message,
		status: state.status === 'error' && !hasErrors ? 'ready' : state.status,
		values: setValueAtPath(state.values, path, value),
	};
};

/**
 * @param {SchemaState} state
 * @param {unknown} responseData
 * @param {Record<string, number>} context
 * @param {string} schemaId
 * @returns {SchemaState}
 */
const hydrateSchemaResponse = (state, responseData, context = state.context, schemaId = state.schemaId) => {
	const data = asRecord(responseData);

	return withSchema(
		state,
		asRecord(data.schema),
		asRecord(data.values),
		context,
		schemaId
	);
};

/**
 * @param {SchemaState} state
 * @param {unknown} responseData
 * @param {string} fallbackMessage
 * @returns {SchemaState}
 */
const withRestError = (state, responseData, fallbackMessage = '') => ({
	...withErrors(state, fieldErrorsFromResponse(responseData)),
	message: messageFromResponse(responseData, fallbackMessage),
});

/**
 * @param {SchemaState} state
 * @param {Record<string, unknown>} [values]
 * @returns {{ values: Record<string, unknown> }}
 */
const serializeSavePayload = (state, values = state.values) => ({
	values: serializeValuesForSchema(state.schema, values),
});

/**
 * @param {SchemaState} state
 * @returns {boolean}
 */
const isSchemaStateDirty = (state) => (
	JSON.stringify(asRecord(state.values)) !== JSON.stringify(asRecord(state.savedValues))
);

module.exports = {
	createSchemaState,
	hydrateSchemaResponse,
	isSchemaStateDirty,
	serializeSavePayload,
	serializeValuesForSchema,
	setValueAtPath,
	withoutErrorAtPath,
	withErrors,
	withFieldValue,
	withRestError,
	withSchema,
	withStatus,
	withValues,
};
