// @ts-check

const { asRecord } = require('./records');

/**
 * @param {unknown} responseData
 * @returns {Record<string, unknown>}
 */
const normalizeErrorData = (responseData) => {
	const data = asRecord(responseData);
	const nestedData = asRecord(data.data);

	return {
		...nestedData,
		...data,
	};
};

/**
 * @param {unknown} responseData
 * @returns {Record<string, string|string[]>}
 */
const fieldErrorsFromResponse = (responseData) => {
	const data = normalizeErrorData(responseData);
	const errors = data.fieldErrors || data.errors || {};

	return errors && typeof errors === 'object'
		? /** @type {Record<string, string|string[]>} */ (errors)
		: {};
};

/**
 * @param {unknown} responseData
 * @param {string} fallback
 * @returns {string}
 */
const messageFromResponse = (responseData, fallback = '') => {
	const data = normalizeErrorData(responseData);

	return String(data.message || fallback || '');
};

module.exports = {
	fieldErrorsFromResponse,
	messageFromResponse,
	normalizeErrorData,
};
