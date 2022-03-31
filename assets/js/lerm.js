/**
 * Global Javascript Functions
 *
 * @Authors Lerm https://www.hanost.com
 * @Date    2016-04-17 22:02:49
 * @Version 2.0
 */

// (function () {
// 	"use strict";

// 	//archives page expand
// 	let archive = $("#archives");
// 	let monthList = $(".month-list", archive);
// 	let monthPostList = $(".month-post-list", archive);
// 	let postList = $(".post-list", archive);
// 	let postListFirst = $(".post-list:first", archive);
// 	postList.hide();
// 	postListFirst.show();
// 	monthList.first().show();
// 	monthPostList.css("cursor", "s-resize").on("click", function () {
// 		$(this).next().slideToggle(400);
// 	});
// 	var animate = function (index, status, s) {
// 		if (index > postList.length) {
// 			return;
// 		}
// 		if (status == "up") {
// 			postList.eq(index).slideUp(s, function () {
// 				animate(index + 1, status, s - 10 < 1 ? 0 : s - 10);
// 			});
// 		} else {
// 			postList.eq(index).slideDown(s, function () {
// 				animate(index + 1, status, s - 10 < 1 ? 0 : s - 10);
// 			});
// 		}
// 	};
// 	$("#al_expand_collapse").on("click", function (e) {
// 		e.preventDefault();
// 		if ($(this).data("s")) {
// 			$(this).data("s", "");
// 			animate(0, "up", 100);
// 		} else {
// 			$(this).data("s", 1);
// 			animate(0, "down", 100);
// 		}
// 	});

// 	$(document).on("click", '[data-toggle="lightbox"]', function (e) {
// 		// console.log(e)
// 		e.preventDefault();
// 		// console.log($(this))
// 		return $(this).ekkoLightbox();
// 	});
// })($);

document.addEventListener("DOMContentLoaded", function (e) {
	let wow = new WOW({
		boxClass: "loading-animate",
		animateClass: "animated",
		offset: 0,
		mobile: true,
	});
	wow.init();
	/**
	 * global variable
	 *
	 */
	let html = document.documentElement;
	let body = document.body;

	// image resize to fill full container
	imageResize = (parentNode) => {
		let item = document.querySelector(parentNode);
		if (item) {
			let items = document.querySelectorAll(parentNode);
			// let naturalWidth = item.querySelector("img").naturalWidth;
			// let naturalHeight = item.querySelector("img").naturalHeight;
			let offsetWidth = item.querySelector("img").offsetWidth;
			let offsetHeight = item.querySelector("img").offsetHeight;
			// console.log(naturalWidth, naturalHeight, offsetWidth, offsetHeight);
			items.forEach((e) => {
				e.querySelector("img").style.width = offsetWidth + "px";
				e.querySelector("img").style.height = offsetHeight + "px";
			});
		}
	};
	/**
	 * carousel image height
	 *
	 */
	// imageResize(".carousel-item");
	// imageResize(".thumbnail-wrap");
	// let carouselItem  = document.querySelector('.thumbnail-wrap');
	// if( carouselItem ) {
	// 	let carouselItems = document.querySelectorAll('.thumbnail-wrap');
	// 	let naturalWidth = carouselItem.querySelector('img').naturalWidth;
	// 	let naturalHeight = carouselItem.querySelector('img').naturalHeight;
	// 	let offsetWidth = carouselItem.querySelector('img').offsetWidth;
	// 	carouselItems.forEach(e=>{
	// 		e.querySelector('img').style.width  = offsetWidth + 'px'
	// 		e.querySelector('img').style.height = naturalHeight / naturalWidth * offsetWidth + 'px';
	// 	})
	// }

	/**
	 * Dropdown menu hover show
	 *
	 */
	let dropdown = document.querySelectorAll(".dropdown");
	if (dropdown) {
		dropdown.forEach((e) => {
			e.onmouseover = () =>
				e.querySelector(".dropdown-menu").classList.add("show");
			e.onmouseout = () =>
				e.querySelector(".dropdown-menu").classList.remove("show");
		});
	}

	//adminbar height
	let windowWidth = document.body.clientWidth;
	let siteHeader = body.querySelector("#site-header");
	let offcanvasMenu = document.querySelector("#offcanvasMenu");

	if (windowWidth < 992) {
		offcanvasMenu.style.top = parseFloat(document.defaultView.getComputedStyle(html, null).marginTop) + 'px';
	}

	/**
	 * calendar add class
	 *
	 */
	document.querySelectorAll("#wp-calendar tbody td a").forEach((e) => {
		e.classList.add("has-posts");
	});

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
	// let favicon = getFaviconURLs('https://cdn.jsdelivr.net/npm/@meltwater/fetch-favicon@1.0.4/dist/index.min.js')
	// console.log(favicon);
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
		animate({
			timing: (progress) => (html.scrollTop = html.scrollTop * (1 - progress)),
			draw: circ,
			duration: 700,
		});
	});
	let circ = (timeFraction) => (1 - Math.sin(Math.acos(timeFraction)));

	/**
	 * ajax load more posts
	 *
	 * @since 3.2
	 */

	const loadMoreBtn = document.querySelector(".more-posts");
	if (typeof loadMoreBtn != "undefined" && loadMoreBtn != null) {
		loadMoreBtn.addEventListener("click", (e) => {
			e.preventDefault();
			loadMoreBtn.innerHTML = adminajax.loading;
			let postsList = document.querySelector(".ajax-posts");
			let currentPage = postsList.dataset.page;
			let maxPages = postsList.dataset.max;

			let params = new URLSearchParams();
			params.append("action", "load_more");
			params.append("security", adminajax.nonce);
			params.append("current_page", currentPage);
			params.append("max_pages", maxPages);

			fetch(adminajax.url, {
				method: "POST",
				body: params,
			})
				.then((response) => response.json())
				.then((data) => {
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

	/**
	 * Ajax like post;
	 */

	let likeBtn = document.querySelector(".like-button");

	if (typeof likeBtn != "undefined" && likeBtn != null && likeBtn.classList.contains("done") == false) {
		let postID = likeBtn.dataset.id;

		let params = new URLSearchParams();
		params.append("action", "lerm_post_like");
		params.append("security", adminajax.nonce);
		params.append("post_ID", postID);

		likeBtn.addEventListener("click", (e) => {
			e.preventDefault();
			fetch(adminajax.url, {
				method: "POST",
				body: params,
			})
				.then((response) => response.json())
				.then((data) => {
					// console.log(data);
					likeBtn.classList.add("done");
					likeBtn.disabled = true;
					document.querySelector(".count").innerHTML = parseInt(data);
				})
				.catch((err) => console.log(err.message));
		});
	}

	/**
	 * Ajax comment submission
	 *
	 * @since 3.2
	 */
	let commentForm = document.getElementById("commentform");
	if (commentForm) {
		//error info display
		commentForm.insertAdjacentHTML("beforeend", '<div id="error" class="text-danger wow"></div>');
		let errInfo = commentForm.querySelector("#error");
		let author = commentForm.querySelector('[name="author"]');
		let email = commentForm.querySelector('[name="email"]');
		let url = commentForm.querySelector('[name="url"]');
		let comment = commentForm.querySelector('[name="comment"]');

		errInfo.style.visibility = "hidden";
		errInfo.innerHTML = "#";

		//submit event
		commentForm.addEventListener("submit", (e) => {
			e.preventDefault();
			errInfo.removeAttribute("style");
			errInfo.classList.remove("shake");
			errInfo.classList.remove("fadeOut");
			errInfo.innerHTML = '<strong><i class="fa fa-spinner fa-pulse me-1"></i>正在提交...</strong>';
			new WOW().init();

			// check user logged in.
			if (!adminajax.loggedin) {
				let formData = {
					author: author.value,
					email: email.value,
					url: url.value,
				};
				if (!formData.author) {
					errInfo.innerHTML = "<strong>错误：</strong>请填写姓名";
					errInfo.classList.add("shake");
					return;
				}

				if (!validateEmail(formData.email)) {
					errInfo.innerHTML = "<strong>错误：</strong>请填写正确的电子邮箱";
					errInfo.classList.add("shake");
					return;
				}
			}

			if (!comment.value) {
				errInfo.innerHTML = "<strong>错误：</strong>请输入评论内容";
				errInfo.classList.add("shake");
				return;
			}

			let params = new URLSearchParams(new FormData(commentForm));
			params.append("action", "ajax_comment");
			params.append("security", adminajax.nonce);
			// console.log(params);
			fetch(adminajax.url, {
				method: "POST",
				body: params,
			})
				.then((response) => response.json())
				.then((data) => {
					// console.log(data);
					let nodeArray = parseToDOM(data.data);

					if (data.success == true && data.data.length != 0) {
						nodeLi = nodeArray[1];
						commentForm.querySelector('[type="submit"]').setAttribute("disabled", "disabled");
						let respond = document.getElementById("respond");
						if (respond.parentNode.classList.contains("comment")) {
							//回复原评论
							if (null !== respond.previousElementSibling) {
								//这个if是为了回复评论时在原评论下面追加子评论
								//previousElementSibling指的是上一个兄弟元素节点
								respond.previousElementSibling.appendChild(nodeLi);
							} else {
								//无子评论时使用此方法
								respond.parentNode.appendChild(nodeLi);
							}
						} else if (document.querySelector(".comment-list")) {
							//增加新评论时使用此方法
							document.querySelector(".comment-list").insertBefore(nodeLi,document.querySelector(".comment-list").firstChild);
						} else {
							//无评论是使用此方法

							let div = document.createElement('div');
							let ol = document.createElement('ol');

							div.classList = 'card mb-3';
							ol.classList = 'comment-list p-0 m-0 list-group list-group-flush';

							ol.appendChild(nodeLi);
							div.appendChild(ol);

							respond.parentNode.appendChild(div);
						}
					} else {
						// 若success:false,则抛出错误；
						throw nodeArray;
					}
				})
				.then(() => {
					commentForm.querySelector("#comment").value = "";
					errInfo.innerHTML = '<strong><i class="fa fa-ok me-1"></i>提交成功</strong>';
					let fadeOut = setTimeout(function () {
						commentForm.querySelector('[type="submit"]').removeAttribute("disabled", "disabled");
						errInfo.classList.add("fadeOut");
						errInfo.style.opacity = 0;
						clearTimeout(fadeOut);
					}, 3000);
				})
				.catch((err) => {
					// console.log(err);
					errInfo.innerHTML = "<strong>错误：</strong>";
					//show orror info
					err.forEach((e) => {
						errInfo.appendChild(e);
						errInfo.classList.add("shake");
						let fadeOut = setTimeout(function () {
							commentForm.querySelector('[type="submit"]').removeAttribute("disabled", "disabled");
							errInfo.classList.add("fadeOut");
							errInfo.style.opacity = 0;
							clearTimeout(fadeOut);
						}, 3000);
					});
				});
		});
	}
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
function animate ({ timing, draw, duration }) {

	let start = performance.now();
	requestAnimationFrame(function animate (time) {
		// timeFraction 从 0 增加到 1
		let timeFraction = (time - start) / duration;
		if (timeFraction > 1) timeFraction = 1;
		// 计算当前动画状态
		let progress = timing(timeFraction);
		draw(progress); // 绘制
		if (timeFraction < 1) {
			requestAnimationFrame(animate);
		}
	});
}
