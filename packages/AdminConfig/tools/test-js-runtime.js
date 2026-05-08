/* eslint-env node */

const assert = require('assert/strict');

const { contextFromConfig, contextQueryString } = require('../resources/core/context');
const { fieldErrorsFromResponse, messageFromResponse } = require('../resources/core/errors');
const { asRecord, asRecordArray } = require('../resources/core/records');
const { normalizeRestError, restUrl } = require('../resources/core/rest-client');
const {
	createSchemaState,
	hydrateSchemaResponse,
	isSchemaStateDirty,
	serializeSavePayload,
	withFieldValue,
	withRestError,
} = require('../resources/core/schema-state');
const { createDefaultControlRegistry } = require('../resources/controls');
const { blockPanelReadOnlyControlTypes, createBlockPanelRuntime, isFieldDependencySatisfied } = require('../resources/block-panel');

function testRecordHelpers() {
	const record = { title: 'Loaded' };

	assert.equal(asRecord(record), record);
	assert.deepEqual(asRecord(null), {});
	assert.deepEqual(asRecord([]), {});
	assert.deepEqual(asRecord('value'), {});
	assert.deepEqual(asRecordArray([ record, [], null, { id: 'next' } ]), [ record, { id: 'next' } ]);
}

function testContextHelpers() {
	assert.deepEqual(
		contextFromConfig({
			context: {
				post_id: '123',
			},
			term_id: '9',
		}),
		{
			post_id: 123,
			term_id: 9,
		}
	);
	assert.equal(contextQueryString({ post_id: 123, term_id: 9 }), 'post_id=123&term_id=9');
}

function testErrorHelpers() {
	const response = {
		data: {
			fieldErrors: {
				title: 'Required.',
			},
			errors: {
				'group.title': [ 'Nested required.' ],
			},
			message: 'Validation failed.',
		},
	};

	assert.deepEqual(fieldErrorsFromResponse(response), { 'group.title': [ 'Nested required.' ] });
	assert.equal(messageFromResponse(response), 'Validation failed.');
}

function testRestUrlHelpers() {
	const plain = restUrl(
		{ restUrl: 'https://example.test/wp-json/lerm-admin-config/v1/' },
		'schemas/demo?post_id=77'
	);
	const fallback = new URL(restUrl(
		{ restUrl: 'https://example.test/index.php?rest_route=/lerm-admin-config/v1/' },
		'schemas/demo?post_id=77'
	));

	assert.equal(plain, 'https://example.test/wp-json/lerm-admin-config/v1/schemas/demo?post_id=77');
	assert.equal(fallback.searchParams.get('rest_route'), '/lerm-admin-config/v1/schemas/demo');
	assert.equal(fallback.searchParams.get('post_id'), '77');

	assert.deepEqual(
		normalizeRestError(
			{
				code: 'validation_error',
				data: {
					status: 422,
					success: false,
					data: {
						fieldErrors: {
							title: 'Required.',
						},
						message: 'Please review.',
					},
				},
				message: 'Fallback message.',
			},
			'Request failed.'
		),
		{
			success: false,
			data: {
				code: 'validation_error',
				fieldErrors: {
					title: 'Required.',
				},
				message: 'Please review.',
				status: 422,
				success: false,
			},
		}
	);
}

function testSchemaStateHelpers() {
	const state = createSchemaState({}, {}, { post_id: 12 }, 'demo');
	const hydrated = hydrateSchemaResponse(
		state,
		{
			schema: { schemaId: 'demo' },
			values: { title: 'Initial' },
		},
		{ post_id: 12 },
		'demo'
	);
	const updated = withFieldValue(hydrated, 'group.heading', 'Next');
	const updatedArray = withFieldValue(
		{
			...hydrated,
			values: {
				items: [
					{
						name: 'First',
						tags: [ 'alpha' ],
					},
					{
						name: 'Second',
					},
				],
			},
		},
		'items.0.name',
		'Updated'
	);
	const errored = withRestError(updated, {
		fieldErrors: {
			'group.heading': [ 'Too short.' ],
		},
		message: 'Please review.',
	});
	const recovered = withFieldValue(errored, 'group.heading', 'Long enough');
	const mediaState = createSchemaState(
		{
			fields: {
				entry_upload: {
					control: 'upload',
					id: 'entry_upload',
				},
				entry_media: {
					control: 'media',
					id: 'entry_media',
				},
				entry_gallery: {
					control: 'gallery',
					id: 'entry_gallery',
				},
				entry_group: {
					control: 'group',
					fields: [
						{
							control: 'media',
							id: 'image',
						},
						{
							control: 'text',
							id: 'label',
						},
					],
					id: 'entry_group',
				},
			},
		},
		{
			entry_gallery: [
				{ id: 12, url: 'https://example.test/two.png' },
				'13',
			],
			entry_media: {
				id: 11,
				thumbnail: 'https://example.test/one-150x150.png',
				url: 'https://example.test/one.png',
			},
			entry_group: [
				{
					image: {
						id: 14,
						url: 'https://example.test/group.png',
					},
					label: 'Group image',
				},
			],
			entry_upload: 'https://example.test/upload.png',
		}
	);

	assert.equal(hydrated.status, 'ready');
	assert.equal(isSchemaStateDirty(hydrated), false);
	assert.deepEqual(updated.values.group, { heading: 'Next' });
	assert.deepEqual(updatedArray.values.items, [
		{
			name: 'Updated',
			tags: [ 'alpha' ],
		},
		{
			name: 'Second',
		},
	]);
	assert.equal(isSchemaStateDirty(updated), true);
	assert.deepEqual(serializeSavePayload(updated), {
		values: {
			title: 'Initial',
			group: {
				heading: 'Next',
			},
		},
	});
	assert.deepEqual(serializeSavePayload(mediaState), {
		values: {
			entry_gallery: [ 12, 13 ],
			entry_media: {
				id: 11,
			},
			entry_group: [
				{
					image: {
						id: 14,
					},
					label: 'Group image',
				},
			],
			entry_upload: 'https://example.test/upload.png',
		},
	});
	assert.equal(errored.status, 'error');
	assert.equal(errored.message, 'Please review.');
	assert.deepEqual(errored.errors, { 'group.heading': [ 'Too short.' ] });
	assert.equal(recovered.status, 'ready');
	assert.deepEqual(recovered.errors, {});
}

function testDefaultControlRegistry() {
	const registry = createDefaultControlRegistry();
	const types = registry.types();

	assert(types.includes('text'));
	assert(types.includes('textarea'));
	assert(types.includes('border'));
	assert(types.includes('switcher'));
	assert(types.includes('select'));
	assert(types.includes('number'));
	assert(types.includes('slug_text'));
	assert(types.includes('radio'));
	assert(types.includes('button_set'));
	assert(types.includes('color'));
	assert(types.includes('date'));
	assert(types.includes('dimensions'));
	assert(types.includes('fieldset'));
	assert(types.includes('gallery'));
	assert(types.includes('group'));
	assert(types.includes('link_color'));
	assert(types.includes('media'));
	assert(types.includes('slider'));
	assert(types.includes('spinner'));
	assert(types.includes('spacing'));
	assert(types.includes('upload'));

	const rendered = registry.get('text')({
		components: {},
		createElement: (type, props, ...children) => ({ type, props, children }),
		field: {
			id: 'title',
			label: 'Title',
			placeholder: 'Write a title',
			type: 'text',
		},
		inputId: 'demo-title',
		onChange: () => {},
		value: 'Loaded',
	});

	assert.equal(rendered.type, 'input');
	assert.equal(rendered.props.id, 'demo-title');
	assert.equal(rendered.props.placeholder, 'Write a title');
	assert.equal(rendered.props.value, 'Loaded');

	const renderedNumberDefault = registry.get('number')({
		components: {},
		createElement: (type, props, ...children) => ({ type, props, children }),
		field: {
			default: 5,
			id: 'limit',
			label: 'Limit',
			type: 'number',
		},
		inputId: 'demo-limit',
		onChange: () => {},
		value: undefined,
	});
	const renderedNumberCleared = registry.get('number')({
		components: {},
		createElement: (type, props, ...children) => ({ type, props, children }),
		field: {
			default: 5,
			id: 'limit',
			label: 'Limit',
			type: 'number',
		},
		inputId: 'demo-limit',
		onChange: () => {},
		value: '',
	});

	assert.equal(renderedNumberDefault.props.value, '5');
	assert.equal(renderedNumberCleared.props.value, '');

	const changes = [];
	const renderedSelect = registry.get('select')({
		components: {},
		createElement: (type, props, ...children) => ({ type, props, children }),
		field: {
			choices: {
				compact: 'Compact',
				feature: 'Feature',
			},
			id: 'layout',
			label: 'Layout',
			type: 'select',
		},
		inputId: 'demo-layout',
		onChange: (value) => changes.push(value),
		value: 'compact',
	});
	const renderedColor = registry.get('color')({
		components: {},
		createElement: (type, props, ...children) => ({ type, props, children }),
		field: {
			id: 'accent',
			label: 'Accent',
			type: 'color',
		},
		inputId: 'demo-accent',
		onChange: (value) => changes.push(value),
		value: '#2271b1',
	});
	const renderedDate = registry.get('date')({
		components: {},
		createElement: (type, props, ...children) => ({ type, props, children }),
		field: {
			id: 'review_date',
			label: 'Review date',
			type: 'date',
		},
		inputId: 'demo-review-date',
		onChange: (value) => changes.push(value),
		value: '2026-04-26',
	});
	const renderedRange = registry.get('slider')({
		components: {},
		createElement: (type, props, ...children) => ({ type, props, children }),
		field: {
			id: 'priority',
			label: 'Priority',
			max: 5,
			min: 1,
			type: 'slider',
		},
		inputId: 'demo-priority',
		onChange: (value) => changes.push(value),
		value: '3',
	});
	const renderedCheckboxChoices = registry.get('checkbox')({
		components: {},
		createElement: (type, props, ...children) => ({ type, props, children }),
		field: {
			choices: {
				newsletter: 'Newsletter',
			},
			id: 'channels',
			label: 'Channels',
			type: 'checkbox',
		},
		inputId: 'demo-channels',
		onChange: (value) => changes.push(value),
		value: [],
	});

	renderedSelect.props.onChange({ target: { value: 'feature' } });
	renderedColor.children[0][1].props.onInput({ target: { value: '#13579b' } });
	renderedDate.children[0][1].props.onInput({ target: { value: '2026-05-03' } });
	renderedRange.children[0][1].props.onInput({ target: { value: '4' } });
	renderedCheckboxChoices.children[0][1].children[0][0].props.onChange({ target: { checked: true } });
	assert.deepEqual(changes, [ 'feature', '#13579b', '2026-05-03', '4', [ 'newsletter' ] ]);
	assert.equal(renderedColor.children[0][1].props.onChange, undefined);
	assert.equal(renderedDate.children[0][1].props.onChange, undefined);
	assert.equal(renderedRange.children[0][1].props.onChange, undefined);
}

function testBlockPanelFieldStatusContract() {
	const readOnlyTypes = blockPanelReadOnlyControlTypes();
	const editableTypes = createDefaultControlRegistry().types();

	for (const type of [
		'accordion',
		'ajax_select',
		'background',
		'backup_tools',
		'code_editor',
		'content',
		'heading',
		'icon',
		'image_select',
		'notice',
		'palette',
		'sorter',
		'subheading',
		'tabbed',
		'typography',
		'wp_editor',
	]) {
		assert(readOnlyTypes.includes(type), `${type} should be read-only in the block panel`);
	}

	for (const type of readOnlyTypes) {
		assert(! editableTypes.includes(type), `${type} should not be registered as an editable control`);
	}
}

function testBlockPanelDependencyVisibility() {
	const fields = {
		feature_enabled: {
			default: false,
			id: 'feature_enabled',
		},
		entry_accent: {
			dependency: {
				field: 'feature_enabled',
				operator: '==',
				value: true,
			},
			id: 'entry_accent',
		},
		entry_hint: {
			dependency: {
				field: 'entry_accent',
				operator: '!=',
				value: '',
			},
			id: 'entry_hint',
		},
		newsletter_summary: {
			dependency: {
				field: 'entry_channels',
				operator: '==',
				value: 'newsletter',
			},
			id: 'newsletter_summary',
		},
		entry_channels: {
			default: [],
			id: 'entry_channels',
		},
		cycle_a: {
			dependency: {
				field: 'cycle_b',
			},
			id: 'cycle_a',
		},
		cycle_b: {
			dependency: {
				field: 'cycle_a',
			},
			id: 'cycle_b',
		},
	};
	const dependencies = Object.fromEntries(
		Object.entries(fields)
			.filter((entry) => entry[1].dependency)
			.map(([ fieldId, field ]) => [ fieldId, field.dependency ])
	);

	assert.equal(isFieldDependencySatisfied('feature_enabled', fields, {}, dependencies), true);
	assert.equal(isFieldDependencySatisfied('entry_accent', fields, {}, dependencies), false);
	assert.equal(isFieldDependencySatisfied('entry_accent', fields, { feature_enabled: true }, dependencies), true);
	assert.equal(
		isFieldDependencySatisfied('entry_hint', fields, { feature_enabled: false, entry_accent: '#13579b' }, dependencies),
		false
	);
	assert.equal(
		isFieldDependencySatisfied('entry_hint', fields, { feature_enabled: true, entry_accent: '#13579b' }, dependencies),
		true
	);
	assert.equal(
		isFieldDependencySatisfied('newsletter_summary', fields, { entry_channels: [ 'homepage', 'newsletter' ] }, dependencies),
		true
	);
	assert.equal(isFieldDependencySatisfied('missing_controller', fields, {}, { missing_controller: { field: 'missing' } }), false);
	assert.equal(isFieldDependencySatisfied('cycle_a', fields, {}, dependencies), false);
}

async function testBlockPanelRuntime() {
	const requests = [];
	const restClient = {
		hasTransport: () => true,
		request: async (path, options = {}) => {
			requests.push({ path, options });

			if (path.includes('/values') && options.method === 'POST') {
				if (options.data.values.title === 'Bad') {
					return {
						success: false,
						data: {
							fieldErrors: {
								title: 'Too short.',
							},
							message: 'Please review.',
						},
					};
				}

				return {
					success: true,
					data: {
						values: options.data.values,
					},
				};
			}

			if (path.includes('/values')) {
				return {
					success: true,
					data: {
						values: {
							title: 'Loaded',
						},
					},
				};
			}

			return {
				success: true,
				data: {
					protocolVersion: 1,
					schemaId: 'demo',
				},
			};
		},
	};
	const runtime = createBlockPanelRuntime(
		{
			context: {
				post_id: 77,
			},
			schemaId: 'demo',
		},
		{ restClient }
	);

	await runtime.loadSchema();
	assert.equal(runtime.isDirty(), false);
	runtime.updateValue('title', 'Saved');
	assert.equal(runtime.isDirty(), true);
	runtime.discardChanges();
	assert.equal(runtime.getState().values.title, 'Loaded');
	assert.equal(runtime.isDirty(), false);
	runtime.updateValue('title', 'Saved');
	await runtime.save();

	assert.equal(requests[0].path, 'schemas/demo?post_id=77');
	assert.equal(requests[1].path, 'schemas/demo/values?post_id=77');
	assert.equal(requests[2].path, 'schemas/demo/values?post_id=77');
	assert.equal(requests[2].options.method, 'POST');
	assert.deepEqual(requests[2].options.data, {
		values: {
			title: 'Saved',
		},
	});
	assert.equal(runtime.getState().values.title, 'Saved');
	assert.equal(runtime.isDirty(), false);

	runtime.updateValue('title', 'Bad');
	await runtime.save();
	assert.equal(runtime.getState().status, 'error');
	assert.deepEqual(runtime.getState().errors, { title: 'Too short.' });
	assert.equal(runtime.isDirty(), true);
	runtime.updateValue('title', 'Better');
	assert.equal(runtime.getState().status, 'ready');
	assert.deepEqual(runtime.getState().errors, {});
}

async function testBlockPanelRuntimeRejectedSave() {
	const restClient = {
		hasTransport: () => true,
		request: async (path, options = {}) => {
			if (path.includes('/values') && options.method === 'POST') {
				throw new Error('Network failed.');
			}

			if (path.includes('/values')) {
				return {
					success: true,
					data: {
						values: {
							title: 'Loaded',
						},
					},
				};
			}

			return {
				success: true,
				data: {
					protocolVersion: 1,
					schemaId: 'demo',
				},
			};
		},
	};
	const runtime = createBlockPanelRuntime(
		{
			context: {
				post_id: 77,
			},
			schemaId: 'demo',
		},
		{ restClient }
	);

	await runtime.loadSchema();
	runtime.updateValue('title', 'Changed');

	const response = await runtime.save();

	assert.equal(response.success, false);
	assert.equal(runtime.getState().status, 'error');
	assert.equal(runtime.getState().message, 'Network failed.');
	assert.equal(runtime.isDirty(), true);
}

async function main() {
	testRecordHelpers();
	testContextHelpers();
	testErrorHelpers();
	testRestUrlHelpers();
	testSchemaStateHelpers();
	testDefaultControlRegistry();
	testBlockPanelFieldStatusContract();
	testBlockPanelDependencyVisibility();
	await testBlockPanelRuntime();
	await testBlockPanelRuntimeRejectedSave();
	console.log('JS runtime tests passed.');
}

main().catch((error) => {
	console.error(error);
	process.exit(1);
});
