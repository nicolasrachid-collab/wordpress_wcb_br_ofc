/**
 * MH Free Gifts (Blocks): o plugin usa wp.data.select('wc/store'), mas o Woo Blocks
 * expõe o carrinho em wc/store/cart — o subscribe do plugin não dispara renderGifts().
 * Este ficheiro repete o mesmo fetch de painel que blocks.js, ligado ao store certo.
 */
(function () {
	'use strict';

	if (typeof window.mhfgfwcBlocks === 'undefined' || !window.wp || !wp.data || typeof wp.data.subscribe !== 'function') {
		return;
	}

	var cfg = window.mhfgfwcBlocks;
	var lastSig = '';
	var debounceTimer = null;
	var inFlight = false;
	var wantAgain = false;

	function revealSlot() {
		var slot = document.getElementById('mhfgfwc-blocks-slot');
		if (!slot) {
			return;
		}
		slot.classList.remove('wcb-mhfgfwc-slot--empty');
		slot.removeAttribute('aria-hidden');
	}

	function renderMhGiftPanel() {
		revealSlot();
		var url = cfg.renderUrl + '&nonce=' + encodeURIComponent(cfg.nonce || '');
		return fetch(url, { credentials: 'same-origin' })
			.then(function (r) {
				return r.text();
			})
			.then(function (html) {
				var inner = document.querySelector('#mhfgfwc-blocks-slot .mhfgfwc-blocks-slot__inner');
				if (inner) {
					inner.innerHTML = html;
				}
				document.dispatchEvent(new CustomEvent('mhfgfwc:cartChanged'));
			});
	}

	function runSync() {
		if (inFlight) {
			wantAgain = true;
			return;
		}
		inFlight = true;
		renderMhGiftPanel()
			.catch(function () {})
			.finally(function () {
				inFlight = false;
				if (wantAgain) {
					wantAgain = false;
					runSync();
				}
			});
	}

	function scheduleSync() {
		clearTimeout(debounceTimer);
		debounceTimer = setTimeout(function () {
			debounceTimer = null;
			runSync();
		}, 60);
	}

	function cartItemsSig() {
		try {
			var sel = wp.data.select('wc/store/cart');
			if (!sel || typeof sel.getCartData !== 'function') {
				return null;
			}
			var cart = sel.getCartData();
			var items = (cart && cart.items) || [];
			return JSON.stringify(
				items.map(function (i) {
					return [i.key, i.quantity, i.totals && i.totals.line_total, i.extensions];
				})
			);
		} catch (e) {
			return null;
		}
	}

	wp.data.subscribe(function () {
		var sig = cartItemsSig();
		if (sig === null) {
			return;
		}
		if (sig === lastSig) {
			return;
		}
		lastSig = sig;
		scheduleSync();
	});

	document.addEventListener('wcb:mhfgfwc_refresh_gifts', function () {
		scheduleSync();
	});
})();
