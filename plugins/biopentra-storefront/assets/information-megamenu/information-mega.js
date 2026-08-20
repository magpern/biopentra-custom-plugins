/**
 * Desktop: flyouts use :hover + .is-active (JS); one active disclosure at a time.
 * Mobile: accordion only (.biopentra-info-mega-inline-detail).
 */
(function () {
	'use strict';

	if (window.__BIOPENTRA_INFO_MEGA_JS__) {
		return;
	}
	window.__BIOPENTRA_INFO_MEGA_JS__ = true;

	var MQ = '(min-width: 1025px)';

	var TITLE_TO_DETAIL_ID = {
		'quality standards': 'quality-standards',
		'third-party testing': 'third-party-testing',
		'certificates / coas': 'certificates-coas',
		'purity & analytics': 'purity-analytics',
		'compliance & safety': 'compliance-safety'
	};

	/**
	 * Elementor HTML widgets sometimes strip <template> wrappers and leave
	 * bare .bp-detail-template-root divs. Rebuild real <template> nodes so
	 * .content cloning works for desktop flyouts + mobile accordion.
	 */
	function normalizeTemplates(root) {
		if (!root || root.getAttribute('data-bp-templates-normalized') === '1') {
			return;
		}
		root.setAttribute('data-bp-templates-normalized', '1');
		var host = root.querySelector('.biopentra-info-mega-templates');
		if (!host) {
			return;
		}
		if (host.querySelector('template[id^="bp-detail-template-"]')) {
			return;
		}
		var roots = Array.prototype.slice.call(
			host.querySelectorAll(':scope > .bp-detail-template-root')
		);
		roots.forEach(function (wrap) {
			var heading = wrap.querySelector('.biopentra-info-mega-detail-heading');
			var title = (heading && heading.textContent ? heading.textContent : '')
				.replace(/\s+/g, ' ')
				.trim()
				.toLowerCase();
			var id = TITLE_TO_DETAIL_ID[title];
			if (!id) {
				id = title
					.replace(/&/g, ' ')
					.replace(/[^a-z0-9]+/g, '-')
					.replace(/^-+|-+$/g, '');
			}
			if (!id || host.querySelector('#bp-detail-template-' + id)) {
				return;
			}
			var tpl = document.createElement('template');
			tpl.id = 'bp-detail-template-' + id;
			tpl.content.appendChild(wrap);
			host.appendChild(tpl);
		});
	}

	function getTemplate(root, detailId) {
		normalizeTemplates(root);
		return root.querySelector('#bp-detail-template-' + detailId);
	}

	function syncAriaControls(triggers, desktop) {
		triggers.forEach(function (btn) {
			var id = btn.getAttribute('data-bp-detail-id');
			if (!id) return;
			btn.setAttribute(
				'aria-controls',
				desktop ? 'bp-flyout-' + id : 'bp-inline-' + id
			);
		});
	}

	function fillFlyoutFromTemplate(root, detailId, flyoutEl) {
		var tpl = getTemplate(root, detailId);
		if (!tpl || !tpl.content || !flyoutEl) return false;
		var frag = tpl.content.cloneNode(true);
		var wrap = frag.querySelector('.bp-detail-template-root');
		if (!wrap) return false;
		flyoutEl.innerHTML = '';
		flyoutEl.appendChild(wrap);
		return true;
	}

	function clearFlyoutContents(root) {
		root.querySelectorAll('.biopentra-info-mega-flyout').forEach(function (fly) {
			fly.innerHTML = '';
			delete fly.dataset.bpPrefilled;
			fly.setAttribute('aria-hidden', 'true');
		});
	}

	function prefillDesktopFlyouts(root) {
		root.querySelectorAll('.biopentra-info-mega-item.biopentra-info-mega-disclosure').forEach(function (item) {
			var btn = item.querySelector('.biopentra-info-mega-trigger');
			var fly = item.querySelector('.biopentra-info-mega-flyout');
			if (!btn || !fly || fly.dataset.bpPrefilled === '1') return;
			var id = btn.getAttribute('data-bp-detail-id');
			if (!id) return;
			if (fillFlyoutFromTemplate(root, id, fly)) {
				fly.dataset.bpPrefilled = '1';
				fly.setAttribute('aria-hidden', 'true');
			}
		});
	}

	function clearDesktopActive(root) {
		root.querySelectorAll('.biopentra-info-mega-item.biopentra-info-mega-disclosure.is-active').forEach(function (el) {
			el.classList.remove('is-active');
		});
	}

	function syncFlyoutAria(root) {
		root.querySelectorAll('.biopentra-info-mega-item.biopentra-info-mega-disclosure').forEach(function (item) {
			var fly = item.querySelector('.biopentra-info-mega-flyout');
			if (!fly) return;
			var open = item.classList.contains('is-active');
			try {
				open = open || item.matches(':hover');
			} catch (e) {
				/* ignore */
			}
			fly.setAttribute('aria-hidden', open ? 'false' : 'true');
		});
	}

	function bindDesktopDisclosure(root, mq) {
		root.querySelectorAll('.biopentra-info-mega-item.biopentra-info-mega-disclosure').forEach(function (item) {
			if (item.dataset.bpDesktopDisclosureBound === '1') return;
			item.dataset.bpDesktopDisclosureBound = '1';

			function activateOnlyThis() {
				clearDesktopActive(root);
				item.classList.add('is-active');
				syncFlyoutAria(root);
			}

			function maybeDeactivateThis() {
				window.setTimeout(function () {
					if (!mq.matches) return;
					try {
						if (!item.matches(':hover') && !item.contains(document.activeElement)) {
							item.classList.remove('is-active');
						}
					} catch (e) {
						item.classList.remove('is-active');
					}
					syncFlyoutAria(root);
				}, 80);
			}

			item.addEventListener('pointerenter', function () {
				if (!mq.matches) return;
				clearDesktopActive(root);
				item.classList.add('is-active');
				syncFlyoutAria(root);
			});

			item.addEventListener('pointerleave', function () {
				if (!mq.matches) return;
				maybeDeactivateThis();
			});

			item.addEventListener('focusin', function () {
				if (!mq.matches) return;
				activateOnlyThis();
			});

			item.addEventListener('focusout', function () {
				if (!mq.matches) return;
				maybeDeactivateThis();
			});
		});
	}

	function clearMobile(root, triggers) {
		triggers.forEach(function (btn) {
			var item = btn.closest('.biopentra-info-mega-item');
			var pan = item ? item.querySelector('.biopentra-info-mega-inline-detail') : null;
			btn.setAttribute('aria-expanded', 'false');
			if (pan) {
				pan.hidden = true;
				pan.innerHTML = '';
				pan.setAttribute('aria-hidden', 'true');
			}
		});
	}

	function openMobileInline(root, detailId, triggerEl) {
		var item = triggerEl.closest('.biopentra-info-mega-item');
		var pan = item ? item.querySelector('.biopentra-info-mega-inline-detail') : null;
		if (!pan) return;
		var tpl = getTemplate(root, detailId);
		if (!tpl || !tpl.content) return;
		var frag = tpl.content.cloneNode(true);
		var wrap = frag.querySelector('.bp-detail-template-root');
		if (!wrap) return;
		var copy = wrap.querySelector('.biopentra-info-mega-detail-copy');
		var learn = wrap.querySelector('.biopentra-info-mega-detail-learn');
		pan.innerHTML = '';
		var inner = document.createElement('div');
		inner.className = 'biopentra-info-mega-inline-detail-inner';
		if (copy) inner.innerHTML = copy.innerHTML;
		if (learn) inner.appendChild(learn.cloneNode(true));
		pan.appendChild(inner);
		pan.hidden = false;
		pan.setAttribute('aria-hidden', 'false');
	}

	function initMega(root) {
		if (root.getAttribute('data-bp-mega-init') === '1') return;
		root.setAttribute('data-bp-mega-init', '1');
		normalizeTemplates(root);

		var mq = window.matchMedia(MQ);
		var triggers = Array.prototype.slice.call(
			root.querySelectorAll('.biopentra-info-mega-trigger[data-bp-detail-id]')
		);
		if (!triggers.length) return;

		function applyMode() {
			syncAriaControls(triggers, mq.matches);
			if (mq.matches) {
				clearMobile(root, triggers);
				clearDesktopActive(root);
				prefillDesktopFlyouts(root);
				bindDesktopDisclosure(root, mq);
				syncFlyoutAria(root);
			} else {
				clearDesktopActive(root);
				clearFlyoutContents(root);
				clearMobile(root, triggers);
			}
		}

		applyMode();

		triggers.forEach(function (btn) {
			var id = btn.getAttribute('data-bp-detail-id');
			if (!id) return;
			var item = btn.closest('.biopentra-info-mega-item');

			btn.addEventListener('click', function (e) {
				if (mq.matches) {
					e.preventDefault();
					btn.blur();
					clearDesktopActive(root);
					syncFlyoutAria(root);
					return;
				}
				e.preventDefault();
				var expanded = btn.getAttribute('aria-expanded') === 'true';
				triggers.forEach(function (other) {
					if (other === btn) return;
					other.setAttribute('aria-expanded', 'false');
					var oItem = other.closest('.biopentra-info-mega-item');
					var p = oItem ? oItem.querySelector('.biopentra-info-mega-inline-detail') : null;
					if (p) {
						p.hidden = true;
						p.innerHTML = '';
						p.setAttribute('aria-hidden', 'true');
					}
				});
				var pan = item ? item.querySelector('.biopentra-info-mega-inline-detail') : null;
				if (expanded) {
					btn.setAttribute('aria-expanded', 'false');
					if (pan) {
						pan.hidden = true;
						pan.innerHTML = '';
						pan.setAttribute('aria-hidden', 'true');
					}
					return;
				}
				openMobileInline(root, id, btn);
				btn.setAttribute('aria-expanded', 'true');
			});
		});

		root.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape') return;
			clearMobile(root, triggers);
			clearDesktopActive(root);
			syncFlyoutAria(root);
			if (mq.matches && root.contains(document.activeElement)) {
				try {
					document.activeElement.blur();
				} catch (err) {
					/* ignore */
				}
			}
		});

		var resizeTimer;
		window.addEventListener(
			'resize',
			function () {
				clearTimeout(resizeTimer);
				resizeTimer = window.setTimeout(applyMode, 150);
			},
			{ passive: true }
		);

		mq.addEventListener('change', applyMode);
	}

	function boot() {
		document.querySelectorAll('.biopentra-information-mega').forEach(initMega);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	var mo = new MutationObserver(function () {
		document.querySelectorAll('.biopentra-information-mega:not([data-bp-mega-init])').forEach(initMega);
	});
	if (document.body) {
		mo.observe(document.body, { childList: true, subtree: true });
	}
})();
