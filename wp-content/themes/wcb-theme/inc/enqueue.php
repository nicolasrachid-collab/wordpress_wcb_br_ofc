<?php
/**
 * WCB Theme — Asset Enqueue
 * Scripts, styles, and inline JS registration.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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

    // Google Fonts — Inter (com preload da variante mais usada para resolver bloqueio de renderização)
    add_action( 'wp_head', function () {
        echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" as="style" crossorigin />' . "\n";
    }, 1 );
    wp_enqueue_style(
        'wcb-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
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

    // Theme JS
    wp_enqueue_script(
        'wcb-main',
        WCB_URI . '/js/main.js',
        array(),
        WCB_VERSION,
        true
    );

    // Pass data to JS
    wp_localize_script( 'wcb-main', 'wcbData', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'siteUrl' => home_url( '/' ),
        'nonce'   => wp_create_nonce( 'wcb_nonce' ),
    ) );

    // My Account premium styles (only on account pages)
    if ( is_account_page() ) {
        wp_enqueue_style(
            'wcb-myaccount',
            WCB_URI . '/inc/myaccount.css',
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

    // Side Cart premium overrides (só com plugin Xoo ativo e tema permitindo)
    if ( class_exists( 'WooCommerce' ) && function_exists( 'wcb_is_side_cart_active' ) && wcb_is_side_cart_active() ) {
        wp_enqueue_style(
            'wcb-side-cart-premium',
            WCB_URI . '/side-cart-premium.css',
            array( 'wcb-style', 'xoo-wsc-style' ),
            WCB_VERSION
        );

        // Cart page premium redesign
        if ( is_cart() || is_page( 'carrinho' ) || is_page( 'cart' ) ) {
            wp_enqueue_style(
                'wcb-cart-premium',
                WCB_URI . '/cart-premium.css',
                array( 'wcb-style' ),
                WCB_VERSION
            );
        }
    }

    // Testimonials Carousel JS
    $testimonials_js = "
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var track    = document.getElementById('wcb-testimonials-track');
        var dotsEl   = document.getElementById('wcb-testimonials-dots');
        var counter  = document.getElementById('wcb-testimonials-counter');
        var btnPrev  = document.querySelector('.wcb-testimonials__nav--prev');
        var btnNext  = document.querySelector('.wcb-testimonials__nav--next');
        var container = document.querySelector('.wcb-testimonials__track-container');

        if (!track || !container) return;

        var cards   = Array.from(track.querySelectorAll('.wcb-tcard'));
        var total   = cards.length;
        var current = 1;
        var GAP     = 24;

        cards.forEach(function(_, i) {
            var d = document.createElement('button');
            d.className = 'wcb-testimonials__dot';
            d.setAttribute('aria-label', 'Depoimento ' + (i + 1));
            d.addEventListener('click', function() { goTo(i); });
            dotsEl.appendChild(d);
        });

        function goTo(idx) {
            current = (idx + total) % total;
            render();
        }

        function render() {
            var cw = container.offsetWidth;
            if (!cw) { setTimeout(render, 50); return; }
            var cardW = Math.floor((cw - GAP * 2) / 3);
            cards.forEach(function(c) {
                c.style.width    = cardW + 'px';
                c.style.minWidth = cardW + 'px';
            });
            var offset = Math.round(cw / 2 - cardW / 2 - current * (cardW + GAP));
            track.style.transform = 'translateX(' + offset + 'px)';
            cards.forEach(function(c, i) { c.classList.toggle('wcb-tcard--active', i === current); });
            var dots = dotsEl.querySelectorAll('.wcb-testimonials__dot');
            dots.forEach(function(d, i) { d.classList.toggle('active', i === current); });
            if (counter) counter.textContent = (current + 1) + ' de ' + total + ' depoimentos';
        }

        btnPrev && btnPrev.addEventListener('click', function() { goTo(current - 1); });
        btnNext && btnNext.addEventListener('click', function() { goTo(current + 1); });

        var timer = setInterval(function() { goTo(current + 1); }, 5500);
        container.addEventListener('mouseenter', function() { clearInterval(timer); });
        container.addEventListener('mouseleave', function() {
            timer = setInterval(function() { goTo(current + 1); }, 5500);
        });

        requestAnimationFrame(function() { render(); });
        window.addEventListener('resize', render);
    });
})();
";
    wp_add_inline_script( 'wcb-main', $testimonials_js );
}
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

    // Quick View — only on shop/category/product pages
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
        $fonts_url  = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap';
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        echo '<link rel="stylesheet" href="' . esc_url( $fonts_url ) . '" />' . "\n";
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

/* ============================================================
   MEGA MENU — Patch: header, VER TODOS, grid dinâmico, UX
   ============================================================ */
add_action( 'wp_footer', function () {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      var megaItems = document.querySelectorAll('.wcb-nav__item.wcb-nav__item--mega');

      megaItems.forEach(function (megaItem) {
        var trigger = megaItem.querySelector(':scope > .wcb-nav__link');
        var mega = megaItem.querySelector(':scope > .wcb-mega');
        var inner = mega ? mega.querySelector('.wcb-mega__inner') : null;
        var list = inner ? inner.querySelector('.wcb-mega__simple') : null;

        if (!trigger || !mega || !inner || !list) return;

        var columns = list.querySelectorAll(':scope > li');

        /* ---------------------------------------
           1. Header row do submenu
           --------------------------------------- */
        if (!inner.querySelector(':scope > .wcb-mega__header')) {
          var rawLabel = (trigger.textContent || '').replace(/[\u2304\u2334\u25BE\u25BC]/g, '').trim();
          var categoryName = rawLabel || 'Categoria';
          var categoryHref = trigger.getAttribute('href') || '#';
          var columnCount = columns.length;

          var header = document.createElement('div');
          header.className = 'wcb-mega__header';

          var title = document.createElement('h3');
          title.className = 'wcb-mega__title';
          title.textContent = categoryName;

          var count = document.createElement('span');
          count.className = 'wcb-mega__count';
          count.textContent = '\u2014 ' + columnCount + ' grupo' + (columnCount !== 1 ? 's' : '');

          var viewAll = document.createElement('a');
          viewAll.className = 'wcb-mega__view-all-top';
          viewAll.href = categoryHref;
          viewAll.textContent = 'Ver todos de ' + categoryName;

          header.appendChild(title);
          header.appendChild(count);
          header.appendChild(viewAll);

          inner.insertBefore(header, list);
        }

        /* ---------------------------------------
           2. Injeção do "VER TODOS" por coluna
              apenas em colunas com filhos
           --------------------------------------- */
        var childColumns = list.querySelectorAll(':scope > li.menu-item-has-children');

        childColumns.forEach(function (col) {
          var headingLink = col.querySelector(':scope > .wcb-mega__link--has-sub');
          var subList = col.querySelector(':scope > .wcb-mega__sub');
          var cta = col.querySelector(':scope > .wcb-mega__ver-todos');

          if (!headingLink || !subList) return;

          if (!cta) {
            cta = document.createElement('a');
            cta.className = 'wcb-mega__ver-todos';
            cta.href = headingLink.getAttribute('href') || '#';
            cta.textContent = 'Ver Todos';
            col.appendChild(cta);
          } else {
            if (!cta.getAttribute('href')) {
              cta.href = headingLink.getAttribute('href') || '#';
            }
            cta.textContent = 'Ver Todos';
          }
        });

        /* ---------------------------------------
           3. Grid ponderado + classe utilitária
              Colunas com filhos = 2fr
              Colunas sem filhos = 1fr
           --------------------------------------- */
        var totalCols = columns.length;
        var frValues = [];
        columns.forEach(function(col) {
          frValues.push(col.classList.contains('menu-item-has-children') ? '2fr' : '1fr');
        });
        list.style.gridTemplateColumns = frValues.join(' ');

        // Classe utilitária para fallback
        list.classList.remove('wcb-mega__simple--cols-2', 'wcb-mega__simple--cols-3', 'wcb-mega__simple--cols-4', 'wcb-mega__simple--cols-5', 'wcb-mega__simple--cols-6');
        list.classList.add('wcb-mega__simple--cols-' + totalCols);

        /* ---------------------------------------
           4. Remoção de prefixos redundantes
              Ex: "Juice Adocicado" → "Adocicado"
              quando o pai é "Perfil de Sabor"
              e o avô é "Juices"
           --------------------------------------- */
        childColumns.forEach(function(col) {
          var parentLink = col.querySelector(':scope > .wcb-mega__link--has-sub');
          if (!parentLink) return;

          var parentName = parentLink.textContent.trim();
          var grandParentName = (trigger.textContent || '').replace(/[\u2304\u2334\u25BE\u25BC]/g, '').trim();

          var subLinks = col.querySelectorAll('.wcb-mega__sub .wcb-mega__link');
          subLinks.forEach(function(link) {
            var originalText = link.textContent.trim();
            var newText = originalText;

            // Tentar remover prefixo do grandParent (ex: "Juice", "SaltNic")
            var prefixes = [grandParentName];
            // Gerar variações (singular, sem "s" final, etc.)
            if (grandParentName.length > 2) {
              var singular = grandParentName.replace(/s$/i, '');
              if (singular !== grandParentName) prefixes.push(singular);
            }

            for (var i = 0; i < prefixes.length; i++) {
              var prefix = prefixes[i];
              if (prefix && newText.length > prefix.length + 2 &&
                  newText.toLowerCase().indexOf(prefix.toLowerCase()) === 0) {
                newText = newText.substring(prefix.length).replace(/^[\s\-–—]+/, '').trim();
                break;
              }
            }

            if (newText && newText !== originalText && newText.length > 1) {
              // Capitalizar primeira letra
              link.textContent = newText.charAt(0).toUpperCase() + newText.slice(1);
            }
          });
        });

        /* ---------------------------------------
           5. Stagger animation nas colunas
           --------------------------------------- */
        columns.forEach(function(col, idx) {
          col.style.transitionDelay = (idx * 0.04) + 's';
        });

        /* ---------------------------------------
           6. Drill-down: "VER TODOS" expande
              a coluna e esconde as outras
           --------------------------------------- */
        (function setupDrillDown(megaInner, simpleList) {
          megaInner.addEventListener('click', function(e) {
            var btn = e.target.closest('.wcb-mega__ver-todos');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();

            var clickedCol = btn.closest('li');
            if (!clickedCol) return;

            var allCols = simpleList.querySelectorAll(':scope > li');
            var headingLink = clickedCol.querySelector('.wcb-mega__link--has-sub');
            var colName = headingLink ? headingLink.textContent.trim() : '';

            // Save original header content
            var headerEl = megaInner.querySelector('.wcb-mega__header');
            if (headerEl && !headerEl.dataset.originalHtml) {
              headerEl.dataset.originalHtml = headerEl.innerHTML;
            }

            // Enter drill-down mode
            megaInner.classList.add('wcb-mega--drilled');

            // Hide other columns with animation
            allCols.forEach(function(col) {
              if (col !== clickedCol) {
                col.classList.add('wcb-mega__col--hidden');
              }
            });

            // Expand clicked column
            clickedCol.classList.add('wcb-mega__col--drilled');

            // Hide the "VER TODOS" button within the drilled column
            btn.style.display = 'none';

            // Update header with back button and category name
            if (headerEl) {
              headerEl.innerHTML = '';

              var backBtn = document.createElement('button');
              backBtn.className = 'wcb-mega__back';
              backBtn.setAttribute('type', 'button');
              backBtn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg> Voltar';

              var drilledTitle = document.createElement('h3');
              drilledTitle.className = 'wcb-mega__title';
              drilledTitle.textContent = colName;

              var drilledLink = document.createElement('a');
              drilledLink.className = 'wcb-mega__view-all-top';
              drilledLink.href = headingLink ? headingLink.getAttribute('href') : '#';
              drilledLink.textContent = 'Ver página de ' + colName;

              headerEl.appendChild(backBtn);
              headerEl.appendChild(drilledTitle);
              headerEl.appendChild(drilledLink);

              // Back button handler
              backBtn.addEventListener('click', function(ev) {
                ev.preventDefault();
                ev.stopPropagation();

                megaInner.classList.remove('wcb-mega--drilled');

                allCols.forEach(function(col) {
                  col.classList.remove('wcb-mega__col--hidden');
                });
                clickedCol.classList.remove('wcb-mega__col--drilled');
                btn.style.display = '';

                // Restore original header
                if (headerEl.dataset.originalHtml) {
                  headerEl.innerHTML = headerEl.dataset.originalHtml;
                }
              });
            }
          });

          // Reset drill-down when mega menu closes
          megaItem.addEventListener('mouseleave', function() {
            setTimeout(function() {
              if (!megaItem.matches(':hover') && megaInner.classList.contains('wcb-mega--drilled')) {
                megaInner.classList.remove('wcb-mega--drilled');
                var allCols = simpleList.querySelectorAll(':scope > li');
                allCols.forEach(function(col) {
                  col.classList.remove('wcb-mega__col--hidden', 'wcb-mega__col--drilled');
                });
                // Restore VER TODOS buttons
                simpleList.querySelectorAll('.wcb-mega__ver-todos').forEach(function(b) {
                  b.style.display = '';
                });
                // Restore header
                var headerEl = megaInner.querySelector('.wcb-mega__header');
                if (headerEl && headerEl.dataset.originalHtml) {
                  headerEl.innerHTML = headerEl.dataset.originalHtml;
                }
              }
            }, 300);
          });
        })(inner, list);
      });

    });
    </script>
    <?php
}, 99 );


