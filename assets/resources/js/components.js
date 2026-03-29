// components.js
import ClickService from './services/ClickService.js';

export const likeBtnSuccess = (data, target) => {
	const { id, type } = target.dataset;
	const buttons = document.querySelectorAll(`.like-${type}-${id}`);
	const isLiked = data.liked === true;


	buttons.forEach(button => {
		button.classList.toggle('btn-outline-danger', isLiked);
		button.classList.toggle('btn-outline-secondary', !isLiked);
		button.setAttribute('title', isLiked ? 'Unlike' : 'Like');
		button.setAttribute('aria-pressed', isLiked ? 'true' : 'false');

		const countEl = button.querySelector('.count-wrap');
		if (countEl) countEl.textContent = data.count;
	});
};

/**
 * Initialize the post like feature when like buttons exist on the page.
 *
 * @returns {void}
 */
export const likeBtnHandle = () => {
	if (!document.querySelector('.like-button')) return;

	const postLike = new ClickService({
		apiUrl: lermData.rest_url,
		selector: '.like-button',
		route: lermData.route_like,          // 'like'
		security: lermData.nonce,
		isThrottled: true,
		enableCache: false,
	});
	postLike.onSuccess = likeBtnSuccess;
};
export const appendPostsToDOM = (data, target) => {
	const loadMoreBtn = target?.closest?.('.more-posts') ?? document.querySelector('.more-posts');
	const postsList = document.querySelector(".ajax-posts");

	if (postsList && data.content) {
		const parser = new DOMParser();
		const doc = parser.parseFromString(data.content, 'text/html');

		if (doc.querySelector('script')) {
			console.warn('Potential XSS threat detected, content rejected.');
			return;
		}
		postsList.insertAdjacentHTML('beforeend', data.content);
		document.dispatchEvent(new Event('contentLoaded'));
	}
	if (loadMoreBtn) {
		// Update the next page number for the following request.
		const nextPage = parseInt(data.page ?? data.paged, 10) + 1;
		if (!Number.isNaN(nextPage) && nextPage >= 1) {
			loadMoreBtn.dataset.page = String(nextPage);
		}

		if (!data.has_more) {
			loadMoreBtn.hidden = true;
		}
	}
};

export const loadMoreHandle = () => {
	if (!document.querySelector('.more-posts')) return;

	const loadMore = new ClickService({
		apiUrl: lermData.rest_url,
		selector: '.more-posts',
		route: lermData.route_loadmore,
		security: lermData.nonce,
		isThrottled: true,
		cacheExpiryTime: 60000,
		method: 'GET',
		enableCache: false
	});
	loadMore.onSuccess = appendPostsToDOM;
};

export const handleCommentSuccess = (data) => {
	const c = data?.comment;
	const commentHtml = data?.comment_html?.trim();
	if (!c || !commentHtml) return;

	const respond = document.getElementById('respond');
	const commentList = document.querySelector('.comment-list');
	const template = document.createElement('template');
	template.innerHTML = commentHtml;

	const commentNode = template.content.firstElementChild;
	if (!commentNode) return;

	const parentId = Number(c.comment_parent || 0);

	if (parentId > 0) {
		const parentComment = document.getElementById(`comment-${parentId}`);
		if (parentComment) {
			let children = Array.from(parentComment.children).find(
				(child) => child.classList?.contains('children')
			);

			if (!children) {
				children = document.createElement('ul');
				children.className = 'children';
				parentComment.appendChild(children);
			}

			children.appendChild(commentNode);
		} else if (commentList) {
			commentList.insertAdjacentElement('afterbegin', commentNode);
		}
	} else if (commentList) {
		commentList.insertAdjacentElement('afterbegin', commentNode);
	} else if (respond?.parentNode) {
		const newCommentCard = document.createElement('div');
		newCommentCard.className = 'card mb-3';

		const ol = document.createElement('ol');
		ol.className = 'comment-list p-0 m-0 list-group list-group-flush';
		ol.appendChild(commentNode);
		newCommentCard.appendChild(ol);
		respond.parentNode.appendChild(newCommentCard);
	}

	if (window.addComment?.cancelForm) {
		window.addComment.cancelForm();
	}
};
export const handleLoginSuccess = () => {
	const target = lermData.front_door || window.location.origin + '/';
	if (window.lermLoadPage?.loadPage) {
		window.lermLoadPage.loadPage(target);
	} else {
		window.location.href = target;
	}
};

export const handleUpdateProfileSuccess = () => {
	const target = lermData.redirect || window.location.href;
	if (window.lermLoadPage?.loadPage) {
		window.lermLoadPage.loadPage(target);
	} else {
		window.location.href = target;
	}
};
