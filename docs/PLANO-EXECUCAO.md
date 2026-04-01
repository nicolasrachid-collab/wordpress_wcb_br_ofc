# WCB — Plano de execução implementado (resumo)

Este documento complementa [WCB-AUDITORIA-TECNICA-COMPLETA.md](./WCB-AUDITORIA-TECNICA-COMPLETA.md) com o que foi **entregue no código** nesta iteração.

## Fase 1 — Blindagem (P0)

| Item | Implementação |
|------|----------------|
| Demo/import/apply só em dev | `WCB_DEV` em [wp-config.php](../wp-config.php) (default `true` local); [functions.php](../wp-content/themes/wcb-theme/functions.php) carrega `setup-demo-products`, `import-product-images`, `apply-images-logo` e `wcb-dev-tools-admin.php` apenas se `WCB_DEV`. Fallback `WCB_DEV = false` se não definido antes do tema. |
| CSRF nas URLs dev | [inc/setup-demo-products.php](../wp-content/themes/wcb-theme/inc/setup-demo-products.php), `import-product-images.php`, `apply-images-logo.php` exigem `_wpnonce`. Página **Ferramentas → WCB Dev** com links `wp_nonce_url`. |
| Carrinho abandonado | [inc/abandoned-cart.php](../wp-content/themes/wcb-theme/inc/abandoned-cart.php): `check_ajax_referer( 'wcb-ab-cart', 'nonce' )` + rate limit por IP (transient). |
| Mini-cart AJAX | [inc/woocommerce/cart-mini-ajax.php](../wp-content/themes/wcb-theme/inc/woocommerce/cart-mini-ajax.php): nonce `wcb-mini-cart`; [enqueue.php](../wp-content/themes/wcb-theme/inc/enqueue.php) `miniCartNonce`; [js/main.js](../wp-content/themes/wcb-theme/js/main.js) envia `nonce` na query. |

## Fase 2 — Performance e AJAX (P1)

| Item | Implementação |
|------|----------------|
| Nonce + throttle público | `wcb_public_ajax` em [inc/woocommerce.php](../wp-content/themes/wcb-theme/inc/woocommerce.php): `wcb_gift_progress_data`, `wcb_live_search`, `wcb_quick_view`; `wcb_calc_shipping` valida `wcb_calc_shipping` + rate limit. Helpers `wcb_verify_public_ajax_request()`, `wcb_rate_limit_public_ajax()`. |
| Live search cache | Transient `wcb_ls_v1_*` (5 min). Invalidação em massa ao guardar produto via `wcb_flush_home_transients`. |
| Filtro lateral | [inc/wcb-filter.php](../wp-content/themes/wcb-theme/inc/wcb-filter.php): cache HTML `wcb_filt_sb_v1_*` (15 min) + flush em `wcb_flush_home_transients`. |
| Promoções | [functions.php](../wp-content/themes/wcb-theme/functions.php): se `count(on_sale_ids) >` filtro `wcb_promocoes_post_in_max` (default 500), usa `meta_query` em `_sale_price` em vez de `post__in` gigante. |
| JS extraídos | [assets/js/wcb-testimonials-carousel.js](../wp-content/themes/wcb-theme/assets/js/wcb-testimonials-carousel.js), [assets/js/wcb-mega-menu-footer.js](../wp-content/themes/wcb-theme/assets/js/wcb-mega-menu-footer.js) enfileirados em [inc/enqueue.php](../wp-content/themes/wcb-theme/inc/enqueue.php). |
| Carrinho em blocos | [inc/cart-page-blocks-extras.php](../wp-content/themes/wcb-theme/inc/cart-page-blocks-extras.php) + [js/cart-page-extras.js](../wp-content/themes/wcb-theme/js/cart-page-extras.js): `noncePublicAjax` no `wcb_gift_progress_data`. |

## Fase 3 — Estrutura e DX (P2)

| Item | Implementação |
|------|----------------|
| Split Woo (início) | Mini-cart movido para [inc/woocommerce/cart-mini-ajax.php](../wp-content/themes/wcb-theme/inc/woocommerce/cart-mini-ajax.php). |
| Home hero | [template-parts/home/hero.php](../wp-content/themes/wcb-theme/template-parts/home/hero.php) + [front-page.php](../wp-content/themes/wcb-theme/front-page.php) com `get_template_part`. |
| PHPCS / npm | [phpcs.xml.dist](../wp-content/themes/wcb-theme/phpcs.xml.dist), [package.json](../wp-content/themes/wcb-theme/package.json) com scripts `lint:php` (requer `composer global` ou projeto com `squizlabs/php_codesniffer` + `wp-coding-standards/wpcs`). |
| SEO / a11y pontual | [header.php](../wp-content/themes/wcb-theme/header.php): `meta description` com `esc_attr`; hero: `type="button"` e labels traduzíveis. |

## Testes automáticos mínimos

Ver [TESTES.md](./TESTES.md) — `npm run test:php`, `npm run test:http`, `npm test` no diretório do tema.

## Mini-ciclo de endurecimento (documentação)

- Inventário AJAX: [WCB-AJAX-ENDPOINTS.md](./WCB-AJAX-ENDPOINTS.md)
- Lint PHP + Composer: [PHP-LINT.md](./PHP-LINT.md)
- QA categoria promoções: [QA-CATEGORIA-PROMOCOES.md](./QA-CATEGORIA-PROMOCOES.md)
- Go-live / `WCB_DEV`: [WP-CONFIG-PRODUCAO.md](./WP-CONFIG-PRODUCAO.md)

**Mini-ciclo aplicado:** nonce + rate limit em `wcb_get_wishlist`; `window.wcbFetchWishlist`; Composer + WPCS + `npm run lint:php` (com `memory_limit=512M`).

## Próximos passos recomendados

- Produção: `define( 'WCB_DEV', false );` em `wp-config.php`; `WP_DEBUG` false; salts únicos fora do Git.
- `composer install` em `wcb-theme` e validar `npm run lint:php`.
- Continuar a partiragem de [inc/woocommerce.php](../wp-content/themes/wcb-theme/inc/woocommerce.php) (search, quick view, side cart UI).
- Executar checklist em [QA-CATEGORIA-PROMOCOES.md](./QA-CATEGORIA-PROMOCOES.md).

## Versão do tema

`WCB_VERSION` bump para **1.4.14** em `functions.php` (cache bust de assets).
