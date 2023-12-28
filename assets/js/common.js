/**
 * Global Javascript Functions
 *
 * @Authors Lerm https://www.hanost.com
 * @Date    2016-04-17 22:02:49
 * @Version 2.0
 */
// (function (global, factory) {
//     typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
//     typeof define === 'function' && define.amd ? define(factory) :
//     (global.libName = factory());
// }(this, (function () { 'use strict';})));
(function () {


	/**
 * --------------------------------------------------------------------------
 * Bootstrap dom/data.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
 * --------------------------------------------------------------------------
 */

	/**
	 * Constants
	 */
	document.addEventListener("DOMContentLoaded", function (e) {
		// Initialize the WOW animation library
		const wow = new WOW({
			boxClass: "loading-animate",
			animateClass: "animated",
			offset: 0,
			mobile: true,
		});
		wow.init();

		// Get the `html` and `body` elements
		const html = document.documentElement;
		const body = document.body;

		// Resize images to fill the container
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

		//adminbar height
		// Get window width once
		const windowWidth = document.body.clientWidth;
		const siteHeader = document.querySelector("#site-header");
		const offCanvasMenu = document.querySelector("#offcanvasMenu");

		// Check if window width is less than 992px
		if (windowWidth < 992) {
			// Set off-canvas menu top to HTML margin top
			offCanvasMenu.style.top = parseFloat(
				getComputedStyle(document.documentElement).marginTop
			) + "px";
		}

		/**
		 * images lazyload
		 *
		 */
		let lazyLoadInstance = new LazyLoad({
			elements_selector: ".lazy",
			threshold: 0,
		});
		if (lazyLoadInstance) {
			lazyLoadInstance.update();
		}

		/**
		 * code highlight
		 *
		 */
		if ("undefined" !== typeof hljs) {
			document.querySelectorAll("pre code").forEach((block) => {
				hljs.highlightBlock(block);
			});
		}

		/**
		 * smooth scroll to top
		 *
		 */
		let scrollUp = document.getElementById("scroll-up");
		scrollUp.addEventListener("click", (e) => {
			e.preventDefault();
			animateScrolling({
				timing: (progress) => (html.scrollTop = html.scrollTop * (1 - progress)),
				draw: (timeFraction) => (1 - Math.sin(Math.acos(timeFraction))),
				duration: 700,
			});
		});
		/**
		 * ajax load more posts
		 *
		 * @since 3.2
		 */
		const likeBtn = document.querySelector(".like-button");
		const countEl = document.querySelector(".count");
		if (likeBtn) {
			let postID = likeBtn.dataset.id;
			let count = localStorage.getItem(`post_like_${postID}`);
			let sentRequest = false;

			if (count) {
				countEl.textContent = count;
				likeBtn.classList.add("done");
				likeBtn.disabled = true;
			}

			likeBtn.addEventListener("click", debounce(async (e) => {
				e.preventDefault();
				if (sentRequest) {
					return;
				}
				let params = new URLSearchParams({
					action: "post_like",
					security: adminajax.nonce,
					post_ID: postID
				});

				try {
					const response = await fetch(adminajax.url, {
						method: "POST",
						body: params,
					});
					const { success, data } = await response.json();

					if (success && (typeof data === 'number')) {
						localStorage.setItem(`post_like_${postID}`, data);
						countEl.textContent = data;
						sentRequest = true;
					} else {
						console.log(data);
					}
				} catch (error) {
					console.error(error);
				} finally {
					likeBtn.classList.add("done");
					likeBtn.disabled = true;
				}
			}, 500));
		}

		/**
		 * Ajax comment submission
		 *
		 * @since 3.2
		 */
		const commentForm = document.getElementById("commentform");

		const showError = (msg) => {
			message.innerHTML = `<strong><i class="fa fa-ok me-1"></i>${msg}</strong>`;
			message.classList.remove("visually-hidden");
			message.classList.add("shake");
			setTimeout(() => {
				message.classList.remove("shake")
				message.classList.add("visually-hidden");
			}, 3000);
		}

		const showSuccess = (msg) => {
			message.innerHTML = `<strong><i class="fa fa-xmark me-1"></i>${msg}</strong>`;
			message.classList.add("show");
			setTimeout(() => message.classList.remove("show"), 3000);
		}

		if (commentForm) {
			//error info display
			commentForm.insertAdjacentHTML("beforeend", '<div id="message" class="text-danger wow visually-hidden"></div>');

			const message = commentForm.querySelector("#message");
		
			//submit event
			commentForm.addEventListener("click", async (e) => {
				// ensure event target is submit button and within comment form
				if (e.target.type === "submit" && e.target.closest("#commentform")) {
					e.preventDefault();
					message.removeAttribute("style");
					message.classList.remove("shake", "fadeOut");
					message.innerHTML = '<strong><i class="fa fa-spinner fa-pulse me-1"></i>正在提交...</strong>';
					new WOW().init();
					// check user logged in.
					const commentData = new FormData(commentForm)
					if (!adminajax.loggedin) {

						if (!commentData.get('author')) {
							showError("请填写姓名");
							return;
						}
						if (!validateEmail(commentData.get('email'))) {
							showError("请填写正确的电子邮箱");
							return;
						}
					}
					if (!commentData.get('comment')) {
						showError("请输入评论内容");
						return;
					}

					// create URLSearchParams object and append necessary parameters
					const params = new URLSearchParams(commentData);
					params.append("action", "ajax_comment");
					params.append("security", adminajax.nonce);

					try {
						const response = await fetch(adminajax.url, {
							method: "POST",
							body: params,
						});
						const { success, data } = await response.json();
						if (success && data.length !== 0) {
							const commentNode = parseToDOM(data);
							const nodeLi = commentNode[1];
							const respond = document.getElementById("respond");

							e.target.setAttribute("disabled", "disabled");

							if (respond.parentNode.classList.contains("comment")) {
								// if there are existing child comments, append the new comment under the last child comment
								// otherwise, append the new comment directly under the original comment
								respond.previousElementSibling?.appendChild(nodeLi) ?? respond.parentNode.appendChild(nodeLi);

							} else if (document.querySelector(".comment-list")) {
								// add new comment using this method when there are already comments
								document.querySelector(".comment-list").insertBefore(nodeLi, document.querySelector(".comment-list").firstChild);
							} else {
								// use this method when there are no comments
								respond.parentNode.insertAdjacentHTML('beforeend', `
						<div class="card mb-3">
							<ol class="comment-list p-0 m-0 list-group list-group-flush">
								${nodeLi.outerHTML}
							</ol>
						</div>
					`);
							}
							showSuccess("评论已成功提交");
							commentForm.querySelector("#comment").value = "";
						} else {
							//throw commentNode;
							showError("评论提交失败，请刷新后重试");
							console.error(data);
						}
					} catch (error) {
						showError("评论提交失败，请刷新后重试！");
						console.log(error);
					} finally {
						e.target.removeAttribute("disabled");
						setTimeout(() => message.classList.remove("show"), 3000);
					};
				}
			});
		}

		loadMore();
		calendarAddClass();
	});
	/**
	 * Is the DOM ready
	 *
	 * this implementation is coming from https://gomakethings.com/a-native-javascript-equivalent-of-jquerys-ready-method/
	 *
	 * @param {Function} fn Callback function to run.
	 */
	function DomReady(fn) {
		if (typeof fn !== 'function') {
			return;
		}

		if (document.readyState === 'interactive' || document.readyState === 'complete') {
			return fn();
		}

		document.addEventListener('DOMContentLoaded', fn, false);
	}

	DomReady(function () {


	})

	const loadMore = () => {
		const loadMoreBtn = document.querySelector(".more-posts");
		const postsList = document.querySelector(".ajax-posts");

		if (loadMoreBtn === null) {
			//console.error("Can't find element with class '.more-posts'");
			return;
		}
		if (typeof loadMoreBtn != "undefined" && loadMoreBtn != null) {
			loadMoreBtn.addEventListener("click", debounce(async (e) => {
				e.preventDefault();
				loadMoreBtn.innerHTML = adminajax.loading;

				let currentPage = postsList.dataset.page;
				let maxPages = postsList.dataset.max;

				let params = new URLSearchParams({
					action: "load_more",
					query: adminajax.posts,
					security: adminajax.nonce,
					current_page: currentPage,
					max_pages: maxPages,
				});
				try {
					const response = await fetch(adminajax.url, {
						method: "POST",
						body: params,
					});

					const { success, data } = await response.json();

					if (success && data.length !== 0) {
						let newData = parseToDOM(data);

						postsList.dataset.page++;

						const fragment = document.createDocumentFragment();
						newData.forEach((e) => fragment.appendChild(e));
						postsList.appendChild(fragment);

						loadMoreBtn.textContent = adminajax.loadmore;
						loadMoreBtn.blur();
					} else {
						loadMoreBtn.textContent = adminajax.noposts;
						loadMoreBtn.blur();
						loadMoreBtn.disabled = true;
						loadMoreBtn.setAttribute("aria-disabled", "true");
					}
				} catch (error) {
					//console.error(error);
					loadMoreBtn.textContent = "出错啦，请刷新";
					loadMoreBtn.disabled = true;
					loadMoreBtn.setAttribute("aria-disabled", "true");
				}
			}, 500));
		}
	}

	//calendar add class
	const calendarAddClass = () => {
		const calendar = document.querySelector("#wp-calendar");
		if (calendar === null) {
			//console.error("Can't find element with id '#wp-calendar'");
			return;
		}
		const calendarLinks = calendar.querySelectorAll("tbody td a");

		calendarLinks.forEach((e) => {
			e.classList.add("has-posts");
		});
	}
	/**
	 *Parse to DOM Array
	 *
	 * @param {*} str
	 * @returns {array}
	 */
	const parseToDOM = (str) => {
		const div = document.createElement("div");
		if (typeof str === "string") {
			div.innerHTML = str;
		}
		return Array.from(div.childNodes);
	}
	/**
	 *
	 * @param {*} el
	 * @param {*} selector
	 */
	const matches = function (el, selector) {
		return (
			el.matches ||
			el.matchesSelector ||
			el.msMatchesSelector ||
			el.mozMatchesSelector ||
			el.webkitMatchesSelector ||
			el.oMatchesSelector
		).call(el, selector);
	};

	/**
	 *fade in and out
	 *
	 * @param {*} e
	 */
	const fadeIn = (element) => {
		element.style.opacity = 0;
		let opacity = 0;
		const duration = 400;
		const start = performance.now();
		function tick(now) {
			const elapsed = now - start;
			opacity = Math.min(1, opacity + elapsed / duration);
			element.style.opacity = opacity;
			if (opacity < 1) {
				(window.requestAnimationFrame && requestAnimationFrame(tick)) ||
					setTimeout(() => tick(performance.now()), 16);
			}
		}
		tick(start);
	}
	/**
	 * validate email
	 *
	 * @param {*} email
	 */
	const validateEmail = (email) => {
		const regExp = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,}$/;
		return regExp.test(email);
	}
	/**
	 * animate function
	 *
	 * @param {*} options
	 */

	let animateScrolling = (options) => {
		let startTime = performance.now();

		let animate = (currentTime) => {
			let timeFraction = (currentTime - startTime) / options.duration;
			if (timeFraction > 1) timeFraction = 1;

			let progress = options.timing(timeFraction);
			options.draw(progress);

			if (timeFraction < 1) {
				requestAnimationFrame(animate);
			}
		};
		requestAnimationFrame(animate);
	};





	/**
	 * Limit the frequency of calls to the click event handler function.
	 * @param {*} func
	 * @param {*} wait
	 * @returns
	 */
	function debounce(func, wait) {
		let timeout;
		return function (...args) {
			clearTimeout(timeout);
			timeout = setTimeout(() => {
				func.apply(this, args);
			}, wait);
		};
	}
})()