<?php
/**
 * WooCommerce → Configurações → Vitrines da Home — painel unificado (Super Ofertas + futuras secções).
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Regista o separador nas Configurações do WooCommerce.
 *
 * @param array<string, string> $tabs Separadores existentes.
 * @return array<string, string>
 */
function wcb_super_ofertas_wc_add_settings_tab( $tabs ) {
	$tabs['wcb_super_ofertas'] = __( 'Vitrines da Home', 'wcb-theme' );
	return $tabs;
}

/**
 * Campos de uma vitrine reutilizáveis (prefixo de opção sem underscore final).
 *
 * @param string $option_prefix ex.: wcb_home_vitrine_novidades
 * @param string $block_note    Texto extra no desc das listas manuais.
 * @return array<int, array<string, mixed>>
 */
function wcb_home_vitrines_wc_fields_for_prefix( $option_prefix, $block_note = '' ) {
	$note = $block_note !== '' ? ' ' . $block_note : '';

	return array(
		array(
			'title'    => __( 'Limite máximo no carrossel', 'wcb-theme' ),
			'desc'     => __( 'Número máximo de produtos na vitrine (1–60).', 'wcb-theme' ),
			'id'       => $option_prefix . '_carousel_max',
			'type'     => 'number',
			'default'  => '24',
			'desc_tip' => true,
			'custom_attributes' => array(
				'min'  => 1,
				'max'  => 60,
				'step' => 1,
			),
		),
		array(
			'title'    => __( 'Modo de montagem', 'wcb-theme' ),
			'desc'     => __( 'Automático: critério da secção no tema. Só lista manual: apenas os IDs abaixo. Híbrido: combina os dois.', 'wcb-theme' ),
			'id'       => $option_prefix . '_assembly_mode',
			'type'     => 'select',
			'default'  => 'automatic',
			'class'    => 'wc-enhanced-select',
			'options'  => array(
				'automatic'   => __( 'Automático', 'wcb-theme' ),
				'manual_only' => __( 'Só lista manual', 'wcb-theme' ),
				'hybrid'      => __( 'Híbrido', 'wcb-theme' ),
			),
		),
		array(
			'title'    => __( 'Prioridade no modo híbrido', 'wcb-theme' ),
			'desc'     => __( 'Ordem ao misturar lista manual e resultado automático. Ignorado fora do modo híbrido.', 'wcb-theme' ),
			'id'       => $option_prefix . '_hybrid_priority',
			'type'     => 'select',
			'default'  => 'manual_first',
			'class'    => 'wc-enhanced-select',
			'options'  => array(
				'manual_first' => __( 'Lista manual primeiro', 'wcb-theme' ),
				'auto_first'   => __( 'Automático primeiro', 'wcb-theme' ),
			),
		),
		array(
			'title'    => __( 'Lista manual de produtos (ordenada)', 'wcb-theme' ),
			'desc'     => __( 'Um ID por linha ou separados por vírgula. Ordem preservada; duplicados ignorados.', 'wcb-theme' ) . $note,
			'id'       => $option_prefix . '_manual_product_ids',
			'type'     => 'textarea',
			'default'  => '',
			'css'      => 'min-width: 400px; min-height: 100px; font-family: monospace;',
			'autoload' => false,
		),
	);
}

/**
 * Campos do separador (Settings API do WooCommerce).
 *
 * @return array<int, array<string, mixed>>
 */
function wcb_home_vitrines_wc_get_settings_fields() {
	$fields = array(
		array(
			'title' => __( 'Configurações gerais', 'wcb-theme' ),
			'type'  => 'title',
			'desc'  => __( 'Painel central das vitrines da página inicial. A Super Ofertas já utiliza estes controlos; Novidades, Mais Vendidos e De Volta ao Estoque guardam opções aqui para integração futura no front-end (comportamento automático atual mantém-se até lá).', 'wcb-theme' ),
			'id'    => 'wcb_home_vitrines_general_title',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'wcb_home_vitrines_general_end',
		),

		array(
			'title' => __( 'Super Ofertas', 'wcb-theme' ),
			'type'  => 'title',
			'desc'  => __( 'Em promoção, score e regras duras do tema (stock mínimo, preço mínimo, visibilidade). Lista manual e modos híbridos já ativos na home.', 'wcb-theme' ),
			'id'    => 'wcb_home_vitrines_so_block_title',
		),
	);

	$fields = array_merge(
		$fields,
		wcb_home_vitrines_wc_fields_for_prefix(
			'wcb_super_ofertas',
			__( '(Ativo na home.)', 'wcb-theme' )
		)
	);

	$fields[] = array(
		'type' => 'sectionend',
		'id'   => 'wcb_home_vitrines_so_block_end',
	);

	$future_note = __( '(Opções guardadas; a home ainda usa só o critério automático até integração.)', 'wcb-theme' );

	$sections = array(
		'novidades' => __( 'Novidades', 'wcb-theme' ),
		'vendidos'  => __( 'Mais vendidos', 'wcb-theme' ),
		'estoque'   => __( 'De volta ao estoque', 'wcb-theme' ),
	);

	foreach ( $sections as $slug => $label ) {
		$fields[] = array(
			'title' => $label,
			'type'  => 'title',
			'desc'  => __( 'Critério automático continua o definido no tema; estas opções ficam disponíveis para o motor partilhado.', 'wcb-theme' ),
			'id'    => 'wcb_home_vitrines_' . $slug . '_title',
		);
		$fields   = array_merge(
			$fields,
			wcb_home_vitrines_wc_fields_for_prefix( 'wcb_home_vitrine_' . $slug, $future_note )
		);
		$fields[] = array(
			'type' => 'sectionend',
			'id'   => 'wcb_home_vitrines_' . $slug . '_end',
		);
	}

	return $fields;
}

/**
 * @return array<int, array<string, mixed>>
 */
function wcb_super_ofertas_wc_get_settings_fields() {
	return wcb_home_vitrines_wc_get_settings_fields();
}

/**
 * Renderiza o separador.
 */
function wcb_super_ofertas_wc_settings_output() {
	if ( ! function_exists( 'woocommerce_admin_fields' ) ) {
		return;
	}
	woocommerce_admin_fields( wcb_home_vitrines_wc_get_settings_fields() );
}

/**
 * Grava opções e invalida caches.
 */
function wcb_super_ofertas_wc_settings_save() {
	if ( ! function_exists( 'woocommerce_update_options' ) ) {
		return;
	}
	woocommerce_update_options( wcb_home_vitrines_wc_get_settings_fields() );
	if ( function_exists( 'wcb_super_ofertas_bust_related_transients' ) ) {
		wcb_super_ofertas_bust_related_transients();
	}
	if ( function_exists( 'wcb_home_vitrines_bust_home_section_caches' ) ) {
		wcb_home_vitrines_bust_home_section_caches();
	}
}

/**
 * Registo de sanitização para um prefixo de vitrine (4 opções).
 *
 * @param string $option_prefix
 */
function wcb_home_vitrines_wc_register_sanitize_for_prefix( $option_prefix ) {
	add_filter( 'woocommerce_admin_settings_sanitize_option_' . $option_prefix . '_carousel_max', 'wcb_super_ofertas_wc_sanitize_carousel_max', 10, 3 );
	add_filter( 'woocommerce_admin_settings_sanitize_option_' . $option_prefix . '_assembly_mode', 'wcb_super_ofertas_wc_sanitize_assembly_mode', 10, 3 );
	add_filter( 'woocommerce_admin_settings_sanitize_option_' . $option_prefix . '_hybrid_priority', 'wcb_super_ofertas_wc_sanitize_hybrid_priority', 10, 3 );
	add_filter( 'woocommerce_admin_settings_sanitize_option_' . $option_prefix . '_manual_product_ids', 'wcb_super_ofertas_wc_sanitize_manual_ids', 10, 3 );
}

/**
 * Liga o separador quando o tema já carregou.
 */
function wcb_super_ofertas_wc_settings_bootstrap() {
	if ( ! class_exists( 'WooCommerce', false ) ) {
		return;
	}
	add_filter( 'woocommerce_settings_tabs_array', 'wcb_super_ofertas_wc_add_settings_tab', 60 );
	add_action( 'woocommerce_settings_wcb_super_ofertas', 'wcb_super_ofertas_wc_settings_output' );
	add_action( 'woocommerce_update_options_wcb_super_ofertas', 'wcb_super_ofertas_wc_settings_save' );

	wcb_home_vitrines_wc_register_sanitize_for_prefix( 'wcb_super_ofertas' );
	foreach ( wcb_home_vitrine_wc_section_slugs() as $slug ) {
		wcb_home_vitrines_wc_register_sanitize_for_prefix( 'wcb_home_vitrine_' . $slug );
	}
}

/**
 * @param mixed $value Valor submetido.
 */
function wcb_super_ofertas_wc_sanitize_carousel_max( $value, $option, $raw_value ) {
	unset( $option, $raw_value );
	$v = absint( $value );
	if ( $v < 1 ) {
		$v = 24;
	}
	if ( $v > 60 ) {
		$v = 60;
	}
	return (string) $v;
}

/**
 * @param mixed $value Valor submetido.
 */
function wcb_super_ofertas_wc_sanitize_assembly_mode( $value, $option, $raw_value ) {
	unset( $option, $raw_value );
	$v = is_string( $value ) ? $value : '';
	if ( ! in_array( $v, array( 'automatic', 'manual_only', 'hybrid' ), true ) ) {
		return 'automatic';
	}
	return $v;
}

/**
 * @param mixed $value Valor submetido.
 */
function wcb_super_ofertas_wc_sanitize_hybrid_priority( $value, $option, $raw_value ) {
	unset( $option, $raw_value );
	$v = is_string( $value ) ? $value : '';
	if ( ! in_array( $v, array( 'manual_first', 'auto_first' ), true ) ) {
		return 'manual_first';
	}
	return $v;
}

/**
 * @param mixed $value Valor submetido.
 */
function wcb_super_ofertas_wc_sanitize_manual_ids( $value, $option, $raw_value ) {
	unset( $option, $raw_value );
	if ( ! is_string( $value ) ) {
		return '';
	}
	return preg_replace( '/[^\d\s,;]/', '', $value );
}
add_action( 'after_setup_theme', 'wcb_super_ofertas_wc_settings_bootstrap', 20 );
