<?php
/**
 * Selo "Mais vendido" — settings, meta por produto, helpers centralizados.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var string */
const WCB_BESTSELLER_OPTION = 'wcb_bestseller';

/** @var string Meta em produto: vazio = automático; force | hide */
const WCB_BESTSELLER_META = 'wcb_bestseller_badge';

/**
 * Defaults da opção (merge com get_option).
 *
 * @return array<string, mixed>
 */
function wcb_get_bestseller_defaults() {
	return array(
		'threshold'             => 20,
		'operator'              => 'gte',
		'trending_min'          => 5,
		'trending_max'          => 20,
		'show_with_low_stock'   => false,
		'show_with_sale_badge'  => false,
		// IDs de produto (pai): lista soberana; ver texto de ajuda no admin.
		'manual_product_ids'    => array(),
	);
}

/**
 * Sanitiza gravação da opção.
 *
 * @param mixed $input Raw from options form.
 * @return array<string, mixed>
 */
function wcb_sanitize_bestseller_option( $input ) {
	$defaults = wcb_get_bestseller_defaults();
	$out      = $defaults;
	$prev     = get_option( WCB_BESTSELLER_OPTION, array() );
	if ( ! is_array( $prev ) ) {
		$prev = array();
	}

	if ( ! is_array( $input ) ) {
		return $out;
	}

	$out['threshold'] = isset( $input['threshold'] ) ? max( 0, absint( $input['threshold'] ) ) : $defaults['threshold'];

	$op = isset( $input['operator'] ) ? sanitize_key( (string) $input['operator'] ) : $defaults['operator'];
	$out['operator']  = in_array( $op, array( 'gte', 'gt' ), true ) ? $op : $defaults['operator'];

	$out['trending_min'] = isset( $input['trending_min'] ) ? max( 0, absint( $input['trending_min'] ) ) : $defaults['trending_min'];
	$out['trending_max'] = isset( $input['trending_max'] ) ? max( 0, absint( $input['trending_max'] ) ) : $defaults['trending_max'];
	if ( $out['trending_max'] < $out['trending_min'] ) {
		$out['trending_max'] = $out['trending_min'];
	}

	$out['show_with_low_stock']  = ! empty( $input['show_with_low_stock'] );
	$out['show_with_sale_badge'] = ! empty( $input['show_with_sale_badge'] );

	if ( isset( $input['_wcb_manual_list'] ) && '1' === (string) $input['_wcb_manual_list'] ) {
		$ids_in = ! empty( $input['manual_product_ids'] ) && is_array( $input['manual_product_ids'] )
			? array_map( 'absint', $input['manual_product_ids'] )
			: array();
		$out['manual_product_ids'] = wcb_bestseller_normalize_allowlist_ids( array_filter( $ids_in ) );
	} else {
		$out['manual_product_ids'] = isset( $prev['manual_product_ids'] ) && is_array( $prev['manual_product_ids'] )
			? wcb_bestseller_normalize_allowlist_ids( array_map( 'intval', $prev['manual_product_ids'] ) )
			: $defaults['manual_product_ids'];
	}

	return $out;
}

/**
 * Extrai IDs inteiros de texto (vírgula, ponto e vírgula ou linha).
 *
 * @param string $text Texto do textarea.
 * @return int[]
 */
function wcb_bestseller_parse_id_list( $text ) {
	$text = is_string( $text ) ? $text : '';
	$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
	$parts = preg_split( '/[\n,;]+/', $text, -1, PREG_SPLIT_NO_EMPTY );
	$ids   = array();
	foreach ( $parts as $p ) {
		$ids[] = absint( trim( $p ) );
	}
	$ids = array_values( array_unique( array_filter( $ids ) ) );
	return $ids;
}

/**
 * Normaliza lista: variações passam a ID do produto pai; remove inválidos.
 *
 * @param int[] $ids IDs brutos.
 * @return int[]
 */
function wcb_bestseller_normalize_allowlist_ids( array $ids ) {
	if ( empty( $ids ) || ! function_exists( 'wc_get_product' ) ) {
		return array();
	}
	$out = array();
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( $id <= 0 ) {
			continue;
		}
		$p = wc_get_product( $id );
		if ( ! $p instanceof WC_Product ) {
			continue;
		}
		if ( $p->is_type( 'variation' ) ) {
			$parent = (int) $p->get_parent_id();
			if ( $parent > 0 ) {
				$out[] = $parent;
			}
		} else {
			$out[] = $id;
		}
	}
	return array_values( array_unique( $out ) );
}

/**
 * ID do produto na lista (pai se for variação).
 *
 * @param WC_Product $product Product.
 * @return int
 */
function wcb_product_bestseller_allowlist_canonical_id( WC_Product $product ) {
	if ( $product->is_type( 'variation' ) ) {
		return (int) $product->get_parent_id();
	}
	return (int) $product->get_id();
}

/**
 * Produto está na lista manual soberana (definições globais).
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function wcb_product_is_in_bestseller_manual_allowlist( WC_Product $product ) {
	$settings = wcb_get_bestseller_settings();
	$ids      = isset( $settings['manual_product_ids'] ) && is_array( $settings['manual_product_ids'] )
		? $settings['manual_product_ids']
		: array();
	if ( empty( $ids ) ) {
		return false;
	}
	$ids = array_map( 'intval', $ids );
	$pid = wcb_product_bestseller_allowlist_canonical_id( $product );
	return in_array( $pid, $ids, true );
}

/**
 * Settings efetivas (defaults + opção + filtro).
 *
 * @return array<string, mixed>
 */
function wcb_get_bestseller_settings() {
	$defaults = wcb_get_bestseller_defaults();
	$stored   = get_option( WCB_BESTSELLER_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	$merged = array_merge( $defaults, $stored );

	if ( ! isset( $merged['manual_product_ids'] ) || ! is_array( $merged['manual_product_ids'] ) ) {
		$merged['manual_product_ids'] = array();
	} else {
		$merged['manual_product_ids'] = array_values(
			array_unique(
				array_filter( array_map( 'intval', $merged['manual_product_ids'] ) )
			)
		);
	}

	return apply_filters( 'wcb_bestseller_settings', $merged );
}

/**
 * total_sales do produto (WooCommerce).
 *
 * @param WC_Product $product Product.
 * @return int
 */
function wcb_product_get_total_sales( WC_Product $product ) {
	$n = (int) $product->get_meta( 'total_sales', true );
	if ( $n <= 0 ) {
		$n = (int) get_post_meta( $product->get_id(), 'total_sales', true );
	}
	return max( 0, $n );
}

/**
 * Modo do meta: auto | force | hide.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function wcb_product_get_bestseller_meta_mode( WC_Product $product ) {
	$v = $product->get_meta( WCB_BESTSELLER_META, true );
	$v = is_string( $v ) ? sanitize_key( $v ) : '';
	if ( 'force' === $v || 'hide' === $v ) {
		return $v;
	}
	return 'auto';
}

/**
 * Critério automático (vendas vs threshold). Sem meta, sem conflitos de UI.
 *
 * @param WC_Product $product Product.
 * @param string     $context card|search|hero.
 * @return bool
 */
function wcb_product_is_bestseller_auto( WC_Product $product, $context = 'card' ) {
	$settings = wcb_get_bestseller_settings();
	$sales    = wcb_product_get_total_sales( $product );
	$th       = (int) $settings['threshold'];
	$op       = isset( $settings['operator'] ) && 'gt' === $settings['operator'] ? 'gt' : 'gte';

	$passes = ( 'gte' === $op ) ? ( $sales >= $th ) : ( $sales > $th );

	$passes = (bool) apply_filters( 'wcb_product_is_bestseller_auto', $passes, $product, (string) $context, $settings );

	return $passes;
}

/**
 * Faixa "Em alta" para live search (vendas > min e <= max).
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function wcb_product_is_trending_sales_band( WC_Product $product ) {
	$settings = wcb_get_bestseller_settings();
	$sales    = wcb_product_get_total_sales( $product );
	$min      = (int) $settings['trending_min'];
	$max      = (int) $settings['trending_max'];
	if ( $max < $min ) {
		return false;
	}
	return $sales > $min && $sales <= $max;
}

/**
 * Exibir selo "Mais vendido" (card, search, hero).
 *
 * @param WC_Product $product Product.
 * @param array      $ctx     is_on_sale, saving, low_stock, in_stock.
 * @param string     $context card|search|hero.
 * @return bool
 */
function wcb_product_should_show_bestseller_badge( WC_Product $product, array $ctx, $context = 'card' ) {
	$settings = wcb_get_bestseller_settings();

	// Lista manual global: soberana sobre hide, force, limiar, toggles de conflito; por defeito ainda exige stock.
	if ( wcb_product_is_in_bestseller_manual_allowlist( $product ) ) {
		$respect_stock = (bool) apply_filters( 'wcb_bestseller_manual_allowlist_respects_stock', true, $product, $ctx, (string) $context, $settings );
		$show          = $respect_stock ? $product->is_in_stock() : true;
		return (bool) apply_filters( 'wcb_product_should_show_bestseller_badge', $show, $product, $ctx, $context, $settings );
	}

	$mode = wcb_product_get_bestseller_meta_mode( $product );

	if ( 'hide' === $mode ) {
		$show = false;
		return (bool) apply_filters( 'wcb_product_should_show_bestseller_badge', $show, $product, $ctx, $context, $settings );
	}

	$in_stock = ! empty( $ctx['in_stock'] );
	if ( ! $in_stock ) {
		$show = false;
		return (bool) apply_filters( 'wcb_product_should_show_bestseller_badge', $show, $product, $ctx, $context, $settings );
	}

	$is_on_sale = ! empty( $ctx['is_on_sale'] );
	$saving     = isset( $ctx['saving'] ) ? (int) $ctx['saving'] : 0;
	$low_stock  = ! empty( $ctx['low_stock'] );

	$has_sale_badge = $is_on_sale && $saving > 0;
	if ( $has_sale_badge && empty( $settings['show_with_sale_badge'] ) ) {
		$show = false;
		return (bool) apply_filters( 'wcb_product_should_show_bestseller_badge', $show, $product, $ctx, $context, $settings );
	}

	if ( $low_stock && empty( $settings['show_with_low_stock'] ) ) {
		$show = false;
		return (bool) apply_filters( 'wcb_product_should_show_bestseller_badge', $show, $product, $ctx, $context, $settings );
	}

	if ( 'force' === $mode ) {
		$show = true;
		return (bool) apply_filters( 'wcb_product_should_show_bestseller_badge', $show, $product, $ctx, $context, $settings );
	}

	$show = wcb_product_is_bestseller_auto( $product, $context );

	return (bool) apply_filters( 'wcb_product_should_show_bestseller_badge', $show, $product, $ctx, $context, $settings );
}

/* ============================================================
 * Admin — WooCommerce > Selo Mais vendido
 *
 * QA manual (checklist):
 * - WooCommerce > Selo Mais vendido: gravar limiar, operador, toggles e “Em alta”; recarregar front (card + live search).
 * - Busca AJAX: por nome, SKU (Data Store) e ID numérico; escolher variação → um único ID pai na lista (dedupe).
 * - Remover / desmarcar checkbox → ID não deve persistir após guardar; lista vazia → array vazio.
 * - Lista manual soberana: ignora hide/force automático e conflitos (exceto produto esgotado).
 * - Live search: is_bestseller alinhado; cache transient (wcb_ls_v*) após mudanças relevantes.
 * - AJAX search: nonce inválido ou sem cap. manage_woocommerce → erro esperado.
 * - Sem SelectWoo registado: aviso inline + select desativado; com registado mas sem .selectWoo(): aviso JS (notice-error).
 * ============================================================ */

/**
 * @return void
 */
function wcb_bestseller_register_settings() {
	register_setting(
		'wcb_bestseller_group',
		WCB_BESTSELLER_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'wcb_sanitize_bestseller_option',
			'default'           => wcb_get_bestseller_defaults(),
		)
	);
}
add_action( 'admin_init', 'wcb_bestseller_register_settings' );

/**
 * @return void
 */
function wcb_bestseller_admin_menu() {
	if ( ! function_exists( 'WC' ) ) {
		return;
	}
	add_submenu_page(
		'woocommerce',
		__( 'Selo Mais vendido', 'wcb-theme' ),
		__( 'Selo Mais vendido', 'wcb-theme' ),
		'manage_woocommerce',
		'wcb-bestseller-badge',
		'wcb_bestseller_render_admin_page'
	);
}
add_action( 'admin_menu', 'wcb_bestseller_admin_menu', 60 );

/**
 * Scripts e estilos da página de definições (SelectWoo + busca AJAX).
 *
 * @param string $hook_suffix Hook da página admin.
 * @return void
 */
function wcb_bestseller_admin_enqueue( $hook_suffix ) {
	if ( 'woocommerce_page_wcb-bestseller-badge' !== $hook_suffix || ! function_exists( 'WC' ) ) {
		return;
	}
	$selectwoo_ok = wp_script_is( 'wc-enhanced-select', 'registered' ) || wp_script_is( 'selectWoo', 'registered' );
	$deps         = array( 'jquery' );
	if ( $selectwoo_ok ) {
		if ( wp_script_is( 'wc-enhanced-select', 'registered' ) ) {
			wp_enqueue_script( 'wc-enhanced-select' );
			$deps[] = 'wc-enhanced-select';
		} else {
			wp_enqueue_script( 'selectWoo' );
			$deps[] = 'selectWoo';
		}
	}
	if ( wp_style_is( 'woocommerce_admin_styles', 'registered' ) ) {
		wp_enqueue_style( 'woocommerce_admin_styles' );
	}
	wp_enqueue_script(
		'wcb-bestseller-admin',
		get_template_directory_uri() . '/js/wcb-bestseller-admin.js',
		$deps,
		defined( 'WCB_VERSION' ) ? WCB_VERSION : '1.0',
		true
	);
	wp_localize_script(
		'wcb-bestseller-admin',
		'wcbBestsellerAdmin',
		array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'wcb_bestseller_search' ),
			'optionKey'    => WCB_BESTSELLER_OPTION,
			'selectWooOk'  => $selectwoo_ok,
			'i18n'         => array(
				'searchPlaceholder' => __( 'Pesquisar por nome, SKU ou ID…', 'wcb-theme' ),
				'remove'            => __( 'Remover', 'wcb-theme' ),
				'noResults'         => __( 'Nenhum produto encontrado.', 'wcb-theme' ),
				'selectWooMissing'  => __( 'A busca por produtos não está disponível (SelectWoo do WooCommerce não foi carregado). Confirme que o WooCommerce está ativo e atualizado. Pode gerir a lista abaixo pelas caixas já guardadas ou contactar o suporte.', 'wcb-theme' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'wcb_bestseller_admin_enqueue', 20 );

/**
 * AJAX: pesquisa de produtos e variações (ID canónico = pai).
 *
 * @return void
 */
function wcb_ajax_bestseller_search_products() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}
	check_ajax_referer( 'wcb_bestseller_search', 'nonce' );

	$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
	$term = trim( $term );
	if ( strlen( $term ) < 1 ) {
		wp_send_json( array( 'results' => array() ) );
	}

	$ids = array();

	if ( ctype_digit( $term ) && strlen( $term ) <= 12 ) {
		$pid = absint( $term );
		if ( $pid > 0 ) {
			$p = wc_get_product( $pid );
			if ( $p && 'publish' === $p->get_status() ) {
				$ids[] = $pid;
			}
		}
	}

	$min_len = ctype_digit( $term ) ? 1 : 2;
	if ( strlen( $term ) >= $min_len ) {
		try {
			$data_store = WC_Data_Store::load( 'product' );
			if ( is_callable( array( $data_store, 'search_products' ) ) ) {
				$found = $data_store->search_products( $term, '', true, false, 30, array(), array() );
				if ( is_array( $found ) ) {
					foreach ( $found as $fid ) {
						$ids[] = (int) $fid;
					}
				}
			}
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	}

	$ids = array_values( array_unique( array_filter( $ids ) ) );

	$results_map = array();
	foreach ( $ids as $product_id ) {
		$p = wc_get_product( $product_id );
		if ( ! $p || 'publish' !== $p->get_status() ) {
			continue;
		}
		$canonical = $p->is_type( 'variation' ) ? (int) $p->get_parent_id() : (int) $p->get_id();
		if ( $canonical <= 0 ) {
			continue;
		}
		if ( isset( $results_map[ $canonical ] ) ) {
			continue;
		}

		$label = $p->get_name();
		if ( $p->is_type( 'variation' ) ) {
			$parent = wc_get_product( $canonical );
			if ( $parent ) {
				$attr = '';
				if ( function_exists( 'wc_get_formatted_variation' ) ) {
					$attr = wc_get_formatted_variation( $p, true, true, true );
				}
				$label = $parent->get_name() . ( $attr ? ' — ' . wp_strip_all_tags( $attr ) : '' );
			}
		}

		$results_map[ $canonical ] = array(
			'id'   => $canonical,
			'text' => sprintf(
				/* translators: 1: product label, 2: canonical (parent) product ID */
				__( '%1$s (produto #%2$d)', 'wcb-theme' ),
				$label,
				$canonical
			),
		);
	}

	wp_send_json( array( 'results' => array_values( $results_map ) ) );
}
add_action( 'wp_ajax_wcb_bestseller_search_products', 'wcb_ajax_bestseller_search_products' );

/**
 * @return void
 */
function wcb_bestseller_render_admin_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$s = wcb_get_bestseller_settings();
	?>
	<div class="wrap">
		<h1><?php echo esc_html( __( 'Selo Mais vendido', 'wcb-theme' ) ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'O selo é um destaque de conversão: combina dados de vendas com regras comerciais. O badge de desconto (-%) tem prioridade visual; estes ajustes controlam quando o selo "Mais vendido" pode aparecer junto de outros sinais.', 'wcb-theme' ); ?>
		</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'wcb_bestseller_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="wcb-bs-product-search"><?php esc_html_e( 'Lista manual (prioridade máxima)', 'wcb-theme' ); ?></label>
					</th>
					<td>
						<input type="hidden" name="<?php echo esc_attr( WCB_BESTSELLER_OPTION ); ?>[_wcb_manual_list]" value="1" />
						<?php
						$manual_ids = isset( $s['manual_product_ids'] ) && is_array( $s['manual_product_ids'] ) ? $s['manual_product_ids'] : array();
						$manual_ids = array_values( array_unique( array_map( 'intval', array_filter( $manual_ids ) ) ) );
						$opt_name   = WCB_BESTSELLER_OPTION;
						$wcb_bs_sel_ok = wp_script_is( 'wc-enhanced-select', 'registered' ) || wp_script_is( 'selectWoo', 'registered' );
						if ( ! $wcb_bs_sel_ok ) :
							?>
						<div class="notice notice-warning inline" style="margin:0 0 12px;padding:8px 12px;">
							<p style="margin:0;">
								<?php
								esc_html_e(
									'SelectWoo (WooCommerce) não está disponível: a pesquisa para adicionar produtos não vai abrir. Confirme que o WooCommerce está ativo e atualizado. Pode remover entradas da lista com Remover/desmarcar e guardar; para novos IDs use outro ambiente ou contacte o suporte até a busca voltar a funcionar.',
									'wcb-theme'
								);
								?>
							</p>
						</div>
						<?php endif; ?>
						<select id="wcb-bs-product-search" class="wcb-bs-product-search" style="min-width: min(100%, 28rem);" data-placeholder="<?php echo esc_attr__( 'Pesquisar por nome, SKU ou ID…', 'wcb-theme' ); ?>" <?php disabled( ! $wcb_bs_sel_ok ); ?>></select>
						<p class="description" style="margin-top:8px;">
							<?php esc_html_e( 'Adicione produtos ou variações; o selo aplica-se ao produto pai. Desmarque a caixa ou use Remover para excluir antes de guardar.', 'wcb-theme' ); ?>
						</p>
						<ul id="wcb-bs-manual-list" class="wcb-bs-manual-list" style="margin-top:12px;max-width:40rem;">
							<?php
							foreach ( $manual_ids as $mid ) {
								$prod = wc_get_product( $mid );
								if ( ! $prod || 'publish' !== $prod->get_status() ) {
									continue;
								}
								$li_label = $prod->get_name();
								?>
								<li class="wcb-bs-manual-item" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
									<label style="display:flex;align-items:center;gap:6px;flex:1;">
										<input type="checkbox" name="<?php echo esc_attr( $opt_name ); ?>[manual_product_ids][]" value="<?php echo esc_attr( (string) (int) $mid ); ?>" checked="checked" />
										<span><?php echo esc_html( sprintf( '%s (#%d)', $li_label, (int) $mid ) ); ?></span>
									</label>
									<button type="button" class="button-link wcb-bs-remove" aria-label="<?php esc_attr_e( 'Remover', 'wcb-theme' ); ?>"><?php esc_html_e( 'Remover', 'wcb-theme' ); ?></button>
								</li>
								<?php
							}
							?>
						</ul>
						<p class="description">
							<?php esc_html_e( 'Estes produtos mostram sempre o selo "Mais vendido" (cartão e pesquisa), ignorando limiar, operador, "Ocultar selo", "Forçar" e os conflitos de desconto/stock baixo. Produtos esgotados não mostram o selo (recomendado).', 'wcb-theme' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wcb_bs_threshold"><?php esc_html_e( 'Limiar de vendas (automático)', 'wcb-theme' ); ?></label></th>
					<td>
						<input name="<?php echo esc_attr( WCB_BESTSELLER_OPTION ); ?>[threshold]" type="number" min="0" step="1" id="wcb_bs_threshold"
							value="<?php echo esc_attr( (string) (int) $s['threshold'] ); ?>" class="small-text" />
						<p class="description"><?php esc_html_e( 'Comparação com total de vendas do WooCommerce para o modo automático.', 'wcb-theme' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Operador', 'wcb-theme' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="<?php echo esc_attr( WCB_BESTSELLER_OPTION ); ?>[operator]" value="gte" <?php checked( $s['operator'], 'gte' ); ?> />
								<?php esc_html_e( 'Maior ou igual (>=)', 'wcb-theme' ); ?>
							</label><br />
							<label>
								<input type="radio" name="<?php echo esc_attr( WCB_BESTSELLER_OPTION ); ?>[operator]" value="gt" <?php checked( $s['operator'], 'gt' ); ?> />
								<?php esc_html_e( 'Maior que (>)', 'wcb-theme' ); ?>
							</label>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Mesma regra no cartão de produto e na pesquisa em tempo real.', 'wcb-theme' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Em alta (pesquisa)', 'wcb-theme' ); ?></th>
					<td>
						<label for="wcb_bs_tmin"><?php esc_html_e( 'Vendas mín. (exclusivo)', 'wcb-theme' ); ?></label>
						<input name="<?php echo esc_attr( WCB_BESTSELLER_OPTION ); ?>[trending_min]" type="number" min="0" step="1" id="wcb_bs_tmin"
							value="<?php echo esc_attr( (string) (int) $s['trending_min'] ); ?>" class="small-text" />
						&nbsp;
						<label for="wcb_bs_tmax"><?php esc_html_e( 'Vendas máx. (inclusivo)', 'wcb-theme' ); ?></label>
						<input name="<?php echo esc_attr( WCB_BESTSELLER_OPTION ); ?>[trending_max]" type="number" min="0" step="1" id="wcb_bs_tmax"
							value="<?php echo esc_attr( (string) (int) $s['trending_max'] ); ?>" class="small-text" />
						<p class="description"><?php esc_html_e( 'Produto aparece como "Em alta" na pesquisa quando vendas > mínimo e <= máximo. Não combina com o selo de mais vendido no mesmo resultado.', 'wcb-theme' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Conflitos', 'wcb-theme' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( WCB_BESTSELLER_OPTION ); ?>[show_with_low_stock]" value="1" <?php checked( ! empty( $s['show_with_low_stock'] ) ); ?> />
							<?php esc_html_e( 'Mostrar selo com estoque baixo ("Últimas und.")', 'wcb-theme' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Desligado: igual ao comportamento anterior — prioridade ao aviso de stock baixo no mesmo espaço do card.', 'wcb-theme' ); ?></p>
						<br />
						<label>
							<input type="checkbox" name="<?php echo esc_attr( WCB_BESTSELLER_OPTION ); ?>[show_with_sale_badge]" value="1" <?php checked( ! empty( $s['show_with_sale_badge'] ) ); ?> />
							<?php esc_html_e( 'Mostrar selo quando há badge de desconto (%)', 'wcb-theme' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Desligado: desconto fica como sinal principal; selo fica oculto nesse caso (modo automático).', 'wcb-theme' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/* ============================================================
 * Produto — dados gerais WooCommerce
 * ============================================================ */

/**
 * @return void
 */
function wcb_bestseller_render_product_field() {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	global $post;
	if ( ! $post || 'product' !== $post->post_type ) {
		return;
	}
	$product = wc_get_product( $post );
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$val = $product->get_meta( WCB_BESTSELLER_META, true );
	$val = is_string( $val ) ? sanitize_key( $val ) : '';
	if ( 'force' !== $val && 'hide' !== $val ) {
		$val = '';
	}
	echo '<div class="options_group">';
	woocommerce_wp_select(
		array(
			'id'          => 'wcb_bestseller_badge_select',
			'name'        => 'wcb_bestseller_badge_select',
			'label'       => __( 'Selo "Mais vendido"', 'wcb-theme' ),
			'description' => __( 'Automático usa vendas + limiar global. Forçar ou ocultar sobrepõe o automático (respeitando regras de conflito nas definições).', 'wcb-theme' ),
			'options'     => array(
				''      => __( 'Automático', 'wcb-theme' ),
				'force' => __( 'Forçar selo', 'wcb-theme' ),
				'hide'  => __( 'Ocultar selo', 'wcb-theme' ),
			),
			'value'       => $val,
		)
	);
	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'wcb_bestseller_render_product_field', 19 );

/**
 * @param WC_Product $product Product.
 * @return void
 */
function wcb_bestseller_save_product_field( $product ) {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	if ( ! isset( $_POST['wcb_bestseller_badge_select'] ) ) {
		return;
	}
	$raw = sanitize_key( wp_unslash( (string) $_POST['wcb_bestseller_badge_select'] ) );
	if ( '' === $raw ) {
		$product->delete_meta_data( WCB_BESTSELLER_META );
		return;
	}
	if ( in_array( $raw, array( 'force', 'hide' ), true ) ) {
		$product->update_meta_data( WCB_BESTSELLER_META, $raw );
	}
}
add_action( 'woocommerce_admin_process_product_object', 'wcb_bestseller_save_product_field', 10, 1 );
