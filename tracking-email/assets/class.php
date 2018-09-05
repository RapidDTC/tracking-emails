<?php defined('ABSPATH') or die('No script kiddies please!');
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
 * RapidDev Tracking Email CLASS
 * ==================================================================*/
	if (!class_exists('RAPIDDEV_TRACKING_EMAIL'))
	{
		class RAPIDDEV_TRACKING_EMAIL
		{
			protected static $tracking_services = array(
				'envelo' => array('[PL] Envelo', 'https://emonitoring.poczta-polska.pl/?numer='),
				'poland_post' => array('[PL] Poczta Polska', 'https://emonitoring.poczta-polska.pl/?numer='),
				'global_expres' => array('[PL] GLOBAL Expres', 'https://emonitoring.poczta-polska.pl/?numer='),
				'ems' => array('[PL] EMS', 'https://emonitoring.poczta-polska.pl/?numer='),
				'pocztex' => array('[PL] Pocztex Kurier', 'https://emonitoring.poczta-polska.pl/?numer='),
				'dpd_poland' => array('[PL] DPD Polska', 'https://tracktrace.dpd.com.pl/parcelDetails?p1='),
				'dpd_uk' => array('DPD United Kingdom', 'http://www.dpd.co.uk/apps/tracking/?reference='),
				'inpost' => array('InPost', 'https://inpost.pl/sledzenie-przesylek?number='),
				'dhl_express' => array('DHL Express', 'http://www.dhl.com.pl/en/express/tracking.html?AWB='),
				'fedex' => array('FedEx', 'https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber='),
				'china_post' => array('China Post', 'http://track-chinapost.com/result_china.php?order_no='),
				'ups' => array('UPS', 'https://wwwapps.ups.com/WebTracking/track?track=yes&trackNums='),
				'tnt' => array('TNT', 'https://www.tnt.com/express/en_us/site/tracking.html?searchType=con&cons='),
				
				'blue_dart' => array('Blue Dart', 'https://t.17track.net/pl#nums='),
				'royal_mail' => array('Royal Mail', 'https://t.17track.net/pl#nums='),
				'postnl' => array('PostNL', 'https://t.17track.net/pl#nums='),
				'yrc_worldwide' => array('YRC WorldWide', 'https://t.17track.net/pl#nums='),
				'dtdc' => array('DTDC', 'https://t.17track.net/pl#nums='),
				'gls' => array('GLS', 'https://t.17track.net/pl#nums='),

				'japan_post' => array('Japan Post','http://tracking.post.japanpost.jp/services/sp/srv/search/?requestNo1=')
			);

			public static function init()
			{
				return new RAPIDDEV_TRACKING_EMAIL();
			}
			public function __construct()
			{
				add_action('plugins_loaded', array($this, 'languages'));

				if (self::check_versions())
				{
					if (is_admin()) {
						global $pagenow;
						if($pagenow == 'post.php' || $pagenow == 'edit.php'){
							add_action('admin_head', array($this, 'css'));
							add_action('admin_head', array($this, 'javascript'));
						}
						add_filter('manage_edit-shop_order_columns', array($this, 'add_column'), 20);
						add_action('manage_shop_order_posts_custom_column', array($this, 'column_content'), 20, 2);
						add_action('manage_shop_order_posts_custom_column', array($this, 'column_buttons'), 20, 2);
						add_action('wp_ajax_nopriv_tracking_email_ajax', array($this, 'ajax'));
						add_action('wp_ajax_tracking_email_ajax', array($this, 'ajax'));
						add_action('wp_ajax_nopriv_tracking_email_ajax_tracking', array($this, 'ajax_tracking'));
						add_action('wp_ajax_tracking_email_ajax_tracking', array($this, 'ajax_tracking'));
						
						add_action('add_meta_boxes', array($this, 'meta_box'));
						add_action('save_post', array($this, 'save_meta'), 10, 1);

						add_action('add_meta_boxes', array($this, 'meta_box_tracking'));
					}
				}
			}
			public function languages()
			{
				load_plugin_textdomain('tracking_email',FALSE, basename(RAPIDDEV_TRACKING_EMAIL_PATH).'/languages/');
			}
			public function ajax()
			{
				if (!isset($_POST['order_id'])){exit('error');}else{$data['order'] = sanitize_text_field($_POST['order_id']);}
				if (!isset($_POST['tracking'])){exit('error');}else{$data['tracking_number'] = sanitize_text_field($_POST['tracking']);}
				if (!isset($_POST['status'])){exit('error');}else{$data['package_status'] = sanitize_text_field($_POST['status']);}
				if (!isset($_POST['service'])){exit('error');}else{$data['carrier'] = sanitize_text_field($_POST['service']);}

				$data['url'] = self::$tracking_services[$data['carrier']][1].preg_replace("/[^a-zA-Z0-9]/", '', $data['tracking_number']);
				$data['carrier'] = self::$tracking_services[$data['carrier']][0];
				$data['blog_name'] = get_bloginfo('name');
				$data['subject'] = $data['blog_name'].' - '.($data['package_status'] == 'ready' ? __('Your parcel is ready to be sent!', 'tracking_email') : __('Your package has been sent!', 'tracking_email'));
				update_post_meta($data['order'], '_tracking_url', esc_url($data['url']));

				$order = wc_get_order($data['order']);

				if (self::mailer_send($order, $data))
				{
					$order->add_order_note(__('Tracking number is', 'tracking_email').':<br /><strong>'.$data['tracking_number'].'</strong><br/>'.__('Sent by ', 'tracking_email').'<strong>'.$data['carrier'].'</strong><a target="_blank" rel="nofollow noopener" href="'.esc_url($data['url']).'" class="button button-secondary" style="width:100%;text-align:center;margin-top:15px;">'.__('Track the package', 'tracking_email').'</a>');
					$order->save();
					exit('success');
				}
				else
				{
					exit('error - unable to sent message');
				}
			}
			private function mailer_send($order, $data)
			{
				$WC_MAILER = WC()->mailer();
				return $WC_MAILER->send(
					$order->get_data()['billing']['email'],
					$data['subject'],
					wc_get_template_html(
						'tracking_email.php',
						array(
							'order' => $order,
							'data' => $data,
							'sent_to_admin' => false,
							'plain_text' => false,
							'email' => $WC_MAILER
						),
						'/emails/',
						RAPIDDEV_TRACKING_EMAIL_PATH.'/emails/'
					),
					"Content-Type: text/html\r\n"
				);
			}
			public function ajax_tracking()
			{
				if (!isset($_POST['post_id'])){exit('error');}else{$data['post_id'] = sanitize_text_field($_POST['post_id']);}
				if (!isset($_POST['carrier'])){exit('error');}else{$data['carrier'] = sanitize_text_field($_POST['carrier']);}
				if (!isset($_POST['tracking_number'])){exit('error');}else{$data['tracking_number'] = sanitize_text_field($_POST['tracking_number']);}
				
				if (is_file(RAPIDDEV_TRACKING_EMAIL_PATH.'/assets/polishpost_api.php'))
				{
					include(RAPIDDEV_TRACKING_EMAIL_PATH.'/assets/polishpost_api.php');
					$package = new PolishPostApi($data['tracking_number']);
					$events = $package->get_events();
					
					if (isset($events[0]['nazwa']))
					{
						$events_list = [];
						foreach ($events as $single) {
							if (isset($single['nazwa']) && isset($single['jednostka']['nazwa'])) {
								$events_list[] = array(
									'event' => $single['nazwa'],
									'post' => $single['jednostka']['nazwa']
								);
							}
						}
						
						update_post_meta($data['post_id'], '_tracking_events', $events_list);
						update_post_meta($data['post_id'], '_tracking_event', end($events_list)['event']);
						
						exit(end($events_list)['event']);
					}else{
						exit('error');
					}
				}
				else
				{
					exit('error');
				}
			}
			public function add_column($columns)
			{
				$reordered = array();
				foreach( $columns as $key => $column)
				{
					$reordered[$key] = $column;
					if( $key ==  'order_status')
					{
						$reordered['rapiddev_tracking_status'] = __('Tracking status', 'tracking_email');
						$reordered['rapiddev_tracking_update'] = '';
					}
				}
				return $reordered;
			}
			public function column_content($column, $post_id)
			{
				if ('rapiddev_tracking_status' != $column) return;
				$data['number'] = $this->get_meta($post_id, '_tracking_number');
				$data['url'] = $this->get_meta($post_id, '_tracking_url');
				$data['event'] = $this->get_meta($post_id, '_tracking_event', __('Unknown shipment status', 'tracking_email'));

				if ($data['number'] == NULL && $data['url'] == NULL)
				{
					_e('Not determined', 'tracking_email');
				}
				else
				{
					echo '<p id="tracking_status_event-'.$post_id.'">'.$data['event'].'</p>';
				}
			}
			public function column_buttons($column, $post_id)
			{
				if ('rapiddev_tracking_update' != $column) return;
				$data['number'] = self::get_meta($post_id, '_tracking_number');
				$data['number_preg'] = preg_replace("/[^a-zA-Z0-9]/", '', $data['number']);
				$data['url'] = self::get_meta($post_id, '_tracking_url');
				$data['carrier'] = self::get_meta($post_id, '_tracking_service');
				$data['event'] = self::get_meta($post_id, '_tracking_event', __('Unknown shipment status', 'tracking_email'));
				if ($data['number'] != NULL)
				{
					$data['url'] = self::$tracking_services[$data['carrier']][1].$data['number_preg'];
				}
				if (in_array($data['carrier'], array('envelo','poland_post','global_expres'), true ) ) {
					$html = '<a href="'.esc_url($data['url']).'" style="width:50%;text-align:center;text-align:center;" target="_blank" rel="nofollow noopener" type="button" class="button button-secondary" value="'.__('Track package', 'tracking_email').'"><span style="width:15px;height:10px;font-size:15px;line-height:25px;" class="dashicons dashicons-external"></span></a>';
					$html .=  '<a href="#" post_id="'.$post_id.'" carrier="'.$data['carrier'].'" tracking_number="'.$data['number_preg'].'" style="width:50%;text-align:center;text-align:center;" type="button" role="button" class="rapiddev_update_shipment_status button button-secondary"><span style="width:15px;height:10px;font-size:15px;line-height:25px;" class="dashicons dashicons-image-rotate"></span></a>';
					echo $html;
				}else{
					if ($data['number'] != NULL && $data['url'] != NULL)
					{
						echo '<a style="width:100%;text-align:center;" href="'.esc_url($data['url']).'" target="_blank" rel="nofollow noopener" type="button" style="text-align:center;" class="button button-secondary">'.__('Track package', 'tracking_email').'</a>';
					}
				}
			}
			public function save_meta($TRACKING_EMAIL)
			{
				if (!isset($_POST['tracking_email_meta_nonce']))
				{
					return $TRACKING_EMAIL;
				}
				if (!wp_verify_nonce($_REQUEST['tracking_email_meta_nonce']))
				{
					return $TRACKING_EMAIL;
				}
				if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
				{
					return $TRACKING_EMAIL;
				}
				if ('page' == $_POST['post_type'])
				{
					if (!current_user_can('edit_page',$TRACKING_EMAIL))
					{
						return $TRACKING_EMAIL;
					}
				}
				else
				{
					if (!current_user_can('edit_post',$TRACKING_EMAIL))
					{
						return $TRACKING_EMAIL;
					}
				}
				update_post_meta($TRACKING_EMAIL, '_tracking_number', sanitize_text_field($_POST[ '_tracking_number']));
				update_post_meta($TRACKING_EMAIL, '_tracking_service', sanitize_text_field($_POST[ '_tracking_service']));
				update_post_meta($TRACKING_EMAIL, '_tracking_status', sanitize_text_field($_POST[ '_tracking_status']));
			}
			public function meta_box()
			{
				add_meta_box( 'rapiddev_tracking_email', __('Shipment tracking','tracking_email'), array($this, 'meta_fields'), 'shop_order', 'side', 'core');
			}
			public function meta_box_tracking()
			{
				add_meta_box( 'rapiddev_tracking_status', __('Tracking events','tracking_email'), array($this, 'meta_tracking'), 'shop_order', 'side', 'core');
			}
			public function meta_fields()
			{
				global $post;
				$tracking_number = self::get_meta($post->ID, '_tracking_number');
				$tracking_service = self::get_meta($post->ID, '_tracking_service');
				$tracking_status = self::get_meta($post->ID, '_tracking_status');

				

				$html = '<div class="invoice-alert invoice-alert-secondary alert-result-sending"><p>'.__('The message is being sent...', 'tracking_email').'</p></div>';
				$html .= '<div class="invoice-alert invoice-alert-secondary alert-result-success"><h4 class="invoice-alert-heading">'.__('Well done', 'tracking_email').'!</h4><hr><p>'.__('The tracking information was sent to the client. The note has been added to your order. You can now save your changes so you do not lose your tracking number and status.', 'tracking_email').'</p></div>';
				$html .= '<div class="invoice-alert invoice-alert-danger alert-result-error"><h4 class="invoice-alert-heading">'.__('An error occured', 'tracking_email').'</h4><hr><p>'.__('An error occurred while sending tracking information.', 'tracking_email').'</p></div>';
				$html .= '<input type="hidden" id="tracking_email_meta_nonce" name="tracking_email_meta_nonce" value="'.wp_create_nonce().'">';
				$html .= '<input type="hidden" id="invoice_order_id" name="invoice_order_id" value="'.$post->ID.'">';
				$html .= '<label>'.__('Tracking number', 'tracking_email').'</label><input type="text" style="width:100%;" name="_tracking_number" id="_tracking_number" placeholder="'.$tracking_number.'" value="'.$tracking_number.'">';
				$html .= '<p><label>'.__('Carrier', 'tracking_email').'</label><select style="width:100%;text-align: center;" name="_tracking_service" id="_tracking_service">';
				foreach (self::$tracking_services as $service => $atts) {
					$html .= '<option'.($tracking_service != $service?'':' selected="selected"').' value="'.$service.'">'.$atts[0].'</option>';
				}
				$html .= '</select></p>';
				$html .= '<p><label>'.__('Status', 'tracking_email').'</label><select style="width:100%;text-align: center;" name="_tracking_status" id="_tracking_status"><option '.($tracking_status!='ready'?'':'selected="selected"').' value="ready">'.__('Ready to send', 'tracking_email').'</option><option '.($tracking_status!='sent'?'':'selected="selected"').' value="sent">'.__('Sent', 'tracking_email').'</option></select></p>';
				$html .= '<p><button type="button" style="width:100%" class="send_tracking button button-primary" name="save">'.__('Send tracking number', 'tracking_email').'</button></p>';
				echo $html;
			}
			public function meta_tracking()
			{
				global $post;
				$tracking_events = self::get_meta($post->ID, '_tracking_events');
				$html = '';
				if ($tracking_events != NULL) {
					foreach ($tracking_events as $event) {
						$html .= '<p><small><strong>'.$event['post'].'</strong></small><br />'.$event['event'].'</p>';
					}
					$html .= '<button type="button" style="width:100%" class="refresh_tracking button button-primary">Refresh</button>';
				}else{
					$html .= __('Unknown shipment status', 'tracking_email');
				}
				echo $html;
			}
			public function css()
			{
				echo '<style>.invoice-alert {display: none;position: relative;padding: 0.75rem 1.25rem;margin-bottom: 1rem;border: 1px solid transparent;border-radius: 0.25rem;}.invoice-alert-heading {color: inherit;}.invoice-alert-secondary {color: #383d41;background-color: #e2e3e5;border-color: #d6d8db;}.invoice-alert-secondary hr {border-top-color: #c8cbcf;}.invoice-alert-danger {color: #721c24;background-color: #f8d7da;border-color: #f5c6cb;}.invoice-alert-danger hr {border-top-color: #f1b0b7;}</style>';
			}
			public function javascript()
			{ ?>
<script async="async" defer="defer">
	function async_jquery(e,n){var t=document,a="script",c=t.createElement(a),r=t.getElementsByTagName(a)[0];c.src=e,n&&c.addEventListener("load",function(e){n(null,e)},!1),r.parentNode.insertBefore(c,r)}
	async_jquery("https://code.jquery.com/jquery-3.3.1.min.js",function(){
		$(".rapiddev_update_shipment_status:not(:disabled)").on("click",function(e){
			var post_id = $(this).attr("post_id");
			e.preventDefault(),$(this).attr("disabled",!0),jQuery.ajax({
				url:"<?php echo admin_url('admin-ajax.php'); ?>",
				type:"post",
				data:{
					action:"tracking_email_ajax_tracking",
					post_id:post_id,
					carrier: $(this).attr('carrier'),
					tracking_number:$(this).attr("tracking_number")
				},success:function(d){
					console.log(d);
					$('.rapiddev_update_shipment_status').attr("disabled",!1);
					if(d != "error"){
						$('#tracking_status_event-'+post_id).html(d);
					}else{
						$('#tracking_status_event-'+post_id).html('<?php echo __('Error occurred while checking the package', 'tracking_email'); ?>');
					}
				}
			});
		});
		$(".send_tracking:not(:disabled)").on("click",function(e){
			e.preventDefault(),$(this).attr("disabled",!0),$(".alert-result-sending").slideToggle(),jQuery.ajax({
				url:"<?php echo admin_url('admin-ajax.php'); ?>",
				type:"post",
				data:{
					action:"tracking_email_ajax",
					order_id:$("#invoice_order_id").val(),
					tracking:$("#_tracking_number").val(),
					service:$("#_tracking_service").val(),
					status:$("#_tracking_status").val()
				},success:function(e){
					console.log(e);
					$(".alert-result-sending").slideToggle();
					if(e == "success"){
						if ($(".alert-result-error").is(":visible")){
							$(".alert-result-error").slideToggle();
						}
						if($(".alert-result-success").is(":hidden")){
							$(".alert-result-success").slideToggle();
						}else{
							$(".alert-result-success").slideToggle(function(){
								$(".alert-result-success").slideToggle();
							});
						}
					}else{
						if ($(".alert-result-success").is(":visible")){
							$(".alert-result-success").slideToggle();
						}
						if ($(".alert-result-error").is(":hidden")){
							$(".alert-result-error").slideToggle();
						}else{
							$(".alert-result-error").slideToggle(function(){
								$(".alert-result-error").slideToggle();
							});
						}
					}
				}
			});
		});
	});
</script><?php
			}
			public function admin_alert()
			{
				switch(RAPIDDEV_TRACKING_EMAIL_ERROR)
				{
					case 1:
						$message = str_replace('%s', RAPIDDEV_TRACKING_EMAIL_PHP_VERSION,__('Your PHP version is outdated. Please, upgrade your PHP to a higher or equal version than %s.', 'tracking_email'));
						break;
					default:
						$message = __('There was an unidentified error. We should look deeper...', 'tracking_email');
						break;
				}
				//Reset version check
				delete_option('tracking_email_versions');
				echo '<div class="error notice"><p><strong>'.RAPIDDEV_TRACKING_EMAIL_NAME.'</strong><br />'.$message.'</p><p><i>'.__('ERROR ID', 'tracking_email').': '.RAPIDDEV_TRACKING_EMAIL_ERROR.'</i></p></div>';
			}
			private function get_meta($post_id, $meta, $default = '')
			{
				$get_meta = get_post_meta($post_id, $meta, true);
				return $get_meta ? $get_meta : $default;
			}
			private function admin_notice($id = 0)
			{
				define('RAPIDDEV_TRACKING_EMAIL_ERROR', $id);
				add_action('admin_notices', array($this, 'admin_alert'));
			}
			private function check_versions()
			{
				$compatibility = get_option('tracking_email_versions', FALSE);
				if ($compatibility)
				{
					return TRUE;
				}
				else
				{
					$php = FALSE;$wp = FALSE;$wc = FALSE;
					//Check PHP
					if (version_compare(PHP_VERSION, RAPIDDEV_TRACKING_EMAIL_PHP_VERSION, '>=')){$php = TRUE;}
					//Check WordPress
					global $wp_version;
					if (version_compare($wp_version, RAPIDDEV_TRACKING_EMAIL_WP_VERSION, '>=')){$wp = TRUE;}
					//Check WooCommerce
					if (!function_exists('get_plugins')){include_once(ABSPATH.'wp-admin/includes/plugin.php');}
					if (is_plugin_active('woocommerce/woocommerce.php')){if(version_compare(get_plugins('/'.'woocommerce')['woocommerce.php']['Version'], RAPIDDEV_TRACKING_EMAIL_WC_VERSION, '>=' ) == TRUE){$wc = TRUE;}}
					
					if ($php && $wp && $wc) {
						update_option('tracking_email_versions', TRUE);
						return TRUE;
					}
					else
					{
						if($wp && $wc)
						{
							self::admin_notice(1);
						}
						else if($wc)
						{
							self::admin_notice(2);
						}
						else
						{
							self::admin_notice(3);
						}
						return FALSE;
					}					
				}
			}
		}
	}