import 'bootstrap';
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
	viewCountHandle,
	handleCommentSuccess,
	handleUpdateProfileSuccess
} from './components.js';

import FormService from './services/FormService.js';

const formAjaxHandle = () => {
	const formConfigs = [
		{ formId: 'commentform', action: lermData.route_comment, security: lermData.nonce }
	];

	if (lermData.loggedin) {
		formConfigs.push({
			formId: 'update-profile',
			action: lermData.route_profile,
			security: lermData.profile_nonce
		});
	}

	formConfigs.forEach((config) => {
		const form = document.getElementById(config.formId);
		if (!form) return;

		const formHandle = new FormService({
			...config,
			apiUrl: lermData.rest_url,
			messageId: `${config.formId}-msg`,
		});

		if (config.formId === 'commentform') {
			formHandle.afterSubmitSuccess = handleCommentSuccess;
		}

		if (config.formId === 'update-profile') {
			formHandle.afterSubmitSuccess = handleUpdateProfileSuccess;
		}
	});
};

DOMContentLoaded(() => {
	safeRequestIdleCallback(() => {
		initScrollAnimate();
		lazyLoadImages();
		codeHighlight();
		calendarAddClass();
		offCanvasMenu();
		navigationToggle();
	});

	scrollTop();
	formAjaxHandle();
	likeBtnHandle();
	viewCountHandle();
	loadMoreHandle();
});

document.addEventListener('contentLoaded', () => {
	initScrollAnimate();
	scrollTop();
	formAjaxHandle();
	lazyLoadImages();
	viewCountHandle();
});
