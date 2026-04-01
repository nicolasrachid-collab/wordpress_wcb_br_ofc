/**
 * Cursor custom elegante: anel com inércia + ponto central.
 * Desativado em touch, prefers-reduced-motion e sobre campos de texto.
 */
(function () {
	'use strict';

	if (typeof window.matchMedia !== 'function') {
		return;
	}

	if (!window.matchMedia('(pointer: fine)').matches) {
		return;
	}

	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		return;
	}

	var body = document.body;
	var textSelector =
		'input[type="text"], input[type="email"], input[type="search"], input[type="password"], input[type="url"], input[type="tel"], input[type="number"], input:not([type]), textarea, [contenteditable="true"]';

	var interactiveSelector =
		'a[href], button, input[type="submit"], input[type="button"], label[for], select, summary, [role="button"], .wp-block-button__link, .wp-element-button, .wc-block-components-button';

	function el(tag, cls) {
		var n = document.createElement(tag);
		n.className = cls;
		n.setAttribute('aria-hidden', 'true');
		return n;
	}

	var ring = el('div', 'wcb-cursor-ring');
	var dot = el('div', 'wcb-cursor-dot');
	body.appendChild(ring);
	body.appendChild(dot);
	body.classList.add('wcb-custom-cursor--active');

	var mx = window.innerWidth * 0.5;
	var my = window.innerHeight * 0.5;
	var rx = mx;
	var ry = my;

	function setTranslate(node, x, y) {
		node.style.transform = 'translate3d(' + x + 'px,' + y + 'px,0)';
	}

	setTranslate(dot, mx, my);
	setTranslate(ring, rx, ry);

	function isTextTarget(t) {
		return t && t.closest && t.closest(textSelector);
	}

	function isInteractiveTarget(t) {
		return t && t.closest && t.closest(interactiveSelector);
	}

	document.addEventListener(
		'mousemove',
		function (e) {
			mx = e.clientX;
			my = e.clientY;
			setTranslate(dot, mx, my);

			var t = e.target;
			if (t.closest && t.closest('#wpadminbar')) {
				body.classList.add('wcb-custom-cursor--chrome');
			} else {
				body.classList.remove('wcb-custom-cursor--chrome');
			}

			if (t.closest && t.closest('.wcb-no-custom-cursor')) {
				body.classList.add('wcb-custom-cursor--no-cursor-ui');
			} else {
				body.classList.remove('wcb-custom-cursor--no-cursor-ui');
			}

			if (isTextTarget(t)) {
				body.classList.add('wcb-custom-cursor--input');
			} else {
				body.classList.remove('wcb-custom-cursor--input');
			}

			if (isInteractiveTarget(t)) {
				ring.classList.add('wcb-cursor-ring--hover');
				dot.classList.add('wcb-cursor-dot--hover');
			} else {
				ring.classList.remove('wcb-cursor-ring--hover');
				dot.classList.remove('wcb-cursor-dot--hover');
			}
		},
		{ passive: true }
	);

	document.addEventListener('mousedown', function () {
		dot.classList.add('wcb-cursor-dot--press');
	});
	document.addEventListener('mouseup', function () {
		dot.classList.remove('wcb-cursor-dot--press');
	});

	document.documentElement.addEventListener('mouseleave', function () {
		body.classList.add('wcb-custom-cursor--hidden');
	});
	document.documentElement.addEventListener('mouseenter', function () {
		body.classList.remove('wcb-custom-cursor--hidden');
	});

	function tick() {
		rx += (mx - rx) * 0.16;
		ry += (my - ry) * 0.16;
		setTranslate(ring, rx, ry);
		requestAnimationFrame(tick);
	}

	requestAnimationFrame(tick);
})();
