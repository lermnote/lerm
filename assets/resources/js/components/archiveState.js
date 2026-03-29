const LOAD_MORE_STATE_PREFIX = 'lerm:loadmore-state:';
const LOAD_MORE_RESTORE_DELAYS = [0, 60, 180, 360];

let loadMoreStateInitialized = false;
let lastArchiveFocus = {
	postId: '',
	offsetTop: null,
};

const canUseSessionStorage = () => {
	try {
		return typeof window !== 'undefined' && 'sessionStorage' in window;
	} catch (error) {
		return false;
	}
};

const getLoadMoreStateKey = () => `${LOAD_MORE_STATE_PREFIX}${window.location.pathname}${window.location.search}`;

const readLoadMoreState = () => {
	if (!canUseSessionStorage()) return null;

	try {
		const rawValue = window.sessionStorage.getItem(getLoadMoreStateKey());
		return rawValue ? JSON.parse(rawValue) : null;
	} catch (error) {
		console.warn('[Lerm] Failed to read load-more restore state:', error);
		return null;
	}
};

const writeLoadMoreState = (state) => {
	if (!canUseSessionStorage()) return;

	try {
		window.sessionStorage.setItem(getLoadMoreStateKey(), JSON.stringify(state));
	} catch (error) {
		console.warn('[Lerm] Failed to persist load-more restore state:', error);
	}
};

const clearLoadMoreState = () => {
	if (!canUseSessionStorage()) return;

	try {
		window.sessionStorage.removeItem(getLoadMoreStateKey());
	} catch (error) {
		console.warn('[Lerm] Failed to clear load-more restore state:', error);
	}
};

const getNavigationType = () => {
	const navigationEntry = window.performance?.getEntriesByType?.('navigation')?.[0];
	if (navigationEntry?.type) {
		return navigationEntry.type;
	}

	if (window.performance?.navigation?.type === 2) {
		return 'back_forward';
	}

	if (window.performance?.navigation?.type === 1) {
		return 'reload';
	}

	return 'navigate';
};

const getLoadMoreElements = () => ({
	postsList: document.querySelector('.ajax-posts'),
	loadMoreBtn: document.querySelector('.more-posts'),
});

const buildLoadMoreStateSnapshot = () => {
	const { postsList, loadMoreBtn } = getLoadMoreElements();
	if (!postsList) return null;

	return {
		html: postsList.innerHTML,
		nextPage: loadMoreBtn?.dataset.page ?? '',
		buttonHidden: Boolean(loadMoreBtn?.hidden),
		scrollY: Math.max(window.scrollY, window.pageYOffset, 0),
		focusPostId: lastArchiveFocus.postId,
		focusOffsetTop: Number.isFinite(lastArchiveFocus.offsetTop) ? lastArchiveFocus.offsetTop : null,
		savedAt: Date.now(),
	};
};

const restoreArchiveScrollPosition = (state) => {
	if (!state) return;

	const restoreScroll = () => {
		let targetTop = Number.isFinite(state.scrollY) ? state.scrollY : 0;

		if (state.focusPostId) {
			const focusPost = document.getElementById(state.focusPostId);
			if (focusPost && Number.isFinite(state.focusOffsetTop)) {
				targetTop = focusPost.getBoundingClientRect().top + window.scrollY - state.focusOffsetTop;
			}
		}

		window.scrollTo({
			top: Math.max(0, targetTop),
			behavior: 'auto',
		});
	};

	const previousScrollRestoration = 'scrollRestoration' in window.history ? window.history.scrollRestoration : null;
	if (previousScrollRestoration) {
		window.history.scrollRestoration = 'manual';
	}

	LOAD_MORE_RESTORE_DELAYS.forEach((delay) => {
		window.setTimeout(restoreScroll, delay);
	});

	if (previousScrollRestoration) {
		window.setTimeout(() => {
			window.history.scrollRestoration = previousScrollRestoration;
		}, LOAD_MORE_RESTORE_DELAYS[LOAD_MORE_RESTORE_DELAYS.length - 1] + 120);
	}
};

const restoreLoadMoreState = () => {
	const state = readLoadMoreState();
	if (!state) return;

	const { postsList, loadMoreBtn } = getLoadMoreElements();
	if (!postsList) return;

	const didReplaceContent = typeof state.html === 'string' && postsList.innerHTML !== state.html;
	if (didReplaceContent) {
		postsList.innerHTML = state.html;
	}

	if (loadMoreBtn) {
		if (state.nextPage) {
			loadMoreBtn.dataset.page = String(state.nextPage);
		}
		loadMoreBtn.hidden = Boolean(state.buttonHidden);
	}

	lastArchiveFocus = {
		postId: typeof state.focusPostId === 'string' ? state.focusPostId : '',
		offsetTop: Number.isFinite(state.focusOffsetTop) ? state.focusOffsetTop : null,
	};

	if (didReplaceContent) {
		document.dispatchEvent(new Event('contentLoaded'));
	}

	restoreArchiveScrollPosition(state);
};

const rememberArchiveFocus = (event) => {
	if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
		return;
	}

	const target = event.target instanceof Element ? event.target : null;
	if (!target) return;

	const link = target.closest('.ajax-posts article a[href]');
	if (!link) return;

	const article = link.closest('article[id]');
	lastArchiveFocus = {
		postId: article?.id ?? '',
		offsetTop: article ? article.getBoundingClientRect().top : null,
	};

	persistArchiveLoadMoreState();
};

export const persistArchiveLoadMoreState = () => {
	const snapshot = buildLoadMoreStateSnapshot();
	if (!snapshot) return;

	writeLoadMoreState(snapshot);
};

export const initializeArchiveLoadMoreState = () => {
	if (loadMoreStateInitialized || !document.querySelector('.ajax-posts')) return;

	loadMoreStateInitialized = true;

	if (getNavigationType() === 'back_forward') {
		restoreLoadMoreState();
	} else {
		clearLoadMoreState();
	}

	document.addEventListener('click', rememberArchiveFocus);
	window.addEventListener('pagehide', persistArchiveLoadMoreState);
};
