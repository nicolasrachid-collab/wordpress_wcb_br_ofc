/**
 * PDP — ordenação / filtro de avaliações + voto "Útil"
 */
(function () {
	'use strict';

	var root = document.getElementById('wcb-pdp-tab-reviews');
	if (!root) return;

	var listWrap = root.querySelector('.wcb-pdp-reviews-list');
	if (!listWrap) return;

	var commentList = listWrap.querySelector('.commentlist');
	if (!commentList) return;

	var sortSel = document.getElementById('wcb-pdp-reviews-sort');
	var filterSel = document.getElementById('wcb-pdp-reviews-filter');

	function getItems() {
		return Array.prototype.slice.call(commentList.querySelectorAll('li.review'));
	}

	function applyFilter() {
		var stars = filterSel ? parseInt(filterSel.value, 10) || 0 : 0;
		getItems().forEach(function (li) {
			var r = parseInt(li.getAttribute('data-wcb-rating'), 10) || 0;
			var show = stars === 0 || r === stars;
			li.classList.toggle('wcb-review--filtered-out', !show);
			li.hidden = !show;
		});
	}

	function applySort() {
		var mode = sortSel ? sortSel.value : 'recent';
		var items = getItems().filter(function (li) {
			return !li.hidden;
		});
		var hidden = getItems().filter(function (li) {
			return li.hidden;
		});

		items.sort(function (a, b) {
			var ra = parseInt(a.getAttribute('data-wcb-rating'), 10) || 0;
			var rb = parseInt(b.getAttribute('data-wcb-rating'), 10) || 0;
			var ha = parseInt(a.getAttribute('data-wcb-helpful'), 10) || 0;
			var hb = parseInt(b.getAttribute('data-wcb-helpful'), 10) || 0;
			var ta = parseInt(a.getAttribute('data-wcb-ts'), 10) || 0;
			var tb = parseInt(b.getAttribute('data-wcb-ts'), 10) || 0;

			if (mode === 'rating-high') {
				if (rb !== ra) return rb - ra;
				return tb - ta;
			}
			if (mode === 'rating-low') {
				if (ra !== rb) return ra - rb;
				return tb - ta;
			}
			if (mode === 'helpful') {
				if (hb !== ha) return hb - ha;
				return tb - ta;
			}
			/* recent */
			return tb - ta;
		});

		items.forEach(function (li) {
			commentList.appendChild(li);
		});
		hidden.forEach(function (li) {
			commentList.appendChild(li);
		});
	}

	function refreshSortFilter() {
		applyFilter();
		applySort();
	}

	if (sortSel) sortSel.addEventListener('change', refreshSortFilter);
	if (filterSel) filterSel.addEventListener('change', refreshSortFilter);

	/* Voto útil */
	root.addEventListener('click', function (e) {
		var btn = e.target.closest('.wcb-pdp-review-helpful__btn');
		if (!btn || root !== btn.closest('#wcb-pdp-tab-reviews')) return;
		if (btn.disabled || btn.classList.contains('is-voted')) return;

		var wrap = btn.closest('.wcb-pdp-review-helpful');
		var cid = wrap && wrap.getAttribute('data-comment-id');
		var nonce = btn.getAttribute('data-nonce');
		if (!cid || !nonce || !window.wcbPdpReviews || !window.wcbPdpReviews.ajaxUrl) return;

		btn.disabled = true;

		var body = new URLSearchParams();
		body.set('action', 'wcb_review_helpful');
		body.set('nonce', nonce);
		body.set('comment_id', cid);

		fetch(window.wcbPdpReviews.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: body.toString(),
		})
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				if (!data || !data.success) {
					btn.disabled = false;
					var msg =
						data && data.data && data.data.message
							? data.data.message
							: window.wcbPdpReviews.i18n.error;
					if (typeof window.wcbToast === 'function') {
						window.wcbToast(msg);
					} else {
						window.alert(msg);
					}
					return;
				}
				var count = data.data && typeof data.data.count !== 'undefined' ? data.data.count : 0;
				var countEl = btn.querySelector('.wcb-pdp-review-helpful__count');
				if (countEl) countEl.textContent = String(count);
				btn.classList.add('is-voted');
				btn.setAttribute('aria-pressed', 'true');
				var li = wrap.closest('li.review');
				if (li) li.setAttribute('data-wcb-helpful', String(count));
				if (sortSel && sortSel.value === 'helpful') applySort();
			})
			.catch(function () {
				btn.disabled = false;
			});
	});

	/* Estado inicial */
	refreshSortFilter();
})();
