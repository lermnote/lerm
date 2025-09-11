// // services/BaseService.js
// export default class BaseService {
//   constructor(apiUrl) {
//     this.apiUrl = apiUrl;
//     this.messageTimeout = null;
//   }

//   fetchData = async ({ url, method = 'GET', body = null, headers = {}, fetchOptions }) => {
//     const options = {
//       method,
//       headers: { ...headers },
//       body: method !== 'GET' ? body : null,
//       ...fetchOptions, // 可以包括 credentials, signal, mode, cache, redirect...
//     };

//     try {
//       const response = await fetch(url, options);

//       const data = await response.json();
//       if (!response.ok) {
//         // 如果后端返回标准化 {message, code}，则优先显示；否则使用 statusText
//         const msg = (data && data.message) ? data.message : response.statusText;
//         throw new Error(`${response.status} ${response.statusText}: ${msg}`);
//       }
//       // if response is empty or not json, this will throw
//       return data;
//     } catch (error) {
//       this.handleError(error);
//       throw error;
//     }
//   }

//   handleError = (error) => {
//     console.error("An error occurred:", error?.message || error);
//     // alert(`An error occurred: ${error?.message || error}`);
//   }

//   rateLimit = (func, wait, isThrottle = false) => {
//     let timeout, lastTime = 0;
//     return (...args) => {
//       const context = this;
//       const now = Date.now();
//       const later = () => {
//         timeout = null;
//         if (!isThrottle) func.apply(context, args);
//       };
//       const remaining = wait - (now - lastTime);
//       if (isThrottle && remaining <= 0) {
//         clearTimeout(timeout);
//         timeout = null;
//         lastTime = now;
//         func.apply(context, args);
//       } else if (!timeout) {
//         timeout = setTimeout(later, isThrottle ? remaining : wait);
//       }
//     };
//   }

//   displayMessage = (message, type = 'info', duration = 5000) => {
//     if (!this.messageId) return;
//     const messageElement = document.getElementById(this.messageId);
//     if (messageElement) {
//       messageElement.innerHTML = message;
//       messageElement.classList.add(`text-${type}`);
//       messageElement.classList.remove('invisible');
//       clearTimeout(this.messageTimeout);
//       this.messageTimeout = setTimeout(() => {
//         messageElement.classList.add('invisible');
//         messageElement.classList.remove(`text-${type}`);
//       }, duration);
//     }
//   }

//   /**
//    * Toggle the loading state of a button by adding/removing a spinner inside the button
//    * and disabling/enabling that specific button.
//    *
//    * @param {HTMLElement} button - The button element to toggle.
//    * @param {boolean} isLoading - Whether to show the loading spinner.
//    * @param {boolean} [disabled=false] - Whether to keep the button disabled at the end.
//    */
//   toggleButton = (button, isLoading, disabled = false) => {
//     if (!button || !(button instanceof HTMLElement)) return;

//     // Use a data attribute to avoid collisions and to find spinner only inside this button
//     const SPINNER_SELECTOR = '[data-lerm-spinner]';

//     if (isLoading) {
//       // Prevent inserting duplicate spinner
//       if (!button.querySelector(SPINNER_SELECTOR)) {
//         const spinner = document.createElement('span');
//         spinner.setAttribute('aria-hidden', 'true');
//         spinner.setAttribute('data-lerm-spinner', '1');
//         spinner.className = 'spinner-border spinner-border-sm';
//         // insert at start, but keep button text intact
//         button.insertBefore(spinner, button.firstChild);
//       }
//       // disable to prevent double submissions
//       button.setAttribute('disabled', 'disabled');
//       // ARIA: indicate busy
//       button.setAttribute('aria-busy', 'true');
//     } else {
//       // remove only spinner inside this button
//       const spinner = button.querySelector(SPINNER_SELECTOR);
//       if (spinner) spinner.remove();

//       // control disabled state according to param
//       if (!disabled) {
//         button.removeAttribute('disabled');
//       } else {
//         button.setAttribute('disabled', 'disabled');
//       }
//       // ARIA: clear busy
//       button.removeAttribute('aria-busy');
//     }
//   }
// }
// services/BaseService.js
export default class BaseService {
  constructor(apiUrl) {
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
    const options = {
      method,
      headers: { ...headers },
      body: method !== 'GET' ? body : null,
      ...fetchOptions,
    };

    try {
      const response = await fetch(url, options);
      const text = await response.text();
      let data = null;
      try {
        data = text ? JSON.parse(text) : null;
      } catch (err) {
        data = text;
      }

      if (!response.ok) {
        const msg = (data && data.message) ? data.message : response.statusText;
        throw new Error(`${response.status} ${response.statusText}: ${msg}`);
      }
      return data;
    } catch (error) {
      this.handleError(error);
      throw error;
    }
  }

  handleError = (error) => {
    console.error("An error occurred:", error?.message || error);
    // graceful fallback: if no UI message area, just console
    if (this.messageId) {
      this.displayMessage(`An error occurred: ${error?.message || error}`, 'danger', 7000);
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
   * Toggle the loading state of a button by adding/removing a spinner inside the button
   * and disabling/enabling that specific button.
   *
   * @param {HTMLElement} button - The button element to toggle.
   * @param {boolean} isLoading - Whether to show the loading spinner.
   * @param {boolean} [disabled=false] - Whether to keep the button disabled at the end.
   */
  toggleButton = (button, isLoading, disabled = false) => {
    if (!button || !(button instanceof HTMLElement)) return;

    const SPINNER_SELECTOR = '[data-lerm-spinner]';

    if (isLoading) {
      if (!button.querySelector(SPINNER_SELECTOR)) {
        const spinner = document.createElement('span');
        spinner.setAttribute('aria-hidden', 'true');
        spinner.setAttribute('data-lerm-spinner', '1');
        spinner.className = 'spinner-border spinner-border-sm';
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
