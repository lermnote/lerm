// @ts-check

const {
	CONTEXT_KEYS,
	contextFromRecord,
	contextFromConfig,
	contextQueryString,
} = require('../../resources/core/context');

describe('CONTEXT_KEYS', () => {
	it('contains all expected keys', () => {
		expect(CONTEXT_KEYS).toEqual([
			'post_id',
			'term_id',
			'user_id',
			'comment_id',
			'network_id',
		]);
	});
});

describe('contextFromRecord', () => {
	it('extracts valid positive integer context values', () => {
		expect(contextFromRecord({ post_id: 42, term_id: '7' })).toEqual({ post_id: 42, term_id: 7 });
	});

	it('filters out zero and negative values', () => {
		expect(contextFromRecord({ post_id: 0, term_id: -1, user_id: 5 })).toEqual({ user_id: 5 });
	});

	it('filters out non-integer string values but truncates floats via parseInt', () => {
		// parseInt(String(3.5), 10) === 3, which IS an integer
		expect(contextFromRecord({ post_id: 'abc', term_id: 3.5, user_id: 10 })).toEqual({ term_id: 3, user_id: 10 });
	});

	it('returns empty object for null', () => {
		expect(contextFromRecord(null)).toEqual({});
	});

	it('returns empty object for empty input', () => {
		expect(contextFromRecord({})).toEqual({});
	});

	it('ignores unknown keys', () => {
		expect(contextFromRecord({ unknown_key: 42, post_id: 1 })).toEqual({ post_id: 1 });
	});
});

describe('contextFromConfig', () => {
	it('extracts context from top-level and nested .context', () => {
		const config = { post_id: 1, context: { term_id: 2 } };
		expect(contextFromConfig(config)).toEqual({ post_id: 1, term_id: 2 });
	});

	it('nested context overrides top-level', () => {
		const config = { post_id: 1, context: { post_id: 99 } };
		expect(contextFromConfig(config)).toEqual({ post_id: 99 });
	});

	it('returns empty object for empty config', () => {
		expect(contextFromConfig({})).toEqual({});
	});

	it('returns empty object for null', () => {
		expect(contextFromConfig(null)).toEqual({});
	});
});

describe('contextQueryString', () => {
	it('builds query string from context', () => {
		const result = contextQueryString({ post_id: 42, term_id: 7 });
		expect(result).toContain('post_id=42');
		expect(result).toContain('term_id=7');
	});

	it('skips zero and negative values', () => {
		const result = contextQueryString({ post_id: 0, term_id: 5 });
		expect(result).not.toContain('post_id');
		expect(result).toContain('term_id=5');
	});

	it('returns empty string for empty context', () => {
		expect(contextQueryString({})).toBe('');
	});

	it('returns empty string for null', () => {
		expect(contextQueryString(null)).toBe('');
	});
});
