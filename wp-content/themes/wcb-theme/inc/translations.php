<?php
/**
 * WCB Theme — Translations
 * Gettext filters for WooCommerce, CartFlows, and checkout strings.
 * Also handles checkout fields and side cart translations.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   CHECKOUT — Tradução de strings WooCommerce / CartFlows
   Strings geradas via gettext que não são editáveis no Gutenberg.
   ============================================================ */
function wcb_translate_checkout_strings( $translated, $original, $domain ) {
    $translations = array(
        // Seções
        'Customer information'   => 'Informações do cliente',
        'Billing details'        => 'Detalhes de cobrança',
        'Shipping details'       => 'Detalhes de entrega',
        'Your order'             => 'Seu pedido',
        'Additional information' => 'Informação adicional',

        // Campos do formulário
        'First name'             => 'Nome',
        'Last name'              => 'Sobrenome',
        'Company name'           => 'Empresa (opcional)',
        'Country / Region'       => 'País',
        'Street address'         => 'Endereço',
        'House number and street name'                             => 'Nome da rua e número da casa',
        'Apartment, suite, unit, etc. (optional)'                  => 'Apartamento, suíte, sala, etc. (opcional)',
        'Apartment, suite, unit, etc.'                             => 'Apto, suíte, sala, etc.',
        'Town / City'            => 'Cidade',
        'State / County'         => 'Estado',
        'Postcode / ZIP'         => 'CEP',
        'Phone'                  => 'Telefone',
        'Email address'          => 'Endereço de e-mail',
        'Order notes'            => 'Observações do pedido',
        'Notes about your order, e.g. special notes for delivery.' => 'Observações sobre seu pedido, ex.: observações especiais sobre entrega.',

        // Tabela de pedido
        'Product'                => 'Produto',
        'Subtotal'               => 'Subtotal',
        'Total'                  => 'Total',
        'Shipping'               => 'Entrega',
        'Discount'               => 'Desconto',

        // Pagamento
        'Payment'                => 'Pagamento',
        'Place Order'            => 'Finalizar Compra',
        'Place order'            => 'Finalizar Compra',
        'Have a coupon?'         => 'Usar cupom',
        'Apply coupon'           => 'Aplicar cupom',
        'Coupon code'            => 'Código do cupom',
        'If you have a coupon code, please apply it below.' => 'Se você tem um código de cupom, aplique abaixo.',

        // Shipping options
        'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.' => 'Não há opções de envio disponíveis. Certifique-se de que seu endereço foi inserido corretamente ou entre em contato conosco caso precise de ajuda.',

        // CartFlows
        'Welcome Back'           => 'Bem-vindo de volta',
        'Cart'                   => 'Carrinho',
        'Information'            => 'Informações',
        'Finish'                 => 'Finalizar',
        'Click here to enter your code' => 'Clique aqui para inserir seu código',
        'Coupon:'                => 'Cupom:',

        // Thank You page
        'Thank you'              => 'Obrigado',
        'Your order has been received.' => 'Seu pedido foi recebido.',
        'Join the community'     => 'Participe da nossa comunidade',
        'Join now'               => 'Participar agora',

        // WooCommerce Blocks (Cart page)
        'Cart totals'            => 'Resumo do Pedido',
        'Estimated total'        => 'Total Estimado',
        'Add a coupon'           => 'Adicionar cupom',
        'Remove item'            => 'Remover',
        /* CTA carrinho em blocos — alinhado ao drawer (Checkout → Finalizar Compra) */
        'Proceed to checkout'    => 'Finalizar Compra',
    );

    if ( isset( $translations[ $original ] ) ) {
        return $translations[ $original ];
    }

    return $translated;
}
add_filter( 'gettext', 'wcb_translate_checkout_strings', 20, 3 );

/* Botão "Place Order" → "Finalizar Compra" */
function wcb_order_button_text( $text ) {
    return 'Finalizar Compra';
}
add_filter( 'woocommerce_order_button_text', 'wcb_order_button_text' );

/* Labels e placeholders dos campos do checkout */
function wcb_translate_checkout_fields( $fields ) {
    $billing_map = array(
        'billing_first_name' => array( 'label' => 'Nome',                   'placeholder' => 'Nome' ),
        'billing_last_name'  => array( 'label' => 'Sobrenome',              'placeholder' => 'Sobrenome' ),
        'billing_company'    => array( 'label' => 'Empresa (opcional)',      'placeholder' => 'Empresa (opcional)' ),
        'billing_address_1'  => array( 'label' => 'Endereço',               'placeholder' => 'Nome da rua e número da casa' ),
        'billing_address_2'  => array( 'placeholder' => 'Apartamento, suíte, sala, etc. (opcional)' ),
        'billing_city'       => array( 'label' => 'Cidade',                 'placeholder' => 'Cidade' ),
        'billing_postcode'   => array( 'label' => 'CEP',                    'placeholder' => 'CEP' ),
        'billing_phone'      => array( 'label' => 'Telefone',               'placeholder' => 'Telefone' ),
        'billing_email'      => array( 'label' => 'Endereço de e-mail',     'placeholder' => 'Endereço de e-mail' ),
    );

    foreach ( $billing_map as $field => $values ) {
        if ( isset( $fields['billing'][ $field ] ) ) {
            foreach ( $values as $key => $value ) {
                $fields['billing'][ $field ][ $key ] = $value;
            }
        }
    }

    // Order notes
    if ( isset( $fields['order']['order_comments'] ) ) {
        $fields['order']['order_comments']['label']       = 'Observações do pedido';
        $fields['order']['order_comments']['placeholder'] = 'Observações sobre seu pedido, ex.: observações especiais sobre entrega.';
    }

    return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'wcb_translate_checkout_fields', 30 );

/* ============================================================
   TRADUÇÃO DO CARRINHO LATERAL (Side Cart WooCommerce / xoo-wsc)
   ============================================================ */
function wcb_translate_side_cart_strings( $translated, $original, $domain ) {
    if ( function_exists( 'wcb_is_side_cart_active' ) && ! wcb_is_side_cart_active() ) {
        return $translated;
    }
    $map = array(
        'Your Cart'              => 'Seu Carrinho',
        'Your cart'              => 'Seu carrinho',
        'View Cart'              => 'Ver Carrinho',
        'View cart'              => 'Ver Carrinho',
        'Continue Shopping'      => 'Continuar Comprando',
        'Continue shopping'      => 'Continuar Comprando',
        'Checkout'               => 'Finalizar Compra',
        'Price: '                => 'Preço: ',
        'Qty:'                   => 'Qtd:',
        'View'                   => 'Ver',
        'Item removed'           => 'Item removido',
        'Undo?'                  => 'Desfazer?',
        'Item updated'           => 'Item atualizado',
        'Empty Cart'             => 'Carrinho vazio',
        'Save'                   => 'Economize',
        'SAVE'                   => 'ECONOMIZE',
        'Subtotal'               => 'Subtotal',
        'Your savings on this order are:'                        => 'Sua economia neste pedido:',
        'Shipping, taxes, and discounts calculated at checkout.' => 'Frete e descontos calculados no checkout.',
        'Shipping and taxes calculated at checkout.'             => 'Frete e impostos calculados no checkout.',
        'Taxes and shipping calculated at checkout'              => 'Impostos e frete calculados no checkout',
        'Calculate shipping'                                     => 'Calcular frete',
        'Please use checkout form to calculate shipping'         => 'Use o formulário de checkout para calcular o frete',
        'Please enter promo code'                                => 'Insira o código promocional',
        'Only %s% in stock'                                      => 'Apenas %s% em estoque',
        'Quantity can only be purchased in multiple of %s%'      => 'A quantidade deve ser múltiplo de %s%',
    );

    if ( isset( $map[ $original ] ) ) {
        return $map[ $original ];
    }

    return $translated;
}
add_filter( 'gettext', 'wcb_translate_side_cart_strings', 20, 3 );
