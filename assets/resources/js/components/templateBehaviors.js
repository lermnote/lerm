import { Tooltip } from 'bootstrap';

let templateBehaviorsInitialized = false;

const toggleToc = (toggle) => {
	const toc = toggle.closest('.lerm-toc');
	if (!toc) return;

	const isCollapsed = toc.classList.toggle('is-collapsed');
	toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
};

const initializeTocToggles = () => {
	document.querySelectorAll('[data-lerm-toc-toggle]').forEach((toggle) => {
		if (toggle.dataset.lermTocBound === 'true') return;
		toggle.dataset.lermTocBound = 'true';

		toggle.addEventListener('click', () => {
			toggleToc(toggle);
		});

		toggle.addEventListener('keydown', (event) => {
			if (event.key !== 'Enter' && event.key !== ' ') return;
			event.preventDefault();
			toggleToc(toggle);
		});
	});
};

const initializeTooltips = () => {
	document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
		if (element.dataset.lermTooltipBound === 'true') return;
		element.dataset.lermTooltipBound = 'true';
		Tooltip.getOrCreateInstance(element);
	});
};

export const initializeTemplateBehaviors = () => {
	if (!templateBehaviorsInitialized) {
		templateBehaviorsInitialized = true;
	}

	initializeTocToggles();
	initializeTooltips();
};
