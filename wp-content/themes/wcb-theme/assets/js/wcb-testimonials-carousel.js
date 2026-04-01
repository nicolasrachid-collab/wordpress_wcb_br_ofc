/**
 * Carrossel de depoimentos (home).
 */
(function () {
	document.addEventListener('DOMContentLoaded', function () {
		var track = document.getElementById('wcb-testimonials-track');
		var dotsEl = document.getElementById('wcb-testimonials-dots');
		var counter = document.getElementById('wcb-testimonials-counter');
		var btnPrev = document.querySelector('.wcb-testimonials__nav--prev');
		var btnNext = document.querySelector('.wcb-testimonials__nav--next');
		var container = document.querySelector('.wcb-testimonials__track-container');

		if (!track || !container || !dotsEl) return;

		var cards = Array.from(track.querySelectorAll('.wcb-tcard'));
		var total = cards.length;
		var current = 1;
		var GAP = 24;

		cards.forEach(function (_, i) {
			var d = document.createElement('button');
			d.type = 'button';
			d.className = 'wcb-testimonials__dot';
			d.setAttribute('aria-label', 'Depoimento ' + (i + 1));
			d.addEventListener('click', function () {
				goTo(i);
			});
			dotsEl.appendChild(d);
		});

		function goTo(idx) {
			current = (idx + total) % total;
			render();
		}

		function render() {
			var cw = container.offsetWidth;
			if (!cw) {
				setTimeout(render, 50);
				return;
			}
			var cardW = Math.floor((cw - GAP * 2) / 3);
			cards.forEach(function (c) {
				c.style.width = cardW + 'px';
				c.style.minWidth = cardW + 'px';
			});
			var offset = Math.round(cw / 2 - cardW / 2 - current * (cardW + GAP));
			track.style.transform = 'translateX(' + offset + 'px)';
			cards.forEach(function (c, i) {
				c.classList.toggle('wcb-tcard--active', i === current);
			});
			var dots = dotsEl.querySelectorAll('.wcb-testimonials__dot');
			dots.forEach(function (d, i) {
				d.classList.toggle('active', i === current);
			});
			if (counter) counter.textContent = current + 1 + ' de ' + total + ' depoimentos';
		}

		if (btnPrev) btnPrev.addEventListener('click', function () { goTo(current - 1); });
		if (btnNext) btnNext.addEventListener('click', function () { goTo(current + 1); });

		var timer = setInterval(function () { goTo(current + 1); }, 5500);
		container.addEventListener('mouseenter', function () { clearInterval(timer); });
		container.addEventListener('mouseleave', function () {
			timer = setInterval(function () { goTo(current + 1); }, 5500);
		});

		requestAnimationFrame(function () { render(); });
		window.addEventListener('resize', render);
	});
})();
