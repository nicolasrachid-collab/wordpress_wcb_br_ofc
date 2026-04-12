<?php
/**
 * WCB Theme — Single Post template
 *
 * @package WCB_Theme
 */

get_header();
?>

<div class="wcb-container wcb-single-post-wrap">
	<?php
	while ( have_posts() ) :
		the_post();
		$reading_min = function_exists( 'wcb_get_post_reading_minutes' ) ? wcb_get_post_reading_minutes() : 1;
		?>
		<div class="wcb-post-layout">
			<?php if ( function_exists( 'wcb_render_post_breadcrumb' ) ) : ?>
				<?php wcb_render_post_breadcrumb(); ?>
			<?php endif; ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'wcb-post-article' ); ?>>
				<header class="wcb-post-header">
					<h1 class="wcb-page-title wcb-post-title"><?php the_title(); ?></h1>
					<div class="wcb-post-meta">
						<time class="wcb-post-meta__time" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
							<?php echo esc_html( get_the_date() ); ?>
						</time>
						<span class="wcb-post-meta__dot" aria-hidden="true">•</span>
						<span class="wcb-post-meta__author"><?php echo esc_html( get_the_author() ); ?></span>
						<?php if ( ! post_password_required() ) : ?>
							<span class="wcb-post-meta__dot" aria-hidden="true">•</span>
							<span class="wcb-post-meta__read">
								<?php
								/* translators: %d: estimated minutes */
								printf( esc_html__( '%d min de leitura', 'wcb-theme' ), (int) $reading_min );
								?>
							</span>
						<?php endif; ?>
					</div>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="wcb-post-featured">
						<?php
						the_post_thumbnail(
							'large',
							array(
								'class'         => 'wcb-post-featured__img',
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'decoding'      => 'async',
							)
						);
						?>
					</figure>
				<?php endif; ?>

				<div class="wcb-page-content wcb-post-content">
					<?php the_content(); ?>
				</div>
			</article>
		</div>
	<?php endwhile; ?>
</div>

<?php
get_footer();
