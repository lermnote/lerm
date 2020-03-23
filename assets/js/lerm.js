/**
 * Global Javascript Functions
 *
 * @Authors Lerm https://www.hanost.com
 * @Date 2016-04-17 22:02:49
 * @LastEditTime 2020-03-21 19:03:53
 * @version 2.0
 */

(function () {
	"use strict";
	//archives page expand
	let archive = $("#archives");
	let monthList = $(".month-list", archive);
	let monthPostList = $(".month-post-list", archive);
	let postList = $(".post-list", archive);
	let postListFirst = $(".post-list:first", archive);
	postList.hide();
	postListFirst.show();
	monthList.first().show();
	monthPostList.css("cursor", "s-resize").on("click", function () {
		$(this)
			.next()
			.slideToggle(400);
	});
	var animate = function (index, status, s) {
		if (index > postList.length) {
			return;
		}
		if (status == "up") {
			postList.eq(index).slideUp(s, function () {
				animate(index + 1, status, s - 10 < 1 ? 0 : s - 10);
			});
		} else {
			postList.eq(index).slideDown(s, function () {
				animate(index + 1, status, s - 10 < 1 ? 0 : s - 10);
			});
		}
	};
	$("#al_expand_collapse").on("click", function (e) {
		e.preventDefault();
		if ($(this).data("s")) {
			$(this).data("s", "");
			animate(0, "up", 100);
		} else {
			$(this).data("s", 1);
			animate(0, "down", 100);
		}
	});

	$(document).on("click", '[data-toggle="lightbox"]', function (e) {
		// console.log(e)
		e.preventDefault();
		// console.log($(this))
		return $(this).ekkoLightbox();
	});
})($);
document.addEventListener("DOMContentLoaded", function (e) {
	/**
	 * global variable
	 *
	 */
	let html = document.documentElement;
	let body = document.body;

	/**
	 * dropdown menu hover show
	 *
	 */
	let dropdown = document.querySelectorAll(".dropdown");
	if (dropdown) {
		dropdown.forEach(e => {
			e.onmouseover = () =>
				e.querySelector(".dropdown-menu").classList.add("show");
			e.onmouseout = () =>
				e.querySelector(".dropdown-menu").classList.remove("show");
		});
	}

	/**
	 * mobile menu
	 *
	 */
	//dropback
	let backdrop = body.querySelector(".menu-backdrop");
	if (!backdrop) {
		return;
	}

	//adminbar height
	let windowWidth = document.body.clientWidth;
	let siteHeader = body.querySelector("#site-header");
	let navbar = document.querySelector("#navbar");
	if (!navbar) {
		return;
	}

	// mobile menu collope
	let navigation = document.querySelector("#site-navigation");
	if (!navigation) {
		return;
	}

	let toggleButton = navigation.querySelector(".menu-toggle");
	if ("undefined" === typeof toggleButton) {
		return;
	}

	let menuList = navigation.querySelector("ul");
	//Hide menu toggle toggleButton if menu is empty and return early.
	if (!menuList) {
		toggleButton.style.display = "none";
	} else {
		menuList.setAttribute("aria-expanded", "false");
		// Get all the link elements within the menu.
		let links = menuList.querySelectorAll("a");
		let subMenus = menuList.getElementsByClassName("dropdown-menu");

		// Set menu items with submenus to aria-haspopup="true".
		for (let i = 0, len = subMenus.length; i < len; i++) {
			subMenus[i].parentNode.setAttribute("aria-haspopup", "true");
		}
		//click link to hidden menu
		links.forEach(e => {
			if (!e.classList.contains("dropdown-toggle"))
				e.onclick = function () {
					body.classList.remove("nav-opened");
					navigation.classList.remove("toggled");
					backdrop.classList.remove("show");
					html.classList.remove("noscroll");
				};
		});
	}
	if (windowWidth < 992) {
		// navbar.style.top = parseFloat(document.defaultView.getComputedStyle(html, null).marginTop) + navigation.offsetHeight + 'px';
		navbar.style.top = document.defaultView.getComputedStyle(
			html,
			null
		).marginTop;
	}

	toggleButton.onclick = function () {
		if (-1 != navigation.className.indexOf("toggled")) {
			navigation.classList.remove("toggled");
			body.classList.remove("nav-opened");
			toggleButton.setAttribute("aria-expanded", "false");
			menuList.setAttribute("aria-expanded", "false");
			backdrop.classList.remove("show");
			html.classList.remove("noscroll");
		} else {
			body.classList.add("nav-opened");
			navigation.classList.add("toggled");
			toggleButton.setAttribute("aria-expanded", "true");
			menuList.setAttribute("aria-expanded", "true");
			backdrop.classList.add("show");
			html.classList.add("noscroll");
		}
	};

	let dragging = false;
	backdrop.addEventListener("touchmove", () => {
		dragging = true;
		// console.log("触摸滑动事件", dragging);
	});
	backdrop.addEventListener("touchend", e => {
		e.preventDefault();
		if (false === dragging) {
			// console.log("触摸结束事件", dragging);
			if (-1 !== backdrop.className.indexOf("show")) {
				body.classList.remove("nav-opened");
				navigation.classList.remove("toggled");
				backdrop.classList.remove("show");
				html.classList.remove("noscroll");
			}
		}
	});
	backdrop.addEventListener("touchstart", function () {
		dragging = false;
		// console.log("触摸开始事件", dragging);
	});

	/**
	 * calendar add class
	 *
	 */
	document.querySelectorAll("#wp-calendar tbody td a").forEach(e => {
		e.classList.add("has-posts");
	});

	/**
	 * images lazyload
	 *
	 */
	let lazyLoadInstance = new LazyLoad({
		elements_selector: ".lazy",
		threshold: 0
	});
	if (lazyLoadInstance) {
		lazyLoadInstance.update();
	}

	/**
	 * code highlight
	 *
	 */
	if ("undefined" !== typeof hljs) {
		document.querySelectorAll("pre code").forEach(block => {
			hljs.highlightBlock(block);
		});
	}

	/**
	 * smooth scroll to top
	 *
	 */
	let scrollUp = document.getElementById("scroll-up");
	scrollUp.addEventListener("click", e => {
		e.preventDefault();
		animate({
			duration: 700,
			timing: scrollUpEaseOut,
			draw: progress =>
				(html.scrollTop = html.scrollTop * (1 - progress / 7))
		});
	});
	window.addEventListener("scroll", check);

	function check() {
		pageYOffset >= 500 && scrollUp.classList.add("show");
		pageYOffset < 500 && scrollUp.classList.remove("show");
	}
	let circ = timeFraction =>
		1 -
		Math.sin(
			Math.acos(timeFraction > 1 ? (timeFraction = 1) : timeFraction)
		);

	let makeEaseOut = timing => timeFraction => 1 - timing(1 - timeFraction);
	let scrollUpEaseOut = makeEaseOut(circ);

	/**
	 * ajax load more posts
	 *
	 * @since 3.2
	 */
	let loadMorePosts = document.querySelector(".more-posts");
	if (loadMorePosts) {
		loadMorePosts.addEventListener("click", e => {
			e.preventDefault();
			loadMorePosts.innerHTML = adminajax.loading;

			let params = new FormData();
			params.append("query", adminajax.posts);
			params.append("page", adminajax.current);
			params.append("security", adminajax.nonce);
			params.append("action", "lerm_load_more");
			fetch(adminajax.url, {
					method: "POST",
					body: params
				})
				.then(response => response.text())
				.then(data => {
					if (data.length) {
						adminajax.current++;
						let newData = parseToDOM(data);
						newData.forEach(e => {
							document
								.querySelector(".ajax-posts")
								.appendChild(e);
							if (e.childNodes.length) {
								e.classList.add("ajax-loading");
							}
						});
						return document.querySelectorAll(".ajax-loading");
					}

					loadMorePosts.innerHTML = adminajax.noposts;
					throw data;
				})
				.then(e => {
					Array.prototype.slice.call(e).forEach(f => {
						fadeIn(f);
						f.classList.remove("ajax-loading");
					});
					loadMorePosts.innerHTML = adminajax.loadmore;
				})
				.catch(err => {
					// console.log(err)
					loadMorePosts.setAttribute("disabled", "true");
					loadMorePosts.setAttribute("aria-disabled", "true");
				});
		});
	}

	/**
	 * Ajax like post;
	 *
	 * @since 3.2
	 */
	let likeButton = document.querySelector("#like-button");
	// console.log(likeButton);
	if (likeButton) {
		let id = likeButton.dataset.id;
		let params = new FormData();
		params.append("action", "lerm_post_like");
		params.append("postID", id);
		params.append("security", adminajax.nonce);

		likeButton.addEventListener("click", e => {
			e.preventDefault();

			if (!likeButton.classList.contains("done")) {
				fetch(adminajax.url, {
						method: "POST",
						body: params
					})
					.then(response => response.json())
					.then(data => {
						// console.log(data);
						likeButton.classList.add("done");
						likeButton.disabled = true;
						document.querySelector(".count").innerHTML = parseInt(
							data
						);
					})
					.catch(err => console.log(err.message));
			}
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
		commentForm.insertAdjacentHTML( 'beforeend', '<div id="error" class="text-danger wow"></div>' );
		let errInfo = commentForm.querySelector("#error");
		let author = commentForm.querySelector('[name="author"]');
		let email = commentForm.querySelector('[name="email"]');
		let url = commentForm.querySelector('[name="url"]');
		let comment = commentForm.querySelector('[name="comment"]');

		errInfo.style.visibility = "hidden";
		errInfo.innerHTML = "#";

		//submit event
		commentForm.addEventListener("submit", e => {
			e.preventDefault();
			errInfo.removeAttribute('style');
			errInfo.classList.remove('shake');
			errInfo.classList.remove('fadeOut');
			errInfo.innerHTML =  '<strong><i class="fa fa-spinner fa-pulse mr-1"></i>正在提交...</strong>';
			new WOW().init();

			// check user logged in.
			if (!adminajax.loggedin) {
				let data = {
					author: author.value,
					email: email.value,
					url: url.value,
				};
				if (!data.author) {
					errInfo.innerHTML = '<strong>错误：</strong>请填写姓名';
					errInfo.classList.add('shake');
					// let shake = setTimeout(function(){
					// 	errInfo.classList.remove('shake');
					// 	// errInfo.removeAttribute('style');
					// 	clearTimeout(shake);
					// }, 3000);
					return;
				}

				if (!validateEmail(data.email)) {
					errInfo.innerHTML = '<strong>错误：</strong>请填写正确的电子邮箱';
					errInfo.classList.add('shake');
					// let shake = setTimeout(function(){
					// 	errInfo.classList.remove('shake');
					// 	// errInfo.removeAttribute('style');
					// 	clearTimeout(shake);
					// }, 3000);
					return;
				}
			}

			if (!comment.value) {
				errInfo.innerHTML = '<strong>错误：</strong>请输入评论内容';
				errInfo.classList.add('shake');
				// let shake = setTimeout(function(){
				// 	errInfo.classList.remove('shake');
				// 	// errInfo.removeAttribute('style');
				// 	clearTimeout(shake);
				// }, 3000);
				return;
			}

			let params = new URLSearchParams(new FormData(commentForm));
			params.append("action", "ajax_comment");
			params.append("security", adminajax.nonce);
			// console.log(params);
			fetch(adminajax.url, {
					method: "POST",
					body: params
				})
				.then(response => response.text())
				.then(e => {
					let data = parseToDOM(e);
					// console.log(data, data.length)
					if ("STRONG" === data[0].nodeName||data.length===1) {
						throw data;
					}
					let respond = document.getElementById("respond");
					// errInfo.style.display = "none";
					if (respond.parentNode.classList.contains("comment")) {
						if (null !== respond.nextElementSibling) {
							data.forEach(e => {
								respond.nextElementSibling.appendChild(e);
								commentForm.querySelector('[type="submit"]').removeAttribute('disabled', 'disabled')
							});
							//console.log('1')
						} else {
							data.forEach(e => {
								// console.log(e)
								respond.parentNode.appendChild(e);
								commentForm.querySelector('[type="submit"]').removeAttribute('disabled', 'disabled')
							});
						}
					}
					if (document.querySelector(".comment-list")) {
						data.forEach(e => {
							document.querySelector(".comment-list").insertBefore( e, document.querySelector(".comment-list") .firstChild );
							commentForm.querySelector('[type="submit"]').removeAttribute('disabled', 'disabled')
							//console.log('2')
						});
					} else {
						data.forEach(e => {
							// console.log(e)
							respond.parentNode.appendChild(e);
							commentForm.querySelector('[type="submit"]').removeAttribute('disabled', 'disabled')
						});
					}
				})
				.then(() => {
					commentForm.querySelector("#comment").value = "";
					errInfo.innerHTML =  '<strong><i class="fa fa-ok mr-1"></i>提交成功</strong>';
					let fadeOut = setTimeout(function(){
						errInfo.classList.add('fadeOut');
						errInfo.style.opacity = 0;
						clearTimeout(fadeOut)
					}, 3000);
				})
				.catch(err => {
					 console.log(err);
					errInfo.innerHTML = " ";
					//show orror info
					err.forEach(e => {
						errInfo.appendChild(e);
						let fadeOut = setTimeout(function(){
							errInfo.classList.add('fadeOut');
							errInfo.style.opacity = 0;
							clearTimeout(fadeOut)
						}, 3000);
					});
					// console.log(e);
				});
		});
	}
});

/**
 *Parse to DOM Array
 *
 * @param {*} s
 * @returns
 */
function parseToDOM(s) {
	let div = document.createElement("div");
	if (typeof s == "string") div.innerHTML = s;
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
function fadeIn(e) {
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
function validateEmail(email) {
	var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(String(email).toLowerCase());
}
/**
 *
 *
 * @param {*} options
 */
function animate(options) {
	let start = performance.now();

	requestAnimationFrame(function animate(time) {
		let timeFraction = (time - start) / options.duration;
		timeFraction > 1 && (timeFraction = 1);

		let progress = options.timing(timeFraction);

		options.draw(progress);
		timeFraction < 1 && requestAnimationFrame(animate);
	});
}