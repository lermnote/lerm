import FormService from '../services/FormService.js';
import { handleCommentSuccess } from './comments.js';
import { handleUpdateProfileSuccess } from './profile.js';

const getFormConfigs = () => {
	const formConfigs = [
		{ formId: 'commentform', action: lermData.route_comment, security: lermData.nonce },
	];

	if (lermData.loggedin) {
		formConfigs.push({
			formId: 'update-profile',
			action: lermData.route_profile,
			security: lermData.profile_nonce,
		});
	}

	return formConfigs;
};

export const initializeForms = () => {
	getFormConfigs().forEach((config) => {
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
