// services/FormService.js
import BaseService from './BaseService.js';
import { delegate } from '../utils.js';

/**
 * 默认校验规则（可在外部或实例化后覆盖）
 * errorMessage 可部分提供，代码会回退到合理默认文本
 */
const defaultValidationRules = {
  email: {
    pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    message: 'Invalid email format',
    errorMessage: { pattern: 'Invalid email format' },
  },
  username: {
    minLength: 3,
    errorMessage: { minLength: 'Register must be at least {minLength} characters long.' },
  },
  author: {
    minLength: 3,
    errorMessage: { minLength: 'Comment username must be at least {minLength} characters long.' },
  },
  regist_password: {
    minLength: 8,
    hasUppercase: /[A-Z]/,
    hasNumber: /\d/,
    hasSpecialChar: /[!@#$%^&*]/,
    message:
      'Password must be at least 8 characters long, include one uppercase letter, one number, and one special character.',
    errorMessage: {
      minLength: 'Password must be at least {minLength} characters long.',
      hasUppercase: 'Password must contain at least one uppercase letter.',
      hasNumber: 'Password must contain at least one number.',
      hasSpecialChar: 'Password must contain at least one special character.',
    },
  },
  confirm_password: {
    match: 'regist_password',
    message: 'Passwords do not match',
  },
  comment: {
    minLength: 6,
    message: 'Textarea must be at least 6 characters long',
    errorMessage: { minLength: 'Comment textarea must be at least {minLength} characters long.' },
  },
};

const defaultValidateField = (field, rulesMap = {}, formValues = {}) => {
  const rule = rulesMap[field.name];
  const value = (field.value ?? '').trim();

  if (!rule) return { valid: true };

  const {
    pattern,
    minLength,
    hasUppercase,
    hasNumber,
    hasSpecialChar,
    match,
    errorMessage = {},
    message,
  } = rule;

  if (pattern && value && !pattern.test(value)) {
    return { valid: false, message: errorMessage.pattern ?? message ?? 'Invalid format' };
  }

  if (minLength && value.length < minLength) {
    const tpl = errorMessage.minLength ?? message ?? 'Too short';
    return { valid: false, message: tpl.replace('{minLength}', String(minLength)) };
  }

  if (hasUppercase && value && !hasUppercase.test(value)) {
    return { valid: false, message: errorMessage.hasUppercase ?? message ?? 'Missing uppercase letter' };
  }

  if (hasNumber && value && !hasNumber.test(value)) {
    return { valid: false, message: errorMessage.hasNumber ?? message ?? 'Missing number' };
  }

  if (hasSpecialChar && value && !hasSpecialChar.test(value)) {
    return { valid: false, message: errorMessage.hasSpecialChar ?? message ?? 'Missing special character' };
  }

  if (match) {
    const otherValue = formValues[match] ?? '';
    if (value !== otherValue) {
      return { valid: false, message: message ?? 'Values do not match' };
    }
  }

  return { valid: true };
};

export default class FormService extends BaseService {
  /**
   * options:
   * { apiUrl, formId, action, security, headers = {}, messageId, passwordToggle = false, validationRules = {} }
   */
  constructor(options = {}) {
    super(options.apiUrl);
    const {
      formId,
      action,
      security,
      headers = {},
      messageId = null,
      passwordToggle = false,
      validationRules = {},
      submitTimeoutMs = 15000,
    } = options;

    Object.assign(this, {
      formId,
      action,
      security,
      headers,
      messageId,
      passwordToggle,
      submitTimeoutMs,
    });

    // 合并默认规则与用户规则（用户规则覆盖默认）
    this.validationRules = { ...defaultValidationRules, ...(validationRules || {}) };

    this.init();
  }

  init = () => {
    if (!this.formId) return;
    const form = document.getElementById(this.formId);
    if (!form) return;

    // 使用 delegate 绑定 submit（delegate helper 需兼容 (event, el) 这样的调用）
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
      try {
        field.type = isVisible ? 'text' : 'password';
      } catch (e) {
        // some browser / input combos might be immutable; fallback: replace input (rare)
        const replacement = field.cloneNode(true);
        replacement.type = isVisible ? 'text' : 'password';
        field.replaceWith(replacement);
      }
    });
    toggleElement.setAttribute('aria-pressed', String(isVisible));
  }

  /**
   * submit handler
   * event may be a delegated event where form argument is provided by delegate helper
   */
  handleFormSubmit = async (event, form) => {
    event.preventDefault();
    if (!form || !(form instanceof HTMLFormElement)) {
      // try to find form by id as fallback
      const f = document.getElementById(this.formId);
      if (!f) return;
      form = f;
    }

    // validation
    if (!this.validateForm(form)) {
      return;
    }

    const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');

    // prevent double-submit
    if (submitButton && (submitButton.disabled || submitButton.getAttribute('data-submitting') === '1')) return;

    if (submitButton) {
      submitButton.setAttribute('data-submitting', '1');
      this.toggleButton(submitButton, true);
    }

    this.beforeSubmit(form);

    // prepare body: by default send FormData
    // if caller prefers JSON, they can override by passing a plain object to `this.prepareRequestBody`
    let body = new FormData(form);
    // allow subclass/instance to customize body serialization (return FormData or plain object)
    if (typeof this.prepareRequestBody === 'function') {
      try {
        const custom = this.prepareRequestBody(form, body);
        if (custom instanceof FormData || typeof custom === 'object') body = custom;
      } catch (e) {
        console.warn('prepareRequestBody failed, falling back to FormData:', e);
      }
    }

    // build headers (if body is FormData we must NOT set Content-Type)
    const headers = { ...this.headers };
    const isFormData = body instanceof FormData;
    if (!isFormData && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json; charset=utf-8';
    }
    if (this.security) headers['X-WP-Nonce'] = this.security;

    const url = `${this.apiUrl.replace(/\/$/, '')}/${String(this.action || '').replace(/^\//, '')}`;

    try {
      const response = await this.fetchData({
        url,
        method: 'POST',
        body: isFormData ? body : body, // BaseService will serialize plain objects; FormData is passed through
        headers,
        fetchOptions: {
          credentials: 'same-origin',
          timeoutMs: this.submitTimeoutMs,
        },
      });

      // standardize success handling: some APIs return { success: true, data }, others return data directly
      this.onSuccess(response, form);
      this.afterSubmitSuccess(response, form);
    } catch (err) {
      // server may return structured errors: err.data, err.status
      this.onError(err, form);
    } finally {
      if (submitButton) {
        submitButton.removeAttribute('data-submitting');
        this.toggleButton(submitButton, false);
      }
      this.afterSubmit(form);
    }
  };

  /**
   * 验证表单：结合浏览器原生检验与自定义规则
   * - 报错只展示一次全局消息并将焦点移到首个错误元素
   */
  validateForm = (form) => {
    if (!form) return false;
    const fields = Array.from(form.querySelectorAll('input[name], textarea[name], select[name]'));
    const formValues = Object.fromEntries(new FormData(form));
    let firstInvalidEl = null;
    let globalMessage = null;

    // browser native constraint validation
    const nativeValid = form.checkValidity();
    if (!nativeValid) {
      // let browser show its messages; but we'll also continue to collect our custom messages
      try {
        form.reportValidity();
      } catch (e) { }
    }

    // custom rules
    for (const field of fields) {
      field.classList.remove('is-invalid');
      const { valid, message } = defaultValidateField(field, this.validationRules, formValues);
      if (!valid) {
        field.classList.add('is-invalid');
        if (!firstInvalidEl) firstInvalidEl = field;
        if (!globalMessage) globalMessage = message || 'Invalid input';
      }
    }

    if (firstInvalidEl) {
      try {
        firstInvalidEl.focus({ preventScroll: true });
      } catch (e) { }
    }

    if (globalMessage) {
      // show single message via displayMessage (BaseService)
      if (typeof this.displayMessage === 'function') this.displayMessage(globalMessage, 'danger', 7000);
      return false;
    }

    // if native reported invalid but our custom rules didn't, still fail
    if (!nativeValid) return false;

    return true;
  };

  // hooks: 可在实例或子类覆盖
  beforeSubmit = (_form) => { };
  afterSubmit = (_form) => { };
  afterSubmitSuccess = (_response, _form) => { };
  prepareRequestBody = (_form, defaultFormData) => defaultFormData; // override to return plain object for JSON

  onSuccess = (response, form) => {
    // 默认行为：重置表单，显示成功消息
    try {
      if (form && form instanceof HTMLFormElement) form.reset();
    } catch (e) { }
    if (typeof this.displayMessage === 'function') this.displayMessage('Form submitted successfully!', 'success', 5000);
    console.info('FormService onSuccess:', response);
  };

  onError = (error, form) => {
    // 统一错误处理：优先 server message -> err.data?.message -> err.message
    let msg = 'Submission failed';
    if (error && typeof error === 'object') {
      msg = (error.data && error.data.message) || error.message || msg;
      // if API returns validation errors array, try to display first message
      if (error.data && Array.isArray(error.data.errors) && error.data.errors.length) {
        msg = String(error.data.errors[0].message || error.data.errors[0]);
      }
    } else if (typeof error === 'string') {
      msg = error;
    }

    console.error('FormService onError:', error);
    if (typeof this.displayMessage === 'function') this.displayMessage(msg, 'danger', 8000);

    // Optionally mark related fields invalid when server returns field-specific errors
    if (error && error.data && error.data.fields && form) {
      // error.data.fields expected shape: { fieldName: 'message' }
      Object.keys(error.data.fields).forEach((fname) => {
        const el = form.querySelector(`[name="${fname}"]`);
        if (el) {
          el.classList.add('is-invalid');
          // optionally attach inline message via aria-describedby or custom UI (not implemented here)
        }
      });
    }
  };
}
