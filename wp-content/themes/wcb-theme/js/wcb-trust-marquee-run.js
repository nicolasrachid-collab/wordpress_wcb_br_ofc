/**
 * Trust bar — letreiro ≤1118px via requestAnimationFrame (fallback fiável ao CSS).
 */
(function () {
    'use strict';

    var MARQUEE_CLASS = 'wcb-trust--marquee-js';
    var DURATION_NORMAL_MS = 36000;
    var DURATION_REDUCE_MOTION_MS = 72000;

    var section;
    var track;
    var viewport;
    var mqW;
    var mqR;
    var rafId = 0;
    var lastNow = 0;
    var phase = 0;
    var hoverPause = false;

    function findEls() {
        section = document.querySelector('.wcb-trust');
        track = document.querySelector('.wcb-trust__track');
        viewport = document.querySelector('.wcb-trust__viewport');
    }

    function stop() {
        if (section) {
            section.classList.remove(MARQUEE_CLASS);
        }
        if (track) {
            track.style.transform = '';
            track.style.willChange = '';
        }
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = 0;
        }
        lastNow = 0;
        phase = 0;
    }

    function durationMs() {
        return mqR && mqR.matches ? DURATION_REDUCE_MOTION_MS : DURATION_NORMAL_MS;
    }

    function bindHoverPause() {
        if (!viewport) {
            return;
        }
        if (!window.matchMedia('(hover: hover)').matches || !window.matchMedia('(pointer: fine)').matches) {
            return;
        }
        viewport.addEventListener('mouseenter', function () {
            hoverPause = true;
        });
        viewport.addEventListener('mouseleave', function () {
            hoverPause = false;
        });
    }

    function tick(now) {
        if (!track || !section || !section.classList.contains(MARQUEE_CLASS)) {
            return;
        }

        var half = track.scrollWidth / 2;
        if (half < 8) {
            rafId = requestAnimationFrame(tick);
            return;
        }

        if (!lastNow) {
            lastNow = now;
        }
        var dt = now - lastNow;
        lastNow = now;

        if (!hoverPause) {
            phase = (phase + dt / durationMs()) % 1;
        }

        var x = -phase * half;
        track.style.willChange = 'transform';
        track.style.transform = 'translate3d(' + x + 'px,0,0)';

        rafId = requestAnimationFrame(tick);
    }

    function start() {
        stop();
        /* prefers-reduced-motion: movimento mais lento (ver durationMs), não desligar — logs 8eb372 mostraram mqReduceMotion:true bloqueando tudo. */
        if (!mqW.matches || !section || !track) {
            return;
        }
        section.classList.add(MARQUEE_CLASS);
        lastNow = 0;
        phase = 0;
        rafId = requestAnimationFrame(tick);
    }

    function init() {
        findEls();
        if (!section || !track) {
            return;
        }
        mqW = window.matchMedia('(max-width: 1118px)');
        mqR = window.matchMedia('(prefers-reduced-motion: reduce)');
        bindHoverPause();
        start();
        mqW.addEventListener('change', start);
        mqR.addEventListener('change', start);
        window.addEventListener('resize', function () {
            if (!section.classList.contains(MARQUEE_CLASS)) {
                return;
            }
            /* scrollWidth recalcula no próximo tick */
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
