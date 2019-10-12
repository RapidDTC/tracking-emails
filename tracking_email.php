<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: Tracking: Emails and Notifications for WooCommerce
Plugin URI: http://wordpress.org/plugins/tracking-email/
Description: Send e-mail notifications about the delivery status to your customers. It's easy!
Author: RapidDev
Author URI: https://rdev.cc/
License: MIT
License URI: https://opensource.org/licenses/MIT
Version: 1.3.0
Text Domain: tracking_email
Domain Path: /languages
*/
/**
 * @package WordPress
 * @subpackage Tracking: Emails and Notifications for WooCommerce
 *
 * @author Leszek Pomianowski
 * @copyright Copyright (c) 2018-2019, RapidDev
 * @link https://www.rdev.cc/
 * @license http://opensource.org/licenses/MIT
 */

/* ====================================================================
 * Constants
 * ==================================================================*/
	define('RDEV_TRACK_VERSION', '1.3.0');
	define('RDEV_TRACK_NAME', 'Tracking: Emails and Notifications for WooCommerce');
	define('RDEV_TRACK_PATH', plugin_dir_path( __FILE__ ));
	define('RDEV_TRACK_URL', plugin_dir_url(__FILE__));
	define('RDEV_TRACK_WP_VERSION', '5.2.3');
	define('RDEV_TRACK_PHP_VERSION', '5.6.0');
	define('RDEV_TRACK_WC_VERSION', '3.7.1');

/* ====================================================================
 * Plugin class
 * ==================================================================*/
	if (is_file(RDEV_TRACK_PATH.'assets/class.php')) {
		include(RDEV_TRACK_PATH.'assets/class.php');
		RDEV_TRACK::init();
	}
?>