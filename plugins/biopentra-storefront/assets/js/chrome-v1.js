/**
 * Milestone E — header search control behaviour (frozen).
 *
 * - If #biopentra-shop-s or #biopentra-search-refine is visible: focus + scrollIntoView
 * - Else: navigate to shop page #biopentra-shop-s
 */
(function () {
	'use strict';

	function firstVisibleInput() {
		var selectors = ['#biopentra-shop-s', '#biopentra-search-refine'];
		for (var i = 0; i < selectors.length; i++) {
			var el = document.querySelector(selectors[i]);
			if (!el) {
				continue;
			}
			var rect = el.getBoundingClientRect();
			var style = window.getComputedStyle(el);
			if (
				style.display !== 'none' &&
				style.visibility !== 'hidden' &&
				rect.width > 0 &&
				rect.height > 0
			) {
				return el;
			}
		}
		return null;
	}

	function onSearchActivate(event) {
		if (event) {
			event.preventDefault();
		}
		var input = firstVisibleInput();
		if (input) {
			input.scrollIntoView({ behavior: 'smooth', block: 'center' });
			window.setTimeout(function () {
				input.focus({ preventScroll: true });
			}, 50);
			return;
		}
		var url =
			(window.biopentraChrome && window.biopentraChrome.shopSearchUrl) ||
			'/shop/#biopentra-shop-s';
		window.location.assign(url);
	}

	function bind() {
		document.querySelectorAll('[data-bp-chrome-search]').forEach(function (btn) {
			if (btn.getAttribute('data-bp-bound') === '1') {
				return;
			}
			btn.setAttribute('data-bp-bound', '1');
			btn.addEventListener('click', onSearchActivate);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bind);
	} else {
		bind();
	}

	// Focus shop search when arriving with fragment.
	function focusFragment() {
		if (window.location.hash !== '#biopentra-shop-s') {
			return;
		}
		var input = document.querySelector('#biopentra-shop-s');
		if (input) {
			input.focus({ preventScroll: false });
		}
	}
	window.addEventListener('load', focusFragment);
})();
