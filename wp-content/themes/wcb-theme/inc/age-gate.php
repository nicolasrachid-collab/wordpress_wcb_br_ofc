<?php
/**
 * Verificação de idade (18+) — overlay ao primeiro acesso; cookie HTTP (SameSite=Lax).
 *
 * Filtros:
 * - wcb_age_gate_enabled (bool) — desliga o gate.
 * - wcb_age_gate_cookie_days (int) — TTL do cookie em dias (default 90).
 * - wcb_age_gate_exit_url (string) — URL ao recusar (default Google).
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var string Nome do cookie (lado servidor para leitura; JS usa o mesmo). */
define( 'WCB_AGE_GATE_COOKIE', 'wcb_age_verified' );

/**
 * @return int Dias de validade do cookie.
 */
function wcb_age_gate_cookie_days() {
	return max( 1, (int) apply_filters( 'wcb_age_gate_cookie_days', 90 ) );
}

/**
 * @return bool Utilizador já confirmou idade (cookie).
 */
function wcb_age_gate_is_verified() {
	return isset( $_COOKIE[ WCB_AGE_GATE_COOKIE ] ) && '1' === (string) $_COOKIE[ WCB_AGE_GATE_COOKIE ];
}

/**
 * @return bool Mostrar o modal no front.
 */
function wcb_age_gate_should_display() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return false;
	}
	if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
		return false;
	}
	if ( ! apply_filters( 'wcb_age_gate_enabled', true ) ) {
		return false;
	}
	if ( wcb_age_gate_is_verified() ) {
		return false;
	}
	return (bool) apply_filters( 'wcb_age_gate_should_display', true );
}

/**
 * Logo do site (sem link — evita navegar antes de confirmar).
 */
function wcb_age_gate_logo_markup() {
	echo '<div class="wcb-age-gate__logo">';
	if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			echo wp_get_attachment_image(
				$logo_id,
				'medium',
				false,
				array(
					'class'   => 'wcb-age-gate__logo-img',
					'alt'     => esc_attr( get_bloginfo( 'name', 'display' ) ),
					'loading' => 'eager',
					'decoding'=> 'async',
				)
			);
		}
	} else {
		echo '<span class="wcb-age-gate__logo-text">White <span>Cloud</span></span>';
	}
	echo '</div>';
}

/**
 * @return void
 */
function wcb_age_gate_enqueue() {
	if ( ! wcb_age_gate_should_display() ) {
		return;
	}
	wp_enqueue_style(
		'wcb-age-gate',
		get_template_directory_uri() . '/css/age-gate.css',
		array( 'wcb-style' ),
		WCB_VERSION
	);
	wp_enqueue_script(
		'wcb-age-gate',
		get_template_directory_uri() . '/js/age-gate.js',
		array(),
		WCB_VERSION,
		true
	);
	$days   = wcb_age_gate_cookie_days();
	$max_age = $days * DAY_IN_SECONDS;
	wp_localize_script(
		'wcb-age-gate',
		'wcbAgeGate',
		array(
			'cookieName'     => WCB_AGE_GATE_COOKIE,
			'maxAgeSeconds'  => $max_age,
			'exitUrl'        => esc_url_raw( apply_filters( 'wcb_age_gate_exit_url', 'https://www.google.com/' ) ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'wcb_age_gate_enqueue', 25 );

/**
 * @param string[] $classes
 * @return string[]
 */
function wcb_age_gate_body_class( $classes ) {
	if ( wcb_age_gate_should_display() ) {
		$classes[] = 'wcb-age-gate--active';
	}
	return $classes;
}
add_filter( 'body_class', 'wcb_age_gate_body_class' );

/**
 * Modal no início do body (overlay por cima do conteúdo).
 *
 * @return void
 */
function wcb_age_gate_render() {
	if ( ! wcb_age_gate_should_display() ) {
		return;
	}
	?>
	<div
		id="wcb-age-gate"
		class="wcb-age-gate"
		role="dialog"
		aria-modal="true"
		aria-labelledby="wcb-age-gate-title"
		aria-describedby="wcb-age-gate-desc"
	>
		<div class="wcb-age-gate__panel">
			<?php wcb_age_gate_logo_markup(); ?>
			<div class="wcb-age-gate__badge">
				<span class="wcb-age-gate__badge-icon" aria-hidden="true">18</span>
				<span><?php esc_html_e( 'Acesso restrito a maiores de idade', 'wcb-theme' ); ?></span>
			</div>
			<h2 id="wcb-age-gate-title" class="wcb-age-gate__title">
				<?php esc_html_e( 'Você tem mais de 18 anos?', 'wcb-theme' ); ?>
			</h2>
			<p id="wcb-age-gate-desc" class="wcb-age-gate__text">
				<?php esc_html_e( 'Ao acessar este site, você declara ser maior de 18 anos e estar ciente de que os produtos comercializados são destinados exclusivamente ao público adulto.', 'wcb-theme' ); ?>
			</p>
			<div class="wcb-age-gate__actions">
				<button type="button" class="wcb-age-gate__btn wcb-age-gate__btn--primary" id="wcb-age-gate-confirm">
					<?php esc_html_e( 'Sim, sou maior de 18 anos', 'wcb-theme' ); ?>
				</button>
				<button type="button" class="wcb-age-gate__btn wcb-age-gate__btn--secondary" id="wcb-age-gate-decline">
					<?php esc_html_e( 'Não, sair do site', 'wcb-theme' ); ?>
				</button>
			</div>
			<p class="wcb-age-gate__legal">
				<?php esc_html_e( 'Venda destinada exclusivamente a maiores de 18 anos, conforme legislação aplicável.', 'wcb-theme' ); ?>
			</p>
		</div>
	</div>
	<?php
}
add_action( 'wp_body_open', 'wcb_age_gate_render', 5 );
