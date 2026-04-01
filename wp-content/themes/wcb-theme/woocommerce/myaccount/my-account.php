<?php
/**
 * My Account page — WCB Premium Template v3
 * @package WCB_Theme
 */

defined('ABSPATH') || exit;

// User data
$current_user = wp_get_current_user();
$user_name    = $current_user->display_name ?: $current_user->user_login;
$fn           = trim( $current_user->first_name );
$ln           = trim( $current_user->last_name );
$user_email   = $current_user->user_email;
if ( $fn && $ln ) {
	$initials = strtoupper( substr( $fn, 0, 1 ) . substr( $ln, 0, 1 ) );
} elseif ( $fn ) {
	$initials = strtoupper( substr( $fn, 0, 1 ) );
} else {
	$initials = strtoupper( substr( preg_replace( '/\s+/', ' ', $user_name ), 0, 1 ) );
}
$orders_count = wc_get_customer_order_count( $current_user->ID );
$member_date  = date_i18n( 'F Y', strtotime( $current_user->user_registered ) );
?>

<!-- Breadcrumb — mesma faixa que .wcb-myaccount-wrapper (.wcb-account-strip) -->
<div class="wcb-account-breadcrumb wcb-account-strip">
    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    <span>Minha Conta</span>
</div>

<!-- Hero Banner -->
<div class="wcb-account-hero">
    <div class="wcb-container">
        <div class="wcb-account-hero__panel">
            <div class="wcb-account-hero__inner">
                <div class="wcb-account-hero__identity">
                    <div class="wcb-account-hero__avatar" aria-hidden="true">
                        <span><?php echo esc_html( $initials ); ?></span>
                    </div>
                    <div class="wcb-account-hero__info">
                        <p class="wcb-account-hero__eyebrow"><?php esc_html_e( 'Sua conta', 'wcb-theme' ); ?></p>
                        <h1 class="wcb-account-hero__name"><?php echo esc_html( $user_name ); ?></h1>
                        <p class="wcb-account-hero__email"><?php echo esc_html( $user_email ); ?></p>
                    </div>
                </div>
                <div class="wcb-account-hero__stats">
                    <div class="wcb-account-hero__stat">
                        <span class="wcb-account-hero__stat-value"><?php echo esc_html( $orders_count ); ?></span>
                        <span class="wcb-account-hero__stat-label"><?php esc_html_e( 'Pedidos', 'wcb-theme' ); ?></span>
                    </div>
                    <div class="wcb-account-hero__stat wcb-account-hero__stat--wide">
                        <span class="wcb-account-hero__stat-value"><?php echo esc_html( $member_date ); ?></span>
                        <span class="wcb-account-hero__stat-label"><?php esc_html_e( 'Membro desde', 'wcb-theme' ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Wrapper — mesma faixa que o breadcrumb -->
<div class="wcb-myaccount-wrapper wcb-account-strip">

    <?php do_action('woocommerce_account_navigation'); ?>

    <div class="woocommerce-MyAccount-content">
        <?php do_action('woocommerce_account_content'); ?>
    </div>

</div>