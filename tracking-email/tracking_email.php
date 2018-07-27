<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: Email tracking notification for WooCommerce
Plugin URI: http://wordpress.org/plugins/tracking-email/
Description: Send e-mail notifications about the delivery status to your customers. It's easy!
Author: RapidDev | Polish technology company
Author URI: https://rapiddev.pl/
License: MIT
License URI: https://opensource.org/licenses/MIT
Version: 1.2.0
Text Domain: tracking_email
Domain Path: /languages
*/
/**
 * @package WordPress
 * @subpackage Invoice and Tracking for WooCommerce
 *
 * @author Leszek Pomianowski
 * @copyright Copyright (c) 2018, RapidDev
 * @link https://www.rapiddev.pl/rapiddev-productseo
 * @license http://opensource.org/licenses/MIT
 */

/* ====================================================================
 * Constant
 * ==================================================================*/
	define('TRACKING_EMAIL_VERSION', '1.2.0');
	define('TRACKING_EMAIL_NAME', 'Email tracking notification for WooCommerce');
	define('TRACKING_EMAIL_PATH', plugin_dir_path( __FILE__ ));
	define('TRACKING_EMAIL_URL', plugin_dir_url(__FILE__));
	define('TRACKING_EMAIL_WP_VERSION', '4.5.0');
	define('TRACKING_EMAIL_PHP_VERSION', '5.4.0');
	define('TRACKING_EMAIL_WC_VERSION', '3.4.0');

/* ====================================================================
 * Define language files
 * ==================================================================*/
	function tracking_email_languages(){
		load_plugin_textdomain( 'tracking_email', FALSE, basename(TRACKING_EMAIL_PATH) . '/languages/' );
	}
	add_action('plugins_loaded', 'tracking_email_languages');

/* ====================================================================
 * WordPress version check
 * ==================================================================*/
	global $wp_version;
	if (version_compare($wp_version, TRACKING_EMAIL_WP_VERSION, '>='))
	{

/* ====================================================================
 * PHP Version verification
 * ==================================================================*/
	if (version_compare(PHP_VERSION, TRACKING_EMAIL_PHP_VERSION, '>='))
	{
/* ====================================================================
 * If WooCommerce is Active
 * ==================================================================*/
		if (!function_exists( 'get_plugins')){
			include_once(ABSPATH.'wp-admin/includes/plugin.php');
		}
		if (is_plugin_active('woocommerce/woocommerce.php'))
		{

/* ====================================================================
 * WooCommerce version check
 * ==================================================================*/
			if(version_compare(get_plugins('/'.'woocommerce')['woocommerce.php']['Version'], TRACKING_EMAIL_WC_VERSION, '>=' ) == true){

/* ====================================================================
 * Add meta container
 * ==================================================================*/
				add_action( 'add_meta_boxes', 'tracking_email_add_meta_box' );
				if ( ! function_exists( 'tracking_email_add_meta_box' ) )
				{
					function tracking_email_add_meta_box()
					{
						add_meta_box( 'tracking_email', __('Shipment tracking','tracking_email'), 'invoice_and_track_add_meta_fields', 'shop_order', 'side', 'core' );
					}
				}

/* ====================================================================
 * Add meta box
 * ==================================================================*/
				if ( ! function_exists( 'invoice_and_track_add_meta_fields' ) )
				{
					function invoice_and_track_add_meta_fields()
					{
						global $post;
						$tracking_number = get_post_meta( $post->ID, '_tracking_number', true ) ? get_post_meta( $post->ID, '_tracking_number', true ) : '';
						$tracking_service = get_post_meta( $post->ID, '_tracking_service', true ) ? get_post_meta( $post->ID, '_tracking_service', true ) : '';
						$tracking_status = get_post_meta( $post->ID, '_tracking_status', true ) ? get_post_meta( $post->ID, '_tracking_status', true ) : '';
			?>
<div class="invoice-alert invoice-alert-secondary alert-result-sending">
	<p><?php _e('The message is being sent.', 'tracking_email') ?></p>
</div>
<div class="invoice-alert invoice-alert-secondary alert-result-success">
	<h4 class="invoice-alert-heading"><?php _e('Well done', 'tracking_email') ?>!</h4>
	<hr>
	<p><?php _e('The tracking information was sent to the client. The note has been added to your order. You can now save your changes so you do not lose your tracking number and status.', 'tracking_email') ?></p>
</div>
<div class="invoice-alert invoice-alert-danger alert-result-error">
	<h4 class="invoice-alert-heading"><?php _e('An error occured', 'tracking_email') ?></h4>
	<hr>
	<p><?php _e('An error occurred while sending tracking information.', 'tracking_email') ?></p>
</div>
<input type="hidden" name="tracking_email_meta_nonce" value="<?php echo wp_create_nonce(); ?>">
<input id="invoice_order_id" name="invoice_order_id" type="hidden" value="<?php echo $post->ID; ?>">
<p>
	<label><?php _e('Tracking number', 'tracking_email') ?></label>
	<input type="text" style="width:100%;" name="_tracking_number" id="_tracking_number" placeholder="<?php echo $tracking_number; ?>" value="<?php echo $tracking_number; ?>">
	<select style="width:100%;text-align: center;" name="_tracking_service" id="_tracking_service">
		<option <?php if ( $tracking_service == 'null' ) echo 'selected="selected"'; ?> value="null">-- <?php _e('Select courier', 'tracking_email') ?> --</option>
		<option <?php if ( $tracking_service == 'japan_post' ) echo 'selected="selected"'; ?> value="japan_post">Japan Post</option>
		<option <?php if ( $tracking_service == 'fedex' ) echo 'selected="selected"'; ?> value="fedex">FedEx</option>
		<option <?php if ( $tracking_service == 'dhl' ) echo 'selected="selected"'; ?> value="dhl">DHL</option>
		<option <?php if ( $tracking_service == 'ups' ) echo 'selected="selected"'; ?> value="ups">UPS</option>
		<option <?php if ( $tracking_service == 'blue_dart' ) echo 'selected="selected"'; ?> value="blue_dart">Blue Dart</option>
		<option <?php if ( $tracking_service == 'royal_mail' ) echo 'selected="selected"'; ?> value="royal_mail">Royal Mail</option>
		<option <?php if ( $tracking_service == 'schenker' ) echo 'selected="selected"'; ?> value="schenker">Schenker AG</option>
		<option <?php if ( $tracking_service == 'postnl' ) echo 'selected="selected"'; ?> value="postnl">PostNL</option>
		<option <?php if ( $tracking_service == 'yrc_worldwide' ) echo 'selected="selected"'; ?> value="yrc_worldwide">YRC Worldwide</option>
		<option <?php if ( $tracking_service == 'dtdc' ) echo 'selected="selected"'; ?> value="dtdc">DTDC</option>
		<option <?php if ( $tracking_service == 'dpd' ) echo 'selected="selected"'; ?> value="dpd">DPD</option>
		<option <?php if ( $tracking_service == 'tnt' ) echo 'selected="selected"'; ?> value="tnt">TNT</option>
		<option <?php if ( $tracking_service == 'gls' ) echo 'selected="selected"'; ?> value="gls">GLS</option>
		<option <?php if ( $tracking_service == 'poland_post' ) echo 'selected="selected"'; ?> value="poland_post">Poland Post</option>
		<option <?php if ( $tracking_service == 'envelo' ) echo 'selected="selected"'; ?> value="envelo">Envelo</option>
		<option <?php if ( $tracking_service == 'inpost' ) echo 'selected="selected"'; ?> value="inpost">InPost</option>
	</select>
</p>
<p>
	<label><?php _e('Tracking status', 'tracking_email') ?></label>
	<select style="width:100%;text-align: center;" name="_tracking_status" id="_tracking_status">
		<option <?php if ( $tracking_status == 'ready' ) echo 'selected="selected"'; ?> value="ready"><?php _e('Ready to send', 'tracking_email') ?></option>
		<option <?php if ( $tracking_status == 'sent' ) echo 'selected="selected"'; ?> value="sent"><?php _e('Sent', 'tracking_email') ?></option>
	</select>
</p>
<p>
	<button type="button" style="width:100%" class="button send_tracking button-primary" name="save" value="Send tracking number"><?php _e('Send tracking number', 'tracking_email') ?></button>
</p>
			<?php
					}
				}

/* ====================================================================
 * Save meta fields
 * ==================================================================*/
				add_action( 'save_post', 'tracking_email_save_meta', 10, 1 );
				if ( ! function_exists( 'tracking_email_save_meta' ) )
				{
					function tracking_email_save_meta( $post_id ) {
						if ( ! isset( $_POST[ 'tracking_email_meta_nonce' ] ) ) {
							return $post_id;
						}
						$nonce = $_REQUEST[ 'tracking_email_meta_nonce' ];
						if ( ! wp_verify_nonce( $nonce ) ) {
							return $post_id;
						}
						if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
							return $post_id;
						}
						if ( 'page' == $_POST[ 'post_type' ] ) {

							if ( ! current_user_can( 'edit_page', $post_id ) ) {
								return $post_id;
							}
						} else {
							if ( ! current_user_can( 'edit_post', $post_id ) ) {
								return $post_id;
							}
						}
						update_post_meta( $post_id, '_tracking_number', sanitize_text_field($_POST[ '_tracking_number' ] ));
						update_post_meta( $post_id, '_tracking_service', sanitize_text_field($_POST[ '_tracking_service' ] ));
						update_post_meta( $post_id, '_tracking_status', sanitize_text_field($_POST[ '_tracking_status' ] ));

					}
				}

/* ====================================================================
 * Add javascript
 * ==================================================================*/
				function tracking_email_scripts(){
				?><script async="async" defer="defer">function async_jquery(e,n){var t=document,a="script",c=t.createElement(a),r=t.getElementsByTagName(a)[0];c.src=e,n&&c.addEventListener("load",function(e){n(null,e)},!1),r.parentNode.insertBefore(c,r)}async_jquery("https://code.jquery.com/jquery-3.3.1.min.js",function(){$(".send_tracking:not(:disabled)").on("click",function(e){e.preventDefault(),$(".send_tracking").attr("disabled",!0),$('.alert-result-sending').slideToggle(),jQuery.ajax({url:"<?php echo admin_url( 'admin-ajax.php' ); ?>",type:"post",data:{action:"tracking_email_ajax",order_id:$("#invoice_order_id").val(),tracking:$("#_tracking_number").val(),service:$("#_tracking_service").val(),status:$("#_tracking_status").val()},success:function(e){console.log(e);$('.alert-result-sending').slideToggle();if(e == 'success'){if ($(".alert-result-error").is(":visible")){$('.alert-result-error').slideToggle();}if ($(".alert-result-success").is(":hidden")){$('.alert-result-success').slideToggle();}else{$('.alert-result-success').slideToggle(function(){$('.alert-result-success').slideToggle();});}}else{if ($(".alert-result-success").is(":visible")){$('.alert-result-success').slideToggle();}if ($(".alert-result-error").is(":hidden")){$('.alert-result-error').slideToggle();}else{$('.alert-result-error').slideToggle(function(){$('.alert-result-error').slideToggle();});}}}})})});</script><?php
				}
				global $pagenow;
				if ($pagenow == 'post.php') {
					add_action('admin_footer', 'tracking_email_scripts');
				}

/* ====================================================================
 * Add css
 * ==================================================================*/
				function tracking_email_css(){
				?><style>.invoice-alert {display: none;position: relative;padding: 0.75rem 1.25rem;margin-bottom: 1rem;border: 1px solid transparent;border-radius: 0.25rem;}.invoice-alert-heading {color: inherit;}.invoice-alert-secondary {color: #383d41;background-color: #e2e3e5;border-color: #d6d8db;}.invoice-alert-secondary hr {border-top-color: #c8cbcf;}.invoice-alert-danger {color: #721c24;background-color: #f8d7da;border-color: #f5c6cb;}.invoice-alert-danger hr {border-top-color: #f1b0b7;}</style><?php
				}
				global $pagenow;
				if ($pagenow == 'post.php') {
					add_action('admin_head', 'tracking_email_css');
				}

/* ====================================================================
 * Custom email template
 * ==================================================================*/
				function tracking_email_send_tracking( $order, $heading = false, $mailer, $tracking, $service_name, $service_url, $service_status, $title, $blog_name ) {
					return wc_get_template_html('tracking_email.php', array(
						'order'         => $order,
						'email_heading' => $heading,
						'title'         => $title,
						'tracking'      => $tracking,
						'service_url'   => $service_url,
						'service_name'  => $service_name,
						'service_status'=> $service_status,
						'blog_name'     => $blog_name,
						'sent_to_admin' => false,
						'plain_text'    => false,
						'email'         => $mailer
					), '/emails/', TRACKING_EMAIL_PATH.'/emails/');
				}

/* ====================================================================
 * Send email ajax
 * ==================================================================*/
					function tracking_email_ajax() {
						if (!isset($_POST['order_id'])) {
							exit('error');
						}else{
							$order_id = sanitize_text_field($_POST['order_id']);
						}
						if (isset($_POST['status'])) {
							$status = sanitize_text_field($_POST['status']);
							if ($status == '' || $status == 'null') {
								$status = NULL;
							}
						}else{
							$status = NULL;
						}
						if (isset($_POST['service'])) {
							$service = sanitize_text_field($_POST['service']);
							if ($service == '' || $service == 'null') {
								$service = NULL;
							}
						}else{
							$service = NULL;
						}
						if (isset($_POST['tracking'])) {
							$tracking = sanitize_text_field($_POST['tracking']);
							if ($tracking != '') {
								$tracking = preg_replace("/[^0-9]/", '', $tracking);
							}else{
								exit('error');
							}
						}else{
							exit('error');
						}
						if ($tracking !== NULL) {
							if ($service !== NULL) {
								switch ($service) {
									case 'envelo':
										$url = 'https://emonitoring.poczta-polska.pl/?numer='.$tracking;
										$name = 'Envelo';
										break;
									case 'poland_post':
										$url = 'https://emonitoring.poczta-polska.pl/?numer='.$tracking;
										$name = 'Poczta Polska';
										break;
									case 'japan_post':
										$url = 'http://tracking.post.japanpost.jp/services/sp/srv/search/?requestNo1='.$tracking.'&search=Beginning&locale=en';
										$name = 'Japan Post';
										break;
									case 'fedex':
										$url = 'https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber='.$tracking;
										$name = 'FedEx';
										break;
									case 'dhl':
										$url = 'http://www.dhl.com.pl/en/express/tracking.html?AWB='.$tracking;
										$name = 'DHL';
										break;
									case 'ups':
										$url = 'https://t.17track.net/pl#nums='.$tracking;
										$name = 'UPS';
										break;
									case 'blue_dart':
										$url = 'https://t.17track.net/pl#nums='.$tracking;
										$name = 'Blue Dart';
										break;
									case 'royal_mail':
										$url = 'https://t.17track.net/pl#nums='.$tracking;
										$name = 'Royal Mail';
										break;
									case 'schenker':
										$url = 'https://t.17track.net/pl#nums='.$tracking;
										$name = 'Schenker';
										break;
									case 'postnl':
										$url = 'https://t.17track.net/pl#nums='.$tracking;
										$name = 'PostNL';
										break;
									case 'yrc_worldwide':
										$url = 'https://t.17track.net/pl#nums='.$tracking;
										$name = 'YRC Woldwide';
										break;
									case 'dtdc':
										$url = 'https://t.17track.net/pl#nums='.$tracking;
										$name = 'DTDC';
										break;
									case 'tnt':
										$url = 'https://t.17track.net/pl#nums='.$tracking;
										$name = 'TNT';
										break;
									case 'dpd':
										$url = 'https://t.17track.net/pl#nums='.$tracking;
										$name = 'DPD';
										break;
									case 'gls':
										$url = 'https://t.17track.net/pl#nums='.$tracking;
										$name = 'GLS';
										break;
									case 'inpost':
										$url = 'https://inpost.pl/sledzenie-przesylek?number='.$tracking;
										$name = 'InPost';
										break;
									default:
										$url = 'https://t.17track.net/pl#nums='.$tracking;
										$name = __('Courier', 'tracking_email');
										break;
								}
								if ($url == NULL) {
									$service_html = NULL;
								}else{
									$service_html = '<br/>'.__('Sent by ', 'tracking_email').'<strong>'.$name.'</strong><a target="_blank" rel="nofollow noopener" href="'.esc_url($url).'" class="button button-secondary" style="width:100%;text-align:center;margin-top:15px;">'.__('Track the package', 'tracking_email').'</a>';
								}
							}else{
								$service_html = NULL;
							}
							$order = wc_get_order($order_id);
							$order_data = $order->get_data();
							//Add order note
							$order->add_order_note(__('Tracking number is', 'tracking_email').':<br /><strong>'.$tracking.'</strong>'.$service_html);

							switch ($status) {
								case 'ready':
									$title = __('Your parcel is ready to be sent!', 'tracking_email');
									$notify = __('Shipment information sent to the client, status: Ready to send', 'tracking_email');
									break;
								default:
									$title = __('Your package has been sent!', 'tracking_email');
									$notify = __('Shipment information sent to the client, status: Sent', 'tracking_email');
									break;
							}

							//Send email to the customer
							$mailer = WC()->mailer();
							$recipient = $order_data['billing']['email'];
							$blog_name = get_bloginfo('name');
							$subject = $blog_name.' - '.$title;
							$content = tracking_email_send_tracking($order, $subject, $mailer, $tracking, $name, esc_url($url), $status, $title, $blog_name);
							$headers = "Content-Type: text/html\r\n";
							$mailer->send( $recipient, $subject, $content, $headers );
							//Add order note
							$order->add_order_note($notify);

						}
						$order->save();
						exit('success');
					}
					add_action('wp_ajax_nopriv_tracking_email_ajax', 'tracking_email_ajax');
					add_action('wp_ajax_tracking_email_ajax', 'tracking_email_ajax');

/* ====================================================================
 * Order list column
 * ==================================================================*/
					function tracking_email_orders_column($columns)
					{
						$reordered_columns = array();
						foreach( $columns as $key => $column){
							$reordered_columns[$key] = $column;
							if( $key ==  'order_status' ){
								$reordered_columns['tracking'] = __('Tracking', 'tracking_email');
							}
						}
						return $reordered_columns;
					}
					add_filter( 'manage_edit-shop_order_columns', 'tracking_email_orders_column', 20 );

/* ====================================================================
 * Order list content
 * ==================================================================*/
				function tracking_email_column_tax($column, $post_id)
				{
					if ('tracking' != $column) return;

					$tracking_number = get_post_meta( $post_id, '_tracking_number', true ) ? get_post_meta( $post_id, '_tracking_number', true ) : '';
					$tracking_service = get_post_meta( $post_id, '_tracking_service', true ) ? get_post_meta( $post_id, '_tracking_service', true ) : '';

					if($tracking_number == '') {
						echo __('Not determined', 'tracking_email');
					}else{
						$tracking = str_replace(array(' ', '(', ')'), array('', '', ''), $tracking_number);
						if ($tracking_service != '') {
							switch ($tracking_service) {
								case 'envelo':
									$url = 'https://emonitoring.poczta-polska.pl/?numer='.$tracking;
									break;
								case 'poland_post':
									$url = 'https://emonitoring.poczta-polska.pl/?numer='.$tracking;
									break;
								case 'japan_post':
									$url = 'http://tracking.post.japanpost.jp/services/sp/srv/search/?requestNo1='.$tracking.'&search=Beginning&locale=en';
									break;
								case 'fedex':
									$url = 'https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber='.$tracking;
									break;
								case 'dhl':
									$url = 'http://www.dhl.com.pl/en/express/tracking.html?AWB='.$tracking;
									break;
								case 'ups':
									$url = 'https://t.17track.net/pl#nums='.$tracking;
									break;
								case 'blue_dart':
									$url = 'https://t.17track.net/pl#nums='.$tracking;
									break;
								case 'royal_mail':
									$url = 'https://t.17track.net/pl#nums='.$tracking;
									break;
								case 'schenker':
									$url = 'https://t.17track.net/pl#nums='.$tracking;
									break;
								case 'postnl':
									$url = 'https://t.17track.net/pl#nums='.$tracking;
									break;
								case 'yrc_worldwide':
									$url = 'https://t.17track.net/pl#nums='.$tracking;
									break;
								case 'dtdc':
									$url = 'https://t.17track.net/pl#nums='.$tracking;
									break;
								case 'tnt':
									$url = 'https://t.17track.net/pl#nums='.$tracking;
									break;
								case 'dpd':
									$url = 'https://t.17track.net/pl#nums='.$tracking;
									break;
								case 'gls':
									$url = 'https://t.17track.net/pl#nums='.$tracking;
									break;
								case 'inpost':
									$url = 'https://inpost.pl/sledzenie-przesylek?number='.$tracking;
									break;
								default:
									$url = 'https://t.17track.net/pl#nums='.$tracking;
									break;
							}
							echo '<a href="'.esc_url($url).'" target="_blank" rel="nofollow noopener" type="button" style="width:100%;text-align:center;" class="button button-secondary">'.__('Track package', 'tracking_email').'</a>';
						}else{
							echo __('Not determined', 'tracking_email');
						}
						
					}
				}
				add_action( 'manage_shop_order_posts_custom_column' , 'tracking_email_column_tax', 20, 2 );

/* ====================================================================
 * WooCommerce version error
 * ==================================================================*/
				}else{
					if (!function_exists('tracking_email_wc_version_error')){
						function tracking_email_wc_version_error(){
							echo '<div class="notice notice-error"><p><strong>'.__('ERROR', 'tracking_email').'!</strong><br />'.__('The', 'tracking_email').' <i>'.TRACKING_EMAIL_NAME.'</i> '.__('requires at least', 'tracking_email').' WooCommerce '.TRACKING_EMAIL_WC_VERSION.'<br />'.__('You need to update your WooCommerce plugin', 'tracking_email').'.<br /><small><i>'.__('ERROR ID', 'tracking_email').': 3</i></small></p></div>';
						}
						add_action('admin_notices', 'tracking_email_wc_version_error');
					}
				}
			}
/* ====================================================================
 * WordPress <4.5.0 error
 * ==================================================================*/
		}else{
			if (!function_exists('tracking_email_wordpress_error'))
			{
				function tracking_email_wordpress_error(){
					echo '<div class="notice notice-error"><p><strong>'.__('ERROR', 'tracking_email').'!</strong><br />'.__('The', 'tracking_email').' <i>'.TRACKING_EMAIL_NAME.'</i> '.__('requires at least', 'tracking_email').' WordPress '.TRACKING_EMAIL_WP_VERSION.'<br />'.__('You need to update your WordPress site', 'tracking_email').'.<br /><small><i>'.__('ERROR ID', 'tracking_email').': 1</i></small></p></div>';
				}
				add_action('admin_notices', 'tracking_email_wordpress_error');
			}
		}

/* ====================================================================
 * PHP <5.4.0 error
 * ==================================================================*/
	}else{
		if (!function_exists('tracking_email_php_error'))
		{
			function tracking_email_php_error(){
				echo '<div class="notice notice-error"><p><strong>'.__('ERROR', 'tracking_email').'!</strong><br />'.__('The', 'tracking_email').' <i>'.TRACKING_EMAIL_NAME.'</i> '.__('requires at least', 'tracking_email').' PHP '.TRACKING_EMAIL_PHP_VERSION.'<br />'.__('You need to update your WordPress site', 'tracking_email').'.<br /><small><i>'.__('ERROR ID', 'tracking_email').': 1</i></small></p></div>';
			}
			add_action('admin_notices', 'tracking_email_php_error');
		}
	}
?>