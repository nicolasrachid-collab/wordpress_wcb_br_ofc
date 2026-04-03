<?php
/**
 * AJAX — combo FBT: quantidade do principal = buybox PDP (validada) + addons.
 * Rollback transacional: ou entra tudo ou remove linhas adicionadas neste request.
 *
 * @package WCB_Child
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_wcb_fbt_add_combo', 'wcb_fbt_ajax_add_combo' );
add_action( 'wp_ajax_nopriv_wcb_fbt_add_combo', 'wcb_fbt_ajax_add_combo' );

/**
 * Remove linhas do carrinho adicionadas no combo (melhor esforço).
 *
 * @param array<int, string> $keys Cart item keys.
 */
function wcb_fbt_rollback_cart_keys( array $keys ) {
	$cart = WC()->cart;
	if ( ! $cart ) {
		return;
	}
	foreach ( $keys as $key ) {
		if ( is_string( $key ) && $key !== '' ) {
			$cart->remove_cart_item( $key );
		}
	}
}

/**
 * Classifica mensagem de erro do Woo para código estável.
 *
 * @param string $message Texto do aviso.
 * @return string
 */
function wcb_fbt_classify_cart_error( $message ) {
	$m = strtolower( wp_strip_all_tags( (string) $message ) );

	$stock_hints = array( 'estoque', 'stock', 'insufficient', 'insuficiente', 'not enough', 'indisponível', 'unavailable', 'esgotado', 'out of stock' );
	foreach ( $stock_hints as $h ) {
		if ( false !== strpos( $m, $h ) ) {
			return 'stock_error';
		}
	}

	$var_hints = array( 'variation', 'variação', 'please choose', 'escolha', 'select an option', 'opção' );
	foreach ( $var_hints as $h ) {
		if ( false !== strpos( $m, $h ) ) {
			return 'invalid_variation';
		}
	}

	return 'unknown';
}

/**
 * Mensagem amigável por código (com fallback à mensagem original).
 *
 * @param string $code    Código.
 * @param string $raw     Texto original do Woo.
 * @return string
 */
function wcb_fbt_combo_error_message_for_code( $code, $raw ) {
	switch ( $code ) {
		case 'stock_error':
			return __( 'Estoque insuficiente para um dos itens do combo.', 'wcb-child' );
		case 'invalid_variation':
			return __( 'Selecione uma variação válida.', 'wcb-child' );
		case 'invalid_item':
			return __( 'Um dos itens do combo não está disponível.', 'wcb-child' );
		case 'invalid_main':
			return __( 'O produto principal não está disponível para compra.', 'wcb-child' );
		case 'invalid_data':
			return __( 'Dados do combo inválidos. Atualize a página e tente novamente.', 'wcb-child' );
		case 'cart_unavailable':
			return __( 'Carrinho indisponível no momento.', 'wcb-child' );
		case 'rollback_failed':
			return __( 'Não foi possível concluir o combo. Verifique o carrinho.', 'wcb-child' );
		default:
			$raw = trim( wp_strip_all_tags( (string) $raw ) );
			return $raw !== '' ? $raw : __( 'Não foi possível adicionar o combo. Tente novamente.', 'wcb-child' );
	}
}

/**
 * Responde erro JSON padronizado após rollback e limpeza de notices.
 *
 * @param array<int, string> $added_keys Chaves já adicionadas.
 * @param string             $code       Código do erro.
 * @param string             $message    Mensagem para o utilizador.
 * @param int                $status     HTTP status.
 */
function wcb_fbt_combo_fail( array $added_keys, $code, $message, $status = 400 ) {
	wcb_fbt_rollback_cart_keys( $added_keys );
	wc_clear_notices();

	if ( function_exists( 'wc_get_logger' ) && ! empty( $added_keys ) ) {
		wc_get_logger()->error(
			sprintf( 'WCB FBT combo rollback (%s): %s', $code, $message ),
			array( 'source' => 'wcb-fbt' )
		);
	}

	wp_send_json_error(
		array(
			'code'    => $code,
			'message' => $message,
		),
		$status
	);
}

/**
 * Adiciona ao carrinho: principal (qty buybox) + addons (atómico).
 */
function wcb_fbt_ajax_add_combo() {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
		wp_send_json_error(
			array(
				'code'    => 'cart_unavailable',
				'message' => wcb_fbt_combo_error_message_for_code( 'cart_unavailable', '' ),
			),
			500
		);
	}

	check_ajax_referer( 'wcb_fbt_combo', 'wcb_fbt_nonce' );

	$main_id  = isset( $_POST['main_id'] ) ? absint( wp_unslash( $_POST['main_id'] ) ) : 0;
	$main_qty = isset( $_POST['main_qty'] ) ? absint( wp_unslash( $_POST['main_qty'] ) ) : 1;
	$main_qty = max( 1, $main_qty );
	$items    = array();

	if ( isset( $_POST['items'] ) && is_string( $_POST['items'] ) ) {
		$decoded = json_decode( wp_unslash( $_POST['items'] ), true );
		if ( is_array( $decoded ) ) {
			$items = $decoded;
		}
	}

	if ( $main_id < 1 || ! is_array( $items ) ) {
		wp_send_json_error(
			array(
				'code'    => 'invalid_data',
				'message' => wcb_fbt_combo_error_message_for_code( 'invalid_data', '' ),
			),
			400
		);
	}

	$main = wc_get_product( $main_id );
	if ( ! $main instanceof WC_Product || ! $main->is_purchasable() ) {
		wp_send_json_error(
			array(
				'code'    => 'invalid_main',
				'message' => wcb_fbt_combo_error_message_for_code( 'invalid_main', '' ),
			),
			400
		);
	}

	require_once get_stylesheet_directory() . '/inc/wcb-fbt-helpers.php';

	$cart = WC()->cart;
	if ( ! $cart ) {
		wp_send_json_error(
			array(
				'code'    => 'cart_unavailable',
				'message' => wcb_fbt_combo_error_message_for_code( 'cart_unavailable', '' ),
			),
			500
		);
	}

	$max_main = wcb_fbt_max_purchase_qty( $main );
	if ( $main_qty > $max_main ) {
		$main_qty = $max_main;
	}

	$added_keys = array();

	$key_main = wcb_fbt_cart_add_line( $main, $main_qty );
	if ( ! is_string( $key_main ) || $key_main === '' ) {
		$raw  = wcb_fbt_last_cart_error();
		$code = wcb_fbt_classify_cart_error( $raw );
		if ( 'unknown' === $code ) {
			$code = 'invalid_main';
		}
		wp_send_json_error(
			array(
				'code'    => $code,
				'message' => wcb_fbt_combo_error_message_for_code( $code, $raw ),
			),
			400
		);
	}
	$added_keys[] = $key_main;

	foreach ( $items as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$pid = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
		$qty = isset( $row['qty'] ) ? absint( $row['qty'] ) : 0;
		if ( $pid < 1 || $qty < 1 ) {
			continue;
		}
		if ( (int) $pid === (int) $main_id ) {
			continue;
		}

		$product = wc_get_product( $pid );
		if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
			wcb_fbt_combo_fail( $added_keys, 'invalid_item', wcb_fbt_combo_error_message_for_code( 'invalid_item', '' ) );
		}

		if ( $product->is_type( 'variable' ) ) {
			wcb_fbt_combo_fail(
				$added_keys,
				'invalid_item',
				__( 'Um item do combo está incompleto. Atualize a página e tente novamente.', 'wcb-child' )
			);
		}

		$max = wcb_fbt_max_purchase_qty( $product );
		if ( $qty > $max ) {
			$qty = $max;
		}

		$key_item = wcb_fbt_cart_add_line( $product, $qty );
		if ( ! is_string( $key_item ) || $key_item === '' ) {
			$raw  = wcb_fbt_last_cart_error();
			$code = wcb_fbt_classify_cart_error( $raw );
			wcb_fbt_combo_fail( $added_keys, $code, wcb_fbt_combo_error_message_for_code( $code, $raw ) );
		}
		$added_keys[] = $key_item;
	}

	wc_add_notice( __( 'Combo adicionado ao carrinho.', 'wcb-child' ), 'success' );

	if ( get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes' ) {
		$redirect = wc_get_cart_url();
	} else {
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = home_url( '/' );
		}
		$redirect = remove_query_arg( array( 'action', '_wpnonce' ), $redirect );
	}

	wp_send_json_success(
		array(
			'message'  => __( 'Combo adicionado ao carrinho com sucesso.', 'wcb-child' ),
			'redirect' => esc_url_raw( $redirect ),
		)
	);
}

/**
 * Adiciona uma linha ao carrinho (simples ou variação).
 *
 * @param WC_Product $product Produto ou variação.
 * @param int        $qty     Quantidade.
 * @return string|false Cart item key ou false.
 */
function wcb_fbt_cart_add_line( WC_Product $product, $qty ) {
	$qty = max( 1, absint( $qty ) );
	$cart = WC()->cart;
	if ( ! $cart ) {
		return false;
	}

	$variation_id = 0;
	$attrs        = array();
	$product_id   = (int) $product->get_id();

	if ( $product->is_type( 'variation' ) ) {
		$variation_id = (int) $product->get_id();
		$product_id   = (int) $product->get_parent_id();
		$attrs        = $product->get_variation_attributes();
	}

	$key = $cart->add_to_cart( $product_id, $qty, $variation_id, $attrs );
	if ( ! $key ) {
		return false;
	}
	return $key;
}

/**
 * Última mensagem de erro do WooCommerce.
 *
 * @return string
 */
function wcb_fbt_last_cart_error() {
	$notices = wc_get_notices( 'error' );
	wc_clear_notices();
	if ( empty( $notices ) ) {
		return __( 'Não foi possível adicionar ao carrinho.', 'wcb-child' );
	}
	$first = reset( $notices );
	if ( is_array( $first ) && isset( $first['notice'] ) ) {
		return wp_strip_all_tags( $first['notice'] );
	}
	return __( 'Não foi possível adicionar ao carrinho.', 'wcb-child' );
}
