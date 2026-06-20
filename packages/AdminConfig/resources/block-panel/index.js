// @ts-check

const { createAdminConfigRestClient, normalizeRestError } = require('../core/rest-client');
const { contextFromConfig, contextQueryString } = require('../core/context');
const { isFieldDependencySatisfied } = require('../core/dependencies');
const { asRecord, asRecordArray } = require('../core/records');
const { fieldControlType } = require('../core/utils');
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
const { register: registerStore, STORE_NAME } = require('../store');
const { __, _n, sprintf } = require('../i18n');

const BLOCK_PANEL_CONFIG_GLOBAL = 'lermAdminConfigBlockPanelConfigs';
const panelInstances = new Map();
const registeredPanelNames = new Set();
const READ_ONLY_CONTROL_TYPES = new Set([
	'accordion',
	'backup_tools',
	'code_editor',
	'content',
	'heading',
	'notice',
	'sorter',
	'subheading',
	'tabbed',
	'wp_editor',
]);

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
	 * @param {Record<string, unknown>} params
	 * @returns {Promise<{ success: boolean, data: Record<string, unknown> }>}
	 */
	const requestDataSource = (params = {}) => {
		const body = new FormData();
		const selected = Array.isArray(params.selected) ? params.selected : [];

		body.set('field_id', String(params.fieldId || ''));
		body.set('search', String(params.search || ''));
		body.set('page', String(params.page || 1));
		body.set('per_page', String(params.perPage || 20));

		selected.map(String).filter(Boolean).forEach((value) => {
			body.append('selected[]', value);
		});

		return rest.request(withContext(`schemas/${ schemaId }/data-source`), {
			body,
			method: 'POST',
		});
	};

	return {
		controls,
		rest,
		requestDataSource,
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

			let schemaResponse;
			let valuesResponse;

			try {
				schemaResponse = await rest.request(withContext(`schemas/${schemaId}`));
			} catch (error) {
				schemaResponse = normalizeRestError(error, 'Unable to load the schema.');
			}

			if (!schemaResponse.success) {
				state = withRestError(state, schemaResponse.data, 'Unable to load the schema.');
				return schemaResponse;
			}

			try {
				valuesResponse = await rest.request(withContext(`schemas/${schemaId}/values`));
			} catch (error) {
				valuesResponse = normalizeRestError(error, 'Unable to load the schema values.');
			}

			if (!valuesResponse.success) {
				state = withRestError(state, valuesResponse.data, 'Unable to load the schema values.');
				return valuesResponse;
			}

			state = hydrateSchemaResponse(
				state,
				{
					schema: schemaResponse.data,
					values: valuesResponse.data.values,
				},
				context,
				schemaId
			);

			return {
				success: true,
				data: {
					schema: schemaResponse.data,
					values: valuesResponse.data.values,
				},
			};
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
			state = withStatus(state, 'ready', __('Unsaved changes discarded.', 'lerm-admin-config'));

			return state;
		},

		/**
		 * @param {Record<string, unknown>} [values]
		 */
		async save(values = state.values) {
			state = withStatus(state, 'saving');

			let response;

			try {
				response = await rest.request(
					withContext(`schemas/${schemaId}/values`),
					{
						data: serializeSavePayload(state, values),
						method: 'POST',
					}
				);
			} catch (error) {
				response = normalizeRestError(error, 'Unable to save the schema.');
			}

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
	const configs = [];

	for (const group of groupedConfigs) {
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
 * @param {string} controlType
 * @param {unknown} control
 * @returns {'editable'|'read-only'|'unsupported'}
 */
const fieldControlStatus = (field, controlType, control) => {
	if (field.supported === false) {
		return 'unsupported';
	}

	if (field.readOnly === true) {
		return 'read-only';
	}

	if (typeof control === 'function') {
		return 'editable';
	}

	return READ_ONLY_CONTROL_TYPES.has(controlType) ? 'read-only' : 'unsupported';
};

/**
 * @returns {Array<string>}
 */
const blockPanelReadOnlyControlTypes = () => Array.from(READ_ONLY_CONTROL_TYPES).sort();

/**
 * @param {Function} createElement
 * @param {Record<string, unknown>} field
 * @param {string} controlType
 * @param {'read-only'|'unsupported'} status
 * @param {string} error
 * @returns {unknown}
 */
const renderUnavailableField = (createElement, field, controlType, status, error = '') => {
	const fieldId = String(field.id || '');
	const label = String(field.label || fieldId);
	const description = String(field.description || '');
	const isReadOnly = status === 'read-only';

	return createElement(
		'div',
		{
			className: `lerm-admin-config-block-panel__field lerm-admin-config-block-panel__${ isReadOnly ? 'readonly' : 'unsupported' }-field${ error ? ' is-error' : '' }`,
			'data-control-status': status,
			'data-field-id': fieldId,
			'data-field-type': controlType,
			'data-read-only-control': isReadOnly ? 'true' : undefined,
			'data-unsupported-control': isReadOnly ? undefined : 'true',
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
				isReadOnly
					? sprintf(__('Field type "%s" is read-only in the block editor panel.', 'lerm-admin-config'), controlType)
					: sprintf(__('Field type "%s" is not available in the block editor panel yet.', 'lerm-admin-config'), controlType)
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
	const { createElement, useEffect, useMemo, useRef, useState } = element;

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

		const statusLabels = {
			error: __('Error', 'lerm-admin-config'),
			idle: __('Idle', 'lerm-admin-config'),
			loading: __('Loading…', 'lerm-admin-config'),
			ready: __('Ready', 'lerm-admin-config'),
			saving: __('Saving…', 'lerm-admin-config'),
		};
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
				statusLabels[status] || status.charAt(0).toUpperCase() + status.slice(1)
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
			const fieldCountValue = fieldCount(state.schema);
			body.push(createElement(
				'p',
				{
					className: 'lerm-admin-config-block-panel__meta',
					key: 'meta',
				},
				sprintf(_n('%d field loaded', '%d fields loaded', fieldCountValue, 'lerm-admin-config'), fieldCountValue)
			));
		}

		let components = {};
		try {
			components = asRecord(require('@wordpress/components'));
		} catch (_e) {
			// Fallback for environments without @wordpress/components (e.g. Node.js tests).
		}
		const sections = orderedSections(state.schema);
		const fieldsById = asRecord(state.schema.fields);
		const dependencies = asRecord(state.schema.dependencies);
		const dirty = isSchemaStateDirty(state);
		const dirtyRef = useRef(false);
		dirtyRef.current = dirty;

		useEffect(() => {
			const handleBeforeUnload = (event) => {
				if (dirtyRef.current) {
					event.preventDefault();
					event.returnValue = '';
				}
			};
			window.addEventListener('beforeunload', handleBeforeUnload);
			return () => window.removeEventListener('beforeunload', handleBeforeUnload);
		}, []);

		useEffect(() => {
			let dispatch;
			try {
				dispatch = require('@wordpress/data').dispatch;
			} catch (_e) {
				dispatch = null;
			}
			const editorDispatch = dispatch ? dispatch('core/editor') : null;
			if (dirty && editorDispatch && typeof editorDispatch.lockPostSaving === 'function') {
				editorDispatch.lockPostSaving('lerm-admin-config-unsaved');
			} else if (!dirty && editorDispatch && typeof editorDispatch.unlockPostSaving === 'function') {
				editorDispatch.unlockPostSaving('lerm-admin-config-unsaved');
			}
			return () => {
				if (editorDispatch && typeof editorDispatch.unlockPostSaving === 'function') {
					editorDispatch.unlockPostSaving('lerm-admin-config-unsaved');
				}
			};
		}, [dirty]);
		const isBusy = status === 'loading' || status === 'saving';
		const canRenderFields = status === 'ready' || (status === 'error' && Object.keys(state.schema || {}).length > 0);
		const Button = components.Button;
		const Spinner = components.Spinner;
		const Modal = components.Modal;
		const [showDiscardDialog, setShowDiscardDialog] = useState(false);
		const fieldBody = [];
		const syncPanelState = () => setState(runtime.getState());
		const saveChanges = () => {
			const savePromise = runtime.save();

			syncPanelState();
			savePromise.then(syncPanelState, syncPanelState);
		};
		const discardChanges = () => {
			if (typeof Modal === 'function') {
				setShowDiscardDialog(true);
				return;
			}

			const shouldDiscard = typeof window === 'undefined' ||
				typeof window.confirm !== 'function' ||
				window.confirm('Discard unsaved AdminConfig changes?');

			if (!shouldDiscard) {
				return;
			}

			runtime.discardChanges();
			syncPanelState();
		};

		const handleConfirmDiscard = () => {
			setShowDiscardDialog(false);
			runtime.discardChanges();
			syncPanelState();
		};
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
					const controlStatus = fieldControlStatus(field, controlType, control);

					if (!fieldId) {
						return null;
					}

					if (!isFieldDependencySatisfied(fieldId, fieldsById, asRecord(state.values), dependencies)) {
						return null;
					}

					const error = fieldError(state.errors || {}, fieldId);

					if (controlStatus !== 'editable') {
						return renderUnavailableField(createElement, field, controlType, controlStatus, error);
					}

					hasEditableFields = true;

					return createElement(
						'div',
						{
							className: `lerm-admin-config-block-panel__field${ error ? ' is-error' : '' }`,
							'data-control-status': 'editable',
							'data-field-id': fieldId,
							'data-field-type': controlType,
							key: fieldId,
						},
						[
							control({
								components,
								controls: runtime.controls,
								createElement,
								dataSourceRequest: runtime.requestDataSource,
								error,
								errors: state.errors || {},
								field,
								inputId: `${ panelSlug(String(config.schemaId || 'schema')) }-${ fieldId }`,
								onChange: (value) => {
									runtime.updateValue(fieldId, value);
									setState(runtime.getState());
								},
								onPathChange: (path, value) => {
									runtime.updateValue(path, value);
									setState(runtime.getState());
								},
								path: [ fieldId ],
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
			if (dirty) {
				body.push(createElement(
					'div',
					{
						className: 'lerm-admin-config-block-panel__save-notice',
						key: 'save-notice',
					},
					__('Save AdminConfig changes before updating the post — they are stored separately.', 'lerm-admin-config')
				))
			}
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
								onClick: saveChanges,
								variant: 'primary',
							},
							status === 'saving' ? __('Saving', 'lerm-admin-config') : __('Save', 'lerm-admin-config')
						)
						: createElement(
							'button',
							{
								disabled: isBusy || !dirty,
								key: 'save',
								onClick: saveChanges,
								type: 'button',
							},
							status === 'saving' ? __('Saving', 'lerm-admin-config') : __('Save', 'lerm-admin-config')
						),
					typeof Button === 'function'
						? createElement(
							Button,
							{
								disabled: isBusy || !dirty,
								key: 'discard',
								onClick: discardChanges,
								variant: 'secondary',
							},
							__('Discard', 'lerm-admin-config')
						)
						: createElement(
							'button',
							{
								disabled: isBusy || !dirty,
								key: 'discard',
								onClick: discardChanges,
								type: 'button',
							},
							__('Discard', 'lerm-admin-config')
						),
					createElement(
						'span',
						{
							className: 'lerm-admin-config-block-panel__dirty-state',
							'data-dirty': dirty ? 'true' : 'false',
							key: 'dirty',
						},
						dirty ? __('Unsaved changes', 'lerm-admin-config') : __('Saved', 'lerm-admin-config')
					),
				]
			));
		}

		const panelElement = createElement(
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

		if (showDiscardDialog && typeof Modal === 'function') {
			return [
				panelElement,
				createElement(
					Modal,
					{
						key: 'discard-dialog',
						title: __('Discard changes?', 'lerm-admin-config'),
						onRequestClose: () => setShowDiscardDialog(false),
					},
					createElement(
						'div',
						{ style: { padding: '0 0 16px' } },
						createElement('p', null, __('Discard unsaved AdminConfig changes?', 'lerm-admin-config'))
					),
					createElement(
						'div',
						{
							style: {
								display: 'flex',
								gap: '8px',
								justifyContent: 'flex-end',
							},
						},
						typeof Button === 'function'
							? createElement(
								Button,
								{
									key: 'cancel',
									onClick: () => setShowDiscardDialog(false),
									variant: 'secondary',
								},
								__('Cancel', 'lerm-admin-config')
							)
							: null,
						typeof Button === 'function'
							? createElement(
								Button,
								{
									isDestructive: true,
									key: 'confirm',
									onClick: handleConfirmDiscard,
									variant: 'primary',
								},
								__('Discard', 'lerm-admin-config')
							)
							: null
					)
				),
			];
		}

		return panelElement;
	};
};

/**
 * @param {Array<Record<string, unknown>>} [configs]
 * @returns {boolean}
 */
const registerBlockEditorPanels = (configs = blockPanelConfigsFromWindow()) => {
	if (typeof window === 'undefined') return false;

	registerStore();

	let plugins, editor, element;
	try {
		plugins = require('@wordpress/plugins');
	} catch (_e) { plugins = {}; }
	try {
		editor = require('@wordpress/editor');
	} catch (_e) { editor = {}; }
	try {
		element = require('@wordpress/element');
	} catch (_e) { element = {}; }

	const { registerPlugin } = plugins;
	const Panel = editor.PluginDocumentSettingPanel;

	if (
		typeof registerPlugin !== 'function' ||
		typeof Panel !== 'function' ||
		typeof element.createElement !== 'function' ||
		typeof element.useEffect !== 'function' ||
		typeof element.useMemo !== 'function' ||
		typeof element.useRef !== 'function' ||
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
	blockPanelReadOnlyControlTypes,
	createBlockPanelRuntime,
	isFieldDependencySatisfied,
	registerBlockEditorPanels,
};
