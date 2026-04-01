<?php
/**
 * WCB Theme — Cart & Checkout Customization
 * Ajustes na página do carrinho WooCommerce (título/newsletter) e traduções Blocks.
 * and JS fallback translations for WooCommerce Blocks components.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   CART PAGE — Oculta newsletter e título da página (layout limpo).
   Rodapé institucional (#wcb-footer) permanece visível.
   ============================================================ */
function wcb_cart_custom_header() {
    if ( ! is_cart() ) return;

    echo '<style id="wcb-cart-header-override">
        body.woocommerce-cart .wcb-newsletter,
        body.woocommerce-cart .wcb-newsletter-section,
        body.woocommerce-cart .newsletter-section,
        body.woocommerce-cart section[class*="newsletter"],
        body.woocommerce-cart h1.wcb-page-title,
        body.woocommerce-cart .entry-title,
        body.woocommerce-cart .wp-block-post-title,
        body.woocommerce-cart .page-title {
            display: none !important;
        }
    </style>' . "\n";
}
add_action( 'wp_head', 'wcb_cart_custom_header', 999 );

/* ============================================================
   CARTFLOWS PAGES — Inject full WCB site header
   CartFlows Canvas template skips get_header(), so the 
   top bar, header, and nav are never rendered. We capture
   just the body-level HTML from header.php and inject it.
   ============================================================ */
function wcb_inject_site_header_on_cartflows() {
    // Inject on CartFlows steps OR WooCommerce native checkout (page with cartflows-canvas class)
    if ( ! is_singular( 'cartflows_step' ) && ! is_checkout() ) return;

    // Directly include the nav-only partial that outputs topbar + header + nav
    $partial = get_template_directory() . '/inc/site-header-partial.php';
    if ( file_exists( $partial ) ) {
        include $partial;
    }
}
add_action( 'wp_body_open', 'wcb_inject_site_header_on_cartflows', 0 );

/* Kept for reference — no longer injected on cart page since the real
   WCB site header is now visible. The progress bar is shown separately. */
function wcb_cart_header_html() {
    // Intentionally unhooked — the real site header now shows on the cart page
}
// add_action( 'wp_body_open', 'wcb_cart_header_html', 1 ); // disabled


/* Rodapé mínimo removido: o tema usa footer.php (#wcb-footer) como nas outras páginas. */

/* ============================================================
   FREE SHIPPING URGENCY BAR
   Shows how much more the user needs to spend to get free shipping.
   ============================================================ */
function wcb_free_shipping_bar() {
    if ( ! is_cart() ) return;
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) return;

    $threshold = function_exists('wcb_get_free_ship_threshold') ? wcb_get_free_ship_threshold() : 199;

    // Subtotal (excluding gifts)
    $subtotal = 0;
    foreach ( WC()->cart->get_cart() as $item ) {
        if ( ! empty( $item['mhfgfwc_free_gift'] ) ) continue;
        $subtotal += (float) $item['line_subtotal'];
    }

    $remaining = max( 0, $threshold - $subtotal );
    $progress  = $subtotal > 0 ? min( 100, ( $subtotal / $threshold ) * 100 ) : 0;
    $unlocked  = $remaining <= 0 && $subtotal > 0;
    ?>
    <div class="wcb-shipping-bar" id="wcb-shipping-bar">
        <?php if ( $unlocked ) : ?>
            <span class="wcb-shipping-bar__text">🎉 <strong>Frete grátis</strong> desbloqueado!</span>
        <?php else : ?>
            <span class="wcb-shipping-bar__text">🚚 Faltam <strong>R$ <?php echo number_format( $remaining, 2, ',', '.' ); ?></strong> para <strong>frete grátis</strong></span>
        <?php endif; ?>
        <div class="wcb-shipping-bar__track">
            <div class="wcb-shipping-bar__fill" style="width: <?php echo esc_attr( $progress ); ?>%"></div>
        </div>
    </div>
    <script>
    (function(){
        var threshold = <?php echo (int) $threshold; ?>;
        function updateShippingBar(subtotal){
            var bar = document.getElementById('wcb-shipping-bar');
            if(!bar) return;
            var remaining = Math.max(0, threshold - subtotal);
            var pct = subtotal > 0 ? Math.min(100, (subtotal/threshold)*100) : 0;
            var fill = bar.querySelector('.wcb-shipping-bar__fill');
            var text = bar.querySelector('.wcb-shipping-bar__text');
            if(fill) fill.style.width = pct + '%';
            if(text){
                if(remaining <= 0){
                    text.innerHTML = '🎉 <strong>Frete grátis</strong> desbloqueado!';
                } else {
                    text.innerHTML = '🚚 Faltam <strong>R$ ' + remaining.toLocaleString('pt-BR',{minimumFractionDigits:2}) + '</strong> para <strong>frete grátis</strong>';
                }
            }
        }
        if(typeof jQuery!=='undefined'){
            jQuery(document.body).on('wc_fragments_refreshed updated_cart_totals', function(){
                /* Barras de frete e brinde já atualizadas por woocommerce.php via DOM — sem AJAX extra */
            });
        }

    })();
    </script>
    <?php
}
add_action( 'wp_body_open', 'wcb_free_shipping_bar', 2 );

/* ============================================================
   PAYMENT LOGOS above the checkout submit button
   ============================================================ */
function wcb_payment_logos_before_submit() {
    if ( ! is_singular('cartflows_step') && ! is_checkout() ) return;
    ?>
    <div class="wcb-payment-logos">
        <span class="wcb-payment-logos__label">Pagamento seguro</span>
        <div class="wcb-payment-logos__icons">
            <!-- Pix -->
            <span class="wcb-pay-icon wcb-pay-pix" title="Pix">
                <svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" width="32" height="32"><path fill="#32BCAD" d="M378.7 395.3l-89.8-89.8c-8.1-8.1-21.4-8.1-29.5 0l-89.8 89.8c-4 4-9.5 6.3-15.2 6.3H121l113.3 113.3c12 12 31.6 12 43.6 0L391 401.6h-12.4c-5.7 0-11.2-2.3-15.2-6.3zm0-278.6c4 4 6.3 9.5 6.3 15.2v13.4L277.6 38c-12-12-31.6-12-43.6 0L120.9 151.3h33.3c5.7 0 11.2 2.3 15.2 6.3l89.8 89.8c8.1 8.1 21.4 8.1 29.5 0l89.8-89.8zM120 281.9l-95.4-95.4c-12-12-12-31.6 0-43.6l28.2-28.2 109.5 109.5c4 4 6.3 9.5 6.3 15.2v15.6c0 5.7-2.3 11.2-6.3 15.2l-42.4 42.4V281.9zm272 0v30.6l-42.4-42.4c-4-4-6.3-9.5-6.3-15.2v-15.6c0-5.7 2.3-11.2 6.3-15.2L459.1 115l28.2 28.2c12 12 12 31.6 0 43.6L392 281.9z"/></svg>
            </span>
            <!-- Visa -->
            <span class="wcb-pay-icon wcb-pay-visa" title="Visa">
                <svg viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" width="44" height="28"><rect width="60" height="40" rx="4" fill="#1a1f71"/><text x="50%" y="57%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-weight="bold" font-size="18" fill="#fff" letter-spacing="-1">VISA</text></svg>
            </span>
            <!-- Mastercard -->
            <span class="wcb-pay-icon wcb-pay-mc" title="Mastercard">
                <svg viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" width="44" height="28"><rect width="60" height="40" rx="4" fill="#252525"/><circle cx="22" cy="20" r="12" fill="#EB001B"/><circle cx="38" cy="20" r="12" fill="#F79E1B"/><path d="M30 10.5a12 12 0 010 19 12 12 0 010-19z" fill="#FF5F00"/></svg>
            </span>
            <!-- Elo -->
            <span class="wcb-pay-icon wcb-pay-elo" title="Elo">
                <svg viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" width="44" height="28"><rect width="60" height="40" rx="4" fill="#fff" stroke="#ddd" stroke-width="1"/><text x="50%" y="57%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-weight="900" font-size="17" fill="#000">elo</text></svg>
            </span>
        </div>
    </div>
    <?php
}
add_action( 'woocommerce_review_order_before_submit', 'wcb_payment_logos_before_submit', 5 );
add_action( 'cartflows_checkout_before_place_order', 'wcb_payment_logos_before_submit', 5 );

/* ============================================================
   JS TRANSLATION FALLBACK
   Handles WooCommerce Blocks React-rendered strings that cannot
   be caught via gettext filters. Uses delayed execution to
   wait for React to render.
   ============================================================ */
function wcb_blocks_translation_js() {
    if ( is_singular( 'cartflows_step' ) || is_checkout() || is_cart() ) {
        echo '<script>
        (function(){
            var map = {
                "Have a coupon?": "Usar Cupom",
                "Click here to enter your code": "Clique aqui para inserir seu código",
                "Your order has been received.": "Seu pedido foi recebido.",
                "Refund policy": "Política de reembolso",
                "Privacy policy": "Política de privacidade",
                "Terms of service": "Termos de serviço",
                "Add coupons": "Adicionar cupom",
                "ESTIMATED TOTAL": "TOTAL ESTIMADO",
                "TOTAL NO CARRINHO": "RESUMO DO PEDIDO",
                "Remover item": "Remover",
                "Remove item": "Remover",
                "CHOOSE YOUR FREE GIFT": "ESCOLHA SEU BRINDE",
                "Choose Your Free Gift": "Escolha Seu Brinde",
                "Add Gift": "Adicionar Brinde",
                "Welcome Back": "Bem-vindo de volta",
                "Shipping": "Entrega",
                "Order notes": "Observações do pedido",
                "Notes about your order, e.g. special notes for delivery.": "Observações sobre seu pedido, ex.: observações especiais sobre entrega.",
                "Country / Region": "País / Região",
                "There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.": "Não há opções de envio disponíveis. Certifique-se de que seu endereço foi inserido corretamente ou entre em contato conosco caso precise de ajuda."
            };
            /* Targeted WooCommerce Blocks selectors */
            var wcbSelectors = {
                ".wc-block-cart__totals-title": "RESUMO DO PEDIDO",
                ".wc-block-components-totals-footer-item .wc-block-components-totals-item__label": "TOTAL ESTIMADO"
            };
            function translate(){
                document.querySelectorAll("a, span, p, strong, li, h2, h3, h4, button, label, th, td, div").forEach(function(el){
                    if(el.children.length > 2) return;
                    var t = el.textContent.trim();
                    if(map[t]) el.textContent = map[t];
                });
                for(var sel in wcbSelectors){
                    document.querySelectorAll(sel).forEach(function(el){ el.textContent = wcbSelectors[sel]; });
                }
                document.querySelectorAll("p, span, div").forEach(function(el){
                    if(el.innerHTML && el.innerHTML.indexOf("Yoursite") > -1){
                        el.innerHTML = el.innerHTML.replace(/Yoursite/g,"White Cloud Brasil");
                        el.innerHTML = el.innerHTML.replace(/All rights reserved/g,"Todos os direitos reservados");
                    }
                });
                /* Partial match: Welcome Back (concatenated with username) */
                document.querySelectorAll("p, span, div, strong").forEach(function(el){
                    if(el.childNodes.length <= 3 && el.textContent.indexOf("Welcome Back") > -1){
                        el.innerHTML = el.innerHTML.replace(/Welcome Back/g, "Bem-vindo de volta");
                    }
                    if(el.childNodes.length <= 3 && el.textContent.indexOf("Country / Region") > -1){
                        el.innerHTML = el.innerHTML.replace(/Country \/ Region/g, "País / Região");
                    }
                });
            }
            document.addEventListener("DOMContentLoaded", translate);
            document.addEventListener("DOMContentLoaded", function(){
                setTimeout(translate, 1500);
                setTimeout(translate, 3000);
            });
        })();
        </script>' . "\n";
    }
}
add_action( 'wp_footer', 'wcb_blocks_translation_js', 999 );
