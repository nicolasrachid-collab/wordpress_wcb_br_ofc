/**
 * Carrossel paginado (viewport, setas, progresso, autoplay).
 * Partilhado entre PDP (similares) e, no futuro, front-page.
 *
 * @package WCB_Theme
 */
(function (window) {
    'use strict';

    /**
     * @param {string} carouselId ID do elemento .wcb-paged-carousel
     * @param {number} [delayMs=3000] Intervalo do autoplay quando há vários slides
     * @returns {object|null}
     */
    function initPagedCarousel(carouselId, delayMs) {
        var carousel = document.getElementById(carouselId);
        if (!carousel) {
            return null;
        }

        var track = carousel.querySelector('.wcb-paged-carousel__track');
        var total = carousel.querySelectorAll('.wcb-paged-carousel__slide').length;

        if (!track || total === 0) {
            return null;
        }

        var navMulti = total > 1;
        var DELAY = typeof delayMs === 'number' && delayMs > 0 ? delayMs : 3000;

        var viewport = document.createElement('div');
        viewport.className = 'wcb-carousel-viewport';
        track.parentNode.insertBefore(viewport, track);
        viewport.appendChild(track);

        var current = 0;
        var autoTimer = null;

        var prevArrow = document.createElement('button');
        prevArrow.type = 'button';
        prevArrow.className = 'wcb-carousel-arrow wcb-carousel-arrow--prev';
        prevArrow.setAttribute('aria-label', 'Anterior');
        prevArrow.innerHTML =
            '<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>';
        carousel.appendChild(prevArrow);

        var nextArrow = document.createElement('button');
        nextArrow.type = 'button';
        nextArrow.className = 'wcb-carousel-arrow wcb-carousel-arrow--next';
        nextArrow.setAttribute('aria-label', 'Próximo');
        nextArrow.innerHTML =
            '<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>';
        carousel.appendChild(nextArrow);

        if (!navMulti) {
            prevArrow.disabled = true;
            nextArrow.disabled = true;
            prevArrow.setAttribute('aria-disabled', 'true');
            nextArrow.setAttribute('aria-disabled', 'true');
        }

        var progressBar = document.createElement('div');
        progressBar.className = 'wcb-carousel-progress';
        if (navMulti) {
            for (var i = 0; i < total; i++) {
                var seg = document.createElement('div');
                seg.className = 'wcb-carousel-progress__segment' + (i === 0 ? ' active' : '');
                seg.setAttribute('data-index', i);
                var fill = document.createElement('div');
                fill.className = 'wcb-carousel-progress__fill';
                seg.appendChild(fill);
                progressBar.appendChild(seg);
            }
        } else {
            progressBar.hidden = true;
        }
        carousel.appendChild(progressBar);

        var segments = progressBar.querySelectorAll('.wcb-carousel-progress__segment');
        var fills = progressBar.querySelectorAll('.wcb-carousel-progress__fill');

        function goTo(idx) {
            if (idx < 0) {
                idx = total - 1;
            }
            if (idx >= total) {
                idx = 0;
            }
            current = idx;
            track.style.transform = 'translateX(-' + current * 100 + '%)';

            segments.forEach(function (seg, i) {
                seg.classList.remove('active', 'done');
                if (fills[i]) {
                    fills[i].style.transition = 'none';
                    fills[i].style.width = '0%';
                }
                if (i < current) {
                    seg.classList.add('done');
                }
                if (i === current) {
                    seg.classList.add('active');
                }
            });
        }

        function animateProgress() {
            if (!navMulti) {
                return;
            }
            var fill = fills[current];
            if (!fill) {
                return;
            }

            fill.style.transition = 'none';
            fill.style.width = '0%';

            void fill.offsetWidth;

            fill.style.transition = 'width ' + DELAY + 'ms linear';
            fill.style.width = '100%';
        }

        function startAuto() {
            if (!navMulti) {
                return;
            }
            stopAuto();
            animateProgress();
            autoTimer = setTimeout(function autoNext() {
                goTo(current + 1);
                animateProgress();
                autoTimer = setTimeout(autoNext, DELAY);
            }, DELAY);
        }

        function stopAuto() {
            if (autoTimer) {
                clearTimeout(autoTimer);
                autoTimer = null;
            }
            if (!navMulti) {
                return;
            }
            fills.forEach(function (f) {
                var w = f.getBoundingClientRect().width;
                var pW = f.parentElement.getBoundingClientRect().width;
                f.style.transition = 'none';
                f.style.width = (pW > 0 ? (w / pW) * 100 : 0) + '%';
            });
        }

        function resetAuto() {
            startAuto();
        }

        if (navMulti) {
            prevArrow.addEventListener('click', function () {
                goTo(current - 1);
                resetAuto();
            });
            nextArrow.addEventListener('click', function () {
                goTo(current + 1);
                resetAuto();
            });

            segments.forEach(function (seg, i) {
                seg.addEventListener('click', function () {
                    goTo(i);
                    resetAuto();
                });
            });

            carousel.addEventListener('mouseenter', stopAuto);
            carousel.addEventListener('mouseleave', startAuto);
        }

        goTo(0);
        if (navMulti) {
            startAuto();
        }

        return {
            goTo: goTo,
            resetAuto: resetAuto,
            getCurrent: function () {
                return current;
            },
        };
    }

    window.WcbPagedCarousel = {
        init: initPagedCarousel,
    };
})(window);
