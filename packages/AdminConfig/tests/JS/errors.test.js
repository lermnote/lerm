// @ts-check

const {
	fieldErrorsFromResponse,
	messageFromResponse,
	normalizeErrorData,
} = require('../../resources/core/errors');

describe('normalizeErrorData', () => {
	it('returns empty object for null', () => {
		expect(normalizeErrorData(null)).toEqual({});
	});

	it('returns empty object for undefined', () => {
		expect(normalizeErrorData(undefined)).toEqual({});
	});

	it('returns empty object for primitives', () => {
		expect(normalizeErrorData(42)).toEqual({});
		expect(normalizeErrorData('string')).toEqual({});
	});

	it('merges top-level and nested .data keys', () => {
		const response = { message: 'Top', data: { detail: 'Nested' } };
		const result = normalizeErrorData(response);
		expect(result.message).toBe('Top');
		expect(result.detail).toBe('Nested');
	});

	it('top-level keys override nested .data keys', () => {
		const response = { message: 'Top', data: { message: 'Nested' } };
		const result = normalizeErrorData(response);
		expect(result.message).toBe('Top');
	});
});

describe('fieldErrorsFromResponse', () => {
	it('extracts fieldErrors from response', () => {
		const response = { fieldErrors: { name: 'Required', email: 'Invalid' } };
		expect(fieldErrorsFromResponse(response)).toEqual({ name: 'Required', email: 'Invalid' });
	});

	it('falls back to errors when fieldErrors is absent', () => {
		const response = { errors: { age: 'Too young' } };
		expect(fieldErrorsFromResponse(response)).toEqual({ age: 'Too young' });
	});

	it('prefers errors over fieldErrors when both exist', () => {
		const response = {
			errors: { a: 'from errors' },
			fieldErrors: { b: 'from fieldErrors' },
		};
		expect(fieldErrorsFromResponse(response)).toEqual({ a: 'from errors' });
	});

	it('returns empty object when neither errors nor fieldErrors exist', () => {
		expect(fieldErrorsFromResponse({})).toEqual({});
		expect(fieldErrorsFromResponse(null)).toEqual({});
	});

	it('returns empty object when errors/fieldErrors are not objects', () => {
		expect(fieldErrorsFromResponse({ errors: 'string' })).toEqual({});
		expect(fieldErrorsFromResponse({ fieldErrors: 42 })).toEqual({});
	});
});

describe('messageFromResponse', () => {
	it('extracts message from top-level', () => {
		expect(messageFromResponse({ message: 'Hello' })).toBe('Hello');
	});

	it('extracts message from nested .data', () => {
		expect(messageFromResponse({ data: { message: 'Nested' } })).toBe('Nested');
	});

	it('uses fallback when no message', () => {
		expect(messageFromResponse({}, 'Fallback')).toBe('Fallback');
	});

	it('returns empty string when no message and no fallback', () => {
		expect(messageFromResponse({})).toBe('');
	});

	it('top-level message overrides nested', () => {
		expect(messageFromResponse({ message: 'Top', data: { message: 'Nested' } })).toBe('Top');
	});
});
