<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: Tracking: Emails and Notifications for WooCommerce
Plugin URI: https://wordpress.org/plugins/email-tracking-notification-for-woocommerce/
Description: Send e-mail notifications about the delivery status to your customers. It's easy!
Author: RapidDev
Author URI: https://rdev.cc/
License: MIT
License URI: https://opensource.org/licenses/MIT
Version: 1.7.4
Text Domain: tracking_email
Domain Path: /languages
*/
/**
 * @package WordPress
 * @subpackage Tracking: Emails and Notifications for WooCommerce
 *
 * @author Leszek Pomianowski
 * @copyright Copyright (c) 2018-2020, RapidDev
 * @link https://www.rdev.cc/
 * @license http://opensource.org/licenses/MIT
 */

/* ====================================================================
 * Constants
 * ==================================================================*/
	define( 'RDEV_TRACK_VERSION',		'1.7.4' );
	define( 'RDEV_TRACK_WP_VERSION',	'5.2.3' );
	define( 'RDEV_TRACK_PHP_VERSION',	'5.6.0' );
	define( 'RDEV_TRACK_WC_VERSION',	'4.0.1' );
	define( 'RDEV_TRACK_PATH',			plugin_dir_path( __FILE__ ) );
	define( 'RDEV_TRACK_URL',			plugin_dir_url(__FILE__) );

/* ====================================================================
 * Plugin class
 * ==================================================================*/
	require_once RDEV_TRACK_PATH . 'assets/api/api_polish_post.php';
	require_once RDEV_TRACK_PATH . 'assets/api/api_inpost.php';
	require_once RDEV_TRACK_PATH . 'assets/class.php';
	new RDEVTracking();
?>
