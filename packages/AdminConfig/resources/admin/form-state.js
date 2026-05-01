// @ts-check

/**
 * @param {string} token
 * @returns {boolean}
 */
const isArrayToken = (token) => token === '' || /^\d+$/.test(String(token));

/**
 * @param {unknown} nextToken
 * @returns {Record<string, unknown>|unknown[]}
 */
const createStateContainer = (nextToken) => isArrayToken(String(nextToken ?? '')) ? [] : {};

/**
 * Build helpers for reading and comparing the current option-field state.
 *
 * @param {{ getOptionName: (form: HTMLFormElement) => string }} options
 */
const createFormStateHelpers = ({ getOptionName }) => {
	/**
	 * @param {HTMLFormElement} form
	 * @param {string} name
	 * @returns {string[]}
	 */
	const optionFieldTokens = (form, name) => {
		const prefix = `${getOptionName(form)}[`;
		if (!name.startsWith(prefix)) return [];
		const suffix = name.slice(prefix.length);
		if (!suffix.endsWith(']')) return [];
		return suffix.slice(0, -1).split('][');
	};

	/**
	 * @param {Record<string, unknown>|unknown[]} state
	 * @param {string[]} tokens
	 * @param {FormDataEntryValue} value
	 */
	const assignStateValue = (state, tokens, value) => {
		if (!tokens.length) return;
		let cursor = state;

		for (let i = 0; i < tokens.length; i += 1) {
			const token = tokens[i];
			const isLast = i === tokens.length - 1;
			const targetKey = token === '' && Array.isArray(cursor)
				? String(cursor.length)
				: token;

			if (isLast) {
				if (Array.isArray(cursor)) {
					cursor[Number(targetKey)] = String(value);
					return;
				}

				const objectCursor = /** @type {Record<string, unknown>} */ (cursor);
				objectCursor[targetKey] = String(value);
				return;
			}

			const nextToken = tokens[i + 1] ?? '';
			if (Array.isArray(cursor)) {
				const index = Number(targetKey);
				if (cursor[index] === undefined) cursor[index] = createStateContainer(nextToken);
				cursor = /** @type {Record<string, unknown>|unknown[]} */ (cursor[index]);
				continue;
			}

			const objectCursor = /** @type {Record<string, unknown>} */ (cursor);
			if (objectCursor[targetKey] === undefined) objectCursor[targetKey] = createStateContainer(nextToken);
			cursor = /** @type {Record<string, unknown>|unknown[]} */ (objectCursor[targetKey]);
		}
	};

	/**
	 * @param {unknown} value
	 * @returns {string}
	 */
	const stableStateString = (value) => {
		if (Array.isArray(value)) return `[${value.map(stableStateString).join(',')}]`;
		if (value && typeof value === 'object') {
			return `{${Object.keys(value).sort().map((key) => `${JSON.stringify(key)}:${stableStateString(/** @type {Record<string, unknown>} */ (value)[key])}`).join(',')}}`;
		}
		return JSON.stringify(value ?? null);
	};

	/**
	 * @param {HTMLFormElement} form
	 * @returns {Record<string, unknown>}
	 */
	const readFormState = (form) => {
		const state = {};
		for (const [ name, value ] of new FormData(form).entries()) {
			const tokens = optionFieldTokens(form, String(name));
			if (!tokens.length) continue;
			assignStateValue(state, tokens, value);
		}
		return state;
	};

	/**
	 * @param {unknown} value
	 * @returns {Record<string, unknown>}
	 */
	const cloneState = (value) => /** @type {Record<string, unknown>} */ (JSON.parse(JSON.stringify(value ?? {})));

	return {
		assignStateValue,
		cloneState,
		optionFieldTokens,
		readFormState,
		stableStateString,
	};
};

module.exports = {
	createFormStateHelpers,
};
