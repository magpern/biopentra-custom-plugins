/**
 * Focus #reviews after summary-link activation or deep-link hash (A11).
 * Sticky buy bar is out of scope — do not touch sticky selectors.
 */
(function () {
	'use strict';

	function reviewsEl() {
		return document.getElementById('reviews');
	}

	function focusReviews() {
		var el = reviewsEl();
		if (!el) {
			return;
		}
		if (!el.hasAttribute('tabindex')) {
			el.setAttribute('tabindex', '-1');
		}
		try {
			el.focus({ preventScroll: false });
		} catch (e) {
			el.focus();
		}
	}

	function hashIsReviews() {
		return window.location.hash === '#reviews';
	}

	function onHash() {
		if (hashIsReviews()) {
			focusReviews();
		}
	}

	document.addEventListener(
		'click',
		function (event) {
			var target = event.target;
			if (!target || typeof target.closest !== 'function') {
				return;
			}
			var link = target.closest('a[href*="#reviews"]');
			if (!link) {
				return;
			}
			window.setTimeout(function () {
				if (hashIsReviews()) {
					focusReviews();
				}
			}, 0);
		},
		false
	);

	window.addEventListener('hashchange', onHash, false);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', onHash, false);
	} else {
		onHash();
	}
})();
