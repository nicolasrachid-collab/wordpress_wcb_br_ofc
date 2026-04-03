<?php
/**
 * WCB Theme — Header
 *
 * @package WCB_Theme
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo esc_attr( get_bloginfo( 'description', 'display' ) ); ?>">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <!-- ==================== SITE HEADER WRAPPER (STICKY) ==================== -->
    <div class="wcb-site-header" id="wcb-site-header">

    <!-- ==================== ANNOUNCEMENT BANNER (dismissível) ==================== -->
    <?php
    /**
     * Banner de Avisos — Componente flexível e dismissível
     * 
     * Para alterar o conteúdo: edite o $banner_content abaixo.
     * Para forçar reexibição após mudança de mensagem: altere o $banner_id.
     * O banner ficará oculto por 24h após ser fechado pelo usuário.
     */
    $banner_id      = 'frete-gratis-v1'; // Mude o ID para forçar reexibição
    $banner_content = '🚚 Frete <strong>GRÁTIS</strong> para compras acima de <strong>R$199</strong> — Aproveite!';
    $banner_link    = home_url('/loja/'); // Link opcional (deixe vazio se não quiser link)
    $banner_style   = 'info'; // 'info' (azul), 'promo' (verde/dourado), 'alert' (laranja), 'dark' (escuro)
    ?>
    <div class="wcb-announcement" id="wcb-announcement" data-banner-id="<?php echo esc_attr($banner_id); ?>" role="banner" aria-label="Aviso">
        <div class="wcb-announcement__inner">
            <?php if (!empty($banner_link)): ?>
                <a href="<?php echo esc_url($banner_link); ?>" class="wcb-announcement__content">
                    <span class="wcb-announcement__text"><?php echo $banner_content; ?></span>
                    <svg class="wcb-announcement__arrow" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            <?php else: ?>
                <span class="wcb-announcement__content">
                    <span class="wcb-announcement__text"><?php echo $banner_content; ?></span>
                </span>
            <?php endif; ?>

        </div>
    </div>


    <!-- ==================== MAIN HEADER ==================== -->
    <header class="wcb-header" id="wcb-header">
        <div class="wcb-container wcb-header__inner">

            <!-- Mobile Toggle -->
            <button class="wcb-mobile-toggle" id="wcb-mobile-toggle" aria-label="Abrir menu">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <!-- Logo -->
            <div class="wcb-header__logo">
                <?php wcb_get_logo(); ?>
            </div>

            <!-- Search Bar -->
            <div class="wcb-header__search" id="wcb-search">
                <?php get_search_form(); ?>
            </div>

            <!-- Header Actions -->
            <div class="wcb-header__actions">
                <?php if (class_exists('WooCommerce')): ?>
                    <!-- Login / Register Button -->
                    <?php if (!is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>"
                            class="wcb-header__login-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                                <polyline points="10 17 15 12 10 7" />
                                <line x1="15" y1="12" x2="3" y2="12" />
                            </svg>
                            Entre ou Cadastre-se
                        </a>
                    <?php endif; ?>

                    <!-- Cloud Prime (sem link — em breve) -->
                    <span class="wcb-header__cloud-club-btn wcb-header__cloud-club-btn--soon"
                        aria-label="<?php echo esc_attr__( 'Cloud Prime — em breve', 'wcb-theme' ); ?>">
                        <svg class="wcb-header__cloud-club-btn__mark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z" />
                        </svg>
                        <span class="wcb-header__cloud-club-btn__title"><?php esc_html_e( 'Cloud Prime', 'wcb-theme' ); ?></span>
                        <span class="wcb-header__cloud-club-btn__divider" aria-hidden="true"></span>
                        <span class="wcb-header__cloud-club-btn__soon"><?php esc_html_e( 'Em breve', 'wcb-theme' ); ?></span>
                    </span>

                    <!-- Account -->
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>" class="wcb-header__action"
                        title="Minha Conta">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Conta</span>
                    </a>

                    <!-- Favorites -->
                    <a href="<?php echo esc_url(home_url('/minha-conta/favoritos/')); ?>" class="wcb-header__action"
                        title="Favoritos" id="wcb-header-fav-link">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                            </path>
                        </svg>
                        <?php
                        $fav_count = 0;
                        if (is_user_logged_in()) {
                            $fav_list = get_user_meta(get_current_user_id(), '_wcb_wishlist', true);
                            $fav_count = is_array($fav_list) ? count($fav_list) : 0;
                        }
                        ?>
                        <span class="wcb-header__fav-count" id="wcb-header-fav-count" <?php echo $fav_count === 0 ? 'style="display:none"' : ''; ?>><?php echo $fav_count; ?></span>
                        <span>Favoritos</span>
                    </a>

                    <!-- Carrinho: sempre página do carrinho (não abre drawer lateral no header) -->
                    <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wcb-header__action" id="wcb-mini-cart-trigger" title="<?php echo esc_attr__( 'Carrinho', 'wcb-theme' ); ?>" aria-label="<?php echo esc_attr__( 'Ver carrinho', 'wcb-theme' ); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        <span class="wcb-header__cart-count">
                            <?php echo wcb_cart_count(); ?>
                        </span>
                        <span><?php esc_html_e( 'Carrinho', 'wcb-theme' ); ?></span>
                    </a>
                <?php else: ?>
                    <!-- Account (no WC) -->
                    <a href="<?php echo esc_url(wp_login_url()); ?>" class="wcb-header__action" title="Login">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Entrar</span>
                    </a>
                <?php endif; ?>

                <!-- Mobile Search Toggle -->
                <button class="wcb-header__action wcb-mobile-toggle" id="wcb-search-toggle" aria-label="Buscar"
                    style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- ==================== NAVIGATION BAR ==================== -->
    <nav class="wcb-nav" id="wcb-nav" role="navigation" aria-label="Menu principal">
        <div class="wcb-container wcb-nav__inner">
            <?php
            if (has_nav_menu('primary')) {
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'wcb-nav__list',
                    'items_wrap'     => '<ul id="%1$s" class="%2$s" role="menubar">%3$s</ul>',
                    'walker'         => new WCB_Nav_Walker(),
                    'depth'          => 3,
                ));
            } else {
                // Fallback static links shown until a menu is assigned in WP Admin
                ?>
                <ul class="wcb-nav__list" role="menubar">
                    <li class="wcb-nav__item" role="none">
                        <a href="<?php echo esc_url(home_url('/produto/promocao/')); ?>"
                           class="wcb-nav__link wcb-nav__link--promo" role="menuitem">Promoção</a>
                    </li>
                    <li class="wcb-nav__item wcb-nav__item--sep" role="none" aria-hidden="true"></li>
                    <li class="wcb-nav__item" role="none"><a href="<?php echo esc_url(home_url('/produto/pods/')); ?>" class="wcb-nav__link" role="menuitem">Pods</a></li>
                    <li class="wcb-nav__item" role="none"><a href="<?php echo esc_url(home_url('/produto/cartuchos/')); ?>" class="wcb-nav__link" role="menuitem">Cartuchos</a></li>
                    <li class="wcb-nav__item" role="none"><a href="<?php echo esc_url(home_url('/produto/coils/')); ?>" class="wcb-nav__link" role="menuitem">Coils</a></li>
                    <li class="wcb-nav__item" role="none"><a href="<?php echo esc_url(home_url('/produto/juices/')); ?>" class="wcb-nav__link" role="menuitem">Juices</a></li>
                    <li class="wcb-nav__item" role="none"><a href="<?php echo esc_url(home_url('/produto/kits/')); ?>" class="wcb-nav__link" role="menuitem">Kits</a></li>
                    <li class="wcb-nav__item" role="none"><a href="<?php echo esc_url(home_url('/produto/acessorios/')); ?>" class="wcb-nav__link" role="menuitem">Acessórios</a></li>
                </ul>
                <?php
            }
            ?>
        </div>

        <!-- Promo hover: dentro de #wcb-nav para position:absolute top:100% ancorar na barra -->
        <?php if ( class_exists('WooCommerce') ) :
            $wcb_promo_dd_transient = defined( 'WCB_PROMO_DD_TRANSIENT' ) ? WCB_PROMO_DD_TRANSIENT : 'wcb_promo_dropdown_cards_promocoes_v3';
            $promo_cards_html       = get_transient( $wcb_promo_dd_transient );
            if ( false === $promo_cards_html ) {
                $promo_cards_html = '';
                /** @var string Slug da categoria WooCommerce (filtro: wcb_promo_dropdown_category_slug). */
                $wcb_promo_dd_cat_slug = apply_filters( 'wcb_promo_dropdown_category_slug', 'promocoes' );
                $wcb_promo_dd_term     = get_term_by( 'slug', $wcb_promo_dd_cat_slug, 'product_cat' );
                if ( $wcb_promo_dd_term && ! is_wp_error( $wcb_promo_dd_term ) ) {
                    $promo_q = new WP_Query(
                        function_exists( 'wcb_promo_dropdown_wp_query_args' )
                            ? wcb_promo_dropdown_wp_query_args( (int) $wcb_promo_dd_term->term_id, (string) $wcb_promo_dd_term->slug )
                            : array(
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
                                'tax_query'              => function_exists( 'wcb_promo_dropdown_tax_query' )
                                    ? wcb_promo_dropdown_tax_query( (int) $wcb_promo_dd_term->term_id )
                                    : array(
                                        array(
                                            'taxonomy'         => 'product_cat',
                                            'field'            => 'term_id',
                                            'terms'            => (int) $wcb_promo_dd_term->term_id,
                                            'include_children' => true,
                                        ),
                                    ),
                            )
                    );
                    if ( $promo_q->have_posts() ) :
                        while ( $promo_q->have_posts() ) : $promo_q->the_post();
                            global $product;
                            if (!$product) continue;
                            $reg  = (float) $product->get_regular_price();
                            $cur  = (float) $product->get_price();
                            $pix  = $cur > 0 ? $cur * 0.95 : 0;
                            $save = ($reg > 0 && $cur > 0 && $reg > $cur) ? round((($reg - $cur) / $reg) * 100) : 0;
                            $img  = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'wcb-product-thumb') : '';

                            $promo_cards_html .= '<div class="wcb-promo-dd__card">';
                            if ($save > 0) {
                                $promo_cards_html .= '<span class="wcb-promo-dd__discount">-' . $save . '%</span>';
                            }
                            $promo_cards_html .= '<a href="' . esc_url(get_permalink()) . '" class="wcb-promo-dd__img-wrap">';
                            if ($img) {
                                $promo_cards_html .= '<img src="' . esc_url($img) . '" alt="' . esc_attr(get_the_title()) . '" loading="lazy" width="176" height="158">';
                            } else {
                                $promo_cards_html .= '<div class="wcb-promo-dd__no-img"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" opacity="0.2"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg></div>';
                            }
                            $promo_cards_html .= '</a>';
                            $promo_cards_html .= '<a href="' . esc_url(get_permalink()) . '" class="wcb-promo-dd__name">' . esc_html(get_the_title()) . '</a>';
                            $promo_cards_html .= '<div class="wcb-promo-dd__prices">';
                            if ($reg > 0 && $reg > $cur) {
                                $promo_cards_html .= '<span class="wcb-promo-dd__price-old">R$ ' . number_format($reg, 2, ',', '.') . '</span>';
                            }
                            if ($cur > 0) {
                                $promo_cards_html .= '<span class="wcb-promo-dd__price-cur">R$ ' . number_format($cur, 2, ',', '.') . '</span>';
                            }
                            $promo_cards_html .= '</div>';
                            if ($pix > 0) {
                                $promo_cards_html .= '<span class="wcb-promo-dd__pix">R$ ' . number_format($pix, 2, ',', '.') . ' no PIX</span>';
                            }
                            $promo_cards_html .= '</div>';
                        endwhile;
                        wp_reset_postdata();
                    endif;
                }
                set_transient( $wcb_promo_dd_transient, $promo_cards_html, 6 * HOUR_IN_SECONDS );
            }
        ?>
        <?php if ( ! empty($promo_cards_html) ) :
            /** Mesmo destino que o item "Promoções" do menu (arquivo da categoria WooCommerce). */
            $wcb_promo_see_all_slug = apply_filters('wcb_promo_dropdown_category_slug', 'promocoes');
            $wcb_promo_see_all_term = get_term_by('slug', $wcb_promo_see_all_slug, 'product_cat');
            $wcb_promo_see_all_url  = home_url('/categoria-produto/promocoes/');
            if ($wcb_promo_see_all_term && ! is_wp_error($wcb_promo_see_all_term)) {
                $wcb_promo_see_all_link = get_term_link($wcb_promo_see_all_term);
                if (! is_wp_error($wcb_promo_see_all_link)) {
                    $wcb_promo_see_all_url = $wcb_promo_see_all_link;
                }
            }
        ?>
        <div class="wcb-promo-dd" id="wcb-promo-dd" aria-hidden="true">
            <div class="wcb-container">
                <div class="wcb-promo-dd__inner">
                    <div class="wcb-promo-dd__header">
                        <div class="wcb-promo-dd__header-left">
                            <h3 class="wcb-promo-dd__title">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 0.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z"/></svg>
                                Promoções Imperdíveis
                            </h3>
                            <span class="wcb-promo-dd__subtitle">Produtos com até 30% OFF + 5% extra no PIX</span>
                        </div>
                        <a href="<?php echo esc_url($wcb_promo_see_all_url); ?>" class="wcb-promo-dd__see-all">
                            Ver todas ofertas
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                    <div class="wcb-promo-dd__carousel">
                        <button class="wcb-promo-dd__arrow wcb-promo-dd__arrow--prev" aria-label="Anterior">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <div class="wcb-promo-dd__track-wrap">
                            <div class="wcb-promo-dd__track" id="wcb-promo-dd-track">
                                <?php echo $promo_cards_html; ?>
                            </div>
                        </div>
                        <button class="wcb-promo-dd__arrow wcb-promo-dd__arrow--next" aria-label="Próximo">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </nav>

    </div><!-- /.wcb-site-header -->

    <script>
    (function() {
        var header = document.getElementById('wcb-site-header');
        if (!header) return;
        var lastScroll = 0;
        window.addEventListener('scroll', function() {
            var y = window.scrollY || window.pageYOffset;
            if (y > 60) {
                header.classList.add('is-scrolled');
            } else {
                header.classList.remove('is-scrolled');
            }
            lastScroll = y;
        }, { passive: true });
    })();
    </script>

    <!-- ==================== MEGA MENU SCRIPT ==================== -->
    <script>
    (function () {
        'use strict';

        document.addEventListener('DOMContentLoaded', function () {
            var megaItems = document.querySelectorAll('.wcb-nav__item--mega');
            var openDelay = null; /* delay para evitar abertura acidental */

            megaItems.forEach(function (navItem) {
                var fixedItems = navItem.querySelectorAll('.wcb-mega__fixed-item');
                var rightCol   = navItem.querySelector('.wcb-mega__col--right');
                var headerEl   = navItem.querySelector('.wcb-mega__header');
                var childWraps = navItem.querySelectorAll('.wcb-mega__children-wrap');
                var topLink    = navItem.querySelector('.wcb-nav__link--has-mega');

                if (!rightCol) return;

                /* ── Mapa de wraps por data-parent ── */
                var wrapMap = {};
                childWraps.forEach(function (wrap) {
                    wrapMap[wrap.dataset.parent] = wrap;
                });

                /* ── Fade de conteúdo da coluna direita ── */
                function fadeIn(el) {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(6px)';
                    requestAnimationFrame(function () {
                        el.style.transition = 'opacity 0.18s ease, transform 0.18s ease';
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    });
                }

                /* ── Ativa o primeiro item ao abrir ── */
                function activateFirst() {
                    if (fixedItems.length > 0) activateItem(fixedItems[0]);
                }

                /* ── Ativa um submenu fixo ── */
                function activateItem(fixedItem) {
                    var groupId     = fixedItem.dataset.groupId;
                    var hasChildren = fixedItem.dataset.hasChildren === '1';

                    fixedItems.forEach(function (fi) { fi.classList.remove('wcb-active'); });
                    fixedItem.classList.add('wcb-active');

                    rightCol.innerHTML = '';
                    rightCol.classList.remove('wcb-expanded');
                    rightCol.style.transition = '';

                    var link  = fixedItem.querySelector('.wcb-mega__fixed-link');
                    var title = link ? (link.textContent || link.innerText).trim() : '';

                    if (hasChildren) {
                        var wrap = wrapMap[groupId];
                        if (!wrap) return;

                        /* Conta total de filhos */
                        var totalChildren = wrap.querySelectorAll('.wcb-mega__child').length;

                        /* Atualiza cabeçalho com título + contagem */
                        if (headerEl) {
                            headerEl.innerHTML =
                                '<span class="wcb-mega__header-title">' + title + '</span>' +
                                '<span class="wcb-mega__header-count">' + totalChildren + ' ' + (totalChildren === 1 ? 'item' : 'itens') + '</span>';
                        }

                        var clone = wrap.cloneNode(true);
                        clone.removeAttribute('hidden');
                        rightCol.appendChild(clone);
                        fadeIn(clone);

                        /* Botão VER TODOS */
                        var btn = clone.querySelector('.wcb-mega__see-all');
                        if (btn) {
                            var btnTitle = btn.dataset.categoryTitle || title;
                            var btnTextSpan = btn.querySelector('.wcb-mega__see-all-text');

                            btn.addEventListener('click', function (e) {
                                e.preventDefault();
                                e.stopPropagation();
                                var hasMore = btn.dataset.hasMore === '1';
                                if (hasMore) {
                                    /* R3: expande inline */
                                    rightCol.classList.toggle('wcb-expanded');
                                    var expanded = rightCol.classList.contains('wcb-expanded');

                                    if (btnTextSpan) {
                                        btnTextSpan.innerHTML = expanded 
                                            ? '← Ver menos em <strong>' + btnTitle + '</strong>' 
                                            : 'Ver todos em <strong>' + btnTitle + '</strong>';
                                    }

                                    var hiddenCount = parseInt(btn.dataset.hiddenCount, 10) || 0;
                                    var badge = btn.querySelector('.wcb-mega__see-all-badge');
                                    if (badge) {
                                        badge.style.display = expanded ? 'none' : '';
                                    }
                                } else {
                                    /* R4: redireciona para categoria */
                                    var url = btn.dataset.categoryUrl;
                                    if (url && url !== '#') window.location.href = url;
                                }
                            });
                        }
                    } else {
                        /* R1: sem filhos → link direto + sugestão de explorar */
                        var leafLink = fixedItem.querySelector('.wcb-mega__fixed-link');
                        var leafHref = leafLink ? leafLink.getAttribute('href') : '#';

                        if (headerEl) {
                            headerEl.innerHTML = '<span class="wcb-mega__header-title">' + title + '</span>';
                        }

                        var wrapper = document.createElement('div');
                        wrapper.className = 'wcb-mega__leaf-wrapper';

                        var ul = document.createElement('ul');
                        ul.className = 'wcb-mega__leaf-list';

                        var li = document.createElement('li');
                        li.className = 'wcb-mega__leaf-item';
                        var a = document.createElement('a');
                        a.href      = leafHref;
                        a.className = 'wcb-mega__leaf-link';
                        a.innerHTML = 'Ver todos em <strong>' + title + '</strong><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
                        li.appendChild(a);
                        ul.appendChild(li);
                        wrapper.appendChild(ul);
                        rightCol.appendChild(wrapper);
                        fadeIn(wrapper);
                    }
                }

                /* ── Hover nos submenus fixos (sem delay) ── */
                fixedItems.forEach(function (fi) {
                    fi.addEventListener('mouseenter', function () { activateItem(fi); });
                });

                /* ── Abrir mega com delay de 80ms para evitar abertura acidental ── */
                navItem.addEventListener('mouseenter', function () {
                    clearTimeout(openDelay);
                    openDelay = setTimeout(function () {
                        activateFirst();
                        rightCol.classList.remove('wcb-expanded');
                    }, 80);
                });

                /* ── Fechar ao sair do item ── */
                navItem.addEventListener('mouseleave', function () {
                    clearTimeout(openDelay);
                    fixedItems.forEach(function (fi) { fi.classList.remove('wcb-active'); });
                    rightCol.innerHTML = '';
                    rightCol.classList.remove('wcb-expanded');
                    if (headerEl) headerEl.innerHTML = '';
                });
            });

            /* ── Fechar ao clicar fora do menu ── */
            document.addEventListener('click', function (e) {
                var nav = document.querySelector('.wcb-nav');
                if (nav && !nav.contains(e.target)) {
                    megaItems.forEach(function (navItem) {
                        var rightCol = navItem.querySelector('.wcb-mega__col--right');
                        var headerEl = navItem.querySelector('.wcb-mega__header');
                        var fixedItems = navItem.querySelectorAll('.wcb-mega__fixed-item');
                        if (rightCol) {
                            fixedItems.forEach(function (fi) { fi.classList.remove('wcb-active'); });
                            rightCol.innerHTML = '';
                            rightCol.classList.remove('wcb-expanded');
                            if (headerEl) headerEl.innerHTML = '';
                        }
                    });
                }
            });

            /* ── Fechar com tecla Escape ── */
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    megaItems.forEach(function (navItem) {
                        var rightCol   = navItem.querySelector('.wcb-mega__col--right');
                        var headerEl   = navItem.querySelector('.wcb-mega__header');
                        var fixedItems = navItem.querySelectorAll('.wcb-mega__fixed-item');
                        if (rightCol) {
                            fixedItems.forEach(function (fi) { fi.classList.remove('wcb-active'); });
                            rightCol.innerHTML = '';
                            rightCol.classList.remove('wcb-expanded');
                            if (headerEl) headerEl.innerHTML = '';
                        }
                    });
                }
            });
        });
    })();
    </script>

    <!-- ==================== PROMO DROPDOWN SCRIPT ==================== -->
    <script>
    (function () {
        'use strict';
        document.addEventListener('DOMContentLoaded', function () {
            var dd = document.getElementById('wcb-promo-dd');
            if (!dd) return;

            var track = document.getElementById('wcb-promo-dd-track');
            if (!track) return;

            var cards     = track.querySelectorAll('.wcb-promo-dd__card');
            var prevBtn   = dd.querySelector('.wcb-promo-dd__arrow--prev');
            var nextBtn   = dd.querySelector('.wcb-promo-dd__arrow--next');
            var totalCards = cards.length;
            var offset     = 0;
            var autoTimer  = null;
            var hideTimer  = null;

            /* ── Encontra o link "Promoção/Promoções" na navbar ── */
            var promoLink = document.querySelector('.wcb-nav__link--promo');
            var promoItem = promoLink ? promoLink.closest('.wcb-nav__item') || promoLink.parentElement : null;

            if (!promoItem) return;

            /* ── Quantos cards visíveis cabem ── */
            function getVisibleCount() {
                var wrap = dd.querySelector('.wcb-promo-dd__track-wrap');
                if (!wrap || !cards[0]) return 6;
                var wrapW = wrap.offsetWidth;
                var cardW = cards[0].offsetWidth + 16; /* gap ~16px */
                return Math.max(1, Math.floor(wrapW / cardW));
            }

            function getMaxOffset() {
                return Math.max(0, totalCards - getVisibleCount());
            }

            function slide(dir) {
                var max = getMaxOffset();
                offset = offset + dir;
                if (offset < 0)    offset = max;
                if (offset > max)  offset = 0;
                var cardW = cards[0].offsetWidth + 16;
                track.style.transform = 'translateX(-' + (offset * cardW) + 'px)';
            }

            if (prevBtn) prevBtn.addEventListener('click', function () { slide(-1); resetAuto(); });
            if (nextBtn) nextBtn.addEventListener('click', function () { slide(1);  resetAuto(); });

            function startAuto() { autoTimer = setInterval(function () { slide(1); }, 3000); }
            function stopAuto()  { if (autoTimer) { clearInterval(autoTimer); autoTimer = null; } }
            function resetAuto() { stopAuto(); startAuto(); }

            /* ── Show / Hide com delay ── */
            function showDD() {
                clearTimeout(hideTimer);
                dd.classList.add('wcb-promo-dd--visible');
                dd.setAttribute('aria-hidden', 'false');
                offset = 0;
                track.style.transform = 'translateX(0)';
                startAuto();
            }

            function hideDD() {
                hideTimer = setTimeout(function () {
                    dd.classList.remove('wcb-promo-dd--visible');
                    dd.setAttribute('aria-hidden', 'true');
                    stopAuto();
                }, 150);
            }

            /* ── Eventos de hover ── */
            promoItem.addEventListener('mouseenter', showDD);
            promoItem.addEventListener('mouseleave', hideDD);
            dd.addEventListener('mouseenter', function () { clearTimeout(hideTimer); });
            dd.addEventListener('mouseleave', hideDD);
        });
    })();
    </script>

    <!-- ==================== MOBILE MENU ==================== -->

    <div class="wcb-mobile-overlay" id="wcb-mobile-overlay"></div>
    <div class="wcb-mobile-menu" id="wcb-mobile-menu">
        <div class="wcb-mobile-menu__header">
            <span class="wcb-header__logo-text">White <span>Cloud</span></span>
            <button class="wcb-mobile-menu__close" id="wcb-mobile-close" aria-label="Fechar menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <nav class="wcb-mobile-menu__nav">
            <?php
            if (has_nav_menu('primary')) {
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container' => false,
                    'items_wrap' => '%3$s',
                    'depth' => 1,
                    'fallback_cb' => false,
                ));
            } else {
                echo '<a href="' . esc_url(home_url('/')) . '">Início</a>';
                echo '<a href="' . esc_url(home_url('/loja/')) . '">Loja</a>';
                if (class_exists('WooCommerce')) {
                    echo '<a href="' . esc_url(wc_get_cart_url()) . '">Carrinho</a>';
                    echo '<a href="' . esc_url(wc_get_account_endpoint_url('dashboard')) . '">Minha Conta</a>';
                }
            }
            ?>
        </nav>
    </div>

    <!-- ==================== MINI-CART FLYOUT ==================== -->
    <div class="wcb-mini-cart-overlay" id="wcb-mini-cart-overlay" aria-hidden="true"></div>
    <div class="wcb-mini-cart" id="wcb-mini-cart" role="dialog" aria-modal="true" aria-label="Carrinho" aria-hidden="true">
        <!-- Header -->
        <div class="wcb-mini-cart__header">
            <div class="wcb-mini-cart__title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Meu Carrinho</span>
                <span class="wcb-mini-cart__count" id="wcb-mini-cart-count">0</span>
            </div>
            <button type="button" class="wcb-mini-cart__close" id="wcb-mini-cart-close" aria-label="Fechar carrinho">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <!-- Body (items loaded via AJAX) -->
        <div class="wcb-mini-cart__body" id="wcb-mini-cart-body">
            <div class="wcb-mini-cart__loading" id="wcb-mini-cart-loading">
                <div class="wcb-mini-cart__spinner"></div>
                <span>Carregando...</span>
            </div>
        </div>
        <!-- Footer -->
        <div class="wcb-mini-cart__footer" id="wcb-mini-cart-footer" style="display:none;">
            <div class="wcb-mini-cart__subtotal">
                <span>Subtotal</span>
                <strong id="wcb-mini-cart-subtotal">R$ 0,00</strong>
            </div>
            <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wcb-mini-cart__btn wcb-mini-cart__btn--sec">
                Ver Carrinho
            </a>
            <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="wcb-mini-cart__btn wcb-mini-cart__btn--primary">
                Finalizar Compra
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    <!-- ==================== MAIN CONTENT START ==================== -->
    <main class="wcb-main" id="main-content">