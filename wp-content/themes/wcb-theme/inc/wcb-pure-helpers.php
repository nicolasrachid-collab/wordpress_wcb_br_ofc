<?php
/**
 * Funções puras (sem side-effects) — testáveis com PHPUnit sem WordPress.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extrai apenas dígitos de um CEP (entrada bruta).
 *
 * @param string $raw Texto com ou sem máscara.
 * @return string Apenas 0-9.
 */
function wcb_normalize_cep_digits( $raw ) {
	return preg_replace( '/\D/', '', (string) $raw );
}

/**
 * Se true, a listagem "promoções" deve usar post__in com IDs on sale; se false, meta_query alternativa.
 *
 * @param array<int|string> $on_sale_ids IDs retornados por wc_get_product_ids_on_sale().
 * @param int               $max         Limite máximo (ex. filtro wcb_promocoes_post_in_max).
 * @return bool
 */
function wcb_promocoes_should_use_post_in( array $on_sale_ids, $max ) {
	$max = (int) $max;
	if ( $max < 0 ) {
		$max = 0;
	}
	return count( $on_sale_ids ) <= $max;
}
