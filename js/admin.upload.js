/**
 * Scripts for the Upload add form.
 */

jQuery(document).ready(function ($) {
    'use strict';

    $('.nicebackgrounds-upload').each(function () {
        var $upload = $(this);
        var $input = $(this).find('input[type="file"]');
        var $container = $upload.closest(".nicebackgrounds-tab-group-set > .nicebackgrounds-tab-panels > div");
        var droppedFiles = false;

        $input.on('change', function (e) {
            e.preventDefault();
            if ($upload.hasClass('is-wait')) {
                return false;
            }
            $upload.addClass('is-wait').removeClass('is-error').removeClass('is-failure');
            var ajaxData = new FormData();

            if (droppedFiles) {
                $.each(droppedFiles, function (i, file) {
                    ajaxData.append('file' + i, file);
                });
                // Clear files.
                droppedFiles = false;
            } else {
                for (var i = 0; i < $input[0].files.length; i++) {
                    ajaxData.append('file' + i, $input[0].files[i]);
                }
            }

            ajaxData.append('action', 'nicebackgrounds');
            ajaxData.append('func', 'save_upload');
            ajaxData.append('set_id', $container.data('tab-key'));
            ajaxData.append('nonce', $upload.data('save-upload'));

            $.ajax({
                    type: "POST",
                    url: nicebackgrounds_data.ajax_url,
                    data: ajaxData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function (response) {
                        var suffix = 'error';
                        var message = '???';
                        if (null !== response && response.success) {
                            suffix = 'success';
                            if (null !== response.message) {
                                message = response.message;
                                var $result = $(response.result);
                                $result.hide(0);
                                $container.find(".nicebackgrounds-collection-display").append($result);
                                $result.show(150);
                            }
                        }
                        $upload.addClass('is-' + suffix);
                        var $msg = $('<span class="message">' + message + "</span>");
                        $upload.find('.add-status-' + suffix).append($msg);
                        setTimeout(function () {
                            $msg.remove();
                            $upload.removeClass('is-' + suffix);
                        }, 3000);
                    },
                })
                .fail(function () {
                    $upload.addClass('is-failure');
                    setTimeout(function () {
                        $upload.removeClass('is-failure');
                    }, 3000);
                })
                .always(function () {
                    $upload.removeClass('is-wait');
                });
        });

        $input.siblings("button").click(function () {
            $input.click();
        });

        var div = document.createElement('div');
        if (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) {
            $upload
                .addClass('has-advanced-upload')
                .on('drag dragstart dragend dragover dragenter dragleave drop', function (e) {
                    // preventing the unwanted behaviours
                    e.preventDefault();
                    e.stopPropagation();
                })
                .on('dragover dragenter', function () {
                    $(this).addClass('is-dragover').addClass('is-draggingover');
                    if (null !== $(this).data('timeout') || undefined !== $(this).data('timeout')) {
                        clearTimeout($(this).data("timeout"));
                        $(this).removeData("timeout");
                    }
                })
                .on('dragleave dragend drop', function () {
                    $(this).removeClass('is-draggingover');
                    var timeout = setTimeout(function () {
                        $(this).removeClass('is-dragover');
                    }, 300);
                    $(this).data("timeout", timeout);
                })
                .on('drop', function (e) {
                    $(this).removeClass('is-dragover');
                    droppedFiles = e.originalEvent.dataTransfer.files;
                    $input.trigger("change");
                });
        }

    });

});