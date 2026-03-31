import { delegate } from '../utils/dom.js';

let scrollTopInitialized = false;

export const initializeScrollTop = () => {
	if (scrollTopInitialized) return;
	scrollTopInitialized = true;

	const btn = document.getElementById('scroll-up');
	if (!btn) return;

	// Read threshold from data attribute (set by PHP from options)
	const threshold = parseInt(btn.dataset.threshold, 10) || 400;

	// Show / hide based on scroll position
	const onScroll = () => {
		btn.style.display = window.scrollY > threshold ? '' : 'none';
	};

	window.addEventListener('scroll', onScroll, { passive: true });
	onScroll(); // run once on init

	// Scroll-to-top click
	delegate('click', '#scroll-up', (event) => {
		event.preventDefault();
		document.documentElement.scrollIntoView({ behavior: 'smooth' });
	});
};
