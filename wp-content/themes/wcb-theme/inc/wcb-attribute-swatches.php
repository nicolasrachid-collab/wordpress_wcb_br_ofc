<?php
/**
 * Swatches por termo: cor (hex) e/ou imagem (URL), JSON na PDP para o JS do tema.
 *
 * @package WCB_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Meta keys armazenados em cada termo de atributo (pa_*). */
define('WCB_SWATCH_COLOR_META', 'wcb_swatch_color');
define('WCB_SWATCH_IMAGE_META', 'wcb_swatch_image');

/**
 * Normaliza cor para #RRGGBB aceita por sanitize_hex_color ou string vazia.
 */
function wcb_sanitize_swatch_color($color)
{
    $color = is_string($color) ? trim($color) : '';
    if ($color === '') {
        return '';
    }
    if (strpos($color, '#') !== 0) {
        $color = '#' . $color;
    }
    $hex = sanitize_hex_color($color);
    if ($hex) {
        return $hex;
    }
    if (preg_match('/^#([a-f0-9]{3})$/i', $color, $m)) {
        $s = $m[1];
        return '#' . $s[0] . $s[0] . $s[1] . $s[1] . $s[2] . $s[2];
    }
    return '';
}

/**
 * Normaliza rótulo/slug para bater com as chaves do mapa de cores.
 */
function wcb_swatch_normalize_key($string)
{
    $string = remove_accents((string) $string);
    $string = strtolower(trim($string));
    $string = preg_replace('/\s+/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Aliases cor → hex (PT/EN). Chaves em forma normalizada (minúsculas, hífens).
 */
function wcb_swatch_color_alias_map()
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $pairs = array(
        // Neutros
        'preto' => '#1a1a1a',
        'black' => '#1a1a1a',
        'negro' => '#1a1a1a',
        'branco' => '#f5f5f5',
        'white' => '#f5f5f5',
        'branca' => '#f5f5f5',
        'cinza' => '#6b7280',
        'cinzento' => '#6b7280',
        'gray' => '#6b7280',
        'grey' => '#6b7280',
        'gris' => '#6b7280',
        'prata' => '#c8c9d0',
        'silver' => '#c8c9d0',
        'prateado' => '#c8c9d0',
        'prateada' => '#c8c9d0',
        'off-white' => '#f8f8f6',
        'offwhite' => '#f8f8f6',
        'bege' => '#d6c4a8',
        'beige' => '#d6c4a8',
        'creme' => '#faf8f0',
        'cream' => '#faf8f0',
        'marfim' => '#fffff0',
        'ivory' => '#fffff0',
        'champagne' => '#f3e5c9',
        // Metais / especiais
        'dourado' => '#d4af37',
        'gold' => '#d4af37',
        'ouro' => '#d4af37',
        'rose-gold' => '#b76e79',
        'rosegold' => '#b76e79',
        'ouro-rose' => '#b76e79',
        'cobre' => '#b87333',
        'copper' => '#b87333',
        'bronze' => '#8c6239',
        // Vermelhos / rosas
        'vermelho' => '#dc2626',
        'vermelha' => '#dc2626',
        'red' => '#dc2626',
        'bordo' => '#7f1d1d',
        'bordô' => '#7f1d1d',
        'burgundy' => '#7f1d1d',
        'vinho' => '#722f37',
        'wine' => '#722f37',
        'rosa' => '#ec4899',
        'pink' => '#ec4899',
        'coral' => '#fb7185',
        'salmao' => '#fda4af',
        'salmão' => '#fda4af',
        'salmon' => '#fda4af',
        // Laranja / amarelo
        'laranja' => '#ea580c',
        'orange' => '#ea580c',
        'amarelo' => '#facc15',
        'yellow' => '#facc15',
        'amarela' => '#facc15',
        'mostarda' => '#ca8a04',
        'mustard' => '#ca8a04',
        // Verdes
        'verde' => '#16a34a',
        'green' => '#16a34a',
        'verde-limao' => '#84cc16',
        'verde-limão' => '#84cc16',
        'lime' => '#84cc16',
        'lima' => '#84cc16',
        'verde-escuro' => '#14532d',
        'dark-green' => '#14532d',
        'verde-claro' => '#86efac',
        'light-green' => '#86efac',
        'menta' => '#6ee7b7',
        'mint' => '#6ee7b7',
        'oliva' => '#6b7c3f',
        'olive' => '#6b7c3f',
        'esmeralda' => '#059669',
        'emerald' => '#059669',
        // Azuis
        'azul' => '#2563eb',
        'blue' => '#2563eb',
        'azul-claro' => '#38bdf8',
        'light-blue' => '#38bdf8',
        'azul-escuro' => '#1e3a8a',
        'dark-blue' => '#1e3a8a',
        'navy' => '#1e3a8a',
        'azul-marinho' => '#1e3a8a',
        'marinho' => '#1e3a8a',
        'turquesa' => '#06b6d4',
        'turquoise' => '#06b6d4',
        'ciano' => '#22d3ee',
        'cyan' => '#22d3ee',
        'teal' => '#0d9488',
        // Roxos
        'roxo' => '#7c3aed',
        'purple' => '#7c3aed',
        'violeta' => '#8b5cf6',
        'violet' => '#8b5cf6',
        'lilas' => '#c084fc',
        'lilás' => '#c084fc',
        'lilac' => '#c084fc',
        'lavanda' => '#e9d5ff',
        'lavender' => '#e9d5ff',
        // Marrons
        'marrom' => '#78350f',
        'brown' => '#78350f',
        'castanho' => '#78350f',
        'cafe' => '#5c4033',
        'café' => '#5c4033',
        'coffee' => '#5c4033',
        'chocolate' => '#5d3a1a',
        'caramelo' => '#b45309',
        'caramel' => '#b45309',
        'caqui' => '#c3b091',
        'khaki' => '#c3b091',
        'nude' => '#e8d4c4',
        'tan' => '#d2b48c',
        // Utilitários PDP comuns
        'multicolor' => '#6366f1',
        'multicolorido' => '#6366f1',
        'multicor' => '#6366f1',
        'transparente' => '#e5e7eb',
        'transparent' => '#e5e7eb',
        'cristal' => '#dbeafe',
        'crystal' => '#dbeafe',
        'natural' => '#d4c4a8',
        'wood' => '#a16207',
        'madeira' => '#a16207',
    );

    $map = array();
    foreach ($pairs as $alias => $hex) {
        $key = wcb_swatch_normalize_key($alias);
        if ($key !== '') {
            $map[$key] = wcb_sanitize_swatch_color($hex);
        }
    }

    return $map;
}

/**
 * Infere hex a partir do nome/slug do termo (sem gravar).
 */
function wcb_guess_swatch_hex_for_term($term)
{
    if (!$term instanceof WP_Term) {
        return '';
    }

    $map = wcb_swatch_color_alias_map();
    if (empty($map)) {
        return '';
    }

    $slug_key = wcb_swatch_normalize_key($term->slug);
    if ($slug_key !== '' && isset($map[$slug_key])) {
        return $map[$slug_key];
    }

    $name_key = wcb_swatch_normalize_key($term->name);
    if ($name_key !== '' && isset($map[$name_key])) {
        return $map[$name_key];
    }

    $tokens = array_filter(explode('-', $name_key));
    $n = count($tokens);
    for ($i = 0; $i < $n - 1; $i++) {
        $pair = $tokens[$i] . '-' . $tokens[$i + 1];
        if (isset($map[$pair])) {
            return $map[$pair];
        }
    }
    foreach ($tokens as $tok) {
        if ($tok !== '' && isset($map[$tok])) {
            return $map[$tok];
        }
    }

    return '';
}

/**
 * Preenche meta de cor em termos de atributo que ainda estão vazios.
 */
function wcb_backfill_empty_swatch_colors()
{
    if (!function_exists('wc_get_attribute_taxonomies')) {
        return 0;
    }

    $updated = 0;
    foreach (wc_get_attribute_taxonomies() as $attr) {
        $taxonomy = wc_attribute_taxonomy_name($attr->attribute_name);
        if (!$taxonomy || !taxonomy_exists($taxonomy)) {
            continue;
        }

        $terms = get_terms(
            array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            )
        );

        if (is_wp_error($terms) || empty($terms)) {
            continue;
        }

        foreach ($terms as $term) {
            $existing = get_term_meta($term->term_id, WCB_SWATCH_COLOR_META, true);
            if (is_string($existing) && trim($existing) !== '') {
                continue;
            }

            $hex = wcb_guess_swatch_hex_for_term($term);
            if ($hex !== '') {
                update_term_meta($term->term_id, WCB_SWATCH_COLOR_META, $hex);
                $updated++;
            }
        }
    }

    return $updated;
}

/**
 * Sobrescreve wcb_swatch_color em todos os termos pa_* quando o mapa reconhece nome/slug.
 * Não altera termos sem correspondência (mantém hex ou vazio atual).
 *
 * @return int Quantidade de termos atualizados.
 */
function wcb_force_reapply_all_swatch_guessed_colors()
{
    if (!function_exists('wc_get_attribute_taxonomies')) {
        return 0;
    }

    $updated = 0;
    foreach (wc_get_attribute_taxonomies() as $attr) {
        $taxonomy = wc_attribute_taxonomy_name($attr->attribute_name);
        if (!$taxonomy || !taxonomy_exists($taxonomy)) {
            continue;
        }

        $terms = get_terms(
            array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            )
        );

        if (is_wp_error($terms) || empty($terms)) {
            continue;
        }

        foreach ($terms as $term) {
            $hex = wcb_guess_swatch_hex_for_term($term);
            if ($hex === '') {
                continue;
            }
            update_term_meta($term->term_id, WCB_SWATCH_COLOR_META, $hex);
            $updated++;
        }
    }

    return $updated;
}

/**
 * Admin: Produtos → Reaplicar cores swatch.
 */
function wcb_swatch_register_reapply_admin_page()
{
    if (!function_exists('wc_get_attribute_taxonomies')) {
        return;
    }

    add_submenu_page(
        'edit.php?post_type=product',
        __('Reaplicar cores dos swatches', 'wcb-theme'),
        __('Reaplicar cores swatch', 'wcb-theme'),
        'edit_products',
        'wcb-reapply-swatch-colors',
        'wcb_swatch_reapply_admin_page_render'
    );
}

add_action('admin_menu', 'wcb_swatch_register_reapply_admin_page', 99);

function wcb_swatch_reapply_admin_page_render()
{
    if (!current_user_can('edit_products')) {
        wp_die(esc_html__('Sem permissão.', 'wcb-theme'));
    }

    $notice = '';
    $notice_type = 'success';

    if (isset($_POST['wcb_swatch_force_reapply']) && isset($_POST['wcb_swatch_force_reapply_nonce'])) {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wcb_swatch_force_reapply_nonce'])), 'wcb_swatch_force_reapply_action')) {
            $notice = esc_html__('Pedido inválido. Tente de novo.', 'wcb-theme');
            $notice_type = 'error';
        } else {
            $count = wcb_force_reapply_all_swatch_guessed_colors();
            $notice = esc_html(
                sprintf(
                    /* translators: %d: number of terms updated */
                    _n(
                        'Cor do mapa aplicada em %d termo de atributo.',
                        'Cor do mapa aplicada em %d termos de atributo.',
                        $count,
                        'wcb-theme'
                    ),
                    $count
                )
            );
        }
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Reaplicar cores dos swatches', 'wcb-theme'); ?></h1>

        <?php if ($notice !== '') : ?>
            <div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible"><p><?php echo $notice; ?></p></div>
        <?php endif; ?>

        <p><?php esc_html_e('Percorre todos os termos já cadastrados nos atributos de produto (ex.: Cor, Tamanho com taxonomia pa_*) e grava de novo a cor em hexadecimal com base no mapa do tema (nome e slug), mesmo que já exista uma cor guardada.', 'wcb-theme'); ?></p>
        <p><strong><?php esc_html_e('Imagens de swatch (URL) não são alteradas.', 'wcb-theme'); ?></strong></p>
        <p><?php esc_html_e('Se o nome do termo não corresponder a nenhuma cor conhecida, esse termo não é modificado.', 'wcb-theme'); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field('wcb_swatch_force_reapply_action', 'wcb_swatch_force_reapply_nonce'); ?>
            <p>
                <button type="submit" name="wcb_swatch_force_reapply" value="1" class="button button-primary"
                    onclick="return confirm('<?php echo esc_js(__('Isto sobrescreve a cor (hex) de todos os termos em que o mapa reconhecer o nome. Continuar?', 'wcb-theme')); ?>');">
                    <?php esc_html_e('Reaplicar agora', 'wcb-theme'); ?>
                </button>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Uma execução após deploy: atribui cores onde ainda não há hex.
 * Para repetir: apagar opção `wcb_swatch_auto_colors_v1` (ex.: em WP-CLI ou plugin de opções).
 */
function wcb_maybe_run_swatch_color_backfill()
{
    if (!is_admin() || wp_doing_ajax()) {
        return;
    }
    if (!function_exists('taxonomy_is_product_attribute')) {
        return;
    }
    if (get_option('wcb_swatch_auto_colors_v1', '')) {
        return;
    }
    if (!current_user_can('edit_products')) {
        return;
    }

    wcb_backfill_empty_swatch_colors();
    update_option('wcb_swatch_auto_colors_v1', 1, false);
}

add_action('admin_init', 'wcb_maybe_run_swatch_color_backfill', 30);

/**
 * Novos termos: sugere cor automaticamente se o utilizador não preencheu.
 */
function wcb_apply_guessed_swatch_on_new_attribute_term($term_id, $tt_id, $taxonomy)
{
    if (!function_exists('taxonomy_is_product_attribute') || !taxonomy_is_product_attribute($taxonomy)) {
        return;
    }

    $existing = get_term_meta($term_id, WCB_SWATCH_COLOR_META, true);
    if (is_string($existing) && trim($existing) !== '') {
        return;
    }

    $term = get_term($term_id, $taxonomy);
    if (!$term || is_wp_error($term)) {
        return;
    }

    $hex = wcb_guess_swatch_hex_for_term($term);
    if ($hex !== '') {
        update_term_meta($term_id, WCB_SWATCH_COLOR_META, $hex);
    }
}

add_action('created_term', 'wcb_apply_guessed_swatch_on_new_attribute_term', 50, 3);

/**
 * Registra campos e save em todas as taxonomias de atributo WooCommerce.
 */
function wcb_register_attribute_swatch_term_hooks()
{
    if (!function_exists('wc_get_attribute_taxonomies')) {
        return;
    }
    foreach (wc_get_attribute_taxonomies() as $tax) {
        $taxonomy = wc_attribute_taxonomy_name($tax->attribute_name);
        if (!$taxonomy || !taxonomy_exists($taxonomy)) {
            continue;
        }
        add_action($taxonomy . '_add_form_fields', 'wcb_swatch_term_add_fields');
        add_action($taxonomy . '_edit_form_fields', 'wcb_swatch_term_edit_fields', 10, 2);
        add_action('created_' . $taxonomy, 'wcb_swatch_save_term_meta', 10, 1);
        add_action('edited_' . $taxonomy, 'wcb_swatch_save_term_meta', 10, 1);
    }
}

add_action('init', 'wcb_register_attribute_swatch_term_hooks', 100);

/**
 * Telas de termos de atributos de produto (lista + edição).
 */
function wcb_is_product_attribute_taxonomy_admin_screen()
{
    if (!function_exists('get_current_screen') || !function_exists('taxonomy_is_product_attribute')) {
        return false;
    }
    $screen = get_current_screen();
    if (!$screen || empty($screen->taxonomy)) {
        return false;
    }
    return taxonomy_is_product_attribute($screen->taxonomy);
}

/**
 * Scripts e estilos do color picker + media modal.
 */
function wcb_swatch_term_admin_assets()
{
    if (!wcb_is_product_attribute_taxonomy_admin_screen()) {
        return;
    }

    wp_enqueue_media();

    $handle = 'wcb-admin-attribute-swatches';
    $src = (defined('WCB_URI') ? WCB_URI : get_template_directory_uri()) . '/js/wcb-admin-attribute-swatches.js';
    $ver = defined('WCB_VERSION') ? WCB_VERSION : '1.0.0';

    wp_enqueue_script(
        $handle,
        $src,
        array('jquery', 'media-editor'),
        $ver,
        true
    );

    wp_localize_script(
        $handle,
        'wcbSwatchAdmin',
        array(
            'i18n' => array(
                'choose' => __('Escolher imagem do swatch', 'wcb-theme'),
                'use' => __('Usar esta imagem', 'wcb-theme'),
            ),
        )
    );

    $css = '.wcb-swatch-color-row{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:6px;}
.wcb-swatch-color-picker{width:44px;height:36px;padding:2px;border:1px solid #8c8f94;border-radius:4px;cursor:pointer;flex-shrink:0;}
.wcb-swatch-image-row{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-top:6px;}
.wcb-swatch-image-row .large-text{flex:1;min-width:220px;}
.wcb-swatch-image-preview{margin-top:10px;}
.wcb-swatch-image-preview__img{max-width:96px;max-height:96px;border-radius:8px;border:1px solid #c3c4c7;object-fit:cover;display:block;}';

    wp_register_style('wcb-swatch-admin', false, array(), $ver);
    wp_enqueue_style('wcb-swatch-admin');
    wp_add_inline_style('wcb-swatch-admin', $css);
}

add_action('admin_enqueue_scripts', 'wcb_swatch_term_admin_assets', 20);

function wcb_swatch_term_add_fields($taxonomy)
{
    wp_nonce_field('wcb_swatch_term_meta', 'wcb_swatch_nonce');
    ?>
    <div class="form-field term-wcb-swatch-wrap">
        <label for="wcb_swatch_color"><?php esc_html_e('Cor do swatch', 'wcb-theme'); ?></label>
        <div class="wcb-swatch-color-row">
            <input type="color" id="wcb_swatch_color_picker" class="wcb-swatch-color-picker"
                value="#ffffff" aria-label="<?php esc_attr_e('Seletor de cor', 'wcb-theme'); ?>" />
            <input type="text" name="wcb_swatch_color" id="wcb_swatch_color" value=""
                placeholder="#2563eb"
                class="regular-text" />
            <button type="button" class="button wcb-swatch-color-clear"><?php esc_html_e('Limpar cor', 'wcb-theme'); ?></button>
        </div>
        <p class="description"><?php esc_html_e('Deixe vazio para mostrar só o texto do termo na PDP.', 'wcb-theme'); ?></p>
    </div>
    <div class="form-field term-wcb-swatch-wrap">
        <label for="wcb_swatch_image"><?php esc_html_e('Imagem do swatch', 'wcb-theme'); ?></label>
        <div class="wcb-swatch-image-block">
            <div class="wcb-swatch-image-row">
                <input type="url" name="wcb_swatch_image" id="wcb_swatch_image" value=""
                    placeholder="https://"
                    class="large-text" />
                <button type="button" class="button wcb-swatch-media-btn"><?php esc_html_e('Biblioteca de media', 'wcb-theme'); ?></button>
                <button type="button" class="button wcb-swatch-image-clear"><?php esc_html_e('Limpar imagem', 'wcb-theme'); ?></button>
            </div>
            <div class="wcb-swatch-image-preview" aria-hidden="true"></div>
        </div>
        <p class="description"><?php esc_html_e('Opcional. Se preenchida, tem prioridade sobre a cor.', 'wcb-theme'); ?></p>
    </div>
    <?php
}

function wcb_swatch_term_edit_fields($term, $taxonomy)
{
    $color = get_term_meta($term->term_id, WCB_SWATCH_COLOR_META, true);
    $image = get_term_meta($term->term_id, WCB_SWATCH_IMAGE_META, true);
    wp_nonce_field('wcb_swatch_term_meta', 'wcb_swatch_nonce');
    ?>
    <tr class="form-field term-wcb-swatch-wrap">
        <th scope="row"><label for="wcb_swatch_color"><?php esc_html_e('Cor do swatch', 'wcb-theme'); ?></label></th>
        <td>
            <div class="wcb-swatch-color-row">
                <?php
                $color_san = is_string($color) && $color !== '' ? wcb_sanitize_swatch_color($color) : '';
                $picker_val = $color_san !== '' ? $color_san : '#ffffff';
                ?>
                <input type="color" id="wcb_swatch_color_picker" class="wcb-swatch-color-picker"
                    value="<?php echo esc_attr($picker_val); ?>"
                    aria-label="<?php esc_attr_e('Seletor de cor', 'wcb-theme'); ?>" />
                <input type="text" name="wcb_swatch_color" id="wcb_swatch_color"
                    value="<?php echo esc_attr($color); ?>" placeholder="#2563eb" class="regular-text" />
                <button type="button" class="button wcb-swatch-color-clear"><?php esc_html_e('Limpar cor', 'wcb-theme'); ?></button>
            </div>
            <p class="description"><?php esc_html_e('Bolinha colorida na PDP; o nome aparece ao selecionar.', 'wcb-theme'); ?></p>
        </td>
    </tr>
    <tr class="form-field term-wcb-swatch-wrap">
        <th scope="row"><label for="wcb_swatch_image"><?php esc_html_e('Imagem do swatch', 'wcb-theme'); ?></label></th>
        <td>
            <div class="wcb-swatch-image-block">
                <div class="wcb-swatch-image-row">
                    <input type="url" name="wcb_swatch_image" id="wcb_swatch_image"
                        value="<?php echo esc_url($image); ?>" placeholder="https://" class="large-text" />
                    <button type="button" class="button wcb-swatch-media-btn"><?php esc_html_e('Biblioteca de media', 'wcb-theme'); ?></button>
                    <button type="button" class="button wcb-swatch-image-clear"><?php esc_html_e('Limpar imagem', 'wcb-theme'); ?></button>
                </div>
                <div class="wcb-swatch-image-preview" aria-hidden="true"></div>
            </div>
        </td>
    </tr>
    <?php
}

function wcb_swatch_save_term_meta($term_id)
{
    if (!isset($_POST['wcb_swatch_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wcb_swatch_nonce'])), 'wcb_swatch_term_meta')) {
        return;
    }
    if (!current_user_can('edit_products')) {
        return;
    }

    $color_raw = isset($_POST['wcb_swatch_color']) ? sanitize_text_field(wp_unslash($_POST['wcb_swatch_color'])) : '';
    $color = wcb_sanitize_swatch_color($color_raw);
    if ($color !== '') {
        update_term_meta($term_id, WCB_SWATCH_COLOR_META, $color);
    } else {
        delete_term_meta($term_id, WCB_SWATCH_COLOR_META);
    }

    $image = isset($_POST['wcb_swatch_image']) ? esc_url_raw(wp_unslash($_POST['wcb_swatch_image'])) : '';
    if ($image !== '') {
        update_term_meta($term_id, WCB_SWATCH_IMAGE_META, $image);
    } else {
        delete_term_meta($term_id, WCB_SWATCH_IMAGE_META);
    }
}

/**
 * Mapa para o front: [ attribute_pa_xxx => [ slug => [ color, image ] ] ].
 *
 * @param WC_Product $product
 * @return array<string, array<string, array{color: string, image: string}>>
 */
function wcb_build_variation_swatch_meta($product)
{
    if (!$product instanceof WC_Product || !$product->is_type('variable')) {
        return array();
    }

    $variation_attrs = $product->get_variation_attributes();
    if (empty($variation_attrs) || !is_array($variation_attrs)) {
        return array();
    }

    $out = array();

    foreach ($variation_attrs as $taxonomy => $slugs) {
        if (!taxonomy_exists($taxonomy) || !is_array($slugs)) {
            continue;
        }

        $attr_key = 'attribute_' . sanitize_title($taxonomy);
        $out[$attr_key] = array();

        foreach ($slugs as $slug) {
            $slug = (string) $slug;
            if ($slug === '') {
                continue;
            }
            $term = get_term_by('slug', $slug, $taxonomy);
            if (!$term || is_wp_error($term)) {
                $out[$attr_key][$slug] = array('color' => '', 'image' => '');
                continue;
            }
            $color = get_term_meta($term->term_id, WCB_SWATCH_COLOR_META, true);
            $color = is_string($color) ? wcb_sanitize_swatch_color($color) : '';
            $image = get_term_meta($term->term_id, WCB_SWATCH_IMAGE_META, true);
            $image = is_string($image) ? esc_url_raw($image) : '';

            $out[$attr_key][$slug] = array(
                'color' => $color,
                'image' => $image,
            );
        }
    }

    return $out;
}

/**
 * JSON dentro do formulário de variações (lido pelo JS em single-product.php).
 */
function wcb_print_variation_swatch_meta_json()
{
    global $product;
    if (!$product instanceof WC_Product || !$product->is_type('variable')) {
        return;
    }

    $data = wcb_build_variation_swatch_meta($product);
    if (empty($data)) {
        return;
    }

    $has_any = false;
    foreach ($data as $slugs) {
        foreach ($slugs as $m) {
            if (!empty($m['color']) || !empty($m['image'])) {
                $has_any = true;
                break 2;
            }
        }
    }
    if (!$has_any) {
        return;
    }

    echo '<script type="application/json" id="wcb-variation-swatch-meta">';
    echo wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo '</script>';
}

add_action('woocommerce_before_variations_form', 'wcb_print_variation_swatch_meta_json', 5);
