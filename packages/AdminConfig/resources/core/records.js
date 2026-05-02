// @ts-check

/**
 * @param {unknown} value
 * @returns {Record<string, unknown>}
 */
const asRecord = (value) => value && typeof value === 'object' && !Array.isArray(value)
	? /** @type {Record<string, unknown>} */ (value)
	: {};

/**
 * @param {unknown} value
 * @returns {Array<Record<string, unknown>>}
 */
const asRecordArray = (value) => Array.isArray(value)
	? value.map(asRecord).filter((record) => Object.keys(record).length > 0)
	: [];

module.exports = {
	asRecord,
	asRecordArray,
};
