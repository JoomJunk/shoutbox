/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2014 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

function addSmiley(smiley, id)
{
	// If we are not passed an id, use the default 'jj_message'.
	if (!id)
	{
		id = 'jj_message';
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

function getCurserPosition(id){
	var el = document.getElementById(id);
	var pos = 0;
	// IE Support
	if (document.selection){
		el.focus ();
		var Sel = document.selection.createRange();
		var SelLength = document.selection.createRange().text.length;
		Sel.moveStart ('character', -el.value.length);
		pos = Sel.text.length - SelLength;
	}
	// Firefox support
	else if (el.selectionStart || el.selectionStart == '0')
		pos = el.selectionStart;

	return pos;
}

function submitPost(name, title, recaptcha, maths, security, root)
{
    (function ($) {
		// Assemble some commonly used vars
		var textarea = $("textarea#jj_message"),
		message = textarea.val();

		// If no message body show an error message and stop
		if(message == ""){
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
			'jjshoutbox[name]' : name,
			'jjshoutbox[message]' : message.replace(/\n/g, "<br />"),
			'jjshoutbox[shout]' : 'Shout!',
			'jjshoutbox[title]' : title,
		};

		request[security] = 1;

		if (recaptcha)
		{
			request["jjshoutbox[recaptcha_challenge_field]"] = $("input#recaptcha_challenge_field").val();
			request["jjshoutbox[recaptcha_response_field]"] = $("input#recaptcha_response_field").val();
		}

		// AJAX request
		$.ajax({
			type: "POST",
			url: root + "?option=com_ajax&module=shoutbox&method=submit&format=json",
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
				}
			},
			error:function(ts){
				console.log(ts);
			}
		});

		// Valid or not refresh recaptcha
		if (recaptcha)
		{
			Recaptcha.reload();
		}

		return false;
    }(jQuery));
}

function getPost(title)
{
    (function ($) {
        var request = {
            'jjshoutbox[title]' : title
        };

        // AJAX request
        $.ajax({
            type: "GET",
            url: root + "?option=com_ajax&module=shoutbox&method=getShouts&format=json",
            data: request,
            success:function(response)
            {
                if (response.success)
                {
                    // Wipe the existing posts and then add in the latest ones in the response.data property
                }
            },
            error:function(ts){
                console.log(ts);
            }
        });

        return false;
    }(jQuery));
}
