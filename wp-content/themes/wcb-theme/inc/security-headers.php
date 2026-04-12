<?php
/**
 * Cabeçalhos HTTP de segurança (sem CSP — evita quebrar WooCommerce e terceiros).
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Envia headers recomendados uma vez por pedido.
 *
 * @return void
 */
function wcb_send_security_headers() {
	static $sent = false;
	if ( $sent || headers_sent() ) {
		return;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-XSS-Protection: 1; mode=block' );
	header( 'Referrer-Policy: no-referrer-when-downgrade' );

	$sent = true;
}

add_action( 'init', 'wcb_send_security_headers', 0 );
