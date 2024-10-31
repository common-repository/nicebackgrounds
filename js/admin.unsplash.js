/**
 * Scripts for the Unsplash add form.
 */

jQuery(document).ready(function ($) {
    'use strict';

	var $nicebackgrounds = $('#nicebackgrounds-wrap');

    /**
     *
     */
    var unsplashLoadChoice = function (container, load_id) {
        var $reload = container.find(".nicebackgrounds-unsplash-refresh");
        $reload.addClass("nicebackgrounds-active");
        var $choices = container.find(".nicebackgrounds-unsplash-choices");
        var choicesWidth = $choices.width();
        var totalWidth = 0;

        if (null === $choices.data("load-id") || undefined === $choices.data("load-id")) {
            $choices.data("load-id", load_id);
        }
        else if ($choices.data("load-id") !== load_id) {
            return;
        }

        $choices.find(".nicebackgrounds-thumb").each(function () {
            var widthPlusMargins = $(this).outerWidth(true);
            var width = $(this).outerWidth();
            var difference = widthPlusMargins - width;
            totalWidth += width + difference / 2;
        });

        if (choicesWidth - totalWidth > 0) {
            var data = {
                action: 'nicebackgrounds',
                func: 'load_unsplash',
                set_id: container.data('tab-key'),
                nonce: container.find('.nicebackgrounds-unsplash-container').data('load-unsplash'),
                search: container.find(".nicebackgrounds-unsplash-container-search :input").serialize(),
            };
            $.post(nicebackgrounds_data.ajax_url, data, function (response) {
                if ($choices.data("load-id") !== load_id) {
                    return;
                }
                if (null !== response && response.success && response.cdn) {
                    $choices.find(".nicebackgrounds-new").fadeIn(500).removeClass("nicebackgrounds-new");
                    $choices.find(".nicebackgrounds-faded").fadeTo(500, 1).removeClass("nicebackgrounds-faded");

                    var image = new Image();
                    image.src = response.result;
                    var $thumb = $(response.thumb);
					$thumb.find('img').remove();
                    $thumb.addClass("nicebackgrounds-new").css('display', 'none').append(image);
                    $choices.append($thumb);

                    image.onload = function () {
                        if ($choices.data("load-id") !== load_id) {
                            return;
                        }
                        $thumb.find(".nicebackgrounds-dimensions").text(image.width + "x" + image.height);
                        $thumb.find(".nicebackgrounds-modal").attr("href", image.src);
                        unsplashLoadChoice(container, load_id);
                    };
                }
                else if (null !== response && response.success && !response.cdn) {
                    // We got html from the server.
                    var $result = $(response.result);
                    $choices.append($result);
                    unsplashLoadChoice(container, load_id);
                }
                else {
                    $reload.removeClass("nicebackgrounds-active").addClass("nicebackgrounds-error");
                    setTimeout(function () {
                        $reload.removeClass("nicebackgrounds-error");
                    }, 2000);
                    $choices.removeData("load-id");
                }
            }, "json");
        }
        else {
            $choices.find(".nicebackgrounds-new").addClass("nicebackgrounds-faded").removeClass("nicebackgrounds-new").fadeTo(500, 0.25);
            $reload.removeClass("nicebackgrounds-active");
            $choices.removeData("load-id");
        }
    };

    /**
     *
     */
    var unsplashPanelSwitchLoad = function (panel) {
        if (!panel.find(".nicebackgrounds-unsplash-refresh").hasClass("nicebackgrounds-active")) {
            var load_id = panel.find(".nicebackgrounds-unsplash-choices").data("load-id");
            if (null === load_id || undefined === load_id) {
                load_id = Math.random();
            }
            setTimeout(function () {
                unsplashLoadChoice(panel, load_id);
            }, 1000);
        }
    };

    $nicebackgrounds.on("click", ".nicebackgrounds-tab-group-set > ul > li", function () {
        var $panel = $(".nicebackgrounds-tab-group-set ." + $(this).data("target")).first();
        unsplashPanelSwitchLoad($panel);
    });

    $nicebackgrounds.on("click", ".nicebackgrounds-tab-group-collection-add > ul > li", function () {
        if ("nicebackgrounds-tab-panel-collection-add-unsplash" === $(this).data("target")) {
            var $panel = $(this).closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div").first();
            unsplashPanelSwitchLoad($panel);
        }
    });

    setTimeout(function () {
        $(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div.nicebackgrounds-tab-active").each(function () {
            unsplashLoadChoice($(this), Math.random());
        });
    }, 2000);

    $(".nicebackgrounds-unsplash-refresh").click(function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var $panel = $(this).closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div");
        $panel.find(".nicebackgrounds-unsplash-choices").removeData("load-id").find(".nicebackgrounds-thumb").remove();
        unsplashLoadChoice($panel, Math.random());
    });

    var unsplashLoadMoreThumbRemoved = function(thumb, panel) {
        var load_id = thumb.closest(".nicebackgrounds-unsplash-choices").data("load-id");
        if (null === load_id || undefined === load_id) {
            load_id = Math.random();
        }
        if (!panel.find(".nicebackgrounds-unsplash-refresh").hasClass("nicebackgrounds-active")) {
            unsplashLoadChoice(panel, load_id);
        }
    };

    $nicebackgrounds.on("click", ".nicebackgrounds-unsplash-choices .nicebackgrounds-thumb > .nicebackgrounds-remove", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var $thumb = $(this).closest(".nicebackgrounds-thumb");
        var $panel = $(this).closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div");
        $thumb.fadeOut(300, function () {
            $thumb.remove();
            unsplashLoadMoreThumbRemoved($thumb, $panel);

            // Tell the server to remove this image from /preloads and /240
            if (!$thumb.hasClass("nicebackgrounds-thumb-cdn")) {
                var data = {
                    action: 'nicebackgrounds',
                    func: 'remove_unsplash',
                    set_id: $panel.data('tab-key'),
                    nonce: $panel.find('.nicebackgrounds-unsplash-container').data('remove-unsplash'),
                    file: $thumb.find("img").attr("src"),
                };
                $.post(nicebackgrounds_data.ajax_url, data, function (response) {
                    // We don't particularly care if it worked or not.
                }, "json");
            }
        });
    });

    $nicebackgrounds.on("click", ".nicebackgrounds-unsplash-choices .nicebackgrounds-thumb", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var $thumb = $(this).closest(".nicebackgrounds-thumb");
        if ($thumb.hasClass("nicebackgrounds-choosing")) {
            return;
        }
        $thumb.addClass("nicebackgrounds-choosing");

        var panel = $(this).closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div");

        var data = {
            action: 'nicebackgrounds',
            func: 'save_unsplash',
            set_id: panel.data('tab-key'),
            nonce: panel.find('.nicebackgrounds-unsplash-container').data('save-unsplash'),
            file: $thumb.find("a.nicebackgrounds-modal").attr("href"),
            dimensions: $thumb.find(".nicebackgrounds-dimensions").text(),
            cdn: ($thumb.hasClass("nicebackgrounds-thumb-cdn") ? 1 : 0),
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
                panel.find(".nicebackgrounds-collection-display").append($result);

				// This is for detecting if the image is broken.  Sometimes it is unavailable for a few seconds, no idea why.
				var image = new Image();
				image.src = $result.find('img').attr('src');
				image.onerror = function () {
					if (1 === data.cdn) {
						// Use the Unsplash CDN image.
						$result.find('img').attr('src', data.file);
					}
					else {
						// Use the full image.
						$result.find('img').attr('src', $result.find('.nicebackgrounds-modal').attr('href'));
					}
				};

				$result.show(150);
				$thumb.delay(2000).fadeOut(300, function () {
					$thumb.remove();
					unsplashLoadMoreThumbRemoved($thumb, panel);
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