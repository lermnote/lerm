// @ts-check

/**
 * @typedef {{
 *   schema: Record<string, unknown>,
 *   values: Record<string, unknown>,
 *   errors: Record<string, string|string[]>,
 *   status: 'idle'|'loading'|'saving'|'error'|'ready'
 * }} SchemaState
 */

/**
 * @param {Record<string, unknown>} schema
 * @param {Record<string, unknown>} values
 * @returns {SchemaState}
 */
const createSchemaState = (schema = {}, values = {}) => ({
	schema,
	values,
	errors: {},
	status: 'idle',
});

/**
 * @param {SchemaState} state
 * @param {Record<string, unknown>} values
 * @returns {SchemaState}
 */
const withValues = (state, values) => ({
	...state,
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

module.exports = {
	createSchemaState,
	withErrors,
	withValues,
};
