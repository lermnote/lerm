(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else if(typeof exports === 'object')
		exports["LermApp"] = factory();
	else
		root["LermApp"] = factory();
})(self, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/src/components.js":
/*!**********************************!*\
  !*** ./assets/src/components.js ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   appendPostsToDOM: () => (/* binding */ appendPostsToDOM),
/* harmony export */   handleCommentSuccess: () => (/* binding */ handleCommentSuccess),
/* harmony export */   handleLoginSuccess: () => (/* binding */ handleLoginSuccess),
/* harmony export */   handleUpdateProfileSuccess: () => (/* binding */ handleUpdateProfileSuccess),
/* harmony export */   likeBtnHandle: () => (/* binding */ likeBtnHandle),
/* harmony export */   likeBtnSuccess: () => (/* binding */ likeBtnSuccess),
/* harmony export */   loadMoreHandle: () => (/* binding */ loadMoreHandle)
/* harmony export */ });
/* harmony import */ var _services_ClickService_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./services/ClickService.js */ "./assets/src/services/ClickService.js");
// components.js

var likeBtnSuccess = function likeBtnSuccess(data, target) {
  var _target$dataset = target.dataset,
    id = _target$dataset.id,
    type = _target$dataset.type;
  var buttons = document.querySelectorAll(".like-".concat(type, "-").concat(id));
  var status = data.status;
  var count = data.count;
  buttons.forEach(function (button) {
    button.classList.toggle('btn-outline-danger', status === 'liked');
    button.classList.toggle('btn-outline-secondary', status === 'unliked');
    var c = button.querySelector('.count-wrap');
    if (c) c.textContent = count;
    button.setAttribute('title', status === 'liked' ? 'unlike' : 'like');
  });
};
var likeBtnHandle = function likeBtnHandle() {
  var postLikeConfig = {
    apiUrl: lermData.rest_url,
    selector: '.like-button',
    route: lermData.route,
    security: lermData.nonce,
    isThrottled: true,
    cacheExpiryTime: 60000,
    enableCache: false
  };
  var postLike = new _services_ClickService_js__WEBPACK_IMPORTED_MODULE_0__["default"](postLikeConfig);
  postLike.onSuccess = likeBtnSuccess;
};
var appendPostsToDOM = function appendPostsToDOM(data) {
  var loadMoreBtn = document.querySelector(".more-posts");
  var postsList = document.querySelector(".ajax-posts");
  if (postsList) postsList.insertAdjacentHTML('beforeend', data.content);
  if (loadMoreBtn) loadMoreBtn.dataset.currentPage = data.currentPage;
};
var loadMoreHandle = function loadMoreHandle() {
  var loadMore = new _services_ClickService_js__WEBPACK_IMPORTED_MODULE_0__["default"]({
    apiUrl: lermData.rest_url,
    selector: '.more-posts',
    route: lermData.loadmore_action,
    security: lermData.nonce,
    isThrottled: true,
    cacheExpiryTime: 60000,
    enableCache: false
  });
  loadMore.onSuccess = appendPostsToDOM;
};
var handleCommentSuccess = function handleCommentSuccess(data) {
  var respond = document.getElementById('respond');
  var commentList = document.querySelector('.comment-list');
  var c = data && data.comment ? data.comment : null;
  if (!c) return;

  // Build <li>
  var li = document.createElement('li');
  li.id = "comment-".concat(c.comment_ID);
  li.className = "".concat(c.comment_type || '', " list-group-item").concat(c.comment_parent !== '0' ? ' p-0' : '');

  // <article>
  var article = document.createElement('article');
  article.id = "div-comment-".concat(c.comment_ID);
  article.className = 'comment-body';

  // footer/meta
  var footer = document.createElement('footer');
  footer.className = 'comment-meta mb-1';

  // author vcard
  var spanAuthor = document.createElement('span');
  spanAuthor.className = 'comment-author vcard';
  var img = document.createElement('img');
  img.src = c.avatar_url || '';
  // keep srcset if you want 2x support (redundant if you only have one size)
  if (c.avatar_url) img.srcset = "".concat(c.avatar_url, " 2x");
  img.alt = c.comment_author || '';
  img.className = "avatar avatar-".concat(c.avatar_size || 48, " photo");
  img.height = c.avatar_size || 48;
  img.width = c.avatar_size || 48;
  img.loading = 'lazy';
  img.decoding = 'async';
  var b = document.createElement('b');
  b.className = 'fn';
  b.textContent = c.comment_author || '';
  spanAuthor.appendChild(img);
  spanAuthor.appendChild(b);

  // metadata (time + link)
  var spanMeta = document.createElement('span');
  spanMeta.className = 'comment-metadata';
  var bullet = document.createElement('span');
  bullet.setAttribute('aria-hidden', 'true');
  bullet.textContent = '•';
  var a = document.createElement('a');
  var postLink = c.comment_post_link || '#';
  a.href = "".concat(postLink, "#comment-").concat(c.comment_ID);
  var timeEl = document.createElement('time');
  if (c.comment_date_gmt) timeEl.setAttribute('datetime', c.comment_date_gmt);
  timeEl.textContent = c.comment_date || '';
  a.appendChild(timeEl);
  spanMeta.appendChild(bullet);
  spanMeta.appendChild(a);
  footer.appendChild(spanAuthor);
  footer.appendChild(spanMeta);
  article.appendChild(footer);

  // moderation badge if awaiting moderation
  if (c.comment_approved === '0') {
    var modBadge = document.createElement('span');
    modBadge.className = 'comment-awaiting-moderation badge rounded-pill bg-info';
    modBadge.textContent = '您的评论正在等待审核。';
    article.appendChild(modBadge);
  }

  // content section (server is expected to sanitize HTML)
  var section = document.createElement('section');
  section.className = 'comment-content';
  section.style.marginLeft = '56px';
  var p = document.createElement('p');
  // NOTE: using innerHTML because server returns sanitized HTML (wp_kses_post).
  // If server returns plain text or you want extra safety, use: p.textContent = c.comment_content;
  p.innerHTML = c.comment_content || '';
  section.appendChild(p);
  article.appendChild(section);
  li.appendChild(article);

  // Insert into DOM (preserve original placement logic)
  if (commentList) {
    var previousElement = respond.previousElementSibling;
    if (previousElement) {
      var lastChild = previousElement.lastElementChild;
      if (lastChild && lastChild.classList.contains('children')) {
        lastChild.appendChild(li);
      } else {
        var childrenUl = document.createElement('ul');
        childrenUl.className = 'children';
        childrenUl.appendChild(li);
        previousElement.appendChild(childrenUl);
      }
    } else {
      // top-level — prepend into existing commentList
      commentList.insertAdjacentElement('afterbegin', li);
    }
  } else {
    // No comment list exists yet — build card + ol + append
    var newCommentCard = document.createElement('div');
    newCommentCard.className = 'card mb-3';
    var ol = document.createElement('ol');
    ol.className = 'comment-list p-0 m-0 list-group list-group-flush';
    ol.appendChild(li);
    newCommentCard.appendChild(ol);

    // append after respond wrapper
    respond.parentNode.appendChild(newCommentCard);
  }
};
var handleLoginSuccess = function handleLoginSuccess() {
  if (window.loadPage) loadPage(lermData.frontDoor);
};
var handleUpdateProfileSuccess = function handleUpdateProfileSuccess() {
  if (window.loadPage) loadPage(lermData.redirect);
};

/***/ }),

/***/ "./assets/src/services/BaseService.js":
/*!********************************************!*\
  !*** ./assets/src/services/BaseService.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ BaseService)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
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
var BaseService = /*#__PURE__*/_createClass(function BaseService(apiUrl) {
  var _this = this;
  _classCallCheck(this, BaseService);
  /**
   * fetchData supports arbitrary fetch options via fetchOptions
   * usage:
   *   fetchData({ url, method, body, headers, fetchOptions })
   */
  _defineProperty(this, "fetchData", /*#__PURE__*/function () {
    var _ref2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(_ref) {
      var url, _ref$method, method, _ref$body, body, _ref$headers, headers, _ref$fetchOptions, fetchOptions, options, response, text, data, msg, _t;
      return _regenerator().w(function (_context) {
        while (1) switch (_context.p = _context.n) {
          case 0:
            url = _ref.url, _ref$method = _ref.method, method = _ref$method === void 0 ? 'GET' : _ref$method, _ref$body = _ref.body, body = _ref$body === void 0 ? null : _ref$body, _ref$headers = _ref.headers, headers = _ref$headers === void 0 ? {} : _ref$headers, _ref$fetchOptions = _ref.fetchOptions, fetchOptions = _ref$fetchOptions === void 0 ? {} : _ref$fetchOptions;
            options = _objectSpread({
              method: method,
              headers: _objectSpread({}, headers),
              body: method !== 'GET' ? body : null
            }, fetchOptions);
            _context.p = 1;
            _context.n = 2;
            return fetch(url, options);
          case 2:
            response = _context.v;
            _context.n = 3;
            return response.text();
          case 3:
            text = _context.v;
            data = null;
            try {
              data = text ? JSON.parse(text) : null;
            } catch (err) {
              data = text;
            }
            if (response.ok) {
              _context.n = 4;
              break;
            }
            msg = data && data.message ? data.message : response.statusText;
            throw new Error("".concat(response.status, " ").concat(response.statusText, ": ").concat(msg));
          case 4:
            return _context.a(2, data);
          case 5:
            _context.p = 5;
            _t = _context.v;
            _this.handleError(_t);
            throw _t;
          case 6:
            return _context.a(2);
        }
      }, _callee, null, [[1, 5]]);
    }));
    return function (_x) {
      return _ref2.apply(this, arguments);
    };
  }());
  _defineProperty(this, "handleError", function (error) {
    console.error("An error occurred:", (error === null || error === void 0 ? void 0 : error.message) || error);
    // graceful fallback: if no UI message area, just console
    if (_this.messageId) {
      _this.displayMessage("An error occurred: ".concat((error === null || error === void 0 ? void 0 : error.message) || error), 'danger', 7000);
    }
  });
  _defineProperty(this, "rateLimit", function (func, wait) {
    var isThrottle = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
    var timeout,
      lastTime = 0;
    return function () {
      for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }
      var context = _this;
      var now = Date.now();
      var later = function later() {
        timeout = null;
        if (!isThrottle) func.apply(context, args);
      };
      var remaining = wait - (now - lastTime);
      if (isThrottle && remaining <= 0) {
        clearTimeout(timeout);
        timeout = null;
        lastTime = now;
        func.apply(context, args);
      } else if (!timeout) {
        timeout = setTimeout(later, isThrottle ? remaining : wait);
      }
    };
  });
  _defineProperty(this, "displayMessage", function (message) {
    var type = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'info';
    var duration = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 5000;
    if (!_this.messageId) {
      console.warn('No messageId set for displayMessage:', message);
      return;
    }
    var messageElement = document.getElementById(_this.messageId);
    if (messageElement) {
      messageElement.innerHTML = message;
      messageElement.classList.add("text-".concat(type));
      messageElement.classList.remove('invisible');
      clearTimeout(_this.messageTimeout);
      _this.messageTimeout = setTimeout(function () {
        messageElement.classList.add('invisible');
        messageElement.classList.remove("text-".concat(type));
      }, duration);
    }
  });
  /**
   * Toggle the loading state of a button by adding/removing a spinner inside the button
   * and disabling/enabling that specific button.
   *
   * @param {HTMLElement} button - The button element to toggle.
   * @param {boolean} isLoading - Whether to show the loading spinner.
   * @param {boolean} [disabled=false] - Whether to keep the button disabled at the end.
   */
  _defineProperty(this, "toggleButton", function (button, isLoading) {
    var disabled = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
    if (!button || !(button instanceof HTMLElement)) return;
    var SPINNER_SELECTOR = '[data-lerm-spinner]';
    if (isLoading) {
      if (!button.querySelector(SPINNER_SELECTOR)) {
        var spinner = document.createElement('span');
        spinner.setAttribute('aria-hidden', 'true');
        spinner.setAttribute('data-lerm-spinner', '1');
        spinner.className = 'spinner-border spinner-border-sm';
        button.insertBefore(spinner, button.firstChild);
      }
      button.setAttribute('disabled', 'disabled');
      button.setAttribute('aria-busy', 'true');
    } else {
      var _spinner = button.querySelector(SPINNER_SELECTOR);
      if (_spinner) _spinner.remove();
      if (!disabled) {
        button.removeAttribute('disabled');
      } else {
        button.setAttribute('disabled', 'disabled');
      }
      button.removeAttribute('aria-busy');
    }
  });
  this.apiUrl = apiUrl;
  this.messageTimeout = null;
  this.messageId = null; // 可被子类设置以使用 displayMessage
});


/***/ }),

/***/ "./assets/src/services/CacheDB.js":
/*!****************************************!*\
  !*** ./assets/src/services/CacheDB.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ CacheDB)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
// services/CacheDB.js
var CacheDB = /*#__PURE__*/function () {
  function CacheDB() {
    var dbName = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : "PageCacheDB";
    var storeName = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : "pages";
    _classCallCheck(this, CacheDB);
    this.dbName = dbName;
    this.storeName = storeName;
  }
  return _createClass(CacheDB, [{
    key: "openDB",
    value: function () {
      var _openDB = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        var _this = this;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.n) {
            case 0:
              return _context.a(2, new Promise(function (resolve, reject) {
                var request = indexedDB.open(_this.dbName, 1);
                request.onupgradeneeded = function (event) {
                  var db = event.target.result;
                  if (!db.objectStoreNames.contains(_this.storeName)) {
                    db.createObjectStore(_this.storeName, {
                      keyPath: "url"
                    });
                  }
                };
                request.onsuccess = function (event) {
                  return resolve(event.target.result);
                };
                request.onerror = function (event) {
                  return reject(event.target.error);
                };
              }));
          }
        }, _callee);
      }));
      function openDB() {
        return _openDB.apply(this, arguments);
      }
      return openDB;
    }()
  }, {
    key: "set",
    value: function () {
      var _set = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2(url, data) {
        var _this2 = this;
        var expiry,
          db,
          _args2 = arguments;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.n) {
            case 0:
              expiry = _args2.length > 2 && _args2[2] !== undefined ? _args2[2] : 5 * 60 * 1000;
              _context2.n = 1;
              return this.openDB();
            case 1:
              db = _context2.v;
              return _context2.a(2, new Promise(function (resolve, reject) {
                var transaction = db.transaction(_this2.storeName, "readwrite");
                var store = transaction.objectStore(_this2.storeName);
                var entry = {
                  url: url,
                  data: data,
                  timestamp: Date.now(),
                  expiry: expiry
                };
                var request = store.put(entry);
                request.onsuccess = function () {
                  return resolve(true);
                };
                request.onerror = function (event) {
                  return reject(event.target.error);
                };
              }));
          }
        }, _callee2, this);
      }));
      function set(_x, _x2) {
        return _set.apply(this, arguments);
      }
      return set;
    }()
  }, {
    key: "get",
    value: function () {
      var _get = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3(url) {
        var _this3 = this;
        var db;
        return _regenerator().w(function (_context3) {
          while (1) switch (_context3.n) {
            case 0:
              _context3.n = 1;
              return this.openDB();
            case 1:
              db = _context3.v;
              return _context3.a(2, new Promise(function (resolve, reject) {
                var transaction = db.transaction(_this3.storeName, "readonly");
                var store = transaction.objectStore(_this3.storeName);
                var request = store.get(url);
                request.onsuccess = function (event) {
                  var result = event.target.result;
                  if (result && Date.now() - result.timestamp > result.expiry) {
                    _this3["delete"](url);
                    resolve(null);
                  } else {
                    resolve(result ? result.data : null);
                  }
                };
                request.onerror = function (event) {
                  return reject(event.target.error);
                };
              }));
          }
        }, _callee3, this);
      }));
      function get(_x3) {
        return _get.apply(this, arguments);
      }
      return get;
    }()
  }, {
    key: "delete",
    value: function () {
      var _delete2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee4(url) {
        var _this4 = this;
        var db;
        return _regenerator().w(function (_context4) {
          while (1) switch (_context4.n) {
            case 0:
              _context4.n = 1;
              return this.openDB();
            case 1:
              db = _context4.v;
              return _context4.a(2, new Promise(function (resolve, reject) {
                var transaction = db.transaction(_this4.storeName, "readwrite");
                var store = transaction.objectStore(_this4.storeName);
                var request = store["delete"](url);
                request.onsuccess = function () {
                  return resolve(true);
                };
                request.onerror = function (event) {
                  return reject(event.target.error);
                };
              }));
          }
        }, _callee4, this);
      }));
      function _delete(_x4) {
        return _delete2.apply(this, arguments);
      }
      return _delete;
    }()
  }, {
    key: "clear",
    value: function () {
      var _clear = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee5() {
        var _this5 = this;
        var db;
        return _regenerator().w(function (_context5) {
          while (1) switch (_context5.n) {
            case 0:
              _context5.n = 1;
              return this.openDB();
            case 1:
              db = _context5.v;
              return _context5.a(2, new Promise(function (resolve, reject) {
                var transaction = db.transaction(_this5.storeName, "readwrite");
                var store = transaction.objectStore(_this5.storeName);
                var request = store.clear();
                request.onsuccess = function () {
                  return resolve(true);
                };
                request.onerror = function (event) {
                  return reject(event.target.error);
                };
              }));
          }
        }, _callee5, this);
      }));
      function clear() {
        return _clear.apply(this, arguments);
      }
      return clear;
    }()
  }]);
}();


/***/ }),

/***/ "./assets/src/services/ClickService.js":
/*!*********************************************!*\
  !*** ./assets/src/services/ClickService.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ClickService)
/* harmony export */ });
/* harmony import */ var _BaseService_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./BaseService.js */ "./assets/src/services/BaseService.js");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils.js */ "./assets/src/utils.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
// // services/ClickService.js
// import BaseService from './BaseService.js';
// import { delegate } from '../utils.js';

// export default class ClickService extends BaseService {
//   constructor({ apiUrl, selector, route, security, headers = {}, additionalData = {}, isThrottled = false, cacheExpiryTime = 60000, enableCache = true }) {
//     super(apiUrl);
//     Object.assign(this, { selector, route, security, headers, additionalData, cacheExpiryTime, enableCache });
//     this.messageId = null; // 可设置以使用 displayMessage
//     this.clickHandler = isThrottled ? this.rateLimit(this.handleClick, 1000, true) : this.handleClick;
//     delegate('click', this.selector, this.clickHandler);
//   }

//   handleClick = async (event, target) => {
//     // compatibility: if delegate passed only event, try to find target
//     if (!target) target = event && event.currentTarget ? event.currentTarget : event.target;
//     if (!target) return;

//     event.preventDefault();
//     this.beforeClick(event, target);

//     //  validate
//     const nonce = this.security ?? target.dataset.nonce;
//     if (!nonce) {
//       this.onError(new Error('Missing nonce'), target);
//       return;
//     }

//     const payload = {
//       ...target.dataset,
//       ...this.additionalData,
//     };

//     const cacheKey = `click_action_${this.route}`;
//     if (this.enableCache && this.isCacheValid(cacheKey)) {
//       this.useCache(cacheKey);
//       return;
//     }

//     this.toggleButton(target, true);

//     const url = `${this.apiUrl.replace(/\/$/, '')}/${this.route.replace(/^\//, '')}`;

//     try {
//       const response = await this.fetchData({
//         url: url,
//         method: 'POST',
//         credentials: 'same-origin',
//         headers: {
//           'Content-Type': 'application/json',
//           'X-WP-Nonce': nonce,
//           ...this.headers
//         },
//         body: JSON.stringify(payload),
//       });

//       this.onSuccess(response, target);
//       if (this.enableCache) this.updateCache(cacheKey, data);
//     } catch (err) {
//       this.onError(err, target);
//     } finally {
//       this.toggleButton(target, false);
//     }
//   }

//   isCacheValid = (cacheKey) => {
//     const cachedData = sessionStorage.getItem(cacheKey);
//     const cacheTime = sessionStorage.getItem(`${cacheKey}_time`);
//     return cachedData && (Date.now() - cacheTime) < this.cacheExpiryTime;
//   }

//   useCache = (cacheKey) => {
//     const cachedResponse = JSON.parse(sessionStorage.getItem(cacheKey));
//     this.onSuccess(cachedResponse);
//   }

//   cacheResponse = (cacheKey, response) => {
//     localStorage.setItem(cacheKey, JSON.stringify(response));
//     localStorage.setItem(`${cacheKey}_time`, Date.now());
//   }

//   updateCache = (cacheKey, response) => {
//     sessionStorage.setItem(cacheKey, JSON.stringify(response));
//     sessionStorage.setItem(`${cacheKey}_time`, Date.now());
//   }

//   beforeClick = () => { console.log('Processing click...'); }

//   onSuccess = (response, target) => {
//     this.displayMessage('Click action was successful!');
//     console.log('Response:', response);
//   }
//   onError = (error, target) => {
//     this.displayMessage('Failed to process click action.');
//     console.error('Error:', error);
//     if (target) {
//       target.setAttribute('disabled', 'disabled');
//       target.innerHTML = error.message;
//     }
//   }

// }
// services/ClickService.js


var ClickService = /*#__PURE__*/function (_BaseService) {
  /**
   * options:
   * { apiUrl, selector, route, security, headers = {}, additionalData = {}, isThrottled = false, cacheExpiryTime = 60000, enableCache = true, cacheStorage = 'session' }
   */
  function ClickService() {
    var _this;
    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, ClickService);
    _this = _callSuper(this, ClickService, [options.apiUrl]);
    _defineProperty(_this, "handleClick", /*#__PURE__*/function () {
      var _ref = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(event, target) {
        var _this$security;
        var nonce, payload, payloadFingerprint, cacheKey, buttonEl, url, response, _t;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.p = _context.n) {
            case 0:
              // compatibility: if delegate passed only event, try to find target
              if (!target) target = event && event.currentTarget ? event.currentTarget : event.target;
              if (target) {
                _context.n = 1;
                break;
              }
              return _context.a(2);
            case 1:
              event.preventDefault();
              _this.beforeClick(event, target);

              // validate nonce
              nonce = (_this$security = _this.security) !== null && _this$security !== void 0 ? _this$security : target.dataset.nonce;
              if (nonce) {
                _context.n = 2;
                break;
              }
              _this.onError(new Error('Missing nonce'), target);
              return _context.a(2);
            case 2:
              // build payload: merge explicit dataset and additionalData
              payload = _objectSpread({}, _this.additionalData);
              Object.keys(target.dataset || {}).forEach(function (k) {
                if (['nonce', 'action'].includes(k)) return;
                payload[k] = target.dataset[k];
              });

              // generate unique cacheKey using payload fingerprint
              payloadFingerprint = '';
              try {
                payloadFingerprint = btoa(unescape(encodeURIComponent(JSON.stringify(payload)))).slice(0, 24);
              } catch (e) {
                payloadFingerprint = String(Date.now());
              }
              cacheKey = "click_action_".concat(_this.route, "_").concat(payloadFingerprint);
              if (!(_this.enableCache && _this.isCacheValid(cacheKey))) {
                _context.n = 3;
                break;
              }
              _this.useCache(cacheKey);
              return _context.a(2);
            case 3:
              // find the button element for spinner control
              buttonEl = target instanceof HTMLElement ? target : target.closest && target.closest('button, a') || target;
              _this.toggleButton(buttonEl, true);
              url = "".concat(_this.apiUrl.replace(/\/$/, ''), "/").concat(_this.route.replace(/^\//, ''));
              _context.p = 4;
              _context.n = 5;
              return _this.fetchData({
                url: url,
                method: 'POST',
                body: JSON.stringify(payload),
                headers: _objectSpread({
                  'Content-Type': 'application/json',
                  'X-WP-Nonce': nonce
                }, _this.headers),
                fetchOptions: {
                  credentials: 'same-origin'
                }
              });
            case 5:
              response = _context.v;
              if (_this.enableCache) _this.updateCache(cacheKey, response);
              _this.onSuccess(response, target);
              _context.n = 7;
              break;
            case 6:
              _context.p = 6;
              _t = _context.v;
              _this.onError(_t, target);
            case 7:
              _context.p = 7;
              _this.toggleButton(buttonEl, false);
              return _context.f(7);
            case 8:
              return _context.a(2);
          }
        }, _callee, null, [[4, 6, 7, 8]]);
      }));
      return function (_x, _x2) {
        return _ref.apply(this, arguments);
      };
    }());
    _defineProperty(_this, "isCacheValid", function (cacheKey) {
      var storage = _this.cacheStorage === 'local' ? localStorage : sessionStorage;
      var cachedData = storage.getItem(cacheKey);
      var cacheTime = parseInt(storage.getItem("".concat(cacheKey, "_time")), 10);
      return cachedData && !Number.isNaN(cacheTime) && Date.now() - cacheTime < _this.cacheExpiryTime;
    });
    _defineProperty(_this, "useCache", function (cacheKey) {
      var storage = _this.cacheStorage === 'local' ? localStorage : sessionStorage;
      var cachedResponse = JSON.parse(storage.getItem(cacheKey));
      _this.onSuccess(cachedResponse);
    });
    _defineProperty(_this, "updateCache", function (cacheKey, response) {
      var storage = _this.cacheStorage === 'local' ? localStorage : sessionStorage;
      storage.setItem(cacheKey, JSON.stringify(response));
      storage.setItem("".concat(cacheKey, "_time"), String(Date.now()));
    });
    _defineProperty(_this, "beforeClick", function () {/* hook */});
    _defineProperty(_this, "onSuccess", function (response, target) {
      _this.displayMessage && _this.displayMessage('Click action was successful!', 'success');
      console.log('Response:', response);
    });
    _defineProperty(_this, "onError", function (error, target) {
      _this.displayMessage && _this.displayMessage('Failed to process click action.', 'danger');
      console.error('Error:', error);
      if (target && target instanceof HTMLElement) {
        target.setAttribute('disabled', 'disabled');
        if (error && error.message) target.textContent = error.message;
      }
    });
    var selector = options.selector,
      route = options.route,
      security = options.security,
      _options$headers = options.headers,
      headers = _options$headers === void 0 ? {} : _options$headers,
      _options$additionalDa = options.additionalData,
      additionalData = _options$additionalDa === void 0 ? {} : _options$additionalDa,
      _options$isThrottled = options.isThrottled,
      isThrottled = _options$isThrottled === void 0 ? false : _options$isThrottled,
      _options$cacheExpiryT = options.cacheExpiryTime,
      cacheExpiryTime = _options$cacheExpiryT === void 0 ? 60000 : _options$cacheExpiryT,
      _options$enableCache = options.enableCache,
      enableCache = _options$enableCache === void 0 ? true : _options$enableCache,
      _options$cacheStorage = options.cacheStorage,
      cacheStorage = _options$cacheStorage === void 0 ? 'session' : _options$cacheStorage;
    Object.assign(_this, {
      selector: selector,
      route: route,
      security: security,
      headers: headers,
      additionalData: additionalData,
      cacheExpiryTime: cacheExpiryTime,
      enableCache: enableCache,
      cacheStorage: cacheStorage
    });
    _this.messageId = options.messageId || null;

    // bind handler
    _this.clickHandler = isThrottled ? _this.rateLimit(_this.handleClick, 1000, true) : _this.handleClick;
    (0,_utils_js__WEBPACK_IMPORTED_MODULE_1__.delegate)('click', _this.selector, _this.clickHandler);
    return _this;
  }
  _inherits(ClickService, _BaseService);
  return _createClass(ClickService);
}(_BaseService_js__WEBPACK_IMPORTED_MODULE_0__["default"]);


/***/ }),

/***/ "./assets/src/services/FormService.js":
/*!********************************************!*\
  !*** ./assets/src/services/FormService.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FormService)
/* harmony export */ });
/* harmony import */ var _BaseService_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./BaseService.js */ "./assets/src/services/BaseService.js");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils.js */ "./assets/src/utils.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
// services/FormService.js



// reuse the validationRules and validateField from the original script
var validationRules = {
  email: {
    pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    message: 'Invalid email format'
  },
  username: {
    minLength: 3,
    errorMessage: {
      minLength: 'Register must be at least {minLength} characters long.'
    }
  },
  author: {
    minLength: 3,
    errorMessage: {
      minLength: 'Comment username must be at least {minLength} characters long.'
    }
  },
  regist_password: {
    minLength: 8,
    hasUppercase: /[A-Z]/,
    hasNumber: /\d/,
    hasSpecialChar: /[!@#$%^&*]/,
    message: 'Password must be at least 8 characters long, include one uppercase letter, one number, and one special character.',
    errorMessage: {
      minLength: 'Password must be at least {minLength} characters long.',
      hasUppercase: 'Password must contain at least one uppercase letter.',
      hasNumber: 'Password must contain at least one number.',
      hasSpecialChar: 'Password must contain at least one special character.'
    }
  },
  confirm_password: {
    match: 'regist_password',
    message: 'Passwords do not match'
  },
  comment: {
    minLength: 6,
    message: 'Textarea must be at least 10 characters long',
    errorMessage: {
      minLength: 'Comment textarea must be at least {minLength} characters long.'
    }
  }
};
var validateField = function validateField(field, rules) {
  var formValues = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
  var rule = rules[field.name];
  var value = field.value || '';
  if (!rule) return {
    valid: true
  };
  var pattern = rule.pattern,
    minLength = rule.minLength,
    hasUppercase = rule.hasUppercase,
    hasNumber = rule.hasNumber,
    hasSpecialChar = rule.hasSpecialChar,
    match = rule.match,
    errorMessage = rule.errorMessage;
  if (pattern && !pattern.test(value)) return {
    valid: false,
    message: (errorMessage === null || errorMessage === void 0 ? void 0 : errorMessage.pattern) || 'Invalid format'
  };
  if (minLength && value.length < minLength) return {
    valid: false,
    message: errorMessage === null || errorMessage === void 0 ? void 0 : errorMessage.minLength.replace('{minLength}', minLength)
  };
  if (hasUppercase && !hasUppercase.test(value)) return {
    valid: false,
    message: errorMessage.hasUppercase
  };
  if (hasNumber && !hasNumber.test(value)) return {
    valid: false,
    message: errorMessage.hasNumber
  };
  if (hasSpecialChar && !hasSpecialChar.test(value)) return {
    valid: false,
    message: errorMessage.hasSpecialChar
  };
  if (match && value !== formValues[match]) return {
    valid: false,
    message: rule.message || 'Values do not match'
  };
  return {
    valid: true
  };
};
var FormService = /*#__PURE__*/function (_BaseService) {
  function FormService(_ref) {
    var _this;
    var apiUrl = _ref.apiUrl,
      formId = _ref.formId,
      action = _ref.action,
      security = _ref.security,
      _ref$headers = _ref.headers,
      headers = _ref$headers === void 0 ? {} : _ref$headers,
      messageId = _ref.messageId,
      _ref$passwordToggle = _ref.passwordToggle,
      passwordToggle = _ref$passwordToggle === void 0 ? false : _ref$passwordToggle;
    _classCallCheck(this, FormService);
    _this = _callSuper(this, FormService, [apiUrl]);
    _defineProperty(_this, "init", function () {
      var form = document.getElementById(_this.formId);
      if (!form) return;
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_1__.delegate)('submit', "#".concat(_this.formId), function (event, formEl) {
        return _this.handleFormSubmit(event, formEl);
      });
      if (_this.passwordToggle) _this.initPasswordToggle();
    });
    _defineProperty(_this, "handleFormSubmit", /*#__PURE__*/function () {
      var _ref2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(event, form) {
        var submitButton, formData, response, _t;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.p = _context.n) {
            case 0:
              event.preventDefault();
              if (_this.validateForm(form)) {
                _context.n = 1;
                break;
              }
              console.warn("Form validation failed for ID \"".concat(_this.formId, "\"."));
              return _context.a(2);
            case 1:
              submitButton = form.querySelector('button[type="submit"]');
              if (!submitButton.disabled) {
                _context.n = 2;
                break;
              }
              return _context.a(2);
            case 2:
              _this.toggleButton(submitButton, true);
              formData = new FormData(form);
              _this.beforeSubmit();
              _context.p = 3;
              _context.n = 4;
              return _this.fetchData({
                url: _this.apiUrl + '/' + _this.action,
                method: 'POST',
                body: formData,
                headers: _objectSpread({
                  'X-WP-Nonce': _this.security
                }, _this.headers)
              });
            case 4:
              response = _context.v;
              _this.onSuccess(response, form);
              _context.n = 6;
              break;
            case 5:
              _context.p = 5;
              _t = _context.v;
              _this.onError(_t);
            case 6:
              _context.p = 6;
              _this.toggleButton(submitButton, false);
              return _context.f(6);
            case 7:
              return _context.a(2);
          }
        }, _callee, null, [[3, 5, 6, 7]]);
      }));
      return function (_x, _x2) {
        return _ref2.apply(this, arguments);
      };
    }());
    _defineProperty(_this, "validateForm", function (form) {
      var fields = form.querySelectorAll('input, textarea, select');
      var isFormValid = true;
      var formValues = Object.fromEntries(new FormData(form));
      var isValid = form.checkValidity();
      if (!isValid) form.reportValidity();
      fields.forEach(function (field) {
        var _validateField = validateField(field, validationRules, formValues),
          valid = _validateField.valid,
          message = _validateField.message;
        if (!valid) {
          field.classList.add('is-invalid');
          _this.displayMessage(message, 'danger');
          isFormValid = false;
        } else {
          field.classList.remove('is-invalid');
        }
      });
      return isFormValid;
    });
    _defineProperty(_this, "beforeSubmit", function () {});
    _defineProperty(_this, "onSuccess", function (response, form) {
      form.reset();
      _this.afterSubmitSuccess(response);
      _this.displayMessage('Form submitted successfully!', 'success');
    });
    _defineProperty(_this, "afterSubmitSuccess", function (_response) {});
    _defineProperty(_this, "onError", function (error) {
      console.error('Form submission failed:', error);
      if (_this.messageId) _this.displayMessage(error.message, 'danger');
    });
    Object.assign(_this, {
      formId: formId,
      action: action,
      security: security,
      headers: headers,
      messageId: messageId,
      passwordToggle: passwordToggle
    });
    _this.init();
    return _this;
  }
  _inherits(FormService, _BaseService);
  return _createClass(FormService, [{
    key: "initPasswordToggle",
    value: function initPasswordToggle() {
      var _this2 = this;
      var toggleElement = document.getElementById("".concat(this.formId, "-toggle"));
      var passwordFields = Array.from(document.querySelectorAll("#".concat(this.formId, " input[type=\"password\"]")));
      if (!toggleElement || passwordFields.length === 0) return;
      toggleElement.addEventListener('click', function () {
        return _this2.togglePasswordVisibility(passwordFields, toggleElement);
      });
    }
  }, {
    key: "togglePasswordVisibility",
    value: function togglePasswordVisibility(passwordFields, toggleElement) {
      var isVisible = toggleElement.classList.toggle('visible');
      passwordFields.forEach(function (field) {
        return field.type = isVisible ? 'text' : 'password';
      });
    }
  }, {
    key: "afterSubmit",
    value: function afterSubmit(form) {
      console.log('After submitting form:', form);
    }
  }]);
}(_BaseService_js__WEBPACK_IMPORTED_MODULE_0__["default"]);


/***/ }),

/***/ "./assets/src/services/LoadPageService.js":
/*!************************************************!*\
  !*** ./assets/src/services/LoadPageService.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ LoadPageService)
/* harmony export */ });
/* harmony import */ var _BaseService_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./BaseService.js */ "./assets/src/services/BaseService.js");
/* harmony import */ var _CacheDB_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CacheDB.js */ "./assets/src/services/CacheDB.js");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils.js */ "./assets/src/utils.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
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



var LoadPageService = /*#__PURE__*/function (_BaseService) {
  function LoadPageService(_ref) {
    var _this;
    var apiUrl = _ref.apiUrl,
      _ref$action = _ref.action,
      action = _ref$action === void 0 ? 'page' : _ref$action,
      containerId = _ref.containerId,
      _ref$allowUrls = _ref.allowUrls,
      allowUrls = _ref$allowUrls === void 0 ? [] : _ref$allowUrls,
      _ref$ignoreUrls = _ref.ignoreUrls,
      ignoreUrls = _ref$ignoreUrls === void 0 ? [] : _ref$ignoreUrls,
      _ref$cacheExpiry = _ref.cacheExpiry,
      cacheExpiry = _ref$cacheExpiry === void 0 ? 5 * 60 * 1000 : _ref$cacheExpiry;
    _classCallCheck(this, LoadPageService);
    _this = _callSuper(this, LoadPageService, [apiUrl]);
    _this.containerId = containerId;
    _this.ignoreUrls = ignoreUrls;
    _this.allowUrls = allowUrls;
    _this.action = action;
    _this.cacheExpiry = cacheExpiry;
    _this.state = {
      ajaxLoading: false,
      ajaxStarted: false
    };
    _this.cacheDB = new _CacheDB_js__WEBPACK_IMPORTED_MODULE_1__["default"]();
    _this.onPopState = _this.onPopState.bind(_this);
    _this.shouldInterceptLink = _this.shouldInterceptLink.bind(_this);
    _this.loadPage = _this.loadPage.bind(_this);

    // Abort control
    _this.currentAbort = null;
    return _this;
  }
  _inherits(LoadPageService, _BaseService);
  return _createClass(LoadPageService, [{
    key: "init",
    value: function () {
      var _init = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        return _regenerator().w(function (_context) {
          while (1) switch (_context.n) {
            case 0:
              this.onLinkClick(this.shouldInterceptLink, this.loadPage);
              this.onSearchForm('form[method="GET"]', this.loadPage);
              window.addEventListener('popstate', this.onPopState);
              _context.n = 1;
              return this.clearExpiredCache();
            case 1:
              console.log("LoadPageService initialized.");
            case 2:
              return _context.a(2);
          }
        }, _callee, this);
      }));
      function init() {
        return _init.apply(this, arguments);
      }
      return init;
    }()
  }, {
    key: "onLinkClick",
    value: function onLinkClick(interceptCallback, callback) {
      var _this2 = this;
      // delegate clicks on anchors
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_2__.delegate)("click", "a", function (event, link) {
        if (interceptCallback && interceptCallback(link)) {
          event.preventDefault();
          _this2.updateNavState(link);
          callback(link.href);
        }
      });
    }
  }, {
    key: "onSearchForm",
    value: function onSearchForm(selector, callback) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_2__.delegate)("submit", selector, function (event, form) {
        event.preventDefault();
        var paramsObj = Object.fromEntries(new FormData(form));
        // ensure params go to third argument of loadPage
        callback(form.action || window.location.pathname, false, paramsObj);
      });
    }
  }, {
    key: "onPopState",
    value: function onPopState() {
      if (!this.state.ajaxStarted) {
        var href = window.location.href;
        if (this.shouldInterceptLink(href)) {
          console.log('Popstate triggered');
          this.loadPage(href, true).then(function () {
            // update nav active based on current path
            var links = document.querySelectorAll('.nav-link');
            links.forEach(function (a) {
              try {
                var hrefPath = new URL(a.href, window.location.origin).pathname;
                a.classList.toggle('active', hrefPath === location.pathname);
              } catch (e) {}
            });
          })["catch"](function () {});
        }
      }
    }
  }, {
    key: "clearExpiredCache",
    value: function () {
      var _clearExpiredCache = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
        var db, transaction, store, request;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.n) {
            case 0:
              _context2.n = 1;
              return this.cacheDB.openDB();
            case 1:
              db = _context2.v;
              transaction = db.transaction(this.cacheDB.storeName, "readwrite");
              store = transaction.objectStore(this.cacheDB.storeName);
              request = store.openCursor();
              request.onsuccess = function (event) {
                var cursor = event.target.result;
                if (cursor) {
                  var _cursor$value = cursor.value,
                    timestamp = _cursor$value.timestamp,
                    expiry = _cursor$value.expiry;
                  if (Date.now() - timestamp > expiry) {
                    store["delete"](cursor.key);
                  }
                  cursor["continue"]();
                }
              };
            case 2:
              return _context2.a(2);
          }
        }, _callee2, this);
      }));
      function clearExpiredCache() {
        return _clearExpiredCache.apply(this, arguments);
      }
      return clearExpiredCache;
    }()
  }, {
    key: "loadPage",
    value: function () {
      var _loadPage = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3(url) {
        var _this3 = this;
        var isPopState,
          params,
          container,
          paramsObj,
          paramStr,
          fullUrl,
          cachedData,
          headers,
          response,
          data,
          _args3 = arguments,
          _t;
        return _regenerator().w(function (_context3) {
          while (1) switch (_context3.p = _context3.n) {
            case 0:
              isPopState = _args3.length > 1 && _args3[1] !== undefined ? _args3[1] : false;
              params = _args3.length > 2 && _args3[2] !== undefined ? _args3[2] : null;
              if (this.state.ajaxLoading || this.state.ajaxStarted) {
                // cancel previous request if any
                if (this.currentAbort) this.currentAbort.abort();
              }
              this.state.ajaxStarted = true;
              this.state.ajaxLoading = true;
              container = document.getElementById(this.containerId);
              if (container) {
                _context3.n = 1;
                break;
              }
              console.error("Container not found.");
              this._resetState();
              return _context3.a(2);
            case 1:
              // normalize params to object and build fullUrl
              paramsObj = params && _typeof(params) === 'object' ? params : null;
              paramStr = paramsObj ? new URLSearchParams(paramsObj).toString() : '';
              fullUrl = paramStr ? "".concat(url.split('?')[0], "?").concat(paramStr) : url;
              if (!isPopState && history.pushState) {
                history.pushState({}, "", new URL(fullUrl, window.location.origin).href);
              }

              // check cache first
              _context3.n = 2;
              return this.cacheDB.get(fullUrl);
            case 2:
              cachedData = _context3.v;
              if (!cachedData) {
                _context3.n = 3;
                break;
              }
              this.fadeOut(container, function () {
                try {
                  _this3.updatePageContent(container, cachedData);
                } catch (e) {
                  console.error('Failed update from cache', e);
                }
                _this3.fadeIn(container);
                window.scrollTo({
                  top: 0,
                  behavior: "smooth"
                });
                _this3._resetState();
              });
              return _context3.a(2);
            case 3:
              // prepare fetch with abort controller and optional conditional headers
              this.currentAbort = new AbortController();
              headers = {
                'X-WP-Nonce': window.lermData && lermData.nonce || ''
              }; // If cache had an etag stored inside its data, we would include If-None-Match. (optional)
              // const prevEtag = cachedData?.etag; // cachedData may contain meta if you stored it
              // if (prevEtag) headers['If-None-Match'] = prevEtag;
              _context3.p = 4;
              this.fadeOut(container);
              _context3.n = 5;
              return this.fetchData({
                // url: `${this.apiUrl.replace(/\/$/, '')}?action=${this.action}&url=${encodeURIComponent(fullUrl)}`,
                url: "".concat(this.apiUrl.replace(/\/$/, ''), "?url=").concat(encodeURIComponent(fullUrl)),
                method: "GET",
                headers: headers,
                fetchOptions: {
                  signal: this.currentAbort.signal,
                  credentials: 'same-origin'
                }
              });
            case 5:
              response = _context3.v;
              // If REST returns object with success wrapper (e.g. admin-ajax style), adapt:
              data = response;
              if (!(response && response.data && response.success !== undefined)) {
                _context3.n = 7;
                break;
              }
              if (response.success) {
                _context3.n = 6;
                break;
              }
              throw new Error(response.message || 'Request failed');
            case 6:
              data = response.data;
            case 7:
              if (!data) {
                _context3.n = 9;
                break;
              }
              this.updatePageContent(container, data);
              // store full response into cacheDB (including metadata if present)
              _context3.n = 8;
              return this.cacheDB.set(fullUrl, data, this.cacheExpiry);
            case 8:
              _context3.n = 10;
              break;
            case 9:
              throw new Error('Empty response data');
            case 10:
              this.fadeIn(container);
              window.scrollTo({
                top: 0,
                behavior: "smooth"
              });
              _context3.n = 12;
              break;
            case 11:
              _context3.p = 11;
              _t = _context3.v;
              if (_t.name === 'AbortError') {
                console.log('LoadPage request aborted');
              } else {
                console.error("Error during page load:", _t);
                this.displayError(container, "Failed to load page.");
              }
            case 12:
              _context3.p = 12;
              this._resetState();
              this.currentAbort = null;
              return _context3.f(12);
            case 13:
              return _context3.a(2);
          }
        }, _callee3, this, [[4, 11, 12, 13]]);
      }));
      function loadPage(_x) {
        return _loadPage.apply(this, arguments);
      }
      return loadPage;
    }()
  }, {
    key: "_resetState",
    value: function _resetState() {
      this.state.ajaxStarted = false;
      this.state.ajaxLoading = false;
    }
  }, {
    key: "updatePageContent",
    value: function updatePageContent(container, data) {
      document.title = data.title || document.title;
      this.updateMeta("description", data.meta_description);
      this.updateMeta("keywords", data.meta_keywords);
      container.innerHTML = data.content || "";
      container.setAttribute("aria-live", "polite");
      document.dispatchEvent(new Event("contentLoaded"));
    }
  }, {
    key: "updateMeta",
    value: function updateMeta(name, content) {
      if (!content) return;
      var meta = document.querySelector("meta[name=\"".concat(name, "\"]"));
      if (!meta) {
        meta = document.createElement("meta");
        meta.setAttribute("name", name);
        document.head.appendChild(meta);
      }
      meta.setAttribute("content", content);
    }
  }, {
    key: "updateNavState",
    value: function updateNavState(el) {
      if (!el) return;
      var navLinks = document.querySelectorAll('.nav-link');
      navLinks.forEach(function (link) {
        return link.classList.remove('active');
      });
      el.classList.add('active');
    }
  }, {
    key: "shouldInterceptLink",
    value: function shouldInterceptLink(link) {
      try {
        if (!link) return false;
        var url = typeof link === 'string' ? new URL(link, window.location.origin) : new URL(link.href, window.location.origin);
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
  }, {
    key: "shouldProcessUrl",
    value: function shouldProcessUrl(url) {
      return this.isAllowedUrl(url) && !this.isIgnoredUrl(url);
    }
  }, {
    key: "isSameOrigin",
    value: function isSameOrigin(url) {
      return url.origin === window.location.origin;
    }
  }, {
    key: "isAllowedSubdomain",
    value: function isAllowedSubdomain(url) {
      var allowedSubdomains = ["sub1.example.com", "sub2.example.com"];
      return allowedSubdomains.some(function (subdomain) {
        return url.hostname.endsWith(subdomain);
      });
    }
  }, {
    key: "isAllowedUrl",
    value: function isAllowedUrl(url) {
      if (this.allowUrls.length === 0) return true;
      return this.allowUrls.some(function (pattern) {
        return url.includes(pattern);
      });
    }
  }, {
    key: "isIgnoredUrl",
    value: function isIgnoredUrl(url) {
      return this.ignoreUrls.some(function (ignore) {
        return url.includes(ignore);
      });
    }
  }, {
    key: "displayError",
    value: function displayError(container, message) {
      this.fadeIn(container);
      container.innerHTML = "<p class=\"text-danger\">".concat(message, "</p>");
    }
  }, {
    key: "fadeIn",
    value: function fadeIn(element) {
      element.style.opacity = 0;
      var duration = 500;
      var startTime = performance.now();
      var _fade = function fade(currentTime) {
        var elapsed = currentTime - startTime;
        var progress = Math.min(elapsed / duration, 1);
        element.style.opacity = progress;
        if (progress < 1) requestAnimationFrame(_fade);
      };
      requestAnimationFrame(_fade);
    }
  }, {
    key: "fadeOut",
    value: function fadeOut(element, callback) {
      var duration = 500;
      var startTime = performance.now();
      var _fade2 = function fade(currentTime) {
        var elapsed = currentTime - startTime;
        var progress = Math.min(elapsed / duration, 1);
        element.style.opacity = 1 - progress;
        if (progress < 1) requestAnimationFrame(_fade2);else if (callback) callback();
      };
      requestAnimationFrame(_fade2);
    }
  }]);
}(_BaseService_js__WEBPACK_IMPORTED_MODULE_0__["default"]);


/***/ }),

/***/ "./assets/src/utils.js":
/*!*****************************!*\
  !*** ./assets/src/utils.js ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DOMContentLoaded: () => (/* binding */ DOMContentLoaded),
/* harmony export */   calendarAddClass: () => (/* binding */ calendarAddClass),
/* harmony export */   codeHighlight: () => (/* binding */ codeHighlight),
/* harmony export */   delegate: () => (/* binding */ delegate),
/* harmony export */   imageResize: () => (/* binding */ imageResize),
/* harmony export */   initializeWOW: () => (/* binding */ initializeWOW),
/* harmony export */   lazyLoadImages: () => (/* binding */ lazyLoadImages),
/* harmony export */   navigationToggle: () => (/* binding */ navigationToggle),
/* harmony export */   offCanvasMenu: () => (/* binding */ offCanvasMenu),
/* harmony export */   safeRequestIdleCallback: () => (/* binding */ safeRequestIdleCallback),
/* harmony export */   scrollTop: () => (/* binding */ scrollTop)
/* harmony export */ });
function delegate(eventName, selector, handler) {
  var root = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : document;
  root.addEventListener(eventName, function (event) {
    var target = event.target;
    while (target && target !== root) {
      if (target.matches && target.matches(selector)) {
        handler(event, target);
        return;
      }
      target = target.parentElement;
    }
  }, {
    passive: false
  });
}
var DOMContentLoaded = function DOMContentLoaded(cb) {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", cb, {
      once: true
    });
  } else {
    cb();
  }
};
var safeRequestIdleCallback = function safeRequestIdleCallback(cb) {
  if ('requestIdleCallback' in window) {
    requestIdleCallback(cb);
  } else {
    setTimeout(cb, 200);
  }
};
var scrollTop = function scrollTop() {
  delegate("click", "#scroll-up", function (event) {
    event.preventDefault();
    document.documentElement.scrollIntoView({
      behavior: "smooth"
    });
  });
};
var lazyLoadImages = function () {
  var observer;
  return function () {
    if (!observer) {
      observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            var img = entry.target;
            img.src = img.dataset.src;
            observer.unobserve(img);
          }
        });
      }, {
        rootMargin: "0px 0px",
        threshold: 0
      });
    }
    var images = document.querySelectorAll('.lazy');
    images.forEach(function (img) {
      return observer.observe(img);
    });
  };
}();
var codeHighlight = function codeHighlight() {
  if (typeof hljs !== "undefined") {
    document.querySelectorAll("pre code").forEach(function (block) {
      hljs.highlightBlock(block);
    });
  }
};
var calendarAddClass = function calendarAddClass() {
  var calendar = document.querySelector("#wp-calendar");
  if (!calendar) return;
  var calendarLinks = document.querySelectorAll("tbody td a");
  if (calendarLinks.length === 0) return;
  calendarLinks.forEach(function (link) {
    return link.classList.add("has-posts");
  });
};
var imageResize = function imageResize(parentNode) {
  var items = document.querySelectorAll(parentNode);
  if (items.length === 0) return;
  var item = items[0];
  var img = item.querySelector("img");
  if (!img) return;
  var offsetWidth = img.offsetWidth;
  var offsetHeight = img.offsetHeight;
  items.forEach(function (e) {
    var im = e.querySelector("img");
    if (im) {
      im.style.width = offsetWidth + "px";
      im.style.height = offsetHeight + "px";
    }
  });
};
var offCanvasMenu = function offCanvasMenu() {
  var windowWidth = document.body.clientWidth;
  var offCanvasMenu = document.querySelector("#offcanvasMenu");
  if (!offCanvasMenu) return;
  if (windowWidth < 992) {
    offCanvasMenu.style.top = parseFloat(getComputedStyle(document.documentElement).marginTop) + "px";
  }
};
var navigationToggle = function navigationToggle() {
  delegate("click", ".navbar-toggler", function (event, toggler) {
    toggler.classList.toggle("active");
  });
};
var initializeWOW = function initializeWOW() {
  if (typeof WOW === "undefined") return;
  var wow = new WOW({
    boxClass: "loading-animate",
    animateClass: "animated",
    offset: 0,
    mobile: true,
    live: true
  });
  wow.init();
};

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*****************************!*\
  !*** ./assets/src/index.js ***!
  \*****************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils.js */ "./assets/src/utils.js");
/* harmony import */ var _components_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components.js */ "./assets/src/components.js");
/* harmony import */ var _services_FormService_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./services/FormService.js */ "./assets/src/services/FormService.js");
/* harmony import */ var _services_LoadPageService_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./services/LoadPageService.js */ "./assets/src/services/LoadPageService.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
// main.js




var loadPageService = new _services_LoadPageService_js__WEBPACK_IMPORTED_MODULE_3__["default"]({
  apiUrl: lermData.url,
  containerId: "page-ajax",
  headers: {
    'X-WP-Nonce': lermData.nonce
  },
  action: 'load_page_content',
  ignoreUrls: ["/regist", "/reset/", "/wp-admin/", "/wp-login.php"],
  cacheExpiry: 1 * 60 * 1000,
  errorText: "Failed to load the content. Please try again later."
});
var loadRegistService = new _services_LoadPageService_js__WEBPACK_IMPORTED_MODULE_3__["default"]({
  apiUrl: lermData.url,
  containerId: "myTabContent",
  action: 'load_form',
  allowUrls: ["/regist", "/login/", "/reset/"],
  errorText: "Failed to load the content. Please try again later."
});
var formAjaxHandle = function formAjaxHandle() {
  var formConfigs = [
  // { formId: 'login', action: lermData.login_action, security: lermData.login_nonce },
  // { formId: 'reset', action: lermData.reset_action, security: lermData.reset_nonce },
  // { formId: 'regist', action: lermData.regist_action, security: lermData.regist_nonce, passwordToggle: true },
  {
    formId: 'commentform',
    action: lermData.comment_action,
    security: lermData.nonce
  }];
  if (lermData.loggedin) {
    formConfigs.push({
      formId: 'update-profile',
      action: lermData.profile_action,
      security: lermData.profile_nonce
    });
  }
  formConfigs.forEach(function (config) {
    var form = document.getElementById(config.formId);
    if (!form) return;
    var FormHandle = new _services_FormService_js__WEBPACK_IMPORTED_MODULE_2__["default"](_objectSpread(_objectSpread({}, config), {}, {
      apiUrl: lermData.rest_url,
      messageId: "".concat(config.formId, "-msg")
    }));
    if (config.formId === 'commentform') FormHandle.afterSubmitSuccess = _components_js__WEBPACK_IMPORTED_MODULE_1__.handleCommentSuccess;
    if (config.formId === 'login') FormHandle.afterSubmitSuccess = _components_js__WEBPACK_IMPORTED_MODULE_1__.handleLoginSuccess;
    if (config.formId === 'update-profile') FormHandle.afterSubmitSuccess = _components_js__WEBPACK_IMPORTED_MODULE_1__.handleUpdateProfileSuccess;
  });
};
(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.DOMContentLoaded)(/*#__PURE__*/_asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
  return _regenerator().w(function (_context) {
    while (1) switch (_context.n) {
      case 0:
        // 1) 先创建实例（确保传入正确的 apiUrl / action / containerId）
        // const svc = new LoadPageService({
        //   apiUrl: (window.lermData?.rest_url ?? '/wp-json/lerm/v1') + '/page', // 或 '/wp-admin/admin-ajax.php'
        //   action: '', // admin-ajax 情况下填 'my_load_page'
        //   containerId: "page-ajax",
        //   allowUrls: [],
        //   ignoreUrls: ['/wp-login.php', '/wp-admin/'],
        //   cacheExpiry: 5 * 60 * 1000,
        // });

        // // 2) 暴露实例供调试 & 手动清缓存
        // window.lermLoadPage = svc;

        // // 3) 等待 init 完成 —— 确保 delegate、popstate 等已注册
        // try {
        //   await svc.init();
        // } catch (err) {
        //   console.error('LoadPageService init failed:', err);
        //   // 可选回退：location.reload();
        // }
        (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.safeRequestIdleCallback)(function () {
          (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.initializeWOW)();
          (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.lazyLoadImages)();
          (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.codeHighlight)();
          (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.calendarAddClass)();
          (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.offCanvasMenu)();
          (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.navigationToggle)();
        });
        (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.scrollTop)();
        formAjaxHandle();
        (0,_components_js__WEBPACK_IMPORTED_MODULE_1__.likeBtnHandle)();
        (0,_components_js__WEBPACK_IMPORTED_MODULE_1__.loadMoreHandle)();
      case 1:
        return _context.a(2);
    }
  }, _callee);
})));

// Re-run some init on dynamic content loads
document.addEventListener('contentLoaded', function () {
  (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.scrollTop)();
  formAjaxHandle();
});
})();

/******/ 	return __webpack_exports__;
/******/ })()
;
});
//# sourceMappingURL=bundle.js.map