(function () {
	function toast(message, kind) {
		var node = document.querySelector('.lr-toast');
		if (!node) {
			node = document.createElement('div');
			node.className = 'lr-toast';
			node.setAttribute('role', 'status');
			var app = document.querySelector('.lr-app') || document.body;
			app.insertBefore(node, app.firstChild);
		}
		node.dataset.kind = kind || 'ok';
		node.textContent = message;
		window.setTimeout(function () {
			if (node.parentNode) {
				node.remove();
			}
		}, 4200);
	}

	var existing = document.querySelector('.lr-toast');
	if (existing) {
		window.setTimeout(function () {
			existing.remove();
		}, 4200);
	}

	function post(fields) {
		var body = new window.FormData();
		body.append('nonce', window.lrAdmin ? window.lrAdmin.nonce : '');
		Object.keys(fields).forEach(function (key) {
			body.append(key, fields[key]);
		});
		return window.fetch(window.lrAdmin.ajax, {
			method: 'POST',
			body: body,
			credentials: 'same-origin'
		}).then(function (response) {
			return response.json();
		});
	}

	var drag = null;
	document.addEventListener('dragstart', function (event) {
		var item = event.target.closest('.lr-sort > [data-id]');
		if (!item) {
			return;
		}
		drag = item;
		item.classList.add('is-dragging');
	});
	document.addEventListener('dragend', function () {
		if (drag) {
			drag.classList.remove('is-dragging');
		}
		drag = null;
	});
	document.addEventListener('dragover', function (event) {
		var list = event.target.closest('.lr-sort');
		if (!drag || !list || drag.parentNode !== list) {
			return;
		}
		event.preventDefault();
		var item = event.target.closest('.lr-sort > [data-id]');
		if (!item || item === drag) {
			return;
		}
		var box = item.getBoundingClientRect();
		var after = event.clientY > box.top + box.height / 2;
		list.insertBefore(drag, after ? item.nextSibling : item);
	});
	document.addEventListener('drop', function (event) {
		var list = event.target.closest('.lr-sort');
		if (!drag || !list || !window.lrAdmin) {
			return;
		}
		event.preventDefault();
		var ids = Array.prototype.map.call(list.querySelectorAll(':scope > [data-id]'), function (node) {
			return node.getAttribute('data-id');
		});
		var fields = {
			action: 'lr_academy_sort',
			kind: list.getAttribute('data-kind') || ''
		};
		ids.forEach(function (id, index) {
			fields['ids[' + index + ']'] = id;
		});
		post(fields).then(function (payload) {
			if (payload && payload.success) {
				toast('ترتیب ذخیره شد.');
			}
		});
	});

	document.addEventListener('click', function (event) {
		var fold = event.target.closest('.lr-fold');
		if (fold) {
			var section = fold.closest('.lr-section');
			var list = section ? section.querySelector('.lr-lessons') : null;
			if (list) {
				var open = list.hasAttribute('hidden');
				if (open) {
					list.removeAttribute('hidden');
				} else {
					list.setAttribute('hidden', 'hidden');
				}
				fold.setAttribute('aria-expanded', open ? 'true' : 'false');
			}
		}
		var edit = event.target.closest('[data-act="edit"]');
		if (edit) {
			var row = edit.closest('.lr-row');
			openDrawer(row ? JSON.parse(row.getAttribute('data-lesson') || '{}') : {});
		}
		var add = event.target.closest('[data-act="add-lesson"]');
		if (add) {
			openDrawer({ id: 0, module: add.getAttribute('data-module'), type: 'video', title: '', minutes: 0, preview: 0, provider: 'arvan_vod', external_id: '', content: '' });
		}
		if (event.target.closest('[data-act="close-drawer"]')) {
			closeDrawer();
		}
		var copy = event.target.closest('[data-act="copy"]');
		if (copy) {
			var source = copy.closest('.lr-row');
			post({ action: 'lr_academy_lesson_copy', lesson_id: source.getAttribute('data-id') }).then(function (payload) {
				if (!payload || !payload.success) {
					toast('کپی نشد.', 'err');
					return;
				}
				source.parentNode.appendChild(renderRow(payload.data.lesson));
				refreshCount(source.parentNode);
				toast('درس کپی شد.');
			});
		}
		var remove = event.target.closest('[data-act="delete"]');
		if (remove) {
			var gone = remove.closest('.lr-row');
			if (!window.confirm('این درس حذف شود؟')) {
				return;
			}
			post({ action: 'lr_academy_lesson_remove', lesson_id: gone.getAttribute('data-id') }).then(function (payload) {
				if (!payload || !payload.success) {
					toast('حذف نشد.', 'err');
					return;
				}
				var parent = gone.parentNode;
				gone.remove();
				refreshCount(parent);
				toast('درس حذف شد.');
			});
		}
	});

	var form = document.querySelector('.lr-drawer form');
	var videoState = { label: 'آماده', key: 'ok', id: '' };
	if (form) {
		form.addEventListener('input', function (event) {
			var target = event.target;
			if (!target || !target.name) {
				return;
			}
			if (target.name === 'title') {
				var heading = document.querySelector('.lr-drawer-head h2');
				if (heading) {
					heading.textContent = target.value || 'درس';
				}
			}
			if (target.name === 'title' || target.name === 'type' || target.name === 'external_id') {
				paintVideo();
			}
		});
		form.addEventListener('submit', function (event) {
			event.preventDefault();
			var data = new window.FormData(form);
			var fields = {};
			data.forEach(function (value, key) {
				fields[key] = value;
			});
			fields.action = 'lr_academy_lesson';
			post(fields).then(function (payload) {
				if (!payload || !payload.success) {
					toast((payload && payload.data && payload.data.message) || 'ذخیره نشد.', 'err');
					return;
				}
				var lesson = payload.data.lesson;
				var list = document.querySelector('.lr-lessons[data-module="' + lesson.module + '"]');
				var current = list ? list.querySelector('.lr-row[data-id="' + lesson.id + '"]') : null;
				if (current) {
					current.replaceWith(renderRow(lesson));
				} else if (list) {
					list.removeAttribute('hidden');
					list.appendChild(renderRow(lesson));
					refreshCount(list);
				}
				closeDrawer();
				toast('درس ذخیره شد.');
			});
		});
	}

	document.querySelectorAll('.lr-outline form').forEach(function (node) {
		node.addEventListener('submit', function (event) {
			event.preventDefault();
			var input = node.querySelector('input[name="title"]');
			var holder = node.querySelector('[data-course]');
			var course = holder ? holder.getAttribute('data-course') : '';
			post({
				action: 'lr_academy_module',
				course_id: course,
				title: input ? input.value : ''
			}).then(function (payload) {
				if (!payload || !payload.success) {
					toast('فصل اضافه نشد.', 'err');
					return;
				}
				var tree = document.querySelector('.lr-outline > .lr-sort');
				if (tree) {
					tree.appendChild(renderSection(payload.data.module));
				}
				if (input) {
					input.value = '';
				}
				toast('فصل اضافه شد.');
			});
		});
	});

	function openDrawer(lesson) {
		var drawer = document.querySelector('.lr-drawer');
		var back = document.querySelector('.lr-drawer-back');
		if (!drawer || !form) {
			return;
		}
		setField('lesson_id', lesson.id || 0);
		setField('module_id', lesson.module || 0);
		setField('title', lesson.title || '');
		setField('type', lesson.type || 'video');
		setField('provider', lesson.provider || 'arvan_vod');
		setField('external_id', lesson.external_id || '');
		setField('file_url', lesson.file_url || '');
		setField('duration', lesson.minutes || 0);
		setField('content', lesson.content || '');
		var preview = form.elements.namedItem('is_preview');
		if (preview) {
			preview.checked = String(lesson.preview) === '1';
		}
		videoState = {
			label: lesson.badge || 'آماده',
			key: lesson.badge_key || 'ok',
			id: String(lesson.external_id || '')
		};
		var heading = drawer.querySelector('.lr-drawer-head h2');
		if (heading) {
			heading.textContent = lesson.title || 'درس';
		}
		paintVideo();
		drawer.hidden = false;
		if (back) {
			back.hidden = false;
		}
	}

	function closeDrawer() {
		var drawer = document.querySelector('.lr-drawer');
		var back = document.querySelector('.lr-drawer-back');
		if (drawer) {
			drawer.hidden = true;
		}
		if (back) {
			back.hidden = true;
		}
	}

	function setField(name, value) {
		var input = form.elements.namedItem(name);
		if (input) {
			input.value = value;
		}
	}

	function faNum(value) {
		return String(value).replace(/\d/g, function (digit) {
			return '۰۱۲۳۴۵۶۷۸۹'[digit];
		});
	}

	function paintVideo() {
		var box = form ? form.querySelector('.lr-video-box') : null;
		if (!box) {
			return;
		}
		var type = form.elements.namedItem('type');
		var external = form.elements.namedItem('external_id');
		var id = external ? String(external.value || '').trim() : '';
		var isVideo = type && type.value === 'video' && id !== '';
		if (!isVideo) {
			box.hidden = true;
			return;
		}
		box.hidden = false;
		var badge = box.querySelector('[data-role="video-badge"]');
		var idNode = box.querySelector('[data-role="video-id"]');
		var same = id === videoState.id && videoState.id !== '';
		if (badge) {
			badge.textContent = same ? (videoState.label || 'آماده') : 'آماده';
			badge.className = 'lr-pill lr-pill-' + (same ? (videoState.key || 'ok') : 'ok');
		}
		if (idNode) {
			idNode.textContent = id;
		}
	}

	function renderSection(module) {
		var item = document.createElement('li');
		item.className = 'lr-section';
		item.setAttribute('data-id', String(module.id));
		item.setAttribute('draggable', 'true');
		item.innerHTML = '<header class="lr-section-head"><button type="button" class="lr-handle" aria-label="جابه‌جایی">⋮⋮</button><button type="button" class="lr-fold" aria-expanded="true"></button><span class="lr-meta"><span class="lr-lesson-count lr-count">۰ درس</span><span class="lr-dur">۰ دقیقه</span></span></header><ul class="lr-lessons lr-sort" data-kind="lessons"></ul><button type="button" class="button lr-add" data-act="add-lesson">+ درس</button>';
		item.querySelector('.lr-fold').textContent = module.title;
		item.querySelector('.lr-lessons').setAttribute('data-module', String(module.id));
		item.querySelector('[data-act="add-lesson"]').setAttribute('data-module', String(module.id));
		return item;
	}

	function renderRow(lesson) {
		var row = document.createElement('li');
		row.className = 'lr-row';
		row.setAttribute('data-id', String(lesson.id));
		row.setAttribute('data-lesson', JSON.stringify(lesson));
		row.setAttribute('draggable', 'true');
		row.innerHTML = '<button type="button" class="lr-handle" aria-label="جابه‌جایی">⋮⋮</button><span class="lr-type"></span><span class="lr-row-title"></span><span class="lr-row-end"><span class="lr-row-dur"></span><span class="lr-video-badge lr-pill"></span><span class="lr-preview-badge lr-pill lr-pill-ready">پیش‌نمایش</span><button type="button" class="lr-icon" data-act="edit" aria-label="ویرایش">✎</button><button type="button" class="lr-icon" data-act="copy" aria-label="کپی">⧉</button><button type="button" class="lr-icon" data-act="delete" aria-label="حذف">✕</button></span>';
		row.querySelector('.lr-row-title').textContent = lesson.title;
		row.querySelector('.lr-row-dur').textContent = lesson.duration_label;
		var type = row.querySelector('.lr-type');
		type.textContent = lesson.type_icon || '•';
		type.setAttribute('title', lesson.type_label || '');
		var badge = row.querySelector('.lr-video-badge');
		badge.textContent = lesson.badge;
		badge.className = 'lr-pill lr-pill-' + lesson.badge_key + ' lr-video-badge';
		var preview = row.querySelector('.lr-preview-badge');
		if (String(lesson.preview) === '1') {
			preview.hidden = false;
		} else {
			preview.hidden = true;
		}
		return row;
	}

	function refreshCount(list) {
		var section = list.closest('.lr-section');
		if (!section) {
			return;
		}
		var rows = list.querySelectorAll('.lr-row');
		var node = section.querySelector('.lr-lesson-count');
		if (node) {
			node.textContent = faNum(rows.length) + ' درس';
		}
		var minutes = 0;
		Array.prototype.forEach.call(rows, function (row) {
			try {
				var data = JSON.parse(row.getAttribute('data-lesson') || '{}');
				minutes += parseInt(data.minutes, 10) || 0;
			} catch (error) {
				minutes += 0;
			}
		});
		var duration = section.querySelector('.lr-dur');
		if (duration) {
			duration.textContent = faNum(minutes) + ' دقیقه';
		}
	}
})();
