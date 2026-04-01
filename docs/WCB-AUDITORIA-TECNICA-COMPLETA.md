# WCB — Relatório de auditoria técnica completa

**Projeto:** WordPress customizado (tema `wcb-theme`)  
**Âmbito analisado:** tema `wp-content/themes/wcb-theme`, `wp-config.php` na raiz, padrões de segurança e performance identificados no código.  
**Nota:** Plugins de terceiros e núcleo WordPress não foram auditados linha a linha; apenas interações explícitas do tema.

---

## Índice

1. [Visão geral executiva](#1-visão-geral-executiva)
2. [Problemas por categoria](#2-problemas-por-categoria)
3. [Gargalos e riscos futuros](#3-gargalos-e-riscos-futuros)
4. [Oportunidades de melhoria](#4-oportunidades-de-melhoria)
5. [Plano de ação priorizado](#5-plano-de-ação-priorizado)
6. [Refatorações sugeridas](#6-refatorações-sugeridas)
7. [Checklist pré-produção](#7-checklist-pré-produção)
8. [Verificação completa — AJAX e segurança](#8-verificação-completa--ajax-e-segurança)
9. [Top 10 problemas críticos](#9-top-10-problemas-críticos)
10. [Top 10 melhorias com maior ROI](#10-top-10-melhorias-com-maior-roi)

---

## 1. Visão geral executiva

| Critério | Avaliação |
|----------|-----------|
| **Nota geral (0–10)** | **6,0** — loja viável com cuidados de performance e UX, mas com riscos de produção (segurança, escala e manutenção). |
| **Maturidade técnica** | Intermediária a avançada em *features* (Woo, AJAX, transients, dequeue), mais fraca em modularização e *hardening* para produção enterprise. |
| **Principais riscos** | Monolitos (`inc/woocommerce.php`, `front-page.php`, `style.css`), endpoints AJAX sem nonce ou sem rate limit, rotinas admin por GET sem CSRF, filtro de loja com custo alto (N+1), categoria “promoções” com `post__in` potencialmente enorme. |
| **Principais qualidades** | Pasta `inc/` documentada em `functions.php`, transients na home/header, `should_load_separate_core_block_assets`, dequeue seletivo, remoção de emoji/oEmbed, ajustes de Heartbeat, vários handlers com `check_ajax_referer`. |
| **Diagnóstico** | Base ambiciosa e funcional que mistura código de loja, demos, importadores e patches de plugins. Para produção de alta qualidade: reduzir superfície de ataque, modularizar, endurecer AJAX/admin e rever queries à escala de catálogo. |

---

## 2. Problemas por categoria

Formato: **título** · severidade · impacto · evidência · motivo · correção · prioridade.

### Arquitetura

- **Monolito `inc/woocommerce.php` (~3400+ linhas)** · **Alta** · Revisões e regressões difíceis · Inclusão única em `functions.php` · Viola SRP · Dividir em módulos (`cart-ajax.php`, `search.php`, `wishlist.php`, etc.) · Alta.
- **`front-page.php` muito extenso (~1700+ linhas)** · **Média** · Manutenção cara · Hero + múltiplos `WP_Query` + HTML · `get_template_part` por secção + `inc/home-sections.php` · Média.
- **`style.css` monolítico (~29k linhas)** · **Alta** (DX/manutenção) · Especificidade e duplicação · Cabeçalho + corpo massivo em `style.css` · SCSS/CSS modular + build · Média–alta.
- **Demo/import sempre carregados** · **Alta** (produção) · Superfície extra · `functions.php` inclui `setup-demo-products.php`, `import-product-images.php`, `apply-images-logo.php` · Plugin dev ou flag `WCB_DEV` · Crítica para go-live.
- **Pasta `.sfdx/` no repositório** · **Baixa** · Ruído DX · Raiz do projeto · `.gitignore` · Baixa.

### WordPress

- **`remove_all_actions` em plugins de wishlist** · **Média** · Quebra após updates · `functions.php` (hook `init`) · Preferir desativar plugin ou filtros documentados · Média.
- **CSS em `wp_head` para esconder plugins** · **Média** · Peso global · `functions.php` + `inc/woocommerce.php` · Desinstalar ou folha mínima · Média.
- **JS inline (depoimentos + mega menu)** · **Média** · CSP/cache/DX · `inc/enqueue.php` · Ficheiros `.js` enfileirados · Média.

### Performance

- **`wcb_render_native_filter()` custo alto** · **Alta** em catálogos grandes · TTFB loja · `inc/wcb-filter.php` — `get_posts` por categoria + fallback todos os IDs · Query agregada, cache, ou facet dedicado · Alta.
- **`pre_get_posts` em `promocoes` com `post__in` massivo** · **Alta** · Memória/MySQL · `functions.php` · Estratégia escalável (meta/tax/cron) · Alta.
- **Live search: loop por ID com termos/meta** · **Média** · Carga por pedido · `wcb_live_search_handler` em `woocommerce.php` · Cache/objeto search · Média.
- **`wcb_calc_shipping_ajax` persiste `WC()->customer`** · **Média** · Efeito lateral multi-aba · `woocommerce.php` · Calcular sem persistir ou documentar · Baixa–média.

### Segurança

- **`wcb_mini_cart` sem nonce** · **Média** · CSRF / inconsistência · `wcb_mini_cart_ajax()` sem `check_ajax_referer` · Alinhar com `update_qty`/`remove` · Alta.
- **`wcb_save_abandoned_cart` sem nonce** · **Alta** · Spam BD · `inc/abandoned-cart.php` · Nonce + rate limit · Crítica.
- **AJAX sem nonce:** gift progress, calc shipping, live search, quick view, get wishlist (logado) · **Média** · Abuso/DoS · `woocommerce.php` · Nonce público + throttle · Média–alta.
- **GET admin `wcb_setup_demo` / `wcb_import_images` / `wcb_apply_all` sem nonce** · **Crítica** (admin) · CSRF · Ficheiros em `inc/` · `admin_post` + nonce ou WP-CLI · Crítica.
- **`wp-config.php`:** `WP_DEBUG` true, salts no repo · **Alta** se repo exposto · `wp-config.php` · Ambiente separado, chaves únicas · Alta produção.

### SEO

- **Meta description só `bloginfo('description')`** · **Média** · SERPs genéricos · `header.php` · Plugin SEO ou filtros por template · Média.
- **Sem schema/OG explícitos no tema** · **Baixa–média** se plugin cobrir · Grep no tema · Validar Rich Results · Média.

### Acessibilidade

- **Labels i18n em inline JS (depoimentos)** · **Baixa** · `enqueue.php` · `wp_localize_script` · Baixa.
- **Quick view modal** · **Média** · Foco / WCAG · `wcb_quick_view_modal_html` + JS · *Focus trap* e retorno de foco · Média.
- **Hero vídeo `autoplay`** · **Baixa** · `prefers-reduced-motion` · `front-page.php` · Pausa / respeito motion · Baixa.

### Frontend / backend / DX

- **`main.js` muito grande** · **Média** · Parse em mobile · `js/main.js` · Code splitting condicional · Média.
- **Encoding no cabeçalho `style.css`** · **Baixa** · Caracteres corrompidos · `style.css` · UTF-8 · Baixa.
- **Newsletter em `wp_options`** · **Média** (negócio/RGPD) · `newsletter.php` · Serviço externo + consentimento · Média.
- **Sem lint/build no tema** · **Média** · PHPCS WPCS, ESLint · CI · Média.

---

## 3. Gargalos e riscos futuros

- **Atuais:** filtro nativo na loja; `post__in` em promoções; home/header com vários blocos; ficheiros únicos gigantes.
- **Crescimento do catálogo:** arquivo/shop lento; limite de query com milhares de IDs em `post__in`.
- **Débito técnico:** desativar plugins via CSS + `remove_all_actions`; JS inline; ferramentas demo no tema; vários carrinhos (mini + Xoo + ocultar Modern Cart).
- **Produção:** debug ligado; CSRF admin GET; spam na tabela abandonados; AJAX sem throttle.
- **Equipa:** merges e reviews lentos em `woocommerce.php` / `style.css`.

---

## 4. Oportunidades de melhoria

| Tipo | Melhoria |
|------|----------|
| Rápido | Nonces em `wcb_save_abandoned_cart` e `wcb_mini_cart`; `WP_DEBUG` false em prod; remover includes de demo do `functions.php`. |
| Alto impacto | Refatorar `wcb_render_native_filter` + query “promoções”. |
| Estrutural | Dividir `woocommerce.php`, `front-page.php`, pipeline de assets. |
| Padrão | PHPCS; evitar `remove_all_actions` sem testes. |
| Performance | Object cache; lazy sections home. |
| Segurança | Nonce/rate limit em todos `nopriv` relevantes; remover GET admin mutável. |

---

## 5. Plano de ação priorizado

### Imediato (P0)

| Ação | Objetivo | Esforço | Impacto |
|------|----------|---------|---------|
| Desligar demo/import em prod | Superfície | Baixo | Alto |
| Nonce + rate limit abandoned cart | Anti-spam | Baixo | Alto |
| Nonce em `wcb_mini_cart` | CSRF | Baixo | Médio |
| `WP_DEBUG` + salts por ambiente | Segurança | Baixo | Alto |
| Nonce em setup/import/apply (admin) | CSRF admin | Médio | Alto |

### Curto prazo (P1)

| Ação | Objetivo | Esforço | Impacto |
|------|----------|---------|---------|
| Otimizar filtro + cache | TTFB shop | Alto | Alto |
| Redesenhar query promoções | Escala | Médio | Alto |
| Throttle live search / quick view | Abuso | Médio | Médio |

### Médio prazo (P2)

| Ação | Objetivo | Esforço | Impacto |
|------|----------|---------|---------|
| Modularizar `woocommerce.php` + CSS | DX | Alto | Alto |
| Build pipeline | Perf + DX | Alto | Médio–alto |
| E2E checkout/carrinho/filtros | Confiança | Alto | Alto |

---

## 6. Refatorações sugeridas

1. **`woocommerce.php` → módulos** — menos regressões; risco médio (testar AJAX).
2. **`wcb_render_native_filter()`** — loja rápida com muitos SKUs; risco médio (contagens).
3. **Categoria promoções** — eliminar `post__in` ilimitado; risco médio (dados).
4. **Extrair inline JS de `enqueue.php`** — risco baixo.
5. **`style.css` → partials/preprocessador** — risco alto visual; fazer incremental.

---

## 7. Checklist pré-produção

- [ ] `WP_DEBUG` / log / display por ambiente
- [ ] Chaves e salts únicos fora do Git
- [ ] Remover ou proteger demo/import/apply-images
- [ ] Todos os `wp_ajax` / `nopriv` com nonce ou alternativa + throttle onde fizer sentido
- [ ] Política RGPD para `wcb_abandoned_carts` e newsletter
- [ ] Testar shop com catálogo grande (promoções + filtros)
- [ ] Lighthouse / CWV (home, PLP, PDP, checkout)
- [ ] Schema/meta (Rich Results / Search Console)
- [ ] A11y: menus, modais, checkout
- [ ] Cache (ex.: LiteSpeed) alinhado a tipos de página
- [ ] `.gitignore` (`.sfdx`, configs sensíveis)
- [ ] Plano de update dos plugins com hooks removidos no tema

---

## 8. Verificação completa — AJAX e segurança

**Método:** grep em `wp-content/themes/wcb-theme` por `wp_ajax`, `check_ajax_referer`, `$_GET` mutável, `$wpdb` literal; sem `register_rest_route` no tema; sem `eval`/`shell_exec`/`echo` direto de superglobals nos PHP do tema.

### Inventário `admin-ajax` (17 actions)

| # | Action | nopriv | Nonce |
|---|--------|--------|-------|
| 1 | `wcb_mini_cart` | Sim | **Não** |
| 2 | `wcb_mini_cart_update_qty` | Sim | Sim (`wcb-mini-cart`) |
| 3 | `wcb_mini_cart_remove` | Sim | Sim (`wcb-mini-cart`) |
| 4 | `wcb_gift_progress_data` | Sim | **Não** |
| 5 | `wcb_calc_shipping` | Sim | **Não** |
| 6 | `wcb_side_cart_apply_coupon` | Sim | Sim (`wcb_side_cart`) |
| 7 | `wcb_side_cart_remove_coupon` | Sim | Sim (`wcb_side_cart`) |
| 8 | `wcb_side_cart_set_shipping` | Sim | Sim (`wcb_side_cart`) |
| 9 | `wcb_live_search` | Sim | **Não** (GET) |
| 10 | `wcb_quick_view` | Sim | **Não** (GET) |
| 11 | `wcb_toggle_wishlist` | Não | Sim (`wcb_wishlist_nonce`) |
| 12 | `wcb_get_wishlist` | Sim | **Não** |
| 13 | `wcb_cart_page_summary_rows` | Sim | Sim (`wcb_side_cart`) |
| 14 | `wcb_review_helpful` | Sim | Sim (`wcb_review_helpful`) |
| 15 | `wcb_nl4_subscribe` | Sim | Sim (`wcb_nl4`) |
| 16 | `wcb_filter_products` | Sim | Sim (`wcb_filter_nonce`) |
| 17 | `wcb_save_abandoned_cart` | Sim | **Não** |

**Sem nonce (7):** `wcb_mini_cart`, `wcb_gift_progress_data`, `wcb_calc_shipping`, `wcb_live_search`, `wcb_quick_view`, `wcb_get_wishlist`, `wcb_save_abandoned_cart`.

**Atualização:** o inventário e o estado atual dos nonces/throttle estão em [WCB-AJAX-ENDPOINTS.md](./WCB-AJAX-ENDPOINTS.md) (vários itens desta tabela foram corrigidos depois da auditoria; `wcb_get_wishlist` passa a exigir `wcb_wishlist_nonce` após o patch do mini-ciclo).

**GET sensíveis (tema):**

| Parâmetro | Proteção | Nota |
|-----------|----------|------|
| `wcb_setup_demo`, `wcb_import_images`, `wcb_apply_all` | `manage_options` apenas | Falta nonce → CSRF admin |
| `wcb_recover` | Token + `prepare` + TTL 7d | Adequado |
| Admin abandonados `send_email` / `delete_cart` | `check_admin_referer` | OK |

**SQL no tema:** usos revistos sem injeção óbvia; `wcb-filter` usa `IN` com inteiros; admin abandonados usa `prepare` para filtro de status.

---

## 9. Top 10 problemas críticos

1. CSRF em ações admin GET (demo/import/apply).
2. `wcb_save_abandoned_cart` sem nonce.
3. Filtro nativo com padrão de queries muito caro.
4. `post__in` massivo na categoria promoções.
5. `WP_DEBUG` true e salts no repositório (se exposto).
6. Monolitos `woocommerce.php`, `style.css`, `front-page.php`.
7. Vários AJAX sem nonce/throttle (search, QV, frete, gift progress).
8. `wcb_mini_cart` sem nonce.
9. Conflitos com plugins via `remove_all_actions` + CSS.
10. Includes de demo sempre ativos no tema.

---

## 10. Top 10 melhorias com maior ROI

1. Retirar/guardar scripts de demo do carregamento normal.
2. Nonce + rate limit no carrinho abandonado.
3. `WP_DEBUG` false + secrets fora do repo.
4. Nonce em `wcb_mini_cart`.
5. CSRF fix nas URLs `?wcb_setup_demo=` / import / apply.
6. Cache + query única (ou pré-agregação) no filtro lateral.
7. Substituir estratégia `post__in` de promoções.
8. Throttle em live search (cliente + servidor).
9. Extrair mega menu e testimonials para `.js` enfileirado.
10. PHPCS (WPCS) no CI só no `wcb-theme`.

---

## Referências de ficheiros principais

- `wp-content/themes/wcb-theme/functions.php`
- `wp-content/themes/wcb-theme/inc/enqueue.php`
- `wp-content/themes/wcb-theme/inc/woocommerce.php`
- `wp-content/themes/wcb-theme/inc/wcb-filter.php`
- `wp-content/themes/wcb-theme/inc/abandoned-cart.php`
- `wp-content/themes/wcb-theme/inc/newsletter.php`
- `wp-content/themes/wcb-theme/inc/pdp-reviews.php`
- `wp-content/themes/wcb-theme/inc/cart-page-blocks-extras.php`
- `wp-content/themes/wcb-theme/inc/setup-demo-products.php`
- `wp-content/themes/wcb-theme/inc/import-product-images.php`
- `wp-content/themes/wcb-theme/inc/apply-images-logo.php`
- `wp-content/themes/wcb-theme/front-page.php`, `header.php`, `style.css`, `js/main.js`
- `wp-config.php` (raiz)

---

*Documento gerado para suporte à decisão de produção e roadmap técnico. Atualizar após correções implementadas.*

---

## Implementação em código (março 2026)

Várias correções P0/P1/P2 foram aplicadas no tema; ver **[PLANO-EXECUCAO.md](./PLANO-EXECUCAO.md)** para o resumo objetivo e ficheiros alterados.
