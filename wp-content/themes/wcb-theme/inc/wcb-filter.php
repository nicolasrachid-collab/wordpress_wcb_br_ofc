<?php
/**
 * WCB Native Product Filter v2 — AJAX + Attributes + Dynamic Count
 *
 * @package WCB_Theme
 */

defined('ABSPATH') || exit;

/* ================================================================
   1. RENDER FILTER SIDEBAR
   ================================================================ */

function wcb_render_native_filter() {
    // ── Obter IDs dos produtos da query atual (contexto da página) ──
    global $wp_query, $wpdb;
    $context_ids = [];

    if ( isset($wp_query) && $wp_query->posts ) {
        foreach ( $wp_query->posts as $p ) {
            $context_ids[] = is_object($p) ? $p->ID : intval($p);
        }
    }

    // Fallback: se não tiver contexto, pega todos os produtos publicados
    if ( empty($context_ids) ) {
        $context_ids = get_posts([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);
    }

    $get_sig = [];
    foreach ( $_GET as $gk => $gv ) {
        if ( in_array( $gk, [ 'wcb_cat', 'wcb_stock', 'wcb_min', 'wcb_max' ], true ) || strpos( $gk, 'wcb_attr_' ) === 0 ) {
            $get_sig[ $gk ] = $gv;
        }
    }
    ksort( $get_sig );
    $paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
    $filter_cache_key = 'wcb_filt_sb_v1_' . md5( wp_json_encode( [ 'ids' => $context_ids, 'get' => $get_sig, 'paged' => $paged ] ) );
    $filter_cached    = get_transient( $filter_cache_key );
    if ( false !== $filter_cached ) {
        echo $filter_cached;
        return;
    }

    ob_start();

    // ── Categories (somente as que têm produtos no contexto) ──
    $sem_cat = get_term_by('slug', 'sem-categoria', 'product_cat');
    $exclude_ids = $sem_cat ? [$sem_cat->term_id] : [];

    $all_categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'exclude'    => $exclude_ids,
        'parent'     => 0,
    ]);

    // Filtra categorias: só mostra se pelo menos 1 produto do contexto pertence a ela
    $categories = [];
    foreach ($all_categories as $cat) {
        $cat_product_ids = get_posts([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [[
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $cat->term_id,
                'include_children' => true,
            ]],
        ]);
        $count_in_context = count(array_intersect($cat_product_ids, $context_ids));
        if ($count_in_context > 0) {
            $cat->context_count = $count_in_context;
            $categories[] = $cat;
        }
    }

    // ── Price range (baseado nos produtos do contexto) ──
    if (!empty($context_ids)) {
        $ids_placeholder = implode(',', array_map('intval', $context_ids));
        $price_range = $wpdb->get_row("
            SELECT MIN( CAST(meta_value AS DECIMAL(10,2)) ) as min_price,
                   MAX( CAST(meta_value AS DECIMAL(10,2)) ) as max_price
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_price'
            AND meta_value > 0
            AND post_id IN ({$ids_placeholder})
        ");
    } else {
        $price_range = (object)['min_price' => 0, 'max_price' => 500];
    }

    $min_price = floor($price_range->min_price ?? 0);
    $max_price = ceil($price_range->max_price ?? 500);

    // ── Product Attributes (somente os que têm termos usados pelos produtos do contexto) ──
    $attr_priority = ['sabor', 'nicotina', 'volume', 'tipo'];

    $wc_attributes = wc_get_attribute_taxonomies();
    $filter_attributes = [];

    foreach ($wc_attributes as $attr) {
        $taxonomy = 'pa_' . $attr->attribute_name;
        if (!taxonomy_exists($taxonomy)) continue;
        
        // Busca apenas termos que estão atribuídos aos produtos do contexto
        $terms = wp_get_object_terms($context_ids, $taxonomy, [
            'orderby' => 'name',
            'order'   => 'ASC',
        ]);

        if (!is_wp_error($terms) && !empty($terms)) {
            // Remove duplicatas (wp_get_object_terms pode retornar duplicados)
            $unique_terms = [];
            $seen = [];
            foreach ($terms as $t) {
                if (!in_array($t->term_id, $seen)) {
                    $seen[] = $t->term_id;
                    // Conta quantos produtos do contexto usam este termo
                    $term_product_ids = get_posts([
                        'post_type'      => 'product',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'fields'         => 'ids',
                        'tax_query'      => [[
                            'taxonomy' => $taxonomy,
                            'field'    => 'term_id',
                            'terms'    => $t->term_id,
                        ]],
                    ]);
                    $t->context_count = count(array_intersect($term_product_ids, $context_ids));
                    $unique_terms[] = $t;
                }
            }
            $filter_attributes[] = [
                'label'    => $attr->attribute_label,
                'slug'     => $attr->attribute_name,
                'taxonomy' => $taxonomy,
                'terms'    => $unique_terms,
            ];
        }
    }

    // Ordena atributos pela prioridade definida
    usort($filter_attributes, function($a, $b) use ($attr_priority) {
        $pos_a = array_search(strtolower($a['slug']), $attr_priority);
        $pos_b = array_search(strtolower($b['slug']), $attr_priority);
        if ($pos_a === false) $pos_a = 999;
        if ($pos_b === false) $pos_b = 999;
        return $pos_a - $pos_b;
    });

    // ── Current filters from URL ──
    $active_cats   = isset($_GET['wcb_cat']) ? array_map('intval', (array) $_GET['wcb_cat']) : [];
    $active_stock  = isset($_GET['wcb_stock']) ? sanitize_text_field($_GET['wcb_stock']) : '';
    $active_min    = isset($_GET['wcb_min']) ? intval($_GET['wcb_min']) : $min_price;
    $active_max    = isset($_GET['wcb_max']) ? intval($_GET['wcb_max']) : $max_price;
    ?>

    <form id="wcb-filter-form" class="wcb-filter" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-nonce="<?php echo wp_create_nonce('wcb_filter_nonce'); ?>">

        <!-- ── Result count (dynamic) ── -->
        <div class="wcb-filter__result-count" id="wcb-filter-count">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <span id="wcb-filter-count-text"><?php echo count($context_ids); ?> produtos</span>
        </div>

        <!-- ── Categorias ── -->
        <div class="wcb-filter__section" data-section="categories">
            <button type="button" class="wcb-filter__section-header" aria-expanded="true">
                <span class="wcb-filter__section-title">Categorias</span>
                <svg class="wcb-filter__chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="wcb-filter__section-body">
                <ul class="wcb-filter__list">
                    <?php foreach ($categories as $cat): ?>
                        <li class="wcb-filter__item">
                            <label class="wcb-filter__checkbox-label">
                                <input type="checkbox" name="wcb_cat[]" value="<?php echo esc_attr($cat->term_id); ?>"
                                    <?php checked(in_array($cat->term_id, $active_cats)); ?>>
                                <span class="wcb-filter__checkmark"></span>
                                <span class="wcb-filter__text"><?php echo esc_html($cat->name); ?></span>
                                <span class="wcb-filter__count" data-cat-id="<?php echo esc_attr($cat->term_id); ?>"><?php echo intval($cat->context_count); ?></span>
                            </label>
                            <?php
                            $all_children = get_terms([
                                'taxonomy'   => 'product_cat',
                                'hide_empty' => true,
                                'parent'     => $cat->term_id,
                            ]);
                            // Filtra filhos pelo contexto
                            $children = [];
                            foreach ($all_children as $ch) {
                                $ch_ids = get_posts(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','tax_query'=>[['taxonomy'=>'product_cat','field'=>'term_id','terms'=>$ch->term_id]]]);
                                $ch_count = count(array_intersect($ch_ids, $context_ids));
                                if ($ch_count > 0) {
                                    $ch->context_count = $ch_count;
                                    $children[] = $ch;
                                }
                            }
                            if (!empty($children)): ?>
                                <ul class="wcb-filter__sublist">
                                    <?php foreach ($children as $child): ?>
                                        <li class="wcb-filter__item">
                                            <label class="wcb-filter__checkbox-label">
                                                <input type="checkbox" name="wcb_cat[]" value="<?php echo esc_attr($child->term_id); ?>"
                                                    <?php checked(in_array($child->term_id, $active_cats)); ?>>
                                                <span class="wcb-filter__checkmark"></span>
                                                <span class="wcb-filter__text"><?php echo esc_html($child->name); ?></span>
                                                <span class="wcb-filter__count" data-cat-id="<?php echo esc_attr($child->term_id); ?>"><?php echo intval($child->context_count); ?></span>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- ── Product Attributes (ordem: Sabor → Nicotina → Volume → Tipo) ── -->
        <?php foreach ($filter_attributes as $attribute): ?>
            <?php
            $active_attr = isset($_GET['wcb_attr_' . $attribute['slug']])
                ? array_map('sanitize_text_field', (array) $_GET['wcb_attr_' . $attribute['slug']])
                : [];
            ?>
            <div class="wcb-filter__section" data-section="attr-<?php echo esc_attr($attribute['slug']); ?>">
                <button type="button" class="wcb-filter__section-header" aria-expanded="false">
                    <span class="wcb-filter__section-title"><?php echo esc_html($attribute['label']); ?></span>
                    <svg class="wcb-filter__chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="wcb-filter__section-body" style="<?php echo empty($active_attr) ? 'max-height:0;padding-bottom:0;opacity:0;overflow:hidden;' : ''; ?>">
                    <ul class="wcb-filter__list wcb-filter__attr-list">
                        <?php foreach ($attribute['terms'] as $term): ?>
                            <li class="wcb-filter__item">
                                <label class="wcb-filter__checkbox-label">
                                    <input type="checkbox"
                                           name="wcb_attr_<?php echo esc_attr($attribute['slug']); ?>[]"
                                           value="<?php echo esc_attr($term->slug); ?>"
                                           <?php checked(in_array($term->slug, $active_attr)); ?>>
                                    <span class="wcb-filter__checkmark"></span>
                                    <span class="wcb-filter__text"><?php echo esc_html($term->name); ?></span>
                                    <span class="wcb-filter__count"><?php echo intval($term->context_count); ?></span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- ── Preço (penúltimo) ── -->
        <div class="wcb-filter__section" data-section="price">
            <button type="button" class="wcb-filter__section-header" aria-expanded="true">
                <span class="wcb-filter__section-title">Preço</span>
                <svg class="wcb-filter__chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="wcb-filter__section-body">
                <div class="wcb-filter__price-range"
                     data-min="<?php echo esc_attr($min_price); ?>"
                     data-max="<?php echo esc_attr($max_price); ?>"
                     data-current-min="<?php echo esc_attr($active_min); ?>"
                     data-current-max="<?php echo esc_attr($active_max); ?>">
                    <div class="wcb-filter__price-slider" id="wcb-price-slider">
                        <div class="wcb-filter__price-track"></div>
                        <div class="wcb-filter__price-fill"></div>
                        <input type="range" class="wcb-filter__range wcb-filter__range--min"
                               min="<?php echo esc_attr($min_price); ?>"
                               max="<?php echo esc_attr($max_price); ?>"
                               value="<?php echo esc_attr($active_min); ?>"
                               name="wcb_min">
                        <input type="range" class="wcb-filter__range wcb-filter__range--max"
                               min="<?php echo esc_attr($min_price); ?>"
                               max="<?php echo esc_attr($max_price); ?>"
                               value="<?php echo esc_attr($active_max); ?>"
                               name="wcb_max">
                    </div>
                    <div class="wcb-filter__price-labels">
                        <span class="wcb-filter__price-val" id="wcb-price-min-val">R$ <?php echo esc_html($active_min); ?></span>
                        <span class="wcb-filter__price-sep">—</span>
                        <span class="wcb-filter__price-val" id="wcb-price-max-val">R$ <?php echo esc_html($active_max); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Disponibilidade (último) ── -->
        <div class="wcb-filter__section" data-section="stock">
            <button type="button" class="wcb-filter__section-header" aria-expanded="true">
                <span class="wcb-filter__section-title">Disponibilidade</span>
                <svg class="wcb-filter__chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="wcb-filter__section-body">
                <ul class="wcb-filter__list">
                    <li class="wcb-filter__item">
                        <label class="wcb-filter__checkbox-label">
                            <input type="radio" name="wcb_stock" value="instock" <?php checked($active_stock, 'instock'); ?>>
                            <span class="wcb-filter__radio"></span>
                            <span class="wcb-filter__text">Em estoque</span>
                            <span class="wcb-filter__stock-dot wcb-filter__stock-dot--in"></span>
                        </label>
                    </li>
                    <li class="wcb-filter__item">
                        <label class="wcb-filter__checkbox-label">
                            <input type="radio" name="wcb_stock" value="outofstock" <?php checked($active_stock, 'outofstock'); ?>>
                            <span class="wcb-filter__radio"></span>
                            <span class="wcb-filter__text">Fora de estoque</span>
                            <span class="wcb-filter__stock-dot wcb-filter__stock-dot--out"></span>
                        </label>
                    </li>
                    <li class="wcb-filter__item">
                        <label class="wcb-filter__checkbox-label">
                            <input type="radio" name="wcb_stock" value="" <?php checked($active_stock, ''); ?>>
                            <span class="wcb-filter__radio"></span>
                            <span class="wcb-filter__text">Todos</span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ── Actions ── -->
        <div class="wcb-filter__actions">
            <button type="button" class="wcb-filter__btn wcb-filter__btn--apply" id="wcb-filter-apply">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Aplicar Filtros
            </button>
            <button type="button" class="wcb-filter__btn wcb-filter__btn--clear" id="wcb-filter-clear">
                Limpar
            </button>
        </div>

    </form>
    <?php
    $filter_html = ob_get_clean();
    set_transient( $filter_cache_key, $filter_html, 15 * MINUTE_IN_SECONDS );
    echo $filter_html;
}


/* ================================================================
   2. APPLY FILTERS TO PRODUCT QUERY (for non-AJAX / fallback)
   ================================================================ */

add_action('woocommerce_product_query', 'wcb_apply_native_filters');

function wcb_apply_native_filters($q) {
    // ── Categories ──
    if (!empty($_GET['wcb_cat'])) {
        $cat_ids = array_map('intval', (array) $_GET['wcb_cat']);
        $tax_query = $q->get('tax_query') ?: [];
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $cat_ids,
            'operator' => 'IN',
        ];
        $q->set('tax_query', $tax_query);
    }

    // ── Price ──
    if (isset($_GET['wcb_min']) || isset($_GET['wcb_max'])) {
        $meta_query = $q->get('meta_query') ?: [];
        $price_q = ['key' => '_price', 'type' => 'NUMERIC'];

        $min = isset($_GET['wcb_min']) ? intval($_GET['wcb_min']) : 0;
        $max = isset($_GET['wcb_max']) ? intval($_GET['wcb_max']) : 999999;

        $price_q['value'] = [$min, $max];
        $price_q['compare'] = 'BETWEEN';

        $meta_query[] = $price_q;
        $q->set('meta_query', $meta_query);
    }

    // ── Stock ──
    if (!empty($_GET['wcb_stock'])) {
        $stock = sanitize_text_field($_GET['wcb_stock']);
        $meta_query = $q->get('meta_query') ?: [];
        $meta_query[] = [
            'key'   => '_stock_status',
            'value' => $stock,
        ];
        $q->set('meta_query', $meta_query);
    }

    // ── Attributes ──
    $wc_attributes = wc_get_attribute_taxonomies();
    $tax_query = $q->get('tax_query') ?: [];
    foreach ($wc_attributes as $attr) {
        $param = 'wcb_attr_' . $attr->attribute_name;
        if (!empty($_GET[$param])) {
            $terms = array_map('sanitize_text_field', (array) $_GET[$param]);
            $tax_query[] = [
                'taxonomy' => 'pa_' . $attr->attribute_name,
                'field'    => 'slug',
                'terms'    => $terms,
                'operator' => 'IN',
            ];
        }
    }
    if (!empty($tax_query)) {
        $q->set('tax_query', $tax_query);
    }
}


/* ================================================================
   3. AJAX HANDLER — Filter products without page reload
   ================================================================ */

add_action('wp_ajax_wcb_filter_products', 'wcb_ajax_filter_products');
add_action('wp_ajax_nopriv_wcb_filter_products', 'wcb_ajax_filter_products');

function wcb_ajax_filter_products() {
    check_ajax_referer('wcb_filter_nonce', 'nonce');

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'tax_query'      => ['relation' => 'AND'],
        'meta_query'     => [],
    ];

    // ── Categories ──
    if (!empty($_POST['wcb_cat'])) {
        $args['tax_query'][] = [
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => array_map('intval', (array) $_POST['wcb_cat']),
            'operator' => 'IN',
        ];
    }

    // ── Price ──
    if (isset($_POST['wcb_min']) && isset($_POST['wcb_max'])) {
        $args['meta_query'][] = [
            'key'     => '_price',
            'value'   => [intval($_POST['wcb_min']), intval($_POST['wcb_max'])],
            'compare' => 'BETWEEN',
            'type'    => 'NUMERIC',
        ];
    }

    // ── Stock ──
    if (!empty($_POST['wcb_stock'])) {
        $args['meta_query'][] = [
            'key'   => '_stock_status',
            'value' => sanitize_text_field($_POST['wcb_stock']),
        ];
    }

    // ── Attributes ──
    $wc_attributes = wc_get_attribute_taxonomies();
    foreach ($wc_attributes as $attr) {
        $param = 'wcb_attr_' . $attr->attribute_name;
        if (!empty($_POST[$param])) {
            $args['tax_query'][] = [
                'taxonomy' => 'pa_' . $attr->attribute_name,
                'field'    => 'slug',
                'terms'    => array_map('sanitize_text_field', (array) $_POST[$param]),
                'operator' => 'IN',
            ];
        }
    }

    $query = new WP_Query($args);
    $total = $query->found_posts;

    ob_start();

    if ($query->have_posts()) {
        woocommerce_product_loop_start();
        while ($query->have_posts()) {
            $query->the_post();
            wc_get_template_part('content', 'product');
        }
        woocommerce_product_loop_end();
    } else {
        echo '<div class="wcb-filter__no-results">';
        echo '<div class="wcb-filter__no-results-icon">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>';
        echo '</div>';
        echo '<h3>Nenhum produto encontrado</h3>';
        echo '<p>Tente ajustar os filtros ou remover alguns critérios.</p>';
        echo '</div>';
    }

    $html = ob_get_clean();
    wp_reset_postdata();

    wp_send_json_success([
        'html'  => $html,
        'total' => $total,
    ]);
}
