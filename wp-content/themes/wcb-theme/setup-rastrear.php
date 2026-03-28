<?php
/**
 * Setup Script — Cria a página "Rastrear Pedido" no WordPress
 * Acesse via: http://localhost/wcb/wp-content/themes/wcb-theme/setup-rastrear.php
 * APAGUE ESTE ARQUIVO após executar!
 */

// Carrega o WordPress
$wp_load = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load)) {
    die('wp-load.php não encontrado em: ' . $wp_load);
}
require_once($wp_load);

// Verifica se já existe
$existing = get_page_by_path('rastrear-pedido');

if ($existing) {
    // Atualiza o template caso exista
    update_post_meta($existing->ID, '_wp_page_template', 'page-rastrear-pedido.php');
    echo '<p style="color:green;font-family:sans-serif;">✅ Página já existe (ID: ' . $existing->ID . '). Template atualizado para <code>page-rastrear-pedido.php</code>.</p>';
    echo '<p><a href="' . get_permalink($existing->ID) . '" target="_blank">→ Ver página</a></p>';
} else {
    // Cria a página
    $page_id = wp_insert_post(array(
        'post_title'   => 'Rastrear Pedido',
        'post_name'    => 'rastrear-pedido',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '[woocommerce_order_tracking]',
        'menu_order'   => 0,
    ));

    if (is_wp_error($page_id)) {
        echo '<p style="color:red;font-family:sans-serif;">❌ Erro ao criar página: ' . $page_id->get_error_message() . '</p>';
    } else {
        // Define o template customizado
        update_post_meta($page_id, '_wp_page_template', 'page-rastrear-pedido.php');
        echo '<p style="color:green;font-family:sans-serif;font-size:18px;">✅ Página criada com sucesso! ID: ' . $page_id . '</p>';
        echo '<p style="font-family:sans-serif;"><a href="' . get_permalink($page_id) . '" target="_blank" style="color:#155DFD;">→ Ver página: ' . get_permalink($page_id) . '</a></p>';
        echo '<p style="font-family:sans-serif;color:#888;">⚠️ Apague este arquivo após usar: <code>setup-rastrear.php</code></p>';
    }
}
?>
