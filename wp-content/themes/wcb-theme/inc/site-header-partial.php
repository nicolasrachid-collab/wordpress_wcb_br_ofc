<?php
/**
 * WCB Theme — Site Header Partial
 *
 * Outputs ONLY the visible page header (topbar + header + nav + mobile menu)
 * without the HTML skeleton (no <!DOCTYPE>, <html>, <head>, <body>).
 *
 * Used by CartFlows Canvas pages which run their own HTML skeleton and
 * never call get_header(). Included via wcb_inject_site_header_on_cartflows().
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Sem menu principal / drawer no checkout — restantes páginas inalteradas. */
$wcb_skip_shop_nav = function_exists( 'is_checkout' ) && is_checkout();
?>

<!-- Mesmo wrapper que header.php: sem isto, o grid desktop (#wcb-site-header .wcb-header__inner) não aplica no canvas. -->
<div class="wcb-site-header" id="wcb-site-header">

<!-- ==================== TOP BAR ==================== -->
<div class="wcb-topbar">
    <div class="wcb-container">
        <nav class="wcb-topbar__links">
            <a href="<?php echo esc_url( home_url( '/rastrear-pedido/' ) ); ?>">Rastrear Pedido</a>
            <a href="<?php echo esc_url( home_url( '/central-de-ajuda/' ) ); ?>">Central de Ajuda</a>
            <a href="<?php echo esc_url( home_url( '/sobre/' ) ); ?>">Sobre Nós</a>
        </nav>
        <div class="wcb-topbar__right">
            <span class="wcb-topbar__shipping">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Frete Grátis acima de R$199
            </span>
        </div>
    </div>
</div>

<!-- ==================== MAIN HEADER ==================== -->
<header class="wcb-header" id="wcb-header">
    <div class="wcb-container wcb-header__inner">

        <?php if ( ! $wcb_skip_shop_nav ) : ?>
        <!-- Mobile Toggle -->
        <button class="wcb-mobile-toggle" id="wcb-mobile-toggle" aria-label="Abrir menu">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <?php endif; ?>

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
            <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                <?php if ( ! is_user_logged_in() ) : ?>
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>"
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

                <a href="<?php echo esc_url( home_url( '/minha-conta/' ) ); ?>" class="wcb-header__action" title="Favoritos">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span>Favoritos</span>
                </a>

                <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>" class="wcb-header__action" title="Minha Conta">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Conta</span>
                </a>

                <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wcb-header__action" title="Carrinho">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                    <span class="wcb-header__cart-count">
                        <?php echo wcb_cart_count(); ?>
                    </span>
                    <span>Carrinho</span>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( wp_login_url() ); ?>" class="wcb-header__action" title="Login">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Entrar</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ( ! $wcb_skip_shop_nav ) : ?>
<!-- ==================== NAVIGATION BAR ==================== -->
<nav class="wcb-nav" id="wcb-nav" role="navigation" aria-label="Menu principal">
    <div class="wcb-container wcb-nav__inner">
        <?php
        if ( has_nav_menu( 'primary' ) ) {
            wp_nav_menu( array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'wcb-nav__list',
                'items_wrap'     => '<ul id="%1$s" class="%2$s" role="menubar">%3$s</ul>',
                'walker'         => new WCB_Nav_Walker(),
                'depth'          => 3,
            ) );
        } else {
            ?>
            <ul class="wcb-nav__list" role="menubar">
                <li class="wcb-nav__item" role="none">
                    <a href="<?php echo esc_url( home_url( '/produto/promocao/' ) ); ?>"
                       class="wcb-nav__link wcb-nav__link--promo" role="menuitem">Promoção</a>
                </li>
                <li class="wcb-nav__item wcb-nav__item--sep" role="none" aria-hidden="true"></li>
                <li class="wcb-nav__item" role="none"><a href="<?php echo esc_url( home_url( '/produto/pods/' ) ); ?>" class="wcb-nav__link" role="menuitem">Pods</a></li>
                <li class="wcb-nav__item" role="none"><a href="<?php echo esc_url( home_url( '/produto/cartuchos/' ) ); ?>" class="wcb-nav__link" role="menuitem">Cartuchos</a></li>
                <li class="wcb-nav__item" role="none"><a href="<?php echo esc_url( home_url( '/produto/coils/' ) ); ?>" class="wcb-nav__link" role="menuitem">Coils</a></li>
                <li class="wcb-nav__item" role="none"><a href="<?php echo esc_url( home_url( '/produto/juices/' ) ); ?>" class="wcb-nav__link" role="menuitem">Juices</a></li>
                <li class="wcb-nav__item" role="none"><a href="<?php echo esc_url( home_url( '/produto/kits/' ) ); ?>" class="wcb-nav__link" role="menuitem">Kits</a></li>
                <li class="wcb-nav__item" role="none"><a href="<?php echo esc_url( home_url( '/produto/acessorios/' ) ); ?>" class="wcb-nav__link" role="menuitem">Acessórios</a></li>
            </ul>
            <?php
        }
        ?>
    </div>
</nav>

<!-- ==================== SUBNAV (Nível 2 — Sticky) ==================== -->
<div class="wcb-subnav" id="wcb-subnav" role="navigation" aria-label="Submenu da categoria">
    <div class="wcb-subnav__inner" id="wcb-subnav-inner"></div>
</div>

<?php endif; ?>

</div><!-- /.wcb-site-header -->

<?php if ( ! $wcb_skip_shop_nav ) : ?>

<script>
(function () {
    'use strict';
    var subnav      = document.getElementById('wcb-subnav');
    var subnavInner = document.getElementById('wcb-subnav-inner');
    var navItems    = document.querySelectorAll('.wcb-nav__item--mega');
    var hideTimer   = null;

    function clearHide() { if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; } }

    function esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function buildPanel(subLinks, title, href) {
        var h = '<div class="wcb-subnav__panel">';
        h += '<div class="wcb-subnav__panel-header">';
        h += '<span class="wcb-subnav__panel-title">' + esc(title) + '</span>';
        h += '<a href="' + esc(href) + '" class="wcb-subnav__panel-see-all">Ver todos</a>';
        h += '</div>';
        h += '<ul class="wcb-subnav__panel-list">';
        subLinks.forEach(function (s) {
            h += '<li><a href="' + esc(s.href) + '">' + esc(s.textContent.trim()) + '</a></li>';
        });
        h += '</ul>';
        h += '<div class="wcb-subnav__panel-footer"><div class="wcb-subnav__quick-links">';
        h += '<span>Acesso rápido:</span>';
        h += '<a href="<?php echo esc_url(home_url("/loja/?orderby=popularity")); ?>">🔥 Mais Vendidos</a>';
        h += '<a href="<?php echo esc_url(home_url("/loja/?orderby=date")); ?>">✨ Lançamentos</a>';
        h += '<a href="<?php echo esc_url(home_url("/loja/?on_sale=true")); ?>">🏷️ Ofertas</a>';
        h += '</div></div></div>';
        return h;
    }

    function showSubnav(megaEl, label) {
        clearHide();
        var links = megaEl.querySelectorAll(':scope > .wcb-mega__inner > .wcb-mega__simple > li > .wcb-mega__link');
        if (!links.length) { hideSubnav(); return; }

        var html = '<span class="wcb-subnav__label">' + esc(label) + '</span>';

        links.forEach(function (a) {
            var li = a.closest('li');
            var subUl = li ? li.querySelector('ul') : null;
            var hasPanel = subUl && subUl.querySelectorAll('a').length;
            var title = a.textContent.trim();

            html += '<div class="wcb-subnav__item">';
            html += '<a href="' + esc(a.href) + '" class="wcb-subnav__link">' + esc(title);
            if (hasPanel) {
                html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><path d="M6 9l6 6 6-6"/></svg>';
            }
            html += '</a>';
            if (hasPanel) {
                html += buildPanel(Array.from(subUl.querySelectorAll('a')), title, a.href);
            }
            html += '</div>';
        });

        subnavInner.innerHTML = html;
        subnav.classList.add('visible');

        // Mark active by URL
        var cur = window.location.href;
        subnavInner.querySelectorAll('.wcb-subnav__link').forEach(function (lnk) {
            var p = lnk.pathname;
            if (p && p.length > 1 && cur.indexOf(p) !== -1) {
                lnk.closest('.wcb-subnav__item').classList.add('wcb-subnav__item--active');
            }
        });
    }

    function hideSubnav() {
        hideTimer = setTimeout(function () {
            subnav.classList.remove('visible');
            subnavInner.innerHTML = '';
        }, 200);
    }

    navItems.forEach(function (li) {
        var mega = li.querySelector('.wcb-mega');
        var link = li.querySelector('.wcb-nav__link');
        if (!mega) return;

        li.addEventListener('mouseenter', function () {
            clearHide();
            var label = link ? (link.firstChild ? link.firstChild.textContent.trim() : link.textContent.trim()) : '';
            showSubnav(mega, label);
        });
        li.addEventListener('mouseleave', function () {
            if (!subnav.matches(':hover')) hideSubnav();
        });
    });

    subnav.addEventListener('mouseenter', clearHide);
    subnav.addEventListener('mouseleave', function () {
        var anyHovered = Array.from(navItems).some(function (li) { return li.matches(':hover'); });
        if (!anyHovered) hideSubnav();
    });

    // Auto-show subnav if current page is a subcategory
    document.addEventListener('DOMContentLoaded', function () {
        var cur = window.location.href;
        navItems.forEach(function (li) {
            var mega = li.querySelector('.wcb-mega');
            var link = li.querySelector('.wcb-nav__link');
            if (!mega) return;
            var links = mega.querySelectorAll('.wcb-mega__link');
            var active = Array.from(links).some(function (a) {
                return a.pathname && a.pathname.length > 1 && cur.indexOf(a.pathname) !== -1;
            });
            if (active) {
                var label = link ? (link.firstChild ? link.firstChild.textContent.trim() : link.textContent.trim()) : '';
                showSubnav(mega, label);
            }
        });
    });
})();
</script>

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
        if ( has_nav_menu( 'primary' ) ) {
            wp_nav_menu( array(
                'theme_location' => 'primary',
                'container'      => false,
                'items_wrap'     => '%3$s',
                'depth'          => 1,
                'fallback_cb'    => false,
            ) );
        } else {
            echo '<a href="' . esc_url( home_url( '/' ) ) . '">Início</a>';
            echo '<a href="' . esc_url( home_url( '/loja/' ) ) . '">Loja</a>';
            if ( class_exists( 'WooCommerce' ) ) {
                echo '<a href="' . esc_url( wc_get_cart_url() ) . '">Carrinho</a>';
                echo '<a href="' . esc_url( wc_get_account_endpoint_url( 'dashboard' ) ) . '">Minha Conta</a>';
            }
        }
        ?>
    </nav>
</div>

<?php endif; ?>
