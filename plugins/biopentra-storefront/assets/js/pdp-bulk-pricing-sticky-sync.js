/**
 * Sync bulk pricing panel price to D2B sticky bar selectors (no child-theme edits).
 */
(function () {
	'use strict';

	function stickyPriceTarget() {
		return document.querySelector('[data-bp-sticky-bar] [data-bp-sticky-price]');
	}

	function panelPrice() {
		return document.querySelector('.bp-pdp-purchase-panel p.price, .summary p.price');
	}

	function syncStickyFromPanel() {
		var sticky = stickyPriceTarget();
		var panel = panelPrice();
		if (!sticky || !panel) {
			return;
		}
		sticky.innerHTML = panel.innerHTML;
	}

	document.addEventListener('bp-pdp-bulk-pricing-price-updated', syncStickyFromPanel);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', syncStickyFromPanel);
	} else {
		syncStickyFromPanel();
	}
})();
