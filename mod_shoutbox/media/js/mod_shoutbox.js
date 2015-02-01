/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2015 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

var getPosts 	= null;
var submitPost 	= null;

function addSmiley(smiley, id) {

	// If we are not passed an id, use the default 'jj_message'.
	if (!id)
	{
		var id = 'jj_message';
	}

	// Get the position of the user in the text area
	var position = getCurserPosition(id);

	// Get the text area object
	var el = document.getElementById(id);

	// Split the text either side of the cursor
	var strBegin = el.value.substring(0, position);
	var strEnd   = el.value.substring(position);

	// Piece the text back together with the cursor in the midle
	el.value = strBegin + " " + smiley + " " + strEnd;
}

function getCurserPosition(id)
{
	var el = document.getElementById(id);
	var pos = 0;
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

	return pos;
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
		charsLeft.innerHTML = (maxlimit-textareaid.value.length)+' ' + shoutRemainingText;
	}
	
	if (maxlimit-textareaid.value.length > alertLength)
	{
		charsLeft.style.color = "Black";
	}	
	if (maxlimit-textareaid.value.length <= alertLength && maxlimit-textareaid.value.length > warnLength)
	{
		charsLeft.style.color = "Orange";
	}	
	if (maxlimit-textareaid.value.length <= warnLength)
	{
		charsLeft.style.color = "Red";
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


	// SMILEY SLIDETOGGLE
	$('#jj_btn').on('click', function(e) {	
		e.preventDefault();
		$(this).toggleClass('rotated');
		$('#jj_smiley_box').stop(true, false).slideToggle();
	});
	
	
	// SUBMIT POST
	submitPost = function(name, title, securityType, security, root)
	{
		// Assemble some commonly used vars
		var textarea = $('#jj_message'),
		message = textarea.val();

		// If no message body show an error message and stop
		if(message == "")
		{
			$('.jj-shout-error').append('<p class="inner-jj-error">Please enter a message!</p>').slideDown().show().delay(6000).queue(function(next){
				$(this).slideUp().hide();
				$('.inner-jj-error').remove();
				next();
			});
			var $elt = $('#shoutbox-submit').attr('disabled', true);
			setTimeout(function (){
				$elt.attr('disabled', false);
			}, 6000);
			textarea.addClass('jj-redBorder').delay(6000).queue(function(next){
				$(this).removeClass('jj-redBorder');
				next();
			});
			return false;
		}

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
			request['recaptcha_challenge_field'] = $('input#recaptcha_challenge_field').val();
			request['recaptcha_response_field']  = $('input#recaptcha_response_field').val();
		}

		if (securityType == 2)
		{
			request['jjshout[sum1]'] = $('input[name="jjshout[sum1]"]').val();
			request['jjshout[sum2]'] = $('input[name="jjshout[sum2]"]').val();
			request['jjshout[human]'] = $('input[name="jjshout[human]"]').val();
		}

		// AJAX request
		$.ajax({
			type: 'POST',
			url: 'index.php?option=com_ajax&module=shoutbox&method=submit&format=json',
			data: request,
			success:function(response){
				if (response.success)
				{
					// Empty the message value
					textarea.val('');

					// Empty the name value if there is one
					if ($('#shoutbox-name').val())
					{
						$('#shoutbox-name').val('');
					}

					// Refresh the output
					getPosts(title, root)
				}
			},
			error:function(ts){
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
			$('input[name="jjshout[sum1]"]').val(val1);
			$('input[name="jjshout[sum2]"]').val(val2);
			$('label[for="math_output"]').text(val1 + ' + ' + val2);
			$('input[name="jjshout[human]"]').val('');
		}

		return false;
	}
	
	
	// GET POSTS
	getPosts = function(title, root, sound)
	{
		// Assemble variables to submit
		var request = {
			'jjshout[title]' : title,
		};

		// AJAX request
		$.ajax({
			type: 'POST',
			url: 'index.php?option=com_ajax&module=shoutbox&method=getPosts&format=json',
			data: request,
			success:function(response){
				if (response.success)
				{
					$('#jjshoutboxoutput').empty().prepend($('<div class="jj-shout-error"></div>'));

					// Grab the html output and append it to the shoutbox message
					$('.jj-shout-error').after(response.data.html);
					
					// Play notification sound if enabled
					if (sound == 1) 
					{
						document.getElementById('jjshoutbox-audio').play();
					}
				}
			},
			error:function(ts){
				console.log(ts);
			}
		});

		return false;
	}
});
