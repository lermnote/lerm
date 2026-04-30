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
	const context = {};

	for (const key of CONTEXT_KEYS) {
		const value = Number.parseInt(String(source[key] ?? ''), 10);

		if (Number.isInteger(value) && value > 0) {
			context[key] = value;
		}
	}

	return context;
};

module.exports = {
	CONTEXT_KEYS,
	contextFromRecord,
};
