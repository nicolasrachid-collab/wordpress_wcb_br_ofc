<?php
/**
 * Mini-cart flyout — fragmento HTML e AJAX de quantidade/remoção.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX — HTML do mini-cart (drawer fallback do tema).
 */
function wcb_mini_cart_ajax() {
	if ( ! check_ajax_referer( 'wcb-mini-cart', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'invalid_nonce' ), 403 );
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json( array( 'html' => '', 'count' => 0, 'subtotal' => 'R$ 0,00' ) );
	}

	$cart        = WC()->cart;
	$cart_items  = $cart->get_cart();
	$count       = $cart->get_cart_contents_count();
	$subtotal    = $cart->get_cart_subtotal();
	$html        = '';

	if ( empty( $cart_items ) ) {
		$shop_url = esc_url( wc_get_page_permalink( 'shop' ) );
		$html     = '<div class="wcb-mini-cart__empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <p>Seu carrinho está vazio</p>
            <a href="' . $shop_url . '" class="wcb-btn wcb-btn--primary">Ver produtos</a>
        </div>';
	} else {
		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			$_product  = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

			if ( ! $_product || ! $_product->exists() || 0 === (int) $cart_item['quantity'] ) {
				continue;
			}

			$thumbnail_id  = $_product->get_image_id();
			$thumbnail_url = $thumbnail_id
				? wp_get_attachment_image_url( $thumbnail_id, 'woocommerce_thumbnail' )
				: wc_placeholder_img_src( 'woocommerce_thumbnail' );

			$product_name  = $_product->get_name();
			$product_price = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
			$product_qty   = $cart_item['quantity'];
			$product_url   = $_product->get_permalink();

			$html .= '<div class="wcb-mini-cart__item" data-key="' . esc_attr( $cart_item_key ) . '">';
			$html .= '  <a href="' . esc_url( $product_url ) . '" class="wcb-mini-cart__item-img"><img src="' . esc_url( $thumbnail_url ) . '" alt="' . esc_attr( $product_name ) . '" width="64" height="64"></a>';
			$html .= '  <div class="wcb-mini-cart__item-info">';
			$html .= '    <a href="' . esc_url( $product_url ) . '" class="wcb-mini-cart__item-name">' . esc_html( $product_name ) . '</a>';
			$html .= '    <div class="wcb-mini-cart__item-price">' . $product_price . '</div>';
			$html .= '    <div class="wcb-mini-cart__item-actions">';
			$html .= '      <div class="wcb-mini-qty" data-key="' . esc_attr( $cart_item_key ) . '">';
			$html .= '        <button class="wcb-mini-qty__btn wcb-mini-qty__minus" aria-label="Diminuir">−</button>';
			$html .= '        <span class="wcb-mini-qty__val">' . esc_html( (string) $product_qty ) . '</span>';
			$html .= '        <button class="wcb-mini-qty__btn wcb-mini-qty__plus" aria-label="Aumentar">+</button>';
			$html .= '      </div>';
			$html .= '      <button class="wcb-mini-cart__remove" data-key="' . esc_attr( $cart_item_key ) . '" data-nonce="' . wp_create_nonce( 'wcb-mini-cart' ) . '" aria-label="Remover">';
			$html .= '        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
			$html .= '      </button>';
			$html .= '    </div>';
			$html .= '  </div>';
			$html .= '</div>';
		}
	}

	wp_send_json(
		array(
			'html'     => $html,
			'count'    => $count,
			'subtotal' => html_entity_decode( wp_strip_all_tags( $subtotal ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'empty'    => empty( $cart_items ),
		)
	);
}
add_action( 'wp_ajax_wcb_mini_cart', 'wcb_mini_cart_ajax' );
add_action( 'wp_ajax_nopriv_wcb_mini_cart', 'wcb_mini_cart_ajax' );

/**
 * AJAX — atualizar quantidade no mini-cart.
 */
function wcb_mini_cart_update_qty() {
	if ( ! check_ajax_referer( 'wcb-mini-cart', 'nonce', false ) ) {
		wp_send_json_error( 'invalid_nonce' );
	}
	$key = sanitize_text_field( wp_unslash( $_POST['key'] ?? '' ) );
	$qty = max( 0, intval( $_POST['qty'] ?? 1 ) );
	if ( ! $key ) {
		wp_send_json_error( 'invalid_key' );
	}
	WC()->cart->set_quantity( $key, $qty, true );
	WC()->cart->calculate_totals();
	wcb_mini_cart_ajax();
}
add_action( 'wp_ajax_wcb_mini_cart_update_qty', 'wcb_mini_cart_update_qty' );
add_action( 'wp_ajax_nopriv_wcb_mini_cart_update_qty', 'wcb_mini_cart_update_qty' );

/**
 * AJAX — remover linha do mini-cart.
 */
function wcb_mini_cart_remove() {
	if ( ! check_ajax_referer( 'wcb-mini-cart', 'nonce', false ) ) {
		wp_send_json_error( 'invalid_nonce' );
	}
	$key = sanitize_text_field( wp_unslash( $_POST['key'] ?? '' ) );
	if ( ! $key ) {
		wp_send_json_error( 'invalid_key' );
	}
	WC()->cart->remove_cart_item( $key );
	WC()->cart->calculate_totals();
	wcb_mini_cart_ajax();
}
add_action( 'wp_ajax_wcb_mini_cart_remove', 'wcb_mini_cart_remove' );
add_action( 'wp_ajax_nopriv_wcb_mini_cart_remove', 'wcb_mini_cart_remove' );
