<?php
/**
 * Links seguros (com nonce) para ferramentas demo/import — só com WCB_DEV.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Página em Ferramentas com URLs que incluem _wpnonce (proteção CSRF).
 */
function wcb_dev_tools_register_menu() {
	add_management_page(
		'WCB Dev',
		'WCB Dev',
		'manage_options',
		'wcb-dev-tools',
		'wcb_dev_tools_render_page'
	);
}
add_action( 'admin_menu', 'wcb_dev_tools_register_menu' );

/**
 * @return void
 */
function wcb_dev_tools_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$home = home_url( '/' );
	$url_demo  = wp_nonce_url( add_query_arg( 'wcb_setup_demo', '1', $home ), 'wcb_setup_demo' );
	$url_img   = wp_nonce_url( add_query_arg( 'wcb_import_images', '1', $home ), 'wcb_import_images' );
	$url_apply = wp_nonce_url( add_query_arg( 'wcb_apply_all', '1', $home ), 'wcb_apply_all' );
	?>
	<div class="wrap">
		<h1>WCB — Ferramentas de desenvolvimento</h1>
		<p>Estas ações só existem com <code>WCB_DEV</code> ativo. Cada link usa nonce; não partilhe URLs completas.</p>
		<ul style="list-style:disc;margin-left:1.5em;">
			<li><a href="<?php echo esc_url( $url_demo ); ?>">Criar produtos demo</a> (<code>wcb_setup_demo</code>)</li>
			<li><a href="<?php echo esc_url( $url_img ); ?>">Importar imagens de produtos</a> (<code>wcb_import_images</code>)</li>
			<li><a href="<?php echo esc_url( $url_apply ); ?>">Aplicar imagens + logo</a> (<code>wcb_apply_all</code>)</li>
		</ul>
	</div>
	<?php
}
