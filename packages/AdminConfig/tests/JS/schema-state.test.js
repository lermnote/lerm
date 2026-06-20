// @ts-check

const {
	createSchemaState,
	withSchema,
	withValues,
	withErrors,
	withFieldValue,
	withStatus,
	withoutErrorAtPath,
	setValueAtPath,
	isSchemaStateDirty,
	serializeSavePayload,
	serializeValuesForSchema,
	hydrateSchemaResponse,
	withRestError,
} = require('../../resources/core/schema-state');

describe('createSchemaState', () => {
	it('creates initial state with given values', () => {
		const state = createSchemaState({ fields: {} }, { foo: 'bar' }, { post_id: 1 }, 'test');
		expect(state.values).toEqual({ foo: 'bar' });
		expect(state.savedValues).toEqual({ foo: 'bar' });
		expect(state.errors).toEqual({});
		expect(state.status).toBe('idle');
		expect(state.schemaId).toBe('test');
		expect(state.context).toEqual({ post_id: 1 });
	});

	it('defaults to empty objects when no args', () => {
		const state = createSchemaState();
		expect(state.values).toEqual({});
		expect(state.schema).toEqual({});
		expect(state.context).toEqual({});
		expect(state.schemaId).toBe('');
	});
});

describe('withStatus', () => {
	it('sets status and preserves message', () => {
		const state = createSchemaState();
		const updated = withStatus(state, 'saving');
		expect(updated.status).toBe('saving');
		expect(updated.message).toBe('');
	});

	it('sets status with a message', () => {
		const state = createSchemaState();
		const updated = withStatus(state, 'error', 'Something went wrong');
		expect(updated.status).toBe('error');
		expect(updated.message).toBe('Something went wrong');
	});
});

describe('withSchema', () => {
	it('replaces schema, values, and sets status to ready', () => {
		const state = createSchemaState();
		const schema = { fields: { x: { id: 'x' } } };
		const values = { x: 'hello' };
		const updated = withSchema(state, schema, values);
		expect(updated.schema).toBe(schema);
		expect(updated.values).toEqual(values);
		expect(updated.savedValues).toEqual(values);
		expect(updated.status).toBe('ready');
		expect(updated.errors).toEqual({});
	});
});

describe('withValues', () => {
	it('replaces values and savedValues and clears errors', () => {
		const state = withErrors(createSchemaState(), { foo: 'bad' });
		const updated = withValues(state, { bar: 'ok' });
		expect(updated.values).toEqual({ bar: 'ok' });
		expect(updated.savedValues).toEqual({ bar: 'ok' });
		expect(updated.errors).toEqual({});
		expect(updated.status).toBe('ready');
	});
});

describe('withErrors', () => {
	it('sets errors and status to error', () => {
		const state = createSchemaState();
		const updated = withErrors(state, { field1: 'Required' });
		expect(updated.errors).toEqual({ field1: 'Required' });
		expect(updated.status).toBe('error');
	});
});

describe('setValueAtPath', () => {
	it('sets a top-level key', () => {
		const result = setValueAtPath({ a: 1 }, 'b', 2);
		expect(result.b).toBe(2);
		expect(result.a).toBe(1);
	});

	it('sets a nested path', () => {
		const result = setValueAtPath({ a: { b: { c: 1 } } }, 'a.b.c', 42);
		expect(result.a.b.c).toBe(42);
	});

	it('sets a deeply nested path creating intermediate objects', () => {
		const result = setValueAtPath({}, 'a.b.c.d', 'value');
		expect(result.a.b.c.d).toBe('value');
	});

	it('does not mutate the original object', () => {
		const original = { a: { b: 1 } };
		const result = setValueAtPath(original, 'a.b', 2);
		expect(original.a.b).toBe(1);
		expect(result.a.b).toBe(2);
	});

	it('returns a clone when path is empty', () => {
		const original = { a: 1 };
		const result = setValueAtPath(original, '', 'ignored');
		expect(result).toEqual({ a: 1 });
		expect(result).not.toBe(original);
	});

	it('supports array path', () => {
		const result = setValueAtPath({}, ['a', 'b'], 'val');
		expect(result.a.b).toBe('val');
	});
});

describe('withoutErrorAtPath', () => {
	it('removes error at exact field id', () => {
		const errors = { foo: 'error', bar: 'other' };
		expect(withoutErrorAtPath(errors, 'foo')).toEqual({ bar: 'other' });
	});

	it('removes error at full path', () => {
		const errors = { 'foo.bar': 'error' };
		expect(withoutErrorAtPath(errors, 'foo.bar')).toEqual({});
	});

	it('returns original when no matching error', () => {
		const errors = { foo: 'error' };
		expect(withoutErrorAtPath(errors, 'baz')).toEqual({ foo: 'error' });
	});

	it('handles empty path', () => {
		const errors = { foo: 'error' };
		expect(withoutErrorAtPath(errors, '')).toEqual({ foo: 'error' });
	});
});

describe('withFieldValue', () => {
	it('sets value and clears error for that path', () => {
		const state = withErrors(createSchemaState({}, { foo: '' }), { foo: 'Required' });
		const updated = withFieldValue(state, 'foo', 'new value');
		expect(updated.values.foo).toBe('new value');
		expect(updated.errors).toEqual({});
		expect(updated.status).toBe('ready');
	});

	it('preserves other errors', () => {
		const state = withErrors(createSchemaState({}, { a: '', b: '' }), { a: 'err1', b: 'err2' });
		const updated = withFieldValue(state, 'a', 'val');
		expect(updated.errors).toEqual({ b: 'err2' });
		expect(updated.status).toBe('error');
	});
});

describe('isSchemaStateDirty', () => {
	it('returns false when values match savedValues', () => {
		const state = createSchemaState({}, { foo: 'bar' });
		expect(isSchemaStateDirty(state)).toBe(false);
	});

	it('returns true when values differ from savedValues', () => {
		const state = createSchemaState({}, { foo: 'bar' });
		const updated = withFieldValue(state, 'foo', 'changed');
		expect(isSchemaStateDirty(updated)).toBe(true);
	});

	it('returns false for empty state', () => {
		const state = createSchemaState();
		expect(isSchemaStateDirty(state)).toBe(false);
	});
});

describe('serializeValuesForSchema', () => {
	it('serializes media field to { id }', () => {
		const schema = { fields: { img: { id: 'img', type: 'media', control: 'media' } } };
		const values = { img: { id: 42 } };
		const result = serializeValuesForSchema(schema, values);
		expect(result.img).toEqual({ id: 42 });
	});

	it('serializes gallery field to array of ids', () => {
		const schema = { fields: { gal: { id: 'gal', type: 'gallery', control: 'gallery' } } };
		const values = { gal: '1,2,3' };
		const result = serializeValuesForSchema(schema, values);
		expect(result.gal).toEqual([1, 2, 3]);
	});

	it('serializes upload field to string', () => {
		const schema = { fields: { up: { id: 'up', type: 'upload', control: 'upload' } } };
		const values = { up: 'https://example.com/file.pdf' };
		const result = serializeValuesForSchema(schema, values);
		expect(result.up).toBe('https://example.com/file.pdf');
	});

	it('serializes upload field null to empty string', () => {
		const schema = { fields: { up: { id: 'up', type: 'upload', control: 'upload' } } };
		const values = { up: null };
		const result = serializeValuesForSchema(schema, values);
		expect(result.up).toBe('');
	});

	it('serializes group field as array of records', () => {
		const schema = {
			fields: {
				grp: {
					id: 'grp',
					type: 'group',
					control: 'group',
					fields: [{ id: 'name' }, { id: 'url' }],
				},
			},
		};
		const values = { grp: [{ name: 'A', url: 'a.com' }, { name: 'B', url: 'b.com' }] };
		const result = serializeValuesForSchema(schema, values);
		expect(result.grp).toEqual([{ name: 'A', url: 'a.com' }, { name: 'B', url: 'b.com' }]);
	});

	it('serializes group field non-array to empty array', () => {
		const schema = { fields: { grp: { id: 'grp', type: 'group', control: 'group', fields: [] } } };
		const values = { grp: 'not an array' };
		const result = serializeValuesForSchema(schema, values);
		expect(result.grp).toEqual([]);
	});

	it('serializes fieldset as nested record', () => {
		const schema = {
			fields: {
				fs: {
					id: 'fs',
					type: 'fieldset',
					control: 'fieldset',
					fields: [{ id: 'inner' }],
				},
			},
		};
		const values = { fs: { inner: 'value' } };
		const result = serializeValuesForSchema(schema, values);
		expect(result.fs).toEqual({ inner: 'value' });
	});

	it('passes through unknown field types', () => {
		const schema = { fields: { txt: { id: 'txt', type: 'text', control: 'text' } } };
		const values = { txt: 'hello' };
		const result = serializeValuesForSchema(schema, values);
		expect(result.txt).toBe('hello');
	});

	it('returns values as-is when schema has no fields', () => {
		const values = { foo: 'bar' };
		expect(serializeValuesForSchema({}, values)).toEqual({ foo: 'bar' });
	});
});

describe('serializeSavePayload', () => {
	it('wraps serialized values', () => {
		const state = createSchemaState({ fields: {} }, { foo: 'bar' });
		const payload = serializeSavePayload(state);
		expect(payload.values).toEqual({ foo: 'bar' });
	});

	it('uses provided values override', () => {
		const state = createSchemaState({ fields: {} }, { foo: 'bar' });
		const payload = serializeSavePayload(state, { foo: 'override' });
		expect(payload.values).toEqual({ foo: 'override' });
	});
});

describe('hydrateSchemaResponse', () => {
	it('extracts schema and values from response data', () => {
		const state = createSchemaState();
		const responseData = { schema: { fields: {} }, values: { a: 1 } };
		const updated = hydrateSchemaResponse(state, responseData);
		expect(updated.schema).toEqual({ fields: {} });
		expect(updated.values).toEqual({ a: 1 });
		expect(updated.status).toBe('ready');
	});
});

describe('withRestError', () => {
	it('extracts field errors and message from response', () => {
		const state = createSchemaState();
		const responseData = { message: 'Validation failed', fieldErrors: { name: 'Required' } };
		const updated = withRestError(state, responseData, 'Fallback');
		expect(updated.errors).toEqual({ name: 'Required' });
		expect(updated.message).toBe('Validation failed');
		expect(updated.status).toBe('error');
	});

	it('uses fallback message when response has none', () => {
		const state = createSchemaState();
		const updated = withRestError(state, {}, 'Fallback message');
		expect(updated.message).toBe('Fallback message');
	});
});
