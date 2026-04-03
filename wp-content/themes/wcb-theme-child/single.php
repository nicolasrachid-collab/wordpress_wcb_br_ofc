<?php
/**
 * Post único — child (modular: content + related + CTA).
 *
 * @package WCB_Child
 */

get_header();
?>

<div class="wcb-container wcb-single-post-wrap">
	<?php
	while ( have_posts() ) {
		the_post();
		get_template_part( 'template-parts/content', 'single' );
	}
	get_template_part( 'template-parts/single', 'related' );
	get_template_part( 'template-parts/single', 'cta' );
	?>
</div>

<?php
get_footer();
