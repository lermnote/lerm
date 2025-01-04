// (function () {
// 	'use strict';
// 	const domain_name = `${window.location.protocol}//${window.location.host}`;
// 	const ajaxcontent = 'page';
// 	const ajaxsearch_class = 'search-form';
// 	const ajaxignore = ''.split(', ');
// 	const ajaxloading_error_code = 'error';

// 	let ajaxStarted = false;
// 	let ajaxLoading = false;
// 	let ajaxSearchPath = null;

// 	if (!document.getElementById(ajaxcontent)) return;

// 	window.onpopstate = () => {
// 		if (ajaxStarted && ajaxCheckIgnore(window.location.toString())) {
// 			loadPage(window.location.toString(), true);
// 		}
// 	};

// 	const initPageLoading = () => {
// 		document.querySelectorAll('a').forEach(link => {
// 			link.addEventListener('click', async (event) => {
// 				if (link.href.startsWith(domain_name) && ajaxCheckIgnore(link.href)) {
// 					event.preventDefault();
// 					try {
// 						ajaxClickCode(link);
// 						await loadPage(link.href);
// 					} catch (err) {
// 						console.error(err);
// 					}
// 				}
// 			});
// 		});

// 		document.querySelectorAll(`.${ajaxsearch_class}`).forEach(form => {
// 			if (form.action) {
// 				ajaxSearchPath = form.action;
// 				form.addEventListener('submit', event => {
// 					event.preventDefault();
// 					submitSearch(new URLSearchParams(new FormData(form)).toString());
// 				});
// 			}
// 		});
// 	};

// 	const loadPage = async (url, push, getData = null) => {
// 		if (ajaxLoading) return;
// 		ajaxLoading = true;
// 		ajaxStarted = true;

// 		if (!push && history.pushState) {
// 			history.pushState({ foo: Math.random() * 1001 }, 'ajax page loaded...', new URL(url).pathname);
// 		}

// 		const container = document.getElementById(ajaxcontent);
// 		container.style.opacity = '0.5';

// 		try {
// 			const response = await fetch(url, {
// 				method: 'GET',
// 				headers: { 'X-Requested-With': 'XMLHttpRequest' }
// 			});
// 			if (!response.ok) throw new Error('Network response was not ok');

// 			const data = await response.text();

// 			const titleMatch = data.match(/<title>(.*?)<\/title>/i);
// 			console.log(titleMatch);
// 			if (titleMatch) {
// 				const tempDiv = document.createElement('div');
// 				tempDiv.innerHTML = titleMatch[1];
// 				document.title = tempDiv.textContent;
// 			}

// 			const newContent = new DOMParser().parseFromString(data, 'text/html').getElementById(ajaxcontent);
// 			if (newContent) container.innerHTML = newContent.innerHTML;

// 			scrollTop();
// 			container.style.opacity = '1';
// 			initPageLoading();
// 			ajaxReloadCode();
// 		} catch (error) {
// 			document.title = 'Error loading requested page!';
// 			container.innerHTML = ajaxloading_error_code;
// 			console.error('Fetch operation error:', error);
// 		} finally {
// 			ajaxLoading = false;
// 		}
// 	};

// 	const submitSearch = params => {
// 		if (!ajaxLoading) loadPage(ajaxSearchPath, false, params);
// 	};

// 	const ajaxCheckIgnore = url => ajaxignore.some(ignore => url.includes(ignore));

// 	const ajaxReloadCode = () => {
// 		document.querySelectorAll('.mod-index__feature .img_list_6pic a').forEach(el => el.classList.remove('word_display'));
// 		const rightContainer = document.getElementById('continar-right');
// 		if (rightContainer) {
// 			Object.assign(rightContainer.style, { position: 'static', bottom: 'auto', left: 'auto' });
// 		}
// 	};

// 	const ajaxClickCode = el => {
// 		document.querySelectorAll('ul.nav li').forEach(item => item.classList.remove('current-menu-item'));
// 		const parentLi = el.closest('li');
// 		if (parentLi) parentLi.classList.add('current-menu-item');
// 	};
// 	/**
// 	* --------------------------------------------------------------------------
// 	* Lerm Theme BaseService
// 	* --------------------------------------------------------------------------
// 	*/
// 	class BaseService {
// 		constructor(apiUrl) {
// 			this.apiUrl = apiUrl;
// 		}

// 		fetchData = async ({ url, method = 'GET', body = {}, headers = {} }) => {
// 			try {
// 				const options = {
// 					method,
// 					headers: { ...headers },
// 					body: method !== 'GET' ? body : null,
// 				};

// 				const response = await fetch(url, options);

// 				if (!response.ok) {
// 					throw new Error(`Error: ${response.status} - ${response.statusText}`);
// 				}

// 				return await response.json();
// 			} catch (error) {
// 				this.handleError(error);
// 				throw error;
// 			}
// 		}

// 		handleError = (error) => {
// 			console.error("An error occurred:", error.message);
// 			alert(`An error occurred: ${error.message}`);
// 		}
// 		rateLimit = (func, wait, isThrottle = false) => {
// 			let timeout, lastTime = 0;
// 			return (...args) => {
// 				const context = this;
// 				const now = Date.now();
// 				const later = () => {
// 					timeout = null;
// 					if (!isThrottle) func.apply(context, args);
// 				};
// 				const remaining = wait - (now - lastTime);
// 				if (isThrottle && remaining <= 0) {
// 					clearTimeout(timeout);
// 					timeout = null;
// 					lastTime = now;
// 					func.apply(context, args);
// 				} else if (!timeout) {
// 					timeout = setTimeout(later, isThrottle ? remaining : wait);
// 				}
// 			};
// 		}

// 		addGlobalEventListener = (type, selector, callback) => {
// 			document.addEventListener(type, event => {
// 				const targetElement = event.target.closest(selector);
// 				if (targetElement && targetElement.matches(selector)) {
// 					callback(event, targetElement);
// 				}
// 			});
// 		}
// 		displayMessage = (message, type = 'info', duration = 5000) => {
// 			if (!this.messageId) return;

// 			const messageElement = document.getElementById(this.messageId);
// 			if (messageElement) {
// 				messageElement.innerHTML = message;
// 				messageElement.classList.add(`text-${type}`);
// 				messageElement.classList.remove('invisible');
// 				clearTimeout(this.messageTimeout);
// 				this.messageTimeout = setTimeout(() => {
// 					messageElement.classList.add('invisible');
// 					messageElement.classList.remove(`text-${type}`);
// 				}, duration);
// 			}
// 		}
// 		toggleButton = (button, isLoading, diabled = false) => {
// 			if (isLoading) {
// 				button.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> ');

// 			} else {

// 				const tempElement = document.querySelector('.spinner-border');
// 				if (tempElement) {
// 					tempElement.remove();
// 				}
// 			}
// 			if (diabled === false) {
// 				button.removeAttribute('disabled');
// 			};
// 		}
// 	}
// 	/**
// 	 * --------------------------------------------------------------------------
// 	 * Lerm Theme ClickService
// 	 * --------------------------------------------------------------------------
// 	 */
// 	class ClickService extends BaseService {
// 		constructor({ apiUrl, selector, action, security, url, headers = {}, additionalData = {}, isThrottled = false, cacheExpiryTime = 60000,
// 			enableCache = true }) {
// 			super(apiUrl);
// 			this.selector = selector;
// 			this.action = action;
// 			this.security = security;
// 			this.url = url;
// 			this.headers = headers;
// 			this.additionalData = additionalData;
// 			this.cacheExpiryTime = cacheExpiryTime;
// 			this.enableCache = enableCache;

// 			const clickHandler = this.handleClick;

// 			this.clickHandler = isThrottled
// 				? this.rateLimit(clickHandler, 1000, true)
// 				: clickHandler;

// 			this.addGlobalEventListener('click', this.selector, this.clickHandler);
// 		}

// 		handleClick = async (event, target) => {
// 			event.preventDefault()

// 			this.beforeClick(event);

// 			let requestData = new URLSearchParams({
// 				action: this.action,
// 				security: this.security,
// 				...target.dataset,
// 				...this.additionalData
// 			});

// 			const cacheKey = `click_action_${this.selector}`;

// 			if (this.enableCache && this.isCacheValid(cacheKey)) {
// 				const cachedResponse = localStorage.getItem(cacheKey);
// 				this.afterClickSuccess(JSON.parse(cachedResponse), target);
// 			} else {
// 				this.toggleButton(target, true);
// 				try {
// 					const response = await this.submitClickData(requestData);
// 					if (this.enableCache) {
// 						this.cacheResponse(cacheKey, response);
// 					}
// 					if (response.success) {
// 						this.afterClickSuccess(response.data, target);
// 					} else {
// 						throw new Error(response.data || 'Unknown error occurred');
// 					}

// 				} catch (error) {
// 					this.afterClickFail(error, target);
// 				}
// 				this.toggleButton(target, false);
// 			}
// 		}
// 		// 提交点击数据
// 		submitClickData = async (data) => {
// 			return await this.fetchData({
// 				url: this.url,
// 				method: 'POST',
// 				body: data,
// 				headers: this.headers,
// 			});
// 		}

// 		beforeClick = () => { console.log('Processing click...'); }

// 		getRequsetData = () => {
// 			return new URLSearchParams({
// 				action: this.action,
// 				security: this.security,
// 				...this.additionalData
// 			});
// 		}
// 		afterClickSuccess = (response, target) => {
// 			this.displayMessage('Click action was successful!');
// 			console.log('Response:', response);
// 		}

// 		afterClickFail = (error, target) => {
// 			this.displayMessage('Failed to process click action.');
// 			console.error('Error:', error);
// 			target.setAttribute('disabled', 'disabled');
// 			target.innerHTML = error.message
// 		}
// 		// 检查缓存是否有效
// 		isCacheValid = (cacheKey) => {
// 			const cachedTime = localStorage.getItem(`${cacheKey}_time`);
// 			if (!cachedTime) return false;
// 			const currentTime = Date.now();
// 			return currentTime - cachedTime < this.cacheExpiryTime;
// 		}

// 		// 缓存响应并记录缓存时间
// 		cacheResponse = (cacheKey, response) => {
// 			localStorage.setItem(cacheKey, JSON.stringify(response));
// 			localStorage.setItem(`${cacheKey}_time`, Date.now());
// 		}
// 	}

// 	/**
// 	* --------------------------------------------------------------------------
// 	* Lerm Theme Like button
// 	* --------------------------------------------------------------------------
// 	*/
// 	const likeBtnSuccess = (data, target) => {
// 		const { id, type } = target.dataset;
// 		const buttons = document.querySelectorAll(`.like-${type}-${id}`);
// 		const status = data.status;
// 		const count = data.count;

// 		buttons.forEach(button => {
// 			button.classList.toggle('liked', status === 'liked');
// 			button.querySelector('.count').textContent = count;
// 			button.setAttribute('title', status === 'liked' ? 'unlike' : 'like');
// 		});
// 	}
// 	const likeBtnHandle = () => {
// 		const postLikeConfig = {
// 			selector: '.like-button',
// 			action: 'post_like',
// 			security: lermData.like_nonce,
// 			url: lermData.url,
// 			isThrottled: true,
// 			cacheExpiryTime: 60000,
// 			enableCache: false
// 		};

// 		const postLike = new ClickService(postLikeConfig);
// 		postLike.afterClickSuccess = likeBtnSuccess;
// 	}

// 	/**
// 	* --------------------------------------------------------------------------
// 	* Lerm Theme Load More button
// 	* --------------------------------------------------------------------------
// 	*/
// 	const appendPostsToDOM = (data) => {
// 		const loadMoreBtn = document.querySelector(".more-posts");
// 		const postsList = document.querySelector(".ajax-posts");
// 		if (postsList) {
// 			postsList.insertAdjacentHTML('beforeend', data.content);
// 		}

// 		loadMoreBtn.dataset.currentPage = data.currentPage;
// 	};
// 	const loadMoreHanle = () => {
// 		// Load more button setup
// 		const loadMore = new ClickService({
// 			selector: '.more-posts',
// 			action: 'load_more',
// 			security: lermData.nonce,
// 			url: lermData.url,
// 			additionalData: { query: lermData.posts },
// 			isThrottled: true,
// 			cacheExpiryTime: 60000,
// 			enableCache: false
// 		});
// 		loadMore.afterClickSuccess = appendPostsToDOM;
// 	}
// 	/**
// 	 * --------------------------------------------------------------------------
// 	 * Lerm Theme FormService
// 	 * --------------------------------------------------------------------------
// 	 */
// 	const validationRules = {
// 		email: {
// 			pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
// 			message: 'Invalid email format',
// 		},
// 		username: {
// 			minLength: 3,
// 			errorMessage: {
// 				minLength: 'Register must be at least {minLength} characters long.',
// 			}
// 		},
// 		author: {
// 			minLength: 3,
// 			errorMessage: {
// 				minLength: 'Comment username must be at least {minLength} characters long.',
// 			}
// 		},
// 		regist_password: {
// 			minLength: 8,
// 			hasUppercase: /[A-Z]/,
// 			hasNumber: /\d/,
// 			hasSpecialChar: /[!@#$%^&*]/,
// 			message: 'Password must be at least 8 characters long, include one uppercase letter, one number, and one special character.',
// 			errorMessage: {
// 				minLength: 'Password must be at least {minLength} characters long.',
// 				hasUppercase: 'Password must contain at least one uppercase letter.',
// 				hasNumber: 'Password must contain at least one number.',
// 				hasSpecialChar: 'Password must contain at least one special character.',
// 			}
// 		},
// 		confirm_password: {
// 			match: 'regist_password',
// 			message: 'Passwords do not match'
// 		},
// 		comment: {
// 			minLength: 6,
// 			message: 'Textarea must be at least 10 characters long',
// 			errorMessage: {
// 				minLength: 'Comment textarea must be at least {minLength} characters long.',
// 			}
// 		}
// 	};

// 	const validateField = (field, rules) => {
// 		const rule = rules[field.name];
// 		const value = field.value;

// 		if (!rule) return { valid: true };

// 		const { pattern, minLength, hasUppercase, hasNumber, hasSpecialChar, match, errorMessage } = rule;
// 		if (pattern && !pattern.test(value)) {
// 			return { valid: false, message: rule.message || 'Invalid format' };
// 		}
// 		if (minLength && value.length < minLength) {
// 			return { valid: false, message: errorMessage.minLength.replace('{minLength}', minLength) };
// 		}
// 		if (hasUppercase && !hasUppercase.test(value)) {
// 			return { valid: false, message: errorMessage.hasUppercase };
// 		}
// 		if (hasNumber && !hasNumber.test(value)) {
// 			return { valid: false, message: errorMessage.hasNumber };
// 		}
// 		if (hasSpecialChar && !hasSpecialChar.test(value)) {
// 			return { valid: false, message: errorMessage.hasSpecialChar };
// 		}
// 		if (match && value !== formValues[match]) {
// 			return { valid: false, message: rule.message || 'Values do not match' };
// 		}
// 		return { valid: true };
// 	};
// 	const togglePasswordVisibility = (passwordFields, toggleElement) => {
// 		passwordFields.forEach(field => {
// 			const isPasswordVisible = field.type === 'password';
// 			field.type = isPasswordVisible ? 'text' : 'password';
// 		});
// 		toggleElement.innerText = passwordFields[0].type === 'password' ? 'show' : 'hide';
// 	};

// 	class FormService extends BaseService {
// 		constructor({ apiUrl, formId, url, action, security, headers = {}, messageId, passwordToggle = false }) {
// 			super(apiUrl);
// 			this.formId = formId;
// 			this.url = url;
// 			this.action = action;
// 			this.security = security;
// 			this.headers = headers;
// 			this.messageId = messageId;
// 			this.passwordToggle = passwordToggle;

// 			const form = document.getElementById(this.formId);
// 			if (form) {
// 				this.form = form;
// 				this.init();
// 			}
// 		}

// 		init = () => {
// 			document.addEventListener('submit', event => {
// 				const form = event.target;
// 				if (form.id === this.formId) {
// 					event.preventDefault();
// 					this.handleFormSubmit();
// 				}
// 			});
// 			if (this.passwordToggle) {
// 				const toggleElement = document.getElementById(`${this.formId}-toggle`);
// 				const passwordFields = Array.from(this.form.querySelectorAll('input[type="password"]'));
// 				toggleElement?.addEventListener('click', () => togglePasswordVisibility(passwordFields, toggleElement));
// 			}
// 		}

// 		handleFormSubmit = async () => {
// 			const isValid = this.validateForm();
// 			if (!isValid) return;

// 			const submitButton = this.form.querySelector('button[type="submit"]');
// 			if (submitButton.disabled) return;

// 			this.toggleButton(submitButton, true);
// 			const formData = this.getFormData();

// 			this.beforeSubmit();
// 			try {
// 				const response = await this.fetchData({
// 					url: this.url,
// 					method: 'POST',
// 					body: formData,
// 					headers: this.headers
// 				});
// 				this.handleSubmitResponse(response);
// 			} catch (error) {
// 				this.displayMessage(error.message, 'danger');
// 				this.afterSubmitFail(error);
// 			} finally {
// 				this.toggleButton(submitButton, false);
// 			}
// 		}

// 		validateForm = () => {
// 			const fields = this.form.querySelectorAll('input, textarea, select');
// 			let isFormValid = true;

// 			fields.forEach(field => {
// 				const { valid, message } = validateField(field, validationRules);
// 				if (!valid) {
// 					field.classList.add('is-invalid');
// 					this.displayMessage(message, 'danger');
// 					isFormValid = false;
// 				} else {
// 					field.classList.remove('is-invalid');
// 				}
// 			});

// 			return isFormValid;
// 		};

// 		getFormData () {
// 			const formData = new FormData(this.form);
// 			formData.append('action', this.action);
// 			formData.append('security', this.security);
// 			return formData;
// 		}

// 		handleSubmitResponse = (response) => {
// 			if (response.success) {
// 				this.form.reset();
// 				this.afterSubmitSuccess(response.data);
// 				this.displayMessage('Form submitted successfully!', 'success');
// 			} else {
// 				throw new Error(response.data || 'Unknown error occurred');
// 			}
// 		}
// 		beforeSubmit = () => { }
// 		afterSubmitSuccess = (_response) => { }
// 		afterSubmitFail = (_error) => { }
// 	}

// 	/**
// 	 * Handles the successful submission of a comment by adding the new comment to the comment list.
// 	 *
// 	 * @param {Object} data - The data returned after a successful comment submission.
// 	 */
// 	const handleCommentSuccess = (data) => {
// 		const respond = document.getElementById("respond");
// 		const commentList = document.querySelector(".comment-list");
// 		const isParentComment = data.comment.comment_parent === '0';

// 		const nodeLi = `
//         <li class="${data.comment.comment_type} list-group-item${!isParentComment ? ' p-0' : ''}" id="comment-${data.comment.comment_ID}">
//             <article id="div-comment-${data.comment.comment_ID}" class="comment-body">
//                 <footer class="comment-meta mb-1">
//                     <span class="comment-author vcard">
//                         <img src="${data.avatar_url}" srcset="${data.avatar_url} 2x" alt="${data.comment.comment_author}" class="avatar avatar-${data.avatar_size} photo" height="${data.avatar_size}" width="${data.avatar_size}" loading="lazy" decoding="async">
//                         <b class="fn">${data.comment.comment_author}</b>
//                     </span>
//                     <span class="comment-metadata">
//                         <span aria-hidden="true">•</span>
//                         <a href="http://localhost/wordpress/${data.comment.comment_post_ID}/#comment-${data.comment.comment_ID}">
//                             <time datetime="${data.comment.comment_date_gmt}">${data.comment.comment_date}</time>
//                         </a>
//                     </span>
//                 </footer>
//                 ${data.comment.comment_approved === '0' ? `<span class="comment-awaiting-moderation badge rounded-pill bg-info">您的评论正在等待审核。</span>` : ''}
//                 <section class="comment-content" style="margin-left: 56px">
//                     <p>${data.comment.comment_content}</p>
//                 </section>
//             </article>
//         </li>
//     `;

// 		const fragment = document.createDocumentFragment();

// 		if (commentList) {
// 			const previousElement = respond.previousElementSibling;
// 			if (previousElement) {
// 				const lastChild = previousElement.lastElementChild;

// 				if (lastChild && lastChild.classList.contains("children")) {
// 					lastChild.insertAdjacentHTML('beforeend', nodeLi);
// 				} else {
// 					const childrenUl = document.createElement('ul');
// 					childrenUl.classList.add('children');
// 					childrenUl.innerHTML = nodeLi;
// 					previousElement.appendChild(childrenUl);
// 				}
// 			} else {
// 				commentList.insertAdjacentHTML('afterbegin', nodeLi);
// 			}
// 		} else {
// 			const newCard = document.createElement('div');
// 			newCard.classList.add('card', 'mb-3');
// 			newCard.innerHTML = `
//             <ol class="comment-list p-0 m-0 list-group list-group-flush">
//                 ${nodeLi}
//             </ol>
//         `;
// 			fragment.appendChild(newCard);
// 			respond.parentNode.appendChild(fragment);
// 		}
// 	};
// 	const handleLoginSuccess = () => {
// 		loadPage(lermData.frontDoor);
// 	}
// 	const formAjaxHandle = () => {
// 		const formConfigs = [
// 			{ formId: 'login', action: lermData.login_action, security: lermData.login_nonce },
// 			{ formId: 'reset', action: lermData.reset_action, security: lermData.reset_nonce },
// 			{ formId: 'regist', action: lermData.regist_action, security: lermData.regist_nonce, passwordToggle: true },
// 			{ formId: 'commentform', action: lermData.comment_action, security: lermData.comment_nonce },
// 		];

// 		formConfigs.forEach(config => {
// 			const FormHandle = new FormService({
// 				...config,
// 				url: lermData.url,
// 				messageId: `${config.formId}-msg`,
// 			});
// 			if (config.formId === 'commentform') FormHandle.afterSubmitSuccess = handleCommentSuccess;
// 			if (config.formId === 'login') FormHandle.afterSubmitSuccess = handleLoginSuccess;
// 		});
// 	};

// 	const DOMContentLoaded = callback => {
// 		if (document.readyState === "loading") {
// 			document.addEventListener("DOMContentLoaded", callback, { once: true });
// 		} else {
// 			callback();
// 		}
// 	};



// 	//smooth scroll to top
// 	const scrollTop = () => {
// 		let scrollUp = document.getElementById("scroll-up");
// 		scrollUp.addEventListener("click", (e) => {
// 			e.preventDefault();
// 			document.documentElement.scrollIntoView({ behavior: 'smooth' });
// 		});
// 	}

// 	// wowAnimation.js
// 	const initializeWOW = () => {
// 		const wow = new WOW({
// 			boxClass: "loading-animate",
// 			animateClass: "animated",
// 			offset: 0,
// 			mobile: true,
// 		});
// 		wow.init();
// 	};

// 	DOMContentLoaded(() => {
// 		requestIdleCallback(() => {
// 			initializeWOW();
// 		});
// 		scrollTop();
// 		likeBtnHandle();
// 		loadMoreHanle();
// 		formAjaxHandle();
// 		initPageLoading();
// 	})
// })();