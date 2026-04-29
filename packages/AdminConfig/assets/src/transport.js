// @ts-check

const apiFetchModule = require('@wordpress/api-fetch');
const apiFetch = apiFetchModule.default || apiFetchModule;

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
const hasRestTransport = (cfg) => !!(cfg.restUrl && cfg.restNonce);

/**
 * @param {Record<string, unknown>} cfg
 * @returns {boolean}
 */
const hasLegacyAjaxTransport = (cfg) => legacyAjaxFlagEnabled(cfg) && !!cfg.ajaxUrl;

/**
 * @param {Record<string, unknown>} cfg
 * @param {string} path
 * @returns {string}
 */
const restUrl = (cfg, path) => `${String(cfg.restUrl || '').replace(/\/+$/, '')}/${path.replace(/^\/+/, '')}`;

/**
 * @param {HeadersInit|undefined} headersInit
 * @returns {Record<string, string>}
 */
const plainHeaders = (headersInit) => {
	const headers = {};
	new Headers(headersInit || {}).forEach((value, key) => {
		headers[key] = value;
	});
	return headers;
};

/**
 * @param {unknown} parsed
 * @returns {AdminConfigResponse}
 */
const normalizeRestSuccess = (parsed) => {
	if (parsed && typeof parsed === 'object' && 'success' in parsed) {
		return /** @type {AdminConfigResponse} */ (parsed);
	}

	return {
		success: true,
		data: /** @type {Record<string, unknown>} */ (parsed ?? {}),
	};
};

/**
 * @param {unknown} error
 * @param {string} fallbackMessage
 * @returns {AdminConfigResponse}
 */
const normalizeRestError = (error, fallbackMessage) => {
	const err = /** @type {{ code?: string, message?: string, data?: { status?: number, data?: Record<string, unknown> } }} */ (error || {});
	const nestedData = err.data && typeof err.data.data === 'object' && err.data.data
		? err.data.data
		: {};
	const data = { ...nestedData };

	if (!data.message) data.message = err.message || fallbackMessage;
	if (err.code && !data.code) data.code = err.code;
	if (err.data?.status && !data.status) data.status = err.data.status;

	return {
		success: false,
		data,
	};
};

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
	 * @param {string} path
	 * @param {RequestInit} [options]
	 * @returns {Promise<AdminConfigResponse>}
	 */
	const requestRest = async (path, options = {}) => {
		const currentConfig = cfg();
		const headers = plainHeaders(options.headers);
		headers['X-WP-Nonce'] = String(currentConfig.restNonce || '');

		if (options.body instanceof FormData) {
			options.body.set('_wpnonce', String(currentConfig.restNonce || ''));
		}

		try {
			const parsed = await apiFetch({
				...options,
				headers,
				url: restUrl(currentConfig, path),
			});

			return normalizeRestSuccess(parsed);
		} catch (error) {
			return normalizeRestError(error, String(currentConfig.saveError || 'Request failed'));
		}
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
		hasRestTransport: () => hasRestTransport(cfg()),
		requestLegacyAjax,
		requestRest,
		restActionPath,
	};
};

module.exports = {
	createAdminConfigTransport,
};
