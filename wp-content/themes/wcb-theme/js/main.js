/**
 * WCB Theme — Main JavaScript
 *
 * @package WCB_Theme
 * @version 1.0.0
 */

(function () {
    'use strict';

    /* ============================================================
       STICKY HEADER
       ============================================================ */
    const header = document.getElementById('wcb-header');

    if (header) {
        let lastScroll = 0;

        window.addEventListener('scroll', () => {
            if (typeof window.matchMedia === 'function' && window.matchMedia('(max-width: 1023px)').matches) {
                header.classList.remove('scrolled');
                return;
            }
            const currentScroll = window.pageYOffset;

            if (currentScroll > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }

            lastScroll = currentScroll;
        }, { passive: true });
    }

    /* ============================================================
       PREMIUM MEGA MENU — Acessórios hover-intent
       ============================================================ */
    (function () {
        const trigger = document.getElementById('wcb-mega-trigger');
        if (!trigger) return;

        const link = trigger.querySelector('.wcb-nav__link--has-mega');
        let closeTimer = null;

        function openMega() {
            clearTimeout(closeTimer);
            trigger.classList.add('is-open');
            if (link) link.setAttribute('aria-expanded', 'true');
        }

        function closeMega() {
            closeTimer = setTimeout(() => {
                trigger.classList.remove('is-open');
                if (link) link.setAttribute('aria-expanded', 'false');
            }, 120);
        }

        trigger.addEventListener('mouseenter', openMega);
        trigger.addEventListener('mouseleave', closeMega);

        const mega = trigger.querySelector('.wcb-mega');
        if (mega) {
            mega.addEventListener('mouseenter', () => clearTimeout(closeTimer));
            mega.addEventListener('mouseleave', closeMega);
        }

        // Keyboard: Escape closes
        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMega();
        });

        // Click outside closes
        document.addEventListener('click', (e) => {
            if (!trigger.contains(e.target)) {
                trigger.classList.remove('is-open');
                if (link) link.setAttribute('aria-expanded', 'false');
            }
        });
    })();

    /* ============================================================
       OLD MEGA MENU (Departments btn — kept for compat)
       ============================================================ */
    const deptBtn = document.getElementById('wcb-departments-btn');
    const megaMenu = document.getElementById('wcb-mega-menu');

    if (deptBtn && megaMenu) {
        deptBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            megaMenu.classList.toggle('active');
            deptBtn.setAttribute('aria-expanded', megaMenu.classList.contains('active'));
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!megaMenu.contains(e.target) && !deptBtn.contains(e.target)) {
                megaMenu.classList.remove('active');
                deptBtn.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                megaMenu.classList.remove('active');
                deptBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ============================================================
       MOBILE MENU
       ============================================================ */
    const mobileToggle = document.getElementById('wcb-mobile-toggle');
    const mobileMenu = document.getElementById('wcb-mobile-menu');
    const mobileOverlay = document.getElementById('wcb-mobile-overlay');
    const mobileClose = document.getElementById('wcb-mobile-close');
    /** Elemento que abriu o drawer (toggle, barra inferior, etc.) — foco devolvido ao fechar. */
    let wcbMobileMenuOpener = null;

    /** Focos tabuláveis no drawer (ignora ramos com aria-hidden="true", ex. painéis inativos). */
    function wcbMobileMenuGetFocusables() {
        if (!mobileMenu) {
            return [];
        }
        const sel = [
            'a[href]',
            'button:not([disabled])',
            'input:not([disabled]):not([type="hidden"])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])'
        ].join(', ');
        const nodes = Array.prototype.slice.call(mobileMenu.querySelectorAll(sel));
        return nodes.filter(function (el) {
            if (el.getAttribute && el.getAttribute('tabindex') === '-1') {
                return false;
            }
            const r = el.getBoundingClientRect();
            if (!r.width && !r.height) {
                return false;
            }
            let p = el;
            while (p && p !== mobileMenu) {
                if (p.getAttribute && p.getAttribute('aria-hidden') === 'true') {
                    return false;
                }
                p = p.parentElement;
            }
            return true;
        });
    }

    /** Foco preso no dialog (WCAG 2.1 — Tab / Shift+Tab). */
    function wcbMobileMenuOnDocumentKeydown(e) {
        if (!mobileMenu || !mobileMenu.classList.contains('active') || e.key !== 'Tab') {
            return;
        }
        const list = wcbMobileMenuGetFocusables();
        if (list.length === 0) {
            return;
        }
        const first = list[0];
        const last = list[list.length - 1];
        const ac = document.activeElement;
        const inList = list.indexOf(ac) >= 0;
        if (e.shiftKey) {
            if (!inList || ac === first) {
                e.preventDefault();
                last.focus();
            }
        } else if (!inList || ac === last) {
            e.preventDefault();
            first.focus();
        }
    }

    document.addEventListener('keydown', wcbMobileMenuOnDocumentKeydown, true);

    /** Drill-down do menu mobile (painéis horizontais). */
    window.wcbMmDrilldownReset = function () {};
    window.wcbMmDrilldownSize = function () {};
    window.wcbMmDrilldownBackIfNested = function () {
        return false;
    };
    (function wcbInitMobileMenuDrilldown() {
        if (!mobileMenu) return;
        const root = mobileMenu.querySelector('[data-wcb-mm]');
        if (!root) return;
        const viewport = root.querySelector('.wcb-mm-viewport');
        const track = root.querySelector('.wcb-mm-track');
        if (!viewport || !track) return;

        const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        let mmLayoutRaf = 0;
        function scheduleSizeTrack() {
            if (mmLayoutRaf) {
                return;
            }
            mmLayoutRaf = window.requestAnimationFrame(function () {
                mmLayoutRaf = 0;
                if (mobileMenu.classList.contains('active')) {
                    sizeTrack();
                }
            });
        }

        let stack = [0];
        /** Largura aplicada aos painéis no último sizeTrack (transform usa idx * lastPanelW — evita drift vs soma de offsetWidth). */
        let lastPanelW = 360;

        function getPanels() {
            return Array.prototype.slice.call(track.children);
        }

        /** Garante que o topo do stack aponta para um painel existente (corrige estado órfão após DOM / bugs). */
        function clampStackToPanels() {
            const panels = getPanels();
            if (!panels.length) {
                return;
            }
            const idx = stack[stack.length - 1];
            if (idx >= 0 && idx < panels.length) {
                return;
            }
            stack = [0];
            setAriaVisible(0);
            mobileMenu.querySelectorAll('.wcb-mm-next').forEach(function (b) {
                b.setAttribute('aria-expanded', 'false');
            });
        }

        /** Largura útil do viewport (inteiro) — clientWidth evita subpixel; fallback se menu ainda não layoutou. */
        function readViewportWidth() {
            let w = Math.round(viewport.clientWidth);
            if (w < 1) {
                w = Math.round(viewport.getBoundingClientRect().width);
            }
            if (w < 1 && root) {
                w = Math.round(root.clientWidth);
            }
            if (w < 1 && mobileMenu) {
                w = Math.round(mobileMenu.clientWidth);
            }
            return Math.max(1, w);
        }

        function applyTransform(instant) {
            clampStackToPanels();
            const panels = getPanels();
            const idx = stack[stack.length - 1];
            if (idx < 0 || idx >= panels.length) return;
            const w = lastPanelW > 0 ? lastPanelW : readViewportWidth();
            const x = -(idx * w);
            if (instant) {
                track.style.transition = 'none';
                track.style.transform = 'translate3d(' + x + 'px,0,0)';
                void track.offsetHeight;
                track.style.transition = '';
            } else {
                track.style.transform = 'translate3d(' + x + 'px,0,0)';
            }
        }

        function setAriaVisible(idx) {
            getPanels().forEach(function (p, i) {
                const active = i === idx;
                p.setAttribute('aria-hidden', active ? 'false' : 'true');
                p.classList.toggle('is-mm-visible', active);
                if (active) {
                    p.removeAttribute('tabindex');
                } else {
                    p.setAttribute('tabindex', '-1');
                }
                if ('inert' in p) {
                    p.inert = !active;
                }
            });
        }

        /** Scroll da área lista do painel visível para o topo (ao mudar de nível). */
        function scrollActivePanelListTop() {
            const idx = stack[stack.length - 1];
            const p = getPanels()[idx];
            if (!p) {
                return;
            }
            const sc = p.querySelector('.wcb-mm-scroll');
            if (sc) {
                sc.scrollTop = 0;
            }
        }

        /** Sincroniza largura do viewport → painéis e largura total do track (sem alterar transform). */
        function layoutPanelWidths() {
            const w = readViewportWidth();
            lastPanelW = w;
            const panels = getPanels();
            if (!panels.length) return;
            clampStackToPanels();
            const trackW = panels.length * w;
            track.style.width = trackW + 'px';
            track.style.minWidth = trackW + 'px';
            track.style.flexShrink = '0';
            panels.forEach(function (p) {
                p.style.width = w + 'px';
                p.style.flexBasis = w + 'px';
                p.style.maxWidth = w + 'px';
                p.style.minWidth = w + 'px';
                p.style.flexShrink = '0';
                p.style.flexGrow = '0';
            });
        }

        function sizeTrack() {
            layoutPanelWidths();
            applyTransform(true);
        }

        function goToIndex(idx) {
            const panels = getPanels();
            if (idx < 0 || idx >= panels.length) return;
            const prevTop = stack[stack.length - 1];
            layoutPanelWidths();
            applyTransform(true);
            stack.push(idx);
            setAriaVisible(idx);
            scrollActivePanelListTop();
            /* Só anima um passo horizontal (painel seguinte no track). Saltos (ex.: raiz → índice 8) sem animação — evita “slider” através de todas as colunas (layout aparentemente partido). */
            const animate = !prefersReducedMotion && prevTop >= 0 && idx === prevTop + 1;
            applyTransform(!animate);
            const back = panels[idx] && panels[idx].querySelector('.wcb-mm-back');
            if (back) {
                back.focus();
            }
        }

        function goBack() {
            if (stack.length <= 1) return;
            const oldIdx = stack[stack.length - 1];
            const left = getPanels()[oldIdx];
            let opener = null;
            stack.pop();
            const newIdx = stack[stack.length - 1];
            if (left && left.id) {
                opener = mobileMenu.querySelector('.wcb-mm-next[aria-controls="' + left.id + '"]');
                if (opener) opener.setAttribute('aria-expanded', 'false');
            }
            setAriaVisible(newIdx);
            layoutPanelWidths();
            const animateBack = !prefersReducedMotion && (oldIdx - newIdx) === 1;
            applyTransform(!animateBack);
            scrollActivePanelListTop();
            if (opener && typeof opener.focus === 'function') {
                opener.focus();
            }
        }

        function resetStack() {
            stack = [0];
            setAriaVisible(0);
            mobileMenu.querySelectorAll('.wcb-mm-next').forEach(function (b) {
                b.setAttribute('aria-expanded', 'false');
            });
            sizeTrack();
            scrollActivePanelListTop();
        }

        track.addEventListener('click', function (e) {
            const backBtn = e.target.closest('.wcb-mm-back');
            if (backBtn && mobileMenu.contains(backBtn)) {
                e.preventDefault();
                goBack();
                return;
            }
            const nextBtn = e.target.closest('.wcb-mm-next');
            if (!nextBtn || !mobileMenu.contains(nextBtn)) return;
            e.preventDefault();
            const id = nextBtn.getAttribute('aria-controls');
            if (!id) return;
            const panel = document.getElementById(id);
            if (!panel || !track.contains(panel)) return;
            const idx = getPanels().indexOf(panel);
            if (idx < 0) return;
            nextBtn.setAttribute('aria-expanded', 'true');
            goToIndex(idx);
        });

        setAriaVisible(0);
        sizeTrack();
        window.wcbMmDrilldownReset = resetStack;
        window.wcbMmDrilldownSize = sizeTrack;
        window.wcbMmDrilldownBackIfNested = function () {
            if (stack.length <= 1) return false;
            goBack();
            return true;
        };
        window.addEventListener('resize', function () {
            if (mobileMenu.classList.contains('active')) {
                scheduleSizeTrack();
            }
        }, { passive: true });

        function sizeTrackIfMenuActive() {
            scheduleSizeTrack();
        }

        if (typeof ResizeObserver !== 'undefined') {
            const ro = new ResizeObserver(function () {
                if (mobileMenu.classList.contains('active')) {
                    scheduleSizeTrack();
                }
            });
            ro.observe(viewport);
            ro.observe(mobileMenu);
            const navEl = mobileMenu.querySelector('.wcb-mobile-menu__nav');
            if (navEl) {
                ro.observe(navEl);
            }
        }

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', sizeTrackIfMenuActive, { passive: true });
        }

        mobileMenu.addEventListener('transitionend', function (e) {
            if (e.target !== mobileMenu || e.propertyName !== 'transform') {
                return;
            }
            if (!mobileMenu.classList.contains('active')) {
                return;
            }
            scheduleSizeTrack();
        });

    })();

    function wcbCloseShopFiltersIfOpen() {
        const sidebar = document.getElementById('wcb-shop-sidebar');
        const overlay = document.getElementById('wcb-sidebar-overlay');
        const filterToggle = document.getElementById('wcb-filter-toggle');
        if (sidebar && sidebar.classList.contains('is-open')) {
            sidebar.classList.remove('is-open');
            if (overlay) overlay.classList.remove('is-visible');
            document.body.classList.remove('wcb-sidebar-open');
            if (filterToggle) filterToggle.setAttribute('aria-expanded', 'false');
        }
    }

    function openMobileMenu() {
        wcbCloseShopFiltersIfOpen();
        if (mobileMenu && mobileOverlay) {
            const ae = document.activeElement;
            if (ae && typeof ae.focus === 'function') {
                wcbMobileMenuOpener = ae;
            } else {
                wcbMobileMenuOpener = null;
            }
            mobileMenu.classList.add('active');
            mobileOverlay.classList.add('active');
            mobileMenu.setAttribute('aria-hidden', 'false');
            mobileOverlay.setAttribute('aria-hidden', 'false');
            if (mobileToggle) {
                mobileToggle.setAttribute('aria-expanded', 'true');
            }
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    if (typeof window.wcbMmDrilldownReset === 'function') {
                        window.wcbMmDrilldownReset();
                    } else if (typeof window.wcbMmDrilldownSize === 'function') {
                        window.wcbMmDrilldownSize();
                    }
                    requestAnimationFrame(function () {
                        if (typeof window.wcbMmDrilldownSize === 'function') {
                            window.wcbMmDrilldownSize();
                        }
                        if (mobileClose && typeof mobileClose.focus === 'function') {
                            mobileClose.focus();
                        }
                    });
                });
            });
        }
    }

    function closeMobileMenu() {
        if (mobileMenu && mobileOverlay) {
            mobileMenu.classList.remove('active');
            mobileOverlay.classList.remove('active');
            mobileMenu.setAttribute('aria-hidden', 'true');
            mobileOverlay.setAttribute('aria-hidden', 'true');
            if (mobileToggle) {
                mobileToggle.setAttribute('aria-expanded', 'false');
            }
            document.body.style.overflow = '';
            if (typeof window.wcbMmDrilldownReset === 'function') {
                window.wcbMmDrilldownReset();
            }
            const backTo = wcbMobileMenuOpener;
            wcbMobileMenuOpener = null;
            if (backTo && typeof backTo.focus === 'function') {
                try {
                    backTo.focus();
                } catch (err) { /* ignore */ }
            } else if (mobileToggle && typeof mobileToggle.focus === 'function') {
                mobileToggle.focus();
            }
        }
    }

    window.wcbNavCloseMobile = closeMobileMenu;
    window.wcbNavCloseShopFilters = wcbCloseShopFiltersIfOpen;

    if (mobileToggle) mobileToggle.addEventListener('click', openMobileMenu);
    if (mobileClose) mobileClose.addEventListener('click', closeMobileMenu);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileMenu);

    /* Barra inferior: o markup pode vir depois de wp_footer() / main.js — ligar também no DOMContentLoaded */
    function bindWcbMbarMenuButton() {
        const el = document.getElementById('wcb-mbar-menu');
        if (!el || el.dataset.wcbNavMenuBound === '1') return;
        el.addEventListener('click', function (e) {
            if (mobileMenu && mobileOverlay) {
                e.preventDefault();
                openMobileMenu();
            }
        });
        el.dataset.wcbNavMenuBound = '1';
    }
    bindWcbMbarMenuButton();
    document.addEventListener('DOMContentLoaded', bindWcbMbarMenuButton);

    /* Desktop: fechar drawer e limpar overflow se a janela alarga (evita estado preso após resize). */
    window.addEventListener('resize', function () {
        if (window.innerWidth > 1023) {
            closeMobileMenu();
            wcbCloseShopFiltersIfOpen();
        } else if (mobileMenu && mobileMenu.classList.contains('active') && typeof window.wcbMmDrilldownSize === 'function') {
            window.requestAnimationFrame(function () {
                if (mobileMenu.classList.contains('active')) {
                    window.wcbMmDrilldownSize();
                }
            });
        }
    }, { passive: true });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || !mobileMenu || !mobileMenu.classList.contains('active')) return;
        if (typeof window.wcbMmDrilldownBackIfNested === 'function' && window.wcbMmDrilldownBackIfNested()) {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        closeMobileMenu();
    });

    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('xoo_wsc_cart_toggled', function (e, type) {
            if (type === 'show') {
                closeMobileMenu();
                wcbCloseShopFiltersIfOpen();
                const sb = document.getElementById('wcb-search');
                if (sb) sb.classList.remove('active');
            }
        });
    }

    /* Hero principal: slider único em #wcb-hero (bloco legado wcb-hero-slider removido — estava desligado do DOM). */

    /* ============================================================
       FAVORITE TOGGLE — Com persistência no servidor (wishlist)
       ============================================================ */

    // ── Toast helper ─────────────────────────────────────────────
    (function () {
        let toastEl = null;
        let toastTimer = null;

        function getToast() {
            if (!toastEl) {
                toastEl = document.createElement('div');
                toastEl.className = 'wcb-fav-toast';
                toastEl.setAttribute('role', 'status');
                toastEl.setAttribute('aria-live', 'polite');
                toastEl.setAttribute('aria-atomic', 'true');
                document.body.appendChild(toastEl);
            }
            return toastEl;
        }

        window.wcbShowFavToast = function (msg, type) {
            const t = getToast();
            t.className = 'wcb-fav-toast wcb-fav-toast--' + (type || 'added');
            t.innerHTML = msg;
            requestAnimationFrame(() => t.classList.add('show'));
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => t.classList.remove('show'), 2600);
        };
    })();

    // ── Atualizar badge de favoritos no header ───────────────────
    function updateFavBadge(count) {
        function apply(el) {
            if (!el) return;
            if (count > 0) {
                el.textContent = count;
                el.style.display = 'flex';
            } else {
                el.textContent = '0';
                el.style.display = 'none';
            }
        }
        apply(document.getElementById('wcb-header-fav-count'));
        apply(document.getElementById('wcb-mbar-fav-count'));
    }

    // ── Restaurar estado dos botões ao carregar a página ─────────
    (function () {
        function applyWishlistState(ids) {
            ids.forEach(function (id) {
                document.querySelectorAll('.wcb-product-card__fav[data-product-id="' + id + '"]').forEach(function (btn) {
                    btn.classList.add('active');
                });
            });
        }

        const wl = window.wcbWishlist;
        if (wl && wl.wishlist && wl.wishlist.length) {
            applyWishlistState(wl.wishlist);
        }
    })();

    /**
     * Sincroniza favoritos com o servidor (POST + nonce). Opcional: bfcache / multi-aba.
     * O carregamento inicial continua a usar window.wcbWishlist.wishlist (PHP).
     */
    window.wcbFetchWishlist = function () {
        const wl = window.wcbWishlist;
        if (!wl || !wl.ajaxUrl || !wl.nonce) {
            return Promise.resolve([]);
        }
        const fd = new FormData();
        fd.append('action', 'wcb_get_wishlist');
        fd.append('nonce', wl.nonce);
        return fetch(wl.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success || !data.data) {
                    return wl.wishlist || [];
                }
                const ids = Array.isArray(data.data.wishlist) ? data.data.wishlist : [];
                wl.wishlist = ids;
                document.querySelectorAll('.wcb-product-card__fav.active').forEach(function (btn) {
                    btn.classList.remove('active');
                });
                ids.forEach(function (id) {
                    document.querySelectorAll('.wcb-product-card__fav[data-product-id="' + id + '"]').forEach(function (btn) {
                        btn.classList.add('active');
                    });
                });
                updateFavBadge(ids.length);
                return ids;
            })
            .catch(function () {
                return wl.wishlist || [];
            });
    };

    // ── Click no coração ─────────────────────────────────────────
    document.addEventListener('click', function (e) {
        const favBtn = e.target.closest('.wcb-product-card__fav');
        if (!favBtn) return;

        e.preventDefault();
        e.stopPropagation();

        const productId = parseInt(favBtn.dataset.productId, 10);
        if (!productId) return;

        const wl = window.wcbWishlist;

        // Usuário NÃO logado → toast de login
        if (!wl || !wl.isLoggedIn) {
            window.wcbShowFavToast(
                '🔒 <a href="' + (wl ? wl.loginUrl : '/minha-conta/') + '" style="color:#fff;text-decoration:underline">Faça login</a> para salvar favoritos',
                'login'
            );
            return;
        }

        // Feedback visual imediato (optimistic UI)
        const isActive = favBtn.classList.toggle('active');

        // Toast instantâneo — sem esperar AJAX
        if (isActive) {
            window.wcbShowFavToast('❤️ Adicionado aos favoritos!', 'added');
        } else {
            window.wcbShowFavToast('💔 Removido dos favoritos', 'removed');
        }

        // Chamar AJAX em background
        const fd = new FormData();
        fd.append('action', 'wcb_toggle_wishlist');
        fd.append('nonce', wl.nonce);
        fd.append('product_id', productId);

        fetch(wl.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    // Reverter se erro
                    favBtn.classList.toggle('active');
                    return;
                }
                // Atualizar lista local
                wl.wishlist = data.data.wishlist || [];
                updateFavBadge(wl.wishlist.length);

                // Página Meus Favoritos: ao desfavoritar, remove o card (só o coração permanece na UI)
                if (data.data && data.data.action === 'removed') {
                    const shell = favBtn.closest('.wcb-wl-card');
                    if (shell) {
                        shell.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                        shell.style.opacity = '0';
                        shell.style.transform = 'scale(0.95)';
                        setTimeout(function () {
                            shell.remove();
                            var wlHeaderSub = document.querySelector('.wcb-wl-header__sub');
                            if (wlHeaderSub) {
                                var c = wl.wishlist.length;
                                wlHeaderSub.textContent = c + ' produto' + (c !== 1 ? 's' : '') + ' salvo' + (c !== 1 ? 's' : '');
                            }
                            var remaining = document.querySelectorAll('.wcb-wl-card, .wcb-wishlist-card');
                            if (remaining.length === 0) {
                                location.reload();
                            }
                        }, 260);
                    }
                }
            })
            .catch(() => {
                favBtn.classList.toggle('active');
            });
    });

    // ── Remover (layout legado wcb-wishlist-card, se existir) ───
    document.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.wcb-wishlist-card__remove');
        if (!removeBtn) return;

        e.preventDefault();

        const productId = parseInt(removeBtn.dataset.productId, 10);
        const card = removeBtn.closest('.wcb-wishlist-card');
        const wl = window.wcbWishlist;

        if (!productId || !wl || !wl.isLoggedIn) return;

        if (card) {
            card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            setTimeout(() => card.remove(), 260);
        }

        const fd = new FormData();
        fd.append('action', 'wcb_toggle_wishlist');
        fd.append('nonce', wl.nonce);
        fd.append('product_id', productId);

        fetch(wl.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    wl.wishlist = data.data.wishlist || [];
                    updateFavBadge(wl.wishlist.length);
                    window.wcbShowFavToast('💔 Removido dos favoritos', 'removed');
                    var wlHeaderSub = document.querySelector('.wcb-wl-header__sub');
                    if (wlHeaderSub) {
                        var c = wl.wishlist.length;
                        wlHeaderSub.textContent = c + ' produto' + (c !== 1 ? 's' : '') + ' salvo' + (c !== 1 ? 's' : '');
                    }
                    var remaining = document.querySelectorAll('.wcb-wl-card, .wcb-wishlist-card');
                    if (remaining.length === 0) {
                        setTimeout(function() { location.reload(); }, 500);
                    }
                }
            });
    });

    /* ============================================================
       MOBILE SEARCH TOGGLE (≤390px: lupa nas ações + faixa fixa sob #wcb-site-header)
       ============================================================ */
    const searchToggle = document.getElementById('wcb-search-toggle');
    const searchBox = document.getElementById('wcb-search');

    function wcbNarrowHeaderSearch() {
        return typeof window.matchMedia === 'function' && window.matchMedia('(max-width: 390px)').matches;
    }

    function wcbSearchOverlayTopPx() {
        const sh = document.getElementById('wcb-site-header');
        if (!sh) return '72px';
        const bottom = sh.getBoundingClientRect().bottom;
        return (Number.isFinite(bottom) ? Math.max(0, Math.ceil(bottom)) : 72) + 'px';
    }

    function wcbSyncSearchOverlayTop() {
        if (!searchBox || !searchBox.classList.contains('active') || !wcbNarrowHeaderSearch()) return;
        document.documentElement.style.setProperty('--wcb-search-overlay-top', wcbSearchOverlayTopPx());
    }

    function handleSearchToggleVisibility() {
        if (!searchToggle) return;
        if (wcbNarrowHeaderSearch()) {
            searchToggle.removeAttribute('hidden');
            searchToggle.style.removeProperty('display');
        } else {
            searchToggle.setAttribute('hidden', '');
            searchToggle.style.display = 'none';
            if (searchBox) {
                searchBox.classList.remove('active');
            }
            document.documentElement.style.removeProperty('--wcb-search-overlay-top');
            searchToggle.setAttribute('aria-expanded', 'false');
        }
    }

    if (searchToggle && searchBox) {
        searchToggle.addEventListener('click', () => {
            searchBox.classList.toggle('active');
            const open = searchBox.classList.contains('active');
            searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            const input = searchBox.querySelector('input[type="search"]');
            if (open && input) {
                if (wcbNarrowHeaderSearch()) {
                    document.documentElement.style.setProperty('--wcb-search-overlay-top', wcbSearchOverlayTopPx());
                }
                input.focus();
            } else {
                document.documentElement.style.removeProperty('--wcb-search-overlay-top');
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape' || !searchBox || !searchBox.classList.contains('active')) return;
        searchBox.classList.remove('active');
        document.documentElement.style.removeProperty('--wcb-search-overlay-top');
        if (searchToggle) searchToggle.setAttribute('aria-expanded', 'false');
    });

    /* ============================================================
       RESPONSIVE: Show/hide mobile search toggle
       ============================================================ */
    function handleResize() {
        handleSearchToggleVisibility();
        wcbSyncSearchOverlayTop();
        if (window.innerWidth > 1023 && searchBox) {
            searchBox.classList.remove('active');
            document.documentElement.style.removeProperty('--wcb-search-overlay-top');
            if (searchToggle) searchToggle.setAttribute('aria-expanded', 'false');
        }
    }

    window.addEventListener('resize', handleResize);
    window.addEventListener('scroll', wcbSyncSearchOverlayTop, { passive: true });
    handleResize();

    /* Altura até o fim do header sticky (toolbar da loja usa --wcb-sticky-site-header-h).
     * Não persistir medições inválidas (caixa 0×0 / bottom inválido): removeProperty deixa o :root da Fase 3. */
    (function wcbStickySiteHeaderBottomVar() {
        const siteHeader = document.getElementById('wcb-site-header');
        if (!siteHeader) return;

        const shopToolbar = document.querySelector('.wcb-shop__toolbar');

        function narrowViewport() {
            return typeof window.matchMedia === 'function'
                && window.matchMedia('(max-width: 1023px)').matches;
        }

        function applyMeasurement() {
            /* ≤1023px: não escrever variável na raiz (header em fluxo + toolbar top:0 na Fase 3). */
            if (narrowViewport()) {
                document.documentElement.style.removeProperty('--wcb-sticky-site-header-h');
                return;
            }

            const rect = siteHeader.getBoundingClientRect();

            if (
                rect.width < 1 ||
                rect.height < 1 ||
                !Number.isFinite(rect.bottom)
            ) {
                document.documentElement.style.removeProperty('--wcb-sticky-site-header-h');
                return;
            }

            const bottom = Math.ceil(rect.bottom);

            if (bottom < 1) {
                document.documentElement.style.removeProperty('--wcb-sticky-site-header-h');
                return;
            }

            document.documentElement.style.setProperty('--wcb-sticky-site-header-h', bottom + 'px');
        }

        function scheduleMeasure() {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(applyMeasurement);
            });
        }

        scheduleMeasure();

        window.addEventListener('load', scheduleMeasure, { passive: true });
        window.addEventListener('resize', scheduleMeasure, { passive: true });

        /* getBoundingClientRect no scroll: reflow (Chrome [Violation]). Só necessário em
         * desktop (≥1024) com toolbar da loja sticky. */
        function needsScrollMeasure() {
            return !!shopToolbar && !narrowViewport();
        }

        var wcbHeaderMeasureTicking = false;
        function onScrollMeasure() {
            if (wcbHeaderMeasureTicking) return;
            wcbHeaderMeasureTicking = true;
            window.requestAnimationFrame(function () {
                wcbHeaderMeasureTicking = false;
                applyMeasurement();
            });
        }

        var wcbHeaderScrollBound = false;
        function syncScrollMeasureListener() {
            var want = needsScrollMeasure();
            if (want && !wcbHeaderScrollBound) {
                window.addEventListener('scroll', onScrollMeasure, { passive: true });
                wcbHeaderScrollBound = true;
            } else if (!want && wcbHeaderScrollBound) {
                window.removeEventListener('scroll', onScrollMeasure);
                wcbHeaderScrollBound = false;
            }
        }

        syncScrollMeasureListener();
        if (window.matchMedia) {
            var mqScroll = window.matchMedia('(max-width: 1023px)');
            var onMqScroll = function () {
                syncScrollMeasureListener();
                scheduleMeasure();
            };
            if (mqScroll.addEventListener) {
                mqScroll.addEventListener('change', onMqScroll);
            } else if (mqScroll.addListener) {
                mqScroll.addListener(onMqScroll);
            }
        }

        if (typeof ResizeObserver !== 'undefined') {
            try {
                new ResizeObserver(scheduleMeasure).observe(siteHeader);
            } catch (e) { /* noop */ }
        }
    })();

    /* Altura da toolbar da loja — sidebar sticky fica sempre abaixo do header + toolbar */
    (function wcbShopToolbarHeightVar() {
        const toolbar = document.querySelector('.wcb-shop__toolbar');
        if (!toolbar) return;

        function measure() {
            const h = Math.ceil(toolbar.offsetHeight);
            if (h < 1 || !Number.isFinite(h)) {
                document.documentElement.style.removeProperty('--wcb-shop-toolbar-h');
                return;
            }
            document.documentElement.style.setProperty('--wcb-shop-toolbar-h', h + 'px');
        }

        function schedule() {
            window.requestAnimationFrame(measure);
        }

        schedule();
        window.addEventListener('load', schedule, { passive: true });
        window.addEventListener('resize', schedule, { passive: true });
        if (typeof ResizeObserver !== 'undefined') {
            try {
                new ResizeObserver(schedule).observe(toolbar);
            } catch (e) { /* noop */ }
        }
    })();

    /* Loja: toggle grid 3 vs 4 colunas (classe .grid-3 + localStorage) */
    (function wcbInitShopGridViewToggle() {
        const grid = document.querySelector('.wcb-shop__main ul.products');
        const viewBtns = document.querySelectorAll('.wcb-shop__view-btn');
        if (!grid || !viewBtns.length) return;

        function applyView(cols) {
            const v = cols === '4' ? '4' : '3';
            viewBtns.forEach((b) => {
                b.classList.toggle('is-active', b.getAttribute('data-view') === v);
            });
            grid.classList.toggle('grid-3', v === '3');
            try {
                localStorage.setItem('wcb_grid_view', v);
            } catch (e) { /* noop */ }
        }

        let saved = null;
        try {
            saved = localStorage.getItem('wcb_grid_view');
        } catch (e) { /* noop */ }
        if (saved !== '3' && saved !== '4') {
            saved = '3';
        }
        applyView(saved);

        viewBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const cols = btn.getAttribute('data-view');
                if (cols !== '3' && cols !== '4') return;
                applyView(cols);
            });
        });
    })();

    /* ============================================================
       COUNTDOWN — data-wcb-timer + data-end (Super Ofertas, cards, PDP)
       Um único setInterval para todos os nós.
       ============================================================ */
    (function wcbInitAllDataWcbTimers() {
        const roots = document.querySelectorAll('[data-wcb-timer][data-end]');
        if (!roots.length) return;

        function pad(n) { return n < 10 ? '0' + n : String(n); }

        function tick() {
            const now = Date.now();
            roots.forEach((root) => {
                const raw = root.getAttribute('data-end');
                if (!raw) return;
                const endMs = new Date(raw).getTime();
                const diff = endMs - now;
                const dEl = root.querySelector('[data-days]');
                const hEl = root.querySelector('[data-hours]');
                const mEl = root.querySelector('[data-minutes]');
                const sEl = root.querySelector('[data-seconds]');
                if (diff <= 0) {
                    if (dEl) dEl.textContent = '00';
                    if (hEl) hEl.textContent = '00';
                    if (mEl) mEl.textContent = '00';
                    if (sEl) sEl.textContent = '00';
                    root.classList.remove('wcb-cd--warning', 'wcb-cd--urgent');
                    root.classList.add('wcb-cd--done');
                    return;
                }
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                const totalMinutes = days * 1440 + hours * 60 + minutes;
                if (dEl) dEl.textContent = pad(days);
                if (hEl) hEl.textContent = pad(hours);
                if (mEl) mEl.textContent = pad(minutes);
                if (sEl) sEl.textContent = pad(seconds);
                root.classList.remove('wcb-cd--warning', 'wcb-cd--urgent', 'wcb-cd--done');
                if (totalMinutes < 10) {
                    root.classList.add('wcb-cd--urgent');
                } else if (totalMinutes < 60) {
                    root.classList.add('wcb-cd--warning');
                }
            });
        }

        tick();
        setInterval(tick, 1000);
    })();

    /* ============================================================
       SINGLE PRODUCT — GALLERY THUMBNAILS (Suporta v1 e v2)
       ============================================================ */
    // v2 (PDP Premium)
    const pdpThumbs = document.querySelectorAll('.wcb-pdp-gallery__thumb');
    const pdpMainImg = document.getElementById('wcb-pdp-main-img');

    if (pdpThumbs.length > 0 && pdpMainImg) {
        pdpThumbs.forEach(thumb => {
            thumb.addEventListener('click', () => {
                pdpThumbs.forEach(t => t.classList.remove('is-active'));
                thumb.classList.add('is-active');

                const singleSrc = thumb.dataset.single;
                const fullSrc = thumb.dataset.full;
                if (singleSrc) {
                    pdpMainImg.style.opacity = '0';
                    setTimeout(() => {
                        pdpMainImg.src = singleSrc;
                        pdpMainImg.setAttribute('data-zoom', fullSrc || singleSrc);
                        pdpMainImg.style.opacity = '1';
                    }, 150);
                }
            });
        });
    }

    // v1 fallback (PDP antiga)
    const galleryThumbs = document.querySelectorAll('.wcb-gallery__thumb');
    const galleryMainImg = document.getElementById('wcb-gallery-main-img');

    if (galleryThumbs.length > 0 && galleryMainImg) {
        galleryThumbs.forEach(thumb => {
            thumb.addEventListener('click', () => {
                galleryThumbs.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
                const fullSrc = thumb.dataset.full;
                if (fullSrc) {
                    galleryMainImg.style.opacity = '0';
                    galleryMainImg.style.transform = 'scale(0.96)';
                    setTimeout(() => {
                        galleryMainImg.src = fullSrc;
                        galleryMainImg.style.opacity = '1';
                        galleryMainImg.style.transform = 'scale(1)';
                    }, 180);
                }
            });
        });
        if (galleryMainImg) {
            galleryMainImg.style.transition = 'opacity 0.18s ease, transform 0.18s ease';
        }
    }

    /* ============================================================
       SINGLE PRODUCT — ZOOM ON HOVER (PDP v2)
       Alinhado ao layout desktop (min-width: 1024px); matchMedia + resize.
       ============================================================ */
    const zoomWrap = document.getElementById('wcb-pdp-zoom');
    if (zoomWrap && pdpMainImg && typeof window.matchMedia === 'function') {
        const mqZoomDesk = window.matchMedia('(min-width: 1024px)');
        let onZoomMove = null;
        let onZoomLeave = null;

        function unbindPdpZoom() {
            if (onZoomMove) {
                zoomWrap.removeEventListener('mousemove', onZoomMove);
            }
            if (onZoomLeave) {
                zoomWrap.removeEventListener('mouseleave', onZoomLeave);
            }
            onZoomMove = null;
            onZoomLeave = null;
            pdpMainImg.style.transformOrigin = '';
            pdpMainImg.style.transform = '';
        }

        function bindPdpZoom() {
            unbindPdpZoom();
            if (!mqZoomDesk.matches) {
                return;
            }
            onZoomMove = (e) => {
                const rect = zoomWrap.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;
                pdpMainImg.style.transformOrigin = x + '% ' + y + '%';
                pdpMainImg.style.transform = 'scale(2)';
            };
            onZoomLeave = () => {
                pdpMainImg.style.transformOrigin = 'center';
                pdpMainImg.style.transform = 'scale(1)';
            };
            zoomWrap.addEventListener('mousemove', onZoomMove);
            zoomWrap.addEventListener('mouseleave', onZoomLeave);
        }

        bindPdpZoom();
        if (typeof mqZoomDesk.addEventListener === 'function') {
            mqZoomDesk.addEventListener('change', bindPdpZoom);
        } else if (typeof mqZoomDesk.addListener === 'function') {
            mqZoomDesk.addListener(bindPdpZoom);
        }
    }

    /* ============================================================
       SINGLE PRODUCT — STICKY BUY BAR (Suporta v1 e v2)
       ============================================================ */
    // v2 (PDP Premium — card fixo no canto inferior ao rolar)
    const pdpSticky = document.getElementById('wcb-pdp-sticky');
    const pdpBuybox = document.getElementById('wcb-pdp-buybox');

    if (pdpSticky && pdpBuybox) {
        const storageKey = 'wcb_pdp_sticky_dismiss_' + (pdpSticky.dataset.productId || '0');
        let stickyDismissed = false;
        try {
            stickyDismissed = sessionStorage.getItem(storageKey) === '1';
        } catch (e) {
            stickyDismissed = false;
        }
        if (stickyDismissed) {
            pdpSticky.classList.add('is-dismissed');
        }

        const stickyClose = pdpSticky.querySelector('.wcb-pdp-sticky__close');
        if (stickyClose) {
            stickyClose.addEventListener('click', function () {
                pdpSticky.classList.remove('is-visible');
                pdpSticky.classList.add('is-dismissed');
                try {
                    sessionStorage.setItem(storageKey, '1');
                } catch (err) {
                    /* ignore quota / private mode */
                }
            });
        }

        const obs = new IntersectionObserver((entries) => {
            if (pdpSticky.classList.contains('is-dismissed')) {
                return;
            }
            if (!entries[0].isIntersecting) {
                pdpSticky.classList.add('is-visible');
            } else {
                pdpSticky.classList.remove('is-visible');
            }
        }, { rootMargin: '-100px 0px 0px 0px', threshold: 0.1 });
        obs.observe(pdpBuybox);
    }

    /* ============================================================
       SINGLE PRODUCT — descrição breve no buybox (2 linhas → Ver mais / Ver menos)
       Altura colapsada: CSS #wcb-pdp-buybox .wcb-pdp-buybox__desc-inner max-height
       ============================================================ */
    (function () {
        function measureAndBind(wrap) {
            const inner = wrap.querySelector('.wcb-pdp-buybox__desc-inner');
            const btn = wrap.querySelector('.wcb-pdp-buybox__desc-toggle');
            if (!inner || !btn) {
                return;
            }

            const more = wrap.dataset.labelMore || 'Ver mais';
            const less = wrap.dataset.labelLess || 'Ver menos';

            wrap.classList.add('is-collapsible');
            void inner.offsetHeight;
            const needsToggle = inner.scrollHeight > inner.clientHeight + 2;

            if (!needsToggle) {
                wrap.classList.remove('is-collapsible', 'is-expanded');
                btn.hidden = true;
                btn.setAttribute('aria-expanded', 'false');
                return;
            }

            btn.hidden = false;
            btn.setAttribute('aria-expanded', 'false');
            btn.textContent = more;
            wrap.classList.remove('is-expanded');

            btn.addEventListener('click', function () {
                const expanded = wrap.classList.toggle('is-expanded');
                btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                btn.textContent = expanded ? less : more;
            });
        }

        function run(root) {
            var nodes = root && root.querySelectorAll
                ? root.querySelectorAll('.wcb-pdp-buybox__desc[data-wcb-short-desc]')
                : document.querySelectorAll('.wcb-pdp-buybox__desc[data-wcb-short-desc]');
            nodes.forEach(measureAndBind);
        }

        window.wcbInitBuyboxShortDesc = function (root) {
            requestAnimationFrame(function () { run(root); });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                window.wcbInitBuyboxShortDesc();
            });
        } else {
            window.wcbInitBuyboxShortDesc();
        }
    })();

    // v1 fallback (Sticky no bottom)
    const stickyAtc = document.getElementById('wcb-sticky-atc');
    const buyArea = document.getElementById('wcb-buy-area');

    if (stickyAtc && buyArea) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) {
                    stickyAtc.classList.add('visible');
                } else {
                    stickyAtc.classList.remove('visible');
                }
            });
        }, { rootMargin: '-80px 0px 0px 0px', threshold: 0 });
        observer.observe(buyArea);
    }

    /* ============================================================
       SINGLE PRODUCT — TABS (Suporta v1 e v2)
       ============================================================ */
    // v2 (PDP Premium)
    const pdpTabBtns = document.querySelectorAll('.wcb-pdp-tab-btn');
    const pdpTabPanels = document.querySelectorAll('.wcb-pdp-tab-panel');

    if (pdpTabBtns.length > 0) {
        pdpTabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.dataset.tab;
                pdpTabBtns.forEach(b => b.classList.remove('active'));
                pdpTabPanels.forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                const panel = document.getElementById('wcb-pdp-tab-' + targetTab);
                if (panel) panel.classList.add('active');
            });
        });
    }

    // v1 fallback
    const tabBtns = document.querySelectorAll('.wcb-tab-btn');
    const tabPanels = document.querySelectorAll('.wcb-tab-panel');

    if (tabBtns.length > 0 && pdpTabBtns.length === 0) {
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.dataset.tab;
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanels.forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                const panel = document.getElementById('wcb-tab-' + targetTab);
                if (panel) panel.classList.add('active');
            });
        });
    }

    /* ============================================================
       PDP v2 — COUNTDOWN 2h (Barra de Oferta, sessionStorage)
       Escopo global (ex.: categoria Ofertas Relâmpago) vs por produto — data-offer-timer-scope.
       ============================================================ */
    const pdpCountdown = document.getElementById('wcb-pdp-countdown');
    /* Legado: só com sessionStorage se não for timer de campanha (data-wcb-timer + data-end). */
    if (pdpCountdown && !pdpCountdown.hasAttribute('data-wcb-timer') && pdpCountdown.getAttribute('data-wcb-pdp-offer-legacy') === '1') {
        const offerBar = pdpCountdown.closest('.wcb-pdp-offer-bar--buybox');
        const productId = (offerBar && offerBar.dataset.productId) ? String(offerBar.dataset.productId) : '0';
        const durationSec = Math.max(60, parseInt(offerBar && offerBar.dataset.offerDurationSec, 10) || 7200);
        const timerScope = (offerBar && offerBar.dataset.offerTimerScope === 'global') ? 'global' : 'product';
        const storageKey = timerScope === 'global'
            ? 'wcbPdpOfferEnd_global'
            : ('wcbPdpOfferEnd_' + productId);

        function formatHms(totalSec) {
            const h = String(Math.floor(totalSec / 3600)).padStart(2, '0');
            const m = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
            const s = String(totalSec % 60).padStart(2, '0');
            return h + ':' + m + ':' + s;
        }

        let endMs = null;
        try {
            const raw = sessionStorage.getItem(storageKey);
            if (raw) {
                const parsed = parseInt(raw, 10);
                if (Number.isFinite(parsed) && parsed > Date.now()) {
                    endMs = parsed;
                }
            }
        } catch (e) { /* storage indisponível */ }

        if (endMs === null) {
            endMs = Date.now() + durationSec * 1000;
            try {
                sessionStorage.setItem(storageKey, String(endMs));
            } catch (e) { /* modo privado etc. */ }
        }

        function tickPdpOffer() {
            const totalSec = Math.max(0, Math.floor((endMs - Date.now()) / 1000));
            pdpCountdown.innerText = formatHms(totalSec);
            if (totalSec <= 0) {
                clearInterval(pdpOfferTimer);
            }
        }

        tickPdpOffer();
        const pdpOfferTimer = setInterval(tickPdpOffer, 1000);
    }

    /* ============================================================
       PDP v2 — DYNAMIC PRICE UPDATE ON VARIATION CHANGE
       ============================================================ */
    (function () {
        const priceBlock = document.getElementById('wcb-pdp-price-block');
        if (!priceBlock) return;

        const elPriceCurrent = document.getElementById('wcb-pdp-price-current');
        const elPriceOld = document.getElementById('wcb-pdp-price-old');
        const elDiscount = document.getElementById('wcb-pdp-discount');
        const elPixValue = document.getElementById('wcb-pdp-pix-value');
        const elPixWrap = document.getElementById('wcb-pdp-pix');
        const elEconomizePix = document.getElementById('wcb-pdp-economize-pix');
        const elStickyPrice = document.querySelector('.wcb-pdp-sticky__price');

        const basePrice = parseFloat(priceBlock.dataset.basePrice) || 0;
        const baseRegular = parseFloat(priceBlock.dataset.baseRegular) || 0;

        function formatBRL(val) {
            return val.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function updatePrices(displayPrice, regularPrice) {
            const price = parseFloat(displayPrice) || 0;
            const regular = parseFloat(regularPrice) || 0;

            // Preço principal
            if (elPriceCurrent) {
                elPriceCurrent.textContent = 'R$ ' + formatBRL(price);
            }

            // Preço antigo (riscado) + badge de desconto
            if (regular > 0 && regular > price) {
                const pct = Math.round(((regular - price) / regular) * 100);
                if (elPriceOld) {
                    elPriceOld.textContent = 'De R$ ' + formatBRL(regular);
                    elPriceOld.style.display = '';
                }
                if (elDiscount) {
                    elDiscount.textContent = '−' + pct + '% OFF';
                    elDiscount.style.display = '';
                }
            } else {
                if (elPriceOld) elPriceOld.style.display = 'none';
                if (elDiscount) elDiscount.style.display = 'none';
            }

            // PIX (5% desc)
            if (price > 0) {
                const pixVal = price * 0.95;
                const economize = price - pixVal;
                if (elPixValue) elPixValue.textContent = 'R$ ' + formatBRL(pixVal);
                if (elPixWrap) elPixWrap.style.display = '';
                if (elEconomizePix) {
                    elEconomizePix.textContent =
                        'Economia de R$ ' + formatBRL(economize) + ' no pagamento à vista';
                    elEconomizePix.style.display = '';
                }
            } else {
                if (elPixWrap) elPixWrap.style.display = 'none';
                if (elEconomizePix) elEconomizePix.style.display = 'none';
            }

            // Sticky bar — destaque no PIX
            if (elStickyPrice) {
                if (price <= 0) {
                    elStickyPrice.innerHTML = '<span class="wcb-pdp-sticky__card-line">—</span>';
                } else {
                    const pixVal = price * 0.95;
                    let cardLine = '';
                    if (regular > 0 && regular > price) {
                        cardLine += '<del>R$ ' + formatBRL(regular) + '</del> ';
                    }
                    cardLine += 'ou R$ ' + formatBRL(price) + ' em até 12x no cartão';
                    elStickyPrice.innerHTML =
                        '<span class="wcb-pdp-sticky__pix-line"><strong>R$ ' + formatBRL(pixVal) +
                        '</strong> <span class="wcb-pdp-sticky__pix-note">no PIX</span></span>' +
                        '<span class="wcb-pdp-sticky__card-line">' + cardLine + '</span>';
                }
            }
        }

        // WooCommerce dispara esses eventos via jQuery
        if (typeof jQuery !== 'undefined') {
            jQuery('.variations_form')
                .on('show_variation', function (e, variation) {
                    // variation.display_price = preço de venda da variação
                    // variation.display_regular_price = preço regular da variação
                    updatePrices(variation.display_price, variation.display_regular_price);
                })
                .on('hide_variation', function () {
                    // Volta para os preços base do produto
                    updatePrices(basePrice, baseRegular);
                });
        }
    })();


    /* ============================================================
       PDP v2 — SCROLL TO REVIEWS
       ============================================================ */
    const scrollToReviewsLink = document.getElementById('wcb-scroll-to-reviews');
    if (scrollToReviewsLink) {
        scrollToReviewsLink.addEventListener('click', (e) => {
            e.preventDefault();
            // Ativar a aba de reviews
            const reviewsBtn = document.getElementById('wcb-pdp-btn-reviews');
            if (reviewsBtn) reviewsBtn.click();
            // Scroll
            setTimeout(() => {
                const reviewsPanel = document.getElementById('wcb-pdp-tab-reviews');
                if (reviewsPanel) reviewsPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        });
    }

    /* ============================================================
       PDP v2 — TOGGLE REVIEW FORM (sem avaliações: form começa oculto)
       ============================================================ */
    const toggleReviewBtn = document.getElementById('wcb-pdp-toggle-review');
    const reviewFormWrap = document.getElementById('wcb-pdp-review-form');
    const reviewsTabPanel = document.getElementById('wcb-pdp-tab-reviews');
    const reviewFormStartsHidden =
        reviewsTabPanel &&
        reviewsTabPanel.getAttribute('data-wcb-pdp-review-form-start-hidden') === '1';
    if (toggleReviewBtn && reviewFormWrap) {
        toggleReviewBtn.addEventListener('click', () => {
            if (reviewFormStartsHidden) {
                if (reviewFormWrap.hasAttribute('hidden')) {
                    reviewFormWrap.removeAttribute('hidden');
                    toggleReviewBtn.setAttribute('aria-expanded', 'true');
                } else {
                    reviewFormWrap.setAttribute('hidden', '');
                    toggleReviewBtn.setAttribute('aria-expanded', 'false');
                    toggleReviewBtn.focus();
                    return;
                }
            } else {
                toggleReviewBtn.setAttribute('aria-expanded', 'true');
            }
            reviewFormWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => {
                const textarea = reviewFormWrap.querySelector('textarea');
                if (textarea) textarea.focus();
            }, 400);
        });
    }

    /* ============================================================
       PDP v2 — QUANTITY BUTTONS (robust, sem duplicação)
       ============================================================ */
    function injectPdpQtyBtns() {
        const qtyWraps = document.querySelectorAll('.wcb-pdp-buybox__form .quantity');
        qtyWraps.forEach(wrap => {
            // Remover TODOS os botões existentes (tanto nossos quanto do footer)
            wrap.querySelectorAll('.wcb-qty-btn, button').forEach(b => {
                if (b.type === 'submit') return; // não remover botão submit
                b.remove();
            });
            // Marcar como processado (impede o footer.php de processar)
            wrap.classList.add('wcb-pdp-qty-ready');
            wrap.classList.add('buttons_added');

            const input = wrap.querySelector('input.qty');
            if (!input) return;

            // Forçar tipo text para evitar spinners nativos do browser
            input.setAttribute('type', 'text');
            input.setAttribute('inputmode', 'numeric');
            input.setAttribute('pattern', '[0-9]*');

            // Criar botões com handler direto
            const minusBtn = document.createElement('button');
            minusBtn.type = 'button';
            minusBtn.className = 'wcb-qty-btn wcb-minus';
            minusBtn.setAttribute('aria-label', 'Diminuir');
            minusBtn.textContent = '−';

            const plusBtn = document.createElement('button');
            plusBtn.type = 'button';
            plusBtn.className = 'wcb-qty-btn wcb-plus';
            plusBtn.setAttribute('aria-label', 'Aumentar');
            plusBtn.textContent = '+';

            // Handler direto (não depende de delegation)
            function handleQty(direction) {
                let val = parseFloat(input.value) || 1;
                const step = parseFloat(input.getAttribute('step')) || 1;
                const min = parseFloat(input.getAttribute('min')) || 1;
                const max = parseFloat(input.getAttribute('max')) || 9999;

                if (direction === 'plus' && val < max) {
                    input.value = val + step;
                } else if (direction === 'minus' && val > min) {
                    input.value = val - step;
                }
                // Disparar evento change para WooCommerce atualizar
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }

            minusBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                handleQty('minus');
            });

            plusBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                handleQty('plus');
            });

            // Inserir: [−] [input] [+]
            wrap.insertBefore(minusBtn, input);
            wrap.appendChild(plusBtn);
        });
    }
    injectPdpQtyBtns();
    // Re-injetar após AJAX de variações
    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('updated_wc_div wc_variation_form show_variation', function() {
            setTimeout(injectPdpQtyBtns, 80);
        });
    }



    /* ============================================================
       FAB CART COUNT UPDATE
       ============================================================ */
    const fabCartCount = document.querySelector('.wcb-fab-cart__count');
    const headerCartCount = document.querySelector('.wcb-header__cart-count');

    if (fabCartCount && headerCartCount) {
        const syncFabCount = () => {
            fabCartCount.textContent = headerCartCount.textContent;
        };
        syncFabCount();

        // Watch for cart count changes (WooCommerce ajax fragments)
        if (typeof jQuery !== 'undefined') {
            jQuery(document.body).on('wc_fragments_refreshed added_to_cart', function () {
                setTimeout(syncFabCount, 100);
            });
        }
    }

})();

/* ============================================================
   LIVE SEARCH AUTOCOMPLETE
   ============================================================ */
(function () {
    'use strict';

    // Support both id and class selectors — WordPress may cache the form without the id
    const searchInput = document.getElementById('wcb-search-input')
        || document.querySelector('.wcb-header__search-input');
    if (!searchInput) return;

    // Ensure the id is set for future reference
    if (!searchInput.id) searchInput.id = 'wcb-search-input';

    const ajaxUrl = (window.wcbData && window.wcbData.ajaxUrl) || '/wp-admin/admin-ajax.php';
    const pubNonce = (window.wcbData && window.wcbData.publicAjaxNonce)
        ? encodeURIComponent(window.wcbData.publicAjaxNonce) : '';
    const form = searchInput.closest('form');

    // --- Mount dropdown on BODY to escape overflow:hidden parents ---
    const dropdown = document.createElement('div');
    dropdown.id = 'wcb-live-search-dropdown';
    dropdown.className = 'wcb-search-dropdown';
    dropdown.setAttribute('role', 'listbox');
    dropdown.setAttribute('aria-label', 'Resultados da busca');
    document.body.appendChild(dropdown);

    let debounceTimer = null;
    let activeIndex = -1;
    let currentResults = [];

    // --- Position the body-mounted dropdown below the search form ---
    function positionDropdown() {
        const rect = form.getBoundingClientRect();
        dropdown.style.position = 'fixed';
        dropdown.style.top = (rect.bottom + 6) + 'px';
        dropdown.style.left = rect.left + 'px';
        dropdown.style.width = rect.width + 'px';
        dropdown.style.zIndex = '99999';
    }

    // --- Helpers ---
    function highlightTerm(text, term) {
        if (!term) return text;
        const escaped = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return text.replace(new RegExp('(' + escaped + ')', 'gi'), '<mark>$1</mark>');
    }

    function showDropdown(html) {
        positionDropdown();
        dropdown.innerHTML = html;
        dropdown.classList.add('visible');
        activeIndex = -1;
    }

    function hideDropdown() {
        dropdown.classList.remove('visible');
        activeIndex = -1;
    }

    function renderSkeleton() {
        const rows = Array.from({ length: 3 }, () =>
            `<div class="wcb-search-item wcb-search-item--skeleton">
                <div class="wcb-search-item__thumb wcb-skeleton"></div>
                <div class="wcb-search-item__info">
                    <div class="wcb-skeleton wcb-skeleton--line" style="width:70%"></div>
                    <div class="wcb-skeleton wcb-skeleton--line" style="width:40%;margin-top:6px"></div>
                </div>
            </div>`
        ).join('');
        showDropdown(rows);
    }

    function renderResults(items, query) {
        if (!items.length) {
            showDropdown(
                `<div class="wcb-search-empty">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <span>Nenhum produto encontrado para <strong>"${query}"</strong></span>
                </div>`
            );
            return;
        }

        // ── All results kept for filter logic ────────────────────────────────
        currentResults = items;
        let activeFilter = null; // { type: 'all'|'promo'|'flash'|'new' }

        function applyFilter(allItems, filter) {
            if (!filter || filter.type === 'all') return allItems;
            return allItems.filter(item => {
                if (filter.type === 'promo') return !!item.price_old;
                if (filter.type === 'flash') return !!item.is_flash_offer;
                if (filter.type === 'new') return !!item.is_new;
                return true;
            });
        }

        function stars(rating) {
            if (!rating) return '';
            let s = '';
            for (let i = 1; i <= 5; i++) {
                s += `<svg class="wcb-star${i <= Math.round(rating) ? ' wcb-star--on' : ''}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>`;
            }
            return s;
        }

        function buildCard(item, i) {
            return `
            <a href="${item.url}" class="wcb-search-item" role="option" data-index="${i}">
                <div class="wcb-search-item__img-wrap">
                    <img class="wcb-search-item__thumb" src="${item.thumb}" alt="${item.title}" loading="lazy">
                    ${!item.in_stock ? '<span class="wcb-search-item__no-stock">Esgotado</span>' : ''}
                </div>
                <div class="wcb-search-item__info">
                    <div class="wcb-search-item__badges">
                        ${item.is_bestseller ? '<span class="wcb-badge wcb-badge--best">🏆 Mais vendido</span>' : ''}
                        ${item.is_trending && !item.is_bestseller ? '<span class="wcb-badge wcb-badge--trend">🔥 Em alta</span>' : ''}
                    </div>
                    <div class="wcb-search-item__name">${highlightTerm(item.title, query)}</div>
                    <div class="wcb-search-item__meta">
                        ${item.category ? `<span class="wcb-search-item__cat">${item.category}</span>` : ''}
                        ${item.volume ? `<span class="wcb-search-item__dot">·</span><span class="wcb-search-item__vol">${item.volume}</span>` : ''}
                        ${item.nic_type ? `<span class="wcb-search-item__dot">·</span><span class="wcb-search-item__nic">${item.nic_type}</span>` : ''}
                        ${item.brand ? `<span class="wcb-search-item__dot">·</span><span class="wcb-search-item__brand">${item.brand}</span>` : ''}
                    </div>
                    ${item.rating ? `
                    <div class="wcb-search-item__rating">
                        <span class="wcb-stars">${stars(item.rating)}</span>
                        <span class="wcb-search-item__rating-val">${item.rating}</span>
                        ${item.rating_count ? `<span class="wcb-search-item__rating-count">(${item.rating_count})</span>` : ''}
                    </div>` : ''}
                </div>
                <div class="wcb-search-item__right">
                    <div class="wcb-search-item__price">
                        ${item.price_old ? `<span class="wcb-search-item__price-old">R$ ${item.price_old}</span>` : ''}
                        <div class="wcb-search-item__price-row">
                            <span class="wcb-search-item__price-current">R$ ${item.price}</span>
                            ${item.discount_pct ? `<span class="wcb-search-item__discount">-${item.discount_pct}%</span>` : ''}
                        </div>
                    </div>
                    <div class="wcb-search-item__stock ${item.in_stock ? 'wcb-search-item__stock--in' : 'wcb-search-item__stock--out'}">
                        ${item.in_stock ? '● Em estoque' : '● Indisponível'}
                    </div>
                </div>
            </a>`;
        }

        function buildFilterBar(allItems, currentFilter) {
            const hasPromo = allItems.some(i => !!i.price_old);
            const hasFlash = allItems.some(i => !!i.is_flash_offer);
            const hasNew = allItems.some(i => !!i.is_new);

            // Don't show the bar at all if no optional filters apply
            if (!hasPromo && !hasFlash && !hasNew) return '';

            function chip(label, type, emoji) {
                const isActive = type === 'all'
                    ? !currentFilter
                    : (currentFilter && currentFilter.type === type);
                return `<button type="button" class="wcb-filter-chip${isActive ? ' wcb-filter-chip--active' : ''}" data-filter-type="${type}" data-filter-value="">${emoji ? emoji + ' ' : ''}${label}</button>`;
            }

            let chips = chip('Todos', 'all', '');
            if (hasPromo) chips += chip('Promoção', 'promo', '🏷️');
            if (hasFlash) chips += chip('Ofertas relâmpago', 'flash', '⚡');
            if (hasNew) chips += chip('Novidades', 'new', '✨');

            chips += `<button type="button" class="wcb-filter-chip wcb-filter-chip--clear" data-filter-type="clear" data-filter-value="">Limpar</button>`;

            return `<div class="wcb-search-filters">${chips}</div>`;
        }

        function render(allItems, filter) {
            const filtered = applyFilter(allItems, filter);
            const bar = buildFilterBar(allItems, filter);
            const cards = filtered.map((item, i) => buildCard(item, i)).join('');
            const noRes = filtered.length === 0
                ? `<div class="wcb-search-empty" style="padding:1rem"><span>Sem resultados para este filtro</span></div>`
                : '';
            const seeAll = `
                <a href="/?s=${encodeURIComponent(query)}&post_type=product" class="wcb-search-footer">
                    Ver todos os resultados para <strong>"${query}"</strong>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>`;

            showDropdown(bar + cards + noRes + seeAll);

            // Bind filter chip clicks
            dropdown.querySelectorAll('.wcb-filter-chip').forEach(btn => {
                btn.addEventListener('mousedown', e => {
                    e.preventDefault();
                    const type = btn.dataset.filterType;
                    if (type === 'clear' || type === 'all') {
                        activeFilter = null;
                    } else if (activeFilter && activeFilter.type === type) {
                        activeFilter = null; // toggle off same filter
                    } else {
                        activeFilter = { type };
                    }
                    render(allItems, activeFilter);
                });
            });
        }

        render(items, null);
    }

    // --- Keyboard navigation ---
    function updateKeyboardFocus(items) {
        items.forEach((item, i) => {
            item.classList.toggle('wcb-search-item--focused', i === activeIndex);
        });
    }

    searchInput.addEventListener('keydown', (e) => {
        const items = dropdown.querySelectorAll('.wcb-search-item:not(.wcb-search-item--skeleton)');
        if (!dropdown.classList.contains('visible') || !items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            updateKeyboardFocus(items);
            if (items[activeIndex]) items[activeIndex].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, -1);
            updateKeyboardFocus(items);
        } else if (e.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
            e.preventDefault();
            items[activeIndex].click();
        } else if (e.key === 'Escape') {
            hideDropdown();
        }
    });

    // --- Main input handler ---
    searchInput.addEventListener('input', () => {
        const q = searchInput.value.trim();

        clearTimeout(debounceTimer);

        if (q.length < 2) {
            hideDropdown();
            return;
        }

        renderSkeleton();

        debounceTimer = setTimeout(() => {
            fetch(`${ajaxUrl}?action=wcb_live_search&q=${encodeURIComponent(q)}&nonce=${pubNonce}`)
                .then(r => r.json())
                .then(data => {
                    if (!Array.isArray(data)) {
                        hideDropdown();
                        return;
                    }
                    renderResults(data, q);
                })
                .catch(() => hideDropdown());
        }, 300);
    });

    // --- Reposition on scroll/resize (header is sticky); rAF coalescido evita reflow em rajada ---
    var wcbLiveSearchPosTicking = false;
    function schedulePositionDropdown() {
        if (!dropdown.classList.contains('visible')) return;
        if (wcbLiveSearchPosTicking) return;
        wcbLiveSearchPosTicking = true;
        window.requestAnimationFrame(function () {
            wcbLiveSearchPosTicking = false;
            if (dropdown.classList.contains('visible')) positionDropdown();
        });
    }
    window.addEventListener('scroll', schedulePositionDropdown, { passive: true });
    window.addEventListener('resize', schedulePositionDropdown, { passive: true });

    // --- Close on outside click ---
    document.addEventListener('click', (e) => {
        if (!form.contains(e.target) && !dropdown.contains(e.target)) hideDropdown();
    });

    // --- Show dropdown on focus if query exists ---
    searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim().length >= 2 && currentResults.length) {
            showDropdown(dropdown.innerHTML); // Re-show with current content
        }
    });

})();


/* ============================================================
   HERO BANNER SLIDER — só dentro de #wcb-hero (evita slides órfãos sem .active → faixa colapsa).
   ============================================================ */
(function () {
    const hero = document.getElementById('wcb-hero');
    if (!hero) return;

    const slides = Array.from(hero.querySelectorAll('.wcb-hero__slide'));
    const dots = Array.from(hero.querySelectorAll('.wcb-hero__dot'));
    const btnPrev = document.getElementById('hero-prev');
    const btnNext = document.getElementById('hero-next');

    if (!slides.length) return;

    let current = slides.findIndex(s => s.classList.contains('active'));
    if (current < 0) current = 0;

    let timer = null;
    const DELAY = 5000;

    function ensureOneActiveSlide() {
        const actives = hero.querySelectorAll('.wcb-hero__slide.active');
        if (actives.length === 0 && slides[0]) {
            slides.forEach(s => s.classList.remove('active'));
            slides[0].classList.add('active');
            current = 0;
            dots.forEach(d => d.classList.remove('active'));
            if (dots[0]) dots[0].classList.add('active');
        }
    }

    function goTo(idx) {
        if (!slides.length) return;
        const next = (idx + slides.length) % slides.length;
        const curEl = slides[current];
        const nextEl = slides[next];
        if (!curEl || !nextEl) {
            ensureOneActiveSlide();
            return;
        }
        curEl.classList.remove('active');
        if (dots[current]) dots[current].classList.remove('active');
        current = next;
        nextEl.classList.add('active');
        if (dots[current]) dots[current].classList.add('active');
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    function startTimer() {
        stopTimer();
        timer = setInterval(function () {
            try {
                next();
            } catch (e) {
                ensureOneActiveSlide();
            }
        }, DELAY);
    }

    function stopTimer() {
        if (timer) { clearInterval(timer); timer = null; }
    }

    ensureOneActiveSlide();

    if (btnNext) btnNext.addEventListener('click', function () { next(); startTimer(); });
    if (btnPrev) btnPrev.addEventListener('click', function () { prev(); startTimer(); });

    dots.forEach(function (dot, i) {
        dot.addEventListener('click', function () { goTo(i); startTimer(); });
    });

    hero.addEventListener('mouseenter', stopTimer);
    hero.addEventListener('mouseleave', startTimer);
    hero.addEventListener('touchstart', stopTimer, { passive: true });
    hero.addEventListener('touchend', startTimer, { passive: true });

    var touchX = 0;
    hero.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
    hero.addEventListener('touchend', function (e) {
        var dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 40) { dx < 0 ? next() : prev(); startTimer(); }
    }, { passive: true });

    startTimer();
}());


/* ============================================================
   REVIEWS — Toggle form & bar animation
   ============================================================ */
(function () {
    const toggleBtn = document.getElementById('wcb-toggle-form');
    const formWrap = document.getElementById('wcb-review-form-wrap');

    if (toggleBtn && formWrap) {
        toggleBtn.addEventListener('click', () => {
            const isOpen = formWrap.classList.toggle('open');
            toggleBtn.textContent = isOpen ? '✕ Fechar formulário' : 'Escrever avaliação';
            if (isOpen) {
                setTimeout(() => {
                    formWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 120);
            }
        });
    }

    // Animate bar fills on scroll into view
    const bars = document.querySelectorAll('.wcb-reviews__bar-fill');
    if (bars.length && 'IntersectionObserver' in window) {
        const widths = Array.from(bars).map(b => b.style.width);
        bars.forEach(b => { b.style.width = '0'; });

        const obs = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const i = Array.from(bars).indexOf(entry.target);
                    entry.target.style.width = widths[i];
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        bars.forEach(b => obs.observe(b));
    }
}());


/* ============================================================
   ANNOUNCEMENT BAR — auto-cycle & close
   ============================================================ */
(function () {
    const bar = document.getElementById('wcb-announce');
    const slides = bar && bar.querySelectorAll('.wcb-announce__slide');
    const closeBtn = document.getElementById('wcb-announce-close');
    if (!bar || !slides.length) return;

    let current = 0;

    function nextSlide() {
        slides[current].classList.remove('active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('active');
    }

    const cycleInterval = setInterval(nextSlide, 4000);

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            clearInterval(cycleInterval);
            bar.style.maxHeight = bar.offsetHeight + 'px';
            requestAnimationFrame(() => {
                bar.style.transition = 'max-height 0.35s ease, opacity 0.35s ease, padding 0.35s ease';
                bar.style.maxHeight = '0';
                bar.style.opacity = '0';
                bar.style.padding = '0';
                bar.style.overflow = 'hidden';
            });
            setTimeout(() => bar.remove(), 380);
        });
    }
}());


/* ============================================================
   SCROLL FADE-IN — IntersectionObserver
   ============================================================ */
(function () {
    function revealAllHomeSections() {
        document.querySelectorAll('.wcb-section, .wcb-promo-banners').forEach(function (el) {
            el.classList.add('wcb-visible');
        });
    }

    if (!('IntersectionObserver' in window)) {
        revealAllHomeSections();
        return;
    }

    var mq = window.matchMedia ? window.matchMedia('(max-width: 1023px)') : null;

    if (mq && mq.matches) {
        revealAllHomeSections();
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('wcb-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.wcb-section, .wcb-promo-banners').forEach(function (el) {
        observer.observe(el);
    });

    /* Redimensionar para estreito depois do load: o IO não volta a correr → revelar tudo. */
    if (mq) {
        var onMq = function () {
            if (!mq.matches) return;
            observer.disconnect();
            revealAllHomeSections();
        };
        if (mq.addEventListener) {
            mq.addEventListener('change', onMq);
        } else if (mq.addListener) {
            mq.addListener(onMq);
        }
    }
}());


/* ============================================================
   MINI-CART FLYOUT — Integração unificada
   O botão do header abre o Modern Cart (plugin) se disponível,
   caso contrário usa o mini-cart customizado do tema.
   ============================================================ */
(function () {
    'use strict';

    const trigger = document.getElementById('wcb-mini-cart-trigger');
    if (!trigger) return;

    // ── Delegate para Modern Cart se o plugin estiver ativo ──────────────
    function tryOpenModernCart() {
        // Modern Cart usa um botão flutuante como trigger para o drawer
        const mcTrigger = document.querySelector('[data-moderncart-trigger], .moderncart-floating-btn, #moderncart-floating-cart, .moderncart-open-btn');
        if (mcTrigger) {
            mcTrigger.click();
            return true;
        }
        // Algumas versões do plugin expõem uma função global
        if (typeof window.ModernCart !== 'undefined' && typeof window.ModernCart.open === 'function') {
            window.ModernCart.open();
            return true;
        }
        // Tenta abrir pelo drawer diretamente (class toggle)
        const mcDrawer = document.getElementById('moderncart-slide-out') || document.getElementById('moderncart-slide-out-modal');
        if (mcDrawer) {
            mcDrawer.classList.add('active', 'open', 'is-open');
            return true;
        }
        return false;
    }

    trigger.addEventListener('click', function (e) {
        /* Xoo Side Cart: o plugin trata o clique (não interceptar). */
        if (trigger.classList.contains('xoo-wsc-cart-trigger')) {
            return;
        }
        /* Link para a página do carrinho (carrinho lateral desativado). */
        if (trigger.tagName === 'A' && trigger.getAttribute('href')) {
            return;
        }
        if (tryOpenModernCart()) {
            e.preventDefault();
            return;
        }
        // Fallback: abre o mini-cart customizado do tema
        const drawer  = document.getElementById('wcb-mini-cart');
        const overlay = document.getElementById('wcb-mini-cart-overlay');
        const footer  = document.getElementById('wcb-mini-cart-footer');
        const body    = document.getElementById('wcb-mini-cart-body');
        if (!drawer) return;

        drawer.classList.add('is-open');
        if (overlay) overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        trigger.setAttribute('aria-expanded', 'true');
        drawer.setAttribute('aria-hidden', 'false');

        // Carrega itens via AJAX na primeira abertura
        if (!drawer.dataset.loaded) {
            const ajaxUrl = (window.wcbData && window.wcbData.ajaxUrl) || '/wp-admin/admin-ajax.php';
            if (body) body.innerHTML = '<div class="wcb-mini-cart__loading"><div class="wcb-mini-cart__spinner"></div><span>Carregando...</span></div>';
            if (footer) footer.style.display = 'none';
            var mcNonce = (window.wcbData && window.wcbData.miniCartNonce) ? '&nonce=' + encodeURIComponent(window.wcbData.miniCartNonce) : '';
            fetch(ajaxUrl + '?action=wcb_mini_cart' + mcNonce, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (body) body.innerHTML = data.html || '';
                    const countEl = document.getElementById('wcb-mini-cart-count');
                    if (countEl) countEl.textContent = data.count || 0;
                    if (footer && data.count) {
                        footer.style.display = 'flex';
                        const sub = document.getElementById('wcb-mini-cart-subtotal');
                        if (sub) sub.textContent = data.subtotal || 'R$ 0,00';
                    }
                    drawer.dataset.loaded = '1';
                }).catch(() => {});
        }
    });

    // Fecha o mini-cart custom (fallback)
    const closeBtn = document.getElementById('wcb-mini-cart-close');
    const overlay  = document.getElementById('wcb-mini-cart-overlay');
    const drawer   = document.getElementById('wcb-mini-cart');

    function closeCart() {
        if (!drawer) return;
        drawer.classList.remove('is-open');
        if (overlay) overlay.classList.remove('is-open');
        document.body.style.overflow = '';
        trigger.setAttribute('aria-expanded', 'false');
        drawer.setAttribute('aria-hidden', 'true');
    }

    if (closeBtn) closeBtn.addEventListener('click', closeCart);
    if (overlay)  overlay.addEventListener('click', closeCart);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && drawer && drawer.classList.contains('is-open')) closeCart();
    });

    // Auto-abre o Modern Cart ao adicionar produto ao carrinho
    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('added_to_cart', function () {
            setTimeout(() => { tryOpenModernCart(); }, 200);
        });
    }
}());



/* ============================================================
   ABANDONED CART — Captura de email no checkout
   ============================================================ */
(function () {
    'use strict';

    // Só roda em páginas de checkout
    if (!document.body.classList.contains('woocommerce-checkout') &&
        !document.body.classList.contains('cartflows-checkout')) return;

    var abCartData = window.wcbAbCart || {};
    var ajaxUrl    = abCartData.ajaxUrl || '/wp-admin/admin-ajax.php';
    var saveTimer  = null;
    var lastEmail  = '';

    // Seletores para checkout classic e blocks
    var EMAIL_SELECTORS = [
        '#billing_email',   // Classic WooCommerce
        '#billing-email',   // WooCommerce Blocks
        '#email',           // CartFlows
    ];

    var FIRST_SELECTORS = ['#billing_first_name', '#billing-first_name', '#first-name'];
    var LAST_SELECTORS  = ['#billing_last_name',  '#billing-last_name',  '#last-name'];

    function getFieldValue(selectors) {
        for (var i = 0; i < selectors.length; i++) {
            var el = document.querySelector(selectors[i]);
            if (el && el.value) return el.value.trim();
        }
        return '';
    }

    function saveAbandonedCart(email) {
        if (!email || email === lastEmail) return;
        lastEmail = email;

        var fd = new FormData();
        fd.append('action',     'wcb_save_abandoned_cart');
        fd.append('email',      email);
        fd.append('first_name', getFieldValue(FIRST_SELECTORS));
        fd.append('last_name',  getFieldValue(LAST_SELECTORS));
        // nonce injetado pelo PHP via wcbAbCart
        if (abCartData.nonce) fd.append('nonce', abCartData.nonce);

        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
    }

    function initEmailField(el) {
        if (!el || el.dataset.wcbAbCart) return;
        el.dataset.wcbAbCart = '1';

        el.addEventListener('blur', function () {
            var email = this.value.trim();
            if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                saveAbandonedCart(email);
            }
        });

        el.addEventListener('input', function () {
            var self = this;
            clearTimeout(saveTimer);
            saveTimer = setTimeout(function () {
                var email = self.value.trim();
                if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    saveAbandonedCart(email);
                }
            }, 2500); // Debounce de 2.5s para não disparar a cada tecla
        });
    }

    function bootstrap() {
        EMAIL_SELECTORS.forEach(function (sel) {
            var el = document.querySelector(sel);
            if (el) initEmailField(el);
        });
    }

    // MutationObserver para WooCommerce Blocks (renderiza depois do DOMContentLoaded)
    var obs = new MutationObserver(bootstrap);
    obs.observe(document.body, { childList: true, subtree: true });

    document.addEventListener('DOMContentLoaded', function () {
        bootstrap();
        setTimeout(function () { obs.disconnect(); }, 20000);
    });

    // Também tenta capturar no beforeunload se campo preenchido mas não salvo
    window.addEventListener('beforeunload', function () {
        var email = getFieldValue(EMAIL_SELECTORS);
        if (email && email !== lastEmail && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            saveAbandonedCart(email);
        }
    });
}());


/* ============================================================
   QUICK VIEW MODAL — Premium v2
   Supports: gallery carousel, variation swatches, AJAX add-to-cart
   ============================================================ */
(function () {
    'use strict';

    const ajaxUrl = (window.wcbData && window.wcbData.ajaxUrl) || window.ajaxurl || '/wp-admin/admin-ajax.php';
    const pubNonce = (window.wcbData && window.wcbData.publicAjaxNonce)
        ? encodeURIComponent(window.wcbData.publicAjaxNonce) : '';

    const overlay = document.getElementById('wcb-qv-overlay');
    const modal   = overlay && overlay.querySelector('.wcb-qv-modal');
    const closeBtn = document.getElementById('wcb-qv-close');
    const loading  = document.getElementById('wcb-qv-loading');
    const content  = document.getElementById('wcb-qv-content');

    if (!overlay) return;

    // State for current product
    let currentProduct = null;
    let selectedAttributes = {};

    /* ── Open / Close helpers ──────────────────────────────── */
    function openModal() {
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        currentProduct = null;
        selectedAttributes = {};
        setTimeout(() => {
            if (content) {
                content.innerHTML = '';
                content.classList.remove('is-visible');
                content.style.removeProperty('display');
            }
            if (loading) loading.classList.remove('is-hidden');
        }, 300);
    }

    /* ── Rating stars helper ───────────────────────────────── */
    function buildStars(rating) {
        if (!rating) return '';
        let s = '';
        for (let i = 1; i <= 5; i++) {
            s += `<svg class="wcb-qv-star${i <= Math.round(rating) ? ' on' : ''}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>`;
        }
        return s;
    }

    /* ── Find matching variation ───────────────────────────── */
    function findMatchingVariation(p, selected) {
        if (!p.variations || !p.variations.length) return null;

        const attrKeys = Object.keys(selected);
        if (attrKeys.length === 0) return null;

        // Check if all attributes are selected
        const allAttrsCount = p.variation_attributes ? p.variation_attributes.length : 0;
        if (attrKeys.length < allAttrsCount) return null;

        return p.variations.find(v => {
            return attrKeys.every(key => {
                const attrKey = `attribute_${key}`;
                const varValue = v.attributes[attrKey] || '';
                // Empty means "any" in WooCommerce
                if (varValue === '') return true;
                return varValue === selected[key];
            });
        }) || null;
    }

    /* ── Update price block when variation changes ─────────── */
    function updatePriceBlock(p, variation) {
        const priceBlock = content.querySelector('.wcb-qv-price-block');
        if (!priceBlock) return;

        const data = variation || p;
        const oldPrice = (variation && variation.is_on_sale && variation.regular_price) ? variation.regular_price : (p.is_on_sale && p.regular_price ? p.regular_price : '');

        priceBlock.innerHTML = `
            ${oldPrice ? `<span class="wcb-qv-price-old">${oldPrice}</span>` : ''}
            <span class="wcb-qv-price-current">${data.current_price}</span>
            ${data.pix_price ? `<div class="wcb-qv-pix">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                <strong>${data.pix_price}</strong> <span>no PIX</span>
            </div>` : ''}
            ${data.installments ? `<div class="wcb-qv-installments">${data.installments}</div>` : ''}
        `;
    }

    /* ── Update add-to-cart button ─────────────────────────── */
    function updateAddButton(p, variation) {
        const actionsEl = content.querySelector('.wcb-qv-actions');
        const stockRow  = content.querySelector('#wcb-qv-stock-row');
        if (!actionsEl) return;

        const notice = content.querySelector('.wcb-qv-var-notice');
        const allSelected = p.variation_attributes
            ? p.variation_attributes.every(a => selectedAttributes[a.name])
            : false;

        let btnHtml = '';
        let stockHtml = '';
        let showQty = false;

        if (p.type === 'variable') {
            if (!allSelected) {
                btnHtml = `<button class="wcb-qv-add-btn wcb-qv-add-btn--disabled" disabled>
                    Selecione as opções
                </button>`;
                stockHtml = '';
                if (notice) notice.classList.remove('visible');
            } else if (variation && variation.is_in_stock) {
                showQty = true;
                btnHtml = `<button class="wcb-qv-add-btn ajax_add_to_cart"
                    data-product_id="${p.id}"
                    data-variation_id="${variation.variation_id}"
                    data-product_sku="${variation.sku || p.sku}"
                    data-quantity="1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    Adicionar ao carrinho
                </button>`;
                stockHtml = `<span class="wcb-qv-in-stock">● Em estoque</span>`;
                if (notice) notice.classList.remove('visible');
            } else if (variation && !variation.is_in_stock) {
                btnHtml = `<button class="wcb-qv-add-btn wcb-qv-add-btn--disabled" disabled>Indisponível</button>`;
                stockHtml = `<span class="wcb-qv-out-stock">● Fora de estoque</span>`;
                if (notice) notice.classList.remove('visible');
            } else {
                btnHtml = `<button class="wcb-qv-add-btn wcb-qv-add-btn--disabled" disabled>Combinação indisponível</button>`;
                stockHtml = '';
                if (notice) {
                    notice.textContent = 'Essa combinação não está disponível.';
                    notice.classList.add('visible');
                }
            }
        } else {
            if (p.in_stock) {
                showQty = true;
                btnHtml = `<button class="wcb-qv-add-btn ajax_add_to_cart"
                    data-product_id="${p.id}"
                    data-product_sku="${p.sku}"
                    data-quantity="1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    Adicionar ao carrinho
                </button>`;
                stockHtml = `<span class="wcb-qv-in-stock">● Em estoque</span>`;
            } else {
                btnHtml = `<button class="wcb-qv-add-btn wcb-qv-add-btn--disabled" disabled>Indisponível</button>`;
                stockHtml = `<span class="wcb-qv-out-stock">● Fora de estoque</span>`;
            }
        }

        // Update stock row (below variants)
        if (stockRow) stockRow.innerHTML = stockHtml;

        // Build qty stepper + button
        const qtyHtml = showQty ? `<div class="wcb-qv-qty" id="wcb-qv-qty">
            <button class="wcb-qv-qty__btn wcb-qv-qty__minus" type="button" aria-label="Diminuir">−</button>
            <span class="wcb-qv-qty__val">1</span>
            <button class="wcb-qv-qty__btn wcb-qv-qty__plus" type="button" aria-label="Aumentar">+</button>
        </div>` : '';

        actionsEl.innerHTML = `<div class="wcb-qv-cart-row">${qtyHtml}${btnHtml}</div>`;
        if (showQty) bindQtyStepper();
        bindAddToCart();
    }

    /* ── Update image when variation has its own image ─────── */
    function updateMainImage(imageUrl) {
        const mainImg = content.querySelector('#wcb-qv-main-img');
        if (!mainImg || !imageUrl) return;
        mainImg.style.opacity = '0';
        mainImg.style.transform = 'scale(0.96)';
        setTimeout(() => {
            mainImg.src = imageUrl;
            mainImg.style.opacity = '1';
            mainImg.style.transform = 'scale(1)';
        }, 160);
    }

    /* ── Build variation swatches ──────────────────────────── */
    function buildVariationsHtml(p) {
        if (!p.variation_attributes || !p.variation_attributes.length) return '';

        let html = '<div class="wcb-qv-variations">';
        p.variation_attributes.forEach(attr => {
            const defaultVal = p.default_attributes[attr.name] || '';
            html += `<div class="wcb-qv-var-group" data-attribute="${attr.name}">
                <div class="wcb-qv-var-label">${attr.label}: <span class="wcb-qv-var-selected-label"></span></div>
                <div class="wcb-qv-var-options">`;
            attr.terms.forEach(term => {
                const isDefault = (defaultVal === term.slug || defaultVal === term.name);
                html += `<button class="wcb-qv-var-btn${isDefault ? ' active' : ''}"
                    data-attribute="${attr.name}"
                    data-value="${term.slug}"
                    data-label="${term.name}">
                    ${term.name}
                </button>`;
            });
            html += `</div></div>`;
        });
        html += '<p class="wcb-qv-var-notice"></p></div>';
        return html;
    }

    /* ── Bind variation swatch clicks ─────────────────────── */
    function bindVariationSwatches(p) {
        const varBtns = content.querySelectorAll('.wcb-qv-var-btn');
        varBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const attrName = this.dataset.attribute;
                const attrValue = this.dataset.value;
                const label = this.dataset.label;

                // Toggle selection
                const group = this.closest('.wcb-qv-var-group');
                const siblings = group.querySelectorAll('.wcb-qv-var-btn');
                siblings.forEach(s => s.classList.remove('active'));
                this.classList.add('active');

                // Update label
                const labelEl = group.querySelector('.wcb-qv-var-selected-label');
                if (labelEl) labelEl.textContent = label;

                // Update state
                selectedAttributes[attrName] = attrValue;

                // Find matching variation
                const variation = findMatchingVariation(p, selectedAttributes);

                // Update UI
                updatePriceBlock(p, variation);
                updateAddButton(p, variation);

                // Update image if variation has one
                if (variation && variation.image_url) {
                    updateMainImage(variation.image_url);
                }
            });
        });

        // Set defaults
        p.variation_attributes.forEach(attr => {
            const defaultVal = p.default_attributes[attr.name] || '';
            if (defaultVal) {
                selectedAttributes[attr.name] = defaultVal;
                const group = content.querySelector(`.wcb-qv-var-group[data-attribute="${attr.name}"]`);
                if (group) {
                    const labelEl = group.querySelector('.wcb-qv-var-selected-label');
                    const activeBtn = group.querySelector('.wcb-qv-var-btn.active');
                    if (labelEl && activeBtn) labelEl.textContent = activeBtn.dataset.label;
                }
            }
        });

        // If all defaults are set, find and apply variation
        const allDefaults = p.variation_attributes.every(a => selectedAttributes[a.name]);
        if (allDefaults) {
            const variation = findMatchingVariation(p, selectedAttributes);
            if (variation) {
                updatePriceBlock(p, variation);
                updateAddButton(p, variation);
            }
        }
    }

    /* ── Quantity Stepper ───────────────────────────────────── */
    function bindQtyStepper() {
        const qtyWrap = content.querySelector('#wcb-qv-qty');
        if (!qtyWrap) return;
        const valEl  = qtyWrap.querySelector('.wcb-qv-qty__val');
        const minBtn = qtyWrap.querySelector('.wcb-qv-qty__minus');
        const pluBtn = qtyWrap.querySelector('.wcb-qv-qty__plus');
        if (!valEl || !minBtn || !pluBtn) return;

        minBtn.addEventListener('click', () => {
            let v = parseInt(valEl.textContent, 10) || 1;
            if (v > 1) { v--; valEl.textContent = v; }
            const btn = content.querySelector('.wcb-qv-add-btn.ajax_add_to_cart');
            if (btn) btn.dataset.quantity = v;
        });
        pluBtn.addEventListener('click', () => {
            let v = parseInt(valEl.textContent, 10) || 1;
            if (v < 99) { v++; valEl.textContent = v; }
            const btn = content.querySelector('.wcb-qv-add-btn.ajax_add_to_cart');
            if (btn) btn.dataset.quantity = v;
        });
    }

    /* ── AJAX Add to Cart ─────────────────────────────────── */
    function bindAddToCart() {
        const addBtnEl = content.querySelector('.wcb-qv-add-btn.ajax_add_to_cart');
        if (!addBtnEl) return;

        addBtnEl.addEventListener('click', function (e) {
            e.preventDefault();
            const btn = this;
            if (btn.classList.contains('loading') || btn.disabled) return;

            btn.classList.add('loading');
            btn.disabled = true;

            // Read quantity from stepper
            const qtyEl = content.querySelector('.wcb-qv-qty__val');
            const qty = qtyEl ? parseInt(qtyEl.textContent, 10) || 1 : 1;

            const fd = new FormData();
            const variationId = btn.dataset.variation_id;
            const wcAtcUrl = wcbGetWcAjaxAddToCartUrl();

            if (variationId) {
                fd.append('product_id', String(variationId));
                fd.append('quantity', String(qty));
            } else {
                fd.append('product_id', btn.dataset.product_id);
                fd.append('product_sku', btn.dataset.product_sku || '');
                fd.append('quantity', String(qty));
            }

            const postUrl = wcAtcUrl || ajaxUrl;
            if (!wcAtcUrl) {
                fd.append('action', 'woocommerce_add_to_cart');
            }

            fetch(postUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    btn.classList.remove('loading');
                    btn.classList.add('added');
                    btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg> Adicionado!`;
                    if (typeof jQuery !== 'undefined') {
                        jQuery(document.body).trigger('wc_fragment_refresh');
                        jQuery(document.body).trigger('added_to_cart', [data.fragments, data.cart_hash]);
                    }
                    setTimeout(() => {
                        btn.classList.remove('added');
                        btn.disabled = false;
                        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg> Adicionar ao carrinho`;
                    }, 2500);
                })
                .catch(() => {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                });
        });
    }

    function wcbQvFocusInitial() {
        requestAnimationFrame(function () {
            try {
                if (closeBtn) {
                    closeBtn.focus();
                }
            } catch (e) { /* ignore */ }
        });
    }

    function wcbGetWcAjaxAddToCartUrl() {
        if (window.WcbVariationBuybox && typeof window.WcbVariationBuybox.getWcAjaxAddToCartUrl === 'function') {
            return window.WcbVariationBuybox.getWcAjaxAddToCartUrl();
        }
        if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url) {
            return wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart');
        }
        if (typeof wc_add_to_cart_variation_params !== 'undefined' && wc_add_to_cart_variation_params.wc_ajax_url) {
            return wc_add_to_cart_variation_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart');
        }
        return '';
    }

    function initQvNativeVariableBuybox(p) {
        var root = content.querySelector('[data-wcb-qv-native-variations="1"]');
        if (!root || !window.WcbVariationBuybox || typeof window.WcbVariationBuybox.setupQuickViewVariableBuybox !== 'function') {
            return;
        }
        var form = root.querySelector('form.variations_form');
        if (!form) {
            return;
        }
        window.WcbVariationBuybox.setupQuickViewVariableBuybox({
            root: root,
            form: form,
            defaultImageUrl: p.image_url || '',
            onVariationImage: function (url) {
                updateMainImage(url);
            },
            onResetImage: function (url) {
                updateMainImage(url);
            }
        });
    }

    /* ── Populate modal with product data ──────────────────── */
    function populateModal(p) {
        currentProduct = p;
        selectedAttributes = {};

        if (modal) {
            modal.scrollTop = 0;
        }

        var useNativeVarBox = p.type === 'variable' && p.buybox_html && String(p.buybox_html).trim() !== '';

        // Gallery HTML — thumbnails horizontal
        const galleryHtml = p.gallery.length > 1
            ? `<div class="wcb-qv-thumbs">
                ${p.gallery.map((g, i) => `<button class="wcb-qv-thumb${i === 0 ? ' active' : ''}" data-full="${g.full}" aria-label="${g.alt}">
                    <img src="${g.thumb}" alt="${g.alt}" loading="lazy">
                </button>`).join('')}
               </div>`
            : '';

        // Badge
        const badge = p.is_on_sale && p.saving > 0
            ? `<span class="wcb-qv-badge">-${p.saving}%</span>` : '';

        // Low stock notice
        const stockNotice = p.low_stock
            ? `<p class="wcb-qv-low-stock">⚡ Últimas <strong>${p.stock_qty}</strong> unidades!</p>` : '';

        // Specs
        const specsHtml = p.specs.length
            ? `<div class="wcb-qv-specs">${p.specs.map(s => `<span class="wcb-qv-spec">${s.emoji} ${s.value}</span>`).join('')}</div>` : '';

        if (useNativeVarBox) {
            content.innerHTML = `
        <a href="#wcb-qv-buy-area" class="wcb-qv-skip-buy">Ir para comprar</a>
        <div class="wcb-qv-top">
        <div class="wcb-qv-left">
            ${badge}
            <div class="wcb-qv-main-img-wrap">
                <img id="wcb-qv-main-img" src="${p.image_url}" alt="${p.name}" class="wcb-qv-main-img">
            </div>
            ${galleryHtml}
        </div>
        <div class="wcb-qv-right">
            ${p.category ? `<span class="wcb-qv-cat">${p.category}</span>` : ''}
            <h2 class="wcb-qv-title">${p.name}</h2>
            ${specsHtml}
            ${p.buybox_html}
            <a href="${p.permalink}" class="wcb-qv-full-link">
                Ver produto completo
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>
        </div>`;

            const thumbsNv = content.querySelectorAll('.wcb-qv-thumb');
            const mainImgNv = content.querySelector('#wcb-qv-main-img');
            thumbsNv.forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    thumbsNv.forEach(function (t) { t.classList.remove('active'); });
                    thumb.classList.add('active');
                    if (mainImgNv) {
                        mainImgNv.style.opacity = '0';
                        mainImgNv.style.transform = 'scale(0.96)';
                        setTimeout(function () {
                            mainImgNv.src = thumb.dataset.full;
                            mainImgNv.style.opacity = '1';
                            mainImgNv.style.transform = 'scale(1)';
                        }, 160);
                    }
                });
            });
            if (typeof window.wcbInitBuyboxShortDesc === 'function') {
                window.wcbInitBuyboxShortDesc(content);
            }
            initQvNativeVariableBuybox(p);
            wcbQvFocusInitial();
            return;
        }

        // Rating
        const ratingHtml = p.rating_count > 0
            ? `<div class="wcb-qv-rating">
                <span class="wcb-qv-stars">${buildStars(p.avg_rating)}</span>
                <span>${p.avg_rating}</span>
                <span class="wcb-qv-rating-count">(${p.rating_count})</span>
               </div>` : '';

        // Variations HTML
        const variationsHtml = (p.type === 'variable') ? buildVariationsHtml(p) : '';

        // Initial add to cart button
        const isVariable = p.type === 'variable';
        let addBtn = '';
        let inStockLabel = '';
        let showQty = false;

        if (isVariable) {
            addBtn = `<button class="wcb-qv-add-btn wcb-qv-add-btn--disabled" disabled>Selecione as opções</button>`;
            inStockLabel = '';
        } else if (p.in_stock) {
            showQty = true;
            addBtn = `<button class="wcb-qv-add-btn ajax_add_to_cart"
                    data-product_id="${p.id}"
                    data-product_sku="${p.sku}"
                    data-quantity="1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    Adicionar ao carrinho
               </button>`;
            inStockLabel = `<span class="wcb-qv-in-stock">● Em estoque</span>`;
        } else {
            addBtn = `<button class="wcb-qv-add-btn wcb-qv-add-btn--disabled" disabled>Indisponível</button>`;
            inStockLabel = `<span class="wcb-qv-out-stock">● Fora de estoque</span>`;
        }

        // Qty stepper HTML
        const qtyHtml = showQty ? `<div class="wcb-qv-qty" id="wcb-qv-qty">
            <button class="wcb-qv-qty__btn wcb-qv-qty__minus" type="button" aria-label="Diminuir">−</button>
            <span class="wcb-qv-qty__val">1</span>
            <button class="wcb-qv-qty__btn wcb-qv-qty__plus" type="button" aria-label="Aumentar">+</button>
        </div>` : '';

        content.innerHTML = `
        <a href="#wcb-qv-actions" class="wcb-qv-skip-buy">Ir para comprar</a>
        <div class="wcb-qv-top">
        <div class="wcb-qv-left">
            ${badge}
            <div class="wcb-qv-main-img-wrap">
                <img id="wcb-qv-main-img" src="${p.image_url}" alt="${p.name}" class="wcb-qv-main-img">
            </div>
            ${galleryHtml}
        </div>
        <div class="wcb-qv-right">
            ${p.category ? `<span class="wcb-qv-cat">${p.category}</span>` : ''}
            <h2 class="wcb-qv-title">${p.name}</h2>
            ${ratingHtml}
            <div class="wcb-qv-price-block">
                ${p.regular_price && p.is_on_sale ? `<span class="wcb-qv-price-old">${p.regular_price}</span>` : ''}
                <span class="wcb-qv-price-current">${p.current_price}</span>
                ${p.pix_price ? `<div class="wcb-qv-pix">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <strong>${p.pix_price}</strong> <span>no PIX</span>
                </div>` : ''}
                ${p.installments ? `<div class="wcb-qv-installments">${p.installments}</div>` : ''}
            </div>
            ${specsHtml}
            ${variationsHtml}
            <div id="wcb-qv-stock-row" class="wcb-qv-stock-row">${inStockLabel}</div>
            ${stockNotice}
            <hr class="wcb-qv-divider">
            <div class="wcb-qv-actions" id="wcb-qv-actions">
                <div class="wcb-qv-cart-row">${qtyHtml}${addBtn}</div>
            </div>
            <a href="${p.permalink}" class="wcb-qv-full-link">
                Ver produto completo
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>
        </div>`;

        // Gallery thumb switching
        const thumbs   = content.querySelectorAll('.wcb-qv-thumb');
        const mainImg  = content.querySelector('#wcb-qv-main-img');
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', () => {
                thumbs.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
                if (mainImg) {
                    mainImg.style.opacity = '0';
                    mainImg.style.transform = 'scale(0.96)';
                    setTimeout(() => {
                        mainImg.src = thumb.dataset.full;
                        mainImg.style.opacity = '1';
                        mainImg.style.transform = 'scale(1)';
                    }, 160);
                }
            });
        });

        // Bind variation swatches if variable product
        if (isVariable) {
            bindVariationSwatches(p);
        }

        // Bind qty stepper and add to cart
        if (showQty) bindQtyStepper();
        bindAddToCart();
        wcbQvFocusInitial();
    }

    /* ── Click on Quick View button (delegated) ───────────── */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.wcb-product-card__quickview-btn');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const card = btn.closest('.wcb-product-card');
        const productId = card && card.dataset.productId;
        if (!productId) return;

        // Show modal with loading state
        if (loading) loading.classList.remove('is-hidden');
        if (content) {
            content.innerHTML = '';
            content.classList.remove('is-visible');
            content.style.removeProperty('display');
        }
        openModal();

        // Fetch product data
        fetch(`${ajaxUrl}?action=wcb_quick_view&product_id=${productId}&nonce=${pubNonce}`, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error('product error');
                if (loading) loading.classList.add('is-hidden');
                if (content) {
                    content.style.removeProperty('display');
                    content.classList.add('is-visible');
                }
                populateModal(data.data);
            })
            .catch(() => {
                if (loading) loading.innerHTML = '<span style="color:#ef4444">Erro ao carregar produto.</span>';
            });
    });

    /* ── Close handlers ────────────────────────────────────── */
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeModal();
    });

}());

/* ============================================================
   MEGA MENU — Injetar botão "Ver todos" em categorias com filhos
   ============================================================ */
(function () {
    'use strict';

    function injectVerTodos() {
        // Seleciona todos os itens pai dentro do mega menu simples
        const parentItems = document.querySelectorAll(
            '.wcb-mega__simple > li.menu-item-has-children'
        );

        parentItems.forEach(function (li) {
            // Já foi injetado? Evita duplicar
            if (li.querySelector('.wcb-mega__ver-todos')) return;

            // Pega o link do pai (primeiro <a> filho direto do li)
            const parentLink = li.querySelector(':scope > a');
            if (!parentLink) return;

            const href = parentLink.getAttribute('href');
            if (!href || href === '#') return;

            // Verifica se há sublista
            const subList = li.querySelector(':scope > ul');
            if (!subList) return;

            // Cria o link "Ver todos"
            const verTodos = document.createElement('a');
            verTodos.href = href;
            verTodos.className = 'wcb-mega__ver-todos';
            verTodos.textContent = 'Ver todos';

            // Insere após a sublista (dentro do li, no final)
            li.appendChild(verTodos);
        });
    }

    // Executa quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectVerTodos);
    } else {
        injectVerTodos();
    }

}());


/* ============================================================
   MEGA MENU — Hover-intent com delay de abertura + busca inline
   ============================================================ */
(function () {
    'use strict';

    var items = document.querySelectorAll('.wcb-nav__item--mega');
    if (!items.length) return;

    var OPEN_DELAY  = 150;  // ms antes de abrir
    var CLOSE_DELAY = 200;  // ms antes de fechar

    items.forEach(function (li) {
        var openTimer  = null;
        var closeTimer = null;
        var mega = li.querySelector('.wcb-mega');
        if (!mega) return;

        function open() {
            clearTimeout(closeTimer);
            clearTimeout(openTimer);
            openTimer = setTimeout(function () {
                // Fechar outros abertos
                items.forEach(function (other) {
                    if (other !== li) other.classList.remove('wcb-mega--hover');
                });
                li.classList.add('wcb-mega--hover');
            }, OPEN_DELAY);
        }

        function cancelOpen() {
            clearTimeout(openTimer);
        }

        function scheduleClose() {
            cancelOpen();
            closeTimer = setTimeout(function () {
                li.classList.remove('wcb-mega--hover');
                // Reset busca ao fechar
                var search = mega.querySelector('.wcb-mega-search__input');
                if (search && search.value) {
                    search.value = '';
                    filterItems(mega, '');
                }
            }, CLOSE_DELAY);
        }

        li.addEventListener('mouseenter', open);
        li.addEventListener('mouseleave', scheduleClose);

        // Quando o mouse entra no painel, cancela o close timer
        mega.addEventListener('mouseenter', function () {
            clearTimeout(closeTimer);
        });
        mega.addEventListener('mouseleave', scheduleClose);
    });

    /* ── Busca inline nos mega menus ────────────────────── */
    document.querySelectorAll('.wcb-mega__inner').forEach(function (inner) {

        // Cria barra de busca
        var searchWrap = document.createElement('div');
        searchWrap.className = 'wcb-mega-search';
        searchWrap.innerHTML =
            '<svg class="wcb-mega-search__icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>' +
            '<input type="text" class="wcb-mega-search__input" placeholder="Buscar marca ou sabor..." autocomplete="off">';

        // Inserir antes das colunas (tenta .wcb-mega__columns ou .wcb-mega__simple ou .wcb-mega__header)
        var insertBefore = inner.querySelector('.wcb-mega__columns') || inner.querySelector('.wcb-mega__header') || inner.querySelector('.wcb-mega__simple');
        if (insertBefore) {
            inner.insertBefore(searchWrap, insertBefore);
        } else {
            inner.prepend(searchWrap);
        }

        var input = searchWrap.querySelector('.wcb-mega-search__input');
        var mega = inner.closest('.wcb-mega');

        input.addEventListener('input', function () {
            filterItems(mega, this.value);
        });

        // Impedir que o click no input feche o menu
        input.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    function filterItems(mega, query) {
        if (!mega) return;
        var term = query.toLowerCase().trim();

        // Filtrar itens em ambas as estruturas
        var children = mega.querySelectorAll('.wcb-mega__child, .wcb-mega__simple li.menu-item');
        var cols = mega.querySelectorAll('.wcb-mega__col, .wcb-mega__simple > li');

        if (!term) {
            // Mostrar tudo
            children.forEach(function (c) { c.style.display = ''; });
            cols.forEach(function (c) { c.style.display = ''; });
            return;
        }

        children.forEach(function (child) {
            var text = child.textContent.toLowerCase();
            child.style.display = text.indexOf(term) !== -1 ? '' : 'none';
        });

        // Esconde colunas onde TODOS os filhos estão ocultos
        cols.forEach(function (col) {
            var items = col.querySelectorAll('.wcb-mega__child:not([style*="display: none"]), li.menu-item:not([style*="display: none"])');
            // Não esconder a coluna se for um item de nível superior
            if (col.closest('.wcb-mega__simple')) {
                var subItems = col.querySelectorAll(':scope > ul li');
                if (subItems.length === 0) return; // é um item simples, já foi filtrado acima
                var visibles = col.querySelectorAll(':scope > ul li:not([style*="display: none"])');
                col.style.display = visibles.length > 0 ? '' : 'none';
            } else {
                var visibles = col.querySelectorAll('.wcb-mega__child:not([style*="display: none"])');
                col.style.display = visibles.length > 0 ? '' : 'none';
            }
        });
    }
})();

/* ============================================================
   MEGA MENU — Toggle "Ver todos" para expandir itens ocultos
   ============================================================ */
(function() {
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.wcb-mega__see-all-btn--toggle');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        var col = btn.closest('.wcb-mega__col');
        if (!col) return;

        var hidden = col.querySelectorAll('.wcb-mega__child--hidden, .wcb-mega__child--was-hidden');
        var isExpanded = btn.getAttribute('data-expanded') === 'true';
        var textEl = btn.querySelector('.wcb-mega__see-all-text');
        var href = btn.getAttribute('data-href');

        // Remove link "Ver todos" existente (se houver)
        var existingLink = col.querySelector('.wcb-mega__see-all-link');
        if (existingLink) existingLink.remove();

        if (isExpanded) {
            // Colapsar: re-adicionar classe hidden
            hidden.forEach(function(li) {
                li.classList.add('wcb-mega__child--hidden');
                li.classList.remove('wcb-mega__child--was-hidden');
            });
            btn.setAttribute('data-expanded', 'false');
            if (textEl) textEl.textContent = 'Ver todos (+' + hidden.length + ')';
        } else {
            // Expandir: mostrar todos
            hidden.forEach(function(li) {
                li.classList.remove('wcb-mega__child--hidden');
                li.classList.add('wcb-mega__child--was-hidden');
            });
            btn.setAttribute('data-expanded', 'true');
            if (textEl) textEl.textContent = 'Ver menos';

            // Criar link "Ver todos →" para a categoria
            if (href) {
                var link = document.createElement('a');
                link.href = href;
                link.className = 'wcb-mega__see-all-btn wcb-mega__see-all-link';
                link.innerHTML = '<span>Ver todos</span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
                link.style.marginTop = '0';
                link.style.marginLeft = '10px';
                btn.insertAdjacentElement('afterend', link);
            }
        }
    });
})();

/* ============================================================
   SUPER OFERTAS — tracking (dataLayer / gtag + vista da secção)
   ============================================================ */
(function () {
    'use strict';
    var section = document.getElementById('wcb-super-ofertas');
    if (!section) return;

    function wcbSoPush(name, detail) {
        detail = detail || {};
        detail.wcb_section = 'super-ofertas';
        try {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push(Object.assign({ event: name }, detail));
        } catch (e) { /* ignore */ }
        try {
            if (typeof window.gtag === 'function') {
                window.gtag('event', name, detail);
            }
        } catch (e2) { /* ignore */ }
    }

    var seen = false;
    if ('IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting || seen) return;
                seen = true;
                wcbSoPush('wcb_super_ofertas_view', { interaction: 'section_visible' });
                obs.disconnect();
            });
        }, { root: null, threshold: 0.12, rootMargin: '0px' });
        obs.observe(section);
    }

    section.addEventListener(
        'click',
        function (e) {
            var trackRoot = e.target.closest('[data-wcb-track="super-ofertas"]');
            if (!trackRoot || !section.contains(trackRoot)) return;
            var role = trackRoot.getAttribute('data-role') || '';
            var pid = trackRoot.getAttribute('data-product-id') || '';
            var cta = e.target.closest('.wcb-hero-cro__cta, .wcb-product-card__add-btn.ajax_add_to_cart, a.ajax_add_to_cart');
            if (cta && section.contains(cta)) {
                wcbSoPush('wcb_super_ofertas_click', {
                    interaction: 'cta',
                    role: role || 'unknown',
                    product_id: pid,
                });
                return;
            }
            if (e.target.closest('a[href]')) {
                wcbSoPush('wcb_super_ofertas_click', {
                    interaction: 'link',
                    role: role || 'unknown',
                    product_id: pid,
                });
            }
        },
        false
    );
})();

/* ============================================================
   MAIS VENDIDOS — banners estáticos: slide horizontal ≤890px
   ============================================================ */
(function () {
    'use strict';
    var root = document.querySelector('[data-wcb-vendidos-banners-slider]');
    if (!root) return;

    var track = root.querySelector('.wcb-vendidos-banners-group__track');
    var slides = track ? track.querySelectorAll('.wcb-vendidos-layout__banner') : null;
    var dots = root.querySelectorAll('.wcb-vendidos-banners-group__dot');
    if (!track || !slides || slides.length < 2 || dots.length < 2) return;

    var mq = window.matchMedia('(max-width: 890px)');
    var motionReduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    var autoTimer = null;

    function slideW() {
        return track.clientWidth || 0;
    }

    function activeIdx() {
        var w = slideW();
        if (w < 1) return 0;
        var i = Math.round(track.scrollLeft / w);
        if (i < 0) i = 0;
        if (i >= slides.length) i = slides.length - 1;
        return i;
    }

    function syncDots() {
        if (!mq.matches) return;
        var i = activeIdx();
        for (var j = 0; j < dots.length; j++) {
            dots[j].classList.toggle('is-active', j === i);
            dots[j].setAttribute('aria-selected', j === i ? 'true' : 'false');
        }
    }

    function goTo(i) {
        if (!mq.matches || i < 0 || i >= slides.length) return;
        var w = slideW();
        if (w < 1) return;
        track.scrollTo({
            left: i * w,
            behavior: motionReduce.matches ? 'auto' : 'smooth',
        });
    }

    function stopAuto() {
        if (autoTimer) {
            clearInterval(autoTimer);
            autoTimer = null;
        }
    }

    function startAuto() {
        stopAuto();
        if (!mq.matches || motionReduce.matches || slides.length < 2) return;
        autoTimer = window.setInterval(function () {
            if (!mq.matches) return;
            goTo((activeIdx() + 1) % slides.length);
        }, 6500);
    }

    function onScroll() {
        syncDots();
    }

    function onMq() {
        if (!mq.matches) {
            stopAuto();
            track.scrollLeft = 0;
            for (var j = 0; j < dots.length; j++) {
                dots[j].classList.toggle('is-active', j === 0);
                dots[j].setAttribute('aria-selected', j === 0 ? 'true' : 'false');
            }
        } else {
            syncDots();
            startAuto();
        }
    }

    track.addEventListener('scroll', onScroll, { passive: true });

    for (var d = 0; d < dots.length; d++) {
        (function (index) {
            dots[index].addEventListener('click', function () {
                stopAuto();
                goTo(index);
                startAuto();
            });
        }(d));
    }

    track.addEventListener('pointerdown', stopAuto, { passive: true });

    function mqBind(fn) {
        if (mq.addEventListener) mq.addEventListener('change', fn);
        else if (mq.addListener) mq.addListener(fn);
    }
    mqBind(onMq);

    window.addEventListener(
        'resize',
        function () {
            if (mq.matches) syncDots();
        },
        { passive: true }
    );

    if (mq.matches) {
        syncDots();
        startAuto();
    }
})();

/* PDP — carrossel "Você também pode gostar" (js/wcb-paged-carousel.js, enqueue em single product) */
if (window.WcbPagedCarousel && typeof window.WcbPagedCarousel.init === 'function') {
    window.WcbPagedCarousel.init('wcb-pdp-similar-carousel', 3000);
}
