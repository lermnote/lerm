// @ts-check

const { createAdminConfigRestClient } = require('../core/rest-client');

/**
 * @typedef {{
 *   success: boolean,
 *   data: Record<string, unknown>
 * }} AdminConfigResponse
 */

/**
 * @param {{
 *   getConfig: () => Record<string, unknown>,
 *   getData: (element: Element, key: string) => string|null
 * }} options
 */
const createAdminConfigTransport = ({ getConfig, getData }) => {
	/**
	 * @returns {Record<string, unknown>}
	 */
	const cfg = () => getConfig() || {};
	const restClient = createAdminConfigRestClient({ getConfig: cfg });

	/**
	 * @param {HTMLFormElement|null} form
	 * @param {string} endpoint
	 * @returns {string}
	 */
	const restActionPath = (form, endpoint) => {
		const schemaId = form ? getData(form, 'schema-id') : '';
		const normalizedEndpoint = String(endpoint || '').replace(/^\/+|\/+$/g, '');
		const routeEndpoint = normalizedEndpoint === 'save' ? 'values' : normalizedEndpoint;

		return schemaId && routeEndpoint ? `schemas/${schemaId}/${routeEndpoint}` : '';
	};

	return {
		hasRestTransport: () => restClient.hasTransport(),
		requestRest: restClient.request,
		restActionPath,
	};
};

module.exports = {
	createAdminConfigTransport,
};
