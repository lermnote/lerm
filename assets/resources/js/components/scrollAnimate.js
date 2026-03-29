let scrollAnimateObserver;

export const initializeScrollAnimate = () => {
	const elements = document.querySelectorAll('.loading-animate:not(.animate__animated)');
	if (!elements.length || !('IntersectionObserver' in window)) return;

	if (!scrollAnimateObserver) {
		scrollAnimateObserver = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (!entry.isIntersecting) return;
					scrollAnimateObserver.unobserve(entry.target);
				});
			},
			{ threshold: 0.1 }
		);
	}

	elements.forEach((element) => scrollAnimateObserver.observe(element));
};
