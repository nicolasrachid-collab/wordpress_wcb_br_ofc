<?php
/**
 * WCB Theme — Single Product PDP Premium v2
 * Layout premium com conversão otimizada
 * Preserva hooks e lógica nativa do WooCommerce
 *
 * @package WCB_Theme
 */

if (!defined('ABSPATH'))
    exit;

get_header();

do_action('woocommerce_before_main_content');

while (have_posts()):
    the_post();

    // ─── Sempre o produto do post atual (evita global desatualizado) ───
    $product = wc_get_product( get_the_ID() );
    if ( ! $product instanceof WC_Product ) {
        break;
    }
    $GLOBALS['product'] = $product;

    // ─── Product Data (WooCommerce nativo) ────────────────────
    $product_id = $product->get_id();
    $product_title = $product->get_name();
    $regular_price = (float) $product->get_regular_price();
    $sale_price_val = $product->get_sale_price() ? (float) $product->get_sale_price() : 0;
    $current_price = (float) $product->get_price();
    $is_on_sale = $product->is_on_sale();
    $stock_qty = $product->get_stock_quantity();
    $is_in_stock = $product->is_in_stock();
    $pix_price = $current_price * 0.95;
    $economize_pix = ($current_price > 0) ? ($current_price - $pix_price) : 0;
    $saving_pct = ($is_on_sale && $regular_price > 0 && $sale_price_val > 0)
        ? round((($regular_price - $sale_price_val) / $regular_price) * 100)
        : 0;
    $wcb_rating_stats = wcb_get_product_rating_display_stats( $product_id );
    $avg_rating       = $wcb_rating_stats['average'];
    $review_count     = (int) $wcb_rating_stats['count'];
    $product_cats = get_the_terms($product_id, 'product_cat');
    $sku = $product->get_sku();
    $wcb_pdp_offer_timer_scope = function_exists('wcb_pdp_get_offer_bar_timer_scope')
        ? wcb_pdp_get_offer_bar_timer_scope($product_id, $product_cats)
        : 'product';

    // ─── Gallery images (WooCommerce nativo) ──────────────────
    $gallery_ids = $product->get_gallery_image_ids();
    $thumb_id = $product->get_image_id();
    $all_image_ids = array_merge((array) $thumb_id, $gallery_ids);
    $all_image_ids = array_unique(array_filter($all_image_ids));

    // ─── Cartão na PDP: sufixo fixo "em até 12x no cartão" (linha do ticket) ─

    // ─── ACF / Custom Fields (opcionais — fallback seguro) ────
    // Se ACF estiver ativo, puxamos "how_to_use". Caso contrário, fallback.
    $how_to_use = function_exists('get_field') ? get_field('how_to_use', $product_id) : '';
    ?>

    <div class="wcb-pdp">
        <div class="wcb-container">

            <?php
            /**
             * Hook: woocommerce_before_single_product
             * Mostra notices (adicionado ao carrinho, erro de estoque, etc)
             */
            do_action('woocommerce_before_single_product');
            if (function_exists('wcb_pdp_detach_yith_fbt_from_after_summary')) {
                wcb_pdp_detach_yith_fbt_from_after_summary();
            }
            ?>

            <!-- ════════════════════════════════════════════════════
                 1. SEÇÃO HERO (Galeria + BuyBox)
                 ════════════════════════════════════════════════════ -->
            <!-- Breadcrumb -->
            <nav class="wcb-breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
                <span>/</span>
                <?php if ($product_cats && !is_wp_error($product_cats)): ?>
                    <a href="<?php echo esc_url(get_term_link($product_cats[0])); ?>">
                        <?php echo esc_html($product_cats[0]->name); ?>
                    </a>
                    <span>/</span>
                <?php endif; ?>
                <span><?php echo esc_html($product_title); ?></span>
            </nav>

            <section class="wcb-pdp-hero">

                <!-- 2A. GALERIA AVANÇADA -->
                <div class="wcb-pdp-gallery">
                    <!-- Thumbnails (sidebar no desktop, abaixo no mobile) -->
                    <?php if (count($all_image_ids) > 1): ?>
                        <div class="wcb-pdp-gallery__thumbs">
                            <?php foreach ($all_image_ids as $i => $img_id):
                                $t_url = wp_get_attachment_image_url($img_id, 'woocommerce_gallery_thumbnail');
                                $f_url = wp_get_attachment_image_url($img_id, 'full');
                                $s_url = wp_get_attachment_image_url($img_id, 'woocommerce_single');
                                ?>
                                <button class="wcb-pdp-gallery__thumb <?php echo $i === 0 ? 'is-active' : ''; ?>"
                                    data-full="<?php echo esc_url($f_url); ?>" data-single="<?php echo esc_url($s_url); ?>"
                                    data-index="<?php echo $i; ?>" aria-label="Imagem <?php echo $i + 1; ?>">
                                    <img src="<?php echo esc_url($t_url); ?>" alt="" loading="lazy">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Imagem principal com Zoom -->
                    <div class="wcb-pdp-gallery__main" id="wcb-pdp-gallery-main">
                        <!-- Badges -->
                        <div class="wcb-pdp-gallery__badges">
                            <?php if ($is_on_sale && $saving_pct > 0): ?>
                                <span class="wcb-pdp-badge wcb-pdp-badge--sale">-<?php echo $saving_pct; ?>%</span>
                            <?php endif; ?>
                        </div>

                        <!-- Zoom Container -->
                        <div class="wcb-pdp-gallery__zoom" id="wcb-pdp-zoom">
                            <?php if ($thumb_id):
                                $main_full = wp_get_attachment_image_url($thumb_id, 'full');
                                echo wp_get_attachment_image($thumb_id, 'woocommerce_single', false, [
                                    'class' => 'wcb-pdp-gallery__img',
                                    'id' => 'wcb-pdp-main-img',
                                    'data-zoom' => $main_full,
                                    'loading' => 'eager',
                                ]);
                            else: ?>
                                <div class="wcb-pdp-gallery__placeholder">
                                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1" opacity="0.3">
                                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Zoom hint -->
                        <div class="wcb-pdp-gallery__zoom-hint">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                                <line x1="11" y1="8" x2="11" y2="14" />
                                <line x1="8" y1="11" x2="14" y2="11" />
                            </svg>
                            Passe o mouse para zoom
                        </div>
                    </div>
                </div>

                <!-- 2B. Coluna direita: buybox + FBT (YITH) -->
                <div class="wcb-pdp-hero__summary">
                <div class="wcb-pdp-buybox" id="wcb-pdp-buybox">

                    <!-- Título → meta (SKU, avaliações, resumo, estoque) → preço → variação → qtd → CTA -->
                    <h1 class="wcb-pdp-buybox__title"><?php echo esc_html($product_title); ?></h1>

                    <div class="wcb-pdp-buybox__after-price">
                        <?php if ($sku): ?>
                            <p class="wcb-pdp-buybox__sku">SKU: <?php echo esc_html($sku); ?></p>
                        <?php endif; ?>

                        <div class="wcb-pdp-buybox__rating" data-wcb-rating-for="<?php echo esc_attr( (string) (int) $product_id ); ?>">
                            <div class="wcb-pdp-buybox__stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg class="wcb-pdp-buybox__star" width="16" height="16" viewBox="0 0 24 24"
                                        fill="<?php echo $i <= round($avg_rating) ? '#FBBF24' : 'none'; ?>"
                                        stroke="<?php echo $i <= round($avg_rating) ? '#F59E0B' : '#FCD34D'; ?>"
                                        stroke-width="<?php echo $i <= round($avg_rating) ? '1' : '1.35'; ?>">
                                        <polygon
                                            points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <?php if ($review_count > 0): ?>
                                <a href="#wcb-pdp-tab-reviews" class="wcb-pdp-buybox__rating-link" id="wcb-scroll-to-reviews">
                                    <?php echo number_format($avg_rating, 1); ?> · <?php echo $review_count; ?> avaliações
                                </a>
                            <?php else: ?>
                                <span class="wcb-pdp-buybox__rating-link">Seja o primeiro a avaliar</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($product->get_short_description()) : ?>
                            <div
                                class="wcb-pdp-buybox__desc"
                                data-wcb-short-desc
                                data-label-more="<?php echo esc_attr__( 'Ver mais', 'wcb-theme' ); ?>"
                                data-label-less="<?php echo esc_attr__( 'Ver menos', 'wcb-theme' ); ?>"
                            >
                                <div class="wcb-pdp-buybox__desc-inner">
                                    <?php echo wpautop(wp_kses_post($product->get_short_description())); ?>
                                </div>
                                <button
                                    type="button"
                                    class="wcb-pdp-buybox__desc-toggle"
                                    hidden
                                    aria-expanded="false"
                                >
                                    <?php echo esc_html__( 'Ver mais', 'wcb-theme' ); ?>
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ($is_in_stock): ?>
                            <?php if ($stock_qty !== null && $stock_qty <= 10 && $stock_qty > 0): ?>
                                <div class="wcb-pdp-buybox__urgency wcb-pdp-buybox__urgency--low">
                                    🔥 Corra! Apenas <strong><?php echo $stock_qty; ?> unidades</strong> em estoque.
                                </div>
                            <?php else: ?>
                                <div class="wcb-pdp-buybox__urgency wcb-pdp-buybox__urgency--ok">
                                    <span class="wcb-pdp-buybox__urgency-dot" aria-hidden="true"></span>
                                    Em estoque — pronta entrega
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="wcb-pdp-buybox__urgency wcb-pdp-buybox__urgency--out">
                                Produto indisponível
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_on_sale && $saving_pct > 0): ?>
                    <div class="wcb-pdp-offer-bar wcb-pdp-offer-bar--buybox" role="status" aria-live="polite"
                        data-product-id="<?php echo esc_attr((string) $product_id); ?>"
                        data-offer-duration-sec="7200"
                        data-offer-timer-scope="<?php echo esc_attr($wcb_pdp_offer_timer_scope); ?>">
                        <div class="wcb-pdp-offer-bar__inner">
                            <span class="wcb-pdp-offer-bar__icon" aria-hidden="true">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </span>
                            <span class="wcb-pdp-offer-bar__text">Oferta por tempo limitado!</span>
                            <div class="wcb-pdp-offer-bar__timer-row">
                                <span class="wcb-pdp-offer-bar__timer" id="wcb-pdp-countdown">02:00:00</span>
                                <span class="wcb-pdp-offer-bar__secondary">ou enquanto durarem os estoques</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Bloco de Preço Premium (dinâmico via JS para variações) -->
                    <div class="wcb-pdp-buybox__price-block" id="wcb-pdp-price-block"
                        data-base-price="<?php echo $current_price; ?>" data-base-regular="<?php echo $regular_price; ?>">
                        <div class="wcb-pdp-buybox__price-card">
                            <div class="wcb-pdp-buybox__price-card-body">
                                <span class="wcb-pdp-buybox__price-old" id="wcb-pdp-price-old"
                                    style="<?php echo (!$is_on_sale || $regular_price <= 0) ? 'display:none' : ''; ?>">
                                    De R$ <?php echo number_format($regular_price, 2, ',', '.'); ?>
                                </span>

                                <div class="wcb-pdp-buybox__price-ticket wcb-pdp-buybox__pix wcb-pdp-buybox__pix--hero"
                                    id="wcb-pdp-pix"
                                    style="<?php echo ($current_price <= 0) ? 'display:none' : ''; ?>">
                                    <div class="wcb-pdp-buybox__pix-head">
                                        <span class="wcb-pdp-buybox__pix-pill" title="Desconto exclusivo no PIX">PIX
                                            −5%</span>
                                    </div>
                                    <p class="wcb-pdp-buybox__price-ticket__lead">
                                        <strong class="wcb-pdp-buybox__pix-value"
                                            id="wcb-pdp-pix-value">R$ <?php echo number_format($pix_price, 2, ',', '.'); ?></strong><span
                                            class="wcb-pdp-buybox__price-ticket__suffix"> no PIX</span>
                                    </p>
                                    <p class="wcb-pdp-buybox__economize" id="wcb-pdp-economize-pix"
                                        style="<?php echo ($current_price <= 0) ? 'display:none' : ''; ?>">
                                        Economia de R$ <?php echo number_format($economize_pix, 2, ',', '.'); ?> no pagamento à vista
                                    </p>
                                    <p class="wcb-pdp-buybox__price-ticket__card">
                                        <span class="wcb-pdp-buybox__price-ticket__card-prefix">ou </span>
                                        <span class="wcb-pdp-buybox__price-current" id="wcb-pdp-price-current">
                                            R$ <?php echo number_format($current_price, 2, ',', '.'); ?>
                                        </span>
                                        <span class="wcb-pdp-buybox__price-ticket__card-suffix"> em até 12x no cartão</span>
                                        <span class="wcb-pdp-buybox__discount" id="wcb-pdp-discount"
                                            style="<?php echo (!$is_on_sale || $saving_pct <= 0) ? 'display:none' : ''; ?>">
                                            −<?php echo $saving_pct; ?>% OFF
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="wcb-pdp-divider wcb-pdp-divider--buybox">

                    <!-- ═══ FORMULÁRIO ADD-TO-CART NATIVO WOO (INTACTO) ═══ -->
                    <div class="wcb-pdp-buybox__form" id="wcb-pdp-buy-area">
                        <?php
                        /**
                         * Subtotal dinâmico (#wcb-pdp-subtotal) é impresso via hooks em inc/woocommerce.php:
                         * — variável: woocommerce_after_variations_table (acima de .woocommerce-variation-add-to-cart)
                         * — simples: woocommerce_before_add_to_cart_quantity (acima da linha qty + CTA)
                         */
                        woocommerce_template_single_add_to_cart();
                        ?>
                    </div>

                </div><!-- /.wcb-pdp-buybox -->

                    <?php
                    $wcb_fbt_html = function_exists('wcb_pdp_get_yith_fbt_html') ? wcb_pdp_get_yith_fbt_html() : '';
                    if ($wcb_fbt_html !== '' && trim(wp_strip_all_tags($wcb_fbt_html)) !== '') :
                        ?>
                    <div class="wcb-pdp-fbt-slot" data-wcb-pdp-fbt="1">
                        <?php echo $wcb_fbt_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup gerado pelo YITH/WooCommerce ?>
                    </div>
                    <?php endif; ?>

                </div><!-- /.wcb-pdp-hero__summary -->
            </section><!-- /.wcb-pdp-hero -->

            <!-- ════════════════════════════════════════════════════
                 BENEFITS STRIP
                 ════════════════════════════════════════════════════ -->
            <div class="wcb-pdp-benefits" role="list" aria-label="<?php echo esc_attr__( 'Vantagens da loja', 'wcb-theme' ); ?>">
                <div class="wcb-pdp-benefits__item" role="listitem">
                    <span class="wcb-pdp-benefits__icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" focusable="false">
                            <rect x="1" y="3" width="15" height="13" rx="1" />
                            <path d="M16 8h4l3 5v3h-7V8z" />
                            <circle cx="5.5" cy="18.5" r="2.5" />
                            <circle cx="18.5" cy="18.5" r="2.5" />
                        </svg>
                    </span>
                    <div class="wcb-pdp-benefits__body">
                        <strong>Frete Rápido para Todo o Brasil</strong>
                        <span>Entrega ágil e garantida</span>
                    </div>
                </div>
                <div class="wcb-pdp-benefits__item" role="listitem">
                    <span class="wcb-pdp-benefits__icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" focusable="false">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                    </span>
                    <div class="wcb-pdp-benefits__body">
                        <strong>Compra Protegida</strong>
                        <span>Segurança total no pagamento</span>
                    </div>
                </div>
                <div class="wcb-pdp-benefits__item" role="listitem">
                    <span class="wcb-pdp-benefits__icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" focusable="false">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                            <polyline points="9 11 12 14 22 4" />
                        </svg>
                    </span>
                    <div class="wcb-pdp-benefits__body">
                        <strong>Experiência Sem Risco</strong>
                        <span>Troca simples, rápida e garantida</span>
                    </div>
                </div>
                <div class="wcb-pdp-benefits__item" role="listitem">
                    <span class="wcb-pdp-benefits__icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" focusable="false">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                            <polyline points="9 12 11 14 15 10" />
                        </svg>
                    </span>
                    <div class="wcb-pdp-benefits__body">
                        <strong>Original de Verdade</strong>
                        <span>Sem réplicas, sem surpresas</span>
                    </div>
                </div>
            </div>

            <?php
            /*
             * Hook: woocommerce_after_single_product_summary
             * — YITH FBT: renderizado na coluna da buybox (hero); removido do hook em wcb_pdp_detach_yith_fbt_from_after_summary().
             * — Outros plugins que usem o mesmo hook
             *
             * O WooCommerce regista aqui tabs, upsells e related (wc-template-hooks.php).
             * Esta PDP já tem abas próprias (.wcb-pdp-tabs) e "Você também pode gostar";
             * remover só estes três evita conteúdo duplicado.
             */
            remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
            remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
            remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
            ?>
            <div class="wcb-pdp-after-summary">
                <?php do_action( 'woocommerce_after_single_product_summary' ); ?>
            </div>

            <!-- ════════════════════════════════════════════════════
                 3. ABAS (Descrição, Especificações, Como Usar, Avaliações)
                 ════════════════════════════════════════════════════ -->
            <section class="wcb-pdp-tabs">
                <div class="wcb-pdp-tabs__nav">
                    <button class="wcb-pdp-tab-btn active" data-tab="desc">Descrição</button>
                    <button class="wcb-pdp-tab-btn" data-tab="specs">Especificações</button>
                    <button class="wcb-pdp-tab-btn" data-tab="howto">Como Usar</button>
                    <button class="wcb-pdp-tab-btn" data-tab="reviews" id="wcb-pdp-btn-reviews">
                        Avaliações
                        <?php if ($review_count > 0): ?>
                            <span class="wcb-pdp-tab-badge"><?php echo $review_count; ?></span>
                        <?php endif; ?>
                    </button>
                </div>

                <div class="wcb-pdp-tabs__panels">
                    <!-- Descrição -->
                    <div class="wcb-pdp-tab-panel active" id="wcb-pdp-tab-desc">
                        <div class="wcb-pdp-tab-panel__content">
                            <?php the_content(); ?>
                        </div>
                    </div>

                    <!-- Especificações (Atributos nativos WooCommerce) -->
                    <div class="wcb-pdp-tab-panel" id="wcb-pdp-tab-specs">
                        <div class="wcb-pdp-tab-panel__content">
                            <?php
                            // Usa as informações adicionais nativas do Woo (tabela de atributos)
                            do_action('woocommerce_product_additional_information', $product);

                            // Fallback caso não tenha atributos
                            $attributes = $product->get_attributes();
                            if (empty($attributes)): ?>
                                <p class="wcb-pdp-empty">Especificações técnicas não disponíveis para este produto.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Como Usar (ACF: campo 'how_to_use' — tipo WYSIWYG) -->
                    <div class="wcb-pdp-tab-panel" id="wcb-pdp-tab-howto">
                        <div class="wcb-pdp-tab-panel__content">
                            <?php if ($how_to_use): ?>
                                <?php echo wp_kses_post($how_to_use); ?>
                            <?php else: ?>
                                <p class="wcb-pdp-empty">Consulte o manual do fabricante para instruções de uso.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Avaliações (WooCommerce nativo: comments_template) -->
                    <div class="wcb-pdp-tab-panel" id="wcb-pdp-tab-reviews"<?php echo $review_count < 1 ? ' data-wcb-pdp-review-form-start-hidden="1"' : ''; ?>>
                        <div class="wcb-pdp-tab-panel__content">

                            <!-- Dashboard de Avaliações -->
                            <?php
                            $avg   = (float) $wcb_rating_stats['average'];
                            $count = (int) $wcb_rating_stats['count'];
                            ?>
                            <div class="wcb-pdp-reviews-hero<?php echo $count < 1 ? ' wcb-pdp-reviews-hero--empty' : ''; ?>">
                                <?php if ($count > 0) : ?>
                                <div class="wcb-pdp-reviews-hero__score">
                                    <div class="wcb-pdp-reviews-hero__num">
                                        <?php echo number_format($avg, 1); ?>
                                    </div>
                                    <div class="wcb-pdp-reviews-hero__stars" role="img" aria-label="<?php echo esc_attr(sprintf(/* translators: %s: rating 1-5 */ __('Nota média %s de 5', 'wcb-theme'), number_format($avg, 1))); ?>">
                                        <?php for ($s = 1; $s <= 5; $s++) : ?>
                                            <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"
                                                fill="<?php echo $s <= round($avg) ? '#F59E0B' : 'none'; ?>" stroke="#F59E0B"
                                                stroke-width="1.5">
                                                <polygon
                                                    points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="wcb-pdp-reviews-hero__total">
                                        <?php echo esc_html(sprintf(/* translators: %d: number of reviews */ _n('%d avaliação', '%d avaliações', $count, 'wcb-theme'), $count)); ?>
                                    </p>
                                </div>

                                <?php else : ?>
                                <div class="wcb-pdp-reviews-hero__empty">
                                    <p class="wcb-pdp-reviews-hero__empty-kicker"><?php esc_html_e('Avaliações', 'wcb-theme'); ?></p>
                                    <h3 class="wcb-pdp-reviews-hero__empty-title"><?php esc_html_e('Seja o primeiro a opinar', 'wcb-theme'); ?></h3>
                                    <p class="wcb-pdp-reviews-hero__empty-text">
                                        <?php esc_html_e('Comprou este produto? Sua experiência ajuda outros clientes a escolher com confiança.', 'wcb-theme'); ?>
                                    </p>
                                    <div class="wcb-pdp-reviews-hero__stars wcb-pdp-reviews-hero__stars--placeholder" aria-hidden="true">
                                        <?php for ($s = 0; $s < 5; $s++) : ?>
                                            <svg width="22" height="22" viewBox="0 0 24 24">
                                                <polygon fill="#F59E0B" stroke="#F59E0B" stroke-width="1.2"
                                                    points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($count > 0):
                                    $star_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                                    $comments_q = get_comments([
                                        'post_id' => $product_id,
                                        'status' => 'approve',
                                        'type' => 'review',
                                        'parent' => 0,
                                        'number' => 0,
                                    ]);
                                    foreach ($comments_q as $c) {
                                        $r = (int) get_comment_meta($c->comment_ID, 'rating', true);
                                        if (isset($star_counts[$r]))
                                            $star_counts[$r]++;
                                    }
                                    ?>
                                    <div class="wcb-pdp-reviews-hero__bars">
                                        <?php foreach ($star_counts as $star => $n):
                                            $pct = $count > 0 ? round(($n / $count) * 100) : 0;
                                            ?>
                                            <div class="wcb-pdp-bar-row">
                                                <span class="wcb-pdp-bar-row__label"><?php echo $star; ?> ★</span>
                                                <div class="wcb-pdp-bar-row__track">
                                                    <div class="wcb-pdp-bar-row__fill" style="width:<?php echo $pct; ?>%"></div>
                                                </div>
                                                <span class="wcb-pdp-bar-row__pct"><?php echo $pct; ?>%</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="wcb-pdp-reviews-hero__cta">
                                    <?php if ($count > 0) : ?>
                                    <p class="wcb-pdp-reviews-hero__cta-lead"><?php esc_html_e('Curtiu o produto? Conte o que achou.', 'wcb-theme'); ?></p>
                                    <?php endif; ?>
                                    <button type="button" class="wcb-pdp-reviews-hero__btn" id="wcb-pdp-toggle-review"
                                        aria-controls="wcb-pdp-review-form"
                                        aria-expanded="<?php echo $count > 0 ? 'true' : 'false'; ?>">
                                        <?php esc_html_e('Escrever avaliação', 'wcb-theme'); ?>
                                    </button>
                                </div>
                            </div>

                            <!-- Lista de Reviews (WooCommerce nativo + toolbar ordenar/filtrar) -->
                            <?php
                            wcb_pdp_prime_comments_for_product();
                            if (have_comments()):
                                ?>
                                <div class="wcb-pdp-reviews-list">
                                    <div class="wcb-pdp-reviews-list__head">
                                        <h3><?php esc_html_e('O que nossos clientes dizem', 'wcb-theme'); ?></h3>
                                        <div class="wcb-pdp-reviews-toolbar" role="group"
                                            aria-label="<?php esc_attr_e('Ordenar e filtrar avaliações', 'wcb-theme'); ?>">
                                            <div class="wcb-pdp-reviews-toolbar__field">
                                                <label for="wcb-pdp-reviews-sort"
                                                    class="wcb-pdp-reviews-toolbar__label"><?php esc_html_e('Ordenar', 'wcb-theme'); ?></label>
                                                <select id="wcb-pdp-reviews-sort" class="wcb-pdp-reviews-toolbar__select">
                                                    <option value="recent"><?php esc_html_e('Mais recentes', 'wcb-theme'); ?></option>
                                                    <option value="rating-high"><?php esc_html_e('Melhor nota', 'wcb-theme'); ?></option>
                                                    <option value="rating-low"><?php esc_html_e('Menor nota', 'wcb-theme'); ?></option>
                                                    <option value="helpful"><?php esc_html_e('Mais úteis', 'wcb-theme'); ?></option>
                                                </select>
                                            </div>
                                            <div class="wcb-pdp-reviews-toolbar__field">
                                                <label for="wcb-pdp-reviews-filter"
                                                    class="wcb-pdp-reviews-toolbar__label"><?php esc_html_e('Estrelas', 'wcb-theme'); ?></label>
                                                <select id="wcb-pdp-reviews-filter" class="wcb-pdp-reviews-toolbar__select">
                                                    <option value="0"><?php esc_html_e('Todas', 'wcb-theme'); ?></option>
                                                    <option value="5">5 ★</option>
                                                    <option value="4">4 ★</option>
                                                    <option value="3">3 ★</option>
                                                    <option value="2">2 ★</option>
                                                    <option value="1">1 ★</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <ol class="commentlist">
                                        <?php
                                        wp_list_comments(apply_filters('woocommerce_product_review_list_args', [
                                            'callback' => 'woocommerce_comments',
                                        ]));
                                        ?>
                                    </ol>
                                </div>
                            <?php endif; ?>

                            <!-- Formulário de Review (WooCommerce: estrelas + texto, como single-product-reviews.php) -->
                            <?php if (comments_open()): ?>
                                <?php
                                $wcb_can_leave_review = get_option('woocommerce_review_rating_verification_required') === 'no'
                                    || wc_customer_bought_product('', get_current_user_id(), $product_id);
                                ?>
                                <div class="wcb-pdp-review-form" id="wcb-pdp-review-form"<?php echo $count < 1 ? ' hidden' : ''; ?>>
                                    <?php if ($wcb_can_leave_review) : ?>
                                        <?php
                                        $commenter = wp_get_current_commenter();
                                        $name_email_required = (bool) get_option('require_name_email', 1);
                                        $wcb_pdp_comment_form = [
                                            'title_reply' => '<span class="wcb-pdp-review-form__title">✍️ Deixe sua avaliação</span>',
                                            'title_reply_to' => __('Leave a Reply to %s', 'woocommerce'),
                                            'title_reply_before' => '<div class="wcb-pdp-review-form__header" id="reply-title">',
                                            'title_reply_after' => '</div>',
                                            'comment_notes_after' => '',
                                            'label_submit' => __('Publicar Avaliação', 'wcb-theme'),
                                            'submit_button' => '<button type="submit" class="wcb-pdp-review-form__submit">%4$s</button>',
                                            'submit_field' => '<div class="wcb-pdp-review-form__actions">%1$s %2$s</div>',
                                            'logged_in_as' => '',
                                            'comment_field' => '',
                                            'fields' => [],
                                        ];
                                        $wcb_guest_fields = [
                                            'author' => [
                                                'label' => __('Name', 'woocommerce'),
                                                'type' => 'text',
                                                'value' => $commenter['comment_author'],
                                                'required' => $name_email_required,
                                                'autocomplete' => 'name',
                                            ],
                                            'email' => [
                                                'label' => __('Email', 'woocommerce'),
                                                'type' => 'email',
                                                'value' => $commenter['comment_author_email'],
                                                'required' => $name_email_required,
                                                'autocomplete' => 'email',
                                            ],
                                        ];
                                        foreach ($wcb_guest_fields as $key => $field) {
                                            $wcb_pdp_comment_form['fields'][$key] = '<p class="comment-form-' . esc_attr($key) . '">'
                                                . '<label for="' . esc_attr($key) . '">' . esc_html($field['label'])
                                                . ($field['required'] ? '&nbsp;<span class="required">*</span>' : '')
                                                . '</label><input id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" type="'
                                                . esc_attr($field['type']) . '" autocomplete="' . esc_attr($field['autocomplete'])
                                                . '" value="' . esc_attr($field['value']) . '" size="30" '
                                                . ($field['required'] ? 'required' : '') . ' /></p>';
                                        }
                                        $account_page_url = wc_get_page_permalink('myaccount');
                                        if ($account_page_url) {
                                            $wcb_pdp_comment_form['must_log_in'] = '<p class="must-log-in">' . sprintf(
                                                esc_html__('You must be %1$slogged in%2$s to post a review.', 'woocommerce'),
                                                '<a href="' . esc_url($account_page_url) . '">',
                                                '</a>'
                                            ) . '</p>';
                                        }
                                        $wcb_rating_html = '';
                                        if (wc_review_ratings_enabled()) {
                                            $rating_req = wc_review_ratings_required();
                                            $wcb_rating_html = '<div class="comment-form-rating"><label for="rating" id="comment-form-rating-label">'
                                                . esc_html__('Your rating', 'woocommerce')
                                                . ($rating_req ? '&nbsp;<span class="required">*</span>' : '')
                                                . '</label><select name="rating" id="rating"'
                                                . ($rating_req ? ' required' : '')
                                                . '><option value="">' . esc_html__('Rate&hellip;', 'woocommerce') . '</option>'
                                                . '<option value="5">' . esc_html__('Perfect', 'woocommerce') . '</option>'
                                                . '<option value="4">' . esc_html__('Good', 'woocommerce') . '</option>'
                                                . '<option value="3">' . esc_html__('Average', 'woocommerce') . '</option>'
                                                . '<option value="2">' . esc_html__('Not that bad', 'woocommerce') . '</option>'
                                                . '<option value="1">' . esc_html__('Very poor', 'woocommerce') . '</option>'
                                                . '</select></div>';
                                        }
                                        $wcb_pdp_comment_form['comment_field'] = $wcb_rating_html
                                            . '<p class="comment-form-comment"><label for="comment">'
                                            . esc_html__('Your review', 'woocommerce') . '&nbsp;<span class="required">*</span>'
                                            . '</label><textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required></textarea></p>';
                                        comment_form(apply_filters('woocommerce_product_review_comment_form_args', $wcb_pdp_comment_form));
                                        ?>
                                    <?php else : ?>
                                        <p class="woocommerce-verification-required wcb-pdp-review-form__verification">
                                            <?php esc_html_e('Only logged in customers who have purchased this product may leave a review.', 'woocommerce'); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </section>

            <!-- ════════════════════════════════════════════════════
                 5 & 6. PRODUTOS RELACIONADOS & CROSS-SELL
                 ════════════════════════════════════════════════════ -->
            <?php
            $related_ids = wc_get_related_products($product_id, 4);
            if (!empty($related_ids)):
                $args = array(
                    'post_type' => 'product',
                    'post__in' => $related_ids,
                    'posts_per_page' => 4,
                );
                $related_products = new WP_Query($args);
                ?>
                <section class="wcb-section wcb-pdp-similar">
                    <div class="wcb-section__header">
                        <div class="wcb-section__headline">
                            <h2 class="wcb-section__title">Você também pode gostar</h2>
                        </div>
                        <div class="wcb-section__actions">
                            <a href="<?php echo esc_url(home_url('/loja/')); ?>" class="wcb-section__link">
                                Ver mais
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path d="M5 12h14M12 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                    <div class="wcb-section__content">
                        <div class="wcb-products__grid">
                            <?php
                            if ($related_products->have_posts()):
                                while ($related_products->have_posts()):
                                    $related_products->the_post();
                                    if ( function_exists( 'wc_setup_product_data' ) ) {
                                        wc_setup_product_data( get_post() );
                                    }
                                    $wcb_related = wc_get_product( get_the_ID() );
                                    if ( $wcb_related instanceof WC_Product ) {
                                        get_template_part(
                                            'template-parts/product-card',
                                            null,
                                            array( 'product' => $wcb_related )
                                        );
                                    }
                                endwhile;
                                wp_reset_postdata();
                            endif;
                            ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

        </div><!-- /.wcb-container -->
    </div><!-- /.wcb-pdp -->

    <!-- ════════════════════════════════════════════════════
         7. STICKY BUY CARD (canto inferior esquerdo quando a buybox sai de vista)
         ════════════════════════════════════════════════════ -->
    <div class="wcb-pdp-sticky" id="wcb-pdp-sticky" data-product-id="<?php echo esc_attr((string) $product_id); ?>"
        role="complementary" aria-label="<?php echo esc_attr__('Resumo rápido do produto', 'wcb-theme'); ?>">
        <div class="wcb-pdp-sticky__inner">
            <button type="button" class="wcb-pdp-sticky__close" aria-label="<?php esc_attr_e('Fechar', 'wcb-theme'); ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25"
                    stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6L6 18M6 6l12 12" />
                </svg>
            </button>
            <div class="wcb-pdp-sticky__info">
                <?php if ($thumb_id): ?>
                    <div class="wcb-pdp-sticky__thumb">
                        <?php echo wp_get_attachment_image($thumb_id, 'thumbnail', false, ['loading' => 'lazy']); ?>
                    </div>
                <?php endif; ?>
                <div class="wcb-pdp-sticky__text">
                    <span class="wcb-pdp-sticky__name"><?php echo esc_html(wp_trim_words($product_title, 14, '…')); ?></span>
                    <span class="wcb-pdp-sticky__price">
                        <?php if ($current_price > 0): ?>
                            <span class="wcb-pdp-sticky__pix-line"><strong>R$
                                    <?php echo number_format($pix_price, 2, ',', '.'); ?></strong> <span
                                    class="wcb-pdp-sticky__pix-note">no PIX</span></span>
                            <span class="wcb-pdp-sticky__card-line">
                                <?php if ($is_on_sale): ?>
                                    <del>R$ <?php echo number_format($regular_price, 2, ',', '.'); ?></del>
                                <?php endif; ?>
                                ou R$ <?php echo number_format($current_price, 2, ',', '.'); ?> em até 12x no cartão
                            </span>
                        <?php else: ?>
                            <span class="wcb-pdp-sticky__card-line">—</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <div class="wcb-pdp-sticky__action">
                <?php if ($is_in_stock): ?>
                    <button type="button" class="wcb-pdp-sticky__btn"
                        onclick="document.getElementById('wcb-pdp-buy-area').scrollIntoView({behavior:'smooth', block:'center'})">
                        <span class="wcb-pdp-sticky__btn-text"><?php echo esc_html($product->single_add_to_cart_text()); ?></span>
                        <?php echo function_exists('wcb_pdp_cta_arrow_svg') ? wcb_pdp_cta_arrow_svg() : ''; ?>
                    </button>
                <?php else: ?>
                    <span class="wcb-pdp-sticky__unavailable">Indisponível</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         VARIATION SWATCHES + CONVERSION JS
         ════════════════════════════════════════════════════ -->
    <script>
        (function () {
            'use strict';

            /* ─── 1. VARIATION SWATCHES (Premium Card Edition) ─────── */
            function initVariationSwatches() {
                var form = document.querySelector('.variations_form');
                if (!form) return;

                var rows = form.querySelectorAll('.variations tr');

                var swatchMeta = {};
                var swatchMetaEl = document.getElementById('wcb-variation-swatch-meta');
                if (swatchMetaEl && swatchMetaEl.textContent) {
                    try {
                        swatchMeta = JSON.parse(swatchMetaEl.textContent);
                    } catch (eMeta) {
                        swatchMeta = {};
                    }
                }

                function wcbSwatchEscapeUrl(url) {
                    if (!url || typeof url !== 'string') {
                        return '';
                    }
                    return url.replace(/\\/g, '/').replace(/"/g, '%22').replace(/\(/g, '%28').replace(/\)/g, '%29');
                }

                function getSwatchVisualMeta(selectEl, slug) {
                    var an = selectEl.getAttribute('data-attribute_name') || '';
                    if (!an || !swatchMeta[an] || !swatchMeta[an][slug]) {
                        return null;
                    }
                    var m = swatchMeta[an][slug];
                    var hasImg = m.image && String(m.image).length > 0;
                    var hasCol = m.color && String(m.color).length > 0;
                    if (!hasImg && !hasCol) {
                        return null;
                    }
                    return { color: hasCol ? m.color : '', image: hasImg ? m.image : '' };
                }

                // Icon SVGs by label keyword
                var iconMap = {
                    'modelo': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
                    'cor': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="10.5" r="2.5"/><circle cx="8.5" cy="7.5" r="2.5"/><circle cx="6.5" cy="12.5" r="2.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>',
                    'teor': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="9" y1="7" x2="16" y2="7"/><line x1="9" y1="11" x2="14" y2="11"/></svg>',
                    'tamanho': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 3H3v18h18V3z"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>',
                    'sabor': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>',
                    'default': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>'
                };

                function getIcon(label) {
                    var key = label.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                    for (var k in iconMap) {
                        if (k !== 'default' && key.indexOf(k) !== -1) return iconMap[k];
                    }
                    return iconMap['default'];
                }

                rows.forEach(function (row) {
                    var select = row.querySelector('select');
                    if (!select) return;

                    var td = select.closest('td');
                    if (!td) return;

                    // Mark row
                    row.classList.add('wcb-swatch-row');

                    // Get label text
                    var labelEl = row.querySelector('td.label label');
                    var labelText = labelEl ? labelEl.textContent.trim() : '';

                    // ── Build Premium Card ──
                    var card = document.createElement('div');
                    card.className = 'wcb-variation-card';

                    // Card Header
                    var header = document.createElement('div');
                    header.className = 'wcb-variation-card__header';

                    var labelWrap = document.createElement('div');
                    labelWrap.className = 'wcb-variation-card__label';

                    var hintSpan = document.createElement('span');
                    hintSpan.className = 'wcb-variation-card__hint';
                    hintSpan.id = 'wcb-hint-' + select.id;
                    hintSpan.textContent = 'Selecione';
                    labelWrap.appendChild(hintSpan);

                    header.appendChild(labelWrap);

                    card.appendChild(header);

                    // Swatch Wrap (inside card)
                    var wrap = document.createElement('div');
                    wrap.className = 'wcb-swatch-wrap';

                    // Create swatches
                    var options = select.querySelectorAll('option');
                    options.forEach(function (opt) {
                        if (!opt.value || opt.value === '') return;

                        var labelName = opt.textContent.trim();
                        var slug = opt.value;
                        var vMeta = getSwatchVisualMeta(select, slug);

                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'wcb-swatch-btn';
                        btn.setAttribute('data-value', slug);

                        if (vMeta) {
                            btn.classList.add('wcb-swatch-btn--dot');
                            btn.setAttribute('aria-label', labelName);
                            btn.title = labelName;
                            var dot = document.createElement('span');
                            dot.className = 'wcb-swatch-dot';
                            dot.setAttribute('aria-hidden', 'true');
                            if (vMeta.image) {
                                dot.style.backgroundColor = 'transparent';
                                dot.style.backgroundImage = 'url("' + wcbSwatchEscapeUrl(vMeta.image) + '")';
                                dot.style.backgroundSize = 'cover';
                                dot.style.backgroundPosition = 'center';
                            } else if (vMeta.color) {
                                dot.style.backgroundColor = vMeta.color;
                            }
                            btn.appendChild(dot);
                        } else {
                            btn.textContent = labelName;
                            btn.title = labelName;
                        }

                        btn.addEventListener('click', function () {
                            if (btn.classList.contains('is-disabled')) return;

                            // Toggle: if already active, deselect
                            if (btn.classList.contains('is-active')) {
                                btn.classList.remove('is-active');
                                jQuery(select).val('').trigger('change');

                                // Restore card header hint
                                var hint = document.getElementById('wcb-hint-' + select.id);
                                if (hint) {
                                    hint.textContent = 'Selecione';
                                    hint.classList.remove('is-selected');
                                }
                                return;
                            }

                            // Update native select via jQuery (WooCommerce listens to jQuery events)
                            jQuery(select).val(opt.value).trigger('change');

                            // Update swatch visual state
                            wrap.querySelectorAll('.wcb-swatch-btn').forEach(function (b) {
                                b.classList.remove('is-active');
                            });
                            btn.classList.add('is-active');

                            // Update card header: show selected on left
                            var hint = document.getElementById('wcb-hint-' + select.id);
                            if (hint) {
                                hint.innerHTML = 'Selecionado: <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:14px;height:14px;vertical-align:-2px;margin-right:2px;"><polyline points="20 6 9 17 4 12"/></svg>' + labelName;
                                hint.classList.add('is-selected');
                            }
                        });

                        wrap.appendChild(btn);
                    });

                    if (wrap.querySelector('.wcb-swatch-btn--dot')) {
                        wrap.classList.add('wcb-swatch-wrap--dots');
                    }

                    card.appendChild(wrap);

                    // Insert the card into the value TD
                    td.appendChild(card);

                    // If select already has a value, activate it
                    if (select.value) {
                        var activeBtn = wrap.querySelector('[data-value="' + select.value + '"]');
                        if (activeBtn) activeBtn.click();
                    }
                });

                // -- Out-of-stock check function (extracted for re-use with delays) --
                function applyInitialStockDisable() {
                    if (!form) return;
                    var variationsJson = form.getAttribute('data-product_variations');
                    if (!variationsJson || variationsJson === 'false') return;
                    try {
                        var allVariations = JSON.parse(variationsJson);
                        var inStockValues = {};
                        var outOfStockValues = {};
                        allVariations.forEach(function (v) {
                            var attrs = v.attributes || {};
                            Object.keys(attrs).forEach(function (key) {
                                var val = attrs[key];
                                if (!val) return;
                                if (v.is_in_stock) {
                                    if (!inStockValues[key]) inStockValues[key] = {};
                                    inStockValues[key][val] = true;
                                } else {
                                    if (!outOfStockValues[key]) outOfStockValues[key] = {};
                                    outOfStockValues[key][val] = true;
                                }
                            });
                        });

                        rows.forEach(function (row) {
                            var sel = row.querySelector('select');
                            var wrap = row.querySelector('.wcb-swatch-wrap');
                            if (!sel || !wrap) return;
                            var attrName = sel.getAttribute('data-attribute_name') || sel.name;

                            wrap.querySelectorAll('.wcb-swatch-btn').forEach(function (btn) {
                                var val = btn.getAttribute('data-value');
                                var hasInStock = inStockValues[attrName] && inStockValues[attrName][val];
                                var hasOutOfStock = outOfStockValues[attrName] && outOfStockValues[attrName][val];
                                if (!hasInStock && hasOutOfStock) {
                                    btn.classList.add('is-disabled');
                                }
                            });
                        });
                    } catch (e) { /* ignore */ }
                }

                // Run immediately (for fast loads)
                applyInitialStockDisable();

                // Run again after short delay to catch WooCommerce late-init
                setTimeout(applyInitialStockDisable, 100);
                setTimeout(applyInitialStockDisable, 500);

                // Also run when WooCommerce variation form initializes
                if (typeof jQuery !== 'undefined') {
                    jQuery(form).on('wc_variation_form', function () {
                        setTimeout(applyInitialStockDisable, 50);
                    });
                }

                // -- Swatch sync function (reusable) --
                function syncSwatchStates() {
                    rows.forEach(function (row) {
                        var select = row.querySelector('select');
                        var wrap = row.querySelector('.wcb-swatch-wrap');
                        if (!select || !wrap) return;

                        var currentVal = select.value;
                        var btns = wrap.querySelectorAll('.wcb-swatch-btn');

                        // Step 1: Update disabled states based on available options in native select
                        btns.forEach(function (btn) {
                            var val = btn.getAttribute('data-value');
                            var opt = select.querySelector('option[value="' + val + '"]');
                            if (!opt || opt.disabled) {
                                btn.classList.add('is-disabled');
                            } else {
                                btn.classList.remove('is-disabled');
                            }
                        });

                        // Step 2: Re-sync active state with native select value
                        btns.forEach(function (btn) {
                            var val = btn.getAttribute('data-value');
                            if (currentVal && val === currentVal) {
                                btn.classList.add('is-active');
                                btn.classList.remove('is-disabled');
                            } else if (!currentVal) {
                                btn.classList.remove('is-active');
                            } else if (val !== currentVal && btn.classList.contains('is-active')) {
                                btn.classList.remove('is-active');
                            }
                        });

                        // Step 3: Sync card header — update hint on LEFT side
                        var hint = document.getElementById('wcb-hint-' + select.id);
                        if (hint) {
                            if (currentVal) {
                                var activeOpt = select.querySelector('option[value="' + currentVal + '"]');
                                var selectedName = activeOpt ? activeOpt.textContent.trim() : currentVal;
                                hint.innerHTML = 'Selecionado: <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:14px;height:14px;vertical-align:-2px;margin-right:2px;"><polyline points="20 6 9 17 4 12"/></svg>' + selectedName;
                                hint.classList.add('is-selected');
                            } else {
                                hint.textContent = 'Selecione';
                                hint.classList.remove('is-selected');
                            }
                        }
                    });

                    // Step 4: Re-apply permanent out-of-stock disable
                    applyInitialStockDisable();
                }

                // Listen for WooCommerce variation updates — use setTimeout to run AFTER WC finishes DOM updates
                if (typeof jQuery !== 'undefined') {
                    jQuery(form).on('woocommerce_update_variation_values', function () {
                        setTimeout(syncSwatchStates, 10);
                    });

                    // Also listen to native change events on each select as fallback
                    rows.forEach(function (row) {
                        var select = row.querySelector('select');
                        if (select) {
                            jQuery(select).on('change', function () {
                                setTimeout(syncSwatchStates, 10);
                            });
                        }
                    });

                    // Listen for variation found to update price display
                    jQuery(form).on('found_variation', function (e, variation) {
                        var priceBlock = document.getElementById('wcb-pdp-price-block');
                        if (!priceBlock) return;

                        var currentEl = document.getElementById('wcb-pdp-price-current');
                        var oldEl = document.getElementById('wcb-pdp-price-old');
                        var pixEl = document.getElementById('wcb-pdp-pix-value');
                        var economizeEl = document.getElementById('wcb-pdp-economize-pix');
                        var discEl = document.getElementById('wcb-pdp-discount');

                        var price = parseFloat(variation.display_price) || 0;
                        var regular = parseFloat(variation.display_regular_price) || 0;
                        var pix = price * 0.95;

                        function wcbFormatMoney(v) {
                            return v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        }

                        if (currentEl) currentEl.textContent = 'R$ ' + wcbFormatMoney(price);
                        if (pixEl) pixEl.textContent = 'R$ ' + wcbFormatMoney(pix);
                        if (economizeEl) {
                            if (price > 0) {
                                economizeEl.textContent =
                                    'Economia de R$ ' + wcbFormatMoney(price - pix) + ' no pagamento à vista';
                                economizeEl.style.display = '';
                            } else {
                                economizeEl.style.display = 'none';
                            }
                        }

                        if (oldEl) {
                            if (regular > price) {
                                oldEl.textContent = 'De R$ ' + wcbFormatMoney(regular);
                                oldEl.style.display = '';
                            } else {
                                oldEl.style.display = 'none';
                            }
                        }

                        if (discEl) {
                            if (regular > price && regular > 0) {
                                var pct = Math.round(((regular - price) / regular) * 100);
                                discEl.textContent = '\u2212' + pct + '% OFF';
                                discEl.style.display = '';
                            } else {
                                discEl.style.display = 'none';
                            }
                        }

                        // Update PIX wrapper visibility
                        var pixWrap = document.getElementById('wcb-pdp-pix');
                        if (pixWrap) pixWrap.style.display = price > 0 ? '' : 'none';

                        // Store price for subtotal calculation
                        window.wcbCurrentVariationPrice = price;
                        updateSubtotal();
                    });

                    // Reset state 
                    jQuery(form).on('reset_data', function () {
                        rows.forEach(function (row) {
                            var wrap = row.querySelector('.wcb-swatch-wrap');
                            if (wrap) {
                                wrap.querySelectorAll('.wcb-swatch-btn').forEach(function (b) {
                                    b.classList.remove('is-active', 'is-disabled');
                                });
                            }
                            var select = row.querySelector('select');
                            if (select) {
                                // Reset card header
                                var hint = document.getElementById('wcb-hint-' + select.id);
                                if (hint) {
                                    hint.textContent = 'Selecione';
                                    hint.classList.remove('is-selected');
                                }
                            }
                        });

                        // Reset prices to base
                        var priceBlock = document.getElementById('wcb-pdp-price-block');
                        if (priceBlock) {
                            var base = parseFloat(priceBlock.getAttribute('data-base-price')) || 0;
                            var baseReg = parseFloat(priceBlock.getAttribute('data-base-regular')) || 0;
                            var currentEl = document.getElementById('wcb-pdp-price-current');
                            if (currentEl) currentEl.textContent = 'R$ ' + base.toFixed(2).replace('.', ',');
                        }

                        // Hide subtotal on reset
                        window.wcbCurrentVariationPrice = 0;
                        var subtotalEl = document.getElementById('wcb-pdp-subtotal');
                        if (subtotalEl) subtotalEl.style.display = 'none';
                    });
                }

                // ── Subtotal calculation function ──
                function updateSubtotal() {
                    var subtotalEl = document.getElementById('wcb-pdp-subtotal');
                    if (!subtotalEl) return;

                    var unitPrice = window.wcbCurrentVariationPrice || 0;
                    var qtyInput = form ? form.querySelector('input.qty') : null;
                    var qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;

                    if (unitPrice <= 0) {
                        subtotalEl.style.display = 'none';
                        return;
                    }

                    var total = unitPrice * qty;
                    var totalPix = total * 0.95;

                    var priceEl = document.getElementById('wcb-pdp-subtotal-price');
                    var qtyEl = document.getElementById('wcb-pdp-subtotal-qty');
                    var pixEl = document.getElementById('wcb-pdp-subtotal-pix-val');

                    if (priceEl) priceEl.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
                    if (qtyEl) qtyEl.textContent = '(' + qty + (qty === 1 ? ' item' : ' itens') + ')';
                    if (pixEl) pixEl.textContent = 'R$ ' + totalPix.toFixed(2).replace('.', ',') + ' no PIX (5% off)';

                    subtotalEl.style.display = '';
                }

                // Listen for quantity changes
                if (form) {
                    var qtyInput = form.querySelector('input.qty');
                    if (qtyInput) {
                        qtyInput.addEventListener('change', updateSubtotal);
                        qtyInput.addEventListener('input', updateSubtotal);
                        // Also observe WooCommerce quantity buttons
                        var qtyObserver = new MutationObserver(updateSubtotal);
                        qtyObserver.observe(qtyInput, { attributes: true, attributeFilter: ['value'] });
                    }
                }
            }

            /* ─── 2. IMPROVED QUANTITY SELECTOR ──────────────────────── */
            function initQuantityButtons() {
                var qtyInputs = document.querySelectorAll('.wcb-pdp-buybox__form .quantity input.qty');
                qtyInputs.forEach(function (input) {
                    var parent = input.parentElement;
                    if (parent.querySelector('.wcb-qty-btn')) return; // already init

                    // Remove existing WooCommerce - / + buttons if any
                    var existingBtns = parent.querySelectorAll('button:not(.wcb-qty-btn), .minus, .plus');
                    existingBtns.forEach(function (b) { b.style.display = 'none'; });

                    var minusBtn = document.createElement('button');
                    minusBtn.type = 'button';
                    minusBtn.className = 'wcb-qty-btn wcb-qty-minus';
                    minusBtn.innerHTML = '−';
                    minusBtn.setAttribute('aria-label', 'Diminuir quantidade');

                    var plusBtn = document.createElement('button');
                    plusBtn.type = 'button';
                    plusBtn.className = 'wcb-qty-btn wcb-qty-plus';
                    plusBtn.innerHTML = '+';
                    plusBtn.setAttribute('aria-label', 'Aumentar quantidade');

                    parent.insertBefore(minusBtn, input);
                    parent.appendChild(plusBtn);

                    minusBtn.addEventListener('click', function () {
                        var val = parseInt(input.value) || 1;
                        var min = parseInt(input.getAttribute('min')) || 1;
                        if (val > min) {
                            input.value = val - 1;
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });

                    plusBtn.addEventListener('click', function () {
                        var val = parseInt(input.value) || 1;
                        var max = parseInt(input.getAttribute('max')) || 9999;
                        if (val < max) {
                            input.value = val + 1;
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                });
            }

            /* ─── INIT ──────────────────────────────────────────────── */
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    initVariationSwatches();
                    initQuantityButtons();
                });
            } else {
                initVariationSwatches();
                initQuantityButtons();
            }

        })();
    </script>

    <?php
endwhile;
do_action('woocommerce_after_main_content');
get_footer();
?>