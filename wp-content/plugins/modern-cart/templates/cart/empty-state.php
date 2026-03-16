<?php

defined( 'ABSPATH' ) || exit;

?>

<div class="moderncart-empty moderncart-px moderncart-pt <?php echo esc_attr( $classes ); ?>">
	<p class="moderncart-empty__headline"><?php echo wp_kses_post( $headline ); ?></p>
	<p class="moderncart-empty__subheader"><?php echo wp_kses_post( $subheader ); ?></p>
</div>
