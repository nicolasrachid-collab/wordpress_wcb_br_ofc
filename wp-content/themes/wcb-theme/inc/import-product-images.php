<?php
/**
 * WCB — Import product images to WordPress media library
 * Run once via: Ferramentas → WCB Dev (nonce) ou wcb_import_images=1&_wpnonce=...
 */

if (!defined('ABSPATH'))
    exit;

add_action('init', function () {
    if (!defined('WCB_DEV') || !WCB_DEV) {
        return;
    }
    if (!isset($_GET['wcb_import_images']) || !current_user_can('manage_options'))
        return;
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wcb_import_images')) {
        wp_die(esc_html__('Link inválido ou expirado. Use Ferramentas → WCB Dev.', 'wcb-theme'), esc_html__('Erro de segurança', 'wcb-theme'), array('response' => 403));
    }
    if (get_option('wcb_images_imported')) {
        wp_die('Images already imported!');
    }

    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $upload_dir = wp_upload_dir();
    $base_path = $upload_dir['basedir'] . '/2026/03/';
    $base_url = $upload_dir['baseurl'] . '/2026/03/';

    // Map: filename => product SKU
    $image_map = array(
        'vaporesso-xros4-purple-gold.jpg' => 'WCB-XROS4-PG',
        'vaporesso-xros4-gold.jpg' => 'WCB-XROS4-GD',
        'coil-crc-08.jpg' => 'WCB-CRC-08',
        'cartucho-xros-pod.jpg' => 'WCB-XROS-POD',
    );

    $results = array();

    foreach ($image_map as $filename => $sku) {
        $filepath = $base_path . $filename;
        if (!file_exists($filepath)) {
            $results[] = "❌ File not found: $filename";
            continue;
        }

        // Find product by SKU
        $product_id = wc_get_product_id_by_sku($sku);
        if (!$product_id) {
            $results[] = "❌ Product not found for SKU: $sku";
            continue;
        }

        // Create attachment
        $filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'guid' => $base_url . $filename,
            'post_mime_type' => $filetype['type'],
            'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
        );

        $attach_id = wp_insert_attachment($attachment, $filepath, $product_id);
        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Set as product thumbnail
        set_post_thumbnail($product_id, $attach_id);

        $product_name = get_the_title($product_id);
        $results[] = "✅ $filename → $product_name (ID: $product_id)";
    }

    update_option('wcb_images_imported', true);

    echo '<div style="font-family:Inter,sans-serif;max-width:700px;margin:80px auto;padding:2rem;">';
    echo '<h1 style="color:#155DFD;">📸 Images Imported!</h1>';
    echo '<ul style="list-style:none;padding:0;">';
    foreach ($results as $r) {
        echo '<li style="padding:0.5rem 0;font-size:1.1rem;">' . $r . '</li>';
    }
    echo '</ul>';
    echo '<p style="margin-top:2rem;"><a href="' . home_url('/') . '" style="display:inline-block;padding:12px 24px;background:#155DFD;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;">Ver Home →</a></p>';
    echo '</div>';
    exit;
});
