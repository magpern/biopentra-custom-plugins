/**
 * PDP bulk pricing — anchor selection, custom quantity preview (display only).
 */
(function () {
	'use strict';

	var cfg = window.bpPdpBulkPricing || {};
	var contract = cfg.contract || null;

	function root() {
		return document.querySelector('[data-bp-pdp-bulk-pricing]');
	}

	function qtyInput() {
		return document.querySelector('form.cart input.qty, form.cart .qty');
	}

	function panelPrice() {
		return document.querySelector('.bp-pdp-purchase-panel p.price, .summary p.price');
	}

	function minorToHtml(minor, decimals, currency) {
		if (!contract || typeof minor !== 'number') {
			return '';
		}
		var factor = Math.pow(10, decimals || contract.decimals || 2);
		var amount = minor / factor;
		try {
			return new Intl.NumberFormat(undefined, {
				style: 'currency',
				currency: currency || contract.currency || 'EUR',
			}).format(amount);
		} catch (e) {
			return amount.toFixed(decimals || 2);
		}
	}

	function resolveBracket(qty) {
		if (!contract || !Array.isArray(contract.bracket_table)) {
			return null;
		}
		var matched = null;
		contract.bracket_table.forEach(function (row) {
			if (qty >= row.min_quantity) {
				if (!matched || row.min_quantity > matched.min_quantity) {
					matched = row;
				}
			}
		});
		return matched;
	}

	function syncQtyFromAnchor(anchorQty) {
		var input = qtyInput();
		if (!input) {
			return;
		}
		input.value = String(anchorQty);
		input.dispatchEvent(new Event('change', { bubbles: true }));
	}

	function updatePanelPrice(unitMinor) {
		var priceEl = panelPrice();
		if (!priceEl || unitMinor == null) {
			return;
		}
		var html = minorToHtml(unitMinor, contract.decimals, contract.currency);
		if (html) {
			priceEl.innerHTML = '<span class="woocommerce-Price-amount amount"><bdi>' + html + '</bdi></span>';
			document.dispatchEvent(
				new CustomEvent('bp-pdp-bulk-pricing-price-updated', {
					detail: { unitMinor: unitMinor, unitHtml: html },
				})
			);
		}
	}

	function syncCustomPreview(qty) {
		var wrap = document.querySelector('[data-bp-bulk-custom-preview]');
		var unitEl = document.querySelector('[data-bp-bulk-custom-unit]');
		var totalEl = document.querySelector('[data-bp-bulk-custom-total]');
		if (!wrap || !unitEl || !totalEl) {
			return;
		}

		var anchors = (contract && contract.anchors) || [];
		var anchorQtys = anchors.map(function (a) {
			return a.anchor_quantity;
		});
		var isCustom = anchorQtys.indexOf(qty) === -1;

		if (!isCustom) {
			wrap.hidden = true;
			return;
		}

		var bracket = resolveBracket(qty);
		if (!bracket) {
			wrap.hidden = true;
			return;
		}

		wrap.hidden = false;
		unitEl.textContent = minorToHtml(bracket.unit_minor, contract.decimals, contract.currency) + ' / unit';
		totalEl.textContent = minorToHtml(bracket.unit_minor * qty, contract.decimals, contract.currency) + ' total';
		updatePanelPrice(bracket.unit_minor);
	}

	function syncFromQty() {
		var input = qtyInput();
		if (!input) {
			return;
		}
		var qty = Math.max(1, parseInt(input.value, 10) || 1);
		var radios = document.querySelectorAll('.bp-pdp-bulk-pricing__radio');
		var matched = false;
		radios.forEach(function (radio) {
			var anchorQty = parseInt(radio.value, 10);
			var isMatch = anchorQty === qty;
			radio.checked = isMatch;
			if (isMatch) {
				matched = true;
				updatePanelPrice(parseInt(radio.getAttribute('data-unit-minor'), 10));
			}
		});
		if (!matched) {
			syncCustomPreview(qty);
		} else {
			var custom = document.querySelector('[data-bp-bulk-custom-preview]');
			if (custom) {
				custom.hidden = true;
			}
		}
	}

	function init() {
		var el = root();
		if (!el || !contract) {
			return;
		}

		el.hidden = false;

		el.addEventListener('change', function (event) {
			var target = event.target;
			if (!target || !target.classList.contains('bp-pdp-bulk-pricing__radio')) {
				return;
			}
			var qty = parseInt(target.value, 10);
			syncQtyFromAnchor(qty);
			updatePanelPrice(parseInt(target.getAttribute('data-unit-minor'), 10));
			var custom = document.querySelector('[data-bp-bulk-custom-preview]');
			if (custom) {
				custom.hidden = true;
			}
		});

		var input = qtyInput();
		if (input) {
			input.addEventListener('change', syncFromQty);
			input.addEventListener('input', syncFromQty);
		}

		syncFromQty();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
