const trackedViewPostIds = new Set();

const formatViewCount = (count) => {
	const normalizedCount = Number.parseInt(count, 10);
	const safeCount = Number.isNaN(normalizedCount) ? 0 : Math.max(0, normalizedCount);
	return new Intl.NumberFormat(document.documentElement.lang || undefined).format(safeCount);
};

export const viewCountSuccess = (data, postId) => {
	if (!postId || typeof data?.count === 'undefined') return;

	const formattedCount = formatViewCount(data.count);
	document
		.querySelectorAll(`.js-post-views-count[data-post-id="${postId}"]`)
		.forEach((element) => {
			element.textContent = formattedCount;
		});
};

export const viewCountHandle = async () => {
	const postId = Number.parseInt(lermData?.post_id ?? '0', 10);
	if (!postId || trackedViewPostIds.has(postId) || !lermData?.route_views) return;

	const countNodes = document.querySelectorAll(`.js-post-views-count[data-post-id="${postId}"]`);
	if (!countNodes.length) return;

	trackedViewPostIds.add(postId);

	const requestUrl = new URL(
		[
			lermData.rest_url.replace(/\/$/, ''),
			lermData.route_views.replace(/^\//, '').replace(/\/$/, ''),
		].join('/'),
		window.location.origin
	);
	requestUrl.searchParams.set('id', String(postId));

	try {
		const response = await fetch(requestUrl.toString(), {
			method: 'POST',
			headers: {
				'X-WP-Nonce': lermData.nonce,
			},
			credentials: 'same-origin',
		});
		const data = await response.json();

		if (!response.ok) {
			throw new Error(data?.message || response.statusText);
		}

		viewCountSuccess(data, postId);
	} catch (error) {
		trackedViewPostIds.delete(postId);
		console.error('[Lerm] Failed to update post views:', error);
	}
};
