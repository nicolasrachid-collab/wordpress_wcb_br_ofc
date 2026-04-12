<?php
/**
 * Menu mobile — drill-down (painéis horizontais, alinhado ao menu primary).
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filhos diretos de um item de menu.
 *
 * @param array<int, WP_Post> $items       Itens de wp_get_nav_menu_items().
 * @param int                 $parent_id   menu_item_parent.
 * @return array<int, WP_Post>
 */
function wcb_mm_menu_children( array $items, $parent_id ) {
	$parent_id = (int) $parent_id;
	$out       = array();
	foreach ( $items as $it ) {
		if ( (int) $it->menu_item_parent === $parent_id ) {
			$out[] = $it;
		}
	}
	return $out;
}

/**
 * Item identificado como entrada de promoções (título / classes do menu WP).
 *
 * @param object $item Objeto do item de menu.
 * @return bool
 */
function wcb_mm_item_matches_promo_menu_entry( $item ) {
	$wp        = empty( $item->classes ) ? array() : array_filter( (array) $item->classes );
	$title_raw = isset( $item->title ) ? wp_strip_all_tags( (string) $item->title ) : '';
	$title_low = strtolower( $title_raw );

	if ( false !== strpos( $title_low, 'promo' ) ) {
		return true;
	}

	return in_array( 'wcb-mm-highlight', $wp, true )
		|| in_array( 'wcb-nav__link--promo', $wp, true );
}

/**
 * ID seguro para gradiente SVG (único por ícone no drawer).
 *
 * @param string $base Prefixo sugerido (ex. wcb-mm-1-pg-12).
 * @return string
 */
function wcb_mm_promo_svg_gradient_id( $base ) {
	$s = preg_replace( '/[^a-zA-Z0-9_-]/', '-', (string) $base );

	return '' !== $s ? $s : 'wcb-mm-promo-grad';
}

/**
 * Ícone chama (igual ao walker do menu desktop) + gradiente alinhado a .wcb-nav__link--promo.
 *
 * @param string $gradient_id Identificador único no documento.
 * @return string HTML do SVG (sem escapar — usar só com id interno).
 */
function wcb_mm_promo_menu_icon_svg( $gradient_id ) {
	$gid = wcb_mm_promo_svg_gradient_id( $gradient_id );

	return '<svg class="wcb-mm-link__icon wcb-mm-link__icon--promo" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true">'
		. '<defs><linearGradient id="' . esc_attr( $gid ) . '" x1="0%" y1="0%" x2="100%" y2="100%">'
		. '<stop offset="0%" stop-color="#dc2626"/><stop offset="50%" stop-color="#ea580c"/><stop offset="100%" stop-color="#f97316"/>'
		. '</linearGradient></defs>'
		. '<path fill="url(#' . esc_attr( $gid ) . ')" d="M13.5 0.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z"/>'
		. '</svg>';
}

/**
 * Ocultar item do drawer mobile (ex.: entradas só desktop).
 * Itens que coincidem com promo: omitidos só se o filtro `wcb_mm_show_promo_in_mobile_menu` for false (por defeito mostram-se na lista).
 *
 * @param object $item Objeto do item de menu.
 * @return bool
 */
function wcb_mm_omit_item_from_mobile_drawer( $item ) {
	if ( wcb_mm_item_matches_promo_menu_entry( $item ) ) {
		return ! (bool) apply_filters( 'wcb_mm_show_promo_in_mobile_menu', true, $item );
	}

	return (bool) apply_filters( 'wcb_mm_omit_item_from_mobile_drawer', false, $item );
}

/**
 * Conta entradas visíveis num nível de lista (mesma regra que wcb_mm_render_level_ul).
 *
 * @param array<int, WP_Post> $child_items Filhos diretos do painel.
 * @return int
 */
function wcb_mm_count_visible_child_rows( array $child_items ) {
	$n = 0;
	foreach ( $child_items as $ch ) {
		if ( ! wcb_mm_omit_item_from_mobile_drawer( $ch ) ) {
			$n++;
		}
	}
	return $n;
}

/**
 * Classes CSS do <li> — só estrutura (sem ícones/badges herdados do menu WP).
 *
 * @param object $item Objeto do item de menu.
 * @return string
 */
function wcb_mm_nav_item_li_classes( $item ) {
	$classes = array( 'wcb-mm-item' );
	$wp      = empty( $item->classes ) ? array() : array_filter( (array) $item->classes );
	$allow   = array(
		'menu-item-has-children',
		'menu-item',
		'current-menu-item',
		'current-menu-parent',
		'current-menu-ancestor',
	);

	foreach ( $wp as $c ) {
		if ( ! is_string( $c ) || '' === $c ) {
			continue;
		}
		if ( in_array( $c, $allow, true ) ) {
			$classes[] = $c;
		}
	}

	if ( wcb_mm_item_matches_promo_menu_entry( $item ) ) {
		$classes[] = 'wcb-mm-item--promo';
	}

	return implode( ' ', array_unique( array_filter( $classes ) ) );
}

/**
 * Path normalizado do pedido atual (para estado ativo dos chips).
 *
 * @return string
 */
function wcb_mm_get_request_path_normalized() {
	$path = '/';
	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$parsed = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( is_string( $parsed ) && '' !== $parsed ) {
			$path = $parsed;
		}
	}

	return trailingslashit( strtolower( $path ) );
}

/**
 * Path normalizado de um URL de chip.
 *
 * @param string $url URL completo.
 * @return string
 */
function wcb_mm_chip_url_path_normalized( $url ) {
	$p = wp_parse_url( $url, PHP_URL_PATH );
	if ( ! is_string( $p ) || '' === $p ) {
		$p = '/';
	}

	return trailingslashit( strtolower( $p ) );
}

/**
 * Chip corresponde à página atual.
 *
 * @param string $chip_url URL do chip.
 * @return bool
 */
function wcb_mm_chip_is_active( $chip_url ) {
	return wcb_mm_get_request_path_normalized() === wcb_mm_chip_url_path_normalized( $chip_url );
}

/**
 * URL do arquivo de promoções (mesma regra que o mega / header).
 *
 * @return string
 */
function wcb_mm_promocoes_archive_url() {
	$slug = apply_filters( 'wcb_promo_dropdown_category_slug', 'promocoes' );
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term && ! is_wp_error( $term ) ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}

	return home_url( '/categoria-produto/promocoes/' );
}

/**
 * Título do item de menu para o drawer (filtro correto para menus WP).
 *
 * @param object $item           Objeto de item de menu.
 * @param string $theme_location Localização registada.
 * @return string
 */
function wcb_mm_filtered_item_title( $item, $theme_location ) {
	$args = (object) array(
		'theme_location' => $theme_location,
		'context'        => 'wcb_mobile_drilldown',
	);
	return (string) apply_filters( 'nav_menu_item_title', $item->title, $item, $args, 0 );
}

/**
 * URL e rótulo de Promoções (categoria / archive) — usado se o menu não tiver entrada equivalente.
 *
 * @return array{url: string, label: string}|null Null se o URL for inválido.
 */
function wcb_mm_root_promo_cta_config() {
	$url = wcb_mm_promocoes_archive_url();
	$url = (string) apply_filters( 'wcb_mm_root_promo_cta_url', $url );
	if ( '' === $url || '#' === $url ) {
		return null;
	}

	$label = (string) apply_filters( 'wcb_mm_root_promo_cta_label', __( 'Promoções', 'wcb-theme' ) );

	return array(
		'url'   => $url,
		'label' => $label,
	);
}

/**
 * Barra de busca no painel raiz.
 */
function wcb_mm_root_search_markup() {
	$action = home_url( '/' );
	if ( class_exists( 'WooCommerce' ) ) {
		$shop = wc_get_page_permalink( 'shop' );
		if ( $shop ) {
			$action = $shop;
		}
	}
	$uid = 'wcb-mm-search-' . wp_unique_id();
	?>
	<div class="wcb-mm-search" role="search">
		<form class="wcb-mm-search__form" action="<?php echo esc_url( $action ); ?>" method="get">
			<?php if ( class_exists( 'WooCommerce' ) && $action === wc_get_page_permalink( 'shop' ) ) : ?>
				<input type="hidden" name="post_type" value="product" />
			<?php endif; ?>
			<label class="wcb-mm-search__label" for="<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Buscar na loja', 'wcb-theme' ); ?></label>
			<div class="wcb-mm-search__field">
			<input id="<?php echo esc_attr( $uid ); ?>" class="wcb-mm-search__input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr__( 'O que procura?', 'wcb-theme' ); ?>" autocomplete="off" />
			<button type="submit" class="wcb-mm-search__submit" aria-label="<?php echo esc_attr__( 'Buscar', 'wcb-theme' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
			</button>
			</div>
		</form>
	</div>
	<?php
}

/**
 * Há no nível raiz do menu um item visível identificado como promoções?
 *
 * @param array<int, WP_Post> $items Itens de wp_get_nav_menu_items().
 * @return bool
 */
function wcb_mm_root_has_visible_promo_menu_item( array $items ) {
	foreach ( wcb_mm_menu_children( $items, 0 ) as $item ) {
		if ( wcb_mm_omit_item_from_mobile_drawer( $item ) ) {
			continue;
		}
		if ( wcb_mm_item_matches_promo_menu_entry( $item ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Secção fixa inferior: atalho Conta (copy conforme sessão).
 */
function wcb_mm_root_footer_markup() {
	$account_url = class_exists( 'WooCommerce' ) ? wc_get_account_endpoint_url( 'dashboard' ) : wp_login_url();
	$logged_in     = is_user_logged_in();
	$label         = $logged_in
		? __( 'Acessar minha conta', 'wcb-theme' )
		: __( 'Faça seu login ou cadastre-se', 'wcb-theme' );
	?>
	<div class="wcb-mm-root-footer">
		<a class="wcb-mm-root-footer__account" href="<?php echo esc_url( $account_url ); ?>">
			<span class="wcb-mm-root-footer__account-icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
			</span>
			<span class="wcb-mm-root-footer__account-label"><?php echo esc_html( $label ); ?></span>
		</a>
	</div>
	<?php
}

/**
 * Título + subtítulo do drawer (renderizar dentro de .wcb-mobile-menu__header).
 *
 * Copy dinâmica: logado (saudação + nome) vs visitante (CTA conta), com classes de estado para CSS.
 * O diálogo #wcb-mobile-menu usa aria-labelledby="wcb-mobile-menu-heading" neste &lt;h2&gt;.
 */
function wcb_mm_drawer_header_cap_markup() {
	$wrapper_class = 'wcb-mm-root-cap__head wcb-mobile-menu__header-text';
	$logged_in     = is_user_logged_in();

	if ( $logged_in ) {
		$wrapper_class .= ' wcb-mobile-menu__header-text--logged-in';
		$user      = wp_get_current_user();
		$first_raw = get_user_meta( $user->ID, 'first_name', true );
		$name      = is_string( $first_raw ) ? trim( $first_raw ) : '';

		if ( '' === $name && ! empty( $user->display_name ) ) {
			$name = trim( $user->display_name );
		}
		if ( '' === $name ) {
			$name = $user->user_login;
		}

		$title    = sprintf(
			/* translators: %s: user first name, display name, or login */
			__( 'Olá, %s', 'wcb-theme' ),
			$name
		);
		$subtitle = __( 'Acompanhe seus pedidos, aproveite ofertas e encontre seus favoritos.', 'wcb-theme' );
	} else {
		$wrapper_class .= ' wcb-mobile-menu__header-text--guest';
		$title    = __( 'Entre ou crie sua conta', 'wcb-theme' );
		$subtitle = __( 'Desbloqueie ofertas e compre mais rápido.', 'wcb-theme' );
	}
	?>
	<div class="<?php echo esc_attr( $wrapper_class ); ?>">
		<h2 id="wcb-mobile-menu-heading" class="wcb-mm-root-cap__title">
			<?php echo esc_html( $title ); ?>
			<?php if ( $logged_in ) : ?>
				<span class="wcb-mobile-menu__heading-wave" aria-hidden="true"> 👋</span>
			<?php endif; ?>
		</h2>
		<p class="wcb-mm-root-cap__sub"><?php echo esc_html( $subtitle ); ?></p>
	</div>
	<?php
}

/**
 * Lista de painéis em pré-ordem (pai antes dos descendentes).
 *
 * @param array<int, WP_Post> $items
 * @param int                 $parent_id
 * @return array<int, array{parent: WP_Post, child_items: array<int, WP_Post>}>
 */
function wcb_mm_preorder_panels( array $items, $parent_id = 0 ) {
	$out = array();
	foreach ( wcb_mm_menu_children( $items, $parent_id ) as $item ) {
		if ( wcb_mm_omit_item_from_mobile_drawer( $item ) ) {
			continue;
		}
		$sk = wcb_mm_menu_children( $items, $item->ID );
		if ( empty( $sk ) ) {
			continue;
		}
		$out[] = array(
			'parent'      => $item,
			'child_items' => $sk,
		);
		$out   = array_merge( $out, wcb_mm_preorder_panels( $items, $item->ID ) );
	}
	return $out;
}

/**
 * Gera <ul> para um nível (links simples + linhas com submenu).
 *
 * @param array<int, WP_Post> $items            Itens de wp_get_nav_menu_items().
 * @param int                 $parent_id        menu_item_parent.
 * @param string              $mm_uid           Prefixo único por instância (wp_unique_id).
 * @param string              $theme_location   Localização do menu (filtros).
 */
function wcb_mm_render_level_ul( array $items, $parent_id, $mm_uid, $theme_location = 'primary' ) {
	$html     = '<ul class="wcb-mobile-menu__list wcb-mm-list" role="list">';
	$children = wcb_mm_menu_children( $items, $parent_id );

	if ( 0 === (int) $parent_id ) {
		$promo_cfg = wcb_mm_root_promo_cta_config();
		if ( null !== $promo_cfg && ! wcb_mm_root_has_visible_promo_menu_item( $items ) ) {
			$promo_url    = esc_url( $promo_cfg['url'] );
			$promo_label  = esc_html( $promo_cfg['label'] );
			$promo_active = wcb_mm_chip_is_active( $promo_cfg['url'] ) ? ' is-active' : '';
			$html        .= '<li class="wcb-mm-item wcb-mm-item--promo menu-item"><div class="wcb-mm-row wcb-mm-row--leaf">';
			$html        .= '<a class="wcb-mm-link wcb-mm-link--leaf wcb-mm-link--promo' . $promo_active . '" href="' . $promo_url . '">';
			$html        .= wcb_mm_promo_menu_icon_svg( $mm_uid . '-pg-inject' );
			$html        .= '<span class="wcb-mm-link__label">' . $promo_label . '</span></a>';
			$html        .= '</div></li>';
		}
	}

	foreach ( $children as $item ) {
		if ( wcb_mm_omit_item_from_mobile_drawer( $item ) ) {
			continue;
		}

		$title   = wcb_mm_filtered_item_title( $item, $theme_location );
		$href    = ! empty( $item->url ) ? $item->url : '#';
		$url     = esc_url( $href );
		$sk        = wcb_mm_menu_children( $items, $item->ID );
		$li_base  = wcb_mm_nav_item_li_classes( $item );
		$is_promo = wcb_mm_item_matches_promo_menu_entry( $item );

		if ( empty( $sk ) ) {
			$html .= '<li class="' . esc_attr( $li_base ) . '">';
			$html .= '<div class="wcb-mm-row wcb-mm-row--leaf">';
			$html .= '<a class="wcb-mm-link wcb-mm-link--leaf' . ( $is_promo ? ' wcb-mm-link--promo' : '' ) . '" href="' . $url . '">';
			if ( $is_promo ) {
				$html .= wcb_mm_promo_menu_icon_svg( $mm_uid . '-pg-' . (int) $item->ID );
				$html .= '<span class="wcb-mm-link__label">' . esc_html( $title ) . '</span>';
			} else {
				$html .= esc_html( $title );
			}
			$html .= '</a>';
			$html .= '</div></li>';
			continue;
		}

		$liclass = $li_base;
		if ( false === strpos( ' ' . $li_base . ' ', ' menu-item-has-children ' ) ) {
			$liclass .= ' menu-item-has-children';
		}
		$pid          = $mm_uid . '-p-' . (int) $item->ID;
		$label_plain  = wp_strip_all_tags( $title );
		/* translators: %s: menu item title */
		$label_open = sprintf( __( '%s, abrir subcategorias', 'wcb-theme' ), $label_plain );

		$html .= '<li class="' . esc_attr( $liclass ) . '">';
		$html .= '<div class="wcb-mm-row wcb-mm-row--branch">';
		$html .= '<a class="wcb-mm-link wcb-mm-link--branch' . ( $is_promo ? ' wcb-mm-link--promo' : '' ) . '" href="' . $url . '">';
		if ( $is_promo ) {
			$html .= wcb_mm_promo_menu_icon_svg( $mm_uid . '-pg-' . (int) $item->ID );
			$html .= '<span class="wcb-mm-link__label">' . esc_html( $title ) . '</span>';
		} else {
			$html .= esc_html( $title );
		}
		$html .= '</a>';
		$html .= '<button type="button" class="wcb-mm-next" aria-expanded="false" aria-controls="' . esc_attr( $pid ) . '" aria-label="' . esc_attr( $label_open ) . '">';
		$html .= '<span class="wcb-mm-next__inner" aria-hidden="true">';
		$html .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>';
		$html .= '</span></button>';
		$html .= '</div></li>';
	}

	$html .= '</ul>';
	return $html;
}

/**
 * HTML completo do drill-down (viewport + track + painéis).
 *
 * @param string $theme_location Localização do menu.
 * @return string
 */
function wcb_mobile_drilldown_menu_html( $theme_location = 'primary' ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $theme_location ] ) ) {
		return '';
	}

	$menu = wp_get_nav_menu_object( $locations[ $theme_location ] );
	if ( ! $menu ) {
		return '';
	}

	$items = wp_get_nav_menu_items( $menu->term_id, array( 'orderby' => 'menu_order' ) );
	if ( empty( $items ) || ! is_array( $items ) ) {
		return '';
	}

	$panels_data = wcb_mm_preorder_panels( $items, 0 );

	// Instância única por render (evita colisão de id se houver mais de um drill-down na página).
	$mm_uid = 'wcb-mm-' . wp_unique_id();

	// Índice no track: 0 = root, 1..n = painéis na mesma ordem que $panels_data.
	$root_ul = wcb_mm_render_level_ul( $items, 0, $mm_uid, $theme_location );

	$panels_html = '';
	foreach ( $panels_data as $row ) {
		$parent = $row['parent'];
		$pid    = $mm_uid . '-p-' . (int) $parent->ID;
		$title  = wcb_mm_filtered_item_title( $parent, $theme_location );
		$purl   = ! empty( $parent->url ) ? esc_url( $parent->url ) : '';

		$panels_html .= '<div class="wcb-mm-panel wcb-mm-panel--sub" id="' . esc_attr( $pid ) . '" role="region" aria-hidden="true" tabindex="-1">';
		$panels_html .= '<div class="wcb-mm-subhead">';
		$panels_html .= '<button type="button" class="wcb-mm-back" aria-label="' . esc_attr__( 'Voltar', 'wcb-theme' ) . '">';
		$panels_html .= '<span class="wcb-mm-back__inner" aria-hidden="true">';
		$panels_html .= '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>';
		$panels_html .= '</span><span class="wcb-mm-back__text screen-reader-text">' . esc_html__( 'Voltar', 'wcb-theme' ) . '</span>';
		$panels_html .= '</button>';
		$visible_count = wcb_mm_count_visible_child_rows( $row['child_items'] );
		$panels_html .= '<span class="wcb-mm-subhead__title-wrap">';
		$panels_html .= '<span class="wcb-mm-subhead__breadcrumb" aria-hidden="true">';
		$panels_html .= '<span class="wcb-mm-subhead__breadcrumb-root">' . esc_html__( 'Menu', 'wcb-theme' ) . '</span>';
		$panels_html .= '<span class="wcb-mm-subhead__breadcrumb-sep">›</span>';
		$panels_html .= '</span>';
		$panels_html .= '<span class="wcb-mm-subhead__title-line">';
		$panels_html .= '<span class="wcb-mm-subhead__title">' . esc_html( $title ) . '</span>';
		if ( $visible_count > 0 ) {
			$panels_html .= '<span class="wcb-mm-subhead__count" aria-hidden="true">' . esc_html( (string) (int) $visible_count ) . '</span>';
		}
		$panels_html .= '</span></span>';
		$panels_html .= '</div>';
		$panels_html .= '<div class="wcb-mm-scroll">';
		$panels_html .= wcb_mm_render_level_ul( $items, (int) $parent->ID, $mm_uid, $theme_location );
		$panels_html .= '</div>';
		if ( $purl && ! empty( $parent->url ) && '#' !== $parent->url ) {
			/* translators: %s: parent category or menu title */
			$ver_label = sprintf( __( 'Ver todos em %s', 'wcb-theme' ), wp_strip_all_tags( $title ) );
			$panels_html .= '<div class="wcb-mm-ver-todos-wrap">';
			$panels_html .= '<a class="wcb-mm-ver-todos" href="' . $purl . '">';
			$panels_html .= '<span class="wcb-mm-ver-todos__text">' . esc_html( $ver_label ) . '</span>';
			$panels_html .= '<svg class="wcb-mm-ver-todos__icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
			$panels_html .= '</a></div>';
		}
		$panels_html .= '</div>';
	}

	$root_panel_id = $mm_uid . '-root';
	ob_start();
	?>
	<div class="wcb-mm" data-wcb-mm>
		<div class="wcb-mm-viewport">
			<div class="wcb-mm-track">
				<div class="wcb-mm-panel wcb-mm-panel--root" id="<?php echo esc_attr( $root_panel_id ); ?>" role="group" aria-label="<?php echo esc_attr__( 'Menu principal', 'wcb-theme' ); ?>" aria-hidden="false">
					<?php wcb_mm_root_search_markup(); ?>
					<div class="wcb-mm-scroll">
					<?php echo $root_ul; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<?php wcb_mm_root_footer_markup(); ?>
				</div>
				<?php echo $panels_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Fallback quando não há menu primary.
 */
function wcb_mobile_drilldown_fallback_html() {
	$mm_uid          = 'wcb-mm-' . wp_unique_id();
	$root_panel_id   = $mm_uid . '-root';
	ob_start();
	?>
	<div class="wcb-mm" data-wcb-mm>
		<div class="wcb-mm-viewport">
			<div class="wcb-mm-track">
				<div class="wcb-mm-panel wcb-mm-panel--root" id="<?php echo esc_attr( $root_panel_id ); ?>" role="group" aria-label="<?php echo esc_attr__( 'Menu principal', 'wcb-theme' ); ?>" aria-hidden="false">
					<?php wcb_mm_root_search_markup(); ?>
					<div class="wcb-mm-scroll">
					<ul class="wcb-mobile-menu__list wcb-mm-list" role="list">
						<li class="wcb-mm-item menu-item"><div class="wcb-mm-row wcb-mm-row--leaf"><a class="wcb-mm-link wcb-mm-link--leaf" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Início', 'wcb-theme' ); ?></a></div></li>
						<?php
						$wcb_mm_fb_promo = wcb_mm_root_promo_cta_config();
						if ( null !== $wcb_mm_fb_promo ) :
							?>
						<li class="wcb-mm-item wcb-mm-item--promo menu-item"><div class="wcb-mm-row wcb-mm-row--leaf"><a class="wcb-mm-link wcb-mm-link--leaf wcb-mm-link--promo<?php echo wcb_mm_chip_is_active( $wcb_mm_fb_promo['url'] ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $wcb_mm_fb_promo['url'] ); ?>"><?php echo wcb_mm_promo_menu_icon_svg( $mm_uid . '-fb-promo' ); ?><span class="wcb-mm-link__label"><?php echo esc_html( $wcb_mm_fb_promo['label'] ); ?></span></a></div></li>
						<?php endif; ?>
						<li class="wcb-mm-item menu-item"><div class="wcb-mm-row wcb-mm-row--leaf"><a class="wcb-mm-link wcb-mm-link--leaf" href="<?php echo esc_url( home_url( '/loja/' ) ); ?>"><?php esc_html_e( 'Loja', 'wcb-theme' ); ?></a></div></li>
						<?php if ( class_exists( 'WooCommerce' ) ) : ?>
							<li class="wcb-mm-item menu-item"><div class="wcb-mm-row wcb-mm-row--leaf"><a class="wcb-mm-link wcb-mm-link--leaf" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Carrinho', 'wcb-theme' ); ?></a></div></li>
							<li class="wcb-mm-item menu-item"><div class="wcb-mm-row wcb-mm-row--leaf"><a class="wcb-mm-link wcb-mm-link--leaf" href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>"><?php esc_html_e( 'Minha Conta', 'wcb-theme' ); ?></a></div></li>
						<?php endif; ?>
					</ul>
					</div>
					<?php wcb_mm_root_footer_markup(); ?>
				</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
