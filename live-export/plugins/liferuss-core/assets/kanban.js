(function () {
	'use strict';

	var cfg = window.liferussKanban || {};
	var dragging = null;

	document.querySelectorAll('.lr-kanban-card').forEach(function (card) {
		card.addEventListener('dragstart', function (event) {
			dragging = card;
			event.dataTransfer.setData('text/plain', card.getAttribute('data-id') || '');
			event.dataTransfer.effectAllowed = 'move';
			card.classList.add('is-dragging');
		});
		card.addEventListener('dragend', function () {
			card.classList.remove('is-dragging');
			dragging = null;
		});
	});

	document.querySelectorAll('.lr-kanban-col').forEach(function (column) {
		column.addEventListener('dragover', function (event) {
			event.preventDefault();
		});
		column.addEventListener('drop', function (event) {
			event.preventDefault();
			var id = event.dataTransfer.getData('text/plain');
			var card = dragging || document.querySelector('.lr-kanban-card[data-id="' + id + '"]');
			var list = column.querySelector('.lr-kanban-list');
			if (!card || !list || !id) {
				return;
			}
			var previous = card.parentNode;
			list.prepend(card);
			var body = new FormData();
			body.append('action', 'lr_kanban_move');
			body.append('lead_id', id);
			body.append('status', column.getAttribute('data-status') || '');
			body.append('_ajax_nonce', cfg.nonce || '');
			window.fetch(cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
				.then(function (response) { return response.json(); })
				.then(function (payload) {
					if (!payload || !payload.success) {
						if (previous) {
							previous.prepend(card);
						}
					}
				})
				.catch(function () {
					if (previous) {
						previous.prepend(card);
					}
				});
		});
	});
}());
