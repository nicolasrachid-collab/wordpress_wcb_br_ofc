<?php
/**
 * WCB Theme — Front Page Template
 * Página inicial da loja com seções dinâmicas
 *
 * @package WCB_Theme
 */

get_header();

/** @var int[] IDs já usados na homepage (desduplicação “homepage” nos carrosséis) */
$wcb_carousel_homepage_used_ids = array();
?>

<?php get_template_part( 'template-parts/home/hero' ); ?>


<!-- ==================== TRUST BAR ==================== -->
<section class="wcb-trust">
<div class="wcb-container">
<div class="wcb-trust__grid">

  <div class="wcb-trust__item">
      <div class="wcb-trust__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
          </svg>
      </div>
      <div class="wcb-trust__text">
          <strong>Frete Rápido para Todo o Brasil</strong>
          <span>Entrega ágil e garantida</span>
      </div>
  </div>

  <div class="wcb-trust__item">
      <div class="wcb-trust__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          </svg>
      </div>
      <div class="wcb-trust__text">
          <strong>Compra Protegida</strong>
          <span>Segurança total no pagamento</span>
      </div>
  </div>

  <div class="wcb-trust__item">
      <div class="wcb-trust__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
      </div>
      <div class="wcb-trust__text">
          <strong>Experiência Sem Risco</strong>
          <span>Troca simples, rápida e garantida</span>
      </div>
  </div>

  <div class="wcb-trust__item">
      <div class="wcb-trust__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
          </svg>
      </div>
      <div class="wcb-trust__text">
          <strong>Original de Verdade</strong>
          <span>Sem réplicas, sem surpresas</span>
      </div>
  </div>

</div>
</div>
</section>


<!-- ==================== NOVIDADES ==================== -->
<?php if (class_exists('WooCommerce')): ?>
    <section class="wcb-section">
        <div class="wcb-container">

            <?php
            // ── Novidades: cache de 12h (ids + html) ─────────────────────────
            $nov_cached = get_transient('wcb_home_novidades_v2');
            $nov_pack   = function_exists('wcb_carousel_normalize_cache') ? wcb_carousel_normalize_cache($nov_cached) : false;
            if (false === $nov_pack) {
                $novidades = new WP_Query(array(
                    'post_type'      => 'product',
                    'posts_per_page' => 30,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'meta_query'     => array(
                        array(
                            'key'     => '_stock_status',
                            'value'   => 'instock',
                            'compare' => '=',
                        ),
                    ),
                ));
                $all_pairs = function_exists('wcb_carousel_pairs_from_query') ? wcb_carousel_pairs_from_query($novidades) : array();
                $nov_pack  = array(
                    'ids'  => array_column($all_pairs, 'id'),
                    'html' => array_column($all_pairs, 'html'),
                );
                set_transient('wcb_home_novidades_v2', $nov_pack, 12 * HOUR_IN_SECONDS);
            }
            $all_pairs_n = array();
            foreach ($nov_pack['ids'] as $ni => $nid) {
                $all_pairs_n[] = array(
                    'id'   => (int) $nid,
                    'html' => isset($nov_pack['html'][$ni]) ? $nov_pack['html'][$ni] : '',
                );
            }

            // Divide em 2 linhas equilibradas (múltiplos de 5)
            $total_cards  = count($all_pairs_n);
            $half_rounded = (int) (ceil($total_cards / 2 / 5) * 5);
            if ($half_rounded >= $total_cards) {
                $half_rounded = max(5, $total_cards - 5);
            }
            $row1_pairs = array_slice($all_pairs_n, 0, $half_rounded);
            $row2_pairs = array_slice($all_pairs_n, $half_rounded);

            $dedupe_n   = function_exists('wcb_carousel_get_dedupe_scope') ? wcb_carousel_get_dedupe_scope() : 'carousel';
            $fb_mode_n  = function_exists('wcb_carousel_get_fallback_mode') ? wcb_carousel_get_fallback_mode('novidades') : 'shop';
            $fb_cat_n   = function_exists('wcb_carousel_get_fallback_cat_id') ? wcb_carousel_get_fallback_cat_id('novidades') : 0;

            $chunks_r1 = !empty($row1_pairs) ? array_chunk($row1_pairs, 5) : array();
            if (!empty($chunks_r1) && function_exists('wcb_carousel_pad_all_chunks')) {
                $chunks_r1 = wcb_carousel_pad_all_chunks($chunks_r1, 5, $dedupe_n, 'novidades', $fb_mode_n, $fb_cat_n, $wcb_carousel_homepage_used_ids, null);
            }
            $chunks_r1 = array_map('wcb_carousel_pairs_to_html_list', $chunks_r1);
            $num_pages_r1 = count($chunks_r1);

            $chunks_r2 = !empty($row2_pairs) ? array_chunk($row2_pairs, 5) : array();
            if (!empty($chunks_r2) && function_exists('wcb_carousel_pad_all_chunks')) {
                $chunks_r2 = wcb_carousel_pad_all_chunks($chunks_r2, 5, $dedupe_n, 'novidades', $fb_mode_n, $fb_cat_n, $wcb_carousel_homepage_used_ids, null);
            }
            $chunks_r2 = array_map('wcb_carousel_pairs_to_html_list', $chunks_r2);
            $num_pages_r2 = count($chunks_r2);

            $all_cards = array_column($all_pairs_n, 'html');
            ?>

            <!-- ── Linha 1: Novidades ── -->
            <div class="wcb-section__header wcb-section__header--with-controls">
                <h2 class="wcb-section__title">
                    <span class="wcb-section__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6z"/></svg>
                    </span>
                    Novidades
                </h2>
                <div class="wcb-section__actions">
                    <?php if ($num_pages_r1 > 1): ?>
                    <div class="wcb-header-carousel-controls" id="wcb-novidades-header-controls">
                        <button class="wcb-header-carousel-controls__btn" data-dir="prev" aria-label="Página anterior">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <div class="wcb-header-carousel-controls__dots">
                            <?php for ($p = 0; $p < $num_pages_r1; $p++): ?>
                            <button class="wcb-header-carousel-controls__dot<?php echo $p === 0 ? ' active' : ''; ?>"
                                    data-index="<?php echo $p; ?>"
                                    aria-label="Página <?php echo $p + 1; ?>"></button>
                            <?php endfor; ?>
                        </div>
                        <button class="wcb-header-carousel-controls__btn" data-dir="next" aria-label="Próxima página">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                    </div>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(home_url('/loja/?orderby=date')); ?>" class="wcb-section__link">
                        Ver todos
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="wcb-section__content">
            <?php if (!empty($chunks_r1)): ?>
            <div class="wcb-paged-carousel" id="wcb-novidades-carousel">
                <div class="wcb-paged-carousel__track">
                    <?php foreach ($chunks_r1 as $page_html): ?>
                    <div class="wcb-paged-carousel__slide">
                        <div class="wcb-paged-carousel__grid wcb-paged-carousel__grid--single-row">
                            <?php foreach ($page_html as $card): echo $card; endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Linha 2: Novidades (continuação) ── -->
            <?php if (!empty($chunks_r2)): ?>
            <div class="wcb-novidades-row2-header">
                <?php if ($num_pages_r2 > 1): ?>
                <div class="wcb-header-carousel-controls" id="wcb-novidades2-header-controls">
                    <button class="wcb-header-carousel-controls__btn" data-dir="prev" aria-label="Página anterior">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <div class="wcb-header-carousel-controls__dots">
                        <?php for ($p = 0; $p < $num_pages_r2; $p++): ?>
                        <button class="wcb-header-carousel-controls__dot<?php echo $p === 0 ? ' active' : ''; ?>"
                                data-index="<?php echo $p; ?>"
                                aria-label="Página <?php echo $p + 1; ?>"></button>
                        <?php endfor; ?>
                    </div>
                    <button class="wcb-header-carousel-controls__btn" data-dir="next" aria-label="Próxima página">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div class="wcb-paged-carousel" id="wcb-novidades2-carousel">
                <div class="wcb-paged-carousel__track">
                    <?php foreach ($chunks_r2 as $page_html): ?>
                    <div class="wcb-paged-carousel__slide">
                        <div class="wcb-paged-carousel__grid wcb-paged-carousel__grid--single-row">
                            <?php foreach ($page_html as $card): echo $card; endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($all_cards)): ?>
                <p style="color:var(--wcb-gray-500);padding:2rem;text-align:center;">Novos produtos em breve!</p>
            <?php endif; ?>
            </div>

        </div>
    </section>
<?php endif; ?>


<!-- ==================== PROMO BANNERS ==================== -->
<section class="wcb-promo-banners">
    <div class="wcb-container">
        <div class="wcb-promo-banners__grid">

            <!-- Banner 1 -->
            <?php
            $promo_fallback_1 = 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?q=80&w=2000&auto=format&fit=crop';
            $banner1_img      = wcb_promo_banner_image_src( 'promo_banner1_image', 'promo-banner-1.jpg', $promo_fallback_1 );
            $banner1_url      = get_theme_mod( 'promo_banner1_url', esc_url( home_url( '/loja/' ) ) );
            ?>
            <a href="<?php echo esc_url($banner1_url); ?>" class="wcb-promo-banner-card">
                <img class="wcb-promo-banner-card__img" src="<?php echo esc_url( $banner1_img ); ?>" alt="" width="1600" height="900" loading="lazy" decoding="async">
                <div class="wcb-promo-banner-card__overlay" aria-hidden="true"></div>
                <div class="wcb-promo-banner-card__body">
                    <span
                        class="wcb-promo-banner-card__badge"><?php echo esc_html(get_theme_mod('promo_banner1_badge', '🔥 Destaque')); ?></span>
                    <h3 class="wcb-promo-banner-card__title">
                        <?php echo esc_html(get_theme_mod('promo_banner1_title', 'Pods & Cartuchos')); ?>
                    </h3>
                    <p class="wcb-promo-banner-card__sub">
                        <?php echo esc_html(get_theme_mod('promo_banner1_sub', 'Os melhores sistemas de pod do mercado')); ?>
                    </p>
                    <span class="wcb-promo-banner-card__cta">
                        <?php echo esc_html(get_theme_mod('promo_banner1_cta', 'Ver coleção')); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </span>
                </div>
            </a>

            <!-- Banner 2 -->
            <?php
            $promo_fallback_2 = 'https://images.unsplash.com/photo-1585771724684-38269d6639fd?q=80&w=2000&auto=format&fit=crop';
            $banner2_img      = wcb_promo_banner_image_src( 'promo_banner2_image', 'promo-banner-2.jpg', $promo_fallback_2 );
            $banner2_url      = get_theme_mod( 'promo_banner2_url', esc_url( home_url( '/loja/?categoria=juices' ) ) );
            ?>
            <a href="<?php echo esc_url($banner2_url); ?>" class="wcb-promo-banner-card">
                <img class="wcb-promo-banner-card__img" src="<?php echo esc_url( $banner2_img ); ?>" alt="" width="1600" height="900" loading="lazy" decoding="async">
                <div class="wcb-promo-banner-card__overlay" aria-hidden="true"></div>
                <div class="wcb-promo-banner-card__body">
                    <span
                        class="wcb-promo-banner-card__badge"><?php echo esc_html(get_theme_mod('promo_banner2_badge', '⚡ Oferta')); ?></span>
                    <h3 class="wcb-promo-banner-card__title">
                        <?php echo esc_html(get_theme_mod('promo_banner2_title', 'Juices Importados')); ?>
                    </h3>
                    <p class="wcb-promo-banner-card__sub">
                        <?php echo esc_html(get_theme_mod('promo_banner2_sub', 'Sabores incríveis com até 30% OFF')); ?>
                    </p>
                    <span class="wcb-promo-banner-card__cta">
                        <?php echo esc_html(get_theme_mod('promo_banner2_cta', 'Aproveitar')); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </span>
                </div>
            </a>

        </div>
    </div>
</section>

<!-- ==================== MAIS VENDIDOS ==================== -->

<?php if (class_exists('WooCommerce')): ?>
    <section class="wcb-section">
        <div class="wcb-container">

            <?php
            // ── Mais Vendidos: cache de 12h via transient ────────────────────
            $v_cached = get_transient('wcb_home_vendidos');
            $v_pack   = function_exists('wcb_carousel_normalize_cache') ? wcb_carousel_normalize_cache($v_cached) : false;
            if (is_array($v_cached) && isset($v_cached['html']) && empty($v_cached['html'])) {
                delete_transient('wcb_home_vendidos');
                $v_pack = false;
            }
            if (false === $v_pack) {
                $vendidos = new WP_Query(array(
                    'post_type'      => 'product',
                    'posts_per_page' => 20,
                    'post_status'    => 'publish',
                    'meta_key'       => 'total_sales',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                ));
                $v_pairs = function_exists('wcb_carousel_pairs_from_query') ? wcb_carousel_pairs_from_query($vendidos) : array();

                if (empty($v_pairs)) {
                    $fallback = new WP_Query(array(
                        'post_type'      => 'product',
                        'posts_per_page' => 20,
                        'post_status'    => 'publish',
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    ));
                    $v_pairs = function_exists('wcb_carousel_pairs_from_query') ? wcb_carousel_pairs_from_query($fallback) : array();
                }

                $v_pack = array(
                    'ids'  => array_column($v_pairs, 'id'),
                    'html' => array_column($v_pairs, 'html'),
                );
                set_transient('wcb_home_vendidos', $v_pack, 12 * HOUR_IN_SECONDS);
            }

            $all_pairs_v = array();
            foreach ($v_pack['ids'] as $vi => $vid) {
                $all_pairs_v[] = array(
                    'id'   => (int) $vid,
                    'html' => isset($v_pack['html'][$vi]) ? $v_pack['html'][$vi] : '',
                );
            }

            $v_row1_pairs = array();
            $v_row2_pairs = array();
            if (!empty($all_pairs_v)) {
                foreach ($all_pairs_v as $i => $row) {
                    if ($i % 2 === 0) {
                        $v_row1_pairs[] = $row;
                    } else {
                        $v_row2_pairs[] = $row;
                    }
                }
            }

            $dedupe_v  = function_exists('wcb_carousel_get_dedupe_scope') ? wcb_carousel_get_dedupe_scope() : 'carousel';
            $fb_mode_v = function_exists('wcb_carousel_get_fallback_mode') ? wcb_carousel_get_fallback_mode('vendidos') : 'shop';
            $fb_cat_v  = function_exists('wcb_carousel_get_fallback_cat_id') ? wcb_carousel_get_fallback_cat_id('vendidos') : 0;

            $chunks_v1 = !empty($v_row1_pairs) ? array_chunk($v_row1_pairs, 3) : array();
            if (!empty($chunks_v1) && function_exists('wcb_carousel_pad_all_chunks')) {
                $chunks_v1 = wcb_carousel_pad_all_chunks($chunks_v1, 3, $dedupe_v, 'vendidos', $fb_mode_v, $fb_cat_v, $wcb_carousel_homepage_used_ids, null);
            }
            $chunks_v1 = array_map('wcb_carousel_pairs_to_html_list', $chunks_v1);

            $chunks_v2 = !empty($v_row2_pairs) ? array_chunk($v_row2_pairs, 3) : array();
            if (!empty($chunks_v2) && function_exists('wcb_carousel_pad_all_chunks')) {
                $chunks_v2 = wcb_carousel_pad_all_chunks($chunks_v2, 3, $dedupe_v, 'vendidos', $fb_mode_v, $fb_cat_v, $wcb_carousel_homepage_used_ids, null);
            }
            $chunks_v2 = array_map('wcb_carousel_pairs_to_html_list', $chunks_v2);

            $num_pages_v1 = count($chunks_v1);
            $num_pages_v2 = count($chunks_v2);
            $num_pages_v  = max($num_pages_v1, $num_pages_v2);

            $all_cards_v = array_column($all_pairs_v, 'html');
            ?>

            <div class="wcb-section__header wcb-section__header--with-controls">
                <h2 class="wcb-section__title">
                    <span class="wcb-section__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg>
                    </span>
                    Os Mais Vendidos
                </h2>
                <div class="wcb-section__actions">
                    <?php if ($num_pages_v > 1): ?>
                    <div class="wcb-header-carousel-controls" id="wcb-vendidos-header-controls">
                        <button class="wcb-header-carousel-controls__btn" data-dir="prev" aria-label="Página anterior">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <div class="wcb-header-carousel-controls__dots">
                            <?php for ($p = 0; $p < $num_pages_v; $p++) : ?>
                            <button class="wcb-header-carousel-controls__dot<?php echo $p === 0 ? ' active' : ''; ?>"
                                    data-index="<?php echo $p; ?>"
                                    aria-label="Página <?php echo $p + 1; ?>"></button>
                            <?php endfor; ?>
                        </div>
                        <button class="wcb-header-carousel-controls__btn" data-dir="next" aria-label="Próxima página">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                    </div>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(home_url('/loja/?orderby=popularity')); ?>" class="wcb-section__link">
                        Ver todos
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="wcb-section__content">
            <?php if (!empty($all_cards_v)): ?>
            <div class="wcb-vendidos-layout">

                <!-- 2 Banners estáticos empilhados (sem slide) — CSS em style.css (governança DS) -->

                <?php
                    // Banner 1 data
                    $b1_img   = get_theme_mod('ls_a_slide1_image', 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?q=80&w=2000&auto=format&fit=crop');
                    $b1_tag   = get_theme_mod('ls_a_slide1_tag', '🔥 Descubra');
                    $b1_title = get_theme_mod('ls_a_slide1_title', 'A Arte do Vape Redefinida');
                    $b1_desc  = get_theme_mod('ls_a_slide1_desc', 'Uma seleção exclusiva dos dispositivos e essências mais sofisticados do mundo.');
                    $b1_cta   = get_theme_mod('ls_a_slide1_cta', 'Explorar Coleção');
                    $b1_url   = get_theme_mod('ls_a_slide1_url', home_url('/loja/'));

                    // Banner 2 data
                    $b2_img   = get_theme_mod('ls_a_slide2_image', 'https://images.unsplash.com/photo-1585771724684-38269d6639fd?q=80&w=2000&auto=format&fit=crop');
                    $b2_tag   = get_theme_mod('ls_a_slide2_tag', '⚡ Promoção');
                    $b2_title = get_theme_mod('ls_a_slide2_title', 'Até 40% Off em Juices');
                    $b2_desc  = get_theme_mod('ls_a_slide2_desc', 'Os melhores sabores importados com desconto exclusivo.');
                    $b2_cta   = get_theme_mod('ls_a_slide2_cta', 'Ver Ofertas');
                    $b2_url   = get_theme_mod('ls_a_slide2_url', home_url('/loja/?on_sale=true'));
                ?>

                <div class="wcb-vendidos-layout__banner wcb-vendidos-layout__banner--row1">
                    <!-- Banner 1 — Blue -->
                    <a href="<?php echo esc_url($b1_url); ?>" class="wcb-vendidos-banner-card wcb-vendidos-banner-card--blue">
                        <img class="wcb-vendidos-banner-card__img" src="<?php echo esc_url($b1_img); ?>" alt="" loading="lazy">
                        <div class="wcb-vendidos-banner-card__gradient" aria-hidden="true"></div>
                        <div class="wcb-vendidos-banner-card__content">
                            <span class="wcb-vendidos-banner-card__tag"><?php echo esc_html($b1_tag); ?></span>
                            <h3 class="wcb-vendidos-banner-card__title"><?php echo wp_kses_post(preg_replace('/<br\s*\/?>/i', ' ', $b1_title)); ?></h3>
                            <p class="wcb-vendidos-banner-card__desc"><?php echo esc_html($b1_desc); ?></p>
                            <span class="wcb-vendidos-banner-card__btn">
                                <?php echo esc_html($b1_cta); ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Row 1: Carrossel independente (3 cards/slide) -->
                <div class="wcb-vendidos-layout__products wcb-vendidos-layout__products--row1">
                    <?php if (!empty($chunks_v1)): ?>
                    <div class="wcb-paged-carousel" id="wcb-vendidos-row1-carousel">
                        <div class="wcb-paged-carousel__track">
                            <?php foreach ($chunks_v1 as $page_html): ?>
                            <div class="wcb-paged-carousel__slide">
                                <div class="wcb-paged-carousel__grid wcb-paged-carousel__grid--vendidos-row">
                                    <?php foreach ($page_html as $card): echo $card; endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="wcb-vendidos-layout__banner wcb-vendidos-layout__banner--row2">
                    <!-- Banner 2 — Índigo -->
                    <a href="<?php echo esc_url($b2_url); ?>" class="wcb-vendidos-banner-card wcb-vendidos-banner-card--indigo">
                        <img class="wcb-vendidos-banner-card__img" src="<?php echo esc_url($b2_img); ?>" alt="" loading="lazy">
                        <div class="wcb-vendidos-banner-card__gradient" aria-hidden="true"></div>
                        <div class="wcb-vendidos-banner-card__content">
                            <span class="wcb-vendidos-banner-card__tag"><?php echo esc_html($b2_tag); ?></span>
                            <h3 class="wcb-vendidos-banner-card__title"><?php echo wp_kses_post(preg_replace('/<br\s*\/?>/i', ' ', $b2_title)); ?></h3>
                            <p class="wcb-vendidos-banner-card__desc"><?php echo esc_html($b2_desc); ?></p>
                            <span class="wcb-vendidos-banner-card__btn">
                                <?php echo esc_html($b2_cta); ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Row 2: Carrossel independente (3 cards/slide) -->
                <div class="wcb-vendidos-layout__products wcb-vendidos-layout__products--row2">
                    <?php if (!empty($chunks_v2)): ?>
                    <div class="wcb-paged-carousel" id="wcb-vendidos-row2-carousel">
                        <div class="wcb-paged-carousel__track">
                            <?php foreach ($chunks_v2 as $page_html): ?>
                            <div class="wcb-paged-carousel__slide">
                                <div class="wcb-paged-carousel__grid wcb-paged-carousel__grid--vendidos-row">
                                    <?php foreach ($page_html as $card): echo $card; endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
                <p style="color:var(--wcb-gray-500);padding:2rem;text-align:center;">Em breve os mais vendidos!</p>
            <?php endif; ?>
            </div>

        </div>
    </section>
<?php endif; ?>

<!-- ==================== SUPER OFERTAS + COUNTDOWN ==================== -->
<?php if (class_exists('WooCommerce')): ?>
    <?php
    // ── Cache de IDs em promoção (1h) — evita scan completo da tabela de produtos ──
    $on_sale_ids = get_transient('wcb_on_sale_ids');
    if (false === $on_sale_ids) {
        $on_sale_ids = wc_get_product_ids_on_sale();
        set_transient('wcb_on_sale_ids', $on_sale_ids, HOUR_IN_SECONDS);
    }
    $sale_end = get_theme_mod('wcb_sale_end_date', '');
    if (empty($sale_end)) {
        // Default: next Sunday at 23:59:59
        $sale_end = date('Y-m-d', strtotime('next sunday')) . 'T23:59:59';
    }

    /** URL da listagem de promoções (categoria WooCommerce), alinhada ao menu/header. */
    $wcb_ofertas_archive_url = home_url('/categoria-produto/promocoes/');
    if (function_exists('wcb_promocoes_archive_category_slug')) {
        $wcb_promo_term = get_term_by('slug', wcb_promocoes_archive_category_slug(), 'product_cat');
        if ($wcb_promo_term && ! is_wp_error($wcb_promo_term)) {
            $wcb_promo_link = get_term_link($wcb_promo_term);
            if (! is_wp_error($wcb_promo_link)) {
                $wcb_ofertas_archive_url = $wcb_promo_link;
            }
        }
    }
    ?>

    <section class="wcb-section wcb-flash-offers" id="wcb-super-ofertas" data-wcb-track-section="super-ofertas">
        <div class="wcb-container">

            <?php
            // Super Ofertas: scoring + transient único (invalida com lista em promoção + dedupe homepage).
            $so_exclude   = array_map( 'intval', $wcb_carousel_homepage_used_ids );
            $so_cache_key = ( ! empty( $on_sale_ids ) && function_exists( 'wcb_super_ofertas_cache_key' ) )
                ? wcb_super_ofertas_cache_key( $on_sale_ids, $so_exclude )
                : '';

            $so_ctx = false;
            if ( $so_cache_key !== '' ) {
                $cached = get_transient( $so_cache_key );
                $so_ctx = is_array( $cached ) && isset( $cached['hero_id'], $cached['carousel_ids'] ) ? $cached : false;
            }

            if ( false === $so_ctx && ! empty( $on_sale_ids ) && function_exists( 'wcb_super_ofertas_build_context' ) ) {
                $so_ctx = wcb_super_ofertas_build_context( $on_sale_ids, $so_exclude );
                if ( $so_cache_key !== '' && is_array( $so_ctx ) ) {
                    set_transient( $so_cache_key, $so_ctx, 8 * HOUR_IN_SECONDS );
                }
            }

            if ( ! is_array( $so_ctx ) ) {
                $so_ctx = array(
                    'hero_id'              => 0,
                    'hero_id_2'            => 0,
                    'carousel_ids'         => array(),
                    'urgency_type'         => 'default',
                    'urgency_discount_pct' => 0,
                    'hero_badge'           => '',
                    'hero_badge_2'         => '',
                );
            }

            $hero_id      = (int) ( $so_ctx['hero_id'] ?? 0 );
            $hero_id_2    = (int) ( $so_ctx['hero_id_2'] ?? 0 );
            $carousel_ids = isset( $so_ctx['carousel_ids'] ) && is_array( $so_ctx['carousel_ids'] )
                ? array_values( array_filter( array_map( 'intval', $so_ctx['carousel_ids'] ) ) )
                : array();

            $hero_product = ( $hero_id > 0 ) ? wc_get_product( $hero_id ) : null;
            if ( ! $hero_product || ! $hero_product->is_in_stock() ) {
                $hero_product = null;
                $hero_id      = 0;
            }

            $hero_product_2 = ( $hero_id_2 > 0 ) ? wc_get_product( $hero_id_2 ) : null;
            if ( ! $hero_product_2 || ! $hero_product_2->is_in_stock() ) {
                $hero_product_2 = null;
                $hero_id_2      = 0;
            }

            $so_track       = array(
                'wcb_track' => 'super-ofertas',
                'role'      => 'carousel',
            );
            $all_sale_pairs = array();
            if ( ! empty( $carousel_ids ) && function_exists( 'wcb_carousel_pairs_from_query' ) ) {
                $sale_products = new WP_Query(
                    array(
                        'post_type'              => 'product',
                        'post_status'            => 'publish',
                        'post__in'               => $carousel_ids,
                        'orderby'                => 'post__in',
                        'posts_per_page'         => count( $carousel_ids ),
                        'no_found_rows'          => true,
                        'update_post_meta_cache' => true,
                        'update_term_meta_cache' => false,
                    )
                );
                $all_sale_pairs = wcb_carousel_pairs_from_query( $sale_products, $so_track );
            }

            $carousel_sale_pairs = $all_sale_pairs;

            if ( function_exists( 'wcb_carousel_get_dedupe_scope' ) && wcb_carousel_get_dedupe_scope() === 'homepage' ) {
                foreach ( array( $hero_id, $hero_id_2 ) as $hid ) {
                    if ( $hid > 0 && ! in_array( $hid, $wcb_carousel_homepage_used_ids, true ) ) {
                        $wcb_carousel_homepage_used_ids[] = $hid;
                    }
                }
            }

            $dedupe_o  = function_exists( 'wcb_carousel_get_dedupe_scope' ) ? wcb_carousel_get_dedupe_scope() : 'carousel';
            $fb_mode_o = function_exists( 'wcb_carousel_get_fallback_mode' ) ? wcb_carousel_get_fallback_mode( 'ofertas' ) : 'shop';
            $fb_cat_o  = function_exists( 'wcb_carousel_get_fallback_cat_id' ) ? wcb_carousel_get_fallback_cat_id( 'ofertas' ) : 0;

            $o_row1_pairs = array();
            $o_row2_pairs = array();
            if ( ! empty( $carousel_sale_pairs ) ) {
                foreach ( $carousel_sale_pairs as $oi => $orow ) {
                    if ( $oi % 2 === 0 ) {
                        $o_row1_pairs[] = $orow;
                    } else {
                        $o_row2_pairs[] = $orow;
                    }
                }
            }

            $chunks_o1 = ! empty( $o_row1_pairs ) ? array_chunk( $o_row1_pairs, 3 ) : array();
            if ( ! empty( $chunks_o1 ) && function_exists( 'wcb_carousel_pad_all_chunks' ) ) {
                $chunks_o1 = wcb_carousel_pad_all_chunks( $chunks_o1, 3, $dedupe_o, 'ofertas', $fb_mode_o, $fb_cat_o, $wcb_carousel_homepage_used_ids, $on_sale_ids );
            }
            $chunks_o1 = array_map( 'wcb_carousel_pairs_to_html_list', $chunks_o1 );

            $chunks_o2 = ! empty( $o_row2_pairs ) ? array_chunk( $o_row2_pairs, 3 ) : array();
            if ( ! empty( $chunks_o2 ) && function_exists( 'wcb_carousel_pad_all_chunks' ) ) {
                $chunks_o2 = wcb_carousel_pad_all_chunks( $chunks_o2, 3, $dedupe_o, 'ofertas', $fb_mode_o, $fb_cat_o, $wcb_carousel_homepage_used_ids, $on_sale_ids );
            }
            $chunks_o2 = array_map( 'wcb_carousel_pairs_to_html_list', $chunks_o2 );

            // Estoque agregado real (só produtos em oferta com gestão de stock e quantidade > 0)
            $wcb_ofertas_managed_stock_sum = 0;
            $wcb_ofertas_managed_count     = 0;
            if ( ! empty( $on_sale_ids ) ) {
                foreach ( array_slice( array_map( 'intval', (array) $on_sale_ids ), 0, 50 ) as $sid ) {
                    if ( $sid < 1 ) {
                        continue;
                    }
                    $sp = wc_get_product( $sid );
                    if ( ! $sp || ! $sp->is_in_stock() || ! $sp->managing_stock() ) {
                        continue;
                    }
                    $sq = $sp->get_stock_quantity();
                    if ( $sq !== null && (int) $sq > 0 ) {
                        $wcb_ofertas_managed_stock_sum += (int) $sq;
                        $wcb_ofertas_managed_count++;
                    }
                }
            }

            $wcb_so_urgency_type = isset( $so_ctx['urgency_type'] ) ? (string) $so_ctx['urgency_type'] : 'default';
            $wcb_so_urgency_pct  = (int) ( $so_ctx['urgency_discount_pct'] ?? 0 );
            ?>

            <!-- ══ HEADER — título + countdown + CTA (estilos em style.css) ══ -->
            <div class="wcb-section__header wcb-section__header--with-controls wcb-section__header--ofertas">
                <div class="wcb-flash-ofertas-head">
                    <span class="wcb-flash-ofertas-kicker"><?php esc_html_e( 'Oferta por tempo limitado', 'wcb-theme' ); ?></span>
                    <h2 class="wcb-section__title wcb-flash-ofertas-title">
                        <span class="wcb-section__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        </span>
                        <?php esc_html_e( 'Super Ofertas', 'wcb-theme' ); ?>
                    </h2>
                    <p class="wcb-flash-ofertas-sub"><?php esc_html_e( 'Descontos reais enquanto durar o estoque — pague menos no PIX.', 'wcb-theme' ); ?></p>
                </div>

                <div class="wcb-flash-countdown-inline">
                    <span class="wcb-flash-countdown-inline__label"><?php esc_html_e( 'Acaba em', 'wcb-theme' ); ?></span>
                    <div class="wcb-flash-countdown-inline__boxes" id="wcb-countdown" data-end="<?php echo esc_attr($sale_end); ?>">
                        <div class="wcb-flash-countdown-inline__box"><span id="countdown-days">00</span></div>
                        <span class="wcb-flash-countdown-inline__sep">:</span>
                        <div class="wcb-flash-countdown-inline__box"><span id="countdown-hours">00</span></div>
                        <span class="wcb-flash-countdown-inline__sep">:</span>
                        <div class="wcb-flash-countdown-inline__box"><span id="countdown-minutes">00</span></div>
                        <span class="wcb-flash-countdown-inline__sep">:</span>
                        <div class="wcb-flash-countdown-inline__box"><span id="countdown-seconds">00</span></div>
                    </div>
                </div>

                <div class="wcb-section__actions">
                    <a href="<?php echo esc_url($wcb_ofertas_archive_url); ?>" class="wcb-section__link wcb-section__link--ofertas">
                        <?php esc_html_e( 'Ver todas as ofertas', 'wcb-theme' ); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="wcb-section__content">
            <div class="wcb-flash-urgency-strip" role="status">
                <span class="wcb-flash-urgency-strip__pulse" aria-hidden="true"></span>
                <span class="wcb-flash-urgency-strip__text" data-wcb-so-urgency="<?php echo esc_attr( $wcb_so_urgency_type ); ?>">
                    <?php
                    if ( 'low_stock' === $wcb_so_urgency_type ) {
                        esc_html_e( 'Últimas unidades disponíveis — garanta o seu antes de esgotar.', 'wcb-theme' );
                    } elseif ( 'high_discount' === $wcb_so_urgency_type && $wcb_so_urgency_pct > 0 ) {
                        printf(
                            /* translators: %s: máximo desconto percentual no conjunto em promoção */
                            esc_html__( 'Ofertas com até %s%% OFF enquanto durar o estoque.', 'wcb-theme' ),
                            esc_html( (string) $wcb_so_urgency_pct )
                        );
                    } elseif ( 'high_sales' === $wcb_so_urgency_type ) {
                        esc_html_e( 'Produtos mais procurados — seleção em destaque por tempo limitado.', 'wcb-theme' );
                    } elseif ( $wcb_ofertas_managed_stock_sum > 0 ) {
                        $shown = min( (int) $wcb_ofertas_managed_stock_sum, 99999 );
                        printf(
                            /* translators: 1: approximate stock units, 2: number of products with managed stock */
                            esc_html__(
                                'Estoque promocional contabilizado: cerca de %1$s unidades em %2$s produto(s) com gestão de stock — oferta enquanto durar.',
                                'wcb-theme'
                            ),
                            number_format_i18n( $shown ),
                            number_format_i18n( max( 1, (int) $wcb_ofertas_managed_count ) )
                        );
                    } else {
                        esc_html_e(
                            'Ofertas sujeitas ao estoque da loja. Garanta o seu antes de esgotar.',
                            'wcb-theme'
                        );
                    }
                    ?>
                </span>
                <div class="wcb-flash-urgency-strip__bar" aria-hidden="true">
                    <div class="wcb-flash-urgency-strip__fill wcb-flash-urgency-strip__fill--pulse"></div>
                </div>
            </div>

            <!-- ══ Layout tipo Mais Vendidos: 2 heroes + 2 carrosséis (3 cards/slide) ══ -->
            <?php if ($hero_product): ?>
            <div class="wcb-vendidos-layout wcb-flash-ofertas-layout">

                <div class="wcb-vendidos-layout__banner wcb-vendidos-layout__banner--row1">
                    <?php
                    get_template_part(
                        'template-parts/flash-hero-cro',
                        null,
                        array(
                            'product'    => $hero_product,
                            'eager_lcp' => true,
                            'hero_badge' => isset( $so_ctx['hero_badge'] ) ? (string) $so_ctx['hero_badge'] : '',
                        )
                    );
                    ?>
                </div>

                <div class="wcb-vendidos-layout__products wcb-vendidos-layout__products--row1">
                    <?php if (!empty($chunks_o1)): ?>
                    <div class="wcb-paged-carousel" id="wcb-ofertas-row1-carousel" role="region"
                        aria-roledescription="<?php echo esc_attr__( 'Carrossel', 'wcb-theme' ); ?>"
                        aria-label="<?php echo esc_attr__( 'Mais ofertas — primeira linha', 'wcb-theme' ); ?>">
                        <div class="wcb-paged-carousel__track">
                            <?php foreach ($chunks_o1 as $page_html): ?>
                            <div class="wcb-paged-carousel__slide">
                                <div class="wcb-paged-carousel__grid wcb-paged-carousel__grid--vendidos-row">
                                    <?php foreach ($page_html as $card): echo $card; endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="wcb-vendidos-layout__banner wcb-vendidos-layout__banner--row2">
                    <?php if ($hero_product_2): ?>
                        <?php
                        get_template_part(
                            'template-parts/flash-hero-cro',
                            null,
                            array(
                                'product'    => $hero_product_2,
                                'hero_badge' => isset( $so_ctx['hero_badge_2'] ) ? (string) $so_ctx['hero_badge_2'] : '',
                            )
                        );
                        ?>
                    <?php endif; ?>
                </div>

                <div class="wcb-vendidos-layout__products wcb-vendidos-layout__products--row2">
                    <?php if (!empty($chunks_o2)): ?>
                    <div class="wcb-paged-carousel" id="wcb-ofertas-row2-carousel" role="region"
                        aria-roledescription="<?php echo esc_attr__( 'Carrossel', 'wcb-theme' ); ?>"
                        aria-label="<?php echo esc_attr__( 'Mais ofertas — segunda linha', 'wcb-theme' ); ?>">
                        <div class="wcb-paged-carousel__track">
                            <?php foreach ($chunks_o2 as $page_html): ?>
                            <div class="wcb-paged-carousel__slide">
                                <div class="wcb-paged-carousel__grid wcb-paged-carousel__grid--vendidos-row">
                                    <?php foreach ($page_html as $card): echo $card; endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
                <p style="color:var(--wcb-gray-500);padding:2rem;text-align:center;">Nenhuma oferta disponível no momento. Volte em breve!</p>
            <?php endif; ?>
            </div>

        </div>
    </section>
<?php endif; ?>


<!-- ==================== DE VOLTA AO ESTOQUE ==================== -->
<?php if (class_exists('WooCommerce')): ?>
    <section class="wcb-section">
        <div class="wcb-container">

            <?php
            // ── De Volta ao Estoque: cache de 12h (ids + html) ─────────────
            $q_cached = get_transient('wcb_home_estoque');
            $q_pack     = function_exists('wcb_carousel_normalize_cache') ? wcb_carousel_normalize_cache($q_cached) : false;
            if (false === $q_pack) {
                $queridinhos = new WP_Query(array(
                    'post_type'      => 'product',
                    'posts_per_page' => 20,
                    'meta_key'       => '_wc_average_rating',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array(
                            'key'     => '_stock_status',
                            'value'   => 'instock',
                            'compare' => '=',
                        ),
                    ),
                ));
                $q_pairs = function_exists('wcb_carousel_pairs_from_query') ? wcb_carousel_pairs_from_query($queridinhos) : array();
                $q_pack  = array(
                    'ids'  => array_column($q_pairs, 'id'),
                    'html' => array_column($q_pairs, 'html'),
                );
                set_transient('wcb_home_estoque', $q_pack, 12 * HOUR_IN_SECONDS);
            }
            $all_pairs_q = array();
            foreach ($q_pack['ids'] as $qi => $qid) {
                $all_pairs_q[] = array(
                    'id'   => (int) $qid,
                    'html' => isset($q_pack['html'][$qi]) ? $q_pack['html'][$qi] : '',
                );
            }
            $dedupe_q  = function_exists('wcb_carousel_get_dedupe_scope') ? wcb_carousel_get_dedupe_scope() : 'carousel';
            $fb_mode_q = function_exists('wcb_carousel_get_fallback_mode') ? wcb_carousel_get_fallback_mode('estoque') : 'shop';
            $fb_cat_q  = function_exists('wcb_carousel_get_fallback_cat_id') ? wcb_carousel_get_fallback_cat_id('estoque') : 0;

            $chunks_q = !empty($all_pairs_q) ? array_chunk($all_pairs_q, 5) : array();
            if (!empty($chunks_q) && function_exists('wcb_carousel_pad_all_chunks')) {
                $chunks_q = wcb_carousel_pad_all_chunks($chunks_q, 5, $dedupe_q, 'estoque', $fb_mode_q, $fb_cat_q, $wcb_carousel_homepage_used_ids, null);
            }
            $chunks_q = array_map('wcb_carousel_pairs_to_html_list', $chunks_q);
            $num_pages_q = count($chunks_q);
            $all_cards_q = array_column($all_pairs_q, 'html');
            ?>

            <div class="wcb-section__header wcb-section__header--with-controls">
                <h2 class="wcb-section__title">
                    <span class="wcb-section__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    </span>
                    De Volta ao Estoque
                </h2>
                <div class="wcb-section__actions">
                    <?php if ($num_pages_q > 1): ?>
                    <div class="wcb-header-carousel-controls" id="wcb-estoque-header-controls">
                        <button class="wcb-header-carousel-controls__btn" data-dir="prev" aria-label="Página anterior">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <div class="wcb-header-carousel-controls__dots">
                            <?php for ($p = 0; $p < $num_pages_q; $p++): ?>
                            <button class="wcb-header-carousel-controls__dot<?php echo $p === 0 ? ' active' : ''; ?>"
                                    data-index="<?php echo $p; ?>"
                                    aria-label="Página <?php echo $p + 1; ?>"></button>
                            <?php endfor; ?>
                        </div>
                        <button class="wcb-header-carousel-controls__btn" data-dir="next" aria-label="Próxima página">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                    </div>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(home_url('/loja/?orderby=rating')); ?>" class="wcb-section__link">
                        Ver todos
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="wcb-section__content">
            <?php if (!empty($chunks_q)): ?>
            <div class="wcb-paged-carousel" id="wcb-estoque-carousel">
                <div class="wcb-paged-carousel__track">
                    <?php foreach ($chunks_q as $page_html): ?>
                    <div class="wcb-paged-carousel__slide">
                        <div class="wcb-paged-carousel__grid">
                            <?php foreach ($page_html as $card): echo $card; endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
                <p style="color:var(--wcb-gray-500);padding:2rem;text-align:center;">Produtos em breve!</p>
            <?php endif; ?>
            </div>

        </div>
    </section>
<?php endif; ?>



<script>
(function () {
    'use strict';

    function initLifestyleSlider(innerId, prefix) {
        var inner   = document.getElementById(innerId);
        if (!inner) return;

        var slides   = inner.querySelectorAll('.wcb-lifestyle__slides .wcb-slide');
        var dotsWrap = document.getElementById(prefix + '-dots');
        var tag      = document.getElementById(prefix + '-tag');
        var title    = document.getElementById(prefix + '-title');
        var desc     = document.getElementById(prefix + '-desc');
        var cta      = document.getElementById(prefix + '-cta');
        var content  = document.getElementById(prefix + '-content');
        var prevBtn  = document.getElementById(prefix + '-prev');
        var nextBtn  = document.getElementById(prefix + '-next');

        if (!slides.length || !dotsWrap) return;

        var total   = slides.length;
        var current = 0;
        var timer   = null;
        var dots    = [];
        var counter = null;

        for (var i = 0; i < total; i++) {
            (function (idx) {
                var dot = document.createElement('button');
                dot.className = 'wcb-ls-dot' + (idx === 0 ? ' active' : '');
                dot.setAttribute('aria-label', 'Slide ' + (idx + 1));
                dot.addEventListener('click', function () { goTo(idx); resetTimer(); });
                dotsWrap.appendChild(dot);
                dots.push(dot);
            })(i);
        }
        counter = document.createElement('span');
        counter.className = 'wcb-ls-counter';
        counter.textContent = '01/' + String(total).padStart(2, '0');
        dotsWrap.appendChild(counter);

        function applySlide(idx) {
            var s = slides[idx];
            var rootStyle = getComputedStyle(document.documentElement);
            var tagColor = s.getAttribute('data-tag-color') || rootStyle.getPropertyValue('--wcb-lifestyle-tag-default').trim();
            var btnColor = s.getAttribute('data-btn-color') || rootStyle.getPropertyValue('--wcb-lifestyle-btn-default').trim();
            var btnHover = s.getAttribute('data-btn-hover') || rootStyle.getPropertyValue('--wcb-lifestyle-btn-hover-default').trim();

            tag.textContent  = s.getAttribute('data-tag') || '';
            tag.style.color  = tagColor;
            title.innerHTML  = s.getAttribute('data-title') || '';
            desc.textContent = s.getAttribute('data-desc') || '';
            cta.textContent  = s.getAttribute('data-cta') || 'Ver mais';

            cta.innerHTML = cta.textContent + ' <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';

            cta.href             = s.getAttribute('data-url') || '#';
            cta.style.background = btnColor;

            cta.onmouseenter = function () { cta.style.background = btnHover; };
            cta.onmouseleave = function () { cta.style.background = btnColor; };
        }

        function goTo(idx) {
            if (idx === current) return;
            content.classList.add('wcb-ls-exit');
            var prev = current;
            current  = (idx + total) % total;
            slides[prev].classList.remove('active');
            slides[current].classList.add('active');
            dots[prev].classList.remove('active');
            dots[current].classList.add('active');
            counter.textContent = String(current + 1).padStart(2, '0') + '/' + String(total).padStart(2, '0');
            setTimeout(function () {
                content.classList.remove('wcb-ls-exit');
                content.classList.add('wcb-ls-enter');
                applySlide(current);
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        content.classList.remove('wcb-ls-enter');
                    });
                });
            }, 420);
        }

        function next() { goTo(current + 1); }
        function prev() { goTo(current - 1); }
        function startTimer() { timer = setInterval(next, 5000); }
        function resetTimer() { clearInterval(timer); startTimer(); }

        if (nextBtn) nextBtn.addEventListener('click', function () { next(); resetTimer(); });
        if (prevBtn) prevBtn.addEventListener('click', function () { prev(); resetTimer(); });

        applySlide(0);
        startTimer();
    }

    // Initialize both sliders
    initLifestyleSlider('wcb-ls-a-inner', 'wcb-ls-a');
    initLifestyleSlider('wcb-ls-b-inner', 'wcb-ls-b');

    // ── Paged Carousel (grupos de produtos) ─────────────────────
    // Delays individuais por seção (configuráveis via Personalizador)
    var wcbDelays = {
        'wcb-novidades-carousel':  <?php echo max(1, (int) get_theme_mod('wcb_carousel_delay', 3)); ?> * 1000,
        'wcb-novidades2-carousel': <?php echo max(1, (int) get_theme_mod('wcb_carousel_delay_novidades2', 4)); ?> * 1000,
        'wcb-vendidos-carousel':   <?php echo max(1, (int) get_theme_mod('wcb_carousel_delay_vendidos', 5)); ?> * 1000,
        'wcb-ofertas-row1-carousel': <?php echo max(1, (int) get_theme_mod('wcb_carousel_delay_ofertas', 4)); ?> * 1000,
        'wcb-ofertas-row2-carousel': <?php echo max(1, (int) get_theme_mod('wcb_carousel_delay_ofertas', 4)); ?> * 1000,
        'wcb-estoque-carousel':    <?php echo max(1, (int) get_theme_mod('wcb_carousel_delay_estoque', 6)); ?> * 1000
    };

    /* Estilos de navegação dos carrosséis: style.css (bloco “Governança DS”) */

    function initPagedCarousel(carouselId) {
        var carousel = document.getElementById(carouselId);
        if (!carousel) return null;

        var track   = carousel.querySelector('.wcb-paged-carousel__track');
        var total   = carousel.querySelectorAll('.wcb-paged-carousel__slide').length;

        if (!track || total === 0) return null;
        if (total < 2) return null; // No navigation needed for single slide

        // ── Inject viewport wrapper to contain overflow ──
        var viewport = document.createElement('div');
        viewport.className = 'wcb-carousel-viewport';
        track.parentNode.insertBefore(viewport, track);
        viewport.appendChild(track);

        var current   = 0;
        var autoTimer = null;
        var progressTimer = null;
        var DELAY     = wcbDelays[carouselId] || 3000;
        var progressStart = 0;

        // ── Inject floating arrows ──
        var prevArrow = document.createElement('button');
        prevArrow.type = 'button';
        prevArrow.className = 'wcb-carousel-arrow wcb-carousel-arrow--prev';
        prevArrow.setAttribute('aria-label', 'Anterior');
        prevArrow.innerHTML = '<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>';
        carousel.appendChild(prevArrow);

        var nextArrow = document.createElement('button');
        nextArrow.type = 'button';
        nextArrow.className = 'wcb-carousel-arrow wcb-carousel-arrow--next';
        nextArrow.setAttribute('aria-label', 'Próximo');
        nextArrow.innerHTML = '<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>';
        carousel.appendChild(nextArrow);

        // ── Inject progress bar ──
        var progressBar = document.createElement('div');
        progressBar.className = 'wcb-carousel-progress';
        for (var i = 0; i < total; i++) {
            var seg = document.createElement('div');
            seg.className = 'wcb-carousel-progress__segment' + (i === 0 ? ' active' : '');
            seg.setAttribute('data-index', i);
            var fill = document.createElement('div');
            fill.className = 'wcb-carousel-progress__fill';
            seg.appendChild(fill);
            progressBar.appendChild(seg);
        }
        carousel.appendChild(progressBar);

        var segments = progressBar.querySelectorAll('.wcb-carousel-progress__segment');
        var fills = progressBar.querySelectorAll('.wcb-carousel-progress__fill');

        // ── Navigation ──
        function goTo(idx) {
            if (idx < 0)      idx = total - 1;
            if (idx >= total) idx = 0;
            current = idx;
            track.style.transform = 'translateX(-' + (current * 100) + '%)';

            // Update progress segments
            segments.forEach(function (seg, i) {
                seg.classList.remove('active', 'done');
                fills[i].style.transition = 'none';
                fills[i].style.width = '0%';
                if (i < current) seg.classList.add('done');
                if (i === current) seg.classList.add('active');
            });
        }

        // ── Progress bar animation ──
        function animateProgress() {
            var fill = fills[current];
            if (!fill) return;

            fill.style.transition = 'none';
            fill.style.width = '0%';

            // Force reflow
            void fill.offsetWidth;

            fill.style.transition = 'width ' + DELAY + 'ms linear';
            fill.style.width = '100%';
        }

        // ── Autoplay with progress ──
        function startAuto() {
            stopAuto();
            animateProgress();
            autoTimer = setTimeout(function autoNext() {
                goTo(current + 1);
                animateProgress();
                autoTimer = setTimeout(autoNext, DELAY);
            }, DELAY);
        }

        function stopAuto() {
            if (autoTimer) { clearTimeout(autoTimer); autoTimer = null; }
            // Pause progress animation
            fills.forEach(function(f) {
                var w = f.getBoundingClientRect().width;
                var pW = f.parentElement.getBoundingClientRect().width;
                f.style.transition = 'none';
                f.style.width = (pW > 0 ? (w / pW * 100) : 0) + '%';
            });
        }

        function resetAuto() { startAuto(); }

        // ── Event listeners ──
        prevArrow.addEventListener('click', function () { goTo(current - 1); resetAuto(); });
        nextArrow.addEventListener('click', function () { goTo(current + 1); resetAuto(); });

        segments.forEach(function (seg, i) {
            seg.addEventListener('click', function () { goTo(i); resetAuto(); });
        });

        // Pause on hover
        carousel.addEventListener('mouseenter', stopAuto);
        carousel.addEventListener('mouseleave', startAuto);

        // ── Initialize ──
        goTo(0);
        startAuto();

        return { goTo: goTo, resetAuto: resetAuto, getCurrent: function () { return current; } };
    }

    // Inicializa cada carrossel
    var carouselApis = {};
    carouselApis['wcb-novidades-carousel']  = initPagedCarousel('wcb-novidades-carousel');
    carouselApis['wcb-novidades2-carousel'] = initPagedCarousel('wcb-novidades2-carousel');
    carouselApis['wcb-vendidos-row1-carousel'] = initPagedCarousel('wcb-vendidos-row1-carousel');
    carouselApis['wcb-vendidos-row2-carousel'] = initPagedCarousel('wcb-vendidos-row2-carousel');
    carouselApis['wcb-ofertas-row1-carousel'] = initPagedCarousel('wcb-ofertas-row1-carousel');
    carouselApis['wcb-ofertas-row2-carousel'] = initPagedCarousel('wcb-ofertas-row2-carousel');
    carouselApis['wcb-estoque-carousel']    = initPagedCarousel('wcb-estoque-carousel');

})();
</script>



<?php
get_template_part('template-parts/section-depoimentos');
?>

<!-- ==================== DEPARTMENTS GRID ==================== -->
<section class="wcb-section wcb-section--categories">
    <div class="wcb-container">
        <div class="wcb-section__header">
            <div class="wcb-section__headline">
                <h2 class="wcb-section__title"><?php esc_html_e('Escolha por Categoria', 'wcb-theme'); ?></h2>
                <p class="wcb-section__subtitle"><?php esc_html_e('Navegue pelas categorias mais procuradas da loja.', 'wcb-theme'); ?></p>
            </div>
            <div class="wcb-section__actions">
                <a href="<?php echo esc_url(home_url('/loja/')); ?>" class="wcb-section__link">
                    <?php esc_html_e('Ver todos', 'wcb-theme'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="wcb-section__content">
        <div class="wcb-departments__grid">
            <?php
            /**
             * Retorna o SVG inline para cada chave de ícone definida no Customizer.
             */
            function wcb_get_dept_icon( string $key ): string {
                $icons = array(
                    'pods'       => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="3"/><line x1="10" y1="6" x2="14" y2="6"/><line x1="10" y1="18" x2="14" y2="18"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>',
                    'coils'      => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="3" y="6" width="18" height="14" rx="1"/><rect x="5" y="8" width="6" height="5" rx="0.5" fill="white" opacity="0.85"/><rect x="13" y="8" width="6" height="5" rx="0.5" fill="white" opacity="0.85"/><rect x="3" y="13" width="18" height="1.5"/><rect x="5" y="18" width="4" height="1.5" rx="0.5" fill="white" opacity="0.7"/><rect x="15" y="18" width="4" height="1.5" rx="0.5" fill="white" opacity="0.7"/><rect x="5" y="2" width="14" height="5" rx="3"/><rect x="2" y="9" width="1.5" height="4" rx="0.5"/><rect x="20.5" y="9" width="1.5" height="4" rx="0.5"/></svg>',
                    'juice'      => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C6 8 4 12 4 15a8 8 0 0 0 16 0c0-3-2-7-8-13z"/><path d="M9.5 15.5a3 3 0 0 0 3 2.5"/></svg>',
                    'kit'        => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
                    'atomizador' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="5" ry="2"/><rect x="7" y="5" width="10" height="13" rx="0"/><ellipse cx="12" cy="18" rx="5" ry="2"/><line x1="12" y1="18" x2="12" y2="22"/></svg>',
                    'acessorios' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M9.5 2.5 Q10.5 1 11.5 2.5 Q12.5 4 13.5 2.5" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><rect x="10" y="3.5" width="4" height="2.5" rx="0.5"/><rect x="7.5" y="6" width="9" height="1.2" rx="0.3"/><rect x="9.5" y="7.2" width="5" height="3.3" rx="0.4"/><rect x="6.5" y="10.2" width="11" height="1.2" rx="0.3"/><rect x="4" y="11.4" width="16" height="11" rx="2"/><rect x="6" y="13" width="12" height="7.5" rx="1" fill="white" opacity="0.9"/><rect x="4" y="15" width="2" height="3" rx="0.5" fill="white" opacity="0.6"/><circle cx="12" cy="16.5" r="2.2" fill="currentColor"/><rect x="8.5" y="19.2" width="5" height="1" rx="0.5" fill="currentColor" opacity="0.5"/></svg>',
                    'carrinho'   => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
                    'estrela'    => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
                    'tag'        => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
                    'coracao'    => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
                );
                return $icons[ $key ] ?? $icons['carrinho'];
            }

            // ── Renderiza os 6 cards via Customizer ──────────────────────
            $cat_defaults_tpl = array(
                1 => array( 'name' => 'Pods Descartáveis', 'url' => home_url('/loja/?categoria=pods-descartaveis'), 'icon' => 'pods',      'bg' => '#eff6ff', 'ic' => '#155dfd' ),
                2 => array( 'name' => 'Coils e Cartuchos', 'url' => home_url('/loja/?categoria=coils-cartuchos'),  'icon' => 'coils',     'bg' => '#dbeafe', 'ic' => '#2563eb' ),
                3 => array( 'name' => 'Juices',             'url' => home_url('/loja/?categoria=juices'),           'icon' => 'juice',     'bg' => '#e0f2fe', 'ic' => '#0ea5e9' ),
                4 => array( 'name' => 'Kits e Aparelhos',   'url' => home_url('/loja/?categoria=kits'),             'icon' => 'kit',       'bg' => '#dbeafe', 'ic' => '#1d4ed8' ),
                5 => array( 'name' => 'Atomizadores',        'url' => home_url('/loja/?categoria=atomizadores'),     'icon' => 'atomizador','bg' => '#e0f2fe', 'ic' => '#0369a1' ),
                6 => array( 'name' => 'Acessórios',          'url' => home_url('/loja/?categoria=acessorios'),       'icon' => 'acessorios','bg' => '#eff6ff', 'ic' => '#3b82f6' ),
            );
            for ( $n = 1; $n <= 6; $n++ ) :
                $prefix = "cat_card{$n}";
                $def    = $cat_defaults_tpl[ $n ];

                // Categoria WooCommerce selecionada
                $cat_slug  = get_theme_mod( "{$prefix}_cat_slug", '' );
                $cat_term  = ( $cat_slug && class_exists('WooCommerce') ) ? get_term_by( 'slug', $cat_slug, 'product_cat' ) : null;

                // Nome: usa nome personalizado → nome da categoria → default
                $custom_name = get_theme_mod( "{$prefix}_name", '' );
                if ( $custom_name ) {
                    $name = $custom_name;
                } elseif ( $cat_term && ! is_wp_error($cat_term) ) {
                    $name = $cat_term->name;
                } else {
                    $name = $def['name'];
                }

                // URL: usa link da categoria → link manual → default
                if ( $cat_term && ! is_wp_error($cat_term) ) {
                    $url = get_term_link( $cat_term );
                } else {
                    $url = get_theme_mod( "{$prefix}_url", $def['url'] );
                    if ( is_wp_error( $url ) ) $url = $def['url'];
                }

                // Visual
                $icon       = get_theme_mod( "{$prefix}_icon",       $def['icon'] );
                $bg_color   = get_theme_mod( "{$prefix}_bg_color",   $def['bg'] );
                $icon_color = get_theme_mod( "{$prefix}_icon_color", $def['ic'] );

                // Contagem de produtos
                $count_html = '';
                $count_term = $cat_term ?? ( $cat_slug ? null : null );
                if ( ! $count_term && class_exists('WooCommerce') ) {
                    // Tenta extrair do link manual
                    parse_str( parse_url( is_string($url) ? $url : '', PHP_URL_QUERY ) ?? '', $qs );
                    $slug_from_url = $qs['categoria'] ?? '';
                    if ( $slug_from_url ) {
                        $count_term = get_term_by( 'slug', $slug_from_url, 'product_cat' );
                    }
                }
                if ( $count_term && ! is_wp_error($count_term) ) {
                    $count_html = $count_term->count . ' produto' . ( $count_term->count !== 1 ? 's' : '' );
                }
            ?>
                <?php
                // Descrições curtas por categoria
                $cat_descriptions = array(
                    1 => 'Os melhores pods descartáveis do mercado',
                    2 => 'Reposição para seu dispositivo',
                    3 => 'Sabores premium para vape',
                    4 => 'Pods de longa duração',
                    5 => 'Para personalizar sua experiência',
                    6 => 'Tudo para seu kit completo',
                );
                $icon_color_s = sanitize_hex_color( $icon_color );
                $bg_color_s   = sanitize_hex_color( $bg_color );

                // Juices: não usar fundo dourado/âmbar guardado no Customizer — volta ao azul do tema.
                $wcb_dept_gold_bgs = array( '#dd9221', '#d97706', '#ca8a04', '#f59e0b', '#eab308', '#fbbf24', '#b45309', '#d4a574' );
                if ( $bg_color_s && in_array( strtolower( $bg_color_s ), $wcb_dept_gold_bgs, true ) ) {
                    $wcb_is_juice_card = ( $n === 3 )
                        || ( $icon === 'juice' )
                        || ( $cat_term && ! is_wp_error( $cat_term ) && in_array( $cat_term->slug, array( 'juices', 'juice' ), true ) )
                        || ( is_string( $url ) && preg_match( '/[?&]categoria=juices\b/', $url ) );
                    if ( $wcb_is_juice_card ) {
                        $bg_color_s   = sanitize_hex_color( $def['bg'] );
                        $icon_color_s = sanitize_hex_color( $def['ic'] );
                    }
                }

                $card_vars    = '';
                if ( $icon_color_s ) {
                    $card_vars .= '--dept-user-accent:' . $icon_color_s . ';';
                }
                if ( $bg_color_s ) {
                    $card_vars .= '--dept-user-bg:' . $bg_color_s . ';';
                }
                $card_style_attr = $card_vars !== '' ? ' style="' . esc_attr( $card_vars ) . '"' : '';
                $aria_label      = $name;
                if ( $count_html ) {
                    $aria_label .= ' — ' . $count_html;
                }
                $aria_label .= '. ' . __( 'Abrir categoria na loja', 'wcb-theme' );
                ?>
                <a href="<?php echo esc_url( is_string($url) ? $url : '' ); ?>" class="wcb-dept-card"<?php echo $card_style_attr; ?>
                    aria-label="<?php echo esc_attr( $aria_label ); ?>">
                    <div class="wcb-dept-card__content">
                        <span class="wcb-dept-card__name"><?php echo esc_html($name); ?></span>
                        <span class="wcb-dept-card__desc"><?php echo esc_html($cat_descriptions[$n] ?? ''); ?></span>
                        <div class="wcb-dept-card__meta">
                            <?php if ( $count_html ) : ?>
                            <span class="wcb-dept-card__count"><?php echo esc_html($count_html); ?></span>
                            <?php endif; ?>
                            <span class="wcb-dept-card__cta">
                                Ver produtos
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </span>
                        </div>
                    </div>
                    <div class="wcb-dept-card__icon-decor">
                        <?php echo wcb_get_dept_icon($icon); ?>
                    </div>
                </a>
            <?php endfor; ?>

        </div>
        </div>
    </div>
</section>

<!-- ==================== BLOG SECTION ==================== -->

<section class="wcb-section wcb-section--blog">
    <div class="wcb-container">
        <div class="wcb-section__header">
            <div class="wcb-section__headline">
                <h2 class="wcb-section__title">
                    <span class="wcb-section__title-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </span>
                    <?php esc_html_e('Dicas, Guias e Novidades', 'wcb-theme'); ?>
                </h2>
            </div>
            <div class="wcb-section__actions">
                <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="wcb-section__link">
                    <?php esc_html_e('Explorar conteúdos', 'wcb-theme'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="wcb-section__content">
        <div class="wcb-blog__grid">

            <?php
            // Query dos 3 posts mais recentes
            $blog_query = new WP_Query(array(
                'post_type'      => 'post',
                'posts_per_page' => 3,
                'post_status'    => 'publish',
                'orderby'        => 'date',
                'order'          => 'DESC',
            ));

            $wcb_blog_logo_id = (int) get_theme_mod( 'custom_logo' );
            $wcb_blog_avatar_is_logo = $wcb_blog_logo_id > 0;
            $wcb_blog_avatar_logo_html = $wcb_blog_avatar_is_logo
                ? wp_get_attachment_image(
                    $wcb_blog_logo_id,
                    array( 96, 96 ),
                    false,
                    array(
                        'class'    => 'wcb-blog-card__avatar-img',
                        'alt'      => esc_attr( get_bloginfo( 'name' ) ),
                        'loading'  => 'lazy',
                        'decoding' => 'async',
                    )
                )
                : '';

            if ($blog_query->have_posts()) :
                while ($blog_query->have_posts()) : $blog_query->the_post();
                    // Calcular tempo de leitura
                    $word_count = str_word_count(strip_tags(get_the_content()));
                    $read_time  = max(1, ceil($word_count / 200));
                    
                    // Pegar categorias do post
                    $categories = get_the_category();
                    
                    // Pegar iniciais do autor para o avatar
                    $author_name = get_the_author();
                    $name_parts  = explode(' ', $author_name);
                    $initials    = '';
                    foreach ($name_parts as $part) {
                        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                    }
                    $initials = mb_substr($initials, 0, 2);
            ?>
            <a href="<?php the_permalink(); ?>" class="wcb-blog-card">
                <div class="wcb-blog-card__image">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium_large', array('loading' => 'lazy', 'alt' => get_the_title())); ?>
                    <?php else : ?>
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/images/blog-placeholder.jpg'); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
                    <?php endif; ?>
                    <?php if (!empty($categories)) : ?>
                    <div class="wcb-blog-card__tags">
                        <?php 
                        $shown = 0;
                        foreach ($categories as $cat) : 
                            if ($cat->slug === 'uncategorized' || $cat->slug === 'sem-categoria') continue;
                            if ($shown >= 2) break;
                        ?>
                            <span class="wcb-blog-card__tag"><?php echo esc_html($cat->name); ?></span>
                        <?php 
                            $shown++;
                        endforeach; 
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="wcb-blog-card__content">
                    <h3 class="wcb-blog-card__title"><?php the_title(); ?></h3>
                    <p class="wcb-blog-card__excerpt"><?php echo wp_trim_words(get_the_excerpt(), 18, '...'); ?></p>
                    <div class="wcb-blog-card__meta">
                        <div class="wcb-blog-card__author">
                            <div class="wcb-blog-card__avatar<?php echo $wcb_blog_avatar_is_logo ? ' wcb-blog-card__avatar--logo' : ''; ?>">
                                <?php
                                if ( $wcb_blog_avatar_logo_html ) {
                                    echo $wcb_blog_avatar_logo_html;
                                } else {
                                    echo esc_html( $initials );
                                }
                                ?>
                            </div>
                            <div class="wcb-blog-card__author-info">
                                <span class="wcb-blog-card__author-name"><?php esc_html_e( 'White Cloud Brasil', 'wcb-theme' ); ?></span>
                                <span class="wcb-blog-card__date"><?php echo get_the_date('d M Y'); ?></span>
                            </div>
                        </div>
                        <div class="wcb-blog-card__read-time">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?php echo $read_time; ?> min
                        </div>
                    </div>
                </div>
            </a>
            <?php
                endwhile;
                wp_reset_postdata();
            else :
            ?>
                <p style="text-align:center;color:#888;grid-column:1/-1;">Nenhum post encontrado. Crie seus primeiros posts no painel WordPress!</p>
            <?php endif; ?>

        </div>
        </div>
    </div>
</section>


<?php
get_footer();
?>


