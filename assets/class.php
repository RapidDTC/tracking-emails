<?php defined('ABSPATH') or die('No script kiddies please!');
/**
 * @package WordPress
 * @subpackage Tracking: Emails and Notifications for WooCommerce
 *
 * @author Leszek Pomianowski
 * @copyright Copyright (c) 2018-2019, RapidDev
 * @link https://www.rdev.cc/
 * @license http://opensource.org/licenses/MIT
 */	

 	/**
	*
	* RDEV_TRACK
	*
	* @author   Leszek Pomianowski <https://rdev.cc>
	* @version  $Id: class.php;RDEV_TRACK,v 1.3.0 2019/10/11
	* @access   public
	*/
	if(!class_exists('RDEV_TRACK'))
	{
		class RDEV_TRACK
		{
			private $providers = array(
				'usps' => array('USPS', 'https://www.usps.com/search/results.htm?keyword=%s'),
				'fedex' => array('FedEx', 'https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=%s'),
				'ups' => array('UPS', 'https://www.ups.com/track?tracknum=%s&requester=WT/trackdetails'),
				'china_ems' => array('EMS', 'http://www.ems.com.cn/mailtracking/e_you_jian_cha_xun.html'),
				'dhl' => array('DHL', 'http://www.dhl.com.pl/en/express/tracking.html?AWB=%s'),
				'tnt' => array('TNT', 'https://www.tnt.com/express/en_us/site/tracking.html?searchType=con&cons=%s'),
				'india_post' => array('India Post', 'https://www.indiapost.gov.in/vas/Pages/IndiaPostHome.aspx'),
				'dpd' => array('DPD', 'https://tracking.dpd.de/parcelstatus?query=%s'),
				'gls' => array('GLS', 'https://gls-group.eu/PL/en/parcel-tracking'),
				'postnl' => array('PostNL', 'https://postnl.post/tracktrace'),
				'schenker' => array('Schenker', 'https://was.schenker.nu/ctts-a/com.dcs.servicebroker.http.HttpXSLTServlet?request.service=CTTSTYPEA&request.method=search&clientid=&reference_type=*SHP&reference_number=%s'),
				'royal_mail' => array('Royal Mail', 'https://www.royalmail.com/track-your-item'),
				'blue_dart' => array('Blue Dart', 'https://www.bluedart.com/tracking'),
				'japan_post' => array('Japan Post', 'http://tracking.post.japanpost.jp/services/sp/srv/search/?requestNo1=%s&search=Beginning&locale=en'),
				'inpost' => array('InPost', 'https://inpost.pl/sledzenie-przesylek?number=%s'),
				'polish_post' => array('Poczta Polska', 'https://emonitoring.poczta-polska.pl/?numer=%s'),
				'envelo' => array('Envelo', 'https://emonitoring.poczta-polska.pl/?numer=%s'),
				'other_carrier' => array('Other carrier', 'https://t.17track.net/en#nums=%s'),
			);

			/**
			* init
			* Registers class methods in WordPress without assigning to an object.
			*
			* @access   public
			*/
			public static function init()
			{
				return new RDEV_TRACK();
			}

			/**
			* __construct
			* The constructor registers the language domain, actions, filters and other actions.
			*
			* @access   public
			*/
			public function __construct()
			{

				//Languages
				add_action('plugins_loaded', function()
				{
					load_plugin_textdomain('tracking_email',FALSE,basename(RDEV_TRACK_PATH).'/languages/');
				});

				//Main verify
				if(self::verify_integrity())
				{

					//Register all meta stuff
					add_action('add_meta_boxes', function()
					{
						add_meta_box('tracking_email', __('Shipment tracking','tracking_email'), array($this, 'order_meta'), 'shop_order', 'side', 'core' );
					});

					//Css for alerts
					add_action('admin_head', array($this, 'css'));

					//Save tracking info during order update
					add_action('save_post', array($this, 'save_meta'), 10, 1);

					//Customer order page
					add_action('woocommerce_order_details_after_customer_details', array($this, 'customer_meta'), 10, 1);

					//Scripts
					add_action('admin_enqueue_scripts', function($suffix)
					{
						if($suffix == 'edit.php' || $suffix == 'post.php')
						{
							wp_enqueue_script('tracking-email-js', RDEV_TRACK_URL . 'assets/rdev-tracking.js', array('jquery'));
						}
					});

					//Data for scripts
					add_action('admin_head', function()
					{
						global $pagenow;
						if($pagenow == 'edit.php' || $pagenow == 'post.php')
						{
							echo '<script>var rdev_tracking = {url:"'.admin_url('admin-ajax.php').'",nonce:"'.wp_create_nonce('rdev_tracking_nonce').'"};</script>';
						}
					});

					//Send mail
					add_action('wp_ajax_rdev_tracking', array($this, 'ajax'));
					add_action('wp_ajax_nopriv_rdev_tracking',array($this, 'ajax'));
				}
			}

			public function css()
			{
				echo '<style>.rdev-tracking-alert {display: none;position: relative;padding: 0.75rem 1.25rem;margin-bottom: 1rem;border: 1px solid transparent;border-radius: 0.25rem;}.rdev-tracking-alert-heading {color: inherit;}.rdev-tracking-alert-secondary {color: #383d41;background-color: #e2e3e5;border-color: #d6d8db;}.rdev-tracking-alert-secondary hr {border-top-color: #c8cbcf;}.rdev-tracking-alert-danger {color: #721c24;background-color: #f8d7da;border-color: #f5c6cb;}.rdev-tracking-alert-danger hr {border-top-color: #f1b0b7;}</style>';
			}

			/**
			* order_meta
			* Package tracking form in the order page.
			*
			* @access   public
			*/
			public function order_meta()
			{
				global $post;

				$meta_data = array(
					'number' => get_post_meta( $post->ID, '_tracking_number', true ) ? get_post_meta( $post->ID, '_tracking_number', true ) : '',
					'service' => get_post_meta( $post->ID, '_tracking_service', true ) ? get_post_meta( $post->ID, '_tracking_service', true ) : '',
					'status' => get_post_meta( $post->ID, '_tracking_status', true ) ? get_post_meta( $post->ID, '_tracking_status', true ) : ''
				);

				$html = '<div id="rdev-tracking-sending" class="rdev-tracking-alert rdev-tracking-alert-secondary"><p>'.__('The message is being sent...', 'tracking_email').'</p></div>';
				$html .= '<div id="rdev-tracking-send" class="rdev-tracking-alert rdev-tracking-alert-secondary"><h4 class="rdev-tracking-alert-heading">'.__('Well done', 'tracking_email').'!</h4><hr><p>'.__('The tracking information was sent to the client. The note has been added to your order. You can now save your changes so you do not lose your tracking number and status.', 'tracking_email').'</p></div>';
				$html .= '<div id="rdev-tracking-error" class="rdev-tracking-alert rdev-tracking-alert-danger"><h4 class="rdev-tracking-alert-heading">'.__('An error occured', 'tracking_email').'</h4><hr><p>'.__('An error occurred while sending tracking information.', 'tracking_email').'</p></div>';

				$html .= '<input type="hidden" name="tracking_email_meta_nonce" value="'.wp_create_nonce().'">';
				$html .= '<input id="tracking_order_id" name="tracking_order_id" type="hidden" value="'.$post->ID.'">';
				$html .= '<label for="tracking_number">'.__('Tracking number', 'tracking_email').'</label>';
				$html .= '<input id="tracking_number" name="tracking_number" type="text" style="width:100%;" placeholder="'.$meta_data['number'].'" value="'.$meta_data['number'].'">';
				$html .= '<p><label for="tracking_service">'.__('Carrier', 'tracking_email').'</label><select style="width:100%;cursor:pointer;" name="tracking_service" id="tracking_service">';
				foreach ($this->providers as $key => $value)
				{
					$html .= '<option'.($meta_data['service'] == $key ? ' selected="selected"' : '').' value="'.$key.'">'.$value[0].'</option>';
				}
				$html .= '</select></p>';
				$html .= '<p><label for="tracking_status">'.__('Tracking status', 'tracking_email').'</label><select style="width:100%;cursor:pointer;" name="tracking_status" id="tracking_status">';
				$html .= '<option'.($meta_data['status'] == 'ready' ? ' selected="selected"':'').' value="ready">'.__('Ready to send', 'tracking_email').'</option>';
				$html .= '<option'.($meta_data['status'] == 'sent' ? ' selected="selected"':'').' value="sent">'.__('Sent', 'tracking_email').'</option>';
				$html .= '</select></p>';
				$html .= '<p><button id="rdev_send_tracking" type="button" style="width:100%" class="button button-primary" name="save">'.__('Send tracking number', 'tracking_email').'</button></p>';

				echo $html;
			}

			/**
			* save_meta
			* Save changes to the meta if the user updates the order.
			*
			* @access   public
			* @param	int $id
			*/
			public function save_meta($id)
			{
				if (!isset($_POST['tracking_email_meta_nonce']))
					return $id;

				if(isset($_REQUEST['tracking_email_meta_nonce'])){
					if (!wp_verify_nonce($_REQUEST['tracking_email_meta_nonce']))
						return $id;
				}else{return $id;}

				if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
					return $id;

				if (!current_user_can('edit_page', $id))
					return $id;

				update_post_meta($id, '_tracking_number', sanitize_text_field($_POST[ 'tracking_number' ]));
				update_post_meta($id, '_tracking_service', sanitize_text_field($_POST[ 'tracking_service']));
				update_post_meta($id, '_tracking_status', sanitize_text_field($_POST[ 'tracking_status' ]));
			}

			/**
			* customer_meta
			* Display tracking information in the customer's order summary.
			*
			* @access   public
			* @param	object $order
			*/
			public function customer_meta($order)
			{
				$id = $order->get_id();

				$meta_data = array(
					'number' => get_post_meta( $id, '_tracking_number', true ) ? get_post_meta( $id, '_tracking_number', true ) : '',
					'service' => get_post_meta( $id, '_tracking_service', true ) ? get_post_meta( $id, '_tracking_service', true ) : '',
					'status' => get_post_meta( $id, '_tracking_status', true ) ? get_post_meta( $id, '_tracking_status', true ) : ''
				);

				if($meta_data['number'] != '')
				{
					$html = '<section class="rdev-tracking-customer">';
					$html .= '<h2 class="woocommerce-column__title">'.__('Package', 'tracking_email').'</h2>';
					$html .= '<p>'.($meta_data['status'] == 'ready' ? __('Your package is ready to be sent', 'tracking_email') : __('Your package has been sent', 'tracking_email')).'</p>';
					$html .= '<p>'.__('The number of your package is', 'tracking_email').':<br/><strong>'.$meta_data['number'].'</strong></p>';
					$html .= '<a href="'.str_replace('%s', preg_replace("/[^A-Z0-9]+/", '', strtoupper($meta_data['number'])),$this->providers[$meta_data['service']][1]).'" target="_blank" rel="noopener" class="woocommerce-button button view">'.__('Track your parcel', 'tracking_email').'</a>';
					$html .= '</section>';
					echo $html;
				}
			}

			/**
			* ajax
			* Sends email and updates the meta fields.
			*
			* @access   public
			*/
			public function ajax()
			{
				//Verify salt	
				check_ajax_referer('rdev_tracking_nonce', 'nonce');

				//Response array for json
				$response = array(
					'status' => 1,
					'response' => 'error_0'
				);

				//Error protection verify
				if(self::emergency_verification())
				{
					if(isset($_POST['order_id']) && isset($_POST['tracking_number']) && isset($_POST['tracking_status']) && isset($_POST['carrier']))
					{
						$data = array(
							'ID' => sanitize_text_field($_POST['order_id']),
							'raw_number' => sanitize_text_field($_POST['tracking_number']),
							'status' => sanitize_text_field($_POST['tracking_status']),
							'carrier' => sanitize_text_field($_POST['carrier'])
						);

						if($data['raw_number'] != '')
						{
							//Force update meta
							update_post_meta($data['ID'], '_tracking_number', $data['raw_number']);
							update_post_meta($data['ID'], '_tracking_service', $data['carrier']);
							update_post_meta($data['ID'], '_tracking_status', $data['status']);

							//Remove additional chars
							if ($data['raw_number'] != '')
								$data['number'] = preg_replace("/[^A-Z0-9]+/", '', strtoupper($data['raw_number']));

							//Define tracking url
							$data['url'] = str_replace('%s', $data['number'],$this->providers[$data['carrier']][1]);

							//Order notice
							$order = wc_get_order($data['ID']);
							$order->add_order_note(
								'<p>'.str_replace(
									'%s',
									($data['status'] == 'ready' ? __('Ready to send', 'tracking_email') : __('Sent', 'tracking_email')),
									__('Shipment information sent to the client,<br/>Status: %s', 'tracking_email')
								).'</p><p><a target="_blank" rel="nofollow noopener" href="'.esc_url($data['url']).'" class="button button-secondary" style="width:100%;text-align:center;margin-top:15px;">'.__('Track the package', 'tracking_email').'</a></p>'
							);
							$order->save();

							$response['mailer'] = self::tracking_email($order, $data);

							if($response['mailer'] == 1)
								$response['response'] = 'success';
							else
								$response['response'] = 'error_3';
						}else{
							$response['response'] = 'error_2';
						}
					}else{
						$response['response'] = 'error_1';
					}
				}else{
					$response['response'] = 'error_4';
				}
				exit(json_encode($response, JSON_UNESCAPED_UNICODE));
			}

			/**
			* tracking_email
			* Sends email to the client.
			*
			* @access   public
			* @param	object $order
			* @param	array $data
			* @return	int 1/string $exception
			*/
			private function tracking_email($order, $data)
			{
				//Prepare mailer
				$mailer = WC()->mailer();

				//Try send mail
				try {
					$order_data = $order->get_data();
					$mailer->send(
						$order_data['billing']['email'],
						__('Order', 'tracking_email').' #'.$data['ID'].' - '.__('Information about your package', 'tracking_email'),
						wc_get_template_html(
							'tracking_email.php',
							array(
								'blog_name'			=> get_bloginfo('name'),
								'order'				=> $order,
								'email_heading'		=> ($data['status'] == 'ready' ? __('Your parcel is ready to be sent!', 'tracking_email') : __('Your package has been sent!', 'tracking_email')),
								'tracking_number'	=> $data['raw_number'],
								'tracking_url'		=> $data['url'],
								'tracking_carrier'	=> $this->providers[$data['carrier']][0],
								'tracking_status'	=> $data['status'],
								'sent_to_admin'		=> false,
								'plain_text'		=> false,
								'email'				=> $mailer
							),
							'/emails/', RDEV_TRACK_PATH.'/emails/'
						),
						"Content-Type: text/html\r\n"
					);
					return 1;
				} catch (Exception $e) {
					return $e;	
				}
			}

			/**
			* admin_notice
			* Defines an error code and display an alert on the WordPress admin page.
			*
			* @access   private
			* @param	int $id
			*/
			private function admin_notice($id = 0)
			{
				define('RDEV_TRACK_ERROR', $id);
				add_action('admin_notices', function(){
					switch(RDEV_TRACK_ERROR)
					{
						case 1:
							$message = str_replace('%s', RDEV_TRACK_PHP_VERSION,__('Your PHP version is outdated. Please, upgrade your PHP to a higher or equal version than %s.', 'tracking_email'));
							break;
						case 2:
							$message = str_replace('%s', RDEV_TRACK_WP_VERSION,__('Your WordPress version is outdated. Please, upgrade your WordPress to a higher or equal version than %s.', 'tracking_email'));
							break;
						case 3:
							$message = str_replace(array('%s', '%t', '%u'), array('<a href="'.admin_url('plugins.php').'">', '</a>', RDEV_TRACK_WC_VERSION),__('The WooCoomerce plug-in is outdated or disabled. Check if the plugin is enabled %shere%t or upgrade to version %u.', 'tracking_email'));
							break;
						default:
							$message = __('There was an unidentified error. We should look deeper...', 'tracking_email');
							break;
					}
					delete_option('rdev_tracking_verify');
					echo '<div class="error notice"><p><strong>'.RDEV_TRACK_NAME.'</strong><br />'.$message.'</p><p><i>'.__('ERROR ID', 'tracking_email').': '.RDEV_TRACK_ERROR.'</i></p></div>';
				});
			}

			/**
			* emergency_verification
			* Checking if the function exists just in case.
			*
			* @access   private
			* @return	bool	true/false
			*/
			private function emergency_verification()
			{
				if(function_exists('WC'))
				{
					return TRUE;
				}else{
					update_option('rdev_tracking_verify', FALSE);
					return FALSE;
				}
			}

			/**
			* verify_integrity
			* Checks version compatibility.
			*
			* @access   private
			* @return	bool	true/false
			*/
			private function verify_integrity()
			{
				if (get_option('rdev_tracking_verify', FALSE))
					return TRUE;
				
				//Check PHP
				$php = FALSE;
				if (version_compare(PHP_VERSION, RDEV_TRACK_PHP_VERSION, '>='))
					$php = TRUE;
				
				//Check WordPress
				global $wp_version;
				$wp = FALSE;
				if (version_compare($wp_version, RDEV_TRACK_WP_VERSION, '>='))
					$wp = TRUE;

				//Check WooCommerce
				$wc = FALSE;
				if (!function_exists('get_plugins'))
				{
					include_once(ABSPATH.'wp-admin/includes/plugin.php');
					if (is_plugin_active('woocommerce/woocommerce.php'))
						if(version_compare(get_plugins('/'.'woocommerce')['woocommerce.php']['Version'], RDEV_TRACK_WC_VERSION, '>=' ) == true)
							$wc = TRUE;
				}

				if ($php && $wp && $wc)
				{
					update_option('rdev_tracking_verify', TRUE);
					return TRUE;
				}
				else
				{
					if($wp && $wc)
						self::admin_notice(1);
					else if($wc && $php)
						self::admin_notice(2);
					else
						self::admin_notice(3);
					return FALSE;					
				}
			}
		}
	}
?>