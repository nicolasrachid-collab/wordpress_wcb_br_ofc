<?php
/**
 * CLI: ativa WCB Child; preenche ou redistribui YITH FBT (1 associado por produto).
 *
 *   php bin/wcb-activate-child-and-fbt.php
 *   php bin/wcb-activate-child-and-fbt.php --redistribute  (atualiza todos, rodízio entre publicados)
 */
if ( php_sapi_name() !== 'cli' ) {
	exit;
}

$redistribute = in_array( '--redistribute', $argv, true );

define( 'WP_USE_THEMES', false );
require dirname( __DIR__ ) . '/wp-load.php';

if ( ! function_exists( 'wc_get_products' ) ) {
	fwrite( STDERR, "WooCommerce não carregado.\n" );
	exit( 1 );
}

$template   = get_option( 'template' );
$stylesheet = get_option( 'stylesheet' );

if ( 'wcb-theme' !== $template ) {
	update_option( 'template', 'wcb-theme' );
}
update_option( 'stylesheet', 'wcb-theme-child' );

echo "Tema: template=wcb-theme, stylesheet=wcb-theme-child (ativado).\n";

$ids = wc_get_products(
	array(
		'status' => 'publish',
		'limit'  => -1,
		'return' => 'ids',
	)
);

sort( $ids, SORT_NUMERIC );

/**
 * Verifica se o produto pode entrar no FBT (principal ou associado).
 */
$eligible = static function ( $product ) {
	if ( ! $product ) {
		return false;
	}
	if ( in_array( $product->get_type(), array( 'grouped', 'external' ), true ) ) {
		return false;
	}
	return $product->is_purchasable() && $product->is_in_stock();
};

$eligible_ids = array();
foreach ( $ids as $pid ) {
	$p = wc_get_product( $pid );
	if ( $eligible( $p ) ) {
		$eligible_ids[] = (int) $pid;
	}
}
$eligible_ids = array_values( array_unique( $eligible_ids ) );
$n_elig       = count( $eligible_ids );

$filled  = 0;
$skipped = 0;

foreach ( $eligible_ids as $pos => $pid ) {
	$product = wc_get_product( $pid );
	if ( ! $product ) {
		continue;
	}

	$group = $product->get_meta( YITH_WFBT_META, true );
	if ( ! empty( $group ) && is_array( $group ) ) {
		$group = array_filter( array_map( 'absint', $group ) );
	}
	if ( ! empty( $group ) && ! $redistribute ) {
		$skipped++;
		continue;
	}

	$buddy = null;
	if ( $n_elig > 1 ) {
		for ( $step = 1; $step < $n_elig; $step++ ) {
			$cand = $eligible_ids[ ( $pos + $step ) % $n_elig ];
			if ( $cand !== $pid ) {
				$buddy = $cand;
				break;
			}
		}
	}

	if ( ! $buddy ) {
		echo "Aviso: sem par para o produto ID {$pid}.\n";
		continue;
	}

	$product->update_meta_data( YITH_WFBT_META, array( $buddy ) );
	$product->save();
	$filled++;
	echo "FBT: produto {$pid} → associado {$buddy}\n";
}

echo "Resumo: {$filled} produtos atualizados, {$skipped} ignorados (já com grupo; use --redistribute), elegíveis: {$n_elig}.\n";
