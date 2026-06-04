// @ts-check

/**
 * i18n bridge for AdminConfig.
 *
 * Re-exports @wordpress/i18n functions so that both the classic admin page
 * (which reads strings from PHP-localized config during the migration period)
 * and the block-panel / future React options page can use the same import.
 *
 * During the Phase 2 transition, the classic admin-config.js still reads
 * strings from the `cfg` object injected by wp_localize_script. Once Phase 4
 * (React options page) is complete, all string lookups will use __() / _x()
 * exclusively and the PHP-localized config will be removed.
 *
 * @package Lerm\AdminConfig
 */

const i18n = require('@wordpress/i18n');

/**
 * Look up a translated string.
 *
 * @param {string} text   Text to translate.
 * @param {string} domain Domain to retrieve the translated text.
 * @returns {string} Translated text.
 */
const __ = i18n.__;

/**
 * Look up a translated string with context.
 *
 * @param {string} text    Text to translate.
 * @param {string} context Context information for the translators.
 * @param {string} domain  Domain to retrieve the translated text.
 * @returns {string} Translated text.
 */
const _x = i18n._x;

/**
 * Look up a translated plural string.
 *
 * @param {string} single The single form.
 * @param {string} plural The plural form.
 * @param {number} number The number to determine the form.
 * @param {string} domain Domain to retrieve the translated text.
 * @returns {string} Translated text.
 */
const _n = i18n._n;

/**
 * Return a formatted string.
 *
 * @param {string} format The format string.
 * @param {...unknown} args Arguments for the format string.
 * @returns {string} Formatted string.
 */
const sprintf = i18n.sprintf;

/**
 * Determine whether @wordpress/i18n has been initialized with translations.
 *
 * @returns {boolean}
 */
const isRtl = () => i18n.isRTL ? i18n.isRTL() : false;

module.exports = {
	__,
	_x,
	_n,
	sprintf,
	isRtl,
};
