(function () {
	'use strict';

	var cfg = window.liferussFinder || {};
	var key = 'lr_compare';

	function read() {
		try {
			var parsed = JSON.parse(window.localStorage.getItem(key) || '[]');
			return Array.isArray(parsed) ? parsed.slice(0, 4) : [];
		} catch (error) {
			return [];
		}
	}

	function write(items) {
		window.localStorage.setItem(key, JSON.stringify(items.slice(0, 4)));
		paint();
	}

	function paint() {
		var items = read();
		var bar = document.getElementById('lr-compare-bar');
		var text = document.getElementById('lr-compare-bar-text');
		var open = document.getElementById('lr-compare-open');
		if (!bar || !text || !open) {
			return;
		}
		document.querySelectorAll('.lr-compare-add').forEach(function (button) {
			var slug = button.getAttribute('data-slug') || '';
			var on = items.some(function (item) { return item.slug === slug; });
			button.setAttribute('aria-pressed', on ? 'true' : 'false');
			button.textContent = on ? (cfg.strings && cfg.strings.remove) || 'حذف از مقایسه' : (cfg.strings && cfg.strings.add) || 'مقایسه';
		});
		if (!items.length) {
			bar.hidden = true;
			document.body.classList.remove('has-compare-bar');
			return;
		}
		bar.hidden = false;
		document.body.classList.add('has-compare-bar');
		text.textContent = items.map(function (item) { return item.name; }).join('، ');
		if (items.length < 2) {
			open.setAttribute('aria-disabled', 'true');
			open.setAttribute('href', '#');
			text.textContent += ' — ' + ((cfg.strings && cfg.strings.need) || 'حداقل دو دانشگاه');
			return;
		}
		open.removeAttribute('aria-disabled');
		var base = cfg.compareBase || '/compare/';
		open.setAttribute('href', base + (base.indexOf('?') === -1 ? '?' : '&') + 'u=' + encodeURIComponent(items.map(function (item) { return item.slug; }).join(',')));
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest ? event.target.closest('.lr-compare-add') : null;
		if (!button) {
			return;
		}
		event.preventDefault();
		var slug = button.getAttribute('data-slug') || '';
		var name = button.getAttribute('data-name') || slug;
		if (!slug) {
			return;
		}
		var items = read();
		var index = items.findIndex(function (item) { return item.slug === slug; });
		if (index >= 0) {
			items.splice(index, 1);
			write(items);
			return;
		}
		if (items.length >= 4) {
			var barText = document.getElementById('lr-compare-bar-text');
			if (barText) {
				barText.textContent = (cfg.strings && cfg.strings.full) || 'حداکثر ۴ دانشگاه';
			}
			return;
		}
		items.push({ slug: slug, name: name });
		write(items);
	});

	var root = document.getElementById('lr-compare-root');
	if (root) {
		var slugs = (root.getAttribute('data-slugs') || '').split(',').filter(Boolean);
		var names = (root.getAttribute('data-names') || '').split('،');
		if (slugs.length) {
			write(slugs.map(function (slug, index) {
				return { slug: slug.trim(), name: (names[index] || slug).trim() };
			}));
		}
	} else {
		paint();
	}

	var input = document.getElementById('lr-finder-input');
	var list = document.getElementById('lr-finder-list');
	if (!input || !list || !cfg.suggestUrl) {
		return;
	}

	var active = -1;
	var timer = 0;
	var items = [];

	function closeList() {
		list.hidden = true;
		list.innerHTML = '';
		input.setAttribute('aria-expanded', 'false');
		input.setAttribute('aria-activedescendant', '');
		active = -1;
		items = [];
	}

	function choose(index) {
		active = index;
		Array.prototype.forEach.call(list.children, function (node, i) {
			node.setAttribute('aria-selected', i === index ? 'true' : 'false');
		});
		var current = list.children[index];
		input.setAttribute('aria-activedescendant', current ? current.id : '');
	}

	function render(next) {
		items = next;
		list.innerHTML = '';
		if (!next.length) {
			closeList();
			return;
		}
		next.forEach(function (item, index) {
			var option = document.createElement('li');
			option.id = 'lr-opt-' + index;
			option.setAttribute('role', 'option');
			option.setAttribute('aria-selected', 'false');
			option.textContent = item.title + (item.label ? ' — ' + item.label : '');
			option.addEventListener('mousedown', function (event) {
				event.preventDefault();
				window.location.href = item.url;
			});
			list.appendChild(option);
		});
		list.hidden = false;
		input.setAttribute('aria-expanded', 'true');
		choose(0);
	}

	input.addEventListener('input', function () {
		window.clearTimeout(timer);
		var value = input.value.trim();
		if (value.length < 2) {
			closeList();
			return;
		}
		timer = window.setTimeout(function () {
			window.fetch(cfg.suggestUrl + '?q=' + encodeURIComponent(value), { headers: { Accept: 'application/json' } })
				.then(function (response) { return response.json(); })
				.then(function (payload) { render((payload && payload.items) || []); })
				.catch(function () { closeList(); });
		}, 180);
	});

	input.addEventListener('keydown', function (event) {
		if (list.hidden) {
			return;
		}
		if (event.key === 'ArrowDown') {
			event.preventDefault();
			choose(Math.min(active + 1, items.length - 1));
		} else if (event.key === 'ArrowUp') {
			event.preventDefault();
			choose(Math.max(active - 1, 0));
		} else if (event.key === 'Escape') {
			closeList();
		} else if (event.key === 'Enter' && active >= 0 && items[active]) {
			event.preventDefault();
			window.location.href = items[active].url;
		}
	});

	document.addEventListener('click', function (event) {
		if (!event.target.closest || !event.target.closest('.lr-finder')) {
			closeList();
		}
	});
}());
