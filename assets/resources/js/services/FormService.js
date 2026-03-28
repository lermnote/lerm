// services/FormService.js
import BaseService from './BaseService.js';
import { delegate } from '../utils.js';

// reuse the validationRules and validateField from the original script
const validationRules = {
  email: {
    pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    message: 'Invalid email format',
  },
  username: { minLength: 3, errorMessage: { minLength: 'Register must be at least {minLength} characters long.' } },
  author: { minLength: 3, errorMessage: { minLength: 'Comment username must be at least {minLength} characters long.' } },
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
      hasSpecialChar: 'Password must contain at least one special character.',
    }
  },
  confirm_password: { match: 'regist_password', message: 'Passwords do not match' },
  comment: { minLength: 6, message: 'Textarea must be at least 10 characters long', errorMessage: { minLength: 'Comment textarea must be at least {minLength} characters long.' } }
};

const validateField = (field, rules, formValues = {}) => {
  const rule = rules[field.name];
  const value = field.value || '';
  if (!rule) return { valid: true };
  const { pattern, minLength, hasUppercase, hasNumber, hasSpecialChar, match, errorMessage } = rule;

  if (pattern && !pattern.test(value)) return { valid: false, message: errorMessage?.pattern || 'Invalid format' };
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
    this.displayMessage(response?.message || 'Form submitted successfully!', 'success');
  }

  afterSubmitSuccess = (_response) => {}
  onError = (error) => {
    console.error('Form submission failed:', error);
    if (this.messageId) this.displayMessage(error.message, 'danger');
  }
}
