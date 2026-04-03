/**
 * YITH FBT — total, lead, PIX, CTA, linha opcional, card variável (offeringID[]).
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

	function syncVariableOfferingHidden($row) {
		var $h = $row.find('.wcb-fbt__offering-hidden');
		if (!$h.length) {
			return;
		}
		var $cb = $row.find('.wcb-fbt__cb').first();
		$h.prop('disabled', !$cb.prop('checked'));
	}

	function applyVariation($form, $row, variationId) {
		var list = parseVariations($row);
		var d = findVarData(list, variationId);
		if (!d) {
			return;
		}
		$row.attr('data-wcb-price', String(d.price));
		$row.find('[data-wcb-fbt-price-wrap]').html(d.price_html);
		$row.find('.wcb-fbt__offering-hidden').val(String(d.variation_id));

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

		recalc($form);
	}

	function recalc($form) {
		var total = 0;
		$form.find('[data-wcb-fbt-row]').each(function () {
			var $row = $(this);
			var price = parseFloat($row.attr('data-wcb-price'));
			if (isNaN(price)) {
				return;
			}
			var $cb = $row.find('.wcb-fbt__cb');
			if ($cb.length === 0) {
				total += price;
			} else if ($cb.prop('checked')) {
				total += price;
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
		if ($leadP.length && typeof wcbFbt !== 'undefined') {
			if (n === 1 && wcbFbt.i18nLeadOne) {
				$leadP.text(wcbFbt.i18nLeadOne.replace('%s', money));
			} else if (n > 1 && wcbFbt.i18nLeadMany) {
				$leadP.text(wcbFbt.i18nLeadMany.replace('%d', String(n)).replace('%s', money));
			}
		}

		var $leadPix = $section.find('[data-wcb-fbt-lead-pix]');
		if ($leadPix.length && typeof wcbFbt !== 'undefined' && wcbFbt.i18nLeadPix) {
			$leadPix.text(wcbFbt.i18nLeadPix.replace('%s', pixStr));
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

	$(function () {
		$('[data-wcb-fbt-form]').each(function () {
			var $form = $(this);
			$form.find('[data-wcb-fbt-variable-row]').each(function () {
				syncVariableOfferingHidden($(this));
			});
			recalc($form);
			bindRowToggle($form);
			bindVariableRows($form);
			$form.on('change', '.wcb-fbt__cb', function () {
				var $row = $(this).closest('[data-wcb-fbt-row]');
				syncVariableOfferingHidden($row);
				recalc($form);
			});
		});
	});
})(jQuery);
