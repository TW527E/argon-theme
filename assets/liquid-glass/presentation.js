(() => {
	const root = document.documentElement;
	const themeButton = document.querySelector('.liquid-theme-toggle');
	const paletteButton = document.querySelector('.liquid-palette-toggle');
	const menuButton = document.querySelector('.liquid-menu-toggle');
	const navigation = document.querySelector('#liquid-navigation');

	function observeToolbar() {
		const toolbar = document.querySelector('#toolbar');
		const adminBar = document.querySelector('#wpadminbar');

		function syncToolbar() {
			if (toolbar) root.style.setProperty('--liquid-toolbar-height', toolbar.offsetHeight + 'px');
			if (!adminBar && !document.body.classList.contains('admin-bar')) return;

			let reserve = 0;
			if (adminBar) {
				const style = getComputedStyle(adminBar);
				const rect = adminBar.getBoundingClientRect();
				const top = style.position === 'fixed' ? rect.top : rect.top + scrollY;
				const spansTop = top <= 1 && rect.left <= 1 && rect.right >= innerWidth - 1;
				if (spansTop && style.visibility === 'visible' && Number(style.opacity) > 0) {
					reserve = Math.max(0, Math.min(rect.height, top + rect.height));
				}
			}
			root.style.setProperty('--liquid-adminbar-reserve', reserve + 'px');
			root.classList.toggle('liquid-adminbar-managed', true);
		}

		syncToolbar();
		if ('ResizeObserver' in window && toolbar) {
			const observer = new ResizeObserver(syncToolbar);
			observer.observe(toolbar);
			if (adminBar) observer.observe(adminBar);
		}
		if (adminBar) {
			new MutationObserver(syncToolbar).observe(adminBar, { attributeFilter: ['style', 'class', 'hidden'] });
			adminBar.addEventListener('transitionend', syncToolbar);
			window.addEventListener('load', syncToolbar, { once: true });
		}
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', observeToolbar, { once: true });
	} else {
		observeToolbar();
	}

	function updateControls() {
		themeButton.setAttribute('aria-pressed', root.classList.contains('darkmode'));
		paletteButton.setAttribute('aria-pressed', root.dataset.liquidPalette === 'pink');
		document.querySelector('#open_sidebar')?.setAttribute('aria-expanded', root.classList.contains('leftbar-opened'));
	}

	function setPalette(palette) {
		root.dataset.liquidPalette = palette;
		try {
			localStorage.setItem('liquid-glass-palette', palette);
		} catch {}
	}

	themeButton.addEventListener('click', () => toggleDarkmode());
	paletteButton.addEventListener('click', () => setPalette(root.dataset.liquidPalette === 'pink' ? 'warm' : 'pink'));
	document.addEventListener('liquid-glass:custom-color', () => setPalette('warm'));
	new MutationObserver(updateControls).observe(root, { attributes: true, attributeFilter: ['class', 'data-liquid-palette'] });
	updateControls();

	$('#argon_search_modal').on('shown.bs.modal', (event) => {
		event.currentTarget.querySelector('input[type="search"]').focus();
	}).on('hidden.bs.modal', (event) => {
		const input = event.currentTarget.querySelector('input[type="search"]');
		input.value = new URL(window.location.href).searchParams.get('s') ?? '';
	});
	$(document).on('pjax:send', () => $(navigation).collapse('hide'));
	$(document).on('click', '#open_sidebar', () => $(navigation).collapse('hide'));
	document.addEventListener('keydown', (event) => {
		if (event.key !== 'Escape') return;
		if (document.querySelector('.blog_settings_opened')) {
			document.querySelector('#close_blog_settings').click();
		} else if (root.classList.contains('leftbar-opened')) {
			document.querySelector('#sidebar_mask').click();
			menuButton.focus();
		} else if (menuButton.getAttribute('aria-expanded') === 'true') {
			$(navigation).collapse('hide');
			menuButton.focus();
		}
	});

	if (matchMedia('(hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)').matches) {
		let activeSurface;
		document.addEventListener('pointermove', (event) => {
			if (['lite', 'solid'].includes(root.dataset.liquidGlassMode)) return;
			const surface = event.target.closest('.liquid-surface, .liquid-entry, #leftbar_part1, #leftbar_part2');
			if (activeSurface && surface !== activeSurface) {
				activeSurface.style.removeProperty('--glint-x');
				activeSurface.style.removeProperty('--glint-y');
			}
			if (surface) {
				const box = surface.getBoundingClientRect();
				surface.style.setProperty('--glint-x', (event.clientX - box.left) + 'px');
				surface.style.setProperty('--glint-y', (event.clientY - box.top) + 'px');
			}
			activeSurface = surface;
		}, { passive: true });
	}
})();
