# WCB Theme — Inventário de endpoints AJAX (`admin-ajax.php`)

Referência para auditoria de segurança e consistência. Atualizar quando novas actions forem adicionadas.

| Action | Auth | Nonce / verificação | Rate limit | Notas |
|--------|------|---------------------|------------|--------|
| `wcb_mini_cart` | nopriv + auth | `wcb-mini-cart` | — | Leitura carrinho |
| `wcb_mini_cart_update_qty` | nopriv + auth | `wcb-mini-cart` | — | Mutação |
| `wcb_mini_cart_remove` | nopriv + auth | `wcb-mini-cart` | — | Mutação |
| `wcb_gift_progress_data` | nopriv + auth | `wcb_public_ajax` | sim (`gift_prog`) | Leitura |
| `wcb_calc_shipping` | nopriv + auth | `wcb_calc_shipping` | sim (`calc_ship`) | CEP / frete |
| `wcb_side_cart_apply_coupon` | nopriv + auth | `wcb_side_cart` | — | Mutação carrinho |
| `wcb_side_cart_remove_coupon` | nopriv + auth | `wcb_side_cart` | — | Mutação |
| `wcb_side_cart_set_shipping` | nopriv + auth | `wcb_side_cart` | — | Mutação |
| `wcb_live_search` | nopriv + auth | `wcb_public_ajax` | sim (`live_search`) | Leitura + cache |
| `wcb_quick_view` | nopriv + auth | `wcb_public_ajax` | sim (`quick_view`) | Leitura |
| `wcb_toggle_wishlist` | auth only | `wcb_wishlist_nonce` | — | Mutação meta utilizador |
| `wcb_get_wishlist` | nopriv + auth | `wcb_wishlist_nonce` | sim (`wishlist_get`) | Leitura; visitante devolve lista vazia |
| `wcb_filter_products` | nopriv + auth | `wcb_filter_nonce` | — | Catálogo |
| `wcb_cart_page_summary_rows` | nopriv + auth | `wcb_side_cart` | — | Página carrinho (blocks) |
| `wcb_save_abandoned_cart` | nopriv + auth | `wcb-ab-cart` | sim (IP) | Dados limitados |
| `wcb_review_helpful` | nopriv + auth | `wcb_review_helpful` | sim (IP por comentário) | Voto útil |
| `wcb_nl4_subscribe` | nopriv + auth | `wcb_nl4` | — | Newsletter |

**Exceções intencionais:** ações de carrinho lateral / mini-cart não usam o nonce genérico `wcb_public_ajax`; usam nonces específicos por superfície (`wcb_side_cart`, `wcb-mini-cart`) para reduzir superfície de reutilização indevida.

**Melhorias opcionais (P2):** throttle leve em `wcb_side_cart_*` para limitar tentativas de cupom por IP.
