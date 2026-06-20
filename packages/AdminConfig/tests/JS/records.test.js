// @ts-check

const { asRecord, asRecordArray } = require('../../resources/core/records');

describe('asRecord', () => {
	it('returns the object when value is a plain object', () => {
		const obj = { a: 1, b: 'hello' };
		expect(asRecord(obj)).toEqual(obj);
	});

	it('returns empty object for null', () => {
		expect(asRecord(null)).toEqual({});
	});

	it('returns empty object for undefined', () => {
		expect(asRecord(undefined)).toEqual({});
	});

	it('returns empty object for arrays', () => {
		expect(asRecord([1, 2, 3])).toEqual({});
	});

	it('returns empty object for primitives', () => {
		expect(asRecord(42)).toEqual({});
		expect(asRecord('string')).toEqual({});
		expect(asRecord(true)).toEqual({});
	});

	it('returns the object for class instances', () => {
		class Foo { constructor() { this.x = 1; } }
		expect(asRecord(new Foo())).toEqual({ x: 1 });
	});
});

describe('asRecordArray', () => {
	it('maps each array element through asRecord', () => {
		const input = [{ a: 1 }, { b: 2 }, null, 'string', 42];
		expect(asRecordArray(input)).toEqual([{ a: 1 }, { b: 2 }]);
	});

	it('filters out non-object results', () => {
		expect(asRecordArray([null, undefined, 42, 'str'])).toEqual([]);
	});

	it('returns empty array for non-array input', () => {
		expect(asRecordArray(null)).toEqual([]);
		expect(asRecordArray({})).toEqual([]);
		expect(asRecordArray('string')).toEqual([]);
	});

	it('returns empty array for empty array', () => {
		expect(asRecordArray([])).toEqual([]);
	});

	it('filters out empty objects from asRecord results', () => {
		const input = [{ a: 1 }, null, { b: 2 }];
		expect(asRecordArray(input)).toEqual([{ a: 1 }, { b: 2 }]);
	});
});
