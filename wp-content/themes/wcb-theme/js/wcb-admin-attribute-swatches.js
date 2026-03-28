/**
 * Admin: sincroniza color picker + hex e modal de media para swatches de atributo (pa_*).
 */
(function ($) {
    'use strict';

    function hexToSix(hex) {
        if (!hex || typeof hex !== 'string') {
            return '';
        }
        hex = hex.trim();
        if (hex.charAt(0) !== '#') {
            hex = '#' + hex;
        }
        if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
            return hex.toLowerCase();
        }
        if (/^#[0-9A-Fa-f]{3}$/.test(hex)) {
            var s = hex.slice(1);
            return ('#' + s.charAt(0) + s.charAt(0) + s.charAt(1) + s.charAt(1) + s.charAt(2) + s.charAt(2)).toLowerCase();
        }
        return '';
    }

    function initColorRow($row) {
        var $text = $row.find('input[name="wcb_swatch_color"]');
        var $picker = $row.find('.wcb-swatch-color-picker');
        var $clear = $row.find('.wcb-swatch-color-clear');

        function syncPickerFromText() {
            var v = hexToSix($text.val());
            if (v) {
                $picker.val(v);
            } else {
                $picker.val('#ffffff');
            }
        }

        $picker.on('input change', function () {
            $text.val($(this).val());
        });
        $text.on('input change blur', syncPickerFromText);
        syncPickerFromText();

        $clear.on('click', function (e) {
            e.preventDefault();
            $text.val('');
            $picker.val('#ffffff');
        });
    }

    function initImageBlock($block) {
        var $input = $block.find('input[name="wcb_swatch_image"]');
        var $preview = $block.find('.wcb-swatch-image-preview');
        var $btn = $block.find('.wcb-swatch-media-btn');
        var $clear = $block.find('.wcb-swatch-image-clear');

        function showPreview(url) {
            url = (url || '').trim();
            if (!url) {
                $preview.empty();
                return;
            }
            var safe = url.replace(/"/g, '&quot;').replace(/</g, '');
            $preview.html(
                '<img src="' + safe + '" alt="" class="wcb-swatch-image-preview__img" />'
            );
        }

        $btn.on('click', function (e) {
            e.preventDefault();
            var frame = wp.media({
                title: (wcbSwatchAdmin && wcbSwatchAdmin.i18n && wcbSwatchAdmin.i18n.choose) || '',
                button: {
                    text: (wcbSwatchAdmin && wcbSwatchAdmin.i18n && wcbSwatchAdmin.i18n.use) || '',
                },
                multiple: false,
                library: { type: 'image' },
            });
            frame.on('select', function () {
                var att = frame.state().get('selection').first().toJSON();
                var url = att.url || '';
                $input.val(url).trigger('change');
                showPreview(url);
            });
            frame.open();
        });

        $clear.on('click', function (e) {
            e.preventDefault();
            $input.val('');
            showPreview('');
        });

        showPreview($input.val());
    }

    $(function () {
        $('.wcb-swatch-color-row').each(function () {
            initColorRow($(this));
        });
        $('.wcb-swatch-image-block').each(function () {
            initImageBlock($(this));
        });
    });
})(jQuery);
