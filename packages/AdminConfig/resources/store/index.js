// @ts-check

const { createReduxStore, registerStore } = require('@wordpress/data');

const STORE_NAME = 'lerm/admin-config';

/**
 * @typedef {'idle'|'loading'|'saving'|'error'|'ready'} SchemaStatus
 */

/**
 * @typedef {{
 *   values: Record<string, unknown>,
 *   savedValues: Record<string, unknown>,
 *   status: SchemaStatus,
 *   errors: Record<string, string|string[]>,
 *   message: string,
 * }} SchemaSlice
 */

/**
 * @typedef {{
 *   schemas: Record<string, SchemaSlice>,
 * }} StoreState
 */

/** @type {import('@wordpress/data').WPDataStore} */
const DEFAULT_STATE = {
	schemas: {},
};

/**
 * @param {SchemaSlice|undefined} slice
 * @returns {boolean}
 */
const sliceIsDirty = (slice) => {
	if (!slice) {
		return false;
	}

	return JSON.stringify(slice.values) !== JSON.stringify(slice.savedValues);
};

/**
 * @param {SchemaSlice} slice
 * @param {Record<string, unknown>} values
 * @returns {SchemaSlice}
 */
const withValues = (slice, values) => ({
	...slice,
	values,
	errors: {},
});

/**
 * @param {SchemaSlice} slice
 * @returns {SchemaSlice}
 */
const markSaved = (slice) => ({
	...slice,
	savedValues: slice.values,
});

const storeConfig = {
	reducer(state = DEFAULT_STATE, action) {
		switch (action.type) {
			case 'SCHEMA_LOADED':
				return {
					...state,
					schemas: {
						...state.schemas,
						[action.schemaId]: {
							values: action.values,
							savedValues: action.values,
							status: 'ready',
							errors: {},
							message: '',
						},
					},
				};

			case 'VALUES_CHANGED':
				return {
					...state,
					schemas: {
						...state.schemas,
						[action.schemaId]: withValues(
							state.schemas[action.schemaId] || {
								values: {},
								savedValues: {},
								status: 'idle',
								errors: {},
								message: '',
							},
							action.values,
						),
					},
				};

			case 'SAVE_STARTED':
				return {
					...state,
					schemas: {
						...state.schemas,
						[action.schemaId]: {
							...(state.schemas[action.schemaId] || {
								values: {},
								savedValues: {},
								status: 'idle',
								errors: {},
								message: '',
							}),
							status: 'saving',
							errors: {},
							message: '',
						},
					},
				};

			case 'SAVE_SUCCEEDED':
				return {
					...state,
					schemas: {
						...state.schemas,
						[action.schemaId]: markSaved({
							...(state.schemas[action.schemaId] || {
								values: {},
								savedValues: {},
								status: 'idle',
								errors: {},
								message: '',
							}),
							status: 'ready',
							message: action.message || '',
						}),
					},
				};

			case 'SAVE_FAILED':
				return {
					...state,
					schemas: {
						...state.schemas,
						[action.schemaId]: {
							...(state.schemas[action.schemaId] || {
								values: {},
								savedValues: {},
								status: 'idle',
								errors: {},
								message: '',
							}),
							status: 'error',
							errors: action.errors || {},
							message: action.message || '',
						},
					},
				};

			default:
				return state;
		}
	},

	actions: {
		/**
		 * @param {string} schemaId
		 * @param {Record<string, unknown>} values
		 */
		schemaLoaded: (schemaId, values) => ({
			type: 'SCHEMA_LOADED',
			schemaId,
			values,
		}),

		/**
		 * @param {string} schemaId
		 * @param {Record<string, unknown>} values
		 */
		valuesChanged: (schemaId, values) => ({
			type: 'VALUES_CHANGED',
			schemaId,
			values,
		}),

		/**
		 * @param {string} schemaId
		 */
		saveStarted: (schemaId) => ({
			type: 'SAVE_STARTED',
			schemaId,
		}),

		/**
		 * @param {string} schemaId
		 * @param {string} [message]
		 */
		saveSucceeded: (schemaId, message) => ({
			type: 'SAVE_SUCCEEDED',
			schemaId,
			message,
		}),

		/**
		 * @param {string} schemaId
		 * @param {Record<string, string|string[]>} [errors]
		 * @param {string} [message]
		 */
		saveFailed: (schemaId, errors, message) => ({
			type: 'SAVE_FAILED',
			schemaId,
			errors,
			message,
		}),
	},

	selectors: {
		/**
		 * @param {StoreState} state
		 * @param {string} schemaId
		 * @returns {SchemaSlice|undefined}
		 */
		getSchemaState: (state, schemaId) => state.schemas[schemaId],

		/**
		 * @param {StoreState} state
		 * @param {string} schemaId
		 * @returns {Record<string, unknown>}
		 */
		getValues: (state, schemaId) => {
			const slice = state.schemas[schemaId];

			return slice ? slice.values : {};
		},

		/**
		 * @param {StoreState} state
		 * @param {string} schemaId
		 * @returns {boolean}
		 */
		isDirty: (state, schemaId) => sliceIsDirty(state.schemas[schemaId]),

		/**
		 * @param {StoreState} state
		 * @param {string} schemaId
		 * @returns {SchemaStatus}
		 */
		getStatus: (state, schemaId) => {
			const slice = state.schemas[schemaId];

			return slice ? slice.status : 'idle';
		},

		/**
		 * @param {StoreState} state
		 * @param {string} schemaId
		 * @returns {Record<string, string|string[]>}
		 */
		getErrors: (state, schemaId) => {
			const slice = state.schemas[schemaId];

			return slice ? slice.errors : {};
		},

		/**
		 * @param {StoreState} state
		 * @param {string} schemaId
		 * @returns {string}
		 */
		getMessage: (state, schemaId) => {
			const slice = state.schemas[schemaId];

			return slice ? slice.message : '';
		},
	},
};

const store = createReduxStore(STORE_NAME, storeConfig);

/** @type {boolean} */
let isRegistered = false;

/**
 * Register the store with wp.data. Safe to call multiple times — registers
 * only once. Call this during runtime bootstrap (e.g. DOMContentLoaded or
 * block-panel mount) to ensure wp.data is available.
 *
 * In Node.js test environments (no wp.data global), the call is silently
 * skipped so that the module can still be required for testing.
 */
const register = () => {
	if (isRegistered) {
		return;
	}

	try {
		registerStore(store);
		isRegistered = true;
	} catch (_error) {
		// Silently skip registration in environments without wp.data (e.g. Node.js tests).
	}
};

module.exports = {
	STORE_NAME,
	storeConfig,
	store,
	register,
};
