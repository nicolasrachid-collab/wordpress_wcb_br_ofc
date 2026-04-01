<?php
/**
 * WCB Theme — WordPress Customizer
 * Hero Banner and Super Ofertas settings.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Label de tag/badge: texto plano + emoji (UTF-8), sem HTML.
 */
function wcb_sanitize_tag_label( $value ) {
    if ( ! is_string( $value ) ) {
        return '';
    }
    return sanitize_text_field( wp_strip_all_tags( $value ) );
}

/**
 * @param mixed $value
 * @return string slide|carousel|homepage
 */
function wcb_sanitize_carousel_dedupe_scope( $value ) {
    $allowed = array( 'slide', 'carousel', 'homepage' );
    return in_array( $value, $allowed, true ) ? $value : 'carousel';
}

/**
 * @param mixed $value
 * @return string shop|category|mixed
 */
function wcb_sanitize_carousel_fb_mode( $value ) {
    $allowed = array( 'shop', 'category', 'mixed' );
    return in_array( $value, $allowed, true ) ? $value : 'shop';
}

/* ============================================================
   CUSTOMIZER — Hero Banner + Super Ofertas
   ============================================================ */
function wcb_customize_register( $wp_customize ) {
    // ── HERO BANNER SECTION ───────────────────────────────────
    $wp_customize->add_section( 'wcb_hero_banner', array(
        'title'       => __( '🖼️ Banner Principal (Home)', 'wcb-theme' ),
        'description' => __( 'Configure as imagens, textos e links dos 3 slides do banner da página inicial.', 'wcb-theme' ),
        'priority'    => 30,
    ) );

    $slides_defaults = array(
        1 => array( 'badge' => '🔥 Lançamento', 'title' => 'Vaporesso XROS 4', 'subtitle' => 'O pod mais avançado e elegante do Brasil', 'cta' => 'Ver Produto', 'cta_url' => '/produto/vaporesso-xros-4/' ),
        2 => array( 'badge' => '⚡ Até 30% OFF', 'title' => 'Juices Importados', 'subtitle' => 'Os melhores sabores com o melhor preço', 'cta' => 'Ver Promoções', 'cta_url' => '/promocoes/' ),
        3 => array( 'badge' => '🔥 Oferta Especial', 'title' => 'Gifts Especiais', 'subtitle' => '15% OFF em kits selecionados para você', 'cta' => 'Ver Kits', 'cta_url' => '/categoria/kits/' ),
    );

    foreach ( array( 1, 2, 3 ) as $n ) {
        $d      = $slides_defaults[ $n ];
        $prefix = "hero_slide{$n}";
        $label  = "Slide {$n}";

        // Imagem desktop (obrigatório)
        $wp_customize->add_setting( "{$prefix}_image", array( 'default' => get_template_directory_uri() . "/images/banner-{$n}.png", 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "{$prefix}_image", array( 'label' => __( "{$label}: Imagem de fundo (desktop)", 'wcb-theme' ), 'section' => 'wcb_hero_banner' ) ) );

        // Imagem mobile (opcional — se vazio, usa a imagem desktop)
        $wp_customize->add_setting( "{$prefix}_mobile_image", array( 'default' => '', 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "{$prefix}_mobile_image", array( 'label' => __( "{$label}: Imagem mobile (opcional, aparece em telas ≤768px)", 'wcb-theme' ), 'description' => __( 'Deixe vazio para usar a mesma imagem do desktop.', 'wcb-theme' ), 'section' => 'wcb_hero_banner' ) ) );

        // Vídeo de fundo (opcional — se preenchido, substitui a imagem)
        $wp_customize->add_setting( "{$prefix}_video_url", array( 'default' => '', 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_video_url", array(
            'label'       => __( "{$label}: Vídeo de fundo (opcional, URL .mp4)", 'wcb-theme' ),
            'description' => __( 'Cole a URL de um arquivo .mp4. Quando preenchido, o vídeo substitui a imagem de fundo. O vídeo será mudo, sem controles e em loop.', 'wcb-theme' ),
            'section'     => 'wcb_hero_banner',
            'type'        => 'url',
        ) );

        $wp_customize->add_setting( "{$prefix}_badge",    array( 'default' => $d['badge'],    'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_badge",    array( 'label' => __( "{$label}: Badge", 'wcb-theme' ),           'section' => 'wcb_hero_banner', 'type' => 'text' ) );

        $wp_customize->add_setting( "{$prefix}_title",    array( 'default' => $d['title'],    'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_title",    array( 'label' => __( "{$label}: Título", 'wcb-theme' ),          'section' => 'wcb_hero_banner', 'type' => 'text' ) );

        $wp_customize->add_setting( "{$prefix}_subtitle", array( 'default' => $d['subtitle'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_subtitle", array( 'label' => __( "{$label}: Subtítulo", 'wcb-theme' ),       'section' => 'wcb_hero_banner', 'type' => 'text' ) );

        $wp_customize->add_setting( "{$prefix}_cta",      array( 'default' => $d['cta'],      'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_cta",      array( 'label' => __( "{$label}: Texto do botão", 'wcb-theme' ),  'section' => 'wcb_hero_banner', 'type' => 'text' ) );

        $wp_customize->add_setting( "{$prefix}_cta_url",  array( 'default' => home_url( $d['cta_url'] ), 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_cta_url",  array( 'label' => __( "{$label}: Link do botão", 'wcb-theme' ),   'section' => 'wcb_hero_banner', 'type' => 'url' ) );
    }

    // ── SUPER OFERTAS SECTION ─────────────────────────────────
    $wp_customize->add_section( 'wcb_super_ofertas', array(
        'title'    => __( '⏱️ Super Ofertas', 'wcb-theme' ),
        'priority' => 90,
    ) );

    $wp_customize->add_setting( 'wcb_sale_end_date', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'wcb_sale_end_date', array(
        'label'       => __( 'Data de término das ofertas', 'wcb-theme' ),
        'description' => __( 'Formato: YYYY-MM-DDTHH:MM:SS (ex: 2026-03-15T23:59:59). Deixe vazio para usar o próximo domingo automaticamente.', 'wcb-theme' ),
        'section'     => 'wcb_super_ofertas',
        'type'        => 'text',
    ) );
    // ── PROMO BANNER CARDS SECTION ────────────────────────────
    $wp_customize->add_section( 'wcb_promo_banners', array(
        'title'       => __( '🃏 Banners Promocionais', 'wcb-theme' ),
        'description' => __( 'Configure os dois cards promocionais que aparecem abaixo das categorias.', 'wcb-theme' ),
        'priority'    => 50,
    ) );

    $promo_defaults = array(
        1 => array(
            'image' => get_template_directory_uri() . '/images/promo-banner-1.jpg',
            'url'   => home_url( '/loja/' ),
            'badge' => '🔥 Destaque',
            'title' => 'Pods & Cartuchos',
            'sub'   => 'Os melhores sistemas de pod do mercado',
            'cta'   => 'Ver coleção →',
        ),
        2 => array(
            'image' => get_template_directory_uri() . '/images/promo-banner-2.jpg',
            'url'   => home_url( '/loja/?categoria=juices' ),
            'badge' => '⚡ Oferta',
            'title' => 'Juices Importados',
            'sub'   => 'Sabores incríveis com até 30% OFF',
            'cta'   => 'Aproveitar →',
        ),
    );

    foreach ( array( 1, 2 ) as $n ) {
        $d      = $promo_defaults[ $n ];
        $prefix = "promo_banner{$n}";
        $label  = "Card {$n}";

        // Imagem
        $wp_customize->add_setting( "{$prefix}_image", array( 'default' => $d['image'], 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "{$prefix}_image", array( 'label' => __( "{$label}: Imagem de fundo", 'wcb-theme' ), 'section' => 'wcb_promo_banners' ) ) );

        // URL do card
        $wp_customize->add_setting( "{$prefix}_url", array( 'default' => $d['url'], 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_url", array( 'label' => __( "{$label}: Link do card", 'wcb-theme' ), 'section' => 'wcb_promo_banners', 'type' => 'url' ) );

        // Badge
        $wp_customize->add_setting( "{$prefix}_badge", array( 'default' => $d['badge'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_badge", array( 'label' => __( "{$label}: Badge", 'wcb-theme' ), 'section' => 'wcb_promo_banners', 'type' => 'text' ) );

        // Título
        $wp_customize->add_setting( "{$prefix}_title", array( 'default' => $d['title'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_title", array( 'label' => __( "{$label}: Título", 'wcb-theme' ), 'section' => 'wcb_promo_banners', 'type' => 'text' ) );

        // Subtítulo
        $wp_customize->add_setting( "{$prefix}_sub", array( 'default' => $d['sub'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_sub", array( 'label' => __( "{$label}: Subtítulo", 'wcb-theme' ), 'section' => 'wcb_promo_banners', 'type' => 'text' ) );

        // CTA
        $wp_customize->add_setting( "{$prefix}_cta", array( 'default' => $d['cta'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_cta", array( 'label' => __( "{$label}: Texto do botão CTA", 'wcb-theme' ), 'section' => 'wcb_promo_banners', 'type' => 'text' ) );
    }

    // ── CATEGORIAS (HOME GRID) ────────────────────────────────
    $wp_customize->add_section( 'wcb_cat_grid', array(
        'title'       => __( '🗂️ Categorias (Home)', 'wcb-theme' ),
        'description' => __( 'Configure os 6 cards de categoria exibidos na homepage. Cada card tem nome, link, ícone e cor personalizáveis.', 'wcb-theme' ),
        'priority'    => 40,
    ) );

    $cat_defaults = array(
        1 => array( 'name' => 'Pods Descartáveis', 'url' => '/loja/?categoria=pods-descartaveis', 'icon' => 'pods',      'color' => '#eff6ff' ),
        2 => array( 'name' => 'Coils e Cartuchos', 'url' => '/loja/?categoria=coils-cartuchos',  'icon' => 'coils',     'color' => '#dbeafe' ),
        3 => array( 'name' => 'Juices',             'url' => '/loja/?categoria=juices',           'icon' => 'juice',     'color' => '#e0f2fe' ),
        4 => array( 'name' => 'Kits e Aparelhos',   'url' => '/loja/?categoria=kits',             'icon' => 'kit',       'color' => '#dbeafe' ),
        5 => array( 'name' => 'Atomizadores',        'url' => '/loja/?categoria=atomizadores',     'icon' => 'atomizador','color' => '#e0f2fe' ),
        6 => array( 'name' => 'Acessórios',          'url' => '/loja/?categoria=acessorios',       'icon' => 'acessorios','color' => '#eff6ff' ),
    );

    $icon_choices = array(
        'pods'       => '💨 Pods',
        'coils'      => '🔧 Coils / Cartuchos',
        'juice'      => '💧 Juices',
        'kit'        => '📦 Kits',
        'atomizador' => '⚙️ Atomizadores',
        'acessorios' => '🎒 Acessórios',
        'carrinho'   => '🛒 Carrinho / Genérico',
        'estrela'    => '⭐ Novidades',
        'tag'        => '🏷️ Promoções',
        'coracao'    => '❤️ Favoritos',
    );

    // Carrega categorias WooCommerce para o seletor
    $cat_choices = array( '' => '— Nenhuma (usar link manual) —' );
    if ( class_exists( 'WooCommerce' ) ) {
        $wc_cats = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'orderby'    => 'name',
            'exclude'    => array( get_option('default_product_cat') ),
        ) );
        if ( ! is_wp_error( $wc_cats ) ) {
            foreach ( $wc_cats as $cat ) {
                $cat_choices[ $cat->slug ] = $cat->name . ' (' . $cat->count . ')';
            }
        }
    }

    foreach ( range( 1, 6 ) as $n ) {
        $d      = $cat_defaults[ $n ];
        $prefix = "cat_card{$n}";
        $label  = "Card {$n}";

        // Categoria WooCommerce
        $wp_customize->add_setting( "{$prefix}_cat_slug", array( 'default' => '', 'sanitize_callback' => 'sanitize_key', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_cat_slug", array( 'label' => __( "{$label}: Categoria", 'wcb-theme' ), 'description' => __( 'Selecione uma categoria do WooCommerce. O nome e link serão preenchidos automaticamente.', 'wcb-theme' ), 'section' => 'wcb_cat_grid', 'type' => 'select', 'choices' => $cat_choices ) );

        // Nome (sobrescreve o nome da categoria)
        $wp_customize->add_setting( "{$prefix}_name", array( 'default' => $d['name'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_name", array( 'label' => __( "{$label}: Nome personalizado", 'wcb-theme' ), 'description' => __( 'Deixe em branco para usar o nome da categoria selecionada.', 'wcb-theme' ), 'section' => 'wcb_cat_grid', 'type' => 'text' ) );

        // Link manual (usado se nenhuma categoria for selecionada)
        $wp_customize->add_setting( "{$prefix}_url", array( 'default' => home_url( $d['url'] ), 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_url", array( 'label' => __( "{$label}: Link manual", 'wcb-theme' ), 'description' => __( 'Usado somente se nenhuma categoria estiver selecionada acima.', 'wcb-theme' ), 'section' => 'wcb_cat_grid', 'type' => 'url' ) );

        // Ícone
        $wp_customize->add_setting( "{$prefix}_icon", array( 'default' => $d['icon'], 'sanitize_callback' => 'sanitize_key', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_icon", array( 'label' => __( "{$label}: Ícone", 'wcb-theme' ), 'section' => 'wcb_cat_grid', 'type' => 'select', 'choices' => $icon_choices ) );

        // Cor de fundo do ícone
        $wp_customize->add_setting( "{$prefix}_bg_color", array( 'default' => $d['color'], 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$prefix}_bg_color", array( 'label' => __( "{$label}: Cor de fundo do ícone", 'wcb-theme' ), 'section' => 'wcb_cat_grid' ) ) );

        // Cor do ícone
        $wp_customize->add_setting( "{$prefix}_icon_color", array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$prefix}_icon_color", array( 'label' => __( "{$label}: Cor do ícone", 'wcb-theme' ), 'description' => __( 'Deixe vazio para usar a cor automática.', 'wcb-theme' ), 'section' => 'wcb_cat_grid' ) ) );
    }

    // ── LIFESTYLE SLIDER A (entre Mais Vendidos e Super Ofertas) ──
    $wp_customize->add_section( 'wcb_lifestyle_a', array(
        'title'       => __( '🎬 Slider Lifestyle A (Topo)', 'wcb-theme' ),
        'description' => __( 'Slider promocional que aparece após os Mais Vendidos. 4 slides com imagem, textos e botão.', 'wcb-theme' ),
        'priority'    => 55,
    ) );

    $ls_a_defaults = array(
        1 => array( 'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?q=80&w=2000&auto=format&fit=crop', 'gradient' => 'rgba(30,58,138,0.92),rgba(30,64,175,0.7)', 'tag' => '🔥 Descubra', 'tag_color' => '#93c5fd', 'title' => 'A Arte do Vape<br>Redefinida', 'desc' => 'Uma seleção exclusiva dos dispositivos e essências mais sofisticados do mundo.', 'cta' => 'Explorar Coleção', 'url' => '/loja/', 'btn_color' => '#2563eb', 'btn_hover' => '#1d4ed8' ),
        2 => array( 'image' => 'https://images.unsplash.com/photo-1585771724684-38269d6639fd?q=80&w=2000&auto=format&fit=crop', 'gradient' => 'rgba(30,58,138,0.92),rgba(30,64,175,0.7)', 'tag' => '⚡ Promoção', 'tag_color' => '#93c5fd', 'title' => 'Até 40% Off<br>em Juices', 'desc' => 'Os melhores sabores importados com desconto exclusivo.', 'cta' => 'Ver Ofertas', 'url' => '/loja/?on_sale=true', 'btn_color' => '#2563eb', 'btn_hover' => '#1d4ed8' ),
        3 => array( 'image' => 'https://images.unsplash.com/photo-1560707854-5bc2a0d6498c?q=80&w=2000&auto=format&fit=crop', 'gradient' => 'rgba(30,58,138,0.92),rgba(30,64,175,0.7)', 'tag' => '🔥 Promoção', 'tag_color' => '#93c5fd', 'title' => 'Até 40% Off<br>em Kits', 'desc' => 'Aproveite ofertas imperdíveis nos melhores kits do mercado.', 'cta' => 'Ver Ofertas', 'url' => '/categoria/kits/', 'btn_color' => '#2563eb', 'btn_hover' => '#1d4ed8' ),
        4 => array( 'image' => 'https://images.unsplash.com/photo-1612528443702-f6741f70a049?q=80&w=2000&auto=format&fit=crop', 'gradient' => 'rgba(30,58,138,0.92),rgba(30,64,175,0.7)', 'tag' => '✨ Novidade', 'tag_color' => '#93c5fd', 'title' => 'Pods Premium<br>Chegaram', 'desc' => 'Os dispositivos mais avançados do mercado agora disponíveis.', 'cta' => 'Ver Pods', 'url' => '/categoria/pods/', 'btn_color' => '#2563eb', 'btn_hover' => '#1d4ed8' ),
    );

    foreach ( range( 1, 4 ) as $n ) {
        $d      = $ls_a_defaults[ $n ];
        $prefix = "ls_a_slide{$n}";
        $label  = "Slide {$n}";

        $wp_customize->add_setting( "{$prefix}_image", array( 'default' => $d['image'], 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "{$prefix}_image", array( 'label' => __( "{$label}: Imagem de fundo", 'wcb-theme' ), 'section' => 'wcb_lifestyle_a' ) ) );

        $wp_customize->add_setting( "{$prefix}_gradient", array( 'default' => $d['gradient'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_gradient", array( 'label' => __( "{$label}: Gradiente (rgba)", 'wcb-theme' ), 'description' => __( 'Formato: rgba(R,G,B,A),rgba(R,G,B,A)', 'wcb-theme' ), 'section' => 'wcb_lifestyle_a', 'type' => 'text' ) );

        $wp_customize->add_setting( "{$prefix}_tag", array( 'default' => $d['tag'], 'sanitize_callback' => 'wcb_sanitize_tag_label', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_tag", array(
            'label'       => __( "{$label}: Tag (label)", 'wcb-theme' ),
            'description' => __( 'Texto curto. Pode usar emoji no início (ex.: 🔥 Destaque), como nos banners promo da home.', 'wcb-theme' ),
            'section'     => 'wcb_lifestyle_a',
            'type'        => 'text',
        ) );

        $wp_customize->add_setting( "{$prefix}_tag_color", array( 'default' => $d['tag_color'], 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$prefix}_tag_color", array( 'label' => __( "{$label}: Cor da tag", 'wcb-theme' ), 'section' => 'wcb_lifestyle_a' ) ) );

        $wp_customize->add_setting( "{$prefix}_title", array( 'default' => $d['title'], 'sanitize_callback' => 'wp_kses_post', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_title", array( 'label' => __( "{$label}: Título (HTML ok, use <br> para quebra)", 'wcb-theme' ), 'section' => 'wcb_lifestyle_a', 'type' => 'text' ) );

        $wp_customize->add_setting( "{$prefix}_desc", array( 'default' => $d['desc'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_desc", array( 'label' => __( "{$label}: Descrição", 'wcb-theme' ), 'section' => 'wcb_lifestyle_a', 'type' => 'textarea' ) );

        $wp_customize->add_setting( "{$prefix}_cta", array( 'default' => $d['cta'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_cta", array( 'label' => __( "{$label}: Texto do botão", 'wcb-theme' ), 'section' => 'wcb_lifestyle_a', 'type' => 'text' ) );

        $wp_customize->add_setting( "{$prefix}_url", array( 'default' => home_url( $d['url'] ), 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_url", array( 'label' => __( "{$label}: Link do botão", 'wcb-theme' ), 'section' => 'wcb_lifestyle_a', 'type' => 'url' ) );

        $wp_customize->add_setting( "{$prefix}_btn_color", array( 'default' => $d['btn_color'], 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$prefix}_btn_color", array( 'label' => __( "{$label}: Cor do botão", 'wcb-theme' ), 'section' => 'wcb_lifestyle_a' ) ) );

        $wp_customize->add_setting( "{$prefix}_btn_hover", array( 'default' => $d['btn_hover'], 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$prefix}_btn_hover", array( 'label' => __( "{$label}: Cor hover do botão", 'wcb-theme' ), 'section' => 'wcb_lifestyle_a' ) ) );
    }

    // ── LIFESTYLE SLIDER B (entre Categorias e Testimonials) ──
    $wp_customize->add_section( 'wcb_lifestyle_b', array(
        'title'       => __( '🎬 Slider Lifestyle B (Inferior)', 'wcb-theme' ),
        'description' => __( 'Slider promocional que aparece após as Categorias. 4 slides com imagem, textos e botão.', 'wcb-theme' ),
        'priority'    => 56,
    ) );

    $ls_b_defaults = $ls_a_defaults; // Same defaults initially

    foreach ( range( 1, 4 ) as $n ) {
        $d      = $ls_b_defaults[ $n ];
        $prefix = "ls_b_slide{$n}";
        $label  = "Slide {$n}";

        $wp_customize->add_setting( "{$prefix}_image", array( 'default' => $d['image'], 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "{$prefix}_image", array( 'label' => __( "{$label}: Imagem de fundo", 'wcb-theme' ), 'section' => 'wcb_lifestyle_b' ) ) );

        $wp_customize->add_setting( "{$prefix}_gradient", array( 'default' => $d['gradient'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_gradient", array( 'label' => __( "{$label}: Gradiente (rgba)", 'wcb-theme' ), 'description' => __( 'Formato: rgba(R,G,B,A),rgba(R,G,B,A)', 'wcb-theme' ), 'section' => 'wcb_lifestyle_b', 'type' => 'text' ) );

        $wp_customize->add_setting( "{$prefix}_tag", array( 'default' => $d['tag'], 'sanitize_callback' => 'wcb_sanitize_tag_label', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_tag", array(
            'label'       => __( "{$label}: Tag (label)", 'wcb-theme' ),
            'description' => __( 'Texto curto. Pode usar emoji no início (ex.: 🔥 Destaque), como nos banners promo da home.', 'wcb-theme' ),
            'section'     => 'wcb_lifestyle_b',
            'type'        => 'text',
        ) );

        $wp_customize->add_setting( "{$prefix}_tag_color", array( 'default' => $d['tag_color'], 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$prefix}_tag_color", array( 'label' => __( "{$label}: Cor da tag", 'wcb-theme' ), 'section' => 'wcb_lifestyle_b' ) ) );

        $wp_customize->add_setting( "{$prefix}_title", array( 'default' => $d['title'], 'sanitize_callback' => 'wp_kses_post', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_title", array( 'label' => __( "{$label}: Título (HTML ok)", 'wcb-theme' ), 'section' => 'wcb_lifestyle_b', 'type' => 'text' ) );

        $wp_customize->add_setting( "{$prefix}_desc", array( 'default' => $d['desc'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_desc", array( 'label' => __( "{$label}: Descrição", 'wcb-theme' ), 'section' => 'wcb_lifestyle_b', 'type' => 'textarea' ) );

        $wp_customize->add_setting( "{$prefix}_cta", array( 'default' => $d['cta'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_cta", array( 'label' => __( "{$label}: Texto do botão", 'wcb-theme' ), 'section' => 'wcb_lifestyle_b', 'type' => 'text' ) );

        $wp_customize->add_setting( "{$prefix}_url", array( 'default' => home_url( $d['url'] ), 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
        $wp_customize->add_control( "{$prefix}_url", array( 'label' => __( "{$label}: Link do botão", 'wcb-theme' ), 'section' => 'wcb_lifestyle_b', 'type' => 'url' ) );

        $wp_customize->add_setting( "{$prefix}_btn_color", array( 'default' => $d['btn_color'], 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$prefix}_btn_color", array( 'label' => __( "{$label}: Cor do botão", 'wcb-theme' ), 'section' => 'wcb_lifestyle_b' ) ) );

        $wp_customize->add_setting( "{$prefix}_btn_hover", array( 'default' => $d['btn_hover'], 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$prefix}_btn_hover", array( 'label' => __( "{$label}: Cor hover do botão", 'wcb-theme' ), 'section' => 'wcb_lifestyle_b' ) ) );
    }
    // ── CAROUSELS DE PRODUTOS ─────────────────────────────────
    $wp_customize->add_section( 'wcb_product_carousels', array(
        'title'       => __( '🎠 Carousels de Produtos', 'wcb-theme' ),
        'description' => __( 'Configure o comportamento dos carousels de produtos na página inicial (Novidades, Mais Vendidos, Super Ofertas e De Volta ao Estoque).', 'wcb-theme' ),
        'priority'    => 35,
    ) );

    $wp_customize->add_setting( 'wcb_carousel_delay', array(
        'default'           => 3,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'wcb_carousel_delay', array(
        'label'       => __( 'Novidades (Linha 1) — Tempo de troca (segundos)', 'wcb-theme' ),
        'description' => __( 'Intervalo em segundos entre a troca automática dos slides de Novidades linha 1. Padrão: 3.', 'wcb-theme' ),
        'section'     => 'wcb_product_carousels',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 1,
            'max'  => 60,
            'step' => 1,
        ),
    ) );

    // Novidades Linha 2 — delay próprio
    $wp_customize->add_setting( 'wcb_carousel_delay_novidades2', array(
        'default'           => 4,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'wcb_carousel_delay_novidades2', array(
        'label'       => __( 'Novidades (Linha 2) — Tempo de troca (segundos)', 'wcb-theme' ),
        'description' => __( 'Padrão: 4.', 'wcb-theme' ),
        'section'     => 'wcb_product_carousels',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 1,
            'max'  => 60,
            'step' => 1,
        ),
    ) );

    // Mais Vendidos — delay próprio
    $wp_customize->add_setting( 'wcb_carousel_delay_vendidos', array(
        'default'           => 5,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'wcb_carousel_delay_vendidos', array(
        'label'       => __( 'Mais Vendidos — Tempo de troca (segundos)', 'wcb-theme' ),
        'description' => __( 'Padrão: 5. Mais lento para dar tempo de avaliar os produtos populares.', 'wcb-theme' ),
        'section'     => 'wcb_product_carousels',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 1,
            'max'  => 60,
            'step' => 1,
        ),
    ) );

    // Super Ofertas — delay próprio
    $wp_customize->add_setting( 'wcb_carousel_delay_ofertas', array(
        'default'           => 4,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'wcb_carousel_delay_ofertas', array(
        'label'       => __( 'Super Ofertas — Tempo de troca (segundos)', 'wcb-theme' ),
        'description' => __( 'Padrão: 4.', 'wcb-theme' ),
        'section'     => 'wcb_product_carousels',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 1,
            'max'  => 60,
            'step' => 1,
        ),
    ) );

    // De Volta ao Estoque — delay próprio
    $wp_customize->add_setting( 'wcb_carousel_delay_estoque', array(
        'default'           => 6,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'wcb_carousel_delay_estoque', array(
        'label'       => __( 'De Volta ao Estoque — Tempo de troca (segundos)', 'wcb-theme' ),
        'description' => __( 'Padrão: 6.', 'wcb-theme' ),
        'section'     => 'wcb_product_carousels',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 1,
            'max'  => 60,
            'step' => 1,
        ),
    ) );

    // ── Carrosséis: preencher slots vazios + desduplicação ─────────────
    $wp_customize->add_setting( 'wcb_carousel_dedupe_scope', array(
        'default'           => 'carousel',
        'sanitize_callback' => 'wcb_sanitize_carousel_dedupe_scope',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'wcb_carousel_dedupe_scope', array(
        'label'       => __( 'Desduplicação ao preencher slots vazios', 'wcb-theme' ),
        'description' => __( 'Slide: só evita repetir no mesmo slide. Carrossel: não repete em nenhum slide desse carrossel. Homepage: não repete entre todos os carrosséis da home.', 'wcb-theme' ),
        'section'     => 'wcb_product_carousels',
        'type'        => 'select',
        'choices'     => array(
            'slide'     => __( 'Apenas neste slide', 'wcb-theme' ),
            'carousel'  => __( 'Todo o carrossel (recomendado)', 'wcb-theme' ),
            'homepage'  => __( 'Toda a homepage', 'wcb-theme' ),
        ),
    ) );

    $carousel_fb_cats = array( 0 => __( '— (Categoria fixa: escolher abaixo) —', 'wcb-theme' ) );
    if ( class_exists( 'WooCommerce' ) ) {
        $wc_fb = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'orderby'    => 'name',
        ) );
        if ( ! is_wp_error( $wc_fb ) ) {
            foreach ( $wc_fb as $ct ) {
                $carousel_fb_cats[ (string) $ct->term_id ] = $ct->name . ' (' . (int) $ct->count . ')';
            }
        }
    }

    $wcb_carousel_fb_sections = array(
        'novidades' => __( 'Novidades (linhas 1 e 2)', 'wcb-theme' ),
        'vendidos'  => __( 'Mais vendidos', 'wcb-theme' ),
        'estoque'   => __( 'De volta ao estoque', 'wcb-theme' ),
        'ofertas'   => __( 'Super ofertas (grelha ao lado do hero)', 'wcb-theme' ),
    );

    foreach ( $wcb_carousel_fb_sections as $slug => $fb_label ) {
        $wp_customize->add_setting( 'wcb_carousel_fb_' . $slug . '_mode', array(
            'default'           => 'shop',
            'sanitize_callback' => 'wcb_sanitize_carousel_fb_mode',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( 'wcb_carousel_fb_' . $slug . '_mode', array(
            'label'       => sprintf(
                /* translators: %s: section name */
                __( '%s — fallback para slots vazios', 'wcb-theme' ),
                $fb_label
            ),
            'description' => __( 'Loja toda: qualquer produto em stock. Categoria fixa: só a categoria escolhida (completamento na loja se faltar). Misto: usa a categoria mais comum entre os produtos já listados, depois loja.', 'wcb-theme' ),
            'section'     => 'wcb_product_carousels',
            'type'        => 'select',
            'choices'     => array(
                'shop'     => __( 'Loja toda', 'wcb-theme' ),
                'category' => __( 'Categoria fixa (Customizer)', 'wcb-theme' ),
                'mixed'    => __( 'Misto (categoria inferida + loja)', 'wcb-theme' ),
            ),
        ) );

        $wp_customize->add_setting( 'wcb_carousel_fb_' . $slug . '_cat', array(
            'default'           => 0,
            'sanitize_callback' => 'absint',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( 'wcb_carousel_fb_' . $slug . '_cat', array(
            'label'       => sprintf(
                /* translators: %s: section name */
                __( '%s — categoria fixa (se modo “Categoria fixa”)', 'wcb-theme' ),
                $fb_label
            ),
            'section'     => 'wcb_product_carousels',
            'type'        => 'select',
            'choices'     => $carousel_fb_cats,
        ) );
    }

    // ── NEWSLETTER (rodapé wcb-nl4) ───────────────────────────
    $wp_customize->add_section( 'wcb_newsletter_nl4', array(
        'title'       => __( '📧 Newsletter & WhatsApp (rodapé)', 'wcb-theme' ),
        'description' => __( 'Link do grupo WhatsApp após o cadastro. Os e-mails ficam guardados na opção wcb_nl4_emails (exporte ou integre depois).', 'wcb-theme' ),
        'priority'    => 95,
    ) );

    $wp_customize->add_setting( 'wcb_nl4_whatsapp_url', array(
        'default'           => 'https://chat.whatsapp.com/SEU-LINK-AQUI',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'wcb_nl4_whatsapp_url', array(
        'label'       => __( 'URL do grupo WhatsApp (convite)', 'wcb-theme' ),
        'description' => __( 'Cole o link chat.whatsapp.com do seu grupo.', 'wcb-theme' ),
        'section'     => 'wcb_newsletter_nl4',
        'type'        => 'url',
    ) );
}
add_action( 'customize_register', 'wcb_customize_register' );

