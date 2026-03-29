import { delegate } from '../utils/dom.js';

let navigationInitialized = false;

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

export const initializeNavigation = () => {
	if (!navigationInitialized) {
		navigationInitialized = true;

		delegate('click', '.navbar-toggler', (_event, toggler) => {
			toggler.classList.toggle('active');
		});

		window.addEventListener('resize', updateOffCanvasMenuOffset, { passive: true });
	}

	updateOffCanvasMenuOffset();
	document.querySelectorAll('.dropdown').forEach(bindDropdownHover);
};
