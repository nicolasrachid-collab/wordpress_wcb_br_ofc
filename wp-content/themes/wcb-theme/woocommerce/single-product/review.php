<?php
/**
 * Review Comments Template — WCB override: data-* para ordenação/filtro no cliente.
 *
 * @package WooCommerce\Templates
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $comment;

$rating  = $comment instanceof WP_Comment ? (int) get_comment_meta( $comment->comment_ID, 'rating', true ) : 0;
$helpful = $comment instanceof WP_Comment ? (int) get_comment_meta( $comment->comment_ID, 'wcb_review_helpful', true ) : 0;
$ts      = $comment instanceof WP_Comment ? (int) get_comment_time( 'U', true, true, $comment ) : 0;
?>
<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>"
	data-wcb-rating="<?php echo esc_attr( (string) $rating ); ?>"
	data-wcb-helpful="<?php echo esc_attr( (string) max( 0, $helpful ) ); ?>"
	data-wcb-ts="<?php echo esc_attr( (string) $ts ); ?>">

	<div id="comment-<?php comment_ID(); ?>" class="comment_container">

		<?php
		/**
		 * The woocommerce_review_before hook
		 *
		 * @hooked woocommerce_review_display_gravatar - 10
		 */
		do_action( 'woocommerce_review_before', $comment );
		?>

		<div class="comment-text">

			<?php
			/**
			 * The woocommerce_review_before_comment_meta hook.
			 *
			 * @hooked woocommerce_review_display_rating - 10
			 */
			do_action( 'woocommerce_review_before_comment_meta', $comment );

			/**
			 * The woocommerce_review_meta hook.
			 *
			 * @hooked woocommerce_review_display_meta - 10
			 */
			do_action( 'woocommerce_review_meta', $comment );

			do_action( 'woocommerce_review_before_comment_text', $comment );

			/**
			 * The woocommerce_review_comment_text hook
			 *
			 * @hooked woocommerce_review_display_comment_text - 10
			 */
			do_action( 'woocommerce_review_comment_text', $comment );

			do_action( 'woocommerce_review_after_comment_text', $comment );
			?>

		</div>
	</div>
