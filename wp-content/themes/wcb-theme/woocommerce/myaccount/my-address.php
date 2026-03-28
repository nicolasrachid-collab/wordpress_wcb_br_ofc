<?php
/**
 * My Account — My Addresses (List View) — WCB Premium v2
 * Override de: woocommerce/templates/myaccount/my-address.php
 * @package WCB_Theme
 * @version 9.3.0 (compatível)
 */
defined('ABSPATH') || exit;

$customer_id = get_current_user_id();

if (!wc_ship_to_billing_address_only() && wc_shipping_enabled()) {
    $get_addresses = apply_filters(
        'woocommerce_my_account_get_addresses',
        [
            'billing'  => 'Endereço de cobrança',
            'shipping' => 'Endereço de entrega',
        ],
        $customer_id
    );
} else {
    $get_addresses = apply_filters(
        'woocommerce_my_account_get_addresses',
        ['billing' => 'Endereço de cobrança'],
        $customer_id
    );
}
?>

<!-- Header premium de Endereços -->
<div class="wcb-orders-header" style="margin-bottom: 1rem;">
    <div class="wcb-orders-header__left">
        <div class="wcb-orders-header__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        </div>
        <div>
            <div class="wcb-orders-header__title">Meus Endereços</div>
            <div class="wcb-orders-header__sub">Endereços usados na finalização de compra</div>
        </div>
    </div>
</div>

<?php if (!wc_ship_to_billing_address_only() && wc_shipping_enabled()) : ?>
<div class="u-columns woocommerce-Addresses col2-set addresses">
<?php endif; ?>

<?php
$col    = 1;
$oldcol = 1;
foreach ($get_addresses as $name => $address_title) :
    $address = wc_get_account_formatted_address($name);
    $col     = $col * -1;
    $oldcol  = $oldcol * -1;

    $is_billing  = ($name === 'billing');
    $is_shipping = ($name === 'shipping');

    // Ícones SVG por tipo
    $icon_svg = $is_billing
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>';

    $icon_class = $is_billing ? 'wcb-addr-icon' : 'wcb-addr-icon wcb-addr-icon--delivery';
?>
    <div class="u-column<?php echo $col < 0 ? 1 : 2; ?> col-<?php echo $oldcol < 0 ? 1 : 2; ?> woocommerce-Address">
        <header class="woocommerce-Address-title title">
            <div class="wcb-addr-icon-wrap">
                <div class="<?php echo esc_attr($icon_class); ?>">
                    <?php echo $icon_svg; // phpcs:ignore ?>
                </div>
                <h2><?php echo esc_html($address_title); ?></h2>
            </div>
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <?php if ($is_billing) : ?>
                    <span class="wcb-addr-badge">Principal</span>
                <?php endif; ?>
                <a href="<?php echo esc_url(wc_get_endpoint_url('edit-address', $name)); ?>" class="edit">
                    <?php printf(
                        $address ? esc_html__('Editar %s', 'woocommerce') : esc_html__('Adicionar %s', 'woocommerce'),
                        ''
                    ); ?>
                    <?php echo $address
                        ? '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>'
                        : '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'; ?>
                </a>
            </div>
        </header>

        <div class="wcb-addr-divider">
            <?php echo $is_billing ? 'COBRANÇA' : 'ENTREGA'; ?>
        </div>

        <address>
            <?php if ($address) : ?>
                <?php echo wp_kses_post($address); ?>
            <?php else : ?>
                <div class="wcb-addr-empty">
                    <div class="wcb-addr-empty__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <p>Nenhum endereço cadastrado ainda.</p>
                </div>
            <?php endif; ?>
        </address>

        <?php do_action('woocommerce_my_account_after_my_address', $name); ?>
    </div>

<?php endforeach; ?>

<?php if (!wc_ship_to_billing_address_only() && wc_shipping_enabled()) : ?>
</div>
<?php endif; ?>
