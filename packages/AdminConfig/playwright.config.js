// @ts-check

const { defineConfig } = require( '@playwright/test' );

const baseURL = process.env.LERM_ADMIN_CONFIG_BASE_URL || 'http://localhost:8888';

module.exports = defineConfig( {
	testDir: './tests/E2E',
	timeout: 60_000,
	reporter: [ [ 'list' ], [ 'html', { open: 'never' } ] ],
	expect: {
		timeout: 10_000,
	},
	use: {
		baseURL,
		channel: process.env.PLAYWRIGHT_CHANNEL || undefined,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: process.env.CI ? 'off' : 'retain-on-failure',
	},
	workers: 1,
	retries: process.env.CI ? 1 : 0,
	webServer: undefined,
} );
