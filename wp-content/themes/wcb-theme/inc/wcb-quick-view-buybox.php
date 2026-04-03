<?php
/**
 * Quick View — buybox nativo WooCommerce (produto variável), paridade com a PDP.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ID do elemento JSON de meta de swatches no contexto Quick View (evita colisão com a PDP).
 *
 * @return string
 */
function wcb_qv_variation_swatch_meta_element_id() {
	return 'wcb-variation-swatch-meta-qv';
}

/**
 * Imprime after-price + bloco de preço (IDs wcb-qv-pdp-*) + formulário variável nativo.
 *
 * @param WC_Product_Variable $variable_product Produto variável.
 */
function wcb_render_quick_view_variable_buybox( WC_Product_Variable $variable_product ) {
	$GLOBALS['product'] = $variable_product;

	$product_id = $variable_product->get_id();
	$permalink  = $variable_product->get_permalink();

	add_filter( 'wcb_variation_swatch_meta_element_id', 'wcb_qv_variation_swatch_meta_element_id' );
	?>
	<div id="wcb-qv-pdp-buybox" class="wcb-pdp-buybox product" data-wcb-qv-native-variations="1">
		<?php
		wcb_buybox_print_after_price_section(
			$variable_product,
			array(
				'rating_href' => $permalink . '#wcb-pdp-tab-reviews',
			)
		);
		wcb_buybox_print_price_block_section( $variable_product, 'wcb-qv-pdp' );
		?>

		<hr class="wcb-pdp-divider wcb-pdp-divider--buybox">

		<div class="wcb-pdp-buybox__form wcb-qv-buybox__form" id="wcb-qv-buy-area">
			<?php
			$GLOBALS['wcb_qv_variable_buybox_render'] = true;
			woocommerce_variable_add_to_cart();
			unset( $GLOBALS['wcb_qv_variable_buybox_render'] );
			?>
		</div>
	</div>
	<?php
	remove_filter( 'wcb_variation_swatch_meta_element_id', 'wcb_qv_variation_swatch_meta_element_id' );
}
