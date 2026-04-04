
let scrollAnimateObserver;

export const initializeScrollAnimate = () => {
	const elements = document.querySelectorAll('.loading-animate:not(.animate__animated)');
	if (!elements.length) return;

	if (!('IntersectionObserver' in window)) {
		elements.forEach(revealScrollAnimate);
		return;
	}

	if (!scrollAnimateObserver) {
		scrollAnimateObserver = new IntersectionObserver((entries) => {
			entries.forEach(entry => {
				if (!entry.isIntersecting) return;

				revealScrollAnimate(entry.target);
				scrollAnimateObserver.unobserve(entry.target);
			});
		}, { threshold: 0.1 });
	}

	elements.forEach(element => scrollAnimateObserver.observe(element));
};

const revealScrollAnimate = (element) => {
	const hasAnimationClass = Array.from(element.classList).some(
		(className) => className.startsWith('animate__') && className !== 'animate__animated'
	);

	if (!hasAnimationClass) {
		element.classList.add('animate__fadeIn');
	}

	element.classList.add('animate__animated');
};


