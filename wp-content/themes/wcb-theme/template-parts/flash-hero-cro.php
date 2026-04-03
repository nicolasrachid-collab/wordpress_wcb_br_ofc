<?php
/**
 * Card destaque CRO — Super Ofertas (layout imagem + painel).
 *
 * @package WCB_Theme
 *
 * @param array $args {
 *     @type WC_Product $product Produto em destaque.
 *     @type bool      $eager_lcp Se true, loading=eager + fetchpriority=high (1.º hero / LCP).
 *     @type string    $hero_badge Micro-copy do badge de estado (TIPO 2; desconto TIPO 1 em span separado).
 * }
 */

defined( 'ABSPATH' ) || exit;

$args    = isset( $args ) && is_array( $args ) ? $args : array();
$product = isset( $args['product'] ) && $args['product'] instanceof WC_Product ? $args['product'] : null;
if ( ! $product ) {
	return;
}

$hero_regular  = (float) $product->get_regular_price();
$hero_sale     = $product->get_sale_price() ? (float) $product->get_sale_price() : 0;
$hero_current  = (float) $product->get_price();
$hero_saving_r = ( $hero_regular > 0 && $hero_sale > 0 ) ? ( $hero_regular - $hero_sale ) : 0;
$hero_saving_p = ( $hero_regular > 0 && $hero_saving_r > 0 ) ? round( ( $hero_saving_r / $hero_regular ) * 100 ) : 0;
$hero_pix      = $hero_current > 0 ? $hero_current * 0.95 : 0;
$pid           = $product->get_id();
$is_on_sale    = $product->is_on_sale();

$wcb_rating_stats = function_exists( 'wcb_get_product_rating_display_stats' )
	? wcb_get_product_rating_display_stats( $pid )
	: array(
		'average' => 0.0,
		'count'   => 0,
	);
$review_count = (int) $wcb_rating_stats['count'];
$avg_rating   = (float) $wcb_rating_stats['average'];

$cat_name = '';
$terms    = get_the_terms( $pid, 'product_cat' );
if ( $terms && ! is_wp_error( $terms ) ) {
	$cat_name = $terms[0]->name;
}

$wcb_eager_lcp = ! empty( $args['eager_lcp'] );
$hero_badge    = isset( $args['hero_badge'] ) && is_string( $args['hero_badge'] ) && $args['hero_badge'] !== ''
	? $args['hero_badge']
	: __( 'Destaque', 'wcb-theme' );
$img_attrs     = array(
	'class'    => 'wcb-hero-cro__photo',
	'decoding' => 'async',
);
if ( $wcb_eager_lcp ) {
	$img_attrs['loading']       = 'eager';
	$img_attrs['fetchpriority'] = 'high';
} else {
	$img_attrs['loading'] = 'lazy';
}
?>
<div class="wcb-flash-hero">
	<div class="wcb-product-card wcb-product-card--hero-cro" data-wcb-track="super-ofertas" data-role="hero" data-product-id="<?php echo esc_attr( (string) $pid ); ?>">

		<div class="wcb-product-card__img-wrap wcb-hero-cro__media">
			<div class="wcb-hero-cro__media-frame" aria-hidden="true"></div>
			<div class="wcb-product-card__badges wcb-product-card__badges--hero-split">
				<?php if ( $hero_saving_p > 0 ) : ?>
					<span class="wcb-product-card__badge wcb-product-card__badge--hero-discount">-<?php echo (int) $hero_saving_p; ?>%</span>
				<?php endif; ?>
				<span class="wcb-product-card__badge wcb-product-card__badge--hero-status"><?php echo esc_html( $hero_badge ); ?></span>
			</div>

			<button type="button" class="wcb-product-card__fav" title="<?php esc_attr_e( 'Favoritar', 'wcb-theme' ); ?>" data-product-id="<?php echo esc_attr( (string) $pid ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
				</svg>
			</button>

			<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="wcb-product-card__img wcb-hero-cro__img-link" tabindex="-1">
				<span class="wcb-hero-cro__img-surface">
					<?php echo $product->get_image( 'woocommerce_single', $img_attrs ); ?>
				</span>
			</a>
		</div>

		<div class="wcb-product-card__body wcb-hero-cro__panel">
			<div class="wcb-hero-cro__panel-top">
				<?php if ( $cat_name ) : ?>
				<span class="wcb-hero-cro__kicker"><?php echo esc_html( $cat_name ); ?></span>
				<?php endif; ?>

				<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="wcb-product-card__title wcb-hero-cro__title">
					<?php echo esc_html( $product->get_name() ); ?>
				</a>
			</div>

			<div class="wcb-product-card__rating<?php echo $review_count < 1 ? ' wcb-product-card__rating--zero' : ''; ?>"
				data-wcb-rating-for="<?php echo esc_attr( (string) $pid ); ?>"
				<?php
				if ( $review_count < 1 ) {
					echo ' aria-label="' . esc_attr__( 'Sem avaliações ainda', 'wcb-theme' ) . '"';
				}
				?>>
				<div class="wcb-product-card__stars" style="--rating: <?php echo esc_attr( (string) max( 0, min( 5, $avg_rating ) ) ); ?>">
					<?php if ( $review_count > 0 ) : ?>
					<span class="wcb-product-card__stars-fill" aria-hidden="true">★★★★★</span>
					<?php endif; ?>
					<span class="wcb-product-card__stars-empty" aria-hidden="true">★★★★★</span>
				</div>
				<?php if ( $review_count > 0 ) : ?>
				<span class="wcb-product-card__rating-val"><?php echo esc_html( number_format( (float) $avg_rating, 1 ) ); ?></span>
				<span class="wcb-product-card__rating-count">(<?php echo esc_html( (string) $review_count ); ?>)</span>
				<?php endif; ?>
			</div>

			<div class="wcb-hero-cro__commerce">
				<div class="wcb-product-card__price-block">
					<div class="wcb-product-card__price-main">
						<?php if ( $is_on_sale && $hero_regular > 0 ) : ?>
						<span class="wcb-product-card__price-old">R$ <?php echo esc_html( number_format( $hero_regular, 2, ',', '.' ) ); ?></span>
						<?php endif; ?>
						<span class="wcb-product-card__price-current">R$ <?php echo esc_html( number_format( $hero_current, 2, ',', '.' ) ); ?></span>
					</div>
					<?php if ( $hero_pix > 0 ) : ?>
					<span class="wcb-product-card__pix-tag">
						<strong>R$ <?php echo esc_html( number_format( $hero_pix, 2, ',', '.' ) ); ?></strong>
						<?php esc_html_e( 'no PIX', 'wcb-theme' ); ?>
						<em>(-5%)</em>
					</span>
					<?php endif; ?>
					<?php if ( $hero_current > 0 ) : ?>
					<span class="wcb-product-card__installments"><?php esc_html_e( 'ou 12x no cartão', 'wcb-theme' ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="wcb-hero-cro__actions">
				<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
					class="wcb-hero-cro__cta add_to_cart_button ajax_add_to_cart"
					data-wcb-track="super-ofertas"
					data-role="hero-cta"
					data-quantity="1"
					data-product_id="<?php echo esc_attr( (string) $pid ); ?>"
					data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
					aria-label="<?php esc_attr_e( 'Garantir oferta', 'wcb-theme' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
						<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
					</svg>
					<?php esc_html_e( 'Garantir oferta', 'wcb-theme' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>
