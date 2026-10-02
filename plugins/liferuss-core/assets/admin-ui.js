(function () {
	var toast = document.querySelector('.lr-toast');
	if (toast) {
		window.setTimeout(function () {
			toast.remove();
		}, 4200);
	}

	document.querySelectorAll('.lr-sort').forEach(function (list) {
		var drag = null;
		list.addEventListener('dragstart', function (event) {
			drag = event.target.closest('[data-id]');
			if (drag) {
				drag.classList.add('is-dragging');
			}
		});
		list.addEventListener('dragend', function () {
			if (drag) {
				drag.classList.remove('is-dragging');
			}
		});
		list.addEventListener('dragover', function (event) {
			event.preventDefault();
			var item = event.target.closest('[data-id]');
			if (!drag || !item || item === drag || item.parentNode !== list) {
				return;
			}
			var box = item.getBoundingClientRect();
			var after = event.clientY > box.top + box.height / 2;
			list.insertBefore(drag, after ? item.nextSibling : item);
		});
		list.addEventListener('drop', function (event) {
			event.preventDefault();
			if (!window.lrAdmin) {
				return;
			}
			var ids = Array.prototype.map.call(list.querySelectorAll(':scope > [data-id]'), function (node) {
				return node.getAttribute('data-id');
			});
			var body = new window.FormData();
			body.append('action', 'lr_academy_sort');
			body.append('nonce', window.lrAdmin.nonce);
			body.append('kind', list.getAttribute('data-kind') || '');
			ids.forEach(function (id) {
				body.append('ids[]', id);
			});
			window.fetch(window.lrAdmin.ajax, { method: 'POST', body: body, credentials: 'same-origin' });
		});
	});
})();
