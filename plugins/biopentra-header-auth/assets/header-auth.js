(function () {
	'use strict';

	function closeAll() {
		document.querySelectorAll('.biopentra-header-auth--in').forEach(function (root) {
			var btn = root.querySelector('.biopentra-header-auth__trigger');
			var menu = root.querySelector('.biopentra-header-auth__dropdown');
			if (!btn || !menu) {
				return;
			}
			menu.setAttribute('hidden', 'hidden');
			menu.classList.remove('biopentra-header-auth__dropdown--open');
			btn.setAttribute('aria-expanded', 'false');
		});
	}

	function toggle(root) {
		var btn = root.querySelector('.biopentra-header-auth__trigger');
		var menu = root.querySelector('.biopentra-header-auth__dropdown');
		if (!btn || !menu) {
			return;
		}
		var open = btn.getAttribute('aria-expanded') === 'true';
		if (open) {
			menu.setAttribute('hidden', 'hidden');
			menu.classList.remove('biopentra-header-auth__dropdown--open');
			btn.setAttribute('aria-expanded', 'false');
		} else {
			document.querySelectorAll('.biopentra-header-auth--in').forEach(function (other) {
				if (other === root) {
					return;
				}
				var b = other.querySelector('.biopentra-header-auth__trigger');
				var m = other.querySelector('.biopentra-header-auth__dropdown');
				if (b && m) {
					m.setAttribute('hidden', 'hidden');
					m.classList.remove('biopentra-header-auth__dropdown--open');
					b.setAttribute('aria-expanded', 'false');
				}
			});
			menu.removeAttribute('hidden');
			menu.classList.add('biopentra-header-auth__dropdown--open');
			btn.setAttribute('aria-expanded', 'true');
		}
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.biopentra-header-auth--in .biopentra-header-auth__trigger');
		if (btn) {
			e.preventDefault();
			var root = btn.closest('.biopentra-header-auth--in');
			if (root) {
				toggle(root);
			}
			return;
		}
		if (!e.target.closest('.biopentra-header-auth--in')) {
			closeAll();
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') {
			return;
		}
		closeAll();
	});
})();
