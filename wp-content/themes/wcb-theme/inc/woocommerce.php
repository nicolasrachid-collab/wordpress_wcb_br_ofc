<?php
/**
 * WCB Theme — WooCommerce Helpers
 * Cart count, AJAX handlers, side cart stepper, gift progress bar,
 * shop loop configuration, wishlist, and body/search helpers.
 *
 * @package WCB_Theme
 */

if (!defined('ABSPATH'))
    exit;

/* ============================================================
   HELPER — Threshold de frete grátis = valor configurado no WooCommerce
   Lê o método "Frete Grátis" da primeira zona de entrega ativa.
   Fallback: R$ 199 caso nenhuma zona tenha frete grátis configurado.
   ============================================================ */
function wcb_get_free_ship_threshold()
{
    static $cached = null;
    if ($cached !== null)
        return $cached;

    if (!function_exists('WC') || !class_exists('WC_Shipping_Zones')) {
        return $cached = 199;
    }

    // Percorre todas as zonas (incluindo zona 0 = Resto do mundo)
    $zone_ids = array_keys(WC_Shipping_Zones::get_zones());
    $zone_ids[] = 0;

    foreach ($zone_ids as $zone_id) {
        $zone = WC_Shipping_Zones::get_zone($zone_id);
        foreach ($zone->get_shipping_methods(true) as $method) {
            if ($method->id === 'free_shipping' && $method->is_enabled()) {
                $min = (float) $method->get_option('min_amount', 0);
                if ($min > 0) {
                    return $cached = $min;
                }
            }
        }
    }

    return $cached = 199; // fallback padrão
}

/**
 * Obtém o termo product_cat a partir de uma URL de loja/listagem (query ?categoria= ou permalink).
 *
 * @param string $url URL absoluta ou relativa.
 * @return WP_Term|null
 */
function wcb_get_product_cat_from_url($url)
{
    if (!is_string($url) || $url === '' || !taxonomy_exists('product_cat')) {
        return null;
    }
    if (strpos($url, 'http') !== 0) {
        $url = home_url($url);
    }
    $parsed = wp_parse_url($url);
    if (empty($parsed['path']) && empty($parsed['query'])) {
        return null;
    }
    if (!empty($parsed['query'])) {
        parse_str($parsed['query'], $qs);
        if (!empty($qs['categoria'])) {
            $t = get_term_by('slug', sanitize_title($qs['categoria']), 'product_cat');
            if ($t && !is_wp_error($t)) {
                return $t;
            }
        }
    }
    if (!empty($parsed['path'])) {
        $path = trim($parsed['path'], '/');
        $parts = $path === '' ? array() : explode('/', $path);
        foreach (array('categoria-produto', 'product-category') as $marker) {
            $pos = array_search($marker, $parts, true);
            if ($pos !== false && isset($parts[$pos + 1])) {
                $t = get_term_by('slug', $parts[$pos + 1], 'product_cat');
                if ($t && !is_wp_error($t)) {
                    return $t;
                }
            }
        }
    }
    return null;
}

/**
 * Contagem de produtos como na listagem do catálogo: publicados, visíveis na loja,
 * respeita "esconder esgotados" e inclui subcategorias (include_children).
 *
 * @param int|WP_Term $term Term ID ou objeto product_cat.
 * @return int
 */
function wcb_get_product_cat_catalog_count($term)
{
    if (!function_exists('WC') || !taxonomy_exists('product_cat') || !taxonomy_exists('product_visibility')) {
        return 0;
    }
    $t = $term instanceof WP_Term ? $term : get_term((int) $term, 'product_cat');
    if (!$t || is_wp_error($t)) {
        return 0;
    }
    $neg_visibility = array('exclude-from-catalog', 'exclude-from-search');
    if ('yes' === get_option('woocommerce_hide_out_of_stock_items')) {
        $neg_visibility[] = 'outofstock';
    }
    $q = new WP_Query(array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'tax_query' => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => (int) $t->term_id,
                'include_children' => true,
            ),
            array(
                'taxonomy' => 'product_visibility',
                'field' => 'name',
                'terms' => $neg_visibility,
                'operator' => 'NOT IN',
            ),
        ),
    ));
    $n = (int) $q->found_posts;
    wp_reset_postdata();
    return $n;
}

/**
 * Página da loja: ?categoria=slug filtra por product_cat (igual ao link dos cards da home).
 * Sem isto, o parâmetro era ignorado e a contagem dos cards não batia com a listagem.
 */
add_action('woocommerce_product_query', 'wcb_shop_product_query_filter_categoria_param', 25);
function wcb_shop_product_query_filter_categoria_param($q)
{
    if (is_admin() || empty($_GET['categoria']) || !taxonomy_exists('product_cat')) {
        return;
    }
    $slug = sanitize_title(wp_unslash($_GET['categoria']));
    if ($slug === '') {
        return;
    }
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term || is_wp_error($term)) {
        return;
    }
    $tax_query = $q->get('tax_query');
    if (!is_array($tax_query)) {
        $tax_query = array();
    }
    $tax_query[] = array(
        'taxonomy' => 'product_cat',
        'field' => 'term_id',
        'terms' => (int) $term->term_id,
        'include_children' => true,
    );
    $q->set('tax_query', $tax_query);
}

/**
 * Escopo do countdown da barra de oferta na PDP (sessionStorage no navegador).
 *
 * - Categoria “Super Ofertas” (flash): slugs filtráveis via `wcb_pdp_offer_flash_category_slugs`
 *   (por defeito `super-ofertas` e `ofertas-relampago`, equivalentes). Timer global partilhado na sessão.
 * - ACF (opcional): campo `wcb_pdp_offer_timer_scope` no produto — `auto` | `global` | `product`.
 *   Em `auto`, aplica global só se o produto estiver numa categoria flash.
 *
 * @param int                  $product_id   ID do produto.
 * @param array|WP_Error|false $product_cats Resultado de get_the_terms( ..., 'product_cat' ).
 * @return string 'global'|'product'
 */
function wcb_pdp_get_offer_bar_timer_scope($product_id, $product_cats)
{
    $product_id = (int) $product_id;
    $flash_slugs = apply_filters(
        'wcb_pdp_offer_flash_category_slugs',
        array('super-ofertas', 'ofertas-relampago')
    );
    $flash_slugs = array_filter(array_map(function ($s) {
        return strtolower(sanitize_title((string) $s));
    }, (array) $flash_slugs));

    $in_flash = false;
    if (is_array($product_cats) && !is_wp_error($product_cats) && $flash_slugs !== array()) {
        $term_slugs = array_map('strtolower', wp_list_pluck($product_cats, 'slug'));
        foreach ($flash_slugs as $fs) {
            if ($fs !== '' && in_array($fs, $term_slugs, true)) {
                $in_flash = true;
                break;
            }
        }
    }

    $acf_scope = 'auto';
    if (function_exists('get_field')) {
        $v = get_field('wcb_pdp_offer_timer_scope', $product_id);
        if (is_string($v)) {
            $v = strtolower(trim($v));
            if (in_array($v, array('auto', 'global', 'product'), true)) {
                $acf_scope = $v;
            }
        }
    } elseif ($product_id > 0) {
        $raw = get_post_meta($product_id, 'wcb_pdp_offer_timer_scope', true);
        if (is_string($raw) && $raw !== '') {
            $v = strtolower(trim($raw));
            if (in_array($v, array('global', 'product'), true)) {
                $acf_scope = $v;
            }
        }
    }

    if ($acf_scope === 'auto') {
        return $in_flash ? 'global' : 'product';
    }

    return $acf_scope === 'global' ? 'global' : 'product';
}

/**
 * Garante uma categoria “flash” se ainda não existir nenhuma das equivalentes.
 * Slugs equivalentes: super-ofertas, ofertas-relampago (ver `wcb_pdp_offer_flash_category_slugs`).
 * Só cria `super-ofertas` quando nenhum dos dois termos existe (evita duplicar).
 */
function wcb_ensure_flash_offer_product_category()
{
    if (!function_exists('taxonomy_exists') || !taxonomy_exists('product_cat')) {
        return;
    }

    $canonical = 'super-ofertas';
    $legacy = 'ofertas-relampago';

    if (term_exists($canonical, 'product_cat') || term_exists($legacy, 'product_cat')) {
        return;
    }

    $insert = wp_insert_term(
        __('Super Ofertas', 'wcb-theme'),
        'product_cat',
        array(
            'slug' => $canonical,
            'description' => __(
                'Ofertas em destaque (equivalente à secção Super Ofertas). Countdown global da barra de oferta na PDP. Slug legado aceite: ofertas-relampago.',
                'wcb-theme'
            ),
        )
    );

    if (is_wp_error($insert)) {
        return;
    }
}

add_action('init', 'wcb_ensure_flash_offer_product_category', 20);

/**
 * Carrinho lateral Xoo Side Cart: só quando o plugin está ativo.
 * Para desativar pelo tema (plugin ainda ativo): add_filter( 'wcb_side_cart_active', '__return_false' );
 */
function wcb_is_side_cart_active()
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    if (!class_exists('WooCommerce')) {
        return $cached = false;
    }
    if (!function_exists('xoo_wsc')) {
        return $cached = false;
    }
    return $cached = (bool) apply_filters('wcb_side_cart_active', true);
}

/**
 * SVG da seta dos CTAs da buybox da PDP (ex.: adicionar ao carrinho).
 */
function wcb_pdp_cta_arrow_svg()
{
    return '<svg class="wcb-pdp-cta-arrow" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
}

/**
 * Remove o YITH FBT do hook `woocommerce_after_single_product_summary`
 * (o template `single-product.php` renderiza o bloco na coluna da buybox).
 */
function wcb_pdp_detach_yith_fbt_from_after_summary()
{
    if (!class_exists('YITH_WFBT_Frontend')) {
        return;
    }
    remove_action('woocommerce_after_single_product_summary', array(YITH_WFBT_Frontend(), 'add_bought_together_form'), 1);
}

/**
 * HTML do Frequently Bought Together (YITH) para o produto atual, ou string vazia.
 */
function wcb_pdp_get_yith_fbt_html()
{
    if (!class_exists('YITH_WFBT_Frontend')) {
        return '';
    }
    $html = YITH_WFBT_Frontend()->add_bought_together_form(false, true);
    return is_string($html) ? $html : '';
}

add_filter(
    'xoo_wsc_is_sidecart_page',
    static function ($show, $hide_pages) {
        if (!wcb_is_side_cart_active()) {
            return false;
        }
        return $show;
    },
    5,
    2
);

/** Remove o botão "Ver/Editar carrinho" do rodapé do side cart (link para página carrinho). */
add_filter(
    'xoo_wsc_footer_buttons_args',
    static function ($args) {
        if (!empty($args['buttons']['cart'])) {
            unset($args['buttons']['cart']);
        }
        return $args;
    },
    20
);

/* ============================================================
   UNIFIED CART — esconder mini-cart do tema e Modern Cart
   O carrinho único ativo é o Xoo Side Cart.
   ============================================================ */
add_action('wp_head', function () {
    $wcb_side_cart_on = wcb_is_side_cart_active();
    ?>
    <style>
        <?php if ($wcb_side_cart_on) : ?>
        /* Ocultar mini-cart flyout do tema (wcb-mini-cart) enquanto o Xoo está ativo */
        .wcb-mini-cart,
        .wcb-mini-cart-overlay {
            display: none !important;
        }
        <?php endif; ?>


        /* Ocultar Modern Cart plugin (carrinho duplicado) */
        #moderncart-slide-out,
        .moderncart-slide-out,
        .moderncart-overlay,
        #moderncart-floating-cart {
            display: none !important;
        }

        /* Ocultar mensagem de plugin externo de wishlist na nossa página favoritos */
        .woocommerce-account .woocommerce-MyAccount-content .ti-wishlists-notice,
        .woocommerce-account .woocommerce-MyAccount-content .yith-wcwl-wishlist-notice,
        .alg-wc-wl-empty-wishlist,
        .alg-wc-wl-wishlist-table-wrap {
            display: none !important;
        }

        <?php if ($wcb_side_cart_on) : ?>
        /* ── Xoo Side Cart – Design System WCB ── */

        /* Estado vazio: layout centralizado */
        .xoo-wsc-empty-cart {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 1rem !important;
            padding: 2.5rem 1.5rem !important;
            text-align: center !important;
        }

        .xoo-wsc-empty-cart span {
            font-size: 0.95rem !important;
            color: #64748b !important;
            font-weight: 500 !important;
            font-family: 'Inter', sans-serif !important;
        }

        /* Botão primário — azul WCB */
        .xoo-wsc-btn {
            background: #155DFD !important;
            color: #fff !important;
            border: none !important;
            border-radius: 4px !important;
            padding: 12px 28px !important;
            font-size: 0.9rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.02em !important;
            cursor: pointer !important;
            transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease !important;
            box-shadow: 0 4px 14px rgba(21, 93, 253, 0.30) !important;
            text-decoration: none !important;
            display: inline-block !important;
            width: 100% !important;
            text-align: center !important;
        }

        .xoo-wsc-btn:hover {
            background: #1249d6 !important;
            color: #fff !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 20px rgba(21, 93, 253, 0.40) !important;
        }

        /* Botão "Continuar comprando" — estilo link azul discreto */
        .xoo-wsc-ft-btn-continue {
            background: transparent !important;
            color: #155DFD !important;
            border: none !important;
            box-shadow: none !important;
            font-size: 0.82rem !important;
            font-weight: 600 !important;
            padding: 8px 0 !important;
            text-decoration: none !important;
            width: auto !important;
            letter-spacing: 0 !important;
        }

        .xoo-wsc-ft-btn-continue:hover {
            background: transparent !important;
            color: #1249d6 !important;
            box-shadow: none !important;
            transform: none !important;
            text-decoration: underline !important;
        }
        <?php endif; ?>

        /* ══════════════════════════════════════════════════════
                   QUICK VIEW MODAL — Premium Redesign
                   ══════════════════════════════════════════════════════ */

        /* ── Overlay ── */
        .wcb-qv-overlay {
            display: none;
            position: fixed !important;
            inset: 0 !important;
            z-index: 99999 !important;
            background: rgba(10, 15, 30, 0.55) !important;
            backdrop-filter: blur(10px) saturate(1.2) !important;
            -webkit-backdrop-filter: blur(10px) saturate(1.2) !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 1.5rem !important;
            opacity: 0;
            transition: opacity 0.25s ease !important;
        }

        .wcb-qv-overlay.is-open {
            display: flex !important;
            opacity: 1;
        }

        /* ── Modal Container ── */
        .wcb-qv-modal {
            background: #fff !important;
            border-radius: 20px !important;
            width: min(80vw, 1080px) !important;
            max-width: min(80vw, 1080px) !important;
            height: min(80vh, calc(100dvh - 3rem)) !important;
            max-height: min(80vh, calc(100dvh - 3rem)) !important;
            display: flex !important;
            flex-direction: column !important;
            min-height: 0 !important;
            overflow: hidden !important;
            scroll-behavior: smooth !important;
            position: relative !important;
            box-shadow:
                0 25px 60px -12px rgba(10, 15, 30, 0.28),
                0 0 0 1px rgba(15, 23, 42, 0.06) !important;
            animation: wcbQvIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }

        @keyframes wcbQvIn {
            from {
                opacity: 0;
                transform: scale(0.92) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* ── Close Button ── */
        #wcb-qv-close {
            position: absolute !important;
            top: 0.85rem !important;
            right: 0.85rem !important;
            width: 34px !important;
            height: 34px !important;
            border-radius: 50% !important;
            border: 1px solid rgba(0, 0, 0, 0.06) !important;
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(8px) !important;
            cursor: pointer !important;
            z-index: 10 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #475569 !important;
            transition: all 0.15s ease !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        }

        #wcb-qv-close:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
            transform: rotate(90deg) !important;
        }

        #wcb-qv-close:focus-visible {
            outline: 2px solid rgba(21, 93, 253, 0.55) !important;
            outline-offset: 2px !important;
        }

        /* ── Loading State ── */
        .wcb-qv-loading {
            display: flex;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
            padding: 5rem 2rem !important;
            gap: 1rem !important;
            color: #94a3b8 !important;
            font-size: 0.85rem !important;
        }

        .wcb-qv-loading.is-hidden {
            display: none !important;
            flex: 0 0 0 !important;
            min-height: 0 !important;
            height: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        .wcb-qv-spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #e2e8f0;
            border-top-color: #155DFD;
            border-radius: 50%;
            animation: wcbQvSpin 0.7s linear infinite;
        }

        @keyframes wcbQvSpin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── Content: galeria + buybox (preenche altura do modal) ── */
        #wcb-qv-content {
            display: none !important;
            flex-direction: column !important;
            gap: 0 !important;
            padding: 0 !important;
            min-height: 0 !important;
            flex: 0 0 0 !important;
            overflow: hidden !important;
        }

        #wcb-qv-content.is-visible {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
            position: relative !important;
            padding: 0 !important;
            min-height: 0 !important;
            overflow: hidden !important;
        }

        /* Skip link: fora do grid; só visível com :focus-visible (teclado) */
        .wcb-qv-skip-buy {
            position: absolute !important;
            left: 1rem !important;
            top: 0.55rem !important;
            z-index: 11 !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: hidden !important;
            clip-path: inset(50%) !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        .wcb-qv-skip-buy:focus-visible {
            width: auto !important;
            height: auto !important;
            padding: 0.4rem 0.75rem !important;
            clip-path: none !important;
            clip: auto !important;
            overflow: visible !important;
            white-space: normal !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            color: #155DFD !important;
            background: #eff6ff !important;
            border-radius: 8px !important;
            text-decoration: none !important;
            outline: 2px solid rgba(21, 93, 253, 0.45) !important;
            outline-offset: 2px !important;
            box-shadow: 0 2px 10px rgba(21, 93, 253, 0.12) !important;
        }

        .wcb-qv-top {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.12fr) !important;
            grid-template-rows: minmax(0, 1fr) !important;
            gap: 0 !important;
            align-items: stretch !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
            margin-top: 0 !important;
            overflow: hidden !important;
        }

        /* ── LEFT: Gallery ── */
        .wcb-qv-left {
            position: relative !important;
            background: linear-gradient(165deg, #f1f5f9 0%, #f8fafc 45%, #ffffff 100%) !important;
            padding: 1.65rem 1.5rem 1.5rem !important;
            display: flex !important;
            flex-direction: column !important;
            border-radius: 20px 0 0 0 !important;
            border-right: 1px solid rgba(226, 232, 240, 0.95) !important;
            min-height: 0 !important;
            height: 100% !important;
            max-height: none !important;
            align-self: stretch !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            scrollbar-gutter: stable !important;
        }

        .wcb-qv-left::-webkit-scrollbar {
            width: 5px;
        }

        .wcb-qv-left::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .wcb-qv-main-img-wrap {
            flex: 1 1 auto !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 200px !important;
            max-height: none !important;
            overflow: hidden !important;
        }

        .wcb-qv-main-img {
            width: 100% !important;
            height: 100% !important;
            border-radius: 12px !important;
            object-fit: contain !important;
            transition: opacity 0.2s ease, transform 0.2s ease !important;
        }

        /* Badge (discount) */
        .wcb-qv-badge {
            position: absolute !important;
            top: 1.25rem !important;
            left: 1.25rem !important;
            background: #ef4444 !important;
            color: #fff !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            padding: 4px 10px !important;
            border-radius: 6px !important;
            z-index: 2 !important;
            letter-spacing: 0.02em !important;
        }

        /* ── Gallery Thumbnails (horizontal) ── */
        .wcb-qv-thumbs {
            display: flex !important;
            gap: 8px !important;
            margin-top: 0.85rem !important;
            overflow-x: auto !important;
            padding-bottom: 4px !important;
            scrollbar-width: none !important;
        }

        .wcb-qv-thumbs::-webkit-scrollbar {
            display: none;
        }

        .wcb-qv-thumb {
            flex-shrink: 0 !important;
            width: 56px !important;
            height: 56px !important;
            border-radius: 8px !important;
            border: 2px solid transparent !important;
            background: #fff !important;
            cursor: pointer !important;
            overflow: hidden !important;
            transition: all 0.15s ease !important;
            padding: 2px !important;
        }

        .wcb-qv-thumb:hover {
            border-color: #cbd5e1 !important;
        }

        .wcb-qv-thumb.active {
            border-color: #155DFD !important;
            box-shadow: 0 0 0 2px rgba(21, 93, 253, 0.15) !important;
        }

        .wcb-qv-thumb img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 5px !important;
            display: block !important;
        }

        /* ── RIGHT: Product Info ── */
        .wcb-qv-right {
            padding: 1.55rem 3rem 1.65rem 1.65rem !important;
            display: flex !important;
            flex-direction: column !important;
            min-height: 0 !important;
            height: 100% !important;
            max-height: none !important;
            align-self: stretch !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            border-radius: 0 20px 0 0 !important;
            scrollbar-gutter: stable !important;
            background: linear-gradient(180deg, #ffffff 0%, #fafbfc 55%, #f8fafc 100%) !important;
        }

        #wcb-qv-pdp-buybox {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            width: 100% !important;
            overflow-y: auto !important;
        }

        .wcb-qv-right > .wcb-qv-full-link {
            margin-top: auto !important;
            flex-shrink: 0 !important;
        }

        #wcb-qv-pdp-buybox .wcb-pdp-divider--buybox {
            margin: 0.75rem 0 0.65rem !important;
        }

        .wcb-qv-right::-webkit-scrollbar {
            width: 5px;
        }

        .wcb-qv-right::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .wcb-qv-cat {
            font-size: 0.65rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.11em !important;
            text-transform: uppercase !important;
            color: #155DFD !important;
            display: inline-block !important;
            margin: 0 0 0.55rem !important;
            padding: 0 !important;
            border-radius: 0 !important;
            background: none !important;
            line-height: 1.2 !important;
        }

        .wcb-qv-title {
            font-family: 'Inter', system-ui, sans-serif !important;
            font-size: 1.38rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            margin: 0 0 0.65rem !important;
            line-height: 1.22 !important;
            letter-spacing: -0.02em !important;
        }

        /* ── Rating ── */
        .wcb-qv-rating {
            display: flex !important;
            align-items: center !important;
            gap: 5px !important;
            margin-bottom: 0.75rem !important;
            font-size: 0.82rem !important;
            color: #64748b !important;
        }

        .wcb-qv-stars {
            display: flex;
            gap: 1px;
        }

        .wcb-qv-star {
            width: 14px;
            height: 14px;
            fill: #e2e8f0;
            transition: fill 0.15s;
        }

        .wcb-qv-star.on {
            fill: #f59e0b;
        }

        .wcb-qv-rating-count {
            color: #94a3b8;
            font-size: 0.78rem;
        }

        /* ── Description ── */
        .wcb-qv-desc {
            font-size: 0.85rem !important;
            color: #64748b !important;
            line-height: 1.6 !important;
            margin-bottom: 0.85rem !important;
        }

        /* ── Price Block ── */
        .wcb-qv-price-block {
            margin-bottom: 0.75rem !important;
        }

        .wcb-qv-price-old {
            font-size: 0.88rem !important;
            color: #94a3b8 !important;
            text-decoration: line-through !important;
            font-weight: 500 !important;
            display: block !important;
            margin-bottom: 2px !important;
        }

        .wcb-qv-price-current {
            font-family: 'Inter', system-ui, sans-serif !important;
            font-size: 1.55rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            letter-spacing: -0.02em !important;
        }

        .wcb-qv-pix {
            display: inline-flex !important;
            align-items: center !important;
            gap: 5px !important;
            background: #f0fdf4 !important;
            border: 1px solid #bbf7d0 !important;
            border-radius: 6px !important;
            padding: 5px 12px !important;
            font-size: 0.8rem !important;
            color: #166534 !important;
            margin-top: 0.45rem !important;
            font-weight: 500 !important;
        }

        .wcb-qv-installments {
            font-size: 0.78rem !important;
            color: #94a3b8 !important;
            margin-top: 4px !important;
        }

        /* ── Specs ── */
        .wcb-qv-specs {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            margin: 0.35rem 0 0.85rem !important;
        }

        .wcb-qv-spec {
            font-size: 0.75rem !important;
            background: #f1f5f9 !important;
            color: #475569 !important;
            padding: 4px 10px !important;
            border-radius: 6px !important;
            font-weight: 500 !important;
        }

        /* ── Low Stock ── */
        .wcb-qv-low-stock {
            font-size: 0.8rem !important;
            color: #d97706 !important;
            background: #fffbeb !important;
            border: 1px solid #fed7aa !important;
            border-radius: 6px !important;
            padding: 6px 12px !important;
            margin: 0.5rem 0 !important;
            font-weight: 500 !important;
        }

        /* ══ Variation Swatches ══ */
        .wcb-qv-variations {
            margin: 0.75rem 0 0.25rem !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0.85rem !important;
        }

        .wcb-qv-var-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .wcb-qv-var-label {
            font-size: 0.78rem !important;
            font-weight: 700 !important;
            color: #334155 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
        }

        .wcb-qv-var-label span {
            font-weight: 500 !important;
            text-transform: none !important;
            color: #64748b !important;
            letter-spacing: 0 !important;
        }

        .wcb-qv-var-options {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 6px !important;
        }

        .wcb-qv-var-btn {
            background: #fff !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 7px 16px !important;
            font-size: 0.82rem !important;
            font-weight: 600 !important;
            color: #334155 !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
            position: relative !important;
            white-space: nowrap !important;
        }

        .wcb-qv-var-btn:hover {
            border-color: #155DFD !important;
            color: #155DFD !important;
            background: rgba(21, 93, 253, 0.03) !important;
        }

        .wcb-qv-var-btn.active {
            border-color: #155DFD !important;
            background: rgba(21, 93, 253, 0.06) !important;
            color: #155DFD !important;
            box-shadow: 0 0 0 2px rgba(21, 93, 253, 0.12) !important;
        }

        .wcb-qv-var-btn.out-of-stock {
            opacity: 0.4 !important;
            text-decoration: line-through !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
        }

        .wcb-qv-var-notice {
            font-size: 0.78rem !important;
            color: #ef4444 !important;
            display: none !important;
            margin-top: 2px !important;
        }

        .wcb-qv-var-notice.visible {
            display: block !important;
        }

        /* ── Divider ── */
        .wcb-qv-divider {
            border: none !important;
            border-top: 1px solid #f1f5f9 !important;
            margin: 0.75rem 0 !important;
        }

        /* ── Actions ── */
        .wcb-qv-actions {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.5rem !important;
            margin-top: auto !important;
            padding-top: 0.5rem !important;
        }

        .wcb-qv-add-btn {
            width: 100% !important;
            background: #155DFD !important;
            color: #fff !important;
            border: none !important;
            border-radius: 10px !important;
            padding: 13px 24px !important;
            font-size: 0.92rem !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 4px 14px rgba(21, 93, 253, 0.25) !important;
            text-decoration: none !important;
            letter-spacing: 0.01em !important;
            font-family: 'Inter', system-ui, sans-serif !important;
        }

        .wcb-qv-add-btn:hover {
            background: #1249d6 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 20px rgba(21, 93, 253, 0.35) !important;
        }

        .wcb-qv-add-btn:active {
            transform: translateY(0) !important;
        }

        .wcb-qv-add-btn.loading {
            opacity: 0.7 !important;
            pointer-events: none !important;
        }

        .wcb-qv-add-btn.added {
            background: #16a34a !important;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.3) !important;
        }

        .wcb-qv-add-btn--disabled {
            background: #94a3b8 !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
        }

        .wcb-qv-add-btn--disabled:hover {
            background: #94a3b8 !important;
            transform: none !important;
            box-shadow: none !important;
        }

        /* ── Stock Label ── */
        .wcb-qv-in-stock {
            font-size: 0.78rem !important;
            color: #16a34a !important;
            font-weight: 600 !important;
            display: flex !important;
            align-items: center !important;
            gap: 4px !important;
        }

        .wcb-qv-out-stock {
            font-size: 0.78rem !important;
            color: #dc2626 !important;
            font-weight: 600 !important;
            display: flex !important;
            align-items: center !important;
            gap: 4px !important;
        }

        /* ── Stock Row (below variants) ── */
        .wcb-qv-stock-row {
            min-height: 20px !important;
            margin: 0.35rem 0 0 !important;
        }

        /* ── Quantity Stepper ── */
        .wcb-qv-qty {
            display: flex !important;
            align-items: center !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            overflow: hidden !important;
            flex-shrink: 0 !important;
            height: 46px !important;
        }

        .wcb-qv-qty__btn {
            width: 38px !important;
            height: 100% !important;
            border: none !important;
            background: #f8fafc !important;
            cursor: pointer !important;
            font-size: 1.1rem !important;
            color: #334155 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.12s ease !important;
            padding: 0 !important;
            user-select: none !important;
        }

        .wcb-qv-qty__btn:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }

        .wcb-qv-qty__btn:active {
            background: #cbd5e1 !important;
        }

        .wcb-qv-qty__val {
            width: 36px !important;
            text-align: center !important;
            font-size: 0.92rem !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            font-family: 'Inter', system-ui, sans-serif !important;
            user-select: none !important;
        }

        /* ── Cart Row (qty + btn side by side) ── */
        .wcb-qv-cart-row {
            display: flex !important;
            gap: 10px !important;
            align-items: center !important;
            width: 100% !important;
        }

        .wcb-qv-cart-row .wcb-qv-add-btn {
            flex: 1 !important;
        }

        /* ── Full Link (Secondary button) ── */
        .wcb-qv-full-link {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            font-size: 0.85rem !important;
            color: #334155 !important;
            text-decoration: none !important;
            margin-top: 0.65rem !important;
            font-weight: 600 !important;
            transition: all 0.15s ease !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            padding: 11px 20px !important;
            background: rgba(255, 255, 255, 0.92) !important;
            width: 100% !important;
            text-align: center !important;
        }

        .wcb-qv-full-link:hover {
            border-color: #155DFD !important;
            color: #155DFD !important;
            background: rgba(21, 93, 253, 0.03) !important;
        }

        /* ── Responsive ── */
        @media (max-width: 700px) {
            .wcb-qv-overlay {
                padding: 0.75rem !important;
            }

            .wcb-qv-modal {
                border-radius: 16px !important;
                height: min(80vh, calc(100dvh - 2.5rem)) !important;
                max-height: min(80vh, calc(100dvh - 2.5rem)) !important;
            }

            .wcb-qv-top {
                grid-template-columns: 1fr !important;
            }

            #wcb-qv-content {
                max-height: none !important;
            }

            .wcb-qv-left {
                max-height: none !important;
                overflow-y: visible !important;
                border-radius: 16px 16px 0 0 !important;
                border-right: none !important;
                padding: 1.25rem !important;
            }

            .wcb-qv-right {
                border-radius: 0 !important;
                max-height: none !important;
                overflow-y: visible !important;
                padding: 1.25rem !important;
            }

            .wcb-qv-main-img-wrap {
                min-height: 200px !important;
                max-height: 260px !important;
            }

            .wcb-qv-title {
                font-size: 1.15rem !important;
            }

            .wcb-qv-price-current {
                font-size: 1.35rem !important;
            }

            .wcb-qv-cart-row {
                flex-direction: column !important;
            }

            .wcb-qv-qty {
                width: 100% !important;
                justify-content: center !important;
            }
        }
    </style>
    <?php
}, 1);

/* ============================================================
   WOOCOMMERCE HELPERS
   ============================================================ */


/** Get WooCommerce cart item count */
function wcb_cart_count()
{
    if (function_exists('WC') && WC()->cart) {
        return WC()->cart->get_cart_contents_count();
    }
    return 0;
}

/** AJAX update cart count fragment */
function wcb_cart_count_fragment($fragments)
{
    $fragments['.wcb-header__cart-count'] = '<span class="wcb-header__cart-count">' . wcb_cart_count() . '</span>';
    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'wcb_cart_count_fragment');

/**
 * Nonce partilhado para AJAX público de leitura (live search, quick view, gift progress).
 */
function wcb_verify_public_ajax_request()
{
    $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'wcb_public_ajax')) {
        wp_send_json_error(['message' => 'invalid_nonce'], 403);
    }
}

/**
 * Limite simples por IP para reduzir abuso de admin-ajax.
 *
 * @param string $bucket Identificador da action.
 * @param int    $max    Máximo de pedidos por janela de 10 minutos.
 */
function wcb_rate_limit_public_ajax($bucket, $max = 120)
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    if ($ip === '') {
        return;
    }
    $key = 'wcb_rl_' . sanitize_key($bucket) . '_' . md5($ip);
    $n = (int) get_transient($key);
    if ($n >= $max) {
        wp_send_json_error(['message' => 'rate_limited'], 429);
    }
    set_transient($key, $n + 1, 10 * MINUTE_IN_SECONDS);
}

/** Change WooCommerce products per page */
function wcb_products_per_page()
{
    return 12;
}
add_filter('loop_shop_per_page', 'wcb_products_per_page');

/** Change WooCommerce product columns */
function wcb_product_columns()
{
    return 3;
}
add_filter('loop_shop_columns', 'wcb_product_columns');

/** Remove default result_count and catalog_ordering (handled by custom template) */
function wcb_remove_shop_loop_hooks()
{
    remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
}
add_action('wp', 'wcb_remove_shop_loop_hooks');

/* ============================================================
   LOGO HELPERS
   ============================================================ */
function wcb_get_logo()
{
    if (has_custom_logo()) {
        the_custom_logo();
    } else {
        echo '<a href="' . esc_url(home_url('/')) . '" class="wcb-header__logo-text">';
        echo 'White <span>Cloud</span>';
        echo '</a>';
    }
}

function wcb_custom_logo_remove_dimensions($html)
{
    $html = preg_replace('/(width|height)="\d*"\s/', '', $html);
    return $html;
}
add_filter('get_custom_logo', 'wcb_custom_logo_remove_dimensions');

/* ============================================================
   SEARCH FORM OVERRIDE
   ============================================================ */
function wcb_search_form($form)
{
    $form = '<form role="search" method="get" class="wcb-header__search-form" action="' . esc_url(home_url('/')) . '">';
    $form .= '<input type="search" class="wcb-header__search-input" placeholder="' . esc_attr__('Buscar produtos...', 'wcb-theme') . '" value="' . get_search_query() . '" name="s" />';
    if (class_exists('WooCommerce')) {
        $form .= '<input type="hidden" name="post_type" value="product" />';
    }
    $form .= '<button type="submit" class="wcb-header__search-btn" aria-label="Buscar">';
    $form .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>';
    $form .= '</button>';
    $form .= '</form>';
    return $form;
}
add_filter('get_search_form', 'wcb_search_form');

/* ============================================================
   BODY CLASSES & EXCERPT
   ============================================================ */
function wcb_body_classes($classes)
{
    $classes[] = 'wcb-theme';
    if (is_front_page())
        $classes[] = 'wcb-home';
    return $classes;
}
add_filter('body_class', 'wcb_body_classes');

function wcb_excerpt_length($length)
{
    return 20;
}
add_filter('excerpt_length', 'wcb_excerpt_length');

/* ============================================================
   WISHLIST — Limpar e renomear para "Favoritos"
   ============================================================ */
add_filter('woocommerce_account_menu_items', function ($items) {
    unset($items['wishlist'], $items['tinvwl-wishlist'], $items['ti-wishlist']);
    foreach ($items as $key => $label) {
        if (stripos($key, 'alg') !== false || stripos($label, 'wishlist') !== false) {
            $items[$key] = 'Favoritos';
        }
    }
    return $items;
}, 99);

add_action('init', function () {
    remove_action('woocommerce_before_shop_loop_item', array('Alg_WC_Wish_List_Toggle_Btn', 'show_thumb_btn'), 9);
    for ($i = 1; $i <= 20; $i++) {
        remove_action('woocommerce_before_shop_loop_item', array('Alg_WC_Wish_List_Toggle_Btn', 'show_thumb_btn'), $i);
    }
}, 20);

/* ============================================================
   SHOP: JS inline para toggle da sidebar mobile
   ============================================================ */
add_action('wp_footer', function () {
    if (!is_shop() && !is_product_category() && !is_product_tag())
        return;
    ?>
    <script>
        (function () {
            /* ── Sidebar toggle ─────────────────────────────────── */
            var toggle = document.getElementById('wcb-filter-toggle');
            var sidebar = document.getElementById('wcb-shop-sidebar');
            var overlay = document.getElementById('wcb-sidebar-overlay');
            var close = document.getElementById('wcb-sidebar-close');
            if (!toggle || !sidebar) return;

            function openSidebar() {
                sidebar.classList.add('is-open');
                overlay && overlay.classList.add('is-visible');
                document.body.classList.add('wcb-sidebar-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
            function closeSidebar() {
                sidebar.classList.remove('is-open');
                overlay && overlay.classList.remove('is-visible');
                document.body.classList.remove('wcb-sidebar-open');
                toggle.setAttribute('aria-expanded', 'false');
            }

            toggle.addEventListener('click', openSidebar);
            overlay && overlay.addEventListener('click', closeSidebar);
            close && close.addEventListener('click', closeSidebar);

            document.addEventListener('click', function (e) {
                if (e.target && e.target.classList.contains('wpf-btn')) closeSidebar();
            });

            /* ── Back to top ────────────────────────────────────── */
            var btt = document.getElementById('wcb-back-to-top');
            if (btt) {
                window.addEventListener('scroll', function () {
                    btt.classList.toggle('is-visible', window.scrollY > 800);
                }, { passive: true });
                btt.addEventListener('click', function () {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
        })();
    </script>
    <?php
});

/* ============================================================
   SIDE CART — Qty Stepper
   ============================================================ */
/* O stepper e subtotal agora são renderizados diretamente nos templates
   product.php e product-card.php, não mais via hook. */

function wcb_enqueue_qty_stepper_script()
{
    if (!wp_script_is('xoo-wsc-main-js', 'enqueued'))
        return;
    $script = file_get_contents(get_template_directory() . '/side-cart-qty.js');
    if ($script) {
        wp_add_inline_script('xoo-wsc-main-js', $script);
    }
}
add_action('wp_enqueue_scripts', 'wcb_enqueue_qty_stepper_script', 99);

/* ============================================================
   🎁 GIFT PROGRESS BAR — payload (AJAX + primeira pintura no carrinho em blocos)
   ============================================================ */

/**
 * Configuração da barra de brinde vs plugin MH Free Gifts (regras ativas e limiar mínimo).
 *
 * @return array{active: bool, threshold: float}
 */
function wcb_mh_gift_incentive_config()
{
    $default_threshold = 500.0;
    if (! class_exists('MHFGFWC_DB') || ! method_exists('MHFGFWC_DB', 'get_active_rules')) {
        return array(
            'active'    => true,
            'threshold' => $default_threshold,
        );
    }
    $rules = MHFGFWC_DB::get_active_rules();
    if (empty($rules)) {
        return array(
            'active'    => false,
            'threshold' => $default_threshold,
        );
    }
    $candidates = array();
    foreach ((array) $rules as $row) {
        $r  = is_object($row) ? get_object_vars($row) : (array) $row;
        $op = isset($r['subtotal_operator']) ? trim((string) $r['subtotal_operator']) : '';
        if ('' === $op || ! isset($r['subtotal_amount']) || null === $r['subtotal_amount'] || '' === $r['subtotal_amount']) {
            continue;
        }
        if (! in_array($op, array('>=', '>'), true)) {
            continue;
        }
        $amt = (float) $r['subtotal_amount'];
        if ($amt > 0) {
            $candidates[] = $amt;
        }
    }
    if (empty($candidates)) {
        return array(
            'active'    => true,
            'threshold' => $default_threshold,
        );
    }

    return array(
        'active'    => true,
        'threshold' => (float) min($candidates),
    );
}

/**
 * Mesmo shape que wp_send_json no AJAX wcb_gift_progress_data.
 *
 * @return array<string, mixed>
 */
function wcb_gift_progress_payload()
{
    $mh_cfg           = wcb_mh_gift_incentive_config();
    $gift_threshold   = (float) $mh_cfg['threshold'];
    $gift_bar_active  = (bool) $mh_cfg['active'];

    if (!function_exists('WC') || !WC()->cart) {
        return array(
            'subtotal' => 0,
            'remaining' => $gift_bar_active ? $gift_threshold : 0,
            'progress' => 0,
            'unlocked' => false,
            'gift_text' => '',
            'threshold' => $gift_threshold,
            'gift_incentive_active' => $gift_bar_active,
            'ship_remaining' => 0,
            'ship_progress' => 0,
            'ship_unlocked' => false,
            'applied_coupons' => array(),
            'coupon_discount_by_code' => array(),
        );
    }

    $free_ship_threshold = wcb_get_free_ship_threshold();
    $subtotal = 0;
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (!empty($cart_item['mhfgfwc_free_gift'])) {
            continue;
        }
        $subtotal += (float) $cart_item['line_subtotal'];
    }

    $ship_remaining = max(0, $free_ship_threshold - $subtotal);
    $ship_progress = $subtotal > 0 ? min(100, ($subtotal / $free_ship_threshold) * 100) : 0;
    $ship_unlocked = $ship_remaining <= 0 && $subtotal > 0;

    if (! $gift_bar_active) {
        return array(
            'subtotal' => $subtotal,
            'remaining' => 0,
            'progress' => 0,
            'unlocked' => false,
            'gift_text' => '',
            'threshold' => $gift_threshold,
            'gift_incentive_active' => false,
            'ship_remaining' => $ship_remaining,
            'ship_progress' => round($ship_progress, 1),
            'ship_unlocked' => $ship_unlocked,
            'applied_coupons' => array_values(WC()->cart->get_applied_coupons()),
            'coupon_discount_by_code' => function_exists('wcb_side_cart_coupon_discount_by_code') ? wcb_side_cart_coupon_discount_by_code() : array(),
        );
    }

    $remaining = max(0, $gift_threshold - $subtotal);
    $progress = $subtotal > 0 && $gift_threshold > 0 ? min(100, ($subtotal / $gift_threshold) * 100) : 0;
    $unlocked = $remaining <= 0 && $subtotal > 0;

    if ($subtotal <= 0) {
        $gift_text = __('Adicione produtos para ganhar um <strong class="wcb-incentive-accent">brinde grátis</strong>!', 'wcb-theme');
    } elseif ($unlocked) {
        $gift_text = __('<strong class="wcb-incentive-accent">Parabéns!</strong> Você ganhou um <strong class="wcb-incentive-accent">brinde</strong>!', 'wcb-theme');
    } else {
        $gift_text = sprintf(
            /* translators: %s: formatted money amount */
            __('Faltam <strong class="wcb-incentive-accent">R$ %s</strong> para <strong class="wcb-incentive-accent">ganhar um brinde!</strong>', 'wcb-theme'),
            number_format($remaining, 2, ',', '.')
        );
    }

    return array(
        'subtotal' => $subtotal,
        'remaining' => $remaining,
        'progress' => round($progress, 1),
        'unlocked' => $unlocked,
        'gift_text' => $gift_text,
        'threshold' => $gift_threshold,
        'gift_incentive_active' => true,
        'ship_remaining' => $ship_remaining,
        'ship_progress' => round($ship_progress, 1),
        'ship_unlocked' => $ship_unlocked,
        'applied_coupons' => array_values(WC()->cart->get_applied_coupons()),
        'coupon_discount_by_code' => function_exists('wcb_side_cart_coupon_discount_by_code') ? wcb_side_cart_coupon_discount_by_code() : array(),
    );
}

function wcb_gift_progress_ajax()
{
    wcb_verify_public_ajax_request();
    wcb_rate_limit_public_ajax('gift_prog', 150);
    wp_send_json(wcb_gift_progress_payload());
}
add_action('wp_ajax_wcb_gift_progress_data', 'wcb_gift_progress_ajax');
add_action('wp_ajax_nopriv_wcb_gift_progress_data', 'wcb_gift_progress_ajax');

/* ============================================================
   🚚 FRETE GRÁTIS — AJAX handler para cálculo de CEP
   ============================================================ */
function wcb_calc_shipping_ajax()
{
    if (!check_ajax_referer('wcb_calc_shipping', 'nonce', false)) {
        wp_send_json_error(['message' => 'invalid_nonce'], 403);
        return;
    }
    wcb_rate_limit_public_ajax('calc_ship', 100);

    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error('Carrinho não disponível');
        return;
    }

    $postcode = isset($_POST['postcode']) ? wcb_normalize_cep_digits(sanitize_text_field(wp_unslash($_POST['postcode']))) : '';
    if (strlen($postcode) < 8) {
        wp_send_json_error('CEP inválido');
        return;
    }

    // ── 1. Cache do resultado final por CEP + hash do carrinho ────────────────
    // Se o mesmo CEP for calculado com o mesmo carrinho, retorna na hora (sem AJAX)
    $cart_hash  = WC()->cart->get_cart_hash();
    $cache_key  = 'wcb_ship4_' . $postcode . '_' . substr($cart_hash, 0, 8);
    $cached     = get_transient($cache_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    // ── 2. ViaCEP — com cache de 30 dias por CEP (evita chamada externa repetida) ──
    $cep_cache_key = 'wcb_viacep_' . $postcode;
    $cep_data      = get_transient($cep_cache_key);

    $state = 'SP'; // fallback
    $city  = '';

    if ($cep_data !== false) {
        // Cache hit — sem chamada à API
        $state = $cep_data['state'] ?? 'SP';
        $city  = $cep_data['city']  ?? '';
    } else {
        // Cache miss — consulta ViaCEP uma única vez
        $viacep = wp_remote_get("https://viacep.com.br/ws/{$postcode}/json/", array('timeout' => 5));
        if (!is_wp_error($viacep) && wp_remote_retrieve_response_code($viacep) === 200) {
            $data  = json_decode(wp_remote_retrieve_body($viacep), true);
            $state = !empty($data['uf'])        ? strtoupper($data['uf'])  : 'SP';
            $city  = !empty($data['localidade']) ? $data['localidade']      : '';
        }
        // Salva no cache por 30 dias
        set_transient($cep_cache_key, array('state' => $state, 'city' => $city), 30 * DAY_IN_SECONDS);
    }

    // ── 3. Define destino e monta package ────────────────────────────────────
    WC()->customer->set_shipping_postcode($postcode);
    WC()->customer->set_shipping_country('BR');
    WC()->customer->set_shipping_state($state);
    WC()->customer->set_shipping_city($city);
    WC()->customer->save();

    $cart_contents = array();
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if (!empty($cart_item['mhfgfwc_free_gift'])) continue;
        $cart_contents[$cart_item_key] = $cart_item;
    }

    $package = array(
        'contents'        => $cart_contents,
        'contents_cost'   => WC()->cart->get_cart_contents_total(),
        'applied_coupons' => WC()->cart->get_applied_coupons(),
        'destination'     => array(
            'country'   => 'BR',
            'state'     => $state,
            'postcode'  => $postcode,
            'city'      => $city,
            'address'   => '',
            'address_2' => '',
        ),
    );

    // ── 4. Cálculo único e direto (sem triple-call) ───────────────────────────
    $calculated = WC()->shipping()->calculate_shipping_for_package($package);
    $rates      = array();

    if (!empty($calculated['rates'])) {
        foreach ($calculated['rates'] as $rate_id => $rate) {
            $rates[] = array(
                'id'     => sanitize_text_field($rate_id),
                'label'  => wp_strip_all_tags($rate->get_label()),
                'cost'   => (float) $rate->get_cost(),
                'cost_f' => 'R$ ' . number_format((float) $rate->get_cost(), 2, ',', '.'),
                'free'   => (float) $rate->get_cost() === 0.0,
                'eta'    => (string) apply_filters('wcb_side_cart_shipping_rate_eta', '', $rate, $rate_id),
            );
        }
    }

    if (empty($rates)) {
        wp_send_json_error('Nenhuma opção de frete disponível para este CEP.');
        return;
    }

    // Ordena do mais barato para o mais caro
    usort($rates, function ($a, $b) { return $a['cost'] <=> $b['cost']; });

    // ── 5. Salva resultado no cache por 2 horas ───────────────────────────────
    set_transient($cache_key, $rates, 2 * HOUR_IN_SECONDS);

    wp_send_json_success($rates);
}
add_action('wp_ajax_wcb_calc_shipping', 'wcb_calc_shipping_ajax');
add_action('wp_ajax_nopriv_wcb_calc_shipping', 'wcb_calc_shipping_ajax');

/**
 * Tenta obter texto de prazo a partir dos metadados da taxa (plugins de frete).
 *
 * @param string $eta
 * @param object $rate WC_Shipping_Rate
 * @param string $rate_id
 */
function wcb_side_cart_shipping_rate_eta_from_meta($eta, $rate, $rate_id)
{
    if ($eta !== '' && $eta !== null) {
        return $eta;
    }
    if (!is_object($rate) || !method_exists($rate, 'get_meta_data')) {
        return '';
    }
    foreach ($rate->get_meta_data() as $meta) {
        if (!is_object($meta) || !method_exists($meta, 'get_key')) {
            continue;
        }
        $key = strtolower((string) $meta->get_key());
        if (in_array($key, array('delivery_time', 'prazo_entrega', 'prazo', 'deadline', 'delivery_days'), true)) {
            return wp_strip_all_tags((string) $meta->get_value());
        }
    }

    return '';
}

add_filter('wcb_side_cart_shipping_rate_eta', 'wcb_side_cart_shipping_rate_eta_from_meta', 10, 3);

/* ============================================================
   SIDE CART — Totais estendidos (ofertas + cupom + total)
   ============================================================ */

/**
 * Subtotal exibido no mini carrinho: soma das linhas pagas (line_subtotal ± tax conforme exibição do carrinho).
 * Ignora brindes (mhfgfwc_free_gift). Alinha com a soma dos itens antes de cupom de carrinho.
 *
 * Filtro: `wcb_cart_side_subtotal_from_lines` (float $sum, WC_Cart $cart).
 *
 * @param WC_Cart $cart Carrinho.
 * @return float
 */
function wcb_cart_side_subtotal_from_lines($cart)
{
    $sum = 0.0;
    foreach ($cart->get_cart() as $item) {
        if (!empty($item['mhfgfwc_free_gift'])) {
            continue;
        }
        if ($cart->display_prices_including_tax()) {
            $sum += (float) $item['line_subtotal'] + (float) $item['line_subtotal_tax'];
        } else {
            $sum += (float) $item['line_subtotal'];
        }
    }
    $sum = round($sum, wc_get_price_decimals());

    return (float) apply_filters('wcb_cart_side_subtotal_from_lines', $sum, $cart);
}

/**
 * Economia total em ofertas (preço de tabela − preço ativo) × quantidade.
 * Usa `display_context` => `cart` para o mesmo modo de imposto/exibição do carrinho (evita divergência com a loja).
 *
 * Ignora brindes (mhfgfwc_free_gift). Filtro: `wcb_cart_promo_savings_total` (float $sum, WC_Cart $cart).
 *
 * @return float
 */
function wcb_cart_promo_savings_total()
{
    if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
        return 0.0;
    }
    $cart = WC()->cart;
    $sum = 0.0;
    foreach ($cart->get_cart() as $cart_item_key => $item) {
        if (!empty($item['mhfgfwc_free_gift'])) {
            continue;
        }
        $product = isset($item['data']) ? $item['data'] : null;
        if (!$product || !is_a($product, 'WC_Product')) {
            continue;
        }
        $qty = isset($item['quantity']) ? max(0, (int) $item['quantity']) : 0;
        if ($qty < 1) {
            continue;
        }

        $regular_raw = $product->get_regular_price('edit');
        if (($regular_raw === '' || $regular_raw === null) && $product->is_type('variation')) {
            $parent = wc_get_product($product->get_parent_id());
            if ($parent && is_a($parent, 'WC_Product')) {
                $regular_raw = $parent->get_regular_price('edit');
            }
        }
        if ($regular_raw === '' || $regular_raw === null) {
            continue;
        }

        $price_args = array(
            'display_context' => 'cart',
            'qty' => 1,
        );
        $regular = (float) wc_get_price_to_display($product, array_merge($price_args, array('price' => (string) $regular_raw)));
        $current = (float) wc_get_price_to_display($product, $price_args);
        if ($regular <= $current) {
            continue;
        }
        $sum += ($regular - $current) * $qty;
    }
    $sum = round($sum, wc_get_price_decimals());

    return (float) apply_filters('wcb_cart_promo_savings_total', $sum, $cart);
}

/**
 * Desativa a linha "savings" do Side Cart (duplicava wcb_promo com cálculo menos preciso).
 */
function wcb_xoo_wsc_disable_plugin_savings($savings)
{
    return 0.0;
}

add_filter('xoo_wsc_cart_savings', 'wcb_xoo_wsc_disable_plugin_savings', 99);

/**
 * Remove número inicial do título do header quando o badge do carrinho já mostra a quantidade (evita "2 2 SEU CARRINHO").
 *
 * @param array<string, mixed> $args
 * @return array<string, mixed>
 */
function wcb_xoo_wsc_cart_header_heading_dedupe($args)
{
    if (empty($args['heading']) || empty($args['showBasket'])) {
        return $args;
    }
    $args['heading'] = preg_replace('/^\s*\d+\s+/u', '', (string) $args['heading']);

    return $args;
}

add_filter('xoo_wsc_cart_header_args', 'wcb_xoo_wsc_cart_header_heading_dedupe', 15);

/**
 * Mini carrinho — ordem (UX/checkout): Subtotal → Cupom → economia em ofertas (informativo) → Frete → taxas → impostos → Total.
 *
 * @param array<string, array{label: string, value: string, action?: string}> $totals
 */
function wcb_xoo_wsc_cart_totals_discounts($totals)
{
    if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
        return $totals;
    }

    $cart = WC()->cart;
    $cart->calculate_totals();

    unset($totals['savings']);

    $sub_label = __('Subtotal', 'wcb-theme');
    if (!empty($totals['subtotal']['label'])) {
        $sub_label = wp_strip_all_tags((string) $totals['subtotal']['label']);
    }

    $out = array();

    $subtotal_raw = wcb_cart_side_subtotal_from_lines($cart);
    $out['subtotal'] = array(
        'label' => $sub_label,
        'value' => wc_price($subtotal_raw),
    );

    $coupon_disc = (float) $cart->get_discount_total();
    if ($coupon_disc > 0.00001) {
        $out['wcb_coupon'] = array(
            'label' => __('Cupom', 'wcb-theme'),
            'value' => '-' . wc_price($coupon_disc),
            'action' => 'less',
        );
    }

    $promo = wcb_cart_promo_savings_total();
    if ($promo > 0.00001) {
        $out['wcb_promo'] = array(
            'label' => __('Você economizou', 'wcb-theme'),
            'value' => '-' . wc_price($promo),
            'action' => 'less',
        );
    }

    if ($cart->needs_shipping() && $cart->show_shipping()) {
        if ($cart->has_calculated_shipping()) {
            $ship = (float) $cart->get_shipping_total() + (float) $cart->get_shipping_tax();
            if ($ship > 0.00001) {
                $out['wcb_shipping'] = array(
                    'label' => __('Frete', 'wcb-theme'),
                    'value' => wc_price($ship),
                );
            } else {
                $out['wcb_shipping'] = array(
                    'label' => __('Frete', 'wcb-theme'),
                    'value' => __('Grátis', 'wcb-theme'),
                );
            }
        }
    }

    foreach ($cart->get_fees() as $fee) {
        if (!is_object($fee)) {
            continue;
        }
        $fid = !empty($fee->id) ? sanitize_key((string) $fee->id) : 'taxa';
        $fee_key = 'wcb_fee_' . $fid;
        while (isset($out[$fee_key])) {
            $fee_key .= '_';
        }
        $fee_total = 0.0;
        if (isset($fee->total) && $fee->total !== '' && $fee->total !== null) {
            $fee_total = (float) $fee->total;
        } else {
            $fee_total = (float) ($fee->amount ?? 0);
            if (!empty($fee->tax_data) && is_array($fee->tax_data)) {
                $fee_total += (float) array_sum($fee->tax_data);
            } elseif (isset($fee->tax) && $fee->tax !== '' && $fee->tax !== null) {
                $fee_total += (float) $fee->tax;
            }
        }
        $fname = isset($fee->name) ? wp_strip_all_tags((string) $fee->name) : __('Taxa', 'wcb-theme');
        $out[$fee_key] = array(
            'label' => $fname,
            'value' => wc_price($fee_total),
        );
    }

    if (wc_tax_enabled() && !$cart->display_prices_including_tax()) {
        $tax_total = (float) $cart->get_total_tax();
        if ($tax_total > 0.00001) {
            $out['wcb_tax'] = array(
                'label' => __('Impostos', 'wcb-theme'),
                'value' => wc_price($tax_total),
            );
        }
    }

    // Total a pagar: get_cart_total() no WC é só o subtotal dos itens (após descontos), sem frete.
    // Usar get_total('view') = total final do carrinho (itens − cupom + frete + taxas), como no checkout.
    $out['wcb_total'] = array(
        'label' => __('Valor total:', 'wcb-theme'),
        'value' => $cart->get_total('view'),
    );

    return apply_filters('wcb_xoo_wsc_cart_totals', $out, $cart, $totals);
}

add_filter('xoo_wsc_cart_totals', 'wcb_xoo_wsc_cart_totals_discounts', 20);

/* ============================================================
   SIDE CART — Cupom + escolha de frete (AJAX → fragments WC)
   ============================================================ */

/**
 * Preço formatado em texto puro (UTF-8), sem HTML nem entidades — seguro para innerHTML via escHtml no JS.
 */
function wcb_side_cart_price_plain($amount)
{
    if (!function_exists('wc_price')) {
        return '';
    }
    $html = wc_price($amount);
    $text = strip_tags($html);
    return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Rótulo do desconto conforme o cupom (percentual ou valor fixo definidos no admin).
 * Se o tipo não for valor explícito, usa o desconto efetivo do carrinho.
 *
 * @param string               $code
 * @param array<string, float> $discount_totals
 */
function wcb_side_cart_coupon_discount_label_for_code($code, array $discount_totals)
{
    $applied = isset($discount_totals[$code]) ? (float) $discount_totals[$code] : 0.0;

    $coupon = new WC_Coupon($code);
    if (!$coupon->get_id()) {
        return $applied > 0 ? wcb_side_cart_price_plain(-$applied) : '';
    }

    $dtype   = $coupon->get_discount_type();
    $defined = (float) $coupon->get_amount();

    switch ($dtype) {
        case 'percent':
            if ($defined > 0) {
                return trim(wc_format_decimal($defined, wc_get_price_decimals())) . '%';
            }
            break;

        case 'fixed_cart':
        case 'fixed_product':
            if ($defined > 0) {
                return wcb_side_cart_price_plain(-$defined);
            }
            break;
    }

    if ($applied > 0) {
        return wcb_side_cart_price_plain(-$applied);
    }

    return '';
}

/**
 * Mapa código do cupom → texto do desconto (definido no cupom ou fallback do carrinho).
 *
 * @return array<string, string>
 */
function wcb_side_cart_coupon_discount_by_code()
{
    if (!function_exists('WC') || !WC()->cart) {
        return array();
    }
    $out = array();
    $discount_totals = WC()->cart->get_coupon_discount_totals();
    foreach (WC()->cart->get_applied_coupons() as $code) {
        $out[$code] = wcb_side_cart_coupon_discount_label_for_code($code, $discount_totals);
    }
    return $out;
}

/**
 * Mesmo payload de WC_AJAX::get_refreshed_fragments(), com campos extras para o JS do tema.
 *
 * @param array<string, mixed> $extra
 */
function wcb_send_side_cart_fragments_json(array $extra = array())
{
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(array('message' => __('Carrinho indisponível.', 'wcb-theme')));
    }
    WC()->cart->calculate_totals();
    ob_start();
    woocommerce_mini_cart();
    $mini_cart = ob_get_clean();
    $fragments = apply_filters(
        'woocommerce_add_to_cart_fragments',
        array(
            'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
        )
    );
    $data = array_merge(
        array(
            'fragments' => $fragments,
            'cart_hash' => WC()->cart->get_cart_hash(),
            'applied_coupons' => WC()->cart->get_applied_coupons(),
            'coupon_discount_by_code' => wcb_side_cart_coupon_discount_by_code(),
        ),
        $extra
    );
    wp_send_json($data);
}

/**
 * Mensagem de cupom em texto puro (sem HTML/entidades) para JSON e textContent no JS.
 *
 * @param string $html Notice HTML do WooCommerce.
 * @return string
 */
function wcb_strip_coupon_notice_for_display($html)
{
    $t = html_entity_decode(wp_strip_all_tags((string) $html, true), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return trim(preg_replace('/\s+/u', ' ', $t));
}

/**
 * Mensagens de erro de cupom em português (WooCommerce em inglês ou notices com entidades).
 *
 * @param string         $err      Mensagem (pode vir de Exception ou get_coupon_error).
 * @param int|string     $err_code Código WC_Coupon::E_*.
 * @param WC_Coupon|null $coupon   Instância quando disponível.
 * @return string
 */
function wcb_translate_woocommerce_coupon_error($err, $err_code, $coupon)
{
    if (!class_exists('WC_Coupon')) {
        return $err;
    }

    $e = is_numeric($err_code) ? (int) $err_code : 0;
    $is_c = is_object($coupon) && is_a($coupon, 'WC_Coupon');
    $code = $is_c ? $coupon->get_code() : '';

    $price_plain = static function ($amount) {
        if (!function_exists('wc_price')) {
            return '';
        }

        return wcb_strip_coupon_notice_for_display(wc_price((float) $amount));
    };

    switch ($e) {
        case WC_Coupon::E_WC_COUPON_EXPIRED:
            return $code !== ''
                ? sprintf(__('O cupom "%s" expirou.', 'wcb-theme'), $code)
                : __('Este cupom expirou.', 'wcb-theme');

        case WC_Coupon::E_WC_COUPON_NOT_EXIST:
            return $code !== ''
                ? sprintf(__('O cupom "%s" não existe ou não está disponível.', 'wcb-theme'), $code)
                : __('Este cupom não existe.', 'wcb-theme');

        case WC_Coupon::E_WC_COUPON_INVALID_FILTERED:
            return $code !== ''
                ? sprintf(__('O cupom "%s" não pode ser aplicado.', 'wcb-theme'), $code)
                : __('Cupom inválido.', 'wcb-theme');

        case WC_Coupon::E_WC_COUPON_INVALID_REMOVED:
            return $code !== ''
                ? sprintf(__('O cupom "%s" não é válido e foi removido.', 'wcb-theme'), $code)
                : __('O cupom não é válido e foi removido.', 'wcb-theme');

        case WC_Coupon::E_WC_COUPON_NOT_YOURS_REMOVED:
            return $code !== ''
                ? sprintf(__('Use um e-mail autorizado para o cupom "%s" (confira no checkout).', 'wcb-theme'), $code)
                : __('Este cupom não está disponível para a sua conta.', 'wcb-theme');

        case WC_Coupon::E_WC_COUPON_ALREADY_APPLIED:
            return $code !== ''
                ? sprintf(__('O cupom "%s" já está aplicado.', 'wcb-theme'), $code)
                : __('Este cupom já está aplicado.', 'wcb-theme');

        case WC_Coupon::E_WC_COUPON_ALREADY_APPLIED_INDIV_USE_ONLY:
            return $code !== ''
                ? sprintf(__('O cupom "%s" não pode ser usado junto com outros cupons.', 'wcb-theme'), $code)
                : __('Este cupom não pode ser combinado com outros.', 'wcb-theme');

        case WC_Coupon::E_WC_COUPON_USAGE_LIMIT_REACHED:
            return $code !== ''
                ? sprintf(__('O limite de uso do cupom "%s" foi atingido.', 'wcb-theme'), $code)
                : __('O limite de uso deste cupom foi atingido.', 'wcb-theme');

        case WC_Coupon::E_WC_COUPON_USAGE_LIMIT_COUPON_STUCK:
            return $code !== ''
                ? sprintf(__('O limite do cupom "%s" foi atingido. Se um pedido ficou pendente, confira em Minha conta.', 'wcb-theme'), $code)
                : __('Limite de uso do cupom atingido. Confira pedidos pendentes em Minha conta.', 'wcb-theme');

        case WC_Coupon::E_WC_COUPON_USAGE_LIMIT_COUPON_STUCK_GUEST:
            return $code !== ''
                ? sprintf(__('O limite do cupom "%s" foi atingido. Tente mais tarde ou fale conosco.', 'wcb-theme'), $code)
                : __('Limite de uso do cupom atingido. Tente mais tarde.', 'wcb-theme');

        case WC_Coupon::E_WC_COUPON_MIN_SPEND_LIMIT_NOT_MET:
            if ($is_c && $coupon->get_minimum_amount() > 0) {
                return sprintf(
                    __('O pedido mínimo para usar o cupom "%1$s" é %2$s.', 'wcb-theme'),
                    $code,
                    $price_plain($coupon->get_minimum_amount())
                );
            }
            break;

        case WC_Coupon::E_WC_COUPON_MAX_SPEND_LIMIT_MET:
            if ($is_c && $coupon->get_maximum_amount() > 0) {
                return sprintf(
                    __('O valor máximo do pedido para o cupom "%1$s" é %2$s.', 'wcb-theme'),
                    $code,
                    $price_plain($coupon->get_maximum_amount())
                );
            }
            break;

        case WC_Coupon::E_WC_COUPON_NOT_APPLICABLE:
            return $code !== ''
                ? sprintf(__('O cupom "%s" não vale para os itens do carrinho.', 'wcb-theme'), $code)
                : __('Este cupom não vale para o carrinho.', 'wcb-theme');

        case WC_Coupon::E_WC_COUPON_NOT_VALID_SALE_ITEMS:
            return $code !== ''
                ? sprintf(__('O cupom "%s" não vale para produtos em promoção.', 'wcb-theme'), $code)
                : __('Este cupom não vale para produtos em promoção.', 'wcb-theme');

        case WC_Coupon::E_WC_COUPON_EXCLUDED_PRODUCTS:
        case WC_Coupon::E_WC_COUPON_EXCLUDED_CATEGORIES:
            $plain = wcb_strip_coupon_notice_for_display($err);
            if (preg_match('/coupon "([^"]+)" is not applicable to the products:\s*(.+)$/iu', $plain, $m)) {
                return sprintf(
                    __('O cupom "%1$s" não vale para os produtos: %2$s', 'wcb-theme'),
                    $m[1],
                    trim($m[2], " \t\n\r\0\x0B.")
                );
            }
            if (preg_match('/coupon "([^"]+)" is not applicable to the categories:\s*(.+)$/iu', $plain, $m)) {
                return sprintf(
                    __('O cupom "%1$s" não vale para as categorias: %2$s', 'wcb-theme'),
                    $m[1],
                    trim($m[2], " \t\n\r\0\x0B.")
                );
            }
            break;

        case WC_Coupon::E_WC_COUPON_PLEASE_ENTER:
            return __('Digite um código de cupom.', 'wcb-theme');

        default:
            break;
    }

    return wcb_coupon_error_english_fallback($err, $code);
}

/**
 * Último recurso: padrões em inglês comuns do WooCommerce → PT.
 *
 * @param string $err  HTML ou texto.
 * @param string $code Código digitado (fallback).
 * @return string
 */
function wcb_coupon_error_english_fallback($err, $code = '')
{
    $plain = wcb_strip_coupon_notice_for_display($err);

    $map = array(
        '/^Coupon "([^"]+)" has expired\.?$/iu' => __('O cupom "%s" expirou.', 'wcb-theme'),
        '/^Coupon "([^"]+)" cannot be applied because it does not exist\.?$/iu' => __('O cupom "%s" não existe ou não está disponível.', 'wcb-theme'),
        '/^Coupon "([^"]+)" cannot be applied because it is not valid\.?$/iu' => __('O cupom "%s" não é válido.', 'wcb-theme'),
        '/^Coupon code "([^"]+)" already applied\!?$/iu' => __('O cupom "%s" já está aplicado.', 'wcb-theme'),
        '/^Invalid coupon\.?$/iu' => __('Cupom inválido.', 'wcb-theme'),
        '/^Coupon is not valid\.?$/iu' => __('Cupom inválido.', 'wcb-theme'),
    );

    foreach ($map as $pattern => $tpl) {
        if (preg_match($pattern, $plain, $m)) {
            return isset($m[1]) ? sprintf($tpl, $m[1]) : $tpl;
        }
    }

    return $plain !== '' ? $plain : ($code !== '' ? sprintf(__('Não foi possível aplicar o cupom "%s".', 'wcb-theme'), $code) : __('Não foi possível aplicar o cupom.', 'wcb-theme'));
}

add_filter('woocommerce_coupon_error', 'wcb_translate_woocommerce_coupon_error', 20, 3);

function wcb_side_cart_apply_coupon_ajax()
{
    check_ajax_referer('wcb_side_cart', 'nonce');
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(array('message' => __('Carrinho indisponível.', 'wcb-theme')));
    }
    $code = isset($_POST['coupon_code']) ? wc_format_coupon_code(sanitize_text_field(wp_unslash($_POST['coupon_code']))) : '';
    if ($code === '') {
        wp_send_json_error(array('message' => __('Informe um cupom.', 'wcb-theme')));
    }
    $ok = WC()->cart->apply_coupon($code);
    if (!$ok) {
        $errs = wc_get_notices('error');
        wc_clear_notices();
        if (!empty($errs)) {
            $msg = wcb_strip_coupon_notice_for_display($errs[0]['notice']);
        } else {
            $msg = __('Cupom inválido.', 'wcb-theme');
        }
        wp_send_json_error(array('message' => $msg));
    }
    wc_clear_notices();
    wcb_send_side_cart_fragments_json();
}

function wcb_side_cart_remove_coupon_ajax()
{
    check_ajax_referer('wcb_side_cart', 'nonce');
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(array('message' => __('Carrinho indisponível.', 'wcb-theme')));
    }
    $code = isset($_POST['coupon']) ? wc_format_coupon_code(sanitize_text_field(wp_unslash($_POST['coupon']))) : '';
    if ($code === '') {
        wp_send_json_error();
    }
    WC()->cart->remove_coupon($code);
    wc_clear_notices();
    wcb_send_side_cart_fragments_json();
}

function wcb_side_cart_set_shipping_method_ajax()
{
    check_ajax_referer('wcb_side_cart', 'nonce');
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(array('message' => __('Carrinho indisponível.', 'wcb-theme')));
    }
    $rate_id = isset($_POST['rate_id']) ? sanitize_text_field(wp_unslash($_POST['rate_id'])) : '';
    if ($rate_id === '') {
        wp_send_json_error(array('message' => __('Selecione uma opção de frete.', 'wcb-theme')));
    }
    WC()->session->set('chosen_shipping_methods', array('0' => $rate_id));
    wcb_send_side_cart_fragments_json();
}

add_action('wp_ajax_wcb_side_cart_apply_coupon', 'wcb_side_cart_apply_coupon_ajax');
add_action('wp_ajax_nopriv_wcb_side_cart_apply_coupon', 'wcb_side_cart_apply_coupon_ajax');
add_action('wp_ajax_wcb_side_cart_remove_coupon', 'wcb_side_cart_remove_coupon_ajax');
add_action('wp_ajax_nopriv_wcb_side_cart_remove_coupon', 'wcb_side_cart_remove_coupon_ajax');
add_action('wp_ajax_wcb_side_cart_set_shipping', 'wcb_side_cart_set_shipping_method_ajax');
add_action('wp_ajax_nopriv_wcb_side_cart_set_shipping', 'wcb_side_cart_set_shipping_method_ajax');


/* ============================================================
   🎁 GIFT PROGRESS BAR + 🚚 FRETE GRÁTIS BAR + 📦 CAMPO CEP
   Injetados via JS no side cart (sem fetch AJAX na abertura).
   Dados calculados no PHP e passados inline ao script.
   ============================================================ */
function wcb_gift_progress_bar()
{
    if (!wcb_is_side_cart_active()) {
        return;
    }
    if (!function_exists('WC') || !WC()->cart)
        return;

    // ── Dados do brinde
    $gift_threshold = 500;
    $subtotal = 0;
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (!empty($cart_item['mhfgfwc_free_gift']))
            continue;
        $subtotal += (float) $cart_item['line_subtotal'];
    }
    $gift_remaining = max(0, $gift_threshold - $subtotal);
    $gift_progress = $subtotal > 0 ? min(100, ($subtotal / $gift_threshold) * 100) : 0;
    $gift_unlocked = $gift_remaining <= 0 && $subtotal > 0;

    if ($subtotal <= 0) {
        $gift_text = __('Adicione produtos para ganhar um <strong class="wcb-incentive-accent">brinde grátis</strong>!', 'wcb-theme');
    } elseif ($gift_unlocked) {
        $gift_text = __('<strong class="wcb-incentive-accent">Parabéns!</strong> Você ganhou um <strong class="wcb-incentive-accent">brinde</strong>!', 'wcb-theme');
    } else {
        $gift_text = sprintf(
            /* translators: %s: formatted money amount */
            __('Faltam <strong class="wcb-incentive-accent">R$ %s</strong> para <strong class="wcb-incentive-accent">ganhar um brinde!</strong>', 'wcb-theme'),
            number_format($gift_remaining, 2, ',', '.')
        );
    }

    $wcb_sidecart_svg_gift = '<svg class="wcb-sc-bar-icon wcb-sc-bar-icon--gift" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M20 12v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 12V9a2 2 0 012-2h12a2 2 0 012 2v3" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 3v18" stroke="currentColor" stroke-width="1.65" stroke-linecap="round"/><path d="M8 7h8c0-2.2-1.8-4-4-4S8 4.8 8 7z" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    $wcb_sidecart_svg_truck = '<svg class="wcb-sc-bar-icon wcb-sc-bar-icon--truck" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 18h2" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7" cy="18" r="2" stroke="currentColor" stroke-width="1.65"/><circle cx="17" cy="18" r="2" stroke="currentColor" stroke-width="1.65"/><circle cx="7" cy="18" r="0.55" fill="currentColor"/><circle cx="17" cy="18" r="0.55" fill="currentColor"/></svg>';
    $wcb_sidecart_svg_ship_ok = '<svg class="wcb-sc-bar-icon wcb-sc-bar-icon--ship-ok" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 4L12 14.01l-3-3" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    // ── Dados de frete grátis
    $ship_threshold = 199;
    $ship_remaining = max(0, $ship_threshold - $subtotal);
    $ship_progress = $subtotal > 0 ? min(100, ($subtotal / $ship_threshold) * 100) : 0;
    $ship_unlocked = $ship_remaining <= 0 && $subtotal > 0;

    // ── CEP salvo do usuário logado
    $user_postcode = '';
    if (is_user_logged_in()) {
        $uid = get_current_user_id();
        $user_postcode = get_user_meta($uid, 'shipping_postcode', true);
        if (!$user_postcode) {
            $user_postcode = get_user_meta($uid, 'billing_postcode', true);
        }
        // Formata como 00000-000
        $user_postcode = preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', preg_replace('/\D/', '', $user_postcode));
    }

    $applied_coupons_arr = array_values(WC()->cart->get_applied_coupons());
    $coupon_discount_by_code = wcb_side_cart_coupon_discount_by_code();
    $chosen_ship_rate = '';
    if (WC()->session) {
        $ch = WC()->session->get('chosen_shipping_methods');
        if (is_array($ch) && isset($ch[0])) {
            $chosen_ship_rate = (string) $ch[0];
        }
    }
    ?>
    <script>
        (function () {
            var ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
            var wcbSideCartNonce = '<?php echo esc_js(wp_create_nonce('wcb_side_cart')); ?>';
            var giftThreshold = <?php echo (int) $gift_threshold; ?>;
            var shipThreshold = <?php echo (int) $ship_threshold; ?>;
            var userPostcode = '<?php echo esc_js($user_postcode); ?>';
            var chosenShipRateId = <?php echo wp_json_encode($chosen_ship_rate); ?>;

            var WCB_SVG_GIFT = <?php echo wp_json_encode($wcb_sidecart_svg_gift); ?>;
            var WCB_SVG_TRUCK = <?php echo wp_json_encode($wcb_sidecart_svg_truck); ?>;
            var WCB_SVG_SHIP_OK = <?php echo wp_json_encode($wcb_sidecart_svg_ship_ok); ?>;
            var WCB_KICKER_GIFT = <?php echo wp_json_encode(__('Brinde exclusivo', 'wcb-theme')); ?>;
            var WCB_KICKER_SHIP = <?php echo wp_json_encode(__('Envio gratuito', 'wcb-theme')); ?>;

            /* ── Dados iniciais do PHP (sem AJAX ao abrir) ── */
            var initData = {
                subtotal: <?php echo round($subtotal, 2); ?>,
                gift_progress: <?php echo round($gift_progress, 1); ?>,
                gift_unlocked: <?php echo $gift_unlocked ? 'true' : 'false'; ?>,
                gift_text: <?php echo wp_json_encode($gift_text); ?>,
                ship_progress: <?php echo round($ship_progress, 1); ?>,
                ship_remaining: <?php echo round($ship_remaining, 2); ?>,
                ship_unlocked: <?php echo $ship_unlocked ? 'true' : 'false'; ?>,
                appliedCoupons: <?php echo wp_json_encode($applied_coupons_arr); ?>,
                couponDiscountByCode: <?php echo wp_json_encode($coupon_discount_by_code); ?>
            };

            /* Evita rebuild completo do rail brinde/frete quando subtotal e nº de itens não mudaram. */
            var lastIncentiveBarsSig = '';

            function escAttr(s) {
                return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }
            function escHtml(s) {
                return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function applyWcFragments(data) {
                if (!data || !data.fragments) return;
                if (typeof jQuery !== 'undefined') {
                    jQuery.each(data.fragments, function (sel, html) {
                        jQuery(sel).replaceWith(html);
                    });
                    jQuery(document.body).trigger('wc_fragments_refreshed');
                }
            }

            function buildCouponBlock() {
                return '<div class="wcb-coupon-block" id="wcb-coupon-block">' +
                    '<div class="wcb-stack-card wcb-stack-card--coupon">' +
                    '<label class="wcb-stack-card__label" for="wcb-coupon-input"><?php echo esc_js(__('Cupom de desconto', 'wcb-theme')); ?></label>' +
                    '<div class="wcb-stack-card__body">' +
                    '<div class="wcb-checkout-stage wcb-coupon-stage" data-wcb-stage="coupon">' +
                    '<div class="wcb-coupon-layer wcb-coupon-layer--idle" id="wcb-coupon-layer-idle" aria-hidden="false">' +
                    '<div class="wcb-coupon-block__row">' +
                    '<input type="text" class="wcb-coupon-input" id="wcb-coupon-input" placeholder="<?php echo esc_js(__('Digite o código', 'wcb-theme')); ?>" autocomplete="off">' +
                    '<button type="button" class="wcb-coupon-apply" id="wcb-coupon-apply"><?php echo esc_js(__('Aplicar', 'wcb-theme')); ?></button></div></div>' +
                    '<div class="wcb-coupon-layer wcb-coupon-layer--applied" id="wcb-coupon-layer-applied" aria-hidden="true">' +
                    '<div class="wcb-coupon-applied-inner" id="wcb-coupon-applied-inner"></div></div></div></div>' +
                    '<div class="wcb-coupon-msg" id="wcb-coupon-msg" role="status"></div></div></div>';
            }

            function syncCouponStatusUi() {
                var block = document.getElementById('wcb-coupon-block');
                var idle = document.getElementById('wcb-coupon-layer-idle');
                var applied = document.getElementById('wcb-coupon-layer-applied');
                var inner = document.getElementById('wcb-coupon-applied-inner');
                if (!block || !idle || !applied || !inner) return;
                var codes = initData.appliedCoupons || [];
                if (!codes.length) {
                    inner.innerHTML = '';
                    block.classList.remove('wcb-coupon-block--applied');
                    idle.setAttribute('aria-hidden', 'false');
                    applied.setAttribute('aria-hidden', 'true');
                    return;
                }
                var discMap = initData.couponDiscountByCode || {};
                var parts = codes.map(function (c) {
                    var disc = discMap[c] || '';
                    var textHtml = '<strong>' + escHtml(c) + '</strong>';
                    if (disc) {
                        textHtml += ' <span class="wcb-coupon-summary__sep" aria-hidden="true">·</span> <span class="wcb-coupon-summary__amount">' + escHtml(disc) + '</span>';
                    }
                    return '<div class="wcb-coupon-summary__row">' +
                        '<span class="wcb-coupon-summary__ok" aria-hidden="true">✓</span>' +
                        '<span class="wcb-coupon-summary__text">' + textHtml + '</span>' +
                        '<button type="button" class="wcb-coupon-summary__rm wcb-coupon-status__rm" data-code="' + escAttr(c) + '" aria-label="<?php echo esc_js(__('Remover cupom', 'wcb-theme')); ?>"><?php echo esc_js(__('Remover', 'wcb-theme')); ?></button></div>';
                });
                inner.innerHTML = parts.join('');
                block.classList.add('wcb-coupon-block--applied');
                idle.setAttribute('aria-hidden', 'true');
                applied.setAttribute('aria-hidden', 'false');
            }

            function initCouponField() {
                var block = document.getElementById('wcb-coupon-block');
                if (!block || block.getAttribute('data-wcb-inited') === '1') return;
                block.setAttribute('data-wcb-inited', '1');
                var input = document.getElementById('wcb-coupon-input');
                var btn = document.getElementById('wcb-coupon-apply');
                var msgEl = document.getElementById('wcb-coupon-msg');
                if (!input || !btn) return;

                function setMsg(text, isErr) {
                    if (!msgEl) return;
                    msgEl.textContent = text || '';
                    msgEl.className = 'wcb-coupon-msg' + (isErr ? ' wcb-coupon-msg--error' : '');
                }

                function applyCoupon() {
                    var code = input.value.replace(/^\s+|\s+$/g, '');
                    if (!code) {
                        setMsg('<?php echo esc_js(__('Informe um cupom.', 'wcb-theme')); ?>', true);
                        return;
                    }
                    btn.disabled = true;
                    setMsg('');
                    var body = new URLSearchParams();
                    body.set('action', 'wcb_side_cart_apply_coupon');
                    body.set('nonce', wcbSideCartNonce);
                    body.set('coupon_code', code);
                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: body,
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                    }).then(function (r) { return r.json(); }).then(function (data) {
                        btn.disabled = false;
                        if (data.success === false) {
                            var m = (data.data && data.data.message) ? data.data.message : '<?php echo esc_js(__('Não foi possível aplicar.', 'wcb-theme')); ?>';
                            setMsg(m, true);
                            return;
                        }
                        if (data.applied_coupons) initData.appliedCoupons = data.applied_coupons;
                        if (data.coupon_discount_by_code) initData.couponDiscountByCode = data.coupon_discount_by_code;
                        input.value = '';
                        setMsg('');
                        syncCouponStatusUi();
                        applyWcFragments(data);
                    }).catch(function () {
                        btn.disabled = false;
                        setMsg('<?php echo esc_js(__('Erro de rede. Tente de novo.', 'wcb-theme')); ?>', true);
                    });
                }

                btn.addEventListener('click', applyCoupon);
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') applyCoupon();
                });

                block.addEventListener('click', function (e) {
                    var rm = e.target.closest('.wcb-coupon-chip__rm, .wcb-coupon-summary__rm, .wcb-coupon-status__rm');
                    if (!rm) return;
                    e.preventDefault();
                    var code = rm.getAttribute('data-code') || '';
                    if (!code) return;
                    rm.disabled = true;
                    var body = new URLSearchParams();
                    body.set('action', 'wcb_side_cart_remove_coupon');
                    body.set('nonce', wcbSideCartNonce);
                    body.set('coupon', code);
                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: body,
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                    }).then(function (r) { return r.json(); }).then(function (data) {
                        rm.disabled = false;
                        if (data.success === false) return;
                        if (data.applied_coupons) initData.appliedCoupons = data.applied_coupons;
                        if (data.coupon_discount_by_code) initData.couponDiscountByCode = data.coupon_discount_by_code;
                        syncCouponStatusUi();
                        applyWcFragments(data);
                    }).catch(function () { rm.disabled = false; });
                });

                syncCouponStatusUi();
            }

            function wcbNormRateId(id) {
                if (id === null || id === undefined || id === '') return '';
                return String(id);
            }

            function wcbShipRecommendedIndex(rates) {
                var i, fi = -1;
                for (i = 0; i < rates.length; i++) {
                    if (rates[i].free) return i;
                }
                return 0;
            }

            function buildShipModalRatesHtml(rates, selectedId) {
                var recIdx = wcbShipRecommendedIndex(rates);
                var hintEta = '<?php echo esc_js(__('Prazo confirmado na finalização do pedido', 'wcb-theme')); ?>';
                var html = '<ul class="wcb-ship-opt-list" role="radiogroup" aria-label="<?php echo esc_js(__('Opções de entrega', 'wcb-theme')); ?>">';
                rates.forEach(function (r, i) {
                    var isRec = i === recIdx;
                    var isSel = wcbNormRateId(r.id) === wcbNormRateId(selectedId);
                    var etaRaw = r.eta && String(r.eta).trim() ? String(r.eta).trim() : '';
                    var eta = escHtml(etaRaw || hintEta);
                    var freeCls = r.free ? ' wcb-ship-opt__card--free' : '';
                    var selCls = isSel ? ' is-selected' : '';
                    var priceInner = r.free
                        ? '<span class="wcb-ship-opt__price wcb-ship-opt__price--free"><span class="wcb-ship-opt__price-main"><?php echo esc_js(__('Grátis', 'wcb-theme')); ?></span><span class="wcb-ship-opt__price-sub"><?php echo esc_js(__('Economia total no frete', 'wcb-theme')); ?></span></span>'
                        : '<span class="wcb-ship-opt__price"><span class="wcb-ship-opt__price-main">' + escHtml(r.cost_f) + '</span></span>';
                    var badge = isRec
                        ? '<span class="wcb-ship-opt__badge">' + (r.free ? '<?php echo esc_js(__('Melhor opção', 'wcb-theme')); ?>' : '<?php echo esc_js(__('Recomendado', 'wcb-theme')); ?>') + '</span>'
                        : '';
                    html += '<li class="wcb-ship-opt" role="none">' +
                        '<button type="button" class="wcb-ship-opt__card' + freeCls + selCls + '" role="radio" aria-checked="' + (isSel ? 'true' : 'false') + '" data-rate-id="' + escAttr(String(r.id)) + '">' +
                        '<span class="wcb-ship-opt__radio" aria-hidden="true"></span>' +
                        '<span class="wcb-ship-opt__mid">' +
                        '<span class="wcb-ship-opt__title-row">' +
                        '<span class="wcb-ship-opt__name">' + escHtml(r.label) + '</span>' + badge +
                        '</span>' +
                        '<span class="wcb-ship-opt__eta">' + eta + '</span>' +
                        '</span>' +
                        priceInner +
                        '</button></li>';
                });
                html += '</ul>';
                return html;
            }

            var lastFetchedRates = null;
            var WCB_SHIP_SUM_SS = 'wcb_ship_sum_v1';

            function formatCepDisplay(digits) {
                var d = String(digits || '').replace(/\D/g, '');
                return d.length === 8 ? d.slice(0, 5) + '-' + d.slice(5) : d;
            }

            function closeShipModal() {
                var modal = document.getElementById('wcb-ship-modal');
                if (!modal) return;
                modal.hidden = true;
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('wcb-ship-modal-open');
                var cepB = document.getElementById('wcb-cep-block');
                if (cepB) cepB.classList.remove('wcb-cep-block--loading');
            }

            var wcbShipApplying = false;

            function ensureShipModal() {
                var m = document.getElementById('wcb-ship-modal');
                if (m && !document.getElementById('wcb-ship-modal-continue')) {
                    m.remove();
                    m = null;
                }
                if (m) return m;
                document.body.insertAdjacentHTML('beforeend',
                    '<div id="wcb-ship-modal" class="wcb-ship-modal" hidden aria-hidden="true">' +
                    '<div class="wcb-ship-modal__backdrop" tabindex="-1"></div>' +
                    '<div class="wcb-ship-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="wcb-ship-modal-title">' +
                    '<div class="wcb-ship-modal__head">' +
                    '<div class="wcb-ship-modal__head-text">' +
                    '<h2 id="wcb-ship-modal-title" class="wcb-ship-modal__title"><?php echo esc_js(__('Como você quer receber?', 'wcb-theme')); ?></h2>' +
                    '<p class="wcb-ship-modal__subtitle"><?php echo esc_js(__('Selecione a entrega e continue.', 'wcb-theme')); ?></p></div>' +
                    '<button type="button" class="wcb-ship-modal__close" aria-label="<?php echo esc_js(__('Fechar', 'wcb-theme')); ?>">&times;</button></div>' +
                    '<div class="wcb-ship-modal__subhead">' +
                    '<div class="wcb-ship-modal__cep-row">' +
                    '<span class="wcb-ship-modal__cep-badge" id="wcb-ship-modal-cep-disp"></span>' +
                    '<button type="button" class="wcb-ship-modal__cep-edit" id="wcb-ship-modal-edit-cep"><?php echo esc_js(__('Alterar CEP', 'wcb-theme')); ?></button></div></div>' +
                    '<div class="wcb-ship-modal__body" id="wcb-ship-modal-rates"></div>' +
                    '<div class="wcb-ship-modal__footer">' +
                    '<button type="button" class="wcb-ship-modal__continue" id="wcb-ship-modal-continue"><?php echo esc_js(__('Confirmar', 'wcb-theme')); ?></button></div>' +
                    '</div></div>');
                m = document.getElementById('wcb-ship-modal');
                m.querySelector('.wcb-ship-modal__backdrop').addEventListener('click', closeShipModal);
                m.querySelector('.wcb-ship-modal__close').addEventListener('click', closeShipModal);
                return m;
            }

            function bindShipModalEditCep() {
                var editCep = document.getElementById('wcb-ship-modal-edit-cep');
                if (!editCep) return;
                editCep.onclick = function () {
                    closeShipModal();
                    renderShipSummary('', '');
                    chosenShipRateId = '';
                    try {
                        window.sessionStorage.removeItem(WCB_SHIP_SUM_SS);
                    } catch (e) { /* */ }
                    var inp = document.getElementById('wcb-cep-input');
                    if (inp) {
                        inp.focus();
                        try { inp.select(); } catch (e) { /* */ }
                    }
                    var foot = document.querySelector('.xoo-wsc-footer');
                    if (foot && !foot.classList.contains('wcb-cart-more--expanded')) {
                        var tg = document.getElementById('wcb-cart-more-toggle');
                        if (tg) tg.click();
                    }
                };
            }

            function openShipModalLoading(cepFmt) {
                wcbShipApplying = false;
                var modal = ensureShipModal();
                bindShipModalEditCep();
                var contLbl = document.getElementById('wcb-ship-modal-continue');
                if (contLbl) {
                    contLbl.textContent = '<?php echo esc_js(__('Aguarde…', 'wcb-theme')); ?>';
                    contLbl.disabled = true;
                    contLbl.onclick = null;
                }
                var cepDisp = document.getElementById('wcb-ship-modal-cep-disp');
                if (cepDisp) cepDisp.textContent = cepFmt ? ('<?php echo esc_js(__('CEP', 'wcb-theme')); ?> ' + cepFmt) : '';
                var body = document.getElementById('wcb-ship-modal-rates');
                if (body) {
                    body.innerHTML = '<div class="wcb-ship-modal__loading" role="status" aria-live="polite"><span class="wcb-ship-modal__loading-inner"><?php echo esc_js(__('Buscando opções de entrega…', 'wcb-theme')); ?></span></div>';
                }
                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('wcb-ship-modal-open');
                var t = document.getElementById('wcb-ship-modal-title');
                if (t) try { t.focus(); } catch (e) { /* */ }
            }

            function openShipModal(rates, cepFmt) {
                wcbShipApplying = false;
                lastFetchedRates = rates;
                var modal = ensureShipModal();
                var contLbl = document.getElementById('wcb-ship-modal-continue');
                if (contLbl) contLbl.textContent = '<?php echo esc_js(__('Confirmar', 'wcb-theme')); ?>';
                var cepDisp = document.getElementById('wcb-ship-modal-cep-disp');
                if (cepDisp) cepDisp.textContent = cepFmt ? ('<?php echo esc_js(__('CEP', 'wcb-theme')); ?> ' + cepFmt) : '';

                var recIdx = wcbShipRecommendedIndex(rates);
                var cid = wcbNormRateId(chosenShipRateId);
                var hasChosen = cid !== '' && rates.some(function (x) { return wcbNormRateId(x.id) === cid; });
                var selectedId = hasChosen ? chosenShipRateId : rates[recIdx].id;

                var body = document.getElementById('wcb-ship-modal-rates');
                body.innerHTML = buildShipModalRatesHtml(rates, selectedId);

                function findRate(id) {
                    var want = wcbNormRateId(id);
                    var out = null;
                    rates.forEach(function (x) {
                        if (wcbNormRateId(x.id) === want) out = x;
                    });
                    return out;
                }

                function setModalSelection(rateId) {
                    var rid = wcbNormRateId(rateId);
                    body.querySelectorAll('.wcb-ship-opt__card').forEach(function (btn) {
                        var on = wcbNormRateId(btn.getAttribute('data-rate-id')) === rid;
                        btn.classList.toggle('is-selected', on);
                        btn.setAttribute('aria-checked', on ? 'true' : 'false');
                    });
                    var cont = document.getElementById('wcb-ship-modal-continue');
                    if (cont) cont.disabled = rid === '';
                }

                body.querySelectorAll('.wcb-ship-opt__card').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        setModalSelection(this.getAttribute('data-rate-id'));
                    });
                });

                setModalSelection(selectedId);

                var cont = document.getElementById('wcb-ship-modal-continue');
                if (cont) {
                    cont.onclick = function () {
                        if (wcbShipApplying) return;
                        var sel = body.querySelector('.wcb-ship-opt__card.is-selected');
                        if (!sel) return;
                        var id = sel.getAttribute('data-rate-id');
                        var rate = findRate(id);
                        if (rate) postSetShipping(id, rate);
                    };
                }

                bindShipModalEditCep();

                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('wcb-ship-modal-open');
                var toFocus = body.querySelector('.wcb-ship-opt__card.is-selected') || body.querySelector('.wcb-ship-opt__card');
                if (toFocus) toFocus.focus();
            }

            function saveShipSummary(cepDigits, label, costText, rateId) {
                try {
                    window.sessionStorage.setItem(WCB_SHIP_SUM_SS, JSON.stringify({
                        cep: cepDigits,
                        label: label,
                        cost: costText,
                        rateId: rateId
                    }));
                } catch (e) { /* private mode */ }
                chosenShipRateId = wcbNormRateId(rateId);
                renderShipSummary(label, costText);
                syncFooterShippingNote();
            }

            function loadShipSummaryFromStorage() {
                try {
                    var raw = window.sessionStorage.getItem(WCB_SHIP_SUM_SS);
                    return raw ? JSON.parse(raw) : null;
                } catch (e) {
                    return null;
                }
            }

            function renderShipSummary(label, costText) {
                var block = document.getElementById('wcb-cep-block');
                var idle = document.getElementById('wcb-cep-layer-idle');
                var done = document.getElementById('wcb-cep-layer-done');
                var inner = document.getElementById('wcb-cep-applied-inner');
                if (!block || !idle || !done || !inner) return;
                if (!label) {
                    inner.innerHTML = '';
                    block.classList.remove('wcb-cep-block--done', 'wcb-cep-block--loading');
                    idle.setAttribute('aria-hidden', 'false');
                    done.setAttribute('aria-hidden', 'true');
                    return;
                }
                var cost = costText || '';
                inner.innerHTML = '<div class="wcb-cep-summary__row">' +
                    '<span class="wcb-cep-summary__ok" aria-hidden="true">✓</span>' +
                    '<span class="wcb-cep-summary__text"><strong>' + escHtml(label) + '</strong> · ' + escHtml(cost) + '</span>' +
                    '<button type="button" class="wcb-cep-summary__change"><?php echo esc_js(__('Alterar', 'wcb-theme')); ?></button></div>';
                block.classList.remove('wcb-cep-block--loading');
                block.classList.add('wcb-cep-block--done');
                idle.setAttribute('aria-hidden', 'true');
                done.setAttribute('aria-hidden', 'false');
            }

            function syncShipSummaryFromStorage() {
                var data = loadShipSummaryFromStorage();
                var ci = document.getElementById('wcb-cep-input');
                if (!data || !ci || ci.value.replace(/\D/g, '') !== data.cep) return;
                chosenShipRateId = wcbNormRateId(data.rateId);
                renderShipSummary(data.label, data.cost);
                syncFooterShippingNote();
            }

            function syncFooterShippingNote() {
                var ft = document.querySelector('.xoo-wsc-footer .xoo-wsc-footer-txt');
                if (!ft) return;
                ft.textContent = '<?php echo esc_js(__('Frete e totais finais no checkout', 'wcb-theme')); ?>';
                ft.classList.remove('wcb-footer-txt--ship');
            }

            function postSetShipping(rateId, rateMeta) {
                var cont = document.getElementById('wcb-ship-modal-continue');
                var prevLabel = cont ? cont.textContent : '';
                if (wcbShipApplying) return;
                wcbShipApplying = true;
                if (cont) {
                    cont.disabled = true;
                    cont.textContent = '<?php echo esc_js(__('Aplicando…', 'wcb-theme')); ?>';
                }
                var body = new URLSearchParams();
                body.set('action', 'wcb_side_cart_set_shipping');
                body.set('nonce', wcbSideCartNonce);
                body.set('rate_id', wcbNormRateId(rateId));
                fetch(ajaxUrl, {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                }).then(function (r) { return r.json(); }).then(function (data) {
                    wcbShipApplying = false;
                    if (data.success === false) {
                        if (cont) {
                            cont.disabled = false;
                            cont.textContent = prevLabel || '<?php echo esc_js(__('Confirmar', 'wcb-theme')); ?>';
                        }
                        var msg = (data.data && data.data.message) ? String(data.data.message) : '<?php echo esc_js(__('Não foi possível aplicar o frete.', 'wcb-theme')); ?>';
                        setCepInlineMsg('<span class="wcb-cep-error">' + escHtml(msg) + '</span>', true);
                        return;
                    }
                    if (data.applied_coupons) initData.appliedCoupons = data.applied_coupons;
                    if (data.coupon_discount_by_code) initData.couponDiscountByCode = data.coupon_discount_by_code;
                    chosenShipRateId = wcbNormRateId(rateId);
                    if (rateMeta && lastCepPostcode) {
                        var costText = rateMeta.free ? '<?php echo esc_js(__('Grátis', 'wcb-theme')); ?>' : (rateMeta.cost_f || '');
                        saveShipSummary(lastCepPostcode, rateMeta.label, costText, chosenShipRateId);
                    }
                    closeShipModal();
                    if (cont) cont.textContent = prevLabel || '<?php echo esc_js(__('Confirmar', 'wcb-theme')); ?>';
                    applyWcFragments(data);
                }).catch(function () {
                    wcbShipApplying = false;
                    if (cont) {
                        cont.disabled = false;
                        cont.textContent = prevLabel || '<?php echo esc_js(__('Confirmar', 'wcb-theme')); ?>';
                    }
                    setCepInlineMsg('<span class="wcb-cep-error"><?php echo esc_js(__('Erro de rede. Toque em Confirmar de novo.', 'wcb-theme')); ?></span>', true);
                });
            }

            function sideCartUiMissing() {
                var rail = document.getElementById('wcb-incentive-rail');
                var gift = document.getElementById('wcb-gift-bar');
                var ship = document.getElementById('wcb-ship-bar');
                if (!rail || !gift || !ship) {
                    return true;
                }
                if (!rail.contains(gift) || !rail.contains(ship)) {
                    return true;
                }
                return !document.getElementById('wcb-coupon-block')
                    || !document.getElementById('wcb-cep-block');
            }

            var WCB_MORE_SS = 'wcb_side_cart_more_v1';
            function wcbReadMoreOpen() {
                try {
                    return window.sessionStorage.getItem(WCB_MORE_SS) === '1';
                } catch (e) {
                    return false;
                }
            }
            function wcbWriteMoreOpen(open) {
                try {
                    window.sessionStorage.setItem(WCB_MORE_SS, open ? '1' : '0');
                } catch (e) { /* private mode */ }
            }
            function wcbSyncMoreFooterClass() {
                var footer = document.querySelector('.xoo-wsc-footer');
                var btn = document.getElementById('wcb-cart-more-toggle');
                if (!footer || !btn) return;
                if (wcbReadMoreOpen()) {
                    footer.classList.add('wcb-cart-more--expanded');
                    btn.setAttribute('aria-expanded', 'true');
                } else {
                    footer.classList.remove('wcb-cart-more--expanded');
                    btn.setAttribute('aria-expanded', 'false');
                }
            }
            function initMoreToggle() {
                var wrap = document.getElementById('wcb-cart-more');
                var btn = document.getElementById('wcb-cart-more-toggle');
                if (!wrap || !btn || wrap.getAttribute('data-wcb-more-inited') === '1') return;
                wrap.setAttribute('data-wcb-more-inited', '1');
                wcbSyncMoreFooterClass();
                btn.addEventListener('click', function () {
                    var footer = document.querySelector('.xoo-wsc-footer');
                    if (!footer) return;
                    var next = !footer.classList.contains('wcb-cart-more--expanded');
                    footer.classList.toggle('wcb-cart-more--expanded', next);
                    wcbWriteMoreOpen(next);
                    btn.setAttribute('aria-expanded', next ? 'true' : 'false');
                });
            }

            function buildCartMoreBlock() {
                var panelInner = buildCouponBlock() + buildCepBlock();
                return '<div class="wcb-cart-more" id="wcb-cart-more">' +
                    '<button type="button" class="wcb-cart-more-toggle" id="wcb-cart-more-toggle" aria-expanded="false">' +
                    '<span class="wcb-cart-more-toggle__text">' +
                    '<span class="wcb-cart-more__label wcb-cart-more__label--more"><?php echo esc_js(__('Mostrar mais', 'wcb-theme')); ?></span>' +
                    '<span class="wcb-cart-more__label wcb-cart-more__label--less"><?php echo esc_js(__('Mostrar menos', 'wcb-theme')); ?></span>' +
                    '</span>' +
                    '<span class="wcb-cart-more-toggle__chev" aria-hidden="true"><svg class="wcb-cart-more-toggle__chev-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/></svg></span>' +
                    '</button>' +
                    '<div class="wcb-cart-more-panel" id="wcb-cart-more-panel">' +
                    '<div class="wcb-cart-more-stack">' + panelInner + '</div></div>' +
                    '</div>';
            }

            var lastCepPostcode = '';

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                if (document.body.classList.contains('wcb-ship-modal-open')) {
                    closeShipModal();
                    e.preventDefault();
                }
            });

            /* ── Gera HTML da barra de frete grátis ── */
            function buildShipBar(d) {
                var cls = 'wcb-ship-bar' + (d.ship_unlocked ? ' wcb-ship-bar--unlocked' : '');
                var text;
                var icon = WCB_SVG_TRUCK;
                if (d.ship_unlocked) {
                    icon = WCB_SVG_SHIP_OK;
                    text = '<strong class="wcb-incentive-accent">' + <?php echo wp_json_encode(__('Frete grátis', 'wcb-theme')); ?> + '</strong> ' + <?php echo wp_json_encode(__('desbloqueado!', 'wcb-theme')); ?>;
                } else if (d.subtotal <= 0) {
                    text = <?php echo wp_json_encode(__('Compre', 'wcb-theme')); ?> + ' <strong class="wcb-incentive-accent">R$ ' + Number(shipThreshold).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + '</strong> ' + <?php echo wp_json_encode(__('para', 'wcb-theme')); ?> + ' <strong class="wcb-incentive-accent">' + <?php echo wp_json_encode(__('frete grátis', 'wcb-theme')); ?> + '</strong>';
                } else {
                    text = <?php echo wp_json_encode(__('Faltam', 'wcb-theme')); ?> + ' <strong class="wcb-incentive-accent">R$ ' + Number(d.ship_remaining).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + '</strong> ' + <?php echo wp_json_encode(__('para', 'wcb-theme')); ?> + ' <strong class="wcb-incentive-accent">' + <?php echo wp_json_encode(__('frete grátis', 'wcb-theme')); ?> + '</strong>';
                }
                return '<div class="' + cls + '" id="wcb-ship-bar">' +
                    '<span class="wcb-incentive-suite__kicker">' + WCB_KICKER_SHIP + '</span>' +
                    '<div class="wcb-ship-bar__text">' +
                    '<span class="wcb-ship-bar__icon" aria-hidden="true">' + icon + '</span>' +
                    '<span class="wcb-ship-bar__copy">' + text + '</span></div>' +
                    '<div class="wcb-ship-bar__track"><div class="wcb-ship-bar__fill" style="width:' + Math.min(100, d.ship_progress) + '%"></div></div>' +
                    '</div>';
            }

            /* ── Gera HTML da barra de brinde ── */
            function buildGiftBar(d) {
                var cls = 'wcb-gift-progress' + (d.gift_unlocked ? ' wcb-gift-unlocked' : '');
                var txt = d.gift_text || d.text || '';
                return '<div class="' + cls + '" id="wcb-gift-bar" data-threshold="' + giftThreshold + '">' +
                    '<span class="wcb-incentive-suite__kicker">' + WCB_KICKER_GIFT + '</span>' +
                    '<div class="wcb-gift-progress-text">' +
                    '<span class="wcb-gift-progress__icon" aria-hidden="true">' + WCB_SVG_GIFT + '</span>' +
                    '<span class="wcb-gift-progress__copy">' + txt + '</span></div>' +
                    '<div class="wcb-gift-progress-bar"><div class="wcb-gift-progress-fill" style="width:' + d.gift_progress + '%"></div></div>' +
                    '</div>';
            }

            /* Brinde + frete — carrossel 1 slide por vez (JS + largura do viewport) */
            function buildIncentiveRail(d) {
                return '<div id="wcb-incentive-rail" class="wcb-incentive-rail wcb-incentive-rail--drawer-suite" role="region" aria-label="<?php echo esc_js(__('Brinde e frete grátis', 'wcb-theme')); ?>">' +
                    '<div class="wcb-incentive-rail__viewport">' +
                    '<div class="wcb-incentive-rail__track">' +
                    '<div class="wcb-incentive-rail__slide" role="group" aria-label="<?php echo esc_js(__('Brinde', 'wcb-theme')); ?>">' + buildGiftBar(d) + '</div>' +
                    '<div class="wcb-incentive-rail__slide" role="group" aria-label="<?php echo esc_js(__('Frete grátis', 'wcb-theme')); ?>">' + buildShipBar(d) + '</div>' +
                    '</div></div>' +
                    '<div class="wcb-incentive-rail__dots">' +
                    '<button type="button" class="wcb-incentive-rail__dot wcb-incentive-rail__dot--active" aria-label="<?php echo esc_js(__('Ver barra de brinde', 'wcb-theme')); ?>" aria-current="true"></button>' +
                    '<button type="button" class="wcb-incentive-rail__dot" aria-label="<?php echo esc_js(__('Ver barra de frete grátis', 'wcb-theme')); ?>" aria-current="false"></button>' +
                    '</div></div>';
            }

            function wcbDestroyIncentiveRailCarousel() {
                var rail = document.getElementById('wcb-incentive-rail');
                if (rail && typeof rail._wcbRailTeardown === 'function') {
                    rail._wcbRailTeardown();
                    rail._wcbRailTeardown = null;
                }
            }

            function wcbInitIncentiveRailCarousel() {
                wcbDestroyIncentiveRailCarousel();
                var rail = document.getElementById('wcb-incentive-rail');
                if (!rail) return;
                var reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
                var vp = rail.querySelector('.wcb-incentive-rail__viewport');
                var track = rail.querySelector('.wcb-incentive-rail__track');
                if (!vp || !track) return;
                var slides = track.querySelectorAll('.wcb-incentive-rail__slide');
                if (slides.length < 2) return;

                var dots = rail.querySelectorAll('.wcb-incentive-rail__dot');
                var idx = 0;
                var timerId = null;
                var ac = new AbortController();
                var sig = { signal: ac.signal };

                function updateDots(i) {
                    for (var di = 0; di < dots.length; di++) {
                        var on = di === i;
                        dots[di].classList.toggle('wcb-incentive-rail__dot--active', on);
                        dots[di].setAttribute('aria-current', on ? 'true' : 'false');
                    }
                    for (var sj = 0; sj < slides.length; sj++) {
                        slides[sj].setAttribute('aria-hidden', sj === i ? 'false' : 'true');
                    }
                }

                function slideStepPx() {
                    var w = slides[0].offsetWidth;
                    if (w < 1) {
                        var cs = window.getComputedStyle(vp);
                        var pl = parseFloat(cs.paddingLeft) || 0;
                        var pr = parseFloat(cs.paddingRight) || 0;
                        w = vp.clientWidth - pl - pr;
                    }
                    return Math.max(1, Math.round(w));
                }

                /* Deslocamento em px = largura real do slide (offsetWidth); inativos ficam invisíveis via aria-hidden + CSS (sem “filete” do card 2 no slide 1). */
                function goTo(i) {
                    var n = slides.length;
                    var target = ((i % n) + n) % n;
                    idx = target;
                    var step = slideStepPx();
                    track.style.transform = 'translate3d(-' + (target * step) + 'px,0,0)';
                    updateDots(target);
                }

                function syncLayout() {
                    goTo(idx);
                }

                function bumpLayoutUntilSized() {
                    syncLayout();
                    requestAnimationFrame(syncLayout);
                }

                function tick() {
                    goTo(idx + 1);
                }

                function startTimer() {
                    if (timerId) return;
                    timerId = window.setInterval(tick, 6000);
                }

                function stopTimer() {
                    if (timerId) {
                        clearInterval(timerId);
                        timerId = null;
                    }
                }

                bumpLayoutUntilSized();
                if (!reducedMotion) {
                    startTimer();
                }

                var ro = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(function () {
                    syncLayout();
                }) : null;
                if (ro) ro.observe(vp);

                function pause() { stopTimer(); }
                function resume() { startTimer(); }

                rail.addEventListener('mouseenter', pause, sig);
                rail.addEventListener('mouseleave', resume, sig);

                /* Swipe horizontal no viewport (touch): esquerda = próximo, direita = anterior */
                var touchStartX = 0;
                var touchStartY = 0;
                var touchTracking = false;
                vp.addEventListener('touchstart', function (e) {
                    if (!e.touches || e.touches.length !== 1) return;
                    touchTracking = true;
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                }, { passive: true, signal: ac.signal });
                vp.addEventListener('touchmove', function (e) {
                    if (!touchTracking || !e.touches || e.touches.length !== 1) return;
                    var mx = e.touches[0].clientX - touchStartX;
                    var my = e.touches[0].clientY - touchStartY;
                    if (Math.abs(mx) > Math.abs(my) && Math.abs(mx) > 14) {
                        e.preventDefault();
                    }
                }, { passive: false, signal: ac.signal });
                vp.addEventListener('touchend', function (e) {
                    if (!touchTracking) return;
                    touchTracking = false;
                    var ch = e.changedTouches && e.changedTouches[0];
                    if (!ch) return;
                    var dx = ch.clientX - touchStartX;
                    var dy = ch.clientY - touchStartY;
                    var minSwipe = 42;
                    if (Math.abs(dx) < minSwipe || Math.abs(dx) < Math.abs(dy)) return;
                    stopTimer();
                    if (dx < 0) {
                        goTo(idx + 1);
                    } else {
                        goTo(idx - 1);
                    }
                    startTimer();
                }, { passive: true, signal: ac.signal });
                vp.addEventListener('touchcancel', function () {
                    touchTracking = false;
                }, { passive: true, signal: ac.signal });

                for (var di = 0; di < dots.length; di++) {
                    (function (dIndex) {
                        dots[dIndex].addEventListener('click', function () { goTo(dIndex); }, sig);
                        dots[dIndex].addEventListener('keydown', function (e) {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                goTo(dIndex);
                            }
                        }, sig);
                    })(di);
                }

                rail._wcbRailTeardown = function () {
                    ac.abort();
                    stopTimer();
                    if (ro) ro.disconnect();
                    track.style.transform = '';
                };
            }

            /* ── Gera HTML do campo CEP ── */
            function buildCepBlock() {
                return '<div class="wcb-cep-block" id="wcb-cep-block">' +
                    '<div class="wcb-stack-card wcb-stack-card--cep">' +
                    '<label class="wcb-stack-card__label" for="wcb-cep-input"><?php echo esc_js(__('Frete e entrega', 'wcb-theme')); ?></label>' +
                    '<div class="wcb-stack-card__body">' +
                    '<div class="wcb-checkout-stage wcb-cep-stage" data-wcb-stage="cep">' +
                    '<div class="wcb-cep-layer wcb-cep-layer--idle" id="wcb-cep-layer-idle" aria-hidden="false">' +
                    '<div class="wcb-cep-block__row">' +
                    '<input type="text" class="wcb-cep-input" id="wcb-cep-input" placeholder="00000-000" maxlength="9" inputmode="numeric"' +
                    (userPostcode ? ' value="' + userPostcode + '"' : '') + '>' +
                    '<button type="button" class="wcb-cep-btn" id="wcb-cep-btn"><?php echo esc_js(__('Calcular', 'wcb-theme')); ?></button></div>' +
                    '<div class="wcb-cep-layer__skeleton" aria-hidden="true"></div></div>' +
                    '<div class="wcb-cep-layer wcb-cep-layer--done" id="wcb-cep-layer-done" aria-hidden="true">' +
                    '<div class="wcb-cep-applied-inner" id="wcb-cep-applied-inner"></div></div></div></div>' +
                    '<div class="wcb-cep-inline-msg" id="wcb-cep-inline-msg"></div></div></div>';
            }

            function setCepInlineMsg(html, isErr) {
                var el = document.getElementById('wcb-cep-inline-msg');
                if (!el) return;
                el.innerHTML = html || '';
                el.className = 'wcb-cep-inline-msg' + (isErr ? ' wcb-cep-inline-msg--error' : '');
            }

            /* ── Injeta os elementos no DOM do side cart ── */
            var injected = false;
            function inject(data) {
                var header = document.querySelector('.xoo-wsc-header');
                var body = document.querySelector('.xoo-wsc-body');
                var footer = document.querySelector('.xoo-wsc-footer');
                if (!header || !body || !footer) return false;

                lastIncentiveBarsSig = '';

                var oldInc = document.getElementById('wcb-incentives-drawer');
                if (oldInc) oldInc.remove();

                var oldRail = document.getElementById('wcb-incentive-rail');
                var legacyGift = document.getElementById('wcb-gift-bar');
                var legacyShip = document.getElementById('wcb-ship-bar');
                if (legacyGift && (!oldRail || !oldRail.contains(legacyGift))) legacyGift.remove();
                if (legacyShip && (!oldRail || !oldRail.contains(legacyShip))) legacyShip.remove();

                if (oldRail) {
                    wcbDestroyIncentiveRailCarousel();
                    oldRail.outerHTML = buildIncentiveRail(data);
                } else {
                    footer.insertAdjacentHTML('beforebegin', buildIncentiveRail(data));
                }

                // Cupom + CEP dentro de "Mostrar mais"; totais resumidos ficam visíveis quando recolhido
                var ftTotals = footer.querySelector('.xoo-wsc-ft-totals');
                var needMore = !document.getElementById('wcb-coupon-block') || !document.getElementById('wcb-cep-block');
                if (ftTotals) {
                    if (needMore) {
                        var oldMore = document.getElementById('wcb-cart-more');
                        if (oldMore) oldMore.remove();
                        ftTotals.insertAdjacentHTML('beforebegin', buildCartMoreBlock());
                        initCouponField();
                        initCepField();
                        initMoreToggle();
                    }
                } else {
                    if (needMore) {
                        var oldMoreF = document.getElementById('wcb-cart-more');
                        if (oldMoreF) oldMoreF.remove();
                        footer.insertAdjacentHTML('afterbegin', buildCartMoreBlock());
                        initCouponField();
                        initCepField();
                        initMoreToggle();
                    }
                }

                syncShipSummaryFromStorage();
                syncCouponStatusUi();
                syncFooterShippingNote();

                requestAnimationFrame(function () {
                    wcbInitIncentiveRailCarousel();
                });

                injected = true;
                return true;
            }

            /* ── Inicia máscara e handler do campo CEP ── */
            function initCepField() {
                var cepBlock = document.getElementById('wcb-cep-block');
                if (cepBlock && cepBlock.getAttribute('data-wcb-inited') === '1') return;
                var input = document.getElementById('wcb-cep-input');
                var btn = document.getElementById('wcb-cep-btn');
                if (!input || !btn) return;
                if (cepBlock) cepBlock.setAttribute('data-wcb-inited', '1');

                input.addEventListener('input', function () {
                    var v = this.value.replace(/\D/g, '').slice(0, 8);
                    this.value = v.length > 5 ? v.slice(0, 5) + '-' + v.slice(5) : v;
                    var st = loadShipSummaryFromStorage();
                    if (st && v !== st.cep && v.length > 0) {
                        try {
                            window.sessionStorage.removeItem(WCB_SHIP_SUM_SS);
                        } catch (e) { /* */ }
                        renderShipSummary('', '');
                        syncFooterShippingNote();
                    }
                    setCepInlineMsg('', false);
                });

                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') btn.click();
                });

                if (cepBlock) {
                    cepBlock.addEventListener('click', function (e) {
                        if (!e.target.closest('.wcb-cep-summary__change')) return;
                        e.preventDefault();
                        setCepInlineMsg('', false);
                        renderShipSummary('', '');
                        try {
                            window.sessionStorage.removeItem(WCB_SHIP_SUM_SS);
                        } catch (err) { /* */ }
                        lastCepPostcode = '';
                        lastFetchedRates = null;
                        chosenShipRateId = '';
                        if (input) {
                            input.focus();
                            try { input.select(); } catch (ex) { /* */ }
                        }
                    });
                }

                btn.addEventListener('click', function () {
                    var postcode = input.value.replace(/\D/g, '');
                    if (postcode.length < 8) {
                        setCepInlineMsg('<span class="wcb-cep-error"><?php echo esc_js(__('Digite um CEP válido.', 'wcb-theme')); ?></span>', true);
                        return;
                    }
                    if (btn.disabled) return;
                    btn.disabled = true;
                    var prevTxt = btn.textContent;
                    btn.textContent = '…';
                    setCepInlineMsg('', false);
                    if (cepBlock) cepBlock.classList.add('wcb-cep-block--loading');
                    openShipModalLoading(formatCepDisplay(postcode));

                    var fd = new FormData();
                    fd.append('action', 'wcb_calc_shipping');
                    fd.append('postcode', postcode);
                    fd.append('nonce', '<?php echo wp_create_nonce("wcb_calc_shipping"); ?>');

                    fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            btn.disabled = false;
                            btn.textContent = prevTxt;
                            if (cepBlock) cepBlock.classList.remove('wcb-cep-block--loading');
                            if (!res.success) {
                                lastCepPostcode = '';
                                closeShipModal();
                                var errRaw = res.data;
                                var errStr = typeof errRaw === 'string' ? errRaw : (errRaw && errRaw.message) ? String(errRaw.message) : '<?php echo esc_js(__('CEP não encontrado.', 'wcb-theme')); ?>';
                                setCepInlineMsg('<span class="wcb-cep-error">' + escHtml(errStr) + '</span>', true);
                                return;
                            }
                            var rates = res.data;
                            lastCepPostcode = postcode;
                            setCepInlineMsg('', false);
                            openShipModal(rates, formatCepDisplay(postcode));
                        })
                        .catch(function () {
                            btn.disabled = false;
                            btn.textContent = prevTxt;
                            if (cepBlock) cepBlock.classList.remove('wcb-cep-block--loading');
                            closeShipModal();
                            setCepInlineMsg('<span class="wcb-cep-error"><?php echo esc_js(__('Erro ao calcular. Tente novamente.', 'wcb-theme')); ?></span>', true);
                        });
                });
            }

            /* ── Recalcula subtotal a partir do DOM do side cart (sem AJAX) ── */
            function calcSubtotalFromDOM() {
                var total = 0;

                var subRow = document.querySelector('.xoo-wsc-ft-amt-subtotal .xoo-wsc-ft-amt-value, .xoo-wsc-ft-amt-subtotal .amount');
                if (subRow) {
                    var subTxt = subRow.textContent.replace(/[^\d,]/g, '').replace(',', '.');
                    var subVal = parseFloat(subTxt);
                    if (!isNaN(subVal) && subVal > 0) return subVal;
                }

                /* Tentar ler subtotal direto do footer do side cart (mais confiável) */
                var ftAmt = document.querySelector('.xoo-wsc-ft-amt .amount, .xoo-wsc-ft-amt');
                if (ftAmt) {
                    var ftTxt = ftAmt.textContent.replace(/[^\d,]/g, '').replace(',', '.');
                    var ftVal = parseFloat(ftTxt);
                    if (!isNaN(ftVal) && ftVal > 0) return ftVal;
                }

                /* Fallback: soma por item (suporta ambas classes do plugin) */
                document.querySelectorAll('.xoo-wsc-product, .xoo-wsc-item').forEach(function (item) {
                    var priceEl = item.querySelector('.xoo-wsc-sum-price, .xoo-wsc-item-stotal-price, .xoo-wsc-item-total, .amount');
                    if (!priceEl) return;
                    var txt = priceEl.textContent.replace(/[^\d,]/g, '').replace(',', '.');
                    var val = parseFloat(txt);
                    if (!isNaN(val)) total += val;
                });
                return total;
            }

            /* ── Recalcula e actualiza as barras com base no DOM (sem AJAX) ── */
            function refreshBarsLocal() {
                var sub = calcSubtotalFromDOM();
                /* Verificar se o carrinho realmente tem itens */
                var cartItems = document.querySelectorAll('.xoo-wsc-product, .xoo-wsc-item');
                var cartIsEmpty = cartItems.length === 0;

                /* Se o DOM tem itens mas não conseguiu ler os preços, usa initData */
                if (sub <= 0 && !cartIsEmpty && initData.subtotal > 0) {
                    sub = initData.subtotal;
                }

                var subRounded = Math.round(sub * 100) / 100;
                var barsSig = String(subRounded) + '|' + cartItems.length;
                var railEl = document.getElementById('wcb-incentive-rail');
                if (railEl && barsSig === lastIncentiveBarsSig) {
                    return;
                }
                lastIncentiveBarsSig = barsSig;

                var gRem = Math.max(0, giftThreshold - sub);
                var sRem = Math.max(0, shipThreshold - sub);
                var d = {
                    subtotal: sub,
                    gift_progress: sub > 0 ? Math.min(100, (sub / giftThreshold) * 100) : 0,
                    gift_unlocked: sub >= giftThreshold,
                    gift_text: sub <= 0
                        ? <?php echo wp_json_encode(__('Adicione produtos para ganhar um <strong class="wcb-incentive-accent">brinde grátis</strong>!', 'wcb-theme')); ?>
                        : (sub >= giftThreshold
                            ? <?php echo wp_json_encode(__('<strong class="wcb-incentive-accent">Parabéns!</strong> Você ganhou um <strong class="wcb-incentive-accent">brinde</strong>!', 'wcb-theme')); ?>
                            : <?php echo wp_json_encode(__('Faltam', 'wcb-theme')); ?> + ' <strong class="wcb-incentive-accent">R$ ' + gRem.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + '</strong> ' + <?php echo wp_json_encode(__('para', 'wcb-theme')); ?> + ' <strong class="wcb-incentive-accent">' + <?php echo wp_json_encode(__('ganhar um brinde!', 'wcb-theme')); ?> + '</strong>'),
                    ship_progress: sub > 0 ? Math.min(100, (sub / shipThreshold) * 100) : 0,
                    ship_remaining: sRem,
                    ship_unlocked: sub >= shipThreshold
                };

                var legacy = document.getElementById('wcb-incentives-drawer');
                if (legacy) legacy.remove();

                var rail = document.getElementById('wcb-incentive-rail');
                var ft = document.querySelector('.xoo-wsc-footer');
                if (rail) {
                    wcbDestroyIncentiveRailCarousel();
                    rail.outerHTML = buildIncentiveRail(d);
                } else {
                    var gb = document.getElementById('wcb-gift-bar');
                    var sb = document.getElementById('wcb-ship-bar');
                    if (gb) gb.remove();
                    if (sb) sb.remove();
                    if (ft) ft.insertAdjacentHTML('beforebegin', buildIncentiveRail(d));
                }

                /* Atualizar initData para refletir o estado atual */
                initData.subtotal = sub;
                initData.gift_progress = d.gift_progress;
                initData.gift_unlocked = d.gift_unlocked;
                initData.gift_text = d.gift_text;
                initData.ship_progress = d.ship_progress;
                initData.ship_remaining = d.ship_remaining;
                initData.ship_unlocked = d.ship_unlocked;

                requestAnimationFrame(function () {
                    wcbInitIncentiveRailCarousel();
                });
            }

            /* ── Quando o cart é atualizado via WooCommerce events: recalcula localmente ── */
            var updateTimer = null;
            function onCartUpdate() {
                /* Debounce maior: fragments do Xoo disparam vários eventos seguidos */
                clearTimeout(updateTimer);
                updateTimer = setTimeout(function () {
                    if (sideCartUiMissing()) {
                        injected = false;
                        inject(initData);
                    }
                    refreshBarsLocal();
                }, 480);
            }
            if (typeof jQuery !== 'undefined') {
                jQuery(document.body).on('xoo_wsc_cart_updated added_to_cart removed_from_cart wc_fragments_refreshed xoo_wsc_open', onCartUpdate);
                jQuery(document.body).on('xoo_wsc_open', function () {
                    requestAnimationFrame(function () {
                        wcbInitIncentiveRailCarousel();
                    });
                });
            }

            /* ── MutationObserver: injeta quando o side cart aparece (1 verificação por frame) ── */
            var _wcbObsQueued = false;
            var _wcbObserver = new MutationObserver(function () {
                if (_wcbObsQueued) return;
                _wcbObsQueued = true;
                requestAnimationFrame(function () {
                    _wcbObsQueued = false;
                    if (!sideCartUiMissing()) return;
                    var footer = document.querySelector('.xoo-wsc-footer');
                    if (footer) inject(initData);
                });
            });
            var _wcbMoRoot = document.querySelector('.xoo-wsc-markup') || document.querySelector('.xoo-wsc-modal') || document.body;
            _wcbObserver.observe(_wcbMoRoot, { childList: true, subtree: true });
        })();
    </script>
    <?php
}
add_action('wp_footer', 'wcb_gift_progress_bar', 99);

/* ============================================================
   LIVE SEARCH — AJAX Handler
   ============================================================ */
function wcb_live_search_handler()
{
    wcb_verify_public_ajax_request();
    wcb_rate_limit_public_ajax('live_search', 100);

    global $wpdb;

    $query = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
    if (strlen($query) < 2) {
        wp_send_json([]);
    }

    $cache_key = 'wcb_ls_v2_' . md5(strtolower($query));
    $cached = get_transient($cache_key);
    if ($cached !== false && is_array($cached)) {
        wp_send_json($cached);
    }

    // Super Ofertas (sem exclusões da homepage) — cache curto por lista em promoção.
    $flash_id_set = array();
    if (function_exists('wcb_super_ofertas_build_context')) {
        $on_sale_for_so = get_transient('wcb_on_sale_ids');
        if (false === $on_sale_for_so) {
            $on_sale_for_so = wc_get_product_ids_on_sale();
            set_transient('wcb_on_sale_ids', $on_sale_for_so, HOUR_IN_SECONDS);
        }
        if (!empty($on_sale_for_so)) {
            $flash_cache_key = 'wcb_ls_flash_set_' . md5(wp_json_encode(array_values(array_map('intval', $on_sale_for_so))));
            $flash_cached = get_transient($flash_cache_key);
            if (false !== $flash_cached && is_array($flash_cached)) {
                $flash_id_set = array_fill_keys($flash_cached, true);
            } else {
                $so_ctx_quick = wcb_super_ofertas_build_context($on_sale_for_so, array());
                $fid = array();
                if (is_array($so_ctx_quick)) {
                    if (!empty($so_ctx_quick['hero_id'])) {
                        $fid[] = (int) $so_ctx_quick['hero_id'];
                    }
                    if (!empty($so_ctx_quick['hero_id_2'])) {
                        $fid[] = (int) $so_ctx_quick['hero_id_2'];
                    }
                    if (!empty($so_ctx_quick['carousel_ids']) && is_array($so_ctx_quick['carousel_ids'])) {
                        foreach ($so_ctx_quick['carousel_ids'] as $cid) {
                            $fid[] = (int) $cid;
                        }
                    }
                }
                $fid = array_values(array_unique(array_filter($fid)));
                set_transient($flash_cache_key, $fid, 15 * MINUTE_IN_SECONDS);
                $flash_id_set = array_fill_keys($fid, true);
            }
        }
    }

    $like = '%' . $wpdb->esc_like($query) . '%';

    $title_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
             WHERE p.post_type = 'product' AND p.post_status = 'publish' AND p.post_title LIKE %s
             ORDER BY p.post_title ASC LIMIT 8",
            $like
        )
    );

    $tag_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
             WHERE p.post_type = 'product' AND p.post_status = 'publish'
               AND tt.taxonomy IN ('product_tag', 'pa_marca', 'product_cat') AND t.name LIKE %s
             ORDER BY p.post_title ASC LIMIT 8",
            $like
        )
    );

    $ids = array_unique(array_merge($title_ids, $tag_ids));
    $ids = array_slice($ids, 0, 8);
    $results = [];

    foreach ($ids as $post_id) {
        $post = get_post($post_id);
        $product = wc_get_product($post_id);
        if (!$product || !$post || trim($post->post_title) === '')
            continue;

        $thumb_id = $product->get_image_id();
        $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : wc_placeholder_img_src('thumbnail');

        $terms = get_the_terms($post_id, 'product_cat');
        $cat_name = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : '';

        $price = (float) $product->get_price();
        $sale = (float) $product->get_sale_price();
        $regular = (float) $product->get_regular_price();

        if ($sale && $sale < $regular) {
            $price_display = wc_format_localized_price($sale);
            $price_old = wc_format_localized_price($regular);
            $discount_pct = $regular > 0 ? round((1 - $sale / $regular) * 100) : 0;
        } else {
            $price_display = wc_format_localized_price($price ?: $regular);
            $price_old = '';
            $discount_pct = 0;
        }

        $volume = $product->get_attribute('volume') ?: $product->get_attribute('pa_volume');
        $nic_type = '';
        foreach (['nicotina', 'nic-type', 'tipo-de-nicotina', 'pa_nicotina'] as $attr) {
            $v = $product->get_attribute($attr);
            if ($v) {
                $nic_type = sanitize_text_field($v);
                break;
            }
        }
        if (!$nic_type) {
            $tags_terms = get_the_terms($post_id, 'product_tag');
            if ($tags_terms && !is_wp_error($tags_terms)) {
                foreach ($tags_terms as $tag) {
                    $slug = strtolower($tag->slug);
                    if (strpos($slug, 'nic-salt') !== false || strpos($slug, 'salt') !== false) {
                        $nic_type = 'Nic Salt';
                        break;
                    }
                    if (strpos($slug, 'freebase') !== false) {
                        $nic_type = 'Freebase';
                        break;
                    }
                    if (strpos($slug, 'zero') !== false) {
                        $nic_type = 'Zero Nic';
                        break;
                    }
                }
            }
        }

        $wcb_rs       = wcb_get_product_rating_display_stats( $post_id );
        $rating       = (float) $wcb_rs['average'];
        $rating_count = (int) $wcb_rs['count'];
        $total_sales = (int) get_post_meta($post_id, 'total_sales', true);
        $is_bestseller = $total_sales > 20;
        $is_trending = $total_sales > 5 && $total_sales <= 20;
        $days_ago = (time() - strtotime($post->post_date)) / DAY_IN_SECONDS;
        $is_new = $days_ago <= 30;

        $brand = '';
        foreach (['product_brand', 'pa_marca', 'brand', 'pwb-brand'] as $tax) {
            $b_terms = get_the_terms($post_id, $tax);
            if ($b_terms && !is_wp_error($b_terms)) {
                $brand = $b_terms[0]->name;
                break;
            }
        }
        if (!$brand) {
            foreach (['marca', 'brand', 'fabricante'] as $attr) {
                $v = $product->get_attribute($attr);
                if ($v) {
                    $brand = sanitize_text_field($v);
                    break;
                }
            }
        }
        if (!$brand) {
            $first_word = strtok($post->post_title, ' ');
            if (ctype_upper(str_replace(['-', '_'], '', $first_word)))
                $brand = $first_word;
        }

        $results[] = [
            'id' => $post_id,
            'title' => $post->post_title,
            'url' => get_permalink($post_id),
            'thumb' => $thumb_url,
            'price' => $price_display,
            'price_old' => $price_old,
            'discount_pct' => $discount_pct,
            'category' => $cat_name,
            'volume' => $volume,
            'nic_type' => $nic_type,
            'rating' => $rating,
            'rating_count' => $rating_count,
            'is_bestseller' => $is_bestseller,
            'is_trending' => $is_trending,
            'is_new' => $is_new,
            'brand' => $brand,
            'in_stock' => $product->is_in_stock(),
            'is_flash_offer' => isset($flash_id_set[$post_id]),
        ];
    }

    set_transient($cache_key, $results, 5 * MINUTE_IN_SECONDS);
    wp_send_json($results);
}
add_action('wp_ajax_wcb_live_search', 'wcb_live_search_handler');
add_action('wp_ajax_nopriv_wcb_live_search', 'wcb_live_search_handler');

/* ============================================================
   QUICK VIEW — AJAX Handler
   Retorna os dados do produto em JSON para popular o modal.
   Inclui dados de variações para produtos variáveis.
   ============================================================ */
function wcb_quick_view_handler()
{
    wcb_verify_public_ajax_request();
    wcb_rate_limit_public_ajax('quick_view', 80);

    $product_id = intval($_GET['product_id'] ?? 0);
    if (!$product_id) {
        wp_send_json_error('invalid_product');
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error('product_not_found');
    }

    // ── Prices ────────────────────────────────────────────────
    $regular_price = (float) $product->get_regular_price();
    $sale_price = $product->get_sale_price() ? (float) $product->get_sale_price() : 0;
    $current_price = (float) $product->get_price();
    $is_on_sale = $product->is_on_sale();
    $saving = 0;

    if ($is_on_sale && $regular_price > 0 && $sale_price > 0) {
        $saving = round((($regular_price - $sale_price) / $regular_price) * 100);
    }

    $pix_price = $current_price > 0 ? round($current_price * 0.95, 2) : 0;
    $installments = $current_price > 0 ? ceil($current_price / 12) : 0;

    // ── Main image ────────────────────────────────────────────
    $image_id = $product->get_image_id();
    $image_url = $image_id
        ? wp_get_attachment_image_url($image_id, 'wcb-product-large')
        : wc_placeholder_img_src('wcb-product-large');

    // ── Gallery ───────────────────────────────────────────────
    $gallery_ids = $product->get_gallery_image_ids();
    $gallery_imgs = [];
    if ($image_id) {
        $gallery_imgs[] = [
            'thumb' => wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail'),
            'full' => wp_get_attachment_image_url($image_id, 'wcb-product-large'),
            'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: $product->get_name(),
        ];
    }
    foreach (array_slice($gallery_ids, 0, 5) as $gid) {
        $gallery_imgs[] = [
            'thumb' => wp_get_attachment_image_url($gid, 'woocommerce_thumbnail'),
            'full' => wp_get_attachment_image_url($gid, 'wcb-product-large'),
            'alt' => get_post_meta($gid, '_wp_attachment_image_alt', true) ?: $product->get_name(),
        ];
    }

    // ── Category ──────────────────────────────────────────────
    $terms = get_the_terms($product_id, 'product_cat');
    $cat_name = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : '';

    // ── Rating (comentários aprovados + meta rating, igual cards / PDP) ───
    $wcb_qv_rs    = wcb_get_product_rating_display_stats( $product_id );
    $rating_count = (int) $wcb_qv_rs['count'];
    $avg_rating   = (float) $wcb_qv_rs['average'];

    // ── Stock ─────────────────────────────────────────────────
    $stock_qty = $product->get_stock_quantity();
    $low_stock = $stock_qty !== null && $stock_qty > 0 && $stock_qty <= 5;

    // ── Specs ─────────────────────────────────────────────────
    $specs = [];
    $attr_map = [
        'bateria' => '⚡',
        'capacidade' => '💧',
        'conexao' => '🔌',
        'pa-bateria' => '⚡',
    ];
    foreach ($product->get_attributes() as $key => $attr) {
        $clean_key = str_replace('pa_', '', $key);
        if (isset($attr_map[$clean_key])) {
            $values = is_array($attr->get_options()) ? implode(', ', $attr->get_options()) : '';
            $specs[] = ['emoji' => $attr_map[$clean_key], 'value' => $values];
            if (count($specs) >= 3)
                break;
        }
    }

    // ── Short description ─────────────────────────────────────
    $short_desc = wp_trim_words(wp_strip_all_tags($product->get_short_description()), 20, '…');

    // ── Variation data (for variable products) ────────────────
    $variation_attributes = [];
    $variations_data = [];
    $default_attributes = [];

    if ($product->is_type('variable')) {
        // Get variation attributes with their labels and terms
        foreach ($product->get_variation_attributes() as $attr_name => $options) {
            $taxonomy = strpos($attr_name, 'pa_') === 0 ? $attr_name : '';
            $label = wc_attribute_label($attr_name, $product);
            $terms_list = [];

            if ($taxonomy && taxonomy_exists($taxonomy)) {
                $attr_terms = wc_get_product_terms($product_id, $taxonomy, ['fields' => 'all']);
                foreach ($attr_terms as $term) {
                    if (in_array($term->slug, $options) || in_array($term->name, $options)) {
                        $terms_list[] = [
                            'slug' => $term->slug,
                            'name' => $term->name,
                        ];
                    }
                }
            } else {
                foreach ($options as $opt) {
                    $terms_list[] = [
                        'slug' => sanitize_title($opt),
                        'name' => $opt,
                    ];
                }
            }

            $variation_attributes[] = [
                'name' => $attr_name,
                'label' => $label,
                'terms' => $terms_list,
            ];
        }

        // Get default attributes
        $default_attributes = $product->get_default_attributes();

        // Get each available variation
        $available_variations = $product->get_available_variations();
        foreach ($available_variations as $var) {
            $var_product = wc_get_product($var['variation_id']);
            if (!$var_product)
                continue;

            $var_price = (float) $var_product->get_price();
            $var_regular = (float) $var_product->get_regular_price();
            $var_sale = $var_product->get_sale_price() ? (float) $var_product->get_sale_price() : 0;
            $var_on_sale = $var_product->is_on_sale();
            $var_saving = 0;
            if ($var_on_sale && $var_regular > 0 && $var_sale > 0) {
                $var_saving = round((($var_regular - $var_sale) / $var_regular) * 100);
            }
            $var_pix = $var_price > 0 ? round($var_price * 0.95, 2) : 0;
            $var_install = $var_price > 0 ? ceil($var_price / 12) : 0;

            $var_image = $var['image']['url'] ?? '';
            $var_image_thumb = $var['image']['thumb_src'] ?? $var_image;

            $variations_data[] = [
                'variation_id' => $var['variation_id'],
                'attributes' => $var['attributes'],
                'is_in_stock' => $var['is_in_stock'],
                'stock_qty' => $var_product->get_stock_quantity(),
                'sku' => $var_product->get_sku(),
                'regular_price' => $var_regular > 0 ? 'R$ ' . number_format($var_regular, 2, ',', '.') : '',
                'current_price' => $var_price > 0 ? 'R$ ' . number_format($var_price, 2, ',', '.') : '',
                'pix_price' => $var_pix > 0 ? 'R$ ' . number_format($var_pix, 2, ',', '.') : '',
                'installments' => $var_price > 0 ? 'ou 12x no cartão' : '',
                'is_on_sale' => $var_on_sale,
                'saving' => $var_saving,
                'image_url' => $var_image,
                'image_thumb' => $var_image_thumb,
            ];
        }
    }

    $buybox_html = '';
    if ( $product->is_type( 'variable' ) && function_exists( 'wcb_render_quick_view_variable_buybox' ) ) {
        $vp = wc_get_product( $product_id );
        if ( $vp instanceof WC_Product_Variable ) {
            ob_start();
            wcb_render_quick_view_variable_buybox( $vp );
            $buybox_html = ob_get_clean();
        }
    }

    wp_send_json_success([
        'id' => $product_id,
        'name' => $product->get_name(),
        'permalink' => get_permalink($product_id),
        'image_url' => $image_url,
        'gallery' => $gallery_imgs,
        'category' => $cat_name,
        'is_on_sale' => $is_on_sale,
        'saving' => $saving,
        'regular_price' => $regular_price > 0 ? 'R$ ' . number_format($regular_price, 2, ',', '.') : '',
        'current_price' => $current_price > 0 ? 'R$ ' . number_format($current_price, 2, ',', '.') : 'Consulte',
        'pix_price' => $pix_price > 0 ? 'R$ ' . number_format($pix_price, 2, ',', '.') : '',
        'installments' => $current_price > 0 ? 'ou 12x no cartão' : '',
        'rating_count' => $rating_count,
        'avg_rating' => $avg_rating,
        'low_stock' => $low_stock,
        'stock_qty' => $stock_qty,
        'specs' => $specs,
        'short_desc' => $short_desc,
        'add_to_cart_url' => $product->add_to_cart_url(),
        'sku' => $product->get_sku(),
        'in_stock' => $product->is_in_stock(),
        'type' => $product->get_type(),
        'nonce' => wp_create_nonce('wcb-quick-view'),
        // Variation data
        'variation_attributes' => $variation_attributes,
        'variations' => $variations_data,
        'default_attributes' => $default_attributes,
        'buybox_html' => $buybox_html,
    ]);
}
add_action('wp_ajax_wcb_quick_view', 'wcb_quick_view_handler');
add_action('wp_ajax_nopriv_wcb_quick_view', 'wcb_quick_view_handler');

/* ============================================================
   QUICK VIEW — Modal HTML (injetado uma vez no footer)
   ============================================================ */
function wcb_quick_view_modal_html()
{
    ?>
    <!-- ══ WCB Quick View Modal ══════════════════════════════ -->
    <div id="wcb-qv-overlay" class="wcb-qv-overlay" aria-hidden="true" role="dialog" aria-modal="true"
        aria-label="Visualização rápida do produto">
        <div class="wcb-qv-modal">
            <button class="wcb-qv-close" id="wcb-qv-close" aria-label="Fechar">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
            <!-- Loading state -->
            <div id="wcb-qv-loading" class="wcb-qv-loading">
                <div class="wcb-qv-spinner"></div>
                <span>Carregando produto...</span>
            </div>
            <!-- Content populated by JS -->
            <div id="wcb-qv-content" class="wcb-qv-content"></div>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'wcb_quick_view_modal_html', 5);


/* ============================================================
   WISHLIST (FAVORITOS) — Sistema completo
   ============================================================ */

/* ── 1. Registrar o endpoint "favoritos" ─────────────────── */
function wcb_wishlist_register_endpoint()
{
    add_rewrite_endpoint('favoritos', EP_ROOT | EP_PAGES);
}
add_action('init', 'wcb_wishlist_register_endpoint');

/* ── 2. Adicionar tab "Favoritos" ao menu My Account ─────── */
function wcb_wishlist_menu_item($items)
{
    // Inserir antes de "Sair"
    $logout = isset($items['customer-logout']) ? $items['customer-logout'] : null;
    unset($items['customer-logout']);
    $items['favoritos'] = '❤️ Favoritos';
    if ($logout)
        $items['customer-logout'] = $logout;
    return $items;
}
add_filter('woocommerce_account_menu_items', 'wcb_wishlist_menu_item');

/* ── 3. Título da tab (só a página Minha conta — não os cards com the_title() do produto) ── */
function wcb_wishlist_endpoint_title($title)
{
    global $wp_query;
    if (!isset($wp_query->query_vars['favoritos']) || !in_the_loop()) {
        return $title;
    }
    $account_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('myaccount') : 0;
    if ($account_id > 0 && (int) get_the_ID() === $account_id) {
        $title = __('Meus Favoritos', 'wcb-theme');
    }
    return $title;
}
add_filter('the_title', 'wcb_wishlist_endpoint_title');

/* ── 4. Conteúdo da página favoritos ────────────────────── */
function wcb_wishlist_endpoint_content()
{
    $user_id = get_current_user_id();
    $wishlist = get_user_meta($user_id, '_wcb_wishlist', true);
    $wishlist = is_array($wishlist) ? array_filter(array_map('intval', $wishlist)) : [];

    if (empty($wishlist)): ?>
        <div class="wcb-wishlist-empty">
            <div class="wcb-wishlist-empty__icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#155DFD"
                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                </svg>
            </div>
            <h3>Sua lista de favoritos está vazia</h3>
            <p>Explore nossa loja e clique em ❤️ para salvar os produtos que você curtir.</p>
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="wcb-wishlist-shop-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1" />
                    <circle cx="20" cy="21" r="1" />
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                </svg>
                Ver produtos
            </a>
        </div>
    <?php else:
        $products = wc_get_products([
            'include' => $wishlist,
            'limit' => -1,
            'status' => 'publish',
        ]);
        $count = count($products);
        ?>

        <!-- ══ Header premium ══ -->
        <div class="wcb-wl-header">
            <div class="wcb-wl-header__left">
                <div class="wcb-wl-header__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#155DFD"
                        stroke="#155DFD" stroke-width="1.5" stroke-linecap="round">
                        <path
                            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                    </svg>
                </div>
                <div>
                    <div class="wcb-wl-header__title">Meus Favoritos</div>
                    <div class="wcb-wl-header__sub"><?php echo $count; ?> produto<?php echo $count !== 1 ? 's' : ''; ?>
                        salvo<?php echo $count !== 1 ? 's' : ''; ?></div>
                </div>
            </div>
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="wcb-wl-header__cta">
                Continuar comprando →
            </a>
        </div>

        <!-- ══ Grid — mesmos cards da home (template-parts/product-card) ══ -->
        <div class="wcb-wl-grid">
            <?php
            foreach ($products as $wc_product) {
                $pid = $wc_product->get_id();
                $post_object = get_post($pid);
                if (!$post_object || $post_object->post_status !== 'publish') {
                    continue;
                }
                global $post, $product;
                $post    = $post_object;
                $product = $wc_product;
                setup_postdata($post);
                ?>
                <div class="wcb-wl-card wcb-wl-card-shell" data-product-id="<?php echo esc_attr((string) $pid); ?>">
                    <?php
                    get_template_part(
                        'template-parts/product-card',
                        null,
                        array(
                            'product'        => $wc_product,
                            'wishlist_page'  => true,
                        )
                    );
                    ?>
                </div>
                <?php
                wp_reset_postdata();
            }
            ?>
        </div><!-- /.wcb-wl-grid -->

    <?php endif;
}
add_action('woocommerce_account_favoritos_endpoint', 'wcb_wishlist_endpoint_content');

/* ── 5. AJAX: Toggle produto na wishlist ─────────────────── */
function wcb_toggle_wishlist_ajax()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'not_logged_in']);
    }

    check_ajax_referer('wcb_wishlist_nonce', 'nonce');

    $product_id = absint($_POST['product_id'] ?? 0);
    if (!$product_id) {
        wp_send_json_error(['message' => 'invalid_product']);
    }

    $user_id = get_current_user_id();
    $wishlist = get_user_meta($user_id, '_wcb_wishlist', true);
    $wishlist = is_array($wishlist) ? $wishlist : [];

    if (in_array($product_id, $wishlist)) {
        // Remove
        $wishlist = array_values(array_diff($wishlist, [$product_id]));
        $action = 'removed';
    } else {
        // Adiciona
        $wishlist[] = $product_id;
        $action = 'added';
    }

    update_user_meta($user_id, '_wcb_wishlist', $wishlist);
    wp_send_json_success(['action' => $action, 'count' => count($wishlist), 'wishlist' => $wishlist]);
}
add_action('wp_ajax_wcb_toggle_wishlist', 'wcb_toggle_wishlist_ajax');

/* ── 6. AJAX: Buscar lista de favoritos (sincronizar com o servidor) ── */
function wcb_get_wishlist_ajax()
{
    if (!check_ajax_referer('wcb_wishlist_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'invalid_nonce'], 403);
    }
    wcb_rate_limit_public_ajax('wishlist_get', 120);

    if (!is_user_logged_in()) {
        wp_send_json_success(['wishlist' => []]);
    }
    $user_id = get_current_user_id();
    $wishlist = get_user_meta($user_id, '_wcb_wishlist', true);
    $wishlist = is_array($wishlist) ? array_map('intval', $wishlist) : [];
    wp_send_json_success(['wishlist' => $wishlist]);
}
add_action('wp_ajax_wcb_get_wishlist', 'wcb_get_wishlist_ajax');
add_action('wp_ajax_nopriv_wcb_get_wishlist', 'wcb_get_wishlist_ajax');

/* ── 7. Injetar nonce e estado de login para o JS ─────────── */
function wcb_wishlist_localize()
{
    $user_id = get_current_user_id();
    $wishlist = [];
    if ($user_id) {
        $raw = get_user_meta($user_id, '_wcb_wishlist', true);
        $wishlist = is_array($raw) ? array_map('intval', $raw) : [];
    }
    ?>
    <script>
        window.wcbWishlist = {
            isLoggedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>,
            nonce: "<?php echo esc_js(wp_create_nonce('wcb_wishlist_nonce')); ?>",
            ajaxUrl: "<?php echo esc_js(admin_url('admin-ajax.php')); ?>",
            wishlist: <?php echo json_encode($wishlist); ?>,
            loginUrl: "<?php echo esc_js(wp_login_url(get_permalink())); ?>"
        };
    </script>
    <?php
}
add_action('wp_footer', 'wcb_wishlist_localize', 1);

/* ============================================================
   PDP — Subtotal no buybox (sempre acima de qty + Adicionar)
   Markup injetado dentro do form WooCommerce para ordem correta no DOM.
   ============================================================ */

/**
 * Imprime uma única vez o bloco #wcb-pdp-subtotal (atualizado pelo JS da PDP).
 */
function wcb_render_pdp_buybox_subtotal_markup()
{
    static $printed = false;
    if ($printed) {
        return;
    }

    global $product;
    if (!$product instanceof WC_Product || !$product->is_in_stock()) {
        return;
    }

    $printed = true;
    wcb_buybox_print_subtotal_markup( 'wcb-pdp' );
}

/**
 * Quick View (buybox variável AJAX): subtotal com IDs wcb-qv-pdp-*.
 */
function wcb_qv_output_subtotal_after_variations_table() {
    if ( empty( $GLOBALS['wcb_qv_variable_buybox_render'] ) ) {
        return;
    }
    global $product;
    if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) || ! $product->is_in_stock() ) {
        return;
    }
    wcb_buybox_print_subtotal_markup( 'wcb-qv-pdp' );
}
add_action( 'woocommerce_after_variations_table', 'wcb_qv_output_subtotal_after_variations_table', 6 );

/**
 * Produto variável: após a tabela de atributos, antes de .single_variation_wrap / .woocommerce-variation-add-to-cart.
 */
function wcb_pdp_output_subtotal_after_variations_table()
{
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    global $product;
    if (!$product instanceof WC_Product || !$product->is_type('variable')) {
        return;
    }
    wcb_render_pdp_buybox_subtotal_markup();
}
add_action('woocommerce_after_variations_table', 'wcb_pdp_output_subtotal_after_variations_table', 5);

/**
 * Produto simples (e outros não variáveis com o template simple): antes do bloco .quantity.
 */
function wcb_pdp_output_subtotal_before_simple_qty()
{
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    global $product;
    if (!$product instanceof WC_Product || $product->is_type('variable')) {
        return;
    }
    /* Agrupado: o hook corre várias vezes (um por filho); não injetar subtotal aqui. */
    if ($product->is_type('grouped')) {
        return;
    }
    wcb_render_pdp_buybox_subtotal_markup();
}
add_action('woocommerce_before_add_to_cart_quantity', 'wcb_pdp_output_subtotal_before_simple_qty', 5);

/**
 * Trilha de navegação no topo da página do carrinho (Início → Loja → título da página).
 */
function wcb_render_cart_page_breadcrumb()
{
    if (!function_exists('is_cart') || !is_cart()) {
        return;
    }
    ?>
    <nav class="wcb-breadcrumb wcb-breadcrumb--cart-page" aria-label="<?php esc_attr_e('Trilha de navegação', 'wcb-theme'); ?>">
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Início', 'wcb-theme'); ?></a>
        <span class="wcb-breadcrumb__sep" aria-hidden="true">/</span>
        <?php
        if (function_exists('wc_get_page_id')) {
            $shop_id = (int) wc_get_page_id('shop');
            if ($shop_id > 0 && get_post_status($shop_id) === 'publish') {
                echo '<a href="' . esc_url(get_permalink($shop_id)) . '">' . esc_html(get_the_title($shop_id)) . '</a>';
                echo '<span class="wcb-breadcrumb__sep" aria-hidden="true">/</span>';
            }
        }
        $page_id = get_queried_object_id();
        $cart_label = $page_id ? get_the_title($page_id) : '';
        if ($cart_label === '') {
            $cart_label = __('Carrinho', 'wcb-theme');
        }
        ?>
        <span aria-current="page"><?php echo esc_html($cart_label); ?></span>
    </nav>
    <?php
}

/**
 * ACF: grupo de campos do timer da barra de oferta na PDP (produto).
 */
function wcb_register_acf_pdp_offer_timer_fields()
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_wcb_pdp_offer_timer',
        'title' => 'WCB — Timer barra de oferta (PDP)',
        'fields' => array(
            array(
                'key' => 'field_wcb_pdp_offer_timer_scope',
                'label' => __('Timer da oferta (countdown)', 'wcb-theme'),
                'name' => 'wcb_pdp_offer_timer_scope',
                'type' => 'select',
                'instructions' => __('Automático: global para produtos na categoria Super Ofertas (slugs super-ofertas ou ofertas-relampago; filtrável); nos restantes, por produto.', 'wcb-theme'),
                'choices' => array(
                    'auto' => __('Automático (Super Ofertas = global; resto = por produto)', 'wcb-theme'),
                    'global' => __('Global — mesmo tempo que outros PDPs em modo global (sessão do separador)', 'wcb-theme'),
                    'product' => __('Por produto — countdown só deste produto', 'wcb-theme'),
                ),
                'default_value' => 'auto',
                'return_format' => 'value',
                'ui' => 1,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'product',
                ),
            ),
        ),
        'position' => 'side',
        'active' => true,
    ));
}

add_action('acf/init', 'wcb_register_acf_pdp_offer_timer_fields');

/**
 * Sem ACF: campo nativo WooCommerce (mesma meta key que o ACF usaria no produto).
 */
function wcb_render_pdp_offer_timer_wc_field()
{
    global $post;

    if (!is_object($post) || function_exists('acf_add_local_field_group')) {
        return;
    }

    $val = get_post_meta($post->ID, 'wcb_pdp_offer_timer_scope', true);
    if (!is_string($val)) {
        $val = '';
    }

    echo '<div class="options_group wcb-pdp-offer-timer-field">';
    woocommerce_wp_select(
        array(
            'id' => 'wcb_pdp_offer_timer_scope',
            'name' => 'wcb_pdp_offer_timer_scope',
            'value' => $val,
            'label' => __('Timer barra de oferta (PDP)', 'wcb-theme'),
            'options' => array(
                '' => __('Automático (Super Ofertas = global; resto = por produto)', 'wcb-theme'),
                'global' => __('Global (sessão do separador)', 'wcb-theme'),
                'product' => __('Por produto', 'wcb-theme'),
            ),
            'desc_tip' => true,
            'description' => __('Categorias equivalentes: super-ofertas ou ofertas-relampago. Em Automático o countdown é global. Produtos → Categorias.', 'wcb-theme'),
        )
    );
    echo '</div>';
}

add_action('woocommerce_product_options_general_product_data', 'wcb_render_pdp_offer_timer_wc_field', 16);

/**
 * @param WC_Product $product
 */
function wcb_save_pdp_offer_timer_wc_field($product)
{
    if (function_exists('acf_add_local_field_group')) {
        return;
    }

    if (!isset($_POST['wcb_pdp_offer_timer_scope'])) {
        return;
    }

    $v = sanitize_text_field(wp_unslash($_POST['wcb_pdp_offer_timer_scope']));
    if ($v === '') {
        $product->delete_meta_data('wcb_pdp_offer_timer_scope');
    } elseif (in_array($v, array('global', 'product'), true)) {
        $product->update_meta_data('wcb_pdp_offer_timer_scope', $v);
    }
}

add_action('woocommerce_admin_process_product_object', 'wcb_save_pdp_offer_timer_wc_field', 10, 1);
