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

	// Skip hover binding on touch devices or inside offcanvas — rely on click toggle instead
	if (window.matchMedia('(pointer: coarse)').matches) return;
	if (dropdown.closest('.offcanvas')) return;

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

		const offcanvas = document.querySelector('#offcanvasMenu');
		if (offcanvas) {
			offcanvas.addEventListener('shown.bs.offcanvas', () => {
				document.querySelectorAll('.navbar-toggler').forEach(t => t.classList.add('active'));
			});
			offcanvas.addEventListener('hidden.bs.offcanvas', () => {
				document.querySelectorAll('.navbar-toggler').forEach(t => t.classList.remove('active'));
			});
		}

		window.addEventListener('resize', updateOffCanvasMenuOffset, { passive: true });
	}

	updateOffCanvasMenuOffset();
	document.querySelectorAll('.dropdown').forEach(bindDropdownHover);
};
