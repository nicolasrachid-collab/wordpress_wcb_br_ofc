<?php
/**
 * Home carousels: fill empty slots with WooCommerce products (Customizer-driven fallback + dedupe scope).
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return string slide|carousel|homepage
 */
function wcb_carousel_get_dedupe_scope() {
	$scope = get_theme_mod( 'wcb_carousel_dedupe_scope', 'carousel' );
	$allowed = array( 'slide', 'carousel', 'homepage' );
	return in_array( $scope, $allowed, true ) ? $scope : 'carousel';
}

/**
 * @param string $section novidades|vendidos|estoque|ofertas
 * @return string shop|category|mixed
 */
function wcb_carousel_get_fallback_mode( $section ) {
	$key     = 'wcb_carousel_fb_' . $section . '_mode';
	$mode    = get_theme_mod( $key, 'shop' );
	$allowed = array( 'shop', 'category', 'mixed' );
	return in_array( $mode, $allowed, true ) ? $mode : 'shop';
}

/**
 * product_cat term_id (0 = none)
 *
 * @param string $section novidades|vendidos|estoque|ofertas
 */
function wcb_carousel_get_fallback_cat_id( $section ) {
	$key = 'wcb_carousel_fb_' . $section . '_cat';
	return max( 0, (int) get_theme_mod( $key, 0 ) );
}

/**
 * Normalize cached carousel payload (ids + html) or false if legacy / invalid.
 *
 * @param mixed $cached Transient value.
 * @return array{ids:int[],html:string[]}|false
 */
function wcb_carousel_normalize_cache( $cached ) {
	if ( ! is_array( $cached ) || ! isset( $cached['ids'], $cached['html'] ) ) {
		return false;
	}
	if ( ! is_array( $cached['ids'] ) || ! is_array( $cached['html'] ) ) {
		return false;
	}
	$n = count( $cached['ids'] );
	if ( $n !== count( $cached['html'] ) ) {
		return false;
	}
	return array(
		'ids'  => array_map( 'intval', $cached['ids'] ),
		'html' => $cached['html'],
	);
}

/**
 * Build id+html rows from a WP_Query loop (caller must reset postdata).
 *
 * @param WP_Query $query Query com posts.
 * @param array<string, string>|null $wcb_track Opcional: data-wcb-track e data-role no card (ex. Super Ofertas).
 * @return array<int, array{id:int, html:string}>
 */
function wcb_carousel_pairs_from_query( WP_Query $query, $wcb_track = null ) {
	$restore = null;
	if ( is_array( $wcb_track ) && isset( $wcb_track['wcb_track'], $wcb_track['role'] ) ) {
		$restore = array_key_exists( 'wcb_product_card_track', $GLOBALS ) ? $GLOBALS['wcb_product_card_track'] : false;
		$GLOBALS['wcb_product_card_track'] = $wcb_track;
	}

	$out = array();
	if ( ! $query->have_posts() ) {
		if ( null !== $restore ) {
			if ( false === $restore ) {
				unset( $GLOBALS['wcb_product_card_track'] );
			} else {
				$GLOBALS['wcb_product_card_track'] = $restore;
			}
		}
		return $out;
	}
	while ( $query->have_posts() ) {
		$query->the_post();
		if ( function_exists( 'wc_setup_product_data' ) ) {
			wc_setup_product_data( get_post() );
		}
		$pid = get_the_ID();
		ob_start();
		get_template_part( 'template-parts/product', 'card' );
		$html = ob_get_clean();
		if ( $html !== '' ) {
			$out[] = array(
				'id'   => (int) $pid,
				'html' => $html,
			);
		}
	}
	wp_reset_postdata();

	if ( null !== $restore ) {
		if ( false === $restore ) {
			unset( $GLOBALS['wcb_product_card_track'] );
		} else {
			$GLOBALS['wcb_product_card_track'] = $restore;
		}
	}

	return $out;
}

/**
 * Dominant product_cat among products (by assignment count). Excludes default "Uncategorized" if possible.
 *
 * @param int[] $product_ids
 */
function wcb_carousel_dominant_category_id( array $product_ids ) {
	$product_ids = array_values( array_unique( array_filter( array_map( 'intval', $product_ids ) ) ) );
	if ( empty( $product_ids ) ) {
		return 0;
	}
	$default_cat = (int) get_option( 'default_product_cat' );
	$counts      = array();
	foreach ( $product_ids as $pid ) {
		$terms = get_the_terms( $pid, 'product_cat' );
		if ( ! $terms || is_wp_error( $terms ) ) {
			continue;
		}
		foreach ( $terms as $t ) {
			if ( (int) $t->term_id === $default_cat ) {
				continue;
			}
			$tid = (int) $t->term_id;
			if ( ! isset( $counts[ $tid ] ) ) {
				$counts[ $tid ] = 0;
			}
			$counts[ $tid ]++;
		}
	}
	if ( empty( $counts ) ) {
		return 0;
	}
	arsort( $counts, SORT_NUMERIC );
	return (int) array_key_first( $counts );
}

/**
 * @return int[]
 */
function wcb_carousel_pairs_collect_ids( array $pairs ) {
	$ids = array();
	foreach ( $pairs as $row ) {
		if ( ! empty( $row['id'] ) ) {
			$ids[] = (int) $row['id'];
		}
	}
	return $ids;
}

/**
 * Stock instock meta_query fragment (AND-ready).
 */
function wcb_carousel_meta_instock() {
	return array(
		'key'     => '_stock_status',
		'value'   => 'instock',
		'compare' => '=',
	);
}

/**
 * Fetch product IDs to fill slots (section-specific ordering + optional sale).
 *
 * @param int   $needed Max IDs to return.
 * @param int[] $exclude_ids
 * @param string $section novidades|vendidos|estoque|ofertas
 * @param string $mode shop|category|mixed
 * @param int   $fallback_cat_id product_cat term_id
 * @param int[] $mixed_source_ids Product IDs for mixed mode inference
 * @param int[]|null $ofertas_sale_pool When section=ofertas, restrict to these IDs (on sale)
 * @return int[]
 */
function wcb_carousel_query_backfill_ids( $needed, array $exclude_ids, $section, $mode, $fallback_cat_id, array $mixed_source_ids, $ofertas_sale_pool = null ) {
	if ( $needed < 1 || ! class_exists( 'WooCommerce' ) ) {
		return array();
	}

	$exclude_ids = array_values( array_unique( array_filter( array_map( 'intval', $exclude_ids ) ) ) );

	$term_for_cat = 0;
	if ( $mode === 'category' && $fallback_cat_id > 0 ) {
		$term_for_cat = $fallback_cat_id;
	} elseif ( $mode === 'mixed' ) {
		$term_for_cat = wcb_carousel_dominant_category_id( $mixed_source_ids );
	}

	$base_meta = array( wcb_carousel_meta_instock() );

	$run_query = function ( $extra_tax, $limit ) use ( $exclude_ids, $section, $base_meta, $ofertas_sale_pool ) {
		$limit = min( 50, max( 1, (int) $limit ) );
		$args  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'posts_per_page'      => $limit,
			'post__not_in'        => $exclude_ids,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'meta_query'          => $base_meta,
		);

		if ( $section === 'novidades' ) {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		} elseif ( $section === 'vendidos' ) {
			$args['meta_key'] = 'total_sales';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
		} elseif ( $section === 'estoque' ) {
			$args['meta_key'] = '_wc_average_rating';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
		} else {
			$args['orderby'] = 'rand';
		}

		if ( $section === 'ofertas' && is_array( $ofertas_sale_pool ) && ! empty( $ofertas_sale_pool ) ) {
			$pool                    = array_values( array_unique( array_map( 'intval', $ofertas_sale_pool ) ) );
			$args['post__in']        = array_values( array_diff( $pool, $exclude_ids ) );
			$args['post__not_in']    = array();
			$args['orderby']         = 'post__in';
			$args['posts_per_page']  = min( $limit, count( $args['post__in'] ) );
			if ( empty( $args['post__in'] ) ) {
				return array();
			}
		}

		if ( ! empty( $extra_tax ) ) {
			$args['tax_query'] = array( $extra_tax );
		}

		$q = new WP_Query( $args );
		return is_array( $q->posts ) ? array_map( 'intval', $q->posts ) : array();
	};

	$found = array();

	if ( $term_for_cat > 0 ) {
		$tax   = array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => array( $term_for_cat ),
		);
		$found = $run_query( $tax, $needed );
	}

	/* Completar com loja (ou pool de ofertas) se ainda faltar — inclusive modo "categoria" com poucos resultados */
	if ( count( $found ) < $needed ) {
		$still = $needed - count( $found );
		$ex2   = array_unique( array_merge( $exclude_ids, $found ) );
		$args2 = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'posts_per_page'      => min( 50, $still ),
			'post__not_in'        => $ex2,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'meta_query'          => $base_meta,
		);
		if ( $section === 'novidades' ) {
			$args2['orderby'] = 'date';
			$args2['order']   = 'DESC';
		} elseif ( $section === 'vendidos' ) {
			$args2['meta_key'] = 'total_sales';
			$args2['orderby']  = 'meta_value_num';
			$args2['order']    = 'DESC';
		} elseif ( $section === 'estoque' ) {
			$args2['meta_key'] = '_wc_average_rating';
			$args2['orderby']  = 'meta_value_num';
			$args2['order']    = 'DESC';
		} else {
			$args2['orderby'] = 'rand';
		}
		if ( $section === 'ofertas' && is_array( $ofertas_sale_pool ) && ! empty( $ofertas_sale_pool ) ) {
			$pool                     = array_values( array_unique( array_map( 'intval', $ofertas_sale_pool ) ) );
			$args2['post__in']        = array_values( array_diff( $pool, $ex2 ) );
			$args2['post__not_in']    = array();
			$args2['orderby']         = 'post__in';
			$args2['posts_per_page']  = min( 50, max( 0, count( $args2['post__in'] ) ) );
			if ( empty( $args2['post__in'] ) ) {
				$more = array();
			} else {
				$q2   = new WP_Query( $args2 );
				$more = is_array( $q2->posts ) ? array_map( 'intval', $q2->posts ) : array();
			}
		} else {
			$q2   = new WP_Query( $args2 );
			$more = is_array( $q2->posts ) ? array_map( 'intval', $q2->posts ) : array();
		}
		$found = array_merge( $found, $more );
	}

	return array_slice( array_values( array_unique( array_map( 'intval', $found ) ) ), 0, $needed );
}

/**
 * Render product card HTML for a single ID (no global carousel state).
 */
function wcb_carousel_render_card_html( $product_id ) {
	$product_id = (int) $product_id;
	if ( $product_id < 1 || ! class_exists( 'WooCommerce' ) ) {
		return '';
	}
	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_visible() ) {
		return '';
	}
	global $post;
	$post = get_post( $product_id );
	if ( ! $post ) {
		return '';
	}
	setup_postdata( $post );
	$GLOBALS['product'] = $product;
	if ( function_exists( 'wc_setup_product_data' ) ) {
		wc_setup_product_data( $post );
	}
	ob_start();
	get_template_part( 'template-parts/product', 'card' );
	$html = ob_get_clean();
	wp_reset_postdata();
	unset( $GLOBALS['product'] );
	return is_string( $html ) ? $html : '';
}

/**
 * Pad one chunk of [id,html] rows to $slot_size.
 *
 * @param array<int,array{id:int,html:string}> $chunk
 * @param int                                  $slot_size
 * @param int[]                                $carousel_all_base_ids All curated IDs in this carousel (all slides)
 * @param int[]                                $carousel_acc_ids      Accumulator; updated with every ID placed in this carousel
 * @param int[]                                $homepage_used         By ref; updated when scope homepage
 * @param string                               $dedupe_scope slide|carousel|homepage
 * @param string                               $section
 * @param string                               $mode
 * @param int                                  $fallback_cat_id
 * @param int[]                                $mixed_source_ids
 * @param int[]|null                           $ofertas_sale_pool
 * @return array<int,array{id:int,html:string}>
 */
function wcb_carousel_pad_chunk( array $chunk, $slot_size, array $carousel_all_base_ids, array &$carousel_acc_ids, array &$homepage_used, $dedupe_scope, $section, $mode, $fallback_cat_id, array $mixed_source_ids, $ofertas_sale_pool = null ) {
	$slot_size = max( 1, (int) $slot_size );
	$chunk     = array_values( $chunk );
	$have      = count( $chunk );
	if ( $have >= $slot_size ) {
		$out = array_slice( $chunk, 0, $slot_size );
	} else {
		$need = $slot_size - $have;
		$out  = $chunk;

		$ids_on_slide = wcb_carousel_pairs_collect_ids( $out );

		while ( count( $out ) < $slot_size && $need > 0 ) {
			$exclude = $ids_on_slide;

			if ( $dedupe_scope === 'carousel' || $dedupe_scope === 'homepage' ) {
				$exclude = array_values( array_unique( array_merge( $exclude, $carousel_acc_ids ) ) );
			}
			if ( $dedupe_scope === 'homepage' ) {
				$exclude = array_values( array_unique( array_merge( $exclude, $homepage_used ) ) );
			}

			$fetch   = wcb_carousel_query_backfill_ids( $need, $exclude, $section, $mode, $fallback_cat_id, $mixed_source_ids, $ofertas_sale_pool );
			$added   = 0;
			foreach ( $fetch as $pid ) {
				if ( count( $out ) >= $slot_size ) {
					break;
				}
				$html = wcb_carousel_render_card_html( $pid );
				if ( $html === '' ) {
					continue;
				}
				$out[]            = array(
					'id'   => $pid,
					'html' => $html,
				);
				$ids_on_slide[]   = $pid;
				$carousel_acc_ids[] = $pid;
				if ( $dedupe_scope === 'homepage' && ! in_array( $pid, $homepage_used, true ) ) {
					$homepage_used[] = $pid;
				}
				$added++;
			}
			if ( $added === 0 ) {
				break;
			}
			$need = $slot_size - count( $out );
		}
	}

	// Register final IDs on this slide in accumulators (curated rows already counted in carousel_acc at loop setup)
	foreach ( wcb_carousel_pairs_collect_ids( $out ) as $fid ) {
		if ( ! in_array( $fid, $carousel_acc_ids, true ) ) {
			$carousel_acc_ids[] = $fid;
		}
		if ( $dedupe_scope === 'homepage' && ! in_array( $fid, $homepage_used, true ) ) {
			$homepage_used[] = $fid;
		}
	}

	return $out;
}

/**
 * Pad every chunk; initializes carousel_acc from all base IDs in chunks.
 *
 * @param array<int,array<int,array{id:int,html:string}>> $chunks
 * @return array<int,array<int,array{id:int,html:string}>>
 */
function wcb_carousel_pad_all_chunks( array $chunks, $slot_size, $dedupe_scope, $section, $mode, $fallback_cat_id, array &$homepage_used, $ofertas_sale_pool = null ) {
	$flat_ids = array();
	foreach ( $chunks as $ch ) {
		foreach ( $ch as $row ) {
			if ( ! empty( $row['id'] ) ) {
				$flat_ids[] = (int) $row['id'];
			}
		}
	}
	$mixed_source_ids     = $flat_ids;
	$carousel_all_base_ids = array_values( array_unique( $flat_ids ) );
	$carousel_acc_ids      = $carousel_all_base_ids;

	$out = array();
	foreach ( $chunks as $chunk ) {
		$out[] = wcb_carousel_pad_chunk( $chunk, $slot_size, $carousel_all_base_ids, $carousel_acc_ids, $homepage_used, $dedupe_scope, $section, $mode, $fallback_cat_id, $mixed_source_ids, $ofertas_sale_pool );
	}
	return $out;
}

/**
 * @param array<int,array{id:int,html:string}> $pairs
 * @return array<int,string> HTML only for templates
 */
function wcb_carousel_pairs_to_html_list( array $pairs ) {
	$list = array();
	foreach ( $pairs as $row ) {
		if ( ! empty( $row['html'] ) ) {
			$list[] = $row['html'];
		}
	}
	return $list;
}
