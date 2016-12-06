/*
	Hyperspace by HTML5 UP
	html5up.net | @ajlkn
	Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
*/

(function($) {

	skel.breakpoints({
		xlarge:	'(max-width: 1680px)',
		large:	'(max-width: 1280px)',
		medium:	'(max-width: 980px)',
		small:	'(max-width: 736px)',
		xsmall:	'(max-width: 480px)'
	});

	$(function() {

		var	$window = $(window),
			$body = $('body'),
			$sidebar = $('#sidebar');

		// Hack: Enable IE flexbox workarounds.
			if (skel.vars.IEVersion < 12)
				$body.addClass('is-ie');

		// Disable animations/transitions until the page has loaded.
			if (skel.canUse('transition'))
				$body.addClass('is-loading');

			$window.on('load', function() {
				window.setTimeout(function() {
					$body.removeClass('is-loading');
				}, 100);
			});

		// Forms.

			// Fix: Placeholder polyfill.
				$('form').placeholder();

			// Hack: Activate non-input submits.
				$('form').on('click', '.submit', function(event) {

					// Stop propagation, default.
						event.stopPropagation();
						event.preventDefault();

					// Submit form.
						$(this).parents('form').submit();

				});

		// Prioritize "important" elements on medium.
			skel.on('+medium -medium', function() {
				$.prioritize(
					'.important\\28 medium\\29',
					skel.breakpoint('medium').active
				);
			});
	});

})(jQuery);