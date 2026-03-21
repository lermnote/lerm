// // services/LoadPageService.js
// import BaseService from './BaseService.js';
// import CacheDB from './CacheDB.js';
// import { delegate } from '../utils.js';

// export default class LoadPageService extends BaseService {
//   constructor({ apiUrl, action, containerId, allowUrls = [], ignoreUrls = [], cacheExpiry = 5 * 60 * 1000 }) {
//     super(apiUrl);
//     this.containerId = containerId;
//     this.ignoreUrls = ignoreUrls;
//     this.allowUrls = allowUrls;
//     this.action = action;
//     this.cacheExpiry = cacheExpiry;
//     this.state = { ajaxLoading: false, ajaxStarted: false };
//     this.cacheDB = new CacheDB();
//     this.onPopState = this.onPopState.bind(this);
//     this.shouldInterceptLink = this.shouldInterceptLink.bind(this);
//     this.loadPage = this.loadPage.bind(this);
//   }

//   async init() {
//     this.onLinkClick(this.shouldInterceptLink, this.loadPage);
//     this.onSearchForm('form[method="GET"]', this.loadPage);
//     window.onpopstate = this.onPopState;
//     await this.clearExpiredCache();
//     console.log("LoadPageService initialized.");
//   }

//   onLinkClick(interceptCallback, callback) {
//     delegate("click", "a", (event, link) => {
//       if (interceptCallback && interceptCallback(link)) {
//         event.preventDefault();
//         this.updateNavState(link);
//         callback(link.href);
//       }
//     });
//   }

//   onSearchForm(selector, callback) {
//     delegate("submit", selector, (event, form) => {
//       event.preventDefault();
//       const params = Object.fromEntries(new FormData(form));
//       callback(form.action, false, params);
//     });
//   }

//   // Popstate 应传 isPopState 并更新 nav
//   onPopState() {
//     if (!this.state.ajaxStarted) {
//       if (this.shouldInterceptLink(window.location.href)) {
//         console.log('Popstate triggered');
//         this.loadPage(window.location.href, true).then(() => {
//           // update nav active based on location
//           const links = document.querySelectorAll('.nav-link');
//           links.forEach(a => {
//             try {
//               const href = new URL(a.href, window.location.origin).pathname;
//               a.classList.toggle('active', href === location.pathname);
//             } catch (e) { }
//           });
//         });
//       }
//     }
//   }
  
//   async clearExpiredCache() {
//     const db = await this.cacheDB.openDB();
//     const transaction = db.transaction(this.cacheDB.storeName, "readwrite");
//     const store = transaction.objectStore(this.cacheDB.storeName);
//     const request = store.openCursor();
//     request.onsuccess = (event) => {
//       const cursor = event.target.result;
//       if (cursor) {
//         const { timestamp, expiry } = cursor.value;
//         if (Date.now() - timestamp > expiry) {
//           store.delete(cursor.key);
//         }
//         cursor.continue();
//       }
//     };
//   }

//   async loadPage(url, isPopState = false, params = null) {
//     if (this.state.ajaxLoading || this.state.ajaxStarted) {
//       // 如果已有未完成请求，用 abort 取消（如果实现了）
//       if (this.currentAbort) this.currentAbort.abort();
//     }
//     this.currentAbort = new AbortController();
//     const signal = this.currentAbort.signal;

//     this.state.ajaxStarted = true;
//     this.state.ajaxLoading = true;
//     const container = document.getElementById(this.containerId);
//     if (!container) { this._resetState(); return; }

//     // 规范化 URL + params
//     const paramStr = params ? new URLSearchParams(params).toString() : "";
//     const fullUrl = paramStr ? `${url}?${paramStr}` : url;

//     if (!isPopState && history.pushState) {
//       history.pushState({}, "", new URL(fullUrl, window.location.origin).href);
//     }

//     const cachedData = await this.cacheDB.get(fullUrl);

//     if (cachedData) {
//       this.fadeOut(container, () => {
//         this.updatePageContent(container, cachedData);
//         this.fadeIn(container);
//         window.scrollTo({ top: 0, behavior: "smooth" });
//         this._resetState();
//       });
//       return;
//     }

//     try {
//       this.fadeOut(container);
//       const response = await this.fetchData({
//         url: `${this.apiUrl}?action=${this.action}&url=${encodeURIComponent(fullUrl)}`,
//         method: "GET",
//         headers: { 'X-WP-Nonce': (window.lermData && lermData.nonce) || '' },
//         signal
//       });

//       this.updatePageContent(container, response.data);
//       await this.cacheDB.set(fullUrl, response.data, this.cacheExpiry);
//       // analytics: 可选 gtag 或 ga
//       if (window.gtag) window.gtag('event', 'page_view', { page_path: new URL(fullUrl).pathname });

//       // if (response.success) {
//       //   this.updatePageContent(container, response.data);
//       //   await this.cacheDB.set(fullUrl, response.data, this.cacheExpiry);
//       // } else {
//       //   throw new Error(response.message);
//       // }

//       this.fadeIn(container);
//       window.scrollTo({ top: 0, behavior: "smooth" });
//     } catch (error) {
//       if (error.name === 'AbortError') {
//         console.log('Request aborted');
//       } else {
//         console.error("Error during page load:", error);
//         this.displayError(container, "Failed to load page.");
//       }
//     } finally {
//       this._resetState();
//       this.currentAbort = null;
//     }
//   }

//   _resetState() {
//     this.state.ajaxStarted = false;
//     this.state.ajaxLoading = false;
//   }

//   updatePageContent(container, data) {
//     document.title = data.title || document.title;
//     this.updateMeta("description", data.meta_description);
//     this.updateMeta("keywords", data.meta_keywords);
//     container.innerHTML = data.content || "";
//     container.setAttribute("aria-live", "polite");
//     document.dispatchEvent(new Event("contentLoaded"));
//   }

//   updateMeta(name, content) {
//     if (!content) return;
//     let meta = document.querySelector(`meta[name="${name}"]`);
//     if (!meta) {
//       meta = document.createElement("meta");
//       meta.setAttribute("name", name);
//       document.head.appendChild(meta);
//     }
//     meta.setAttribute("content", content);
//   }

//   updateNavState(el) {
//     if (!el) return;
//     const navLinks = document.querySelectorAll('.nav-link');
//     navLinks.forEach(link => link.classList.remove('active'));
//     el.classList.add('active');
//   }

//   shouldInterceptLink(link) {
//     // link may be an anchor element or a string (href)
//     try {
//       if (!link) return false;
//       const url = (typeof link === 'string') ? new URL(link, window.location.origin) : new URL(link.href, window.location.origin);
//       if (!["http:", "https:"].includes(url.protocol)) return false;
//       if (url.origin === window.location.origin && url.pathname === window.location.pathname && url.hash) return false;
//       if (url.hash && url.pathname !== window.location.pathname) return true;
//       if (!this.isSameOrigin(url) && !this.isAllowedSubdomain(url)) return false;
//       if (!this.shouldProcessUrl(url.href)) return false;
//       if (this.state.ajaxLoading) return false;
//       return true;
//     } catch (err) {
//       console.warn('shouldInterceptLink error', err);
//       return false;
//     }
//   }

//   shouldProcessUrl(url) {
//     return this.isAllowedUrl(url) && !this.isIgnoredUrl(url);
//   }

//   isSameOrigin(url) { return url.origin === window.location.origin; }

//   isAllowedSubdomain(url) {
//     const allowedSubdomains = ["sub1.example.com", "sub2.example.com"];
//     return allowedSubdomains.some((subdomain) => url.hostname.endsWith(subdomain));
//   }

//   isAllowedUrl(url) {
//     if (this.allowUrls.length === 0) return true;
//     return this.allowUrls.some((pattern) => url.includes(pattern));
//   }

//   isIgnoredUrl(url) {
//     return this.ignoreUrls.some(ignore => url.includes(ignore));
//   }

//   displayError(container, message) {
//     this.fadeIn(container);
//     container.innerHTML = `<p class="text-danger">${message}</p>`;
//   }

//   // 修正 fade 动画
//   fadeIn(element) {
//     element.style.opacity = 0;
//     const duration = 500;
//     const startTime = performance.now();
//     const fade = (currentTime) => {
//       const elapsed = currentTime - startTime;
//       const progress = Math.min(elapsed / duration, 1);
//       element.style.opacity = progress;
//       if (progress < 1) requestAnimationFrame(fade);
//     };
//     requestAnimationFrame(fade);
//   }

//   fadeOut(element, callback) {
//     const duration = 500;
//     const startTime = performance.now();
//     const fade = (currentTime) => {
//       const elapsed = currentTime - startTime;
//       const progress = Math.min(elapsed / duration, 1);
//       element.style.opacity = 1 - progress;
//       if (progress < 1) requestAnimationFrame(fade);
//       else if (callback) callback();
//     };
//     requestAnimationFrame(fade);
//   }
// }
// services/LoadPageService.js
import BaseService from './BaseService.js';
import CacheDB from './CacheDB.js';
import { delegate } from '../utils.js';

export default class LoadPageService extends BaseService {
  constructor({ apiUrl, action = 'page', containerId, allowUrls = [], ignoreUrls = [], cacheExpiry = 5 * 60 * 1000 }) {
    super(apiUrl);
    this.containerId = containerId;
    this.ignoreUrls = ignoreUrls;
    this.allowUrls = allowUrls;
    this.action = action;
    this.cacheExpiry = cacheExpiry;
    this.state = { ajaxLoading: false, ajaxStarted: false };
    this.cacheDB = new CacheDB();
    this.onPopState = this.onPopState.bind(this);
    this.shouldInterceptLink = this.shouldInterceptLink.bind(this);
    this.loadPage = this.loadPage.bind(this);

    // Abort control
    this.currentAbort = null;
  }

  async init() {
    this.onLinkClick(this.shouldInterceptLink, this.loadPage);
    this.onSearchForm('form[method="GET"]', this.loadPage);
    window.addEventListener('popstate', this.onPopState);
    await this.clearExpiredCache();
    console.log("LoadPageService initialized.");
  }

  onLinkClick(interceptCallback, callback) {
    // delegate clicks on anchors
    delegate("click", "a", (event, link) => {
      if (interceptCallback && interceptCallback(link)) {
        event.preventDefault();
        this.updateNavState(link);
        callback(link.href);
      }
    });
  }

  onSearchForm(selector, callback) {
    delegate("submit", selector, (event, form) => {
      event.preventDefault();
      const paramsObj = Object.fromEntries(new FormData(form));
      // ensure params go to third argument of loadPage
      callback(form.action || window.location.pathname, false, paramsObj);
    });
  }

  onPopState() {
    if (!this.state.ajaxStarted) {
      const href = window.location.href;
      if (this.shouldInterceptLink(href)) {
        console.log('Popstate triggered');
        this.loadPage(href, true).then(() => {
          // update nav active based on current path
          const links = document.querySelectorAll('.nav-link');
          links.forEach(a => {
            try {
              const hrefPath = new URL(a.href, window.location.origin).pathname;
              a.classList.toggle('active', hrefPath === location.pathname);
            } catch (e) {}
          });
        }).catch(() => {});
      }
    }
  }

  async clearExpiredCache() {
    const db = await this.cacheDB.openDB();
    const transaction = db.transaction(this.cacheDB.storeName, "readwrite");
    const store = transaction.objectStore(this.cacheDB.storeName);
    const request = store.openCursor();
    request.onsuccess = (event) => {
      const cursor = event.target.result;
      if (cursor) {
        const { timestamp, expiry } = cursor.value;
        if (Date.now() - timestamp > expiry) {
          store.delete(cursor.key);
        }
        cursor.continue();
      }
    };
  }

  async loadPage(url, isPopState = false, params = null) {
    if (this.state.ajaxLoading || this.state.ajaxStarted) {
      // cancel previous request if any
      if (this.currentAbort) this.currentAbort.abort();
    }

    this.state.ajaxStarted = true;
    this.state.ajaxLoading = true;

    const container = document.getElementById(this.containerId);
    if (!container) {
      console.error("Container not found.");
      this._resetState();
      return;
    }

    // normalize params to object and build fullUrl
    const paramsObj = (params && typeof params === 'object') ? params : null;
    const paramStr = paramsObj ? new URLSearchParams(paramsObj).toString() : '';
    const fullUrl = paramStr ? `${url.split('?')[0]}?${paramStr}` : url;

    if (!isPopState && history.pushState) {
      history.pushState({}, "", new URL(fullUrl, window.location.origin).href);
    }

    // check cache first
    const cachedData = await this.cacheDB.get(fullUrl);
    if (cachedData) {
      this.fadeOut(container, () => {
        try {
          this.updatePageContent(container, cachedData);
        } catch (e) {
          console.error('Failed update from cache', e);
        }
        this.fadeIn(container);
        window.scrollTo({ top: 0, behavior: "smooth" });
        this._resetState();
      });
      return;
    }

    // prepare fetch with abort controller and optional conditional headers
    this.currentAbort = new AbortController();
    const headers = { 'X-WP-Nonce': (window.lermData && lermData.nonce) || '' };
    // If cache had an etag stored inside its data, we would include If-None-Match. (optional)
    // const prevEtag = cachedData?.etag; // cachedData may contain meta if you stored it
    // if (prevEtag) headers['If-None-Match'] = prevEtag;

    try {
      this.fadeOut(container);
      const response = await this.fetchData({
        // url: `${this.apiUrl.replace(/\/$/, '')}?action=${this.action}&url=${encodeURIComponent(fullUrl)}`,
        url: `${this.apiUrl.replace(/\/$/, '')}?url=${encodeURIComponent(fullUrl)}`,
        method: "GET",
        headers,
        fetchOptions: { signal: this.currentAbort.signal, credentials: 'same-origin' }
      });

      // If REST returns object with success wrapper (e.g. admin-ajax style), adapt:
      let data = response;
      if (response && response.data && response.success !== undefined) {
        if (!response.success) throw new Error(response.message || 'Request failed');
        data = response.data;
      }

      if (data) {
        this.updatePageContent(container, data);
        // store full response into cacheDB (including metadata if present)
        await this.cacheDB.set(fullUrl, data, this.cacheExpiry);
      } else {
        throw new Error('Empty response data');
      }

      this.fadeIn(container);
      window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (error) {
      if (error.name === 'AbortError') {
        console.log('LoadPage request aborted');
      } else {
        console.error("Error during page load:", error);
        this.displayError(container, "Failed to load page.");
      }
    } finally {
      this._resetState();
      this.currentAbort = null;
    }
  }

  _resetState() {
    this.state.ajaxStarted = false;
    this.state.ajaxLoading = false;
  }

  updatePageContent(container, data) {
    document.title = data.title || document.title;
    this.updateMeta("description", data.meta_description);
    this.updateMeta("keywords", data.meta_keywords);
    container.innerHTML = data.content || "";
    container.setAttribute("aria-live", "polite");
    document.dispatchEvent(new Event("contentLoaded"));
  }

  updateMeta(name, content) {
    if (!content) return;
    let meta = document.querySelector(`meta[name="${name}"]`);
    if (!meta) {
      meta = document.createElement("meta");
      meta.setAttribute("name", name);
      document.head.appendChild(meta);
    }
    meta.setAttribute("content", content);
  }

  updateNavState(el) {
    if (!el) return;
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => link.classList.remove('active'));
    el.classList.add('active');
  }

  shouldInterceptLink(link) {
    try {
      if (!link) return false;
      const url = (typeof link === 'string') ? new URL(link, window.location.origin) : new URL(link.href, window.location.origin);
      if (!["http:", "https:"].includes(url.protocol)) return false;

      // same-page hash anchor -> ignore
      if (url.origin === window.location.origin && url.pathname === window.location.pathname && url.hash) return false;
      // hash pointing to different page -> handle via AJAX load (so user lands at hash after content inserted)
      if (url.hash && url.pathname !== window.location.pathname) return true;

      if (!this.isSameOrigin(url) && !this.isAllowedSubdomain(url)) return false;
      if (!this.shouldProcessUrl(url.href)) return false;
      if (this.state.ajaxLoading) return false;
      return true;
    } catch (err) {
      console.warn('shouldInterceptLink error', err);
      return false;
    }
  }

  shouldProcessUrl(url) {
    return this.isAllowedUrl(url) && !this.isIgnoredUrl(url);
  }

  isSameOrigin(url) { return url.origin === window.location.origin; }

  isAllowedSubdomain(url) {
    const allowedSubdomains = ["sub1.example.com", "sub2.example.com"];
    return allowedSubdomains.some((subdomain) => url.hostname.endsWith(subdomain));
  }

  isAllowedUrl(url) {
    if (this.allowUrls.length === 0) return true;
    return this.allowUrls.some((pattern) => url.includes(pattern));
  }

  isIgnoredUrl(url) {
    return this.ignoreUrls.some(ignore => url.includes(ignore));
  }

  displayError(container, message) {
    this.fadeIn(container);
    container.innerHTML = `<p class="text-danger">${message}</p>`;
  }

  fadeIn(element) {
    element.style.opacity = 0;
    const duration = 500;
    const startTime = performance.now();
    const fade = (currentTime) => {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      element.style.opacity = progress;
      if (progress < 1) requestAnimationFrame(fade);
    };
    requestAnimationFrame(fade);
  }

  fadeOut(element, callback) {
    const duration = 500;
    const startTime = performance.now();
    const fade = (currentTime) => {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      element.style.opacity = 1 - progress;
      if (progress < 1) requestAnimationFrame(fade);
      else if (callback) callback();
    };
    requestAnimationFrame(fade);
  }
}
