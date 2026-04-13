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
 *  inc/checkout-blocks-cartflows.css — Checkout Woo Blocks + CartFlows (enqueue em enqueue.php)
 *  inc/woocommerce.php      — WC helpers, AJAX, side cart, gift bar, live search
 *  inc/cart-page-blocks-extras.php — Carrinho em blocos: barras brinde/frete, CEP, cupom na sidebar
 *  inc/pdp-reviews.php      — PDP: avaliações (útil, toolbar ordenar/filtrar)
 *  inc/customizer.php       — Hero Banner & Super Ofertas customizer
 *  inc/newsletter.php       — Rodapé newsletter (AJAX, opção wcb_nl4_emails)
 *  inc/widgets-sidebar.php  — Widget area registration
 *  inc/blog-single.php      — Post único: leitura, breadcrumb
 *
 * @package WCB_Theme
 * @version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'WCB_DEV' ) ) {
	define( 'WCB_DEV', false );
}

define( 'WCB_VERSION', '1.5.0' );
define( 'WCB_DIR', get_template_directory() );
define( 'WCB_URI', get_template_directory_uri() );
/** Transient do carrossel "Promoções" no header (v3: igual à query da página categoria promocoes — só em oferta). */
if ( ! defined( 'WCB_PROMO_DD_TRANSIENT' ) ) {
	define( 'WCB_PROMO_DD_TRANSIENT', 'wcb_promo_dropdown_cards_promocoes_v3' );
}

/**
 * tax_query do carrossel Promoções no menu: categoria + mesma visibilidade que o arquivo /categoria-produto/promocoes/.
 *
 * @param int $product_cat_term_id term_id (product_cat).
 * @return array<int, array<string, mixed>>
 */
function wcb_promo_dropdown_tax_query( $product_cat_term_id ) {
	$product_cat_term_id = (int) $product_cat_term_id;
	$tax_query           = array(
		'relation' => 'AND',
		array(
			'taxonomy'         => 'product_cat',
			'field'            => 'term_id',
			'terms'            => $product_cat_term_id,
			'include_children' => (bool) apply_filters( 'wcb_promo_dropdown_include_child_categories', true ),
		),
	);

	if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
		$vis    = wc_get_product_visibility_term_ids();
		$not_in = array();
		if ( ! empty( $vis['exclude-from-catalog'] ) ) {
			$not_in[] = (int) $vis['exclude-from-catalog'];
		}
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! empty( $vis['outofstock'] ) ) {
			$not_in[] = (int) $vis['outofstock'];
		}
		$not_in = array_values( array_filter( $not_in ) );
		if ( $not_in ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $not_in,
				'operator' => 'NOT IN',
			);
		}
	}

	return apply_filters( 'wcb_promo_dropdown_tax_query', $tax_query, $product_cat_term_id );
}

/**
 * Slug da categoria cujo arquivo usa a lógica "só produtos em oferta" (pre_get_posts + dropdown).
 *
 * @return string
 */
function wcb_promocoes_archive_category_slug() {
	return apply_filters( 'wcb_promocoes_archive_category_slug', 'promocoes' );
}

/**
 * Argumentos do WP_Query do carrossel Promoções no header.
 * Para a categoria {@see wcb_promocoes_archive_category_slug()}, replica a mesma regra da página do arquivo
 * (intersecção com wc_get_product_ids_on_sale() ou meta _sale_price, como em pre_get_posts).
 *
 * @param int    $product_cat_term_id term_id product_cat.
 * @param string $product_cat_slug    Slug do termo (ex.: promocoes).
 * @return array<string, mixed>
 */
function wcb_promo_dropdown_wp_query_args( $product_cat_term_id, $product_cat_slug ) {
	$product_cat_term_id = (int) $product_cat_term_id;
	$product_cat_slug    = sanitize_title( (string) $product_cat_slug );

	$args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'posts_per_page'         => 12,
		'orderby'                => 'rand',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => true,
		'meta_query'             => array(
			array(
				'key'   => '_stock_status',
				'value' => 'instock',
			),
		),
		'tax_query'              => wcb_promo_dropdown_tax_query( $product_cat_term_id ),
	);

	if ( $product_cat_slug !== wcb_promocoes_archive_category_slug() ) {
		return apply_filters( 'wcb_promo_dropdown_wp_query_args', $args, $product_cat_term_id, $product_cat_slug );
	}

	$on_sale_ids = function_exists( 'wc_get_product_ids_on_sale' ) ? wc_get_product_ids_on_sale() : array();
	if ( empty( $on_sale_ids ) ) {
		return apply_filters( 'wcb_promo_dropdown_wp_query_args', $args, $product_cat_term_id, $product_cat_slug );
	}

	$max_post_in = (int) apply_filters( 'wcb_promocoes_post_in_max', 500 );
	if ( function_exists( 'wcb_promocoes_should_use_post_in' ) && wcb_promocoes_should_use_post_in( $on_sale_ids, $max_post_in ) ) {
		$args['post__in'] = array_values( array_map( 'absint', $on_sale_ids ) );
		return apply_filters( 'wcb_promo_dropdown_wp_query_args', $args, $product_cat_term_id, $product_cat_slug );
	}

	$args['meta_query'] = array(
		'relation' => 'AND',
		array(
			'key'   => '_stock_status',
			'value' => 'instock',
		),
		array(
			'key'     => '_sale_price',
			'value'   => '',
			'compare' => '!=',
			'type'    => 'CHAR',
		),
	);

	return apply_filters( 'wcb_promo_dropdown_wp_query_args', $args, $product_cat_term_id, $product_cat_slug );
}

/**
 * Quando WCB_VERSION muda, apaga transients da home que guardam HTML de product cards
 * (evita markup antigo até expirar o TTL de 12h).
 */
function wcb_maybe_bust_home_product_card_transients() {
	if ( ! function_exists( 'delete_transient' ) ) {
		return;
	}
	$stored = get_option( 'wcb_home_cards_transients_version', '' );
	if ( $stored === WCB_VERSION ) {
		return;
	}

	$fixed_keys = array(
		'wcb_home_novidades_v2',
		'wcb_home_vendidos',
		'wcb_home_estoque',
	);
	foreach ( $fixed_keys as $key ) {
		delete_transient( $key );
	}

	// IDs em promoção / hero: recalculam na próxima visita (coerente com cards novos).
	delete_transient( 'wcb_on_sale_ids' );
	delete_transient( 'wcb_hero_sale_id' );
	delete_transient( 'wcb_promo_dropdown_cards' );
	delete_transient( 'wcb_promo_dropdown_cards_promocoes' );
	delete_transient( 'wcb_promo_dropdown_cards_promocoes_v2' );
	delete_transient( 'wcb_promo_dropdown_cards_promocoes_v3' );
	delete_transient( 'wcb_flash_campaigns_product_end_v1' );
	delete_transient( 'wcb_flash_campaigns_product_end_v2' );

	// Super Ofertas: chave dinâmica wcb_home_ofertas_{md5(...)} — remover todas as instâncias.
	global $wpdb;
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_wcb_home_ofertas_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_wcb_home_ofertas_' ) . '%'
		)
	);

	update_option( 'wcb_home_cards_transients_version', WCB_VERSION, false );
}
add_action( 'after_setup_theme', 'wcb_maybe_bust_home_product_card_transients', 1 );

/**
 * URL da imagem do promo banner na front: evita fundo sólido (zoom invisível) quando
 * o customizer ainda aponta para /images/promo-banner-*.jpg inexistente no tema.
 *
 * @param string $mod_key        Chave do theme_mod (ex. promo_banner1_image).
 * @param string $legacy_basename Nome do ficheiro legado (ex. promo-banner-1.jpg).
 * @param string $fallback_url   URL externa de reserva.
 */
function wcb_promo_banner_image_src( $mod_key, $legacy_basename, $fallback_url ) {
	$mod = get_theme_mod( $mod_key );
	if ( ! is_string( $mod ) || $mod === '' ) {
		return $fallback_url;
	}
	$path_part = wp_parse_url( $mod, PHP_URL_PATH );
	if ( is_string( $path_part )
		&& preg_match( '#/themes/[^/]+/images/' . preg_quote( $legacy_basename, '#' ) . '$#i', $path_part ) ) {
		$local = get_template_directory() . '/images/' . $legacy_basename;
		if ( ! file_exists( $local ) ) {
			return $fallback_url;
		}
	}
	return $mod;
}

/* ============================================================
   MODULAR INCLUDES
   ============================================================ */
require_once WCB_DIR . '/inc/wcb-pure-helpers.php';
require_once WCB_DIR . '/inc/product-rating-display.php';
require_once WCB_DIR . '/inc/nav-walker.php';
require_once WCB_DIR . '/inc/admin-mobile-menu-quick-buy.php';
require_once WCB_DIR . '/inc/mobile-menu-drilldown.php';
require_once WCB_DIR . '/inc/enqueue.php';
require_once WCB_DIR . '/inc/wcb-debug-trust-log.php';
require_once WCB_DIR . '/inc/translations.php';
require_once WCB_DIR . '/inc/cart-checkout.php';
require_once WCB_DIR . '/inc/woocommerce/cart-mini-ajax.php';
require_once WCB_DIR . '/inc/wcb-buybox-partials.php';
require_once WCB_DIR . '/inc/woocommerce.php';
require_once WCB_DIR . '/inc/wcb-bestseller-badge.php';
require_once WCB_DIR . '/inc/wcb-quick-view-buybox.php';
require_once WCB_DIR . '/inc/cart-page-blocks-extras.php';
require_once WCB_DIR . '/inc/pdp-reviews.php';
require_once WCB_DIR . '/inc/wcb-attribute-swatches.php';
require_once WCB_DIR . '/inc/customizer.php';
require_once WCB_DIR . '/inc/carousel-backfill.php';
require_once WCB_DIR . '/inc/super-ofertas-context.php';
require_once WCB_DIR . '/inc/wcb-home-vitrines-engine.php';
require_once WCB_DIR . '/inc/wcb-super-ofertas-wc-settings.php';
require_once WCB_DIR . '/inc/wcb-flash-campaigns.php';
require_once WCB_DIR . '/inc/newsletter.php';
require_once WCB_DIR . '/inc/widgets-sidebar.php';
require_once WCB_DIR . '/inc/blog-single.php';
require_once WCB_DIR . '/inc/cep-autofill.php';
require_once WCB_DIR . '/inc/abandoned-cart.php';
require_once WCB_DIR . '/inc/wcb-filter.php';
require_once WCB_DIR . '/inc/side-cart-performance.php';
require_once WCB_DIR . '/inc/age-gate.php';
require_once WCB_DIR . '/inc/security-headers.php';

// Perfil AJAX do carrinho lateral (Xoo): define( 'WCB_PROFILE_CART_AJAX', true ); em wp-config.php
if ( defined( 'WCB_PROFILE_CART_AJAX' ) && WCB_PROFILE_CART_AJAX ) {
	require_once WCB_DIR . '/inc/cart-ajax-profile.php';
}


// Demo/import: apenas em desenvolvimento (produção: WCB_DEV false em wp-config.php).
if ( defined( 'WCB_DEV' ) && WCB_DEV ) {
	require_once WCB_DIR . '/inc/setup-demo-products.php';
	require_once WCB_DIR . '/inc/import-product-images.php';
	require_once WCB_DIR . '/inc/apply-images-logo.php';
	require_once WCB_DIR . '/inc/wcb-dev-tools-admin.php';
}

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

/**
 * Carregar CSS de blocos do core só nas páginas que usam esses blocos (menos peso nas demais).
 */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

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
    delete_transient( 'wcb_promo_dropdown_cards_promocoes' );
    delete_transient( 'wcb_promo_dropdown_cards_promocoes_v2' );
    delete_transient( 'wcb_promo_dropdown_cards_promocoes_v3' );
    delete_transient( 'wcb_on_sale_ids' );
    delete_transient( 'wcb_hero_sale_id' );
    // A chave das ofertas usa hash dos IDs — limpa pelo prefixo via DB
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_wcb_home_ofertas_%'
            OR option_name LIKE '_transient_timeout_wcb_home_ofertas_%'"
    );
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_wcb_filt_sb_%'
            OR option_name LIKE '_transient_timeout_wcb_filt_sb_%'"
    );
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_wcb_ls_v1_%'
            OR option_name LIKE '_transient_timeout_wcb_ls_v1_%'
            OR option_name LIKE '_transient_wcb_ls_v2_%'
            OR option_name LIKE '_transient_timeout_wcb_ls_v2_%'
            OR option_name LIKE '_transient_wcb_ls_v3_%'
            OR option_name LIKE '_transient_timeout_wcb_ls_v3_%'
            OR option_name LIKE '_transient_wcb_ls_v4_%'
            OR option_name LIKE '_transient_timeout_wcb_ls_v4_%'"
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

// Esconder resíduos dos plugins de wishlist (evite [class*="…"]: substrings como
// "tinvwl" em "ftinvwl" ou outras classes quebram layout fora dos botões).
add_action('wp_head', function() {
    echo '<style>
        .alg-wc-wl-btn, .alg-wc-wl-toggle-btn,
        .alg-wc-wl-btn-wrapper, .alg-wc-wl-thumb-btn,
        .alg-wc-wl-thumb-btn-shortcode-wrapper, .alg-wc-wl-icon-wrapper,
        .tinvwl_add_to_wishlist_button, .tinv-wishlist,
        .tinv-wraper, .tinvwl-tooltip,
        .tinvwl-wishlist-null, .tinvwl-shortcode-add-to-cart,
        .ti-widget-wishlist, .ti-wishlist-icon {
            display: none !important;
        }
    </style>';
}, 999);

/* ============================================================
   PROMOÇÕES — Mostrar automaticamente produtos em oferta
   Na página da categoria (slug {@see wcb_promocoes_archive_category_slug()}), a query
   cruza categoria + IDs em oferta (wc_get_product_ids_on_sale), como na loja.
   O carrossel do header usa a mesma regra em {@see wcb_promo_dropdown_wp_query_args()}.
   ============================================================ */
add_action( 'pre_get_posts', function ( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;

    if ( is_product_category( wcb_promocoes_archive_category_slug() ) ) {
        $on_sale_ids = wc_get_product_ids_on_sale();
        if ( empty( $on_sale_ids ) ) {
            return;
        }
        $max_post_in = (int) apply_filters( 'wcb_promocoes_post_in_max', 500 );
        $query->set( 'tax_query', array() );
        $query->set( 'orderby', 'date' );
        $query->set( 'order', 'DESC' );
        if ( wcb_promocoes_should_use_post_in( $on_sale_ids, $max_post_in ) ) {
            $query->set( 'post__in', $on_sale_ids );
            return;
        }
        // Catálogos grandes: evita post__in gigante — meta_query aproximada (testar variações em QA)
        $query->set(
            'meta_query',
            array(
                array(
                    'key'     => '_sale_price',
                    'value'   => '',
                    'compare' => '!=',
                    'type'    => 'CHAR',
                ),
            )
        );
    }
} );
