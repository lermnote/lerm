const path = require('path');

module.exports = {
	rootDir: path.resolve(__dirname),
	testEnvironment: 'node',
	roots: ['<rootDir>/tests/JS'],
	testMatch: ['**/*.test.js'],
	transform: {},
};
