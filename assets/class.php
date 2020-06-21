<?php defined('ABSPATH') or die('No script kiddies please!');
/**
 * @package WordPress
 * @subpackage Tracking: Emails and Notifications for WooCommerce
 *
 * @author Leszek Pomianowski
 * @copyright Copyright (c) 2018-2020, RapidDev
 * @link https://www.rdev.cc/
 * @license http://opensource.org/licenses/MIT
 */	

 	/**
	*
	* RDEVTracking
	*
	* @author   Leszek Pomianowski <https://rdev.cc>
	* @version  1.4.0
	* @access   public
	*/
	if(!class_exists('RDEVTracking'))
	{
		class RDEVTracking
		{
			private static $providers = array(
				'usps' => array('USPS', 'https://www.usps.com/search/results.htm?keyword=%s', false),
				'fedex' => array('FedEx', 'https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=%s', false),
				'ups' => array('UPS', 'https://www.ups.com/track?tracknum=%s&requester=WT/trackdetails', false),
				'china_ems' => array('EMS', 'http://www.ems.com.cn/mailtracking/e_you_jian_cha_xun.html', false),
				'dhl' => array('DHL', 'http://www.dhl.com.pl/en/express/tracking.html?AWB=%s', false),
				'tnt' => array('TNT', 'https://www.tnt.com/express/en_us/site/tracking.html?searchType=con&cons=%s', false),
				'india_post' => array('India Post', 'https://www.indiapost.gov.in/vas/Pages/IndiaPostHome.aspx', false),
				'dpd' => array('DPD', 'https://tracking.dpd.de/parcelstatus?query=%s', false),
				'gls' => array('GLS', 'https://gls-group.eu/PL/en/parcel-tracking', false),
				'postnl' => array('PostNL', 'https://postnl.post/tracktrace', false),
				'schenker' => array('Schenker', 'https://was.schenker.nu/ctts-a/com.dcs.servicebroker.http.HttpXSLTServlet?request.service=CTTSTYPEA&request.method=search&clientid=&reference_type=*SHP&reference_number=%s', false),
				'royal_mail' => array('Royal Mail', 'https://www.royalmail.com/track-your-item', false),
				'blue_dart' => array('Blue Dart', 'https://www.bluedart.com/tracking', false),
				'japan_post' => array('Japan Post', 'http://tracking.post.japanpost.jp/services/sp/srv/search/?requestNo1=%s&search=Beginning&locale=en', false),
				'inpost' => array('InPost', 'https://inpost.pl/sledzenie-przesylek?number=%s', false),
				'polish_post' => array('Poczta Polska', 'https://emonitoring.poczta-polska.pl/?numer=%s', true),
				'envelo' => array('Envelo', 'https://emonitoring.poczta-polska.pl/?numer=%s', true),
				'other_carrier' => array('Other carrier', 'https://t.17track.net/en#nums=%s', false),
			);

			/**
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
				if(self::VerifyIntegrity())
				{

					//Register all meta stuff
					add_action('add_meta_boxes', function()
					{
						add_meta_box('tracking_email', __('Shipment tracking','tracking_email'), array($this, 'OrderMeta'), 'shop_order', 'side', 'core' );
					});

					//CSS for alerts
					add_action('admin_head', array($this, 'CSS'));

					//Save tracking info during order update
					add_action('save_post', array($this, 'SaveMeta'), 10, 1);

					//Customer order page
					add_action('woocommerce_order_details_after_customer_details', array($this, 'CustomerMeta'), 10, 1);

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
							echo '<script>let rdev_tracking = {url:"'.admin_url('admin-ajax.php').'",nonce:"'.wp_create_nonce('rdev_tracking_nonce').'"};</script>';
						}
					});

					//Update status
					add_action('wp_ajax_rdev_tracking_update', array($this, 'AjaxUpdateTracking'));
					add_action('wp_ajax_nopriv_rdev_tracking_update',array($this, 'AjaxUpdateTracking'));

					//Send mail
					add_action('wp_ajax_rdev_tracking', array($this, 'AjaxSendTracking'));
					add_action('wp_ajax_nopriv_rdev_tracking',array($this, 'AjaxSendTracking'));

					//Orders list column
					$this->OrdersColumn();
				}
			}

			public function CSS()
			{
				echo '<style>.rapiddev_tracking_status{display:flex;width:100% !important;height:100%;align-items:center;}.rdev-tracking-status{overflow:hidden;margin-left:10px;max-width:100%;word-wrap:unset;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;}.button.rdev-tracking-refresh,.button.rdev-tracking-button{padding:4px;display:inline-flex;align-items:center;}.rdev-tracking-alert{display: none;position: relative;padding: 0.75rem 1.25rem;margin-bottom: 1rem;border: 1px solid transparent;border-radius: 0.25rem;}.rdev-tracking-alert-heading {color: inherit;}.rdev-tracking-alert-secondary{color:#383d41;background-color:#e2e3e5;border-color: #d6d8db;}.rdev-tracking-alert-secondary hr{border-top-color: #c8cbcf;}.rdev-tracking-alert-danger{color: #721c24;background-color: #f8d7da;border-color: #f5c6cb;}.rdev-tracking-alert-danger hr{border-top-color: #f1b0b7;}.button.rdev-rotate-button>span{-webkit-animation:tracking-update-spin 4s linear infinite;-moz-animation:tracking-update-spin 4s linear infinite;animation:tracking-update-spin 4s linear infinite}@-moz-keyframes tracking-update-spin{100%{-moz-transform:rotate(360deg);}}@-webkit-keyframes tracking-update-spin {100%{-webkit-transform:rotate(360deg);}}@keyframes tracking-update-spin{100%{-webkit-transform: rotate(360deg);transform:rotate(360deg);}</style>';
			}

			protected function OrdersColumn()
			{
				add_filter('manage_edit-shop_order_columns', function($columns)
				{
					$reordered = array();
					foreach( $columns as $key => $column)
					{
						$reordered[$key] = $column;
						if( $key ==  'order_status')
						{
							$reordered['rapiddev_tracking_status'] = __('Tracking status', 'tracking_email');
						}
					}
					return $reordered;
				}, 20);

				add_action('manage_shop_order_posts_custom_column', function($column, $post_id)
				{
					if ('rapiddev_tracking_status' != $column)
						return;

					$meta_data = array(
						'number' => get_post_meta( $post_id, '_tracking_number', true ) ? get_post_meta( $post_id, '_tracking_number', true ) : '',
						'service' => get_post_meta( $post_id, '_tracking_service', true ) ? get_post_meta( $post_id, '_tracking_service', true ) : '',
						'status' => get_post_meta( $post_id, '_tracking_status', true ) ? get_post_meta( $post_id, '_tracking_status', true ) : '',
						'event' => get_post_meta( $post_id, '_tracking_event', true ) ? get_post_meta( $post_id, '_tracking_event', true ) : '',
						'saved_events' => get_post_meta( $post_id, '_tracking_events_list', true ) ? get_post_meta( $post_id, '_tracking_events_list', true ) : array(),
					);

					$meta_data['raw_number'] = self::ParseTracking($meta_data['number']);

					$is_button = false;
					$message = '';

					if(empty($meta_data['number']))
					{
						$message = __('No tracking number', 'tracking_email');
					}
					else if(isset(self::$providers[$meta_data['service']]))
					{
						if(self::$providers[$meta_data['service']][2])
						{
							if(empty($meta_data['event']))
								$message = __('Unknown shipment status', 'tracking_email');
							else
								$message = $meta_data['event'];

							$is_button = true;
						}
						else
						{
							$message = __('Shipping carrier not supported', 'tracking_email');
						}
					}
					else
					{
						$message = __('Shipping carrier not supported', 'tracking_email');
					}

					if(!empty($meta_data['number']) && isset(self::$providers[$meta_data['service']]))
					{
						$meta_data['direct_link'] = str_replace('%s', $meta_data['raw_number'], self::$providers[$meta_data['service']][1]);
					}
					else
					{
						$meta_data['direct_link'] = '';
					}

					if(!empty($meta_data['saved_events']))
					{
						$events_list = json_decode($meta_data['saved_events'], true);

						if(isset($events_list[0]['event']))
							$last_event = end($events_list)['event'];
					}
					else
					{
						$last_event = '';
					}

					$html  = '';
					$html .= '<a' . (empty($meta_data['direct_link']) ? ' disabled="disabled"' : '') . ' href="' . (empty($meta_data['direct_link']) ? '#' : $meta_data['direct_link']) . '" target="_blank" rel="noopener" class="rdev-tracking-button button" type="button" style="margin-right:3px;"><span class="dashicons dashicons-admin-links"></span></a>';
					$html .= '<button' . (!$is_button ? ' disabled="disabled"' : '') . ' data-id="' . $meta_data['raw_number'] . '" data-service="' . $meta_data['service'] . '" data-nonce="' . wp_create_nonce('rdev_tracking_update_nonce') . '" data-post_id="' . $post_id . '" class="rdev-tracking-refresh button" type="button"><span class="dashicons dashicons-update"></span></button>';
					$html .= '<span class="rdev-tracking-status"><strong id="rdev-tracking-status-message-' . $post_id . '">' . (empty($last_event) ? $message : $last_event) . '</strong></span>';
					echo $html;
				}, 20, 2);
			}

			private static function ParseTracking($number)
			{
				return preg_replace("/[^A-Z0-9]+/", '', strtoupper($number));
			}

			/**
			* Package tracking form in the order page.
			*
			* @access   public
			*/
			public function OrderMeta()
			{
				global $post;

				$meta_data = array(
					'number' => get_post_meta( $post->ID, '_tracking_number', true ) ? get_post_meta( $post->ID, '_tracking_number', true ) : '',
					'service' => get_post_meta( $post->ID, '_tracking_service', true ) ? get_post_meta( $post->ID, '_tracking_service', true ) : '',
					'status' => get_post_meta( $post->ID, '_tracking_status', true ) ? get_post_meta( $post->ID, '_tracking_status', true ) : '',
					'saved_events' => get_post_meta( $post->ID, '_tracking_events_list', true ) ? get_post_meta( $post->ID, '_tracking_events_list', true ) : array(),
				);

				$html = '<div id="rdev-tracking-sending" class="rdev-tracking-alert rdev-tracking-alert-secondary"><p>'.__('The message is being sent...', 'tracking_email').'</p></div>';
				$html .= '<div id="rdev-tracking-send" class="rdev-tracking-alert rdev-tracking-alert-secondary"><h4 class="rdev-tracking-alert-heading">'.__('Well done', 'tracking_email').'!</h4><hr><p>'.__('The tracking information was sent to the client. The note has been added to your order. Tracking number has been assigned to the order.', 'tracking_email').'</p></div>';
				$html .= '<div id="rdev-tracking-error" class="rdev-tracking-alert rdev-tracking-alert-danger"><h4 class="rdev-tracking-alert-heading">'.__('An error occured', 'tracking_email').'</h4><hr><p>'.__('An error occurred while sending tracking information.', 'tracking_email').'</p></div>';

				$html .= '<input type="hidden" name="tracking_email_meta_nonce" value="'.wp_create_nonce().'">';
				$html .= '<input id="tracking_order_id" name="tracking_order_id" type="hidden" value="'.$post->ID.'">';
				$html .= '<label for="tracking_number">'.__('Tracking number', 'tracking_email').'</label>';
				$html .= '<input id="tracking_number" name="tracking_number" type="text" style="width:100%;" placeholder="'.$meta_data['number'].'" value="'.$meta_data['number'].'">';
				$html .= '<p><label for="tracking_service">'.__('Carrier', 'tracking_email').'</label><select style="width:100%;cursor:pointer;" name="tracking_service" id="tracking_service">';
				foreach (self::$providers as $key => $value)
				{
					$html .= '<option'.($meta_data['service'] == $key ? ' selected="selected"' : '').' value="'.$key.'">'.$value[0].'</option>';
				}
				$html .= '</select></p>';
				$html .= '<p><label for="tracking_status">'.__('Tracking status', 'tracking_email').'</label><select style="width:100%;cursor:pointer;" name="tracking_status" id="tracking_status">';
				$html .= '<option'.($meta_data['status'] == 'ready' ? ' selected="selected"':'').' value="ready">'.__('Ready to send', 'tracking_email').'</option>';
				$html .= '<option'.($meta_data['status'] == 'sent' ? ' selected="selected"':'').' value="sent">'.__('Sent', 'tracking_email').'</option>';
				$html .= '</select></p>';
				$html .= '<p><button id="rdev_send_tracking" type="button" style="width:100%" class="button button-primary" name="save">'.__('Send tracking number', 'tracking_email').'</button></p>';

				if(!empty($meta_data['saved_events']))
				{
					$events_list = json_decode($meta_data['saved_events'], true);

					if(isset($events_list[0]['event']))
					{
						$html .= '<hr /><ul>';
						foreach ($events_list as $event)
						{
							$html .= '<li><strong><small>'.$event['institution'].'</small></strong><br /><i>'.$event['time'].'</i><br/>'.$event['event'].'</li>';
						}
						$html .= '</ul>';
					}
				}

				echo $html;
			}

			/**
			* Save changes to the meta if the user updates the order.
			*
			* @access   public
			* @param	int $id
			*/
			public function SaveMeta($id)
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
			* Display tracking information in the customer's order summary.
			*
			* @access   public
			* @param	object $order
			*/
			public function CustomerMeta($order)
			{
				$id = $order->get_id();

				$meta_data = array(
					'number' => get_post_meta( $id, '_tracking_number', true ) ? get_post_meta( $id, '_tracking_number', true ) : '',
					'service' => get_post_meta( $id, '_tracking_service', true ) ? get_post_meta( $id, '_tracking_service', true ) : '',
					'status' => get_post_meta( $id, '_tracking_status', true ) ? get_post_meta( $id, '_tracking_status', true ) : ''
				);

				if($meta_data['number'] != '')
				{
					$html = '<section class="woocommerce-columns woocommerce-columns--2 col2-set addresses rdev-tracking-customer" style="margin-top:20px;"><div class="woocommerce-column woocommerce-column--1 col-1">';
					$html .= '<h2 class="woocommerce-column__title">'.__('Package', 'tracking_email').'</h2>';
					$html .= '<p>'.($meta_data['status'] == 'ready' ? __('Your package is ready to be sent', 'tracking_email') : __('Your package has been sent', 'tracking_email')).'</p>';
					$html .= '<p>'.__('The number of your package is', 'tracking_email').':<br/><strong>'.$meta_data['number'].'</strong></p>';
					$html .= '<p>'.__('The package was sent by', 'tracking_email').':<br/><strong>' . self::$providers[$meta_data['service']][0] . '</strong></p>';
					$html .= '<a href="'.str_replace('%s', self::ParseTracking($meta_data['number']),self::$providers[$meta_data['service']][1]).'" target="_blank" rel="noopener" class="woocommerce-button button view" style="width:100%;text-align:center;">'.__('Track your parcel', 'tracking_email').'</a>';
					$html .= '</div></section>';
					echo $html;
				}
			}

			/**
			* Download tracking status from carrier
			*
			* @access   public
			*/
			private function ParseTrackingEvents($events, $carrier)
			{
				$parsed = array();

				switch ($carrier)
				{
					case 'polish_post':
					case 'envelo':
						foreach ($events as $event)
						{

							if(isset($event['czas']))
							{
								$time = $event['czas'];
							}
							else
							{
								$time = 'Unknown';
							}

							if(isset($event['jednostka']['nazwa']))
							{
								$institution = $event['jednostka']['nazwa'];
							}
							else
							{
								$institution = 'Unknown';
							}

							if(isset($event['nazwa']))
							{
								$event_name = $event['nazwa'];
							}
							else
							{
								$event_name = 'Unknown';
							}

							$parsed[] = array(
								'time' => $time,
								'institution' => $institution,
								'event' => $event_name
							);
						}
						break;
				}

				return $parsed;
			}

			/**
			* Download tracking status from carrier
			*
			* @access   public
			*/
			private function GetTracking($number, $carrier)
			{
				$status = false;
				$events = array();
				$parsed = array();

				switch ($carrier)
				{
					case 'polish_post':
					case 'envelo':
						$status = true;
						$package = new RDEV_PolishPost($number);
						$events = $package->get_events();
						$parsed = self::ParseTrackingEvents($events, $carrier);
						break;
					/*case 'inpost':
						$status = true;
						$package = new RDEV_InPost($number);
						break;*/
				}

				return array('s' => $status, 'e' => $events, 'p' => $parsed);
			}

			/**
			* Update racking status via Ajax
			*
			* @access   public
			*/
			public function AjaxUpdateTracking()
			{
				//Verify salt	
				check_ajax_referer('rdev_tracking_update_nonce', 'nonce');

				//Response array for json
				$response = array(
					'status' => 1,
					'response' => 'error_0'
				);

				if(isset($_POST['post_id']) && isset($_POST['tracking_number']) && isset($_POST['carrier']))
				{
					if(isset(self::$providers[$_POST['carrier']]))
					{
						if(self::$providers[$_POST['carrier']][2])
						{
							$carrier_result = self::GetTracking($_POST['tracking_number'], $_POST['carrier']);

							if($carrier_result['s'] != false)
							{
								if(!empty($carrier_result['p']))
								{
									$response['response'] = 'success';
									$response['last_event'] = end($carrier_result['p'])['event'];
									update_post_meta($_POST['post_id'], '_tracking_events_list', json_encode($carrier_result['p'], JSON_UNESCAPED_UNICODE));
								}
								else
								{
									$response['response'] = 'error_7'; //Failed to parse data
								}
							}
							else
							{
								$response['response'] = 'error_7'; //Failed to retrieve data
							}
						}
						else
						{
							$response['response'] = 'error_6'; //unsupported carrier
						}
					}
					else
					{
						$response['response'] = 'error_5'; //invalid carrier
					}
				}
				else
				{
					$response['response'] = 'error_4'; //missing fields
				}

				exit(json_encode($response, JSON_UNESCAPED_UNICODE));
			}

			/**
			* Sends email and updates the meta fields.
			*
			* @access   public
			*/
			public function AjaxSendTracking()
			{
				//Verify salt	
				check_ajax_referer('rdev_tracking_nonce', 'nonce');

				//Response array for json
				$response = array(
					'status' => 1,
					'response' => 'error_0'
				);

				//Error protection verify
				if(self::EmergencyVerification())
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
							$data['url'] = str_replace('%s', $data['number'], self::$providers[$data['carrier']][1]);

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

							$response['mailer'] = self::SendTrackingEmail($order, $data);

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
			* Sends email to the client.
			*
			* @access   public
			* @param	object $order
			* @param	array $data
			* @return	int 1/string $exception
			*/
			private function SendTrackingEmail($order, $data)
			{
				//Prepare mailer
				$mailer = WC()->mailer();

				//Try send mail
				try
				{
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
								'tracking_carrier'	=> self::$providers[$data['carrier']][0],
								'tracking_status'	=> $data['status'],
								'sent_to_admin'		=> false,
								'plain_text'		=> false,
								'email'				=> $mailer
							),
							'/emails/', RDEV_TRACK_PATH . '/emails/'
						),
						"Content-Type: text/html\r\n"
					);
					return 1;
				}
				catch (Exception $e)
				{
					return $e;	
				}
			}

			/**
			* Defines an error code and display an alert on the WordPress admin page.
			*
			* @access   private
			* @param	int $id
			*/
			private function AdminNotice($id = 0)
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
					echo '<div class="error notice"><p><strong>Tracking: Emails and Notifications for WooCommerce</strong><br />'.$message.'</p><p><i>'.__('ERROR ID', 'tracking_email').': '.RDEV_TRACK_ERROR.'</i></p></div>';
				});
			}

			/**
			* Checking if the function exists just in case.
			*
			* @access   private
			* @return	bool	true/false
			*/
			private function EmergencyVerification()
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
			* Checks version compatibility.
			*
			* @access   private
			* @return	bool	true/false
			*/
			private function VerifyIntegrity()
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
						self::AdminNotice(1);
					else if($wc && $php)
						self::AdminNotice(2);
					else
						self::AdminNotice(3);
					return FALSE;					
				}
			}
		}
	}
?>