<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/**
 * @package WordPress
 * @subpackage Invoice and Tracking for WooCommerce
 *
 * @author Leszek Pomianowski
 * @copyright Copyright (c) 2018, RapidDev
 * @link https://www.rapiddev.pl/rapiddev-productseo
 * @license http://opensource.org/licenses/MIT
 */
?><style>.btn {text-decoration: none;display:inline-block;font-weight:400;text-align:center;white-space:nowrap;vertical-align:middle;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;border:1px solid transparent;padding:.375rem .75rem;font-size:1rem;line-height:1.5;border-radius:.25rem;transition:color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out}.btn-block {width: 100%;}.btn-outline-secondary{color:#6c757d;background-color:transparent;background-image:none;border-color:#6c757d}.btn-outline-secondary:hover{color:#fff;background-color:#6c757d;border-color:#6c757d}.btn-outline-secondary.focus,.btn-outline-secondary:focus{box-shadow:0 0 0 .2rem rgba(108,117,125,.5)}.btn-outline-secondary.disabled,.btn-outline-secondary:disabled{color:#6c757d;background-color:transparent}.btn-outline-secondary:not(:disabled):not(.disabled).active,.btn-outline-secondary:not(:disabled):not(.disabled):active,.show>.btn-outline-secondary.dropdown-toggle{color:#fff;background-color:#6c757d;border-color:#6c757d}.btn-outline-secondary:not(:disabled):not(.disabled).active:focus,.btn-outline-secondary:not(:disabled):not(.disabled):active:focus,.show>.btn-outline-secondary.dropdown-toggle:focus{box-shadow:0 0 0 .2rem rgba(108,117,125,.5)}</style>
<?php
do_action( 'woocommerce_email_header', $data['blog_name'].' - '.__('Shipment status', 'tracking_email'), $email ); ?>

<h1><?php echo $data['subject']; ?></h1>

<p><?php _e('The number of your package is', 'tracking_email') ?> <br /><strong><?php echo $data['tracking_number']; ?><strong></p>
<hr>
<p><?php _e('Sent with', 'tracking_email') ?> <?php echo $data['carrier']; ?></p>
<p style="margin-top:20px;margin-bottom:20px;">
	<a class="btn btn-block btn-outline-secondary" role="button" href="<?php echo esc_url($data['url']); ?>"><?php _e('Track your parcel', 'tracking_email') ?></a>
</p>

<?php
	do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
	do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
	do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
	do_action( 'woocommerce_email_footer', $email );
?>