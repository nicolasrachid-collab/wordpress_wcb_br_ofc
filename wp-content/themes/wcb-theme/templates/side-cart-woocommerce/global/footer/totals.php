<?php
/**
 * Side Cart — footer totals (override tema WCB)
 *
 * @package WCB_Theme
 * @version 2.7.1 — base XootiX; + região acessível e classe wcb-cart-summary
 */

if (!defined('ABSPATH')) {
    exit;
}

extract(Xoo_Wsc_Template_Args::footer_totals());

if (WC()->cart->is_empty()) {
    return;
}

?>
<div
	class="xoo-wsc-ft-totals wcb-cart-summary"
	role="region"
	aria-live="polite"
	aria-relevant="additions text"
	aria-label="<?php echo esc_attr__('Resumo dos valores do pedido', 'wcb-theme'); ?>"
>
	<?php foreach ($totals as $key => $data) : ?>
		<div class="xoo-wsc-ft-amt xoo-wsc-ft-amt-<?php echo esc_attr($key); ?> <?php echo isset($data['action']) ? 'xoo-wsc-' . esc_attr($data['action']) : ''; ?>">
			<span class="xoo-wsc-ft-amt-label"><?php echo wp_kses_post($data['label']); ?></span>
			<span class="xoo-wsc-ft-amt-value"><?php echo wp_kses_post($data['value']); ?></span>
		</div>
	<?php endforeach; ?>

	<?php do_action('xoo_wsc_totals_end'); ?>
</div>
