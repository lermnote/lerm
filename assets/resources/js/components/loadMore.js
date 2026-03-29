import ClickService from '../services/ClickService.js';
import {
	initializeArchiveLoadMoreState,
	persistArchiveLoadMoreState,
} from './archiveState.js';

export const appendPostsToDOM = (data, target) => {
	const loadMoreBtn = target?.closest?.('.more-posts') ?? document.querySelector('.more-posts');
	const postsList = document.querySelector('.ajax-posts');

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
		const nextPage = parseInt(data.page ?? data.paged, 10) + 1;
		if (!Number.isNaN(nextPage) && nextPage >= 1) {
			loadMoreBtn.dataset.page = String(nextPage);
		}

		if (!data.has_more) {
			loadMoreBtn.hidden = true;
		}
	}

	persistArchiveLoadMoreState();
};

export const loadMoreHandle = () => {
	initializeArchiveLoadMoreState();

	if (!document.querySelector('.more-posts')) return;

	const loadMore = new ClickService({
		apiUrl: lermData.rest_url,
		selector: '.more-posts',
		route: lermData.route_loadmore,
		security: lermData.nonce,
		isThrottled: true,
		cacheExpiryTime: 60000,
		method: 'GET',
		enableCache: false,
	});

	loadMore.onSuccess = appendPostsToDOM;
};
