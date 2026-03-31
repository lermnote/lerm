import { delegate } from '../utils/dom.js';

let navigationInitialized = false;

// ── Offcanvas offset ────────────────────────────────────────────────────────
const updateOffCanvasMenuOffset = () => {
	const offCanvasMenu = document.querySelector('#offcanvasMenu');
	if (!offCanvasMenu) return;

	if (document.body.clientWidth < 992) {
		const rootMarginTop = parseFloat(getComputedStyle(document.documentElement).marginTop) || 0;
		offCanvasMenu.style.top = `${rootMarginTop}px`;
		return;
	}
	offCanvasMenu.style.top = '';
};

// ── Dropdown hover on desktop ───────────────────────────────────────────────
const bindDropdownHover = (dropdown) => {
	if (dropdown.dataset.lermDropdownBound === 'true') return;

	const menu = dropdown.querySelector('.dropdown-menu');
	if (!menu) return;

	dropdown.dataset.lermDropdownBound = 'true';

	dropdown.addEventListener('mouseenter', () => {
		menu.classList.add('show');
		dropdown.setAttribute('aria-expanded', 'true');
	});

	dropdown.addEventListener('mouseleave', () => {
		menu.classList.remove('show');
		dropdown.setAttribute('aria-expanded', 'false');
	});
};

// ── Sticky header: shrink on scroll ────────────────────────────────────────
const initStickyHeader = () => {
	const header = document.getElementById('site-header');
	if (!header) return;

	const isSticky      = header.classList.contains('site-header--sticky');
	const isTransparent = header.classList.contains('site-header--transparent');
	const canShrink     = header.dataset.shrink === 'true';   // set by PHP below

	if (!isSticky && !isTransparent) return;

	const SHRINK_THRESHOLD = 80; // px

	const onScroll = () => {
		const scrolled = window.scrollY > SHRINK_THRESHOLD;

		// Shrink behaviour (sticky only)
		if (isSticky && canShrink) {
			header.classList.toggle('is-shrunk', scrolled);
		}

		// Transparent → solid once user scrolls
		if (isTransparent) {
			header.classList.toggle('is-solid', scrolled);
		}
	};

	window.addEventListener('scroll', onScroll, { passive: true });
	onScroll(); // apply immediately on load
};

export const initializeNavigation = () => {
	if (!navigationInitialized) {
		navigationInitialized = true;

		delegate('click', '.navbar-toggler', (_event, toggler) => {
			toggler.classList.toggle('active');
		});

		window.addEventListener('resize', updateOffCanvasMenuOffset, { passive: true });

		initStickyHeader();
	}

	updateOffCanvasMenuOffset();
	document.querySelectorAll('.dropdown').forEach(bindDropdownHover);
};
