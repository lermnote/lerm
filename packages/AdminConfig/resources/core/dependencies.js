// @ts-check

const { asRecord } = require('./records');

/**
 * @param {unknown} value
 * @returns {string}
 */
const dependencyScalar = (value) => {
	if (typeof value === 'boolean') {
		return value ? '1' : '0';
	}

	if (typeof value === 'number' || typeof value === 'bigint' || typeof value === 'string') {
		return String(value);
	}

	return '';
};

/**
 * @param {unknown} value
 * @returns {Array<string>}
 */
const dependencyScalarList = (value) => (
	Array.isArray(value)
		? value.map(dependencyScalar)
		: [ dependencyScalar(value) ]
);

/**
 * @param {unknown} actual
 * @param {unknown} operator
 * @param {unknown} expected
 * @returns {boolean}
 */
const dependencyMatches = (actual, operator = '==', expected = true) => {
	const op = String(operator || '==').trim() || '==';
	const actualValues = dependencyScalarList(actual);
	const expectedValues = dependencyScalarList(expected);
	const expectedValue = expectedValues[0] || '';

	if (op === '!=' || op === '!==') {
		return !actualValues.includes(expectedValue);
	}

	if (op === 'in') {
		return actualValues.some((value) => expectedValues.includes(value));
	}

	if (op === 'not_in' || op === 'not in') {
		return !actualValues.some((value) => expectedValues.includes(value));
	}

	if ([ '>', '>=', '<', '<=' ].includes(op)) {
		const actualNumber = Number(actualValues[0] || '');
		const expectedNumber = Number(expectedValue);

		if (!Number.isFinite(actualNumber) || !Number.isFinite(expectedNumber)) {
			return false;
		}

		if (op === '>') return actualNumber > expectedNumber;
		if (op === '>=') return actualNumber >= expectedNumber;
		if (op === '<') return actualNumber < expectedNumber;
		return actualNumber <= expectedNumber;
	}

	return actualValues.includes(expectedValue);
};

/**
 * @param {string} fieldId
 * @param {Record<string, unknown>} fields
 * @param {Record<string, unknown>} values
 * @param {Record<string, unknown>} dependencies
 * @param {Set<string>} [seen]
 * @returns {boolean}
 */
const isFieldDependencySatisfied = (fieldId, fields, values, dependencies, seen = new Set()) => {
	const field = asRecord(fields[fieldId]);
	const dependency = asRecord(dependencies[fieldId] || field.dependency);
	const controllerId = String(dependency.field || '').trim();

	if (!controllerId) {
		return true;
	}

	if (seen.has(fieldId)) {
		return false;
	}

	const controller = asRecord(fields[controllerId]);

	if (!Object.keys(controller).length) {
		return false;
	}

	const nextSeen = new Set(seen);
	nextSeen.add(fieldId);

	if (!isFieldDependencySatisfied(controllerId, fields, values, dependencies, nextSeen)) {
		return false;
	}

	const actual = Object.prototype.hasOwnProperty.call(values, controllerId)
		? values[controllerId]
		: controller.default;

	return dependencyMatches(actual, dependency.operator, dependency.value);
};

module.exports = {
	dependencyMatches,
	dependencyScalar,
	isFieldDependencySatisfied,
};
