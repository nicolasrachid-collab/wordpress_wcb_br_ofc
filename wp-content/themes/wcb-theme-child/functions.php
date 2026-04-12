<?php
/**
 * WCB Child — carrega pai + assets do bloco YITH Frequently Bought Together.
 *
 * @package WCB_Child
 */

defined( 'ABSPATH' ) || exit;

define( 'WCB_CHILD_VERSION', '1.2.7' );

$wcb_fbt_ajax_combo = get_stylesheet_directory() . '/inc/wcb-fbt-ajax-combo.php';
if ( is_readable( $wcb_fbt_ajax_combo ) ) {
	require_once $wcb_fbt_ajax_combo;
}

$wcb_blog_growth = get_stylesheet_directory() . '/inc/wcb-blog-growth.php';
if ( is_readable( $wcb_blog_growth ) ) {
	require_once $wcb_blog_growth;
}

/**
 * Enfileirar estilos do tema pai e do child.
 */
function wcb_child_enqueue_styles() {
	$parent = wp_get_theme( get_template() );
	wp_enqueue_style(
		'wcb-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		$parent->exists() ? $parent->get( 'Version' ) : null
	);
	wp_enqueue_style(
		'wcb-child-style',
		get_stylesheet_uri(),
		array( 'wcb-parent-style' ),
		WCB_CHILD_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'wcb_child_enqueue_styles', 5 );

/**
 * CSS do single post (blog) — só em posts.
 */
function wcb_child_enqueue_blog_single_assets() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	wp_enqueue_style(
		'wcb-blog-single',
		get_stylesheet_directory_uri() . '/assets/css/wcb-blog-single.css',
		array( 'wcb-child-style' ),
		WCB_CHILD_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'wcb_child_enqueue_blog_single_assets', 25 );

/**
 * CSS/JS do layout “compre junto” (override do template YITH no child).
 */
function wcb_child_enqueue_yith_fbt_assets() {
	if ( ! class_exists( 'WooCommerce' ) || ! is_product() ) {
		return;
	}

	$deps = array( 'wcb-child-style' );
	if ( wp_style_is( 'yith-wfbt-style', 'registered' ) ) {
		$deps[] = 'yith-wfbt-style';
	}

	wp_enqueue_style(
		'wcb-yith-fbt',
		get_stylesheet_directory_uri() . '/assets/css/wcb-yith-fbt.css',
		$deps,
		WCB_CHILD_VERSION
	);

	wp_enqueue_script(
		'wcb-yith-fbt',
		get_stylesheet_directory_uri() . '/assets/js/wcb-yith-fbt.js',
		array( 'jquery' ),
		WCB_CHILD_VERSION,
		true
	);

	wp_localize_script(
		'wcb-yith-fbt',
		'wcbFbt',
		array(
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'wcb_fbt_combo' ),
			'currency'        => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'BRL',
			'pixPercent'      => 0.95,
			'i18nLeadCombo' => __( 'Seu combo por %s', 'wcb-child' ),
			'i18nLeadPix'   => __( '%s no PIX', 'wcb-child' ),
			'i18nLeadCard'  => __( '%s em até 12x no cartão', 'wcb-child' ),
			'i18nCtaSingle'   => __( 'Adicionar ao carrinho', 'wcb-child' ),
			'i18nCtaCombo'    => __( 'Adicionar combo ao carrinho', 'wcb-child' ),
			'i18nSubmitting'      => __( 'Adicionando…', 'wcb-child' ),
			'i18nFeedbackLoading' => __( 'Processando o seu combo…', 'wcb-child' ),
			'i18nComboSuccess'    => __( 'Combo adicionado ao carrinho com sucesso.', 'wcb-child' ),
			'i18nSubmitError'     => __( 'Não foi possível adicionar o combo. Tente novamente.', 'wcb-child' ),
			'i18nQtyDec'      => __( 'Diminuir quantidade', 'wcb-child' ),
			'i18nQtyInc'      => __( 'Aumentar quantidade', 'wcb-child' ),
			'i18nQtyLabel'    => __( 'Quantidade no combo', 'wcb-child' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'wcb_child_enqueue_yith_fbt_assets', 35 );
