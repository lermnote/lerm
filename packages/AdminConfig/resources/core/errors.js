// @ts-check

/**
 * @param {unknown} responseData
 * @returns {Record<string, string|string[]>}
 */
const fieldErrorsFromResponse = (responseData) => {
	const data = /** @type {{ fieldErrors?: unknown, errors?: unknown }} */ (responseData || {});
	const errors = data.fieldErrors || data.errors || {};

	return errors && typeof errors === 'object'
		? /** @type {Record<string, string|string[]>} */ (errors)
		: {};
};

module.exports = {
	fieldErrorsFromResponse,
};
