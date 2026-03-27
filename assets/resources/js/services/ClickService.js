// services/ClickService.js
import BaseService from './BaseService.js';
import { delegate } from '../utils.js';

export default class ClickService extends BaseService {
	/**
	 * options:
	 * { apiUrl, selector, route, security, headers = {}, additionalData = {}, isThrottled = false, cacheExpiryTime = 60000, enableCache = true, cacheStorage = 'session' }
	 */
	constructor(options = {}) {
		super(options.apiUrl);
		const {
			selector,
			route,
			security,
			headers = {},
			additionalData = {},
			isThrottled = false,
			method = 'POST',
			cacheExpiryTime = 60000,
			enableCache = true,
			cacheStorage = 'session'
		} = options;

		Object.assign(this, { selector, route, security, headers, additionalData, method, cacheExpiryTime, enableCache, cacheStorage });
		this.messageId = options.messageId || null;

		// bind handler
		this.clickHandler = isThrottled ? this.rateLimit(this.handleClick, 1000, true) : this.handleClick;
		delegate('click', this.selector, this.clickHandler);
	}

	handleClick = async (event, target) => {
		// compatibility: if delegate passed only event, try to find target
		if (!target) target = event && event.currentTarget ? event.currentTarget : event.target;
		if (!target) return;

		event.preventDefault();
		this.beforeClick(event, target);

		// validate nonce
		const nonce = this.security ?? target.dataset.nonce;
		if (!nonce) {
			this.onError(new Error('Missing nonce'), target);
			return;
		}

		// build payload: merge explicit dataset and additionalData
		const payload = { ...this.additionalData };
		Object.keys(target.dataset || {}).forEach((k) => {
			if (['nonce', 'action'].includes(k)) return;
			payload[k] = target.dataset[k];
		});

		const isGet = this.method.toUpperCase() === 'GET';

		// generate unique cacheKey using payload fingerprint
		let payloadFingerprint = '';
		try { payloadFingerprint = btoa(unescape(encodeURIComponent(JSON.stringify(payload)))).slice(0, 24); } catch (e) { payloadFingerprint = String(Date.now()); }
		const cacheKey = `click_action_${this.route}_${payloadFingerprint}`;

		if (this.enableCache && this.isCacheValid(cacheKey)) {
			this.useCache(cacheKey, target);
			return;
		}

		// find the button element for spinner control
		const buttonEl = target instanceof HTMLElement ? target : (target.closest && target.closest('button, a')) || target;
		this.toggleButton(buttonEl, true);

		const url = [
			this.apiUrl.replace(/\/$/, ''),
			this.route.replace(/^\//, '').replace(/\/$/, ''),
		].join('/');
		const requestUrl = new URL(url, window.location.origin);

		if (isGet) {
			Object.entries(payload).forEach(([key, value]) => {
				if (value === undefined || value === null || value === '') return;
				requestUrl.searchParams.set(key, String(value));
			});
		}

		const requestHeaders = {
			'X-WP-Nonce': nonce,
			...this.headers
		};

		if (!isGet) {
			requestHeaders['Content-Type'] = 'application/json';
		}

		try {
			const response = await this.fetchData({
				url: requestUrl.toString(),
				method: this.method,
				body: isGet ? null : JSON.stringify(payload),
				headers: requestHeaders,
				fetchOptions: {
					credentials: 'same-origin'
				}
			});

			if (this.enableCache) this.updateCache(cacheKey, response);
			this.onSuccess(response, target);
		} catch (err) {
			this.onError(err, target);
		} finally {
			this.toggleButton(buttonEl, false);
		}
	}

	isCacheValid = (cacheKey) => {
		const storage = this.cacheStorage === 'local' ? localStorage : sessionStorage;
		const cachedData = storage.getItem(cacheKey);
		const cacheTime = parseInt(storage.getItem(`${cacheKey}_time`), 10);
		return cachedData && !Number.isNaN(cacheTime) && (Date.now() - cacheTime) < this.cacheExpiryTime;
	}

	useCache = (cacheKey, target = null) => {
		const storage = this.cacheStorage === 'local' ? localStorage : sessionStorage;
		const cachedResponse = JSON.parse(storage.getItem(cacheKey));
		this.onSuccess(cachedResponse, target);
	}

	updateCache = (cacheKey, response) => {
		const storage = this.cacheStorage === 'local' ? localStorage : sessionStorage;
		storage.setItem(cacheKey, JSON.stringify(response));
		storage.setItem(`${cacheKey}_time`, String(Date.now()));
	}

	beforeClick = () => { /* hook */ }

	onSuccess = (response, target) => {
		this.displayMessage && this.displayMessage('Click action was successful!', 'success');
		console.log('Response:', response);
	}

	onError = (error, target) => {
		this.displayMessage && this.displayMessage('Failed to process click action.', 'danger');
		console.error('Error:', error);
		if (target && (target instanceof HTMLElement)) {
			target.setAttribute('disabled', 'disabled');
			if (error && error.message) target.textContent = error.message;
		}
	}
}
