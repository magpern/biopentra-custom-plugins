(function ($) {
	'use strict';

	var cfg = window.biopentraShopLoopFilter || {};
	var loopWidgetId = cfg.loopWidgetId || 'ed52b7f';
	var patched = false;

	function t(key) {
		return (cfg.i18n && cfg.i18n[key]) || key;
	}

	function getLoopWidget() {
		return document.querySelector('.elementor-element-' + loopWidgetId);
	}

	function triggerWooRefresh() {
		if (!$ || !$.fn || !$.fn.trigger) {
			return;
		}
		$(document.body).trigger('wc_fragments_refreshed');
		$(document.body).trigger('updated_wc_div');
	}

	function reinitLoopCards(root) {
		var scope = root || getLoopWidget() || document;
		if (typeof window.biopentraLoopCardInit === 'function') {
			window.biopentraLoopCardInit(scope);
		}
		triggerWooRefresh();
		$(document).trigger('biopentra-loop-cards-init', [scope]);
	}

	function showLoadError(widget) {
		if (!widget || widget.querySelector('.biopentra-shop-loop-error')) {
			return;
		}
		var el = document.createElement('p');
		el.className = 'biopentra-shop-loop-error';
		el.setAttribute('role', 'alert');
		el.textContent = t('loadError');
		var container = widget.querySelector('.elementor-widget-container');
		if (container) {
			container.prepend(el);
		}
	}

	function patchElementorLoopRefresh() {
		if (
			patched ||
			!window.elementorProFrontend ||
			!elementorProFrontend.modules ||
			!elementorProFrontend.modules.taxonomyFilter
		) {
			return;
		}

		var mod = elementorProFrontend.modules.taxonomyFilter;
		var original = mod.refreshLoopWidget.bind(mod);

		mod.refreshLoopWidget = function (widgetId, filterId) {
			var result = original(widgetId, filterId);
			if (!result || typeof result.then !== 'function') {
				return result;
			}
			return result
				.then(function (response) {
					if (widgetId === loopWidgetId) {
						var widget = getLoopWidget();
						if (widget) {
							var err = widget.querySelector('.biopentra-shop-loop-error');
							if (err) {
								err.remove();
							}
							if (!response || typeof response.data !== 'string') {
								showLoadError(widget);
							} else {
								reinitLoopCards(widget);
							}
						}
					}
					return response;
				})
				.catch(function () {
					if (widgetId === loopWidgetId) {
						showLoadError(getLoopWidget());
					}
					return {};
				});
		};

		patched = true;
	}

	function bindElementorHooks() {
		if (!window.elementorFrontend || !elementorFrontend.hooks) {
			return;
		}
		var hooks = elementorFrontend.hooks;
		['frontend/element_ready/loop-grid', 'frontend/element_ready/loop-grid.product'].forEach(
			function (hook) {
				hooks.addAction(hook, function ($scope) {
					var el = $scope && $scope[0];
					if (!el || !el.classList.contains('elementor-element-' + loopWidgetId)) {
						return;
					}
					reinitLoopCards(el);
				});
			}
		);
	}

	$(window).on('elementor/frontend/init', function () {
		patchElementorLoopRefresh();
		bindElementorHooks();
	});

	if (window.elementorFrontend && elementorFrontend.hooks) {
		patchElementorLoopRefresh();
		bindElementorHooks();
	}
})(window.jQuery);
