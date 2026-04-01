<?php
/**
 * Bootstrap PHPUnit — apenas helpers puros (sem WordPress).
 *
 * @package WCB_Theme
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}

require_once dirname( __DIR__, 2 ) . '/inc/wcb-pure-helpers.php';
