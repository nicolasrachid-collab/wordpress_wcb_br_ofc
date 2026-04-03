<?php
/**
 * Helpers — agrupamento de ofertas FBT (variações → card único).
 *
 * @package WCB_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Agrupa ofertas (índice ≥ 1): variações do mesmo pai num único slot.
 *
 * @param WC_Product[] $products Lista original do YITH.
 * @return array<int, array{type: string, parent_id?: int, variations?: WC_Product_Variation[], product?: WC_Product}>
 */
function wcb_fbt_build_addon_slots( array $products ) {
	$slots       = array();
	$parent_slot = array();

	$count = count( $products );
	for ( $i = 1; $i < $count; $i++ ) {
		$p = $products[ $i ];
		if ( ! $p instanceof WC_Product ) {
			continue;
		}

		if ( $p->is_type( 'variation' ) ) {
			$parent_id = (int) $p->get_parent_id();
			if ( isset( $parent_slot[ $parent_id ] ) ) {
				$idx = $parent_slot[ $parent_id ];
				$slots[ $idx ]['variations'][] = $p;
			} else {
				$parent_slot[ $parent_id ]   = count( $slots );
				$slots[]                       = array(
					'type'       => 'variable',
					'parent_id'  => $parent_id,
					'variations' => array( $p ),
				);
			}
		} else {
			$slots[] = array(
				'type'    => 'simple',
				'product' => $p,
			);
		}
	}

	return $slots;
}

/**
 * Primeira variação em stock; senão a primeira da lista.
 *
 * @param WC_Product_Variation[] $variations Variações agrupadas.
 * @return WC_Product_Variation|null
 */
function wcb_fbt_default_variation( array $variations ) {
	foreach ( $variations as $v ) {
		if ( $v instanceof WC_Product_Variation && $v->is_in_stock() ) {
			return $v;
		}
	}
	$first = $variations[0] ?? null;
	return $first instanceof WC_Product_Variation ? $first : null;
}

/**
 * Rótulo curto para chip (atributos da variação).
 *
 * @param WC_Product_Variation $variation Variação.
 * @return string
 */
function wcb_fbt_variation_chip_label( WC_Product_Variation $variation ) {
	$parts = array();
	foreach ( $variation->get_variation_attributes() as $attr_key => $attr_val ) {
		if ( '' === $attr_val ) {
			continue;
		}
		$tax = str_replace( 'attribute_', '', $attr_key );
		if ( taxonomy_exists( $tax ) ) {
			$term = get_term_by( 'slug', $attr_val, $tax );
			if ( $term && ! is_wp_error( $term ) ) {
				$parts[] = $term->name;
				continue;
			}
		}
		$parts[] = $attr_val;
	}
	$label = implode( ' · ', $parts );
	return $label !== '' ? $label : (string) $variation->get_id();
}

/**
 * Payload JSON para data-wcb-variations (JS).
 *
 * @param WC_Product_Variation[] $variations Lista de variações.
 * @return array<int, array{variation_id: int, price: float, price_html: string, label: string}>
 */
function wcb_fbt_variations_json_payload( array $variations ) {
	$out = array();
	foreach ( $variations as $v ) {
		if ( ! $v instanceof WC_Product_Variation ) {
			continue;
		}
		$out[] = array(
			'variation_id' => (int) $v->get_id(),
			'price'        => (float) wc_get_price_to_display( $v ),
			'price_html'   => wp_kses_post( $v->get_price_html() ),
			'label'        => wcb_fbt_variation_chip_label( $v ),
		);
	}
	return $out;
}
