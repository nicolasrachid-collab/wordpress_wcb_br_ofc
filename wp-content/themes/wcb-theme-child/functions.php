<?php
/**
 * WCB Child — carrega pai + assets do bloco YITH Frequently Bought Together.
 *
 * @package WCB_Child
 */

defined( 'ABSPATH' ) || exit;

define( 'WCB_CHILD_VERSION', '1.0.5' );

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
			'currency'       => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'BRL',
			'pixPercent'     => 0.95,
			'i18nLeadOne'    => __( 'Leve 1 item por %s', 'wcb-child' ),
			'i18nLeadMany'   => __( 'Leve %d itens por %s', 'wcb-child' ),
			'i18nLeadPix'    => __( '%s no PIX', 'wcb-child' ),
			'i18nCtaSingle'  => __( 'Adicionar ao carrinho', 'wcb-child' ),
			'i18nCtaCombo'   => __( 'Adicionar combo ao carrinho', 'wcb-child' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'wcb_child_enqueue_yith_fbt_assets', 35 );
