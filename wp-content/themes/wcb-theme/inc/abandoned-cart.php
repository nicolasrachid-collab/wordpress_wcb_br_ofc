<?php
/**
 * WCB Theme — Abandoned Cart Recovery
 * Captura carrinhos abandonados no checkout, envia email de recuperação
 * automático via WP-Cron, e exibe painel no admin.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   1. CONSTANTES
   ============================================================ */
define( 'WCB_CART_TABLE', 'wcb_abandoned_carts' );
define( 'WCB_CART_TIMEOUT', 3600 );      // 1 hora para considerar abandonado
define( 'WCB_CART_EMAIL_DELAY', 3600 );  // Enviar email após 1 hora

/* ============================================================
   2. CRIAÇÃO DA TABELA NO BANCO
   ============================================================ */
function wcb_create_abandoned_cart_table() {
    global $wpdb;
    $table   = $wpdb->prefix . WCB_CART_TABLE;
    $charset = $wpdb->get_charset_collate();

    // Verifica se já existe para não executar dbDelta desnecessariamente
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) return;

    $sql = "CREATE TABLE $table (
        id            BIGINT(20)   NOT NULL AUTO_INCREMENT,
        session_id    VARCHAR(255) NOT NULL,
        email         VARCHAR(191) NOT NULL DEFAULT '',
        first_name    VARCHAR(100) NOT NULL DEFAULT '',
        last_name     VARCHAR(100) NOT NULL DEFAULT '',
        cart_contents LONGTEXT     NOT NULL,
        cart_total    DECIMAL(10,2) NOT NULL DEFAULT '0.00',
        status        VARCHAR(20)  NOT NULL DEFAULT 'pending',
        recovery_token VARCHAR(64) NOT NULL DEFAULT '',
        email_sent_at DATETIME     DEFAULT NULL,
        created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY idx_session  (session_id),
        KEY idx_email    (email(100)),
        KEY idx_status   (status),
        KEY idx_token    (recovery_token(32))
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
add_action( 'admin_init', 'wcb_create_abandoned_cart_table' );

/* ============================================================
   3. AJAX — Salvar/Atualizar carrinho abandonado
   ============================================================ */
function wcb_save_abandoned_cart_ajax() {
	if ( ! check_ajax_referer( 'wcb-ab-cart', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'invalid_nonce' ), 403 );
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( $ip !== '' ) {
		$rl_key = 'wcb_ab_cart_rl_' . md5( $ip );
		$hits   = (int) get_transient( $rl_key );
		if ( $hits >= 45 ) {
			wp_send_json_error( array( 'message' => 'rate_limited' ), 429 );
		}
		set_transient( $rl_key, $hits + 1, 10 * MINUTE_IN_SECONDS );
	}

    $email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
    $last_name  = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );

    if ( ! is_email( $email ) ) wp_send_json_error( 'invalid_email' );
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) wp_send_json_error( 'no_cart' );

    $cart = WC()->cart;
    if ( $cart->is_empty() ) wp_send_json_error( 'empty_cart' );

    $session_id     = WC()->session->get_customer_id();
    $cart_contents  = json_encode( $cart->get_cart() );
    $cart_total     = (float) $cart->get_cart_contents_total();
    $recovery_token = bin2hex( random_bytes( 32 ) );

    global $wpdb;
    $table = $wpdb->prefix . WCB_CART_TABLE;

    // Verificar se já existe registro para esta sessão
    $existing = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, status FROM $table WHERE session_id = %s ORDER BY id DESC LIMIT 1",
        $session_id
    ) );

    if ( $existing && $existing->status === 'pending' ) {
        $wpdb->update( $table, [
            'email'         => $email,
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            'cart_contents' => $cart_contents,
            'cart_total'    => $cart_total,
        ], [ 'id' => $existing->id ], [ '%s','%s','%s','%s','%f' ], [ '%d' ] );
    } else {
        $wpdb->insert( $table, [
            'session_id'     => $session_id,
            'email'          => $email,
            'first_name'     => $first_name,
            'last_name'      => $last_name,
            'cart_contents'  => $cart_contents,
            'cart_total'     => $cart_total,
            'status'         => 'pending',
            'recovery_token' => $recovery_token,
        ], [ '%s','%s','%s','%s','%s','%f','%s','%s' ] );
    }

    wp_send_json_success();
}
add_action( 'wp_ajax_wcb_save_abandoned_cart',        'wcb_save_abandoned_cart_ajax' );
add_action( 'wp_ajax_nopriv_wcb_save_abandoned_cart', 'wcb_save_abandoned_cart_ajax' );

/* ============================================================
   4. WP-CRON — Registrar intervalo e hook
   ============================================================ */
function wcb_add_cron_interval( $schedules ) {
    $schedules['wcb_hourly'] = [
        'interval' => 3600,
        'display'  => 'A cada hora (WCB)',
    ];
    return $schedules;
}
add_filter( 'cron_schedules', 'wcb_add_cron_interval' );

function wcb_schedule_abandoned_cart_cron() {
    if ( ! wp_next_scheduled( 'wcb_process_abandoned_carts' ) ) {
        wp_schedule_event( time(), 'wcb_hourly', 'wcb_process_abandoned_carts' );
    }
}
add_action( 'wp', 'wcb_schedule_abandoned_cart_cron' );

/* ============================================================
   5. CRON JOB — Processar e enviar emails
   ============================================================ */
add_action( 'wcb_process_abandoned_carts', 'wcb_process_abandoned_carts_callback' );

function wcb_process_abandoned_carts_callback() {
    global $wpdb;
    $table   = $wpdb->prefix . WCB_CART_TABLE;
    $timeout = date( 'Y-m-d H:i:s', time() - WCB_CART_EMAIL_DELAY );

    $carts = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table
         WHERE status = 'pending'
           AND email != ''
           AND email_sent_at IS NULL
           AND created_at < %s
         LIMIT 50",
        $timeout
    ) );

    foreach ( $carts as $cart ) {
        $sent = wcb_send_recovery_email( $cart );
        if ( $sent ) {
            $wpdb->update( $table,
                [ 'email_sent_at' => current_time( 'mysql' ), 'status' => 'emailed' ],
                [ 'id' => $cart->id ],
                [ '%s', '%s' ],
                [ '%d' ]
            );
        }
    }
}

/* ============================================================
   6. EMAIL DE RECUPERAÇÃO
   ============================================================ */
function wcb_send_recovery_email( $cart ) {
    if ( empty( $cart->email ) || ! is_email( $cart->email ) ) return false;

    $recovery_url = add_query_arg( [
        'wcb_recover' => $cart->recovery_token,
        'email'       => rawurlencode( $cart->email ),
    ], home_url( '/' ) );

    $first_name   = $cart->first_name ?: 'cliente';
    $cart_items   = json_decode( $cart->cart_contents, true );
    $total        = 'R$ ' . number_format( (float) $cart->cart_total, 2, ',', '.' );

    $subject = '😢 Você esqueceu alguns itens no carrinho!';

    $items_html = '';
    if ( is_array( $cart_items ) ) {
        foreach ( $cart_items as $item ) {
            $product_id = $item['product_id'] ?? 0;
            $product    = wc_get_product( $product_id );
            if ( ! $product ) continue;
            $qty        = $item['quantity'] ?? 1;
            $img_id     = $product->get_image_id();
            $img_url    = $img_id ? wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src();
            $price      = wc_price( $product->get_price() );
            $items_html .= '<tr>
                <td style="padding:12px 0;border-bottom:1px solid #f0f0f0;">
                    <img src="' . esc_url( $img_url ) . '" width="60" height="60" style="border-radius:8px;vertical-align:middle;margin-right:12px;object-fit:cover;">
                    <strong>' . esc_html( $product->get_name() ) . '</strong><br>
                    <small style="color:#666;">Qty: ' . esc_html( $qty ) . ' &bull; ' . $price . '</small>
                </td>
            </tr>';
        }
    }

    $logo_url  = function_exists( 'get_custom_logo_url' ) ? get_site_icon_url( 50 ) : '';
    $site_name = get_bloginfo( 'name' );

    $body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f5f5f5;font-family:Inter,Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:30px 0;">
        <tr><td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

                <!-- Header -->
                <tr><td style="background:linear-gradient(135deg,#155DFD,#3B72FE);padding:32px 40px;text-align:center;">
                    <h1 style="margin:0;color:#fff;font-size:22px;font-weight:800;">Você esqueceu algo! 🛒</h1>
                    <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Seu carrinho está te esperando.</p>
                </td></tr>

                <!-- Body -->
                <tr><td style="padding:32px 40px;">
                    <p style="font-size:16px;color:#1e293b;margin:0 0 20px;">
                        Olá, <strong>' . esc_html( $first_name ) . '</strong>! 👋
                    </p>
                    <p style="font-size:14px;color:#64748b;line-height:1.6;margin:0 0 24px;">
                        Você deixou ' . count( (array) $cart_items ) . ' ' . _n( 'item', 'itens', count( (array) $cart_items ), 'wcb-theme' ) . ' no seu carrinho. Seu pedido continua reservado, mas não por muito tempo!
                    </p>

                    <!-- Items -->
                    <table width="100%" cellpadding="0" cellspacing="0">
                        ' . $items_html . '
                    </table>

                    <!-- Total -->
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
                        <tr>
                            <td style="padding:16px;background:#f0f4ff;border-radius:10px;text-align:right;">
                                <span style="font-size:14px;color:#64748b;">Total do carrinho:</span>
                                <strong style="font-size:22px;color:#155DFD;margin-left:12px;">' . $total . '</strong>
                            </td>
                        </tr>
                    </table>

                    <!-- CTA Button -->
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0 16px;">
                        <tr><td align="center">
                            <a href="' . esc_url( $recovery_url ) . '"
                               style="display:inline-block;background:linear-gradient(135deg,#155DFD,#3B72FE);color:#fff;text-decoration:none;padding:16px 48px;border-radius:50px;font-size:16px;font-weight:700;letter-spacing:0.02em;box-shadow:0 4px 20px rgba(21,93,253,0.35);">
                                🛒 Recuperar meu carrinho
                            </a>
                        </td></tr>
                    </table>

                    <p style="font-size:12px;color:#94a3b8;text-align:center;margin:0;">
                        Este link é válido por 7 dias. Se não foi você, ignore este email.
                    </p>
                </td></tr>

                <!-- Footer -->
                <tr><td style="background:#f8faff;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
                    <p style="margin:0;font-size:12px;color:#94a3b8;">
                        © ' . date( 'Y' ) . ' ' . esc_html( $site_name ) . ' &bull;
                        <a href="' . esc_url( home_url( '/' ) ) . '" style="color:#155DFD;text-decoration:none;">Visitar loja</a>
                    </p>
                </td></tr>

            </table>
        </td></tr>
    </table>
    </body></html>';

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
    ];

    return wp_mail( $cart->email, $subject, $body, $headers );
}

/* ============================================================
   7. LINK MÁGICO — Recuperar carrinho
   ============================================================ */
function wcb_handle_cart_recovery() {
    $token = sanitize_text_field( wp_unslash( $_GET['wcb_recover'] ?? '' ) );
    if ( empty( $token ) ) return;

    global $wpdb;
    $table = $wpdb->prefix . WCB_CART_TABLE;

    $cart_row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE recovery_token = %s AND status IN ('pending','emailed') LIMIT 1",
        $token
    ) );

    if ( ! $cart_row ) return;

    // Verificar se o token não expirou (7 dias)
    $created = strtotime( $cart_row->created_at );
    if ( ( time() - $created ) > ( 7 * DAY_IN_SECONDS ) ) return;

    // Restaurar o carrinho
    if ( function_exists( 'WC' ) && WC()->cart ) {
        WC()->cart->empty_cart();
        $items = json_decode( $cart_row->cart_contents, true );
        if ( is_array( $items ) ) {
            foreach ( $items as $item ) {
                $product_id   = $item['product_id'] ?? 0;
                $quantity     = $item['quantity'] ?? 1;
                $variation_id = $item['variation_id'] ?? 0;
                $variation    = $item['variation'] ?? [];
                if ( $product_id ) {
                    WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );
                }
            }
        }

        // Pre-fill billing email
        WC()->session->set( 'wcb_recovery_email', $cart_row->email );
        WC()->session->set( 'wcb_recovery_first', $cart_row->first_name );

        // Marcar como sendo recuperado (recovered após thankyou)
        $wpdb->update( $table, [ 'status' => 'recovering' ], [ 'id' => $cart_row->id ], [ '%s' ], [ '%d' ] );
    }

    // Redirecionar para checkout
    wp_safe_redirect( wc_get_checkout_url() );
    exit;
}
add_action( 'template_redirect', 'wcb_handle_cart_recovery', 5 );

/* ============================================================
   8. PRE-FILL EMAIL NO CHECKOUT APÓS RECUPERAÇÃO
   ============================================================ */
add_filter( 'woocommerce_checkout_get_value', function( $value, $input ) {
    if ( ! function_exists( 'WC' ) || ! WC()->session ) return $value;
    if ( $input === 'billing_email' && WC()->session->get( 'wcb_recovery_email' ) ) {
        return WC()->session->get( 'wcb_recovery_email' );
    }
    if ( $input === 'billing_first_name' && WC()->session->get( 'wcb_recovery_first' ) ) {
        return WC()->session->get( 'wcb_recovery_first' );
    }
    return $value;
}, 10, 2 );

/* ============================================================
   9. MARCAR COMO RECUPERADO AO COMPLETAR PEDIDO
   ============================================================ */
function wcb_mark_cart_recovered( $order_id ) {
    if ( ! function_exists( 'WC' ) || ! WC()->session ) return;
    $session_id = WC()->session->get_customer_id();
    if ( ! $session_id ) return;

    global $wpdb;
    $table = $wpdb->prefix . WCB_CART_TABLE;
    $wpdb->update( $table,
        [ 'status' => 'recovered' ],
        [ 'session_id' => $session_id, 'status' => 'recovering' ],
        [ '%s' ],
        [ '%s', '%s' ]
    );
    $wpdb->update( $table,
        [ 'status' => 'recovered' ],
        [ 'session_id' => $session_id, 'status' => 'emailed' ],
        [ '%s' ],
        [ '%s', '%s' ]
    );
}
add_action( 'woocommerce_thankyou', 'wcb_mark_cart_recovered' );

/* ============================================================
   10. ADMIN PAGE — WooCommerce > Carrinhos Abandonados
   ============================================================ */
function wcb_register_abandoned_cart_admin_page() {
    add_submenu_page(
        'woocommerce',
        'Carrinhos Abandonados',
        '🛒 Abandonados',
        'manage_woocommerce',
        'wcb-abandoned-carts',
        'wcb_abandoned_cart_admin_page'
    );
}
add_action( 'admin_menu', 'wcb_register_abandoned_cart_admin_page' );

function wcb_abandoned_cart_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . WCB_CART_TABLE;

    // Ação: enviar email manualmente
    if ( isset( $_GET['send_email'] ) && check_admin_referer( 'wcb_send_email_' . intval( $_GET['cart_id'] ) ) ) {
        $cart_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['cart_id'] ) ) );
        if ( $cart_row ) {
            $sent = wcb_send_recovery_email( $cart_row );
            if ( $sent ) {
                $wpdb->update( $table,
                    [ 'email_sent_at' => current_time( 'mysql' ), 'status' => 'emailed' ],
                    [ 'id' => $cart_row->id ],
                    [ '%s', '%s' ], [ '%d' ]
                );
                echo '<div class="notice notice-success"><p>Email de recuperação enviado para <strong>' . esc_html( $cart_row->email ) . '</strong>!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Erro ao enviar email. Verifique as configurações de SMTP.</p></div>';
            }
        }
    }

    // Ação: deletar
    if ( isset( $_GET['delete_cart'] ) && check_admin_referer( 'wcb_delete_cart_' . intval( $_GET['cart_id'] ) ) ) {
        $wpdb->delete( $table, [ 'id' => intval( $_GET['cart_id'] ) ], [ '%d' ] );
        echo '<div class="notice notice-success"><p>Registro removido.</p></div>';
    }

    // Filtro de status
    $filter_status = sanitize_text_field( wp_unslash( $_GET['status'] ?? '' ) );
    $where = $filter_status ? $wpdb->prepare( "WHERE status = %s", $filter_status ) : '';

    $carts = $wpdb->get_results( "SELECT * FROM $table $where ORDER BY id DESC LIMIT 100" );

    // Totais
    $total_count     = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'pending' OR status = 'emailed'" );
    $recovered_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'recovered'" );
    $total_value     = $wpdb->get_var( "SELECT SUM(cart_total) FROM $table WHERE status IN ('pending','emailed')" ) ?? 0;
    $recovered_value = $wpdb->get_var( "SELECT SUM(cart_total) FROM $table WHERE status = 'recovered'" ) ?? 0;
    $recovery_rate   = $total_count ? round( ( $recovered_count / max( 1, $total_count + $recovered_count ) ) * 100, 1 ) : 0;

    $current_url = admin_url( 'admin.php?page=wcb-abandoned-carts' );

    $status_labels = [
        'pending'    => '<span style="background:#FEF3C7;color:#B45309;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;">⏳ Pendente</span>',
        'emailed'    => '<span style="background:#EEF3FF;color:#155DFD;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;">📧 Email enviado</span>',
        'recovering' => '<span style="background:#f0fdf4;color:#059669;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;">🔗 Recuperando</span>',
        'recovered'  => '<span style="background:#E6FAF4;color:#00A06A;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;">✅ Recuperado</span>',
    ];
    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">🛒 Carrinhos Abandonados</h1>

        <!-- Métricas -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin:20px 0;">
            <?php
            $metrics = [
                [ '🛒', 'Abandonados', $total_count,    '' ],
                [ '💰', 'Valor Abandonado', 'R$ ' . number_format( (float)$total_value, 2, ',', '.' ), '' ],
                [ '✅', 'Recuperados', $recovered_count, '' ],
                [ '📈', 'Taxa de Recuperação', $recovery_rate . '%', '' ],
            ];
            foreach ( $metrics as $m ) :
            ?>
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                <div style="font-size:24px;margin-bottom:4px;"><?php echo $m[0]; ?></div>
                <div style="font-size:12px;color:#64748b;margin-bottom:6px;"><?php echo esc_html( $m[1] ); ?></div>
                <div style="font-size:24px;font-weight:800;color:#1e293b;"><?php echo esc_html( $m[2] ); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filtros -->
        <div style="margin-bottom:16px;display:flex;gap:8px;align-items:center;">
            <strong>Filtrar:</strong>
            <a href="<?php echo esc_url( $current_url ); ?>" class="button <?php echo ! $filter_status ? 'button-primary' : ''; ?>">Todos</a>
            <a href="<?php echo esc_url( add_query_arg( 'status', 'pending', $current_url ) ); ?>" class="button <?php echo $filter_status === 'pending' ? 'button-primary' : ''; ?>">⏳ Pendentes</a>
            <a href="<?php echo esc_url( add_query_arg( 'status', 'emailed', $current_url ) ); ?>" class="button <?php echo $filter_status === 'emailed' ? 'button-primary' : ''; ?>">📧 Com email</a>
            <a href="<?php echo esc_url( add_query_arg( 'status', 'recovered', $current_url ) ); ?>" class="button <?php echo $filter_status === 'recovered' ? 'button-primary' : ''; ?>">✅ Recuperados</a>
        </div>

        <!-- Tabela -->
        <table class="widefat striped" style="border-radius:12px;overflow:hidden;">
            <thead style="background:#f8faff;">
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Nome</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Email Enviado</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $carts ) ) : ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:#64748b;">Nenhum carrinho encontrado.</td></tr>
                <?php else : ?>
                <?php foreach ( $carts as $cart ) :
                    $send_nonce   = wp_create_nonce( 'wcb_send_email_' . $cart->id );
                    $delete_nonce = wp_create_nonce( 'wcb_delete_cart_' . $cart->id );
                    $send_url     = add_query_arg( [ 'send_email' => 1, 'cart_id' => $cart->id, '_wpnonce' => $send_nonce ], $current_url );
                    $delete_url   = add_query_arg( [ 'delete_cart' => 1, 'cart_id' => $cart->id, '_wpnonce' => $delete_nonce ], $current_url );
                    $recovery_url = add_query_arg( [ 'wcb_recover' => $cart->recovery_token, 'email' => rawurlencode( $cart->email ) ], home_url( '/' ) );
                    $status_label = $status_labels[ $cart->status ] ?? $cart->status;
                    $items        = json_decode( $cart->cart_contents, true );
                    $item_count   = is_array( $items ) ? count( $items ) : 0;
                    ?>
                <tr>
                    <td><strong><?php echo esc_html( $cart->id ); ?></strong></td>
                    <td><?php echo esc_html( $cart->email ?: '—' ); ?></td>
                    <td><?php echo esc_html( trim( $cart->first_name . ' ' . $cart->last_name ) ?: '—' ); ?></td>
                    <td><strong>R$ <?php echo number_format( (float) $cart->cart_total, 2, ',', '.' ); ?></strong>
                        <br><small style="color:#94a3b8;"><?php echo $item_count; ?> <?php echo _n( 'item', 'itens', $item_count, 'wcb-theme' ); ?></small>
                    </td>
                    <td><?php echo $status_label; ?></td>
                    <td><?php echo $cart->email_sent_at ? esc_html( date_i18n( 'd/m/Y H:i', strtotime( $cart->email_sent_at ) ) ) : '—'; ?></td>
                    <td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $cart->created_at ) ) ); ?></td>
                    <td style="white-space:nowrap;">
                        <?php if ( $cart->status !== 'recovered' ) : ?>
                        <a href="<?php echo esc_url( $send_url ); ?>" class="button button-small" title="Enviar email de recuperação">📧 Email</a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( $recovery_url ); ?>" target="_blank" class="button button-small" title="Link de recuperação">🔗 Link</a>
                        <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small" onclick="return confirm('Remover este registro?');" title="Deletar">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ( ! empty( $carts ) ) : ?>
        <p style="color:#64748b;font-size:12px;margin-top:8px;">Exibindo até 100 registros mais recentes.</p>
        <?php endif; ?>
    </div>
    <?php
}

/* ============================================================
   11. INJETAR ajaxUrl no frontend para o JS usar
   ============================================================ */
add_action( 'wp_footer', function() {
    if ( ! is_checkout() && ! is_singular( 'cartflows_step' ) ) return;
    ?>
    <script>
    window.wcbAbCart = {
        ajaxUrl: <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
        nonce:   <?php echo json_encode( wp_create_nonce( 'wcb-ab-cart' ) ); ?>
    };
    </script>
    <?php
}, 1 );
