// @ts-check

const { asRecord } = require('./records');

/**
 * Coerce a value to a string, treating null/undefined as empty.
 *
 * @param {unknown} value
 * @returns {string}
 */
const stringValue = (value) => value === null || typeof value === 'undefined' ? '' : String(value);

/**
 * Resolve the control type for a field definition.
 *
 * Falls back to `'text'` when no control/type is declared so callers can
 * always rely on a non-empty string.
 *
 * @param {Record<string, unknown>} field
 * @returns {string}
 */
const fieldControlType = (field) => {
	const client = asRecord(field.client);

	return stringValue(field.control || client.control || field.type || 'text') || 'text';
};

/**
 * Parse a value as a positive integer, returning 0 for invalid/empty input.
 *
 * @param {unknown} value
 * @returns {number}
 */
const positiveInteger = (value) => {
	const parsed = Number.parseInt(stringValue(value), 10);

	return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
};

/**
 * Resolve an attachment ID from a media value that may be a number, string,
 * object with id/ID, or an array (first element wins).
 *
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
 * Resolve an array of unique positive attachment IDs from a gallery value.
 *
 * Accepts comma-separated strings, arrays of ids/objects, or objects with
 * an `ids` property.
 *
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
 * Split a dotted field path into trimmed, non-empty tokens.
 *
 * @param {string|string[]} path
 * @returns {string[]}
 */
const pathTokens = (path) => (Array.isArray(path) ? path : stringValue(path).split('.'))
	.map((token) => stringValue(token).trim())
	.filter(Boolean);

module.exports = {
	attachmentId,
	fieldControlType,
	galleryIds,
	pathTokens,
	positiveInteger,
	stringValue,
};
