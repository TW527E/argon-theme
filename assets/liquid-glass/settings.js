(() => {

	const settings = document.querySelector('.liquid-settings');
	if (!settings) return;

	function initialize(scope) {
		for (const field of scope.querySelectorAll('[data-order]')) {
			const value = field.querySelector('[data-order-value]');
			const list = field.querySelector('[data-order-list]');
			value.type = 'hidden';
			list.hidden = false;

			function sync() {
				value.value = [...list.children].flatMap(row => {
					const enabled = row.querySelector('[data-order-enabled]').checked;
					const selected = row.querySelector('[data-order-default]');
					if (selected) selected.disabled = !enabled;
					if (!enabled) return [];
					return [(selected?.checked ? '*' : '') + row.dataset.orderKey];
				}).join(field.dataset.order);
			}

			list.addEventListener('change', sync);
			list.addEventListener('click', event => {
				const button = event.target.closest('[data-order-move]');
				if (!button) return;
				const row = button.closest('[data-order-key]');
				const previous = button.dataset.orderMove === '-1';
				const neighbor = previous ? row.previousElementSibling : row.nextElementSibling;
				if (!neighbor) return;
				if (previous) neighbor.before(row);
				else neighbor.after(row);
				button.focus();
				sync();
			});
			sync();
		}

		for (const button of scope.querySelectorAll('[data-select-image]')) {
			const wrapper = button.closest('.liquid-field');
			const input = wrapper.querySelector('input');
			const preview = wrapper.querySelector('.liquid-qr-preview');
			input.addEventListener('change', () => {
				preview.hidden = !input.value;
				if (input.value) preview.src = input.value;
			});
			let media;
			button.addEventListener('click', () => {
				if (!window.wp?.media) return;
				if (!media) {
					media = wp.media({
						title: button.textContent.trim(),
						library: { type: 'image' },
						multiple: false,
					});
					media.on('open', () => media.content.mode('browse'));
					media.on('select', () => {
						const attachment = media.state().get('selection').first();
						if (!attachment) return;
						input.value = attachment.get('url');
						input.dispatchEvent(new Event('change'));
					});
				}
				media.open();
			});
		}
	}
	initialize(settings);

	const tabs = [...settings.querySelectorAll('[data-settings-tab]')];
	function selectSection(section, focus = false) {
		for (const tab of tabs) {
			if (tab.dataset.settingsTab === section) tab.setAttribute('aria-current', 'page');
			else tab.removeAttribute('aria-current');
		}
		for (const panel of settings.querySelectorAll('[data-settings-panel]')) {
			panel.hidden = panel.dataset.settingsPanel !== section;
			if (!panel.hidden && focus) panel.querySelector('h2').focus();
		}
	}

	if (window.history.pushState) {
		for (const tab of tabs) {
			tab.addEventListener('click', event => {
				if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
				event.preventDefault();
				if (tab.getAttribute('aria-current') === 'page') return;
				history.pushState(history.state, '', tab.href);
				selectSection(tab.dataset.settingsTab, true);
			});
		}

		window.addEventListener('popstate', () => {
			const requested = new URL(location.href).searchParams.get('section');
			selectSection(tabs.some(tab => tab.dataset.settingsTab === requested) ? requested : 'appearance');
		});
	}

	function showResult(text, isError) {
		settings.querySelector('.liquid-setting-message')?.remove();
		const notice = document.createElement('div');
		notice.className = 'liquid-setting-message' + (isError ? ' is-error' : '');
		notice.setAttribute('role', isError ? 'alert' : 'status');
		const paragraph = document.createElement('p');
		paragraph.textContent = text;
		notice.append(paragraph);
		settings.querySelector('.liquid-settings-header').after(notice);
	}

	if (window.fetch) {
		let saving = false;
		settings.addEventListener('submit', async event => {
			const form = event.target;
			if (!form.matches('[data-settings-form]')) return;
			event.preventDefault();
			if (saving) return;

			const panel = form.closest('[data-settings-panel]');
			const section = panel.dataset.settingsPanel;
			const payload = new FormData(form);
			const hadFocus = panel.contains(document.activeElement);
			const buttons = settings.querySelectorAll('button[type="submit"][form]');
			saving = true;
			buttons.forEach(button => { button.disabled = true; });
			form.setAttribute('inert', '');

			try {
				const response = await fetch(form.getAttribute('action'), { method: 'POST', body: payload, credentials: 'same-origin' });
				const result = new DOMParser().parseFromString(await response.text(), 'text/html');
				const message = result.querySelector('.liquid-setting-message');
				const updatedPanel = result.querySelector(`[data-settings-panel="${section}"]`);
				if (!response.ok || !message || !updatedPanel) throw new Error('Missing settings response');

				const isError = message.classList.contains('is-error');
				showResult(message.textContent.trim(), isError);
				if (isError) return;

				const returnFocus = !panel.hidden && hadFocus;
				updatedPanel.hidden = panel.hidden;
				panel.replaceWith(updatedPanel);
				initialize(updatedPanel);
				settings.querySelector('#liquid-settings-export').value = result.querySelector('#liquid-settings-export').value;
				if (returnFocus) updatedPanel.querySelector('button[type="submit"]').focus({ preventScroll: true });
			} catch {
				showResult(settings.dataset.saveError, true);
			} finally {
				form.removeAttribute('inert');
				buttons.forEach(button => { button.disabled = false; });
				saving = false;
			}
		});
	}

	document.querySelector('[data-download-settings]')?.addEventListener('click', () => {
		const json = document.querySelector('#liquid-settings-export').value;
		const url = URL.createObjectURL(new Blob([json], { type: 'application/json' }));
		const link = document.createElement('a');
		link.href = url;
		link.download = 'liquid-glass-settings.json';
		link.click();
		setTimeout(() => URL.revokeObjectURL(url), 1000);
	});
})();
