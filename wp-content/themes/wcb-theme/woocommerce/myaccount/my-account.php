<?php
/**
 * My Account page — WCB Premium Template v3
 * @package WCB_Theme
 */

defined('ABSPATH') || exit;

// User data
$current_user = wp_get_current_user();
$user_name    = $current_user->display_name ?: $current_user->user_login;
$first_name   = $current_user->first_name ?: $user_name;
$user_email   = $current_user->user_email;
$initials     = strtoupper(substr($first_name, 0, 1));
$orders_count = wc_get_customer_order_count($current_user->ID);
$member_date  = date_i18n('F Y', strtotime($current_user->user_registered));
?>

<!-- Breadcrumb -->
<div class="wcb-account-breadcrumb">
    <div class="wcb-container">
        <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        <span>Minha Conta</span>
    </div>
</div>

<!-- Hero Banner -->
<div class="wcb-account-hero">
    <div class="wcb-container">
        <div class="wcb-account-hero__inner">
            <div class="wcb-account-hero__avatar">
                <span><?php echo esc_html($initials); ?></span>
            </div>
            <div class="wcb-account-hero__info">
                <h1 class="wcb-account-hero__name"><?php echo esc_html($user_name); ?></h1>
                <p class="wcb-account-hero__email"><?php echo esc_html($user_email); ?></p>
            </div>
            <div class="wcb-account-hero__stats">
                <div class="wcb-account-hero__stat">
                    <span class="wcb-account-hero__stat-value"><?php echo esc_html($orders_count); ?></span>
                    <span class="wcb-account-hero__stat-label">Pedidos</span>
                </div>
                <div class="wcb-account-hero__stat wcb-account-hero__stat--active">
                    <span class="wcb-account-hero__stat-value">
                        <span class="wcb-account-hero__stat-dot"></span> Ativa
                    </span>
                    <span class="wcb-account-hero__stat-label">Conta</span>
                </div>
                <div class="wcb-account-hero__stat">
                    <span class="wcb-account-hero__stat-value"><?php echo esc_html($member_date); ?></span>
                    <span class="wcb-account-hero__stat-label">Membro Desde</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Wrapper -->
<div class="wcb-container">
    <div class="wcb-myaccount-wrapper">

        <?php do_action('woocommerce_account_navigation'); ?>

        <div class="woocommerce-MyAccount-content">
            <?php do_action('woocommerce_account_content'); ?>
        </div>

    </div>
</div>