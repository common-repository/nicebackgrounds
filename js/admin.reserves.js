/**
 * Scripts for the Reserves add form.
 */

jQuery(document).ready(function ($) {
    'use strict';

	var $nicebackgrounds = $('#nicebackgrounds-wrap');

    var reservesLoadChoice = function ($panel) {
        var $container = $panel.find('.nicebackgrounds-reserves-container');
        var $choices = $container.find('.nicebackgrounds-reserves-choices');
        if ($choices.hasClass('nicebackgrounds-reserves-choices-done')) {
            return;
        }
        $choices.removeClass('nicebackgrounds-reserves-choices-failure');
            var data = {
                action: 'nicebackgrounds',
                func: 'load_reserves',
                set_id: $panel.data('tab-key'),
                nonce: $container.data('load-reserves'),
            };
            $.post(nicebackgrounds_data.ajax_url, data, function (response) {
                if (null !== response && response.success) {
                    var $result = $(response.result);
                    $choices.html($result);
                    $choices.addClass('nicebackgrounds-reserves-choices-done');
                }
                else {
                    $choices.addClass('nicebackgrounds-reserves-choices-failure');
                }
            }, "json").fail(function () {
                $choices.addClass('nicebackgrounds-reserves-choices-failure');
            });

    };


    $nicebackgrounds.on("click update", ".nicebackgrounds-tab-group-collection-add > ul > li", function () {
        if ("nicebackgrounds-tab-panel-collection-add-reserves" === $(this).data("target")) {
            var panel = $(this).closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div").first();
            reservesLoadChoice(panel);
        }
    });

    $nicebackgrounds.on("click", ".nicebackgrounds-reserves-choices .nicebackgrounds-thumb > .nicebackgrounds-remove", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var thumb = $(this).closest(".nicebackgrounds-thumb");
        var panel = $(this).closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div");
        thumb.fadeOut(300, function () {
            thumb.remove();

            // Tell the server to remove this image.
            var data = {
                action: 'nicebackgrounds',
                func: 'remove_reserves',
                set_id: panel.data('tab-key'),
                nonce: panel.find('.nicebackgrounds-reserves-container').data('remove-reserves'),
                file: thumb.find("img").attr("src"),
            };
            $.post(nicebackgrounds_data.ajax_url, data, function (response) {
                // We don't particularly care if it worked or not.
                // Maybe we should.
            }, "json");
        });
    });

    $nicebackgrounds.on("click", ".nicebackgrounds-reserves-choices .nicebackgrounds-thumb", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var $thumb = $(this).closest(".nicebackgrounds-thumb");
        if ($thumb.hasClass("nicebackgrounds-choosing")) {
            return;
        }
        $thumb.addClass("nicebackgrounds-choosing");

        var $panel = $(this).closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div");

        var data = {
            action: 'nicebackgrounds',
            func: 'save_reserves',
            set_id: $panel.data('tab-key'),
            nonce: $panel.find('.nicebackgrounds-reserves-container').data('save-reserves'),
            file: $thumb.find("a.nicebackgrounds-modal").attr("href"),
            dimensions: $thumb.find(".nicebackgrounds-dimensions").text(),
            //cdn: thumb.hasClass("nicebackgrounds-thumb-cdn"),
        };
        var $overlay = $(
            '<span class="nicebackgrounds-overlay nicebackgrounds-overlay-wait">' +
            '<span class="dashicons dashicons-update"></span>' +
            '<span class="message"></span>' +
            '</span>'
        );
        $overlay.hide(0);
        $thumb.append($overlay);
        $overlay.fadeIn(150);

        $.post(nicebackgrounds_data.ajax_url, data, function (response) {
            if (null !== response && response.message) {
                $overlay.find(".message").text(response.message);
            }
            if (null !== response && response.success) {

                $overlay.removeClass("nicebackgrounds-overlay-wait").addClass("nicebackgrounds-overlay-success");
                $overlay.find(".dashicons").removeClass("dashicons-update").addClass("dashicons-thumbs-up");

                var $result = $(response.result);
                $result.hide(0);
                $panel.find(".nicebackgrounds-collection-display").append($result);
                $result.show(150);

                $thumb.delay(2000).fadeOut(300, function () {
                    $thumb.remove();
                });
            }
            else {
                $overlay.removeClass("nicebackgrounds-overlay-wait").addClass("nicebackgrounds-overlay-error");
                $overlay.find(".dashicons").removeClass("dashicons-update").addClass("dashicons-thumbs-down");
                $overlay.delay(5000).fadeOut(300, function () {
                    $overlay.remove();
                    $thumb.removeClass("nicebackgrounds-choosing");
                });
            }

        }, "json").fail(function () {
            $overlay.removeClass("nicebackgrounds-overlay-wait").addClass("nicebackgrounds-overlay-failure");
            $overlay.find(".dashicons").removeClass("dashicons-update").addClass("dashicons-no");
            $overlay.delay(5000).fadeOut(300, function () {
                $overlay.remove();
                $thumb.removeClass("nicebackgrounds-choosing");
            });
        });

    });
});