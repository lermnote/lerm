/**
 * Global Javascript Functions
 *
 * @Authors Lerm https://www.hanost.com
 * @Date    2016-04-17 22:02:49
 * @Version 2.0
 */
(function(){
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

	// Show dropdown menu on hover
	const dropdown = document.querySelectorAll(".dropdown");
	dropdown.forEach((e) => {
		e.addEventListener("mouseover", () => {
			e.querySelector(".dropdown-menu").classList.add("show");
		});
		e.addEventListener("mouseout",()=>{
			e.querySelector(".dropdown-menu").classList.remove("show");
		});
	});

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

	calendarAddClass;
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

	if (likeBtn && !likeBtn.classList.contains("done")) {
	  let postID = likeBtn.dataset.id;
	  let count = localStorage.getItem("count_" + postID);

	  if (count) {
		countEl.innerHTML = parseInt(count);
	  }

	  likeBtn.addEventListener("click", (e) => {
		e.preventDefault();

		if (!count) {
		  let params = new URLSearchParams();
		  params.append("action", "lerm_post_like");
		  params.append("security", adminajax.nonce);
		  params.append("post_ID", postID);

		  fetch(adminajax.url, {
			method: "POST",
			body: params,
		  })
			.then((response) => response.json())
			.then((data) => {
			  localStorage.setItem("count_" + postID, data);
			  countEl.innerHTML = parseInt(data);
			})
			.catch((err) => console.log(err.message));
		} else {
		  countEl.innerHTML = parseInt(count);
		}
		likeBtn.classList.add("done");
		likeBtn.disabled = true;
	  });
	}
	/**
	 * Ajax comment submission
	 *
	 * @since 3.2
	 */
	const commentForm = document.getElementById("commentform");

	const showError = (msg) => {
		message.innerHTML = `<strong><i class="fa fa-ok me-1"></i>${msg}</strong>`;
		message.classList.add("shake", "show");
		setTimeout(() => message.classList.remove("shake", "show"), 3000);
	}

	const showSuccess = (msg) => {
		message.innerHTML = `<strong><i class="fa fa-xmark me-1"></i>${msg}</strong>`;
		message.classList.add("show");
		setTimeout(() => message.classList.remove("show"), 3000);
	}

	if (commentForm) {
		//error info display
		commentForm.insertAdjacentHTML("beforeend", '<div id="message" class="text-danger wow"></div>');

		const message = commentForm.querySelector("#message");
		const author = commentForm.querySelector('[name="author"]');
		const email = commentForm.querySelector('[name="email"]');
		const url = commentForm.querySelector('[name="url"]');
		const comment = commentForm.querySelector('[name="comment"]');

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
				if (!adminajax.loggedin) {
					const formData = {
						author: author.value,
						email: email.value,
						url: url.value,
					};
					if (!formData.author) {
						showError("请填写姓名");
						return;
					}
					if (!validateEmail(formData.email)) {
						showError("请填写正确的电子邮箱");
						return;
					}
				}
				if (!comment.value) {
					showError("请输入评论内容");
					return;
				}

				// create URLSearchParams object and append necessary parameters
				const params = new URLSearchParams(new FormData(commentForm));
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
function isDomReady (fn) {
	if (typeof fn !== 'function') {
		return;
	}

	if (document.readyState === 'interactive' || document.readyState === 'complete') {
		return fn();
	}

	document.addEventListener('DOMContentLoaded', fn, false);
}

isDomReady(function () {
	
	
})

const loadMore = () =>{
	const loadMoreBtn = document.querySelector(".more-posts");

	if (loadMoreBtn === null) {
		console.error("Can't find element with class '.more-posts'");
		return;
	  }
	if (typeof loadMoreBtn != "undefined" && loadMoreBtn != null) {
		loadMoreBtn.addEventListener("click", (e) => {
			e.preventDefault();
			loadMoreBtn.innerHTML = adminajax.loading;
			let postsList = document.querySelector(".ajax-posts");
			let currentPage = postsList.dataset.page;
			let maxPages = postsList.dataset.max;

			let params = new URLSearchParams();
			params.append("action", "load_more");
			params.append("query", adminajax.posts);
			params.append("security", adminajax.nonce);
			params.append("current_page", currentPage);
			params.append("max_pages", maxPages);

			fetch(adminajax.url, {
				method: "POST",
				body: params,
			})
				.then((response) => {
					if (!response.ok) {
						throw new Error(`HTTP error! status: ${response.status}`);
					}
					return response.json();
				})
				.then((data) => {
					if (!data.success) {
						throw new Error(`Data request failed! Error: ${data.message}`);
					}
					if (currentPage == maxPages) {
						throw data;
					}
					if (data.success == true) {
						let newData = parseToDOM(data.data);
						postsList.dataset.page++;
						newData.forEach((e) => {
							//loadMorePosts.dataset.page = '/page/' + adminajax.current;
							document
								.querySelector(".ajax-posts")
								.appendChild(e);
						});
						//history.replaceState(null, null, '?paged=' + adminajax.current)
					}
					loadMoreBtn.innerHTML = adminajax.loadmore;
					loadMoreBtn.blur();

				})
				.catch((err) => {
					//console.log(err);
					loadMoreBtn.innerHTML = adminajax.noposts;
					loadMoreBtn.blur();
					loadMoreBtn.setAttribute("disabled", "true");
					loadMoreBtn.setAttribute("aria-disabled", "true");
				});
		});
	}
}

//calendar add class
const calendarAddClass = () => {
	const calendar = document.querySelector("#wp-calendar");
	if (calendar === null) {
		console.error("Can't find element with id '#wp-calendar'");
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
 * @param {*} string
 * @returns {array}
 */
const parseToDOM = function (string) {
	let div = document.createElement("div");
	if (typeof string == "string") div.innerHTML = string;
	return Array.prototype.slice.call(div.childNodes);
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
function fadeIn (e) {
	e.style.opacity = 0;

	var last = +new Date();
	var tick = function () {
		e.style.opacity = +e.style.opacity + (new Date() - last) / 400;
		last = +new Date();

		if (+e.style.opacity < 1) {
			(window.requestAnimationFrame && requestAnimationFrame(tick)) ||
				setTimeout(tick, 16);
		}
	};
	tick();
}

/**
 * validate email
 *
 * @param {*} email
 */
const validateEmail = function (email) {
	var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(String(email).toLowerCase());
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
})()