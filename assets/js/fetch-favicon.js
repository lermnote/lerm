(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory(require("x-ray"), require("url"));
	else if(typeof define === 'function' && define.amd)
		define(["x-ray", "url"], factory);
	else if(typeof exports === 'object')
		exports["fetchFavicon"] = factory(require("x-ray"), require("url"));
	else
		root["fetchFavicon"] = factory(root["x-ray"], root["url"]);
})(this, function(__WEBPACK_EXTERNAL_MODULE_1__, __WEBPACK_EXTERNAL_MODULE_2__) {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;
/******/
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			exports: {},
/******/ 			id: moduleId,
/******/ 			loaded: false
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ function(module, exports, __webpack_require__) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	  value: true
	});
	exports.fetchFavicon = fetchFavicon;
	exports.fetchFavicons = fetchFavicons;
	var x = __webpack_require__(1)();
	var Url = __webpack_require__(2);
	var config = __webpack_require__(3);
	var markActiveFavicon = __webpack_require__(4).default;
	
	function getFavicons(url) {
	  return x(url, config.selectors.join(), [{
	    href: '@href',
	    content: '@content',
	    property: '@property',
	    rel: '@rel',
	    name: '@name',
	    sizes: '@sizes'
	  }]);
	}
	
	exports.default = fetchFavicon;
	
	function fetchFavicon(url, size) {
	  return fetchFavicons(url, size).then(function (favicons) {
	    var active = favicons.find(function (favicon) {
	      return favicon.active;
	    });
	    return active.href;
	  });
	}
	
	function fetchFavicons(url, size) {
	  return new Promise(function (resolve, reject) {
	    getFavicons(url)(function (err, favicons) {
	      if (err) {
	        return reject(err);
	      }
	      favicons.push({
	        href: Url.resolve(url, 'favicon.ico'),
	        name: 'favicon.ico'
	      });
	
	      favicons = favicons.map(function (favicon) {
	        var f = {
	          href: favicon.href || favicon.content,
	          name: favicon.name || favicon.rel || favicon.property,
	          size: Math.min.apply(null, (favicon.sizes || '').split(/[^0-9\.]+/g)) || undefined
	        };
	
	        if (!f.size) {
	          delete f.size;
	        }
	
	        return f;
	      });
	
	      markActiveFavicon(favicons, size);
	      return resolve(favicons);
	    });
	  });
	}

/***/ },
/* 1 */
/***/ function(module, exports) {

	module.exports = require("x-ray");

/***/ },
/* 2 */
/***/ function(module, exports) {

	module.exports = require("url");

/***/ },
/* 3 */
/***/ function(module, exports) {

	'use strict';
	
	module.exports = {
	  selectors: ['link[rel=apple-touch-icon-precomposed][href]', 'link[rel=apple-touch-icon][href]', 'link[rel="shortcut icon"][href]', 'link[rel=icon][href]', 'meta[name=msapplication-TileImage][content]', 'meta[name=twitter\\:image][content]', 'meta[property=og\\:image][content]'],
	
	  predicates: [function (f, s) {
	    return f.name === 'apple-touch-icon-precomposed' && f.size >= s;
	  }, function (f, s) {
	    return f.name === 'apple-touch-icon' && f.size >= s;
	  }, function (f, s) {
	    return f.name === 'twitter:image' && f.size >= s;
	  }, function (f, s) {
	    return f.name === 'shortcut icon' && f.size >= s;
	  }, function (f, s) {
	    return f.name === 'icon' && f.size >= s;
	  }, function (f, s) {
	    return f.name === 'og:image' && f.size >= s;
	  }, function (f, s) {
	    return f.name === 'msapplication-TileImage' && f.size >= s;
	  }, function (f, s) {
	    return f.name === 'apple-touch-icon-precomposed';
	  }, function (f, s) {
	    return f.name === 'apple-touch-icon';
	  }, function (f, s) {
	    return f.name === 'twitter:image';
	  }, function (f, s) {
	    return f.name === 'shortcut icon';
	  }, function (f, s) {
	    return f.name === 'icon';
	  }, function (f, s) {
	    return f.name === 'og:image';
	  }, function (f, s) {
	    return f.name === 'msapplication-TileImage';
	  }, function (f, s) {
	    return f.name === 'favicon.ico';
	  }]
	};

/***/ },
/* 4 */
/***/ function(module, exports, __webpack_require__) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	  value: true
	});
	exports.default = markActiveFavicon;
	
	var _config = __webpack_require__(3);
	
	var _config2 = _interopRequireDefault(_config);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	function markActiveFavicon(favicons, minSize) {
	  var _loop = function _loop(i) {
	    var result = favicons.find(function (favicon) {
	      return _config2.default.predicates[i](favicon, minSize);
	    });
	    if (result) {
	      result.active = true;
	      return 'break';
	    }
	  };
	
	  for (var i = 0; i < _config2.default.predicates.length; i++) {
	    var _ret = _loop(i);
	
	    if (_ret === 'break') break;
	  }
	}

/***/ }
/******/ ])
});
;
//# sourceMappingURL=index.js.map