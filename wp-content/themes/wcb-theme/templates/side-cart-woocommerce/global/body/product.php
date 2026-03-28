<?php
/**
 * Product (WCB Theme Override)
 *
 * Layout 3 colunas (referência “Meu carrinho”):
 * [ imagem ] [ título | 🗑 ] / [ meta + stepper | preços ]
 *
 * @version 2.7.1 (original)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$productClasses     = apply_filters( 'xoo_wsc_product_class', $productClasses );
$qty                = (int) $cart_item['quantity'];
$wcb_pname_for_aria = $_product->get_name();
$wcb_pname_src      = (isset($product_name) && is_string($product_name)) ? $product_name : $wcb_pname_for_aria;
$wcb_pname_plain    = wp_strip_all_tags($wcb_pname_src);

?>

<div data-key="<?php echo $cart_item_key ?>" class="<?php echo implode( ' ', $productClasses ) ?>">

    <?php do_action( 'xoo_wsc_product_start', $_product, $cart_item_key ); ?>

    <?php if ( $showPimage ) : ?>
        <div class="xoo-wsc-img-col">
            <?php echo $thumbnail; ?>
            <?php do_action( 'xoo_wsc_product_image_col', $_product, $cart_item_key ); ?>
        </div>
    <?php endif; ?>

    <?php
    $wcb_card_info_class = 'wcb-card-info';
    if ( ! $showPname ) {
        $wcb_card_info_class .= ' wcb-card-info--no-title';
    }
    if ( $showPdel ) {
        $wcb_card_info_class .= ' wcb-card-info--has-del';
    }
    ?>
    <div class="<?php echo esc_attr( $wcb_card_info_class ); ?>">
        <?php if ( $showPname ) : ?>
            <div class="wcb-card-title-row">
                <span class="xoo-wsc-pname"><?php echo $product_name; ?></span>
            </div>
        <?php endif; ?>

        <?php if ( $showPdel ) : ?>
            <div class="wcb-card-delete">
                <?php if ( $deleteType === 'icon' ) : ?>
                    <span class="xoo-wsc-smr-del <?php echo $delete_icon; ?>" role="button" tabindex="0" aria-label="<?php echo esc_attr( sprintf( __( 'Remover %s do carrinho', 'wcb-theme' ), $wcb_pname_for_aria ) ); ?>"></span>
                <?php else : ?>
                    <span class="xoo-wsc-smr-del xoo-wsc-del-txt" role="button" tabindex="0" aria-label="<?php echo esc_attr( sprintf( __( 'Remover %s do carrinho', 'wcb-theme' ), $wcb_pname_for_aria ) ); ?>"><?php echo $deleteText; ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="wcb-card-main">
            <div class="wcb-card-main-top">
                <?php if ( $showPmeta && $product_meta ) : ?>
                    <div class="wcb-card-meta"><?php echo $product_meta; ?></div>
                <?php endif; ?>
            </div>

            <?php if ( $showPqty ) : ?>
                <div class="wcb-card-main-bottom">
                    <div class="wcb-qty-stepper" data-key="<?php echo esc_attr( $cart_item_key ); ?>" data-qty="<?php echo esc_attr( $qty ); ?>" role="group" aria-label="<?php echo esc_attr( sprintf( __( 'Quantidade de %s', 'wcb-theme' ), $wcb_pname_for_aria ) ); ?>">
                        <button type="button" class="wcb-qty-btn wcb-qty-minus" aria-label="<?php echo esc_attr( sprintf( __( 'Diminuir quantidade de %s', 'wcb-theme' ), $wcb_pname_for_aria ) ); ?>">−</button>
                        <span class="wcb-qty-value" aria-live="polite" aria-atomic="true"><?php echo $qty; ?></span>
                        <button type="button" class="wcb-qty-btn wcb-qty-plus" aria-label="<?php echo esc_attr( sprintf( __( 'Aumentar quantidade de %s', 'wcb-theme' ), $wcb_pname_for_aria ) ); ?>">+</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="wcb-card-right">
            <div class="wcb-card-rail">
                <div class="wcb-card-rail-prices">
                    <?php if ( $showPprice && ! $oneLiner ) : ?>
                        <div class="wcb-card-price wcb-card-price--unit wcb-card-price--rail xoo-wsc-pprice wcb-card-rail-line">
                            <span class="wcb-card-rail-line__label"><?php esc_html_e( 'Valor unit.:', 'wcb-theme' ); ?></span>
                            <span class="wcb-card-rail-line__amount"><?php echo $product_price; ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="wcb-card-subtotal wcb-card-subtotal--rail wcb-card-rail-line">
                        <span class="wcb-card-rail-line__label"><?php esc_html_e( 'Subtotal:', 'wcb-theme' ); ?></span>
                        <span class="wcb-card-subtotal-value wcb-card-rail-line__amount"><?php echo $product_subtotal; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php do_action( 'xoo_wsc_product_end', $_product, $cart_item_key ); ?>

</div>
