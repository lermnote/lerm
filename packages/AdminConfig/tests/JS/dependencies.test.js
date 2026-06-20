// @ts-check

const {
	dependencyScalar,
	dependencyMatches,
	isFieldDependencySatisfied,
} = require('../../resources/core/dependencies');

describe('dependencyScalar', () => {
	it('converts boolean true to "1"', () => {
		expect(dependencyScalar(true)).toBe('1');
	});

	it('converts boolean false to "0"', () => {
		expect(dependencyScalar(false)).toBe('0');
	});

	it('converts numbers to strings', () => {
		expect(dependencyScalar(42)).toBe('42');
		expect(dependencyScalar(0)).toBe('0');
		expect(dependencyScalar(-3.14)).toBe('-3.14');
	});

	it('converts bigint to strings', () => {
		expect(dependencyScalar(BigInt(9007199254740991))).toBe('9007199254740991');
	});

	it('passes strings through', () => {
		expect(dependencyScalar('hello')).toBe('hello');
	});

	it('returns empty string for null/undefined/objects', () => {
		expect(dependencyScalar(null)).toBe('');
		expect(dependencyScalar(undefined)).toBe('');
		expect(dependencyScalar({})).toBe('');
		expect(dependencyScalar([1, 2])).toBe('');
	});
});

describe('dependencyMatches', () => {
	describe('equality (==)', () => {
		it('matches when actual equals expected', () => {
			expect(dependencyMatches('on', '==', 'on')).toBe(true);
		});

		it('does not match when actual differs from expected', () => {
			expect(dependencyMatches('off', '==', 'on')).toBe(false);
		});

		it('matches array values containing expected', () => {
			expect(dependencyMatches(['a', 'b'], '==', 'b')).toBe(true);
			expect(dependencyMatches(['a', 'b'], '==', 'c')).toBe(false);
		});

		it('defaults to == when operator is empty', () => {
			expect(dependencyMatches('yes', '', 'yes')).toBe(true);
		});
	});

	describe('inequality (!=)', () => {
		it('matches when actual does not equal expected', () => {
			expect(dependencyMatches('off', '!=', 'on')).toBe(true);
		});

		it('does not match when actual equals expected', () => {
			expect(dependencyMatches('on', '!=', 'on')).toBe(false);
		});
	});

	describe('in operator', () => {
		it('matches when actual value is in expected list', () => {
			expect(dependencyMatches('a', 'in', ['a', 'b', 'c'])).toBe(true);
		});

		it('does not match when actual value is not in expected list', () => {
			expect(dependencyMatches('d', 'in', ['a', 'b', 'c'])).toBe(false);
		});

		it('matches when any actual value is in expected list', () => {
			expect(dependencyMatches(['a', 'd'], 'in', ['a', 'b'])).toBe(true);
		});
	});

	describe('not_in operator', () => {
		it('matches when actual value is not in expected list', () => {
			expect(dependencyMatches('d', 'not_in', ['a', 'b', 'c'])).toBe(true);
		});

		it('does not match when actual value is in expected list', () => {
			expect(dependencyMatches('a', 'not_in', ['a', 'b', 'c'])).toBe(false);
		});
	});

	describe('comparison operators (>, >=, <, <=)', () => {
		it('>', () => {
			expect(dependencyMatches(5, '>', 3)).toBe(true);
			expect(dependencyMatches(3, '>', 5)).toBe(false);
			expect(dependencyMatches(5, '>', 5)).toBe(false);
		});

		it('>=', () => {
			expect(dependencyMatches(5, '>=', 5)).toBe(true);
			expect(dependencyMatches(5, '>=', 3)).toBe(true);
			expect(dependencyMatches(3, '>=', 5)).toBe(false);
		});

		it('<', () => {
			expect(dependencyMatches(3, '<', 5)).toBe(true);
			expect(dependencyMatches(5, '<', 3)).toBe(false);
			expect(dependencyMatches(5, '<', 5)).toBe(false);
		});

		it('<=', () => {
			expect(dependencyMatches(5, '<=', 5)).toBe(true);
			expect(dependencyMatches(3, '<=', 5)).toBe(true);
			expect(dependencyMatches(5, '<=', 3)).toBe(false);
		});

		it('returns false when values are not finite numbers', () => {
			expect(dependencyMatches('abc', '>', 0)).toBe(false);
			expect(dependencyMatches(5, '>=', NaN)).toBe(false);
		});

		it('compares string-represented numbers', () => {
			expect(dependencyMatches('10', '>', '5')).toBe(true);
		});
	});

	describe('boolean shorthand', () => {
		it('matches truthy values when expected is true', () => {
			expect(dependencyMatches('1', '==', true)).toBe(true);
			expect(dependencyMatches(true, '==', true)).toBe(true);
		});

		it('matches falsy values when expected is false', () => {
			expect(dependencyMatches('0', '==', false)).toBe(true);
			expect(dependencyMatches(false, '==', false)).toBe(true);
		});
	});
});

describe('isFieldDependencySatisfied', () => {
	const fields = {
		field_a: { id: 'field_a', type: 'text' },
		field_b: { id: 'field_b', type: 'text', dependency: { field: 'field_a', operator: '==', value: 'on' } },
		field_c: { id: 'field_c', type: 'text', dependency: { field: 'field_b', operator: '!=', value: 'hide' } },
	};
	const values = { field_a: 'on', field_b: 'show' };

	it('returns true for fields without dependencies', () => {
		expect(isFieldDependencySatisfied('field_a', fields, values, {})).toBe(true);
	});

	it('returns true when dependency is satisfied', () => {
		expect(isFieldDependencySatisfied('field_b', fields, values, {})).toBe(true);
	});

	it('returns false when dependency is not satisfied', () => {
		const otherValues = { field_a: 'off' };
		expect(isFieldDependencySatisfied('field_b', fields, otherValues, {})).toBe(false);
	});

	it('returns true when dependency field is empty', () => {
		const noDepFields = { field_x: { id: 'field_x', dependency: { field: '', operator: '==', value: '1' } } };
		expect(isFieldDependencySatisfied('field_x', noDepFields, {}, {})).toBe(true);
	});

	it('detects circular dependencies and returns false', () => {
		const circular = {
			a: { id: 'a', dependency: { field: 'b', operator: '==', value: '1' } },
			b: { id: 'b', dependency: { field: 'a', operator: '==', value: '1' } },
		};
		expect(isFieldDependencySatisfied('a', circular, {}, {})).toBe(false);
	});

	it('returns false when controller field does not exist', () => {
		const orphan = { x: { id: 'x', dependency: { field: 'nonexistent', operator: '==', value: '1' } } };
		expect(isFieldDependencySatisfied('x', orphan, {}, {})).toBe(false);
	});

	it('supports chained dependencies', () => {
		expect(isFieldDependencySatisfied('field_c', fields, values, {})).toBe(true);
		const blockedValues = { field_a: 'off' };
		expect(isFieldDependencySatisfied('field_c', fields, blockedValues, {})).toBe(false);
	});
});
