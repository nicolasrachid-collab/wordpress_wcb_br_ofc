<?php // phpcs:ignore WordPress.NamingConventions
/**
 * GENERAL ARRAY OPTIONS
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\FrequentlyBoughtTogether
 */

$general = array(

	'general' => array(

		array(
			'title' => __( 'General Options', 'yith-woocommerce-frequently-bought-together' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'yith-wcfbt-general-options',
		),

		array(
			'id'        => 'yith-wfbt-form-title',
			'name'      => __( 'Box title', 'yith-woocommerce-frequently-bought-together' ),
			'desc'      => __( 'Title shown on "Frequently Bought Together" box.', 'yith-woocommerce-frequently-bought-together' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'default'   => __( 'Frequently Bought Together', 'yith-woocommerce-frequently-bought-together' ),
		),

		array(
			'id'        => 'yith-wfbt-total-label',
			'name'      => __( 'Total label', 'yith-woocommerce-frequently-bought-together' ),
			'desc'      => __( 'This is the label shown for total price label.', 'yith-woocommerce-frequently-bought-together' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'default'   => __( 'Price for all', 'yith-woocommerce-frequently-bought-together' ),
		),

		array(
			'id'        => 'yith-wfbt-button-label',
			'name'      => __( 'Button label', 'yith-woocommerce-frequently-bought-together' ),
			'desc'      => __( 'This is the label shown for "Add to cart" button.', 'yith-woocommerce-frequently-bought-together' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'default'   => __( 'Add all to Cart', 'yith-woocommerce-frequently-bought-together' ),
		),
		array(
			'id'            => 'yith-wfbt-button-color-multi-colorpicker-background',
			'title'         => _x( 'Button colors', '[admin]Plugin option label', 'yith-woocommerce-frequently-bought-together' ),
			'type'          => 'yith-field',
			'yith-type'     => 'multi-colorpicker',
			'columns'       => 2,
			'colorpickers'  => array(
				array(
					'id'        => 'yith-wfbt-button-color',
					'name'      => __( 'BACKGROUND', 'yith-woocommerce-frequently-bought-together' ),
					'default'   => '#222222',
				),
				array(
					'id'        => 'yith-wfbt-button-color-hover',
					'name'      => __( 'BACKGROUND HOVER', 'yith-woocommerce-frequently-bought-together' ),
					'default'   => '#777777',
				),
				array(),
				array(
					'id'        => 'yith-wfbt-button-text-color',
					'name'      => __( 'TEXT', 'yith-woocommerce-frequently-bought-together' ),
					'default'   => '#ffffff',
				),
				array(
					'id'        => 'yith-wfbt-button-text-color-hover',
					'name'      => __( 'TEXT HOVER', 'yith-woocommerce-frequently-bought-together' ),
					'default'   => '#ffffff',
				),
			),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'yith-wcfbt-general-options',
		),
	),
);

return apply_filters( 'yith_wcfbt_panel_general_options', $general );
