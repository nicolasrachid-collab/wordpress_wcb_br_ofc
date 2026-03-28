<?php
/**
 * WCB Theme — Functions & Definitions
 * Tema personalizado para White Cloud Brasil
 *
 * This file is intentionally lean. All functionality is split
 * into focused modules under the /inc/ directory:
 *
 *  inc/nav-walker.php       — Custom nav walker
 *  inc/enqueue.php          — Scripts & styles
 *  inc/translations.php     — Gettext filters & checkout field labels
 *  inc/cart-checkout.php    — Cart page header/footer & JS translation fallback
 *  inc/woocommerce.php      — WC helpers, AJAX, side cart, gift bar, live search
 *  inc/customizer.php       — Hero Banner & Super Ofertas customizer
 *  inc/newsletter.php       — Rodapé newsletter (AJAX, opção wcb_nl4_emails)
 *  inc/widgets-sidebar.php  — Widget area registration
 *
 * @package WCB_Theme
 * @version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WCB_VERSION', '1.2.3' );
define( 'WCB_DIR', get_template_directory() );
define( 'WCB_URI', get_template_directory_uri() );

/* ============================================================
   MODULAR INCLUDES
   ============================================================ */
require_once WCB_DIR . '/inc/nav-walker.php';
require_once WCB_DIR . '/inc/enqueue.php';
require_once WCB_DIR . '/inc/translations.php';
require_once WCB_DIR . '/inc/cart-checkout.php';
require_once WCB_DIR . '/inc/woocommerce.php';
require_once WCB_DIR . '/inc/wcb-attribute-swatches.php';
require_once WCB_DIR . '/inc/customizer.php';
require_once WCB_DIR . '/inc/newsletter.php';
require_once WCB_DIR . '/inc/widgets-sidebar.php';
require_once WCB_DIR . '/inc/cep-autofill.php';
require_once WCB_DIR . '/inc/abandoned-cart.php';
require_once WCB_DIR . '/inc/wcb-filter.php';


// Demo products setup (one-time, remove after use)
require_once WCB_DIR . '/inc/setup-demo-products.php';
require_once WCB_DIR . '/inc/import-product-images.php';
require_once WCB_DIR . '/inc/apply-images-logo.php';

/* ============================================================
   THEME SETUP
   ============================================================ */
function wcb_theme_setup() {
    // Language
    load_theme_textdomain( 'wcb-theme', WCB_DIR . '/languages' );

    // Theme supports
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 250,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
    add_theme_support( 'html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
    ) );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'responsive-embeds' );

    // WooCommerce Support
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );

    // Image sizes
    add_image_size( 'wcb-product-thumb', 300, 300, true );
    add_image_size( 'wcb-product-large', 600, 600, true );
    add_image_size( 'wcb-banner', 1280, 500, true );
    add_image_size( 'wcb-category', 400, 400, true );

    // Menus
    register_nav_menus( array(
        'primary'    => __( 'Menu Principal', 'wcb-theme' ),
        'departments'=> __( 'Menu Categorias', 'wcb-theme' ),
        'footer-1'   => __( 'Footer — Institucional', 'wcb-theme' ),
        'footer-2'   => __( 'Footer — Atendimento', 'wcb-theme' ),
        'footer-3'   => __( 'Footer — Minha Conta', 'wcb-theme' ),
    ) );
}
add_action( 'after_setup_theme', 'wcb_theme_setup' );

/* ============================================================
   CACHE — Invalida transients da home ao salvar qualquer produto
   ============================================================ */
add_action( 'save_post_product', 'wcb_flush_home_transients' );
add_action( 'woocommerce_update_product', 'wcb_flush_home_transients' );
function wcb_flush_home_transients() {
    delete_transient( 'wcb_home_novidades' );
    delete_transient( 'wcb_home_novidades_v2' );
    delete_transient( 'wcb_home_vendidos' );
    delete_transient( 'wcb_home_estoque' );
    delete_transient( 'wcb_promo_dropdown_cards' );
    delete_transient( 'wcb_on_sale_ids' );
    delete_transient( 'wcb_hero_sale_id' );
    // A chave das ofertas usa hash dos IDs — limpa pelo prefixo via DB
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_wcb_home_ofertas_%'
            OR option_name LIKE '_transient_timeout_wcb_home_ofertas_%'"
    );
}

/* ============================================================
   DESABILITAR PLUGINS DE WISHLIST EXTERNOS
   Conflitam com o sistema customizado de favoritos (_wcb_wishlist)
   ============================================================ */
add_action('init', function() {
    // Desabilitar Wish List for WooCommerce (alg-wc-wl)
    if ( class_exists('Alg_WC_Wish_List_Toggle_Btn') || class_exists('Alg_WC_Wish_List') ) {
        // Remover botões de wishlist dos cards de produto
        remove_all_actions('alg_wc_wl_toggle_btn_single');
        remove_all_actions('alg_wc_wl_toggle_btn_loop');
        // Remover widget/shortcodes
        add_filter('alg_wc_wl_toggle_btn_html', '__return_empty_string', 999);
        add_filter('alg_wc_wl_btn_enabled', '__return_false', 999);
    }

    // Desabilitar TI WooCommerce Wishlist
    if ( class_exists('TInvWL') ) {
        remove_all_actions('tinvwl_after_add_to_cart_button');
        add_filter('tinvwl_wishlist_btn_loop_above_on_image', '__return_false', 999);
        add_filter('tinvwl_wishlist_btn_loop_on_image', '__return_false', 999);
    }
}, 20);

// Esconder completamente via CSS qualquer resíduo dos plugins de wishlist
add_action('wp_head', function() {
    echo '<style>
        .alg-wc-wl-btn, .alg-wc-wl-toggle-btn,
        .tinvwl_add_to_wishlist_button, .tinv-wishlist,
        .tinvwl-wishlist-null, .tinvwl-shortcode-add-to-cart,
        .ti-widget-wishlist, .ti-wishlist-icon,
        [class*="alg-wc-wl"], [class*="tinvwl"] {
            display: none !important;
            visibility: hidden !important;
        }
    </style>';
}, 999);

/* ============================================================
   PROMOÇÕES — Mostrar automaticamente produtos em oferta
   Na página da categoria 'promocoes', substitui a query para
   listar TODOS os produtos com preço de venda ativo (on_sale),
   independente de estarem manualmente atribuídos à categoria.
   ============================================================ */
add_action( 'pre_get_posts', function ( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;

    if ( is_product_category( 'promocoes' ) ) {
        $on_sale_ids = wc_get_product_ids_on_sale();

        if ( ! empty( $on_sale_ids ) ) {
            // Mostra os produtos em promoção, ignorando a taxonomy
            $query->set( 'post__in', $on_sale_ids );
            $query->set( 'tax_query', array() ); // Remove filtro de categoria
            $query->set( 'orderby', 'date' );
            $query->set( 'order', 'DESC' );
        }
    }
} );
