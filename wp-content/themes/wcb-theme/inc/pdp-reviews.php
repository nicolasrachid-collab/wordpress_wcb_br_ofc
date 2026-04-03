<?php
/**
 * PDP — Avaliações enriquecidas (WooCommerce nativo + UX)
 * - Voto "Útil" (comment_meta + transient por IP/comentário)
 * - Toolbar ordenar / filtrar (JS no cliente)
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preenche $wp_query->comments para have_comments() / wp_list_comments() na PDP customizada.
 * O núcleo só faz esta query dentro de comments_template(); o WooCommerce redireciona esse
 * template para single-product-reviews.php — removemos o filtro só para carregar um ficheiro vazio.
 */
function wcb_pdp_prime_comments_for_product() {
	global $post;

	if ( ! $post instanceof WP_Post || 'product' !== $post->post_type ) {
		return;
	}

	$removed = false;
	if ( class_exists( 'WC_Template_Loader', false ) ) {
		$removed = remove_filter( 'comments_template', array( WC_Template_Loader::class, 'comments_template_loader' ) );
	}

	comments_template( '/wcb-pdp-prime-comments.php', false );

	if ( class_exists( 'WC_Template_Loader', false ) && $removed ) {
		add_filter( 'comments_template', array( WC_Template_Loader::class, 'comments_template_loader' ), 10 );
	}
}

/**
 * Rodapé da avaliação: botão Útil + contagem.
 *
 * @param WP_Comment $comment Comentário.
 */
function wcb_review_display_helpful( $comment ) {
	if ( ! $comment instanceof WP_Comment || 'review' !== $comment->comment_type ) {
		return;
	}
	$cid     = (int) $comment->comment_ID;
	$count   = (int) get_comment_meta( $cid, 'wcb_review_helpful', true );
	$nonce   = wp_create_nonce( 'wcb_review_helpful' );
	$voted   = wcb_review_user_has_voted_helpful( $cid );
	$pressed = $voted ? 'true' : 'false';
	?>
	<div class="wcb-pdp-review-helpful" data-comment-id="<?php echo esc_attr( (string) $cid ); ?>">
		<button
			type="button"
			class="wcb-pdp-review-helpful__btn<?php echo $voted ? ' is-voted' : ''; ?>"
			aria-pressed="<?php echo esc_attr( $pressed ); ?>"
			<?php echo $voted ? ' disabled' : ''; ?>
			data-nonce="<?php echo esc_attr( $nonce ); ?>"
		>
			<span class="wcb-pdp-review-helpful__label"><?php esc_html_e( 'Útil', 'wcb-theme' ); ?></span>
			<span class="wcb-pdp-review-helpful__count"><?php echo esc_html( (string) max( 0, $count ) ); ?></span>
		</button>
	</div>
	<?php
}
add_action( 'woocommerce_review_after_comment_text', 'wcb_review_display_helpful', 15 );

/**
 * Verifica se o visitante atual já votou (transient por IP + ID do comentário).
 *
 * @param int $comment_id ID do comentário.
 * @return bool
 */
function wcb_review_user_has_voted_helpful( $comment_id ) {
	$comment_id = absint( $comment_id );
	if ( ! $comment_id ) {
		return false;
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( '' === $ip ) {
		return false;
	}
	$key = 'wcb_rhv_' . $comment_id . '_' . md5( $ip );
	return (bool) get_transient( $key );
}

/**
 * AJAX: incrementa voto útil.
 */
function wcb_ajax_review_helpful() {
	check_ajax_referer( 'wcb_review_helpful', 'nonce' );

	$cid = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
	if ( ! $cid ) {
		wp_send_json_error( array( 'message' => __( 'Pedido inválido.', 'wcb-theme' ) ), 400 );
	}

	$c = get_comment( $cid );
	if ( ! $c || 'review' !== $c->comment_type ) {
		wp_send_json_error( array( 'message' => __( 'Avaliação não encontrada.', 'wcb-theme' ) ), 404 );
	}
	if ( '1' !== (string) $c->comment_approved ) {
		wp_send_json_error( array( 'message' => __( 'Avaliação indisponível.', 'wcb-theme' ) ), 403 );
	}
	if ( 'product' !== get_post_type( (int) $c->comment_post_ID ) ) {
		wp_send_json_error( array( 'message' => __( 'Pedido inválido.', 'wcb-theme' ) ), 400 );
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( '' === $ip ) {
		wp_send_json_error( array( 'message' => __( 'Não foi possível registrar o voto.', 'wcb-theme' ) ), 400 );
	}

	$key = 'wcb_rhv_' . $cid . '_' . md5( $ip );
	if ( get_transient( $key ) ) {
		wp_send_json_error( array( 'message' => __( 'Você já marcou esta avaliação.', 'wcb-theme' ) ), 429 );
	}

	set_transient( $key, 1, DAY_IN_SECONDS );

	$n = (int) get_comment_meta( $cid, 'wcb_review_helpful', true );
	++$n;
	update_comment_meta( $cid, 'wcb_review_helpful', $n );

	wp_send_json_success( array( 'count' => $n ) );
}
add_action( 'wp_ajax_wcb_review_helpful', 'wcb_ajax_review_helpful' );
add_action( 'wp_ajax_nopriv_wcb_review_helpful', 'wcb_ajax_review_helpful' );
