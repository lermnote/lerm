// @ts-check

const apiFetchModule = require('@wordpress/api-fetch');
const apiFetch = apiFetchModule.default || apiFetchModule;
const { asRecord } = require('./records');

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
const restUrl = (cfg, path) => {
	const base = String(cfg.restUrl || '');
	const normalizedPath = path.replace(/^\/+/, '');

	if (!base.includes('rest_route=')) {
		return `${base.replace(/\/+$/, '')}/${normalizedPath}`;
	}

	try {
		const url = new URL(base);
		const route = url.searchParams.get('rest_route');

		if (!route) {
			return `${base.replace(/\/+$/, '')}/${normalizedPath}`;
		}

		const queryIndex = normalizedPath.indexOf('?');
		const routePath = queryIndex >= 0 ? normalizedPath.slice(0, queryIndex) : normalizedPath;
		const queryString = queryIndex >= 0 ? normalizedPath.slice(queryIndex + 1) : '';

		url.searchParams.set(
			'rest_route',
			`${ route.replace(/\/+$/, '') }/${ routePath.replace(/^\/+/, '') }`
		);

		new URLSearchParams(queryString).forEach((value, key) => {
			url.searchParams.set(key, value);
		});

		return url.toString();
	} catch (error) {
		return `${base.replace(/\/+$/, '')}/${normalizedPath}`;
	}
};

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
	const err = /** @type {{ code?: string, message?: string, data?: Record<string, unknown> }} */ (error || {});
	const errorData = asRecord(err.data);
	const nestedData = asRecord(errorData.data);
	const topLevelData = { ...errorData };
	delete topLevelData.data;
	const data = {
		...nestedData,
		...topLevelData,
	};

	if (!data.message) data.message = err.message || fallbackMessage;
	if (err.code && !data.code) data.code = err.code;
	if (errorData.status && !data.status) data.status = errorData.status;

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
