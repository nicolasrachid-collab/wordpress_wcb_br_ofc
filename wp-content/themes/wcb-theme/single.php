<?php
/**
 * WCB Theme — Single Post template
 *
 * @package WCB_Theme
 */

get_header();
?>

<div class="wcb-container">
    <?php while (have_posts()):
        the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <h1 class="wcb-page-title">
                <?php the_title(); ?>
            </h1>
            <div class="wcb-post-meta" style="font-size: 0.8rem; color: var(--wcb-gray-500); margin-bottom: 1.5rem;">
                <time datetime="<?php echo get_the_date('c'); ?>">
                    <?php echo get_the_date(); ?>
                </time>
                &bull;
                <?php the_author(); ?>
            </div>
            <?php if (has_post_thumbnail()): ?>
                <div style="margin-bottom: 1.5rem; border-radius: var(--wcb-radius-lg); overflow: hidden;">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            <div class="wcb-page-content">
                <?php the_content(); ?>
            </div>
        </article>
    <?php endwhile; ?>
</div>

<?php
get_footer();
