<?php
/**
 * WCB Theme — Page template
 *
 * @package WCB_Theme
 */

get_header();
?>

<div class="wcb-container">
    <?php while (have_posts()):
        the_post(); ?>
        <article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>
            <h1 class="wcb-page-title">
                <?php the_title(); ?>
            </h1>
            <div class="wcb-page-content">
                <?php the_content(); ?>
            </div>
        </article>
    <?php endwhile; ?>
</div>

<?php
get_footer();
