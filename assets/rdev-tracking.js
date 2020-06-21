/* ======================================
 *            RDEV Tracking
 * ======================================
 * Copyright © 2018-2020 RapidDev
 * Author: Leszek Pomianowski
 * https://rdev.cc/
 */
 	let jquery_url = 'https://code.jquery.com/jquery-3.4.1.min.js';

 	function console_log(message, color="#fff"){console.log("%cRDEV Tracking: "+"%c"+message, "color:#dc3545;font-weight: bold;", "color: "+color);}
 	function async_jquery(e,n){let t=document,a="script",c=t.createElement(a),r=t.getElementsByTagName(a)[0];c.src=e,n&&c.addEventListener("load",function(e){n(null,e)},!1),r.parentNode.insertBefore(c,r)}
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
		//Referral link
		jQuery('.rdev-tracking-button').on('click', function(e){
			if(jQuery(this).attr('disabled') == 'disabled')
				e.preventDefault();
		});

		//Update tracking
		jQuery('.rdev-tracking-refresh').on('click', function(e){
			e.preventDefault();
			if(jQuery(this).attr('disabled') == 'disabled')
				return;
			console_log('Sending a request to update tracking status...');

			jQuery(this).addClass('rdev-rotate-button');

			let clicked_button = this;
			let button_data = jQuery(this).data();
			let clicked_post_id = button_data.post_id;
			//console.log(button_data);

			jQuery.ajax({
				url: rdev_tracking.url,
				type: 'post',
				data: {
					action: 'rdev_tracking_update',
					nonce: button_data.nonce,
					tracking_number: button_data.id,
					carrier: button_data.service,
					post_id: button_data.post_id
				},
				success: function(e)
				{
					jQuery(clicked_button).removeClass('rdev-rotate-button');

					let status = false;
					if(/^[\],:{}\s]*$/.test(e.replace(/\\["\\\/bfnrtu]/g,"@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,"]").replace(/(?:^|:|,)(?:\s*\[)+/g,"")))
					{
						let o = JSON.parse(e);
						if(o.hasOwnProperty('status'))
						{
							if(o.response == 'success'){
								status = true;
							}
						}
						if(status)
						{
							if(o.last_event != '')
							{
								jQuery('#rdev-tracking-status-message-' + clicked_post_id).html(o.last_event);
							}
						}
					}
				},
				error: function(e)
				{
					jQuery(clicked_button).removeClass('rdev-rotate-button');
					//error
				}
			});
		});

		//Save tracking
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
					let status = false;
					if(/^[\],:{}\s]*$/.test(e.replace(/\\["\\\/bfnrtu]/g,"@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,"]").replace(/(?:^|:|,)(?:\s*\[)+/g,"")))
					{
						let o = JSON.parse(e);
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