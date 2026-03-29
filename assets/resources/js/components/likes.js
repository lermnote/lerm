import ClickService from '../services/ClickService.js';

export const likeBtnSuccess = (data, target) => {
	const { id, type } = target.dataset;
	const buttons = document.querySelectorAll(`.like-${type}-${id}`);
	const isLiked = data.liked === true;

	buttons.forEach((button) => {
		button.classList.toggle('btn-outline-danger', isLiked);
		button.classList.toggle('btn-outline-secondary', !isLiked);
		button.setAttribute('title', isLiked ? 'Unlike' : 'Like');
		button.setAttribute('aria-pressed', isLiked ? 'true' : 'false');

		const countEl = button.querySelector('.count-wrap');
		if (countEl) {
			countEl.textContent = data.count;
		}
	});
};

export const likeBtnHandle = () => {
	if (!document.querySelector('.like-button')) return;

	const postLike = new ClickService({
		apiUrl: lermData.rest_url,
		selector: '.like-button',
		route: lermData.route_like,
		security: lermData.nonce,
		isThrottled: true,
		enableCache: false,
	});

	postLike.onSuccess = likeBtnSuccess;
};
