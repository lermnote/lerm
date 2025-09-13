
// services/ClickService.js
import BaseService from './BaseService.js';
import CacheManager from '../cache/CacheManager.js';
import CacheDB from '../cache/CacheDB.js';
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
      cacheExpiryTime = 60000,
      enableCache = true,
      cacheStorage = 'session',
      cacheDBInstance = null,
      messageId = null,
    } = options;

    Object.assign(this, {
      selector,
      route,
      security,
      headers,
      additionalData,
      cacheExpiryTime,
      enableCache,
      cacheStorage: cacheStorage === 'local' ? 'local' : 'session'
    });
    this.messageId = messageId;

    // 构造 CacheManager（优先使用传入的 cacheDBInstance，否则自动 new 一个 CacheDB 也可以）
    this.cache = new CacheManager({
      cacheDB: cacheDBInstance instanceof CacheDB ? cacheDBInstance : null,
      storage: this.cacheStorage,
      defaultExpiry: this.cacheExpiryTime,
      namespace: 'clicksvc',
      onError: (e) => console.warn('ClickService cache error:', e),
    });

    const rawHandler = this.handleClick.bind(this);
    this.clickHandler = isThrottled
      ? this.rateLimit(rawHandler, 1000, true)
      : rawHandler;

    delegate('click', this.selector, this.clickHandler);
  }

  // 稳定 stringify（key 排序），用于 fingerprint
  stableStringify = (value) => {
    const type = Object.prototype.toString.call(value);
    if (type === '[object Object]') {
      const keys = Object.keys(value).sort();
      return `{${keys.map((k) => JSON.stringify(k) + ':' + this.stableStringify(value[k])).join(',')}}`;
    }
    if (type === '[object Array]') {
      return `[${value.map((v) => this.stableStringify(v)).join(',')}]`;
    }
    return JSON.stringify(value);
  };

  fingerprint = (obj) => {
    try {
      const str = this.stableStringify(obj);
      let hash = 5381;
      for (let i = 0; i < str.length; i++) {
        hash = (hash * 33) ^ str.charCodeAt(i);
      }
      return (hash >>> 0).toString(36).slice(0, 10);
    } catch (e) {
      return String(Date.now());
    }
  };

  handleClick = async (event, target) => {
    if (!target) target = event && event.currentTarget ? event.currentTarget : event.target;
    if (!target) return;

    if (event && typeof event.preventDefault === 'function') event.preventDefault();

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


    const payloadFingerprint = this.fingerprint(payload);
    const sanitizedRoute = String(this.route || '').replace(/^\/|\/$/g, '') || 'root';
    const cacheKey = `click_action_${sanitizedRoute}_${payloadFingerprint}`;

    if (this.enableCache) {
      try {
        const url = `${this.apiUrl.replace(/\/$/, '')}/${String(this.route || '').replace(/^\//, '')}`;
        const response = await this.cache.staleWhileRevalidate(
          cacheKey,
          async () => {
            const res = await this.fetchData({
              url,
              method: 'POST',
              body: payload,
              headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce,
                ...this.headers,
              },
              fetchOptions: { credentials: 'same-origin' },
            });
            return res;
          },
          { expiry: this.cacheExpiryTime, background: true } // background refresh when cache hit
        );

        if (response) {
          // cached or fresh response returned
          this.onSuccess(response, target);
          return;
        }
        // response null -> fallthrough to live request below (rare)
      } catch (e) {
        // cache layer failed -> fallback to live fetch
        console.warn('Cache SWR failed, continuing to live request:', e);
      }
    }

    let buttonEl = null;
    try {
      if (target instanceof HTMLElement) {
        buttonEl =
          (target.closest && target.closest('button, a, [role="button"], input[type="button"], input[type="submit"]')) ||
          (target.matches && target.matches('button, a, [role="button"], input[type="button"], input[type="submit"]') ? target : null);
      }
    } catch (e) {
      buttonEl = null;
    }

    this.toggleButton(buttonEl || target, true);

    const url = `${this.apiUrl.replace(/\/$/, '')}/${String(this.route || '').replace(/^\//, '')}`;

    try {
      const response = await this.fetchData({
        url,
        method: 'POST',
        body: payload,
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': nonce,
          ...this.headers
        },
        fetchOptions: {
          credentials: 'same-origin'
        }
      });

      // write cache
      if (this.enableCache) {
        try {
          await this.cache.set(cacheKey, response, this.cacheExpiryTime);
        } catch (e) {
          console.warn('cache write failed:', e);
        }
      }

      this.onSuccess(response, target);
    } catch (err) {
      this.onError(err, target);
    } finally {
      this.toggleButton(buttonEl || target, false);
    }
  }

  beforeClick = () => { /* hook */ }

  onSuccess = (response, target) => {
    if (typeof this.displayMessage === 'function') {
      this.displayMessage('Click action was successful!', 'success');
    }
    console.log('ClickService onSuccess:', response, target);
  };

  onError = (error, target) => {
    if (typeof this.displayMessage === 'function') {
      this.displayMessage('Failed to process click action.', 'danger');
    }
    console.error('ClickService onError:', error);
    try {
      if (target && target instanceof HTMLElement) {
        target.setAttribute('disabled', 'disabled');
        if (error && error.message) target.textContent = error.message;
      }
    } catch (e) { }
  };
}
