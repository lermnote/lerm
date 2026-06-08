// @ts-check
/*!
 * Lerm Settings Panel
 * Refactored for clarity, modularity, and maintainability.
 */

// ─── WordPress Global Stubs (typed as any — official typings are incomplete) ──
const { resolveAdminConfig } = require('../core/config');
const { createFormStateHelpers } = require('./form-state');
const { createAdminConfigTransport } = require('./transport');
const { dependencyMatches: coreDependencyMatches, dependencyScalar, dependencyScalarList } = require('../core/dependencies');
const { __ } = require('../i18n');

// ─── Confirm Dialog (wp.components.Modal bridge for vanilla JS) ──────
let confirmDialog;
(function buildConfirmDialog() {
	let element, components;
	try {
		element = require('@wordpress/element');
		components = require('@wordpress/components');
	} catch (_e) {
		confirmDialog = (message) => Promise.resolve(window.confirm(message));
		return;
	}

	const { createElement, render } = element;
	const { Button, Modal } = components;

	/**
	 * Show a confirmation dialog using wp.components.Modal.
	 * @param {string} message
	 * @returns {Promise<boolean>}
	 */
	let dialogActive = false;

	confirmDialog = (message) => {
		if (dialogActive) {
			return Promise.resolve(false);
		}

		dialogActive = true;

		return new Promise((resolve) => {
			const container = document.createElement('div');
			document.body.appendChild(container);

			const cleanup = (/** @type {boolean} */ result) => {
				try {
					render(null, container);
				} catch (_err) {
					// If unmount fails, just remove the container.
				}
				container.remove();
				dialogActive = false;
				resolve(result);
			};

			try {
				render(
					createElement(
						Modal,
						{
							title: __('Confirm', 'lerm-admin-config'),
							onRequestClose: () => cleanup(false),
					},
					createElement('p', null, message),
					createElement(
						'div',
						{ style: { display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '16px' } },
						createElement(
							Button,
							{
								variant: 'secondary',
								onClick: () => cleanup(false),
							},
							__('Cancel', 'lerm-admin-config')
						),
						createElement(
							Button,
							{
								variant: 'primary',
								isDestructive: true,
								onClick: () => cleanup(true),
							},
							__('Confirm', 'lerm-admin-config')
						)
					)
				),
				container
			);
		} catch (_renderErr) {
			// If the initial render fails (e.g., incompatible React version),
			// clean up the container and resolve so the caller doesn't hang.
			cleanup(false);
			return;
		}
	});
	};
})();

/** @type {any} */ const wp = /** @type {any} */ (window['wp']);

(function () {
	'use strict';

	// ─── Type Definitions ─────────────────────────────────────────────────────

	/**
	 * @typedef {{
	 *   restUrl?: string, restNonce?: string,
	 *   codeEditor: object|null,
	 * }} LermConfig
	 */

	/**
	 * @typedef {{
	 *   id: number,
	 *   url: string,
	 *   sizes?: { medium?: { url: string }, thumbnail?: { url: string } }
	 * }} WPAttachment
	 */

	/**
	 * @typedef {{
	 *   success: boolean,
	 *   data: { values?: Record<string, unknown>, message?: string, json?: string, fieldErrors?: Record<string, string|string[]>, errors?: Record<string, string|string[]>, tab?: string, subsection?: string }
	 * }} AjaxResponse
	 */

	/**
	 * @typedef {{ enabled?: Record<string, unknown>, disabled?: Record<string, unknown> }} SorterValue
	 */

	/**
	 * @typedef {{ destroy: () => void }} SortableInstance
	 */

	// ─── DOM Utilities ────────────────────────────────────────────────────────

	const dom = {
		/**
		 * @template {Element} T
		 * @param {string} sel
		 * @param {Document|Element} [ctx]
		 * @returns {T|null}
		 */
		find: (sel, ctx = document) => /** @type {T|null} */(ctx.querySelector(sel)),

		/**
		 * @template {Element} T
		 * @param {string} sel
		 * @param {Document|Element} [ctx]
		 * @returns {T[]}
		 */
		findAll: (sel, ctx = document) => /** @type {T[]} */(Array.from(ctx.querySelectorAll(sel))),

		/**
		 * @param {string} tag
		 * @param {Record<string, string|object|EventListener>} [props]
		 * @param {(string|Node)[]} [children]
		 * @returns {HTMLElement}
		 */
		create(tag, props = {}, children = []) {
			const el = document.createElement(tag);
			for (const [k, v] of Object.entries(props)) {
				if (k === 'class') el.className = /** @type {string} */ (v);
				else if (k === 'style' && typeof v === 'object') Object.assign(el.style, v);
				else if (k.startsWith('on')) el.addEventListener(k.slice(2).toLowerCase(), /** @type {EventListener} */(v));
				else el.setAttribute(k, /** @type {string} */(v));
			}
			for (const child of children) {
				el.appendChild(typeof child === 'string' ? document.createTextNode(child) : child);
			}
			return el;
		},

		/** @param {Element} el */
		empty(el) { while (el.firstChild) el.removeChild(el.firstChild); },
	};

	/**
	 * Normalize a dataset-style key to the actual attribute suffix used in HTML.
	 * Accepts both `fieldType` and `field-type`.
	 *
	 * @param {string} key
	 * @returns {string}
	 */
	const normalizeDataKey = (key) => String(key)
		.replace(/([a-z0-9])([A-Z])/g, '$1-$2')
		.replace(/_/g, '-')
		.toLowerCase();

	/**
	 * @param {Element} el
	 * @param {string} key
	 * @returns {string|null}
	 */
	const getData = (el, key) => el.getAttribute('data-' + normalizeDataKey(key));

	/**
	 * @param {Element} el
	 * @param {string} key
	 * @param {string} value
	 */
	const setData = (el, key, value) => el.setAttribute('data-' + normalizeDataKey(key), String(value));

	/**
	 * Resolve a form control by ID without relying on CSS selectors.
	 * This safely supports IDs that begin with digits, such as `404_title`.
	 *
	 * @param {HTMLFormElement} form
	 * @param {string} fieldId
	 * @returns {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement|null}
	 */
	const findFieldById = (form, fieldId) => {
		const el = document.getElementById(String(fieldId));
		if (!el || !form.contains(el)) return null;
		return el instanceof HTMLInputElement || el instanceof HTMLSelectElement || el instanceof HTMLTextAreaElement
			? el
			: null;
	};

	/**
	 * Resolve all form controls that share the same HTML name attribute.
	 *
	 * @param {HTMLFormElement} form
	 * @param {string} name
	 * @returns {(HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement)[]}
	 */
	const getNamedControls = (form, name) => Array.from(form.elements).filter((el) => (
		(el instanceof HTMLInputElement || el instanceof HTMLSelectElement || el instanceof HTMLTextAreaElement)
		&& el.name === name
	));

	// ─── Config ───────────────────────────────────────────────────────────────

	/** @type {LermConfig} */
	let cfg = /** @type {any} */ ({});
	const transport = createAdminConfigTransport({
		getConfig: () => /** @type {Record<string, unknown>} */ (cfg),
		getData,
	});

	const hasRestTransport = () => transport.hasRestTransport();
	const restActionPath = (form, endpoint) => transport.restActionPath(form, endpoint);
	const requestRest = (path, options = {}) => transport.requestRest(path, options);

	/**
	 * @returns {{ tab: string, subsection: string }}
	 */
	const readLocationState = () => {
		const url = new URL(window.location.href);
		return {
			tab: url.searchParams.get('tab') ?? '',
			subsection: url.searchParams.get('subsection') ?? '',
		};
	};

	/**
	 * @param {string} tabId
	 * @param {string} subsectionId
	 * @param {boolean} [replace]
	 */
	const writeLocationState = (tabId, subsectionId, replace = false) => {
		if (!window.history?.pushState) return;
		const url = new URL(window.location.href);

		if (tabId) url.searchParams.set('tab', tabId);
		else url.searchParams.delete('tab');

		if (subsectionId) url.searchParams.set('subsection', subsectionId);
		else url.searchParams.delete('subsection');

		const state = {
			tab: url.searchParams.get('tab') ?? '',
			subsection: url.searchParams.get('subsection') ?? '',
		};

		window.history[replace ? 'replaceState' : 'pushState'](state, '', url.toString());
	};

	// ─── Field Name Helpers ───────────────────────────────────────────────────

	/** @param {HTMLFormElement} form */
	const getOptionName = (form) => getData(form, 'option-name') || 'options_framework';

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} fieldId
	 */
	const buildFieldName = (form, fieldId) => `${getOptionName(form)}[${fieldId}]`;

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} fieldId
	 * @returns {string}
	 */
	const getControllerValue = (form, fieldId) => {
		const el = findFieldById(form, fieldId);
		if (el instanceof HTMLInputElement) return el.type === 'checkbox' ? (el.checked ? '1' : '0') : String(el.value ?? '');
		if (el instanceof HTMLSelectElement || el instanceof HTMLTextAreaElement) return String(el.value ?? '');
		const ajaxSelect = /** @type {HTMLElement|null} */ (dom.find(`.lerm-ajax-select[data-target="${fieldId}"]`, form));
		if (ajaxSelect) {
			const values = /** @type {HTMLInputElement[]} */ (dom.findAll('input[type="hidden"]', ajaxSelect))
				.map((input) => String(input.value ?? '').trim())
				.filter(Boolean);
			return values[0] ?? '';
		}
		const radio = /** @type {HTMLInputElement|undefined} */ (
			getNamedControls(form, buildFieldName(form, fieldId)).find((control) => control instanceof HTMLInputElement && control.type === 'radio' && control.checked)
		);
		return radio ? String(radio.value ?? '') : '';
	};

	// ─── Dependencies ─────────────────────────────────────────────────────────

	/** @param {unknown} value */
	const dependencyExpectedValue = (value) => {
		const raw = String(value ?? '');

		if (!raw.startsWith('[')) {
			return raw;
		}

		try {
			const parsed = JSON.parse(raw);
			return Array.isArray(parsed) ? parsed : raw;
		} catch (error) {
			return raw;
		}
	};

	/** @param {HTMLFormElement} form */
	const toggleDependencies = (form) => {
		/** @type {Map<string, HTMLElement>} */
		const rowsByFieldId = new Map();

		dom.findAll('[data-field-id]', form).forEach(row => {
			const fieldId = getData(row, 'field-id');
			if (fieldId) rowsByFieldId.set(fieldId, /** @type {HTMLElement} */ (row));
		});

		const dependentRows = dom.findAll('[data-dependency-field]', form).map((row) => /** @type {HTMLElement} */ (row));

		for (let pass = 0; pass < dependentRows.length; pass += 1) {
			let changed = false;

			dependentRows.forEach((row) => {
				const dependencyField = getData(row, 'dependency-field') || '';
				const dependencyOperator = getData(row, 'dependency-operator') || '==';
				const dependencyValue = dependencyExpectedValue(getData(row, 'dependency-value'));
				const controllerRow = rowsByFieldId.get(dependencyField);
				const shouldHide = (
					!controllerRow
					|| controllerRow.hidden
					|| !dependencyMatches(getControllerValue(form, dependencyField), dependencyOperator, dependencyValue)
				);

				if (row.hidden !== shouldHide) {
					row.hidden = shouldHide;
					changed = true;
				}
			});

			if (!changed) break;
		}
	};

	// ─── Color Pickers ────────────────────────────────────────────────────────

	/** @typedef {{ hue: number, saturation: number, value: number }} HsvColor */
	/** @typedef {{ input: HTMLInputElement, wrapper: HTMLElement, trigger: HTMLButtonElement, clearButton: HTMLButtonElement, swatch: HTMLElement, pickerState: HsvColor, syncUi: () => void }} ColorControlInstance */

	/** @type {WeakMap<HTMLInputElement, ColorControlInstance>} */
	const colorControlMap = new WeakMap();

	/** @type {{ panel: HTMLElement, valueLabel: HTMLElement, swatch: HTMLElement, spectrum: HTMLElement, spectrumHandle: HTMLElement, hueStrip: HTMLElement, hueHandle: HTMLElement }|null} */
	let sharedColorPopover = null;

	/** @type {ColorControlInstance|null} */
	let activeColorControl = null;

	/** @type {HsvColor} */
	const defaultColorState = {
		hue: 205,
		saturation: 0.78,
		value: 0.85,
	};

	/**
	 * @param {number} value
	 * @param {number} min
	 * @param {number} max
	 * @returns {number}
	 */
	const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

	/**
	 * @param {unknown} value
	 * @returns {string}
	 */
	const normalizeHexColor = (value) => {
		const raw = String(value ?? '').trim();

		if (!raw) return '';

		const candidate = raw.startsWith('#') ? raw : `#${raw}`;

		return /^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(candidate)
			? candidate.toLowerCase()
			: '';
	};

	/**
	 * @param {HsvColor} color
	 * @returns {HsvColor}
	 */
	const cloneHsv = (color) => ({
		hue: color.hue,
		saturation: color.saturation,
		value: color.value,
	});

	/**
	 * @param {number} hue
	 * @param {number} saturation
	 * @param {number} value
	 * @returns {{ r: number, g: number, b: number }}
	 */
	const hsvToRgb = (hue, saturation, value) => {
		const normalizedHue = ((hue % 360) + 360) % 360;
		const chroma = value * saturation;
		const section = normalizedHue / 60;
		const secondary = chroma * (1 - Math.abs((section % 2) - 1));
		const match = value - chroma;

		let red = 0;
		let green = 0;
		let blue = 0;

		if (section >= 0 && section < 1) {
			red = chroma;
			green = secondary;
		} else if (section < 2) {
			red = secondary;
			green = chroma;
		} else if (section < 3) {
			green = chroma;
			blue = secondary;
		} else if (section < 4) {
			green = secondary;
			blue = chroma;
		} else if (section < 5) {
			red = secondary;
			blue = chroma;
		} else {
			red = chroma;
			blue = secondary;
		}

		return {
			r: Math.round((red + match) * 255),
			g: Math.round((green + match) * 255),
			b: Math.round((blue + match) * 255),
		};
	};

	/**
	 * @param {number} red
	 * @param {number} green
	 * @param {number} blue
	 * @returns {HsvColor}
	 */
	const rgbToHsv = (red, green, blue) => {
		const r = red / 255;
		const g = green / 255;
		const b = blue / 255;
		const max = Math.max(r, g, b);
		const min = Math.min(r, g, b);
		const delta = max - min;

		let hue = 0;

		if (delta > 0) {
			if (max === r) {
				hue = 60 * (((g - b) / delta) % 6);
			} else if (max === g) {
				hue = 60 * (((b - r) / delta) + 2);
			} else {
				hue = 60 * (((r - g) / delta) + 4);
			}
		}

		if (hue < 0) {
			hue += 360;
		}

		return {
			hue,
			saturation: max === 0 ? 0 : delta / max,
			value: max,
		};
	};

	/**
	 * @param {string} hex
	 * @returns {{ r: number, g: number, b: number }|null}
	 */
	const hexToRgb = (hex) => {
		const normalized = normalizeHexColor(hex);

		if (!normalized) return null;

		const expanded = 4 === normalized.length
			? `#${normalized[1]}${normalized[1]}${normalized[2]}${normalized[2]}${normalized[3]}${normalized[3]}`
			: normalized;

		return {
			r: parseInt(expanded.slice(1, 3), 16),
			g: parseInt(expanded.slice(3, 5), 16),
			b: parseInt(expanded.slice(5, 7), 16),
		};
	};

	/**
	 * @param {string} hex
	 * @returns {HsvColor}
	 */
	const hexToHsv = (hex) => {
		const rgb = hexToRgb(hex);

		return rgb
			? rgbToHsv(rgb.r, rgb.g, rgb.b)
			: cloneHsv(defaultColorState);
	};

	/**
	 * @param {{ r: number, g: number, b: number }} rgb
	 * @returns {string}
	 */
	const rgbToHex = (rgb) => `#${[rgb.r, rgb.g, rgb.b]
		.map((channel) => clamp(Math.round(channel), 0, 255).toString(16).padStart(2, '0'))
		.join('')}`;

	/**
	 * @param {HsvColor} color
	 * @returns {string}
	 */
	const hsvToHex = (color) => rgbToHex(hsvToRgb(color.hue, color.saturation, color.value));

	/** @param {HTMLInputElement} input */
	const dispatchColorInputEvents = (input) => {
		input.dispatchEvent(new Event('input', { bubbles: true }));
		input.dispatchEvent(new Event('change', { bubbles: true }));
	};

	/**
	 * @param {ColorControlInstance} control
	 */
	const positionSharedColorPopover = (control) => {
		const popover = sharedColorPopover;

		if (!popover || popover.panel.hidden) return;

		if (!document.body.contains(control.wrapper)) {
			activeColorControl = null;
			popover.panel.hidden = true;
			return;
		}

		const rect = control.trigger.getBoundingClientRect();
		const width = popover.panel.offsetWidth || 220;
		const height = popover.panel.offsetHeight || 250;
		const gap = 10;
		const viewportPadding = 12;

		let left = rect.left;
		let top = rect.bottom + gap;

		if (left + width > window.innerWidth - viewportPadding) {
			left = window.innerWidth - width - viewportPadding;
		}

		if (left < viewportPadding) {
			left = viewportPadding;
		}

		if (top + height > window.innerHeight - viewportPadding) {
			top = rect.top - height - gap;
		}

		if (top < viewportPadding) {
			top = viewportPadding;
		}

		popover.panel.style.left = `${Math.round(left)}px`;
		popover.panel.style.top = `${Math.round(top)}px`;
	};

	/**
	 * @param {ColorControlInstance} control
	 */
	const syncSharedColorPopover = (control) => {
		const popover = sharedColorPopover;
		if (!popover) return;

		const rawValue = String(control.input.value ?? '').trim();
		const normalized = normalizeHexColor(rawValue);
		const previewHex = normalized || '';

		if (normalized) {
			control.pickerState = hexToHsv(normalized);
		}

		popover.valueLabel.textContent = previewHex || rawValue || 'No color';
		popover.swatch.style.setProperty('--lerm-color-swatch', previewHex || 'transparent');
		popover.spectrum.style.setProperty('--lerm-hue', String(control.pickerState.hue));
		popover.spectrumHandle.style.left = `${control.pickerState.saturation * 100}%`;
		popover.spectrumHandle.style.top = `${(1 - control.pickerState.value) * 100}%`;
		popover.hueHandle.style.left = `${(control.pickerState.hue / 360) * 100}%`;
		positionSharedColorPopover(control);
	};

	const closeSharedColorPopover = () => {
		if (!sharedColorPopover) return;

		if (activeColorControl) {
			activeColorControl.trigger.setAttribute('aria-expanded', 'false');
		}

		sharedColorPopover.panel.hidden = true;
		sharedColorPopover.panel.style.visibility = '';
		activeColorControl = null;
	};

	/**
	 * @param {ColorControlInstance} control
	 */
	const openSharedColorPopover = (control) => {
		const popover = ensureSharedColorPopover();

		activeColorControl = control;
		control.trigger.setAttribute('aria-expanded', 'true');
		popover.panel.hidden = false;
		popover.panel.style.visibility = 'hidden';
		syncSharedColorPopover(control);
		popover.panel.style.visibility = '';
	};

	/**
	 * @param {ColorControlInstance} control
	 */
	const toggleSharedColorPopover = (control) => {
		if (activeColorControl?.input === control.input) {
			closeSharedColorPopover();
			return;
		}

		openSharedColorPopover(control);
	};

	/**
	 * @param {ColorControlInstance} control
	 * @param {HsvColor} color
	 * @param {boolean} [dispatchEvents]
	 */
	const applyPopoverColorState = (control, color, dispatchEvents = true) => {
		control.pickerState = cloneHsv(color);
		control.input.value = hsvToHex(control.pickerState);
		control.syncUi();

		if (dispatchEvents) {
			dispatchColorInputEvents(control.input);
		}
	};

	const ensureSharedColorPopover = () => {
		if (sharedColorPopover) return sharedColorPopover;

		const panel = dom.create('div', {
			class: 'lerm-color-popover',
			role: 'dialog',
			'aria-modal': 'false',
			hidden: 'hidden',
		});
		const swatch = dom.create('span', {
			class: 'lerm-color-popover__swatch',
			'aria-hidden': 'true',
		});
		const valueLabel = dom.create('span', {
			class: 'lerm-color-popover__value',
		}, ['No color']);
		const spectrum = dom.create('div', {
			class: 'lerm-color-popover__spectrum',
		});
		const spectrumHandle = dom.create('span', {
			class: 'lerm-color-popover__spectrum-handle',
			'aria-hidden': 'true',
		});
		const hueStrip = dom.create('div', {
			class: 'lerm-color-popover__hue',
		});
		const hueHandle = dom.create('span', {
			class: 'lerm-color-popover__hue-handle',
			'aria-hidden': 'true',
		});

		spectrum.appendChild(spectrumHandle);
		hueStrip.appendChild(hueHandle);
		panel.appendChild(dom.create('div', { class: 'lerm-color-popover__header' }, [swatch, valueLabel]));
		panel.appendChild(spectrum);
		panel.appendChild(hueStrip);
		document.body.appendChild(panel);

		/**
		 * @param {HTMLElement} surface
		 * @param {(event: PointerEvent, control: ColorControlInstance) => void} onMove
		 */
		const bindDragSurface = (surface, onMove) => {
			surface.addEventListener('pointerdown', (event) => {
				if (!(event instanceof PointerEvent) || !activeColorControl) return;

				event.preventDefault();

				const control = activeColorControl;
				const move = (nextEvent) => {
					if (!(nextEvent instanceof PointerEvent)) return;
					onMove(nextEvent, control);
				};
				const stop = () => {
					window.removeEventListener('pointermove', move);
					window.removeEventListener('pointerup', stop);
					window.removeEventListener('pointercancel', stop);
				};

				move(event);
				window.addEventListener('pointermove', move);
				window.addEventListener('pointerup', stop);
				window.addEventListener('pointercancel', stop);
			});
		};

		bindDragSurface(spectrum, (event, control) => {
			const rect = spectrum.getBoundingClientRect();
			const saturation = clamp((event.clientX - rect.left) / Math.max(rect.width, 1), 0, 1);
			const value = 1 - clamp((event.clientY - rect.top) / Math.max(rect.height, 1), 0, 1);

			applyPopoverColorState(control, {
				hue: control.pickerState.hue,
				saturation,
				value,
			});
		});

		bindDragSurface(hueStrip, (event, control) => {
			const rect = hueStrip.getBoundingClientRect();
			const ratio = clamp((event.clientX - rect.left) / Math.max(rect.width, 1), 0, 1);

			applyPopoverColorState(control, {
				hue: ratio >= 1 ? 359.999 : ratio * 360,
				saturation: control.pickerState.saturation,
				value: control.pickerState.value,
			});
		});

		document.addEventListener('mousedown', (event) => {
			const target = event.target;

			if (!(target instanceof Node) || panel.hidden) return;
			if (panel.contains(target)) return;
			if (activeColorControl?.wrapper.contains(target)) return;

			closeSharedColorPopover();
		});

		document.addEventListener('keydown', (event) => {
			if ('Escape' !== event.key || !activeColorControl) return;

			const trigger = activeColorControl.trigger;
			closeSharedColorPopover();
			trigger.focus({ preventScroll: true });
		});

		window.addEventListener('resize', () => {
			if (activeColorControl) {
				positionSharedColorPopover(activeColorControl);
			}
		});

		window.addEventListener('scroll', () => {
			if (activeColorControl) {
				positionSharedColorPopover(activeColorControl);
			}
		}, true);

		sharedColorPopover = {
			panel,
			valueLabel,
			swatch,
			spectrum,
			spectrumHandle,
			hueStrip,
			hueHandle,
		};

		return sharedColorPopover;
	};

	/**
	 * @param {HTMLInputElement} input
	 * @returns {ColorControlInstance}
	 */
	const createColorControl = (input) => {
		const existing = colorControlMap.get(input);

		if (existing) return existing;

		const wrapper = dom.create('span', { class: 'lerm-color-control' });
		const trigger = /** @type {HTMLButtonElement} */ (dom.create('button', {
			type: 'button',
			class: 'lerm-color-control__trigger',
			'aria-haspopup': 'dialog',
			'aria-expanded': 'false',
			'aria-label': 'Choose color',
		}));
		const swatch = dom.create('span', {
			class: 'lerm-color-control__swatch',
			'aria-hidden': 'true',
		});
		const clearButton = /** @type {HTMLButtonElement} */ (dom.create('button', {
			type: 'button',
			class: 'lerm-color-control__clear',
			'aria-label': 'Clear color',
			title: 'Clear color',
		}, ['×']));

		if (input.parentNode) {
			input.parentNode.insertBefore(wrapper, input);
		}

		input.classList.add('lerm-color-control__input');
		input.autocomplete = 'off';
		input.spellcheck = false;
		trigger.appendChild(swatch);
		wrapper.appendChild(trigger);
		wrapper.appendChild(input);
		wrapper.appendChild(clearButton);
		clearButton.textContent = 'x';

		/** @type {ColorControlInstance} */
		const control = {
			input,
			wrapper,
			trigger,
			clearButton,
			swatch,
			pickerState: cloneHsv(defaultColorState),
			syncUi: () => {
				const rawValue = String(input.value ?? '').trim();
				const normalized = normalizeHexColor(rawValue);
				const hasVisibleValue = '' !== rawValue;

				if (normalized) {
					control.pickerState = hexToHsv(normalized);
				}

				wrapper.classList.toggle('has-value', hasVisibleValue);
				wrapper.classList.toggle('is-empty', !hasVisibleValue);
				wrapper.classList.toggle('is-invalid', hasVisibleValue && '' === normalized);
				trigger.classList.toggle('has-value', '' !== normalized);
				trigger.setAttribute('aria-expanded', activeColorControl?.input === input ? 'true' : 'false');
				trigger.disabled = input.disabled;
				clearButton.hidden = !hasVisibleValue;
				clearButton.disabled = input.disabled || input.readOnly;
				swatch.style.setProperty('--lerm-color-swatch', normalized || 'transparent');

				if (activeColorControl?.input === input) {
					syncSharedColorPopover(control);
				}
			},
		};

		trigger.addEventListener('click', (event) => {
			event.preventDefault();

			if (input.disabled || input.readOnly) return;

			toggleSharedColorPopover(control);
		});

		clearButton.addEventListener('click', (event) => {
			event.preventDefault();

			if (input.disabled || input.readOnly) return;

			input.value = '';
			control.syncUi();
			dispatchColorInputEvents(input);
			closeSharedColorPopover();
			input.focus({ preventScroll: true });
		});

		input.addEventListener('input', control.syncUi);
		input.addEventListener('change', control.syncUi);
		input.addEventListener('blur', () => {
			const normalized = normalizeHexColor(input.value);

			if (normalized && normalized !== input.value) {
				input.value = normalized;
			}

			control.syncUi();
		});

		colorControlMap.set(input, control);
		control.syncUi();

		return control;
	};

	const colorControlAdapter = {
		/** @param {HTMLInputElement} input */
		init(input) {
			if (getData(input, 'lermColorReady') === '1') return;

			setData(input, 'lerm-color-ready', '1');
			createColorControl(input);
		},

		/**
		 * @param {HTMLInputElement|null} input
		 * @param {unknown} value
		 */
		setValue(input, value) {
			if (!(input instanceof HTMLInputElement)) return;

			this.init(input);
			input.value = normalizeHexColor(value) || String(value ?? '').trim();
			createColorControl(input).syncUi();
		},
	};

	/** @param {Document|Element} scope */
	const initColorPickers = (scope) => {
		dom.findAll('.lerm-color-field', scope).forEach(input => {
			if (input instanceof HTMLInputElement) {
				colorControlAdapter.init(input);
			}
		});
	};

	/** @typedef {{ value: string, label: string }} AjaxSelectOption */
	/** @typedef {{
	 *   container: HTMLElement,
	 *   form: HTMLFormElement|null,
	 *   fieldId: string,
	 *   schemaId: string,
	 *   source: string,
	 *   multiple: boolean,
	 *   allowClear: boolean,
	 *   perPage: number,
	 *   minSearchLength: number,
	 *   search: HTMLInputElement,
	 *   selectedWrap: HTMLElement,
	 *   status: HTMLElement,
	 *   dropdown: HTMLElement,
	 *   results: HTMLElement,
	 *   values: HTMLElement,
	 *   selections: AjaxSelectOption[],
	 *   options: AjaxSelectOption[],
	 *   activeIndex: number,
	 *   more: boolean,
	 *   searchTimer: ReturnType<typeof setTimeout>|null,
	 *   currentQuery: string,
	 *   inputName: string,
	 *   inputNameTemplate: string,
	 *   syncUi: () => void
	 * }} AjaxSelectInstance */

	/** @type {WeakMap<HTMLElement, AjaxSelectInstance>} */
	const ajaxSelectMap = new WeakMap();

	/** @type {AjaxSelectInstance|null} */
	let activeAjaxSelect = null;

	let ajaxSelectGlobalsBound = false;

	/**
	 * @param {unknown} option
	 * @returns {AjaxSelectOption|null}
	 */
	const normalizeAjaxSelectOption = (option) => {
		if (!option) return null;
		if (typeof option === 'object' && 'value' in /** @type {Record<string, unknown>} */ (option)) {
			const value = String(/** @type {Record<string, unknown>} */ (option).value ?? '').trim();
			const label = String(/** @type {Record<string, unknown>} */ (option).label ?? value).trim();
			return value ? { value, label: label || value } : null;
		}

		const value = String(option ?? '').trim();
		return value ? { value, label: value } : null;
	};

	/**
	 * @param {unknown} value
	 * @returns {AjaxSelectOption[]}
	 */
	const normalizeAjaxSelectSelections = (value) => {
		const raw = Array.isArray(value) ? value : (null == value || '' === value ? [] : [value]);
		/** @type {AjaxSelectOption[]} */
		const selections = [];

		raw.forEach((item) => {
			const normalized = normalizeAjaxSelectOption(item);
			if (!normalized || selections.some((current) => current.value === normalized.value)) return;
			selections.push(normalized);
		});

		return selections;
	};

	/**
	 * @param {HTMLFormElement|null} form
	 * @returns {Record<string, string>}
	 */
	const extractContextPayload = (form) => {
		if (!form) return {};

		/** @type {Record<string, string[]>} */
		const map = {
			post_id: ['post_ID'],
			term_id: ['tag_ID', 'term_id'],
			user_id: ['user_id', 'user_ID'],
			comment_id: ['comment_ID'],
			network_id: ['id'],
		};

		/** @type {Record<string, string>} */
		const context = {};

		for (const [contextKey, inputNames] of Object.entries(map)) {
			for (const inputName of inputNames) {
				const input = /** @type {HTMLInputElement|null} */ (dom.find(`input[name="${inputName}"]`, form));
				const value = String(input?.value ?? '').trim();
				if (!/^\d+$/.test(value) || Number(value) <= 0) continue;
				context[contextKey] = value;
				break;
			}
		}

		return context;
	};

	/**
	 * @param {HTMLFormElement|null} form
	 * @param {{
	 *   schemaId: string,
	 *   fieldId: string,
	 *   search?: string,
	 *   page?: number,
	 *   perPage?: number,
	 *   selected?: string[]
	 * }} params
	 * @returns {Promise<AjaxResponse>}
	 */
	const requestDataSource = (form, params) => {
		const body = new FormData();
		body.set('schema_id', params.schemaId);
		body.set('field_id', params.fieldId);
		body.set('search', params.search ?? '');
		body.set('page', String(params.page ?? 1));
		body.set('per_page', String(params.perPage ?? 20));

		(params.selected ?? []).forEach((selected) => body.append('selected[]', selected));

		for (const [contextKey, contextValue] of Object.entries(extractContextPayload(form))) {
			body.set(`context[${contextKey}]`, contextValue);
		}

		if (hasRestTransport()) {
			return requestRest(`schemas/${params.schemaId}/data-source`, { method: 'POST', body });
		}

		return Promise.resolve({ success: false, data: { message: __('Unable to save the settings right now.', 'lerm-admin-config') } });
	};

	/**
	 * @param {AjaxSelectInstance} instance
	 */
	const closeAjaxSelectDropdown = (instance) => {
		instance.dropdown.hidden = true;
		instance.search.setAttribute('aria-expanded', 'false');
		instance.search.removeAttribute('aria-activedescendant');
		instance.activeIndex = -1;
		if (activeAjaxSelect === instance) activeAjaxSelect = null;
	};

	/**
	 * @param {AjaxSelectInstance} instance
	 */
	const openAjaxSelectDropdown = (instance) => {
		if (activeAjaxSelect && activeAjaxSelect !== instance) {
			closeAjaxSelectDropdown(activeAjaxSelect);
		}
		instance.dropdown.hidden = false;
		instance.search.setAttribute('aria-expanded', 'true');
		activeAjaxSelect = instance;
	};

	/**
	 * @param {AjaxSelectInstance} instance
	 * @param {string} message
	 */
	const setAjaxSelectStatus = (instance, message) => {
		instance.status.textContent = message;
	};

	/**
	 * @param {AjaxSelectInstance} instance
	 */
	const syncAjaxSelectInputs = (instance) => {
		dom.empty(instance.values);

		const name = instance.inputName;
		const nameTemplate = instance.inputNameTemplate;
		const attachNameTemplate = (input) => {
			if (nameTemplate) input.setAttribute('data-name-template', nameTemplate);
		};

		if (instance.multiple) {
			if (!instance.selections.length) {
				const input = /** @type {HTMLInputElement} */ (dom.create('input', {
					type: 'hidden',
					name: `${name}[]`,
					value: '',
					'data-lerm-ajax-select-input': 'empty',
				}));
				attachNameTemplate(input);
				instance.values.appendChild(input);
				return;
			}

			instance.selections.forEach((selection) => {
				const input = /** @type {HTMLInputElement} */ (dom.create('input', {
					type: 'hidden',
					name: `${name}[]`,
					value: selection.value,
					'data-lerm-ajax-select-input': '1',
				}));
				attachNameTemplate(input);
				instance.values.appendChild(input);
			});
			return;
		}

		const input = /** @type {HTMLInputElement} */ (dom.create('input', {
			type: 'hidden',
			id: instance.fieldId,
			name,
			value: instance.selections[0]?.value ?? '',
			'data-lerm-ajax-select-input': '1',
		}));
		attachNameTemplate(input);
		instance.values.appendChild(input);
	};

	/**
	 * @param {AjaxSelectInstance} instance
	 */
	const dispatchAjaxSelectEvents = (instance) => {
		instance.container.dispatchEvent(new Event('input', { bubbles: true }));
		instance.container.dispatchEvent(new Event('change', { bubbles: true }));
	};

	/**
	 * @param {AjaxSelectInstance} instance
	 * @param {number} nextIndex
	 */
	const setAjaxSelectActiveIndex = (instance, nextIndex) => {
		const options = /** @type {HTMLButtonElement[]} */ (dom.findAll('[data-lerm-ajax-select-option]', instance.results));

		instance.activeIndex = nextIndex >= 0 && nextIndex < options.length ? nextIndex : -1;

		options.forEach((option, index) => {
			const active = index === instance.activeIndex;
			option.classList.toggle('is-active', active);
			option.setAttribute('aria-selected', active ? 'true' : 'false');
		});

		if (instance.activeIndex >= 0 && options[instance.activeIndex]?.id) {
			instance.search.setAttribute('aria-activedescendant', options[instance.activeIndex].id);
			return;
		}

		instance.search.removeAttribute('aria-activedescendant');
	};

	/**
	 * @param {AjaxSelectInstance} instance
	 * @param {boolean} dispatchEvents
	 */
	const renderAjaxSelectSelections = (instance, dispatchEvents = false) => {
		dom.empty(instance.selectedWrap);
		instance.container.classList.toggle('is-empty', 0 === instance.selections.length);
		instance.container.classList.toggle('has-value', instance.selections.length > 0);

		if (!instance.selections.length) {
			syncAjaxSelectInputs(instance);
			if (dispatchEvents) dispatchAjaxSelectEvents(instance);
			return;
		}

		instance.selections.forEach((selection) => {
			const pill = dom.create('span', { class: 'lerm-ajax-select__pill' }, [
				dom.create('span', { class: 'lerm-ajax-select__pill-label' }, [selection.label]),
			]);

			if (instance.allowClear) {
				pill.appendChild(dom.create('button', {
					type: 'button',
					class: 'lerm-ajax-select__pill-remove',
					'aria-label': __('Remove selection', 'lerm-admin-config'),
					title: __('Remove selection', 'lerm-admin-config'),
					onclick: (event) => {
						event.preventDefault();
						instance.selections = instance.selections.filter((current) => current.value !== selection.value);
						renderAjaxSelectSelections(instance, true);
					},
				}, ['x']));
			}

			instance.selectedWrap.appendChild(pill);
		});

		syncAjaxSelectInputs(instance);
		if (dispatchEvents) dispatchAjaxSelectEvents(instance);
	};

	/**
	 * @param {AjaxSelectInstance} instance
	 * @param {AjaxSelectOption[]} options
	 */
	const renderAjaxSelectOptions = (instance, options) => {
		dom.empty(instance.results);
		instance.options = options;

		if (!options.length) {
			closeAjaxSelectDropdown(instance);
			setAjaxSelectStatus(instance, __('No matching results found.', 'lerm-admin-config'));
			return;
		}

		options.forEach((option, index) => {
			const isSelected = instance.selections.some((selection) => selection.value === option.value);
			const optionId = `${instance.results.id || instance.fieldId || 'lerm-ajax-select'}__option__${index}`;
			instance.results.appendChild(dom.create('li', { role: 'presentation' }, [
				dom.create('button', {
					type: 'button',
					id: optionId,
					class: `lerm-ajax-select__option${isSelected ? ' is-selected' : ''}`,
					role: 'option',
					'aria-selected': 'false',
					'data-lerm-ajax-select-option': String(index),
					onclick: (event) => {
						event.preventDefault();

						if (instance.multiple) {
							if (!instance.selections.some((selection) => selection.value === option.value)) {
								instance.selections = instance.selections.concat(option);
							}
							renderAjaxSelectSelections(instance, true);
							instance.search.value = '';
							instance.currentQuery = '';
							setAjaxSelectStatus(instance, __('Start typing to search.', 'lerm-admin-config'));
							closeAjaxSelectDropdown(instance);
							return;
						}

						instance.selections = [option];
						renderAjaxSelectSelections(instance, true);
						instance.search.value = '';
						instance.currentQuery = '';
						setAjaxSelectStatus(instance, __('Start typing to search.', 'lerm-admin-config'));
						closeAjaxSelectDropdown(instance);
					},
				}, [option.label]),
			]));
		});

		if (instance.more) {
			instance.results.appendChild(dom.create('li', { role: 'presentation' }, [
				dom.create('button', {
					type: 'button',
					class: 'lerm-ajax-select__load-more',
					onclick: (event) => {
						event.preventDefault();
						loadAjaxSelectOptions(instance, instance.currentQuery, Math.max(2, Math.ceil(instance.options.length / Math.max(instance.perPage, 1)) + 1), true);
					},
				}, [__('Load more', 'lerm-admin-config')]),
			]));
		}

		openAjaxSelectDropdown(instance);
		setAjaxSelectActiveIndex(instance, 0);
	};

	/**
	 * @param {AjaxSelectInstance} instance
	 * @param {string} query
	 * @param {number} [page]
	 * @param {boolean} [append]
	 */
	const loadAjaxSelectOptions = (instance, query, page = 1, append = false) => {
		if (!hasRestTransport() || !instance.schemaId || !instance.source) return Promise.resolve();

		instance.currentQuery = query;
		setAjaxSelectStatus(instance, __('Loading results...', 'lerm-admin-config'));

		return requestDataSource(instance.form, {
			schemaId: instance.schemaId,
			fieldId: instance.fieldId,
			search: query,
			page,
			perPage: instance.perPage,
			selected: !query && 1 === page ? instance.selections.map((selection) => selection.value) : [],
		}).then((response) => {
			if (!response?.success) {
				setAjaxSelectStatus(instance, response?.data?.message || __('Unable to save the settings right now.', 'lerm-admin-config'));
				return;
			}

			const nextOptions = Array.isArray(response?.data?.items)
				? response.data.items.map((item) => normalizeAjaxSelectOption(item)).filter(Boolean)
				: [];

			instance.more = !!response?.data?.more;
			renderAjaxSelectOptions(instance, append ? instance.options.concat(/** @type {AjaxSelectOption[]} */ (nextOptions)) : /** @type {AjaxSelectOption[]} */ (nextOptions));
			setAjaxSelectStatus(instance, nextOptions.length ? __('Start typing to search.', 'lerm-admin-config') : __('No matching results found.', 'lerm-admin-config'));
		}).catch(() => {
			setAjaxSelectStatus(instance, __('Unable to save the settings right now.', 'lerm-admin-config'));
		});
	};

	/**
	 * @param {AjaxSelectInstance} instance
	 */
	const hydrateAjaxSelectSelections = (instance) => {
		if (!instance.selections.length) {
			renderAjaxSelectSelections(instance, false);
			return;
		}

		requestDataSource(instance.form, {
			schemaId: instance.schemaId,
			fieldId: instance.fieldId,
			perPage: Math.max(instance.perPage, instance.selections.length),
			selected: instance.selections.map((selection) => selection.value),
		}).then((response) => {
			if (!response?.success || !Array.isArray(response?.data?.items)) {
				renderAjaxSelectSelections(instance, false);
				return;
			}

			const optionMap = new Map(
				response.data.items
					.map((item) => normalizeAjaxSelectOption(item))
					.filter(Boolean)
					.map((item) => [/** @type {AjaxSelectOption} */ (item).value, /** @type {AjaxSelectOption} */ (item)])
			);

			instance.selections = instance.selections.map((selection) => optionMap.get(selection.value) ?? selection);
			renderAjaxSelectSelections(instance, false);
		}).catch(() => {
			renderAjaxSelectSelections(instance, false);
		});
	};

	/**
	 * @param {HTMLElement} container
	 * @returns {AjaxSelectInstance}
	 */
	const createAjaxSelect = (container) => {
		const existing = ajaxSelectMap.get(container);
		if (existing) return existing;

		const values = /** @type {HTMLElement} */ (dom.find('[data-lerm-ajax-select-values]', container));
		const existingInputs = /** @type {HTMLInputElement[]} */ (dom.findAll('input[type="hidden"]', values));
		const existingName = existingInputs[0]?.name ?? '';
		const existingTemplate = existingInputs[0]?.getAttribute('data-name-template') ?? '';

		/** @type {AjaxSelectInstance} */
		const instance = {
			container,
			form: /** @type {HTMLFormElement|null} */ (container.closest('form')),
			fieldId: getData(container, 'target') || '',
			schemaId: getData(container, 'schemaId') || '',
			source: getData(container, 'source') || '',
			multiple: getData(container, 'multiple') === '1',
			allowClear: getData(container, 'allowClear') !== '0',
			perPage: Math.max(1, parseInt(getData(container, 'perPage') || '20', 10) || 20),
			minSearchLength: Math.max(0, parseInt(getData(container, 'minSearchLength') || '0', 10) || 0),
			search: /** @type {HTMLInputElement} */ (dom.find('.lerm-ajax-select__search', container)),
			selectedWrap: /** @type {HTMLElement} */ (dom.find('[data-lerm-ajax-select-selected]', container)),
			status: /** @type {HTMLElement} */ (dom.find('[data-lerm-ajax-select-status]', container)),
			dropdown: /** @type {HTMLElement} */ (dom.find('[data-lerm-ajax-select-dropdown]', container)),
			results: /** @type {HTMLElement} */ (dom.find('[data-lerm-ajax-select-results]', container)),
			values,
			selections: normalizeAjaxSelectSelections(existingInputs.filter((input) => input.value.trim() !== '').map((input) => input.value)),
			options: [],
			activeIndex: -1,
			more: false,
			searchTimer: null,
			currentQuery: '',
			inputName: existingName.replace(/\[\]$/, ''),
			inputNameTemplate: existingTemplate,
			syncUi: () => {
				renderAjaxSelectSelections(instance, false);
			},
		};

		instance.inputName = existingName.replace(/\[\]$/, '');
		if (instance.fieldId) {
			instance.results.id = instance.results.id || `${instance.fieldId}__results`;
		}
		instance.search.setAttribute('aria-controls', instance.results.id);
		instance.search.setAttribute('role', 'combobox');
		instance.search.setAttribute('aria-autocomplete', 'list');
		instance.search.setAttribute('aria-haspopup', 'listbox');
		instance.search.setAttribute('aria-expanded', 'false');

		instance.search.addEventListener('focus', () => {
			if (instance.currentQuery.length >= instance.minSearchLength || 0 === instance.minSearchLength) {
				loadAjaxSelectOptions(instance, instance.currentQuery || '');
			}
		});

		instance.search.addEventListener('input', () => {
			const query = String(instance.search.value || '').trim();
			instance.currentQuery = query;

			if (instance.searchTimer) clearTimeout(instance.searchTimer);

			if (query.length < instance.minSearchLength) {
				closeAjaxSelectDropdown(instance);
				setAjaxSelectStatus(
					instance,
					instance.minSearchLength > 0 ? __('Type more characters to search.', 'lerm-admin-config') : __('Start typing to search.', 'lerm-admin-config')
				);
				return;
			}

			instance.searchTimer = setTimeout(() => {
				loadAjaxSelectOptions(instance, query);
			}, 180);
		});

		instance.search.addEventListener('keydown', (event) => {
			const buttons = /** @type {HTMLButtonElement[]} */ (dom.findAll('[data-lerm-ajax-select-option]', instance.results));
			if (!buttons.length) return;

			switch (event.key) {
				case 'ArrowDown':
					event.preventDefault();
					openAjaxSelectDropdown(instance);
					setAjaxSelectActiveIndex(instance, Math.min(buttons.length - 1, instance.activeIndex + 1));
					break;
				case 'ArrowUp':
					event.preventDefault();
					openAjaxSelectDropdown(instance);
					setAjaxSelectActiveIndex(instance, Math.max(0, instance.activeIndex - 1));
					break;
				case 'Enter':
					if (instance.activeIndex < 0 || instance.activeIndex >= buttons.length) return;
					event.preventDefault();
					buttons[instance.activeIndex].click();
					break;
				case 'Escape':
					closeAjaxSelectDropdown(instance);
					break;
				default:
					break;
			}
		});

		ajaxSelectMap.set(container, instance);
		hydrateAjaxSelectSelections(instance);

		if (!ajaxSelectGlobalsBound) {
			ajaxSelectGlobalsBound = true;

			document.addEventListener('mousedown', (event) => {
				const target = event.target;
				if (!(target instanceof Node) || !activeAjaxSelect) return;
				if (activeAjaxSelect.container.contains(target)) return;
				closeAjaxSelectDropdown(activeAjaxSelect);
			});

			document.addEventListener('keydown', (event) => {
				if ('Escape' !== event.key || !activeAjaxSelect) return;
				closeAjaxSelectDropdown(activeAjaxSelect);
			});
		}

		return instance;
	};

	const ajaxSelectAdapter = {
		/** @param {HTMLElement} container */
		init(container) {
			if (getData(container, 'lermAjaxSelectReady') === '1') return createAjaxSelect(container);
			setData(container, 'lerm-ajax-select-ready', '1');
			return createAjaxSelect(container);
		},

		/**
		 * @param {HTMLElement|null} container
		 * @param {unknown} value
		 */
		setValue(container, value) {
			if (!(container instanceof HTMLElement)) return;
			const instance = this.init(container);
			instance.selections = normalizeAjaxSelectSelections(value);
			hydrateAjaxSelectSelections(instance);
		},
	};

	/** @param {Document|Element} scope */
	const initAjaxSelectFields = (scope) => {
		dom.findAll('.lerm-ajax-select', scope).forEach((container) => {
			if (container instanceof HTMLElement) {
				ajaxSelectAdapter.init(container);
			}
		});
	};

	/** @param {Document|Element} scope */
	const initNumberInputs = (scope) => {
		dom.findAll('.lerm-number-input', scope).forEach(el => {
			const wrap = /** @type {HTMLElement} */ (el);
			if (getData(wrap, 'lerm-number-ready') === '1') return;
			setData(wrap, 'lerm-number-ready', '1');

			const input = /** @type {HTMLInputElement|null} */ (dom.find('.lerm-number-input__control', wrap));
			if (!input) return;

			dom.findAll('[data-lerm-number-step]', wrap).forEach(buttonEl => {
				const button = /** @type {HTMLButtonElement} */ (buttonEl);
				button.addEventListener('click', (e) => {
					e.preventDefault();

					if (input.disabled || input.readOnly) return;

					const direction = getData(button, 'lerm-number-step');

					try {
						if (direction === 'down') input.stepDown();
						else input.stepUp();
					} catch {
						const current = Number(input.value || 0);
						const stepAttr = input.getAttribute('step') ?? '1';
						const step = stepAttr !== 'any' && Number.isFinite(Number(stepAttr)) ? Number(stepAttr) : 1;
						let next = current + (direction === 'down' ? -step : step);
						const min = Number(input.getAttribute('min'));
						const max = Number(input.getAttribute('max'));
						if (Number.isFinite(min)) next = Math.max(next, min);
						if (Number.isFinite(max)) next = Math.min(next, max);
						input.value = String(next);
					}

					input.dispatchEvent(new Event('input', { bubbles: true }));
					input.dispatchEvent(new Event('change', { bubbles: true }));
					input.focus({ preventScroll: true });
				});
			});
		});
	};

	/**
	 * @param {Element} preview
	 * @param {HTMLElement} removeButton
	 * @param {string} url
	 */
	const renderUploadPreview = (preview, removeButton, url) => {
		dom.empty(preview);
		const hasValue = Boolean(url);
		preview.hidden = !hasValue;
		removeButton.hidden = !hasValue;

		if (!hasValue) return;

		if (/\.(avif|gif|jpe?g|png|svg|webp)(\?.*)?$/i.test(url)) {
			preview.appendChild(dom.create('img', { src: url, alt: '' }));
			return;
		}

		const label = url.split('/').pop() || url;
		preview.appendChild(dom.create('a', {
			href: url,
			target: '_blank',
			rel: 'noopener noreferrer',
		}, [label]));
	};

	/** @param {Document|Element} scope */
	const initUploadFields = (scope) => {
		dom.findAll('.lerm-upload-field', scope).forEach(container => {
			if (getData(container, 'lerm-upload-ready') === '1') return;
			setData(container, 'lerm-upload-ready', '1');

			const input = /** @type {HTMLInputElement} */ (dom.find('.lerm-upload-field__input', container));
			const preview = /** @type {HTMLElement} */ (dom.find('.lerm-upload-field__preview', container));
			const removeButton = /** @type {HTMLButtonElement} */ (dom.find('.lerm-upload-field__remove', container));
			const selectButton = /** @type {HTMLButtonElement} */ (dom.find('.lerm-upload-field__select', container));
			const library = getData(container, 'library') || '';
			/** @type {any} */ let frame = null;

			renderUploadPreview(preview, removeButton, input.value);

			selectButton.addEventListener('click', (e) => {
				e.preventDefault();

				if (frame) {
					frame.open();
					return;
				}

				/** @type {any} */
				const config = {
					title: __('Choose file', 'lerm-admin-config'),
					button: { text: __('Use this file', 'lerm-admin-config') },
					multiple: false,
				};

				if (library && library !== 'all') {
					config.library = { type: library };
				}

				frame = wp.media(config);
				frame.on('select', () => {
					const attachment = frame.state().get('selection').first().toJSON();
					input.value = String(attachment.url || '');
					renderUploadPreview(preview, removeButton, input.value);
					input.dispatchEvent(new Event('change', { bubbles: true }));
				});
				frame.open();
			});

			removeButton.addEventListener('click', (e) => {
				e.preventDefault();
				input.value = '';
				renderUploadPreview(preview, removeButton, '');
				input.dispatchEvent(new Event('change', { bubbles: true }));
			});

			input.addEventListener('input', () => renderUploadPreview(preview, removeButton, input.value));
		});
	};

	/** @param {Document|Element} scope */
	const initRangeInputs = (scope) => {
		dom.findAll('.lerm-range-input', scope).forEach(el => {
			const wrap = /** @type {HTMLElement} */ (el);
			if (getData(wrap, 'lerm-range-ready') === '1') return;
			setData(wrap, 'lerm-range-ready', '1');

			const range = /** @type {HTMLInputElement|null} */ (dom.find('.lerm-range-input__range', wrap));
			const number = /** @type {HTMLInputElement|null} */ (dom.find('.lerm-range-input__number', wrap));
			if (!range || !number) return;

			range.addEventListener('input', () => {
				number.value = range.value;
				number.dispatchEvent(new Event('input', { bubbles: true }));
			});

			range.addEventListener('change', () => {
				number.value = range.value;
				number.dispatchEvent(new Event('change', { bubbles: true }));
			});

			number.addEventListener('input', () => {
				range.value = number.value;
			});

			number.addEventListener('change', () => {
				range.value = number.value;
			});
		});
	};

	// ─── Media Fields ─────────────────────────────────────────────────────────

	/**
	 * @param {Element} preview
	 * @param {HTMLElement} removeButton
	 * @param {string} imageUrl
	 */
	const renderMediaPreview = (preview, removeButton, imageUrl) => {
		dom.empty(preview);
		if (imageUrl) {
			preview.hidden = false;
			preview.appendChild(dom.create('img', { src: imageUrl, alt: '' }));
			removeButton.hidden = false;
		} else {
			preview.hidden = true;
			removeButton.hidden = true;
		}
	};

	/** @param {Document|Element} scope */
	const initMediaFields = (scope) => {
		dom.findAll('.lerm-media-field', scope).forEach(container => {
			if (getData(container, 'lerm-media-ready') === '1') return;
			setData(container, 'lerm-media-ready', '1');

			const input = /** @type {HTMLInputElement} */  (dom.find('input[type="hidden"]', container));
			const preview = /** @type {HTMLElement} */       (dom.find('.lerm-media-preview', container));
			const removeButton = /** @type {HTMLElement} */       (dom.find('.lerm-media-remove', container));
			const existingImage = /** @type {HTMLImageElement|null} */ (dom.find('img', preview));
			/** @type {any} */ let frame = null;

			renderMediaPreview(preview, removeButton, input.value ? (existingImage?.getAttribute('src') ?? '') : '');

			/** @type {HTMLElement} */ (dom.find('.lerm-media-select', container)).addEventListener('click', (e) => {
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({ title: __('Choose image', 'lerm-admin-config'), button: { text: __('Use this image', 'lerm-admin-config') }, library: { type: 'image' }, multiple: false });
				frame.on('select', () => {
					/** @type {WPAttachment} */
					const attachment = frame.state().get('selection').first().toJSON();
					const url = attachment.sizes?.medium?.url ?? attachment.url;
					input.value = String(attachment.id);
					renderMediaPreview(preview, removeButton, url);
					input.dispatchEvent(new Event('change', { bubbles: true }));
				});
				frame.open();
			});

			removeButton.addEventListener('click', (e) => {
				e.preventDefault();
				input.value = '';
				renderMediaPreview(preview, removeButton, '');
				input.dispatchEvent(new Event('change', { bubbles: true }));
			});
		});
	};

	// ─── Gallery Fields ───────────────────────────────────────────────────────

	/**
	 * @param {number} id
	 * @returns {Promise<WPAttachment|null>}
	 */
	const fetchAttachment = (id) => new Promise(resolve => {
		const model = wp.media.attachment(id);
		const done = () => resolve(/** @type {WPAttachment} */(model.toJSON()));
		model.get('url') ? done() : model.fetch({ success: done, error: () => resolve(null) });
	});

	/**
	 * @param {Element} preview
	 * @param {WPAttachment[]} attachments
	 */
	const renderGalleryPreview = (preview, attachments) => {
		dom.empty(preview);
		preview.hidden = attachments.length === 0;
		if (!attachments.length) return;
		attachments.forEach(a => {
			preview.appendChild(dom.create('img', { src: a.sizes?.thumbnail?.url ?? a.url, alt: '' }));
		});
	};

	/**
	 * @param {Element} preview
	 * @param {number[]} ids
	 */
	const renderGalleryByIds = (preview, ids) => {
		if (!ids.length) { renderGalleryPreview(preview, []); return; }
		Promise.all(ids.map(fetchAttachment)).then(list =>
			renderGalleryPreview(preview, /** @type {WPAttachment[]} */(list.filter(Boolean)))
		);
	};

	/** @param {Document|Element} scope */
	const initGalleryFields = (scope) => {
		dom.findAll('.lerm-gallery-field', scope).forEach(container => {
			if (getData(container, 'lerm-gallery-ready') === '1') return;
			setData(container, 'lerm-gallery-ready', '1');

			const input = /** @type {HTMLInputElement} */ (dom.find('input[type="hidden"]', container));
			const preview = /** @type {HTMLElement} */     (dom.find('.lerm-gallery-preview', container));
			const removeButton = /** @type {HTMLElement} */     (dom.find('.lerm-gallery-remove', container));
			const hasImages = input.value.split(',').map((id) => parseInt(id, 10)).filter(Boolean).length > 0;
			/** @type {any} */ let frame = null;

			preview.hidden = !hasImages;
			removeButton.hidden = !hasImages;

			/** @type {HTMLElement} */ (dom.find('.lerm-gallery-select', container)).addEventListener('click', (e) => {
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({ title: __('Choose images', 'lerm-admin-config'), button: { text: __('Use these images', 'lerm-admin-config') }, library: { type: 'image' }, multiple: true });
				frame.on('select', () => {
					/** @type {WPAttachment[]} */
					const attachments = frame.state().get('selection').toJSON();
					const ids = attachments.map(a => a.id);
					input.value = ids.join(',');
					renderGalleryPreview(preview, attachments);
					removeButton.hidden = ids.length === 0;
					input.dispatchEvent(new Event('change', { bubbles: true }));
				});
				frame.open();
			});

			removeButton.addEventListener('click', (e) => {
				e.preventDefault();
				input.value = '';
				renderGalleryPreview(preview, []);
				removeButton.hidden = true;
				input.dispatchEvent(new Event('change', { bubbles: true }));
			});
		});
	};

	// ─── Native Drag-and-Drop Sortable ────────────────────────────────────────

	/**
	 * Minimal native sortable — replaces jQuery UI sortable.
	 * Fires a custom 'sortupdate' event on the list element after a successful drop.
	 *
	 * @param {HTMLElement} list
	 * @param {{ handle?: string|null }} [options]
	 * @returns {SortableInstance}
	 */
	const makeSortable = (list, { handle: handleSel = null } = {}) => {
		/** @type {HTMLElement|null} */ let dragging = null;
		/** @type {HTMLElement|null} */ let placeholder = null;

		/** @param {HTMLElement} ref @returns {HTMLElement} */
		const createPlaceholder = (ref) => {
			const ph = document.createElement('div');
			ph.className = 'lerm-sortable-placeholder';
			ph.style.height = ref.offsetHeight + 'px';
			return ph;
		};

		/**
		 * @param {EventTarget|null} target
		 * @param {HTMLElement} item
		 * @returns {boolean}
		 */
		const isHandle = (target, item) => {
			if (!handleSel || !(target instanceof Element)) return true;
			return !!target.closest(handleSel) && item.contains(target);
		};

		list.addEventListener('mousedown', (e) => {
			const item = /** @type {Element} */ (e.target)?.closest('[draggable], li, [data-lerm-group-item], .lerm-sorter-item');
			if (!item || !list.contains(item)) return;
			if (!isHandle(e.target, /** @type {HTMLElement} */(item))) return;
			item.setAttribute('draggable', 'true');
		});

		list.addEventListener('dragstart', (e) => {
			const de = /** @type {DragEvent} */ (e);
			const item = /** @type {HTMLElement|null} */ (/** @type {Element} */ (e.target)?.closest('[draggable]'));
			if (!item || !list.contains(item)) return;
			dragging = item;
			placeholder = createPlaceholder(item);
			requestAnimationFrame(() => { if (dragging) dragging.classList.add('lerm-sortable-dragging'); });
			if (de.dataTransfer) de.dataTransfer.effectAllowed = 'move';
		});

		list.addEventListener('dragover', (e) => {
			e.preventDefault();
			const de = /** @type {DragEvent} */ (e);
			if (de.dataTransfer) de.dataTransfer.dropEffect = 'move';
			if (!dragging || !placeholder) return;
			const target = /** @type {Element|null} */ (/** @type {Element} */ (e.target)?.closest('li, [data-lerm-group-item], .lerm-sorter-item'));
			if (!target || target === dragging || target === placeholder || !list.contains(target)) return;
			const midY = target.getBoundingClientRect().top + target.getBoundingClientRect().height / 2;
			list.insertBefore(placeholder, de.clientY < midY ? target : target.nextSibling);
		});

		list.addEventListener('drop', (e) => {
			e.preventDefault();
			if (!dragging || !placeholder) return;
			list.insertBefore(dragging, placeholder);
			cleanup();
			list.dispatchEvent(new CustomEvent('sortupdate', { bubbles: true }));
		});

		list.addEventListener('dragend', () => cleanup());

		const cleanup = () => {
			if (dragging) {
				dragging.classList.remove('lerm-sortable-dragging');
				dragging.removeAttribute('draggable');
				dragging = null;
			}
			placeholder?.remove();
			placeholder = null;
		};

		return { destroy() { /* event listeners are on list; caller manages element lifecycle */ } };
	};

	/** @type {WeakMap<HTMLElement, SortableInstance>} */
	const sortableMap = new WeakMap();

	// ─── Sorters ──────────────────────────────────────────────────────────────

	/** @param {Document|Element} scope */
	const initSorters = (scope) => {
		dom.findAll('.lerm-sorter-list', scope).forEach(list => {
			if (!sortableMap.has(/** @type {HTMLElement} */(list))) {
				sortableMap.set(/** @type {HTMLElement} */(list), makeSortable(/** @type {HTMLElement} */(list), { handle: '.lerm-sorter-handle' }));
			}
		});
	};

	// ─── Groups ───────────────────────────────────────────────────────────────

	/**
	 * @param {string|null} template
	 * @param {number} index
	 * @returns {string}
	 */
	const replaceIndex = (template, index) => String(template ?? '').replace(/__INDEX__/g, String(index));

	/** @param {HTMLElement} group */
	const refreshGroupEmpty = (group) => {
		const emptyEl = /** @type {HTMLElement|null} */ (dom.find('.lerm-group__empty', group));
		if (emptyEl) emptyEl.hidden = dom.findAll('[data-lerm-group-item]', group).length > 0;
	};

	/** @param {HTMLElement} group */
	const renumberGroupItems = (group) => {
		dom.findAll('[data-lerm-group-item]', group).forEach((item, i) => {
			/** @type {HTMLElement} */ (item).dataset['index'] = String(i);
			const title = dom.find('.lerm-group-item__title', item);
			if (title) title.textContent = 'Item ' + (i + 1);
			dom.findAll('[data-field-path-template]', item).forEach(el => {
				const template = getData(el, 'fieldPathTemplate');
				if (template) setData(/** @type {HTMLElement} */ (el), 'field-path', replaceIndex(template, i));
			});
			dom.findAll('[data-name-template]', item).forEach(el => {
				const template = getData(el, 'nameTemplate');
				if (template) /** @type {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} */ (el).name = replaceIndex(template, i);
			});
			dom.findAll('[data-id-template]', item).forEach(el => {
				const template = getData(el, 'idTemplate');
				if (template) /** @type {HTMLElement} */ (el).id = replaceIndex(template, i);
			});
			dom.findAll('[data-for-template]', item).forEach(el => {
				const template = getData(el, 'forTemplate');
				if (template) /** @type {HTMLLabelElement} */ (el).htmlFor = replaceIndex(template, i);
			});
		});
		refreshGroupEmpty(group);
	};

	/** @param {Document|Element} scope */
	const initGroupChildren = (scope) => {
		initColorPickers(scope);
		initAjaxSelectFields(scope);
		initNumberInputs(scope);
		initRangeInputs(scope);
		initMediaFields(scope);
		initUploadFields(scope);
		initGalleryFields(scope);
		initCodeEditors(scope);
		initIconFields(scope);
		initAccordionFields(scope);
		initTabbedFields(scope);
	};

	/** @param {Document|Element} scope */
	const initGroups = (scope) => {
		dom.findAll('.lerm-group', scope).forEach(group => {
			const groupEl = /** @type {HTMLElement} */ (group);
			if (getData(groupEl, 'lerm-group-ready') === '1') { renumberGroupItems(groupEl); return; }
			setData(groupEl, 'lerm-group-ready', '1');

			const list = /** @type {HTMLElement} */      (dom.find('[data-lerm-group-list]', groupEl));
			const template = /** @type {HTMLElement|null} */ (dom.find('.lerm-group-template', groupEl));

			sortableMap.set(list, makeSortable(list, { handle: '.lerm-sorter-handle' }));
			list.addEventListener('sortupdate', () => {
				renumberGroupItems(groupEl);
				const form = /** @type {HTMLFormElement} */ (groupEl.closest('form'));
				form.dispatchEvent(new Event('sortupdate'));
				syncDirtyState(form);
			});

			/** @type {HTMLElement} */ (dom.find('[data-lerm-group-add]', groupEl)).addEventListener('click', (e) => {
				e.preventDefault();
				list.insertAdjacentHTML('beforeend', template?.innerHTML ?? '');
				renumberGroupItems(groupEl);
				initGroupChildren(groupEl);
				syncDirtyState(/** @type {HTMLFormElement} */(groupEl.closest('form')));
			});

			list.addEventListener('click', async (e) => {
				const btn = /** @type {HTMLElement} */ (e.target)?.closest('[data-lerm-group-remove]');
				if (!btn || !list.contains(btn)) return;
				e.preventDefault();
				if (!await confirmDialog(__('Remove this item?', 'lerm-admin-config'))) return;
    			/** @type {HTMLElement} */ (btn.closest('[data-lerm-group-item]')).remove();
				renumberGroupItems(groupEl);
				syncDirtyState(/** @type {HTMLFormElement} */(groupEl.closest('form')));
			});

			renumberGroupItems(groupEl);
		});
	};

	// ─── Code Editors ─────────────────────────────────────────────────────────

	/** @type {WeakMap<HTMLTextAreaElement, any>} */
	const codeEditorMap = new WeakMap();

	/** @param {Document|Element} scope */
	const initCodeEditors = (scope) => {
		if (!window['wp']?.codeEditor || !cfg.codeEditor) return;
		dom.findAll('.lerm-code-editor', scope).forEach(el => {
			const textarea = /** @type {HTMLTextAreaElement} */ (el);
			if (getData(textarea, 'lerm-editor-ready')) return;
			const editor = wp.codeEditor.initialize(textarea, { ...cfg.codeEditor });
			codeEditorMap.set(textarea, editor);
			setData(textarea, 'lerm-editor-ready', '1');
			editor?.codemirror?.on('change', () => {
				editor.codemirror.save();
				textarea.dispatchEvent(new Event('change'));
			});
		});
	};

	/** @param {Document|Element} scope */
	const refreshCodeEditors = (scope) => {
		dom.findAll('.lerm-code-editor', scope).forEach(el => {
			codeEditorMap.get(/** @type {HTMLTextAreaElement} */ (el))?.codemirror?.refresh();
		});
	};

	/**
	 * @param {Element} container
	 */
	const syncIconFieldState = (container) => {
		const preview = /** @type {HTMLElement|null} */ (dom.find('.lerm-icon-field__current-preview', container));
		const label = /** @type {HTMLElement|null} */ (dom.find('[data-lerm-icon-current-label]', container));
		const checked = /** @type {HTMLInputElement|null} */ (dom.find('input[type="radio"]:checked', container));
		const checkedItem = checked ? checked.closest('.lerm-icon-field__item') : null;
		const emptyLabel = getData(container, 'emptyLabel') || 'No icon selected';

		if (preview) {
			dom.empty(preview);
			if (checked?.value) {
				preview.appendChild(dom.create('span', {
					class: `dashicons ${checked.value}`,
					'aria-hidden': 'true',
				}));
			}
		}

		if (label) {
			label.textContent = checked
				? (dom.find('.lerm-icon-field__label', checkedItem ?? container)?.textContent ?? checked.value)
				: emptyLabel;
		}
	};

	/**
	 * @param {Element|null} container
	 * @param {unknown} value
	 */
	const applyIconValue = (container, value) => {
		if (!container) return;
		const current = String(value ?? '');
		dom.findAll('input[type="radio"]', container).forEach(el => {
			/** @type {HTMLInputElement} */ (el).checked = /** @type {HTMLInputElement} */ (el).value === current;
		});
		syncIconFieldState(container);
	};

	/** @param {Document|Element} scope */
	const initIconFields = (scope) => {
		dom.findAll('.lerm-icon-field', scope).forEach(container => {
			const field = /** @type {HTMLElement} */ (container);
			if (getData(field, 'lermIconReady') === '1') {
				syncIconFieldState(field);
				return;
			}

			setData(field, 'lermIconReady', '1');

			const search = /** @type {HTMLInputElement|null} */ (dom.find('.lerm-icon-field__search', field));

			if (search) {
				search.addEventListener('input', () => {
					const query = String(search.value || '').trim().toLowerCase();
					dom.findAll('.lerm-icon-field__item', field).forEach(item => {
						const haystack = getData(item, 'iconLabel') || '';
						/** @type {HTMLElement} */ (item).hidden = '' !== query && !haystack.includes(query);
					});
				});
			}

			field.addEventListener('change', (e) => {
				const target = /** @type {HTMLElement|null} */ (e.target instanceof HTMLElement ? e.target : null);
				if (!(target instanceof HTMLInputElement) || target.type !== 'radio') return;
				syncIconFieldState(field);
			});

			syncIconFieldState(field);
		});
	};

	/**
	 * @param {Element} item
	 * @param {boolean} isOpen
	 */
	const setAccordionItemState = (item, isOpen) => {
		const trigger = /** @type {HTMLButtonElement|null} */ (dom.find('[data-lerm-accordion-trigger]', item));
		const panel = /** @type {HTMLElement|null} */ (dom.find('[data-lerm-accordion-panel]', item));
		if (!trigger || !panel) return;

		trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		panel.hidden = !isOpen;
		/** @type {HTMLElement} */ (item).classList.toggle('is-open', isOpen);

		if (isOpen) refreshCodeEditors(panel);
	};

	/** @param {Document|Element} scope */
	const initAccordionFields = (scope) => {
		dom.findAll('[data-lerm-accordion]', scope).forEach(container => {
			const accordion = /** @type {HTMLElement} */ (container);
			if (getData(accordion, 'lermAccordionReady') !== '1') {
				setData(accordion, 'lermAccordionReady', '1');

				accordion.addEventListener('click', (e) => {
					const target = /** @type {HTMLElement|null} */ (e.target instanceof HTMLElement ? e.target : null);
					const trigger = /** @type {HTMLButtonElement|null} */ (target?.closest('[data-lerm-accordion-trigger]') ?? null);
					if (!trigger || !accordion.contains(trigger)) return;

					e.preventDefault();

					const item = trigger.closest('.lerm-accordion__item');
					if (!item) return;

					const nextState = trigger.getAttribute('aria-expanded') !== 'true';
					const allowMultiple = getData(accordion, 'allowMultiple') === '1';

					if (!allowMultiple && nextState) {
						dom.findAll('.lerm-accordion__item', accordion).forEach(otherItem => {
							if (otherItem === item) return;
							setAccordionItemState(otherItem, false);
						});
					}

					setAccordionItemState(item, nextState);
				});
			}

			dom.findAll('.lerm-accordion__item', accordion).forEach(item => {
				const trigger = dom.find('[data-lerm-accordion-trigger]', item);
				setAccordionItemState(item, trigger?.getAttribute('aria-expanded') === 'true');
			});
		});
	};

	/**
	 * @param {Element} container
	 * @param {string} targetId
	 */
	const activateTabbedField = (container, targetId) => {
		const triggers = dom.findAll('[data-lerm-tabbed-trigger]', container);
		const panels = dom.findAll('[data-lerm-tabbed-panel]', container);
		const fallbackTarget = getData(container, 'defaultTab')
			|| getData(triggers[0] ?? container, 'lermTabbedTarget')
			|| getData(panels[0] ?? container, 'lermTabbedPanel')
			|| '';
		const activeTarget = targetId || fallbackTarget;

		triggers.forEach(trigger => {
			const isActive = getData(trigger, 'lermTabbedTarget') === activeTarget;
			trigger.classList.toggle('is-active', isActive);
			trigger.setAttribute('aria-selected', isActive ? 'true' : 'false');
			trigger.setAttribute('tabindex', isActive ? '0' : '-1');
		});

		panels.forEach(panel => {
			const isActive = getData(panel, 'lermTabbedPanel') === activeTarget;
			/** @type {HTMLElement} */ (panel).hidden = !isActive;
			panel.classList.toggle('is-active', isActive);
			if (isActive) refreshCodeEditors(panel);
		});
	};

	/** @param {Document|Element} scope */
	const initTabbedFields = (scope) => {
		dom.findAll('[data-lerm-tabbed]', scope).forEach(container => {
			const tabbed = /** @type {HTMLElement} */ (container);
			if (getData(tabbed, 'lermTabbedReady') !== '1') {
				setData(tabbed, 'lermTabbedReady', '1');

				tabbed.addEventListener('click', (e) => {
					const target = /** @type {HTMLElement|null} */ (e.target instanceof HTMLElement ? e.target : null);
					const trigger = /** @type {HTMLButtonElement|null} */ (target?.closest('[data-lerm-tabbed-trigger]') ?? null);
					if (!trigger || !tabbed.contains(trigger)) return;

					e.preventDefault();
					activateTabbedField(tabbed, getData(trigger, 'lermTabbedTarget') || '');
				});

				tabbed.addEventListener('keydown', (e) => {
					const target = /** @type {HTMLElement|null} */ (e.target instanceof HTMLElement ? e.target : null);
					const trigger = /** @type {HTMLButtonElement|null} */ (target?.closest('[data-lerm-tabbed-trigger]') ?? null);
					if (!trigger || !tabbed.contains(trigger)) return;

					const triggers = /** @type {HTMLButtonElement[]} */ (dom.findAll('[data-lerm-tabbed-trigger]', tabbed));
					const index = triggers.indexOf(trigger);
					if (index < 0) return;

					let nextIndex = index;

					switch (e.key) {
						case 'ArrowRight':
						case 'ArrowDown':
							nextIndex = (index + 1) % triggers.length;
							break;
						case 'ArrowLeft':
						case 'ArrowUp':
							nextIndex = (index - 1 + triggers.length) % triggers.length;
							break;
						case 'Home':
							nextIndex = 0;
							break;
						case 'End':
							nextIndex = triggers.length - 1;
							break;
						default:
							return;
					}

					e.preventDefault();

					const nextTrigger = triggers[nextIndex];
					activateTabbedField(tabbed, getData(nextTrigger, 'lermTabbedTarget') || '');
					nextTrigger.focus();
				});
			}

			activateTabbedField(tabbed, getData(tabbed, 'defaultTab') || '');
		});
	};

	/**
	 * @param {string} text
	 * @returns {Promise<void>}
	 */
	const copyText = (text) => {
		if (navigator.clipboard?.writeText) {
			return navigator.clipboard.writeText(text);
		}

		return new Promise((resolve, reject) => {
			const textarea = document.createElement('textarea');
			textarea.value = text;
			textarea.setAttribute('readonly', 'true');
			textarea.style.position = 'fixed';
			textarea.style.top = '0';
			textarea.style.left = '-9999px';
			document.body.appendChild(textarea);
			textarea.focus();
			textarea.select();

			try {
				document.execCommand('copy');
				resolve();
			} catch (error) {
				reject(error);
			} finally {
				textarea.remove();
			}
		});
	};

	/** @param {Document|Element} scope */
	const initDebugPanels = (scope) => {
		dom.findAll('[data-lerm-debug-panel]', scope).forEach((panelEl) => {
			const panel = /** @type {HTMLElement} */ (panelEl);
			if (getData(panel, 'lermDebugReady') === '1') return;
			setData(panel, 'lerm-debug-ready', '1');

			const button = /** @type {HTMLButtonElement|null} */ (dom.find('[data-lerm-debug-copy]', panel));
			const json = /** @type {HTMLElement|null} */ (dom.find('[data-lerm-debug-json]', panel));

			if (!button || !json) return;

			button.addEventListener('click', () => {
				const defaultLabel = button.textContent || __('Copy JSON', 'lerm-admin-config');

				copyText(json.textContent || '')
					.then(() => {
						button.textContent = __('Copied', 'lerm-admin-config');
						window.setTimeout(() => {
							button.textContent = __('Copy JSON', 'lerm-admin-config') ;
						}, 1400);
					})
					.catch(() => {
						button.textContent = __('Unable to save the settings right now.', 'lerm-admin-config') ;
						window.setTimeout(() => {
							button.textContent = __('Copy JSON', 'lerm-admin-config') ;
						}, 1400);
					});
			});
		});
	};

	/** @param {HTMLFormElement} form */
	const triggerEditorSave = (form) => {
		window['tinyMCE']?.triggerSave?.();
		dom.findAll('.lerm-code-editor', form).forEach(el => {
			codeEditorMap.get(/** @type {HTMLTextAreaElement} */(el))?.codemirror?.save();
		});
	};

	// ─── Status & Flash ───────────────────────────────────────────────────────

	/** @param {HTMLFormElement} form @returns {Element|null} */
	const getPanel = (form) => form.closest('.lerm-settings-panel');

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} state
	 * @param {string} message
	 */
	const setStatus = (form, state, message) => {
		const pill = /** @type {HTMLElement|null} */ (
			dom.find('[data-lerm-status]', form)
			|| dom.find('[data-lerm-status]', getPanel(form) ?? form)
		);
		if (!pill) return;
		pill.dataset['lermStatus'] = state;
		pill.textContent = message;
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} type
	 * @param {string} message
	 */
	const showFlash = (form, type, message) => {
		if (!message) return;
		setStatus(form, type === 'error' ? 'error' : type === 'success' ? 'success' : 'idle', message);
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {boolean} busy
	 * @param {string} label
	 */
	const setBusy = (form, busy, label) => {
		setData(form, 'lerm-busy', busy ? '1' : '0');
		dom.findAll('button, input[type="submit"]', form).forEach(el => { /** @type {HTMLButtonElement} */ (el).disabled = busy; });
		dom.findAll('.lerm-settings-spinner', form).forEach(el => el.classList.toggle('is-active', busy));
		dom.findAll('[data-lerm-save]', form).forEach(el => {
			const btn = /** @type {HTMLElement} */ (el);
			if (busy) {
				if (!getData(btn, 'original-label')) setData(btn, 'original-label', btn.textContent ?? '');
				btn.textContent = label;
			} else {
				btn.textContent = getData(btn, 'original-label') || btn.textContent;
			}
		});
	};

	/** @param {HTMLFormElement} form @returns {boolean} */
	const isDirty = (form) => getData(form, 'lerm-dirty') === '1';

	/** @type {WeakMap<HTMLFormElement, Record<string, unknown>>} */
	const formSnapshotMap = new WeakMap();

	/** @type {WeakMap<HTMLElement, {
	 * activateSubsection: (subsectionId: string, pushState?: boolean) => void,
	 * currentSubsection: () => string,
	 * defaultSubsection: () => string,
	 * hasSubsection: (subsectionId: string) => boolean
	 * }>} */
	const subsectionControllerMap = new WeakMap();

	/** @type {((tabId: string, pushState?: boolean, requestedSubsection?: string) => void)|null} */
	let activateTabController = null;

	/**
	 * @param {HTMLFormElement} form
	 * @returns {HTMLFormElement[]}
	 */
	const pageForms = (form) => {
		const scope = /** @type {Element|Document} */ (form.closest('.lerm-settings-main') || document);
		return dom.findAll('.lerm-settings-form', scope);
	};

	/**
	 * @param {HTMLFormElement} form
	 * @returns {HTMLFormElement|null}
	 */
	const activePageForm = (form) => {
		const scope = /** @type {Element|Document} */ (form.closest('.lerm-settings-main') || document);
		return /** @type {HTMLFormElement|null} */ (dom.find('.lerm-settings-form', scope)) || form;
	};

	/**
	 * @param {HTMLFormElement} form
	 * @returns {boolean}
	 */
	const pageIsDirty = (form) => pageForms(form).some((pageForm) => isDirty(pageForm));

	const {
		cloneState,
		readFormState,
		stableStateString,
	} = createFormStateHelpers({ getOptionName });

	/** @param {HTMLFormElement} form */
	const saveFormSnapshot = (form) => {
		formSnapshotMap.set(form, cloneState(readFormState(form)));
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {string[]} fieldIds
	 */
	const mergeFormSnapshot = (form, fieldIds) => {
		const currentState = readFormState(form);
		const savedState = cloneState(formSnapshotMap.get(form) ?? {});

		fieldIds.forEach((fieldId) => {
			if (Object.prototype.hasOwnProperty.call(currentState, fieldId)) {
				savedState[fieldId] = currentState[fieldId];
				return;
			}

			delete savedState[fieldId];
		});

		formSnapshotMap.set(form, savedState);
	};

	/** @type {ReturnType<typeof setTimeout>|null} */
	let statusTimer = null;

	/** @param {HTMLFormElement} form */
	const queueReadyStatus = (form) => {
		if (statusTimer) clearTimeout(statusTimer);
		statusTimer = setTimeout(() => {
			const dirty = pageIsDirty(form);
			setStatus(form, dirty ? 'dirty' : 'idle', dirty ? __('Unsaved changes', 'lerm-admin-config') : __('Synced', 'lerm-admin-config'));
		}, 1800);
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {boolean} dirty
	 */
	const setDirty = (form, dirty) => {
		if (statusTimer) clearTimeout(statusTimer);
		setData(form, 'lerm-dirty', dirty ? '1' : '0');
		const pageDirty = pageIsDirty(form);
		setStatus(form, pageDirty ? 'dirty' : 'idle', pageDirty ? __('Unsaved changes', 'lerm-admin-config') : __('Synced', 'lerm-admin-config'));
	};

	/** @param {HTMLFormElement} form */
	const syncDirtyState = (form) => {
		const savedState = formSnapshotMap.get(form) ?? {};
		const dirty = stableStateString(readFormState(form)) !== stableStateString(savedState);
		setDirty(form, dirty);
	};

	// ─── REST Actions ──────────────────────────────────────────────────────────

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} endpoint
	 * @param {Record<string, string>} [extras]
	 * @returns {Promise<AjaxResponse>}
	 */
	const request = (form, endpoint, extras = {}) => {
		const body = new FormData(form);
		for (const [k, v] of Object.entries(extras)) body.set(k, v);

		const path = restActionPath(form, endpoint);
		if (hasRestTransport() && path) {
			if (endpoint === 'export') {
				return requestRest(path, { method: 'GET' });
			}

			return requestRest(path, { method: 'POST', body });
		}

		return Promise.resolve({ success: false, data: { message: __('Unable to save the settings right now.', 'lerm-admin-config') } });
	};

	/**
	 * @param {FormData} body
	 * @param {HTMLFormElement} sourceForm
	 */
	const appendOptionEntries = (body, sourceForm) => {
		const prefix = `${getOptionName(sourceForm)}[`;

		for (const [name, rawValue] of new FormData(sourceForm).entries()) {
			if (!String(name).startsWith(prefix)) continue;
			body.append(String(name), rawValue);
		}
	};

	/**
	 * Build a full-page request body that includes option fields from every tab.
	 *
	 * @param {HTMLFormElement} form
	 * @param {string} endpoint
	 * @param {Record<string, string>} [extras]
	 * @returns {Promise<AjaxResponse>}
	 */
	const requestPage = (form, endpoint, extras = {}) => {
		const body = new FormData(form);
		for (const [k, v] of Object.entries(extras)) body.set(k, v);

		pageForms(form).forEach((pageForm) => {
			if (pageForm === form) return;
			appendOptionEntries(body, pageForm);
		});

		const path = restActionPath(form, endpoint);
		if (hasRestTransport() && path) {
			return requestRest(path, { method: 'POST', body });
		}

		return Promise.resolve({ success: false, data: { message: __('Unable to save the settings right now.', 'lerm-admin-config') } });
	};

	// ─── Value Application ────────────────────────────────────────────────────

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} fieldId
	 * @param {unknown} value
	 */
	const applyColorValue = (form, fieldId, value) => {
		const input = findFieldById(form, fieldId);
		colorControlAdapter.setValue(input instanceof HTMLInputElement ? input : null, value);
	};

	/**
	 * @param {Element|null} container
	 * @param {any} value
	 */
	const applyMediaContainer = (container, value) => {
		if (!container) return;
		const input = /** @type {HTMLInputElement} */ (dom.find('input[type="hidden"]', container));
		const preview = /** @type {HTMLElement} */     (dom.find('.lerm-media-preview', container));
		const removeButton = /** @type {HTMLElement} */     (dom.find('.lerm-media-remove', container));
		input.value = String(value?.id ?? '');
		renderMediaPreview(preview, removeButton, value?.thumbnail ?? value?.url ?? '');
	};

	/**
	 * @param {Element|null} container
	 * @param {unknown} ids
	 */
	const applyGalleryContainer = (container, ids) => {
		if (!container) return;
		const clean = (
			Array.isArray(ids)
				? ids
				: (typeof ids === 'object' && ids && 'ids' in /** @type {Record<string, unknown>} */ (ids))
					? String(/** @type {Record<string, unknown>} */ (ids).ids ?? '').split(',')
					: (typeof ids === 'string' ? ids.split(',') : [])
		).map((id) => parseInt(String(id), 10)).filter(Boolean);
		/** @type {HTMLInputElement} */ (dom.find('input[type="hidden"]', container)).value = clean.join(',');
		/** @type {HTMLElement} */      (dom.find('.lerm-gallery-remove', container)).hidden = clean.length === 0;
		renderGalleryByIds(/** @type {Element} */(dom.find('.lerm-gallery-preview', container)), clean);
	};

	/**
	 * Apply single or multi-select values.
	 *
	 * @param {HTMLSelectElement|null} select
	 * @param {unknown} value
	 */
	const applySelectValue = (select, value) => {
		if (!select) return;
		if (!select.multiple) {
			select.value = String(value ?? '');
			return;
		}

		const selected = Array.isArray(value) ? value.map((item) => String(item)) : [];
		Array.from(select.options).forEach((option) => {
			option.selected = selected.includes(option.value);
		});
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} fieldId
	 * @param {SorterValue} value
	 */
	const applySorterValue = (form, fieldId, value) => {
		const container = dom.find(`.lerm-sorter[data-target="${fieldId}"]`, form);
		if (!container) return;
		const list = /** @type {HTMLElement} */ (dom.find('.lerm-sorter-list', container));
		const enabled = value?.enabled ? Object.keys(value.enabled) : [];
		const order = enabled.concat(value?.disabled ? Object.keys(value.disabled) : []);
		/** @type {Record<string, HTMLElement>} */ const items = {};
		dom.findAll('.lerm-sorter-item', container).forEach(item => {
			items[/** @type {HTMLInputElement} */ (dom.find('input[type="hidden"]', item)).value] = /** @type {HTMLElement} */ (item);
		});
		order.forEach(key => { if (items[key]) list.appendChild(items[key]); });
		dom.findAll('input[type="checkbox"]', list).forEach(el => {
			/** @type {HTMLInputElement} */ (el).checked = enabled.includes(String(/** @type {HTMLInputElement} */(el).value));
		});
	};

	/**
	 * Apply a value to a scoped sub-field (inside a fieldset or group item).
	 * @param {Element} scope
	 * @param {string|null} fieldType
	 * @param {unknown} value
	 */
	const applyScopedValue = (scope, fieldType, value) => {
		switch (String(fieldType ?? 'text')) {
			case 'switcher':
				/** @type {HTMLInputElement} */ (dom.find('input[type="checkbox"]', scope)).checked = !!value;
				break;
			case 'color':
				colorControlAdapter.setValue(/** @type {HTMLInputElement|null} */ (dom.find('.lerm-color-field', scope)), value);
				break;
			case 'button_set':
			case 'radio':
				dom.findAll('input[type="radio"]', scope).forEach(el => { /** @type {HTMLInputElement} */ (el).checked = /** @type {HTMLInputElement} */ (el).value === String(value); });
				break;
			case 'icon':
				applyIconValue(dom.find('.lerm-icon-field', scope), value);
				break;
			case 'ajax_select':
				ajaxSelectAdapter.setValue(/** @type {HTMLElement|null} */ (dom.find('.lerm-ajax-select', scope)), value);
				break;
			case 'select':
				applySelectValue(/** @type {HTMLSelectElement|null} */ (dom.find('select', scope)), value);
				break;
			case 'fieldset':
			case 'typography':
				applyNestedValueMap(scope, value);
				break;
			case 'textarea':
				/** @type {HTMLTextAreaElement} */ (dom.find('textarea', scope)).value = String(value || '');
				break;
			case 'code_editor': {
				const textarea = /** @type {HTMLTextAreaElement|null} */ (dom.find('textarea', scope));
				if (textarea) {
					textarea.value = String(value || '');
					codeEditorMap.get(textarea)?.codemirror?.setValue(String(value || ''));
				}
				break;
			}
			case 'wp_editor': {
				const textarea = /** @type {HTMLTextAreaElement|null} */ (dom.find('textarea', scope));
				if (textarea) textarea.value = String(value || '');
				break;
			}
			case 'media':
				applyMediaContainer(dom.find('.lerm-media-field', scope), value);
				break;
			case 'gallery':
				applyGalleryContainer(dom.find('.lerm-gallery-field', scope), value);
				break;
			default: {
				const control = /** @type {HTMLInputElement|HTMLTextAreaElement|null} */ (dom.find('input:not([type="hidden"]), textarea', scope));
				if (control) control.value = String(value ?? '');
				break;
			}
		}
	};

	/**
	 * @param {Element|null} container
	 * @param {unknown} value
	 */
	const applyNestedValueMap = (container, value) => {
		if (!container || typeof value !== 'object' || !value) return;
		for (const [subfieldId, subfieldValue] of Object.entries(value)) {
			const scope = dom.find(`[data-subfield-id="${subfieldId}"]`, container);
			if (scope) applyScopedValue(scope, getData(scope, 'fieldType'), subfieldValue);
		}
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} fieldId
	 * @param {Record<string, unknown>} value
	 */
	const applyFieldsetValue = (form, fieldId, value) => {
		applyNestedValueMap(dom.find(`.lerm-fieldset[data-target="${fieldId}"]`, form), value);
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} fieldId
	 * @param {unknown} value
	 * @param {string} selector
	 */
	const applyPanelFieldValue = (form, fieldId, value, selector) => {
		const container = dom.find(`${selector}[data-target="${fieldId}"]`, form);
		if (!container || typeof value !== 'object' || !value) return;

		for (const [itemId, itemValues] of Object.entries(value)) {
			const item = dom.find(`[data-item-id="${itemId}"]`, container);
			if (!item) continue;
			applyNestedValueMap(item, itemValues);
		}

		if (container.matches('[data-lerm-accordion]')) initAccordionFields(container);
		if (container.matches('[data-lerm-tabbed]')) initTabbedFields(container);
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} fieldId
	 * @param {unknown} items
	 */
	const applyGroupValue = (form, fieldId, items) => {
		const group = /** @type {HTMLElement|null} */ (dom.find(`.lerm-group[data-target="${fieldId}"]`, form));
		if (!group) return;
		const list = /** @type {HTMLElement} */      (dom.find('[data-lerm-group-list]', group));
		const template = /** @type {HTMLElement|null} */ (dom.find('.lerm-group-template', group));
		const rows = /** @type {Record<string, unknown>[]} */ (Array.isArray(items) ? items : []);

		dom.empty(list);
		rows.forEach(() => list.insertAdjacentHTML('beforeend', template?.innerHTML ?? ''));
		renumberGroupItems(group);
		initGroupChildren(group);

		dom.findAll('[data-lerm-group-item]', group).forEach((item, i) => {
			const rowData = rows[i] ?? {};
			for (const [subfieldId, subfieldValue] of Object.entries(rowData)) {
				const scope = dom.find(`[data-subfield-id="${subfieldId}"]`, item);
				if (scope) applyScopedValue(scope, getData(scope, 'fieldType'), subfieldValue);
			}
		});
		refreshGroupEmpty(group);
	};

	/**
	 * Apply a batch of field values returned from the server back to the form.
	 * @param {HTMLFormElement} form
	 * @param {Record<string, unknown>} values
	 */
	const applyFieldValues = (form, values = {}) => {
		for (const [fieldId, value] of Object.entries(values)) {
			const row = dom.find(`[data-field-id="${fieldId}"]`, form);
			const fieldType = row ? getData(row, 'fieldType') : '';
			const input = findFieldById(form, fieldId);

			switch (fieldType) {
				case 'switcher':
					if (input instanceof HTMLInputElement) input.checked = !!value;
					break;
				case 'color':
					applyColorValue(form, fieldId, value);
					break;
				case 'button_set':
				case 'radio': {
					getNamedControls(form, buildFieldName(form, fieldId)).forEach((control) => {
						if (control instanceof HTMLInputElement && control.type === 'radio') {
							control.checked = control.value === String(value ?? '');
						}
					});
					break;
				}
				case 'icon':
					applyIconValue(dom.find(`.lerm-icon-field[data-target="${fieldId}"]`, form), value);
					break;
				case 'ajax_select':
					ajaxSelectAdapter.setValue(/** @type {HTMLElement|null} */ (dom.find(`.lerm-ajax-select[data-target="${fieldId}"]`, form)), value);
					break;
				case 'select':
					applySelectValue(input instanceof HTMLSelectElement ? input : null, value);
					break;
				case 'checkbox_list':
					getNamedControls(form, `${buildFieldName(form, fieldId)}[]`).forEach((control) => {
						if (!(control instanceof HTMLInputElement)) return;
						control.checked = Array.isArray(value) && value.map((item) => String(item)).includes(String(control.value));
					});
					break;
				case 'media':
					applyMediaContainer(dom.find(`.lerm-media-field[data-target="${fieldId}"]`, form), value);
					break;
				case 'gallery':
					applyGalleryContainer(dom.find(`.lerm-gallery-field[data-target="${fieldId}"]`, form), value);
					break;
				case 'sorter':
					applySorterValue(form, fieldId, /** @type {SorterValue} */(value));
					break;
				case 'fieldset':
				case 'typography':
					applyFieldsetValue(form, fieldId, /** @type {Record<string, unknown>} */(value));
					break;
				case 'group':
					applyGroupValue(form, fieldId, value);
					break;
				case 'accordion':
					applyPanelFieldValue(form, fieldId, value, '.lerm-accordion-field');
					break;
				case 'tabbed':
					applyPanelFieldValue(form, fieldId, value, '.lerm-tabbed-field');
					break;
				case 'backup_tools':
					break;
				case 'code_editor':
					if (input instanceof HTMLTextAreaElement) {
						input.value = String(value || '');
						codeEditorMap.get(input)?.codemirror?.setValue(String(value || ''));
					}
					break;
				case 'wp_editor': {
					const ta = /** @type {HTMLTextAreaElement|undefined} */ (
						getNamedControls(form, buildFieldName(form, fieldId)).find((control) => control instanceof HTMLTextAreaElement)
					);
					if (ta) ta.value = String(value || '');
					window['tinyMCE']?.get('lerm-' + fieldId)?.setContent(String(value || ''));
					break;
				}
				default:
					if (input) input.value = String(value ?? '');
					else {
						const el = getNamedControls(form, buildFieldName(form, fieldId))[0];
						if (el) el.value = String(value);
					}
					break;
			}
		}
		toggleDependencies(form);
	};

	/**
	 * @param {HTMLElement} row
	 */
	const clearFieldErrorRow = (row) => {
		row.classList.remove('is-invalid');
		row.removeAttribute('data-lerm-field-error');
		dom.findAll('[data-lerm-field-error-message]', row).forEach((el) => el.remove());
		dom.findAll('input, select, textarea', row).forEach((el) => el.removeAttribute('aria-invalid'));
	};

	/**
	 * @param {HTMLElement} target
	 */
	const clearFieldErrorAncestors = (target) => {
		/** @type {HTMLElement|null} */ (target.closest('.lerm-group-item'))?.classList.remove('is-invalid');
		/** @type {HTMLElement|null} */ (target.closest('.lerm-accordion__item'))?.classList.remove('is-invalid');
		/** @type {HTMLElement|null} */ (target.closest('.lerm-tabbed__panel'))?.classList.remove('is-invalid');
		/** @type {HTMLElement|null} */ (target.closest('.lerm-fieldset'))?.classList.remove('is-invalid');
		/** @type {HTMLElement|null} */ (target.closest('.lerm-group'))?.classList.remove('is-invalid');

		const tabbedPanel = /** @type {HTMLElement|null} */ (target.closest('.lerm-tabbed__panel'));
		if (tabbedPanel) {
			const tabbed = /** @type {HTMLElement|null} */ (tabbedPanel.closest('[data-lerm-tabbed]'));
			const tabId = getData(tabbedPanel, 'lermTabbedPanel');
			if (tabbed && tabId) {
				const trigger = /** @type {HTMLElement|null} */ (dom.find(`[data-lerm-tabbed-target="${tabId}"]`, tabbed));
				trigger?.classList.remove('is-invalid');
			}
		}
	};

	/**
	 * @param {HTMLElement} target
	 * @returns {HTMLElement}
	 */
	const fieldErrorHost = (target) => /** @type {HTMLElement} */ (
		dom.find('.lerm-settings-row__body', target)
		|| dom.find('td', target)
		|| target
	);

	/** @param {HTMLFormElement} form */
	const clearFieldErrors = (form) => {
		dom.findAll('[data-field-path]', form).forEach((row) => clearFieldErrorRow(/** @type {HTMLElement} */ (row)));
		dom.findAll('.lerm-group-item.is-invalid, .lerm-accordion__item.is-invalid, .lerm-tabbed__panel.is-invalid, .lerm-tabbed__trigger.is-invalid, .lerm-fieldset.is-invalid, .lerm-group.is-invalid', form)
			.forEach((el) => el.classList.remove('is-invalid'));
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} fieldId
	 * @returns {HTMLElement|null}
	 */
	const findFieldRow = (form, fieldId) => {
		for (const row of dom.findAll('[data-field-id]', form)) {
			if (getData(row, 'field-id') === fieldId) return /** @type {HTMLElement} */ (row);
		}

		return null;
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} fieldPath
	 * @returns {HTMLElement|null}
	 */
	const findFieldPathTarget = (form, fieldPath) => {
		for (const row of dom.findAll('[data-field-path]', form)) {
			if (getData(row, 'fieldPath') === fieldPath) return /** @type {HTMLElement} */ (row);
		}

		const fallbackFieldId = String(fieldPath || '').split('.')[0] || '';
		return fallbackFieldId ? findFieldRow(form, fallbackFieldId) : null;
	};

	/**
	 * @param {HTMLElement} target
	 */
	const revealErrorTarget = (target) => {
		const accordionItem = /** @type {HTMLElement|null} */ (target.closest('.lerm-accordion__item'));
		if (accordionItem) setAccordionItemState(accordionItem, true);

		const tabbedPanel = /** @type {HTMLElement|null} */ (target.closest('.lerm-tabbed__panel'));
		if (tabbedPanel) {
			const tabbed = /** @type {HTMLElement|null} */ (tabbedPanel.closest('[data-lerm-tabbed]'));
			const tabId = getData(tabbedPanel, 'lermTabbedPanel') || '';
			if (tabbed && tabId) {
				activateTabbedField(tabbed, tabId);
				const trigger = /** @type {HTMLElement|null} */ (dom.find(`[data-lerm-tabbed-target="${tabId}"]`, tabbed));
				trigger?.classList.add('is-invalid');
			}
		}

		/** @type {HTMLElement|null} */ (target.closest('.lerm-group-item'))?.classList.add('is-invalid');
		/** @type {HTMLElement|null} */ (target.closest('.lerm-fieldset'))?.classList.add('is-invalid');
		/** @type {HTMLElement|null} */ (target.closest('.lerm-group'))?.classList.add('is-invalid');
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {Record<string, string|string[]>} fieldErrors
	 * @param {boolean} [scrollIntoViewEnabled]
	 */
	const applyFieldErrors = (form, fieldErrors = {}, scrollIntoViewEnabled = true) => {
		clearFieldErrors(form);

		let firstRow = null;

		for (const [fieldPath, rawMessage] of Object.entries(fieldErrors)) {
			const row = findFieldPathTarget(form, String(fieldPath));
			if (!row) continue;

			const message = (Array.isArray(rawMessage) ? rawMessage : [rawMessage])
				.map((item) => String(item || '').trim())
				.filter(Boolean)
				.join(' ');

			if (!message) continue;

			revealErrorTarget(row);
			if (!firstRow) firstRow = row;

			row.classList.add('is-invalid');
			setData(row, 'lerm-field-error', '1');
			fieldErrorHost(row).appendChild(dom.create('p', {
				class: 'lerm-field-error',
				'data-lerm-field-error-message': '1',
			}, [message]));

			dom.findAll('input, select, textarea', row).forEach((el) => el.setAttribute('aria-invalid', 'true'));
		}

		if (scrollIntoViewEnabled) {
			firstRow?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		}
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {boolean} busy
	 * @param {string} label
	 */
	const setBusyAcrossPage = (form, busy, label) => {
		pageForms(form).forEach((pageForm) => setBusy(pageForm, busy, label));
	};

	/** @param {HTMLFormElement} form */
	const triggerEditorSaveAcrossPage = (form) => {
		pageForms(form).forEach((pageForm) => triggerEditorSave(pageForm));
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {Record<string, unknown>} values
	 */
	const applyPageValues = (form, values = {}) => {
		pageForms(form).forEach((pageForm) => {
			clearFieldErrors(pageForm);
			applyFieldValues(pageForm, values);
			saveFormSnapshot(pageForm);
			syncDirtyState(pageForm);
		});
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {Record<string, string|string[]>} fieldErrors
	 * @param {AjaxResponse} response
	 */
	const applyPageErrors = (form, fieldErrors, response) => {
		pageForms(form).forEach((pageForm) => applyFieldErrors(pageForm, fieldErrors, false));

		const targetTab = String(response?.data?.tab ?? '');
		const targetSubsection = String(response?.data?.subsection ?? '');

		if (targetTab && activateTabController) {
			activateTabController(targetTab, true, targetSubsection);
		}

		const focusForm = activePageForm(form) || form;
		applyFieldErrors(focusForm, fieldErrors, true);
		setStatus(focusForm, 'error', response?.data?.message || __('Unable to save the settings right now.', 'lerm-admin-config'));
	};

	// ─── Backup Tools ─────────────────────────────────────────────────────────

	/** @param {HTMLFormElement} form */
	const bindBackupTools = (form) => {
		const exportBtn = /** @type {HTMLElement|null} */ (dom.find('[data-lerm-backup-export]', form));
		if (exportBtn) {
			exportBtn.addEventListener('click', (e) => {
				e.preventDefault();
				request(form, 'export').then(response => {
					if (!response?.success) {
						setStatus(form, 'error', response?.data?.message || __('Unable to save the settings right now.', 'lerm-admin-config'));
						return;
					}
					/** @type {HTMLInputElement} */ (dom.find('[data-lerm-backup-export-output]', form)).value = response.data.json || '';
					setStatus(form, 'success', response.data.message || __('Current settings snapshot generated.', 'lerm-admin-config'));
					queueReadyStatus(form);
				}).catch(() => setStatus(form, 'error', __('Unable to save the settings right now.', 'lerm-admin-config')));
			});
		}

		const importBtn = /** @type {HTMLElement|null} */ (dom.find('[data-lerm-backup-import]', form));
		if (importBtn) {
			importBtn.addEventListener('click', async (e) => {
				e.preventDefault();
				if (!await confirmDialog(__('Importing will overwrite the current saved settings. Continue?', 'lerm-admin-config'))) return;
				const json = String(/** @type {HTMLInputElement|null} */(dom.find('[data-lerm-backup-import-input]', form))?.value ?? '');
				pageForms(form).forEach((pageForm) => clearFieldErrors(pageForm));
				setBusyAcrossPage(form, true, __('Resetting...', 'lerm-admin-config'));
				setStatus(form, 'saving', __('Saving...', 'lerm-admin-config'));
				request(form, 'import', { backup_json: json })
					.then(response => handlePageSaveResponse(form, response, __('Settings imported successfully.', 'lerm-admin-config')))
					.catch(() => setStatus(form, 'error', __('Unable to import the provided settings JSON.', 'lerm-admin-config')))
					.finally(() => setBusyAcrossPage(form, false, __('Resetting...', 'lerm-admin-config')));
			});
		}
	};

	// ─── REST Form ────────────────────────────────────────────────────────────

	/**
	 * @param {HTMLFormElement} form
	 * @param {AjaxResponse} response
	 * @param {string} successMsg
	 */
	const handleSaveResponse = (form, response, successMsg, partialFieldIds = null) => {
		if (!response?.success) {
			applyFieldErrors(form, /** @type {Record<string, string|string[]>} */ (response?.data?.errors ?? response?.data?.fieldErrors ?? {}));
			setStatus(form, 'error', response?.data?.message || __('Unable to save the settings right now.', 'lerm-admin-config'));
			return;
		}
		clearFieldErrors(form);
		applyFieldValues(form, response.data.values ?? {});
		if (Array.isArray(partialFieldIds) && partialFieldIds.length) {
			mergeFormSnapshot(form, partialFieldIds);
		} else {
			saveFormSnapshot(form);
		}
		syncDirtyState(form);
		setStatus(form, 'success', response.data.message || successMsg);
		queueReadyStatus(form);
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {AjaxResponse} response
	 * @param {string} successMsg
	 */
	const handlePageSaveResponse = (form, response, successMsg) => {
		if (!response?.success) {
			applyPageErrors(
				form,
				/** @type {Record<string, string|string[]>} */ (response?.data?.errors ?? response?.data?.fieldErrors ?? {}),
				response,
			);
			return;
		}

		applyPageValues(form, response.data.values ?? {});
		setStatus(form, 'success', response.data.message || successMsg);
		queueReadyStatus(form);
	};

	/** @param {HTMLFormElement} form */
	const bindAjaxForm = (form) => {
		form.addEventListener('submit', (e) => {
			e.preventDefault();
			triggerEditorSaveAcrossPage(form);
			pageForms(form).forEach((pageForm) => clearFieldErrors(pageForm));
			setBusyAcrossPage(form, true, __('Saving...', 'lerm-admin-config'));
			setStatus(form, 'saving', __('Saving...', 'lerm-admin-config'));
			requestPage(form, 'save')
				.then(r => handlePageSaveResponse(form, r, __('Settings saved.', 'lerm-admin-config')))
				.catch(() => setStatus(form, 'error', __('Unable to save the settings right now.', 'lerm-admin-config')))
				.finally(() => setBusyAcrossPage(form, false, __('Saving...', 'lerm-admin-config')));
		});

		dom.findAll('[data-lerm-reset]', form).forEach(btn => {
			btn.addEventListener('click', async (e) => {
				e.preventDefault();
				const scope = getData(btn, 'lerm-reset') === 'all' ? 'all' : 'section';
				if (!await confirmDialog(scope === 'all' ? __('Reset every section on this page back to default values?', 'lerm-admin-config') : __('Reset the current page back to its default values?', 'lerm-admin-config'))) return;
				triggerEditorSave(form);
				clearFieldErrors(form);
				setBusy(form, true, __('Resetting...', 'lerm-admin-config'));
				setStatus(form, 'resetting', __('Resetting...', 'lerm-admin-config'));
				request(form, 'reset', { reset_scope: scope })
					.then(r => {
						const partialFieldIds = r?.data?.scope === 'subsection'
							? Object.keys(/** @type {Record<string, unknown>} */ (r.data.values ?? {}))
							: null;
						handleSaveResponse(form, r, scope === 'all' ? __('All sections have been reset to defaults.', 'lerm-admin-config') : __('The current page has been reset to defaults.', 'lerm-admin-config'), partialFieldIds);
						// After a full reset, reload values for every other tab form too.
						if (scope === 'all' && r?.success) {
							dom.findAll('.lerm-settings-form').forEach(otherEl => {
								const otherForm = /** @type {HTMLFormElement} */ (otherEl);
								if (otherForm === form) return;
								// Silently re-fetch defaults for this tab.
								request(otherForm, 'reset', { reset_scope: 'fetch_only' })
									.then(r2 => {
										if (!r2?.success) return;
										applyFieldValues(otherForm, r2.data.values ?? {});
										saveFormSnapshot(otherForm);
										syncDirtyState(otherForm);
									})
									.catch(() => { /* best-effort, ignore */ });
							});
						}
					})
					.catch(() => setStatus(form, 'error', __('Unable to reset the settings right now.', 'lerm-admin-config')))
					.finally(() => setBusy(form, false, __('Saving...', 'lerm-admin-config')));
			});
		});

		form.addEventListener('input', (e) => {
			syncDirtyState(form);
			const row = /** @type {HTMLElement|null} */ ((/** @type {HTMLElement} */ (e.target))?.closest('[data-field-path]'));
			if (row) {
				clearFieldErrorRow(row);
				clearFieldErrorAncestors(row);
			}
		});
		form.addEventListener('change', (e) => {
			syncDirtyState(form);
			const row = /** @type {HTMLElement|null} */ ((/** @type {HTMLElement} */ (e.target))?.closest('[data-field-path]'));
			if (row) {
				clearFieldErrorRow(row);
				clearFieldErrorAncestors(row);
			}
		});

		const sorterList = /** @type {HTMLElement|null} */ (dom.find('.lerm-sorter-list', form));
		if (sorterList) sorterList.addEventListener('sortupdate', () => syncDirtyState(form));
	};

	const initSubsectionSwitching = () => {
		dom.findAll('[data-tab-panel]').forEach(panelEl => {
			const tabPanel = /** @type {HTMLElement} */ (panelEl);
			const navItems = dom.findAll('[data-subsection-target]', tabPanel);
			const panels = dom.findAll('[data-subsection-panel]', tabPanel);
			if (!navItems.length || !panels.length) return;
			const form = /** @type {HTMLFormElement|null} */ (dom.find('.lerm-settings-form', tabPanel.closest('.lerm-settings-main') || document));
			const formTabId = tabPanel.getAttribute('data-tab-panel') ?? '';
			const subsectionInput = form
				? /** @type {HTMLInputElement|null} */ (dom.find('[data-lerm-current-subsection]', form))
				: null;

			/**
			 * @param {string} subsectionId
			 * @returns {boolean}
			 */
			const hasSubsection = (subsectionId) => navItems.some((item) => item.getAttribute('data-subsection-target') === subsectionId);

			/**
			 * @param {string} subsectionId
			 * @param {boolean} [pushState]
			 */
			const activateSubsection = (subsectionId, pushState = false) => {
				panels.forEach(panelEl => {
					const panel = /** @type {HTMLElement} */ (panelEl);
					panel.hidden = panel.getAttribute('data-subsection-panel') !== subsectionId;
				});

				navItems.forEach(item => {
					const active = item.getAttribute('data-subsection-target') === subsectionId;
					item.classList.toggle('is-active', active);
					item.setAttribute('aria-pressed', active ? 'true' : 'false');
				});

				const activePanel = /** @type {HTMLElement|null} */ (dom.find(`[data-subsection-panel="${subsectionId}"]`, tabPanel));
				if (activePanel) {
					dom.findAll('.lerm-code-editor', activePanel).forEach(editor => {
						codeEditorMap.get(/** @type {HTMLTextAreaElement} */ (editor))?.codemirror?.refresh();
					});
				}

				setData(tabPanel, 'current-subsection', subsectionId);

				const currentTabInput = form
					? /** @type {HTMLInputElement|null} */ (dom.find('[data-lerm-current-tab]', form))
					: null;

				if (subsectionInput && currentTabInput?.value === formTabId) {
					subsectionInput.value = subsectionId;
				}

				if (pushState && formTabId) writeLocationState(formTabId, subsectionId);

				queueStickyActionsSync();
			};

			subsectionControllerMap.set(tabPanel, {
				activateSubsection,
				currentSubsection: () => getData(tabPanel, 'currentSubsection') || '',
				defaultSubsection: () => (
					navItems[0]?.getAttribute('data-subsection-target')
					?? panels[0]?.getAttribute('data-subsection-panel')
					?? ''
				),
				hasSubsection,
			});

			navItems.forEach(item => {
				item.addEventListener('click', (e) => {
					e.preventDefault();
					const subsectionId = item.getAttribute('data-subsection-target') ?? '';
					if (subsectionId) activateSubsection(subsectionId, true);
				});
			});

			const locationState = readLocationState();
			const requestedSubsection = locationState.tab === formTabId ? locationState.subsection : '';
			const initialSubsection = (
				(requestedSubsection && hasSubsection(requestedSubsection) ? requestedSubsection : '')
				|| ((getData(tabPanel, 'currentSubsection') || '') && hasSubsection(getData(tabPanel, 'currentSubsection') || '') ? (getData(tabPanel, 'currentSubsection') || '') : '')
				|| navItems[0]?.getAttribute('data-subsection-target')
				|| panels[0]?.getAttribute('data-subsection-panel')
				|| ''
			);

			if (initialSubsection) activateSubsection(initialSubsection, false);
		});
	};

	/** @type {ReturnType<typeof requestAnimationFrame>|null} */
	let stickyActionFrame = null;

	/** @type {boolean} */
	let stickyActionsBound = false;

	/**
	 * @returns {number}
	 */
	const getStickyOffset = () => window.matchMedia('(max-width: 782px)').matches ? 46 : 44;

	/**
	 * @param {HTMLElement} wrap
	 * @param {HTMLElement} bar
	 */
	const releaseStickyAction = (wrap, bar) => {
		bar.classList.remove('is-fixed');
		bar.style.removeProperty('--lerm-sticky-left');
		bar.style.removeProperty('--lerm-sticky-width');
		wrap.style.minHeight = '';
	};

	const syncStickyActions = () => {
		const syncStickyGroup = (scope, stickyOffset) => {
			let currentOffset = stickyOffset;

			dom.findAll('[data-lerm-sticky-wrap]', scope).forEach(wrapEl => {
				const wrap = /** @type {HTMLElement} */ (wrapEl);
				const bar = /** @type {HTMLElement|null} */ (dom.find('[data-lerm-sticky-bar]', wrap));
				if (!bar) return;

				const wrapRect = wrap.getBoundingClientRect();
				const barHeight = bar.offsetHeight;
				const wrapMarginBottom = Number.parseFloat(window.getComputedStyle(wrap).marginBottom || '0') || 0;

				bar.style.setProperty('--lerm-sticky-top', `${currentOffset}px`);

				if (wrapRect.top > currentOffset) {
					releaseStickyAction(wrap, bar);
					currentOffset += barHeight + wrapMarginBottom;
					return;
				}

				wrap.style.minHeight = `${barHeight}px`;
				bar.classList.add('is-fixed');
				bar.style.setProperty('--lerm-sticky-left', `${wrapRect.left}px`);
				bar.style.setProperty('--lerm-sticky-width', `${wrapRect.width}px`);

				currentOffset += barHeight + wrapMarginBottom;
			});
		};

		const stickyOffset = getStickyOffset();
		const handledWraps = new Set();

		dom.findAll('[data-tab-panel]').forEach(panelEl => {
			const panel = /** @type {HTMLElement} */ (panelEl);
			const wraps = dom.findAll('[data-lerm-sticky-wrap]', panel);

			wraps.forEach(wrap => handledWraps.add(wrap));

			if (panel.hidden) {
				wraps.forEach(wrapEl => {
					const wrap = /** @type {HTMLElement} */ (wrapEl);
					const bar = /** @type {HTMLElement|null} */ (dom.find('[data-lerm-sticky-bar]', wrap));
					if (bar) releaseStickyAction(wrap, bar);
				});
				return;
			}

			syncStickyGroup(panel, stickyOffset);
		});

		dom.findAll('[data-lerm-sticky-wrap]').forEach(wrapEl => {
			if (handledWraps.has(wrapEl)) return;

			const wrap = /** @type {HTMLElement} */ (wrapEl);
			const bar = /** @type {HTMLElement|null} */ (dom.find('[data-lerm-sticky-bar]', wrap));
			if (!bar) return;

			const wrapRect = wrap.getBoundingClientRect();
			const barHeight = bar.offsetHeight;

			bar.style.setProperty('--lerm-sticky-top', `${stickyOffset}px`);

			if (wrapRect.top > stickyOffset) {
				releaseStickyAction(wrap, bar);
				return;
			}

			wrap.style.minHeight = `${barHeight}px`;
			bar.classList.add('is-fixed');
			bar.style.setProperty('--lerm-sticky-left', `${wrapRect.left}px`);
			bar.style.setProperty('--lerm-sticky-width', `${wrapRect.width}px`);
		});
	};

	const queueStickyActionsSync = () => {
		if (stickyActionFrame) cancelAnimationFrame(stickyActionFrame);
		stickyActionFrame = requestAnimationFrame(() => {
			stickyActionFrame = null;
			syncStickyActions();
		});
	};

	const initStickyActions = () => {
		queueStickyActionsSync();

		if (stickyActionsBound) return;
		stickyActionsBound = true;

		window.addEventListener('scroll', queueStickyActionsSync, { passive: true });
		window.addEventListener('resize', queueStickyActionsSync);
	};

	// ─── Tab Switching ────────────────────────────────────────────────────────

	/**
	 * Wire up JS-driven tab switching:
	 * - All tab panels are rendered in the DOM (PHP renders every section).
	 * - Clicking a nav link hides all panels and reveals the target one.
	 * - The intro title/description is updated to reflect the active panel.
	 * - The browser URL is updated via history.pushState (no reload).
	 * - Unsaved changes in any tab are preserved because the fields stay in DOM.
	 */
	const initTabSwitching = () => {
		const navItems = dom.findAll('[data-tab-target]');
		const panels = dom.findAll('[data-tab-panel]');
		if (!navItems.length || !panels.length) return;

		const panel = /** @type {HTMLElement|null} */ (dom.find('.lerm-settings-panel'));

		/**
		 * @param {string} tabId
		 * @param {boolean} [pushState]
		 * @param {string} [requestedSubsection]
		 */
		const activateTab = (tabId, pushState = true, requestedSubsection = '') => {
			// Show / hide panels.
			panels.forEach(p => {
				const el = /** @type {HTMLElement} */ (p);
				el.hidden = el.getAttribute('data-tab-panel') !== tabId;
			});

			// Update nav active state.
			navItems.forEach(a => {
				a.classList.toggle('is-active', a.getAttribute('data-tab-target') === tabId);
			});

			const activePanel = /** @type {HTMLElement|null} */ (dom.find(`[data-tab-panel="${tabId}"]`));

			// Update intro title / description.
			if (activePanel && panel) {
				const titleEl = /** @type {HTMLElement|null} */ (dom.find('[data-lerm-tab-intro-title]', panel));
				const descEl = /** @type {HTMLElement|null} */ (dom.find('[data-lerm-tab-intro-desc]', panel));
				if (titleEl) titleEl.textContent = activePanel.getAttribute('data-tab-title') ?? '';
				if (descEl) descEl.textContent = activePanel.getAttribute('data-tab-description') ?? '';
			}

			// Sync the status pill to the newly-active tab's page-level dirty state.
			if (activePanel) {
				const activeForm = /** @type {HTMLFormElement|null} */ (dom.find('.lerm-settings-form', activePanel.closest('.lerm-settings-main') || document));
				if (activeForm) {
					const subsectionController = subsectionControllerMap.get(activePanel);
					const locationState = readLocationState();
					const targetSubsection = subsectionController
						? (
							(requestedSubsection && subsectionController.hasSubsection(requestedSubsection) ? requestedSubsection : '')
							|| (locationState.tab === tabId && locationState.subsection && subsectionController.hasSubsection(locationState.subsection) ? locationState.subsection : '')
							|| subsectionController.currentSubsection()
							|| subsectionController.defaultSubsection()
						)
						: '';

					if (subsectionController && targetSubsection) {
						subsectionController.activateSubsection(targetSubsection, false);
					}

					const tabInput = /** @type {HTMLInputElement|null} */ (dom.find('[data-lerm-current-tab]', activeForm));
					const subsectionInput = /** @type {HTMLInputElement|null} */ (dom.find('[data-lerm-current-subsection]', activeForm));
					const nonceInput = /** @type {HTMLInputElement|null} */ (dom.find('[data-lerm-current-nonce]', activeForm));

					if (tabInput) tabInput.value = tabId;
					if (subsectionInput) subsectionInput.value = targetSubsection || '';
					if (nonceInput) nonceInput.value = activePanel.getAttribute('data-tab-nonce') ?? '';

					const dirty = pageIsDirty(activeForm);
					setStatus(activeForm, dirty ? 'dirty' : 'idle', dirty ? __('Unsaved changes', 'lerm-admin-config') : __('Synced', 'lerm-admin-config'));
				}
			}

			// Refresh any CodeMirror editors that were hidden during initialisation.
			if (activePanel) {
				dom.findAll('.lerm-code-editor', activePanel).forEach(el => {
					codeEditorMap.get(/** @type {HTMLTextAreaElement} */(el))?.codemirror?.refresh();
				});
			}

			queueStickyActionsSync();

			// Keep the URL in sync so the page can be bookmarked / refreshed correctly.
			if (pushState) {
				const activeForm = activePanel
					? /** @type {HTMLFormElement|null} */ (dom.find('.lerm-settings-form', activePanel.closest('.lerm-settings-main') || document))
					: null;
				const activeSubsection = activeForm
					? String(/** @type {HTMLInputElement|null} */ (dom.find('[data-lerm-current-subsection]', activeForm))?.value ?? '')
					: '';
				writeLocationState(tabId, activeSubsection);
			}
		};

		activateTabController = activateTab;

		// Intercept nav clicks.
		navItems.forEach(a => {
			a.addEventListener('click', (e) => {
				e.preventDefault();
				const tabId = a.getAttribute('data-tab-target') ?? '';
				if (tabId) activateTab(tabId);
			});
		});

		// Restore tab on browser back/forward.
		window.addEventListener('popstate', (e) => {
			const tabId = /** @type {any} */ (e).state?.tab
				?? new URL(window.location.href).searchParams.get('tab')
				?? '';
			const subsectionId = /** @type {any} */ (e).state?.subsection
				?? new URL(window.location.href).searchParams.get('subsection')
				?? '';
			if (tabId) activateTab(tabId, false, subsectionId);
		});

		const initialState = readLocationState();
		const initialTab = initialState.tab
			|| dom.find('.lerm-settings-nav__item.is-active')?.getAttribute('data-tab-target')
			|| panels[0]?.getAttribute('data-tab-panel')
			|| '';

		if (initialTab) {
			activateTab(initialTab, false, initialState.subsection);
		}
	};

	// ─── Init ─────────────────────────────────────────────────────────────────

	document.addEventListener('DOMContentLoaded', () => {
		// Resolve shared cfg from the first form found (all forms share the same global).
		cfg = /** @type {LermConfig} */ (resolveAdminConfig({
			find: dom.find,
			getData,
			windowRef: window,
		}));

		// Register a single Ctrl/Cmd+S shortcut that submits the visible form,
		// which now bundles values from every tab on the page.
		document.addEventListener('keydown', (e) => {
			if (!(e.ctrlKey || e.metaKey) || e.key?.toLowerCase() !== 's') return;
			e.preventDefault();
			// Find the currently visible tab panel and submit its form.
			const activePanel = /** @type {HTMLElement|null} */ (
				dom.find('[data-tab-panel]:not([hidden])')
			);
			const activeForm = activePanel
				? /** @type {HTMLFormElement|null} */ (dom.find('.lerm-settings-form', activePanel.closest('.lerm-settings-main') || document))
				: null;
			if (activeForm && getData(activeForm, 'lerm-busy') !== '1') {
				activeForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
			}
		});

		// Initialize shared field widgets on non-options-page screens too.
		initColorPickers(document);
		initAjaxSelectFields(document);
		initNumberInputs(document);
		initRangeInputs(document);
		initMediaFields(document);
		initUploadFields(document);
		initGalleryFields(document);
		initSorters(document);
		initGroups(document);
		initCodeEditors(document);
		initIconFields(document);
		initAccordionFields(document);
		initTabbedFields(document);
		initDebugPanels(document);

		// Initialise the settings form.
		dom.findAll('.lerm-settings-form').forEach(el => {
			const form = /** @type {HTMLFormElement} */ (el);

			initColorPickers(form);
			initAjaxSelectFields(form);
			initNumberInputs(form);
			initRangeInputs(form);
			initMediaFields(form);
			initUploadFields(form);
			initGalleryFields(form);
			initSorters(form);
			initGroups(form);
			initCodeEditors(form);
			initIconFields(form);
			initAccordionFields(form);
			initTabbedFields(form);
			toggleDependencies(form);
			bindAjaxForm(form);
			bindBackupTools(form);
			saveFormSnapshot(form);
			syncDirtyState(form);

			document.addEventListener('change', (e) => {
				const target = /** @type {HTMLElement} */ (e.target);
				if (form.contains(target) && (getData(target, 'lerm-controller') || target.getAttribute('type') === 'radio')) {
					toggleDependencies(form);
				}
			});
		});

		// Wire up section-internal subsection switching for longer pages.
		initSubsectionSwitching();

		// Keep the action bar pinned while scrolling, with a JS fallback for admin layouts.
		initStickyActions();

		// Wire up client-side tab switching (must run after forms are init'd).
		initTabSwitching();
	});

})();
