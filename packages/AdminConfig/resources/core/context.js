// @ts-check

const CONTEXT_KEYS = [
	'post_id',
	'term_id',
	'user_id',
	'comment_id',
	'network_id',
];

/**
 * @param {Record<string, unknown>} source
 * @returns {Record<string, number>}
 */
const contextFromRecord = (source = {}) => {
	const record = source && typeof source === 'object' ? source : {};
	const context = {};

	for (const key of CONTEXT_KEYS) {
		const value = Number.parseInt(String(record[key] ?? ''), 10);

		if (Number.isInteger(value) && value > 0) {
			context[key] = value;
		}
	}

	return context;
};

/**
 * @param {unknown} value
 * @returns {Record<string, unknown>}
 */
const recordFromUnknown = (value) => value && typeof value === 'object'
	? /** @type {Record<string, unknown>} */ (value)
	: {};

/**
 * @param {Record<string, unknown>} source
 * @returns {Record<string, number>}
 */
const contextFromConfig = (source = {}) => {
	const record = source && typeof source === 'object' ? source : {};
	return {
		...contextFromRecord(record),
		...contextFromRecord(recordFromUnknown(record.context)),
	};
};

/**
 * @param {Record<string, number>} context
 * @returns {string}
 */
const contextQueryString = (context = {}) => {
	const record = context && typeof context === 'object' ? context : {};
	const params = new URLSearchParams();

	for (const key of CONTEXT_KEYS) {
		const value = record[key];

		if (Number.isInteger(value) && value > 0) {
			params.set(key, String(value));
		}
	}

	return params.toString();
};

module.exports = {
	CONTEXT_KEYS,
	contextFromConfig,
	contextFromRecord,
	contextQueryString,
};
