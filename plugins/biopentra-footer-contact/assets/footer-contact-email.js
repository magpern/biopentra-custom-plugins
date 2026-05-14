(function () {
	function addr() {
		return String.fromCharCode.apply(
			null,
			[105, 110, 102, 111, 64, 98, 105, 111, 112, 101, 110, 116, 114, 97, 46, 101, 117]
		);
	}
	function activate(e) {
		var btn = e.target.closest(".biopentra-footer-email-btn");
		if (!btn) {
			return;
		}
		e.preventDefault();
		window.location.href = "mailto:" + addr();
	}
	document.addEventListener("click", activate);
	document.addEventListener("keydown", function (e) {
		if (e.key !== "Enter" && e.key !== " ") {
			return;
		}
		activate(e);
	});
})();
