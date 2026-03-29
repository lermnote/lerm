export function delegate(eventName, selector, handler, root = document) {
	root.addEventListener(
		eventName,
		(event) => {
			let target = event.target;

			while (target && target !== root) {
				if (target.matches && target.matches(selector)) {
					handler(event, target);
					return;
				}

				target = target.parentElement;
			}
		},
		{ passive: false }
	);
}

export const DOMContentLoaded = (callback) => {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', callback, { once: true });
		return;
	}

	callback();
};
