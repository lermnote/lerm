// services/FormService.js
import BaseService from './BaseService.js';
import { delegate } from '../utils/dom.js';
import { translate } from '../utils/i18n.js';

// reuse the validationRules and validateField from the original script
const validationRules = {
  email: {
    pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    message: translate('invalid_email_format'),
  },
  username: { minLength: 3, errorMessage: { minLength: translate('register_username_min') } },
  author: { minLength: 3, errorMessage: { minLength: translate('comment_username_min') } },
  regist_password: {
    minLength: 8,
    hasUppercase: /[A-Z]/,
    hasNumber: /\d/,
    hasSpecialChar: /[!@#$%^&*]/,
    message: translate('password_min', { minLength: 8 }),
    errorMessage: {
      minLength: translate('password_min'),
      hasUppercase: translate('password_uppercase'),
      hasNumber: translate('password_number'),
      hasSpecialChar: translate('password_special'),
    }
  },
  confirm_password: { match: 'regist_password', message: translate('password_mismatch') },
  comment: { minLength: 6, message: translate('comment_min', { minLength: 6 }), errorMessage: { minLength: translate('comment_min') } }
};

const validateField = (field, rules, formValues = {}) => {
  const rule = rules[field.name];
  const value = field.value || '';
  if (!rule) return { valid: true };
  const { pattern, minLength, hasUppercase, hasNumber, hasSpecialChar, match, errorMessage } = rule;

  if (pattern && !pattern.test(value)) return { valid: false, message: errorMessage?.pattern || translate('invalid_format') };
  if (minLength && value.length < minLength) return { valid: false, message: errorMessage?.minLength.replace('{minLength}', minLength) };
  if (hasUppercase && !hasUppercase.test(value)) return { valid: false, message: errorMessage.hasUppercase };
  if (hasNumber && !hasNumber.test(value)) return { valid: false, message: errorMessage.hasNumber };
  if (hasSpecialChar && !hasSpecialChar.test(value)) return { valid: false, message: errorMessage.hasSpecialChar };
  if (match && value !== formValues[match]) return { valid: false, message: rule.message || 'Values do not match' };
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
    if (!form) return;
    delegate('submit', `#${this.formId}`, (event, formEl) => this.handleFormSubmit(event, formEl));
    if (this.passwordToggle) this.initPasswordToggle();
  }

  initPasswordToggle() {
    const toggleElement = document.getElementById(`${this.formId}-toggle`);
    const passwordFields = Array.from(document.querySelectorAll(`#${this.formId} input[type="password"]`));
    if (!toggleElement || passwordFields.length === 0) return;
    toggleElement.addEventListener('click', () => this.togglePasswordVisibility(passwordFields, toggleElement));
  }

  togglePasswordVisibility(passwordFields, toggleElement) {
    const isVisible = toggleElement.classList.toggle('visible');
    passwordFields.forEach((field) => field.type = isVisible ? 'text' : 'password');
    toggleElement.textContent = isVisible ? translate('hide') : translate('show');
    toggleElement.setAttribute('aria-label', isVisible ? translate('hide_password') : translate('show_password'));
  }

  handleFormSubmit = async (event, form) => {
    event.preventDefault();
    if (!this.validateForm(form)) {
      console.warn(`Form validation failed for ID "${this.formId}".`);
      return;
    }
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton.disabled) return;
    this.toggleButton(submitButton, true);
    const formData = new FormData(form);
    const requestUrl = [
      this.apiUrl.replace(/\/$/, ''),
      this.action.replace(/^\//, '')
    ].join('/');

    this.beforeSubmit();
    try {
      const response = await this.fetchData({
        url: requestUrl,
        method: 'POST',
        body: formData,
        headers: {
            'X-WP-Nonce': this.security, 
            ...this.headers
        },
      });
 
      this.onSuccess(response, form);

    } catch (error) {
      this.onError(error);
    } finally {
      this.toggleButton(submitButton, false);
    }
  }

  validateForm = (form) => {
    const fields = form.querySelectorAll('input, textarea, select');
    let isFormValid = true;
    const formValues = Object.fromEntries(new FormData(form));
    const isValid = form.checkValidity();
    if (!isValid) form.reportValidity();
    fields.forEach(field => {
      const { valid, message } = validateField(field, validationRules, formValues);
      if (!valid) {
        field.classList.add('is-invalid');
        this.displayMessage(message, 'danger');
        isFormValid = false;
      } else {
        field.classList.remove('is-invalid');
      }
    });
    return isFormValid;
  }

  beforeSubmit = () => {}
  afterSubmit(form) { console.log('After submitting form:', form); }

  onSuccess = (response, form) => {
    form.reset();
    this.afterSubmitSuccess(response);
    this.displayMessage(response?.message || translate('form_submitted'), 'success');
  }

  afterSubmitSuccess = (_response) => {}
  onError = (error) => {
    console.error('Form submission failed:', error);
    if (this.messageId) this.displayMessage(error.message, 'danger');
  }
}
