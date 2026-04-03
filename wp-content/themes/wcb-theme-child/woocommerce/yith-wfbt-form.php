<?php
/**
 * YITH Frequently Bought Together — override no child (UX/CRO, card único por variável).
 *
 * Variáveis: $products (array de WC_Product), global $product.
 *
 * @package WCB_Child
 */

if ( ! defined( 'YITH_WFBT' ) ) {
	exit;
}

$wcb_fbt_helpers_path = get_stylesheet_directory() . '/inc/wcb-fbt-helpers.php';
if ( is_readable( $wcb_fbt_helpers_path ) ) {
	require_once $wcb_fbt_helpers_path;
}

global $product;

if ( ! isset( $products ) || ! is_array( $products ) || count( $products ) < 2 ) {
	return;
}

if ( ! function_exists( 'wcb_fbt_build_addon_slots' ) ) {
	return;
}

$url  = ! is_null( $product ) ? $product->get_permalink() : '';
$url  = add_query_arg( 'action', 'yith_bought_together', $url );
$url  = wp_nonce_url( $url, 'yith_bought_together' );
$size = apply_filters( 'yith_wcfbt_image_size', 'yith_wfbt_image_size' );

$label = get_option( 'yith-wfbt-button-label', __( 'Add all to Cart', 'yith-woocommerce-frequently-bought-together' ) );

$main_product_obj = $products[0];
$main_id          = (int) $main_product_obj->get_id();

$addon_slots = wcb_fbt_build_addon_slots( $products );
$card_count  = 1 + count( $addon_slots );

$total_display = (float) wc_get_price_to_display( $main_product_obj );
foreach ( $addon_slots as $slot ) {
	if ( 'simple' === $slot['type'] && isset( $slot['product'] ) && $slot['product'] instanceof WC_Product ) {
		$total_display += (float) wc_get_price_to_display( $slot['product'] );
	} elseif ( 'variable' === $slot['type'] && ! empty( $slot['variations'] ) ) {
		$def_v = wcb_fbt_default_variation( $slot['variations'] );
		if ( $def_v ) {
			$total_display += (float) wc_get_price_to_display( $def_v );
		}
	}
}

$pix_total = $total_display > 0 ? round( $total_display * 0.95, wc_get_price_decimals() ) : 0.0;

$price_str = wp_strip_all_tags( wc_price( $total_display ) );

$lead_primary = sprintf(
	/* translators: %s: formatted combo total (fallback sem linha PIX) */
	__( 'Seu combo por %s', 'wcb-child' ),
	$price_str
);

$lead_pix_line = $pix_total > 0
	? sprintf(
		/* translators: %s: formatted PIX price */
		__( '%s no PIX', 'wcb-child' ),
		wp_strip_all_tags( wc_price( $pix_total ) )
	)
	: '';

$lead_card_line = $total_display > 0
	? sprintf(
		/* translators: %s: formatted combo total (cartão / parcelas) */
		__( '%s em até 12x no cartão', 'wcb-child' ),
		$price_str
	)
	: '';

$cta_initial = $card_count > 1
	? __( 'Adicionar combo ao carrinho', 'wcb-child' )
	: __( 'Adicionar ao carrinho', 'wcb-child' );

/**
 * Atributos legíveis — linha principal ou simples (código existente).
 *
 * @param WC_Product $current_product Produto.
 */
$wcb_fbt_render_variation_attrs = static function ( WC_Product $current_product ) {
	if ( ! $current_product->is_type( 'variation' ) ) {
		return;
	}
	$attributes = $current_product->get_variation_attributes();
	$variations = array();
	foreach ( $attributes as $key => $attribute ) {
		$key = str_replace( 'attribute_', '', $key );
		$terms = get_terms(
			array(
				'taxonomy'   => sanitize_title( $key ),
				'menu_order' => 'ASC',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) ) {
			continue;
		}
		foreach ( $terms as $term ) {
			if ( ! is_object( $term ) || ! in_array( $term->slug, array( $attribute ), true ) ) {
				continue;
			}
			$variations[] = esc_html( $term->name );
		}
	}
	if ( ! empty( $variations ) ) {
		echo '<div class="wcb-fbt__attrs">' . esc_html( implode( ' · ', $variations ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
};

?>
<section class="wcb-fbt yith-wfbt-section woocommerce" aria-labelledby="wcb-fbt-heading">
	<header class="wcb-fbt__header">
		<span class="wcb-fbt__badge"><?php esc_html_e( 'Mais comprado junto', 'wcb-child' ); ?></span>
		<h2 id="wcb-fbt-heading" class="wcb-fbt__title"><?php esc_html_e( 'Compre junto e economize', 'wcb-child' ); ?></h2>
		<p class="wcb-fbt__subtitle">
			<?php esc_html_e( 'Complete sua compra com itens recomendados para melhor experiência.', 'wcb-child' ); ?>
		</p>
	</header>

	<div class="wcb-fbt__lead" data-wcb-fbt-live-region aria-live="polite" aria-atomic="true">
		<?php if ( $pix_total > 0 ) : ?>
			<p class="wcb-fbt__lead-primary">
				<span data-wcb-fbt-lead-primary><?php echo esc_html( $lead_pix_line ); ?></span>
			</p>
			<p class="wcb-fbt__lead-card">
				<span data-wcb-fbt-lead-card><?php echo esc_html( $lead_card_line ); ?></span>
			</p>
		<?php else : ?>
			<p class="wcb-fbt__lead-primary wcb-fbt__lead-primary--solo">
				<span data-wcb-fbt-lead-primary><?php echo esc_html( $lead_primary ); ?></span>
			</p>
		<?php endif; ?>
	</div>

	<form class="yith-wfbt-form wcb-fbt__form" method="post" action="<?php echo esc_url( $url ); ?>" data-wcb-fbt-form data-wcb-fbt-main-id="<?php echo esc_attr( (string) $main_id ); ?>">
		<?php wp_nonce_field( 'wcb_fbt_combo', 'wcb_fbt_nonce', false, true ); ?>

		<div class="wcb-fbt__feedback is-empty" data-wcb-fbt-feedback role="status" aria-live="polite" aria-atomic="true"></div>

		<p class="wcb-fbt__list-label"><?php esc_html_e( 'O que inclui', 'wcb-child' ); ?></p>
		<div class="wcb-fbt__rows">
			<?php
			$current_product       = $main_product_obj;
			$index                 = 0;
			$current_product_is_variation = $current_product->is_type( 'variation' );
			$current_product_price        = (float) wc_get_price_to_display( $current_product );
			$current_product_link         = $current_product->get_permalink();
			$current_product_image        = $current_product->get_image( $size );
			$current_product_title        = $current_product->get_title();

			$row_classes = 'wcb-fbt__row wcb-fbt__row--main';
			$row_attrs   = sprintf(
				' data-wcb-fbt-row data-wcb-price="%s" data-wcb-index="%s"',
				esc_attr( (string) $current_product_price ),
				esc_attr( (string) $index )
			);
			?>
			<div class="<?php echo esc_attr( $row_classes ); ?>"<?php echo $row_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<div class="wcb-fbt__check-col">
					<span class="wcb-fbt__row-tag wcb-fbt__row-tag--main"><?php esc_html_e( 'Seu produto', 'wcb-child' ); ?></span>
					<span class="wcb-fbt__check wcb-fbt__check--spacer" aria-hidden="true"></span>
				</div>
				<a href="<?php echo esc_url( $current_product_link ); ?>" class="wcb-fbt__thumb" tabindex="-1">
					<?php echo $current_product_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<div class="wcb-fbt__body">
					<a href="<?php echo esc_url( $current_product_link ); ?>" class="wcb-fbt__name">
						<?php echo esc_html( $current_product_title ); ?>
					</a>
					<?php $wcb_fbt_render_variation_attrs( $current_product ); ?>
					<div class="wcb-fbt__price">
						<?php echo $current_product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</div>
			<?php
			$addon_count = count( $addon_slots );
			if ( $addon_count > 0 ) {
				echo '<div class="wcb-fbt__op wcb-fbt__op--plus" aria-hidden="true">+</div>';
			}

			foreach ( $addon_slots as $ai => $slot ) {
				$index = 1 + (int) $ai;

				if ( 'simple' === $slot['type'] && isset( $slot['product'] ) && $slot['product'] instanceof WC_Product ) {
					$cp               = $slot['product'];
					$cp_id            = (int) $cp->get_id();
					$cp_price         = (float) wc_get_price_to_display( $cp );
					$cp_link          = $cp->get_permalink();
					$cp_image         = $cp->get_image( $size );
					$cp_title         = $cp->get_title();
					$cb_id            = 'wcb-fbt-offer-' . (string) $index;
					$cp_max_qty       = wcb_fbt_max_purchase_qty( $cp );
					$row_attrs_simple = sprintf(
						' data-wcb-fbt-row data-wcb-fbt-addon="1" data-wcb-fbt-line-id="%s" data-wcb-price="%s" data-wcb-index="%s" data-wcb-fbt-max-qty="%s"',
						esc_attr( (string) $cp_id ),
						esc_attr( (string) $cp_price ),
						esc_attr( (string) $index ),
						esc_attr( (string) $cp_max_qty )
					);
					?>
					<div class="wcb-fbt__row wcb-fbt__row--addon"
						<?php echo $row_attrs_simple; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						data-wcb-fbt-row-toggle
						role="group"
						aria-label="<?php esc_attr_e( 'Item opcional do combo', 'wcb-child' ); ?>">
						<div class="wcb-fbt__check-col">
							<span class="wcb-fbt__row-tag wcb-fbt__row-tag--addon"><?php esc_html_e( 'Adicionar', 'wcb-child' ); ?></span>
							<div class="wcb-fbt__check-wrap">
								<input type="checkbox"
									class="wcb-fbt__cb"
									id="<?php echo esc_attr( $cb_id ); ?>"
									checked />
								<label class="wcb-fbt__check" for="<?php echo esc_attr( $cb_id ); ?>">
									<span class="wcb-fbt__check-ui" aria-hidden="true"></span>
									<span class="screen-reader-text"><?php esc_html_e( 'Incluir este item no combo', 'wcb-child' ); ?></span>
								</label>
							</div>
						</div>
						<a href="<?php echo esc_url( $cp_link ); ?>" class="wcb-fbt__thumb">
							<?php echo $cp_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
						<div class="wcb-fbt__body">
							<a href="<?php echo esc_url( $cp_link ); ?>" class="wcb-fbt__name">
								<?php echo esc_html( $cp_title ); ?>
							</a>
							<?php $wcb_fbt_render_variation_attrs( $cp ); ?>
							<div class="wcb-fbt__price">
								<?php echo $cp->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<div class="wcb-fbt__qty-wrap" data-wcb-fbt-qty-wrap>
								<button type="button" class="wcb-fbt__qty-btn" data-wcb-fbt-qty-dec aria-label="<?php esc_attr_e( 'Diminuir quantidade', 'wcb-child' ); ?>">−</button>
								<input type="number" class="wcb-fbt__qty-input" name="" value="1" min="1" max="<?php echo esc_attr( (string) $cp_max_qty ); ?>" step="1" inputmode="numeric" data-wcb-fbt-qty-input aria-label="<?php esc_attr_e( 'Quantidade no combo', 'wcb-child' ); ?>" />
								<button type="button" class="wcb-fbt__qty-btn" data-wcb-fbt-qty-inc aria-label="<?php esc_attr_e( 'Aumentar quantidade', 'wcb-child' ); ?>">+</button>
							</div>
						</div>
					</div>
					<?php
				} elseif ( 'variable' === $slot['type'] && ! empty( $slot['variations'] ) ) {
					$parent   = isset( $slot['parent_id'] ) ? wc_get_product( (int) $slot['parent_id'] ) : null;
					$def_var  = wcb_fbt_default_variation( $slot['variations'] );
					if ( ! $def_var || ! $parent ) {
						continue;
					}
					$def_price    = (float) wc_get_price_to_display( $def_var );
					$def_max_qty  = wcb_fbt_max_purchase_qty( $def_var );
					$parent_link  = $parent->get_permalink();
					$parent_title = $parent->get_name();
					$var_image    = $def_var->get_image( $size );
					$cb_id        = 'wcb-fbt-offer-' . (string) $index;
					$json_payload = wcb_fbt_variations_json_payload( $slot['variations'] );
					$json_attr    = esc_attr( wp_json_encode( $json_payload ) );
					$row_attrs_v  = sprintf(
						' data-wcb-fbt-row data-wcb-fbt-addon="1" data-wcb-fbt-variable-row data-wcb-fbt-line-id="%s" data-wcb-price="%s" data-wcb-index="%s" data-wcb-fbt-max-qty="%s" data-wcb-variations="%s"',
						esc_attr( (string) $def_var->get_id() ),
						esc_attr( (string) $def_price ),
						esc_attr( (string) $index ),
						esc_attr( (string) $def_max_qty ),
						$json_attr
					);
					$use_select   = count( $slot['variations'] ) > 10;
					?>
					<div class="wcb-fbt__row wcb-fbt__row--addon wcb-fbt__row--variable"
						<?php echo $row_attrs_v; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						data-wcb-fbt-row-toggle
						role="group"
						aria-label="<?php esc_attr_e( 'Item opcional do combo com variações', 'wcb-child' ); ?>">
						<div class="wcb-fbt__check-col">
							<span class="wcb-fbt__row-tag wcb-fbt__row-tag--addon"><?php esc_html_e( 'Adicionar', 'wcb-child' ); ?></span>
							<div class="wcb-fbt__check-wrap">
								<input type="checkbox"
									class="wcb-fbt__cb"
									id="<?php echo esc_attr( $cb_id ); ?>"
									checked
									aria-controls="<?php echo esc_attr( 'wcb-fbt-var-ui-' . (string) $index ); ?>" />
								<label class="wcb-fbt__check" for="<?php echo esc_attr( $cb_id ); ?>">
									<span class="wcb-fbt__check-ui" aria-hidden="true"></span>
									<span class="screen-reader-text"><?php esc_html_e( 'Incluir este item no combo', 'wcb-child' ); ?></span>
								</label>
							</div>
						</div>
						<a href="<?php echo esc_url( $parent_link ); ?>" class="wcb-fbt__thumb">
							<?php echo $var_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
						<div class="wcb-fbt__body">
							<a href="<?php echo esc_url( $parent_link ); ?>" class="wcb-fbt__name">
								<?php echo esc_html( $parent_title ); ?>
							</a>
							<div class="wcb-fbt__var" id="<?php echo esc_attr( 'wcb-fbt-var-ui-' . (string) $index ); ?>">
								<?php if ( $use_select ) : ?>
									<label class="wcb-fbt__var-label screen-reader-text" for="<?php echo esc_attr( 'wcb-fbt-var-sel-' . (string) $index ); ?>">
										<?php esc_html_e( 'Escolha a variação', 'wcb-child' ); ?>
									</label>
									<select class="wcb-fbt__var-select" id="<?php echo esc_attr( 'wcb-fbt-var-sel-' . (string) $index ); ?>" data-wcb-fbt-var-select>
										<?php foreach ( $json_payload as $j ) : ?>
											<option value="<?php echo esc_attr( (string) $j['variation_id'] ); ?>" <?php selected( (int) $j['variation_id'], (int) $def_var->get_id() ); ?>>
												<?php echo esc_html( $j['label'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								<?php else : ?>
									<div class="wcb-fbt__var-chips" role="radiogroup" aria-label="<?php esc_attr_e( 'Variação', 'wcb-child' ); ?>">
										<?php foreach ( $json_payload as $j ) : ?>
											<?php
											$is_active = (int) $j['variation_id'] === (int) $def_var->get_id();
											?>
											<button type="button"
												class="wcb-fbt__var-chip<?php echo $is_active ? ' is-active' : ''; ?>"
												data-wcb-fbt-var-pick="<?php echo esc_attr( (string) $j['variation_id'] ); ?>"
												role="radio"
												aria-checked="<?php echo $is_active ? 'true' : 'false'; ?>">
												<?php echo esc_html( $j['label'] ); ?>
											</button>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
							<div class="wcb-fbt__price" data-wcb-fbt-price-wrap>
								<?php echo wp_kses_post( $def_var->get_price_html() ); ?>
							</div>
							<div class="wcb-fbt__qty-wrap" data-wcb-fbt-qty-wrap>
								<button type="button" class="wcb-fbt__qty-btn" data-wcb-fbt-qty-dec aria-label="<?php esc_attr_e( 'Diminuir quantidade', 'wcb-child' ); ?>">−</button>
								<input type="number" class="wcb-fbt__qty-input" name="" value="1" min="1" max="<?php echo esc_attr( (string) $def_max_qty ); ?>" step="1" inputmode="numeric" data-wcb-fbt-qty-input aria-label="<?php esc_attr_e( 'Quantidade no combo', 'wcb-child' ); ?>" />
								<button type="button" class="wcb-fbt__qty-btn" data-wcb-fbt-qty-inc aria-label="<?php esc_attr_e( 'Aumentar quantidade', 'wcb-child' ); ?>">+</button>
							</div>
						</div>
					</div>
					<?php
				}

				if ( $ai < $addon_count - 1 ) {
					echo '<div class="wcb-fbt__op wcb-fbt__op--plus" aria-hidden="true">+</div>';
				}
			}
			?>
		</div>

		<div class="wcb-fbt__op wcb-fbt__op--eq" aria-hidden="true">=</div>

		<div class="wcb-fbt__footer">
			<p class="wcb-fbt__footer-total">
				<span class="wcb-fbt__footer-total-label"><?php esc_html_e( 'Total', 'wcb-child' ); ?></span>
				<span class="wcb-fbt__footer-total-amount" data-wcb-fbt-total><?php echo wc_price( $total_display ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</p>

			<button type="submit" class="yith-wfbt-submit-button button wcb-fbt__submit" data-wcb-fbt-submit data-wcb-default-label="<?php echo esc_attr( $label ); ?>">
				<?php echo esc_html( $cta_initial ); ?>
			</button>
		</div>
	</form>
</section>
