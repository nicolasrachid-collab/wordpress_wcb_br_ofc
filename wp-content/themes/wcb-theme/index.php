<?php
/**
 * WCB Theme — Main template
 *
 * @package WCB_Theme
 */

get_header();
?>

<div class="wcb-container">
    <?php if (have_posts()): ?>
        <div class="wcb-posts-grid">
            <?php while (have_posts()):
                the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('wcb-card'); ?>>
                    <?php if (has_post_thumbnail()): ?>
                        <div class="wcb-card__image">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium_large'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="wcb-card__body" style="padding: 1.2rem;">
                        <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;">
                            <a href="<?php the_permalink(); ?>" style="color: var(--wcb-gray-900); text-decoration: none;">
                                <?php the_title(); ?>
                            </a>
                        </h2>
                        <p style="font-size: 0.85rem; color: var(--wcb-gray-500); line-height: 1.5;">
                            <?php the_excerpt(); ?>
                        </p>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <?php the_posts_navigation(); ?>

    <?php else: ?>
        <div style="text-align: center; padding: 4rem 0;">
            <h2 class="wcb-page-title">Nenhum conteúdo encontrado</h2>
            <p style="color: var(--wcb-gray-500);">Tente buscar por algo diferente.</p>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
