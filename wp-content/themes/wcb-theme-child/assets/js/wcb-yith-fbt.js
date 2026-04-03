/**
 * YITH FBT — total, lead, PIX, CTA, variações, quantidade em addons, submit AJAX (child).
 */
(function ($) {
	'use strict';

	function formatMoney(value) {
		var cur = (typeof wcbFbt !== 'undefined' && wcbFbt.currency) ? wcbFbt.currency : 'BRL';
		try {
			return new Intl.NumberFormat('pt-BR', {
				style: 'currency',
				currency: cur
			}).format(value);
		} catch (e) {
			return 'R$ ' + value.toFixed(2).replace('.', ',');
		}
	}

	function parseVariations($row) {
		var raw = $row.attr('data-wcb-variations');
		if (!raw) {
			return [];
		}
		try {
			return JSON.parse(raw);
		} catch (e) {
			return [];
		}
	}

	function findVarData(list, variationId) {
		var vid = parseInt(variationId, 10);
		var i;
		for (i = 0; i < list.length; i++) {
			if (parseInt(list[i].variation_id, 10) === vid) {
				return list[i];
			}
		}
		return null;
	}

	function getRowMaxQty($row) {
		var m = parseInt($row.attr('data-wcb-fbt-max-qty'), 10);
		if (isNaN(m) || m < 1) {
			return 99;
		}
		return m;
	}

	/**
	 * Quantidade do produto principal no buybox da PDP (alinhada ao combo).
	 */
	function getPdpMainQty($form) {
		var $ctx = $form.closest('.wcb-pdp-hero__summary, .wcb-pdp-hero, .wcb-pdp');
		var $inp = $ctx.find('.wcb-pdp-buybox__form input.qty').first();
		if (!$inp.length) {
			$inp = $('.wcb-pdp-buybox__form input.qty').first();
		}
		if (!$inp.length) {
			return 1;
		}
		var q = parseInt($inp.val(), 10);
		if (isNaN(q) || q < 1) {
			return 1;
		}
		return q;
	}

	function getAddonQty($row) {
		var $in = $row.find('[data-wcb-fbt-qty-input]');
		if (!$in.length) {
			return 1;
		}
		var q = parseInt($in.val(), 10);
		if (isNaN(q) || q < 1) {
			return 1;
		}
		var max = getRowMaxQty($row);
		return Math.min(max, q);
	}

	function setQtyDisabled($row, disabled) {
		var $wrap = $row.find('[data-wcb-fbt-qty-wrap]');
		var $in = $row.find('[data-wcb-fbt-qty-input]');
		var $btns = $row.find('[data-wcb-fbt-qty-dec], [data-wcb-fbt-qty-inc]');
		$wrap.toggleClass('is-disabled', disabled);
		$in.prop('disabled', disabled);
		$btns.prop('disabled', disabled);
	}

	function clampQtyInput($row) {
		var $in = $row.find('[data-wcb-fbt-qty-input]');
		if (!$in.length) {
			return;
		}
		var max = getRowMaxQty($row);
		$in.attr('max', String(max));
		var v = parseInt($in.val(), 10);
		if (isNaN(v) || v < 1) {
			v = 1;
		}
		if (v > max) {
			v = max;
		}
		$in.val(String(v));
	}

	function applyVariation($form, $row, variationId) {
		var list = parseVariations($row);
		var d = findVarData(list, variationId);
		if (!d) {
			return;
		}
		$row.attr('data-wcb-price', String(d.price));
		var maxQ = typeof d.max_qty !== 'undefined' ? parseInt(d.max_qty, 10) : getRowMaxQty($row);
		if (isNaN(maxQ) || maxQ < 1) {
			maxQ = 99;
		}
		$row.attr('data-wcb-fbt-max-qty', String(maxQ));
		$row.attr('data-wcb-fbt-line-id', String(d.variation_id));

		$row.find('[data-wcb-fbt-price-wrap]').html(d.price_html);

		$row.find('.wcb-fbt__var-chip').each(function () {
			var $b = $(this);
			var id = $b.attr('data-wcb-fbt-var-pick');
			var active = String(id) === String(d.variation_id);
			$b.toggleClass('is-active', active);
			$b.attr('aria-checked', active ? 'true' : 'false');
		});

		var $sel = $row.find('.wcb-fbt__var-select');
		if ($sel.length) {
			$sel.val(String(d.variation_id));
		}

		clampQtyInput($row);
		recalc($form);
	}

	function recalc($form) {
		var total = 0;
		var mainQty = getPdpMainQty($form);
		$form.find('[data-wcb-fbt-row]').each(function () {
			var $row = $(this);
			var price = parseFloat($row.attr('data-wcb-price'));
			if (isNaN(price)) {
				return;
			}
			var $cb = $row.find('.wcb-fbt__cb');
			if ($cb.length === 0) {
				total += price * mainQty;
			} else if ($cb.prop('checked')) {
				var q = $row.is('[data-wcb-fbt-addon]') ? getAddonQty($row) : 1;
				total += price * q;
			}
		});

		var n = 1 + $form.find('.wcb-fbt__cb:checked').length;
		var money = formatMoney(total);
		var pixMult = (typeof wcbFbt !== 'undefined' && wcbFbt.pixPercent) ? wcbFbt.pixPercent : 0.95;
		var pixVal = Math.round(total * pixMult * 100) / 100;
		var pixStr = formatMoney(pixVal);

		$form.find('[data-wcb-fbt-total]').text(money);

		var $section = $form.closest('.wcb-fbt');
		var $leadP = $section.find('[data-wcb-fbt-lead-primary]');
		var $leadCard = $section.find('[data-wcb-fbt-lead-card]');
		if ($leadP.length && typeof wcbFbt !== 'undefined') {
			if ($leadCard.length && wcbFbt.i18nLeadPix && wcbFbt.i18nLeadCard && total > 0) {
				$leadP.text(wcbFbt.i18nLeadPix.replace('%s', pixStr));
				$leadCard.text(wcbFbt.i18nLeadCard.replace('%s', money));
			} else if (wcbFbt.i18nLeadCombo) {
				$leadP.text(wcbFbt.i18nLeadCombo.replace('%s', money));
			}
		}

		var $btn = $form.find('[data-wcb-fbt-submit]');
		var def = $btn.attr('data-wcb-default-label') || '';
		if (typeof wcbFbt !== 'undefined' && wcbFbt.i18nCtaCombo && n > 1) {
			$btn.text(wcbFbt.i18nCtaCombo);
		} else if (typeof wcbFbt !== 'undefined' && wcbFbt.i18nCtaSingle && n === 1) {
			$btn.text(wcbFbt.i18nCtaSingle);
		} else {
			$btn.text(def);
		}
	}

	function bindRowToggle($form) {
		$form.on('click', '[data-wcb-fbt-row-toggle]', function (e) {
			var $target = $(e.target);
			if ($target.closest('a').length) {
				return;
			}
			if ($target.closest('.wcb-fbt__var').length) {
				return;
			}
			if ($target.closest('[data-wcb-fbt-qty-wrap]').length) {
				return;
			}
			if ($target.closest('label.wcb-fbt__check').length) {
				return;
			}
			if ($target.is('input.wcb-fbt__cb')) {
				return;
			}
			var $cb = $(this).find('.wcb-fbt__cb').first();
			if (!$cb.length) {
				return;
			}
			$cb.prop('checked', !$cb.prop('checked')).trigger('change');
		});
	}

	function bindVariableRows($form) {
		$form.on('click', '[data-wcb-fbt-var-pick]', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var id = $(this).attr('data-wcb-fbt-var-pick');
			var $row = $(this).closest('[data-wcb-fbt-variable-row]');
			if (!$row.length || !id) {
				return;
			}
			applyVariation($form, $row, id);
		});

		$form.on('change', '.wcb-fbt__var-select', function () {
			var $row = $(this).closest('[data-wcb-fbt-variable-row]');
			var id = $(this).val();
			if ($row.length && id) {
				applyVariation($form, $row, id);
			}
		});
	}

	function bindQty($form) {
		$form.on('click', '[data-wcb-fbt-qty-dec]', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $row = $(this).closest('[data-wcb-fbt-row]');
			var $in = $row.find('[data-wcb-fbt-qty-input]');
			var v = getAddonQty($row) - 1;
			if (v < 1) {
				v = 1;
			}
			$in.val(String(v));
			recalc($form);
		});

		$form.on('click', '[data-wcb-fbt-qty-inc]', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $row = $(this).closest('[data-wcb-fbt-row]');
			var $in = $row.find('[data-wcb-fbt-qty-input]');
			var max = getRowMaxQty($row);
			var v = getAddonQty($row) + 1;
			if (v > max) {
				v = max;
			}
			$in.val(String(v));
			recalc($form);
		});

		$form.on('change input blur', '[data-wcb-fbt-qty-input]', function () {
			var $row = $(this).closest('[data-wcb-fbt-row]');
			clampQtyInput($row);
			recalc($form);
		});
	}

	function collectComboPayload($form) {
		var mainId = parseInt($form.attr('data-wcb-fbt-main-id'), 10);
		if (isNaN(mainId) || mainId < 1) {
			return null;
		}
		var items = [];
		$form.find('[data-wcb-fbt-addon]').each(function () {
			var $row = $(this);
			var $cb = $row.find('.wcb-fbt__cb');
			if (!$cb.length || !$cb.prop('checked')) {
				return;
			}
			var lineId = parseInt($row.attr('data-wcb-fbt-line-id'), 10);
			if (isNaN(lineId) || lineId < 1) {
				return;
			}
			var qty = getAddonQty($row);
			items.push({ id: lineId, qty: qty });
		});
		return { mainId: mainId, items: items };
	}

	function getFbtSection($form) {
		return $form.closest('.wcb-fbt');
	}

	function setFeedback($form, type, message) {
		var $w = getFbtSection($form).find('[data-wcb-fbt-feedback]');
		if (!$w.length) {
			return;
		}
		$w.removeClass('wcb-fbt__feedback--error wcb-fbt__feedback--success wcb-fbt__feedback--loading');
		if (!type || type === 'clear') {
			$w.addClass('is-empty').text('');
			return;
		}
		$w.removeClass('is-empty').addClass('wcb-fbt__feedback--' + type);
		$w.text(message || '');
	}

	function resetSubmitUi($form, $btn, label) {
		$form.data('wcbFbtSubmitting', false);
		getFbtSection($form).removeClass('is-loading');
		$btn.removeClass('is-loading');
		$btn.prop('disabled', false);
		if (typeof label === 'string') {
			$btn.text(label);
		}
	}

	function bindAjaxSubmit($form) {
		$form.on('submit', function (e) {
			e.preventDefault();
			if ($form.data('wcbFbtSubmitting')) {
				return;
			}
			var payload = collectComboPayload($form);
			if (!payload) {
				return;
			}
			var $btn = $form.find('[data-wcb-fbt-submit]');
			var label = $btn.text();
			var nonce = $form.find('input[name="wcb_fbt_nonce"]').val();
			if (typeof wcbFbt === 'undefined' || !wcbFbt.ajaxUrl || !nonce) {
				return;
			}

			$form.data('wcbFbtSubmitting', true);
			getFbtSection($form).addClass('is-loading');
			$btn.addClass('is-loading');
			$btn.prop('disabled', true);
			if (wcbFbt.i18nSubmitting) {
				$btn.text(wcbFbt.i18nSubmitting);
			}
			setFeedback($form, 'clear');
			var loadingMsg = wcbFbt.i18nFeedbackLoading || wcbFbt.i18nSubmitting || '';
			if (loadingMsg) {
				setFeedback($form, 'loading', loadingMsg);
			}

			var mainQtySubmit = getPdpMainQty($form);

			$.ajax({
				url: wcbFbt.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wcb_fbt_add_combo',
					wcb_fbt_nonce: nonce,
					main_id: payload.mainId,
					main_qty: mainQtySubmit,
					items: JSON.stringify(payload.items)
				}
			})
				.done(function (r) {
					var data = (r && r.data) ? r.data : {};
					if (r && r.success && data.redirect) {
						var okMsg = data.message || wcbFbt.i18nComboSuccess || '';
						if (okMsg) {
							setFeedback($form, 'success', okMsg);
						}
						window.location.href = data.redirect;
						return;
					}
					var msg = data.message || (wcbFbt.i18nSubmitError || '');
					if (!r || !r.success) {
						if (!msg) {
							msg = wcbFbt.i18nSubmitError || '';
						}
					}
					setFeedback($form, 'error', msg);
					resetSubmitUi($form, $btn, label);
				})
				.fail(function (xhr) {
					var d = (xhr.responseJSON && xhr.responseJSON.data) ? xhr.responseJSON.data : {};
					var msg = d.message || wcbFbt.i18nSubmitError || '';
					setFeedback($form, 'error', msg);
					resetSubmitUi($form, $btn, label);
				});
		});
	}

	$(function () {
		$('[data-wcb-fbt-form]').each(function () {
			var $form = $(this);
			$form.find('[data-wcb-fbt-addon]').each(function () {
				var $row = $(this);
				var $cb = $row.find('.wcb-fbt__cb');
				setQtyDisabled($row, !$cb.prop('checked'));
				clampQtyInput($row);
			});
			recalc($form);
			bindRowToggle($form);
			bindVariableRows($form);
			bindQty($form);
			bindAjaxSubmit($form);
			$form.on('change', '.wcb-fbt__cb', function () {
				var $row = $(this).closest('[data-wcb-fbt-row]');
				if ($row.is('[data-wcb-fbt-addon]')) {
					setQtyDisabled($row, !$(this).prop('checked'));
					if ($(this).prop('checked')) {
						clampQtyInput($row);
					}
				}
				recalc($form);
			});
		});

		$(document).on('change input', '.wcb-pdp-buybox__form input.qty', function () {
			var $ctx = $(this).closest('.wcb-pdp-hero__summary, .wcb-pdp-hero, .wcb-pdp, body');
			$ctx.find('[data-wcb-fbt-form]').each(function () {
				recalc($(this));
			});
		});
	});
})(jQuery);
