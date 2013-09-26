<?php
/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2013 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die('Restricted access');

$document->addStyleSheet(JUri::root() . 'media/mod_shoutbox/css/mod_shoutbox.css');
$style = '#jjshoutboxoutput {
		border-color: ' . $params->get('bordercolor', '#FF3C16') . ';
		border-width: ' . $params->get('borderwidth', '1') . 'px;
	}
	#jjshoutboxoutput div h1 {
		background: ' . $params->get('headercolor', '#D0D0D0') . ';
	}';

if (version_compare(JVERSION, '3.0.0', 'le'))
{
	$style .= '#jj_btn, #jj_btn2{
		width: 25px !important;
	}';
}

$user = JFactory::getUser();

if ($user->authorise('core.delete'))
{
	$style .= '#jjshoutboxoutput input[type=submit]{
		color:' . $params->get('deletecolor', '#FF0000') . ';
	}';
}

$document->addStyleDeclaration($style);

JText::script('SHOUT_ANSWER_INCORRECT');
?>

<div id="jjshoutbox">
<div id="jjshoutboxoutput">
	<div id="newshout"></div>
	<?php
	$shouts	= array();

	// Retrieves the shouts from the database
	$shouts = ModShoutboxHelper::getShouts($number, $dataerror);
	$i = 0;

	// Counts the number of shouts retrieved from the database
	$actualnumber = count($shouts);

	if ($actualnumber == 0)
	{
		// Display shout empty message if there are no posts
		?>
		<div><p><?php echo JText::_('SHOUT_EMPTY') ?></p></div>
	<?php
	}
	else
	{
		if ($actualnumber < $number)
		{
			$number = $actualnumber;
		}

		// Loops through the shouts
		while ($i < $number)
		{
			?>
			<div>
				<?php
				// Displays Name or Name with link to profile
				$profile_link = ModShoutboxHelper::linkUser($profile, $shouts[$i]->name, $shouts[$i]->user_id);
				?>
				<h1 <?php echo ModShoutboxHelper::shouttitle($user, $shouts[$i]->ip); ?>>
					<?php
					if ($bbcode == 0)
					{
						echo ModShoutboxHelper::bbcodeFilter($profile_link);
					}
					else
					{
						echo $profile_link;
					}
					?> - <?php
					echo JFactory::getDate($shouts[$i]->when)->format($show_date . 'H:i');

					if ($user->authorise('core.delete'))
					{
						?>
						<form method="post" name="delete">
							<input name="delete" type="submit" value="x" />
							<input name="idvalue" type="hidden" value="<?php echo $shouts[$i]->id ?>" />
						</form>
					<?php
					}
					?>
				</h1>
				<p>
					<?php
					if ($bbcode == 0)
					{
						echo ModShoutboxHelper::bbcodeFilter($shouts[$i]->msg);
					}
					else
					{
						echo nl2br($shouts[$i]->msg);
					}
					?>
				</p>
			</div>
			<?php
			$i++;
		}
	}
	?>
</div>
<div id="jjshoutboxform">
<?php
if (($actualnumber > 0) && ($shouts[0]->msg == $dataerror) && ($shouts[0]->ip == 'System'))
{
	// Shows the error message instead of the form if there is a database error.
	echo JText::_('SHOUT_DATABASEERROR');
}
elseif (($user->guest && $guestpost == 0)||!$user->guest)
{
	?>
	<form method="post" name="shout">
		<?php
		// Displays the Name of the user if logged in unless stated in the parameters to be a input box
		if ($displayName == 0 && !$user->guest)
		{
			echo JText::_('SHOUT_NAME');
			echo ": ";
			echo $user->name;
		}
		elseif ($displayName == 1 && !$user->guest)
		{
			echo JText::_('SHOUT_NAME');
			echo ": ";
			echo $user->username;
		}
		elseif (($guestpost == 0 && $user->guest)||($displayName == 2 && !$user->guest))
		{
			?>
			<input name="name" type="text" value="Name" maxlength="25" id="shoutbox-name" onfocus="this.value = (this.value=='Name')? '' : this.value;" />
		<?php
		}

		if (($user->guest && $guestpost == 0) || !$user->guest)
		{
			echo '<br />';

			// Adds in session token to prevent re-posts and a security token to prevent CRSF attacks
			$_SESSION['token'] = uniqid("token", true);
			echo JHtml::_('form.token');
			?>
			<input name="token" type="hidden" value="<?php echo $_SESSION['token'];?>" />

			<span id="charsLeft"></span>
			<noscript>
							<span id="noscript_charsleft">
								<?php echo JText::_('SHOUT_NOSCRIPT_THERE_IS_A') . $params->get('messagelength', '200') . JText::_('SHOUT_NOSCRIPT_CHARS_LIMIT'); ?>
							</span>
			</noscript>
			<textarea id="message" cols="20" rows="5" name="message" onKeyDown="textCounter('message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);" onKeyUp="textCounter('message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);"></textarea>
			<div class="jj-shout-error"></div>
			
			<?php if ( $bbcode == 0 )
			{
			?>
			<div class="btn-toolbar">
				<div class="btn-group">
					<button class="btn btn-small jj-bold">B</button>
					<button class="btn btn-small jj-italic">I</button>
					<button class="btn btn-small jj-strike">S</button>
					<button class="btn btn-small jj_bbcode_link">Link</button>
					<a class="btn btn-small dropdown-toggle" data-toggle="dropdown" href="#">
						<img src="<?php echo JUri::root(); ?>media/mod_shoutbox/images/icon_razz.gif" alt=":D"/>
						<span class="caret"></span>
					</a>
					<ul class="dropdown-menu">
						<li>
						<?php
							echo '<div id="jj_smiley_box">';
							$path = JPATH_ROOT . "/media/mod_shoutbox/images";
							$smilies = JFolder::files($path);
							echo ModShoutboxHelper::smileyShow($smilies);
							echo '</div>';
						?>					
						</li>
					</ul>
				</div>
			</div>
			<?php 
			}
			?>
			
			<script type="text/javascript">
				var bbcode = <?php echo $bbcode; ?>;
				function textCounter(textarea, countdown, maxlimit) {
					textareaid = document.getElementById(textarea);
					if (textareaid.value.length > maxlimit)
						textareaid.value = textareaid.value.substring(0, maxlimit);
					else
						document.getElementById('charsLeft').innerHTML = (maxlimit-textareaid.value.length)+' <?php echo JText::_('SHOUT_REMAINING') ?>';

					if (maxlimit-textareaid.value.length > <?php echo $params->get('alertlength', '50'); ?>)
						document.getElementById('charsLeft').style.color = "Black";
					if (maxlimit-textareaid.value.length <= <?php echo $params->get('alertlength', '50'); ?> && maxlimit-textareaid.value.length > <?php echo $params->get('warnlength', '10'); ?>)
						document.getElementById('charsLeft').style.color = "Orange";
					if (maxlimit-textareaid.value.length <= <?php echo $params->get('warnlength', '10'); ?>)
						document.getElementById('charsLeft').style.color = "Red";

				}
				textCounter('message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);

				if (bbcode == 0) {
					(function($){
						var message = $('#message').val();
						$('#jj_smiley_box img').click(function(){
							var smiley = $(this).attr('alt');
							var caretPos = caretPos();
							var strBegin = message.substring(0, caretPos);
							var strEnd   = message.substring(caretPos);
							$('#message').val(strBegin + " " + smiley + " " + strEnd);
							function caretPos(){
								var el = document.getElementById("message");
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
						});
					})(jQuery);
				}
			</script>

			<?php
			// Shows recapture or math question depending on the parameters
			if ($recaptcha == 0)
			{
				if ($params->get('recaptcha-public') == '' || $params->get('recaptcha-private') == '')
				{
					echo JText::_('SHOUT_RECAPTCHA_KEY_ERROR');
				}
				else
				{
					$publickey = $params->get('recaptcha-public');

					if (!isset($resp))
					{
						$resp = null;
					}

					if (!isset($error))
					{
						$error = null;
					}

					echo recaptcha_get_html($publickey, $error);
				}
			}

			if ($securityQuestion == 0)
			{
				$que_number1 = ModShoutboxHelper::randomnumber(1);
				$que_number2 = ModShoutboxHelper::randomnumber(1); ?>
				<label class="jj_label"><?php echo $que_number1; ?> + <?php echo $que_number2; ?> = ?</label>
				<input type="hidden" name="sum1" value="<?php echo $que_number1; ?>" />
				<input type="hidden" name="sum2" value="<?php echo $que_number2; ?>" />
				<input class="jj_input" type="text" name="human" id="mathsanswer" />
			<?php
			}

			if ($params->get('recaptchaon') == 0 && $securityQuestion == 0)
			{
				// Shows warning if both security questions are enabled and logs to error file.
				JLog::add(JText::_('SHOUT_BOTH_SECURITY_ENABLED'), JLog::CRITICAL, 'mod_shoutbox');
				JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_BOTH_SECURITY_ENABLED'), 'error');
			}
			if($enterclick == 0) { ?>
				<input name="shout" id="shoutbox-submit" class="btn" type="submit" value="<?php echo $submittext ?>" <?php if (($params->get('recaptchaon')==0 && !$params->get('recaptcha-public')) || ($params->get('recaptchaon')==0 && !$params->get('recaptcha-private')) || ($params->get('recaptchaon')==0 && $securityQuestion==0)) { echo 'disabled="disabled"'; }?> />
			<?php
			}
		}
		?>
	</form>
	<?php
	// Shows mass delete button if enabled
	if ($user->authorise('core.delete'))
	{
		if ($mass_delete == 0)
		{ ?>
			<form method="post" name="deleteall">
				<input type="hidden" name="max" value="<?php echo $number; ?>" />
				<?php
				if (version_compare(JVERSION, '3.0.0', 'ge'))
				{
					?><div class="input-append">
						<input class="span2" type="number" name="valueall" min="1" max="<?php echo $number; ?>" step="1" value="0" style="width:50px;">
						<input class="btn btn-danger" type="submit" name="deleteall" value="<?php echo JText::_('SHOUT_MASS_DELETE') ?>"style="color: #FFF;" />
					</div>	
					<?php
				}
				else
				{
					?>
					<input class="jj_admin_label" type="number" name="valueall" min="1" max="<?php echo $number; ?>" step="1" value="0" />
					<input class="jj_admin_button" name="deleteall" type="submit" value="<?php echo JText::_('SHOUT_MASS_DELETE') ?>" /><?php
				}
				?>
			</form>
		<?php
		}
	}
}
elseif ($guestpost == 1 && $guestpost == 1)
{
	// Shows no members allowed to post text
	?>
	<p id="noguest"><?php echo $nonmembers; ?></p>
<?php
}
?>
</div>
</div>
<script>
	(function($){
		var textarea = $("textarea#message");
		<?php if($enterclick == 1) { ?>
		textarea.keypress(function(e){
			if (e.keyCode == 13 && !e.shiftKey){
				<?php } else { ?>
					$( "#shoutbox-submit" ).click( function() {
						<?php } ?>
						if(textarea.val() == ""){
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
						else {
							<?php if($displayName==1 && !$user->guest){ ?>
							var name = "<?php echo $user->username;?>";
							<?php } elseif($displayName==0 && !$user->guest) { ?>
							var name = "<?php echo $user->name;?>";
							<?php } else { ?>
							if($('#shoutbox-name').val() == ""){
								var name = "<?php echo $genericname; ?>";
							}
							else{
								var name = $('#shoutbox-name').val();
							}
							<?php } ?>
							var request = {
								'name' : name,
								'message' : textarea.val(),
								'<?php echo JSession::getFormToken(); ?>'    : '1',
								'token'   : '<?php echo $_SESSION['token']; ?>',
								'shout' : 'Shout!',
								'title' : '<?php echo $title; ?>',
								'ajax' : 'true'
								<?php
								if ($recaptcha==0) {
								?>
								,'recaptcha_response_field' : $('#recaptcha_response_field').val(),
								'recaptcha_challenge_field' : $('#recaptcha_challenge_field').val()
								<?php
								}
								elseif ($securityQuestion == 0)
								{
								?>
								,'sum1' : '<?php echo $que_number1; ?>',
								'sum2' : '<?php echo $que_number2; ?>',
								'human' : $('#mathsanswer').val()
								<?php
								}
								?>
							}
							<?php
							if($bbcode == 0)
							{
							?>
							,
							map = {
								':)':   '<img src="media/mod_shoutbox/images/icon_e_smile.gif" alt=":)" />',
								':(':   '<img src="media/mod_shoutbox/images/icon_e_sad.gif" alt=":(" />',
								':D':   '<img src="media/mod_shoutbox/images/icon_e_biggrin.gif" alt=":D" />',
								'xD':   '<img src="media/mod_shoutbox/images/icon_e_biggrin.gif" alt="xD" />',
								':P':   '<img src="media/mod_shoutbox/images/icon_razz.gif" alt=":P" />',
								';)':   '<img src="media/mod_shoutbox/images/icon_e_wink.gif" alt=";)" />',
								':S':   '<img src="media/mod_shoutbox/images/icon_e_confused.gif" alt=":S" />',
								':@':   '<img src="media/mod_shoutbox/images/icon_mad.gif" alt=":@" />',
								':O':   '<img src="media/mod_shoutbox/images/icon_e_surprised.gif" alt=":O" />',
								'lol':   '<img src="media/mod_shoutbox/images/icon_lol.gif" alt="lol" />'
							},
							message = textarea.val(),
							Object.keys(map).forEach(function (ico) {
								var icoE   = ico.replace(/([.?*+^$[\]\\(){}|-])/g, "\\$1");
								message    = message.replace( new RegExp(icoE, 'g'), map[ico] );
							});
							filtered_message = message.replace(/\[i\](.*)\[\/i\]/g, '<span class="jj-italic">$1</span>')
							.replace(/\[s\](.*)\[\/s\]/g, '<span class="jj-strike">$1</span>')
							.replace(/\[b\](.*)\[\/b\]/g, '<span class="jj-bold">$1</span>')
							.replace(/\n/g, "<br />")
							.replace(/\[url=([^\]]+)\]\s*(.*?)\s*\[\/url\]/gi, "<a href='$1'>$2</a>");
							<?php
							}
							else
							{
							?>							
							filtered_message = textarea.val().replace(/\n/g, "<br />");
							<?php
							}
							?>
							$.ajax({
								type: "POST",
								url: "<?php echo JUri::current() . '?option=com_ajax&module=shoutbox&method=submit&format=json'; ?>",
								data: request,
								success:function(response){
									if (response.data['value'])
									{
										var deleteResponse = '';
										<?php
										if ($user->authorise('core.delete'))
										{
										?>
										deleteResponse = '<form method="post" name="delete"><input name="delete" type="submit" value="x" /><input name="idvalue" type="hidden" value="' + response.data['value'] + '" /></form>';
										<?php
										}
										?>
										$('<div><h1>' + name + ' - 	<?php echo JFactory::getDate('now', JFactory::getConfig()->get('offset'))->format($show_date . 'H:i');?>' + deleteResponse + '</h1><p>' + filtered_message + '</p>').hide().insertAfter('#newshout').slideDown();
										<?php if($displayName == 2 || $user->guest)
										{ ?>
										$('#shoutbox-name').val('');
										<?php }
										if($securityQuestion == 0)
										{?>
										$('#mathsanswer').val('');
										<?php }
										if($recaptcha == 0)
										{ ?>
										Recaptcha.reload();
										<?php } ?>
										textarea.val('');
									}
									else
									{
										var error = '';
										if(response.data['error'])
										{
											error = response.data['error'];
										}
										$('.jj-shout-error').append('<p class="inner-jj-error">' + error + '</p>').slideDown().show().delay(6000).queue(function(next){
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
								},
								error:function(ts){
									console.log(ts.responseText);
								}
							});
							return false;
						}
						<?php if($enterclick == 1) { ?>
					}
				}
				<?php } else { ?>
			}
			<?php } ?>
		);
	})(jQuery);
</script>
