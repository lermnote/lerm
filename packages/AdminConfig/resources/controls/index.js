// @ts-check

/**
 * @typedef {(props: Record<string, unknown>) => unknown} FieldControl
 */

/**
 * @param {Record<string, FieldControl>} initialControls
 */
const createControlRegistry = (initialControls = {}) => {
	const controls = new Map(Object.entries(initialControls));

	return {
		/**
		 * @param {string} type
		 * @param {FieldControl} control
		 */
		register(type, control) {
			if (type && typeof control === 'function') {
				controls.set(type, control);
			}
		},

		/**
		 * @param {string} type
		 * @returns {FieldControl|null}
		 */
		get(type) {
			return controls.get(type) || null;
		},

		/**
		 * @returns {string[]}
		 */
		types() {
			return Array.from(controls.keys()).sort();
		},
	};
};

module.exports = {
	createControlRegistry,
};
