<?php
/**
 * Otimiza o fluxo AJAX do Side Cart Xoo: evita um calculate_totals() duplicado
 * após set_quantity / remove_cart_item (o WC já recalculou nesses caminhos).
 *
 * Mantém o mesmo HTML dos fragments (3 templates do plugin).
 * Se o XootiX alterar a classe ou o método, rever esta integração após atualizar o plugin.
 *
 * Checklist manual (loja + checkout), após mudanças no tema ou no plugin Xoo:
 * 1. Loja: “Adicionar ao carrinho” por AJAX; abrir carrinho lateral.
 * 2. Carrinho lateral: botões + e − (stepper); conferir subtotal/total e contador no header.
 * 3. Remover linha ou levar quantidade a 0; lista e totais coerentes (ou carrinho vazio).
 * 4. Dois produtos no carrinho: alterar um e confirmar que o outro não “pisca” errado.
 * 5. Finalizar compra: alterar método de envio (se houver); totais e fragments corretos.
 * Opcional: em wp-config, define('WCB_PROFILE_CART_AJAX', true) e ver linhas [WCB cart profile] em debug.log.
 * Debug no browser: localStorage.setItem('wcb_cart_debug','1') → Console mostra ms do AJAX do stepper.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param array<string, string> $fragments Fragments do WooCommerce.
 * @return array<string, string>
 */
function wcb_xoo_side_cart_fragments_optimized( $fragments ) {
	if ( function_exists( 'WC' ) && WC()->cart ) {
		if ( empty( $GLOBALS['wcb_xoo_skip_fragment_calculate_totals'] ) ) {
			WC()->cart->calculate_totals();
		} else {
			unset( $GLOBALS['wcb_xoo_skip_fragment_calculate_totals'] );
		}
	}

	if ( ! function_exists( 'xoo_wsc_helper' ) ) {
		return $fragments;
	}

	ob_start();
	xoo_wsc_helper()->get_template( 'xoo-wsc-container.php' );
	$container = ob_get_clean();

	ob_start();
	xoo_wsc_helper()->get_template( 'xoo-wsc-slider.php' );
	$slider = ob_get_clean();

	ob_start();
	xoo_wsc_helper()->get_template( 'xoo-wsc-shortcode.php' );
	$shortcode = ob_get_clean();

	$fragments['div.xoo-wsc-container'] = $container;
	$fragments['div.xoo-wsc-slider']    = $slider;
	$fragments['div.xoo-wsc-sc-cont']   = $shortcode;

	return $fragments;
}

/**
 * Troca o filtro do plugin pelo nosso (init: o tema carrega depois de plugins_loaded).
 */
function wcb_replace_xoo_side_cart_fragment_filter() {
	if ( ! class_exists( 'Xoo_Wsc_Cart' ) || ! function_exists( 'xoo_wsc_helper' ) ) {
		return;
	}

	$xoo = Xoo_Wsc_Cart::get_instance();
	remove_filter( 'woocommerce_add_to_cart_fragments', array( $xoo, 'set_ajax_fragments' ) );
	remove_filter( 'woocommerce_update_order_review_fragments', array( $xoo, 'set_ajax_fragments' ) );

	add_filter( 'woocommerce_add_to_cart_fragments', 'wcb_xoo_side_cart_fragments_optimized', 10, 1 );
	add_filter( 'woocommerce_update_order_review_fragments', 'wcb_xoo_side_cart_fragments_optimized', 10, 1 );
}
add_action( 'init', 'wcb_replace_xoo_side_cart_fragment_filter', 20 );

/**
 * Sinaliza que os totais já foram calculados antes de get_refreshed_fragments().
 */
add_action( 'wc_ajax_xoo_wsc_update_item_quantity', static function () {
	$GLOBALS['wcb_xoo_skip_fragment_calculate_totals'] = true;
}, 0 );
