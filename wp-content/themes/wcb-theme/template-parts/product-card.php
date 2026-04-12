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

/*
 * Fonte do WC_Product (evita misturar produto da PDP com cards em loops secundários):
 * 1) wc_get_product( get_the_ID() ) quando o post atual é product — alinha título, link e avaliações ao mesmo ID.
 * 2) $product injetado por get_template_part( ..., array( 'product' => … ) ) — não pode vir depois de global $product
 *    no topo (isso anulava o inject e mantinha o global da PDP).
 * 3) global $product como último recurso.
 */
$wcb_pc_track = isset( $GLOBALS['wcb_product_card_track'] ) && is_array( $GLOBALS['wcb_product_card_track'] )
	? $GLOBALS['wcb_product_card_track']
	: null;

$wcb_injected_product = ( isset( $product ) && $product instanceof WC_Product ) ? $product : null;

$product    = null;
$wcb_post_id = (int) get_the_ID();
if ( $wcb_post_id > 0 && get_post_type( $wcb_post_id ) === 'product' ) {
	$p_by_loop = wc_get_product( $wcb_post_id );
	if ( $p_by_loop instanceof WC_Product ) {
		$product = $p_by_loop;
	}
}
if ( ! $product instanceof WC_Product && $wcb_injected_product instanceof WC_Product ) {
	$product = $wcb_injected_product;
}
if ( ! $product instanceof WC_Product ) {
	global $product;
}
if ( ! $product instanceof WC_Product ) {
	return;
}

$wcb_fav_btn_label = ! empty( $wishlist_page )
	? __( 'Remover dos favoritos', 'wcb-theme' )
	: __( 'Favoritar', 'wcb-theme' );

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

/* ── Stock bar: desativada em todos os cards (sem .wcb-stock-bar / __label) ── */

/* ── Category ──────────────────────────────────────────── */
$cat_name = '';
$terms = get_the_terms($product->get_id(), 'product_cat');
if ($terms && !is_wp_error($terms)) {
    $cat_name = $terms[0]->name;
}

/* ── Rating: comentários aprovados com estrelas (alinhado à aba Avaliações) ── */
$wcb_rating_stats = wcb_get_product_rating_display_stats( $product->get_id() );
$review_count     = (int) $wcb_rating_stats['count'];
$avg_rating       = (float) $wcb_rating_stats['average'];

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

<div class="wcb-product-card<?php echo !$in_stock ? ' wcb-product-card--out-of-stock' : ''; ?>" data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>"
	<?php
	if ( $wcb_pc_track && ! empty( $wcb_pc_track['wcb_track'] ) && ! empty( $wcb_pc_track['role'] ) ) {
		echo ' data-wcb-track="' . esc_attr( (string) $wcb_pc_track['wcb_track'] ) . '" data-role="' . esc_attr( (string) $wcb_pc_track['role'] ) . '"';
	}
	?>>

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
        <button type="button" class="wcb-product-card__fav" title="<?php echo esc_attr( $wcb_fav_btn_label ); ?>"
            aria-label="<?php echo esc_attr( $wcb_fav_btn_label ); ?>"
            data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>">
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

        <!-- Rating: sem avaliações = só estrelas neutras; com avaliações = estrelas + nota + (N) -->
        <div class="wcb-product-card__rating<?php echo $review_count < 1 ? ' wcb-product-card__rating--zero' : ''; ?>"
            data-wcb-rating-for="<?php echo esc_attr( (string) (int) $product->get_id() ); ?>"
            <?php
            if ( $review_count < 1 ) {
                echo ' aria-label="' . esc_attr__( 'Sem avaliações ainda', 'wcb-theme' ) . '"';
            }
            ?>>
            <div class="wcb-product-card__stars" style="--rating: <?php echo esc_attr( (string) max( 0, min( 5, $avg_rating ) ) ); ?>">
                <?php if ( $review_count > 0 ) : ?>
                <span class="wcb-product-card__stars-fill" aria-hidden="true">★★★★★</span>
                <?php endif; ?>
                <span class="wcb-product-card__stars-empty" aria-hidden="true">★★★★★</span>
            </div>
            <?php if ( $review_count > 0 ) : ?>
            <span class="wcb-product-card__rating-val"><?php echo esc_html( number_format( (float) $avg_rating, 1 ) ); ?></span>
            <span class="wcb-product-card__rating-count">(<?php echo esc_html( (string) (int) $review_count ); ?>)</span>
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
            <?php if ($current_price > 0): ?>
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