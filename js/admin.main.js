/**
 * General scripts for the Nice Backgrounds admin interface.
 */

jQuery(document).ready(function ($) {
	'use strict';

	var $nicebackgrounds = $('#nicebackgrounds-wrap');

	/**
	 * Common stuff.
	 */

	var showStatus = function (status) {
		var $status = $(".nicebackgrounds-status");
		if (null !== $status.data('timeout') || undefined !== $status.data('timeout')) {
			$status.removeData('timeout');
			clearTimeout($status.data('timeout'));
		}
		if ('wait' === status) {
			$status.find('.nicebackgrounds-failure').hide(30);
			$status.find('.nicebackgrounds-success').hide(30);
			$status.find('.nicebackgrounds-wait').show(30);
			$status.show(150);
		}
		else if ('success' === status) {
			$status.find('.nicebackgrounds-wait').hide(30);
			$status.find('.nicebackgrounds-failure').hide(30);
			$status.find('.nicebackgrounds-success').show(30);
			$status.show(150);

			// No need to persist a success message.  Use a timeout rather than delay so it can be cancelled.
			var timeout = setTimeout(function () {
				$('.nicebackgrounds-status').hide(300);
			}, 5000);
			$status.data('timeout', timeout);
		}
		else if ('failure' === status) {
			$status.find('.nicebackgrounds-wait').hide(30);
			$status.find('.nicebackgrounds-success').hide(30);
			$status.find('.nicebackgrounds-failure').show(30);
			$status.show(150);
		}
	};

	// Used in conjunction with focus() this sets the cursor at the end of the text input.
	$.fn.setCursorToTextEnd = function () {
		var $initialVal = this.val();
		this.val($initialVal);
	};

	/**
	 * Show and hide form elements.
	 */
	var formShowHide = function ($container) {
		var $form_div = $container.closest("form > div");
		var hide = [];
		var show = [];
		$container.find('[data-show]').each(function () {
			if (!$(this).is(':checked, :selected')) {
				var hideSels = $(this).data('show').split(",");
				for (var i = 0; i < hideSels.length; i++) {
					hide.push(hideSels[i]);
				}
			}
			else {
				show.push($(this).data('show'));
			}
		});
		show = show.join(",");
		if (hide.length) {
			hide = show.length ? hide.join(":not(" + show + "),") + ":not(" + show + ")" : hide.join(",");
			$form_div.find(hide).hide(100);
		}
		$form_div.find(show).show(100);
	};
	var showHideSel = ".nicebackgrounds-form-row:not(.nicebackgrounds-form-row .nicebackgrounds-form-row):has([data-show])";
	$(showHideSel).each(function () {
		formShowHide($(this));
	});
	$nicebackgrounds.on("change", showHideSel, function () {
		formShowHide($(this));
	});

	/**
	 * New set widget.
	 */
	$("#nicebackgrounds-new-set-container").submit(function (e) {
		e.preventDefault();
		showStatus('wait');
		var data = {
			action: 'nicebackgrounds',
			func: 'new_set',
			nonce: $(this).data('new-set'),
			nonce_key: 'new_set',
			value: $(this).find("input").val(),
		};
		$.post(nicebackgrounds_data.ajax_url, data, function (response) {
			if (null !== response && response.success && response.result) {
				showStatus('success');
				location.reload();
			}
			else {
				showStatus('failure');
			}
		}, "json").fail(function () {
			showStatus('failure');
		});
	});

	$("#nicebackgrounds-new-set")
		.click(function (e) {
			e.preventDefault();
			$(this).siblings(".nicebackgrounds-input-new-set-form").fadeToggle(150).find("input").focus();
		})
		.find("button").click(function (e) {
			e.preventDefault();
			$(this).closest("#nicebackgrounds-new-set-container").submit();
		});


	/**
	 * Action links.
	 */

	var setAction = function ($el, action, value) {
		showStatus('wait');

		var $set = $el.closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div");
		var set_id = $set.data("tab-key");
		var data = {
			action: 'nicebackgrounds',
			func: 'set_action',
			set_id: set_id,
			nonce: $el.closest(".nicebackgrounds-set-actions").data('set-action'),
			value: value,
			set_action: action,
		};
		$.post(nicebackgrounds_data.ajax_url, data, function (response) {
			if (null !== response && response.success && response.result) {
				showStatus('success');
				location.reload();
			}
			else {
				showStatus('failure');
			}
		}, "json").fail(function () {
			showStatus('failure');
		});
	};

	$('.nicebackgrounds-action-link a').click(function (e) {
		e.preventDefault();
		var $form = $(this).siblings('.nicebackgrounds-form-row');
		$(this).closest('.nicebackgrounds-set-actions').find('.nicebackgrounds-form-row').not($form).fadeOut();
		$form.fadeToggle(150).find("input").focus().setCursorToTextEnd();
	});

	$('.nicebackgrounds-set-clear').click(function (e) {
		e.preventDefault();
		setAction($(this), 'clear', null);
	});

	$('.nicebackgrounds-action-link-rename button').click(function (e) {
		e.preventDefault();
		var value = $(this).parent().find("input").val();
		setAction($(this), 'rename', value);
	});

	$('.nicebackgrounds-action-link-clone button').click(function (e) {
		e.preventDefault();
		var value = $(this).parent().find("input").val();
		setAction($(this), 'clone', value);
	});

	$('.nicebackgrounds-action-link-delete button').click(function (e) {
		e.preventDefault();
		setAction($(this), 'delete', null);
	});


	/**
	 * Auto save inputs.
	 */

	var saveInput = function ($input, iteration) {
		if ($input.is(".picklist-freetext")) {
			// Don't save these.
			return;
		}
		if (null === $input.data('unsaved')) {
			// Something else saved it ?
			return;
		}
		if (iteration >= 5) {
			// Can't seem to save.  Indicate this to user.
			showStatus('failure');
			$input.data('cantsave', 'cantsave');
			return;
		}
		showStatus('wait');

		var $set = $input.closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div");
		var value = $input.val();
		if ($input.is("input[type=checkbox]")) {
			value = $input.is(':checked') ? 1 : 0;
		}

		var key = $input.attr('name');

		var data = {
			action: 'nicebackgrounds',
			func: 'save_set',
			set_id: $set.data('tab-key'),
			nonce: $set.find(".nicebackgrounds-set-form").data('save-set'),
			key: key,
			value: value,
		};
		$.post(nicebackgrounds_data.ajax_url, data, function (response) {
			if (null !== response && response.success) {
				$input.removeData('unsaved');
				// We are able to save - find any other unsaved items.
				var $cantsave = $(".nicebackgrounds-set-settings input[data-cantsave], .nicebackgrounds-set-settings select[data-cantsave]");
				if ($cantsave.length) {
					saveInput($cantsave.removeData('cantsave').first());
				}
				else {
					// All saved.
					showStatus('success');
				}
			}
			else {
				setTimeout(function () {
					saveInput($input, iteration + 1);
				}, 1000);
			}
		}, "json").fail(function () {
			setTimeout(function () {
				saveInput($input, iteration + 1);
			}, 1000);
		});
	};

	var $setInputs = $(".nicebackgrounds-set-settings input, .nicebackgrounds-set-settings select, .nicebackgrounds-css-settings input");
	$setInputs.change(function () {
		$(this).data('unsaved', 'unsaved');
		saveInput($(this), 0);
	});


	/**
	 * Auto generate adaptive images while user is looking at admin screen.
	 */

	var generateSets = function () {
		var $set = $(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div:not(.nicebackgrounds-processed)");
		if ($set.length) {
			var data = {
				action: 'nicebackgrounds',
				func: 'generate_set',
				set_id: $set.data('tab-key'),
				nonce: $set.find(".nicebackgrounds-set-form").data('generate-set'),
			};
			$.post(nicebackgrounds_data.ajax_url, data, function (response) {
				if (null !== response && response.success && response.result) {
					$set.addClass('nicebackgrounds-processed');
				}
				setTimeout(function () {
					generateSets();
				}, 2000);
			}, "json");
		}
	};
	generateSets();


	/**
	 * Thumbnail to modal.
	 */

	$nicebackgrounds.on("click", ".nicebackgrounds-thumb > .nicebackgrounds-modal", function (e) {
		e.preventDefault();
		e.stopImmediatePropagation();
		var $link = $(this);
		$link.addClass("nicebackgrounds-modal-waiting");
		var image = new Image();
		image.src = $link.attr("href");
		image.onload = function () {
			var $fullimg = $(image);
			$("body").append($fullimg);
			$fullimg.attr("class", "nicebackgrounds-fullscreen-image").css('display', 'none');
			$("html").addClass("nicebackgrounds-with-fullscreen-image");
			$fullimg.fadeIn(100);
			$fullimg.click(function () {
				$fullimg.fadeOut(100, function () {
					$fullimg.remove();
					$("html").removeClass("nicebackgrounds-with-fullscreen-image");
					$link.removeClass("nicebackgrounds-modal-waiting");
				});
			});
		};
	});


	/**
	 * Thumbnail remove from set.
	 */

	$nicebackgrounds.on("click", ".nicebackgrounds-collection-display .nicebackgrounds-thumb > .nicebackgrounds-remove", function (e) {
		e.preventDefault();
		e.stopImmediatePropagation();
		showStatus('wait');
		var $thumb = $(this).closest(".nicebackgrounds-thumb");
		var $panel = $(this).closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div");
		$thumb.fadeTo(300, 0.25);
		// Tell the server to remove this image from the set.
		var data = {
			action: 'nicebackgrounds',
			func: 'remove_image',
			set_id: $panel.data('tab-key'),
			nonce: $panel.find('.nicebackgrounds-collection-display').data('remove-image'),
			file: $thumb.find("img").attr("src"),
		};
		$.post(nicebackgrounds_data.ajax_url, data, function (response) {
			if (null !== response && response.success) {
				showStatus('success');
				$thumb.fadeTo(150, 0, function () {
					$thumb.remove();

					// Update reserves.
					$panel.find('.nicebackgrounds-reserves-choices').removeClass('nicebackgrounds-reserves-choices-done');
					$panel.find('.nicebackgrounds-tab-group-collection-add > ul > li.nicebackgrounds-tab-collection-add-reserves')
						.trigger('update');
				});
			}
			else {
				showStatus('failure');
				$thumb.fadeTo(300, 100);
			}
		}, "json").fail(function () {
			showStatus('failure');
			$thumb.fadeTo(300, 100);
		});

	});

});


/**
 * This section is explicitly namespaced so it can be easily used as callbacks from other files.
 */
(function (nicebackgrounds_callable, $, undefined) {

	/**
	 * Callback for picklist.
	 */
	nicebackgrounds_callable.nicebackgrounds_save_picklist_option = function (picklist, value, remove) {
		var data = {
			action: 'nicebackgrounds',
			func: 'save_picklist_option',
			nonce: $('#nicebackgrounds-wrap').data('save-picklist-option'),
			nonce_key: 'save_picklist_option',
			option: picklist.data('picklist-option'),
			value: value,
			remove: remove,
		};
		$.post(nicebackgrounds_data.ajax_url, data, function (response) {
			// If it doesn't work, we don't bother the user about it.
		}, "json");

		// Find all picklists with the same data-picklist-option value, and update the option there too.
		$(".nicebackgrounds-picklist").each(function () {
			if ($(this).data('picklist-option') == picklist.data('picklist-option')) {
				// Check to make sure we're not creating a duplicate, or in the case of remove; act on found items.
				var found = false;
				$(this).find('span.picklist-option:contains(' + value + '), span.picklist-picked-value:contains(' + value + ')').each(function () {
					if (value === $(this).text()) {
						found = true;
						if (remove) {
							$(this).remove();
						}
						else {
							// Equivalent to 'break' statement.
							return false;
						}
					}
				});
				if (!found && !remove) {
					var newEl = $('<span class="picklist-option">' + value + '<span class="dashicons dashicons-trash"></span>');
					$(this).find(".picklist-options").append(newEl);
				}
			}
		});

	};

}(window.nicebackgrounds_callable = window.nicebackgrounds_callable || {}, jQuery));

