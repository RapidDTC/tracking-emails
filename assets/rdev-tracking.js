/* ======================================
 *            RDEV Tracking
 * ======================================
 * Copyright © 2019 RapidDev
 * Author: Leszek Pomianowski
 * https://rdev.cc/
 */
 	var jquery_url = 'https://code.jquery.com/jquery-3.4.1.min.js';

 	function console_log(message, color="#fff"){console.log("%cRDEV Tracking: "+"%c"+message, "color:#dc3545;font-weight: bold;", "color: "+color);}
 	function async_jquery(e,n){var t=document,a="script",c=t.createElement(a),r=t.getElementsByTagName(a)[0];c.src=e,n&&c.addEventListener("load",function(e){n(null,e)},!1),r.parentNode.insertBefore(c,r)}
 	console_log('Script was loaded');

	window.onload = function()
	{
		if (window.jQuery)
		{
			console_log('jQuery was detected');
			register_rdev_tracking();
		}else{
			console_log('jQuery was not detected. Downloading from CDN...');
			async_jquery(jquery_url,function()
			{
				console_log('JQuery was downloaded from '+jquery_url);
				register_rdev_tracking();
			});
			
		}
	};

	function register_rdev_tracking()
	{
		jQuery('#rdev_send_tracking').on('click', function(e){
			e.preventDefault();
			console_log('Sending a request to send an email...');

			if(jQuery('#rdev-tracking-send').is(':visible'))
			{
				jQuery('#rdev-tracking-send').hide();
			}

			if(jQuery('#rdev-tracking-error').is(':visible'))
			{
				jQuery('#rdev-tracking-error').hide();
			}

			if(jQuery('#rdev-tracking-error').is(':hidden'))
			{
				jQuery('#rdev-tracking-sending').slideToggle();
			}else{
				jQuery('#rdev-tracking-sending').slideToggle(function(){
					jQuery('#rdev-tracking-sending').slideToggle();
				});
			}

			jQuery.ajax({
				url: rdev_tracking.url,
				type: 'post',
				data: {
					action: 'rdev_tracking',
					nonce: rdev_tracking.nonce,
					order_id: jQuery("#tracking_order_id").val(),
					tracking_number: jQuery("#tracking_number").val(),
					tracking_status: jQuery("#tracking_status").val(),
					carrier: jQuery("#tracking_service").val()
				},
				success: function(e)
				{
					var status = false;
					if(/^[\],:{}\s]*$/.test(e.replace(/\\["\\\/bfnrtu]/g,"@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,"]").replace(/(?:^|:|,)(?:\s*\[)+/g,"")))
					{
						var o = JSON.parse(e);
						if(o.hasOwnProperty('status'))
						{
							if(o.response == 'success'){
								status = true;
							}
						}
					}
					console_log('Attempt was successful.');
					jQuery('#rdev-tracking-sending').slideToggle();
					if(status){
						jQuery('#rdev-tracking-send').slideToggle();
					}else{
						jQuery('#rdev-tracking-error').slideToggle();
					}
					
					console.log(e);
				},
				error: function(e)
				{
					jQuery('#rdev-tracking-sending').slideToggle();
					jQuery('#rdev-tracking-error').slideToggle();
				}
			});
		});
	}