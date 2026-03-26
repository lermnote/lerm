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
 * 初始化文章点赞功能
 * 检测页面是否存在点赞按钮元素，若存在则创建点赞服务实例并配置相关参数
 * @param {void} 无参数
 * @returns {void} 无返回值
 */
export const likeBtnHandle = () => {
	if (!document.querySelector('.like-button')) return;

	const postLike = new ClickService({
		apiUrl: lermData.rest_url,
		selector: '.like-button',
		route: lermData.route,          // 'like'
		security: lermData.nonce,
		isThrottled: true,
		enableCache: false,
	});
	postLike.onSuccess = likeBtnSuccess;
};

export const appendPostsToDOM = (data) => {
	const loadMoreBtn = document.querySelector(".more-posts");
	const postsList = document.querySelector(".ajax-posts");

	if (postsList && data.content) {
		const parser = new DOMParser();
		const doc = parser.parseFromString(data.content, 'text/html');

		if (doc.querySelector('script')) {
			console.warn('Potential XSS threat detected, content rejected.');
			return;
		}
		postsList.insertAdjacentHTML('beforeend', data.content)
	};
	if (loadMoreBtn && data.currentPage) {
		const pageNum = parseInt(data.currentPage, 10);
		if (!isNaN(pageNum) && pageNum >= 1) {
			loadMoreBtn.dataset.currentPage = pageNum;
		}
	}
};

export const loadMoreHandle = () => {
	if (!document.querySelector('.more-posts')) return;

	const loadMore = new ClickService({
		apiUrl: lermData.rest_url,
		selector: '.more-posts',
		route: lermData.loadmore_action,
		security: lermData.nonce,
		isThrottled: true,
		cacheExpiryTime: 60000,
		enableCache: false
	});
	loadMore.onSuccess = appendPostsToDOM;
};

export const handleCommentSuccess = (data) => {
	const c = data?.comment;
	if (!c) return;

	const respond = document.getElementById('respond');
	const commentList = document.querySelector('.comment-list');


	// Build <li>
	const li = document.createElement('li');
	li.id = `comment-${c.comment_ID}`;
	li.className = [c.comment_type || '', 'list-group-item', c.comment_parent !== '0' ? 'p-0' : ''].filter(Boolean).join(' ');

	// <article>
	const article = document.createElement('article');
	article.id = `div-comment-${c.comment_ID}`;
	article.className = 'comment-body';

	// footer/meta
	const footer = document.createElement('footer');
	footer.className = 'comment-meta mb-1';

	// author vcard
	const spanAuthor = document.createElement('span');
	spanAuthor.className = 'comment-author vcard';

	const img = document.createElement('img');
	img.src = c.avatar_url || '';
	img.alt = c.comment_author || '';
	img.className = `avatar avatar-${c.avatar_size || 48} photo`;
	img.height = c.avatar_size || 48;
	img.width = c.avatar_size || 48;
	img.loading = 'lazy';
	img.decoding = 'async';
	if (c.avatar_url) img.srcset = `${c.avatar_url} 2x`;

	const b = document.createElement('b');
	b.className = 'fn';
	b.textContent = c.comment_author || '';

	spanAuthor.appendChild(img);
	spanAuthor.appendChild(b);

	// metadata (time + link)
	const spanMeta = document.createElement('span');
	spanMeta.className = 'comment-metadata';

	const bullet = document.createElement('span');
	bullet.setAttribute('aria-hidden', 'true');
	bullet.textContent = '•';

	const a = document.createElement('a');
	const postLink = c.comment_post_link || '#';
	a.href = `${postLink}#comment-${c.comment_ID}`;

	const timeEl = document.createElement('time');
	if (c.comment_date_gmt) timeEl.setAttribute('datetime', c.comment_date_gmt);
	timeEl.textContent = c.comment_date || '';

	a.appendChild(timeEl);
	spanMeta.appendChild(bullet);
	spanMeta.appendChild(a);

	footer.appendChild(spanAuthor);
	footer.appendChild(spanMeta);

	article.appendChild(footer);

	// moderation badge if awaiting moderation
	if (c.comment_approved === '0') {
		const modBadge = document.createElement('span');
		modBadge.className = 'comment-awaiting-moderation badge rounded-pill bg-info';
		modBadge.textContent = '您的评论正在等待审核。';
		article.appendChild(modBadge);
	}

	// content section (server is expected to sanitize HTML)
	const section = document.createElement('section');
	section.className = 'comment-content';
	section.style.marginLeft = '56px';

	const p = document.createElement('p');
	// NOTE: using innerHTML because server returns sanitized HTML (wp_kses_post).
	// If server returns plain text or you want extra safety, use: p.textContent = c.comment_content;
	p.innerHTML = c.comment_content || '';
	section.appendChild(p);
	article.appendChild(section);

	li.appendChild(article);

	// Insert into DOM (preserve original placement logic)
	if (commentList) {
		const previousElement = respond.previousElementSibling;
		if (previousElement) {
			const lastChild = previousElement.lastElementChild;
			if (lastChild?.classList.contains('children')) {
				lastChild.appendChild(li);
			} else {
				const childrenUl = document.createElement('ul');
				childrenUl.className = 'children';
				childrenUl.appendChild(li);
				previousElement.appendChild(childrenUl);
			}
		} else {
			// top-level — prepend into existing commentList
			commentList.insertAdjacentElement('afterbegin', li);
		}
	} else {
		// No comment list exists yet — build card + ol + append
		const newCommentCard = document.createElement('div');
		newCommentCard.className = 'card mb-3';

		const ol = document.createElement('ol');
		ol.className = 'comment-list p-0 m-0 list-group list-group-flush';
		ol.appendChild(li);
		newCommentCard.appendChild(ol);

		// append after respond wrapper
		respond.parentNode.appendChild(newCommentCard);
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
