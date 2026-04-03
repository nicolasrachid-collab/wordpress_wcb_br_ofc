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
 *   hero_badge_2: string
 * }
 */
function wcb_super_ofertas_build_context( $on_sale_ids, $exclude_ids = array() ) {
	$empty = array(
		'hero_id'                => 0,
		'hero_id_2'              => 0,
		'carousel_ids'           => array(),
		'urgency_type'           => 'default',
		'urgency_discount_pct'   => 0,
		'hero_badge'             => '',
		'hero_badge_2'           => '',
	);

	if ( ! function_exists( 'wc_get_products' ) || empty( $on_sale_ids ) ) {
		return $empty;
	}

	$exclude_map = array_fill_keys( array_map( 'intval', (array) $exclude_ids ), true );

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
		return $empty;
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
		return $empty;
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
		$p = $by_id[ $pid ];
		if ( ! $p->is_on_sale() ) {
			continue;
		}
		$pm       = wcb_super_ofertas_price_metrics( $p );
		$current  = $pm['current'];
		$disc     = $pm['discount_pct'];
		if ( $disc <= 0 && $pm['sale'] <= 0 ) {
			continue;
		}

		$total_sales = (int) $p->get_total_sales();
		$stock_comp  = wcb_super_ofertas_stock_score_component( $p );
		$score       = ( log( 1 + max( 0, $total_sales ) ) * 3 )
			+ ( $current * 0.002 )
			+ ( $disc * 2 )
			+ ( $stock_comp * 1.5 );

		$rows[ $pid ] = array(
			'product'       => $p,
			'score'         => $score,
			'total_sales'   => $total_sales,
			'discount_pct'  => $disc,
			'current'       => $current,
			'stock_comp'    => $stock_comp,
		);
	}

	if ( empty( $rows ) ) {
		return $empty;
	}

	$prices = array();
	foreach ( $rows as $r ) {
		$prices[] = $r['current'];
	}
	sort( $prices, SORT_NUMERIC );
	$median_price = 0.0;
	$n_p          = count( $prices );
	if ( $n_p > 0 ) {
		$mid = (int) floor( $n_p / 2 );
		$median_price = ( $n_p % 2 === 1 ) ? (float) $prices[ $mid ] : (float) ( $prices[ $mid - 1 ] + $prices[ $mid ] ) / 2;
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

	// Hero 1: maior score entre elegíveis (stock hero); fallback = maior preço * desconto.
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
		return $empty;
	}

	$hero_dom = function_exists( 'wcb_carousel_dominant_category_id' )
		? wcb_carousel_dominant_category_id( array( $hero_id ) )
		: 0;

	// Hero 2: melhor score fora da categoria dominante do hero (complementar).
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
		$boost = ( $hero_dom > 0 && has_term( $hero_dom, 'product_cat', $pid ) ) ? WCB_SO_UPSELL_BOOST : 0.0;
		$carousel_candidates[ $pid ] = $rows[ $pid ]['score'] + $boost;
	}

	arsort( $carousel_candidates, SORT_NUMERIC );
	$carousel_ids = array_keys( $carousel_candidates );
	$carousel_ids = array_slice( $carousel_ids, 0, WCB_SO_MAX_CAROUSEL );

	// Urgência agregada.
	$max_disc = 0.0;
	$max_sale = 0;
	$min_mq   = null;
	foreach ( $rows as $pid => $r ) {
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

	$r1            = $rows[ $hero_id ];
	$hero_badge    = wcb_super_ofertas_hero_badge_for_metrics(
		array(
			'total_sales'       => $r1['total_sales'],
			'discount_pct'      => $r1['discount_pct'],
			'current'           => $r1['current'],
			'pool_price_median' => $median_price,
		)
	);
	$hero_badge_2 = '';
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
		'hero_id'                => (int) $hero_id,
		'hero_id_2'              => (int) $hero_id_2,
		'carousel_ids'           => $carousel_ids,
		'urgency_type'           => $urgency_type,
		'urgency_discount_pct'   => max( 0, $urgency_discount_pct ),
		'hero_badge'             => $hero_badge,
		'hero_badge_2'           => $hero_badge_2,
	);
}

/**
 * Chave de transient (lista em promoção + exclusões homepage).
 *
 * @param int[] $on_sale_ids
 * @param int[] $exclude_ids
 */
function wcb_super_ofertas_cache_key( $on_sale_ids, $exclude_ids ) {
	$s = array_values( array_unique( array_map( 'intval', (array) $on_sale_ids ) ) );
	sort( $s, SORT_NUMERIC );
	$e = array_values( array_unique( array_map( 'intval', (array) $exclude_ids ) ) );
	sort( $e, SORT_NUMERIC );

	return 'wcb_super_ofertas_v1_' . md5( wp_json_encode( array( 's' => $s, 'e' => $e ) ) );
}
