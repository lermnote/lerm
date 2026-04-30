// @ts-check

const { createAdminConfigRestClient } = require('../core/rest-client');
const { createSchemaState } = require('../core/schema-state');
const { createControlRegistry } = require('../controls');
const { STORE_NAME } = require('../store');

/**
 * @param {Record<string, unknown>} config
 */
const createBlockPanelRuntime = (config = {}) => ({
	controls: createControlRegistry(),
	rest: createAdminConfigRestClient({ getConfig: () => config }),
	state: createSchemaState(),
	storeName: STORE_NAME,
});

if (typeof window !== 'undefined') {
	window.lermAdminConfigBlockPanel = {
		createRuntime: createBlockPanelRuntime,
	};
}

module.exports = {
	createBlockPanelRuntime,
};
