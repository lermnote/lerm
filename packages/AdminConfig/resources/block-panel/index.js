// @ts-check

const { createAdminConfigRestClient } = require('../core/rest-client');
const { contextFromConfig, contextQueryString } = require('../core/context');
const {
	createSchemaState,
	hydrateSchemaResponse,
	isSchemaStateDirty,
	serializeSavePayload,
	withFieldValue,
	withRestError,
	withStatus,
	withValues,
} = require('../core/schema-state');
const { createDefaultControlRegistry } = require('../controls');
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
	const controls = createDefaultControlRegistry();
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
		isDirty: () => isSchemaStateDirty(state),

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

		discardChanges() {
			state = withValues(state, state.savedValues);
			state = withStatus(state, 'ready', 'Unsaved changes discarded.');

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
			state = withStatus(state, 'ready', String(response.data.message || 'Settings saved.'));

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
	const fields = asRecord(schema.fields);
	const sections = asRecord(schema.sections);

	if (!Object.keys(sections).length) {
		return Object.keys(fields).length;
	}

	return Object.values(sections).reduce((count, section) => {
		const sectionRecord = asRecord(section);
		const sectionFields = Array.isArray(sectionRecord.fields)
			? sectionRecord.fields
			: Object.values(asRecord(sectionRecord.fields));

		return count + (Array.isArray(sectionFields) ? sectionFields.length : 0);
	}, 0);
};

/**
 * @param {Record<string, unknown>} field
 * @returns {string}
 */
const fieldControlType = (field) => {
	const client = asRecord(field.client);

	return String(client.control || field.type || 'text');
};

/**
 * @param {Record<string, unknown>} schema
 * @returns {Array<Record<string, unknown>>}
 */
const orderedSections = (schema) => {
	const fields = asRecord(schema.fields);
	const sections = asRecord(schema.sections);

	if (!Object.keys(sections).length) {
		return [
			{
				id: 'general',
				title: '',
				description: '',
				fields: Object.values(fields).map(asRecord),
			},
		];
	}

	return Object.entries(sections).map(([ sectionId, section ]) => {
		const sectionRecord = asRecord(section);
		const fieldIds = Array.isArray(sectionRecord.fields) ? sectionRecord.fields : [];
		const sectionFields = fieldIds
			.map((fieldId) => asRecord(fields[String(fieldId)]))
			.filter((field) => String(field.id || '') !== '');

		return {
			description: String(sectionRecord.description || ''),
			fields: sectionFields,
			id: String(sectionRecord.id || sectionId),
			title: String(sectionRecord.title || ''),
		};
	});
};

/**
 * @param {Record<string, string|string[]>} errors
 * @param {string} fieldId
 * @returns {string}
 */
const fieldError = (errors, fieldId) => {
	const error = errors[fieldId];

	if (Array.isArray(error)) {
		return error.map(String).filter(Boolean).join(' ');
	}

	return String(error || '');
};

/**
 * @param {Record<string, unknown>} values
 * @param {string} fieldId
 * @param {unknown} fallback
 * @returns {unknown}
 */
const fieldValue = (values, fieldId, fallback = '') => (
	Object.prototype.hasOwnProperty.call(values, fieldId) ? values[fieldId] : fallback
);

/**
 * @param {Function} createElement
 * @param {Record<string, unknown>} field
 * @param {string} controlType
 * @param {string} error
 * @returns {unknown}
 */
const renderUnsupportedField = (createElement, field, controlType, error = '') => {
	const fieldId = String(field.id || '');
	const label = String(field.label || fieldId);
	const description = String(field.description || '');

	return createElement(
		'div',
		{
			className: `lerm-admin-config-block-panel__field lerm-admin-config-block-panel__unsupported-field${ error ? ' is-error' : '' }`,
			'data-field-id': fieldId,
			'data-field-type': controlType,
			'data-unsupported-control': 'true',
			key: fieldId,
			role: 'note',
		},
		[
			createElement('strong', { key: 'label' }, label),
			description
				? createElement('p', { className: 'lerm-admin-config-block-panel__field-description', key: 'description' }, description)
				: null,
			createElement(
				'p',
				{
					className: 'lerm-admin-config-block-panel__unsupported-message',
					key: 'unsupported',
				},
				`Field type "${ controlType }" is not available in the block editor panel yet.`
			),
			error
				? createElement(
					'p',
					{
						className: 'lerm-admin-config-block-panel__field-error',
						'data-field-error': fieldId,
						key: `${ fieldId }-error`,
					},
					error
				)
				: null,
		].filter(Boolean)
	);
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
		const errorCount = Object.keys(state.errors || {}).length;
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
				status === 'ready' ? 'Ready' : status.charAt(0).toUpperCase() + status.slice(1)
			),
		];

		if (message) {
			body.push(createElement(
				'p',
				{
					className: 'lerm-admin-config-block-panel__message',
					'data-error-count': String(errorCount),
					key: 'message',
					role: status === 'error' ? 'alert' : 'status',
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

		const components = asRecord((typeof window !== 'undefined' && window.wp && window.wp.components) || {});
		const sections = orderedSections(state.schema);
		const dirty = isSchemaStateDirty(state);
		const isBusy = status === 'loading' || status === 'saving';
		const canRenderFields = status === 'ready' || (status === 'error' && Object.keys(state.schema || {}).length > 0);
		const Button = components.Button;
		const Spinner = components.Spinner;
		const fieldBody = [];
		let hasEditableFields = false;

		if (status === 'loading' && typeof Spinner === 'function') {
			fieldBody.push(createElement(Spinner, { key: 'spinner' }));
		}

		if (canRenderFields) {
			sections.forEach((section) => {
				const fields = Array.isArray(section.fields) ? section.fields : [];
				const renderedFields = fields.map((field) => {
					const fieldId = String(field.id || '');
					const controlType = fieldControlType(field);
					const control = runtime.controls.get(controlType);

					if (!fieldId) {
						return null;
					}

					const error = fieldError(state.errors || {}, fieldId);

					if (!control) {
						return renderUnsupportedField(createElement, field, controlType, error);
					}

					hasEditableFields = true;

					return createElement(
						'div',
						{
							className: `lerm-admin-config-block-panel__field${ error ? ' is-error' : '' }`,
							'data-field-id': fieldId,
							'data-field-type': controlType,
							key: fieldId,
						},
						[
							control({
								components,
								createElement,
								error,
								field,
								inputId: `${ panelSlug(String(config.schemaId || 'schema')) }-${ fieldId }`,
								onChange: (value) => {
									runtime.updateValue(fieldId, value);
									setState(runtime.getState());
								},
								value: fieldValue(state.values || {}, fieldId, field.default),
							}),
							error
								? createElement(
									'p',
									{
										className: 'lerm-admin-config-block-panel__field-error',
										'data-field-error': fieldId,
										key: `${ fieldId }-error`,
									},
									error
								)
								: null,
						].filter(Boolean)
					);
				}).filter(Boolean);

				if (!renderedFields.length) {
					return;
				}

				fieldBody.push(createElement(
					'section',
					{
						className: 'lerm-admin-config-block-panel__section',
						'data-section-id': section.id,
						key: section.id,
					},
					[
						section.title
							? createElement('h3', { key: 'title' }, section.title)
							: null,
						section.description
							? createElement('p', { className: 'lerm-admin-config-block-panel__section-description', key: 'description' }, section.description)
							: null,
						...renderedFields,
					].filter(Boolean)
				));
			});
		}

		if (fieldBody.length) {
			body.push(...fieldBody);
		}

		if (fieldBody.length && hasEditableFields) {
			body.push(createElement(
				'div',
				{
					className: 'lerm-admin-config-block-panel__actions',
					key: 'actions',
				},
				[
					typeof Button === 'function'
						? createElement(
							Button,
							{
								disabled: isBusy || !dirty,
								isBusy: status === 'saving',
								key: 'save',
								onClick: () => {
									setState(runtime.getState());
									runtime.save().then(() => {
										setState(runtime.getState());
									});
								},
								variant: 'primary',
							},
							status === 'saving' ? 'Saving' : 'Save'
						)
						: createElement(
							'button',
							{
								disabled: isBusy || !dirty,
								key: 'save',
								onClick: () => {
									setState(runtime.getState());
									runtime.save().then(() => {
										setState(runtime.getState());
									});
								},
								type: 'button',
							},
							status === 'saving' ? 'Saving' : 'Save'
						),
					typeof Button === 'function'
						? createElement(
							Button,
							{
								disabled: isBusy || !dirty,
								key: 'discard',
								onClick: () => {
									runtime.discardChanges();
									setState(runtime.getState());
								},
								variant: 'secondary',
							},
							'Discard'
						)
						: createElement(
							'button',
							{
								disabled: isBusy || !dirty,
								key: 'discard',
								onClick: () => {
									runtime.discardChanges();
									setState(runtime.getState());
								},
								type: 'button',
							},
							'Discard'
						),
					createElement(
						'span',
						{
							className: 'lerm-admin-config-block-panel__dirty-state',
							'data-dirty': dirty ? 'true' : 'false',
							key: 'dirty',
						},
						dirty ? 'Unsaved changes' : 'Saved'
					),
				]
			));
		}

		return createElement(
			Panel,
			{
				className: 'lerm-admin-config-block-panel',
				initialOpen: true,
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
					'data-dirty': dirty ? 'true' : 'false',
					'data-error-count': String(errorCount),
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
