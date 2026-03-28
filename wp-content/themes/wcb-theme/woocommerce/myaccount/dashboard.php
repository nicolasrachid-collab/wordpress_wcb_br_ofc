<?php
/**
 * My Account Dashboard — WCB Premium v3
 * Override de: woocommerce/templates/myaccount/dashboard.php
 * @package WCB_Theme
 */
defined('ABSPATH') || exit;

$orders_url   = wc_get_endpoint_url('orders');
$address_url  = wc_get_endpoint_url('edit-address');
$account_url  = wc_get_endpoint_url('edit-account');
$wishlist_url = wc_get_endpoint_url('favoritos');
$shop_url     = get_permalink(wc_get_page_id('shop'));
$orders_count = wc_get_customer_order_count($current_user->ID);

// Dados dos favoritos
$fav_count = 0;
$fav_cookie = isset($_COOKIE['wcb_favorites']) ? json_decode(stripslashes($_COOKIE['wcb_favorites']), true) : array();
if (is_array($fav_cookie)) $fav_count = count($fav_cookie);
?>

<!-- Boas-vindas premium -->
<div class="wcb-dash-welcome">
    <div class="wcb-dash-welcome__text">
        <h3>Bem-vindo de volta, <?php echo esc_html($current_user->first_name ?: $current_user->display_name); ?>! 👋</h3>
        <p>Aqui você gerencia seus pedidos, endereços de entrega e preferências de conta.</p>
    </div>
    <a href="<?php echo esc_url($shop_url); ?>" class="wcb-dash-welcome__cta">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        Explorar Produtos
    </a>
</div>

<!-- Grid de ações rápidas -->
<div class="wcb-dash-grid">

    <!-- Pedidos -->
    <a href="<?php echo esc_url($orders_url); ?>" class="wcb-dash-card">
        <div class="wcb-dash-card__icon wcb-dash-card__icon--blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <div class="wcb-dash-card__title">Meus Pedidos</div>
        <div class="wcb-dash-card__sub"><?php echo esc_html($orders_count); ?> pedido<?php echo $orders_count !== 1 ? 's' : ''; ?> no total</div>
        <div class="wcb-dash-card__arrow">
            Ver pedidos
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
    </a>

    <!-- Endereços -->
    <a href="<?php echo esc_url($address_url); ?>" class="wcb-dash-card">
        <div class="wcb-dash-card__icon wcb-dash-card__icon--green">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        </div>
        <div class="wcb-dash-card__title">Endereços</div>
        <div class="wcb-dash-card__sub">Gerencie cobrança e entrega</div>
        <div class="wcb-dash-card__arrow">
            Ver endereços
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
    </a>

    <!-- Favoritos -->
    <a href="<?php echo esc_url($wishlist_url); ?>" class="wcb-dash-card">
        <div class="wcb-dash-card__icon wcb-dash-card__icon--pink">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </div>
        <div class="wcb-dash-card__title">Favoritos</div>
        <div class="wcb-dash-card__sub"><?php echo esc_html($fav_count); ?> produto<?php echo $fav_count !== 1 ? 's' : ''; ?> salvo<?php echo $fav_count !== 1 ? 's' : ''; ?></div>
        <div class="wcb-dash-card__arrow">
            Ver favoritos
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
    </a>

    <!-- Segurança / Conta -->
    <a href="<?php echo esc_url($account_url); ?>" class="wcb-dash-card">
        <div class="wcb-dash-card__icon wcb-dash-card__icon--amber">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="wcb-dash-card__title">Segurança</div>
        <div class="wcb-dash-card__sub">Senha e dados da conta</div>
        <div class="wcb-dash-card__arrow">
            Editar conta
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
    </a>

</div>

<!-- Último Pedido -->
<?php
$recent_orders = wc_get_orders(array(
    'customer_id' => $current_user->ID,
    'limit'       => 1,
    'orderby'     => 'date',
    'order'       => 'DESC',
    'status'      => array('wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending'),
));

if (!empty($recent_orders)) :
    $order      = $recent_orders[0];
    $order_id   = $order->get_id();
    $order_num  = $order->get_order_number();
    $order_date = wc_format_datetime($order->get_date_created(), 'd M Y');
    $order_total = $order->get_formatted_order_total();
    $order_items = $order->get_item_count();
    $order_status = $order->get_status();
    $order_url  = $order->get_view_order_url();

    $status_map = array(
        'processing' => array('label' => 'Processando', 'class' => 'processing'),
        'completed'  => array('label' => 'Entregue',     'class' => 'completed'),
        'on-hold'    => array('label' => 'Aguardando',   'class' => 'on-hold'),
        'pending'    => array('label' => 'Pendente',     'class' => 'pending'),
    );
    $status_info = isset($status_map[$order_status]) ? $status_map[$order_status] : array('label' => ucfirst($order_status), 'class' => $order_status);
?>
<div class="wcb-dash-last-order">
    <div class="wcb-dash-last-order__header">
        <h3 class="wcb-dash-last-order__title">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Último Pedido
        </h3>
        <a href="<?php echo esc_url($orders_url); ?>" class="wcb-dash-last-order__view-all">
            Ver todos
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
    </div>
    <a href="<?php echo esc_url($order_url); ?>" class="wcb-dash-last-order__card">
        <div class="wcb-dash-last-order__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        </div>
        <div class="wcb-dash-last-order__info">
            <div class="wcb-dash-last-order__num">
                #WCB-<?php echo esc_html($order_num); ?>
                <span class="wcb-dash-last-order__status wcb-dash-last-order__status--<?php echo esc_attr($status_info['class']); ?>">
                    <span class="wcb-dash-last-order__status-dot"></span>
                    <?php echo esc_html($status_info['label']); ?>
                </span>
            </div>
            <div class="wcb-dash-last-order__meta"><?php echo esc_html($order_date); ?> · <?php echo esc_html($order_items); ?> ite<?php echo $order_items > 1 ? 'ns' : 'm'; ?></div>
        </div>
        <div class="wcb-dash-last-order__total"><?php echo wp_kses_post($order_total); ?></div>
    </a>
</div>
<?php endif; ?>

<?php
do_action('woocommerce_account_dashboard');
do_action('woocommerce_before_my_account');
do_action('woocommerce_after_my_account');
?>
