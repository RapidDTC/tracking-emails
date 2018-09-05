<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: Email tracking notification for WooCommerce
Plugin URI: http://wordpress.org/plugins/tracking-email/
Description: Send e-mail notifications about the delivery status to your customers. It's easy!
Author: RapidDev | Polish technology company
Author URI: https://rapiddev.pl/
License: MIT
License URI: https://opensource.org/licenses/MIT
Version: 2.0.0
Text Domain: tracking_email
Domain Path: /languages
*/
/**
 * @package WordPress
 * @subpackage Invoice and Tracking for WooCommerce
 *
 * @author Leszek Pomianowski
 * @copyright Copyright (c) 2018, RapidDev
 * @link https://www.rapiddev.pl/tracking-email
 * @license http://opensource.org/licenses/MIT
 */

/* ====================================================================
 * Constant
 * ==================================================================*/
	define('RAPIDDEV_TRACKING_EMAIL_NAME', 'Email tracking notification for WooCommerce');
	define('RAPIDDEV_TRACKING_EMAIL_PATH', plugin_dir_path( __FILE__ ));
	define('RAPIDDEV_TRACKING_EMAIL_URL', plugin_dir_url(__FILE__));
	define('RAPIDDEV_TRACKING_EMAIL_BASENAME', plugin_basename(__FILE__));
	define('RAPIDDEV_TRACKING_EMAIL_WP_VERSION', '4.9.0');
	define('RAPIDDEV_TRACKING_EMAIL_PHP_VERSION', '5.4.0');
	define('RAPIDDEV_TRACKING_EMAIL_WC_VERSION', '3.4.4');
/* ====================================================================
 * Plugin class
 * ==================================================================*/
	if (is_file(RAPIDDEV_TRACKING_EMAIL_PATH.'assets/class.php')) {
		include(RAPIDDEV_TRACKING_EMAIL_PATH.'assets/class.php');
		RAPIDDEV_TRACKING_EMAIL::init();
	}

?>