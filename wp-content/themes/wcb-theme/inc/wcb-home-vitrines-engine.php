<?php
/**
 * Motor partilhado — vitrines/carrosséis da home (Fase 2).
 *
 * Super Ofertas mantém a lógica dedicada em super-ofertas-context.php; este ficheiro
 * centraliza leitura normalizada de opções, merge manual/automático e bust de caches
 * para Novidades, Mais Vendidos e De Volta ao Estoque (integração futura no front-page).
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var string Slugs das secções com opções wcb_home_vitrine_{slug}_* */
const WCB_HOME_VITRINE_SECTION_NOVIDADES = 'novidades';
const WCB_HOME_VITRINE_SECTION_VENDIDOS  = 'vendidos';
const WCB_HOME_VITRINE_SECTION_ESTOQUE   = 'estoque';

/**
 * Secções com painel próprio (exclui super_ofertas, que usa wcb_super_ofertas_*).
 *
 * @return string[]
 */
function wcb_home_vitrine_wc_section_slugs() {
	return array(
		WCB_HOME_VITRINE_SECTION_NOVIDADES,
		WCB_HOME_VITRINE_SECTION_VENDIDOS,
		WCB_HOME_VITRINE_SECTION_ESTOQUE,
	);
}

/**
 * Limite 1–60 a partir de valor de opção.
 *
 * @param mixed $value
 * @return int
 */
function wcb_home_vitrine_effective_cap_from_value( $value ) {
	$v = absint( $value );
	if ( $v < 1 ) {
		$v = 24;
	}
	if ( $v > 60 ) {
		$v = 60;
	}
	return $v;
}

/**
 * Configuração normalizada para uma vitrine.
 *
 * @param string $section super_ofertas | novidades | vendidos | estoque
 * @return array{carousel_max:int,assembly_mode:string,hybrid_priority:string,manual_raw:string,manual_ids_parsed:int[]}
 */
function wcb_home_vitrine_get_normalized_config( $section ) {
	$section = is_string( $section ) ? $section : '';
	$empty   = array(
		'carousel_max'      => 24,
		'assembly_mode'     => 'automatic',
		'hybrid_priority'   => 'manual_first',
		'manual_raw'        => '',
		'manual_ids_parsed' => array(),
	);

	if ( 'super_ofertas' === $section ) {
		if ( function_exists( 'wcb_super_ofertas_get_wc_settings' ) && function_exists( 'wcb_super_ofertas_effective_carousel_cap' ) ) {
			$so = wcb_super_ofertas_get_wc_settings();
			return array(
				'carousel_max'      => wcb_super_ofertas_effective_carousel_cap(),
				'assembly_mode'     => $so['assembly_mode'],
				'hybrid_priority'   => $so['hybrid_priority'],
				'manual_raw'        => $so['manual_raw'],
				'manual_ids_parsed' => $so['manual_ids_parsed'],
			);
		}
		return $empty;
	}

	if ( ! in_array( $section, wcb_home_vitrine_wc_section_slugs(), true ) ) {
		return $empty;
	}

	$p    = 'wcb_home_vitrine_' . $section . '_';
	$mode = get_option( $p . 'assembly_mode', 'automatic' );
	$mode = is_string( $mode ) ? $mode : 'automatic';
	if ( ! in_array( $mode, array( 'automatic', 'manual_only', 'hybrid' ), true ) ) {
		$mode = 'automatic';
	}

	$prio = get_option( $p . 'hybrid_priority', 'manual_first' );
	$prio = is_string( $prio ) ? $prio : 'manual_first';
	if ( ! in_array( $prio, array( 'manual_first', 'auto_first' ), true ) ) {
		$prio = 'manual_first';
	}

	$raw = get_option( $p . 'manual_product_ids', '' );
	$raw = is_string( $raw ) ? $raw : '';

	$parsed = function_exists( 'wcb_super_ofertas_parse_manual_id_list' )
		? wcb_super_ofertas_parse_manual_id_list( $raw )
		: array();

	return array(
		'carousel_max'      => wcb_home_vitrine_effective_cap_from_value( get_option( $p . 'carousel_max', 24 ) ),
		'assembly_mode'     => $mode,
		'hybrid_priority'   => $prio,
		'manual_raw'        => $raw,
		'manual_ids_parsed' => $parsed,
	);
}

/**
 * Junta listas automática e manual conforme modo e prioridade (sem dedupe extra entre si além da função de merge).
 *
 * @param int[]  $manual_ordered IDs manuais ordenados.
 * @param int[]  $auto_ordered   IDs do critério automático da secção.
 * @param string $mode           automatic | manual_only | hybrid.
 * @param string $hybrid_priority manual_first | auto_first.
 * @return int[]
 */
function wcb_home_vitrine_merge_by_mode( array $manual_ordered, array $auto_ordered, $mode, $hybrid_priority ) {
	$mode            = is_string( $mode ) ? $mode : 'automatic';
	$hybrid_priority = is_string( $hybrid_priority ) ? $hybrid_priority : 'manual_first';

	if ( 'manual_only' === $mode ) {
		return array_values( array_map( 'intval', $manual_ordered ) );
	}
	if ( 'automatic' === $mode ) {
		return array_values( array_map( 'intval', $auto_ordered ) );
	}

	if ( 'manual_first' === $hybrid_priority && function_exists( 'wcb_super_ofertas_merge_priority_lists' ) ) {
		return wcb_super_ofertas_merge_priority_lists( $manual_ordered, $auto_ordered );
	}
	if ( function_exists( 'wcb_super_ofertas_merge_priority_lists' ) ) {
		return wcb_super_ofertas_merge_priority_lists( $auto_ordered, $manual_ordered );
	}

	return array_values(
		array_unique(
			array_merge(
				array_map( 'intval', $manual_ordered ),
				array_map( 'intval', $auto_ordered )
			)
		)
	);
}

/**
 * Aplica limite final.
 *
 * @param int[] $ids
 * @param int   $cap
 * @return int[]
 */
function wcb_home_vitrine_apply_cap( array $ids, $cap ) {
	$cap = max( 1, (int) $cap );
	$out = array();
	$seen = array();
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( $id < 1 || isset( $seen[ $id ] ) ) {
			continue;
		}
		$seen[ $id ] = true;
		$out[]       = $id;
		if ( count( $out ) >= $cap ) {
			break;
		}
	}
	return $out;
}

/**
 * Assinatura para futuras chaves de cache por secção (Fase 3).
 *
 * @param string $section
 * @return string
 */
function wcb_home_vitrine_settings_cache_signature( $section ) {
	$cfg = wcb_home_vitrine_get_normalized_config( $section );
	return md5( wp_json_encode( $cfg ) );
}

/**
 * Nome do transient de Novidades (v4: inclui hash da config do painel).
 *
 * @return string
 */
function wcb_home_vitrine_novidades_get_cache_transient_name() {
	return 'wcb_home_novidades_v4_' . wcb_home_vitrine_settings_cache_signature( 'novidades' );
}

/**
 * Critério automático Novidades (igual à query antiga da home): produto publicado, _stock_status instock, ordenação por data DESC.
 * Pool até 60 para permitir caps do painel até 60 (antes fixava-se 30 na query).
 *
 * @return int[]
 */
function wcb_home_vitrine_novidades_get_auto_ids() {
	$q = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => 60,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_term_meta_cache' => false,
			'meta_query'             => array(
				array(
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '=',
				),
			),
		)
	);
	$ids = array_map( 'intval', $q->posts );
	wp_reset_postdata();
	return array_values(
		array_filter(
			$ids,
			static function ( $id ) {
				return $id > 0;
			}
		)
	);
}

/**
 * Lista manual Novidades: mesma base que o automático (publicado, instock) + visível no catálogo; ordem preservada.
 *
 * @param int[] $ordered_ids
 * @return int[]
 */
function wcb_home_vitrine_novidades_validate_manual_ids( array $ordered_ids ) {
	$ordered_ids = array_values(
		array_filter(
			array_map( 'intval', $ordered_ids ),
			static function ( $id ) {
				return $id > 0;
			}
		)
	);
	if ( empty( $ordered_ids ) ) {
		return array();
	}

	$q = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'post__in'               => $ordered_ids,
			'orderby'                => 'post__in',
			'posts_per_page'         => count( $ordered_ids ),
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_term_meta_cache' => false,
			'meta_query'             => array(
				array(
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '=',
				),
			),
		)
	);
	$found    = array_map( 'intval', $q->posts );
	$found_set = array_fill_keys( $found, true );
	wp_reset_postdata();

	$out = array();
	foreach ( $ordered_ids as $id ) {
		if ( empty( $found_set[ $id ] ) ) {
			continue;
		}
		$p = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
		if ( $p && $p->is_visible() ) {
			$out[] = $id;
		}
	}
	return $out;
}

/**
 * IDs finais da vitrine Novidades (motor: auto + manual + cap).
 *
 * @return int[]
 */
function wcb_home_vitrine_novidades_resolve_final_ids() {
	$cfg = wcb_home_vitrine_get_normalized_config( 'novidades' );
	$auto = wcb_home_vitrine_novidades_get_auto_ids();
	$manual = wcb_home_vitrine_novidades_validate_manual_ids( $cfg['manual_ids_parsed'] );
	$merged = wcb_home_vitrine_merge_by_mode(
		$manual,
		$auto,
		$cfg['assembly_mode'],
		$cfg['hybrid_priority']
	);
	return wcb_home_vitrine_apply_cap( $merged, $cfg['carousel_max'] );
}

/**
 * Nome do transient de Mais Vendidos (v4: inclui hash da config do painel).
 *
 * @return string
 */
function wcb_home_vitrine_vendidos_get_cache_transient_name() {
	return 'wcb_home_vendidos_v4_' . wcb_home_vitrine_settings_cache_signature( 'vendidos' );
}

/**
 * Critério automático Mais Vendidos (igual à home): por total_sales DESC; se vazio, fallback por data DESC.
 * Pool até 60 para caps do painel (antes fixava-se 20 na query).
 *
 * @return int[]
 */
function wcb_home_vitrine_vendidos_get_auto_ids() {
	$q = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => 60,
			'meta_key'               => 'total_sales',
			'orderby'                => 'meta_value_num',
			'order'                  => 'DESC',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_term_meta_cache' => false,
		)
	);
	$ids = array_map( 'intval', $q->posts );
	wp_reset_postdata();
	$ids = array_values(
		array_filter(
			$ids,
			static function ( $id ) {
				return $id > 0;
			}
		)
	);

	if ( ! empty( $ids ) ) {
		return $ids;
	}

	$fb = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => 60,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_term_meta_cache' => false,
		)
	);
	$ids2 = array_map( 'intval', $fb->posts );
	wp_reset_postdata();
	return array_values(
		array_filter(
			$ids2,
			static function ( $id ) {
				return $id > 0;
			}
		)
	);
}

/**
 * Lista manual Mais Vendidos: publicado, instock, visível; ordem preservada.
 *
 * @param int[] $ordered_ids
 * @return int[]
 */
function wcb_home_vitrine_vendidos_validate_manual_ids( array $ordered_ids ) {
	$ordered_ids = array_values(
		array_filter(
			array_map( 'intval', $ordered_ids ),
			static function ( $id ) {
				return $id > 0;
			}
		)
	);
	if ( empty( $ordered_ids ) ) {
		return array();
	}

	$q = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'post__in'               => $ordered_ids,
			'orderby'                => 'post__in',
			'posts_per_page'         => count( $ordered_ids ),
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_term_meta_cache' => false,
			'meta_query'             => array(
				array(
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '=',
				),
			),
		)
	);
	$found     = array_map( 'intval', $q->posts );
	$found_set = array_fill_keys( $found, true );
	wp_reset_postdata();

	$out = array();
	foreach ( $ordered_ids as $id ) {
		if ( empty( $found_set[ $id ] ) ) {
			continue;
		}
		$p = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
		if ( $p && $p->is_visible() ) {
			$out[] = $id;
		}
	}
	return $out;
}

/**
 * IDs finais da vitrine Mais Vendidos (motor: auto + manual + cap).
 *
 * @return int[]
 */
function wcb_home_vitrine_vendidos_resolve_final_ids() {
	$cfg    = wcb_home_vitrine_get_normalized_config( 'vendidos' );
	$auto   = wcb_home_vitrine_vendidos_get_auto_ids();
	$manual = wcb_home_vitrine_vendidos_validate_manual_ids( $cfg['manual_ids_parsed'] );
	$merged = wcb_home_vitrine_merge_by_mode(
		$manual,
		$auto,
		$cfg['assembly_mode'],
		$cfg['hybrid_priority']
	);
	return wcb_home_vitrine_apply_cap( $merged, $cfg['carousel_max'] );
}

/**
 * Invalida transients HTML/IDs das secções da home (além da Super Ofertas).
 * Chamado ao guardar o painel unificado.
 */
function wcb_home_vitrines_bust_home_section_caches() {
	if ( ! function_exists( 'delete_transient' ) ) {
		return;
	}
	delete_transient( 'wcb_home_novidades_v3' );
	delete_transient( 'wcb_home_novidades_v2' );
	delete_transient( 'wcb_home_novidades' );
	delete_transient( 'wcb_home_vendidos' );
	delete_transient( 'wcb_home_estoque' );

	global $wpdb;
	$like_t  = $wpdb->esc_like( '_transient_wcb_home_novidades_v4_' ) . '%';
	$like_to = $wpdb->esc_like( '_transient_timeout_wcb_home_novidades_v4_' ) . '%';
	$like_v  = $wpdb->esc_like( '_transient_wcb_home_vendidos_v4_' ) . '%';
	$like_vo = $wpdb->esc_like( '_transient_timeout_wcb_home_vendidos_v4_' ) . '%';
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
			$like_t,
			$like_to,
			$like_v,
			$like_vo
		)
	);
}
