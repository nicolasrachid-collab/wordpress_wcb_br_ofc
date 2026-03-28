<?php
/**
 * My Account — Downloads — WCB Premium v2
 * Override de: woocommerce/templates/myaccount/downloads.php
 * @package WCB_Theme
 * @version 7.8.0 (compatível)
 */
defined('ABSPATH') || exit;

$downloads     = WC()->customer->get_downloadable_products();
$has_downloads = (bool) $downloads;

do_action('woocommerce_before_account_downloads', $has_downloads);
?>

<!-- Header premium de Downloads -->
<div class="wcb-dl-header">
    <div class="wcb-dl-header__left">
        <div class="wcb-dl-header__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        </div>
        <div>
            <div class="wcb-dl-header__title">Downloads</div>
            <div class="wcb-dl-header__sub">Seus arquivos digitais disponíveis</div>
        </div>
    </div>
</div>

<?php if ($has_downloads) : ?>

    <?php do_action('woocommerce_before_available_downloads'); ?>
    <?php do_action('woocommerce_available_downloads', $downloads); ?>
    <?php do_action('woocommerce_after_available_downloads'); ?>

<?php else : ?>

    <!-- Empty state premium de downloads -->
    <div class="wcb-orders-empty">
        <div class="wcb-orders-empty__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        </div>
        <h3>Nenhum download disponível</h3>
        <p>Compre produtos digitais para que eles apareçam aqui para download.</p>
        <a href="<?php echo esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>" class="wcb-orders-shop-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Explorar Produtos
        </a>
    </div>

<?php endif; ?>

<?php do_action('woocommerce_after_account_downloads', $has_downloads); ?>
