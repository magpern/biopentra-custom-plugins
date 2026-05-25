/**
 * BioPentra mini-cart drawer — layout class, loading states, qty updates.
 */
(function ($) {
	'use strict';

	var config = window.bpMiniCart || {};
	var SCOPES = [
		'.elementor-menu-cart--cart-type-side-cart',
		'.elementor-menu-cart--cart-type-mini-cart',
		'#woo-cart-panel',
		'.ct-header-cart'
	];

	function getDrawerRoots() {
		var roots = [];
		document.querySelectorAll('.elementor-menu-cart__main').forEach(function (el) {
			roots.push(el);
		});
		var panel = document.getElementById('woo-cart-panel');
		if (panel) {
			roots.push(panel);
		}
		document.querySelectorAll('.ct-cart-content').forEach(function (el) {
			roots.push(el);
		});
		return roots;
	}

	function markDrawers() {
		getDrawerRoots().forEach(function (root) {
			root.classList.add('bp-mini-cart--drawer');
		});
		SCOPES.forEach(function (sel) {
			document.querySelectorAll(sel).forEach(function (el) {
				el.classList.add('bp-mini-cart--drawer');
			});
		});
	}

	function setLoading(on) {
		getDrawerRoots().forEach(function (root) {
			root.classList.toggle('is-loading', on);
		});
	}

	function parseCartHash(name) {
		var match = name && name.match(/cart\[([\w]+)\]\[qty\]/);
		return match ? match[1] : null;
	}

	function updateQuantity(input) {
		var hash = parseCartHash(input.name);
		if (!hash || !config.ajaxUrl) {
			return;
		}

		var qty = parseFloat(input.value);
		if (!input.reportValidity()) {
			return;
		}

		var row = input.closest('.elementor-menu-cart__product, .woocommerce-mini-cart-item');
		if (row) {
			row.classList.add('processing');
		}
		setLoading(true);

		var done = function () {
			setLoading(false);
			if (row) {
				row.classList.remove('processing');
			}
			$(document.body).trigger('wc_fragment_refresh');
			$(document.body).trigger('wc_fragments_refreshed');
			$(document.body).trigger('updated_wc_div');
		};

		if (config.hasBlocksyQty && typeof window.ctEvents !== 'undefined') {
			$.ajax({
				type: 'POST',
				url: config.ajaxUrl,
				data: {
					action: 'blocksy_update_qty_cart',
					hash: hash,
					quantity: qty
				}
			}).always(done);
			return;
		}

		$.ajax({
			type: 'POST',
			url: config.ajaxUrl,
			data: {
				action: 'biopentra_update_mini_cart_qty',
				hash: hash, quantity: qty
			}
		});

		// Fallback: refresh fragments (WC + Elementor listen).
		setTimeout(done, 400);
	}

	function bindQty() {
		$(document.body).on('change', '.bp-mini-cart--drawer .qty', function () {
			updateQuantity(this);
		});
	}

	function bindLoading() {
		$(document.body).on('adding_to_cart', function () {
			setLoading(true);
		});
		$(document.body).on('wc_fragments_loaded wc_fragments_refreshed removed_from_cart added_to_cart', function () {
			setLoading(false);
			markDrawers();
		});
	}

	function init() {
		markDrawers();
		bindQty();
		bindLoading();
	}

	$(init);
	$(document.body).on('wc_fragments_loaded wc_fragments_refreshed', markDrawers);

	if (typeof window.ctEvents !== 'undefined') {
		window.ctEvents.on('blocksy:frontend:init', markDrawers);
	}
})(jQuery);
