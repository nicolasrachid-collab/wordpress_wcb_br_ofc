<?php
/**
 * Partials reutilizáveis: meta abaixo do título + card de preço (PDP e Quick View variável).
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bloco after-price: SKU, rating, resumo, urgência.
 *
 * @param WC_Product $product Produto (variável = preço/título do pai).
 * @param array      $args {
 *     @type string $rating_href    URL ou fragmento (#reviews).
 *     @type string $rating_link_id Atributo id opcional no <a> das avaliações.
 * }
 */
function wcb_buybox_print_after_price_section( WC_Product $product, array $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'rating_href'    => '#wcb-pdp-tab-reviews',
			'rating_link_id' => '',
		)
	);

	$product_id = $product->get_id();
	$sku        = $product->get_sku();

	$wcb_rating_stats = wcb_get_product_rating_display_stats( $product_id );
	$avg_rating       = (float) $wcb_rating_stats['average'];
	$review_count     = (int) $wcb_rating_stats['count'];

	$stock_qty   = $product->get_stock_quantity();
	$is_in_stock = $product->is_in_stock();

	$rating_href = (string) $args['rating_href'];
	$href_out    = ( strpos( $rating_href, '#' ) === 0 ) ? esc_attr( $rating_href ) : esc_url( $rating_href );
	$link_id     = ! empty( $args['rating_link_id'] ) ? ' id="' . esc_attr( (string) $args['rating_link_id'] ) . '"' : '';

	?>
	<div class="wcb-pdp-buybox__after-price">
		<?php if ( $sku ) : ?>
			<p class="wcb-pdp-buybox__sku">SKU: <?php echo esc_html( $sku ); ?></p>
		<?php endif; ?>

		<div class="wcb-pdp-buybox__rating" data-wcb-rating-for="<?php echo esc_attr( (string) (int) $product_id ); ?>">
			<div class="wcb-pdp-buybox__stars">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<svg class="wcb-pdp-buybox__star" width="16" height="16" viewBox="0 0 24 24"
						fill="<?php echo $i <= round( $avg_rating ) ? '#FBBF24' : 'none'; ?>"
						stroke="<?php echo $i <= round( $avg_rating ) ? '#F59E0B' : '#FCD34D'; ?>"
						stroke-width="<?php echo $i <= round( $avg_rating ) ? '1' : '1.35'; ?>">
						<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
					</svg>
				<?php endfor; ?>
			</div>
			<?php if ( $review_count > 0 ) : ?>
				<a href="<?php echo $href_out; ?>" class="wcb-pdp-buybox__rating-link"<?php echo $link_id; ?>>
					<?php echo esc_html( number_format( $avg_rating, 1, ',', '.' ) . ' · ' . $review_count . ' avaliações' ); ?>
				</a>
			<?php else : ?>
				<span class="wcb-pdp-buybox__rating-link"><?php esc_html_e( 'Seja o primeiro a avaliar', 'wcb-theme' ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( $product->get_short_description() ) : ?>
			<div
				class="wcb-pdp-buybox__desc"
				data-wcb-short-desc
				data-label-more="<?php echo esc_attr__( 'Ver mais', 'wcb-theme' ); ?>"
				data-label-less="<?php echo esc_attr__( 'Ver menos', 'wcb-theme' ); ?>"
			>
				<div class="wcb-pdp-buybox__desc-inner">
					<?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?>
				</div>
				<button type="button" class="wcb-pdp-buybox__desc-toggle" hidden aria-expanded="false">
					<?php esc_html_e( 'Ver mais', 'wcb-theme' ); ?>
				</button>
			</div>
		<?php endif; ?>

		<?php if ( $is_in_stock ) : ?>
			<?php if ( $stock_qty !== null && $stock_qty <= 10 && $stock_qty > 0 ) : ?>
				<div class="wcb-pdp-buybox__urgency wcb-pdp-buybox__urgency--low">
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %d: stock quantity */
							__( '🔥 Corra! Apenas <strong>%d unidades</strong> em estoque.', 'wcb-theme' ),
							(int) $stock_qty
						)
					);
					?>
				</div>
			<?php else : ?>
				<div class="wcb-pdp-buybox__urgency wcb-pdp-buybox__urgency--ok">
					<span class="wcb-pdp-buybox__urgency-dot" aria-hidden="true"></span>
					<?php esc_html_e( 'Em estoque — pronta entrega', 'wcb-theme' ); ?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<div class="wcb-pdp-buybox__urgency wcb-pdp-buybox__urgency--out">
				<?php esc_html_e( 'Produto indisponível', 'wcb-theme' ); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Card de preço premium (IDs = prefixo + sufixo fixo, ex. wcb-pdp-price-block).
 *
 * @param WC_Product $product    Produto.
 * @param string     $id_prefix  Ex.: wcb-pdp ou wcb-qv-pdp.
 */
function wcb_buybox_print_price_block_section( WC_Product $product, $id_prefix ) {
	$id_prefix = preg_replace( '/[^a-z0-9_-]/i', '', (string) $id_prefix );
	if ( $id_prefix === '' ) {
		$id_prefix = 'wcb-pdp';
	}

	$dec = wc_get_price_decimals();

	$regular_price  = (float) $product->get_regular_price();
	$sale_price_val = $product->get_sale_price() ? (float) $product->get_sale_price() : 0;
	$current_price  = (float) $product->get_price();
	$is_on_sale     = $product->is_on_sale();
	$pix_price      = $current_price > 0 ? round( $current_price * 0.95, $dec ) : 0;
	$economize_pix  = ( $current_price > 0 ) ? ( $current_price - $pix_price ) : 0;
	$saving_pct     = ( $is_on_sale && $regular_price > 0 && $sale_price_val > 0 )
		? (int) round( ( ( $regular_price - $sale_price_val ) / $regular_price ) * 100 )
		: 0;

	$bid = static function ( $suffix ) use ( $id_prefix ) {
		return $id_prefix . '-' . $suffix;
	};
	?>
	<div class="wcb-pdp-buybox__price-block" id="<?php echo esc_attr( $bid( 'price-block' ) ); ?>"
		data-base-price="<?php echo esc_attr( (string) $current_price ); ?>"
		data-base-regular="<?php echo esc_attr( (string) $regular_price ); ?>">
		<div class="wcb-pdp-buybox__price-card">
			<div class="wcb-pdp-buybox__price-card-body">
				<span class="wcb-pdp-buybox__price-old" id="<?php echo esc_attr( $bid( 'price-old' ) ); ?>"
					style="<?php echo ( ! $is_on_sale || $regular_price <= 0 ) ? 'display:none' : ''; ?>">
					De R$ <?php echo esc_html( number_format( $regular_price, $dec, ',', '.' ) ); ?>
				</span>

				<div class="wcb-pdp-buybox__price-ticket wcb-pdp-buybox__pix wcb-pdp-buybox__pix--hero"
					id="<?php echo esc_attr( $bid( 'pix' ) ); ?>"
					style="<?php echo ( $current_price <= 0 ) ? 'display:none' : ''; ?>">
					<div class="wcb-pdp-buybox__pix-head">
						<span class="wcb-pdp-buybox__pix-pill" title="<?php esc_attr_e( 'Desconto exclusivo no PIX', 'wcb-theme' ); ?>">
							PIX −5%
						</span>
					</div>
					<p class="wcb-pdp-buybox__price-ticket__lead">
						<strong class="wcb-pdp-buybox__pix-value" id="<?php echo esc_attr( $bid( 'pix-value' ) ); ?>">
							<?php echo esc_html( 'R$ ' . number_format( $pix_price, $dec, ',', '.' ) ); ?>
						</strong>
						<span class="wcb-pdp-buybox__price-ticket__suffix"> <?php esc_html_e( 'no PIX', 'wcb-theme' ); ?></span>
					</p>
					<p class="wcb-pdp-buybox__economize" id="<?php echo esc_attr( $bid( 'economize-pix' ) ); ?>"
						style="<?php echo ( $current_price <= 0 ) ? 'display:none' : ''; ?>">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: economia formatada */
								__( 'Economia de R$ %s no pagamento à vista', 'wcb-theme' ),
								number_format( $economize_pix, $dec, ',', '.' )
							)
						);
						?>
					</p>
					<p class="wcb-pdp-buybox__price-ticket__card">
						<span class="wcb-pdp-buybox__price-ticket__card-prefix"><?php esc_html_e( 'ou ', 'wcb-theme' ); ?></span>
						<span class="wcb-pdp-buybox__price-current" id="<?php echo esc_attr( $bid( 'price-current' ) ); ?>">
							<?php echo esc_html( 'R$ ' . number_format( $current_price, $dec, ',', '.' ) ); ?>
						</span>
						<span class="wcb-pdp-buybox__price-ticket__card-suffix"> <?php esc_html_e( 'em até 12x no cartão', 'wcb-theme' ); ?></span>
						<span class="wcb-pdp-buybox__discount" id="<?php echo esc_attr( $bid( 'discount' ) ); ?>"
							style="<?php echo ( ! $is_on_sale || $saving_pct <= 0 ) ? 'display:none' : ''; ?>">
							−<?php echo (int) $saving_pct; ?>% OFF
						</span>
					</p>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Bloco de subtotal (qty × preço + linha PIX), IDs = prefixo + sufixo (ex.: wcb-pdp-subtotal).
 *
 * @param string $id_prefix Ex.: wcb-pdp, wcb-qv-pdp.
 */
function wcb_buybox_print_subtotal_markup( $id_prefix ) {
	$id_prefix = preg_replace( '/[^a-z0-9_-]/i', '', (string) $id_prefix );
	if ( $id_prefix === '' ) {
		$id_prefix = 'wcb-pdp';
	}
	$bid = static function ( $suffix ) use ( $id_prefix ) {
		return $id_prefix . '-' . $suffix;
	};
	?>
	<div class="wcb-pdp-subtotal" id="<?php echo esc_attr( $bid( 'subtotal' ) ); ?>" style="display:none;">
		<div class="wcb-pdp-subtotal__row">
			<div class="wcb-pdp-subtotal__label">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
					<line x1="1" y1="10" x2="23" y2="10" />
				</svg>
				<span><?php esc_html_e( 'Subtotal', 'wcb-theme' ); ?> <small id="<?php echo esc_attr( $bid( 'subtotal-qty' ) ); ?>">(1 item)</small></span>
			</div>
			<div class="wcb-pdp-subtotal__value">
				<span class="wcb-pdp-subtotal__price" id="<?php echo esc_attr( $bid( 'subtotal-price' ) ); ?>">R$ 0,00</span>
			</div>
		</div>
		<div class="wcb-pdp-subtotal__pix" id="<?php echo esc_attr( $bid( 'subtotal-pix' ) ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
				<polyline points="20 6 9 17 4 12" />
			</svg>
			<span id="<?php echo esc_attr( $bid( 'subtotal-pix-val' ) ); ?>">R$ 0,00 no PIX (5% off)</span>
		</div>
	</div>
	<?php
}
