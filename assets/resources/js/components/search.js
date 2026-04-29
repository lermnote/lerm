import { translate } from '../utils/i18n.js';

const MIN_QUERY_LENGTH = 2;

const hideResults = (container) => {
	if (!container) return;
	container.innerHTML = '';
	container.classList.add('d-none');
};

const showResults = (container, markup) => {
	if (!container) return;
	container.innerHTML = markup;
	container.classList.remove('d-none');
};

const buildSearchUrl = (form, query) => {
	const searchUrl = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
	searchUrl.searchParams.set('s', query);
	return searchUrl.toString();
};

const buildRequestUrl = (query) => {
	const requestUrl = new URL(
		[lermData.rest_url.replace(/\/$/, ''), lermData.route_search.replace(/^\//, '')].join('/'),
		window.location.origin
	);

	requestUrl.searchParams.set('q', query);
	requestUrl.searchParams.set('per_page', String(lermData.search_results_per_page || 5));

	return requestUrl.toString();
};

const renderResults = (form, container, query, data) => {
	const results = Array.isArray(data?.results) ? data.results : [];
	const searchUrl = buildSearchUrl(form, query);

	if (!results.length) {
		showResults(
			container,
			`<div class="list-group-item small text-muted">${translate('search_no_results')}</div>`
		);
		return;
	}

	const items = results
		.map((item) => {
			const thumb = item.thumbnail
				? `<img src="${item.thumbnail}" alt="" class="rounded flex-shrink-0" width="48" height="48" loading="lazy">`
				: '';
			const meta = [item.category, item.date].filter(Boolean).join(' · ');

			return `
				<a class="list-group-item list-group-item-action" href="${item.url}">
					<div class="d-flex gap-2 align-items-start">
						${thumb}
						<div class="flex-grow-1">
							<div class="fw-semibold">${item.title}</div>
							${meta ? `<div class="small text-muted">${meta}</div>` : ''}
							${item.excerpt ? `<div class="small text-muted">${item.excerpt}</div>` : ''}
						</div>
					</div>
				</a>
			`;
		})
		.join('');

	showResults(
		container,
		`${items}<a class="list-group-item list-group-item-action text-primary small fw-semibold" href="${searchUrl}">${translate('search_view_all')}</a>`
	);
};

export const initializeSearch = () => {
	document.querySelectorAll('.search-form').forEach((form) => {
		if (form.dataset.lermSearchBound === 'true') return;
		form.dataset.lermSearchBound = 'true';

		const input = form.querySelector('input[name="s"]');
		const container = form.querySelector('.js-live-search-results');

		if (!input || !container || !lermData?.route_search) return;

		let debounceTimer = null;
		let abortController = null;

		const runSearch = async () => {
			const query = input.value.trim();

			if (query.length < MIN_QUERY_LENGTH) {
				abortController?.abort();
				hideResults(container);
				return;
			}

			showResults(
				container,
				`<div class="list-group-item small text-muted">${translate('search_loading')}</div>`
			);

			abortController?.abort();
			abortController = new AbortController();

			try {
				const response = await fetch(buildRequestUrl(query), {
					headers: {
						'X-WP-Nonce': lermData.nonce,
					},
					signal: abortController.signal,
					credentials: 'same-origin',
				});
				const data = await response.json();

				if (!response.ok) {
					throw new Error(data?.message || response.statusText);
				}

				renderResults(form, container, query, data);
			} catch (error) {
				if (error.name === 'AbortError') return;

				showResults(
					container,
					`<div class="list-group-item small text-danger">${error.message || translate('click_failed')}</div>`
				);
			}
		};

		input.addEventListener('input', () => {
			window.clearTimeout(debounceTimer);
			debounceTimer = window.setTimeout(runSearch, 220);
		});

		input.addEventListener('keydown', (event) => {
			if (event.key === 'Escape') {
				hideResults(container);
			}
		});

		document.addEventListener('click', (event) => {
			if (!form.contains(event.target)) {
				hideResults(container);
			}
		});
	});
};
