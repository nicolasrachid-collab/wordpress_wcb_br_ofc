<?php
/**
 * WCB — Apply images to all products + set site logo
 * Run once via: Ferramentas → WCB Dev (nonce) ou wcb_apply_all=1&_wpnonce=...
 */

if (!defined('ABSPATH'))
    exit;

add_action('init', function () {
    if (!defined('WCB_DEV') || !WCB_DEV) {
        return;
    }
    if (!isset($_GET['wcb_apply_all']) || !current_user_can('manage_options'))
        return;
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wcb_apply_all')) {
        wp_die(esc_html__('Link inválido ou expirado. Use Ferramentas → WCB Dev.', 'wcb-theme'), esc_html__('Erro de segurança', 'wcb-theme'), array('response' => 403));
    }

    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $upload_dir = wp_upload_dir();
    $base_path = $upload_dir['basedir'] . '/2026/03/';
    $base_url = $upload_dir['baseurl'] . '/2026/03/';

    $results = array();

    // ── PART 1: Assign images to ALL products ──
    // Available product images (will cycle through them)
    $image_files = array(
        'vaporesso-xros4-purple-gold.jpg',
        'vaporesso-xros4-gold.jpg',
        'coil-crc-08.jpg',
        'cartucho-xros-pod.jpg',
    );

    // Get all attachment IDs for these images (create if needed)
    $image_attach_ids = array();
    foreach ($image_files as $filename) {
        $filepath = $base_path . $filename;
        if (!file_exists($filepath))
            continue;

        // Check if attachment already exists
        $existing = get_posts(array(
            'post_type' => 'attachment',
            'meta_query' => array(
                array(
                    'key' => '_wp_attached_file',
                    'value' => '2026/03/' . $filename,
                )
            ),
            'posts_per_page' => 1,
        ));

        if (!empty($existing)) {
            $image_attach_ids[] = $existing[0]->ID;
        } else {
            $filetype = wp_check_filetype($filename, null);
            $attachment = array(
                'guid' => $base_url . $filename,
                'post_mime_type' => $filetype['type'],
                'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                'post_content' => '',
                'post_status' => 'inherit',
            );
            $attach_id = wp_insert_attachment($attachment, $filepath);
            $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
            wp_update_attachment_metadata($attach_id, $attach_data);
            $image_attach_ids[] = $attach_id;
        }
    }

    if (empty($image_attach_ids)) {
        wp_die('No product images found in uploads!');
    }

    // Get ALL products
    $all_products = get_posts(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));

    $img_count = count($image_attach_ids);
    $i = 0;
    foreach ($all_products as $product_post) {
        $thumbnail_id = get_post_thumbnail_id($product_post->ID);
        if (!$thumbnail_id) {
            // Assign cycling through available images
            $attach_id = $image_attach_ids[$i % $img_count];
            set_post_thumbnail($product_post->ID, $attach_id);
            $results[] = '🖼️ ' . $product_post->post_title . ' → imagem atribuída';
            $i++;
        } else {
            $results[] = '✅ ' . $product_post->post_title . ' → já tinha imagem';
        }
    }

    // ── PART 2: Import Logo ──
    $logo_file = $base_path . 'white-cloud-brasil-logo.png';
    if (file_exists($logo_file)) {
        // Check if logo attachment already exists
        $existing_logo = get_posts(array(
            'post_type' => 'attachment',
            'meta_query' => array(
                array(
                    'key' => '_wp_attached_file',
                    'value' => '2026/03/white-cloud-brasil-logo.png',
                )
            ),
            'posts_per_page' => 1,
        ));

        if (!empty($existing_logo)) {
            $logo_id = $existing_logo[0]->ID;
        } else {
            $filetype = wp_check_filetype('white-cloud-brasil-logo.png', null);
            $attachment = array(
                'guid' => $base_url . 'white-cloud-brasil-logo.png',
                'post_mime_type' => $filetype['type'],
                'post_title' => 'White Cloud Brasil Logo',
                'post_content' => '',
                'post_status' => 'inherit',
            );
            $logo_id = wp_insert_attachment($attachment, $logo_file);
            $attach_data = wp_generate_attachment_metadata($logo_id, $logo_file);
            wp_update_attachment_metadata($logo_id, $attach_data);
        }

        // Set as custom logo
        set_theme_mod('custom_logo', $logo_id);
        $results[] = '';
        $results[] = '🎨 Logo importada e aplicada ao site (ID: ' . $logo_id . ')';
    } else {
        $results[] = '❌ Logo file not found at: ' . $logo_file;
    }

    echo '<div style="font-family:Inter,sans-serif;max-width:700px;margin:80px auto;padding:2rem;">';
    echo '<h1 style="color:#155DFD;">✅ Tudo aplicado!</h1>';
    echo '<ul style="list-style:none;padding:0;">';
    foreach ($results as $r) {
        if (empty($r)) {
            echo '<li style="padding:0.25rem;">&nbsp;</li>';
            continue;
        }
        echo '<li style="padding:0.4rem 0;font-size:1rem;">' . $r . '</li>';
    }
    echo '</ul>';
    echo '<p style="margin-top:2rem;">';
    echo '<a href="' . home_url('/') . '" style="display:inline-block;padding:12px 24px;background:#155DFD;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;">Ver Site →</a>';
    echo '</p>';
    echo '</div>';
    exit;
});
