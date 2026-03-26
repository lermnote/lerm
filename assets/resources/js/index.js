// bootstrap.js
import 'bootstrap';
// main.js
import '../css/index.css';

import {
	DOMContentLoaded,
	safeRequestIdleCallback,
	initScrollAnimate,
	lazyLoadImages,
	codeHighlight,
	calendarAddClass,
	offCanvasMenu,
	navigationToggle,
	scrollTop
} from './utils.js';

import {
	likeBtnHandle,
	loadMoreHandle,
	handleCommentSuccess,
	handleLoginSuccess,
	handleUpdateProfileSuccess
} from './components.js';

import FormService from './services/FormService.js';


// import LoadPageService from './services/LoadPageService.js';

// const loadPageService = new LoadPageService({
//   apiUrl:      lermData.rest_url,
//   containerId: 'page-ajax',
//   headers:     { 'X-WP-Nonce': lermData.nonce },
//   ignoreUrls:  ['/regist', '/reset/', '/wp-admin/', '/wp-login.php'],
//   cacheExpiry: 1 * 60 * 1000,
// });

const formAjaxHandle = () => {
	const formConfigs = [
		// { formId: 'login', action: lermData.login_action, security: lermData.login_nonce },
		// { formId: 'reset', action: lermData.reset_action, security: lermData.reset_nonce },
		// { formId: 'regist', action: lermData.regist_action, security: lermData.regist_nonce, passwordToggle: true },
		{ formId: 'commentform', action: lermData.comment_action, security: lermData.nonce }
	];
	if (lermData.loggedin) {
		formConfigs.push({ formId: 'update-profile', action: lermData.profile_action, security: lermData.profile_nonce });
	}

	formConfigs.forEach(config => {
		const form = document.getElementById(config.formId);
		if (!form) return;
		const FormHandle = new FormService({
			...config,
			apiUrl: lermData.rest_url,
			messageId: `${config.formId}-msg`,
		});
		if (config.formId === 'commentform') FormHandle.afterSubmitSuccess = handleCommentSuccess;
		if (config.formId === 'login') FormHandle.afterSubmitSuccess = handleLoginSuccess;
		if (config.formId === 'update-profile') FormHandle.afterSubmitSuccess = handleUpdateProfileSuccess;
	});
};

DOMContentLoaded( () => {
	safeRequestIdleCallback(() => {
		initScrollAnimate()

		lazyLoadImages();
		codeHighlight();
		calendarAddClass();
		offCanvasMenu();
		navigationToggle();
	});

	scrollTop();
	formAjaxHandle();
	likeBtnHandle();
	loadMoreHandle();
	// Ajax 翻页（可选，取消注释以启用）
	// try {
	//   await loadPageService.init();
	//   window.lermLoadPage = loadPageService; // 暴露供调试
	// } catch (err) {
	//   console.error('[Lerm] LoadPageService init failed:', err);
	// }
});

// Re-run some init on dynamic content loads
document.addEventListener('contentLoaded', () => {
	scrollTop();
	formAjaxHandle();
	lazyLoadImages();
});
