/**
 * Scripts that run the Picklist widget.
 */

jQuery(document).ready(function ($) {
	'use strict';

	var $nicebackgrounds = $('#nicebackgrounds-wrap');

	/**
	 * Handle removing picked item.
	 */
	var picklistUnselect = function (picked_item) {
		// Remove value.
		var $container = picked_item.closest(".nicebackgrounds-picklist");
		var remove_value = picked_item.text();
		var $input = $container.find("input.picklist-value");
		var values = $input.val().trim().split(",");
		var new_values = [];
		for (var v = 0; v < values.length; v++) {
			if (values[v] !== remove_value) {
				new_values.push(values[v]);
			}
		}
		$input.val(new_values.join(',')).trigger('change');
		// Move span to options.
		var newEl = picked_item.clone().removeClass("picklist-picked-value").addClass("picklist-option");
		newEl.find("span.dashicons-no").removeClass("dashicons-no").addClass("dashicons-trash");
		picked_item.closest(".nicebackgrounds-picklist").find(".picklist-options").append(newEl);
		picked_item.remove();
	};

	/**
	 * Handle removing all items.
	 */
	var picklistUnselectAll = function ($container) {
		$container.find(".picklist-picked-value").each(function () {
			picklistUnselect($(this));
		});
	};

	/**
	 * Handle adding to the hidden values input.
	 */
	var picklistAddValue = function ($container, add_value) {
		var isMultiple = $container.hasClass("nicebackgrounds-multiple");
		if (!isMultiple) {
			picklistUnselectAll($container);
		}
		var $input = $container.find("input.picklist-value");
		if ("" === $input.val() || null === $input.val() || !isMultiple) {
			$input.val(add_value).trigger('change');
		}
		else {
			$input.val($input.val() + "," + add_value).trigger('change');
		}
	};

	/**
	 *
	 */
	var picklistOptionCallback = function ($container, value, remove) {
		// Call the callback defined in data-option-save-callback.  It can be provided as "namespace.function_name".
		if (null !== $container.data('option-save-callback') || undefined !== $container.data('option-save-callback')) {
			var namespaces = $container.data('option-save-callback').split(".");
			var func = namespaces.pop();
			var context = window;
			for (var i = 0; i < namespaces.length; i++) {
				context = context[namespaces[i]];
			}
			context[func].call(context, $container, value, remove);
		}
	};

	/**
	 * Handle freetext input.
	 */
	var picklistAddFreeText = function ($picklist_freetext) {
		var value = $picklist_freetext.val();
		if ('' === value.trim()) {
			return;
		}
		var $container = $picklist_freetext.closest(".nicebackgrounds-picklist");
		var isMultiple = $container.hasClass("nicebackgrounds-multiple");
		if (!isMultiple) {
			picklistUnselectAll($container);
		}
		var values = value.split(",");
		for (var v = 0; v < values.length; v++) {
			if ('' !== values[v].trim()) {
				picklistAddValue($container, values[v].trim());
				var newEl = $('<span class="picklist-picked-value">' + values[v].trim() + '<span class="dashicons dashicons-no"></span>');
				$container.find("input.picklist-freetext").before(newEl);

				picklistOptionCallback($container, values[v].trim(), 0);
			}
		}
		$picklist_freetext.val('');
	};

	/**
	 * Handle picking.
	 */
	var picklistAddPick = function ($pick) {
		var $container = ($pick.closest(".nicebackgrounds-picklist"));
		var isMultiple = $container.hasClass("nicebackgrounds-multiple");
		picklistAddValue($container, $pick.text());
		var $newEl = $pick.clone().removeClass("picklist-option").addClass("picklist-picked-value");
		$newEl.find("span.dashicons-trash").removeClass("dashicons-trash").addClass("dashicons-no");
		if (!isMultiple) {
			picklistUnselectAll($container);
		}
		$container.find("input.picklist-freetext").before($newEl);
		$pick.remove();
	};

	/**
	 * Pick list delete hint.
	 */
	$nicebackgrounds.on('mouseup', ".nicebackgrounds-picklist .picklist-option > span.dashicons-trash", function () {
		if ($(this).hasClass("clicked")) {
			$(this).removeClass("clicked").closest(".nicebackgrounds-picklist")
				.find(".picklist-delete-hint").show(300).delay(10000).hide(300);
		}
		else {
			$(this).addClass("clicked");
			var el = $(this);
			setTimeout(function () {
				el.removeClass("clicked");
			}, 2000);
		}
	});

	/**
	 * Pick list item delete.
	 */
	$nicebackgrounds.on('mousedown', ".nicebackgrounds-picklist .picklist-option > span.dashicons-trash", function () {
		$(this).addClass("nicebackgrounds-deleting").parent(".picklist-option").animate({opacity: 0.25}, 2000, function () {
			if ($(this).find("span.dashicons-trash").hasClass("nicebackgrounds-deleting")) {

				picklistOptionCallback($(this).closest(".nicebackgrounds-picklist"), $(this).text().trim(), 1);

				$(this).hide(100);
			}
		});
	});
	$nicebackgrounds.on('mouseup mouseleave click', ".nicebackgrounds-picklist .picklist-option > span.dashicons-trash", function (e) {
		e.stopImmediatePropagation();
		$(this).removeClass("nicebackgrounds-deleting").parent(".picklist-option").stop(true).animate({opacity: 1}, 100);
	});

	/**
	 * Pick list item select.
	 */
	$nicebackgrounds.on('click', ".nicebackgrounds-picklist .picklist-option", function () {
		picklistAddPick($(this));
	});


	/**
	 * Pick list item unselect.
	 */
	$nicebackgrounds.on('click', ".nicebackgrounds-picklist .picklist-picked-value .dashicons-no", function () {
		picklistUnselect($(this).parent());
	});

	/**
	 * Pick list freetext blur.
	 */
	$nicebackgrounds.on('blur', ".nicebackgrounds-picklist .picklist-freetext", function () {
		picklistAddFreeText($(this));
	});

	/**
	 * Pick list freetext enter key.
	 */
	$nicebackgrounds.on('keypress', ".nicebackgrounds-picklist .picklist-freetext", function (e) {
		if (e.which === 13) {
			picklistAddFreeText($(this));
			return false;
		}
	});

	/**
	 * Pick list focus.
	 */
	$nicebackgrounds.on('click', ".nicebackgrounds-picklist .picklist-picked-values", function () {
		$(this).find(".picklist-freetext").focus();
	});
	$nicebackgrounds.on('focus', ".nicebackgrounds-picklist .picklist-freetext", function () {
		$(this).parent().addClass('active');
	});
	$nicebackgrounds.on('blur', ".nicebackgrounds-picklist .picklist-freetext", function () {
		$(this).parent().removeClass('active');
	});

	//@todo optimise the duplicate listeners.

});

