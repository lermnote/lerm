import { delegate } from '../utils/dom.js';

let scrollTopInitialized = false;

export const initializeScrollTop = () => {
	if (scrollTopInitialized) return;

	scrollTopInitialized = true;

	delegate('click', '#scroll-up', (event) => {
		event.preventDefault();
		document.documentElement.scrollIntoView({ behavior: 'smooth' });
	});
};
