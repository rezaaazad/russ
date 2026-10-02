(function () {
	if (typeof Chart === "undefined") {
		return;
	}
	document.querySelectorAll("canvas[data-lr-chart]").forEach(function (node) {
		var raw = node.getAttribute("data-lr-chart");
		if (!raw) {
			return;
		}
		var spec;
		try {
			spec = JSON.parse(raw);
		} catch (error) {
			return;
		}
		new Chart(node, spec);
	});
})();
