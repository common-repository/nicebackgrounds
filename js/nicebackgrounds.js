/**
 * Scripts for the front-end.
 */

jQuery(document).ready(function ($) {
	'use strict';

	var num_auto_sels = nicebackgrounds_data.auto_sels.length;
	_.each(nicebackgrounds_data.auto_sels, function (settings, set_id) {

		$(settings.sel).each(function () {
			var size = null;
			switch (settings.dimension) {
				case 'width':
					size = (settings.measure == 'screen') ? screen.width : $(this).innerWidth();
					break;
				case 'height':
					size = (settings.measure == 'screen') ? screen.height : $(this).innerHeight();
					break;
				case 'longest':
					size = (settings.measure == 'screen') ?
						Math.max(screen.width, screen.height) :
						Math.max($(this).innerWidth(), $(this).innerHeight());
					break;
			}
			var pixel_ratio = window.devicePixelRatio ? window.devicePixelRatio : 1;
			size = size * pixel_ratio;

			var url = nicebackgrounds_data.display + "?nicebackgrounds=" + set_id + "&size=" + size;

			$(this)
				.addClass('nicebackgrounds')
				.addClass('nicebackgrounds-set-' + set_id)
				.css('background-image', 'url(' + url + ')');

		});

	});

});

