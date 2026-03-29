export const safeRequestIdleCallback = (callback) => {
	if ('requestIdleCallback' in window) {
		window.requestIdleCallback(callback);
		return;
	}

	window.setTimeout(callback, 200);
};
