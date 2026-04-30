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
const hasRestConfig = (cfg) => !!(cfg.restUrl && cfg.restNonce);

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
 * @param {{
 *   getConfig: () => Record<string, unknown>
 * }} options
 */
const createAdminConfigRestClient = ({ getConfig }) => {
	/**
	 * @returns {Record<string, unknown>}
	 */
	const cfg = () => getConfig() || {};

	/**
	 * @param {string} path
	 * @param {RequestInit} [options]
	 * @returns {Promise<AdminConfigResponse>}
	 */
	const request = async (path, options = {}) => {
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

	return {
		hasTransport: () => hasRestConfig(cfg()),
		request,
	};
};

module.exports = {
	createAdminConfigRestClient,
	hasRestConfig,
	normalizeRestError,
	normalizeRestSuccess,
	restUrl,
};
