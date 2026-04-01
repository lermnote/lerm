(function ($) {
	'use strict';

	const toggleDependencies = function () {
		$('[data-dependency-field]').each(function () {
			const row = $(this);
			const dependencyField = row.data('dependency-field');
			const dependencyValue = String(row.data('dependency-value'));
			const controller = $('#' + dependencyField);
			let currentValue = '';

			if (controller.is(':checkbox')) {
				currentValue = controller.is(':checked') ? '1' : '0';
			} else {
				currentValue = String(controller.val() || '');
			}

			row.toggle(currentValue === dependencyValue);
		});
	};

	const initColorPickers = function () {
		$('.lerm-color-field').wpColorPicker();
	};

	const initMediaFields = function () {
		$('.lerm-media-field').each(function () {
			const container = $(this);
			const input = container.find('input[type="hidden"]');
			const preview = container.find('.lerm-media-preview');
			const removeButton = container.find('.lerm-media-remove');
			let frame = null;

			container.find('.lerm-media-select').on('click', function (event) {
				event.preventDefault();

				if (frame) {
					frame.open();
					return;
				}

				frame = wp.media({
					title: lermThemeSettings.selectMedia,
					button: { text: lermThemeSettings.useMedia },
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

			removeButton.on('click', function (event) {
				event.preventDefault();
				input.val('');
				preview.html('<span class="lerm-media-placeholder">' + lermThemeSettings.noMedia + '</span>');
				removeButton.prop('hidden', true);
			});
		});
	};

	$(function () {
		initColorPickers();
		initMediaFields();
		toggleDependencies();

		$(document).on('change', '[data-lerm-controller]', toggleDependencies);
	});
}(jQuery));
