<?php
/**
 * Super Ofertas — contexto orientado a scoring (hero + carrossel + urgência).
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var int Mínimo de unidades (gestão ativa) para o hero principal */
const WCB_SO_HERO_MIN_STOCK = 5;

/** @var int Excluir do carrossel se stock gerido abaixo disto */
const WCB_SO_CAROUSEL_MIN_STOCK = 2;

/** @var float Preço mínimo (BRL) no carrossel */
const WCB_SO_MIN_CAROUSEL_PRICE = 10.0;

/** @var int Máx. IDs candidatos a partir da lista em promoção */
const WCB_SO_POOL_CAP = 120;

/** @var int Máx. produtos no carrossel (antes do chunk) */
const WCB_SO_MAX_CAROUSEL = 24;

/** @var float Boost de score para upsell (mesma categoria dominante do hero) */
const WCB_SO_UPSELL_BOOST = 3.0;

/**
 * Preços regular / sale / atual para scoring (simples + variável).
 *
 * @param WC_Product $product Produto.
 * @return array{regular:float,sale:float,current:float,discount_pct:float}
 */
function wcb_super_ofertas_price_metrics( WC_Product $product ) {
	$regular = (float) $product->get_regular_price();
	$sale    = $product->get_sale_price() !== '' && $product->get_sale_price() !== null
		? (float) $product->get_sale_price()
		: 0.0;
	$current = (float) $product->get_price();

	if ( $product->is_type( 'variable' ) ) {
		$vr = (float) $product->get_variation_regular_price( 'min', true );
		$vs = (float) $product->get_variation_sale_price( 'min', true );
		if ( $regular <= 0 && $vr > 0 ) {
			$regular = $vr;
		}
		if ( $sale <= 0 && $vs > 0 ) {
			$sale = $vs;
		}
	}

	$discount_pct = 0.0;
	if ( $regular > 0 && $sale > 0 && $sale < $regular ) {
		$discount_pct = ( ( $regular - $sale ) / $regular ) * 100;
	}

	return array(
		'regular'       => $regular,
		'sale'          => $sale,
		'current'       => $current > 0 ? $current : ( $sale > 0 ? $sale : $regular ),
		'discount_pct'  => $discount_pct,
	);
}

/**
 * Stock score 1 / 0.5 / 0 para fórmula global.
 */
function wcb_super_ofertas_stock_score_component( WC_Product $product ) {
	if ( ! $product->is_in_stock() ) {
		return 0.0;
	}
	if ( ! $product->managing_stock() ) {
		return 1.0;
	}
	$q = $product->get_stock_quantity();
	if ( $q === null ) {
		return 1.0;
	}
	$q = (int) $q;
	if ( $q >= 5 ) {
		return 1.0;
	}
	if ( $q >= 1 ) {
		return 0.5;
	}
	return 0.0;
}

/**
 * Quantidade de stock para regras de filtro (null = não gerido).
 */
function wcb_super_ofertas_stock_qty( WC_Product $product ) {
	if ( ! $product->managing_stock() ) {
		return null;
	}
	$q = $product->get_stock_quantity();
	return $q === null ? null : (int) $q;
}

/**
 * Hero principal: stock gerido >= mínimo; sem gestão + instock = elegível.
 */
function wcb_super_ofertas_hero_stock_ok( WC_Product $product ) {
	if ( ! $product->is_in_stock() ) {
		return false;
	}
	$qty = wcb_super_ofertas_stock_qty( $product );
	if ( $qty === null ) {
		return true;
	}
	return $qty >= WCB_SO_HERO_MIN_STOCK;
}

/**
 * Carrossel: excluir stock gerido abaixo do mínimo.
 */
function wcb_super_ofertas_carousel_stock_ok( WC_Product $product ) {
	if ( ! $product->is_in_stock() ) {
		return false;
	}
	$qty = wcb_super_ofertas_stock_qty( $product );
	if ( $qty === null ) {
		return true;
	}
	return $qty >= WCB_SO_CAROUSEL_MIN_STOCK;
}

/**
 * Micro-copy do hero (primário).
 *
 * @param array<string, mixed> $m Métricas internas do produto.
 */
function wcb_super_ofertas_hero_badge_for_metrics( array $m ) {
	$sales = (int) ( $m['total_sales'] ?? 0 );
	$disc  = (float) ( $m['discount_pct'] ?? 0 );
	$price = (float) ( $m['current'] ?? 0 );
	$median = (float) ( $m['pool_price_median'] ?? 0 );

	if ( $sales >= 30 ) {
		return __( 'Mais vendido da loja', 'wcb-theme' );
	}
	if ( $disc >= 25 ) {
		return __( 'Oferta imperdível', 'wcb-theme' );
	}
	if ( $median > 0 && $price > 0 && $price <= $median && $disc >= 12 ) {
		return __( 'Melhor custo-benefício', 'wcb-theme' );
	}
	if ( $disc > 0 ) {
		return __( 'Destaque', 'wcb-theme' );
	}
	return __( 'Destaque', 'wcb-theme' );
}

/**
 * Opções WooCommerce (Super Ofertas).
 *
 * @return array{assembly_mode:string,hybrid_priority:string,manual_raw:string,manual_ids_parsed:int[]}
 */
function wcb_super_ofertas_get_wc_settings() {
	$mode = get_option( 'wcb_super_ofertas_assembly_mode', 'automatic' );
	$mode = is_string( $mode ) ? $mode : 'automatic';
	if ( ! in_array( $mode, array( 'automatic', 'manual_only', 'hybrid' ), true ) ) {
		$mode = 'automatic';
	}

	$prio = get_option( 'wcb_super_ofertas_hybrid_priority', 'manual_first' );
	$prio = is_string( $prio ) ? $prio : 'manual_first';
	if ( ! in_array( $prio, array( 'manual_first', 'auto_first' ), true ) ) {
		$prio = 'manual_first';
	}

	$raw_manual = get_option( 'wcb_super_ofertas_manual_product_ids', '' );
	$raw_manual = is_string( $raw_manual ) ? $raw_manual : '';

	return array(
		'assembly_mode'     => $mode,
		'hybrid_priority'   => $prio,
		'manual_raw'        => $raw_manual,
		'manual_ids_parsed' => wcb_super_ofertas_parse_manual_id_list( $raw_manual ),
	);
}

/**
 * Limite efetivo do carrossel (1–60), substituindo WCB_SO_MAX_CAROUSEL quando configurado.
 *
 * @return int
 */
function wcb_super_ofertas_effective_carousel_cap() {
	$v = absint( get_option( 'wcb_super_ofertas_carousel_max', 24 ) );
	if ( $v < 1 ) {
		$v = 24;
	}
	if ( $v > 60 ) {
		$v = 60;
	}
	return $v;
}

/**
 * Lista manual: preserva ordem, remove duplicados e valores inválidos.
 *
 * @param string $raw Textarea / CSV.
 * @return int[]
 */
function wcb_super_ofertas_parse_manual_id_list( $raw ) {
	$raw = (string) $raw;
	$raw = str_replace( array( "\r\n", "\r" ), "\n", $raw );
	$raw = str_replace( "\n", ',', $raw );
	$parts = preg_split( '/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
	if ( ! is_array( $parts ) ) {
		return array();
	}
	$out  = array();
	$seen = array();
	foreach ( $parts as $p ) {
		$id = absint( $p );
		if ( $id < 1 || isset( $seen[ $id ] ) ) {
			continue;
		}
		$seen[ $id ] = true;
		$out[]       = $id;
	}
	return $out;
}

/**
 * Assinatura das opções para cache (live search + transient).
 *
 * @return string
 */
function wcb_super_ofertas_settings_cache_signature() {
	$cfg = wcb_super_ofertas_get_wc_settings();
	return md5(
		wp_json_encode(
			array(
				'cap'  => wcb_super_ofertas_effective_carousel_cap(),
				'mode' => $cfg['assembly_mode'],
				'prio' => $cfg['hybrid_priority'],
				'man'  => $cfg['manual_ids_parsed'],
			)
		)
	);
}

/**
 * Invalida transients ligados à Super Ofertas e à pesquisa que reutiliza o contexto.
 */
function wcb_super_ofertas_bust_related_transients() {
	global $wpdb;
	$patterns = array(
		$wpdb->esc_like( '_transient_wcb_super_ofertas_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_wcb_super_ofertas_' ) . '%',
		$wpdb->esc_like( '_transient_wcb_ls_flash_set_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_wcb_ls_flash_set_' ) . '%',
	);
	foreach ( $patterns as $pat ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $pat ) );
	}
}

/**
 * Se deve chamar wcb_super_ofertas_build_context (ex.: lista manual sem on_sale global).
 *
 * @param int[] $on_sale_ids
 * @return bool
 */
function wcb_super_ofertas_should_build_context( $on_sale_ids ) {
	$cfg = wcb_super_ofertas_get_wc_settings();
	if ( in_array( $cfg['assembly_mode'], array( 'manual_only', 'hybrid' ), true ) && ! empty( $cfg['manual_ids_parsed'] ) ) {
		return true;
	}
	return ! empty( $on_sale_ids );
}

/**
 * Junta duas listas sem duplicados; a segunda só acrescenta IDs novos.
 *
 * @param int[] $primary
 * @param int[] $secondary
 * @return int[]
 */
function wcb_super_ofertas_merge_priority_lists( array $primary, array $secondary ) {
	$seen = array();
	$out  = array();
	foreach ( $primary as $id ) {
		$id = (int) $id;
		if ( $id < 1 || isset( $seen[ $id ] ) ) {
			continue;
		}
		$seen[ $id ] = true;
		$out[]       = $id;
	}
	foreach ( $secondary as $id ) {
		$id = (int) $id;
		if ( $id < 1 || isset( $seen[ $id ] ) ) {
			continue;
		}
		$seen[ $id ] = true;
		$out[]       = $id;
	}
	return $out;
}

/**
 * Linha de scoring para um produto ou null se não entra na vitrine.
 *
 * @return array<string, mixed>|null
 */
function wcb_super_ofertas_make_row_from_product( WC_Product $p ) {
	if ( ! $p->is_in_stock() || ! $p->is_visible() ) {
		return null;
	}
	if ( ! $p->is_on_sale() ) {
		return null;
	}
	$pm      = wcb_super_ofertas_price_metrics( $p );
	$current = $pm['current'];
	$disc    = $pm['discount_pct'];
	if ( $disc <= 0 && $pm['sale'] <= 0 ) {
		return null;
	}
	$total_sales = (int) $p->get_total_sales();
	$stock_comp  = wcb_super_ofertas_stock_score_component( $p );
	$score       = ( log( 1 + max( 0, $total_sales ) ) * 3 )
		+ ( $current * 0.002 )
		+ ( $disc * 2 )
		+ ( $stock_comp * 1.5 );

	return array(
		'product'      => $p,
		'score'        => $score,
		'total_sales'  => $total_sales,
		'discount_pct' => $disc,
		'current'      => $current,
		'stock_comp'   => $stock_comp,
	);
}

/**
 * Mediana de preço atual nas linhas.
 *
 * @param array<int, array<string, mixed>> $rows
 */
function wcb_super_ofertas_median_price_from_rows( array $rows ) {
	$prices = array();
	foreach ( $rows as $r ) {
		$prices[] = $r['current'];
	}
	sort( $prices, SORT_NUMERIC );
	$n_p = count( $prices );
	if ( $n_p < 1 ) {
		return 0.0;
	}
	$mid = (int) floor( $n_p / 2 );
	return ( $n_p % 2 === 1 ) ? (float) $prices[ $mid ] : (float) ( $prices[ $mid - 1 ] + $prices[ $mid ] ) / 2;
}

/**
 * Urgência + badges a partir do conjunto de linhas.
 *
 * @param array<int, array<string, mixed>> $rows
 */
function wcb_super_ofertas_compute_urgency_and_badges( array $rows, $hero_id, $hero_id_2, $median_price ) {
	$max_disc = 0.0;
	$max_sale = 0;
	$min_mq   = null;
	foreach ( $rows as $r ) {
		$max_disc = max( $max_disc, $r['discount_pct'] );
		$max_sale = max( $max_sale, $r['total_sales'] );
		$p        = $r['product'];
		if ( $p->managing_stock() ) {
			$q = $p->get_stock_quantity();
			if ( $q !== null ) {
				$qi = (int) $q;
				if ( $qi > 0 && $qi <= 5 ) {
					$min_mq = ( null === $min_mq ) ? $qi : min( $min_mq, $qi );
				}
			}
		}
	}

	$urgency_type         = 'default';
	$urgency_discount_pct = (int) round( $max_disc );

	if ( null !== $min_mq && $min_mq <= 3 ) {
		$urgency_type = 'low_stock';
	} elseif ( $max_disc >= 28 ) {
		$urgency_type = 'high_discount';
	} elseif ( $max_sale >= 40 ) {
		$urgency_type = 'high_sales';
	}

	$hero_badge   = '';
	$hero_badge_2 = '';

	if ( $hero_id > 0 && isset( $rows[ $hero_id ] ) ) {
		$r1         = $rows[ $hero_id ];
		$hero_badge = wcb_super_ofertas_hero_badge_for_metrics(
			array(
				'total_sales'       => $r1['total_sales'],
				'discount_pct'      => $r1['discount_pct'],
				'current'           => $r1['current'],
				'pool_price_median' => $median_price,
			)
		);
	}
	if ( $hero_id_2 > 0 && isset( $rows[ $hero_id_2 ] ) ) {
		$r2           = $rows[ $hero_id_2 ];
		$hero_badge_2 = wcb_super_ofertas_hero_badge_for_metrics(
			array(
				'total_sales'       => $r2['total_sales'],
				'discount_pct'      => $r2['discount_pct'],
				'current'           => $r2['current'],
				'pool_price_median' => $median_price,
			)
		);
	}

	return array(
		'urgency_type'         => $urgency_type,
		'urgency_discount_pct' => max( 0, $urgency_discount_pct ),
		'hero_badge'           => $hero_badge,
		'hero_badge_2'         => $hero_badge_2,
	);
}

/**
 * $rows a partir de IDs ordenados (mesmas regras duras do carrossel).
 *
 * @param int[]              $ordered_ids
 * @param array<int, bool>   $exclude_map
 * @return array<int, array<string, mixed>>
 */
function wcb_super_ofertas_rows_from_ordered_ids( array $ordered_ids, array $exclude_map ) {
	$ordered_ids = array_values(
		array_filter(
			array_map( 'intval', $ordered_ids ),
			static function ( $id ) use ( $exclude_map ) {
				return $id > 0 && empty( $exclude_map[ $id ] );
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
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_term_meta_cache' => false,
			'meta_query'             => array( wcb_carousel_meta_instock() ),
		)
	);

	$ids = array_map( 'intval', $q->posts );
	wp_reset_postdata();

	if ( empty( $ids ) ) {
		return array();
	}

	$products = wc_get_products(
		array(
			'include' => $ids,
			'limit'   => -1,
			'status'  => 'publish',
			'return'  => 'objects',
		)
	);

	$by_id = array();
	foreach ( $products as $p ) {
		if ( $p instanceof WC_Product ) {
			$by_id[ (int) $p->get_id() ] = $p;
		}
	}

	$rows = array();
	foreach ( $ordered_ids as $pid ) {
		if ( empty( $by_id[ $pid ] ) ) {
			continue;
		}
		$row = wcb_super_ofertas_make_row_from_product( $by_id[ $pid ] );
		if ( null === $row ) {
			continue;
		}
		$p = $row['product'];
		if ( ! wcb_super_ofertas_carousel_stock_ok( $p ) ) {
			continue;
		}
		if ( $row['current'] < WCB_SO_MIN_CAROUSEL_PRICE ) {
			continue;
		}
		$rows[ $pid ] = $row;
	}

	return $rows;
}

/**
 * Pipeline de score (lista em promoção) — igual ao legado, com limite de carrossel configurável.
 *
 * @param int[]            $on_sale_ids
 * @param array<int, bool> $exclude_map
 * @param int              $carousel_cap Limite de itens só na fatia do carrossel (exclui heróis).
 * @return array<string, mixed>|null
 */
function wcb_super_ofertas_run_scoring_pipeline( $on_sale_ids, $exclude_map, $carousel_cap ) {
	if ( ! function_exists( 'wc_get_products' ) || empty( $on_sale_ids ) ) {
		return null;
	}

	$pool = array_values(
		array_unique(
			array_filter(
				array_map( 'intval', (array) $on_sale_ids ),
				static function ( $id ) use ( $exclude_map ) {
					return $id > 0 && empty( $exclude_map[ $id ] );
				}
			)
		)
	);

	if ( count( $pool ) > WCB_SO_POOL_CAP ) {
		$pool = array_slice( $pool, 0, WCB_SO_POOL_CAP );
	}

	if ( empty( $pool ) ) {
		return null;
	}

	$q = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'post__in'               => $pool,
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_term_meta_cache' => false,
			'meta_query'             => array( wcb_carousel_meta_instock() ),
		)
	);

	$instock_ids = array_map( 'intval', $q->posts );
	wp_reset_postdata();

	if ( empty( $instock_ids ) ) {
		return null;
	}

	$products = wc_get_products(
		array(
			'include' => $instock_ids,
			'limit'   => -1,
			'status'  => 'publish',
			'return'  => 'objects',
		)
	);

	$by_id = array();
	foreach ( $products as $p ) {
		if ( $p instanceof WC_Product ) {
			$by_id[ (int) $p->get_id() ] = $p;
		}
	}

	$rows = array();
	foreach ( $instock_ids as $pid ) {
		if ( empty( $by_id[ $pid ] ) ) {
			continue;
		}
		$row = wcb_super_ofertas_make_row_from_product( $by_id[ $pid ] );
		if ( null !== $row ) {
			$rows[ $pid ] = $row;
		}
	}

	if ( empty( $rows ) ) {
		return null;
	}

	uasort(
		$rows,
		static function ( $a, $b ) {
			$ds = $b['score'] <=> $a['score'];
			if ( 0 !== $ds ) {
				return $ds;
			}
			return $b['discount_pct'] <=> $a['discount_pct'];
		}
	);

	$sorted_ids = array_keys( $rows );

	$hero_id = 0;
	foreach ( $sorted_ids as $pid ) {
		$p = $rows[ $pid ]['product'];
		if ( wcb_super_ofertas_hero_stock_ok( $p ) ) {
			$hero_id = $pid;
			break;
		}
	}
	if ( $hero_id < 1 ) {
		$best_fb = 0.0;
		foreach ( $sorted_ids as $pid ) {
			$r  = $rows[ $pid ];
			$fb = $r['current'] * ( 1 + $r['discount_pct'] / 100 );
			if ( $fb >= $best_fb ) {
				$best_fb = $fb;
				$hero_id = $pid;
			}
		}
	}

	if ( $hero_id < 1 ) {
		return null;
	}

	$hero_dom = function_exists( 'wcb_carousel_dominant_category_id' )
		? wcb_carousel_dominant_category_id( array( $hero_id ) )
		: 0;

	$hero_id_2 = 0;
	foreach ( $sorted_ids as $pid ) {
		if ( $pid === $hero_id ) {
			continue;
		}
		if ( $hero_dom > 0 && has_term( $hero_dom, 'product_cat', $pid ) ) {
			continue;
		}
		$hero_id_2 = $pid;
		break;
	}

	if ( $hero_id_2 < 1 ) {
		foreach ( $sorted_ids as $pid ) {
			if ( $pid !== $hero_id ) {
				$hero_id_2 = $pid;
				break;
			}
		}
	}

	$used = array( $hero_id => true );
	if ( $hero_id_2 > 0 ) {
		$used[ $hero_id_2 ] = true;
	}

	$carousel_candidates = array();
	foreach ( $sorted_ids as $pid ) {
		if ( ! empty( $used[ $pid ] ) ) {
			continue;
		}
		$p = $rows[ $pid ]['product'];
		if ( ! wcb_super_ofertas_carousel_stock_ok( $p ) ) {
			continue;
		}
		if ( $rows[ $pid ]['current'] < WCB_SO_MIN_CAROUSEL_PRICE ) {
			continue;
		}
		$boost                       = ( $hero_dom > 0 && has_term( $hero_dom, 'product_cat', $pid ) ) ? WCB_SO_UPSELL_BOOST : 0.0;
		$carousel_candidates[ $pid ] = $rows[ $pid ]['score'] + $boost;
	}

	arsort( $carousel_candidates, SORT_NUMERIC );
	$carousel_keys_all   = array_keys( $carousel_candidates );
	$carousel_cap        = max( 1, (int) $carousel_cap );
	$carousel_ids_sliced = array_slice( $carousel_keys_all, 0, $carousel_cap );

	$auto_order_full = array();
	if ( $hero_id > 0 ) {
		$auto_order_full[] = (int) $hero_id;
	}
	if ( $hero_id_2 > 0 && $hero_id_2 !== $hero_id ) {
		$auto_order_full[] = (int) $hero_id_2;
	}
	foreach ( $carousel_keys_all as $cid ) {
		$auto_order_full[] = (int) $cid;
	}
	$auto_order_full = array_values( array_unique( array_map( 'intval', $auto_order_full ) ) );

	$median_price = wcb_super_ofertas_median_price_from_rows( $rows );

	return array(
		'rows'            => $rows,
		'median_price'    => $median_price,
		'hero_id'         => (int) $hero_id,
		'hero_id_2'       => (int) $hero_id_2,
		'carousel_ids'    => $carousel_ids_sliced,
		'auto_order_full' => $auto_order_full,
		'sorted_ids'      => $sorted_ids,
	);
}

/**
 * Empacota contexto com ordem final plana (sem prepend de heróis na home).
 *
 * @param int[]                             $final_ids
 * @param array<int, array<string, mixed>> $rows
 * @return array<string, mixed>
 */
function wcb_super_ofertas_package_flat_context( array $final_ids, array $rows ) {
	$final_ids = array_values(
		array_unique(
			array_filter(
				array_map( 'intval', $final_ids ),
				static function ( $id ) {
					return $id > 0;
				}
			)
		)
	);

	$hero_id   = $final_ids[0] ?? 0;
	$hero_id_2 = $final_ids[1] ?? 0;

	$subset_rows = array();
	foreach ( $final_ids as $fid ) {
		if ( isset( $rows[ $fid ] ) ) {
			$subset_rows[ $fid ] = $rows[ $fid ];
		}
	}
	$median = wcb_super_ofertas_median_price_from_rows( ! empty( $subset_rows ) ? $subset_rows : $rows );

	$badges = wcb_super_ofertas_compute_urgency_and_badges( ! empty( $subset_rows ) ? $subset_rows : $rows, $hero_id, $hero_id_2, $median );

	return array(
		'hero_id'              => (int) $hero_id,
		'hero_id_2'            => (int) $hero_id_2,
		'carousel_ids'         => $final_ids,
		'urgency_type'         => $badges['urgency_type'],
		'urgency_discount_pct' => $badges['urgency_discount_pct'],
		'hero_badge'           => $badges['hero_badge'],
		'hero_badge_2'         => $badges['hero_badge_2'],
		'skip_hero_prepend'    => true,
	);
}

/**
 * Modo só lista manual.
 *
 * @param int[]              $manual_ids
 * @param array<int, bool>   $exclude_map
 * @param int                $cap
 * @return array<string, mixed>|null
 */
function wcb_super_ofertas_build_manual_only_context( array $manual_ids, array $exclude_map, $cap ) {
	if ( empty( $manual_ids ) ) {
		return null;
	}
	$rows = wcb_super_ofertas_rows_from_ordered_ids( $manual_ids, $exclude_map );
	if ( empty( $rows ) ) {
		return null;
	}
	$ordered_valid = array();
	foreach ( $manual_ids as $mid ) {
		$mid = (int) $mid;
		if ( $mid > 0 && isset( $rows[ $mid ] ) && empty( $exclude_map[ $mid ] ) ) {
			$ordered_valid[] = $mid;
		}
	}
	if ( empty( $ordered_valid ) ) {
		return null;
	}
	$final = array_slice( $ordered_valid, 0, max( 1, (int) $cap ) );
	return wcb_super_ofertas_package_flat_context( $final, $rows );
}

/**
 * Retorno modo automático (prepend de heróis na home).
 *
 * @param array<string, mixed> $pipe
 * @param int                  $cap
 * @return array<string, mixed>
 */
function wcb_super_ofertas_build_context_automatic_return( array $pipe, $cap ) {
	$hero_id   = (int) $pipe['hero_id'];
	$hero_id_2 = (int) $pipe['hero_id_2'];
	$rows      = $pipe['rows'];
	$median    = (float) $pipe['median_price'];
	$badges    = wcb_super_ofertas_compute_urgency_and_badges( $rows, $hero_id, $hero_id_2, $median );
	$cap       = max( 1, (int) $cap );

	return array(
		'hero_id'              => $hero_id,
		'hero_id_2'            => $hero_id_2,
		'carousel_ids'         => array_slice( (array) $pipe['carousel_ids'], 0, $cap ),
		'urgency_type'         => $badges['urgency_type'],
		'urgency_discount_pct' => $badges['urgency_discount_pct'],
		'hero_badge'           => $badges['hero_badge'],
		'hero_badge_2'         => $badges['hero_badge_2'],
		'skip_hero_prepend'    => false,
	);
}

/**
 * Constrói contexto completo (sem transient — o caller trata cache).
 *
 * @param int[] $on_sale_ids IDs retornados por wc_get_product_ids_on_sale().
 * @param int[] $exclude_ids IDs a ignorar (dedupe homepage).
 * @return array{
 *   hero_id: int,
 *   hero_id_2: int,
 *   carousel_ids: int[],
 *   urgency_type: string,
 *   urgency_discount_pct: int,
 *   hero_badge: string,
 *   hero_badge_2: string,
 *   skip_hero_prepend?: bool
 * }
 */
function wcb_super_ofertas_build_context( $on_sale_ids, $exclude_ids = array() ) {
	$empty = array(
		'hero_id'              => 0,
		'hero_id_2'            => 0,
		'carousel_ids'         => array(),
		'urgency_type'         => 'default',
		'urgency_discount_pct' => 0,
		'hero_badge'           => '',
		'hero_badge_2'         => '',
		'skip_hero_prepend'    => false,
	);

	if ( ! function_exists( 'wc_get_products' ) ) {
		return $empty;
	}

	$cfg         = wcb_super_ofertas_get_wc_settings();
	$cap         = wcb_super_ofertas_effective_carousel_cap();
	$exclude_map = array_fill_keys( array_map( 'intval', (array) $exclude_ids ), true );
	$manual_ids  = $cfg['manual_ids_parsed'];
	$mode        = $cfg['assembly_mode'];

	if ( 'manual_only' === $mode ) {
		$ctx = wcb_super_ofertas_build_manual_only_context( $manual_ids, $exclude_map, $cap );
		return null !== $ctx ? $ctx : $empty;
	}

	if ( 'hybrid' === $mode && ! empty( $manual_ids ) ) {
		$hybrid_cap = max( $cap, 500 );
		$pipe       = wcb_super_ofertas_run_scoring_pipeline( $on_sale_ids, $exclude_map, $hybrid_cap );
		$m_rows     = wcb_super_ofertas_rows_from_ordered_ids( $manual_ids, $exclude_map );
		$manual_valid = array();
		foreach ( $manual_ids as $mid ) {
			$mid = (int) $mid;
			if ( $mid > 0 && isset( $m_rows[ $mid ] ) ) {
				$manual_valid[] = $mid;
			}
		}

		if ( null === $pipe && empty( $manual_valid ) ) {
			return $empty;
		}
		if ( null === $pipe ) {
			$ctx = wcb_super_ofertas_build_manual_only_context( $manual_ids, $exclude_map, $cap );
			return null !== $ctx ? $ctx : $empty;
		}
		if ( empty( $manual_valid ) ) {
			return wcb_super_ofertas_build_context_automatic_return( $pipe, $cap );
		}

		$auto_order = isset( $pipe['auto_order_full'] ) ? $pipe['auto_order_full'] : array();
		if ( 'manual_first' === $cfg['hybrid_priority'] ) {
			$merged = wcb_super_ofertas_merge_priority_lists( $manual_valid, $auto_order );
		} else {
			$merged = wcb_super_ofertas_merge_priority_lists( $auto_order, $manual_valid );
		}
		$final    = array_slice( $merged, 0, max( 1, (int) $cap ) );
		$all_rows = $pipe['rows'];
		foreach ( $m_rows as $pid => $r ) {
			$all_rows[ $pid ] = $r;
		}
		return wcb_super_ofertas_package_flat_context( $final, $all_rows );
	}

	if ( empty( $on_sale_ids ) ) {
		return $empty;
	}

	$pipe = wcb_super_ofertas_run_scoring_pipeline( $on_sale_ids, $exclude_map, $cap );
	if ( null === $pipe ) {
		return $empty;
	}

	return wcb_super_ofertas_build_context_automatic_return( $pipe, $cap );
}

/**
 * Chave de transient (lista em promoção + exclusões + opções WC).
 *
 * @param int[] $on_sale_ids
 * @param int[] $exclude_ids
 */
function wcb_super_ofertas_cache_key( $on_sale_ids, $exclude_ids ) {
	$s = array_values( array_unique( array_map( 'intval', (array) $on_sale_ids ) ) );
	sort( $s, SORT_NUMERIC );
	$e = array_values( array_unique( array_map( 'intval', (array) $exclude_ids ) ) );
	sort( $e, SORT_NUMERIC );
	$cfg = function_exists( 'wcb_super_ofertas_settings_cache_signature' ) ? wcb_super_ofertas_settings_cache_signature() : '';

	return 'wcb_super_ofertas_v2_' . md5( wp_json_encode( array( 's' => $s, 'e' => $e, 'cfg' => $cfg ) ) );
}

/**
 * Carrega ou constrói o contexto Super Ofertas da home (transient + lista final do carrossel com hero prepend e cap).
 * Reutilizado no início do front-page (prioridade global entre vitrines) e na secção Super Ofertas.
 *
 * @param int[] $on_sale_ids IDs de wc_get_product_ids_on_sale().
 * @return array{
 *   ctx: array,
 *   carousel_ids: int[],
 *   cache_key: string
 * }
 */
function wcb_super_ofertas_load_home_context_state( $on_sale_ids ) {
	$on_sale_ids = is_array( $on_sale_ids ) ? $on_sale_ids : array();

	$default_ctx = array(
		'hero_id'              => 0,
		'hero_id_2'            => 0,
		'carousel_ids'         => array(),
		'urgency_type'         => 'default',
		'urgency_discount_pct' => 0,
		'hero_badge'           => '',
		'hero_badge_2'         => '',
		'skip_hero_prepend'    => false,
	);

	$empty_ret = array(
		'ctx'               => $default_ctx,
		'carousel_ids'      => array(),
		'cache_key'         => '',
		'hero_prepend_ids'  => array(),
		'skip_hero_prepend' => false,
	);

	if ( ! function_exists( 'wcb_super_ofertas_cache_key' ) ) {
		return $empty_ret;
	}

	$so_cache_key = wcb_super_ofertas_cache_key( $on_sale_ids, array() );

	$so_ctx = false;
	if ( $so_cache_key !== '' ) {
		$cached = get_transient( $so_cache_key );
		$so_ctx  = is_array( $cached ) && isset( $cached['carousel_ids'] ) ? $cached : false;
	}

	$so_needs_build = function_exists( 'wcb_super_ofertas_should_build_context' )
		? wcb_super_ofertas_should_build_context( $on_sale_ids )
		: ! empty( $on_sale_ids );

	if ( false === $so_ctx && $so_needs_build && function_exists( 'wcb_super_ofertas_build_context' ) ) {
		$so_ctx = wcb_super_ofertas_build_context( $on_sale_ids, array() );
		if ( $so_cache_key !== '' && is_array( $so_ctx ) ) {
			set_transient( $so_cache_key, $so_ctx, 8 * HOUR_IN_SECONDS );
		}
	}

	if ( ! is_array( $so_ctx ) ) {
		$so_ctx = $default_ctx;
	}

	$ctx_hero_1   = (int) ( $so_ctx['hero_id'] ?? 0 );
	$ctx_hero_2   = (int) ( $so_ctx['hero_id_2'] ?? 0 );
	$carousel_ids = isset( $so_ctx['carousel_ids'] ) && is_array( $so_ctx['carousel_ids'] )
		? array_values( array_filter( array_map( 'intval', $so_ctx['carousel_ids'] ) ) )
		: array();

	$so_cap = function_exists( 'wcb_super_ofertas_effective_carousel_cap' )
		? wcb_super_ofertas_effective_carousel_cap()
		: ( defined( 'WCB_SO_MAX_CAROUSEL' ) ? (int) WCB_SO_MAX_CAROUSEL : 24 );

	$skip_hero_prepend = ! empty( $so_ctx['skip_hero_prepend'] );

	$so_hero_prepend = array();
	if ( ! $skip_hero_prepend ) {
		foreach ( array( $ctx_hero_1, $ctx_hero_2 ) as $hid ) {
			if ( $hid < 1 || in_array( $hid, $so_hero_prepend, true ) ) {
				continue;
			}
			$hp = function_exists( 'wc_get_product' ) ? wc_get_product( $hid ) : null;
			if ( $hp && $hp->is_in_stock() ) {
				$so_hero_prepend[] = $hid;
			}
		}
		if ( ! empty( $so_hero_prepend ) ) {
			$carousel_ids = array_values( array_unique( array_merge( $so_hero_prepend, $carousel_ids ) ) );
			if ( count( $carousel_ids ) > $so_cap ) {
				$carousel_ids = array_slice( $carousel_ids, 0, $so_cap );
			}
		}
	} elseif ( count( $carousel_ids ) > $so_cap ) {
		$carousel_ids = array_slice( $carousel_ids, 0, $so_cap );
	}

	return array(
		'ctx'                => $so_ctx,
		'carousel_ids'       => $carousel_ids,
		'cache_key'          => $so_cache_key,
		'hero_prepend_ids'   => $so_hero_prepend,
		'skip_hero_prepend'  => $skip_hero_prepend,
	);
}
