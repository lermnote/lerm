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

/***/ "./assets/src/js/cache/CacheDB.js":
/*!****************************************!*\
  !*** ./assets/src/js/cache/CacheDB.js ***!
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
var CacheDB = /*#__PURE__*/function () {
  function CacheDB() {
    var dbName = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'PageCacheDB';
    var storeName = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'pages';
    var version = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 1;
    _classCallCheck(this, CacheDB);
    this.dbName = dbName;
    this.storeName = storeName;
    this.version = version;
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
                try {
                  var request = indexedDB.open(_this.dbName, _this.version);
                  request.onupgradeneeded = function (event) {
                    var db = event.target.result;
                    if (!db.objectStoreNames.contains(_this.storeName)) {
                      db.createObjectStore(_this.storeName, {
                        keyPath: 'key'
                      });
                    }
                  };
                  request.onsuccess = function (event) {
                    return resolve(event.target.result);
                  };
                  request.onerror = function (event) {
                    return reject(event.target.error);
                  };
                } catch (err) {
                  reject(err);
                }
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
      var _set = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2(key, data) {
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
                var tx = db.transaction(_this2.storeName, 'readwrite');
                var store = tx.objectStore(_this2.storeName);
                var entry = {
                  key: key,
                  data: data,
                  timestamp: Date.now(),
                  expiry: expiry
                };
                var req = store.put(entry);
                req.onsuccess = function () {
                  return resolve(true);
                };
                req.onerror = function (e) {
                  var _e$target$error, _e$target;
                  return reject((_e$target$error = (_e$target = e.target) === null || _e$target === void 0 ? void 0 : _e$target.error) !== null && _e$target$error !== void 0 ? _e$target$error : e);
                };
              }));
          }
        }, _callee2, this);
      }));
      function set(_x, _x2) {
        return _set.apply(this, arguments);
      }
      return set;
    }() // 返回完整条目或 null（并且自动删除过期项）
  }, {
    key: "getEntry",
    value: function () {
      var _getEntry = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee4(key) {
        var _this3 = this;
        var db;
        return _regenerator().w(function (_context4) {
          while (1) switch (_context4.n) {
            case 0:
              _context4.n = 1;
              return this.openDB();
            case 1:
              db = _context4.v;
              return _context4.a(2, new Promise(function (resolve, reject) {
                var tx = db.transaction(_this3.storeName, 'readonly');
                var store = tx.objectStore(_this3.storeName);
                var req = store.get(key);
                req.onsuccess = /*#__PURE__*/function () {
                  var _ref = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3(event) {
                    var result, txd;
                    return _regenerator().w(function (_context3) {
                      while (1) switch (_context3.n) {
                        case 0:
                          result = event.target.result;
                          if (result) {
                            _context3.n = 1;
                            break;
                          }
                          return _context3.a(2, resolve(null));
                        case 1:
                          if (!(Date.now() - result.timestamp > result.expiry)) {
                            _context3.n = 2;
                            break;
                          }
                          // expired -> delete then return null
                          try {
                            txd = db.transaction(_this3.storeName, 'readwrite');
                            txd.objectStore(_this3.storeName)["delete"](key);
                          } catch (_) {}
                          return _context3.a(2, resolve(null));
                        case 2:
                          resolve(result);
                        case 3:
                          return _context3.a(2);
                      }
                    }, _callee3);
                  }));
                  return function (_x4) {
                    return _ref.apply(this, arguments);
                  };
                }();
                req.onerror = function (e) {
                  var _e$target$error2, _e$target2;
                  return reject((_e$target$error2 = (_e$target2 = e.target) === null || _e$target2 === void 0 ? void 0 : _e$target2.error) !== null && _e$target$error2 !== void 0 ? _e$target$error2 : e);
                };
              }));
          }
        }, _callee4, this);
      }));
      function getEntry(_x3) {
        return _getEntry.apply(this, arguments);
      }
      return getEntry;
    }()
  }, {
    key: "get",
    value: function () {
      var _get = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee5(key) {
        var entry;
        return _regenerator().w(function (_context5) {
          while (1) switch (_context5.n) {
            case 0:
              _context5.n = 1;
              return this.getEntry(key);
            case 1:
              entry = _context5.v;
              return _context5.a(2, entry ? entry.data : null);
          }
        }, _callee5, this);
      }));
      function get(_x5) {
        return _get.apply(this, arguments);
      }
      return get;
    }()
  }, {
    key: "delete",
    value: function () {
      var _delete2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee6(key) {
        var _this4 = this;
        var db;
        return _regenerator().w(function (_context6) {
          while (1) switch (_context6.n) {
            case 0:
              _context6.n = 1;
              return this.openDB();
            case 1:
              db = _context6.v;
              return _context6.a(2, new Promise(function (resolve, reject) {
                var tx = db.transaction(_this4.storeName, 'readwrite');
                var store = tx.objectStore(_this4.storeName);
                var req = store["delete"](key);
                req.onsuccess = function () {
                  return resolve(true);
                };
                req.onerror = function (e) {
                  var _e$target$error3, _e$target3;
                  return reject((_e$target$error3 = (_e$target3 = e.target) === null || _e$target3 === void 0 ? void 0 : _e$target3.error) !== null && _e$target$error3 !== void 0 ? _e$target$error3 : e);
                };
              }));
          }
        }, _callee6, this);
      }));
      function _delete(_x6) {
        return _delete2.apply(this, arguments);
      }
      return _delete;
    }()
  }, {
    key: "clear",
    value: function () {
      var _clear = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee7() {
        var _this5 = this;
        var db;
        return _regenerator().w(function (_context7) {
          while (1) switch (_context7.n) {
            case 0:
              _context7.n = 1;
              return this.openDB();
            case 1:
              db = _context7.v;
              return _context7.a(2, new Promise(function (resolve, reject) {
                var tx = db.transaction(_this5.storeName, 'readwrite');
                var store = tx.objectStore(_this5.storeName);
                var req = store.clear();
                req.onsuccess = function () {
                  return resolve(true);
                };
                req.onerror = function (e) {
                  var _e$target$error4, _e$target4;
                  return reject((_e$target$error4 = (_e$target4 = e.target) === null || _e$target4 === void 0 ? void 0 : _e$target4.error) !== null && _e$target$error4 !== void 0 ? _e$target$error4 : e);
                };
              }));
          }
        }, _callee7, this);
      }));
      function clear() {
        return _clear.apply(this, arguments);
      }
      return clear;
    }()
  }]);
}();


/***/ }),

/***/ "./assets/src/js/cache/CacheManager.js":
/*!*********************************************!*\
  !*** ./assets/src/js/cache/CacheManager.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ CacheManager)
/* harmony export */ });
/* harmony import */ var _CacheDB_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CacheDB.js */ "./assets/src/js/cache/CacheDB.js");
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
// cache/CacheManager.js

var CacheManager = /*#__PURE__*/function () {
  function CacheManager() {
    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, CacheManager);
    var _options$cacheDB = options.cacheDB,
      cacheDB = _options$cacheDB === void 0 ? null : _options$cacheDB,
      _options$storage = options.storage,
      storage = _options$storage === void 0 ? 'session' : _options$storage,
      _options$defaultExpir = options.defaultExpiry,
      defaultExpiry = _options$defaultExpir === void 0 ? 60 * 1000 : _options$defaultExpir,
      _options$namespace = options.namespace,
      namespace = _options$namespace === void 0 ? 'appcache' : _options$namespace,
      _options$onError = options.onError,
      onError = _options$onError === void 0 ? function (e) {
        return console.warn('CacheManager error:', e);
      } : _options$onError;
    this.cacheDB = cacheDB instanceof _CacheDB_js__WEBPACK_IMPORTED_MODULE_0__["default"] ? cacheDB : null;
    this.storage = storage === 'local' ? 'local' : 'session';
    this.defaultExpiry = defaultExpiry;
    this.namespace = String(namespace || 'appcache');
    this.onError = onError;
  }
  return _createClass(CacheManager, [{
    key: "_namespaced",
    value: function _namespaced(key) {
      return "".concat(this.namespace, "::").concat(key);
    }
  }, {
    key: "_storage",
    value: function _storage() {
      return this.storage === 'local' ? localStorage : sessionStorage;
    }
  }, {
    key: "set",
    value: function () {
      var _set = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(key, data) {
        var expiry,
          ns,
          store,
          entry,
          _args = arguments,
          _t,
          _t2;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.p = _context.n) {
            case 0:
              expiry = _args.length > 2 && _args[2] !== undefined ? _args[2] : this.defaultExpiry;
              ns = this._namespaced(key);
              if (!this.cacheDB) {
                _context.n = 4;
                break;
              }
              _context.p = 1;
              _context.n = 2;
              return this.cacheDB.set(ns, data, expiry);
            case 2:
              return _context.a(2, true);
            case 3:
              _context.p = 3;
              _t = _context.v;
              this.onError(_t);
              // fall through to fallback
            case 4:
              _context.p = 4;
              store = this._storage();
              entry = {
                data: data,
                timestamp: Date.now(),
                expiry: expiry
              };
              store.setItem(ns, JSON.stringify(entry));
              return _context.a(2, true);
            case 5:
              _context.p = 5;
              _t2 = _context.v;
              this.onError(_t2);
              return _context.a(2, false);
          }
        }, _callee, this, [[4, 5], [1, 3]]);
      }));
      function set(_x, _x2) {
        return _set.apply(this, arguments);
      }
      return set;
    }()
  }, {
    key: "get",
    value: function () {
      var _get = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2(key) {
        var entry;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.n) {
            case 0:
              _context2.n = 1;
              return this.getEntry(key);
            case 1:
              entry = _context2.v;
              return _context2.a(2, entry ? entry.data : null);
          }
        }, _callee2, this);
      }));
      function get(_x3) {
        return _get.apply(this, arguments);
      }
      return get;
    }()
  }, {
    key: "getEntry",
    value: function () {
      var _getEntry = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3(key) {
        var ns, entry, store, raw, parsed, _t3, _t4;
        return _regenerator().w(function (_context3) {
          while (1) switch (_context3.p = _context3.n) {
            case 0:
              ns = this._namespaced(key);
              if (!this.cacheDB) {
                _context3.n = 5;
                break;
              }
              _context3.p = 1;
              _context3.n = 2;
              return this.cacheDB.getEntry(ns);
            case 2:
              entry = _context3.v;
              if (!entry) {
                _context3.n = 3;
                break;
              }
              return _context3.a(2, {
                data: entry.data,
                timestamp: entry.timestamp,
                expiry: entry.expiry
              });
            case 3:
              return _context3.a(2, null);
            case 4:
              _context3.p = 4;
              _t3 = _context3.v;
              this.onError(_t3);
              // fall through
            case 5:
              _context3.p = 5;
              store = this._storage();
              raw = store.getItem(ns);
              if (raw) {
                _context3.n = 6;
                break;
              }
              return _context3.a(2, null);
            case 6:
              parsed = JSON.parse(raw);
              if (!(!parsed || typeof parsed.timestamp !== 'number' || typeof parsed.expiry !== 'number')) {
                _context3.n = 7;
                break;
              }
              try {
                store.removeItem(ns);
              } catch (_) {}
              return _context3.a(2, null);
            case 7:
              if (!(Date.now() - parsed.timestamp > parsed.expiry)) {
                _context3.n = 8;
                break;
              }
              try {
                store.removeItem(ns);
              } catch (_) {}
              return _context3.a(2, null);
            case 8:
              return _context3.a(2, {
                data: parsed.data,
                timestamp: parsed.timestamp,
                expiry: parsed.expiry
              });
            case 9:
              _context3.p = 9;
              _t4 = _context3.v;
              this.onError(_t4);
              return _context3.a(2, null);
          }
        }, _callee3, this, [[5, 9], [1, 4]]);
      }));
      function getEntry(_x4) {
        return _getEntry.apply(this, arguments);
      }
      return getEntry;
    }()
  }, {
    key: "isValid",
    value: function () {
      var _isValid = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee4(key) {
        var entry, _t5;
        return _regenerator().w(function (_context4) {
          while (1) switch (_context4.p = _context4.n) {
            case 0:
              _context4.p = 0;
              _context4.n = 1;
              return this.getEntry(key);
            case 1:
              entry = _context4.v;
              return _context4.a(2, Boolean(entry));
            case 2:
              _context4.p = 2;
              _t5 = _context4.v;
              this.onError(_t5);
              return _context4.a(2, false);
          }
        }, _callee4, this, [[0, 2]]);
      }));
      function isValid(_x5) {
        return _isValid.apply(this, arguments);
      }
      return isValid;
    }()
  }, {
    key: "delete",
    value: function () {
      var _delete2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee5(key) {
        var ns, store, _t6, _t7;
        return _regenerator().w(function (_context5) {
          while (1) switch (_context5.p = _context5.n) {
            case 0:
              ns = this._namespaced(key);
              if (!this.cacheDB) {
                _context5.n = 4;
                break;
              }
              _context5.p = 1;
              _context5.n = 2;
              return this.cacheDB["delete"](ns);
            case 2:
              return _context5.a(2, true);
            case 3:
              _context5.p = 3;
              _t6 = _context5.v;
              this.onError(_t6);
            case 4:
              _context5.p = 4;
              store = this._storage();
              store.removeItem(ns);
              return _context5.a(2, true);
            case 5:
              _context5.p = 5;
              _t7 = _context5.v;
              this.onError(_t7);
              return _context5.a(2, false);
          }
        }, _callee5, this, [[4, 5], [1, 3]]);
      }));
      function _delete(_x6) {
        return _delete2.apply(this, arguments);
      }
      return _delete;
    }()
  }, {
    key: "clear",
    value: function () {
      var _clear = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee6() {
        var store, toRemove, i, k, _t8, _t9;
        return _regenerator().w(function (_context6) {
          while (1) switch (_context6.p = _context6.n) {
            case 0:
              if (!this.cacheDB) {
                _context6.n = 4;
                break;
              }
              _context6.p = 1;
              _context6.n = 2;
              return this.cacheDB.clear();
            case 2:
              return _context6.a(2, true);
            case 3:
              _context6.p = 3;
              _t8 = _context6.v;
              this.onError(_t8);
              // fall through to namespaced cleanup
            case 4:
              _context6.p = 4;
              store = this._storage();
              toRemove = [];
              for (i = 0; i < store.length; i++) {
                k = store.key(i);
                if (k && k.startsWith("".concat(this.namespace, "::"))) toRemove.push(k);
              }
              toRemove.forEach(function (k) {
                return store.removeItem(k);
              });
              return _context6.a(2, true);
            case 5:
              _context6.p = 5;
              _t9 = _context6.v;
              this.onError(_t9);
              return _context6.a(2, false);
          }
        }, _callee6, this, [[4, 5], [1, 3]]);
      }));
      function clear() {
        return _clear.apply(this, arguments);
      }
      return clear;
    }()
    /**
     * staleWhileRevalidate(key, fetcher, { expiry, background })
     * - fetcher: async function that returns fresh data
     * - if cached: return cached immediately and optionally refresh in background
     * - if not cached: await fetcher(), cache and return
     */
  }, {
    key: "staleWhileRevalidate",
    value: (function () {
      var _staleWhileRevalidate = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee8(key, fetcher) {
        var _this = this;
        var options,
          _options$expiry,
          expiry,
          _options$background,
          background,
          entry,
          cached,
          fresh,
          _fresh2,
          _args8 = arguments,
          _t1,
          _t10,
          _t11;
        return _regenerator().w(function (_context8) {
          while (1) switch (_context8.p = _context8.n) {
            case 0:
              options = _args8.length > 2 && _args8[2] !== undefined ? _args8[2] : {};
              _options$expiry = options.expiry, expiry = _options$expiry === void 0 ? this.defaultExpiry : _options$expiry, _options$background = options.background, background = _options$background === void 0 ? true : _options$background;
              _context8.p = 1;
              _context8.n = 2;
              return this.getEntry(key);
            case 2:
              entry = _context8.v;
              if (!entry) {
                _context8.n = 3;
                break;
              }
              cached = entry.data;
              if (background) {
                _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee7() {
                  var _fresh, _t0;
                  return _regenerator().w(function (_context7) {
                    while (1) switch (_context7.p = _context7.n) {
                      case 0:
                        _context7.p = 0;
                        _context7.n = 1;
                        return fetcher();
                      case 1:
                        _fresh = _context7.v;
                        _context7.n = 2;
                        return _this.set(key, _fresh, expiry);
                      case 2:
                        _context7.n = 4;
                        break;
                      case 3:
                        _context7.p = 3;
                        _t0 = _context7.v;
                        _this.onError(_t0);
                      case 4:
                        return _context7.a(2);
                    }
                  }, _callee7, null, [[0, 3]]);
                }))();
              }
              return _context8.a(2, cached);
            case 3:
              _context8.n = 4;
              return fetcher();
            case 4:
              fresh = _context8.v;
              _context8.n = 5;
              return this.set(key, fresh, expiry);
            case 5:
              return _context8.a(2, fresh);
            case 6:
              _context8.p = 6;
              _t1 = _context8.v;
              this.onError(_t1);
              // last resort: attempt direct fetch
              _context8.p = 7;
              _context8.n = 8;
              return fetcher();
            case 8:
              _fresh2 = _context8.v;
              _context8.p = 9;
              _context8.n = 10;
              return this.set(key, _fresh2, expiry);
            case 10:
              _context8.n = 12;
              break;
            case 11:
              _context8.p = 11;
              _t10 = _context8.v;
            case 12:
              return _context8.a(2, _fresh2);
            case 13:
              _context8.p = 13;
              _t11 = _context8.v;
              this.onError(_t11);
              return _context8.a(2, null);
          }
        }, _callee8, this, [[9, 11], [7, 13], [1, 6]]);
      }));
      function staleWhileRevalidate(_x7, _x8) {
        return _staleWhileRevalidate.apply(this, arguments);
      }
      return staleWhileRevalidate;
    }())
  }]);
}();


/***/ }),

/***/ "./assets/src/js/components.js":
/*!*************************************!*\
  !*** ./assets/src/js/components.js ***!
  \*************************************/
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
/* harmony import */ var _services_ClickService_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./services/ClickService.js */ "./assets/src/js/services/ClickService.js");
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

/***/ "./assets/src/js/services/BaseService.js":
/*!***********************************************!*\
  !*** ./assets/src/js/services/BaseService.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ BaseService)
/* harmony export */ });
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
var BaseService = /*#__PURE__*/_createClass(function BaseService() {
  var _this = this;
  var apiUrl = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  _classCallCheck(this, BaseService);
  /**
   * fetchData supports arbitrary fetch options via fetchOptions
   * usage:
   *   fetchData({ url, method, body, headers, fetchOptions })
   */
  _defineProperty(this, "fetchData", /*#__PURE__*/function () {
    var _ref2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(_ref) {
      var url, _ref$method, method, _ref$body, body, _ref$headers, headers, _ref$fetchOptions, fetchOptions, resolvedUrl, normalizedHeaders, _iterator, _step, _step$value, k, v, hasContentType, isPlainObject, bodyToSend, controller, externalSignal, signal, timeoutId, options, response, contentType, text, data, msg, err, _t;
      return _regenerator().w(function (_context) {
        while (1) switch (_context.p = _context.n) {
          case 0:
            url = _ref.url, _ref$method = _ref.method, method = _ref$method === void 0 ? 'GET' : _ref$method, _ref$body = _ref.body, body = _ref$body === void 0 ? null : _ref$body, _ref$headers = _ref.headers, headers = _ref$headers === void 0 ? {} : _ref$headers, _ref$fetchOptions = _ref.fetchOptions, fetchOptions = _ref$fetchOptions === void 0 ? {} : _ref$fetchOptions;
            resolvedUrl = _this.apiUrl ? new URL(url, _this.apiUrl).toString() : url;
            normalizedHeaders = {};
            if (headers instanceof Headers) {
              _iterator = _createForOfIteratorHelper(headers.entries());
              try {
                for (_iterator.s(); !(_step = _iterator.n()).done;) {
                  _step$value = _slicedToArray(_step.value, 2), k = _step$value[0], v = _step$value[1];
                  normalizedHeaders[k] = v;
                }
              } catch (err) {
                _iterator.e(err);
              } finally {
                _iterator.f();
              }
            } else if (headers && _typeof(headers) === 'object') {
              Object.assign(normalizedHeaders, headers);
            }

            // case-insensitive check for existing Content-Type
            hasContentType = Object.keys(normalizedHeaders).some(function (k) {
              return k.toLowerCase() === 'content-type';
            }); // more robust plain-object test: exclude FormData, Blob, File, Array
            isPlainObject = function isPlainObject(v) {
              return v && _typeof(v) === 'object' && !Array.isArray(v) && !(v instanceof FormData) && !(v instanceof Blob);
            };
            bodyToSend = body;
            if (body != null && isPlainObject(body)) {
              // only set Content-Type when body is a plain object and caller didn't provide any Content-Type (case-insensitive)
              if (!hasContentType) {
                normalizedHeaders['Content-Type'] = 'application/json; charset=utf-8';
              }
              bodyToSend = JSON.stringify(body);
            }
            controller = new AbortController();
            externalSignal = fetchOptions.signal;
            signal = externalSignal || controller.signal;
            timeoutId = null;
            if (!externalSignal && fetchOptions.timeoutMs && typeof fetchOptions.timeoutMs === 'number') {
              timeoutId = setTimeout(function () {
                return controller.abort();
              }, fetchOptions.timeoutMs);
            }
            options = _objectSpread({
              method: method,
              headers: normalizedHeaders,
              body: method.toUpperCase() === 'GET' ? null : bodyToSend,
              signal: signal
            }, fetchOptions);
            delete options.timeoutMs;
            _context.p = 1;
            _context.n = 2;
            return fetch(resolvedUrl, options);
          case 2:
            response = _context.v;
            if (timeoutId) clearTimeout(timeoutId);
            contentType = response.headers.get('content-type') || '';
            _context.n = 3;
            return response.text();
          case 3:
            text = _context.v;
            data = null;
            if (contentType.includes('application/json') || contentType.includes('+json')) {
              try {
                data = text ? JSON.parse(text) : null;
              } catch (_unused) {
                data = text;
              }
            } else {
              try {
                data = text ? JSON.parse(text) : null;
              } catch (_unused2) {
                data = text;
              }
            }

            // try {
            //   data = text ? JSON.parse(text) : null;
            // } catch (err) {
            //   data = text;
            // }
            if (response.ok) {
              _context.n = 4;
              break;
            }
            msg = data && data.message ? data.message : response.statusText;
            err = new Error("".concat(response.status, " ").concat(response.statusText, ": ").concat(msg));
            err.status = response.status;
            err.response = response;
            err.data = data;
            throw err;
          case 4:
            return _context.a(2, data);
          case 5:
            _context.p = 5;
            _t = _context.v;
            if (_t.name === 'AbortError') {
              _t.message = 'Request aborted (timeout or cancelled)';
            }
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
      var message = error !== null && error !== void 0 && error.message ? String(error.message) : 'An unknown error occurred';
      _this.displayMessage("Error: ".concat(message), 'danger', 7000);
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
   * Toggle button loading state with Bootstrap 5 spinner
   * @param {HTMLElement} button - The button element
   * @param {boolean} isLoading - Whether to show loading spinner
   * @param {boolean} [disabled=false] - Whether to keep the button disabled when loading stops
   */
  _defineProperty(this, "toggleButton", function (button, isLoading) {
    var disabled = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
    if (!button || !(button instanceof HTMLElement)) return;
    var SPINNER_SELECTOR = '[data-lerm-spinner]';
    if (isLoading) {
      // Insert spinner if not already present
      if (!button.querySelector(SPINNER_SELECTOR)) {
        var spinner = document.createElement('span');
        spinner.setAttribute('data-lerm-spinner', '1');
        spinner.className = 'spinner-border spinner-border-sm me-2';
        spinner.setAttribute('role', 'status');
        spinner.setAttribute('aria-hidden', 'true');
        var sr = document.createElement('span');
        sr.className = 'visually-hidden';
        sr.textContent = 'Loading...';
        spinner.appendChild(sr);
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

/***/ "./assets/src/js/services/ClickService.js":
/*!************************************************!*\
  !*** ./assets/src/js/services/ClickService.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ClickService)
/* harmony export */ });
/* harmony import */ var _BaseService_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./BaseService.js */ "./assets/src/js/services/BaseService.js");
/* harmony import */ var _cache_CacheManager_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../cache/CacheManager.js */ "./assets/src/js/cache/CacheManager.js");
/* harmony import */ var _cache_CacheDB_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../cache/CacheDB.js */ "./assets/src/js/cache/CacheDB.js");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils.js */ "./assets/src/js/utils.js");
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
    // 稳定 stringify（key 排序），用于 fingerprint
    _defineProperty(_this, "stableStringify", function (value) {
      var type = Object.prototype.toString.call(value);
      if (type === '[object Object]') {
        var keys = Object.keys(value).sort();
        return "{".concat(keys.map(function (k) {
          return JSON.stringify(k) + ':' + _this.stableStringify(value[k]);
        }).join(','), "}");
      }
      if (type === '[object Array]') {
        return "[".concat(value.map(function (v) {
          return _this.stableStringify(v);
        }).join(','), "]");
      }
      return JSON.stringify(value);
    });
    _defineProperty(_this, "fingerprint", function (obj) {
      try {
        var str = _this.stableStringify(obj);
        var hash = 5381;
        for (var i = 0; i < str.length; i++) {
          hash = hash * 33 ^ str.charCodeAt(i);
        }
        return (hash >>> 0).toString(36).slice(0, 10);
      } catch (e) {
        return String(Date.now());
      }
    });
    _defineProperty(_this, "handleClick", /*#__PURE__*/function () {
      var _ref = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2(event, target) {
        var _this$security;
        var nonce, payload, payloadFingerprint, sanitizedRoute, cacheKey, _url, response, buttonEl, url, _response, _t, _t2, _t3;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.p = _context2.n) {
            case 0:
              if (!target) target = event && event.currentTarget ? event.currentTarget : event.target;
              if (target) {
                _context2.n = 1;
                break;
              }
              return _context2.a(2);
            case 1:
              if (event && typeof event.preventDefault === 'function') event.preventDefault();
              _this.beforeClick(event, target);

              // validate nonce
              nonce = (_this$security = _this.security) !== null && _this$security !== void 0 ? _this$security : target.dataset.nonce;
              if (nonce) {
                _context2.n = 2;
                break;
              }
              _this.onError(new Error('Missing nonce'), target);
              return _context2.a(2);
            case 2:
              // build payload: merge explicit dataset and additionalData
              payload = _objectSpread({}, _this.additionalData);
              Object.keys(target.dataset || {}).forEach(function (k) {
                if (['nonce', 'action'].includes(k)) return;
                payload[k] = target.dataset[k];
              });
              payloadFingerprint = _this.fingerprint(payload);
              sanitizedRoute = String(_this.route || '').replace(/^\/|\/$/g, '') || 'root';
              cacheKey = "click_action_".concat(sanitizedRoute, "_").concat(payloadFingerprint);
              if (!_this.enableCache) {
                _context2.n = 7;
                break;
              }
              _context2.p = 3;
              _url = "".concat(_this.apiUrl.replace(/\/$/, ''), "/").concat(String(_this.route || '').replace(/^\//, ''));
              _context2.n = 4;
              return _this.cache.staleWhileRevalidate(cacheKey, /*#__PURE__*/_asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
                var res;
                return _regenerator().w(function (_context) {
                  while (1) switch (_context.n) {
                    case 0:
                      _context.n = 1;
                      return _this.fetchData({
                        url: _url,
                        method: 'POST',
                        body: payload,
                        headers: _objectSpread({
                          'Content-Type': 'application/json',
                          'X-WP-Nonce': nonce
                        }, _this.headers),
                        fetchOptions: {
                          credentials: 'same-origin'
                        }
                      });
                    case 1:
                      res = _context.v;
                      return _context.a(2, res);
                  }
                }, _callee);
              })), {
                expiry: _this.cacheExpiryTime,
                background: true
              } // background refresh when cache hit
              );
            case 4:
              response = _context2.v;
              if (!response) {
                _context2.n = 5;
                break;
              }
              // cached or fresh response returned
              _this.onSuccess(response, target);
              return _context2.a(2);
            case 5:
              _context2.n = 7;
              break;
            case 6:
              _context2.p = 6;
              _t = _context2.v;
              // cache layer failed -> fallback to live fetch
              console.warn('Cache SWR failed, continuing to live request:', _t);
            case 7:
              // if (this.enableCache && this.isCacheValid(cacheKey)) {
              //   this.useCache(cacheKey);
              //   return;
              // }
              buttonEl = null;
              try {
                if (target instanceof HTMLElement) {
                  buttonEl = target.closest && target.closest('button, a, [role="button"], input[type="button"], input[type="submit"]') || (target.matches && target.matches('button, a, [role="button"], input[type="button"], input[type="submit"]') ? target : null);
                }
              } catch (e) {
                buttonEl = null;
              }
              _this.toggleButton(buttonEl || target, true);
              url = "".concat(_this.apiUrl.replace(/\/$/, ''), "/").concat(String(_this.route || '').replace(/^\//, ''));
              console.log(payload);
              _context2.p = 8;
              _context2.n = 9;
              return _this.fetchData({
                url: url,
                method: 'POST',
                body: payload,
                headers: _objectSpread({
                  'Content-Type': 'application/json',
                  'X-WP-Nonce': nonce
                }, _this.headers),
                fetchOptions: {
                  credentials: 'same-origin'
                }
              });
            case 9:
              _response = _context2.v;
              if (!_this.enableCache) {
                _context2.n = 13;
                break;
              }
              _context2.p = 10;
              _context2.n = 11;
              return _this.cache.set(cacheKey, _response, _this.cacheExpiryTime);
            case 11:
              _context2.n = 13;
              break;
            case 12:
              _context2.p = 12;
              _t2 = _context2.v;
              console.warn('cache write failed:', _t2);
            case 13:
              _this.onSuccess(_response, target);
              _context2.n = 15;
              break;
            case 14:
              _context2.p = 14;
              _t3 = _context2.v;
              _this.onError(_t3, target);
            case 15:
              _context2.p = 15;
              _this.toggleButton(buttonEl || target, false);
              return _context2.f(15);
            case 16:
              return _context2.a(2);
          }
        }, _callee2, null, [[10, 12], [8, 14, 15, 16], [3, 6]]);
      }));
      return function (_x, _x2) {
        return _ref.apply(this, arguments);
      };
    }());
    _defineProperty(_this, "beforeClick", function () {/* hook */});
    _defineProperty(_this, "onSuccess", function (response, target) {
      if (typeof _this.displayMessage === 'function') {
        _this.displayMessage('Click action was successful!', 'success');
      }
      console.log('ClickService onSuccess:', response, target);
    });
    _defineProperty(_this, "onError", function (error, target) {
      if (typeof _this.displayMessage === 'function') {
        _this.displayMessage('Failed to process click action.', 'danger');
      }
      console.error('ClickService onError:', error);
      try {
        if (target && target instanceof HTMLElement) {
          target.setAttribute('disabled', 'disabled');
          if (error && error.message) target.textContent = error.message;
        }
      } catch (e) {}
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
      cacheStorage = _options$cacheStorage === void 0 ? 'session' : _options$cacheStorage,
      _options$cacheDBInsta = options.cacheDBInstance,
      cacheDBInstance = _options$cacheDBInsta === void 0 ? null : _options$cacheDBInsta,
      _options$messageId = options.messageId,
      messageId = _options$messageId === void 0 ? null : _options$messageId;
    Object.assign(_this, {
      selector: selector,
      route: route,
      security: security,
      headers: headers,
      additionalData: additionalData,
      cacheExpiryTime: cacheExpiryTime,
      enableCache: enableCache,
      cacheStorage: cacheStorage === 'local' ? 'local' : 'session'
    });
    _this.messageId = messageId;

    // 构造 CacheManager（优先使用传入的 cacheDBInstance，否则自动 new 一个 CacheDB 也可以）
    _this.cache = new _cache_CacheManager_js__WEBPACK_IMPORTED_MODULE_1__["default"]({
      cacheDB: cacheDBInstance instanceof _cache_CacheDB_js__WEBPACK_IMPORTED_MODULE_2__["default"] ? cacheDBInstance : null,
      storage: _this.cacheStorage,
      defaultExpiry: _this.cacheExpiryTime,
      namespace: 'clicksvc',
      onError: function onError(e) {
        return console.warn('ClickService cache error:', e);
      }
    });
    var rawHandler = _this.handleClick.bind(_this);
    _this.clickHandler = isThrottled ? _this.rateLimit(rawHandler, 1000, true) : rawHandler;
    (0,_utils_js__WEBPACK_IMPORTED_MODULE_3__.delegate)('click', _this.selector, _this.clickHandler);
    return _this;
  }
  _inherits(ClickService, _BaseService);
  return _createClass(ClickService);
}(_BaseService_js__WEBPACK_IMPORTED_MODULE_0__["default"]);


/***/ }),

/***/ "./assets/src/js/services/FormService.js":
/*!***********************************************!*\
  !*** ./assets/src/js/services/FormService.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FormService)
/* harmony export */ });
/* harmony import */ var _BaseService_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./BaseService.js */ "./assets/src/js/services/BaseService.js");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils.js */ "./assets/src/js/utils.js");
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
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



/**
 * 默认校验规则（可在外部或实例化后覆盖）
 * errorMessage 可部分提供，代码会回退到合理默认文本
 */
var defaultValidationRules = {
  email: {
    pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    message: 'Invalid email format',
    errorMessage: {
      pattern: 'Invalid email format'
    }
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
    message: 'Textarea must be at least 6 characters long',
    errorMessage: {
      minLength: 'Comment textarea must be at least {minLength} characters long.'
    }
  }
};
var defaultValidateField = function defaultValidateField(field) {
  var _field$value;
  var rulesMap = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
  var formValues = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
  var rule = rulesMap[field.name];
  var value = ((_field$value = field.value) !== null && _field$value !== void 0 ? _field$value : '').trim();
  if (!rule) return {
    valid: true
  };
  var pattern = rule.pattern,
    minLength = rule.minLength,
    hasUppercase = rule.hasUppercase,
    hasNumber = rule.hasNumber,
    hasSpecialChar = rule.hasSpecialChar,
    match = rule.match,
    _rule$errorMessage = rule.errorMessage,
    errorMessage = _rule$errorMessage === void 0 ? {} : _rule$errorMessage,
    message = rule.message;
  if (pattern && value && !pattern.test(value)) {
    var _ref, _errorMessage$pattern;
    return {
      valid: false,
      message: (_ref = (_errorMessage$pattern = errorMessage.pattern) !== null && _errorMessage$pattern !== void 0 ? _errorMessage$pattern : message) !== null && _ref !== void 0 ? _ref : 'Invalid format'
    };
  }
  if (minLength && value.length < minLength) {
    var _ref2, _errorMessage$minLeng;
    var tpl = (_ref2 = (_errorMessage$minLeng = errorMessage.minLength) !== null && _errorMessage$minLeng !== void 0 ? _errorMessage$minLeng : message) !== null && _ref2 !== void 0 ? _ref2 : 'Too short';
    return {
      valid: false,
      message: tpl.replace('{minLength}', String(minLength))
    };
  }
  if (hasUppercase && value && !hasUppercase.test(value)) {
    var _ref3, _errorMessage$hasUppe;
    return {
      valid: false,
      message: (_ref3 = (_errorMessage$hasUppe = errorMessage.hasUppercase) !== null && _errorMessage$hasUppe !== void 0 ? _errorMessage$hasUppe : message) !== null && _ref3 !== void 0 ? _ref3 : 'Missing uppercase letter'
    };
  }
  if (hasNumber && value && !hasNumber.test(value)) {
    var _ref4, _errorMessage$hasNumb;
    return {
      valid: false,
      message: (_ref4 = (_errorMessage$hasNumb = errorMessage.hasNumber) !== null && _errorMessage$hasNumb !== void 0 ? _errorMessage$hasNumb : message) !== null && _ref4 !== void 0 ? _ref4 : 'Missing number'
    };
  }
  if (hasSpecialChar && value && !hasSpecialChar.test(value)) {
    var _ref5, _errorMessage$hasSpec;
    return {
      valid: false,
      message: (_ref5 = (_errorMessage$hasSpec = errorMessage.hasSpecialChar) !== null && _errorMessage$hasSpec !== void 0 ? _errorMessage$hasSpec : message) !== null && _ref5 !== void 0 ? _ref5 : 'Missing special character'
    };
  }
  if (match) {
    var _formValues$match;
    var otherValue = (_formValues$match = formValues[match]) !== null && _formValues$match !== void 0 ? _formValues$match : '';
    if (value !== otherValue) {
      return {
        valid: false,
        message: message !== null && message !== void 0 ? message : 'Values do not match'
      };
    }
  }
  return {
    valid: true
  };
};
var FormService = /*#__PURE__*/function (_BaseService) {
  /**
   * options:
   * { apiUrl, formId, action, security, headers = {}, messageId, passwordToggle = false, validationRules = {} }
   */
  function FormService() {
    var _this;
    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, FormService);
    _this = _callSuper(this, FormService, [options.apiUrl]);
    _defineProperty(_this, "init", function () {
      if (!_this.formId) return;
      var form = document.getElementById(_this.formId);
      if (!form) return;

      // 使用 delegate 绑定 submit（delegate helper 需兼容 (event, el) 这样的调用）
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_1__.delegate)('submit', "#".concat(_this.formId), function (event, formEl) {
        return _this.handleFormSubmit(event, formEl);
      });
      if (_this.passwordToggle) _this.initPasswordToggle();
    });
    /**
     * submit handler
     * event may be a delegated event where form argument is provided by delegate helper
     */
    _defineProperty(_this, "handleFormSubmit", /*#__PURE__*/function () {
      var _ref6 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(event, form) {
        var f, submitButton, body, custom, headers, isFormData, url, response, _t;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.p = _context.n) {
            case 0:
              event.preventDefault();
              if (!(!form || !(form instanceof HTMLFormElement))) {
                _context.n = 2;
                break;
              }
              // try to find form by id as fallback
              f = document.getElementById(_this.formId);
              if (f) {
                _context.n = 1;
                break;
              }
              return _context.a(2);
            case 1:
              form = f;
            case 2:
              if (_this.validateForm(form)) {
                _context.n = 3;
                break;
              }
              return _context.a(2);
            case 3:
              submitButton = form.querySelector('button[type="submit"], input[type="submit"]'); // prevent double-submit
              if (!(submitButton && (submitButton.disabled || submitButton.getAttribute('data-submitting') === '1'))) {
                _context.n = 4;
                break;
              }
              return _context.a(2);
            case 4:
              if (submitButton) {
                submitButton.setAttribute('data-submitting', '1');
                _this.toggleButton(submitButton, true);
              }
              _this.beforeSubmit(form);

              // prepare body: by default send FormData
              // if caller prefers JSON, they can override by passing a plain object to `this.prepareRequestBody`
              body = new FormData(form); // allow subclass/instance to customize body serialization (return FormData or plain object)
              if (typeof _this.prepareRequestBody === 'function') {
                try {
                  custom = _this.prepareRequestBody(form, body);
                  if (custom instanceof FormData || _typeof(custom) === 'object') body = custom;
                } catch (e) {
                  console.warn('prepareRequestBody failed, falling back to FormData:', e);
                }
              }

              // build headers (if body is FormData we must NOT set Content-Type)
              headers = _objectSpread({}, _this.headers);
              isFormData = body instanceof FormData;
              if (!isFormData && !headers['Content-Type']) {
                headers['Content-Type'] = 'application/json; charset=utf-8';
              }
              if (_this.security) headers['X-WP-Nonce'] = _this.security;
              url = "".concat(_this.apiUrl.replace(/\/$/, ''), "/").concat(String(_this.action || '').replace(/^\//, ''));
              _context.p = 5;
              _context.n = 6;
              return _this.fetchData({
                url: url,
                method: 'POST',
                body: isFormData ? body : body,
                // BaseService will serialize plain objects; FormData is passed through
                headers: headers,
                fetchOptions: {
                  credentials: 'same-origin',
                  timeoutMs: _this.submitTimeoutMs
                }
              });
            case 6:
              response = _context.v;
              // standardize success handling: some APIs return { success: true, data }, others return data directly
              _this.onSuccess(response, form);
              _this.afterSubmitSuccess(response, form);
              _context.n = 8;
              break;
            case 7:
              _context.p = 7;
              _t = _context.v;
              // server may return structured errors: err.data, err.status
              _this.onError(_t, form);
            case 8:
              _context.p = 8;
              if (submitButton) {
                submitButton.removeAttribute('data-submitting');
                _this.toggleButton(submitButton, false);
              }
              _this.afterSubmit(form);
              return _context.f(8);
            case 9:
              return _context.a(2);
          }
        }, _callee, null, [[5, 7, 8, 9]]);
      }));
      return function (_x, _x2) {
        return _ref6.apply(this, arguments);
      };
    }());
    /**
     * 验证表单：结合浏览器原生检验与自定义规则
     * - 报错只展示一次全局消息并将焦点移到首个错误元素
     */
    _defineProperty(_this, "validateForm", function (form) {
      if (!form) return false;
      var fields = Array.from(form.querySelectorAll('input[name], textarea[name], select[name]'));
      var formValues = Object.fromEntries(new FormData(form));
      var firstInvalidEl = null;
      var globalMessage = null;

      // browser native constraint validation
      var nativeValid = form.checkValidity();
      if (!nativeValid) {
        // let browser show its messages; but we'll also continue to collect our custom messages
        try {
          form.reportValidity();
        } catch (e) {}
      }

      // custom rules
      for (var _i = 0, _fields = fields; _i < _fields.length; _i++) {
        var field = _fields[_i];
        field.classList.remove('is-invalid');
        var _defaultValidateField = defaultValidateField(field, _this.validationRules, formValues),
          valid = _defaultValidateField.valid,
          message = _defaultValidateField.message;
        if (!valid) {
          field.classList.add('is-invalid');
          if (!firstInvalidEl) firstInvalidEl = field;
          if (!globalMessage) globalMessage = message || 'Invalid input';
        }
      }
      if (firstInvalidEl) {
        try {
          firstInvalidEl.focus({
            preventScroll: true
          });
        } catch (e) {}
      }
      if (globalMessage) {
        // show single message via displayMessage (BaseService)
        if (typeof _this.displayMessage === 'function') _this.displayMessage(globalMessage, 'danger', 7000);
        return false;
      }

      // if native reported invalid but our custom rules didn't, still fail
      if (!nativeValid) return false;
      return true;
    });
    // hooks: 可在实例或子类覆盖
    _defineProperty(_this, "beforeSubmit", function (_form) {});
    _defineProperty(_this, "afterSubmit", function (_form) {});
    _defineProperty(_this, "afterSubmitSuccess", function (_response, _form) {});
    _defineProperty(_this, "prepareRequestBody", function (_form, defaultFormData) {
      return defaultFormData;
    });
    // override to return plain object for JSON
    _defineProperty(_this, "onSuccess", function (response, form) {
      // 默认行为：重置表单，显示成功消息
      try {
        if (form && form instanceof HTMLFormElement) form.reset();
      } catch (e) {}
      if (typeof _this.displayMessage === 'function') _this.displayMessage('Form submitted successfully!', 'success', 5000);
      console.info('FormService onSuccess:', response);
    });
    _defineProperty(_this, "onError", function (error, form) {
      // 统一错误处理：优先 server message -> err.data?.message -> err.message
      var msg = 'Submission failed';
      if (error && _typeof(error) === 'object') {
        msg = error.data && error.data.message || error.message || msg;
        // if API returns validation errors array, try to display first message
        if (error.data && Array.isArray(error.data.errors) && error.data.errors.length) {
          msg = String(error.data.errors[0].message || error.data.errors[0]);
        }
      } else if (typeof error === 'string') {
        msg = error;
      }
      console.error('FormService onError:', error);
      if (typeof _this.displayMessage === 'function') _this.displayMessage(msg, 'danger', 8000);

      // Optionally mark related fields invalid when server returns field-specific errors
      if (error && error.data && error.data.fields && form) {
        // error.data.fields expected shape: { fieldName: 'message' }
        Object.keys(error.data.fields).forEach(function (fname) {
          var el = form.querySelector("[name=\"".concat(fname, "\"]"));
          if (el) {
            el.classList.add('is-invalid');
            // optionally attach inline message via aria-describedby or custom UI (not implemented here)
          }
        });
      }
    });
    var formId = options.formId,
      action = options.action,
      security = options.security,
      _options$headers = options.headers,
      _headers = _options$headers === void 0 ? {} : _options$headers,
      _options$messageId = options.messageId,
      messageId = _options$messageId === void 0 ? null : _options$messageId,
      _options$passwordTogg = options.passwordToggle,
      passwordToggle = _options$passwordTogg === void 0 ? false : _options$passwordTogg,
      _options$validationRu = options.validationRules,
      validationRules = _options$validationRu === void 0 ? {} : _options$validationRu,
      _options$submitTimeou = options.submitTimeoutMs,
      submitTimeoutMs = _options$submitTimeou === void 0 ? 15000 : _options$submitTimeou;
    Object.assign(_this, {
      formId: formId,
      action: action,
      security: security,
      headers: _headers,
      messageId: messageId,
      passwordToggle: passwordToggle,
      submitTimeoutMs: submitTimeoutMs
    });

    // 合并默认规则与用户规则（用户规则覆盖默认）
    _this.validationRules = _objectSpread(_objectSpread({}, defaultValidationRules), validationRules || {});
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
        try {
          field.type = isVisible ? 'text' : 'password';
        } catch (e) {
          // some browser / input combos might be immutable; fallback: replace input (rare)
          var replacement = field.cloneNode(true);
          replacement.type = isVisible ? 'text' : 'password';
          field.replaceWith(replacement);
        }
      });
      toggleElement.setAttribute('aria-pressed', String(isVisible));
    }
  }]);
}(_BaseService_js__WEBPACK_IMPORTED_MODULE_0__["default"]);


/***/ }),

/***/ "./assets/src/js/utils.js":
/*!********************************!*\
  !*** ./assets/src/js/utils.js ***!
  \********************************/
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
/*!********************************!*\
  !*** ./assets/src/js/index.js ***!
  \********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils.js */ "./assets/src/js/utils.js");
/* harmony import */ var _components_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components.js */ "./assets/src/js/components.js");
/* harmony import */ var _services_FormService_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./services/FormService.js */ "./assets/src/js/services/FormService.js");
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



// import LoadPageService from './services/LoadPageService.js';

// const loadPageService = new LoadPageService({
//   apiUrl: lermData.url,
//   containerId: "page-ajax",
//   headers: { 'X-WP-Nonce': lermData.nonce },
//   action: 'load_page_content',
//   ignoreUrls: ["/regist", "/reset/", "/wp-admin/", "/wp-login.php"],
//   cacheExpiry: 1 * 60 * 1000,
//   errorText: "Failed to load the content. Please try again later."
// });

// const loadRegistService = new LoadPageService({
//   apiUrl: lermData.url,
//   containerId: "myTabContent",
//   action: 'load_form',
//   allowUrls: ["/regist", "/login/", "/reset/"],
//   errorText: "Failed to load the content. Please try again later."
// });

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