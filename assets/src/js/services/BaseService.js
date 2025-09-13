export default class BaseService {
  constructor(apiUrl = '') {
    this.apiUrl = apiUrl;
    this.messageTimeout = null;
    this.messageId = null; // 可被子类设置以使用 displayMessage
  }

  /**
   * fetchData supports arbitrary fetch options via fetchOptions
   * usage:
   *   fetchData({ url, method, body, headers, fetchOptions })
   */
  fetchData = async ({ url, method = 'GET', body = null, headers = {}, fetchOptions = {} }) => {
    const resolvedUrl = this.apiUrl ? new URL(url, this.apiUrl).toString() : url;

    const normalizedHeaders = {};
    if (headers instanceof Headers) {
      for (const [k, v] of headers.entries()) normalizedHeaders[k] = v;
    } else if (headers && typeof headers === 'object') {
      Object.assign(normalizedHeaders, headers);
    }

    // case-insensitive check for existing Content-Type
    const hasContentType = Object.keys(normalizedHeaders).some(k => k.toLowerCase() === 'content-type');

    // more robust plain-object test: exclude FormData, Blob, File, Array
    const isPlainObject = (v) => v && typeof v === 'object' && !Array.isArray(v) && !(v instanceof FormData) && !(v instanceof Blob);

    let bodyToSend = body;

    if (body != null && isPlainObject(body)) {
      // only set Content-Type when body is a plain object and caller didn't provide any Content-Type (case-insensitive)
      if (!hasContentType) {
        normalizedHeaders['Content-Type'] = 'application/json; charset=utf-8';
      }
      bodyToSend = JSON.stringify(body);
    }

    const controller = new AbortController();
    const externalSignal = fetchOptions.signal;
    const signal = externalSignal || controller.signal;

    let timeoutId = null;
    if (!externalSignal && fetchOptions.timeoutMs && typeof fetchOptions.timeoutMs === 'number') {
      timeoutId = setTimeout(() => controller.abort(), fetchOptions.timeoutMs);
    }

    const options = {
      method,
      headers: normalizedHeaders,
      body: method.toUpperCase() === 'GET' ? null : bodyToSend,
      signal,
      ...fetchOptions,
    };
    delete options.timeoutMs;


    try {
      const response = await fetch(resolvedUrl, options);

      if (timeoutId) clearTimeout(timeoutId);

      const contentType = response.headers.get('content-type') || '';
      const text = await response.text();

      let data = null;
      if (contentType.includes('application/json') || contentType.includes('+json')) {
        try {
          data = text ? JSON.parse(text) : null;
        } catch {
          data = text;
        }
      } else {
        try {
          data = text ? JSON.parse(text) : null;
        } catch {
          data = text;
        }
      }


      // try {
      //   data = text ? JSON.parse(text) : null;
      // } catch (err) {
      //   data = text;
      // }

      if (!response.ok) {
        const msg = (data && data.message) ? data.message : response.statusText;
        const err = new Error(`${response.status} ${response.statusText}: ${msg}`);
        err.status = response.status;
        err.response = response;
        err.data = data;
        throw err;
      }

      return data;
    } catch (error) {
      if (error.name === 'AbortError') {
        error.message = 'Request aborted (timeout or cancelled)';
      }
      this.handleError(error);
      throw error;
    }
  }

  handleError = (error) => {
    console.error("An error occurred:", error?.message || error);
    // graceful fallback: if no UI message area, just console
    if (this.messageId) {
      const message = error?.message ? String(error.message) : 'An unknown error occurred';
      this.displayMessage(`Error: ${message}`, 'danger', 7000);
    }
  }

  rateLimit = (func, wait, isThrottle = false) => {
    let timeout, lastTime = 0;
    return (...args) => {
      const context = this;
      const now = Date.now();
      const later = () => {
        timeout = null;
        if (!isThrottle) func.apply(context, args);
      };
      const remaining = wait - (now - lastTime);
      if (isThrottle && remaining <= 0) {
        clearTimeout(timeout);
        timeout = null;
        lastTime = now;
        func.apply(context, args);
      } else if (!timeout) {
        timeout = setTimeout(later, isThrottle ? remaining : wait);
      }
    };
  }


  displayMessage = (message, type = 'info', duration = 5000) => {
    if (!this.messageId) {
      console.warn('No messageId set for displayMessage:', message);
      return;
    }
    const messageElement = document.getElementById(this.messageId);
    if (messageElement) {
      messageElement.innerHTML = message;
      messageElement.classList.add(`text-${type}`);
      messageElement.classList.remove('invisible');
      clearTimeout(this.messageTimeout);
      this.messageTimeout = setTimeout(() => {
        messageElement.classList.add('invisible');
        messageElement.classList.remove(`text-${type}`);
      }, duration);
    }
  }

  /**
   * Toggle button loading state with Bootstrap 5 spinner
   * @param {HTMLElement} button - The button element
   * @param {boolean} isLoading - Whether to show loading spinner
   * @param {boolean} [disabled=false] - Whether to keep the button disabled when loading stops
   */
  toggleButton = (button, isLoading, disabled = false) => {
    if (!button || !(button instanceof HTMLElement)) return;

    const SPINNER_SELECTOR = '[data-lerm-spinner]';

    if (isLoading) {
      // Insert spinner if not already present
      if (!button.querySelector(SPINNER_SELECTOR)) {
        const spinner = document.createElement('span');
        spinner.setAttribute('data-lerm-spinner', '1');
        spinner.className = 'spinner-border spinner-border-sm me-2';
        spinner.setAttribute('role', 'status');
        spinner.setAttribute('aria-hidden', 'true');

        const sr = document.createElement('span');
        sr.className = 'visually-hidden';
        sr.textContent = 'Loading...';

        spinner.appendChild(sr);
        button.insertBefore(spinner, button.firstChild);
      }

      button.setAttribute('disabled', 'disabled');
      button.setAttribute('aria-busy', 'true');
    } else {
      const spinner = button.querySelector(SPINNER_SELECTOR);
      if (spinner) spinner.remove();

      if (!disabled) {
        button.removeAttribute('disabled');
      } else {
        button.setAttribute('disabled', 'disabled');
      }
      button.removeAttribute('aria-busy');
    }
  }
}
