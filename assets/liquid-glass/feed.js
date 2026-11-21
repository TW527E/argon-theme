(() => {
	if (!('ResizeObserver' in window)) return;

	let feed;
	let cards = [];
	let raf = 0;
	const observer = new ResizeObserver(schedule);

	function schedule() {
		if (!raf) raf = requestAnimationFrame(layout);
	}

	function mount() {
		const next = document.querySelector('.article-list:not([data-columns="1"]) .liquid-feed-items');
		if (next !== feed) {
			observer.disconnect();
			feed = next;
			cards = feed ? [...feed.children] : [];
			if (feed) observer.observe(feed);
			for (const card of cards) observer.observe(card);
		}
		schedule();
	}

	function assignColumns(heights, count, gap) {
		const bottoms = Array(count).fill(0);
		const assignments = [];
		const tailStart = Math.max(count, heights.length - 2 * count);
		let prefix;
		for (let index = 0; index < heights.length; index++) {
			if (index === tailStart) prefix = [...bottoms];
			const column = bottoms.indexOf(Math.min(...bottoms));
			assignments.push(column);
			bottoms[column] += heights[index] + gap;
		}

		let bestSpread = Math.max(...bottoms) - Math.min(...bottoms);
		if (!prefix || bestSpread <= 3 * gap) return assignments;

		// Keep the opening cards in reading order and only rebalance near pagination.
		const trial = [...assignments];
		let best = assignments;
		let bestMoves = 0;
		function visit(index, moves) {
			if (index === heights.length) {
				const spread = Math.max(...prefix) - Math.min(...prefix);
				if (spread < bestSpread || (spread === bestSpread && moves < bestMoves)) {
					bestSpread = spread;
					bestMoves = moves;
					best = [...trial];
				}
				return;
			}

			const height = heights[index] + gap;
			for (let column = 0; column < count; column++) {
				trial[index] = column;
				prefix[column] += height;
				visit(index + 1, moves + Number(column !== assignments[index]));
				prefix[column] -= height;
			}
		}
		visit(tailStart, 0);
		return best;
	}

	function layout() {
		raf = 0;
		if (!feed) return;

		const css = getComputedStyle(feed);
		const available = Number(css.getPropertyValue('--liquid-column-count')) || 1;
		const columns = Math.min(available, cards.length);
		if (columns < 2) {
			feed.classList.remove('is-packed');
			return;
		}

		const gap = parseFloat(css.columnGap);
		const width = (feed.getBoundingClientRect().width - gap * (columns - 1)) / columns;
		if (width <= 0) return;

		const tileWidth = width + 'px';
		const changedWidth = feed.style.getPropertyValue('--liquid-tile-width') !== tileWidth;
		if (changedWidth) feed.style.setProperty('--liquid-tile-width', tileWidth);
		feed.classList.add('is-packed');

		const heights = cards.map(card => card.offsetHeight);
		const assignments = assignColumns(heights, columns, gap);
		const bottoms = Array(columns).fill(0);
		let moved = changedWidth;
		cards.forEach((card, index) => {
			const column = assignments[index];
			const top = bottoms[column] + 'px';
			const start = column * (width + gap) + 'px';
			if (card.style.getPropertyValue('--liquid-tile-top') !== top ||
				card.style.getPropertyValue('--liquid-tile-start') !== start) {
				card.style.setProperty('--liquid-tile-top', top);
				card.style.setProperty('--liquid-tile-start', start);
				moved = true;
			}
			bottoms[column] += heights[index] + gap;
		});
		const height = Math.max(...bottoms) - gap + 'px';
		if (feed.style.getPropertyValue('--liquid-packed-height') !== height) {
			feed.style.setProperty('--liquid-packed-height', height);
			moved = true;
		}
		if (moved) document.dispatchEvent(new Event('liquid-feed:layout'));
	}

	$(document).on('pjax:end', mount);
	mount();
})();
