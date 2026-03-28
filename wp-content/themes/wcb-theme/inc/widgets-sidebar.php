<?php
/**
 * WCB Theme — Widget Areas & Sidebars
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   WIDGET AREAS
   ============================================================ */
function wcb_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Sidebar', 'wcb-theme' ),
        'id'            => 'wcb-sidebar',
        'before_widget' => '<div id="%1$s" class="wcb-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="wcb-widget__title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => __( 'Footer Widget 1', 'wcb-theme' ),
        'id'            => 'wcb-footer-1',
        'before_widget' => '<div id="%1$s" class="wcb-footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="wcb-footer-widget__title">',
        'after_title'   => '</h4>',
    ) );
}
add_action( 'widgets_init', 'wcb_widgets_init' );
