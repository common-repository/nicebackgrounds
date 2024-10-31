/**
 * Scripts for the URL add form.
 */

jQuery(document).ready(function ($) {
    'use strict';

    var urlInput = function (container, url) {
        var data = {
            action: 'nicebackgrounds',
            func: 'save_url',
            set_id: container.data('tab-key'),
            nonce: container.find('.nicebackgrounds-url').data('save-url'),
            url: url,
        };
        container.find(".nicebackgrounds-url .add-status").hide(150);
        container.find(".nicebackgrounds-url .add-status-wait").show(150);

        $.post(nicebackgrounds_data.ajax_url, data, function (response) {
            var suffix = "error";
            if (null !== response && response.success) {
                suffix = "success";
                container.find(".nicebackgrounds-input-url input").val('');
                var $result = $(response.result);
                $result.hide(0);
                container.find(".nicebackgrounds-collection-display").append($result);
                $result.show(150);
            }
            if (null !== response && response.message) {
                var $msg = $('<div class="nicebackgrounds-url-message">' + response.message + '</div>');
                container.find(".nicebackgrounds-url .add-status").hide(150);
                container.find(".nicebackgrounds-url .add-status-" + suffix).append($msg).show(150).delay(5000).hide(150, function () {
                    $msg.remove();
                });
            }
        }, "json").fail(function () {

            container.find(".nicebackgrounds-url .add-status").hide(150);
            container.find(".nicebackgrounds-url .add-status-failure").show(150);
        });
    };

    $(".nicebackgrounds-url button").click(function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var val = $(this).closest(".nicebackgrounds-url").find(".nicebackgrounds-input-url input").val();
        var $container = $(this).closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div");
        urlInput($container, val);
    });

    $(".nicebackgrounds-input-url input").keyup(function (e) {
        if (13 === e.keyCode) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var $container = $(this).closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div");
            urlInput($container, $(this).val());
        }
    });

});