/**
 * MOTION-1 footer controller — classify, observe, recover.
 * Does not host the head gate (see biopentra-motion-gate inline script).
 *
 * Homepage: observe section bands + curated-grid containers (stagger cards).
 * Shop (2026-08-31): per-card observe of /shop ed52b7f loop items only.
 */
(function () {
	'use strict';

	var NODE_FAILSAFE_MS = 25000;
	var STAGGER_CLEAR_MS = 700;
	var SECTION_SELECTOR =
		'body.home .bp-home-cats-section, body.home .bp-m5-trust, body.home .bp-m6-confidence, body.home .bp-m6-why, body.home .bp-m6-faq, body.home .bp-m7-guidance';
	var GRID_SELECTOR = 'body.home .bp-home-products-section';
	var CARD_SELECTOR = '.elementor-loop-container > .e-loop-item';
	var SHOP_WIDGET_ID = 'ed52b7f';
	var SHOP_WIDGET_SELECTOR = 'body.woocommerce-shop .elementor-element-' + SHOP_WIDGET_ID;

	var observer = null;
	var pendingNodes = [];
	var pendingTimers = typeof WeakMap === 'function' ? new WeakMap() : null;
	var inited = false;
	var reduceMq = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

	function htmlEl() {
		return document.documentElement;
	}

	function motionAllowed() {
		return (
			window.bpMotion &&
			window.bpMotion.allowed === true &&
			htmlEl().classList.contains('bp-motion')
		);
	}

	function isShopSurface() {
		if (document.body && document.body.classList.contains('woocommerce-shop')) {
			return true;
		}
		var path = (window.location.pathname || '').replace(/\/+$/, '');
		return path === '/shop' && !!document.querySelector('.elementor-element-' + SHOP_WIDGET_ID);
	}

	function isInView(el) {
		var r = el.getBoundingClientRect();
		var vh = window.innerHeight || htmlEl().clientHeight;
		var vw = window.innerWidth || htmlEl().clientWidth;
		return r.width > 0 && r.height > 0 && r.bottom > 0 && r.top < vh && r.right > 0 && r.left < vw;
	}

	function clearNodeTimer(el) {
		var id;
		if (pendingTimers) {
			id = pendingTimers.get(el);
			if (id) {
				window.clearTimeout(id);
				pendingTimers.delete(el);
			}
		}
		pendingNodes = pendingNodes.filter(function (node) {
			return node !== el;
		});
	}

	function setIn(el) {
		clearNodeTimer(el);
		el.setAttribute('data-bp-motion-state', 'in');
		el.style.removeProperty('will-change');
	}

	function maybeUnobserveAfterCard(el) {
		var grid;
		if (!observer) {
			return;
		}
		grid = el.closest ? el.closest('.bp-home-products-section') : null;
		if (!grid) {
			return;
		}
		if (!grid.querySelector(CARD_SELECTOR + '[data-bp-motion-state="pending"]')) {
			observer.unobserve(grid);
		}
	}

	function unobserveNode(el) {
		if (observer && el) {
			observer.unobserve(el);
		}
	}

	function startNodeTimer(el) {
		var id;
		clearNodeTimer(el);
		pendingNodes.push(el);
		id = window.setTimeout(function () {
			if (pendingTimers) {
				pendingTimers.delete(el);
			}
			pendingNodes = pendingNodes.filter(function (node) {
				return node !== el;
			});
			if (!el.isConnected || el.getAttribute('data-bp-motion-state') !== 'pending') {
				return;
			}
			setIn(el);
			if (el.getAttribute('data-bp-motion') === 'reveal') {
				unobserveNode(el);
			} else if (el.getAttribute('data-bp-motion') === 'shop-card') {
				unobserveNode(el);
			} else {
				maybeUnobserveAfterCard(el);
			}
		}, NODE_FAILSAFE_MS);
		if (pendingTimers) {
			pendingTimers.set(el, id);
		}
	}

	function setPending(el) {
		el.setAttribute('data-bp-motion-state', 'pending');
		el.style.willChange = 'opacity, transform';
		startNodeTimer(el);
	}

	function pruneDetachedPending() {
		pendingNodes.slice().forEach(function (el) {
			if (el && el.isConnected) {
				return;
			}
			clearNodeTimer(el);
			unobserveNode(el);
		});
	}

	function clearAllTimers() {
		pendingNodes.slice().forEach(function (el) {
			clearNodeTimer(el);
		});
		pendingNodes = [];
	}

	function disconnectObserver() {
		if (observer) {
			observer.disconnect();
			observer = null;
		}
	}

	function cleanup(reason) {
		clearAllTimers();
		disconnectObserver();
		if (reason === 'failsafe') {
			return;
		}
		if (reason === 'reduced' || reason === 'pagehide') {
			document.querySelectorAll('[data-bp-motion-state="pending"]').forEach(function (el) {
				el.setAttribute('data-bp-motion-state', 'in');
				el.style.removeProperty('will-change');
			});
		}
		if (reason === 'reduced') {
			htmlEl().classList.remove('bp-motion');
		}
	}

	window.bpMotionCleanup = cleanup;

	function revealSection(section) {
		setIn(section);
		unobserveNode(section);
	}

	function revealGrid(grid) {
		var cards = grid.querySelectorAll(CARD_SELECTOR);
		var i;
		grid.classList.add('bp-motion-staggering');
		for (i = 0; i < cards.length; i++) {
			if (cards[i].getAttribute('data-bp-motion-state') === 'pending') {
				setIn(cards[i]);
			}
		}
		unobserveNode(grid);
		window.setTimeout(function () {
			grid.classList.remove('bp-motion-staggering');
		}, STAGGER_CLEAR_MS);
	}

	function revealShopCard(card) {
		setIn(card);
		unobserveNode(card);
	}

	function onIntersect(entries) {
		entries.forEach(function (entry) {
			if (!entry.isIntersecting) {
				return;
			}
			var kind = entry.target.getAttribute('data-bp-motion');
			if (kind === 'stagger') {
				revealGrid(entry.target);
			} else if (kind === 'shop-card') {
				revealShopCard(entry.target);
			} else {
				revealSection(entry.target);
			}
		});
	}

	function classifySections() {
		document.querySelectorAll(SECTION_SELECTOR).forEach(function (section) {
			if (section.getAttribute('data-bp-motion-state') === 'in') {
				return;
			}
			section.setAttribute('data-bp-motion', 'reveal');
			if (isInView(section)) {
				setIn(section);
				return;
			}
			setPending(section);
			observer.observe(section);
		});
	}

	function classifyGrids() {
		document.querySelectorAll(GRID_SELECTOR).forEach(function (grid) {
			var cards = grid.querySelectorAll(CARD_SELECTOR);
			var i;
			var card;
			grid.setAttribute('data-bp-motion', 'stagger');
			if (!cards.length) {
				return;
			}
			if (isInView(grid)) {
				for (i = 0; i < cards.length; i++) {
					setIn(cards[i]);
				}
				return;
			}
			for (i = 0; i < cards.length; i++) {
				card = cards[i];
				if (card.getAttribute('data-bp-motion-state') === 'in') {
					continue;
				}
				setPending(card);
			}
			observer.observe(grid);
		});
	}

	function getShopWidget(scope) {
		var node = scope;
		if (node && node.jquery) {
			node = node[0];
		}
		if (node && node.nodeType === 1) {
			if (node.classList && node.classList.contains('elementor-element-' + SHOP_WIDGET_ID)) {
				return node;
			}
			if (node.closest) {
				var closest = node.closest('.elementor-element-' + SHOP_WIDGET_ID);
				if (closest) {
					return closest;
				}
			}
			if (node.querySelector) {
				var inner = node.querySelector('.elementor-element-' + SHOP_WIDGET_ID);
				if (inner) {
					return inner;
				}
			}
		}
		return document.querySelector(SHOP_WIDGET_SELECTOR);
	}

	function classifyShopCards(scope) {
		var widget;
		var cards;
		var i;
		var card;
		var state;
		if (!isShopSurface() || !observer) {
			return;
		}
		pruneDetachedPending();
		widget = getShopWidget(scope);
		if (!widget) {
			return;
		}
		cards = widget.querySelectorAll(CARD_SELECTOR);
		for (i = 0; i < cards.length; i++) {
			card = cards[i];
			state = card.getAttribute('data-bp-motion-state');
			if (state === 'in' || state === 'pending') {
				continue;
			}
			card.setAttribute('data-bp-motion', 'shop-card');
			if (isInView(card)) {
				setIn(card);
				continue;
			}
			setPending(card);
			observer.observe(card);
		}
	}

	function onLoopCardsInit(scope) {
		if (!motionAllowed() || !inited) {
			return;
		}
		classifyShopCards(scope);
	}

	function bindLoopCardsInit() {
		document.addEventListener('biopentra-loop-cards-init', function (event) {
			var detail = event && event.detail;
			onLoopCardsInit(detail || (event && event.target));
		});
		if (window.jQuery && window.jQuery.fn && window.jQuery.fn.on) {
			window.jQuery(document).on('biopentra-loop-cards-init', function (_event, scope) {
				onLoopCardsInit(scope);
			});
		}
	}

	function init() {
		if (inited) {
			return;
		}
		if (!motionAllowed()) {
			return;
		}

		try {
			observer = new IntersectionObserver(onIntersect, {
				root: null,
				rootMargin: '0px 0px -8% 0px',
				threshold: 0.12,
			});
			classifySections();
			classifyGrids();
			classifyShopCards(null);
			inited = true;
			if (window.bpMotion && window.bpMotion.failsafeId) {
				window.clearTimeout(window.bpMotion.failsafeId);
				window.bpMotion.failsafeId = null;
			}
		} catch (e) {
			return;
		}
	}

	function onReduceChange() {
		if (reduceMq && reduceMq.matches) {
			cleanup('reduced');
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	bindLoopCardsInit();

	if (reduceMq) {
		if (reduceMq.addEventListener) {
			reduceMq.addEventListener('change', onReduceChange);
		} else if (reduceMq.addListener) {
			reduceMq.addListener(onReduceChange);
		}
	}

	window.addEventListener('pagehide', function () {
		if (window.bpMotion && window.bpMotion.failsafeId) {
			window.clearTimeout(window.bpMotion.failsafeId);
			window.bpMotion.failsafeId = null;
		}
		cleanup('pagehide');
	});
})();
