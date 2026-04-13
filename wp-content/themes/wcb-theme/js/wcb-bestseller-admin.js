/**
 * Admin — Lista manual do selo "Mais vendido" (SelectWoo + AJAX).
 */
(function ($) {
	'use strict';

	function getCheckboxName() {
		var k = (window.wcbBestsellerAdmin && wcbBestsellerAdmin.optionKey) || 'wcb_bestseller';
		return k + '[manual_product_ids][]';
	}

	function listHasId(canonicalId) {
		var id = String(canonicalId);
		var found = false;
		$('#wcb-bs-manual-list input[type="checkbox"]').each(function () {
			if (String(this.value) === id) {
				found = true;
				return false;
			}
		});
		return found;
	}

	function addRow(canonicalId, labelText) {
		if (listHasId(canonicalId)) {
			return;
		}
		var name = getCheckboxName();
		var $li = $('<li class="wcb-bs-manual-item" />').css({
			display: 'flex',
			alignItems: 'center',
			gap: '8px',
			marginBottom: '6px',
		});
		var $label = $('<label />').css({
			display: 'flex',
			alignItems: 'center',
			gap: '6px',
			flex: '1',
		});
		$label.append(
			$('<input type="checkbox" />')
				.attr('name', name)
				.attr('value', String(canonicalId))
				.prop('checked', true)
		);
		$label.append($('<span />').text(labelText));
		$li.append($label);
		var removeLabel =
			(window.wcbBestsellerAdmin && wcbBestsellerAdmin.i18n && wcbBestsellerAdmin.i18n.remove) ||
			'Remover';
		$li.append(
			$('<button type="button" class="button-link wcb-bs-remove" />')
				.attr('aria-label', removeLabel)
				.text(removeLabel)
		);
		$('#wcb-bs-manual-list').append($li);
	}

	$(function () {
		var $sel = $('#wcb-bs-product-search');
		if (!$sel.length || !window.wcbBestsellerAdmin) {
			return;
		}

		var ph =
			(wcbBestsellerAdmin.i18n && wcbBestsellerAdmin.i18n.searchPlaceholder) ||
			$sel.data('placeholder') ||
			'';

		if (!wcbBestsellerAdmin.selectWooOk) {
			return;
		}

		if (typeof $sel.selectWoo !== 'function') {
			var miss =
				(wcbBestsellerAdmin.i18n && wcbBestsellerAdmin.i18n.selectWooMissing) || '';
			if (miss) {
				var $w = $('<div class="notice notice-error inline" style="padding:8px 12px;margin-bottom:10px;" />');
				$w.append($('<p style="margin:0;" />').text(miss));
				$sel.before($w);
			}
			return;
		}

		$sel.selectWoo({
			allowClear: true,
			placeholder: ph,
			width: '100%',
			minimumInputLength: 1,
			language: {
				noResults: function () {
					return (
						(wcbBestsellerAdmin.i18n && wcbBestsellerAdmin.i18n.noResults) ||
						'Nenhum produto encontrado.'
					);
				},
			},
			ajax: {
				url: wcbBestsellerAdmin.ajaxUrl,
				dataType: 'json',
				delay: 300,
				data: function (params) {
					return {
						action: 'wcb_bestseller_search_products',
						nonce: wcbBestsellerAdmin.nonce,
						term: params.term || '',
					};
				},
				processResults: function (response) {
					if (!response || !response.results) {
						return { results: [] };
					}
					return { results: response.results };
				},
			},
		});

		$sel.on('select2:select', function (e) {
			var d = e.params.data;
			if (!d || d.id == null) {
				return;
			}
			addRow(d.id, d.text || String(d.id));
			$sel.val(null).trigger('change');
		});

		$('#wcb-bs-manual-list').on('click', '.wcb-bs-remove', function () {
			$(this).closest('li').remove();
		});
	});
})(jQuery);
