// @ts-check

const { createAdminConfigRestClient } = require('../core/rest-client');
const { contextFromConfig, contextQueryString } = require('../core/context');
const {
	createSchemaState,
	hydrateSchemaResponse,
	serializeSavePayload,
	withFieldValue,
	withRestError,
	withStatus,
	withValues,
} = require('../core/schema-state');
const { createControlRegistry } = require('../controls');
const { STORE_NAME } = require('../store');

/**
 * @param {Record<string, unknown>} config
 * @param {{ restClient?: { hasTransport: () => boolean, request: (path: string, options?: Record<string, unknown>) => Promise<{ success: boolean, data: Record<string, unknown> }> } }} options
 */
const createBlockPanelRuntime = (config = {}, options = {}) => {
	const controls = createControlRegistry();
	const rest = options.restClient || createAdminConfigRestClient({ getConfig: () => config });
	let context = contextFromConfig(config);
	let schemaId = String(config.schemaId || config.schema_id || '');
	let state = createSchemaState({}, {}, context, schemaId);

	/**
	 * @param {string} path
	 * @returns {string}
	 */
	const withContext = (path) => {
		const queryString = contextQueryString(context);

		return queryString ? `${path}?${queryString}` : path;
	};

	/**
	 * @param {unknown} response
	 * @returns {{ success: boolean, data: Record<string, unknown> }}
	 */
	const normalizeResponse = (response) => {
		const candidate = /** @type {{ success?: unknown, data?: unknown }} */ (response || {});

		return {
			success: candidate.success === true,
			data: candidate.data && typeof candidate.data === 'object'
				? /** @type {Record<string, unknown>} */ (candidate.data)
				: {},
		};
	};

	return {
		controls,
		rest,
		storeName: STORE_NAME,

		getContext: () => ({ ...context }),
		getSchemaId: () => schemaId,
		getState: () => state,

		/**
		 * @param {Record<string, unknown>} nextContext
		 */
		setContext(nextContext) {
			context = contextFromConfig(nextContext);
			state = {
				...state,
				context,
			};

			return state;
		},

		/**
		 * @param {string} nextSchemaId
		 * @param {Record<string, unknown>} [nextContext]
		 */
		async loadSchema(nextSchemaId = schemaId, nextContext = context) {
			schemaId = String(nextSchemaId || schemaId);
			context = contextFromConfig(nextContext);
			state = withStatus({ ...state, context, schemaId }, 'loading');

			const response = normalizeResponse(await rest.request(withContext(`schema/${schemaId}`)));

			if (!response.success) {
				state = withRestError(state, response.data, 'Unable to load the schema.');
				return response;
			}

			state = hydrateSchemaResponse(state, response.data, context, schemaId);

			return response;
		},

		/**
		 * @param {string|string[]} path
		 * @param {unknown} value
		 */
		updateValue(path, value) {
			state = withFieldValue(state, path, value);
			return state;
		},

		/**
		 * @param {Record<string, unknown>} [values]
		 */
		async save(values = state.values) {
			state = withStatus(state, 'saving');

			const response = normalizeResponse(
				await rest.request(
					withContext(`schema/${schemaId}/save`),
					{
						data: serializeSavePayload(state, values),
						method: 'POST',
					}
				)
			);

			if (!response.success) {
				state = withRestError(state, response.data, 'Unable to save the schema.');
				return response;
			}

			state = withValues(state, response.data.values && typeof response.data.values === 'object'
				? /** @type {Record<string, unknown>} */ (response.data.values)
				: values);

			return response;
		},
	};
};

if (typeof window !== 'undefined') {
	window.lermAdminConfigBlockPanel = {
		createRuntime: createBlockPanelRuntime,
	};
}

module.exports = {
	createBlockPanelRuntime,
};
