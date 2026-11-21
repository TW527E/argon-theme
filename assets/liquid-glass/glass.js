(() => {
	// Refraction profile adapted from tw527e.zip/src/scripts/liquid-gl.ts (MIT).
	const vertex = `
		attribute vec2 aPosition;
		void main() { gl_Position = vec4(aPosition, 0.0, 1.0); }
	`;
	const fragment = `
		precision mediump float;
		uniform sampler2D uWallpaper;
		uniform vec2 uPixels;
		uniform vec4 uRegion;
		uniform vec3 uCard;
		uniform vec4 uImage;
		uniform vec4 uMaterial;

		vec2 uv(vec2 point) { return (point - uImage.xy) / uImage.zw; }

		float bend(float t) {
			t = clamp(t, 0.0, 0.999);
			float a = pow(1.0 - t, 3.0);
			float slope = pow(1.0 - t, 2.0) * pow(max(1.0 - a, 0.001), -0.6667);
			float angle = atan(slope);
			return clamp(tan(angle - asin(sin(angle) / 1.5)) / 1.118, 0.0, 1.0);
		}

		void main() {
			vec2 local = vec2(gl_FragCoord.x, uPixels.y - gl_FragCoord.y) / uPixels * uRegion.zw;
			vec2 c = local + uRegion.xy - uCard.xy * 0.5;
			float radius = uCard.z;
			vec2 q = abs(c) - (uCard.xy * 0.5 - radius);
			vec2 o = max(q, 0.0);
			float outside = length(o);
			float depth = radius - outside - min(max(q.x, q.y), 0.0);
			float band = min(6.0, min(uCard.x, uCard.y) * 0.20);
			if (depth <= 0.0 || depth >= band) discard;

			vec2 normal = outside > 0.0 ? o / outside : (q.x > q.y ? vec2(1.0, 0.0) : vec2(0.0, 1.0));
			normal *= sign(c);
			vec2 shift = normal * bend(depth / band) * 8.0;
			vec2 at = local + uRegion.xy;
			vec3 color = vec3(
				texture2D(uWallpaper, uv(at - shift)).r,
				texture2D(uWallpaper, uv(at - shift * 1.04)).g,
				texture2D(uWallpaper, uv(at - shift * 1.08)).b
			);
			color = mix(color, uMaterial.rgb, 0.66);
			color += max(0.0, 1.0 - depth / 2.5) * (0.06 + 0.15 * max(0.0, dot(normal, vec2(-0.55, -0.83))));
			float alpha = smoothstep(0.0, 0.7, depth) * (1.0 - smoothstep(1.0, band, depth));
			gl_FragColor = vec4(clamp(color, 0.0, 1.0), alpha * uMaterial.a);
		}
	`;
	const root = document.documentElement;
	const backdrop = document.querySelector('.liquid-backdrop');
	const button = document.querySelector('[data-liquid-lite]');
	if (!backdrop || !button) return;

	const selector = '.liquid-surface, .liquid-entry, #leftbar_part1, #leftbar_part2';
	const reducedTransparency = matchMedia('(prefers-reduced-transparency: reduce)');
	const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
	const preferenceKey = 'liquid-glass-lite';
	const qualityKey = 'liquid-glass-auto-quality';
	const retryAfter = 30 * 60 * 1000;
	const scrollIdle = 160;
	const sampleSize = 24;

	function read(key) {
		try {
			return localStorage.getItem(key);
		} catch {
			return null;
		}
	}

	function write(key, value) {
		try {
			localStorage.setItem(key, value);
		} catch {}
	}

	const [savedTier, expires] = (read(qualityKey) || '').split(':').map(Number);
	const remembered = expires > Date.now();
	let tier = remembered && (savedTier === 1 || savedTier === 2) ? savedTier : 0;
	let calibrated = remembered && savedTier === 0;
	let canProbe = false;
	let manual = read(preferenceKey) === '1';
	let renderer;
	let unavailable = false;
	let sourceKey = '';
	let ready = false;
	let opacity = 1;
	let raf = 0;
	let lastFrame = 0;
	let lastDraw = 0;
	let scrollUntil = 0;
	let dirty = true;
	let samples = 0;
	let slow = 0;
	let strikes = 0;
	let framePeriod = 1000 / 60;
	let goodWindows = 0;
	let paintIndex = 0;
	let paintTotal = 0;
	let lastPaintTotal = Infinity;
	const panes = new Map();
	const resize = 'ResizeObserver' in window ? new ResizeObserver(invalidate) : null;

	function makeRenderer() {
		const canvas = document.createElement('canvas');
		const gl = canvas.getContext('webgl', {
			alpha: true, antialias: false, premultipliedAlpha: false,
			preserveDrawingBuffer: true, failIfMajorPerformanceCaveat: true,
		});
		if (!gl) return null;

		const program = gl.createProgram();
		for (const [kind, code] of [[gl.VERTEX_SHADER, vertex], [gl.FRAGMENT_SHADER, fragment]]) {
			const shader = gl.createShader(kind);
			gl.shaderSource(shader, code);
			gl.compileShader(shader);
			gl.attachShader(program, shader);
			gl.deleteShader(shader);
		}
		gl.linkProgram(program);
		if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
			gl.deleteProgram(program);
			return null;
		}
		gl.useProgram(program);
		const buffer = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
		const position = gl.getAttribLocation(program, 'aPosition');
		gl.enableVertexAttribArray(position);
		gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);
		const texture = gl.createTexture();
		gl.bindTexture(gl.TEXTURE_2D, texture);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
		gl.enable(gl.SCISSOR_TEST);

		const pixels = gl.getUniformLocation(program, 'uPixels');
		const region = gl.getUniformLocation(program, 'uRegion');
		const card = gl.getUniformLocation(program, 'uCard');
		const image = gl.getUniformLocation(program, 'uImage');
		const material = gl.getUniformLocation(program, 'uMaterial');
		let imageWidth;
		let imageHeight;

		canvas.addEventListener('webglcontextlost', () => {
			if (renderer?.canvas !== canvas) return;
			unavailable = true;
			update();
		});

		return {
			canvas,
			load(img, filter) {
				const source = document.createElement('canvas');
				const maxSize = Math.min(2048, gl.getParameter(gl.MAX_TEXTURE_SIZE));
				const scale = Math.min(1, maxSize / Math.max(img.naturalWidth, img.naturalHeight));
				imageWidth = img.naturalWidth;
				imageHeight = img.naturalHeight;
				source.width = Math.max(1, Math.round(imageWidth * scale));
				source.height = Math.max(1, Math.round(imageHeight * scale));
				const ctx = source.getContext('2d');
				ctx.filter = filter;
				ctx.drawImage(img, 0, 0, source.width, source.height);
				gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, source);
				return gl.getError() === gl.NO_ERROR;
			},
			paint(first, incremental) {
				const start = performance.now();
				const viewport = backdrop.getBoundingClientRect();
				const scale = Math.max(viewport.width / imageWidth, viewport.height / imageHeight);
				const imageW = imageWidth * scale;
				const imageH = imageHeight * scale;
				const imageX = viewport.left + (viewport.width - imageW) / 2;
				const imageY = viewport.top + (viewport.height - imageH) / 2;
				const dpr = Math.min(1.25, devicePixelRatio, 1450 / Math.max(innerWidth, innerHeight));
				const bufferWidth = Math.max(1, Math.ceil(innerWidth * dpr));
				const bufferHeight = Math.max(1, Math.ceil(innerHeight * dpr));
				if (canvas.width !== bufferWidth || canvas.height !== bufferHeight) {
					canvas.width = bufferWidth;
					canvas.height = bufferHeight;
				}
				const entries = Array.from(panes);
				let painted = 0;
				for (let index = first; index < entries.length; index++) {
					const [target, pane] = entries[index];
					const rect = target.getBoundingClientRect();
					const x = Math.max(0, rect.left);
					const y = Math.max(0, rect.top);
					const w = Math.min(innerWidth, rect.right) - x;
					const h = Math.min(innerHeight, rect.bottom) - y;
					if (w <= 0 || h <= 0 || getComputedStyle(target).visibility === 'hidden') {
						pane.hidden = true;
						continue;
					}
					const css = getComputedStyle(target);
					const color = css.backgroundColor.match(/[\d.]+/g)?.map(Number) ?? [];
					const width = Math.max(1, Math.ceil(w * dpr));
					const height = Math.max(1, Math.ceil(h * dpr));
					const radius = Math.min(parseFloat(css.borderTopLeftRadius) || 0, rect.width / 2, rect.height / 2);
					gl.viewport(0, 0, width, height);
					gl.scissor(0, 0, width, height);
					gl.clear(gl.COLOR_BUFFER_BIT);
					gl.uniform2f(pixels, width, height);
					gl.uniform4f(region, x - rect.left, y - rect.top, w, h);
					gl.uniform3f(card, rect.width, rect.height, radius);
					gl.uniform4f(image, imageX - rect.left, imageY - rect.top, imageW, imageH);
					gl.uniform4f(material, (color[0] ?? 240) / 255, (color[1] ?? 225) / 255, (color[2] ?? 216) / 255, opacity * 0.38);
					gl.drawArrays(gl.TRIANGLES, 0, 3);

					pane.hidden = false;
					pane.style.left = (x - rect.left - target.clientLeft) + 'px';
					pane.style.top = (y - rect.top - target.clientTop) + 'px';
					pane.style.width = w + 'px';
					pane.style.height = h + 'px';
					if (pane.width !== width || pane.height !== height) {
						pane.width = width;
						pane.height = height;
					}
					const ctx = pane.getContext('2d');
					ctx.clearRect(0, 0, width, height);
					ctx.drawImage(canvas, 0, bufferHeight - height, width, height, 0, 0, width, height);
					if (incremental && (++painted >= 2 || performance.now() - start >= 5)) {
						return index + 1 < entries.length ? index + 1 : 0;
					}
				}
				return 0;
			},
			destroy() {
				gl.deleteTexture(texture);
				gl.deleteBuffer(buffer);
				gl.deleteProgram(program);
				gl.getExtension('WEBGL_lose_context')?.loseContext();
			},
		};
	}

	function refreshPanes() {
		const targets = new Set([...document.querySelectorAll(selector)].filter(target =>
			!target.closest('.liquid-banner.has-banner-image') &&
			(root.classList.contains('toolbar-blur') || !target.closest('#toolbar'))
		));
		for (const [target, pane] of panes) {
			if (renderer && targets.has(target)) continue;
			resize?.unobserve(target);
			pane.remove();
			panes.delete(target);
		}
		if (!renderer) return;
		for (const target of targets) {
			if (panes.has(target)) continue;
			const pane = document.createElement('canvas');
			pane.className = 'liquid-rim-canvas';
			pane.hidden = true;
			pane.setAttribute('aria-hidden', 'true');
			target.prepend(pane);
			panes.set(target, pane);
			resize?.observe(target);
		}
		invalidate();
	}

	function syncMode() {
		const mode = reducedTransparency.matches ? 'solid' : manual || tier === 2 || !ready ? 'lite'
			: tier === 1 || !calibrated || reducedMotion.matches ? 'rest' : 'full';
		if (root.dataset.liquidGlassMode !== mode) root.dataset.liquidGlassMode = mode;
		button.setAttribute('aria-pressed', String(manual));
	}

	function update() {
		if (manual || reducedTransparency.matches || tier === 2 || unavailable) {
			renderer?.destroy();
			renderer = undefined;
			ready = false;
			sourceKey = '';
			root.classList.toggle('liquid-rim-scrolling', false);
			refreshPanes();
			syncMode();
			return;
		}

		if (!canProbe) {
			syncMode();
			return;
		}

		const css = getComputedStyle(backdrop, '::before');
		const url = css.backgroundImage.match(/^url\("(.*)"\)$/)?.[1];
		opacity = Number(css.opacity);
		const dark = root.classList.contains('darkmode');
		const filter = dark && backdrop.classList.contains('has-dark-wallpaper')
			? (root.classList.contains('amoled-dark') ? 'brightness(.42)' : 'none')
			: css.getPropertyValue('--wallpaper-filter').trim() || 'none';
		const key = url && opacity > 0 ? url + '\n' + filter : '';
		if (key === sourceKey) {
			syncMode();
			invalidate();
			return;
		}
		sourceKey = key;
		ready = false;
		syncMode();
		if (!key) return;

		if (!renderer) {
			renderer = makeRenderer();
			if (!renderer) {
				unavailable = true;
				return;
			}
			refreshPanes();
		}
		const current = renderer;
		const image = new Image();
		image.crossOrigin = 'anonymous';
		image.onload = () => {
			if (renderer !== current || sourceKey !== key) return;
			try {
				ready = current.load(image, filter);
			} catch {
				ready = false;
			}
			syncMode();
			invalidate();
		};
		image.src = url;
	}

	function schedule() {
		if (!raf) raf = requestAnimationFrame(frame);
	}

	function invalidate() {
		dirty = true;
		paintIndex = paintTotal = 0;
		if (ready) schedule();
	}

	function rememberTier() {
		write(qualityKey, tier + ':' + (Date.now() + retryAfter));
	}

	function frame(time) {
		raf = 0;
		if (!ready || document.hidden) {
			lastFrame = 0;
			return;
		}
		const moving = time < scrollUntil;
		const rest = root.dataset.liquidGlassMode === 'rest';
		const hide = moving && rest;
		const wasHidden = root.classList.contains('liquid-rim-scrolling');
		root.classList.toggle('liquid-rim-scrolling', hide);

		let cost = 0;
		if (!hide && (dirty || wasHidden || (moving && time - lastDraw >= 15))) {
			const start = performance.now();
			paintIndex = renderer.paint(paintIndex, !moving);
			cost = performance.now() - start;
			paintTotal += cost;
			lastDraw = time;
			dirty = paintIndex !== 0;
			if (!dirty) {
				lastPaintTotal = paintTotal;
				paintTotal = 0;
			}
		}

		if (moving && lastFrame) {
			const gap = time - lastFrame;
			framePeriod = Math.min(framePeriod, Math.max(8, gap));
			if (gap > Math.max(21, framePeriod * 1.65) || cost > Math.max(9, framePeriod * 0.75)) slow++;
			if (++samples >= sampleSize) {
				const slowShare = slow / samples;
				strikes = slowShare >= 0.35 ? strikes + 1 : 0;
				goodWindows = slowShare <= 0.1 ? goodWindows + 1 : 0;
				samples = slow = 0;
				if (strikes >= 2) {
					tier = rest ? 2 : 1;
					strikes = 0;
					rememberTier();
					update();
				} else if (!calibrated && tier === 0 && goodWindows >= 2 && lastPaintTotal <= 8) {
					calibrated = true;
					rememberTier();
					syncMode();
				}
			}
		}
		lastFrame = moving ? time : 0;
		if ((moving || dirty) && ready) schedule();
	}

	button.hidden = false;
	button.addEventListener('click', () => {
		manual = !manual;
		write(preferenceKey, manual ? '1' : '0');
		if (!manual) {
			tier = 0;
			calibrated = false;
			samples = slow = strikes = goodWindows = 0;
			write(qualityKey, '');
		}
		update();
	});
	window.addEventListener('scroll', () => {
		const now = performance.now();
		if (now - scrollUntil > 1500) samples = slow = strikes = goodWindows = 0;
		scrollUntil = now + scrollIdle;
		invalidate();
	}, { passive: true });
	window.addEventListener('resize', invalidate, { passive: true });
	document.addEventListener('liquid-feed:layout', invalidate);
	document.addEventListener('animationend', event => {
		if (event.target.matches(selector)) invalidate();
	});
	$(document).on('pjax:complete', refreshPanes);
	new MutationObserver(update).observe(root, { attributeFilter: ['class', 'data-liquid-palette'] });
	new MutationObserver(update).observe(backdrop, { attributeFilter: ['class', 'style'] });
	reducedTransparency.addEventListener('change', update);
	reducedMotion.addEventListener('change', update);

	// Keep the first paint on CSS and never compile shaders during the first scroll.
	function queueProbe() {
		const probe = () => {
			if (performance.now() < scrollUntil) {
				setTimeout(queueProbe, scrollIdle);
				return;
			}
			canProbe = true;
			update();
		};
		if ('requestIdleCallback' in window) {
			requestIdleCallback(probe);
		} else {
			requestAnimationFrame(() => setTimeout(probe, 0));
		}
	}
	syncMode();
	queueProbe();
})();
