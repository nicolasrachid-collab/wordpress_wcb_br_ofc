<?php
/**
 * Campanhas de ofertas — CPT + ACF Free (sem repeater).
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Slug do tipo de conteúdo (URLs e queries). */
if ( ! defined( 'WCB_FLASH_CAMPAIGN_POST_TYPE' ) ) {
	define( 'WCB_FLASH_CAMPAIGN_POST_TYPE', 'wcb_flash_campaign' );
}

if ( ! defined( 'WCB_FLASH_CAMPAIGNS_MAP_TRANSIENT' ) ) {
	define( 'WCB_FLASH_CAMPAIGNS_MAP_TRANSIENT', 'wcb_flash_campaigns_product_end_v2' );
}

/**
 * Apaga o mapa produto → fim (transient + cache estático do request).
 */
function wcb_flash_campaigns_bust_cache() {
	delete_transient( WCB_FLASH_CAMPAIGNS_MAP_TRANSIENT );
	unset( $GLOBALS['wcb_flash_campaigns_map_static'] );
}

/**
 * Lê dados de uma campanha (ACF ou meta nativa).
 *
 * @param int $post_id
 * @return array{ativo:bool,data_fim:string,prioridade:int,produtos:array,categorias:array}
 */
function wcb_flash_campaign_get_row_data( $post_id ) {
	$post_id = (int) $post_id;
	$defaults = array(
		'ativo'       => false,
		'data_fim'    => '',
		'prioridade'  => 10,
		'produtos'    => array(),
		'categorias'  => array(),
	);
	if ( $post_id < 1 ) {
		return $defaults;
	}

	if ( function_exists( 'get_field' ) ) {
		$av                     = get_field( 'wcb_fc_ativo', $post_id );
		$defaults['ativo']      = ( true === $av || 1 === $av || '1' === $av );
		$df                     = get_field( 'wcb_fc_data_fim', $post_id );
		$defaults['data_fim']   = is_string( $df ) ? $df : '';
		$defaults['prioridade'] = (int) get_field( 'wcb_fc_prioridade', $post_id );
		$defaults['produtos']   = get_field( 'wcb_fc_produtos', $post_id );
		$defaults['categorias'] = get_field( 'wcb_fc_categorias', $post_id );
		if ( ! is_array( $defaults['produtos'] ) ) {
			$defaults['produtos'] = array();
		}
		if ( ! is_array( $defaults['categorias'] ) ) {
			$defaults['categorias'] = array();
		}
		return $defaults;
	}

	$am                     = get_post_meta( $post_id, 'wcb_fc_ativo', true );
	$defaults['ativo']      = ( '1' === $am || 1 === $am || true === $am );
	$defaults['data_fim']   = (string) get_post_meta( $post_id, 'wcb_fc_data_fim', true );
	$defaults['prioridade'] = (int) get_post_meta( $post_id, 'wcb_fc_prioridade', true );
	$p                      = get_post_meta( $post_id, 'wcb_fc_produtos', true );
	$c                      = get_post_meta( $post_id, 'wcb_fc_categorias', true );
	$defaults['produtos']   = is_array( $p ) ? $p : array();
	$defaults['categorias'] = is_array( $c ) ? $c : array();
	return $defaults;
}

/**
 * Converte data/hora local para timestamp Unix.
 *
 * @param string $str Valor típico Y-m-d H:i:s.
 * @return int 0 se inválido.
 */
function wcb_flash_campaigns_parse_end_local( $str ) {
	$str = trim( (string) $str );
	if ( $str === '' ) {
		return 0;
	}
	try {
		$dt = new DateTimeImmutable( $str, wp_timezone() );
		return $dt->getTimestamp();
	} catch ( Exception $e ) {
		$t = strtotime( $str );
		return ( is_numeric( $t ) && (int) $t > 0 ) ? (int) $t : 0;
	}
}

/**
 * IDs a partir de post_object / meta.
 *
 * @param mixed $raw
 * @return int[]
 */
function wcb_flash_campaigns_collect_post_ids( $raw ) {
	if ( ! is_array( $raw ) || $raw === array() ) {
		return array();
	}
	$out = array();
	foreach ( $raw as $item ) {
		if ( is_object( $item ) && isset( $item->ID ) ) {
			$out[] = (int) $item->ID;
		} elseif ( is_numeric( $item ) ) {
			$out[] = (int) $item;
		}
	}
	return array_values( array_filter( array_unique( $out ) ) );
}

/**
 * term_ids a partir de taxonomy field / meta.
 *
 * @param mixed $raw
 * @return int[]
 */
function wcb_flash_campaigns_collect_term_ids( $raw ) {
	if ( ! is_array( $raw ) || $raw === array() ) {
		return array();
	}
	$out = array();
	foreach ( $raw as $item ) {
		if ( is_object( $item ) && isset( $item->term_id ) ) {
			$out[] = (int) $item->term_id;
		} elseif ( is_numeric( $item ) ) {
			$out[] = (int) $item;
		}
	}
	return array_values( array_filter( array_unique( $out ) ) );
}

/**
 * Regista o CPT Campanha (lista nativa WP: Adicionar novo, etc.).
 */
function wcb_flash_campaigns_register_post_type() {
	$labels = array(
		'name'               => __( 'Campanhas de ofertas', 'wcb-theme' ),
		'singular_name'      => __( 'Campanha de ofertas', 'wcb-theme' ),
		'add_new'            => __( 'Adicionar campanha', 'wcb-theme' ),
		'add_new_item'       => __( 'Nova campanha', 'wcb-theme' ),
		'edit_item'          => __( 'Editar campanha', 'wcb-theme' ),
		'new_item'           => __( 'Nova campanha', 'wcb-theme' ),
		'view_item'          => __( 'Ver campanha', 'wcb-theme' ),
		'search_items'       => __( 'Procurar campanhas', 'wcb-theme' ),
		'not_found'          => __( 'Nenhuma campanha.', 'wcb-theme' ),
		'not_found_in_trash' => __( 'Nada no lixo.', 'wcb-theme' ),
		'menu_name'          => __( 'Campanhas de Ofertas', 'wcb-theme' ),
	);

	$show_menu = class_exists( 'WooCommerce' ) ? 'woocommerce' : true;

	register_post_type(
		WCB_FLASH_CAMPAIGN_POST_TYPE,
		array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => $show_menu,
			'menu_position'       => 56,
			'menu_icon'           => 'dashicons-clock',
			'capability_type'     => 'product',
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'show_in_rest'        => false,
		)
	);
}

add_action( 'init', 'wcb_flash_campaigns_register_post_type', 5 );

/**
 * Flush de permalinks uma vez após introduzir o CPT.
 */
function wcb_flash_campaigns_maybe_flush_rewrites() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$v = get_option( 'wcb_flash_campaign_cpt_version', '' );
	if ( $v === '1' ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'wcb_flash_campaign_cpt_version', '1', false );
}

add_action( 'admin_init', 'wcb_flash_campaigns_maybe_flush_rewrites', 30 );

/**
 * Há campanha publicada, ativa e com fim no futuro?
 */
function wcb_flash_campaigns_has_active_schedule() {
	$q = new WP_Query(
		array(
			'post_type'              => WCB_FLASH_CAMPAIGN_POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => 30,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'orderby'                => 'date',
			'order'                  => 'DESC',
		)
	);
	if ( empty( $q->posts ) ) {
		return false;
	}
	$now = time();
	foreach ( $q->posts as $pid ) {
		$row = wcb_flash_campaign_get_row_data( (int) $pid );
		if ( empty( $row['ativo'] ) ) {
			continue;
		}
		$end = wcb_flash_campaigns_parse_end_local( $row['data_fim'] );
		if ( $end > $now ) {
			return true;
		}
	}
	return false;
}

/**
 * Monta mapa product_id => timestamp_fim (maior prioridade vence).
 *
 * @return array<int, int>
 */
function wcb_flash_campaigns_build_product_end_map() {
	$q = new WP_Query(
		array(
			'post_type'              => WCB_FLASH_CAMPAIGN_POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'orderby'                => 'date',
			'order'                  => 'ASC',
		)
	);
	if ( empty( $q->posts ) ) {
		return apply_filters( 'wcb_flash_campaigns_product_end_map', array() );
	}

	$now       = time();
	$campaigns = array();
	foreach ( $q->posts as $pid ) {
		$row = wcb_flash_campaign_get_row_data( (int) $pid );
		if ( empty( $row['ativo'] ) ) {
			continue;
		}
		$end = wcb_flash_campaigns_parse_end_local( $row['data_fim'] );
		if ( $end <= $now ) {
			continue;
		}
		$prio = isset( $row['prioridade'] ) ? (int) $row['prioridade'] : 10;
		$pids = wcb_flash_campaigns_collect_post_ids( $row['produtos'] );
		$cats = wcb_flash_campaigns_collect_term_ids( $row['categorias'] );
		if ( $cats !== array() ) {
			$cat_pids = get_posts(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_term_meta_cache' => false,
					'tax_query'              => array(
						array(
							'taxonomy'         => 'product_cat',
							'field'            => 'term_id',
							'terms'            => $cats,
							'operator'         => 'IN',
							'include_children' => true,
						),
					),
				)
			);
			if ( is_array( $cat_pids ) && $cat_pids !== array() ) {
				$pids = array_merge( $pids, array_map( 'intval', $cat_pids ) );
			}
		}
		$pids = array_values( array_unique( array_filter( $pids ) ) );
		if ( $pids === array() ) {
			continue;
		}
		$campaigns[] = array(
			'end'      => $end,
			'priority' => $prio,
			'products' => $pids,
		);
	}

	usort(
		$campaigns,
		static function ( $a, $b ) {
			return ( (int) $b['priority'] <=> (int) $a['priority'] );
		}
	);
	$map = array();
	foreach ( $campaigns as $c ) {
		foreach ( $c['products'] as $pid ) {
			if ( $pid < 1 ) {
				continue;
			}
			if ( ! isset( $map[ $pid ] ) ) {
				$map[ $pid ] = (int) $c['end'];
			}
		}
	}

	return apply_filters( 'wcb_flash_campaigns_product_end_map', $map );
}

/**
 * Mapa em cache (transient + static por request).
 *
 * @return array<int, int>
 */
function wcb_flash_campaigns_get_product_end_map() {
	if ( isset( $GLOBALS['wcb_flash_campaigns_map_static'] ) && is_array( $GLOBALS['wcb_flash_campaigns_map_static'] ) ) {
		return $GLOBALS['wcb_flash_campaigns_map_static'];
	}
	$cached = get_transient( WCB_FLASH_CAMPAIGNS_MAP_TRANSIENT );
	if ( is_array( $cached ) ) {
		$clean = array();
		foreach ( $cached as $k => $v ) {
			$ik = (int) $k;
			$iv = (int) $v;
			if ( $ik > 0 && $iv > 0 ) {
				$clean[ $ik ] = $iv;
			}
		}
		$GLOBALS['wcb_flash_campaigns_map_static'] = $clean;
		return $clean;
	}
	$map = wcb_flash_campaigns_build_product_end_map();
	set_transient( WCB_FLASH_CAMPAIGNS_MAP_TRANSIENT, $map, WEEK_IN_SECONDS );
	$GLOBALS['wcb_flash_campaigns_map_static'] = $map;
	return $map;
}

/**
 * Data/hora ISO8601 do fim da campanha aplicável ao produto, ou null.
 *
 * @param int $product_id
 * @return string|null
 */
function wcb_get_product_flash_timer( $product_id ) {
	$product_id = (int) $product_id;
	if ( $product_id < 1 ) {
		return null;
	}
	$map = wcb_flash_campaigns_get_product_end_map();
	if ( ! isset( $map[ $product_id ] ) ) {
		return null;
	}
	$ts = (int) $map[ $product_id ];
	if ( $ts <= time() ) {
		return null;
	}
	return wp_date( 'c', $ts );
}

/**
 * Field group ACF no CPT (tipos compatíveis com ACF Free).
 */
function wcb_flash_campaigns_register_acf() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_wcb_flash_campaign_post',
			'title'                 => __( 'WCB — Campanha (timer)', 'wcb-theme' ),
			'fields'                => array(
				array(
					'key'          => 'field_wcb_fc_msg',
					'label'        => __( 'Como usar', 'wcb-theme' ),
					'name'         => '',
					'type'         => 'message',
					'message'      => __( 'Use o <strong>título</strong> para identificar a campanha. Defina a data de fim, marque <strong>Ativa</strong>, escolha produtos e/ou categorias. Maior <strong>prioridade</strong> vence quando o mesmo produto entra em várias campanhas. Com campanhas ativas no futuro, o countdown global do cabeçalho “Super Ofertas” na home é ocultado.', 'wcb-theme' ),
					'new_lines'    => 'wpautop',
					'esc_html'     => 0,
				),
				array(
					'key'            => 'field_wcb_fc_data_fim',
					'label'          => __( 'Data de fim', 'wcb-theme' ),
					'name'           => 'wcb_fc_data_fim',
					'type'           => 'date_time_picker',
					'required'       => 1,
					'display_format' => 'd/m/Y H:i',
					'return_format'  => 'Y-m-d H:i:s',
					'first_day'      => 1,
				),
				array(
					'key'           => 'field_wcb_fc_ativo',
					'label'         => __( 'Campanha ativa', 'wcb-theme' ),
					'name'          => 'wcb_fc_ativo',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				),
				array(
					'key'           => 'field_wcb_fc_prioridade',
					'label'         => __( 'Prioridade', 'wcb-theme' ),
					'name'          => 'wcb_fc_prioridade',
					'type'          => 'number',
					'default_value' => 10,
					'min'           => 0,
					'step'          => 1,
					'instructions'  => __( 'Número maior ganha se o produto couber em mais de uma campanha.', 'wcb-theme' ),
				),
				array(
					'key'            => 'field_wcb_fc_produtos',
					'label'          => __( 'Produtos', 'wcb-theme' ),
					'name'           => 'wcb_fc_produtos',
					'type'           => 'post_object',
					'post_type'      => array( 'product' ),
					'return_format'  => 'id',
					'multiple'       => 1,
					'allow_null'     => 1,
					'ui'             => 1,
				),
				array(
					'key'           => 'field_wcb_fc_categorias',
					'label'         => __( 'Categorias de produto (opcional)', 'wcb-theme' ),
					'name'          => 'wcb_fc_categorias',
					'type'          => 'taxonomy',
					'taxonomy'      => 'product_cat',
					'field_type'    => 'multi_select',
					'return_format' => 'id',
					'add_term'      => 0,
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => WCB_FLASH_CAMPAIGN_POST_TYPE,
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		)
	);
}

add_action( 'acf/init', 'wcb_flash_campaigns_register_acf' );

/**
 * Busca segura para campo ACF de produtos da campanha flash.
 *
 * Estratégia:
 * - não usa search_products do WooCommerce;
 * - não depende de wc_product_meta_lookup;
 * - sem termo `s`, devolve $args (2.ª passagem name/key não quebra post__in);
 * - busca só em post_title e _sku, com LIMIT/OFFSET alinhados ao Select2.
 *
 * @param array<string,mixed> $args    Argumentos WP_Query do ACF.
 * @param array<string,mixed> $field   Definição do campo ACF.
 * @param int|string          $post_id Post em edição (campanha).
 * @return array<string,mixed>
 */
function wcb_fc_produtos_safe_query( $args, $_field, $_post_id ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return $args;
	}

	global $wpdb;

	// Sem busca: mantém fluxo ACF; na 2.ª execução do filtro (name + key) `s` já foi removido.
	if ( empty( $args['s'] ) ) {
		return $args;
	}

	$search = trim( (string) wp_unslash( $args['s'] ) );
	if ( $search === '' ) {
		return $args;
	}

	$search = function_exists( 'wc_clean' ) ? wc_clean( $search ) : sanitize_text_field( $search );

	unset( $args['s'] );

	$args['update_post_meta_cache'] = false;
	$args['update_term_meta_cache'] = false;
	$args['no_found_rows']          = true;

	$per_page = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 20;
	$per_page = max( 1, min( 40, $per_page ) );
	$paged    = isset( $args['paged'] ) ? max( 1, (int) $args['paged'] ) : 1;
	$offset   = ( $paged - 1 ) * $per_page;

	$statuses = apply_filters(
		'woocommerce_search_products_post_statuses',
		current_user_can( 'edit_private_products' ) ? array( 'private', 'publish' ) : array( 'publish' )
	);
	$statuses = array_values(
		array_unique(
			array_intersect(
				array_map( 'sanitize_key', (array) $statuses ),
				array( 'publish', 'private' )
			)
		)
	);
	if ( $statuses === array() ) {
		$statuses = array( 'publish' );
	}
	$args['post_status'] = $statuses;
	$status_in           = "'" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "'";

	$like       = '%' . $wpdb->esc_like( $search ) . '%';
	$numeric_id = preg_match( '/^\d+$/', $search ) ? absint( $search ) : 0;

	$sql = "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
		WHERE p.post_type = 'product'
		AND p.post_status IN ($status_in)
		AND (
			p.post_title LIKE %s
			OR ( pm.meta_id IS NOT NULL AND pm.meta_value <> '' AND pm.meta_value LIKE %s )
			OR ( %d <> 0 AND p.ID = %d )
		)
		ORDER BY p.post_title ASC
		LIMIT %d OFFSET %d";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- status_in vem de lista fixa + esc_sql.
	$prepared = $wpdb->prepare( $sql, $like, $like, $numeric_id, $numeric_id, $per_page, $offset );

	$product_ids = array();
	if ( is_string( $prepared ) && $prepared !== '' ) {
		$col = $wpdb->get_col( $prepared );
		if ( $wpdb->last_error === '' && is_array( $col ) ) {
			$product_ids = array_values( array_filter( array_map( 'absint', $col ) ) );
		}
	}

	if ( $product_ids === array() ) {
		$args['post__in'] = array( 0 );
	} else {
		$args['post__in'] = $product_ids;
	}
	$args['orderby']        = 'post__in';
	$args['posts_per_page'] = $per_page;
	$args['paged']          = 1;

	return $args;
}

add_filter( 'acf/fields/post_object/query/name=wcb_fc_produtos', 'wcb_fc_produtos_safe_query', 20, 3 );
add_filter( 'acf/fields/post_object/query/key=field_wcb_fc_produtos', 'wcb_fc_produtos_safe_query', 20, 3 );

/**
 * Invalida cache ao gravar campanha.
 *
 * @param int|string $post_id
 */
function wcb_flash_campaigns_on_save_campaign( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id < 1 ) {
		return;
	}
	if ( get_post_type( $post_id ) !== WCB_FLASH_CAMPAIGN_POST_TYPE ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	wcb_flash_campaigns_bust_cache();
}

add_action( 'save_post_' . WCB_FLASH_CAMPAIGN_POST_TYPE, 'wcb_flash_campaigns_on_save_campaign', 20, 1 );

/**
 * ACF save_post (inclui importação JSON, etc.).
 *
 * @param int|string $post_id
 */
function wcb_flash_campaigns_on_acf_save_post( $post_id ) {
	if ( ! is_numeric( $post_id ) ) {
		return;
	}
	wcb_flash_campaigns_on_save_campaign( (int) $post_id );
}

add_action( 'acf/save_post', 'wcb_flash_campaigns_on_acf_save_post', 30 );
