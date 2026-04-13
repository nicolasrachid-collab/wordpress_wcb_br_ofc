<?php
/**
 * Admin: atalhos “Mais pesquisados” + rodapé WhatsApp do menu mobile (Aparência → Atalhos menu mobile).
 *
 * Persistência: `wcb_mm_quick_buy_items` (atalhos), `wcb_mm_drawer_footer` (URL/texto rodapé).
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var string */
const WCB_MM_QUICK_BUY_OPTION = 'wcb_mm_quick_buy_items';

/** @var string Rodapé do drawer: URL WhatsApp + rótulo opcional. */
const WCB_MM_DRAWER_FOOTER_OPTION = 'wcb_mm_drawer_footer';

/**
 * Valores guardados do rodapé do drawer (front + admin).
 *
 * @return array{url: string, label: string}
 */
function wcb_mm_drawer_footer_get_stored() {
	$raw = get_option( WCB_MM_DRAWER_FOOTER_OPTION, array() );
	if ( ! is_array( $raw ) ) {
		return array(
			'url'   => '',
			'label' => '',
		);
	}
	return array(
		'url'   => isset( $raw['url'] ) ? (string) $raw['url'] : '',
		'label' => isset( $raw['label'] ) ? (string) $raw['label'] : '',
	);
}

/**
 * Sanitiza opção do rodapé.
 *
 * @param mixed $value Input.
 * @return array{url: string, label: string}
 */
function wcb_mm_drawer_footer_sanitize_option( $value ) {
	if ( ! is_array( $value ) ) {
		return array(
			'url'   => '',
			'label' => '',
		);
	}
	$url = isset( $value['url'] ) ? esc_url_raw( (string) wp_unslash( $value['url'] ) ) : '';
	$label = isset( $value['label'] ) ? sanitize_text_field( (string) wp_unslash( $value['label'] ) ) : '';
	return array(
		'url'   => $url,
		'label' => $label,
	);
}

/**
 * Regista página em Aparência.
 */
function wcb_mm_quick_buy_admin_menu() {
	add_theme_page(
		__( 'Atalhos menu mobile', 'wcb-theme' ),
		__( 'Atalhos menu mobile', 'wcb-theme' ),
		'edit_theme_options',
		'wcb-mobile-menu-quick-buy',
		'wcb_mm_quick_buy_render_admin_page'
	);
}
add_action( 'admin_menu', 'wcb_mm_quick_buy_admin_menu' );

/**
 * No front: só substitui os padrões quando a opção foi guardada pelo menos uma vez.
 *
 * @param array<int, array{label: string, slug: string}> $defaults Itens padrão do tema.
 * @return array<int, array{label: string, slug?: string, url?: string}>
 */
function wcb_mm_quick_buy_filter_from_option( $defaults ) {
	if ( null === get_option( WCB_MM_QUICK_BUY_OPTION, null ) ) {
		return $defaults;
	}
	$saved = get_option( WCB_MM_QUICK_BUY_OPTION, array() );
	return is_array( $saved ) ? $saved : $defaults;
}
add_filter( 'wcb_mm_root_quick_buy_items', 'wcb_mm_quick_buy_filter_from_option', 5 );

/**
 * Sanitiza o array guardado nas opções.
 *
 * @param mixed $value Input.
 * @return array<int, array{label: string, slug?: string, url?: string}>
 */
function wcb_mm_quick_buy_sanitize_option( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}
	$out = array();
	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$label = isset( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : '';
		$url   = isset( $row['url'] ) ? esc_url_raw( (string) $row['url'] ) : '';
		$slug  = isset( $row['slug'] ) ? sanitize_title( (string) $row['slug'] ) : '';
		if ( '' === $label ) {
			continue;
		}
		if ( '' !== $url && '#' !== $url ) {
			$out[] = array(
				'label' => $label,
				'url'   => $url,
			);
		} elseif ( '' !== $slug ) {
			$out[] = array(
				'label' => $label,
				'slug'  => $slug,
			);
		}
	}
	return array_slice( $out, 0, 6 );
}

/**
 * Restaurar padrões do tema (apaga a opção).
 */
function wcb_mm_quick_buy_handle_reset() {
	if ( ! is_admin() || ! isset( $_POST['wcb_mm_quick_buy_reset'] ) ) {
		return;
	}
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wcb_mm_quick_buy_reset' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	delete_option( WCB_MM_QUICK_BUY_OPTION );
	delete_option( WCB_MM_DRAWER_FOOTER_OPTION );
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'  => 'wcb-mobile-menu-quick-buy',
				'reset' => '1',
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'wcb_mm_quick_buy_handle_reset' );

/**
 * Guardar formulário principal.
 */
function wcb_mm_quick_buy_handle_save() {
	if ( ! is_admin() || ! isset( $_POST['wcb_mm_quick_buy_save'] ) ) {
		return;
	}
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wcb_mm_quick_buy_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$raw = isset( $_POST['wcb_mm_quick_buy'] ) ? wp_unslash( $_POST['wcb_mm_quick_buy'] ) : array();
	if ( ! is_array( $raw ) ) {
		$raw = array();
	}
	$clean = wcb_mm_quick_buy_sanitize_option( $raw );
	update_option( WCB_MM_QUICK_BUY_OPTION, $clean );

	$footer_raw = isset( $_POST['wcb_mm_drawer_footer'] ) ? wp_unslash( $_POST['wcb_mm_drawer_footer'] ) : array();
	$footer_clean = wcb_mm_drawer_footer_sanitize_option( $footer_raw );
	update_option( WCB_MM_DRAWER_FOOTER_OPTION, $footer_clean );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'  => 'wcb-mobile-menu-quick-buy',
				'saved' => '1',
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'wcb_mm_quick_buy_handle_save' );

/**
 * Linhas do formulário (até 4 visíveis).
 *
 * @return array<int, array{label: string, slug: string, url: string}>
 */
function wcb_mm_quick_buy_admin_form_rows() {
	$saved = get_option( WCB_MM_QUICK_BUY_OPTION, null );
	$empty = array( 'label' => '', 'slug' => '', 'url' => '' );

	if ( null === $saved ) {
		$rows = wcb_mm_root_quick_buy_default_items();
		foreach ( $rows as $i => $r ) {
			$rows[ $i ] = array(
				'label' => isset( $r['label'] ) ? (string) $r['label'] : '',
				'slug'  => isset( $r['slug'] ) ? (string) $r['slug'] : '',
				'url'   => isset( $r['url'] ) ? (string) $r['url'] : '',
			);
		}
	} elseif ( is_array( $saved ) && empty( $saved ) ) {
		$rows = array();
	} else {
		$rows = array();
		foreach ( (array) $saved as $r ) {
			if ( ! is_array( $r ) ) {
				continue;
			}
			$rows[] = array(
				'label' => isset( $r['label'] ) ? (string) $r['label'] : '',
				'slug'  => isset( $r['slug'] ) ? (string) $r['slug'] : '',
				'url'   => isset( $r['url'] ) ? (string) $r['url'] : '',
			);
		}
	}

	while ( count( $rows ) < 4 ) {
		$rows[] = $empty;
	}

	return array_slice( $rows, 0, 4 );
}

/**
 * Conteúdo da página de opções.
 */
function wcb_mm_quick_buy_render_admin_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( isset( $_GET['saved'] ) && '1' === $_GET['saved'] ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Definições guardadas.', 'wcb-theme' ) . '</p></div>';
	}
	if ( isset( $_GET['reset'] ) && '1' === $_GET['reset'] ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Atalhos padrão restaurados e configuração do rodapé WhatsApp removida.', 'wcb-theme' ) . '</p></div>';
	}

	$rows         = wcb_mm_quick_buy_admin_form_rows();
	$has_db       = null !== get_option( WCB_MM_QUICK_BUY_OPTION, null );
	$footer_store = wcb_mm_drawer_footer_get_stored();
	$footer_url   = isset( $footer_store['url'] ) ? (string) $footer_store['url'] : '';
	$footer_label = isset( $footer_store['label'] ) ? (string) $footer_store['label'] : '';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Atalhos menu mobile', 'wcb-theme' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Define até quatro atalhos do bloco “Mais pesquisados” no painel raiz do menu lateral (telefone), o link do grupo WhatsApp no rodapé do drawer e, se quiser, um texto personalizado para esse botão. O drill-down do menu principal não é alterado.', 'wcb-theme' ); ?>
		</p>
		<?php if ( ! $has_db ) : ?>
			<p class="description">
				<strong><?php esc_html_e( 'Nota:', 'wcb-theme' ); ?></strong>
				<?php esc_html_e( 'Ainda não guardou nenhuma configuração: o site usa os padrões do tema. Ao guardar, passa a usar apenas as linhas abaixo (podem ficar vazias para ocultar o bloco).', 'wcb-theme' ); ?>
			</p>
		<?php endif; ?>

		<form method="post" action="" style="max-width: 920px; margin-top: 1rem;">
			<?php wp_nonce_field( 'wcb_mm_quick_buy_save' ); ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col" style="width: 22%;"><?php esc_html_e( 'Rótulo', 'wcb-theme' ); ?></th>
						<th scope="col" style="width: 22%;"><?php esc_html_e( 'Slug da categoria', 'wcb-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'URL personalizada (opcional)', 'wcb-theme' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $i => $row ) : ?>
					<tr>
						<td>
							<label class="screen-reader-text" for="wcb-qb-label-<?php echo esc_attr( (string) $i ); ?>"><?php esc_html_e( 'Rótulo', 'wcb-theme' ); ?></label>
							<input type="text" class="large-text" id="wcb-qb-label-<?php echo esc_attr( (string) $i ); ?>" name="wcb_mm_quick_buy[<?php echo esc_attr( (string) $i ); ?>][label]" value="<?php echo esc_attr( $row['label'] ); ?>" autocomplete="off" />
						</td>
						<td>
							<label class="screen-reader-text" for="wcb-qb-slug-<?php echo esc_attr( (string) $i ); ?>"><?php esc_html_e( 'Slug', 'wcb-theme' ); ?></label>
							<input type="text" class="large-text code" id="wcb-qb-slug-<?php echo esc_attr( (string) $i ); ?>" name="wcb_mm_quick_buy[<?php echo esc_attr( (string) $i ); ?>][slug]" value="<?php echo esc_attr( $row['slug'] ); ?>" placeholder="ex.: pods-descartaveis" autocomplete="off" />
						</td>
						<td>
							<label class="screen-reader-text" for="wcb-qb-url-<?php echo esc_attr( (string) $i ); ?>"><?php esc_html_e( 'URL', 'wcb-theme' ); ?></label>
							<input type="url" class="large-text" id="wcb-qb-url-<?php echo esc_attr( (string) $i ); ?>" name="wcb_mm_quick_buy[<?php echo esc_attr( (string) $i ); ?>][url]" value="<?php echo esc_attr( $row['url'] ); ?>" placeholder="https://…" autocomplete="off" />
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">
				<?php esc_html_e( 'Se preencher a URL, ela tem prioridade sobre o slug. O slug deve corresponder a uma categoria de produto (product_cat). No site são usados no máximo quatro atalhos com link válido.', 'wcb-theme' ); ?>
			</p>

			<h2 class="title" style="margin-top: 2rem;"><?php esc_html_e( 'Rodapé do menu mobile (WhatsApp)', 'wcb-theme' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Link do convite do grupo (ex.: chat.whatsapp.com/…). Se deixar o texto em branco, usa-se a frase padrão do tema. Estes valores têm prioridade sobre o Personalizar (Newsletter) quando preenchidos.', 'wcb-theme' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="wcb-mm-drawer-footer-url"><?php esc_html_e( 'URL do grupo WhatsApp', 'wcb-theme' ); ?></label>
					</th>
					<td>
						<input type="url" class="large-text" id="wcb-mm-drawer-footer-url" name="wcb_mm_drawer_footer[url]" value="<?php echo esc_attr( $footer_url ); ?>" placeholder="https://chat.whatsapp.com/…" autocomplete="off" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wcb-mm-drawer-footer-label"><?php esc_html_e( 'Texto do botão (opcional)', 'wcb-theme' ); ?></label>
					</th>
					<td>
						<input type="text" class="large-text" id="wcb-mm-drawer-footer-label" name="wcb_mm_drawer_footer[label]" value="<?php echo esc_attr( $footer_label ); ?>" placeholder="<?php echo esc_attr( __( 'Entre no grupo VIP do WhatsApp e receba ofertas exclusivas', 'wcb-theme' ) ); ?>" autocomplete="off" />
					</td>
				</tr>
			</table>

			<p>
				<button type="submit" name="wcb_mm_quick_buy_save" class="button button-primary" value="1"><?php esc_html_e( 'Guardar alterações', 'wcb-theme' ); ?></button>
			</p>
		</form>

		<hr />

		<form method="post" action="" onsubmit="return confirm('<?php echo esc_js( __( 'Restaurar os padrões do tema? Atalhos, URL do WhatsApp no rodapé e texto personalizado guardados serão apagados.', 'wcb-theme' ) ); ?>');">
			<?php wp_nonce_field( 'wcb_mm_quick_buy_reset' ); ?>
			<p>
				<button type="submit" name="wcb_mm_quick_buy_reset" class="button" value="1"><?php esc_html_e( 'Restaurar padrões do tema', 'wcb-theme' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}
