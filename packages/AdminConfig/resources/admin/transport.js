// @ts-check

const { createAdminConfigRestClient } = require('../core/rest-client');

/**
 * @typedef {{
 *   success: boolean,
 *   data: Record<string, unknown>
 * }} AdminConfigResponse
 */

/**
 * @param {Record<string, unknown>} cfg
 * @returns {boolean}
 */
const legacyAjaxFlagEnabled = (cfg) => ![ false, 0, '', '0', 'false' ].includes(
	/** @type {any} */ (cfg.legacyAjaxEnabled ?? true)
);

/**
 * @param {Record<string, unknown>} cfg
 * @returns {boolean}
 */
const hasLegacyAjaxTransport = (cfg) => legacyAjaxFlagEnabled(cfg) && !!cfg.ajaxUrl;

/**
 * @param {Response} response
 * @param {Record<string, unknown>} cfg
 * @returns {Promise<AdminConfigResponse>}
 */
const parseLegacyJsonResponse = async (response, cfg) => {
	const text = await response.text();

	try {
		const parsed = JSON.parse(text);

		if (parsed && typeof parsed === 'object' && 'success' in parsed) {
			return /** @type {AdminConfigResponse} */ (parsed);
		}

		if (!response.ok || (parsed && typeof parsed === 'object' && 'code' in parsed)) {
			return {
				success: false,
				data: parsed?.data?.data || { message: parsed?.message || cfg.saveError },
			};
		}

		return { success: true, data: parsed };
	} catch {
		if (!response.ok) throw new Error('Network error: ' + response.status);
		throw new Error('Invalid JSON response: ' + text.slice(0, 120));
	}
};

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
	 * @param {string} action
	 * @returns {string}
	 */
	const restActionPath = (form, action) => {
		const currentConfig = cfg();
		const schemaId = form ? getData(form, 'schema-id') : '';
		if (!schemaId) return '';

		if (action === currentConfig.saveAction) return `schema/${schemaId}/save`;
		if (action === currentConfig.resetAction) return `schema/${schemaId}/reset`;
		if (action === currentConfig.importAction) return `schema/${schemaId}/import`;
		if (action === currentConfig.exportAction) return `schema/${schemaId}/export`;

		return '';
	};

	/**
	 * @param {FormData} body
	 * @returns {Promise<AdminConfigResponse>}
	 */
	const requestLegacyAjax = (body) => {
		const currentConfig = cfg();
		return fetch(String(currentConfig.ajaxUrl), { method: 'POST', body })
			.then((response) => parseLegacyJsonResponse(response, currentConfig));
	};

	return {
		hasLegacyAjaxTransport: () => hasLegacyAjaxTransport(cfg()),
		hasRestTransport: () => restClient.hasTransport(),
		requestLegacyAjax,
		requestRest: restClient.request,
		restActionPath,
	};
};

module.exports = {
	createAdminConfigTransport,
};
