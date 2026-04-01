<?php
/**
 * Página Carrinho (WooCommerce Blocks) — incentivos acima do grid; CEP/cupom na sidebar.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Linhas do resumo do pedido na página carrinho (Blocks) — mesma lógica do carrinho lateral (`xoo_wsc_cart_totals`).
 *
 * @return array<int, array{key: string, label: string, value: string, action: string}>
 */
function wcb_get_cart_page_order_summary_rows() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return array();
	}

	WC()->cart->calculate_totals();

	if ( ! function_exists( 'wcb_xoo_wsc_cart_totals_discounts' ) ) {
		return array();
	}

	$seed = array(
		'subtotal' => array(
			'label' => __( 'Subtotal', 'woocommerce' ),
			'value' => '',
		),
	);

	$totals = wcb_xoo_wsc_cart_totals_discounts( $seed );
	if ( ! is_array( $totals ) ) {
		return array();
	}

	$rows = array();
	foreach ( $totals as $key => $data ) {
		if ( ! is_array( $data ) || ! isset( $data['label'], $data['value'] ) ) {
			continue;
		}
		$rows[] = array(
			'key'    => sanitize_key( (string) $key ),
			'label'  => wp_strip_all_tags( (string) $data['label'] ),
			'value'  => is_string( $data['value'] ) ? wp_kses_post( $data['value'] ) : '',
			'action' => isset( $data['action'] ) ? sanitize_key( (string) $data['action'] ) : '',
		);
	}

	return apply_filters( 'wcb_cart_page_order_summary_rows', $rows, WC()->cart );
}

/**
 * AJAX — atualizar linhas do resumo (cupom, frete, quantidades).
 */
function wcb_ajax_cart_page_summary_rows() {
	check_ajax_referer( 'wcb_side_cart', 'nonce' );

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error( array( 'message' => 'cart' ), 400 );
	}

	wp_send_json_success(
		array(
			'rows' => wcb_get_cart_page_order_summary_rows(),
		)
	);
}
add_action( 'wp_ajax_wcb_cart_page_summary_rows', 'wcb_ajax_cart_page_summary_rows' );
add_action( 'wp_ajax_nopriv_wcb_cart_page_summary_rows', 'wcb_ajax_cart_page_summary_rows' );

/**
 * Enfileira script: brinde/frete acima do layout do carrinho; CEP e cupom na sidebar.
 */
function wcb_enqueue_cart_page_blocks_extras() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	if ( ! is_cart() && ! is_page( 'carrinho' ) && ! is_page( 'cart' ) ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	$wcb_cart_pg_deps = array();
	foreach ( array( 'wc-cart-block-frontend', 'wc-blocks-checkout' ) as $h ) {
		if ( wp_script_is( $h, 'registered' ) ) {
			$wcb_cart_pg_deps[] = $h;
			break;
		}
	}

	wp_enqueue_script(
		'wcb-cart-page-extras',
		WCB_URI . '/js/cart-page-extras.js',
		$wcb_cart_pg_deps,
		WCB_VERSION,
		true
	);

	$gift_threshold = 500;
	$ship_threshold = function_exists( 'wcb_get_free_ship_threshold' ) ? (int) wcb_get_free_ship_threshold() : 199;

	$user_postcode = '';
	if ( is_user_logged_in() ) {
		$uid = get_current_user_id();
		$user_postcode = get_user_meta( $uid, 'shipping_postcode', true );
		if ( ! $user_postcode ) {
			$user_postcode = get_user_meta( $uid, 'billing_postcode', true );
		}
		$user_postcode = preg_replace( '/^(\d{5})(\d{3})$/', '$1-$2', preg_replace( '/\D/', '', (string) $user_postcode ) );
	}

	$chosen_ship_rate = '';
	if ( WC()->session ) {
		$ch = WC()->session->get( 'chosen_shipping_methods' );
		if ( is_array( $ch ) && isset( $ch[0] ) ) {
			$chosen_ship_rate = (string) $ch[0];
		}
	}

	$svg_gift   = '<svg class="wcb-sc-bar-icon wcb-sc-bar-icon--gift" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M20 12v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 12V9a2 2 0 012-2h12a2 2 0 012 2v3" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 3v18" stroke="currentColor" stroke-width="1.65" stroke-linecap="round"/><path d="M8 7h8c0-2.2-1.8-4-4-4S8 4.8 8 7z" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	$svg_truck  = '<svg class="wcb-sc-bar-icon wcb-sc-bar-icon--truck" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 18h2" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7" cy="18" r="2" stroke="currentColor" stroke-width="1.65"/><circle cx="17" cy="18" r="2" stroke="currentColor" stroke-width="1.65"/><circle cx="7" cy="18" r="0.55" fill="currentColor"/><circle cx="17" cy="18" r="0.55" fill="currentColor"/></svg>';
	$svg_ship_ok = '<svg class="wcb-sc-bar-icon wcb-sc-bar-icon--ship-ok" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 4L12 14.01l-3-3" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/></svg>';

	$initial_progress = function_exists( 'wcb_gift_progress_payload' ) ? wcb_gift_progress_payload() : array();

	wp_localize_script(
		'wcb-cart-page-extras',
		'wcbCartPageExtras',
		array(
			'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
			'nonceSideCart'          => wp_create_nonce( 'wcb_side_cart' ),
			'nonceCep'               => wp_create_nonce( 'wcb_calc_shipping' ),
			/* Primeira pintura imediata (sem admin-ajax) */
			'initialProgress'        => $initial_progress,
			'giftThreshold'          => $gift_threshold,
			'shipThreshold'          => $ship_threshold,
			'svgGift'                => $svg_gift,
			'svgTruck'               => $svg_truck,
			'svgShipOk'              => $svg_ship_ok,
			'userPostcode'           => $user_postcode,
			'chosenShipRateId'       => $chosen_ship_rate,
			'shipSumKey'             => 'wcb_ship_sum_pg_v1',
			'appliedCoupons'         => array_values( WC()->cart->get_applied_coupons() ),
			'couponDiscountByCode'   => function_exists( 'wcb_side_cart_coupon_discount_by_code' ) ? wcb_side_cart_coupon_discount_by_code() : array(),
			'checkoutButtonLabel'    => __( 'Finalizar Compra', 'wcb-theme' ),
			'orderSummaryAction'     => 'wcb_cart_page_summary_rows',
			'orderSummaryRows'       => wcb_get_cart_page_order_summary_rows(),
			'i18n'                   => array(
				'orderSummaryAria'  => __( 'Resumo dos valores do pedido', 'wcb-theme' ),
				'ariaIncentives'    => __( 'Brinde e frete grátis', 'wcb-theme' ),
				'incentiveKickerGift' => __( 'Brinde exclusivo', 'wcb-theme' ),
				'incentiveKickerShip' => __( 'Envio gratuito', 'wcb-theme' ),
				'shipUnlocked'      => __( 'Frete grátis', 'wcb-theme' ),
				'shipUnlockedTail'  => __( 'desbloqueado!', 'wcb-theme' ),
				'shipBuyPrefix'     => __( 'Compre', 'wcb-theme' ),
				'shipFor'           => __( 'para', 'wcb-theme' ),
				'shipFreeLabel'     => __( 'frete grátis', 'wcb-theme' ),
				'shipMissing'       => __( 'Faltam', 'wcb-theme' ),
				'couponLabel'       => __( 'Cupom de desconto', 'wcb-theme' ),
				'couponPlaceholder' => __( 'Digite o código', 'wcb-theme' ),
				'couponApply'       => __( 'Aplicar', 'wcb-theme' ),
				'couponEmpty'       => __( 'Informe um cupom.', 'wcb-theme' ),
				'couponRm'          => __( 'Remover', 'wcb-theme' ),
				'couponRmAria'      => __( 'Remover cupom', 'wcb-theme' ),
				'couponNetErr'      => __( 'Erro de rede. Tente de novo.', 'wcb-theme' ),
				'couponFail'        => __( 'Não foi possível aplicar.', 'wcb-theme' ),
				'cepLabel'          => __( 'Frete e entrega', 'wcb-theme' ),
				'cepCalc'           => __( 'Calcular', 'wcb-theme' ),
				'cepChange'         => __( 'Alterar', 'wcb-theme' ),
				'cepInvalid'        => __( 'Digite um CEP válido.', 'wcb-theme' ),
				'cepErrCalc'        => __( 'Erro ao calcular. Tente novamente.', 'wcb-theme' ),
				'cepNotFound'       => __( 'CEP não encontrado.', 'wcb-theme' ),
				'freeShip'          => __( 'Grátis', 'wcb-theme' ),
				'shipModalTitle'    => __( 'Como você quer receber?', 'wcb-theme' ),
				'shipModalSub'      => __( 'Selecione a entrega e continue.', 'wcb-theme' ),
				'shipModalClose'    => __( 'Fechar', 'wcb-theme' ),
				'shipWait'          => __( 'Aguarde…', 'wcb-theme' ),
				'shipLoading'       => __( 'Buscando opções de entrega…', 'wcb-theme' ),
				'shipConfirm'       => __( 'Confirmar', 'wcb-theme' ),
				'shipApplying'      => __( 'Aplicando…', 'wcb-theme' ),
				'shipFail'          => __( 'Não foi possível aplicar o frete.', 'wcb-theme' ),
				'shipNetErr'        => __( 'Erro de rede. Toque em Confirmar de novo.', 'wcb-theme' ),
				'shipOptsAria'      => __( 'Opções de entrega', 'wcb-theme' ),
				'shipEtaHint'       => __( 'Prazo confirmado na finalização do pedido', 'wcb-theme' ),
				'shipRecFree'       => __( 'Melhor opção', 'wcb-theme' ),
				'shipRecPaid'       => __( 'Recomendado', 'wcb-theme' ),
				'shipSaveTotal'     => __( 'Economia total no frete', 'wcb-theme' ),
				'cepBadge'          => __( 'CEP', 'wcb-theme' ),
				'cepEdit'           => __( 'Alterar CEP', 'wcb-theme' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'wcb_enqueue_cart_page_blocks_extras', 25 );
