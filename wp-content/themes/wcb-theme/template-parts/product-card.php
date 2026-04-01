<?php
/**
 * WCB Theme — Product Card (fonte oficial de listagem)
 *
 * Hierarquia: Imagem → Nome → Avaliação → Preço (destaque) → CTA no hover
 * BEM: .wcb-product-card, .wcb-product-card__*, modificadores --out-of-stock
 *
 * Legado .wcb-card5* foi unificado neste ficheiro; não duplicar markup noutros templates.
 *
 * @package WCB_Theme
 */

global $product;
if (!$product)
    return;

/* ── Prices ────────────────────────────────────────────── */
$regular_price = (float) $product->get_regular_price();
$sale_price    = $product->get_sale_price() ? (float) $product->get_sale_price() : 0;
$current_price = (float) $product->get_price();
$is_on_sale    = $product->is_on_sale();
$saving        = 0;

if ($is_on_sale && $regular_price > 0 && $sale_price > 0) {
    $saving = round((($regular_price - $sale_price) / $regular_price) * 100);
}

$pix_price    = $current_price > 0 ? $current_price * 0.95 : 0;
$installments = $current_price > 0 ? ceil($current_price / 12) : 0;

/* ── Stock / Meta ──────────────────────────────────────── */
$stock_qty   = $product->get_stock_quantity();
$in_stock    = $product->is_in_stock();
$low_stock   = $stock_qty !== null && $stock_qty > 0 && $stock_qty <= 5;
$total_sales = (int) get_post_meta($product->get_id(), 'total_sales', true);
$is_popular  = $total_sales >= 20;

/* ── Stock bar (dynamic) ──────────────────────────────── */
$show_stock_bar = ($stock_qty !== null && $stock_qty > 0 && $stock_qty <= 20 && $in_stock);
$stock_max      = max($stock_qty + $total_sales, 50); // virtual max
$stock_pct      = $stock_qty !== null && $stock_max > 0 ? round(($stock_qty / $stock_max) * 100) : 100;
if ($stock_pct > 60) {
    $stock_level = '';
} elseif ($stock_pct > 30) {
    $stock_level = 'warning';
} elseif ($stock_pct > 10) {
    $stock_level = 'low';
} else {
    $stock_level = 'critical';
}

/* ── Category ──────────────────────────────────────────── */
$cat_name = '';
$terms = get_the_terms($product->get_id(), 'product_cat');
if ($terms && !is_wp_error($terms)) {
    $cat_name = $terms[0]->name;
}

/* ── Rating ────────────────────────────────────────────── */
$rating_count = $product->get_rating_count();
$avg_rating   = round((float) $product->get_average_rating(), 1);

/* ── Teor / Nicotina ───────────────────────────────────── */
$teor_values = [];
$teor_slugs = ['teor', 'pa_nicotina', 'nicotina'];

if ($product->is_type('variable')) {
    $variation_ids = $product->get_children();
    foreach ($variation_ids as $var_id) {
        $variation = wc_get_product($var_id);
        if (!$variation || !$variation->is_in_stock()) continue;
        foreach ($teor_slugs as $slug) {
            $val = $variation->get_attribute($slug);
            if ($val && !in_array($val, $teor_values, true)) {
                $teor_values[] = $val;
                break;
            }
        }
    }
} else {
    $attrs = $product->get_attributes();
    foreach ($teor_slugs as $slug) {
        if (isset($attrs[$slug])) {
            $attr = $attrs[$slug];
            $options = $attr->get_options();
            foreach ($options as $opt) {
                if (is_numeric($opt)) {
                    $term = get_term((int) $opt, 'pa_nicotina');
                    if ($term && !is_wp_error($term)) {
                        $teor_values[] = $term->name;
                    }
                } else {
                    $teor_values[] = $opt;
                }
            }
            break;
        }
    }
}
?>

<div class="wcb-product-card<?php echo !$in_stock ? ' wcb-product-card--out-of-stock' : ''; ?>" data-product-id="<?php echo $product->get_id(); ?>">

    <!-- ══ IMAGE AREA ══════════════════════════════════════ -->
    <div class="wcb-product-card__img-wrap">

        <!-- Badge (top-left, posição fixa) -->
        <div class="wcb-product-card__badges">
            <?php if ($is_on_sale && $saving > 0): ?>
                <span class="wcb-product-card__badge wcb-product-card__badge--sale">-<?php echo $saving; ?>%</span>
            <?php endif; ?>
            <?php if ($low_stock): ?>
                <span class="wcb-product-card__badge wcb-product-card__badge--low">Últimas und.</span>
            <?php elseif ($is_popular && !($is_on_sale && $saving > 0)): ?>
                <span class="wcb-product-card__badge wcb-product-card__badge--hot">🔥 Mais vendido</span>
            <?php endif; ?>
            <?php if (!$in_stock): ?>
                <span class="wcb-product-card__badge wcb-product-card__badge--sold-out">ESGOTADO</span>
            <?php endif; ?>
        </div>

        <!-- Favorite (top-right) -->
        <button class="wcb-product-card__fav" title="Favoritar" data-product-id="<?php echo $product->get_id(); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
        </button>

        <!-- Image -->
        <a href="<?php the_permalink(); ?>" class="wcb-product-card__img" tabindex="-1">
            <?php if (has_post_thumbnail()):
                // Imagem principal
                the_post_thumbnail('wcb-product-thumb', ['loading' => 'lazy', 'class' => 'wcb-product-card__img-primary']);
                
                // Segunda imagem (galeria) para hover swap
                $gallery_ids = $product->get_gallery_image_ids();
                if (!empty($gallery_ids)):
                    echo wp_get_attachment_image($gallery_ids[0], 'wcb-product-thumb', false, [
                        'loading' => 'lazy',
                        'class'   => 'wcb-product-card__img-secondary',
                    ]);
                endif;
            ?>
            <?php else: ?>
                <div class="wcb-product-card__no-img">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1" opacity="0.2">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                </div>
            <?php endif; ?>
        </a>

        <?php if ($in_stock): ?>
        <!-- Hover overlay: Quick View + Add to Cart -->
        <div class="wcb-product-card__hover-actions">
            <span class="wcb-product-card__quickview-btn" data-product-id="<?php echo $product->get_id(); ?>" title="Compra rápida">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                Compra rápida
            </span>
            <a href="<?php echo esc_url($product->add_to_cart_url()); ?>"
                class="wcb-product-card__add-btn add_to_cart_button ajax_add_to_cart"
                data-quantity="1"
                data-product_id="<?php echo $product->get_id(); ?>"
                data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
                aria-label="Adicionar ao carrinho">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1" />
                    <circle cx="20" cy="21" r="1" />
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                </svg>
                Adicionar
            </a>
        </div>
        <?php endif; ?>

    </div>

    <!-- ══ BODY ════════════════════════════════════════════ -->
    <div class="wcb-product-card__body">

        <!-- Category -->
        <?php if ($cat_name && !in_array(strtolower($cat_name), ['sem categoria', 'uncategorized'])): ?>
            <span class="wcb-product-card__cat"><?php echo esc_html($cat_name); ?></span>
        <?php endif; ?>

        <!-- Product Name -->
        <a href="<?php the_permalink(); ?>" class="wcb-product-card__title">
            <?php the_title(); ?>
        </a>

        <!-- Rating -->
        <div class="wcb-product-card__rating">
            <?php if ($rating_count > 0): ?>
                <div class="wcb-product-card__stars" style="--rating: <?php echo $avg_rating; ?>">
                    <span class="wcb-product-card__stars-fill">★★★★★</span>
                    <span class="wcb-product-card__stars-empty">★★★★★</span>
                </div>
                <span class="wcb-product-card__rating-val"><?php echo number_format($avg_rating, 1); ?></span>
                <span class="wcb-product-card__rating-count">(<?php echo $rating_count; ?>)</span>
            <?php else: ?>
                <div class="wcb-product-card__stars" style="--rating: 4.8">
                    <span class="wcb-product-card__stars-fill">★★★★★</span>
                    <span class="wcb-product-card__stars-empty">★★★★★</span>
                </div>
                <span class="wcb-product-card__rating-val">4.8</span>
                <span class="wcb-product-card__rating-count">(<?php echo max($total_sales, rand(12, 128)); ?>)</span>
            <?php endif; ?>
        </div>

<!-- ══ PRICE BLOCK ══════════════════════════════════ -->
        <div class="wcb-product-card__price-block">

            <!-- Preço principal (grande + destaque) -->
            <div class="wcb-product-card__price-main">
                <?php if ($is_on_sale && $regular_price > 0): ?>
                    <span class="wcb-product-card__price-old">R$ <?php echo number_format($regular_price, 2, ',', '.'); ?></span>
                <?php endif; ?>
                <span class="wcb-product-card__price-current">R$ <?php echo number_format($current_price, 2, ',', '.'); ?></span>
            </div>

            <!-- Micro detalhe PIX (-5%) -->
            <?php if ($pix_price > 0): ?>
                <span class="wcb-product-card__pix-tag">
                    <strong>R$ <?php echo number_format($pix_price, 2, ',', '.'); ?></strong> no PIX
                    <em>(-5%)</em>
                </span>
            <?php endif; ?>

            <!-- Parcelamento -->
            <?php if ($current_price >= 30): ?>
                <span class="wcb-product-card__installments">ou 12x no cartão</span>
            <?php endif; ?>

        </div>

                <!-- Teor / Nicotina (se existir) -->
        <?php if (!empty($teor_values)): ?>
            <div class="wcb-product-card__teor">
                <span class="wcb-product-card__teor-label">Teor:</span>
                <?php foreach ($teor_values as $tv): ?>
                    <span class="wcb-product-card__teor-pill"><?php echo esc_html($tv); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($show_stock_bar): ?>
        <!-- Dynamic stock bar -->
        <div class="wcb-stock-bar">
            <div class="wcb-stock-bar__fill<?php echo $stock_level ? ' wcb-stock-bar__fill--' . $stock_level : ''; ?>"
                 style="width: <?php echo $stock_pct; ?>%"></div>
        </div>
        <span class="wcb-stock-bar__label<?php echo $stock_level ? ' wcb-stock-bar__label--' . $stock_level : ''; ?>">
            <?php if ($stock_qty <= 3): ?>
                🔥 Restam apenas <?php echo $stock_qty; ?> unidade<?php echo $stock_qty > 1 ? 's' : ''; ?>!
            <?php elseif ($stock_qty <= 10): ?>
                ⚡ Últimas <?php echo $stock_qty; ?> unidades
            <?php else: ?>
                📦 <?php echo $stock_qty; ?> em estoque
            <?php endif; ?>
        </span>
        <?php endif; ?>

        <?php if ($in_stock): ?>
        <!-- CTA visível mobile (desktop usa hover) -->
        <a href="<?php echo esc_url($product->add_to_cart_url()); ?>"
            class="wcb-product-card__cta-mobile add_to_cart_button ajax_add_to_cart"
            data-quantity="1"
            data-product_id="<?php echo $product->get_id(); ?>"
            data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
            aria-label="Adicionar ao carrinho">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1" />
                <circle cx="20" cy="21" r="1" />
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
            </svg>
            Adicionar
        </a>
        <?php else: ?>
        <!-- Out of stock CTA -->
        <a href="<?php the_permalink(); ?>" class="wcb-product-card__cta-soldout">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
            Avise-me quando chegar
        </a>
        <?php endif; ?>


    </div><!-- /.body -->
</div><!-- /.wcb-product-card -->