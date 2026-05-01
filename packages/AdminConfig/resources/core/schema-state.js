// @ts-check

const { fieldErrorsFromResponse, messageFromResponse } = require('./errors');

/**
 * @typedef {{
 *   context: Record<string, number>,
 *   message: string,
 *   schema: Record<string, unknown>,
 *   schemaId: string,
 *   values: Record<string, unknown>,
 *   errors: Record<string, string|string[]>,
 *   status: 'idle'|'loading'|'saving'|'error'|'ready'
 * }} SchemaState
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
 * @returns {Record<string, unknown>}
 */
const cloneRecord = (value) => JSON.parse(JSON.stringify(asRecord(value)));

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

		cursor[token] = cloneRecord(cursor[token]);
		cursor = /** @type {Record<string, unknown>} */ (cursor[token]);
	});

	return nextValues;
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
const withFieldValue = (state, path, value) => ({
	...state,
	values: setValueAtPath(state.values, path, value),
});

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
	values,
});

module.exports = {
	createSchemaState,
	hydrateSchemaResponse,
	serializeSavePayload,
	setValueAtPath,
	withErrors,
	withFieldValue,
	withRestError,
	withSchema,
	withStatus,
	withValues,
};
