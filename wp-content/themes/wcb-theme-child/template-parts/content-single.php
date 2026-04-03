<?php
/**
 * Artigo single — header, hero, corpo, tags, navegação.
 *
 * @package WCB_Child
 */

defined( 'ABSPATH' ) || exit;

$wcb_read_mins = function_exists( 'wcb_child_reading_time_minutes' ) ? wcb_child_reading_time_minutes() : 1;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'wcb-post-article' ); ?>>
	<?php
	if ( function_exists( 'wcb_child_render_single_post_breadcrumb' ) ) {
		wcb_child_render_single_post_breadcrumb();
	}
	?>
	<header class="wcb-post-header">
		<h1 class="wcb-page-title wcb-post-title"><?php the_title(); ?></h1>
		<div class="wcb-post-meta">
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
			<span class="wcb-post-meta__sep" aria-hidden="true">&bull;</span>
			<span class="wcb-post-meta__author"><?php the_author(); ?></span>
			<span class="wcb-post-meta__sep" aria-hidden="true">&bull;</span>
			<span class="wcb-post-meta__read"><?php echo esc_html( sprintf( _n( '%d min de leitura', '%d min de leitura', $wcb_read_mins, 'wcb-child' ), $wcb_read_mins ) ); ?></span>
		</div>
	</header>

	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="wcb-post-hero">
			<?php
			the_post_thumbnail(
				'large',
				array(
					'class'    => 'wcb-post-hero__img',
					'loading'  => 'eager',
					'decoding' => 'async',
				)
			);
			?>
		</figure>
	<?php endif; ?>

	<div class="wcb-post-inner wcb-page-content">
		<?php the_content(); ?>
	</div>

	<footer class="wcb-post-footer">
		<?php
		$wcb_tags = get_the_tag_list( '<div class="wcb-post-tags">', '', '</div>' );
		if ( $wcb_tags ) {
			echo $wcb_tags; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		the_post_navigation(
			array(
				'prev_text' => '<span class="wcb-post-nav__label">' . esc_html__( 'Anterior', 'wcb-child' ) . '</span><span class="wcb-post-nav__title">%title</span>',
				'next_text' => '<span class="wcb-post-nav__label">' . esc_html__( 'Próximo', 'wcb-child' ) . '</span><span class="wcb-post-nav__title">%title</span>',
			)
		);
		?>
	</footer>
</article>
