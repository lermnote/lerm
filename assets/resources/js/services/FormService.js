import BaseService from './BaseService.js';
import { delegate } from '../utils/dom.js';
import { translate } from '../utils/i18n.js';

const commentMinLength = Math.max(Number.parseInt(window.lermData?.comment_min_length ?? '6', 10) || 0, 0);
const strongPasswordRule = {
	minLength: 8,
	hasUppercase: /[A-Z]/,
	hasNumber: /\d/,
	hasSpecialChar: /[!@#$%^&*]/,
	errorMessage: {
		minLength: translate('password_min', { minLength: 8 }),
		hasUppercase: translate('password_uppercase'),
		hasNumber: translate('password_number'),
		hasSpecialChar: translate('password_special'),
	},
};

const validationRules = {
	email: {
		pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
		errorMessage: {
			pattern: translate('invalid_email_format'),
		},
	},
	username: {
		minLength: 3,
		errorMessage: {
			minLength: translate('register_username_min', { minLength: 3 }),
		},
	},
	author: {
		minLength: 3,
		errorMessage: {
			minLength: translate('comment_username_min', { minLength: 3 }),
		},
	},
	password: strongPasswordRule,
	regist_password: strongPasswordRule,
	pass1: strongPasswordRule,
	password_confirm: {
		matches: ['password', 'regist_password', 'pass1'],
		message: translate('password_mismatch'),
	},
	confirm_password: {
		matches: ['password', 'regist_password', 'pass1'],
		message: translate('password_mismatch'),
	},
	pass2: {
		matches: ['password', 'regist_password', 'pass1'],
		message: translate('password_mismatch'),
	},
	comment: {
		minLength: commentMinLength,
		errorMessage: {
			minLength: translate('comment_min', { minLength: commentMinLength }),
		},
	},
};

const resolveMatchTarget = (formValues, matches = []) => {
	return matches.find((fieldName) => typeof formValues[fieldName] !== 'undefined') ?? '';
};

const validateField = (field, rules, formValues = {}) => {
	const rule = rules[field.name];
	const value = (field.value || '').trim();

	if (!rule) return { valid: true };
	if (!field.required && value.length === 0) return { valid: true };

	const {
		pattern,
		minLength,
		hasUppercase,
		hasNumber,
		hasSpecialChar,
		match,
		matches,
		errorMessage,
		message,
	} = rule;

	if (pattern && !pattern.test(value)) {
		return { valid: false, message: errorMessage?.pattern || translate('invalid_format') };
	}

	if (minLength && value.length < minLength) {
		return {
			valid: false,
			message: errorMessage?.minLength || translate('password_min', { minLength }),
		};
	}

	if (hasUppercase && !hasUppercase.test(value)) {
		return { valid: false, message: errorMessage?.hasUppercase || translate('password_uppercase') };
	}

	if (hasNumber && !hasNumber.test(value)) {
		return { valid: false, message: errorMessage?.hasNumber || translate('password_number') };
	}

	if (hasSpecialChar && !hasSpecialChar.test(value)) {
		return { valid: false, message: errorMessage?.hasSpecialChar || translate('password_special') };
	}

	const matchField = match || resolveMatchTarget(formValues, matches);
	if (matchField && value !== formValues[matchField]) {
		return { valid: false, message: message || translate('password_mismatch') };
	}

	return { valid: true };
};

export default class FormService extends BaseService {
	constructor({ apiUrl, formId, action, security, headers = {}, messageId, passwordToggle = false }) {
		super(apiUrl);
		Object.assign(this, { formId, action, security, headers, messageId, passwordToggle });
		this.init();
	}

	init = () => {
		const form = document.getElementById(this.formId);
		if (!form || form.dataset.lermFormBound === 'true') return;

		form.dataset.lermFormBound = 'true';
		delegate('submit', `#${this.formId}`, (event, formEl) => this.handleFormSubmit(event, formEl));

		if (this.passwordToggle) this.initPasswordToggle();
	};

	initPasswordToggle() {
		const toggleElement = document.getElementById(`${this.formId}-toggle`);
		const passwordFields = Array.from(document.querySelectorAll(`#${this.formId} input[type="password"]`));
		if (!toggleElement || passwordFields.length === 0) return;

		toggleElement.addEventListener('click', () => this.togglePasswordVisibility(passwordFields, toggleElement));
	}

	togglePasswordVisibility(passwordFields, toggleElement) {
		const isVisible = toggleElement.classList.toggle('visible');
		passwordFields.forEach((field) => {
			field.type = isVisible ? 'text' : 'password';
		});
		toggleElement.textContent = isVisible ? translate('hide') : translate('show');
		toggleElement.setAttribute('aria-label', isVisible ? translate('hide_password') : translate('show_password'));
	}

	handleFormSubmit = async (event, form) => {
		event.preventDefault();
		if (!this.validateForm(form)) {
			return;
		}

		const submitButton = form.querySelector('button[type="submit"]');
		if (submitButton?.disabled) return;

		this.toggleButton(submitButton, true);

		const formData = new FormData(form);
		const requestUrl = [this.apiUrl.replace(/\/$/, ''), this.action.replace(/^\//, '')].join('/');

		this.beforeSubmit();
		try {
			const response = await this.fetchData({
				url: requestUrl,
				method: 'POST',
				body: formData,
				headers: {
					'X-WP-Nonce': this.security,
					...this.headers,
				},
				fetchOptions: {
					credentials: 'same-origin',
				},
			});

			this.onSuccess(response, form);
		} catch (error) {
			this.onError(error);
		} finally {
			this.toggleButton(submitButton, false);
		}
	};

	validateForm = (form) => {
		const fields = form.querySelectorAll('input, textarea, select');
		let isFormValid = true;
		const formValues = Object.fromEntries(new FormData(form));
		const isValid = form.checkValidity();

		if (!isValid) form.reportValidity();

		fields.forEach((field) => {
			const { valid, message } = validateField(field, validationRules, formValues);
			if (!valid) {
				field.classList.add('is-invalid');
				this.displayMessage(message, 'danger');
				isFormValid = false;
			} else {
				field.classList.remove('is-invalid');
			}
		});

		return isValid && isFormValid;
	};

	beforeSubmit = () => {}

	onSuccess = (response, form) => {
		form.reset();
		this.afterSubmitSuccess(response);
		this.displayMessage(response?.message || translate('form_submitted'), 'success');
	};

	afterSubmitSuccess = (_response) => {}

	onError = (error) => {
		console.error('Form submission failed:', error);
		if (this.messageId) this.displayMessage(error.message, 'danger');
	};
}
