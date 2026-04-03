<?php
/**
 * Posts relacionados (categoria + fallback).
 *
 * @package WCB_Child
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wcb_child_related_post_ids' ) ) {
	return;
}

$wcb_related_ids = wcb_child_related_post_ids( 3 );
if ( empty( $wcb_related_ids ) ) {
	return;
}

$wcb_q = new WP_Query(
	array(
		'post_type'           => 'post',
		'post__in'            => $wcb_related_ids,
		'orderby'             => 'post__in',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( ! $wcb_q->have_posts() ) {
	return;
}
?>

<section class="wcb-post-related" aria-labelledby="wcb-related-heading">
	<h2 id="wcb-related-heading" class="wcb-post-related__title">
		<span class="wcb-post-related__kicker"><?php esc_html_e( 'Continue lendo', 'wcb-child' ); ?></span>
		<span class="wcb-post-related__headline"><?php esc_html_e( 'Leia também', 'wcb-child' ); ?></span>
	</h2>
	<div class="wcb-posts-grid wcb-post-related__grid">
		<?php
		while ( $wcb_q->have_posts() ) {
			$wcb_q->the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'wcb-card wcb-post-related__card' ); ?>>
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="wcb-card__image">
						<a href="<?php the_permalink(); ?>">
							<?php the_post_thumbnail( 'medium_large' ); ?>
						</a>
					</div>
				<?php endif; ?>
				<div class="wcb-card__body wcb-post-related__body">
					<h3 class="wcb-post-related__card-title">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h3>
					<p class="wcb-post-related__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
				</div>
			</article>
			<?php
		}
		wp_reset_postdata();
		?>
	</div>
</section>
