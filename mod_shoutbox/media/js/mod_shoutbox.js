/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2015 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

var JJShoutbox = JJShoutbox || {};


/**
 * Ask access for HTML5 Notifications
 */
JJShoutbox.performNotificationCheck = function()
{
	// Let's check if the browser supports notifications
	if (!('Notification' in window)) 
	{
		// Browser does not support Notifications. Abort
		return;
	}

	Notification.requestPermission(function(permission) {
	});
}


/**
 * Create the HTML5 Notification
 */
JJShoutbox.createNotification = function(title, options)
{
	options = {
		icon: 'media/mod_shoutbox/images/notification.png'
	};
	
	// Let's check if the browser supports notifications
	if (!('Notification' in window)) 
	{
		// Browser does not support Notifications. Abort
		return;
	}

	if (Notification.permission === 'granted')
	{
		var notification = new Notification(title, options);
	}
	else if (Notification.permission !== 'denied') 
	{
		Notification.requestPermission(function(permission) {
			// If the user accepts, let's create a notification
			if (permission === 'granted') 
			{
				var notification = new Notification(title, options);
			}
		});
	}
}


/**
 * Adds a smiley to the textarea
 */
JJShoutbox.addSmiley = function(smiley, id) 
{
	// Get the text area object
	var el = document.getElementById(id);
	
	// Define ID is not already defined
	if (!el)
	{
		var el = 'jj_message';
	}
	
	// IE Support
	if (document.selection)
	{
		el.focus();
		var Sel = document.selection.createRange();
		var SelLength = document.selection.createRange().text.length;
		Sel.moveStart ('character', -el.value.length);
		pos = Sel.text.length - SelLength;
	}
	// Firefox support
	else if (el.selectionStart || el.selectionStart == '0')
	{
		pos = el.selectionStart;
	}
	
	var strBegin = el.value.substring(0, pos);
	var strEnd   = el.value.substring(pos);

	// Piece the text back together with the cursor in the midle
	el.value = strBegin + " " + smiley + " " + strEnd;
}


/**
 * Inserts the BBCode selected to the textarea
 */
JJShoutbox.insertBBCode = function(start, end, el) 
{
	// IE Support
	if (document.selection) 
	{
		el.focus();
		sel = document.selection.createRange();
		sel.text = start + sel.text + end;
	} 
	// Firefox support
	else if (el.selectionStart || el.selectionStart == '0') 
	{
		el.focus();
		var startPos = el.selectionStart;
		var endPos = el.selectionEnd;
		el.value = el.value.substring(0, startPos) + start + el.value.substring(startPos, endPos) + end + el.value.substring(endPos, el.value.length);
	} 
	else 
	{			
		el.value += start + end;
	}	
}


/**
 * Changes the text counter colour based on the max, alert and warning limits
 */
JJShoutbox.textCounter = function(textarea, countdown, maxlimit, alertLength, warnLength, shoutRemainingText)
{
	var textareaid = document.getElementById(textarea);
	var charsLeft = document.getElementById('charsLeft');
	
	if (textareaid.value.length > maxlimit)
	{
		textareaid.value = textareaid.value.substring(0, maxlimit);
	}
	else
	{
		charsLeft.innerHTML = (maxlimit-textareaid.value.length) + ' ' + shoutRemainingText;
	}
	
	if (maxlimit-textareaid.value.length > alertLength)
	{
		charsLeft.style.color = 'Black';
	}	
	if (maxlimit-textareaid.value.length <= alertLength && maxlimit-textareaid.value.length > warnLength)
	{
		charsLeft.style.color = 'Orange';
	}	
	if (maxlimit-textareaid.value.length <= warnLength)
	{
		charsLeft.style.color = 'Red';
	}
}


/**
 * Returns a random integer number between min (inclusive) and max (exclusive)
 */
JJShoutbox.getRandomArbitrary = function(min, max) 
{
	var random = 0;
    random = Math.random() * (max - min) + min;

	return parseInt(random);
}


/**
 * Draw the maths question using a canvas
 */
JJShoutbox.drawMathsQuestion = function(number1, number2) 
{	
	var c = document.getElementById('mathscanvas');
	var ctx = c.getContext('2d');

	ctx.clearRect(0, 0, c.width, c.height);
	ctx.font = '14px Arial';
	ctx.fillText(number1 + ' + ' + number2 + ' = ', 10, 20);
}


/**
 * Returns the last ID of the shoutbox output
 */
JJShoutbox.getLastID = function(instance)
{
	var lastId = instance.find('.shout-header:first-child').data('shout-id');

	return lastId;
}


/**
 * Returns the author of the last shout
 */
JJShoutbox.getLastAuthor = function(instance)
{
	var lastauthor = instance.find('.shout-header:first-child').data('shout-name');

	return lastauthor;
}


/**
 * Check if the name or message fields are empty
 *
 * TODO: Make this the general error handling function and improve it
 */
JJShoutbox.showError = function(msg, instance)
{
	if (JJ_Framework_type == 'uikit')
	{
		var alertClass = 'uk-alert uk-alert-danger';
	}
	else if (JJ_Framework_type == 'bootstrap3')
	{
		var alertClass = 'alert alert-danger';
	}
	else
	{
		var alertClass = 'alert alert-error';
	}
	
	var errorBox = instance.find('.jj-shout-error');
	var errorMsg = '<div class="' + alertClass + '">' + msg + '</div>';

	errorBox.html(errorMsg)
			.slideDown().delay(5000).slideUp(400, function() {
				errorBox.empty();
			});
	
	return false;
}
	
	

jQuery(document).ready(function($) {
	

	/**
	 * Compile the BBCode ready to insert
	 * Display insert box for images and links
	 */
	$('#jjshoutboxform .bbcode-button').on('click', function() {
		
		var bbcode 	= $(this).data('bbcode-type');
		var start 	= '[' + bbcode + ']';
		var end 	= '[/' + bbcode + ']';
		
		if (bbcode == 'url' || bbcode == 'img')
		{
			$('#jj-bbcode-type').data('bbcode-input-type', bbcode);
			
			if (bbcode == 'url')
			{
				$('#bbcode-form p').text(Joomla.JText._('SHOUT_BBCODE_INSERT_URL'));
			}
			else
			{
				$('#bbcode-form p').text(Joomla.JText._('SHOUT_BBCODE_INSERT_IMG'));
			}
			
			$('#bbcode-form').slideDown();
		}
		else
		{
			JJShoutbox.insertBBCode(start, end, $('#jj_message').get(0));
		}

	});
	
	
	/**
	 * Insert the BBCode and close the form
	 */
	$('#jjshoutboxform #bbcode-insert').on('click', function() {
			
		var bbcode = $('#jj-bbcode-type').data('bbcode-input-type');		
		var start  = '[' + bbcode + '=' + $('#bbcode-form #bbcode-url').val() + ']' + $('#bbcode-form #bbcode-text').val();
		var end    = '[/' + bbcode + ']';
		
		JJShoutbox.insertBBCode(start, end, $('#jj_message').get(0));	
		
		$('#bbcode-form').slideUp();
	});
	
	/**
	 * Close the form
	 */
	$('#jjshoutboxform #bbcode-cancel').on('click', function() {
			
		$('#bbcode-form').slideUp();
		
	});

	
	/**
	 * Populate modal with image
	 */
	$('#jjshoutboxoutput').on('click', '.jj-image-modal', function(e) {

		e.preventDefault();
		
		if (JJ_Framework_type == 'uikit')
		{
			var modal = UIkit.modal('#jj-image-modal');
		}
		else
		{
			var modal = $('#jj-image-modal');
		}
		
		// Get the image src and name
		var image 	= $(this).data('jj-image');
		var alt 	= $(this).data('jj-image-alt');

		// Populate the image src/alt and header text
		modal.find('img').attr('src', image);
		modal.find('img').attr('alt', alt);
		modal.find('.image-name').text(alt);
		
		// Show the modal
		if (JJ_Framework_type == 'uikit')
		{
			modal.show();
		}
		else
		{
			modal.modal('show');
		}

	});
	
	
	/**
	 * Open the history modal
	 */
	$('#jjshoutboxoutput').on('click', '#jj-history-trigger', function(e) {

		e.preventDefault();
		
		if (JJ_Framework_type == 'uikit')
		{
			UIkit.modal('#jj-history-modal').show();
		}
		else
		{
			$('#jj-history-modal').modal('show');
		}

	});
	
	
	
	
	
	
	
	
	
	
	
	

	
	/**
	 * Check the current timestamp and the timestamp stored in the database for that shout
	 */
	JJShoutbox.checkTimestamp = function(title, Itemid, instance, id)
	{
		// Assemble variables to submit
		var request = {
			'jjshout[title]' : title,
			'jjshout[id]' : id
		};

		// If there is an active menu item then we need to add it to the request.
		if (Itemid !== null)
		{
			request['Itemid'] = Itemid;
		}

		// AJAX request
		$.ajax({
			type: 'POST',
			url: 'index.php?option=com_ajax&module=shoutbox&method=checkTimestamp&format=raw',
			data: request,
			success: function(response){

				if (response == '')
				{
					console.log('Too late to edit');
					JJShoutbox.showError(Joomla.JText._('SHOUT_EDITOWN_TOO_LATE'), instance);
				}
				else
				{
					var json = $.parseJSON(response);
					
					$('#jj_message').val(json[0].msg);
				}
				
				
			},
			error: function(ts){
				console.log(ts);
			}
		});

		return false;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
		
	/**
	 * Submit a shout
	 */
	JJShoutbox.submitPost = function(name, title, securityType, security, Itemid, instance, ReCaptchaResponse, history)
	{
		// Assemble some commonly used vars
		var textarea = instance.find('#jj_message'),
		message = textarea.val();

		// Assemble variables to submit
		var request = {
			'jjshout[name]' : name,
			'jjshout[message]' : message.replace(/\n/g, "<br />"),
			'jjshout[shout]' : 'Shout!',
			'jjshout[title]' : title,
		};

		request[security] = 1;

		if (securityType == 1)
		{
			request['g-recaptcha-response'] = ReCaptchaResponse;
		}

		if (securityType == 2)
		{
			request['jjshout[sum1]'] = instance.find('input[name="jjshout[sum1]"]').val();
			request['jjshout[sum2]'] = instance.find('input[name="jjshout[sum2]"]').val();
			request['jjshout[human]'] = instance.find('input[name="jjshout[human]"]').val();
		}

		// If there is an active menu item then we need to add it to the request.
		if (Itemid !== null)
		{
			request['Itemid'] = Itemid;
		}

		// AJAX request
		$.ajax({
			type: 'POST',
			url: 'index.php?option=com_ajax&module=shoutbox&method=submit&format=json',
			data: request,
			success: function(response){
				if (response.success)
				{
					// Empty the message value
					textarea.val('');

					// Empty the name value if there is one
					if (instance.find('#shoutbox-name').val())
					{
						instance.find('#shoutbox-name').val('');
					}

					// Refresh the output
					JJShoutbox.getPosts(title, false, false, Itemid, instance, false, history)
				}
				else
				{
					JJShoutbox.showError(response.message, instance);
				}
			},
			error: function(ts){
				console.log(ts);
			}
		});

		// Valid or not refresh recaptcha
		if (securityType == 1)
		{
			var JJ_RecaptchaReset = typeof(grecaptcha) == 'undefined' ? '' : grecaptcha.reset();
			
			JJ_RecaptchaReset;
		}

		// Valid or not refresh maths values and empty answer
		if (securityType == 2)
		{
			var val1, val2;
			val1 = JJShoutbox.getRandomArbitrary(0,9);
			val2 = JJShoutbox.getRandomArbitrary(0,9);
			instance.find('input[name="jjshout[sum1]"]').val(val1);
			instance.find('input[name="jjshout[sum2]"]').val(val2);
			instance.find('label[for="math_output"]').text(val1 + ' + ' + val2);
			instance.find('input[name="jjshout[human]"]').val('');
			JJShoutbox.drawMathsQuestion(val1, val2);
		}

		return false;
	}
	
	
	/**
	 * Get the latest shouts
	 * Play a sound notification if new shouts are shown
	 */
	JJShoutbox.getPosts = function(title, sound, notifications, Itemid, instance, loggedInUser, history)
	{
		// Get the ID of the last shout
		var lastID 		= JJShoutbox.getLastID(instance);
		var lastName 	= JJShoutbox.getLastAuthor(instance);
		
		// Assemble variables to submit
		var request = {
			'jjshout[title]' : title,
		};

		// If there is an active menu item then we need to add it to the request.
		if (Itemid !== null)
		{
			request['Itemid'] = Itemid;
		}

		// AJAX request
		$.ajax({
			type: 'POST',
			url: 'index.php?option=com_ajax&module=shoutbox&method=getPosts&format=json',
			data: request,
			success: function(response){
				if (response.success)
				{
					instance.find('#jjshoutboxoutput').empty().prepend($('<div class="jj-shout-new"></div>'));
					
					var historyButton = '';
					
					if (history == 1)
					{
						historyButton = '<div class="center-block"><a href="#" id="jj-history-trigger" class="btn btn-primary btn-mini btn-xs uk-button uk-button-primary uk-button-mini">' + Joomla.JText._('SHOUT_HISTORY_BUTTON') + '</a></div>';
					}
					
					// Grab the html output and append it to the shoutbox message
					instance.find('.jj-shout-new').after(response.data.html + historyButton);
					
					// Get the ID of the last shout after the output has been updated
					var newLastID = JJShoutbox.getLastID(instance);
					
					// Post ID and name checks
					if (newLastID > lastID && (loggedInUser == lastName)) 
					{
						// Show HTML5 Notification if enabled
						if (notifications == 1) 
						{
							JJShoutbox.createNotification(Joomla.JText._('SHOUT_NEW_SHOUT_ALERT'));
						}
						// Play notification sound if enabled
						if (sound == 1) 
						{
							instance.find('.jjshoutbox-audio').get(0).play();
						}
					}
				}
				else
				{
					JJShoutbox.showError(response.message, instance);
				}
			},
			error: function(ts){
				console.log(ts);
			}
		});

		return false;
	}
	
	
	/**
	 * Get the the shouts history based on the offset and count
	 */
	JJShoutbox.getPostsHistory = function(title, Itemid, instance, offset)
	{
		// Assemble variables to submit
		var request = {
			'jjshout[title]' : title,
			'jjshout[offset]' : offset,
		};

		// If there is an active menu item then we need to add it to the request.
		if (Itemid !== null)
		{
			request['Itemid'] = Itemid;
		}

		// AJAX request
		$.ajax({
			type: 'POST',
			url: 'index.php?option=com_ajax&module=shoutbox&method=getPosts&format=json',
			data: request,
			success: function(response){
				if (response.success)
				{
					if (response.data.html == '')
					{
						instance.find('#jj-load-more').hide();
					}
					else
					{
						// Grab the html output and append it to the shoutbox message
						instance.find('#jj-load-more').parent().before(response.data.html);
					}
				}
				else
				{
					JJShoutbox.showError(response.message, instance);
				}
			},
			error: function(ts){
				console.log(ts);
			}
		});

		return false;
	}
	
});
