// @ts-check

/**
 * Resolve the localized AdminConfig object for the current admin screen.
 *
 * @param {{
 *   windowRef: Window,
 *   find: (selector: string) => Element|null,
 *   getData: (element: Element, key: string) => string|null
 * }} options
 * @returns {Record<string, unknown>}
 */
const resolveAdminConfig = ({ windowRef, find, getData }) => {
	const firstForm = find('.lerm-settings-form');
	if (firstForm) {
		const jsGlobal = getData(firstForm, 'js-global');
		if (jsGlobal && windowRef[jsGlobal]) {
			return /** @type {Record<string, unknown>} */ (windowRef[jsGlobal]);
		}
	}

	return /** @type {Record<string, unknown>} */ (windowRef.lermAdminConfig || {});
};

module.exports = {
	resolveAdminConfig,
};
