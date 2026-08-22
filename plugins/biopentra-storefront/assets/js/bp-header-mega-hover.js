/**
 * Desktop Information mega-menu hover handoff (E1.2 patch).
 *
 * Elementor Pro closes the active mega tab when the pointer enters any sibling
 * title. Keep hover-open, but block that deactivation while the Information
 * panel is open on desktop only.
 */
(function () {
	'use strict';

	var DESKTOP_MQ = '(min-width: 1025px)';

	function isDesktop() {
		return window.matchMedia(DESKTOP_MQ).matches;
	}

	function getInformationItems() {
		return Array.prototype.slice.call(
			document.querySelectorAll('.elementor-location-header .e-n-menu-item .biopentra-information-mega')
		).map(function (mega) {
			return mega.closest('.e-n-menu-item');
		}).filter(Boolean);
	}

	function getContent(item) {
		return item ? item.querySelector(':scope > .e-n-menu-content') : null;
	}

	function isInformationOpen(item) {
		var content = getContent(item);
		if (!content) {
			return false;
		}
		return (
			content.classList.contains('e-active') ||
			content.getAttribute('aria-hidden') === 'false'
		);
	}

	function isSiblingTitle(target, item) {
		var title = target.closest('.e-n-menu-title');
		if (!title) {
			return false;
		}
		var titleItem = title.closest('.e-n-menu-item');
		return !!(titleItem && titleItem !== item);
	}

	function blockSiblingTitleActivation(event, item) {
		if (!isDesktop() || !isInformationOpen(item)) {
			return;
		}
		if (!isSiblingTitle(event.target, item)) {
			return;
		}
		event.stopImmediatePropagation();
		event.preventDefault();
	}

	function bindMenu(menu, item) {
		if (!menu || menu.getAttribute('data-bp-mega-hover-bound') === '1') {
			return;
		}
		menu.setAttribute('data-bp-mega-hover-bound', '1');

		// Capture before Elementor's per-title mouseenter handlers run.
		menu.addEventListener('mouseenter', function (event) {
			blockSiblingTitleActivation(event, item);
		}, true);
		menu.addEventListener('mouseover', function (event) {
			blockSiblingTitleActivation(event, item);
		}, true);
	}

	function init() {
		var items = getInformationItems();
		if (!items.length) {
			return;
		}
		items.forEach(function (item) {
			var menu = item.closest('.e-n-menu');
			bindMenu(menu, item);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	window.addEventListener('elementor/frontend/init', init);
})();
