<?php
/**
 * Side Cart Header — override WCB (a11y: contagem no badge não duplicada por leitores de tela)
 *
 * @see woocommerce.php — wcb_xoo_wsc_cart_header_heading_dedupe
 * @version 2.7.1
 */

if (! defined('ABSPATH')) {
    exit;
}

extract(Xoo_Wsc_Template_Args::cart_header());

$headingHTML = $basketHTML = $closeHTML = $saveHTML = '';

?>

<?php ob_start(); ?>

<?php if ($showBasket) : ?>
	<?php
	$wcb_hdr_count = (int) xoo_wsc_cart()->get_cart_count();
	$wcb_hdr_basket_label = sprintf(
		_n('%d item no carrinho', '%d itens no carrinho', $wcb_hdr_count, 'wcb-theme'),
		$wcb_hdr_count
	);
	?>
<div class="xoo-wsch-basket" aria-label="<?php echo esc_attr($wcb_hdr_basket_label); ?>">

	<span class="xoo-wsch-bki <?php echo esc_html($basketIcon); ?> xoo-wsch-icon" aria-hidden="true"></span>

	<span class="xoo-wsch-items-count" aria-hidden="true"><?php echo (int) $wcb_hdr_count; ?></span>
</div>
<?php endif; ?>

<?php $basketHTML = ob_get_clean(); ?>



<?php ob_start(); ?>

<?php if ($heading) : ?>
	<span class="xoo-wsch-text"><?php echo $heading; ?></span>
<?php endif; ?>

<?php $headingHTML = ob_get_clean(); ?>



<?php ob_start(); ?>

<?php if ($showCloseIcon) : ?>
	<span class="xoo-wsch-close <?php echo $close_icon; ?> xoo-wsch-icon"></span>
<?php endif; ?>
<?php $closeHTML = ob_get_clean(); ?>


<div class="xoo-wsch-top xoo-wsch-new">

	<?php if ($showNotifications) : ?>
		<?php xoo_wsc_cart()->print_notices_html('cart'); ?>
	<?php endif; ?>

	<?php foreach ($headerLayout as $section => $elements) : ?>

		<div class="xoo-wsch-section xoo-wsch-sec-<?php echo esc_attr($section); ?>">
			<?php
			foreach ($elements as $element) {
				echo ${$element . 'HTML'};
			}
			?>
		</div>


	<?php endforeach; ?>

</div>
