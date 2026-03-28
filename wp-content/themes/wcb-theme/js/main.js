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

    function openMobileMenu() {
        if (mobileMenu && mobileOverlay) {
            mobileMenu.classList.add('active');
            mobileOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeMobileMenu() {
        if (mobileMenu && mobileOverlay) {
            mobileMenu.classList.remove('active');
            mobileOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (mobileToggle) mobileToggle.addEventListener('click', openMobileMenu);
    if (mobileClose) mobileClose.addEventListener('click', closeMobileMenu);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileMenu);

    /* ============================================================
       HERO SLIDER
       ============================================================ */
    const heroSlider = document.getElementById('wcb-hero-slider');
    const heroSlides = heroSlider ? heroSlider.querySelectorAll('.wcb-hero__slide') : [];
    const heroDots = document.querySelectorAll('.wcb-hero__dot');
    const heroPrev = document.getElementById('hero-prev');
    const heroNext = document.getElementById('hero-next');
    let currentSlide = 0;
    let heroInterval;

    function showSlide(index) {
        if (heroSlides.length === 0) return;
        heroSlides.forEach(s => s.classList.remove('active'));
        heroDots.forEach(d => d.classList.remove('active'));
        currentSlide = (index + heroSlides.length) % heroSlides.length;
        heroSlides[currentSlide].classList.add('active');
        if (heroDots[currentSlide]) heroDots[currentSlide].classList.add('active');
    }

    function nextSlide() { showSlide(currentSlide + 1); }
    function prevSlide() { showSlide(currentSlide - 1); }

    function startAutoPlay() {
        heroInterval = setInterval(nextSlide, 5000);
    }

    function resetAutoPlay() {
        clearInterval(heroInterval);
        startAutoPlay();
    }

    if (heroSlides.length > 0) {
        if (heroNext) heroNext.addEventListener('click', () => { nextSlide(); resetAutoPlay(); });
        if (heroPrev) heroPrev.addEventListener('click', () => { prevSlide(); resetAutoPlay(); });
        heroDots.forEach(dot => {
            dot.addEventListener('click', () => {
                showSlide(parseInt(dot.dataset.slide));
                resetAutoPlay();
            });
        });
        startAutoPlay();
    }

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
        var badge = document.getElementById('wcb-header-fav-count');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';
        } else {
            badge.textContent = '0';
            badge.style.display = 'none';
        }
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
            })
            .catch(() => {
                favBtn.classList.toggle('active');
            });
    });

    // ── Remover da página de favoritos (botão X do card) ─────────
    document.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.wcb-wishlist-card__remove, .wcb-wl-card__remove');
        if (!removeBtn) return;

        e.preventDefault();

        const productId = parseInt(removeBtn.dataset.productId, 10);
        const card = removeBtn.closest('.wcb-wishlist-card, .wcb-wl-card');
        const wl = window.wcbWishlist;

        if (!productId || !wl || !wl.isLoggedIn) return;

        // Animar saída
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
                    // Atualizar contagem no header da página de favoritos
                    var wlHeaderSub = document.querySelector('.wcb-wl-header__sub');
                    if (wlHeaderSub) {
                        var c = wl.wishlist.length;
                        wlHeaderSub.textContent = c + ' produto' + (c !== 1 ? 's' : '') + ' salvo' + (c !== 1 ? 's' : '');
                    }
                    // Verificar se ficou vazio
                    var remaining = document.querySelectorAll('.wcb-wl-card, .wcb-wishlist-card');
                    if (remaining.length === 0) {
                        setTimeout(function() { location.reload(); }, 500);
                    }
                }
            });
    });

    /* ============================================================
       MOBILE SEARCH TOGGLE
       ============================================================ */
    const searchToggle = document.getElementById('wcb-search-toggle');
    const searchBox = document.getElementById('wcb-search');

    if (searchToggle && searchBox) {
        searchToggle.addEventListener('click', () => {
            searchBox.classList.toggle('active');
            const input = searchBox.querySelector('input[type="search"]');
            if (searchBox.classList.contains('active') && input) {
                input.focus();
            }
        });
    }

    /* ============================================================
       RESPONSIVE: Show/hide mobile search toggle
       ============================================================ */
    function handleResize() {
        if (searchToggle) {
            if (window.innerWidth <= 768) {
                searchToggle.style.display = 'flex';
            } else {
                searchToggle.style.display = 'none';
                if (searchBox) searchBox.classList.remove('active');
            }
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize();

    /* ============================================================
       COUNTDOWN TIMER (Super Ofertas) — Premium v2
       ============================================================ */
    const countdownEl = document.getElementById('wcb-countdown');

    if (countdownEl) {
        const endDate = new Date(countdownEl.dataset.end).getTime();
        const daysEl    = document.getElementById('countdown-days');
        const hoursEl   = document.getElementById('countdown-hours');
        const minutesEl = document.getElementById('countdown-minutes');
        const secondsEl = document.getElementById('countdown-seconds');

        function pad(n) { return n < 10 ? '0' + n : String(n); }

        // Animação de flip quando o dígito muda
        function flipUpdate(el, newVal) {
            if (!el) return;
            const formatted = pad(newVal);
            if (el.textContent === formatted) return;
            el.classList.add('wcb-cd-flip');
            setTimeout(() => {
                el.textContent = formatted;
                el.classList.remove('wcb-cd-flip');
            }, 120);
        }

        function updateCountdown() {
            const diff = endDate - Date.now();

            if (diff <= 0) {
                [daysEl, hoursEl, minutesEl, secondsEl].forEach(el => {
                    if (el) { el.textContent = '00'; }
                });
                countdownEl.classList.remove('wcb-cd--warning', 'wcb-cd--urgent');
                countdownEl.classList.add('wcb-cd--done');
                return;
            }

            const days    = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours   = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            const totalMinutes = days * 1440 + hours * 60 + minutes;

            flipUpdate(daysEl,    days);
            flipUpdate(hoursEl,   hours);
            flipUpdate(minutesEl, minutes);
            flipUpdate(secondsEl, seconds);

            // Transição de cor: azul (normal) → laranja (< 1h) → vermelho (< 10min)
            countdownEl.classList.remove('wcb-cd--warning', 'wcb-cd--urgent', 'wcb-cd--done');
            if (totalMinutes < 10) {
                countdownEl.classList.add('wcb-cd--urgent');
            } else if (totalMinutes < 60) {
                countdownEl.classList.add('wcb-cd--warning');
            }
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

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
       ============================================================ */
    const zoomWrap = document.getElementById('wcb-pdp-zoom');
    if (zoomWrap && pdpMainImg && window.innerWidth > 1024) {
        zoomWrap.addEventListener('mousemove', (e) => {
            const rect = zoomWrap.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            pdpMainImg.style.transformOrigin = x + '% ' + y + '%';
            pdpMainImg.style.transform = 'scale(2)';
        });
        zoomWrap.addEventListener('mouseleave', () => {
            pdpMainImg.style.transformOrigin = 'center';
            pdpMainImg.style.transform = 'scale(1)';
        });
    }

    /* ============================================================
       SINGLE PRODUCT — STICKY BUY BAR (Suporta v1 e v2)
       ============================================================ */
    // v2 (PDP Premium — fixa no topo)
    const pdpSticky = document.getElementById('wcb-pdp-sticky');
    const pdpBuybox = document.getElementById('wcb-pdp-buybox');

    if (pdpSticky && pdpBuybox) {
        const obs = new IntersectionObserver((entries) => {
            if (!entries[0].isIntersecting) {
                pdpSticky.classList.add('is-visible');
            } else {
                pdpSticky.classList.remove('is-visible');
            }
        }, { rootMargin: '-100px 0px 0px 0px', threshold: 0.1 });
        obs.observe(pdpBuybox);
    }

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
       PDP v2 — COUNTDOWN 2h (Barra de Oferta)
       ============================================================ */
    const pdpCountdown = document.getElementById('wcb-pdp-countdown');
    if (pdpCountdown) {
        let totalSec = 7200; // 2 horas
        const tick = setInterval(() => {
            if (totalSec <= 0) { clearInterval(tick); pdpCountdown.innerText = '00:00:00'; return; }
            totalSec--;
            const h = String(Math.floor(totalSec / 3600)).padStart(2, '0');
            const m = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
            const s = String(totalSec % 60).padStart(2, '0');
            pdpCountdown.innerText = h + ':' + m + ':' + s;
        }, 1000);
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
        const elInstallments = document.getElementById('wcb-pdp-installments');
        const elInstallmentsVal = document.getElementById('wcb-pdp-installments-val');
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

            // Parcelas (12x)
            if (price >= 100) {
                const parcela = price / 12;
                if (elInstallmentsVal) elInstallmentsVal.textContent = formatBRL(parcela);
                if (elInstallments) elInstallments.style.display = '';
            } else {
                if (elInstallments) elInstallments.style.display = 'none';
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
                    cardLine += 'Cartão R$ ' + formatBRL(price);
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
       PDP v2 — TOGGLE REVIEW FORM
       ============================================================ */
    const toggleReviewBtn = document.getElementById('wcb-pdp-toggle-review');
    const reviewFormWrap = document.getElementById('wcb-pdp-review-form');
    if (toggleReviewBtn && reviewFormWrap) {
        toggleReviewBtn.addEventListener('click', () => {
            reviewFormWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Focar no textarea
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
        let activeFilter = null; // { type: 'all'|'promo'|'new'|'stock' }

        function applyFilter(allItems, filter) {
            if (!filter || filter.type === 'all') return allItems;
            return allItems.filter(item => {
                if (filter.type === 'promo') return !!item.price_old;
                if (filter.type === 'new') return !!item.is_new;
                if (filter.type === 'stock') return item.in_stock === true;
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
                        ${item.is_trending && !item.is_bestseller ? '<span class="wcb-badge wcb-badge--trend">🔥 Trending</span>' : ''}
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
            const hasNew = allItems.some(i => !!i.is_new);
            const hasInStock = allItems.some(i => !!i.in_stock);

            // Don't show the bar at all if no optional filters apply
            if (!hasPromo && !hasNew && !hasInStock) return '';

            function chip(label, type, emoji) {
                const isActive = type === 'all'
                    ? !currentFilter
                    : (currentFilter && currentFilter.type === type);
                return `<button class="wcb-filter-chip${isActive ? ' wcb-filter-chip--active' : ''}" data-filter-type="${type}" data-filter-value="">${emoji ? emoji + ' ' : ''}${label}</button>`;
            }

            let chips = chip('Todos', 'all', '');
            if (hasPromo) chips += chip('Promoção', 'promo', '🏷️');
            if (hasNew) chips += chip('Novidades', 'new', '✨');
            if (hasInStock) chips += chip('Em estoque', 'stock', '●');

            if (currentFilter) {
                chips += `<button class="wcb-filter-chip wcb-filter-chip--clear" data-filter-type="clear" data-filter-value="">✕ Limpar</button>`;
            }

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
            fetch(`${ajaxUrl}?action=wcb_live_search&q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => renderResults(data, q))
                .catch(() => hideDropdown());
        }, 300);
    });

    // --- Reposition on scroll/resize (header is sticky) ---
    window.addEventListener('scroll', () => {
        if (dropdown.classList.contains('visible')) positionDropdown();
    }, { passive: true });
    window.addEventListener('resize', () => {
        if (dropdown.classList.contains('visible')) positionDropdown();
    }, { passive: true });

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
   HERO BANNER SLIDER
   ============================================================ */
(function () {
    const slides = Array.from(document.querySelectorAll('.wcb-hero__slide'));
    const dots = Array.from(document.querySelectorAll('.wcb-hero__dot'));
    const btnPrev = document.getElementById('hero-prev');
    const btnNext = document.getElementById('hero-next');
    const hero = document.getElementById('wcb-hero');

    if (!slides.length) return;

    let current = 0;
    let timer = null;
    const DELAY = 5000;

    function goTo(idx) {
        slides[current].classList.remove('active');
        dots[current]?.classList.remove('active');
        current = (idx + slides.length) % slides.length;
        slides[current].classList.add('active');
        dots[current]?.classList.add('active');
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    function startTimer() {
        stopTimer();
        timer = setInterval(next, DELAY);
    }

    function stopTimer() {
        if (timer) { clearInterval(timer); timer = null; }
    }

    btnNext?.addEventListener('click', () => { next(); startTimer(); });
    btnPrev?.addEventListener('click', () => { prev(); startTimer(); });

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => { goTo(i); startTimer(); });
    });

    // Pause on hover / touch
    hero?.addEventListener('mouseenter', stopTimer);
    hero?.addEventListener('mouseleave', startTimer);
    hero?.addEventListener('touchstart', stopTimer, { passive: true });
    hero?.addEventListener('touchend', startTimer, { passive: true });

    // Swipe support
    let touchX = 0;
    hero?.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
    hero?.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - touchX;
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
    if (!('IntersectionObserver' in window)) {
        document.querySelectorAll('.wcb-section, .wcb-promo-banners').forEach(el => {
            el.classList.add('wcb-visible');
        });
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('wcb-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.wcb-section, .wcb-promo-banners').forEach(el => {
        observer.observe(el);
    });
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
            fetch(ajaxUrl + '?action=wcb_mini_cart', { credentials: 'same-origin' })
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
            if (content) { content.innerHTML = ''; content.classList.remove('is-visible'); }
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

            if (variationId) {
                fd.append('action', 'woocommerce_ajax_add_to_cart');
                fd.append('product_id', btn.dataset.product_id);
                fd.append('variation_id', variationId);
                fd.append('quantity', qty);
                Object.entries(selectedAttributes).forEach(([key, val]) => {
                    fd.append(`attribute_${key}`, val);
                });
            } else {
                fd.append('action', 'woocommerce_ajax_add_to_cart');
                fd.append('product_id', btn.dataset.product_id);
                fd.append('product_sku', btn.dataset.product_sku || '');
                fd.append('quantity', qty);
            }

            fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
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

    /* ── Populate modal with product data ──────────────────── */
    function populateModal(p) {
        currentProduct = p;
        selectedAttributes = {};

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

        // Rating
        const ratingHtml = p.rating_count > 0
            ? `<div class="wcb-qv-rating">
                <span class="wcb-qv-stars">${buildStars(p.avg_rating)}</span>
                <span>${p.avg_rating}</span>
                <span class="wcb-qv-rating-count">(${p.rating_count})</span>
               </div>` : '';

        // Short desc
        const descHtml = p.short_desc
            ? `<p class="wcb-qv-desc">${p.short_desc}</p>` : '';

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
            ${descHtml}
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
            <div class="wcb-qv-actions">
                <div class="wcb-qv-cart-row">${qtyHtml}${addBtn}</div>
            </div>
            <a href="${p.permalink}" class="wcb-qv-full-link">
                Ver produto completo
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
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
        if (content) { content.innerHTML = ''; content.classList.remove('is-visible'); }
        openModal();

        // Fetch product data
        fetch(`${ajaxUrl}?action=wcb_quick_view&product_id=${productId}`, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error('product error');
                if (loading) loading.classList.add('is-hidden');
                if (content) content.classList.add('is-visible');
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
