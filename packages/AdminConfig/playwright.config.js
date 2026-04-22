// @ts-check

const { defineConfig } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: './tests/E2E',
	timeout: 60_000,
	reporter: [ [ 'list' ], [ 'html', { open: 'never' } ] ],
	expect: {
		timeout: 10_000,
	},
	use: {
		baseURL: 'http://localhost:8888',
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},
	workers: 1,
	retries: process.env.CI ? 1 : 0,
	webServer: undefined,
} );
