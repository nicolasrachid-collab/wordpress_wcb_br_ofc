<?php
/**
 * Debug session 8eb372 — grava NDJSON em ABSPATH/debug-8eb372.log (nonce).
 *
 * @package WCB_Theme
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_nopriv_wcb_debug_trust', 'wcb_debug_trust_ndjson_log' );
add_action( 'wp_ajax_wcb_debug_trust', 'wcb_debug_trust_ndjson_log' );

/**
 * Recebe uma linha NDJSON (POST line) e anexa ao ficheiro de debug.
 */
function wcb_debug_trust_ndjson_log() {
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wcb_dbg_8eb372' ) ) {
		wp_send_json_error( array( 'e' => 'nonce' ), 403 );
	}

	$line = isset( $_POST['line'] ) ? wp_unslash( $_POST['line'] ) : '';
	if ( strlen( $line ) > 24000 ) {
		wp_send_json_error( array( 'e' => 'size' ), 400 );
	}

	$path = trailingslashit( ABSPATH ) . 'debug-8eb372.log';
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
	$ok = ( false !== file_put_contents( $path, $line . "\n", FILE_APPEND | LOCK_EX ) );

	wp_send_json_success( array( 'written' => $ok ) );
}
