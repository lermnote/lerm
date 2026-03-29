let lazyImageObserver;

const loadImage = (image) => {
	const nextSrc = image.dataset.src;
	if (!nextSrc || image.dataset.lazyLoaded === 'true') return;

	image.src = nextSrc;
	image.dataset.lazyLoaded = 'true';
};

export const initializeLazyImages = () => {
	const images = document.querySelectorAll('.lazy');
	if (!images.length) return;

	if (!('IntersectionObserver' in window)) {
		images.forEach(loadImage);
		return;
	}

	if (!lazyImageObserver) {
		lazyImageObserver = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) return;

				loadImage(entry.target);
				lazyImageObserver.unobserve(entry.target);
			});
		});
	}

	images.forEach((image) => {
		if (image.dataset.lazyLoaded === 'true') return;
		lazyImageObserver.observe(image);
	});
};
