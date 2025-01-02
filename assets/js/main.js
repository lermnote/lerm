/**
 * Global Javascript Functions
 *
 * @package Lerm https://lerm.net
 */
(() => {
	'use strict';

	/**
	 * BaseService - Handles API interactions with reusable methods
	 */
	class BaseService {
		constructor(apiUrl) {
			this.apiUrl = apiUrl;
		}
		/**
		  * Fetch data from API with flexible configuration
		  * @param {Object} config - Request configuration object
		  * @returns {Promise<Object>} - Parsed JSON response
		  */
		fetchData = async ({ url, method = 'GET', body = {}, headers = {} }) => {
			const options = {
				method,
				headers: { ...headers },
				body: method !== 'GET' ? body : null,
			};
			try {
				const response = await fetch(url, options);
				if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
				return await response.json();
			} catch (error) {
				if (error.name === 'AbortError') {
					console.warn('Fetch aborted');
				} else {
					this.handleError(error);
				}
				throw error;
			}
		}

		/**
		 * Handle errors by logging them and displaying an alert.
		 * @param {Error} error - The error object to handle.
		 */
		handleError = (error) => {
			console.error("An error occurred:", error.message);
			alert(`An error occurred: ${error.message}`);
		}
		/**
		 * Rate limit a function to prevent it from being called too frequently.
		 * @param {Function} func - The function to rate limit.
		 * @param {number} wait - The number of milliseconds to wait before allowing the function to be called again.
		 * @param {boolean} isThrottle - Whether to use throttling (true) or debouncing (false).
		 * @returns {Function} - The rate-limited function.
		 */
		rateLimit = (func, wait, isThrottle = false) => {
			let timeout, lastTime = 0;
			return (...args) => {
				const context = this;
				const now = Date.now();
				const later = () => {
					timeout = null;
					if (!isThrottle) func.apply(context, args);
				};
				const remaining = wait - (now - lastTime);
				if (isThrottle && remaining <= 0) {
					clearTimeout(timeout);
					timeout = null;
					lastTime = now;
					func.apply(context, args);
				} else if (!timeout) {
					timeout = setTimeout(later, isThrottle ? remaining : wait);
				}
			};
		}

		/**
		 * Display a message in a specified element for a limited duration.
		 * @param {string} message - The message to display.
		 * @param {string} [type='info'] - The type of message (e.g., 'info', 'success', 'danger').
		 * @param {number} [duration=5000] - The duration to display the message (in milliseconds).
		 */
		displayMessage = (message, type = 'info', duration = 5000) => {
			if (!this.messageId) return;

			const messageElement = document.getElementById(this.messageId);
			if (messageElement) {
				messageElement.innerHTML = message;
				messageElement.classList.add(`text-${type}`);
				messageElement.classList.remove('invisible');
				clearTimeout(this.messageTimeout);
				this.messageTimeout = setTimeout(() => {
					messageElement.classList.add('invisible');
					messageElement.classList.remove(`text-${type}`);
				}, duration);
			}
		}
		/**
		 * Toggle the loading state of a button by adding/removing a spinner and disabling/enabling the button.
		 * @param {HTMLElement} button - The button element to toggle.
		 * @param {boolean} isLoading - Whether to show the loading spinner.
		 * @param {boolean} [disabled=false] - Whether to disable the button.
		 */
		toggleButton = (button, isLoading, disabled = false) => {
			if (isLoading) {
				button.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> ');
			} else {
				const tempElement = document.querySelector('.spinner-border');
				if (tempElement) {
					tempElement.remove();
				}
			}
			if (!disabled) {
				button.removeAttribute('disabled');
			}
		}
	}

	/**
	 * --------------------------------------------------------------------------
	 * DOM Utilities
	 * --------------------------------------------------------------------------
	 */
	/**
	 * Utility function to add a global event listener for a specific selector.
	 * @param {string} type - Event type (e.g., "click", "mouseover").
	 * @param {string} selector - CSS selector to match target elements.
	 * @param {Function} callback - Function to execute when event is triggered.
	 */
	const addEventListener = (type, selector, callback) => {
		document.addEventListener(type, event => {
			const targetElement = event.target.closest(selector);
			if (targetElement && targetElement.matches(selector)) {
				event.preventDefault();
				callback(event, targetElement);
			}
		});
	}

	/**
	 * --------------------------------------------------------------------------
	 * Lerm Theme ClickService
	 * --------------------------------------------------------------------------
	 */
	class ClickService extends BaseService {
		constructor({ apiUrl, selector, action, security, headers = {}, additionalData = {}, isThrottled = false, cacheExpiryTime = 60000,
			enableCache = true }) {
			super(apiUrl);
			Object.assign(this, {
				selector,
				action,
				security,
				headers,
				additionalData,
				cacheExpiryTime,
				enableCache,
			});

			this.clickHandler = isThrottled
				? this.rateLimit(this.handleClick, 1000, true)
				: this.handleClick;

			addEventListener('click', this.selector, this.clickHandler);
		}

		handleClick = async (event, target) => {
			event.preventDefault()

			this.beforeClick(event);

			let requestData = new URLSearchParams({
				action: this.action,
				security: this.security,
				...target.dataset,
				...this.additionalData
			});

			const cacheKey = `click_action_${this.selector}`;
			if (this.enableCache && this.isCacheValid(cacheKey)) {
				this.useCache(cacheKey);
				return;
			}
			this.toggleButton(target, true);
			try {
				const response = await this.fetchData({
					url: this.apiUrl,
					method: 'POST',
					body: requestData.toString(),
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
						...this.headers,
					},
				});

				if (response.success) {
					this.onSuccess(response.data, target);
				} else {
					throw new Error(response.data || 'Unknown error occurred');
				}

				if (this.enableCache) {
					this.updateCache(cacheKey, response);
				}
			} catch (error) {
				this.onError(error, target);
			}
			this.toggleButton(target, false);
		}
		// 检查缓存是否有效
		isCacheValid = (cacheKey) => {
			const cachedData = sessionStorage.getItem(cacheKey);
			const cacheTime = sessionStorage.getItem(`${cacheKey}_time`);
			return cachedData && Date.now() - cacheTime < this.cacheExpiryTime;
		}
		/**
		 * 使用缓存数据
		 * @param {string} cacheKey
		 */
		useCache (cacheKey) {
			const cachedResponse = JSON.parse(sessionStorage.getItem(cacheKey));
			this.onSuccess(cachedResponse);
		}
		// 缓存响应并记录缓存时间
		cacheResponse = (cacheKey, response) => {
			localStorage.setItem(cacheKey, JSON.stringify(response));
			localStorage.setItem(`${cacheKey}_time`, Date.now());
		}
		/**
		 * 更新缓存
		 * @param {string} cacheKey
		 * @param {Object} response
		 */
		updateCache (cacheKey, response) {
			sessionStorage.setItem(cacheKey, JSON.stringify(response));
			sessionStorage.setItem(`${cacheKey}_time`, Date.now());
		}

		beforeClick = () => { console.log('Processing click...'); }
		onSuccess = (response, target) => {
			this.displayMessage('Click action was successful!');
			console.log('Response:', response);
		}
		onError = (error, target) => {
			this.displayMessage('Failed to process click action.');
			console.error('Error:', error);
			target.setAttribute('disabled', 'disabled');
			target.innerHTML = error.message
		}
	}

	/**
	 * --------------------------------------------------------------------------
	 * Lerm Theme LoadPageService
	 * --------------------------------------------------------------------------
	 */
	class LoadPageService extends BaseService {
		constructor({ apiUrl, containerId, ignoreUrls = [], errorText = "An error occurred while loading content." }) {
			super(apiUrl);
			this.containerId = containerId;
			this.ignoreUrls = ignoreUrls;
			this.errorText = errorText;
			this.state = {
				ajaxLoading: false,
				ajaxStarted: false,
			};
		}

		init () {
			this.bindLinkClicks((link) => this.shouldInterceptLink(link), (href) => this.loadPage(href));
			this.bindSearchForm('form[method="GET"]', (action, params) => this.loadPage(action, false, params));
			window.onpopstate = () => this.handlePopState();
			console.log("LoadPageService initialized.");
		}
		/**
		* Example: Bind click events to links with interception logic.
		* @param {Function} interceptCallback - Callback function to determine if a link should be intercepted.
		*/
		bindLinkClicks = (interceptCallback, callback) => {
			addEventListener("click", "a", (event, link) => {
				if (interceptCallback && interceptCallback(link)) {
					event.preventDefault();
					callback(link.href);
				}
			});
		};
		/**
		 * Bind global search form submit events
		 * Uses a global event listener to handle form submissions dynamically
		 * @param {string} selector - CSS selector for the forms
		 * @param {Function} callback - Logic to handle the form submission
		 */
		bindSearchForm = (selector, callback) => {
			addEventListener("submit", selector, (event, form) => {
				event.preventDefault(); // Prevent default form submission
				const params = new URLSearchParams(new FormData(form)).toString(); // Serialize form data to query string
				callback(form.action, params); // Execute the callback with action URL and parameters
			});
		};
		// Handle browser back/forward navigation
		handlePopState () {
			if (this.state.ajaxStarted && !this.isIgnoredUrl(window.location.href)) {
				this.loadPage(window.location.href, true);
			}
		}
		/**
		 * Determine if a link should be intercepted for custom handling
		 * @param {HTMLAnchorElement} link - The link element to evaluate
		 * @returns {boolean} - Returns true if the link should be intercepted
		 */
		shouldInterceptLink (link) {
			return (
				link.href.includes(window.location.origin) && // Only intercept internal links
				!this.isIgnoredUrl(link.href) &&              // Exclude links to ignored URLs
				!this.state.ajaxLoading                       // Ensure no concurrent loading is happening
			);
		}
		/**
		 * Load a page via AJAX and dynamically update the content.
		 * @param {string} url - The URL to load.
		 * @param {boolean} isPopState - Whether the call is from popstate navigation.
		 * @param {string} [params=null] - Optional query parameters.
		 */
		async loadPage (url, isPopState = false, params = null) {
			if (this.state.ajaxLoading) return;
			this.state.ajaxLoading = true;

			const container = document.getElementById(this.containerId);
			if (!container) {
				console.error("Container not found.");
				this.state.ajaxLoading = false;
				return;
			}

			// Update browser history
			if (!isPopState && history.pushState) {
				const updatedUrl = params ? `${url}?${params}` : url;
				history.pushState({}, "", new URL(updatedUrl, window.location.origin).href);
			}
			fadeOut(container);

			try {
				const fullUrl = params ? `${url}?${params}` : url;

				const response = await this.fetchData({
					url: `${this.apiUrl}?action=load_page_content&url=${encodeURIComponent(fullUrl)}`,
					method: "GET"
				});
				if (response.success) {
					this.updatePageContent(container, response.data);
				} else {
					throw new Error(response.message || this.errorText);
				}
				fadeIn(container);
				window.scrollTo({ top: 0, behavior: "smooth" });
			} catch (error) {
				console.error("Error during page load:", error);
				this.displayError(container);
			} finally {
				this.state.ajaxLoading = false;
			}
		}
		/**
		 * Update the page content dynamically.
		 * @param {HTMLElement} container - The container element to update.
		 * @param {object} data - The new content and metadata to display.
		 */
		updatePageContent (container, data) {
			document.title = data.title || document.title;

			this.updateMeta("description", data.meta_description);
			this.updateMeta("keywords", data.meta_keywords);

			container.innerHTML = data.content || "";
			document.dispatchEvent(new Event("contentLoaded"));
		}
		/**
		 * Update meta tags dynamically.
		 * @param {string} name - The name of the meta tag.
		 * @param {string} content - The content for the meta tag.
		 */
		updateMeta (name, content) {
			if (!content) return;
			let meta = document.querySelector(`meta[name="${name}"]`);
			if (!meta) {
				meta = document.createElement("meta");
				meta.setAttribute("name", name);
				document.head.appendChild(meta);
			}
			meta.setAttribute("content", content);
		}
		/**
		 * Check if a URL is in the ignore list
		 * @param {string} url - The URL to evaluate
		 * @returns {boolean} - Returns true if the URL matches any ignored pattern
		 */
		isIgnoredUrl (url) {
			return this.ignoreUrls.some(ignore => url.includes(ignore));
		}
		/**
		 * Display an error message in the container.
		 * @param {HTMLElement} container - The container to display the error in.
		 * @param {string} message - The error message to display.
		 */
		displayError (container, message) {
			fadeIn(container);
			container.innerHTML = `<p class="text-danger">${message}</p>`;
		}
	}

	/**
	* --------------------------------------------------------------------------
	* Lerm Theme Like button
	* --------------------------------------------------------------------------
	*/
	const likeBtnSuccess = (data, target) => {
		const { id, type } = target.dataset;
		const buttons = document.querySelectorAll(`.like-${type}-${id}`);
		const status = data.status;
		const count = data.count;

		buttons.forEach(button => {
			button.classList.toggle('btn-outline-danger', status === 'liked');
			button.classList.toggle('btn-outline-secondary', status === 'unliked');
			button.querySelector('.count').textContent = count;
			button.setAttribute('title', status === 'liked' ? 'unlike' : 'like');
		});
	}
	const likeBtnHandle = () => {
		const postLikeConfig = {
			selector: '.like-button',
			action: 'post_like',
			security: lermData.like_nonce,
			apiUrl: lermData.url,
			isThrottled: true,
			cacheExpiryTime: 60000,
			enableCache: false
		};

		const postLike = new ClickService(postLikeConfig);
		postLike.onSuccess = likeBtnSuccess;
	}

	/**
	* --------------------------------------------------------------------------
	* Lerm Theme Load More button
	* --------------------------------------------------------------------------
	*/
	const appendPostsToDOM = (data) => {
		const loadMoreBtn = document.querySelector(".more-posts");
		const postsList = document.querySelector(".ajax-posts");
		if (postsList) {
			postsList.insertAdjacentHTML('beforeend', data.content);
		}

		loadMoreBtn.dataset.currentPage = data.currentPage;
	};
	const loadMoreHanle = () => {
		// Load more button setup
		const loadMore = new ClickService({
			selector: '.more-posts',
			action: 'load_more',
			security: lermData.nonce,
			apiUrl: lermData.url,
			isThrottled: true,
			cacheExpiryTime: 60000,
			enableCache: false
		});
		loadMore.onSuccess = appendPostsToDOM;
	}
	/**
	 * --------------------------------------------------------------------------
	 * Lerm Theme FormService
	 * --------------------------------------------------------------------------
	 */
	const validationRules = {
		email: {
			pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
			message: 'Invalid email format',
		},
		username: {
			minLength: 3,
			errorMessage: {
				minLength: 'Register must be at least {minLength} characters long.',
			}
		},
		author: {
			minLength: 3,
			errorMessage: {
				minLength: 'Comment username must be at least {minLength} characters long.',
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
				hasSpecialChar: 'Password must contain at least one special character.',
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
				minLength: 'Comment textarea must be at least {minLength} characters long.',
			}
		}
	};

	const validateField = (field, rules,formValues={}) => {
		const rule = rules[field.name];
		const value = field.value;

		if (!rule) return { valid: true };

		const {
			pattern,
			minLength,
			hasUppercase,
			hasNumber,
			hasSpecialChar,
			match,
			errorMessage,
		  } = rule;
		  console.log(pattern);
		if (pattern && !pattern.test(value)) {

			return { valid: false, message: errorMessage?.pattern || 'Invalid format' };
		}
		if (minLength && value.length < minLength) {
			return { valid: false, message: errorMessage?.minLength.replace('{minLength}', minLength) };
		}
		if (hasUppercase && !hasUppercase.test(value)) {
			return { valid: false, message: errorMessage.hasUppercase };
		}
		if (hasNumber && !hasNumber.test(value)) {
			return { valid: false, message: errorMessage.hasNumber };
		}
		if (hasSpecialChar && !hasSpecialChar.test(value)) {
			return { valid: false, message: errorMessage.hasSpecialChar };
		}
		if (match && value !== formValues[match]) {
			return { valid: false, message: rule.message || 'Values do not match' };
		}
		return { valid: true };
	};
	const togglePasswordVisibility = (passwordFields, toggleElement) => {
		passwordFields.forEach(field => {
			const isPasswordVisible = field.type === 'password';
			field.type = isPasswordVisible ? 'text' : 'password';
		});
		toggleElement.innerText = passwordFields[0].type === 'password' ? 'show' : 'hide';
	};

	class FormService extends BaseService {
		constructor({ apiUrl, formId, action, security, headers = {}, messageId, passwordToggle = false }) {
			super(apiUrl);
			Object.assign(this, {
				formId,
				action,
				security,
				headers,
				messageId,
				passwordToggle,
			});

			this.init();
		}

		init = () => {
			const form = document.getElementById(this.formId);
			if (!form) return

			addEventListener('submit', `#${this.formId}`, (event, form) => {
				this.handleFormSubmit(event, form);
			});

			if (this.passwordToggle) {
				// this.initPasswordToggle();
				const toggleElement = document.getElementById(`${this.formId}-toggle`);
				const passwordFields = Array.from(document.querySelectorAll('input[type="password"]'));
				toggleElement?.addEventListener('click', () => togglePasswordVisibility(passwordFields, toggleElement));
			}
		}

		togglePasswordVisibility = (passwordFields, toggleElement) => {
			passwordFields.forEach(field => {
				const isPasswordVisible = field.type === 'password';
				field.type = isPasswordVisible ? 'text' : 'password';
			});
			toggleElement.innerText = passwordFields[0].type === 'password' ? 'show' : 'hide';
		};

		/**
		 * 初始化密码可见性切换功能
		 */
		initPasswordToggle () {
			const toggleElement = document.getElementById(`${this.formId}-toggle`);
			const passwordFields = Array.from(
				document.querySelectorAll(`#${this.formId} input[type="password"]`)
			);

			if (!toggleElement || passwordFields.length === 0) {
				console.warn(`Password toggle or fields not found for form ID "${this.formId}".`);
				return;
			}

			toggleElement.addEventListener('click', () =>
				this.togglePasswordVisibility(passwordFields, toggleElement)
			);
		}

		/**
		 * 切换密码字段的可见性
		 * @param {Array} passwordFields - 密码字段数组
		 * @param {Element} toggleElement - 切换按钮元素
		 */
		togglePasswordVisibility (passwordFields, toggleElement) {
			const isVisible = toggleElement.classList.toggle('visible');
			passwordFields.forEach((field) => {
				field.type = isVisible ? 'text' : 'password';
			});
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
			formData.append('action', this.action);
			formData.append('security', this.security);

			this.beforeSubmit();

			try {
				const response = await this.fetchData({
					url: this.apiUrl,
					method: 'POST',
					body: formData,
					headers: this.headers,
				});
				if (response.success) {
					this.onSuccess(response, form);
				} else {
					throw new Error(response || 'Unknown error occurred');
				}
			} catch (error) {
				this.onError(error);
			} finally {
				this.toggleButton(submitButton, false);
			}
		}

		validateForm = (form) => {
			const fields = document.querySelectorAll('input, textarea, select');
			let isFormValid = true;
			const formValues = Object.fromEntries(new FormData(form));

			fields.forEach(field => {
				const { valid, message } = validateField(field, validationRules,formValues);
				if (!valid) {
					field.classList.add('is-invalid');
					this.displayMessage(message, 'danger');
					isFormValid = false;
				} else {
					field.classList.remove('is-invalid');
				}
			});

			return isFormValid;
		};
		// validateForm (form) {
		// 	const isValid = form.checkValidity();
		// 	if (!isValid) {
		// 		form.reportValidity(); // 浏览器提示验证错误
		// 	}
		// 	return isValid;
		// }

		beforeSubmit = () => { }
		afterSubmit (form) {
			console.log('After submitting form:', form);
		}
		onSuccess = (response, form) => {
			form.reset();
			this.afterSubmitSuccess(response.data);
			this.displayMessage('Form submitted successfully!', 'success');
		}

		afterSubmitSuccess = (_response) => { }
		onError (error) {
			console.error('Form submission failed:', error);
			if (this.messageId) {
				this.displayMessage(error.message, 'danger');
			}
		}
	}

	/**
	 * Handles the successful submission of a comment by adding the new comment to the comment list.
	 *
	 * @param {Object} data - The data returned after a successful comment submission.
	 */
	const handleCommentSuccess = (data) => {
		const respond = document.getElementById("respond");
		const commentList = document.querySelector(".comment-list");
		const isParentComment = data.comment.comment_parent === '0';
		const createCommentHTML = (comment) => `
        <li class="${comment.comment_type} list-group-item${comment.comment_parent !== '0' ? ' p-0' : ''}" id="comment-${comment.comment_ID}">
            <article id="div-comment-${comment.comment_ID}" class="comment-body">
                <footer class="comment-meta mb-1">
                    <span class="comment-author vcard">
                        <img src="${comment.avatar_url}" srcset="${comment.avatar_url} 2x"
                             alt="${comment.comment_author}" class="avatar avatar-${comment.avatar_size} photo"
                             height="${comment.avatar_size}" width="${comment.avatar_size}" loading="lazy" decoding="async">
                        <b class="fn">${comment.comment_author}</b>
                    </span>
                    <span class="comment-metadata">
                        <span aria-hidden="true">•</span>
                        <a href="http://localhost/wordpress/${comment.comment_post_ID}/#comment-${comment.comment_ID}">
                            <time datetime="${comment.comment_date_gmt}">${comment.comment_date}</time>
                        </a>
                    </span>
                </footer>
                ${comment.comment_approved === '0' ? `
                    <span class="comment-awaiting-moderation badge rounded-pill bg-info">
                        您的评论正在等待审核。
                    </span>` : ''}
                <section class="comment-content" style="margin-left: 56px">
                    <p>${comment.comment_content}</p>
                </section>
            </article>
        </li>`;

		const nodeLi = createCommentHTML(data.comment);

		if (commentList) {
			const previousElement = respond.previousElementSibling;

			if (previousElement) {
				const lastChild = previousElement.lastElementChild;

				if (lastChild && lastChild.classList.contains("children")) {
					lastChild.insertAdjacentHTML('beforeend', nodeLi);
				} else {
					const childrenUl = document.createElement('ul');
					childrenUl.classList.add('children');
					childrenUl.innerHTML = nodeLi;
					previousElement.appendChild(childrenUl);
				}
			} else {
				commentList.insertAdjacentHTML('afterbegin', nodeLi);
			}
		} else {
			const newCommentCard = document.createElement('div');
			newCommentCard.classList.add('card', 'mb-3');
			newCommentCard.innerHTML = `
				<ol class="comment-list p-0 m-0 list-group list-group-flush">
					${nodeLi}
				</ol>`;
			respond.parentNode.appendChild(newCommentCard);
		}
	};
	const handleLoginSuccess = () => {
		loadPage(lermData.frontDoor);
	}

	const handleUpdateProfileSuccess = () => {
		loadPage(lermData.redirect);
	}
	const formAjaxHandle = () => {
		const formConfigs = [
			{ formId: 'login', action: lermData.login_action, security: lermData.login_nonce },
			{ formId: 'reset', action: lermData.reset_action, security: lermData.reset_nonce },
			{ formId: 'regist', action: lermData.regist_action, security: lermData.regist_nonce, passwordToggle: true },
			{ formId: 'commentform', action: lermData.comment_action, security: lermData.nonce }
		];
		if (lermData.loggedin) {
			formConfigs.push({ formId: 'update-profile', action: lermData.profile_action, security: lermData.profile_nonce })
		}

		formConfigs.forEach(config => {
			const form = document.getElementById(config.formId);
			if (!form) return;
			const FormHandle = new FormService({
				...config,
				apiUrl: lermData.url,
				messageId: `${config.formId}-msg`,
			});
			console.log(config.formId);
			if (config.formId === 'commentform') FormHandle.afterSubmitSuccess = handleCommentSuccess;
			if (config.formId === 'login') FormHandle.afterSubmitSuccess = handleLoginSuccess;
			if (config.formId === 'update-profile') FormHandle.afterSubmitSuccess = handleUpdateProfileSuccess;
		});
	};

	const DOMContentLoaded = callback => {
		if (document.readyState === "loading") {
			document.addEventListener("DOMContentLoaded", callback, { once: true });
		} else {
			callback();
		}
	};

	//calendar add class
	const calendarAddClass = () => {
		const calendar = document.querySelector("#wp-calendar");
		if (!calendar) return;  // Exit if the calendar element is not found

		const calendarLinks = document.querySelectorAll("tbody td a");

		if (calendarLinks.length === 0) {
			console.warn("No calendar links found.");
			return;
		}

		calendarLinks.forEach(link => {
			link.classList.add("has-posts");
		});
	};

	/**
	 * Smoothly scrolls the page to the top when the "scroll-up" button is clicked.
	 */
	const scrollTop = () => {
		addEventListener("click", "#scroll-up", (event) => {
			event.preventDefault();
			document.documentElement.scrollIntoView({ behavior: "smooth" });
		});
	};
	const lazyLoadImages = (() => {
		let observer;

		return () => {
			if (!observer) {
				observer = new IntersectionObserver((entries, observer) => {
					entries.forEach(entry => {
						if (entry.isIntersecting) {
							const img = entry.target;
							img.src = img.dataset.src;
							observer.unobserve(img);
						}
					});
				}, { rootMargin: "0px 0px", threshold: 0 });
			}

			const images = document.querySelectorAll('.lazy');
			images.forEach(img => observer.observe(img));
		};
	})();
	// wowAnimation.js
	const initializeWOW = () => {
		const wow = new WOW({
			boxClass: "loading-animate",
			animateClass: "animated",
			offset: 0,
			mobile: true,
			live: true
		});
		wow.init();
	};

	// imageResize.js
	const imageResize = (parentNode) => {
		const items = document.querySelectorAll(parentNode);
		if (items.length === 0) {
			return;
		}
		const item = items[0];
		const offsetWidth = item.querySelector("img").offsetWidth;
		const offsetHeight = item.querySelector("img").offsetHeight;
		items.forEach((e) => {
			e.querySelector("img").style.width = offsetWidth + "px";
			e.querySelector("img").style.height = offsetHeight + "px";
		});
	};
	/**
	* code highlight
	*/
	const codeHighlight = () => {
		if (typeof hljs !== "undefined") {
			document.querySelectorAll("pre code").forEach((block) => {
				hljs.highlightBlock(block);
			});
		}
	}

	const offCanvasMenu = () => {
		const windowWidth = document.body.clientWidth;
		const offCanvasMenu = document.querySelector("#offcanvasMenu");

		if (windowWidth < 992) {
			offCanvasMenu.style.top = parseFloat(
				getComputedStyle(document.documentElement).marginTop
			) + "px";
		}
	}
	/**
	 * Toggles the "active" class on the navbar-toggler element when clicked.
	 */
	const navigationToggle = () => {
		addEventListener("click", ".navbar-toggler", (event, toggler) => {
			toggler.classList.toggle("active");
		});
	};
	const fadeOut = (element, duration) => {
		let opacity = 1; // 初始不透明度
		const interval = 50; // 每次更新的间隔时间（毫秒）
		const step = interval / duration; // 每次减少的透明度

		const fade = setInterval(() => {
			opacity -= step;
			if (opacity <= 0.5) {
				clearInterval(fade);
				opacity = 0.5; // 确保不透明度不会低于 0
			}
			element.style.opacity = opacity;
		}, interval);
	}
	const fadeIn = (element, duration) => {
		let opacity = 0.5; // 初始不透明度
		const interval = 50; // 每次更新的间隔时间（毫秒）
		const step = interval / duration; // 每次增加的透明度

		const fade = setInterval(() => {
			opacity += step;
			if (opacity >= 1) {
				clearInterval(fade);
				opacity = 1; // 确保不透明度不会高于 1
			}
			element.style.opacity = opacity;
		}, interval);
	}

	const loadPageService = new LoadPageService({
		apiUrl: lermData.url,
		containerId: "myTabContent",
		ignoreUrls: ["/page/", "/wp-admin/", "/wp-login.php"],
		errorText: "Failed to load the content. Please try again later."
	});

	DOMContentLoaded(() => {
		loadPageService.init();
		requestIdleCallback(() => {
			initializeWOW();
			lazyLoadImages();
			codeHighlight();
			calendarAddClass();
			offCanvasMenu();
			navigationToggle();
		});
		scrollTop();
		likeBtnHandle();
		loadMoreHanle();
		formAjaxHandle();
	})

	document.addEventListener('contentLoaded', () => {
		scrollTop();
		formAjaxHandle();
	});
})();
