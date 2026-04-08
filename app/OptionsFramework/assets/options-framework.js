/**
 * Options Framework - Vanilla JS (no jQuery dependency)
 * Uses WordPress native APIs: wp.media, wp.codeEditor, wpColorPicker, jQuery UI sortable
 */
(function () {
	'use strict';

	// ------ DOM Helpers ------
	const $ = (selector, context = document) => context.querySelector(selector);
	const $$ = (selector, context = document) => Array.from(context.querySelectorAll(selector));

	const closest = (el, selector) => el.closest(selector);
	const getData = (el, key) => el.getAttribute('data-' + key);
	const setData = (el, key, value) => el.setAttribute('data-' + key, String(value));
	const hasClass = (el, cls) => el.classList.contains(cls);
	const addClass = (el, cls) => el.classList.add(cls);
	const removeClass = (el, cls) => el.classList.remove(cls);
	const toggleClass = (el, cls, force) => el.classList.toggle(cls, force);
	const empty = (el) => { while (el.firstChild) el.removeChild(el.firstChild); };
	const append = (parent, child) => parent.appendChild(child);
	const codeEditorMap = new WeakMap();
	const createElement = (tag, props = {}, children = []) => {
		const el = document.createElement(tag);
		Object.entries(props).forEach(([k, v]) => {
			if (k === 'class') el.className = v;
			else if (k === 'style' && typeof v === 'object') Object.assign(el.style, v);
			else if (k.startsWith('data-')) el.dataset[k.replace(/^data-/, '')] = v;
			else if (k.startsWith('on')) el.addEventListener(k.slice(2).toLowerCase(), v);
			else el.setAttribute(k, v);
		});
		children.forEach(c => typeof c === 'string' ? el.appendChild(document.createTextNode(c)) : append(el, c));
		return el;
	};

	// ------ Event Helper ------
	const on = (el, event, handler) => {
		if (!el) return;
		if (el instanceof NodeList || Array.isArray(el)) {
			el.forEach(e => on(e, event, handler));
			return;
		}
		el.addEventListener(event, handler);
	};

	// ------ Config ------
	let cfg = {};

	// ------ Field Helpers ------
	const fieldName = function (form, fieldId) {
		const optionName = getData(form, 'option-name') || 'options_framework';
		return optionName + '[' + fieldId + ']';
	};

	const controllerValue = function (form, fieldId) {
		const idControl = $('#' + fieldId, form);
		if (idControl) {
			if (idControl.type === 'checkbox') {
				return idControl.checked ? '1' : '0';
			}
			return String(idControl.value || '');
		}

		const radioControl = $(`input[name="${fieldName(form, fieldId)}"]:checked`, form);
		if (radioControl) {
			return String(radioControl.value || '');
		}

		return '';
	};

	// ------ Dependencies ------
	const toggleDependencies = function (form) {
		$$('[data-dependency-field]', form).forEach(row => {
			const dependencyField = getData(row, 'dependency-field');
			const dependencyValue = getData(row, 'dependency-value');
			row.hidden = controllerValue(form, dependencyField) !== dependencyValue;
		});
	};

	// ------ Color Pickers ------
	const initColorPickers = function (scope) {
		$$('.lerm-color-field', scope).forEach(input => {
			if (!hasClass(input, 'wp-color-picker')) {
				jQuery(input).wpColorPicker();
			}
		});
	};

	// ------ Media Fields ------
	const initMediaFields = function (scope) {
		$$('.lerm-media-field', scope).forEach(container => {
			const input = $('input[type="hidden"]', container);
			const preview = $('.lerm-media-preview', container);
			const removeButton = $('.lerm-media-remove', container);
			let frame = null;

			const selectBtn = $('.lerm-media-select', container);
			selectBtn.onclick = function (event) {
				event.preventDefault();

				if (frame) {
					frame.open();
					return;
				}

				frame = wp.media({
					title: cfg.selectMedia,
					button: { text: cfg.useMedia },
					library: { type: 'image' },
					multiple: false
				});

				frame.on('select', function () {
					const attachment = frame.state().get('selection').first().toJSON();
					const imageUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;

					input.value = attachment.id;
					empty(preview);
					append(preview, createElement('img', { src: imageUrl, alt: '' }));
					removeButton.hidden = false;
				});

				frame.open();
			};

			removeButton.onclick = function (event) {
				event.preventDefault();
				input.value = '';
				empty(preview);
				append(preview, createElement('span', { class: 'lerm-media-placeholder', 'data-lerm-i18n': 'noMedia' }, [cfg.noMedia]));
				removeButton.hidden = true;
			};
		});
	};

	// ------ Gallery Fields ------
	const renderGalleryPreview = function (preview, attachments) {
		if (!attachments.length) {
			empty(preview);
			append(preview, createElement('span', { class: 'lerm-media-placeholder' }, [cfg.noGallery]));
			return;
		}

		empty(preview);
		attachments.forEach(attachment => {
			const imageUrl = attachment.sizes?.thumbnail?.url ?? attachment.url;
			append(preview, createElement('img', { src: imageUrl, alt: '' }));
		});
	};

	const fetchAttachment = function (id) {
		return new Promise(resolve => {
			const model = wp.media.attachment(id);
			const finish = () => resolve(model.toJSON());

			if (model.get('url')) {
				finish();
				return;
			}

			model.fetch({
				success: finish,
				error: () => resolve(null)
			});
		});
	};

	const renderGalleryIds = function (preview, ids) {
		if (!ids.length) {
			renderGalleryPreview(preview, []);
			return;
		}

		Promise.all(ids.map(fetchAttachment)).then(attachments => {
			renderGalleryPreview(preview, attachments.filter(Boolean));
		});
	};

	const initGalleryFields = function (scope) {
		$$('.lerm-gallery-field', scope).forEach(container => {
			const input = $('input[type="hidden"]', container);
			const preview = $('.lerm-gallery-preview', container);
			const removeButton = $('.lerm-gallery-remove', container);
			let frame = null;

			const selectBtn = $('.lerm-gallery-select', container);
			selectBtn.onclick = function (event) {
				event.preventDefault();

				if (frame) {
					frame.open();
					return;
				}

				frame = wp.media({
					title: cfg.selectImages,
					button: { text: cfg.useImages },
					library: { type: 'image' },
					multiple: true
				});

				frame.on('select', function () {
					const attachments = frame.state().get('selection').toJSON();
					const ids = attachments.map(a => a.id);

					input.value = ids.join(',');
					renderGalleryPreview(preview, attachments);
					removeButton.hidden = ids.length === 0;
				});

				frame.open();
			};

			removeButton.onclick = function (event) {
				event.preventDefault();
				input.value = '';
				renderGalleryPreview(preview, []);
				removeButton.hidden = true;
			};
		});
	};

	// ------ Sorters ------
	const initSorters = function (scope) {
		$$('.lerm-sorter-list', scope).forEach(list => {
			jQuery(list).sortable({
				axis: 'y',
				handle: '.lerm-sorter-handle',
				placeholder: 'lerm-sorter-placeholder'
			});
		});
	};

	// ------ Groups ------
	const replaceIndexToken = (template, index) => String(template || '').replace(/__INDEX__/g, String(index));

	const refreshGroupEmptyState = function (group) {
		const hasItems = $$('[data-lerm-group-item]', group).length > 0;
		const emptyEl = $('.lerm-group__empty', group);
		if (emptyEl) emptyEl.hidden = hasItems;
	};

	const renumberGroupItems = function (group) {
		$$('[data-lerm-group-item]', group).forEach((item, index) => {
			item.dataset.index = index;
			const title = $('.lerm-group-item__title', item);
			if (title) title.textContent = 'Item ' + (index + 1);

			$$('[data-name-template]', item).forEach(input => {
				input.name = replaceIndexToken(getData(input, 'nameTemplate'), index);
			});

			$$('[data-id-template]', item).forEach(input => {
				input.id = replaceIndexToken(getData(input, 'idTemplate'), index);
			});

			$$('[data-for-template]', item).forEach(label => {
				label.htmlFor = replaceIndexToken(getData(label, 'forTemplate'), index);
			});
		});

		refreshGroupEmptyState(group);
	};

	const initGroups = function (scope) {
		$$('.lerm-group', scope).forEach(group => {
			if (getData(group, 'lerm-group-ready') !== '1') {
				setData(group, 'lerm-group-ready', '1');

				const list = $('[data-lerm-group-list]', group);
				jQuery(list).sortable({
					axis: 'y',
					handle: '.lerm-sorter-handle',
					placeholder: 'lerm-sorter-placeholder',
					update: function () {
						renumberGroupItems(group);
						closest(group, 'form').dispatchEvent(new Event('sortupdate'));
					}
				});

				const addBtn = $('[data-lerm-group-add]', group);
				addBtn.onclick = function (event) {
					event.preventDefault();
					const template = $('.lerm-group-template', group);
					const templateHtml = template ? template.innerHTML : '';
					list.insertAdjacentHTML('beforeend', templateHtml);
					renumberGroupItems(group);
					initColorPickers(group);
					initMediaFields(group);
					initGalleryFields(group);
					initCodeEditors(group);
					setDirty(closest(group, 'form'), true);
				};

				const removeBtns = $$('[data-lerm-group-remove]', group);
				removeBtns.forEach(btn => {
					btn.onclick = function (event) {
						event.preventDefault();
						if (!window.confirm(cfg.confirmRemoveItem)) return;
						closest(btn, '[data-lerm-group-item]').remove();
						renumberGroupItems(group);
						setDirty(closest(group, 'form'), true);
					};
				});
			}

			renumberGroupItems(group);
		});
	};

	// ------ Code Editors ------
	const initCodeEditors = function (scope) {
		if (!window.wp || !wp.codeEditor || !cfg.codeEditor) return;

		$$('.lerm-code-editor', scope).forEach(textarea => {
			if (getData(textarea, 'lerm-editor-ready')) return;

			const settings = Object.assign({}, cfg.codeEditor || {});
			const editor = wp.codeEditor.initialize(textarea, settings);

			// setData(textarea, 'lerm-code-editor', editor);
			codeEditorMap.set(textarea, editor);
			setData(textarea, 'lerm-editor-ready', '1');

			if (editor && editor.codemirror) {
				editor.codemirror.on('change', () => {
					textarea.dispatchEvent(new Event('change'));
				});
			}
		});
	};

	const editorId = fieldId => 'lerm-' + fieldId;

	const triggerEditorSave = function (form) {
		if (window.tinyMCE && typeof window.tinyMCE.triggerSave === 'function') {
			window.tinyMCE.triggerSave();
		}

		$$('.lerm-code-editor', form).forEach(textarea => {
			const editor = codeEditorMap.get(textarea);
			if (editor?.codemirror) {
				editor.codemirror.save();
			}
		});
	};

	// ------ Status & Flash ------
	const setStatus = function (form, state, message) {
		const panel = closest(form, '.lerm-settings-panel');
		const pill = $('[data-lerm-status]', panel);
		pill.dataset.lermStatus = state;
		pill.textContent = message;
	};

	const showFlash = function (form, type, message) {
		const panel = closest(form, '.lerm-settings-panel');
		const flash = $('[data-lerm-flash]', panel);

		if (!message) {
			empty(flash);
			removeClass(flash, 'is-visible');
			return;
		}

		const wrapper = document.createElement('div');
		wrapper.className = `notice notice-${type} inline`;
		const p = document.createElement('p');
		p.textContent = message;   // .textContent escapes HTML
		wrapper.appendChild(p);
		empty(flash);
		append(flash, wrapper);
		addClass(flash, 'is-visible');
	};

	const setBusy = function (form, busy, label) {
		const buttons = $$('button, input[type="submit"]', form);
		const spinners = $$('.lerm-settings-spinner', form);
		const saveButtons = $$('[data-lerm-save]', form);

		setData(form, 'lerm-busy', busy ? '1' : '0');
		buttons.forEach(btn => btn.disabled = busy);
		spinners.forEach(spinner => toggleClass(spinner, 'is-active', busy));

		saveButtons.forEach(button => {
			if (busy) {
				// Capture label before changing it
				if (!getData(button, 'original-label')) {
					setData(button, 'original-label', button.textContent);
				}
				button.textContent = label;
			} else {
				button.textContent = getData(button, 'original-label') || button.textContent;
			}
		});
	};

	const isDirty = form => getData(form, 'lerm-dirty') === '1';

	let statusTimer = null;
	const clearStatusTimer = () => {
		if (statusTimer) {
			window.clearTimeout(statusTimer);
			statusTimer = null;
		}
	};

	const queueReadyStatus = function (form) {
		clearStatusTimer();
		statusTimer = window.setTimeout(() => {
			if (!isDirty(form)) {
				setStatus(form, 'idle', cfg.statusReady);
			}
		}, 1800);
	};

	const setDirty = function (form, dirty) {
		clearStatusTimer();
		setData(form, 'lerm-dirty', dirty ? '1' : '0');
		setStatus(form, dirty ? 'dirty' : 'idle', dirty ? cfg.statusDirty : cfg.statusReady);
	};

	// ------ AJAX ------
	const request = function (form, action, extras = {}) {
		return new Promise((resolve, reject) => {
			const formData = new FormData(form);
			formData.set('action', action);
			Object.entries(extras).forEach(([key, value]) => formData.set(key, value));

			fetch(cfg.ajaxUrl, {
				method: 'POST',
				body: formData
			})
				.then(response => response.json())
				.then(data => resolve(data))
				.catch(error => reject(error));
		});
	};

	// ------ Value Application ------
	const applyColorValue = function (form, fieldId, value) {
		const input = $('#' + fieldId, form);
		if (!input) return;

		try {
			jQuery(input).wpColorPicker('color', value || '');
		} catch (e) {
			input.value = value || '';
		}
	};

	const applyMediaValue = function (form, fieldId, value) {
		const container = $(`.lerm-media-field[data-target="${fieldId}"]`, form);
		if (!container) return;

		const input = $('input[type="hidden"]', container);
		const preview = $('.lerm-media-preview', container);
		const removeButton = $('.lerm-media-remove', container);
		const imageUrl = value && (value.thumbnail || value.url) ? (value.thumbnail || value.url) : '';

		input.value = value && value.id ? value.id : '';
		empty(preview);
		if (imageUrl) {
			append(preview, createElement('img', { src: imageUrl, alt: '' }));
		} else {
			append(preview, createElement('span', { class: 'lerm-media-placeholder' }, [cfg.noMedia]));
		}
		removeButton.hidden = !imageUrl;
	};

	const applyGalleryValue = function (form, fieldId, ids) {
		const container = $(`.lerm-gallery-field[data-target="${fieldId}"]`, form);
		if (!container) return;

		const cleanIds = Array.isArray(ids) ? ids.map(id => parseInt(id, 10)).filter(Boolean) : [];
		$('input[type="hidden"]', container).value = cleanIds.join(',');
		$('.lerm-gallery-remove', container).hidden = cleanIds.length === 0;
		renderGalleryIds($('.lerm-gallery-preview', container), cleanIds);
	};

	const sorterState = function (value) {
		const enabled = value && value.enabled ? Object.keys(value.enabled) : [];
		const disabled = value && value.disabled ? Object.keys(value.disabled) : [];
		return { order: enabled.concat(disabled), enabled };
	};

	const applySorterValue = function (form, fieldId, value) {
		const container = $(`.lerm-sorter[data-target="${fieldId}"]`, form);
		if (!container) return;

		const list = $('.lerm-sorter-list', container);
		const state = sorterState(value);
		const items = {};

		$$('.lerm-sorter-item', container).forEach(item => {
			const key = $('input[type="hidden"]', item).value;
			items[String(key)] = item;
		});

		state.order.forEach(key => {
			if (items[key]) list.appendChild(items[key]);
		});

		$$('input[type="checkbox"]', list).forEach(checkbox => {
			checkbox.checked = state.enabled.indexOf(String(checkbox.value)) !== -1;
		});
	};

	const applyScopedFieldValue = function (scope, fieldType, value) {
		const normalizedType = String(fieldType || 'text');

		switch (normalizedType) {
			case 'switcher':
				$('input[type="checkbox"]', scope).checked = !!value;
				break;
			case 'color':
				try {
					$('.lerm-color-field', scope).value = value || '';
				} catch (e) { }
				break;
			case 'button_set':
			case 'radio':
				$$('input[type="radio"]', scope).forEach(r => r.checked = r.value === String(value));
				break;
			case 'select':
				$('select', scope).value = value;
				break;
			case 'textarea':
				$('textarea', scope).value = value || '';
				break;
			case 'media': {
				const container = $('.lerm-media-field', scope);
				const input = $('input[type="hidden"]', container);
				const preview = $('.lerm-media-preview', container);
				const removeButton = $('.lerm-media-remove', container);
				const imageUrl = value && (value.thumbnail || value.url) ? (value.thumbnail || value.url) : '';
				input.value = value && value.id ? value.id : '';
				empty(preview);
				if (imageUrl) append(preview, createElement('img', { src: imageUrl, alt: '' }));
				else append(preview, createElement('span', { class: 'lerm-media-placeholder' }, [cfg.noMedia]));
				removeButton.hidden = !imageUrl;
				break;
			}
			case 'gallery': {
				const container = $('.lerm-gallery-field', scope);
				const ids = Array.isArray(value) ? value.map(id => parseInt(id, 10)).filter(Boolean) : [];
				$('input[type="hidden"]', container).value = ids.join(',');
				$('.lerm-gallery-remove', container).hidden = ids.length === 0;
				renderGalleryIds($('.lerm-gallery-preview', container), ids);
				break;
			}
			case 'number':
			case 'url':
			case 'text':
			default:
				$('input, textarea', scope).value = value;
				break;
		}
	};

	const applyFieldsetValue = function (form, fieldId, value) {
		const container = $(`.lerm-fieldset[data-target="${fieldId}"]`, form);
		if (!container || !value || typeof value !== 'object') return;

		Object.entries(value).forEach(([subfieldId, subfieldValue]) => {
			const scope = $(`[data-subfield-id="${subfieldId}"]`, container);
			if (scope) applyScopedFieldValue(scope, getData(scope, 'fieldType'), subfieldValue);
		});
	};

	const applyGroupValue = function (form, fieldId, value) {
		const group = $(`.lerm-group[data-target="${fieldId}"]`, form);
		if (!group) return;

		const list = $('[data-lerm-group-list]', group);
		const template = $('.lerm-group-template', group);
		const templateHtml = template ? template.innerHTML : '';
		const items = Array.isArray(value) ? value : [];

		try { jQuery(list).sortable('destroy'); } catch (e) { }
		empty(list);

		items.forEach(() => list.insertAdjacentHTML('beforeend', templateHtml));
		renumberGroupItems(group);

		initColorPickers(group);
		initMediaFields(group);
		initGalleryFields(group);
		initCodeEditors(group);

		setData(group, 'lerm-group-ready', '0');
		initGroups(group);

		$$('[data-lerm-group-item]', group).forEach((item, index) => {
			const itemData = items[index] || {};
			Object.entries(itemData).forEach(([subfieldId, subfieldValue]) => {
				const scope = $(`[data-subfield-id="${subfieldId}"]`, item);
				if (scope) applyScopedFieldValue(scope, getData(scope, 'fieldType'), subfieldValue);
			});
		});

		refreshGroupEmptyState(group);
	};

	const applyFieldValues = function (form, values) {
		Object.entries(values || {}).forEach(([fieldId, value]) => {
			const row = $(`[data-field-id="${fieldId}"]`, form);
			const fieldType = row ? getData(row, 'fieldType') : '';
			const input = $('#' + fieldId, form);

			switch (fieldType) {
				case 'switcher':
					input.checked = !!value;
					break;
				case 'color':
					applyColorValue(form, fieldId, value);
					break;
				case 'button_set':
				case 'radio':
					const radio = $(`input[name="${fieldName(form, fieldId)}"][value="${String(value)}"]`, form);
					if (radio) radio.checked = true;
					break;
				case 'checkbox_list':
					$$(`input[name="${fieldName(form, fieldId)}[]"]`, form).forEach(cb => {
						cb.checked = Array.isArray(value) && value.indexOf(String(cb.value)) !== -1;
					});
					break;
				case 'media':
					applyMediaValue(form, fieldId, value);
					break;
				case 'gallery':
					applyGalleryValue(form, fieldId, value);
					break;
				case 'sorter':
					applySorterValue(form, fieldId, value);
					break;
				case 'fieldset':
					applyFieldsetValue(form, fieldId, value);
					break;
				case 'group':
					applyGroupValue(form, fieldId, value);
					break;
				case 'backup_tools':
					break;
				case 'code_editor':
					if (input) {
						input.value = value || '';
						const codeEditor = codeEditorMap.get(input);
						if (codeEditor?.codemirror) {
							codeEditor.codemirror.setValue(value || '');
						}
					}
					break;
				case 'wp_editor':
					$(`textarea[name="${fieldName(form, fieldId)}"]`, form).value = value || '';
					if (window.tinyMCE) {
						const editor = window.tinyMCE.get(editorId(fieldId));
						if (editor) editor.setContent(value || '');
					}
					break;
				default:
					if (input) input.value = value;
					else $(`[name="${fieldName(form, fieldId)}"]`, form).value = value;
					break;
			}
		});

		toggleDependencies(form);
	};

	// ------ Backup Tools ------
	const bindBackupTools = function (form) {
		const exportBtn = $('[data-lerm-backup-export]', form);
		if (exportBtn) {
			exportBtn.onclick = function (event) {
				event.preventDefault();
				showFlash(form, '', '');

				request(form, cfg.exportAction).then(response => {
					if (!response || !response.success) {
						const message = response?.data?.message || cfg.saveError;
						showFlash(form, 'error', message);
						return;
					}

					$('[data-lerm-backup-export-output]', form).value = response.data.json || '';
					showFlash(form, 'success', response.data.message || cfg.exportSuccess);
				}).catch(() => {
					showFlash(form, 'error', cfg.saveError);
				});
			};
		}

		const importBtn = $('[data-lerm-backup-import]', form);
		if (importBtn) {
			importBtn.onclick = function (event) {
				event.preventDefault();

				if (!window.confirm(cfg.confirmImport)) return;

				const backupJson = String($('[data-lerm-backup-import-input]', form).value || '');
				showFlash(form, '', '');
				setBusy(form, true, cfg.resetting);
				setStatus(form, 'saving', cfg.statusSaving);

				request(form, cfg.importAction, { backup_json: backupJson }).then(response => {
					if (!response || !response.success) {
						const message = response?.data?.message || cfg.importError;
						showFlash(form, 'error', message);
						setStatus(form, 'error', cfg.statusError);
						return;
					}

					applyFieldValues(form, response.data.values || {});
					showFlash(form, 'success', response.data.message || cfg.importSuccess);
					setDirty(form, false);
					setStatus(form, 'success', cfg.statusSaved);
					queueReadyStatus(form);
				}).catch(() => {
					showFlash(form, 'error', cfg.importError);
					setStatus(form, 'error', cfg.statusError);
				}).finally(() => {
					setBusy(form, false, cfg.saving);
				});
			};
		}
	};

	// ------ AJAX Form ------
	const bindAjaxForm = function (form) {
		form.addEventListener('submit', function (event) {
			event.preventDefault();
			triggerEditorSave(form);
			showFlash(form, '', '');
			setBusy(form, true, cfg.saving);
			setStatus(form, 'saving', cfg.statusSaving);

			request(form, cfg.saveAction).then(response => {
				if (!response || !response.success) {
					const message = response?.data?.message || cfg.saveError;
					showFlash(form, 'error', message);
					setStatus(form, 'error', cfg.statusError);
					return;
				}

				applyFieldValues(form, response.data.values || {});
				showFlash(form, 'success', response.data.message || cfg.saveSuccess);
				setDirty(form, false);
				setStatus(form, 'success', cfg.statusSaved);
				queueReadyStatus(form);
			}).catch(() => {
				showFlash(form, 'error', cfg.saveError);
				setStatus(form, 'error', cfg.statusError);
			}).finally(() => {
				setBusy(form, false, cfg.saving);
			});
		});

		$$('[data-lerm-reset]', form).forEach(btn => {
			btn.addEventListener('click', function (event) {
				event.preventDefault();

				const scope = getData(btn, 'lerm-reset') === 'all' ? 'all' : 'section';
				const confirmed = window.confirm(scope === 'all' ? cfg.confirmResetAll : cfg.confirmResetSection);

				if (!confirmed) return;

				triggerEditorSave(form);
				showFlash(form, '', '');
				setBusy(form, true, cfg.resetting);
				setStatus(form, 'resetting', cfg.statusResetting);

				request(form, cfg.resetAction, { reset_scope: scope }).then(response => {
					if (!response || !response.success) {
						const message = response?.data?.message || cfg.resetError;
						showFlash(form, 'error', message);
						setStatus(form, 'error', cfg.statusError);
						return;
					}

					applyFieldValues(form, response.data.values || {});
					showFlash(form, 'success', response.data.message || (scope === 'all' ? cfg.resetAllSuccess : cfg.resetSectionSuccess));
					setDirty(form, false);
					setStatus(form, 'success', cfg.statusSaved);
					queueReadyStatus(form);
				}).catch(() => {
					showFlash(form, 'error', cfg.resetError);
					setStatus(form, 'error', cfg.statusError);
				}).finally(() => {
					setBusy(form, false, cfg.saving);
				});
			});
		});

		form.addEventListener('input', () => setDirty(form, true));
		form.addEventListener('change', () => setDirty(form, true));

		const sorterList = $('.lerm-sorter-list', form);
		if (sorterList) {
			jQuery(sorterList).on('sortupdate sortstop', () => setDirty(form, true));
		}

		const ctrl = new AbortController();
		const handler = function (event) {
			if (!(event.ctrlKey || event.metaKey)) return;
			if (String(event.key || '').toLowerCase() !== 's') return;
			event.preventDefault();
			if (getData(form, 'lerm-busy') === '1') return;
			form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
		};
		document.addEventListener('keydown', handler, { signal: ctrl.signal });
		const panel = form.closest('.lerm-settings-panel') || document;
		$$(`.lerm-settings-nav__item`, panel).forEach(link => {
			link.addEventListener('click', function (event) {
				if (!isDirty(form) || getData(form, 'lerm-busy') === '1') return;
				if (!window.confirm(cfg.confirmNavigate)) {
					event.preventDefault();
				}
			});
		});

		window.addEventListener('beforeunload', function (event) {
			if (!isDirty(form) || getData(form, 'lerm-busy') === '1') return;
			event.preventDefault(); // sufficient for all modern browsers
		});
	};

	// ------ Init ------
	document.addEventListener('DOMContentLoaded', function () {
		$$('.lerm-settings-form').forEach(form => {
			const jsGlobal = getData(form, 'js-global');
			const formCfg = (jsGlobal && window[jsGlobal]) ? window[jsGlobal] : {};

			initColorPickers(form);
			bindAjaxForm(form, formCfg);
			initMediaFields(form);
			initGalleryFields(form);
			initSorters(form);
			initGroups(form);
			initCodeEditors(form);
			toggleDependencies(form);
			setDirty(form, false);
			bindBackupTools(form);
			document.addEventListener('change', function (event) {
				if (getData(event.target, 'lerm-controller') ||
					event.target.type === 'radio') {
					if (form.contains(event.target)) {
						toggleDependencies(form);
					}
				}
			});
		});
	});
})();