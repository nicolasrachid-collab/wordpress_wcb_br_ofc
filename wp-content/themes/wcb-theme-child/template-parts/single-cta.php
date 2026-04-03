<?php
/**
 * CTA pós-artigo — loja WooCommerce.
 *
 * @package WCB_Child
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$wcb_shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
if ( ! $wcb_shop ) {
	return;
}
?>

<section class="wcb-post-cta" aria-label="<?php esc_attr_e( 'Chamada para a loja', 'wcb-child' ); ?>">
	<div class="wcb-post-cta__inner">
		<p class="wcb-post-cta__text"><?php esc_html_e( 'Quer ver os produtos na loja?', 'wcb-child' ); ?></p>
		<a class="wcb-btn wcb-btn--primary wcb-post-cta__btn" href="<?php echo esc_url( $wcb_shop ); ?>">
			<?php esc_html_e( 'Ir para a loja', 'wcb-child' ); ?>
		</a>
	</div>
</section>
