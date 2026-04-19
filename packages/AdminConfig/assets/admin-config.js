// @ts-check
/*!
 * Lerm Settings Panel
 * Refactored for clarity, modularity, and maintainability.
 */

// ─── WordPress Global Stubs (typed as any — official typings are incomplete) ──
/** @type {any} */ const wp = /** @type {any} */ (window['wp']);
/** @type {any} */ const jQuery = /** @type {any} */ (window['jQuery']);

(function () {
	'use strict';

	// ─── Type Definitions ─────────────────────────────────────────────────────

	/**
	 * @typedef {{
	 *   ajaxUrl: string,
	 *   saveAction: string, resetAction: string, exportAction: string, importAction: string,
	 *   codeEditor: object|null,
	 *   selectMedia: string, useMedia: string, noMedia: string,
	 *   selectImages: string, useImages: string, noGallery: string,
	 *   saving: string, resetting: string,
	 *   saveSuccess: string, saveError: string,
	 *   resetError: string, resetAllSuccess: string, resetSectionSuccess: string,
	 *   importSuccess: string, importError: string, exportSuccess: string,
	 *   statusReady: string, statusDirty: string, statusSaving: string,
	 *   statusResetting: string, statusSaved: string, statusError: string,
	 *   confirmResetAll: string, confirmResetSection: string,
	 *   confirmRemoveItem: string, confirmImport: string
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
	 *   data: { values?: Record<string, unknown>, message?: string, json?: string, fieldErrors?: Record<string, string|string[]> }
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
		const radio = /** @type {HTMLInputElement|undefined} */ (
			getNamedControls(form, buildFieldName(form, fieldId)).find((control) => control instanceof HTMLInputElement && control.type === 'radio' && control.checked)
		);
		return radio ? String(radio.value ?? '') : '';
	};

	// ─── Dependencies ─────────────────────────────────────────────────────────

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
				const dependencyValue = getData(row, 'dependency-value');
				const controllerRow = rowsByFieldId.get(dependencyField);
				const shouldHide = (
					!controllerRow
					|| controllerRow.hidden
					|| getControllerValue(form, dependencyField) !== dependencyValue
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

	/** @param {Document|Element} scope */
	const initColorPickers = (scope) => {
		dom.findAll('.lerm-color-field', scope).forEach(input => {
			if (!input.classList.contains('wp-color-picker')) {
				jQuery(input).wpColorPicker(); // wp.wpColorPicker requires jQuery — cannot remove
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
					title: cfg.selectFile || cfg.selectMedia,
					button: { text: cfg.useFile || cfg.useMedia },
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
				frame = wp.media({ title: cfg.selectMedia, button: { text: cfg.useMedia }, library: { type: 'image' }, multiple: false });
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
				frame = wp.media({ title: cfg.selectImages, button: { text: cfg.useImages }, library: { type: 'image' }, multiple: true });
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

			list.addEventListener('click', (e) => {
				const btn = /** @type {HTMLElement} */ (e.target)?.closest('[data-lerm-group-remove]');
				if (!btn || !list.contains(btn)) return;
				e.preventDefault();
				if (!window.confirm(cfg.confirmRemoveItem)) return;
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

	/** @type {WeakMap<HTMLFormElement, {
	 * activateSubsection: (subsectionId: string, pushState?: boolean) => void,
	 * currentSubsection: () => string,
	 * defaultSubsection: () => string,
	 * hasSubsection: (subsectionId: string) => boolean
	 * }>} */
	const subsectionControllerMap = new WeakMap();

	/**
	 * @param {string} token
	 * @returns {boolean}
	 */
	const isArrayToken = (token) => token === '' || /^\d+$/.test(String(token));

	/**
	 * @param {string|null|undefined} nextToken
	 * @returns {unknown[]|Record<string, unknown>}
	 */
	const createStateContainer = (nextToken) => isArrayToken(String(nextToken ?? '')) ? [] : {};

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} name
	 * @returns {string[]}
	 */
	const optionFieldTokens = (form, name) => {
		const prefix = `${getOptionName(form)}[`;
		if (!String(name).startsWith(prefix)) return [];
		return Array.from(String(name).matchAll(/\[([^\]]*)\]/g)).map((match) => match[1] ?? '');
	};

	/**
	 * @param {Record<string, unknown>} state
	 * @param {string[]} tokens
	 * @param {string} value
	 */
	const assignStateValue = (state, tokens, value) => {
		/** @type {unknown} */
		let cursor = state;

		tokens.forEach((token, index) => {
			const isLast = index === tokens.length - 1;
			const nextToken = tokens[index + 1] ?? '';

			if (token === '') {
				if (!Array.isArray(cursor)) return;
				if (isLast) {
					cursor.push(value);
					return;
				}
				const nextContainer = createStateContainer(nextToken);
				cursor.push(nextContainer);
				cursor = nextContainer;
				return;
			}

			if (!cursor || typeof cursor !== 'object') return;

			const key = Array.isArray(cursor) && /^\d+$/.test(token) ? Number(token) : token;

			if (isLast) {
				cursor[key] = value;
				return;
			}

			if (!Object.prototype.hasOwnProperty.call(cursor, key) || !cursor[key] || typeof cursor[key] !== 'object') {
				cursor[key] = createStateContainer(nextToken);
			}

			cursor = cursor[key];
		});
	};

	/**
	 * @param {unknown} value
	 * @returns {string}
	 */
	const stableStateString = (value) => {
		if (Array.isArray(value)) return `[${value.map((item) => stableStateString(item)).join(',')}]`;
		if (value && typeof value === 'object') {
			return `{${Object.keys(/** @type {Record<string, unknown>} */ (value)).sort().map((key) => (
				`${JSON.stringify(key)}:${stableStateString(/** @type {Record<string, unknown>} */ (value)[key])}`
			)).join(',')}}`;
		}
		return JSON.stringify(value ?? null);
	};

	/**
	 * @param {HTMLFormElement} form
	 * @returns {Record<string, unknown>}
	 */
	const readFormState = (form) => {
		/** @type {Record<string, unknown>} */
		const state = {};
		const formData = new FormData(form);

		for (const [name, rawValue] of formData.entries()) {
			const tokens = optionFieldTokens(form, String(name));
			if (!tokens.length) continue;
			assignStateValue(state, tokens, String(rawValue));
		}

		return state;
	};

	/**
	 * @param {unknown} value
	 * @returns {Record<string, unknown>}
	 */
	const cloneState = (value) => /** @type {Record<string, unknown>} */ (JSON.parse(JSON.stringify(value ?? {})));

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
		statusTimer = setTimeout(() => { if (!isDirty(form)) setStatus(form, 'idle', cfg.statusReady); }, 1800);
	};

	/**
	 * @param {HTMLFormElement} form
	 * @param {boolean} dirty
	 */
	const setDirty = (form, dirty) => {
		if (statusTimer) clearTimeout(statusTimer);
		setData(form, 'lerm-dirty', dirty ? '1' : '0');
		setStatus(form, dirty ? 'dirty' : 'idle', dirty ? cfg.statusDirty : cfg.statusReady);
	};

	/** @param {HTMLFormElement} form */
	const syncDirtyState = (form) => {
		const savedState = formSnapshotMap.get(form) ?? {};
		const dirty = stableStateString(readFormState(form)) !== stableStateString(savedState);
		setDirty(form, dirty);
	};

	// ─── AJAX ─────────────────────────────────────────────────────────────────

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} action
	 * @param {Record<string, string>} [extras]
	 * @returns {Promise<AjaxResponse>}
	 */
	const request = (form, action, extras = {}) => {
		const body = new FormData(form);
		body.set('action', action);
		for (const [k, v] of Object.entries(extras)) body.set(k, v);
		return fetch(cfg.ajaxUrl, { method: 'POST', body }).then(async (r) => {
			const text = await r.text();

			try {
				return /** @type {AjaxResponse} */ (JSON.parse(text));
			} catch {
				if (!r.ok) throw new Error('Network error: ' + r.status);
				throw new Error('Invalid JSON response: ' + text.slice(0, 120));
			}
		});
	};

	// ─── Value Application ────────────────────────────────────────────────────

	/**
	 * @param {HTMLFormElement} form
	 * @param {string} fieldId
	 * @param {unknown} value
	 */
	const applyColorValue = (form, fieldId, value) => {
		const input = findFieldById(form, fieldId);
		if (!(input instanceof HTMLInputElement)) return;
		try { jQuery(input).wpColorPicker('color', value || ''); } catch { input.value = String(value || ''); }
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
				try {
					const input = /** @type {HTMLInputElement|null} */ (dom.find('.lerm-color-field', scope));
					if (input) {
						try { jQuery(input).wpColorPicker('color', value || ''); } catch { input.value = String(value || ''); }
					}
				} catch { /* noop */ }
				break;
			case 'button_set':
			case 'radio':
				dom.findAll('input[type="radio"]', scope).forEach(el => { /** @type {HTMLInputElement} */ (el).checked = /** @type {HTMLInputElement} */ (el).value === String(value); });
				break;
			case 'icon':
				applyIconValue(dom.find('.lerm-icon-field', scope), value);
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

	/** @param {HTMLFormElement} form */
	const clearFieldErrors = (form) => {
		dom.findAll('[data-field-id]', form).forEach((row) => clearFieldErrorRow(/** @type {HTMLElement} */ (row)));
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
	 * @param {Record<string, string|string[]>} fieldErrors
	 */
	const applyFieldErrors = (form, fieldErrors = {}) => {
		clearFieldErrors(form);

		let firstRow = null;

		for (const [fieldId, rawMessage] of Object.entries(fieldErrors)) {
			const row = findFieldRow(form, String(fieldId));
			if (!row) continue;

			const message = (Array.isArray(rawMessage) ? rawMessage : [rawMessage])
				.map((item) => String(item || '').trim())
				.filter(Boolean)
				.join(' ');

			if (!message) continue;

			if (!firstRow) firstRow = row;

			row.classList.add('is-invalid');
			setData(row, 'lerm-field-error', '1');

			const target = /** @type {HTMLElement|null} */ (
				dom.find('.lerm-settings-row__body', row)
				|| dom.find('td', row)
				|| row
			);

			target?.appendChild(dom.create('p', {
				class: 'lerm-field-error',
				'data-lerm-field-error-message': '1',
			}, [message]));

			dom.findAll('input, select, textarea', row).forEach((el) => el.setAttribute('aria-invalid', 'true'));
		}

		firstRow?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	};

	// ─── Backup Tools ─────────────────────────────────────────────────────────

	/** @param {HTMLFormElement} form */
	const bindBackupTools = (form) => {
		const exportBtn = /** @type {HTMLElement|null} */ (dom.find('[data-lerm-backup-export]', form));
		if (exportBtn) {
			exportBtn.addEventListener('click', (e) => {
				e.preventDefault();
				request(form, cfg.exportAction).then(response => {
					if (!response?.success) {
						setStatus(form, 'error', response?.data?.message || cfg.saveError);
						return;
					}
					/** @type {HTMLInputElement} */ (dom.find('[data-lerm-backup-export-output]', form)).value = response.data.json || '';
					setStatus(form, 'success', response.data.message || cfg.exportSuccess);
					queueReadyStatus(form);
				}).catch(() => setStatus(form, 'error', cfg.saveError));
			});
		}

		const importBtn = /** @type {HTMLElement|null} */ (dom.find('[data-lerm-backup-import]', form));
		if (importBtn) {
			importBtn.addEventListener('click', (e) => {
				e.preventDefault();
				if (!window.confirm(cfg.confirmImport)) return;
				const json = String(/** @type {HTMLInputElement|null} */(dom.find('[data-lerm-backup-import-input]', form))?.value ?? '');
				clearFieldErrors(form);
				setBusy(form, true, cfg.resetting);
				setStatus(form, 'saving', cfg.statusSaving);
				request(form, cfg.importAction, { backup_json: json })
					.then(response => {
						if (!response?.success) {
							applyFieldErrors(form, /** @type {Record<string, string|string[]>} */ (response?.data?.fieldErrors ?? {}));
							setStatus(form, 'error', response?.data?.message || cfg.importError);
							return;
						}
						clearFieldErrors(form);
						applyFieldValues(form, response.data.values ?? {});
						saveFormSnapshot(form);
						syncDirtyState(form);
						setStatus(form, 'success', response.data.message || cfg.importSuccess);
						queueReadyStatus(form);
					})
					.catch(() => setStatus(form, 'error', cfg.importError))
					.finally(() => setBusy(form, false, cfg.saving));
			});
		}
	};

	// ─── AJAX Form ────────────────────────────────────────────────────────────

	/**
	 * @param {HTMLFormElement} form
	 * @param {AjaxResponse} response
	 * @param {string} successMsg
	 */
	const handleSaveResponse = (form, response, successMsg, partialFieldIds = null) => {
		if (!response?.success) {
			applyFieldErrors(form, /** @type {Record<string, string|string[]>} */ (response?.data?.fieldErrors ?? {}));
			setStatus(form, 'error', response?.data?.message || cfg.saveError);
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

	/** @param {HTMLFormElement} form */
	const bindAjaxForm = (form) => {
		form.addEventListener('submit', (e) => {
			e.preventDefault();
			triggerEditorSave(form);
			clearFieldErrors(form);
			setBusy(form, true, cfg.saving);
			setStatus(form, 'saving', cfg.statusSaving);
			request(form, cfg.saveAction)
				.then(r => handleSaveResponse(form, r, cfg.saveSuccess))
				.catch(() => setStatus(form, 'error', cfg.saveError))
				.finally(() => setBusy(form, false, cfg.saving));
		});

		dom.findAll('[data-lerm-reset]', form).forEach(btn => {
			btn.addEventListener('click', (e) => {
				e.preventDefault();
				const scope = getData(btn, 'lerm-reset') === 'all' ? 'all' : 'section';
				if (!window.confirm(scope === 'all' ? cfg.confirmResetAll : cfg.confirmResetSection)) return;
				triggerEditorSave(form);
				clearFieldErrors(form);
				setBusy(form, true, cfg.resetting);
				setStatus(form, 'resetting', cfg.statusResetting);
				request(form, cfg.resetAction, { reset_scope: scope })
					.then(r => {
						const partialFieldIds = r?.data?.scope === 'subsection'
							? Object.keys(/** @type {Record<string, unknown>} */ (r.data.values ?? {}))
							: null;
						handleSaveResponse(form, r, scope === 'all' ? cfg.resetAllSuccess : cfg.resetSectionSuccess, partialFieldIds);
						// After a full reset, reload values for every other tab form too.
						if (scope === 'all' && r?.success) {
							dom.findAll('.lerm-settings-form').forEach(otherEl => {
								const otherForm = /** @type {HTMLFormElement} */ (otherEl);
								if (otherForm === form) return;
								// Silently re-fetch defaults for this tab.
								request(otherForm, cfg.resetAction, { reset_scope: 'fetch_only' })
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
					.catch(() => setStatus(form, 'error', cfg.resetError))
					.finally(() => setBusy(form, false, cfg.saving));
			});
		});

		form.addEventListener('input', (e) => {
			syncDirtyState(form);
			const row = /** @type {HTMLElement|null} */ ((/** @type {HTMLElement} */ (e.target))?.closest('[data-field-id]'));
			if (row) clearFieldErrorRow(row);
		});
		form.addEventListener('change', (e) => {
			syncDirtyState(form);
			const row = /** @type {HTMLElement|null} */ ((/** @type {HTMLElement} */ (e.target))?.closest('[data-field-id]'));
			if (row) clearFieldErrorRow(row);
		});

		const sorterList = /** @type {HTMLElement|null} */ (dom.find('.lerm-sorter-list', form));
		if (sorterList) sorterList.addEventListener('sortupdate', () => syncDirtyState(form));
	};

	const initSubsectionSwitching = () => {
		dom.findAll('.lerm-settings-form').forEach(el => {
			const form = /** @type {HTMLFormElement} */ (el);
			const navItems = dom.findAll('[data-subsection-target]', form);
			const panels = dom.findAll('[data-subsection-panel]', form);
			if (!navItems.length || !panels.length) return;
			const formPanel = /** @type {HTMLElement|null} */ (form.closest('[data-tab-panel]'));
			const formTabId = formPanel?.getAttribute('data-tab-panel') ?? '';
			const subsectionInput = /** @type {HTMLInputElement|null} */ (dom.find('input[name="lerm_settings_subsection"]', form));

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

				const activePanel = /** @type {HTMLElement|null} */ (dom.find(`[data-subsection-panel="${subsectionId}"]`, form));
				if (activePanel) {
					dom.findAll('.lerm-code-editor', activePanel).forEach(editor => {
						codeEditorMap.get(/** @type {HTMLTextAreaElement} */ (editor))?.codemirror?.refresh();
					});
				}

				if (subsectionInput) subsectionInput.value = subsectionId;
				if (pushState && formTabId) writeLocationState(formTabId, subsectionId);

				queueStickyActionsSync();
			};

			subsectionControllerMap.set(form, {
				activateSubsection,
				currentSubsection: () => subsectionInput?.value ?? '',
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
				|| (subsectionInput?.value && hasSubsection(subsectionInput.value) ? subsectionInput.value : '')
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

			// Sync the status pill to the newly-active tab's dirty state.
			if (activePanel) {
				const activeForm = /** @type {HTMLFormElement|null} */ (dom.find('.lerm-settings-form', activePanel));
				if (activeForm) {
					const subsectionController = subsectionControllerMap.get(activeForm);
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

					const dirty = isDirty(activeForm);
					setStatus(activeForm, dirty ? 'dirty' : 'idle', dirty ? cfg.statusDirty : cfg.statusReady);
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
					? /** @type {HTMLFormElement|null} */ (dom.find('.lerm-settings-form', activePanel))
					: null;
				const activeSubsection = activeForm
					? String(/** @type {HTMLInputElement|null} */ (dom.find('input[name="lerm_settings_subsection"]', activeForm))?.value ?? '')
					: '';
				writeLocationState(tabId, activeSubsection);
			}
		};

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
		const firstForm = /** @type {HTMLFormElement|null} */ (dom.find('.lerm-settings-form'));
		if (firstForm) {
			const jsGlobal = getData(firstForm, 'js-global');
			cfg = /** @type {LermConfig} */ ((jsGlobal && window[jsGlobal]) ? window[jsGlobal] : {});
		}

		// Register a single Ctrl/Cmd+S shortcut that submits only the visible (active) form.
		document.addEventListener('keydown', (e) => {
			if (!(e.ctrlKey || e.metaKey) || e.key?.toLowerCase() !== 's') return;
			e.preventDefault();
			// Find the currently visible tab panel and submit its form.
			const activePanel = /** @type {HTMLElement|null} */ (
				dom.find('[data-tab-panel]:not([hidden])')
			);
			const activeForm = activePanel
				? /** @type {HTMLFormElement|null} */ (dom.find('.lerm-settings-form', activePanel))
				: null;
			if (activeForm && getData(activeForm, 'lerm-busy') !== '1') {
				activeForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
			}
		});

		// Initialise every form (one per tab panel).
		dom.findAll('.lerm-settings-form').forEach(el => {
			const form = /** @type {HTMLFormElement} */ (el);

			initColorPickers(form);
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
