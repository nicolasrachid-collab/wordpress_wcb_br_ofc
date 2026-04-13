/* #region agent log — debug trust marquee session 8eb372 */
(function () {
    var ENDPOINT = 'http://127.0.0.1:7636/ingest/84dd36e5-8118-419a-b3a7-83f8e201977e';
    var SESSION = '8eb372';

    function send(hypothesisId, message, data) {
        var payload = {
            sessionId: SESSION,
            runId: 'verify-reduce-motion-fix',
            hypothesisId: hypothesisId,
            location: 'wcb-debug-trust-marquee.js',
            message: message,
            data: data,
            timestamp: Date.now(),
        };
        fetch(ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Debug-Session-Id': SESSION,
            },
            body: JSON.stringify(payload),
        }).catch(function () {});

        if (typeof wcbDbgTrust !== 'undefined' && wcbDbgTrust.ajaxUrl && wcbDbgTrust.nonce) {
            var fd = new FormData();
            fd.append('action', 'wcb_debug_trust');
            fd.append('nonce', wcbDbgTrust.nonce);
            fd.append('line', JSON.stringify(payload));
            fetch(wcbDbgTrust.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' }).catch(
                function () {}
            );
        }
    }

    function runSample() {
        var track = document.querySelector('.wcb-trust__track');
        var viewport = document.querySelector('.wcb-trust__viewport');
        var cs = track ? window.getComputedStyle(track) : null;
        var vcs = viewport ? window.getComputedStyle(viewport) : null;
        var mq1118 = window.matchMedia('(max-width: 1118px)').matches;
        var mqReduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var mqHover = window.matchMedia('(hover: hover)').matches;
        var mqFine = window.matchMedia('(pointer: fine)').matches;
        var sec = document.querySelector('.wcb-trust');

        send('H-JS', 'marquee-js class after run script', {
            hasMarqueeJsClass: !!(sec && sec.classList.contains('wcb-trust--marquee-js')),
            mqReduceMotion: mqReduce,
            expectJsWhenNarrow: mq1118 && !!(sec && sec.classList.contains('wcb-trust--marquee-js')),
        });

        send('H-A', 'viewport vs breakpoint', {
            innerWidth: window.innerWidth,
            clientWidth: document.documentElement ? document.documentElement.clientWidth : null,
            mqMax1118: mq1118,
        });

        send('H-B', 'reduced motion', {
            mqReduceMotion: mqReduce,
        });

        send('H-C', 'computed animation', {
            trackFound: !!track,
            animationName: cs ? cs.animationName : null,
            animationNameExpectLayer: cs && cs.animationName && String(cs.animationName).indexOf('wcb-trust-marquee-layer') !== -1,
            animationDuration: cs ? cs.animationDuration : null,
            animationIterationCount: cs ? cs.animationIterationCount : null,
            animationPlayState: cs ? cs.animationPlayState : null,
            transform: cs ? cs.transform : null,
        });

        send('H-D', 'display layout', {
            trackDisplay: cs ? cs.display : null,
            viewportDisplay: vcs ? vcs.display : null,
            viewportOverflow: vcs ? vcs.overflow : null,
        });

        send('H-E', 'hover/pointer (pause rule)', {
            mqHover: mqHover,
            mqFine: mqFine,
        });

        if (track && cs) {
            var t0 = cs.transform;
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    var cs2 = window.getComputedStyle(track);
                    send('H-C2', 'transform delta after frames', {
                        t0: t0,
                        t1: cs2.transform,
                        changed: t0 !== cs2.transform,
                    });
                });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runSample);
    } else {
        runSample();
    }
})();
/* #endregion agent log */
