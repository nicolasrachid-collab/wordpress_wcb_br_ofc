/**
 * WCB Native Filter v2 — AJAX filtering + Dynamic Count
 * Zero dependencies — vanilla JS
 */
(function () {
  'use strict';

  var form, grid, countEl, countTextEl, ajaxUrl, nonce;
  var debounceTimer = null;
  var isLoading = false;

  document.addEventListener('DOMContentLoaded', init);

  function init() {
    form       = document.getElementById('wcb-filter-form');
    grid       = document.querySelector('.wcb-shop__main');
    countEl    = document.getElementById('wcb-filter-count');
    countTextEl = document.getElementById('wcb-filter-count-text');

    if (!form || !grid) return;

    ajaxUrl = form.dataset.ajaxUrl || (window.wcbData && window.wcbData.ajaxUrl) || '/wcb/wp-admin/admin-ajax.php';
    nonce   = form.dataset.nonce || '';

    initAccordions();
    initPriceSlider();
    initAjaxFilter();
    initClear();
    initCollapsedSections();
  }

  /* ── Accordion toggle ──────────────────────────────────────── */
  function initAccordions() {
    form.querySelectorAll('.wcb-filter__section-header').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var section = btn.closest('.wcb-filter__section');
        var body = section.querySelector('.wcb-filter__section-body');
        var isExpanded = btn.getAttribute('aria-expanded') === 'true';

        if (isExpanded) {
          // Collapse
          body.style.maxHeight = body.scrollHeight + 'px';
          requestAnimationFrame(function () {
            body.style.maxHeight = '0';
            body.style.paddingBottom = '0';
            body.style.opacity = '0';
          });
          btn.setAttribute('aria-expanded', 'false');
        } else {
          // Expand
          body.style.maxHeight = body.scrollHeight + 'px';
          body.style.paddingBottom = '12px';
          body.style.opacity = '1';
          btn.setAttribute('aria-expanded', 'true');

          // After transition, remove max-height to allow dynamic content
          var onEnd = function () {
            body.style.maxHeight = 'none';
            body.removeEventListener('transitionend', onEnd);
          };
          body.addEventListener('transitionend', onEnd);
        }
      });
    });
  }

  /* ── Handle collapsed sections on load ─────────────────────── */
  function initCollapsedSections() {
    form.querySelectorAll('.wcb-filter__section-header[aria-expanded="false"]').forEach(function (btn) {
      var body = btn.closest('.wcb-filter__section').querySelector('.wcb-filter__section-body');
      if (body) {
        body.style.maxHeight = '0';
        body.style.paddingBottom = '0';
        body.style.opacity = '0';
        body.style.overflow = 'hidden';
      }
    });
  }

  /* ── Dual Range Slider (Preço) ─────────────────────────────── */
  function initPriceSlider() {
    var container = document.querySelector('.wcb-filter__price-range');
    if (!container) return;

    var minInput = container.querySelector('.wcb-filter__range--min');
    var maxInput = container.querySelector('.wcb-filter__range--max');
    var fill = container.querySelector('.wcb-filter__price-fill');
    var minLabel = document.getElementById('wcb-price-min-val');
    var maxLabel = document.getElementById('wcb-price-max-val');

    if (!minInput || !maxInput) return;

    function update() {
      var min = parseInt(minInput.value);
      var max = parseInt(maxInput.value);
      var rangeMin = parseInt(minInput.min);
      var rangeMax = parseInt(minInput.max);

      // Prevent crossing
      if (min > max) {
        var temp = min;
        minInput.value = max;
        maxInput.value = temp;
        min = parseInt(minInput.value);
        max = parseInt(maxInput.value);
      }

      // Update fill bar
      var leftPct = ((min - rangeMin) / (rangeMax - rangeMin)) * 100;
      var rightPct = ((max - rangeMin) / (rangeMax - rangeMin)) * 100;
      fill.style.left = leftPct + '%';
      fill.style.width = (rightPct - leftPct) + '%';

      // Update labels
      if (minLabel) minLabel.textContent = 'R$ ' + min;
      if (maxLabel) maxLabel.textContent = 'R$ ' + max;
    }

    minInput.addEventListener('input', update);
    maxInput.addEventListener('input', update);

    // Debounced AJAX on slider change
    minInput.addEventListener('change', function () { debouncedFilter(); });
    maxInput.addEventListener('change', function () { debouncedFilter(); });

    update();
  }

  /* ── AJAX Filter Logic ─────────────────────────────────────── */
  function initAjaxFilter() {
    // Listen to all checkbox/radio changes for instant AJAX
    form.addEventListener('change', function (e) {
      var target = e.target;
      if (target.type === 'checkbox' || target.type === 'radio') {
        debouncedFilter();
      }
    });

    // Apply button (manual trigger)
    var applyBtn = document.getElementById('wcb-filter-apply');
    if (applyBtn) {
      applyBtn.addEventListener('click', function (e) {
        e.preventDefault();
        runAjaxFilter();
      });
    }
  }

  function debouncedFilter() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(runAjaxFilter, 350);
  }

  /** Remove estilos inline da grelha (evita opacity:0 preso se AJAX falhar ou success=false). */
  function clearGridTransitionStyles() {
    if (!grid) return;
    grid.style.removeProperty('opacity');
    grid.style.removeProperty('transform');
  }

  function runAjaxFilter() {
    if (isLoading) return;
    isLoading = true;

    // Show loading state
    grid.classList.add('wcb-filter--loading');

    // Update the apply button
    var applyBtn = document.getElementById('wcb-filter-apply');
    if (applyBtn) {
      applyBtn.classList.add('wcb-filter__btn--loading');
    }

    // Collect form data
    var data = new FormData();
    data.append('action', 'wcb_filter_products');
    data.append('nonce', nonce);

    // Checkboxes (categories + attributes)
    form.querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) {
      data.append(cb.name, cb.value);
    });

    // Radio buttons (stock)
    form.querySelectorAll('input[type="radio"]:checked').forEach(function (rb) {
      if (rb.value) data.append(rb.name, rb.value);
    });

    // Price range
    var minInput = form.querySelector('.wcb-filter__range--min');
    var maxInput = form.querySelector('.wcb-filter__range--max');
    if (minInput) data.append('wcb_min', minInput.value);
    if (maxInput) data.append('wcb_max', maxInput.value);

    // Send AJAX
    fetch(ajaxUrl, {
      method: 'POST',
      body: data,
    })
    .then(function (res) { return res.json(); })
    .then(function (response) {
      if (!response || !response.success) {
        clearGridTransitionStyles();
        return;
      }

      // Fade out
      grid.style.opacity = '0';
      grid.style.transform = 'translateY(8px)';

      setTimeout(function () {
        try {
          var existingList = grid.querySelector('ul.products');
          var existingNoResults = grid.querySelector('.wcb-filter__no-results');
          var existingPagination = grid.querySelector('.woocommerce-pagination');

          if (existingList) existingList.remove();
          if (existingNoResults) existingNoResults.remove();
          if (existingPagination) existingPagination.remove();

          if (!response.data || typeof response.data.html !== 'string') {
            clearGridTransitionStyles();
            return;
          }

          grid.insertAdjacentHTML('beforeend', response.data.html);

          updateCount(
            response.data.total,
            response.data.visible_count,
            response.data.result_capped
          );

          grid.style.opacity = '1';
          grid.style.transform = 'translateY(0)';
        } catch (e) {
          clearGridTransitionStyles();
          console.error('WCB Filter DOM error:', e);
        }
      }, 200);
    })
    .catch(function (err) {
      console.error('WCB Filter error:', err);
      clearGridTransitionStyles();
    })
    .finally(function () {
      isLoading = false;
      grid.classList.remove('wcb-filter--loading');
      var applyBtn = document.getElementById('wcb-filter-apply');
      if (applyBtn) applyBtn.classList.remove('wcb-filter__btn--loading');
    });
  }

  function updateCount(total, visibleCount, resultCapped) {
    if (!countTextEl) return;

    total = typeof total === 'number' ? total : parseInt(total, 10) || 0;
    visibleCount =
      typeof visibleCount === 'number' ? visibleCount : parseInt(visibleCount, 10);
    if (isNaN(visibleCount)) {
      visibleCount = total;
    }
    resultCapped = !!resultCapped;

    var text;
    if (resultCapped && total > visibleCount) {
      text =
        'Mostrando ' +
        visibleCount +
        ' de ' +
        total +
        ' produto' +
        (total !== 1 ? 's' : '');
    } else {
      text = total + ' produto' + (total !== 1 ? 's' : '');
    }
    countTextEl.textContent = text;

    // Also update the unified toolbar count
    var toolbarCount = document.getElementById('wcb-toolbar-count');
    if (toolbarCount) {
      toolbarCount.textContent = text;
      toolbarCount.classList.add('wcb-shop__toolbar-count--pulse');
      setTimeout(function () {
        toolbarCount.classList.remove('wcb-shop__toolbar-count--pulse');
      }, 600);
    }

    // Animate the sidebar count
    if (countEl) {
      countEl.classList.add('wcb-filter__count--pulse');
      setTimeout(function () {
        countEl.classList.remove('wcb-filter__count--pulse');
      }, 600);
    }
  }

  /* ── Clear all filters ─────────────────────────────────────── */
  function initClear() {
    var clearBtn = document.getElementById('wcb-filter-clear');
    if (!clearBtn) return;

    clearBtn.addEventListener('click', function () {
      // Uncheck all checkboxes
      form.querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) {
        cb.checked = false;
      });

      // Reset radio to "Todos"
      var allRadio = form.querySelector('input[type="radio"][value=""]');
      if (allRadio) allRadio.checked = true;

      // Reset price slider
      var minInput = form.querySelector('.wcb-filter__range--min');
      var maxInput = form.querySelector('.wcb-filter__range--max');
      if (minInput) {
        minInput.value = minInput.min;
        minInput.dispatchEvent(new Event('input'));
      }
      if (maxInput) {
        maxInput.value = maxInput.max;
        maxInput.dispatchEvent(new Event('input'));
      }

      // Run AJAX with cleared filters
      runAjaxFilter();
    });
  }

})();
