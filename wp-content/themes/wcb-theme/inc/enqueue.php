<?php
/**
 * WCB Theme — Asset Enqueue
 * Scripts, styles, and inline JS registration.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Google Fonts sem bloquear render: preload → stylesheet após download.
 *
 * @param string $href URL completa da folha de estilo.
 * @return string HTML seguro.
 */
function wcb_google_fonts_nonblocking_link( $href ) {
	$href_esc = esc_url( $href );
	return sprintf(
		'<link rel="preload" as="style" href="%1$s" onload="this.onload=null;this.rel=\'stylesheet\'" />' .
		'<noscript><link rel="stylesheet" href="%1$s" /></noscript>',
		$href_esc
	) . "\n";
}

/**
 * @param string $tag  HTML original.
 * @param string $handle Style handle.
 * @param string $href  URL (WP 5.9+).
 */
function wcb_async_google_fonts_stylesheet( $tag, $handle, $href = '', $media = 'all' ) {
	if ( 'wcb-google-fonts' !== $handle ) {
		return $tag;
	}
	if ( '' === $href && preg_match( '/href\s*=\s*["\']([^"\']+)/', $tag, $m ) ) {
		$href = $m[1];
	}
	if ( '' === $href ) {
		return $tag;
	}
	return wcb_google_fonts_nonblocking_link( $href );
}
add_filter( 'style_loader_tag', 'wcb_async_google_fonts_stylesheet', 10, 4 );

/* ============================================================
   ENQUEUE STYLES & SCRIPTS
   ============================================================ */
function wcb_enqueue_assets() {
    // Preconnect para Google Fonts (reduz latência)
    wp_enqueue_style(
        'wcb-google-fonts-preconnect',
        'https://fonts.googleapis.com',
        array(),
        null
    );
    add_filter( 'style_loader_tag', function( $html, $handle ) {
        if ( $handle === 'wcb-google-fonts-preconnect' ) {
            return '<link rel="preconnect" href="https://fonts.googleapis.com" />' . "\n"
                 . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />' . "\n";
        }
        return $html;
    }, 10, 2 );

    // Google Fonts — Inter (tag não bloqueante via filtro style_loader_tag)
    wp_enqueue_style(
        'wcb-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
        array( 'wcb-google-fonts-preconnect' ),
        null
    );

    // Theme stylesheet
    wp_enqueue_style(
        'wcb-style',
        get_stylesheet_uri(),
        array( 'wcb-google-fonts' ),
        WCB_VERSION
    );

    wp_enqueue_style(
        'wcb-paged-carousel-touch',
        WCB_URI . '/css/wcb-paged-carousel-touch.css',
        array( 'wcb-style' ),
        WCB_VERSION
    );

    $wcb_wc_qv_scripts = class_exists( 'WooCommerce' ) && ! is_cart() && ! is_checkout() && ! is_admin();
    if ( $wcb_wc_qv_scripts ) {
        wp_enqueue_script( 'wc-add-to-cart' );
        wp_enqueue_script( 'wc-add-to-cart-variation' );
        wp_enqueue_script(
            'wcb-variation-buybox',
            WCB_URI . '/js/wcb-variation-buybox.js',
            array( 'jquery', 'wc-add-to-cart-variation' ),
            WCB_VERSION,
            true
        );
    }

    $wcb_main_deps = array( 'jquery' );
    if ( $wcb_wc_qv_scripts ) {
        $wcb_main_deps[] = 'wcb-variation-buybox';
    }

    // Theme JS
    wp_enqueue_script(
        'wcb-main',
        WCB_URI . '/js/main.js',
        $wcb_main_deps,
        WCB_VERSION,
        true
    );

    // Cursor personalizado (desktop; respeita reduced-motion e campos de texto)
    wp_enqueue_style(
        'wcb-custom-cursor',
        WCB_URI . '/custom-cursor.css',
        array( 'wcb-style' ),
        WCB_VERSION
    );
    wp_enqueue_script(
        'wcb-custom-cursor',
        WCB_URI . '/js/custom-cursor.js',
        array( 'wcb-main' ),
        WCB_VERSION,
        true
    );

    // Pass data to JS
    wp_localize_script( 'wcb-main', 'wcbData', array(
        'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
        'siteUrl'         => home_url( '/' ),
        'nonce'           => wp_create_nonce( 'wcb_nonce' ),
        'miniCartNonce'   => wp_create_nonce( 'wcb-mini-cart' ),
        'publicAjaxNonce' => wp_create_nonce( 'wcb_public_ajax' ),
    ) );

    // PDP — avaliações: ordenar, filtrar, voto "Útil"
    if ( function_exists( 'is_product' ) && is_product() ) {
        wp_enqueue_script(
            'wcb-pdp-reviews',
            WCB_URI . '/js/pdp-reviews.js',
            array(),
            WCB_VERSION,
            true
        );
        wp_localize_script(
            'wcb-pdp-reviews',
            'wcbPdpReviews',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'i18n'    => array(
                    'error' => __( 'Não foi possível registrar. Tente de novo.', 'wcb-theme' ),
                ),
            )
        );

        wp_enqueue_style(
            'wcb-pdp-responsive-phase1',
            WCB_URI . '/css/wcb-pdp-responsive-phase1.css',
            array( 'wcb-style' ),
            WCB_VERSION
        );
    }

    // My Account premium styles (only on account pages)
    if ( is_account_page() ) {
        wp_enqueue_style(
            'wcb-myaccount',
            WCB_URI . '/inc/myaccount.css',
            array( 'wcb-style' ),
            WCB_VERSION
        );
    }

    // Checkout WooCommerce Blocks (CartFlows / finalizar-compra) — alinha com Minha conta
    if ( function_exists( 'is_checkout' ) && ( is_checkout() || is_singular( 'cartflows_step' ) ) ) {
        wp_enqueue_style(
            'wcb-checkout-blocks-cartflows',
            WCB_URI . '/inc/checkout-blocks-cartflows.css',
            array( 'wcb-style' ),
            WCB_VERSION
        );
    }

    // WCB Native Filter (shop + category pages)
    if ( is_shop() || is_product_category() || is_product_tag() ) {
        wp_enqueue_style(
            'wcb-filter',
            WCB_URI . '/inc/wcb-filter.css',
            array( 'wcb-style' ),
            WCB_VERSION
        );
        wp_enqueue_script(
            'wcb-filter',
            WCB_URI . '/inc/wcb-filter.js',
            array(),
            WCB_VERSION,
            true
        );
    }

    // Fase 3 — navegação global: depois do tema; na loja, depois de wcb-filter (se enfileirado)
    $wcb_nav_phase3_deps = array( 'wcb-style' );
    if ( ( is_shop() || is_product_category() || is_product_tag() ) && wp_style_is( 'wcb-filter', 'enqueued' ) ) {
        $wcb_nav_phase3_deps[] = 'wcb-filter';
    }
    wp_enqueue_style(
        'wcb-navigation-phase3',
        WCB_URI . '/css/wcb-navigation-responsive-phase3.css',
        $wcb_nav_phase3_deps,
        WCB_VERSION
    );

    // Nav mobile: depende de phase3 → impressão depois dele (cascata correta vs header grid / tablet).
    wp_enqueue_style(
        'wcb-nav-mobile-bp',
        get_template_directory_uri() . '/css/wcb-nav-mobile-bp.css',
        array( 'wcb-navigation-phase3' ),
        WCB_VERSION
    );

    $wcb_is_cart_page = class_exists( 'WooCommerce' ) && ( is_cart() || is_page( 'carrinho' ) || is_page( 'cart' ) );

    // Página Carrinho (WooCommerce Blocks): layout + componentes alinhados ao carrinho lateral
    if ( $wcb_is_cart_page ) {
        $wcb_cart_premium_deps = array( 'wcb-style' );
        // Ícone remover = .xoo-wsc-icon-trash (plugin só enfileira fontes em isSideCartPage; no /carrinho/ não carregavam)
        if ( defined( 'XOO_WSC_URL' ) && defined( 'XOO_WSC_VERSION' ) ) {
            wp_enqueue_style(
                'wcb-xoo-wsc-fonts-cart',
                XOO_WSC_URL . '/assets/css/xoo-wsc-fonts.css',
                array(),
                XOO_WSC_VERSION
            );
            $wcb_cart_premium_deps[] = 'wcb-xoo-wsc-fonts-cart';
        }
        wp_enqueue_style(
            'wcb-cart-premium',
            WCB_URI . '/cart-premium.css',
            $wcb_cart_premium_deps,
            WCB_VERSION
        );
        wp_enqueue_style(
            'wcb-side-cart-premium',
            WCB_URI . '/side-cart-premium.css',
            array( 'wcb-style' ),
            WCB_VERSION
        );
    }

    // Side Cart premium overrides (só com plugin Xoo ativo e tema permitindo)
    if ( class_exists( 'WooCommerce' ) && function_exists( 'wcb_is_side_cart_active' ) && wcb_is_side_cart_active() ) {
        if ( ! $wcb_is_cart_page ) {
            wp_enqueue_style(
                'wcb-side-cart-premium',
                WCB_URI . '/side-cart-premium.css',
                array( 'wcb-style', 'xoo-wsc-style' ),
                WCB_VERSION
            );
        }
    }

    wp_enqueue_script(
        'wcb-testimonials-carousel',
        WCB_URI . '/assets/js/wcb-testimonials-carousel.js',
        array( 'wcb-main' ),
        WCB_VERSION,
        true
    );
    wp_enqueue_script(
        'wcb-mega-menu-footer',
        WCB_URI . '/assets/js/wcb-mega-menu-footer.js',
        array( 'wcb-main' ),
        WCB_VERSION,
        true
    );
}

/**
 * Fase 2 — responsividade unificada carrinho / checkout / CartFlows / side cart.
 */
function wcb_enqueue_cart_checkout_responsive_phase2() {
    if ( ! class_exists( 'WooCommerce' ) || is_admin() ) {
        return;
    }

    $on_cart_page = ( function_exists( 'is_cart' ) && is_cart() )
        || is_page( 'carrinho' )
        || is_page( 'cart' );
    $on_checkout  = function_exists( 'is_checkout' ) && is_checkout();
    $on_cf_step   = is_singular( 'cartflows_step' );
    $side_cart    = function_exists( 'wcb_is_side_cart_active' ) && wcb_is_side_cart_active();

    if ( ! $on_cart_page && ! $on_checkout && ! $on_cf_step && ! $side_cart ) {
        return;
    }

    $deps = array( 'wcb-style' );

    if ( $on_cart_page ) {
        if ( wp_style_is( 'wcb-cart-premium', 'registered' ) ) {
            $deps[] = 'wcb-cart-premium';
        }
        if ( wp_style_is( 'wcb-side-cart-premium', 'registered' ) ) {
            $deps[] = 'wcb-side-cart-premium';
        }
    }

    if ( $on_checkout || $on_cf_step ) {
        if ( wp_style_is( 'wcb-checkout-blocks-cartflows', 'registered' ) ) {
            $deps[] = 'wcb-checkout-blocks-cartflows';
        }
    }

    if ( $side_cart && ! $on_cart_page ) {
        if ( wp_style_is( 'xoo-wsc-style', 'registered' ) ) {
            $deps[] = 'xoo-wsc-style';
        }
        if ( wp_style_is( 'wcb-side-cart-premium', 'registered' ) ) {
            $deps[] = 'wcb-side-cart-premium';
        }
    }

    wp_enqueue_style(
        'wcb-cart-checkout-responsive-phase2',
        WCB_URI . '/css/wcb-cart-checkout-responsive-phase2.css',
        array_values( array_unique( $deps ) ),
        WCB_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'wcb_enqueue_cart_checkout_responsive_phase2', 40 );

/**
 * Buybox variável (PDP + Quick View): swatches, preço por variação, subtotal no QV.
 */
function wcb_enqueue_variation_buybox_script() {
    if ( is_admin() || ! class_exists( 'WooCommerce' ) ) {
        return;
    }
    wp_enqueue_script( 'wc-add-to-cart' );
    wp_enqueue_script( 'wc-add-to-cart-variation' );
    wp_enqueue_script(
        'wcb-variation-buybox',
        WCB_URI . '/js/wcb-variation-buybox.js',
        array( 'jquery', 'wc-add-to-cart-variation' ),
        WCB_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'wcb_enqueue_variation_buybox_script', 20 );

add_action( 'wp_enqueue_scripts', 'wcb_enqueue_assets' );

/* ============================================================
   PERFORMANCE — Defer non-critical scripts
   ============================================================ */
function wcb_defer_scripts( $tag, $handle, $src ) {
    // Don't defer in admin
    if ( is_admin() ) return $tag;
    
    // Scripts that can safely be deferred
    $defer_handles = array(
        'woo-cart-abandonment-recovery',
        'woo-cart-abandonment-recovery-tracking',
        'wcf-checkout-global',
        'wcf-checkout',
        'woosq-frontend',
        'wbpf-frontend',
        'ti-wishlist',
        'alg-wc-wl',
        'yith-wfbt-frontend',
        /* Tema — não bloqueiam primeira pintura (main.js mantém síncrono por causa de inline) */
        'wcb-custom-cursor',
        'wcb-filter',
        'wcb-pdp-reviews',
        'wcb-testimonials-carousel',
        'wcb-mega-menu-footer',
    );
    
    if ( in_array( $handle, $defer_handles, true ) ) {
        return str_replace( ' src=', ' defer src=', $tag );
    }
    return $tag;
}
add_filter( 'script_loader_tag', 'wcb_defer_scripts', 10, 3 );

/* ============================================================
   PERFORMANCE — Dequeue unnecessary assets per page
   ============================================================ */
function wcb_dequeue_unnecessary_assets() {
    // CartFlows assets — only needed on checkout
    if ( ! is_checkout() && ! is_singular( 'cartflows_step' ) ) {
        wp_dequeue_style( 'wcf-checkout-general' );
        wp_dequeue_script( 'wcf-checkout-global' );
        wp_dequeue_script( 'wcf-checkout' );
    }
    
    // Cart abandonment tracking — only on checkout
    if ( ! is_checkout() ) {
        wp_dequeue_script( 'woo-cart-abandonment-recovery' );
        wp_dequeue_script( 'woo-cart-abandonment-recovery-tracking' );
        wp_dequeue_style( 'woo-cart-abandonment-recovery' );
    }
    
    // UAG Gutenberg blocks — only on pages/posts with blocks
    if ( is_shop() || is_product_category() || is_product() ) {
        wp_dequeue_style( 'uagb-block-common-editor' );
        wp_dequeue_style( 'uagb-block-editor' );
    }

    // YITH Frequently Bought Together — only on single product pages
    if ( ! is_product() ) {
        wp_dequeue_style( 'yith-wfbt-frontend' );
        wp_dequeue_script( 'yith-wfbt-frontend' );
    }

    // Quick View — shop, categorias, produto e home (carrosséis / listagens)
    if ( ! is_shop() && ! is_product_category() && ! is_product() && ! is_front_page() ) {
        wp_dequeue_style( 'woosq-frontend' );
        wp_dequeue_script( 'woosq-frontend' );
    }

    // Wishlist plugins — desabilitados via functions.php, descarregar assets
    wp_dequeue_style( 'ti-woocommerce-wishlist' );
    wp_dequeue_script( 'ti-wishlist' );
    wp_dequeue_style( 'alg-wc-wl' );
    wp_dequeue_script( 'alg-wc-wl' );
}
add_action( 'wp_enqueue_scripts', 'wcb_dequeue_unnecessary_assets', 100 );

/* ============================================================
   PERFORMANCE — Disable WordPress Emoji scripts & styles
   Saves ~10KB of JS + 1 DNS prefetch request
   ============================================================ */
function wcb_disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'emoji_svg_url', '__return_false' );
    // Remove DNS prefetch for emoji CDN
    add_filter( 'wp_resource_hints', function( $urls, $relation_type ) {
        if ( 'dns-prefetch' === $relation_type ) {
            $urls = array_filter( $urls, function( $url ) {
                return ( strpos( $url, 'https://s.w.org/images/core/emoji/' ) === false );
            });
        }
        return $urls;
    }, 10, 2 );
}
add_action( 'init', 'wcb_disable_emojis' );

/* ============================================================
   PERFORMANCE — Disable oEmbed discovery & scripts
   Saves 1 external request + ~3KB of JS
   ============================================================ */
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' );

/* ============================================================
   PERFORMANCE — Throttle Heartbeat API on frontend
   Reduces AJAX requests from every 15s to every 60s (or disable)
   ============================================================ */
function wcb_heartbeat_settings( $settings ) {
    // Disable heartbeat on frontend entirely (keep in admin for autosave)
    if ( ! is_admin() ) {
        $settings['interval'] = 120; // 2 minutes instead of 15 seconds
    }
    return $settings;
}
add_filter( 'heartbeat_settings', 'wcb_heartbeat_settings' );

// Dequeue heartbeat script on frontend (not needed for visitors)
function wcb_dequeue_heartbeat() {
    if ( ! is_admin() && ! is_user_logged_in() ) {
        wp_deregister_script( 'heartbeat' );
    }
}
add_action( 'wp_enqueue_scripts', 'wcb_dequeue_heartbeat', 1 );


/* ============================================================
   CART / CHECKOUT — CSS via wp_head (Canvas template bypass)
   ============================================================ */
function wcb_checkout_premium_css_head() {
    if ( is_singular( 'cartflows_step' ) || is_checkout() ) {
        // Inject Google Fonts + main theme stylesheet so our injected nav renders correctly
        // (CartFlows Canvas may not enqueue these via normal wp_enqueue_scripts)
        $style_url  = get_stylesheet_uri();
        $fonts_url  = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap';
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        echo wcb_google_fonts_nonblocking_link( $fonts_url );
        echo '<link rel="stylesheet" id="wcb-style-canvas-css" href="' . esc_url( $style_url ) . '?ver=' . WCB_VERSION . '" type="text/css" media="all" />' . "\n";

        $css_file = get_stylesheet_directory() . '/checkout-premium.css';
        if ( file_exists( $css_file ) ) {
            $css_url = get_stylesheet_directory_uri() . '/checkout-premium.css';
            $ver     = filemtime( $css_file );
            echo '<link rel="stylesheet" id="wcb-checkout-premium-css" href="' . esc_url( $css_url ) . '?ver=' . $ver . '" type="text/css" media="all" />' . "\n";
        }
        /* Force blue on SVG icons */
        echo '<style id="wcb-checkout-icon-colors">
            .uagb-icon-list__source-wrap svg,
            .uagb-icon-list__source-wrap svg path,
            .uagb-icon-list__source-wrap svg circle,
            .uagb-icon-list__source-wrap svg polygon,
            .uagb-icon-list__source-wrap svg rect,
            .uagb-icon-list__source-icon svg,
            .uagb-icon-list__source-icon svg path {
                fill: #155DFD !important;
                color: #155DFD !important;
            }
            .uagb-icon-list__source-wrap { color: #155DFD !important; }
        </style>' . "\n";
    }
}
add_action( 'wp_head', 'wcb_checkout_premium_css_head', 1 );

/* ============================================================
   FIX: ajaxurl global no frontend (WBW Product Filter)
   ============================================================ */
add_action( 'wp_head', function () {
    echo '<script>var ajaxurl = "' . esc_js( admin_url( 'admin-ajax.php' ) ) . '";</script>' . "\n";
}, 1 );

