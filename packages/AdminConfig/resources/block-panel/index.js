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

const BLOCK_PANEL_CONFIG_GLOBAL = 'lermAdminConfigBlockPanelConfigs';
const LEGACY_BLOCK_PANEL_CONFIG_GLOBAL = 'lermAdminConfigBlockPanelConfig';
const panelInstances = new Map();
const registeredPanelNames = new Set();

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

/**
 * @param {Record<string, unknown>} base
 * @param {Record<string, unknown>} schema
 * @returns {Record<string, unknown>}
 */
const mergePanelConfig = (base, schema = {}) => ({
	...base,
	...schema,
	context: {
		...asRecord(base.context),
		...asRecord(schema.context),
	},
	restNonce: schema.restNonce || base.restNonce,
	restUrl: schema.restUrl || base.restUrl,
	schemaId: schema.schemaId || schema.schema_id || base.schemaId || base.schema_id || '',
});

/**
 * @returns {Array<Record<string, unknown>>}
 */
const blockPanelConfigsFromWindow = () => {
	if (typeof window === 'undefined') return [];

	const groupedConfigs = asRecordArray(window[BLOCK_PANEL_CONFIG_GLOBAL]);
	const legacyConfig = asRecord(window[LEGACY_BLOCK_PANEL_CONFIG_GLOBAL]);
	const groups = groupedConfigs.length ? groupedConfigs : (Object.keys(legacyConfig).length ? [ legacyConfig ] : []);
	const configs = [];

	for (const group of groups) {
		const schemas = asRecordArray(group.schemas);

		if (!schemas.length) {
			configs.push(mergePanelConfig({}, group));
			continue;
		}

		for (const schema of schemas) {
			configs.push(mergePanelConfig(group, schema));
		}
	}

	return configs.filter((config) => String(config.schemaId || '') !== '');
};

/**
 * @param {string} value
 * @returns {string}
 */
const panelSlug = (value) => String(value || '')
	.toLowerCase()
	.replace(/[^a-z0-9_-]+/g, '-')
	.replace(/^-+|-+$/g, '') || 'schema';

/**
 * @param {Record<string, unknown>} config
 * @returns {string}
 */
const panelName = (config) => `lerm-admin-config-${panelSlug(String(config.schemaId || 'schema'))}`;

/**
 * @param {Record<string, unknown>} schema
 * @returns {number}
 */
const fieldCount = (schema) => {
	const sections = asRecord(schema.sections);

	return Object.values(sections).reduce((count, section) => {
		const fields = asRecord(section).fields;

		return count + (Array.isArray(fields) ? fields.length : 0);
	}, 0);
};

/**
 * @param {Record<string, unknown>} config
 * @param {Function} Panel
 * @param {{ createElement: Function, useEffect: Function, useMemo: Function, useState: Function }} element
 */
const createPanelComponent = (config, Panel, element) => {
	const { createElement, useEffect, useMemo, useState } = element;

	return function AdminConfigBlockPanel() {
		const runtime = useMemo(() => createBlockPanelRuntime(config), []);
		const [ state, setState ] = useState(runtime.getState());
		const instanceKey = panelName(config);

		useEffect(() => {
			let active = true;

			panelInstances.set(instanceKey, runtime);

			if (!runtime.rest.hasTransport()) {
				setState(withStatus(runtime.getState(), 'error', 'REST transport is unavailable.'));
				return () => {
					active = false;
					panelInstances.delete(instanceKey);
				};
			}

			setState(runtime.getState());
			runtime.loadSchema(String(config.schemaId || ''), asRecord(config.context)).then(() => {
				if (active) {
					setState(runtime.getState());
				}
			});

			return () => {
				active = false;
				panelInstances.delete(instanceKey);
			};
		}, [ runtime ]);

		const status = String(state.status || 'idle');
		const message = String(state.message || '');
		const title = String(config.title || 'Admin Config');
		const context = asRecord(config.context);
		const postId = String(context.post_id || '');
		const body = [
			createElement(
				'p',
				{
					className: 'lerm-admin-config-block-panel__status',
					key: 'status',
				},
				status === 'ready' ? 'Ready' : status
			),
		];

		if (message) {
			body.push(createElement(
				'p',
				{
					className: 'lerm-admin-config-block-panel__message',
					key: 'message',
				},
				message
			));
		}

		if (status === 'ready') {
			body.push(createElement(
				'p',
				{
					className: 'lerm-admin-config-block-panel__meta',
					key: 'meta',
				},
				`${ fieldCount(state.schema) } fields loaded`
			));
		}

		return createElement(
			Panel,
			{
				className: 'lerm-admin-config-block-panel',
				name: panelSlug(String(config.schemaId || 'schema')),
				title,
			},
			createElement(
				'div',
				{
					'data-lerm-admin-config-block-panel': 'true',
					'data-post-id': postId,
					'data-schema-id': String(config.schemaId || ''),
					'data-status': status,
				},
				body
			)
		);
	};
};

/**
 * @param {Array<Record<string, unknown>>} [configs]
 * @returns {boolean}
 */
const registerBlockEditorPanels = (configs = blockPanelConfigsFromWindow()) => {
	if (typeof window === 'undefined') return false;

	const wp = window.wp || {};
	const registerPlugin = wp.plugins && wp.plugins.registerPlugin;
	const editorPackage = wp.editPost || wp.editor || {};
	const Panel = editorPackage.PluginDocumentSettingPanel;
	const element = wp.element || {};

	if (
		typeof registerPlugin !== 'function' ||
		typeof Panel !== 'function' ||
		typeof element.createElement !== 'function' ||
		typeof element.useEffect !== 'function' ||
		typeof element.useMemo !== 'function' ||
		typeof element.useState !== 'function'
	) {
		return false;
	}

	for (const config of configs) {
		const name = panelName(config);

		if (registeredPanelNames.has(name)) {
			continue;
		}

		registeredPanelNames.add(name);
		registerPlugin(name, {
			render: createPanelComponent(config, Panel, element),
		});
	}

	return registeredPanelNames.size > 0;
};

if (typeof window !== 'undefined') {
	window.lermAdminConfigBlockPanel = {
		createRuntime: createBlockPanelRuntime,
		getInstances: () => Array.from(panelInstances.entries()).map(([ name, runtime ]) => ({
			context: runtime.getContext(),
			name,
			schemaId: runtime.getSchemaId(),
			state: runtime.getState(),
		})),
		registerPanels: registerBlockEditorPanels,
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => registerBlockEditorPanels(), { once: true });
	} else {
		registerBlockEditorPanels();
	}
}

module.exports = {
	createBlockPanelRuntime,
	registerBlockEditorPanels,
};
