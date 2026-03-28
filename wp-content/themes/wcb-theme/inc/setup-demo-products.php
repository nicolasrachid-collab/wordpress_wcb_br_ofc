<?php
/**
 * WCB Theme — Demo Products Setup (run once via admin)
 * Creates sample products with categories for visual testing
 *
 * Usage: Navigate to http://localhost/wcb/?wcb_setup_demo=1 while logged in as admin
 *
 * @package WCB_Theme
 */

if (!defined('ABSPATH'))
    exit;

add_action('init', function () {
    if (!isset($_GET['wcb_setup_demo']) || !current_user_can('manage_options'))
        return;
    if (!class_exists('WooCommerce')) {
        wp_die('WooCommerce not active');
    }

    // Prevent duplicate runs
    if (get_option('wcb_demo_products_created')) {
        wp_die('Demo products already created! Delete this option from wp_options to run again.');
    }

    // ── Categories ──
    $categories = array(
        'Pods' => 'Dispositivos pod system para uso diário',
        'Coils' => 'Resistências e coils de reposição',
        'Cartuchos' => 'Cartuchos de reposição para pods',
        'Juices' => 'Juices e líquidos premium',
        'Kits' => 'Kits completos para iniciantes',
        'Acessórios' => 'Acessórios e peças de reposição',
    );

    $cat_ids = array();
    foreach ($categories as $name => $desc) {
        $term = term_exists($name, 'product_cat');
        if (!$term) {
            $term = wp_insert_term($name, 'product_cat', array('description' => $desc));
        }
        $cat_ids[$name] = is_array($term) ? $term['term_id'] : $term;
    }

    // ── Products ──
    $products = array(
        array(
            'title' => 'Vaporesso XROS 4 — Purple Gold',
            'desc' => 'O XROS 4 da Vaporesso combina design premium com tecnologia de ponta. Acabamento degradê em roxo e dourado, display LED inteligente e bateria de 1000mAh. Compatível com pods XROS e coils da série CRC.',
            'price' => '279.90',
            'sale' => '249.90',
            'sku' => 'WCB-XROS4-PG',
            'cat' => 'Pods',
            'stock' => 45,
            'weight' => '0.08',
        ),
        array(
            'title' => 'Vaporesso XROS 4 — Gold Edition',
            'desc' => 'Edição especial Gold do XROS 4. Acabamento escovado em dourado, design sofisticado e ergonomia perfeita. Mesmo desempenho excepcional do XROS 4 com um visual ainda mais premium.',
            'price' => '289.90',
            'sale' => '',
            'sku' => 'WCB-XROS4-GD',
            'cat' => 'Pods',
            'stock' => 30,
            'weight' => '0.08',
        ),
        array(
            'title' => 'Coil Vaporesso CRC 0.8Ω (13-18W)',
            'desc' => 'Resistência de reposição Vaporesso CRC 0.8 ohm, ideal para MTL. Faixa de potência de 13 a 18W para um draw apertado e cheio de sabor. Pacote com 5 unidades.',
            'price' => '69.90',
            'sale' => '59.90',
            'sku' => 'WCB-CRC-08',
            'cat' => 'Coils',
            'stock' => 120,
            'weight' => '0.02',
        ),
        array(
            'title' => 'Cartucho XROS Pod 2ml',
            'desc' => 'Cartucho de reposição para a linha XROS da Vaporesso. Capacidade de 2ml, design transparente para fácil visualização do nível de líquido. Compatível com coils CRC.',
            'price' => '49.90',
            'sale' => '',
            'sku' => 'WCB-XROS-POD',
            'cat' => 'Cartuchos',
            'stock' => 85,
            'weight' => '0.015',
        ),
        // Extra products without images for variety
        array(
            'title' => 'Juice Mango Ice 30ml — Nic Salt',
            'desc' => 'Juice premium sabor manga gelada com nicotina salt 35mg. Perfeito para pods e dispositivos MTL. Blend VG/PG 50/50.',
            'price' => '39.90',
            'sale' => '34.90',
            'sku' => 'WCB-JC-MANGO',
            'cat' => 'Juices',
            'stock' => 200,
            'weight' => '0.04',
        ),
        array(
            'title' => 'Juice Strawberry Cream 30ml — Nic Salt',
            'desc' => 'Sabor morango com creme, suave e aveludado. Nicotina salt 20mg. Experiência de sabor premium para o dia inteiro.',
            'price' => '39.90',
            'sale' => '',
            'sku' => 'WCB-JC-STRAW',
            'cat' => 'Juices',
            'stock' => 180,
            'weight' => '0.04',
        ),
        array(
            'title' => 'Kit Iniciante Vaporesso XROS 4 Completo',
            'desc' => 'Kit completo para começar: inclui 1x XROS 4, 2x Coils CRC (0.6Ω e 0.8Ω), 1x cartucho extra, cabo USB-C e juice de brinde 15ml.',
            'price' => '399.90',
            'sale' => '349.90',
            'sku' => 'WCB-KIT-XROS4',
            'cat' => 'Kits',
            'stock' => 20,
            'weight' => '0.2',
        ),
        array(
            'title' => 'Cordão de Pescoço Universal — Preto',
            'desc' => 'Cordão de pescoço para pods com conector universal. Material em nylon resistente com acabamento premium. Compatível com a maioria dos pods do mercado.',
            'price' => '24.90',
            'sale' => '',
            'sku' => 'WCB-ACC-CORD',
            'cat' => 'Acessórios',
            'stock' => 300,
            'weight' => '0.01',
        ),
    );

    foreach ($products as $p) {
        // Check if already exists
        $existing = get_page_by_title($p['title'], OBJECT, 'product');
        if ($existing)
            continue;

        $product = new WC_Product_Simple();
        $product->set_name($p['title']);
        $product->set_description($p['desc']);
        $product->set_short_description(wp_trim_words($p['desc'], 20));
        $product->set_regular_price($p['price']);
        if (!empty($p['sale'])) {
            $product->set_sale_price($p['sale']);
        }
        $product->set_sku($p['sku']);
        $product->set_manage_stock(true);
        $product->set_stock_quantity($p['stock']);
        $product->set_stock_status('instock');
        $product->set_weight($p['weight']);
        $product->set_catalog_visibility('visible');
        $product->set_status('publish');

        // Set category
        if (isset($cat_ids[$p['cat']])) {
            $product->set_category_ids(array((int) $cat_ids[$p['cat']]));
        }

        $product->save();
    }

    update_option('wcb_demo_products_created', true);

    echo '<div style="font-family:Inter,sans-serif;max-width:600px;margin:80px auto;text-align:center;">';
    echo '<h1 style="color:#155DFD;">✅ Demo Products Created!</h1>';
    echo '<p>8 produtos e 6 categorias criados com sucesso.</p>';
    echo '<p><strong>Agora faça upload das imagens manualmente pelo admin do WordPress.</strong></p>';
    echo '<p><a href="' . admin_url('edit.php?post_type=product') . '" style="display:inline-block;padding:12px 24px;background:#155DFD;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;">Ver Produtos →</a></p>';
    echo '</div>';
    exit;
});
