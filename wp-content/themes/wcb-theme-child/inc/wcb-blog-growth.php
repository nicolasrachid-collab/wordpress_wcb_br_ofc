<?php
/**
 * Blog — tempo de leitura, related posts, JSON-LD BlogPosting.
 *
 * @package WCB_Child
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WCB_CHILD_BLOG_JSONLD' ) ) {
	define( 'WCB_CHILD_BLOG_JSONLD', true );
}

/**
 * Tempo de leitura estimado (min), mínimo 1.
 *
 * @param int|null $post_id ID do post ou null para o atual.
 * @return int
 */
function wcb_child_reading_time_minutes( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( ! $post_id ) {
		return 1;
	}
	$raw   = get_post_field( 'post_content', $post_id );
	$words = str_word_count( wp_strip_all_tags( (string) $raw ) );
	$mins  = (int) ceil( $words / 200 );

	return max( 1, $mins );
}

/**
 * IDs de posts relacionados: mesma categoria; fallback para recentes.
 *
 * @param int $limit Quantidade (1–6).
 * @return int[]
 */
function wcb_child_related_post_ids( $limit = 3 ) {
	$current = (int) get_queried_object_id();
	if ( $current < 1 ) {
		return array();
	}
	$limit   = max( 1, min( 6, (int) $limit ) );
	$exclude = array( $current );
	$ids     = array();

	$cat_ids = wp_get_post_categories( $current );
	if ( ! empty( $cat_ids ) ) {
		$from_cat = get_posts(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => $limit,
				'post__not_in'        => $exclude,
				'category__in'        => $cat_ids,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'fields'              => 'ids',
			)
		);
		$ids = $from_cat;
	}

	if ( count( $ids ) >= $limit ) {
		return array_slice( $ids, 0, $limit );
	}

	$exclude_merge = array_merge( $exclude, $ids );
	$need          = $limit - (int) count( $ids );
	$extra         = get_posts(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $need,
			'post__not_in'        => $exclude_merge,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'fields'              => 'ids',
		)
	);

	return array_slice( array_merge( $ids, $extra ), 0, $limit );
}

/**
 * Breadcrumb do post (mesmo padrão visual do PDP: .wcb-breadcrumb + separador /).
 */
function wcb_child_render_single_post_breadcrumb() {
	$post_id = get_the_ID();
	if ( $post_id < 1 ) {
		return;
	}

	echo '<nav class="wcb-breadcrumb wcb-breadcrumb--single-post" aria-label="' . esc_attr__( 'Breadcrumb', 'wcb-child' ) . '">';
	echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Início', 'wcb-child' ) . '</a>';
	echo '<span>' . esc_html( '/' ) . '</span>';

	$posts_page_id = (int) get_option( 'page_for_posts' );
	if ( $posts_page_id > 0 ) {
		echo '<a href="' . esc_url( get_permalink( $posts_page_id ) ) . '">' . esc_html( get_the_title( $posts_page_id ) ) . '</a>';
		echo '<span>' . esc_html( '/' ) . '</span>';
	}

	$cats = get_the_category( $post_id );
	if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
		$primary = $cats[0];
		$term_link = get_term_link( $primary );
		if ( ! is_wp_error( $term_link ) ) {
			echo '<a href="' . esc_url( $term_link ) . '">' . esc_html( $primary->name ) . '</a>';
			echo '<span>' . esc_html( '/' ) . '</span>';
		}
	}

	echo '<span aria-current="page">' . esc_html( get_the_title( $post_id ) ) . '</span>';
	echo '</nav>';
}

/**
 * JSON-LD BlogPosting. Desativar se plugin SEO já emitir Article/BlogPosting:
 * define( 'WCB_CHILD_BLOG_JSONLD', false ); em wp-config.php
 */
function wcb_child_blogposting_jsonld() {
	if ( ! WCB_CHILD_BLOG_JSONLD || ! is_singular( 'post' ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( $post_id < 1 ) {
		return;
	}

	$author_id = (int) get_post_field( 'post_author', $post_id );
	$author    = get_the_author_meta( 'display_name', $author_id );

	$data = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'BlogPosting',
		'headline'         => wp_strip_all_tags( get_the_title( $post_id ) ),
		'datePublished'    => get_the_date( 'c', $post_id ),
		'dateModified'     => get_the_modified_date( 'c', $post_id ),
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => get_permalink( $post_id ),
		),
		'author'           => array(
			'@type' => 'Person',
			'name'  => $author ? $author : get_bloginfo( 'name' ),
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
		),
	);

	$thumb_id = get_post_thumbnail_id( $post_id );
	if ( $thumb_id ) {
		$url = wp_get_attachment_image_url( $thumb_id, 'large' );
		if ( $url ) {
			$data['image'] = array( $url );
		}
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'wcb_child_blogposting_jsonld', 20 );
