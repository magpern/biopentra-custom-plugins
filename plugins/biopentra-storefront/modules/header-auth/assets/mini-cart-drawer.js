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
	var preserveDrawerAfterRemove = false;
	var preserveDrawerTimers = [];
	var preserveDrawerKind = null;

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

	function clearPreserveDrawerTimers() {
		preserveDrawerTimers.forEach(function (timer) {
			window.clearTimeout(timer);
		});
		preserveDrawerTimers = [];
	}

	function isElementorCartOpen() {
		var container = document.querySelector('.elementor-menu-cart__container');
		var drawer = document.querySelector('.elementor-menu-cart__main');
		var toggle = document.querySelector('.elementor-menu-cart__toggle_button');
		var widget = (drawer || container || toggle) && (drawer || container || toggle).closest('.elementor-widget-woocommerce-menu-cart');

		return !!(
			(widget && widget.classList.contains('elementor-menu-cart--shown')) ||
			(container && container.getAttribute('aria-hidden') === 'false') ||
			(drawer && drawer.getAttribute('aria-hidden') === 'false') ||
			(toggle && toggle.getAttribute('aria-expanded') === 'true')
		);
	}

	function forceElementorCartOpen() {
		var container = document.querySelector('.elementor-menu-cart__container');
		var drawer = document.querySelector('.elementor-menu-cart__main');
		var toggle = document.querySelector('.elementor-menu-cart__toggle_button');
		var source = drawer || container || toggle;
		var widget = source && source.closest('.elementor-widget-woocommerce-menu-cart');

		if (!source) {
			return false;
		}

		if (widget) {
			widget.classList.add('elementor-menu-cart--shown');
		}
		if (container) {
			container.setAttribute('aria-hidden', 'false');
		}
		if (drawer) {
			drawer.setAttribute('aria-hidden', 'false');
		}
		if (toggle) {
			toggle.setAttribute('aria-expanded', 'true');
		}

		return true;
	}

	function openElementorCart() {
		if (isElementorCartOpen()) {
			forceElementorCartOpen();
			return true;
		}

		var toggle = document.querySelector('.elementor-menu-cart__toggle_button');
		if (!toggle) {
			return false;
		}

		toggle.click();
		return forceElementorCartOpen();
	}

	function isBlocksyCartOpen() {
		var panel = document.getElementById('woo-cart-panel');
		var cartContent = document.querySelector('.ct-header-cart .ct-cart-content');

		return !!(
			(panel && (panel.classList.contains('active') || panel.getAttribute('aria-hidden') === 'false')) ||
			(cartContent && cartContent.offsetParent !== null)
		);
	}

	function openBlocksyCart() {
		if (isBlocksyCartOpen()) {
			return true;
		}

		var trigger = document.querySelector('.ct-header-cart .ct-cart-item, .ct-header-cart > a');
		if (!trigger) {
			return false;
		}

		trigger.click();
		return true;
	}

	function getWooAjaxUrl(endpoint) {
		var params = window.wc_add_to_cart_params || window.wc_cart_fragments_params || {};

		if (!params.wc_ajax_url) {
			return '';
		}

		return params.wc_ajax_url.toString().replace('%%endpoint%%', endpoint);
	}

	function replaceFragments(fragments) {
		if (!fragments) {
			return;
		}

		$.each(fragments, function (selector, html) {
			$(selector).replaceWith(html);
		});
	}

	function restorePreservedDrawer() {
		if (!preserveDrawerAfterRemove) {
			return;
		}

		markDrawers();
		setLoading(false);

		if (preserveDrawerKind === 'elementor') {
			openElementorCart();
			return;
		}

		if (preserveDrawerKind === 'blocksy') {
			openBlocksyCart();
			return;
		}

		openElementorCart() || openBlocksyCart();
	}

	function queuePreservedDrawerRestore() {
		clearPreserveDrawerTimers();

		[120, 600, 1400, 2600, 4200].forEach(function (delay) {
			preserveDrawerTimers.push(window.setTimeout(restorePreservedDrawer, delay));
		});

		preserveDrawerTimers.push(window.setTimeout(function () {
			preserveDrawerAfterRemove = false;
			preserveDrawerKind = null;
			clearPreserveDrawerTimers();
		}, 5500));
	}

	function rememberDrawerRemove(button) {
		if (!button || !button.closest('.bp-mini-cart--drawer')) {
			return;
		}

		preserveDrawerAfterRemove = true;
		preserveDrawerKind = button.closest('.elementor-menu-cart__main, .elementor-menu-cart__container')
			? 'elementor'
			: 'blocksy';
		setLoading(true);
		queuePreservedDrawerRestore();
	}

	function removeCartItem(button) {
		var cartItemKey = button.getAttribute('data-cart_item_key');
		var removeUrl = getWooAjaxUrl('remove_from_cart');
		var row = button.closest('.bp-mini-cart-row, .elementor-menu-cart__product, .woocommerce-mini-cart-item');

		if (!cartItemKey || !removeUrl) {
			return false;
		}

		if (row) {
			row.classList.add('processing');
		}

		$.ajax({
			type: 'POST',
			url: removeUrl,
			data: {
				cart_item_key: cartItemKey
			},
			dataType: 'json'
		}).done(function (response) {
			if (!response || !response.fragments) {
				window.location.href = button.href;
				return;
			}

			replaceFragments(response.fragments);
			$(document.body).trigger('removed_from_cart', [response.fragments, response.cart_hash, $(button)]);
			$(document.body).trigger('wc_fragments_refreshed');
			$(document.body).trigger('updated_wc_div');
		}).fail(function () {
			window.location.href = button.href;
		}).always(function () {
			if (row) {
				row.classList.remove('processing');
			}
			setLoading(false);
			markDrawers();
			restorePreservedDrawer();
			queuePreservedDrawerRestore();
		});

		return true;
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

		var row = input.closest('.bp-mini-cart-row, .elementor-menu-cart__product, .woocommerce-mini-cart-item');
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

	function bindRemovePreserve() {
		document.addEventListener(
			'click',
			function (event) {
				var button = event.target.closest && event.target.closest(
					'.bp-mini-cart--drawer .remove_from_cart_button, .bp-mini-cart--drawer .elementor_remove_from_cart_button'
				);

				if (button) {
					rememberDrawerRemove(button);
					if (removeCartItem(button)) {
						event.preventDefault();
						event.stopPropagation();
						event.stopImmediatePropagation();
					}
				}
			},
			true
		);

		$(document.body).on(
			'click',
			'.bp-mini-cart--drawer .remove_from_cart_button, .bp-mini-cart--drawer .elementor_remove_from_cart_button',
			function () {
				rememberDrawerRemove(this);
			}
		);
	}

	function bindRemoveAjaxRestore() {
		$(document).ajaxComplete(function () {
			if (!preserveDrawerAfterRemove) {
				return;
			}

			restorePreservedDrawer();
			queuePreservedDrawerRestore();
		});
	}

	function bindLoading() {
		$(document.body).on('adding_to_cart', function () {
			setLoading(true);
		});
		$(document.body).on('wc_fragments_loaded wc_fragments_refreshed removed_from_cart added_to_cart', function () {
			setLoading(false);
			markDrawers();
			restorePreservedDrawer();
		});
	}

	function init() {
		markDrawers();
		bindQty();
		bindRemovePreserve();
		bindRemoveAjaxRestore();
		bindLoading();
	}

	$(init);
	$(document.body).on('wc_fragments_loaded wc_fragments_refreshed', markDrawers);

	if (typeof window.ctEvents !== 'undefined') {
		window.ctEvents.on('blocksy:frontend:init', markDrawers);
	}
})(jQuery);
