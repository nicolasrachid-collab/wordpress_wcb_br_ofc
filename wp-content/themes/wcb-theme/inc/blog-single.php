<?php
/**
 * Blog — post único: tempo de leitura e breadcrumb.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Estimativa de minutos de leitura (≈200 palavras/min).
 *
 * @param int|null $post_id ID do post ou null para o global $post.
 * @return int Mínimo 1, máximo 120.
 */
function wcb_get_post_reading_minutes( $post_id = null ) {
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type ) {
		return 1;
	}
	$text = wp_strip_all_tags( $post->post_content );
	if ( $text === '' ) {
		return 1;
	}
	$words = 0;
	if ( preg_match_all( '/\p{L}+/u', $text, $matches ) ) {
		$words = count( $matches[0] );
	} else {
		$words = str_word_count( $text );
	}
	$minutes = (int) ceil( $words / 200 );
	return max( 1, min( 120, $minutes ) );
}

/**
 * Breadcrumb simples: Início / Categoria / Título (sem link no atual).
 */
function wcb_render_post_breadcrumb() {
	$home_label = __( 'Início', 'wcb-theme' );
	$nav_label  = __( 'Trilha de navegação', 'wcb-theme' );

	echo '<nav class="wcb-post-breadcrumb" aria-label="' . esc_attr( $nav_label ) . '">';
	echo '<ol class="wcb-post-breadcrumb__list">';
	echo '<li class="wcb-post-breadcrumb__item"><a class="wcb-post-breadcrumb__link" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( $home_label ) . '</a></li>';

	$cats = get_the_category();
	if ( ! empty( $cats ) ) {
		$cat = $cats[0];
		echo '<li class="wcb-post-breadcrumb__item"><a class="wcb-post-breadcrumb__link" href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a></li>';
	}

	echo '<li class="wcb-post-breadcrumb__item"><span class="wcb-post-breadcrumb__current" aria-current="page">' . esc_html( get_the_title() ) . '</span></li>';
	echo '</ol></nav>';
}
