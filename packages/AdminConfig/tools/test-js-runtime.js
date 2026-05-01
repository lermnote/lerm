/* eslint-env node */

const assert = require('assert/strict');

const { contextFromConfig, contextQueryString } = require('../resources/core/context');
const { fieldErrorsFromResponse, messageFromResponse } = require('../resources/core/errors');
const { restUrl } = require('../resources/core/rest-client');
const {
	createSchemaState,
	hydrateSchemaResponse,
	serializeSavePayload,
	withFieldValue,
	withRestError,
} = require('../resources/core/schema-state');
const { createBlockPanelRuntime } = require('../resources/block-panel');

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
			message: 'Validation failed.',
		},
	};

	assert.deepEqual(fieldErrorsFromResponse(response), { title: 'Required.' });
	assert.equal(messageFromResponse(response), 'Validation failed.');
}

function testRestUrlHelpers() {
	const plain = restUrl(
		{ restUrl: 'https://example.test/wp-json/lerm-admin-config/v1/' },
		'schema/demo?post_id=77'
	);
	const fallback = new URL(restUrl(
		{ restUrl: 'https://example.test/index.php?rest_route=/lerm-admin-config/v1/' },
		'schema/demo?post_id=77'
	));

	assert.equal(plain, 'https://example.test/wp-json/lerm-admin-config/v1/schema/demo?post_id=77');
	assert.equal(fallback.searchParams.get('rest_route'), '/lerm-admin-config/v1/schema/demo');
	assert.equal(fallback.searchParams.get('post_id'), '77');
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
	const errored = withRestError(updated, {
		fieldErrors: {
			'group.heading': [ 'Too short.' ],
		},
		message: 'Please review.',
	});

	assert.equal(hydrated.status, 'ready');
	assert.deepEqual(updated.values.group, { heading: 'Next' });
	assert.deepEqual(serializeSavePayload(updated), {
		values: {
			title: 'Initial',
			group: {
				heading: 'Next',
			},
		},
	});
	assert.equal(errored.status, 'error');
	assert.equal(errored.message, 'Please review.');
	assert.deepEqual(errored.errors, { 'group.heading': [ 'Too short.' ] });
}

async function testBlockPanelRuntime() {
	const requests = [];
	const restClient = {
		hasTransport: () => true,
		request: async (path, options = {}) => {
			requests.push({ path, options });

			if (path.includes('/save')) {
				return {
					success: true,
					data: {
						values: options.data.values,
					},
				};
			}

			return {
				success: true,
				data: {
					schema: {
						schemaId: 'demo',
					},
					values: {
						title: 'Loaded',
					},
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
	runtime.updateValue('title', 'Saved');
	await runtime.save();

	assert.equal(requests[0].path, 'schema/demo?post_id=77');
	assert.equal(requests[1].path, 'schema/demo/save?post_id=77');
	assert.equal(requests[1].options.method, 'POST');
	assert.deepEqual(requests[1].options.data, {
		values: {
			title: 'Saved',
		},
	});
	assert.equal(runtime.getState().values.title, 'Saved');
}

async function main() {
	testContextHelpers();
	testErrorHelpers();
	testRestUrlHelpers();
	testSchemaStateHelpers();
	await testBlockPanelRuntime();
	console.log('JS runtime tests passed.');
}

main().catch((error) => {
	console.error(error);
	process.exit(1);
});
