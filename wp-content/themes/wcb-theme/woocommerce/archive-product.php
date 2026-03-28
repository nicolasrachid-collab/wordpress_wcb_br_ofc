<?php
/**
 * WCB Theme — Archive Product (Shop Page)
 * Layout: Sidebar esquerda (WBW Filter) + Grid de produtos
 *
 * @package WCB_Theme
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

get_header('shop');

do_action('woocommerce_before_main_content');
?>

<?php
/* ── Context ─────────────────────────────────────────── */
$shop_page_id = wc_get_page_id('shop');
$shop_title = $shop_page_id ? get_the_title($shop_page_id) : __('Todos os Produtos', 'wcb-theme');
global $wp_query;
$total_products = wc_get_loop_prop('total') ?: $wp_query->found_posts;
?>

<!-- ══ Unified Shop Toolbar ══════════════════════════════ -->
<div class="wcb-shop__toolbar">
    <div class="wcb-shop__wrap wcb-shop__toolbar-inner">

        <!-- Left: title + count (single line, no duplication) -->
        <div class="wcb-shop__toolbar-left">
            <div class="wcb-shop__toolbar-title-group">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="wcb-shop__home-link" aria-label="Início">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                </a>
                <span class="wcb-shop__title-sep" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6" />
                    </svg>
                </span>
                <h1 class="wcb-shop__page-title"><?php echo esc_html($shop_title); ?></h1>
                <span class="wcb-shop__toolbar-count" id="wcb-toolbar-count">
                    <?php printf('%d %s', intval($total_products), intval($total_products) === 1 ? 'produto' : 'produtos'); ?>
                </span>
            </div>
        </div>

        <!-- Right: filter toggle (mobile) + ordering -->
        <div class="wcb-shop__toolbar-right">
            <!-- Mobile: botão toggle sidebar/filtros -->
            <button class="wcb-shop__filter-toggle" id="wcb-filter-toggle" aria-label="Abrir filtros"
                aria-expanded="false">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                </svg>
                Filtros
            </button>

            <!-- View mode toggles -->
            <div class="wcb-shop__view-toggles" aria-label="Modo de visualização">
                <button class="wcb-shop__view-btn" data-view="3" aria-label="Grid 3 colunas" title="3 colunas">
                    <!-- 2×2 large squares = spacious view -->
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <rect x="0.5" y="0.5" width="6.5" height="6.5" rx="1.2" fill="currentColor"/>
                        <rect x="9" y="0.5" width="6.5" height="6.5" rx="1.2" fill="currentColor"/>
                        <rect x="0.5" y="9" width="6.5" height="6.5" rx="1.2" fill="currentColor"/>
                        <rect x="9" y="9" width="6.5" height="6.5" rx="1.2" fill="currentColor"/>
                    </svg>
                </button>
                <button class="wcb-shop__view-btn is-active" data-view="4" aria-label="Grid 4 colunas"
                    title="4 colunas">
                    <!-- 3×3 small squares = dense grid view -->
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <rect x="0.5" y="0.5" width="3.8" height="3.8" rx="0.8" fill="currentColor"/>
                        <rect x="6.1" y="0.5" width="3.8" height="3.8" rx="0.8" fill="currentColor"/>
                        <rect x="11.7" y="0.5" width="3.8" height="3.8" rx="0.8" fill="currentColor"/>
                        <rect x="0.5" y="6.1" width="3.8" height="3.8" rx="0.8" fill="currentColor"/>
                        <rect x="6.1" y="6.1" width="3.8" height="3.8" rx="0.8" fill="currentColor"/>
                        <rect x="11.7" y="6.1" width="3.8" height="3.8" rx="0.8" fill="currentColor"/>
                        <rect x="0.5" y="11.7" width="3.8" height="3.8" rx="0.8" fill="currentColor"/>
                        <rect x="6.1" y="11.7" width="3.8" height="3.8" rx="0.8" fill="currentColor"/>
                        <rect x="11.7" y="11.7" width="3.8" height="3.8" rx="0.8" fill="currentColor"/>
                    </svg>
                </button>
            </div>

            <span class="wcb-shop__toolbar-sep" aria-hidden="true"></span>

            <?php if (function_exists('woocommerce_catalog_ordering')): ?>
                <div class="wcb-shop__ordering">
                    <?php woocommerce_catalog_ordering(); ?>
                </div>
            <?php endif; ?>
        </div>


    </div>
</div>

<div class="wcb-shop__wrap">

    <!-- ══ Shop Body (Sidebar + Grid) ══════════════════════════ -->
    <div class="wcb-shop__body">

            <!-- Overlay mobile -->
            <div class="wcb-shop__sidebar-overlay" id="wcb-sidebar-overlay" aria-hidden="true"></div>

            <!-- Sidebar esquerda com filtro nativo WCB -->
            <aside class="wcb-shop__sidebar" id="wcb-shop-sidebar" aria-label="Filtros de produtos">

                <!-- Header da sidebar (mobile only) -->
                <div class="wcb-shop__sidebar-header">
                    <span class="wcb-shop__sidebar-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                        </svg>
                        Filtros
                    </span>
                    <button class="wcb-shop__sidebar-close" id="wcb-sidebar-close" aria-label="Fechar filtros">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>

                <!-- WCB Native Filter -->
                <div class="wcb-shop__filter-wrap">
                    <?php wcb_render_native_filter(); ?>
                </div>

            </aside><!-- /.wcb-shop__sidebar -->

            <!-- Conteúdo principal: grid de produtos -->
            <main class="wcb-shop__main">

                <?php
                // ── Active filter chips ──
                $chips = [];
                if (!empty($_GET['wcb_cat'])) {
                    foreach ((array) $_GET['wcb_cat'] as $cid) {
                        $term = get_term(intval($cid), 'product_cat');
                        if ($term && !is_wp_error($term)) {
                            $remove_url = remove_query_arg('wcb_cat'); // simplified
                            $chips[] = '<a class="wcb-filter-chip" href="' . esc_url(remove_query_arg('wcb_cat')) . '"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' . esc_html($term->name) . '</a>';
                        }
                    }
                }
                if (!empty($_GET['wcb_stock'])) {
                    $stock_label = $_GET['wcb_stock'] === 'instock' ? 'Em estoque' : 'Fora de estoque';
                    $chips[] = '<a class="wcb-filter-chip" href="' . esc_url(remove_query_arg('wcb_stock')) . '"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' . esc_html($stock_label) . '</a>';
                }
                if (!empty($chips)) {
                    $clear_url = get_permalink(wc_get_page_id('shop'));
                    echo '<div class="wcb-filter-chips">';
                    echo implode('', $chips);
                    echo '<a class="wcb-filter-chip wcb-filter-chip--clear" href="' . esc_url($clear_url) . '">Limpar todos</a>';
                    echo '</div>';
                }
                ?>

                <?php if (woocommerce_product_loop()): ?>

                    <?php do_action('woocommerce_before_shop_loop'); ?>

                    <?php woocommerce_product_loop_start(); ?>

                    <?php if (wc_get_loop_prop('total')): ?>
                        <?php while (have_posts()):
                            the_post(); ?>
                            <?php do_action('woocommerce_shop_loop'); ?>
                            <?php wc_get_template_part('content', 'product'); ?>
                        <?php endwhile; ?>
                    <?php endif; ?>

                    <?php woocommerce_product_loop_end(); ?>

                    <?php do_action('woocommerce_after_shop_loop'); ?>

                    <?php
                    // ── Page info indicator ──
                    $current_page = max(1, get_query_var('paged'));
                    $total_pages = $GLOBALS['wp_query']->max_num_pages ?: 1;
                    if ($total_pages > 1):
                        ?>
                        <div class="wcb-pagination-info">
                            Página <strong><?php echo $current_page; ?></strong> de <strong><?php echo $total_pages; ?></strong>
                        </div>
                    <?php endif; ?>

                <?php else: ?>

                    <?php do_action('woocommerce_no_products_found'); ?>

                <?php endif; ?>
            </main><!-- /.wcb-shop__main -->

        </div><!-- /.wcb-shop__body -->

    <?php do_action('woocommerce_after_main_content'); ?>

</div><!-- /.wcb-shop__wrap -->

<!-- Back to top -->
<button class="wcb-back-to-top" id="wcb-back-to-top" aria-label="Voltar ao topo">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15" />
    </svg>
</button>

<?php /* woocommerce_sidebar removido — usamos sidebar própria dentro do layout */ ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ── View Mode Toggle ──
        var viewBtns = document.querySelectorAll('.wcb-shop__view-btn');
        var grid = document.querySelector('.wcb-shop__main ul.products');

        viewBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                viewBtns.forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');

                var cols = btn.getAttribute('data-view');
                if (grid) {
                    grid.classList.remove('grid-3');
                    if (cols === '3') {
                        grid.classList.add('grid-3');
                    }
                }
                // Persist preference
                try { localStorage.setItem('wcb_grid_view', cols); } catch (e) { }
            });
        });

        // Restore preference
        try {
            var saved = localStorage.getItem('wcb_grid_view');
            if (saved && grid) {
                viewBtns.forEach(function (b) {
                    b.classList.toggle('is-active', b.getAttribute('data-view') === saved);
                });
                grid.classList.toggle('grid-3', saved === '3');
            }
        } catch (e) { }
    });
</script>

<?php get_footer('shop'); ?>