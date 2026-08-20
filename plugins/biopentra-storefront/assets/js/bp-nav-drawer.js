/**
 * M1 — Premium Ecommerce header / left nav drawer (Approach A: in-place).
 *
 * Transforms Elementor's existing mobile menu wrapper into a fixed left drawer.
 * Does not reparent DOM. Does not set `display` on `.e-n-menu-toggle`.
 *
 * Hotfix: header stacking context elevation while open (backdrop no longer
 * steals clicks); leaf links navigate; Information expands in-drawer content.
 *
 * Click model: stopPropagation on the wrapper in the *bubble* phase so target
 * handlers (nav links, Information accordion) still run, while Elementor's
 * document-level outside-close does not see the event.
 */
(function () {
	'use strict';

	var MQ = '(max-width: 1024px)';
	var OPEN_CLASS = 'bp-nav-drawer-open';
	var BACKDROP_ID = 'bp-nav-drawer-backdrop';
	var CLOSE_ID = 'bp-nav-drawer-close';
	var lastFocus = null;
	var observer = null;

	function mqMobile() {
		return window.matchMedia(MQ).matches;
	}

	function headerRoot() {
		return document.querySelector('.elementor-location-header');
	}

	function toggleEl() {
		var root = headerRoot();
		return root ? root.querySelector('.e-n-menu-toggle') : null;
	}

	function wrapperEl() {
		var root = headerRoot();
		return root ? root.querySelector('.e-n-menu-wrapper') : null;
	}

	function isExpanded(toggle) {
		return toggle && toggle.getAttribute('aria-expanded') === 'true';
	}

	function ensureBackdrop() {
		var el = document.getElementById(BACKDROP_ID);
		if (el) {
			return el;
		}
		el = document.createElement('div');
		el.id = BACKDROP_ID;
		el.className = 'bp-nav-drawer-backdrop';
		el.setAttribute('hidden', '');
		el.setAttribute('aria-hidden', 'true');
		el.addEventListener('click', function () {
			closeDrawer();
		});
		document.body.appendChild(el);
		return el;
	}

	function ensureCloseButton(wrapper) {
		if (!wrapper || wrapper.querySelector('#' + CLOSE_ID)) {
			return;
		}
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.id = CLOSE_ID;
		btn.className = 'bp-nav-drawer-close';
		btn.setAttribute('aria-label', 'Close menu');
		btn.innerHTML =
			'<svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18.3 5.7a1 1 0 0 0-1.4-1.4L12 9.17 7.1 4.3A1 1 0 0 0 5.7 5.7L10.59 10.6 5.7 15.49a1 1 0 1 0 1.4 1.42L12 12l4.9 4.9a1 1 0 0 0 1.4-1.4L13.41 10.6z"/></svg>';
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			closeDrawer();
		});
		wrapper.insertBefore(btn, wrapper.firstChild);
	}

	function ensureAccountLink(wrapper) {
		if (!wrapper || wrapper.querySelector('[data-bp-drawer-account]')) {
			return;
		}
		var auth = document.querySelector(
			'.elementor-location-header .biopentra-header-auth__trigger'
		);
		if (!auth) {
			return;
		}
		var href = auth.getAttribute('href') || auth.dataset.href || '';
		if (!href && auth.tagName === 'BUTTON') {
			var nest = auth.closest('.biopentra-header-auth');
			var link = nest ? nest.querySelector('a[href]') : null;
			href = link ? link.getAttribute('href') : '';
		}
		if (!href) {
			href = '/my-account/';
		}
		var label = (auth.textContent || 'Account').replace(/\s+/g, ' ').trim() || 'Account';
		var row = document.createElement('div');
		row.className = 'bp-nav-drawer-account';
		row.setAttribute('data-bp-drawer-account', '1');
		var a = document.createElement('a');
		a.href = href;
		a.className = 'bp-nav-drawer-account__link';
		a.textContent = label;
		row.appendChild(a);
		wrapper.appendChild(row);
	}

	function focusable(container) {
		if (!container) {
			return [];
		}
		return Array.prototype.slice
			.call(
				container.querySelectorAll(
					'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
				)
			)
			.filter(function (el) {
				return el.offsetParent !== null || el === document.activeElement;
			});
	}

	function setOpen(open) {
		var html = document.documentElement;
		var backdrop = ensureBackdrop();
		var wrapper = wrapperEl();
		var toggle = toggleEl();

		if (open && mqMobile()) {
			if (!html.classList.contains(OPEN_CLASS)) {
				lastFocus = document.activeElement;
			}
			html.classList.add(OPEN_CLASS);
			backdrop.removeAttribute('hidden');
			backdrop.setAttribute('aria-hidden', 'false');
			if (wrapper) {
				ensureCloseButton(wrapper);
				ensureAccountLink(wrapper);
				wrapper.setAttribute('role', 'dialog');
				wrapper.setAttribute('aria-modal', 'true');
				wrapper.setAttribute('aria-label', 'Site navigation');
				var closeBtn = wrapper.querySelector('#' + CLOSE_ID);
				window.setTimeout(function () {
					if (closeBtn) {
						closeBtn.focus();
					}
				}, 10);
			}
		} else {
			html.classList.remove(OPEN_CLASS);
			backdrop.setAttribute('hidden', '');
			backdrop.setAttribute('aria-hidden', 'true');
			if (wrapper) {
				wrapper.removeAttribute('role');
				wrapper.removeAttribute('aria-modal');
				wrapper.removeAttribute('aria-label');
			}
			if (lastFocus && typeof lastFocus.focus === 'function') {
				lastFocus.focus();
			} else if (toggle) {
				toggle.focus();
			}
			lastFocus = null;
		}
	}

	function closeDrawer() {
		var toggle = toggleEl();
		if (!toggle) {
			setOpen(false);
			return;
		}
		if (isExpanded(toggle)) {
			toggle.click();
		}
		window.setTimeout(function () {
			setOpen(false);
		}, 0);
	}

	function syncFromToggle() {
		var toggle = toggleEl();
		var open = isExpanded(toggle) && mqMobile();
		setOpen(open);
	}

	function titleHasDropdown(title) {
		if (!title) {
			return false;
		}
		if (title.classList.contains('e-click') || title.classList.contains('e-anchor')) {
			return true;
		}
		var item = title.closest('.e-n-menu-item');
		return !!(item && item.querySelector(':scope > .e-n-menu-content'));
	}

	function isLeafNavLink(el) {
		var link = el.closest('a.e-n-menu-title-container, a.e-link, .e-n-menu-title a[href]');
		if (!link) {
			return false;
		}
		var title = link.closest('.e-n-menu-title') || link;
		if (titleHasDropdown(title)) {
			return false;
		}
		var href = link.getAttribute('href') || '';
		if (!href || href === '#' || href.slice(-1) === '#') {
			return false;
		}
		return true;
	}

	function markDropdownContentOpen(title) {
		var item = title ? title.closest('.e-n-menu-item') : null;
		var content = item ? item.querySelector(':scope > .e-n-menu-content') : null;
		if (content) {
			content.setAttribute('aria-hidden', 'false');
			content.classList.add('e-active');
		}
		if (isExpanded(toggleEl())) {
			document.documentElement.classList.add(OPEN_CLASS);
		}
	}

	function onKeydown(e) {
		if (!document.documentElement.classList.contains(OPEN_CLASS)) {
			return;
		}
		if (e.key === 'Escape') {
			e.preventDefault();
			closeDrawer();
			return;
		}
		if (e.key !== 'Tab') {
			return;
		}
		var wrapper = wrapperEl();
		var nodes = focusable(wrapper);
		if (!nodes.length) {
			return;
		}
		var first = nodes[0];
		var last = nodes[nodes.length - 1];
		if (e.shiftKey && document.activeElement === first) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && document.activeElement === last) {
			e.preventDefault();
			first.focus();
		}
	}

	function onCartOpen() {
		if (document.documentElement.classList.contains(OPEN_CLASS)) {
			closeDrawer();
		}
	}

	/**
	 * Bubble-phase: keep in-drawer events from reaching Elementor outside-close
	 * on document, without blocking target-phase handlers (links / accordion).
	 */
	function onWrapperBubble(e) {
		if (!document.documentElement.classList.contains(OPEN_CLASS) || !mqMobile()) {
			return;
		}
		e.stopPropagation();
	}

	function onWrapperClick(e) {
		if (!document.documentElement.classList.contains(OPEN_CLASS) || !mqMobile()) {
			return;
		}
		e.stopPropagation();

		if (e.target.closest('#' + CLOSE_ID)) {
			return;
		}

		var title = e.target.closest('.e-n-menu-title');
		if (title && titleHasDropdown(title)) {
			window.setTimeout(function () {
				markDropdownContentOpen(title);
			}, 0);
			return;
		}

		// Leaf links: allow default navigation; drawer closes with page unload.
		if (isLeafNavLink(e.target) || e.target.closest('[data-bp-drawer-account] a')) {
			return;
		}
	}

	function bindWrapperGuards(wrapper) {
		if (!wrapper || wrapper.getAttribute('data-bp-drawer-guard') === '1') {
			return;
		}
		wrapper.setAttribute('data-bp-drawer-guard', '1');
		wrapper.addEventListener('pointerdown', onWrapperBubble, false);
		wrapper.addEventListener('click', onWrapperClick, false);
	}

	function bind() {
		var toggle = toggleEl();
		var wrapper = wrapperEl();
		if (!toggle || !wrapper) {
			return;
		}

		ensureBackdrop();
		bindWrapperGuards(wrapper);

		if (toggle.getAttribute('data-bp-drawer-bound') !== '1') {
			toggle.setAttribute('data-bp-drawer-bound', '1');
			toggle.addEventListener('click', function () {
				window.setTimeout(syncFromToggle, 0);
			});
		}

		if (observer) {
			observer.disconnect();
		}
		observer = new MutationObserver(syncFromToggle);
		observer.observe(toggle, { attributes: true, attributeFilter: ['aria-expanded', 'class'] });

		document.addEventListener('keydown', onKeydown);

		document.addEventListener('click', function (e) {
			if (e.target.closest('.elementor-menu-cart__toggle_button, .elementor-menu-cart__toggle')) {
				onCartOpen();
			}
		});

		window.matchMedia(MQ).addEventListener('change', function (ev) {
			if (!ev.matches) {
				setOpen(false);
			} else {
				syncFromToggle();
			}
		});

		syncFromToggle();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bind);
	} else {
		bind();
	}
})();
