/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2015 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

var JJgetPosts;
var JJsubmitPost;
var showError;

function addSmiley(smiley, id) 
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

function insertBBCode(start, end, el) 
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

function textCounter(textarea, countdown, maxlimit, alertLength, warnLength, shoutRemainingText)
{
	textareaid = document.getElementById(textarea);
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
function getRandomArbitrary(min, max) 
{
	var random = 0;
    random = Math.random() * (max - min) + min;

	return parseInt(random);
}

jQuery(document).ready(function($) {

	// Append BBCode
	$('#jjshoutboxform .btn-toolbar button').on('click', function() {
		
		var bbcode 		= $(this).data('bbcode-type');
		var start 		= '[' + bbcode + ']';
		var end 		= '[/' + bbcode + ']';
		var element 	= $('#jj_message').get(0);

		var param = '';

		if ( bbcode == 'url' )
		{
			start = '[url=' + param + ']';
		}
		
		insertBBCode(start, end, element);
		
		return false;
	  
    });
	
	// SMILEY SLIDETOGGLE
	$('#jj_btn').on('click', function(e) {
		e.preventDefault();
		$(this).toggleClass('rotated');
		$('#jj_smiley_box').stop(true, false).slideToggle();
	});
		
	// SUBMIT POST
	JJsubmitPost = function(name, title, securityType, security, Itemid, instance)
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
			request['recaptcha_challenge_field'] = instance.find('input#recaptcha_challenge_field').val();
			request['recaptcha_response_field']  = instance.find('input#recaptcha_response_field').val();
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
					JJgetPosts(title, false, Itemid, instance)
				}
			},
			error: function(ts){
				console.log(ts);
			}
		});

		// Valid or not refresh recaptcha
		if (securityType == 1)
		{
			Recaptcha.reload();
		}

		// Valid or not refresh maths values and empty answer
		if (securityType == 2)
		{
			var val1, val2;
			val1 = getRandomArbitrary(0,9);
			val2 = getRandomArbitrary(0,9);
			instance.find('input[name="jjshout[sum1]"]').val(val1);
			instance.find('input[name="jjshout[sum2]"]').val(val2);
			instance.find('label[for="math_output"]').text(val1 + ' + ' + val2);
			instance.find('input[name="jjshout[human]"]').val('');
		}

		return false;
	}
	
	
	// GET POSTS
	JJgetPosts = function(title, sound, Itemid, instance)
	{
		// Get the ID of the last shout
		var lastID = getLastID(instance);
		
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

					// Grab the html output and append it to the shoutbox message
					instance.find('.jj-shout-new').after(response.data.html);
					
					// Get the ID of the last shout after the output has been updated
					var newLastID = getLastID(instance);
					
					// Play notification sound if enabled
					if (sound == 1 && newLastID > lastID) 
					{
						instance.find('.jjshoutbox-audio').get(0).play();
					}
				}
			},
			error: function(ts){
				console.log(ts);
			}
		});

		return false;
	}
	
	// Get the last ID of the shoutbox output
	function getLastID(instance)
	{
		var lastId = instance.find('.shout-header:first-child').data('shout-id');

		return lastId;
	}
	
	// Check if the name or message fields are empty
	showError = function(errorMsg, instance)
	{
		var errorBox = instance.find('.jj-shout-error');
		var errorMsgWithPTag = '<p>' + errorMsg + '</p>';

		errorBox.html(errorMsgWithPTag)
				.slideDown().delay(5000).slideUp(400, function() {
					$(this).empty();
				});
		
		return false;
	}
	
});
