/**
 * M6 Ordering Questions — start fully collapsed.
 * Elementor accordion autoExpand activates item 1; undo that for .bp-m6-faq only.
 * Functional only (no decorative motion).
 */
(function ($) {
	var userOpened = false;
	var hooked = false;
	var observer = null;

	function collapseAccordion($scope) {
		if (userOpened) {
			return;
		}
		var $faq = $scope.closest('.bp-m6-faq');
		if (!$faq.length) {
			return;
		}
		var $titles = $scope.find('.elementor-tab-title.elementor-active');
		var $contents = $scope.find('.elementor-tab-content.elementor-active');
		if (!$titles.length && !$contents.length) {
			return;
		}
		$titles
			.removeClass('elementor-active')
			.attr({
				'aria-expanded': 'false',
				'aria-selected': 'false',
				tabindex: '-1',
			});
		$contents
			.removeClass('elementor-active')
			.attr('hidden', 'hidden')
			.stop(true, true)
			.hide();
		$scope.find('.elementor-tab-title').first().attr('tabindex', '0');
	}

	function collapseAll() {
		if (userOpened) {
			return;
		}
		$('.bp-m6-faq .elementor-widget-accordion').each(function () {
			collapseAccordion($(this));
		});
	}

	function markInteracted() {
		userOpened = true;
		if (observer) {
			observer.disconnect();
			observer = null;
		}
		$('.bp-m6-faq').addClass('bp-m6-faq--interacted').removeClass('bp-m6-faq--force-collapsed');
	}

	function registerHook() {
		if (hooked || !window.elementorFrontend || !elementorFrontend.hooks) {
			return !!window.elementorFrontend;
		}
		hooked = true;
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/accordion.default',
			function ($scope) {
				window.setTimeout(function () {
					collapseAccordion($scope);
				}, 0);
			}
		);
		return true;
	}

	function watchUntilCollapsed() {
		var root = document.querySelector('.bp-m6-faq');
		if (!root || typeof MutationObserver === 'undefined') {
			return;
		}
		observer = new MutationObserver(function () {
			if (userOpened) {
				if (observer) {
					observer.disconnect();
					observer = null;
				}
				return;
			}
			if (root.querySelector('.elementor-tab-content.elementor-active')) {
				collapseAll();
			}
		});
		observer.observe(root, {
			attributes: true,
			subtree: true,
			attributeFilter: ['class'],
		});
		window.setTimeout(function () {
			if (observer) {
				observer.disconnect();
				observer = null;
			}
		}, 2500);
	}

	// pointerdown fires before Elementor's click activate + MutationObserver race.
	$(document).on(
		'pointerdown keydown',
		'.bp-m6-faq .elementor-tab-title, .bp-m6-faq .elementor-accordion-title',
		function (e) {
			if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') {
				return;
			}
			markInteracted();
		}
	);

	$(window).on('elementor/frontend/init', function () {
		registerHook();
		window.setTimeout(collapseAll, 0);
	});

	$(function () {
		registerHook();
		collapseAll();
		watchUntilCollapsed();
		[0, 50, 150, 400].forEach(function (ms) {
			window.setTimeout(collapseAll, ms);
		});
	});
})(window.jQuery);
