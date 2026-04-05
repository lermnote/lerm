(function ($) {
	'use strict';

	const fieldName = function (scope, fieldId) {
		const optionName = String(scope.data('option-name') || 'options_framework');
		return optionName + '[' + fieldId + ']';
	};

	const controllerValue = function (scope, fieldId) {
		const idControl = scope.find('#' + fieldId);

		if (idControl.length) {
			if (idControl.is(':checkbox')) {
				return idControl.is(':checked') ? '1' : '0';
			}

			return String(idControl.val() || '');
		}

		const radioControl = scope.find('input[name="' + fieldName(scope, fieldId) + '"]:checked');
		if (radioControl.length) {
			return String(radioControl.val() || '');
		}

		return '';
	};

	const toggleDependencies = function (scope) {
		scope.find('[data-dependency-field]').each(function () {
			const row = $(this);
			const dependencyField = String(row.data('dependency-field'));
			const dependencyValue = String(row.data('dependency-value'));

			row.toggle(controllerValue(scope, dependencyField) === dependencyValue);
		});
	};

	const initColorPickers = function (scope) {
		scope.find('.lerm-color-field').each(function () {
			const input = $(this);

			if (!input.hasClass('wp-color-picker')) {
				input.wpColorPicker();
			}
		});
	};

	const initMediaFields = function (scope) {
		scope.find('.lerm-media-field').each(function () {
			const container = $(this);
			const input = container.find('input[type="hidden"]');
			const preview = container.find('.lerm-media-preview');
			const removeButton = container.find('.lerm-media-remove');
			let frame = null;

			container.find('.lerm-media-select').off('click').on('click', function (event) {
				event.preventDefault();

				if (frame) {
					frame.open();
					return;
				}

				frame = wp.media({
					title: lermOptionsFramework.selectMedia,
					button: { text: lermOptionsFramework.useMedia },
					library: { type: 'image' },
					multiple: false
				});

				frame.on('select', function () {
					const attachment = frame.state().get('selection').first().toJSON();
					const imageUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;

					input.val(attachment.id);
					preview.html('<img src="' + imageUrl + '" alt="">');
					removeButton.prop('hidden', false);
				});

				frame.open();
			});

			removeButton.off('click').on('click', function (event) {
				event.preventDefault();
				input.val('');
				preview.html('<span class="lerm-media-placeholder">' + lermOptionsFramework.noMedia + '</span>');
				removeButton.prop('hidden', true);
			});
		});
	};

	const renderGalleryPreview = function (preview, attachments) {
		if (!attachments.length) {
			preview.html('<span class="lerm-media-placeholder">' + lermOptionsFramework.noGallery + '</span>');
			return;
		}

		const html = attachments.map(function (attachment) {
			const imageUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
			return '<img src="' + imageUrl + '" alt="">';
		}).join('');

		preview.html(html);
	};

	const fetchAttachment = function (id) {
		return new Promise(function (resolve) {
			const model = wp.media.attachment(id);
			const finish = function () {
				resolve(model.toJSON());
			};

			if (model.get('url')) {
				finish();
				return;
			}

			model.fetch({
				success: finish,
				error: function () {
					resolve(null);
				}
			});
		});
	};

	const renderGalleryIds = function (preview, ids) {
		if (!ids.length) {
			renderGalleryPreview(preview, []);
			return;
		}

		Promise.all(ids.map(fetchAttachment)).then(function (attachments) {
			renderGalleryPreview(preview, attachments.filter(Boolean));
		});
	};

	const initGalleryFields = function (scope) {
		scope.find('.lerm-gallery-field').each(function () {
			const container = $(this);
			const input = container.find('input[type="hidden"]');
			const preview = container.find('.lerm-gallery-preview');
			const removeButton = container.find('.lerm-gallery-remove');
			let frame = null;

			container.find('.lerm-gallery-select').off('click').on('click', function (event) {
				event.preventDefault();

				if (frame) {
					frame.open();
					return;
				}

				frame = wp.media({
					title: lermOptionsFramework.selectImages,
					button: { text: lermOptionsFramework.useImages },
					library: { type: 'image' },
					multiple: true
				});

				frame.on('select', function () {
					const attachments = frame.state().get('selection').toJSON();
					const ids = attachments.map(function (attachment) {
						return attachment.id;
					});

					input.val(ids.join(','));
					renderGalleryPreview(preview, attachments);
					removeButton.prop('hidden', ids.length === 0);
				});

				frame.open();
			});

			removeButton.off('click').on('click', function (event) {
				event.preventDefault();
				input.val('');
				renderGalleryPreview(preview, []);
				removeButton.prop('hidden', true);
			});
		});
	};

	const initSorters = function (scope) {
		scope.find('.lerm-sorter-list').sortable({
			axis: 'y',
			handle: '.lerm-sorter-handle',
			placeholder: 'lerm-sorter-placeholder'
		});
	};

	const initCodeEditors = function (scope) {
		if (!window.wp || !wp.codeEditor || !lermOptionsFramework.codeEditor) {
			return;
		}

		scope.find('.lerm-code-editor').each(function () {
			const textarea = $(this);

			if (textarea.data('lerm-editor-ready')) {
				return;
			}

			const settings = $.extend(true, {}, lermOptionsFramework.codeEditor);
			const editor = wp.codeEditor.initialize(textarea[0], settings);

			textarea.data('lerm-code-editor', editor);
			textarea.data('lerm-editor-ready', '1');

			if (editor && editor.codemirror) {
				editor.codemirror.on('change', function () {
					textarea.trigger('change');
				});
			}
		});
	};

	const editorId = function (fieldId) {
		return 'lerm-' + fieldId;
	};

	const triggerEditorSave = function (form) {
		if (window.tinyMCE && typeof window.tinyMCE.triggerSave === 'function') {
			window.tinyMCE.triggerSave();
		}

		form.find('.lerm-code-editor').each(function () {
			const editor = $(this).data('lerm-code-editor');

			if (editor && editor.codemirror && typeof editor.codemirror.save === 'function') {
				editor.codemirror.save();
			}
		});
	};

	const setStatus = function (form, state, message) {
		const pill = form.closest('.lerm-settings-panel').find('[data-lerm-status]');
		pill.attr('data-lerm-status', state).text(message);
	};

	const showFlash = function (form, type, message) {
		const flash = form.closest('.lerm-settings-panel').find('[data-lerm-flash]');

		if (!message) {
			flash.empty().removeClass('is-visible');
			return;
		}

		flash
			.html('<div class="notice notice-' + type + ' inline"><p>' + message + '</p></div>')
			.addClass('is-visible');
	};

	const setBusy = function (form, busy, label) {
		const buttons = form.find('button, input[type="submit"]');
		const spinners = form.find('.lerm-settings-spinner');
		const saveButtons = form.find('[data-lerm-save]');

		form.data('lerm-busy', busy ? '1' : '0');
		buttons.prop('disabled', busy);
		spinners.toggleClass('is-active', busy);

		saveButtons.each(function () {
			const button = $(this);
			const original = button.data('original-label') || button.text();
			button.data('original-label', original);
			button.text(busy ? label : original);
		});
	};

	const isDirty = function (form) {
		return form.data('lerm-dirty') === '1';
	};

	const clearStatusTimer = function (form) {
		const timer = form.data('lerm-status-timer');

		if (timer) {
			window.clearTimeout(timer);
		}
	};

	const queueReadyStatus = function (form) {
		clearStatusTimer(form);

		form.data('lerm-status-timer', window.setTimeout(function () {
			if (!isDirty(form)) {
				setStatus(form, 'idle', lermOptionsFramework.statusReady);
			}
		}, 1800));
	};

	const setDirty = function (form, dirty) {
		clearStatusTimer(form);
		form.data('lerm-dirty', dirty ? '1' : '0');
		setStatus(form, dirty ? 'dirty' : 'idle', dirty ? lermOptionsFramework.statusDirty : lermOptionsFramework.statusReady);
	};

	const formDataForRequest = function (form, action, extras) {
		const formData = new window.FormData(form[0]);

		formData.set('action', action);

		$.each(extras || {}, function (key, value) {
			formData.set(key, value);
		});

		return formData;
	};

	const applyColorValue = function (form, fieldId, value) {
		const input = form.find('#' + fieldId);

		if (!input.length) {
			return;
		}

		try {
			input.wpColorPicker('color', value || '');
		} catch (error) {
			input.val(value || '');
		}
	};

	const applyMediaValue = function (form, fieldId, value) {
		const container = form.find('.lerm-media-field[data-target="' + fieldId + '"]');

		if (!container.length) {
			return;
		}

		const input = container.find('input[type="hidden"]');
		const preview = container.find('.lerm-media-preview');
		const removeButton = container.find('.lerm-media-remove');
		const imageUrl = value && (value.thumbnail || value.url) ? (value.thumbnail || value.url) : '';

		input.val(value && value.id ? value.id : '');
		preview.html(imageUrl ? '<img src="' + imageUrl + '" alt="">' : '<span class="lerm-media-placeholder">' + lermOptionsFramework.noMedia + '</span>');
		removeButton.prop('hidden', !imageUrl);
	};

	const applyGalleryValue = function (form, fieldId, ids) {
		const container = form.find('.lerm-gallery-field[data-target="' + fieldId + '"]');

		if (!container.length) {
			return;
		}

		const cleanIds = Array.isArray(ids) ? ids.map(function (id) {
			return parseInt(id, 10);
		}).filter(Boolean) : [];

		container.find('input[type="hidden"]').val(cleanIds.join(','));
		container.find('.lerm-gallery-remove').prop('hidden', cleanIds.length === 0);
		renderGalleryIds(container.find('.lerm-gallery-preview'), cleanIds);
	};

	const sorterState = function (value) {
		const enabled = value && value.enabled ? Object.keys(value.enabled) : [];
		const disabled = value && value.disabled ? Object.keys(value.disabled) : [];

		return {
			order: enabled.concat(disabled),
			enabled: enabled
		};
	};

	const applySorterValue = function (form, fieldId, value) {
		const container = form.find('.lerm-sorter[data-target="' + fieldId + '"]');

		if (!container.length) {
			return;
		}

		const list = container.find('.lerm-sorter-list');
		const state = sorterState(value);
		const items = {};

		list.find('.lerm-sorter-item').each(function () {
			const item = $(this);
			const key = item.find('input[type="hidden"]').val();
			items[String(key)] = item;
		});

		state.order.forEach(function (key) {
			if (items[key]) {
				list.append(items[key]);
			}
		});

		list.find('input[type="checkbox"]').each(function () {
			const checkbox = $(this);
			checkbox.prop('checked', state.enabled.indexOf(String(checkbox.val())) !== -1);
		});
	};

	const applyFieldValues = function (form, values) {
		$.each(values || {}, function (fieldId, value) {
			const row = form.find('[data-field-id="' + fieldId + '"]');
			const fieldType = row.data('field-type');
			const input = form.find('#' + fieldId);

			switch (fieldType) {
				case 'switcher':
					input.prop('checked', !!value);
					break;

				case 'color':
					applyColorValue(form, fieldId, value);
					break;

				case 'button_set':
				case 'radio':
					form.find('input[name="' + fieldName(form, fieldId) + '"][value="' + String(value) + '"]').prop('checked', true);
					break;

				case 'checkbox_list':
					form.find('input[name="' + fieldName(form, fieldId) + '[]"]').each(function () {
						const checkbox = $(this);
						checkbox.prop('checked', Array.isArray(value) && value.indexOf(String(checkbox.val())) !== -1);
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

				case 'code_editor':
					input.val(value || '');
					if (input.length) {
						const codeEditor = input.data('lerm-code-editor');
						if (codeEditor && codeEditor.codemirror) {
							codeEditor.codemirror.setValue(value || '');
						}
					}
					break;

				case 'wp_editor':
					form.find('textarea[name="' + fieldName(form, fieldId) + '"]').val(value || '');
					if (window.tinyMCE) {
						const editor = window.tinyMCE.get(editorId(fieldId));
						if (editor) {
							editor.setContent(value || '');
						}
					}
					break;

				default:
					if (input.length) {
						input.val(value);
					} else {
						form.find('[name="' + fieldName(form, fieldId) + '"]').val(value);
					}
					break;
			}
		});

		toggleDependencies(form);
	};

	const request = function (form, action, extras) {
		return $.ajax({
			url: lermOptionsFramework.ajaxUrl,
			type: 'POST',
			data: formDataForRequest(form, action, extras),
			processData: false,
			contentType: false
		});
	};

	const bindAjaxForm = function (form) {
		form.on('submit', function (event) {
			event.preventDefault();
			triggerEditorSave(form);
			showFlash(form, '', '');
			setBusy(form, true, lermOptionsFramework.saving);
			setStatus(form, 'saving', lermOptionsFramework.statusSaving);

			request(form, lermOptionsFramework.saveAction).done(function (response) {
				if (!response || !response.success) {
					const message = response && response.data && response.data.message ? response.data.message : lermOptionsFramework.saveError;
					showFlash(form, 'error', message);
					setStatus(form, 'error', lermOptionsFramework.statusError);
					return;
				}

				applyFieldValues(form, response.data.values || {});
				showFlash(form, 'success', response.data.message || lermOptionsFramework.saveSuccess);
				setDirty(form, false);
				setStatus(form, 'success', lermOptionsFramework.statusSaved);
				queueReadyStatus(form);
			}).fail(function () {
				showFlash(form, 'error', lermOptionsFramework.saveError);
				setStatus(form, 'error', lermOptionsFramework.statusError);
			}).always(function () {
				setBusy(form, false, lermOptionsFramework.saving);
			});
		});

		form.find('[data-lerm-reset]').on('click', function (event) {
			event.preventDefault();

			const scope = $(this).data('lerm-reset') === 'all' ? 'all' : 'section';
			const confirmed = window.confirm(scope === 'all' ? lermOptionsFramework.confirmResetAll : lermOptionsFramework.confirmResetSection);

			if (!confirmed) {
				return;
			}

			triggerEditorSave(form);
			showFlash(form, '', '');
			setBusy(form, true, lermOptionsFramework.resetting);
			setStatus(form, 'resetting', lermOptionsFramework.statusResetting);

			request(form, lermOptionsFramework.resetAction, { reset_scope: scope }).done(function (response) {
				if (!response || !response.success) {
					const message = response && response.data && response.data.message ? response.data.message : lermOptionsFramework.resetError;
					showFlash(form, 'error', message);
					setStatus(form, 'error', lermOptionsFramework.statusError);
					return;
				}

				applyFieldValues(form, response.data.values || {});
				showFlash(form, 'success', response.data.message || (scope === 'all' ? lermOptionsFramework.resetAllSuccess : lermOptionsFramework.resetSectionSuccess));
				setDirty(form, false);
				setStatus(form, 'success', lermOptionsFramework.statusSaved);
				queueReadyStatus(form);
			}).fail(function () {
				showFlash(form, 'error', lermOptionsFramework.resetError);
				setStatus(form, 'error', lermOptionsFramework.statusError);
			}).always(function () {
				setBusy(form, false, lermOptionsFramework.saving);
			});
		});

		form.on('input change sortupdate', 'input, textarea, select', function () {
			setDirty(form, true);
		});

		form.find('.lerm-sorter-list').on('sortupdate sortstop', function () {
			setDirty(form, true);
		});

		$(document).on('keydown.lermOptionsFramework', function (event) {
			if (!(event.ctrlKey || event.metaKey)) {
				return;
			}

			if (String(event.key || '').toLowerCase() !== 's') {
				return;
			}

			event.preventDefault();

			if (form.data('lerm-busy') === '1') {
				return;
			}

			form.trigger('submit');
		});

		$('.lerm-settings-nav__item').on('click', function (event) {
			if (!isDirty(form) || form.data('lerm-busy') === '1') {
				return;
			}

			if (!window.confirm(lermOptionsFramework.confirmNavigate)) {
				event.preventDefault();
			}
		});

		$(window).on('beforeunload.lermOptionsFramework', function () {
			if (!isDirty(form) || form.data('lerm-busy') === '1') {
				return undefined;
			}

			return lermOptionsFramework.confirmLeave;
		});
	};

	$(function () {
		const form = $('.lerm-settings-form');

		if (!form.length) {
			return;
		}

		initColorPickers(form);
		initMediaFields(form);
		initGalleryFields(form);
		initSorters(form);
		initCodeEditors(form);
		toggleDependencies(form);
		setDirty(form, false);
		bindAjaxForm(form);

		$(document).on('change', '[data-lerm-controller], input[type="radio"]', function () {
			toggleDependencies(form);
		});
	});
}(jQuery));
