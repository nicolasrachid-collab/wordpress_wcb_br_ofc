<?php
/**
 * Newsletter rodapé (wcb-nl4) — registo de e-mails via AJAX.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rate limit newsletter por IP (janela 15 min).
 *
 * @return bool True se excedeu o limite.
 */
function wcb_nl4_rate_limit_exceeded() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( '' === $ip ) {
		return false;
	}
	$key = 'wcb_nl4_rl_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( $n >= 30 ) {
		return true;
	}
	set_transient( $key, $n + 1, 15 * MINUTE_IN_SECONDS );
	return false;
}

/**
 * AJAX: guardar e-mail (lista em opção; máx. 8000 entradas).
 */
function wcb_nl4_subscribe_ajax() {
	if ( ! check_ajax_referer( 'wcb_nl4', 'nonce', false ) ) {
		wp_send_json_error(
			array(
				'code'    => 'invalid_request',
				'message' => __( 'Sessão expirada. Atualize a página e tente de novo.', 'wcb-theme' ),
			),
			403
		);
	}

	if ( wcb_nl4_rate_limit_exceeded() ) {
		wp_send_json_error(
			array(
				'code'    => 'rate_limited',
				'message' => __( 'Muitas tentativas. Aguarde alguns minutos e tente de novo.', 'wcb-theme' ),
			),
			429
		);
	}

	$raw   = isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '';
	$email = sanitize_email( $raw );

	if ( ! is_email( $email ) ) {
		wp_send_json_error(
			array(
				'code'    => 'invalid_request',
				'message' => __( 'Digite um e-mail válido.', 'wcb-theme' ),
			),
			400
		);
	}

	$list = get_option( 'wcb_nl4_emails', array() );
	if ( ! is_array( $list ) ) {
		$list = array();
	}

	$email_lower = strtolower( $email );
	$is_new      = true;
	foreach ( $list as $existing ) {
		if ( strtolower( (string) $existing ) === $email_lower ) {
			$is_new = false;
			break;
		}
	}

	if ( $is_new ) {
		$list[] = $email;
		if ( count( $list ) > 8000 ) {
			$list = array_slice( $list, -8000 );
		}
		update_option( 'wcb_nl4_emails', $list, false );

		if ( apply_filters( 'wcb_nl4_send_admin_notification', false ) ) {
			wp_mail(
				get_option( 'admin_email' ),
				sprintf( '[%s] Novo e-mail — newsletter', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
				$email
			);
		}
	}

	do_action( 'wcb_nl4_after_subscribe', $email, $is_new );

	wp_send_json_success(
		array(
			'code'    => 'ok',
			'message' => __( 'Cadastro registrado.', 'wcb-theme' ),
		)
	);
}

add_action( 'wp_ajax_nopriv_wcb_nl4_subscribe', 'wcb_nl4_subscribe_ajax' );
add_action( 'wp_ajax_wcb_nl4_subscribe', 'wcb_nl4_subscribe_ajax' );
