<?php
/**
 * Home — hero slider (banner principal).
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- ==================== HERO BANNER SLIDER ==================== -->

<?php
$theme_uri = get_template_directory_uri();

$slides = array(
	array(
		'bg'           => get_theme_mod( 'hero_slide1_image', $theme_uri . '/images/banner-1.png' ),
		'bg_mobile'    => get_theme_mod( 'hero_slide1_mobile_image', '' ),
		'video_url'    => get_theme_mod( 'hero_slide1_video_url', '' ),
		'badge'        => get_theme_mod( 'hero_slide1_badge', '🔥 Lançamento' ),
		'label'        => 'Pod System',
		'title'        => get_theme_mod( 'hero_slide1_title', 'Vaporesso XROS 4' ),
		'subtitle'     => get_theme_mod( 'hero_slide1_subtitle', 'O pod mais avançado e elegante do Brasil' ),
		'cta'          => get_theme_mod( 'hero_slide1_cta', 'Ver Produto' ),
		'cta_url'      => get_theme_mod( 'hero_slide1_cta_url', home_url( '/produto/vaporesso-xros-4/' ) ),
		'cta2'         => 'Ver Pods',
		'cta2_url'     => home_url( '/categoria/pods/' ),
		'pix'          => '5% OFF no PIX',
		'align'        => 'left',
		'overlay'      => 'rgba(4, 10, 40, 0.55)',
	),
	array(
		'bg'           => get_theme_mod( 'hero_slide2_image', $theme_uri . '/images/banner-2.png' ),
		'bg_mobile'    => get_theme_mod( 'hero_slide2_mobile_image', '' ),
		'video_url'    => get_theme_mod( 'hero_slide2_video_url', '' ),
		'badge'        => get_theme_mod( 'hero_slide2_badge', '⚡ Até 30% OFF' ),
		'label'        => 'Super Ofertas',
		'title'        => get_theme_mod( 'hero_slide2_title', 'Juices Importados' ),
		'subtitle'     => get_theme_mod( 'hero_slide2_subtitle', 'Os melhores sabores com o melhor preço' ),
		'cta'          => get_theme_mod( 'hero_slide2_cta', 'Ver Promoções' ),
		'cta_url'      => get_theme_mod( 'hero_slide2_cta_url', home_url( '/promocoes/' ) ),
		'cta2'         => 'Ver Juices',
		'cta2_url'     => home_url( '/categoria/juices/' ),
		'pix'          => 'Frete grátis acima de R$ 299',
		'align'        => 'left',
		'overlay'      => 'rgba(20, 5, 40, 0.58)',
	),
	array(
		'bg'           => get_theme_mod( 'hero_slide3_image', $theme_uri . '/images/banner-3.png' ),
		'bg_mobile'    => get_theme_mod( 'hero_slide3_mobile_image', '' ),
		'video_url'    => get_theme_mod( 'hero_slide3_video_url', '' ),
		'badge'        => get_theme_mod( 'hero_slide3_badge', '🔥 Oferta Especial' ),
		'label'        => 'Destaque',
		'title'        => get_theme_mod( 'hero_slide3_title', 'Gifts Especiais' ),
		'subtitle'     => get_theme_mod( 'hero_slide3_subtitle', '15% OFF em kits selecionados para você' ),
		'cta'          => get_theme_mod( 'hero_slide3_cta', 'Ver Kits' ),
		'cta_url'      => get_theme_mod( 'hero_slide3_cta_url', home_url( '/categoria/kits/' ) ),
		'cta2'         => 'Ver Promoções',
		'cta2_url'     => home_url( '/promocoes/' ),
		'pix'          => '15% OFF no PIX',
		'align'        => 'left',
		'overlay'      => 'rgba(40, 5, 30, 0.52)',
	),
);
?>

<section class="wcb-hero" id="wcb-hero">
	<div class="wcb-hero__track" id="wcb-hero-track">
		<?php foreach ( $slides as $i => $s ) : ?>
			<div class="wcb-hero__slide<?php echo 0 === $i ? ' active' : ''; ?>"
				data-slide-index="<?php echo esc_attr( (string) $i ); ?>"
				style="--wcb-hero-bg-desktop:url(<?php echo esc_url( $s['bg'] ); ?>);<?php echo ! empty( $s['bg_mobile'] ) ? '--wcb-hero-bg-mobile:url(' . esc_url( $s['bg_mobile'] ) . ');' : ''; ?>">

				<?php if ( ! empty( $s['video_url'] ) ) : ?>
				<video
					class="wcb-hero__video"
					src="<?php echo esc_url( $s['video_url'] ); ?>"
					autoplay muted loop playsinline
					poster="<?php echo esc_url( $s['bg'] ); ?>"
					aria-hidden="true">
				</video>
				<?php endif; ?>

			</div>
		<?php endforeach; ?>

	</div>

	<button class="wcb-hero__arrow wcb-hero__arrow--prev" id="hero-prev" type="button" aria-label="<?php esc_attr_e( 'Anterior', 'wcb-theme' ); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
			<path d="M15 18l-6-6 6-6" />
		</svg>
	</button>
	<button class="wcb-hero__arrow wcb-hero__arrow--next" id="hero-next" type="button" aria-label="<?php esc_attr_e( 'Próximo', 'wcb-theme' ); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
			<path d="M9 18l6-6-6-6" />
		</svg>
	</button>

	<div class="wcb-hero__dots" id="hero-dots">
		<?php foreach ( $slides as $i => $s ) : ?>
			<button type="button" class="wcb-hero__dot<?php echo 0 === $i ? ' active' : ''; ?>" data-slide="<?php echo esc_attr( (string) $i ); ?>"
				aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number */ __( 'Slide %d', 'wcb-theme' ), $i + 1 ) ); ?>"></button>
		<?php endforeach; ?>
	</div>
</section>
