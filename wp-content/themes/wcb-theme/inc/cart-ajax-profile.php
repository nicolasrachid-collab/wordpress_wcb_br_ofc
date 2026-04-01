<?php
/**
 * Perfil de performance do AJAX de atualização de quantidade do Side Cart (Xoo).
 *
 * Ativar em wp-config.php (antes de "That's all, stop editing"):
 *   define( 'WCB_PROFILE_CART_AJAX', true );
 *
 * Os tempos vão para debug.log (WP_DEBUG_LOG) ou para o log de erros do PHP.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return bool
 */
function wcb_cart_ajax_profile_active() {
	return ( defined( 'WCB_PROFILE_CART_AJAX' ) && WCB_PROFILE_CART_AJAX );
}

if ( ! wcb_cart_ajax_profile_active() ) {
	return;
}

/**
 * @return bool
 */
function wcb_cart_ajax_profile_is_xoo_qty_request() {
	return wp_doing_ajax()
		&& isset( $_REQUEST['wc-ajax'] )
		&& 'xoo_wsc_update_item_quantity' === $_REQUEST['wc-ajax'];
}

add_action(
	'wc_ajax_xoo_wsc_update_item_quantity',
	static function () {
		if ( ! wcb_cart_ajax_profile_is_xoo_qty_request() ) {
			return;
		}
		$GLOBALS['wcb_cart_ajax_prof'] = array(
			't_start'    => microtime( true ),
			'calc_count' => 0,
		);
	},
	0
);

add_action(
	'woocommerce_before_calculate_totals',
	static function () {
		if ( empty( $GLOBALS['wcb_cart_ajax_prof'] ) || ! is_array( $GLOBALS['wcb_cart_ajax_prof'] ) ) {
			return;
		}
		$GLOBALS['wcb_cart_ajax_prof']['calc_count']++;
	},
	0
);

add_action(
	'woocommerce_before_mini_cart',
	static function () {
		if ( empty( $GLOBALS['wcb_cart_ajax_prof'] ) ) {
			return;
		}
		$GLOBALS['wcb_cart_ajax_prof']['t_before_mini'] = microtime( true );
	},
	0
);

add_action(
	'woocommerce_after_mini_cart',
	static function () {
		if ( empty( $GLOBALS['wcb_cart_ajax_prof'] ) || empty( $GLOBALS['wcb_cart_ajax_prof']['t_before_mini'] ) ) {
			return;
		}
		$GLOBALS['wcb_cart_ajax_prof']['mini_cart_ms'] = ( microtime( true ) - $GLOBALS['wcb_cart_ajax_prof']['t_before_mini'] ) * 1000;
	},
	999
);

add_filter(
	'woocommerce_add_to_cart_fragments',
	static function ( $fragments ) {
		if ( empty( $GLOBALS['wcb_cart_ajax_prof'] ) ) {
			return $fragments;
		}
		$GLOBALS['wcb_cart_ajax_prof']['t_frag_in'] = microtime( true );
		return $fragments;
	},
	1
);

add_filter(
	'woocommerce_add_to_cart_fragments',
	static function ( $fragments ) {
		if ( empty( $GLOBALS['wcb_cart_ajax_prof'] ) || empty( $GLOBALS['wcb_cart_ajax_prof']['t_frag_in'] ) ) {
			return $fragments;
		}
		$p     = &$GLOBALS['wcb_cart_ajax_prof'];
		$p['fragments_pipeline_ms'] = ( microtime( true ) - $p['t_frag_in'] ) * 1000;
		$bytes = 0;
		foreach ( (array) $fragments as $html ) {
			$bytes += is_string( $html ) ? strlen( $html ) : 0;
		}
		$p['fragments_html_kb'] = round( $bytes / 1024, 1 );
		$p['fragment_keys']    = array_keys( (array) $fragments );

		$total_ms = ( microtime( true ) - $p['t_start'] ) * 1000;
		$mini     = isset( $p['mini_cart_ms'] ) ? sprintf( '%.1f', $p['mini_cart_ms'] ) : 'n/a';
		$pipe     = sprintf( '%.1f', $p['fragments_pipeline_ms'] );
		$calcs    = (int) ( $p['calc_count'] ?? 0 );
		$keys_n   = count( $p['fragment_keys'] );

		error_log(
			sprintf(
				'[WCB cart profile] xoo_wsc_update_item_quantity | total_php=%.1fms | mini_cart=%sms | fragments_filters=%sms | calculate_totals_passes=%d | fragments=%d | html~%sKB',
				$total_ms,
				$mini,
				$pipe,
				$calcs,
				$keys_n,
				$p['fragments_html_kb']
			)
		);
		error_log( '[WCB cart profile] fragment selectors: ' . implode( ', ', $p['fragment_keys'] ) );

		return $fragments;
	},
	99999
);
