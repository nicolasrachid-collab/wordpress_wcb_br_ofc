<?php
/**
 * Nota e contagem exibidas na loja: derivadas de comentários tipo review aprovados com estrelas,
 * não só da meta agregada do WooCommerce (pode ficar dessincronizada ou não refletir o que há em Produtos → Avaliações).
 *
 * @package WCB_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array{average: float, count: int} média com 1 casa decimal; count = reviews com rating 1–5.
 */
function wcb_get_product_rating_display_stats( $product_id ) {
	$product_id = absint( $product_id );
	$empty      = array(
		'average' => 0.0,
		'count'   => 0,
	);
	if ( $product_id < 1 || get_post_type( $product_id ) !== 'product' ) {
		return $empty;
	}

	$cache_key = 'wcb_prst_' . $product_id;
	$cached    = wp_cache_get( $cache_key, 'wcb_ratings' );
	if ( is_array( $cached ) && array_key_exists( 'average', $cached ) && array_key_exists( 'count', $cached ) ) {
		return $cached;
	}

	global $wpdb;
	$values = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT cm.meta_value FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID AND cm.meta_key = 'rating'
			WHERE c.comment_post_ID = %d AND c.comment_approved = '1' AND c.comment_type = 'review' AND c.comment_parent = 0",
			$product_id
		)
	);

	$ratings = array();
	foreach ( $values as $v ) {
		$r = (int) $v;
		if ( $r >= 1 && $r <= 5 ) {
			$ratings[] = $r;
		}
	}

	$count = count( $ratings );
	$avg   = $count > 0 ? round( array_sum( $ratings ) / $count, 1 ) : 0.0;

	$out = array(
		'average' => $avg,
		'count'   => $count,
	);
	wp_cache_set( $cache_key, $out, 'wcb_ratings', 600 );
	return $out;
}

function wcb_invalidate_product_rating_display_cache( $product_id ) {
	$product_id = absint( $product_id );
	if ( $product_id ) {
		wp_cache_delete( 'wcb_prst_' . $product_id, 'wcb_ratings' );
	}
}

function wcb_invalidate_rating_cache_from_comment_id( $comment_id ) {
	$c = get_comment( $comment_id );
	if ( ! $c || (int) $c->comment_post_ID < 1 ) {
		return;
	}
	if ( get_post_type( (int) $c->comment_post_ID ) !== 'product' ) {
		return;
	}
	wcb_invalidate_product_rating_display_cache( (int) $c->comment_post_ID );
}

add_action( 'comment_post', 'wcb_invalidate_rating_cache_from_comment_id', 30, 1 );

add_action(
	'transition_comment_status',
	static function ( $new_status, $old_status, $comment ) {
		if ( $comment instanceof WP_Comment ) {
			wcb_invalidate_rating_cache_from_comment_id( $comment->comment_ID );
		}
	},
	30,
	3
);

add_action(
	'deleted_comment',
	static function ( $comment_id, $comment ) {
		if ( $comment instanceof WP_Comment && (int) $comment->comment_post_ID > 0 ) {
			wcb_invalidate_product_rating_display_cache( (int) $comment->comment_post_ID );
			return;
		}
		wcb_invalidate_rating_cache_from_comment_id( (int) $comment_id );
	},
	30,
	2
);
