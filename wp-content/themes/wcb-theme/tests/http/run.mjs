#!/usr/bin/env node
/**
 * Testes HTTP mínimos — smoke de páginas + contratos AJAX (admin-ajax.php).
 * Requer site WordPress acessível (ex.: XAMPP).
 *
 * Uso: WCB_BASE_URL=http://localhost/wcb node tests/http/run.mjs
 *
 * Opcional:
 *   WCB_TEST_AUTH_COOKIE — header Cookie (sessão logada) para wcb_toggle_wishlist
 *   WCB_TEST_PRODUCT_ID — ID de produto para toggle (com cookie)
 *   WCB_TEST_PRODUCT_URL — URL absoluta de uma PDP (se a loja não tiver links detectáveis)
 */

const BASE = (process.env.WCB_BASE_URL || 'http://localhost/wcb').replace(/\/$/, '');
const AUTH_COOKIE = process.env.WCB_TEST_AUTH_COOKIE || '';
const PRODUCT_ID = process.env.WCB_TEST_PRODUCT_ID || '';

let failures = 0;

function fail(msg, err) {
	failures++;
	console.error(`FAIL: ${msg}`, err?.message || err || '');
}

const FETCH_MS = parseInt(process.env.WCB_FETCH_TIMEOUT_MS || '15000', 10);

async function fetchText(url, opts = {}) {
	const res = await fetch(url, {
		redirect: 'follow',
		signal: AbortSignal.timeout(FETCH_MS),
		...opts,
		headers: {
			...(opts.headers || {}),
			...(AUTH_COOKIE && url.includes('admin-ajax') ? { Cookie: AUTH_COOKIE } : {}),
		},
	});
	const text = await res.text();
	return { res, text };
}

function parseJsonLoose(text) {
	const t = text.trim();
	if (t === '-1' || t === '0') return { wpDie: t };
	try {
		return JSON.parse(t);
	} catch {
		return { parseError: true, raw: t.slice(0, 200) };
	}
}

function ajaxUrlFromHome(html) {
	const m = html.match(/"ajaxUrl"\s*:\s*"([^"]+)"/i);
	if (m && m[1].includes('admin-ajax')) {
		return m[1].replace(/\\\//g, '/');
	}
	return `${BASE}/wp-admin/admin-ajax.php`;
}

async function postAjax(ajaxUrl, fields) {
	const body = new URLSearchParams();
	for (const [k, v] of Object.entries(fields)) {
		body.append(k, v);
	}
	const { res, text } = await fetchText(ajaxUrl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
		},
		body: body.toString(),
	});
	return { res, text, json: parseJsonLoose(text) };
}

function extractMiniCartNonce(html) {
	const m = html.match(/"miniCartNonce":"([^"]+)"/);
	return m ? m[1] : null;
}

function extractPublicAjaxNonce(html) {
	const m = html.match(/"publicAjaxNonce":"([^"]+)"/);
	return m ? m[1] : null;
}

function extractWishlistNonce(html) {
	const m = html.match(/wcbWishlist[\s\S]{0,1200}?nonce:\s*"([^"]+)"/);
	return m ? m[1] : null;
}

async function runAjaxTests(ajaxUrl, homeHtml) {
	const miniNonce = extractMiniCartNonce(homeHtml);
	const wishNonce = extractWishlistNonce(homeHtml);
	const publicNonce = extractPublicAjaxNonce(homeHtml);

	// --- wcb_get_wishlist ---
	try {
		let r = await postAjax(ajaxUrl, { action: 'wcb_get_wishlist' });
		if (!r.json || r.json.success !== false) {
			fail('wcb_get_wishlist sem nonce deve retornar success:false', r.text.slice(0, 120));
		}
		r = await postAjax(ajaxUrl, { action: 'wcb_get_wishlist', nonce: 'invalid_nonce_xyz' });
		if (!r.json || r.json.success !== false) {
			fail('wcb_get_wishlist nonce inválido deve falhar');
		}
		if (wishNonce) {
			r = await postAjax(ajaxUrl, { action: 'wcb_get_wishlist', nonce: wishNonce });
			if (!r.json || r.json.success !== true || !Array.isArray(r.json.data?.wishlist)) {
				fail('wcb_get_wishlist com nonce válido deve retornar data.wishlist array', JSON.stringify(r.json).slice(0, 200));
			}
		} else {
			console.warn('SKIP: nonce wishlist não encontrado no HTML da home');
		}
	} catch (e) {
		fail('wcb_get_wishlist', e);
	}

	// --- wcb_mini_cart ---
	try {
		let r = await postAjax(ajaxUrl, { action: 'wcb_mini_cart' });
		if (!r.json || r.json.success !== false) {
			fail('wcb_mini_cart sem nonce deve retornar success:false');
		}
		if (miniNonce) {
			r = await postAjax(ajaxUrl, { action: 'wcb_mini_cart', nonce: miniNonce });
			if (r.json?.parseError || r.json?.wpDie) {
				fail('wcb_mini_cart com nonce válido deve retornar JSON', r.text.slice(0, 100));
			}
			if (typeof r.json.html !== 'string' || typeof r.json.count !== 'number') {
				fail('wcb_mini_cart resposta deve ter html (string) e count (number)');
			}
		} else {
			console.warn('SKIP: miniCartNonce não encontrado');
		}
	} catch (e) {
		fail('wcb_mini_cart', e);
	}

	// --- wcb_save_abandoned_cart ---
	try {
		const r = await postAjax(ajaxUrl, {
			action: 'wcb_save_abandoned_cart',
			email: 'test@example.com',
		});
		if (!r.json || r.json.success !== false) {
			fail('wcb_save_abandoned_cart sem nonce deve falhar');
		}
	} catch (e) {
		fail('wcb_save_abandoned_cart', e);
	}

	// --- wcb_toggle_wishlist (só wp_ajax; visitante → 0 / não JSON) ---
	try {
		const r = await postAjax(ajaxUrl, {
			action: 'wcb_toggle_wishlist',
			nonce: wishNonce || 'x',
			product_id: '1',
		});
		const t = r.text.trim();
		if (t !== '0' && t !== '-1' && !t.includes('success')) {
			// alguns ambientes podem devolver JSON de erro de nonce se hook diferente
			if (!(r.json && r.json.success === false)) {
				fail('wcb_toggle_wishlist visitante: esperado 0/-1 ou JSON erro', t.slice(0, 80));
			}
		}
	} catch (e) {
		fail('wcb_toggle_wishlist visitante', e);
	}

	if (AUTH_COOKIE && wishNonce && PRODUCT_ID) {
		try {
			const r = await fetch(ajaxUrl, {
				method: 'POST',
				redirect: 'follow',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
					Cookie: AUTH_COOKIE,
				},
				body: new URLSearchParams({
					action: 'wcb_toggle_wishlist',
					nonce: wishNonce,
					product_id: PRODUCT_ID,
				}).toString(),
			});
			const text = await r.text();
			const j = parseJsonLoose(text);
			if (!j.success && !j.data) {
				console.warn('wcb_toggle_wishlist logado: resposta inesperada (verifique cookie/produto)', text.slice(0, 120));
			}
		} catch (e) {
			fail('wcb_toggle_wishlist logado', e);
		}
	}

	// --- wcb_live_search: nonce inválido ---
	if (publicNonce) {
		try {
			const url = `${ajaxUrl}?action=wcb_live_search&q=test&nonce=bad`;
			const { text } = await fetchText(url);
			const j = parseJsonLoose(text);
			if (j.success !== false && !j.parseError) {
				fail('wcb_live_search com nonce inválido deve retornar erro', text.slice(0, 100));
			}
		} catch (e) {
			fail('wcb_live_search', e);
		}
	} else {
		console.warn('SKIP: publicAjaxNonce não encontrado (live search)');
	}
}

async function runSmoke() {
	const paths = ['/', '/shop/', '/loja/', '/product-category/promocoes/', '/cart/', '/carrinho/'];
	const seen = new Set();
	let productUrl = null;

	for (const p of paths) {
		const url = `${BASE}${p}`;
		if (seen.has(url)) continue;
		seen.add(url);
		try {
			const { res, text } = await fetchText(url);
			const ok = res.ok || res.status === 301 || res.status === 302;
			const soft404 =
				p === '/cart/' ||
				p === '/carrinho/' ||
				p === '/product-category/promocoes/';
			if (!ok && res.status === 404 && soft404) {
				console.warn(`SKIP smoke ${p} (404 — slug pode não existir neste ambiente)`);
				continue;
			}
			if (!ok && res.status !== 404) {
				fail(`smoke ${p} status ${res.status}`);
			}
			if (res.status === 404 && (p === '/cart/' || p === '/carrinho/')) {
				continue;
			}
			if (text.length < 200) {
				fail(`smoke ${p} corpo muito curto`);
			}
			const lower = text.toLowerCase();
			if (lower.includes('fatal error') || lower.includes('uncaught error')) {
				fail(`smoke ${p} possível fatal no HTML`);
			}
			if (!lower.includes('<html') && !lower.includes('<!doctype')) {
				fail(`smoke ${p} sem html raiz`);
			}
			if ((p === '/shop/' || p === '/loja/') && !productUrl) {
				const patterns = [
					/href="([^"]*\/product\/[^"?#]+)/i,
					/href="([^"]*\?post_type=product[^"#]+)/i,
					/<a[^>]+href="([^"]+)"[^>]*class="[^"]*woocommerce-loop-product__link/i,
				];
				for (const re of patterns) {
					const m = text.match(re);
					if (m) {
						try {
							productUrl = new URL(m[1], BASE).href;
							break;
						} catch {
							productUrl = null;
						}
					}
				}
			}
		} catch (e) {
			fail(`smoke ${p}`, e);
		}
	}

	if (process.env.WCB_TEST_PRODUCT_URL) {
		productUrl = process.env.WCB_TEST_PRODUCT_URL;
	}
	if (productUrl) {
		try {
			const { res, text } = await fetchText(productUrl);
			if (!res.ok || text.length < 200) {
				fail('smoke produto PDP');
			}
		} catch (e) {
			fail('smoke produto', e);
		}
	} else {
		console.warn('SKIP: URL de produto não encontrada (defina WCB_TEST_PRODUCT_URL ou use /shop/ com produtos)');
	}
}

async function main() {
	console.log(`WCB HTTP tests — BASE=${BASE}\n`);

	let homeHtml = '';
	try {
		const { res, text } = await fetchText(`${BASE}/`);
		if (!res.ok) {
			fail(`home status ${res.status}`);
			process.exit(1);
		}
		homeHtml = text;
	} catch (e) {
		fail('home inacessível — suba o servidor (Apache/XAMPP)', e);
		console.error('\nDefina WCB_BASE_URL se o site não estiver em http://localhost/wcb/');
		process.exit(1);
	}

	let catalogHtml = '';
	for (const path of ['/shop/', '/loja/']) {
		try {
			const { res, text } = await fetchText(`${BASE}${path}`);
			if (res.ok && text.length > 500) {
				catalogHtml = text;
				break;
			}
		} catch {
			/* tenta próximo */
		}
	}

	const htmlForNonces = `${homeHtml}\n${catalogHtml}`;
	const ajaxUrl = ajaxUrlFromHome(htmlForNonces);
	console.log(`admin-ajax: ${ajaxUrl}\n`);

	await runSmoke();
	await runAjaxTests(ajaxUrl, htmlForNonces);

	if (failures > 0) {
		console.error(`\n${failures} falha(s).`);
		process.exit(1);
	}
	console.log('\nOK: todos os testes HTTP passaram.');
}

main().catch((e) => {
	console.error(e);
	process.exit(1);
});
