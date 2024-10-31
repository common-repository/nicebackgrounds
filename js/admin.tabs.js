/**
 * Scripts for the tab system.
 */

jQuery(document).ready(function ($) {
    'use strict';

    // Tab setup.
    $($(".nicebackgrounds-tab-group").get().reverse()).each(function () {
        // If there is no active tab, make it the first one.
        var $active = $(this).find("> ul > li.nicebackgrounds-tab-active");
        if (0 === $active.length) {
            $active = $(this).find("> ul > li:first-child");
            $active.addClass("nicebackgrounds-tab-active");
        }

        // Initialize underline.
        var $tabs = $active.closest(".nicebackgrounds-tabs");
        if (0 === $tabs.siblings(".nicebackgrounds-underline").length) {
            var $underline = $('<div class="nicebackgrounds-underline"></div>');
            var tab_offset = $active.offset();
            var tabs_offset = $tabs.offset();
            $underline
                .css("position", "absolute")
                .css("top", tabs_offset.top - tab_offset.top)
                .css("left", tab_offset.left - tabs_offset.left)
                .css("width", Math.max($active.width()))
                .css("height", Math.max($active.height()));
            $tabs.after($underline);
        }

        // Hide the non-corresponding panel.
        $(this).find("> .nicebackgrounds-tab-panels > div:not(." + $active.data("target") + ")").hide();
        // Mark the corresponding active panel.
        $(this).find("> .nicebackgrounds-tab-panels > div." + $active.data("target"))
            .addClass("nicebackgrounds-tab-active");

    });

    // Tab switch.
    $(".nicebackgrounds-tab-group > ul > li").click(function () {
        var group = $(this).closest(".nicebackgrounds-tab-group");
        if (group.hasClass("nicebackgrounds-animating") || $(this).hasClass("nicebackgrounds-tab-active")) {
            return;
        }
        group.addClass("nicebackgrounds-animating");

        // Animate underline.
        var $targetTab = $(this);
        var $currentTab = group.find("li.nicebackgrounds-tab-active").first();
        var target_geometry = $(this).offset();
        var $tabs = $(this).closest(".nicebackgrounds-tabs");
        var tabs_offset = $tabs.offset();
        var $underline = $tabs.siblings(".nicebackgrounds-underline");
        $underline.animate({
            top: target_geometry.top - tabs_offset.top,
            left: target_geometry.left - tabs_offset.left,
            width: Math.max($(this).innerWidth())
        }, 300, function () {

            $currentTab.removeClass("nicebackgrounds-tab-active");
            $targetTab.addClass("nicebackgrounds-tab-active");
        });

        // Animate panels.
        var targetSel = "." + $targetTab.data("target");
        var $currentPanel = group.find(" > .nicebackgrounds-tab-panels > div.nicebackgrounds-tab-active").first();
        var $targetPanel = group.find(targetSel).first();
        var currentEnd = "-110%";
        var targetStart = "110%";
        if (0 !== $targetPanel.nextAll("div.nicebackgrounds-tab-active").length) {
            // currentPanel is before (to the left) of the targetPanel.
            currentEnd = "110%";
            targetStart = "-110%";
        }
        $currentPanel.animate({"margin-left": currentEnd}, 150, function () {
            $currentPanel.hide(0).css("margin-left", 0);
            $targetPanel.css("margin-left", targetStart).show(0).animate({"margin-left": 0}, 150, function () {
                $currentPanel.removeClass("nicebackgrounds-tab-active");
                $targetPanel.addClass("nicebackgrounds-tab-active");
                group.removeClass("nicebackgrounds-animating");
            });
        });
    });

});