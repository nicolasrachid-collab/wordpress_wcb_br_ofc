<?php
/**
 * My Account — Orders — WCB Premium v2
 * Override de: woocommerce/templates/myaccount/orders.php
 * @package WCB_Theme
 * @version 9.5.0 (compatível)
 */
defined('ABSPATH') || exit;

$orders_count = wc_get_customer_order_count(get_current_user_id());

do_action('woocommerce_before_account_orders', $has_orders);
?>

<!-- Header premium de pedidos -->
<div class="wcb-orders-header">
    <div class="wcb-orders-header__left">
        <div class="wcb-orders-header__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <div>
            <div class="wcb-orders-header__title">Meus Pedidos</div>
            <div class="wcb-orders-header__sub">Histórico completo de compras</div>
        </div>
    </div>
    <?php if ($orders_count > 0) : ?>
        <span class="wcb-orders-header__badge"><?php echo esc_html($orders_count); ?></span>
    <?php endif; ?>
</div>

<?php if ($has_orders) : ?>

    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
        <thead>
            <tr>
                <?php foreach (wc_get_account_orders_columns() as $column_id => $column_name) : ?>
                    <th scope="col" class="woocommerce-orders-table__header woocommerce-orders-table__header-<?php echo esc_attr($column_id); ?>">
                        <span class="nobr"><?php echo esc_html($column_name); ?></span>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customer_orders->orders as $customer_order) :
                $order      = wc_get_order($customer_order);
                $item_count = $order->get_item_count() - $order->get_item_count_refunded();
                ?>
                <tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr($order->get_status()); ?> order">
                    <?php foreach (wc_get_account_orders_columns() as $column_id => $column_name) :
                        $is_order_number = 'order-number' === $column_id;
                        ?>
                        <?php if ($is_order_number) : ?>
                            <th class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr($column_id); ?>" data-title="<?php echo esc_attr($column_name); ?>" scope="row">
                        <?php else : ?>
                            <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr($column_id); ?>" data-title="<?php echo esc_attr($column_name); ?>">
                        <?php endif; ?>

                        <?php if (has_action('woocommerce_my_account_my_orders_column_' . $column_id)) : ?>
                            <?php do_action('woocommerce_my_account_my_orders_column_' . $column_id, $order); ?>

                        <?php elseif ($is_order_number) : ?>
                            <a href="<?php echo esc_url($order->get_view_order_url()); ?>"
                               aria-label="<?php echo esc_attr(sprintf(__('View order number %s', 'woocommerce'), $order->get_order_number())); ?>">
                                <?php echo esc_html(_x('#', 'hash before order number', 'woocommerce') . $order->get_order_number()); ?>
                            </a>

                        <?php elseif ('order-date' === $column_id) : ?>
                            <time datetime="<?php echo esc_attr($order->get_date_created()->date('c')); ?>">
                                <?php echo esc_html(wc_format_datetime($order->get_date_created())); ?>
                            </time>

                        <?php elseif ('order-status' === $column_id) : ?>
                            <mark class="order-status status-<?php echo esc_attr($order->get_status()); ?>">
                                <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                            </mark>

                        <?php elseif ('order-total' === $column_id) : ?>
                            <?php echo wp_kses_post(sprintf(
                                _n('%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'woocommerce'),
                                $order->get_formatted_order_total(),
                                $item_count
                            )); ?>

                        <?php elseif ('order-actions' === $column_id) : ?>
                            <?php $actions = wc_get_account_orders_actions($order);
                            if (!empty($actions)) {
                                foreach ($actions as $key => $action) {
                                    if (empty($action['aria-label'])) {
                                        $action_aria_label = sprintf(__('%1$s order number %2$s', 'woocommerce'), $action['name'], $order->get_order_number());
                                    } else {
                                        $action_aria_label = $action['aria-label'];
                                    }
                                    echo '<a href="' . esc_url($action['url']) . '" class="woocommerce-button' . esc_attr($wp_button_class) . ' button ' . sanitize_html_class($key) . '" aria-label="' . esc_attr($action_aria_label) . '">'
                                        . '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
                                        . esc_html($action['name']) . '</a>';
                                    unset($action_aria_label);
                                }
                            } ?>
                        <?php endif; ?>

                        <?php if ($is_order_number) : ?>
                            </th>
                        <?php else : ?>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php do_action('woocommerce_before_account_orders_pagination'); ?>

    <?php if (1 < $customer_orders->max_num_pages) : ?>
        <div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
            <?php if (1 !== $current_page) : ?>
                <a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button<?php echo esc_attr($wp_button_class); ?>"
                   href="<?php echo esc_url(wc_get_endpoint_url('orders', $current_page - 1)); ?>">
                    <?php esc_html_e('Previous', 'woocommerce'); ?>
                </a>
            <?php endif; ?>
            <?php if (intval($customer_orders->max_num_pages) !== $current_page) : ?>
                <a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button<?php echo esc_attr($wp_button_class); ?>"
                   href="<?php echo esc_url(wc_get_endpoint_url('orders', $current_page + 1)); ?>">
                    <?php esc_html_e('Next', 'woocommerce'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php else : ?>

    <!-- Empty state premium -->
    <div class="wcb-orders-empty">
        <div class="wcb-orders-empty__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <h3>Nenhum pedido ainda</h3>
        <p>Quando você fizer sua primeira compra, ela aparecerá aqui.</p>
        <a href="<?php echo esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>" class="wcb-orders-shop-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Explorar Produtos
        </a>
    </div>

<?php endif; ?>

<?php do_action('woocommerce_after_account_orders', $has_orders); ?>
