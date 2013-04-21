<?php
/**
* @version   $Id: default.php 2012-01-16 21:00:00
* @package   JJ Shoutbox
* @copyright Copyright (C) 2011 - 2013 JoomJunk. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');
	
$document->addStyleSheet(JUri::root() . 'modules/mod_shoutbox/assets/css/mod_shoutbox.css');
$style = '#jjshoutboxoutput {
		border-color: #'. $params->get('bordercolor', 'FF3C16') .';
		border-width: '. $params->get('borderwidth', '1') .'px;
	}
	#jjshoutboxoutput div h1 {
		background: #'. $params->get('headercolor', 'D0D0D0') .';
	}';
if(version_compare(JVERSION,'3.0.0','le')) {
	$style .= '#jj_btn, #jj_btn2{
		width: 25px !important;
	}';
}
$user = JFactory::getUser();
if($user->authorise('core.delete')) {
	$style .= '#jjshoutboxoutput input[type=submit]{
		color:#' . $params->get('deletecolor', 'FF0000') . ';
	}'; 
}
$document->addStyleDeclaration( $style );
?>

<div id="jjshoutbox">
	<div id="jjshoutboxoutput">
		<div id="newshout"></div>
		<?php
			$shouts	= array();
			//Retrieves the shouts from the database
			$shouts= modShoutboxHelper::getShouts($number, $dataerror);
			$i=0;
			//Counts the number of shouts retrieved from the database
			$actualnumber = count($shouts);
			if($actualnumber==0) {
			//Display shout empty message if there are no posts
			?>
				<div><p><?php echo JText::_('SHOUT_EMPTY') ?></p></div>
			<?php }
		else {
			if($actualnumber<$number) {
				$number=$actualnumber;
			}
			//Loops through the shouts
			while ($i < $number) { ?>
				<div>
					<?php
						//Displays Name or Name with link to profile
						$profile_link = modShoutboxHelper::linkUser($profile, $shouts[$i]->name, $shouts[$i]->user_id);
					?>
					<h1 <?php echo modShoutboxHelper::shouttitle($user, $shouts[$i]->ip); ?>>
						<?php
							if ($smile==0){
								echo modShoutboxHelper::smileyfilter($profile_link);
							} else {
								echo $profile_link;
							}
						?> - <?php
							echo JFactory::getDate($shouts[$i]->when)->format($show_date . 'H:i');
							if($user->authorise('core.delete')) {
						?> 
						<form method="post" name="delete">
							<input name="delete" type="submit" value="x" />
							<input name="idvalue" type="hidden" value="<?php echo $shouts[$i]->id ?>" />
						</form> 
						<?php } ?>
					</h1>
					<p>
						<?php if ($smile==0){
							echo modShoutboxHelper::smileyfilter($shouts[$i]->msg);
						} else {
							echo $shouts[$i]->msg;
						} ?>
					</p>
				</div>
				<?php $i++; ?>
				<br />
				<?php
			}
		}
		?>
	</div>
	<div id="jjshoutboxform">
		<?php
		if(($actualnumber>0) && ($shouts[0]->msg==$dataerror) && ($shouts[0]->ip=='System')) {
			//Shows the error message instead of the form if there is a database error.
			echo JText::_('SHOUT_DATABASEERROR');
		} else if (($user->guest && $guestpost==0)||!$user->guest) { ?>
			<form method="post" name="shout">
				<?php
				//Displays the Name of the user if logged in unless stated in the parameters to be a input box
				if($displayname==0 && !$user->guest) {
				  echo JText::_( 'SHOUT_NAME' );
				  echo ": ";
				  echo $user->name;
				} else if($displayname==1 && !$user->guest) {
				  echo JText::_( 'SHOUT_NAME' );
				  echo ": ";
				  echo $user->username;
				} else if(($guestpost==0 && $user->guest)||($displayname==2 && !$user->guest)) { ?>
					<input name="name" type="text" value="Name" maxlength="25" id="shoutbox-name" onfocus="this.value = (this.value=='Name')? '' : this.value;" />
				<?php }
				
				if (($user->guest && $guestpost==0)||!$user->guest) {
					echo '<br />';
					//Adds in session token to prevent reposts and a security token to prevent CRSF attacks
					$_SESSION['token'] = uniqid("token",true);
					echo JHTML::_( 'form.token' );
					?>
						<input name="token" type="hidden" value="<?php echo $_SESSION['token'];?>" />

						<span id="charsleft"></span>
						<noscript>
							<span id="noscript_charsleft">
								<?php echo JText::_('SHOUT_NOSCRIPT_THERE_IS_A') . $params->get('messagelength', '200') . JText::_('SHOUT_NOSCRIPT_CHARS_LIMIT'); ?>
							</span>
						</noscript>
						<?php if($enterclick == 0) { ?>
						<textarea id="message" cols="20" rows="5" name="message" onKeyDown="textCounter('message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);" onKeyUp="textCounter('message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);"></textarea>
						<?php } else { ?>
						<input id="message" type="text" name="message" onKeyDown="textCounter('message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);" onKeyUp="textCounter('message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);" />
						<?php } 
						if($smile == 1 || $smile == 2){ 
								if($smile == 2){
									if(version_compare(JVERSION,'3.0.0','ge')) { 
										echo '<div id="jj_smiley_button">
												<input id="jj_btn" type="button" class="btn btn-mini" value="&#9650;" />
												<input id="jj_btn2" type="button" class="btn btn-mini" value="&#9660;" />
										      </div>';
									}
									else{
										echo '<div id="jj_smiley_button">
												<input id="jj_btn" type="button" class="btn" value="&#9650;" />
												<input id="jj_btn2" type="button" class="btn" value="&#9660;" />
										      </div>';
									}
								}
								echo '<div id="jj_smiley_box">';
									$path = JPATH_SITE ."/modules/mod_shoutbox/assets/images";			
									$smilies = JFolder::files($path);
									echo modShoutboxHelper::smileyshow($smilies);					
								echo '</div>';
						} ?>						
						<script type="text/javascript">
							function textCounter(textarea, countdown, maxlimit) {
								textareaid = document.getElementById(textarea);
								if (textareaid.value.length > maxlimit)
								  textareaid.value = textareaid.value.substring(0, maxlimit);
								else
								  document.getElementById('charsleft').innerHTML = (maxlimit-textareaid.value.length)+' <?php echo JText::_('SHOUT_REMAINING') ?>';
								  
								if (maxlimit-textareaid.value.length > <?php echo $params->get('alertlength', '50'); ?>)
								  document.getElementById('charsleft').style.color = "Black";	
								if (maxlimit-textareaid.value.length <= <?php echo $params->get('alertlength', '50'); ?> && maxlimit-textareaid.value.length > <?php echo $params->get('warnlength', '10'); ?>)
								  document.getElementById('charsleft').style.color = "Orange";
								if (maxlimit-textareaid.value.length <= <?php echo $params->get('warnlength', '10'); ?>)
								  document.getElementById('charsleft').style.color = "Red";
								  
							}
							textCounter('message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);
							<?php if($smile == 1 || $smile == 2 ){ ?>
								(function($){
									$('#jj_smiley_box a').click(function(){
										var smiley = $(this).attr('title');
										var caretPos = caretPos();
										var strBegin = $('#message').val().substring(0, caretPos);
										var strEnd   = $('#message').val().substring(caretPos);
										$('#message').val( strBegin + " " + smiley + " " + strEnd);
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
									<?php if($smile == 2){ ?>
									$("#jj_smiley_button").click(function () {
										$("#jj_smiley_box").slideToggle("slow");
									});
									
									$('#jj_btn').click(function(){
										$('#jj_btn2').show();
										$('#jj_btn').hide();
									});
									$('#jj_btn2').click(function(){
										$('#jj_btn').show();
										$('#jj_btn2').hide();
									});
									
									<?php } ?>
								})(jQuery);
							<?php } ?>
						</script>
		
						<?php
						//Shows recapture or math question depending on the parameters
						if($recaptcha==0) {
							if($params->get('recaptcha-public')=='' || $params->get('recaptcha-private')=='') {
								echo JText::_('SHOUT_RECAPTCHA_KEY_ERROR');
							} else {

								$publickey = $params->get('recaptcha-public');

								if(!isset($resp)) {
									$resp = null;
								}

								if(!isset($error)) {
									$error = null;
								}

								echo recaptcha_get_html($publickey, $error);
							}
						}
						if($securityquestion==0){
							$que_number1 = modShoutboxHelper::randomnumber(1);
							$que_number2 = modShoutboxHelper::randomnumber(1); ?>
							<label class="jj_label"><?php echo $que_number1; ?> + <?php echo $que_number2; ?> = ?</label>
							<input type="hidden" name="sum1" value="<?php echo $que_number1; ?>" />
							<input type="hidden" name="sum2" value="<?php echo $que_number2; ?>" />
							<input class="jj_input" type="text" name="human" id="mathsanswer" />
						<?php
						}
						if($params->get('recaptchaon')==0 && $securityquestion==0){
							//Shows warning if both security questions are enabled and logs to error file.
							JLog::add(JText::_('SHOUT_BOTH_SECURITY_ENABLED'), JLog::CRITICAL, 'mod_shoutbox');
							JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_BOTH_SECURITY_ENABLED'), 'error');
						}
						if($enterclick == 0) { ?>
						<input name="shout" id="shoutbox-submit" class="btn" type="submit" value="<?php echo $submittext ?>" <?php if (($recaptcha==0 && !$params->get('recaptcha-public')) || ($params->get('recaptchaon')==0 && !$params->get('recaptcha-private')) || ($params->get('recaptchaon')==0 && $securityquestion==0)) { echo 'disabled="disabled"'; }?> />
						<?php } } ?>
			</form> 
			<?php
			//Shows mass delete button if enabled
			if($user->authorise('core.delete')) { 
				if ($mass_delete == 0){ ?> 
					<form method="post" name="deleteall">
						<input class="jj_admin_label" type="number" name="valueall" min="1" max="<?php echo $number; ?>" step="1" value="0" />
						<input type="hidden" name="max" value="<?php echo $number; ?>" />
						<?php
						if(version_compare(JVERSION,'3.0.0','ge'))
						{
							?><input class="btn btn-danger btn-small" name="deleteall" type="submit" style="width:35%;margin-bottom:10px;color:white;" value="<?php echo JText::_('SHOUT_MASS_DELETE') ?>" /><?php
						}
						else
						{
							?><input class="jj_admin_button" name="deleteall" type="submit" value="<?php echo JText::_('SHOUT_MASS_DELETE') ?>" /><?php
						}
						?>
					</form> 
				<?php }
			}
		} else if($guestpost==1 && $guestpost==1) {
			//Shows no members allowed to post text
			?>
			<p id="noguest"><?php echo $nonmembers; ?></p>
		<?php } ?>
	</div>
</div>
<script>
(function($){
	<?php if($enterclick == 1) { ?>
	$('input#message').keypress(function (e) {
		if (e.which == 13) {
	<?php } else { ?>	
	$( "#shoutbox-submit" ).click( function() {
	<?php } ?>
		$.ajax({
			type: "POST",
			url: "<?php echo JUri::current() . '?task=submitShout'; ?>",
			data: 'name=' + encodeURIComponent($('#shoutbox-name').val()) + "&message=" + encodeURIComponent($('#message').val()) + "&<?php echo JSession::getFormToken(); ?>=1&token=<?php echo $_SESSION['token']; ?>&shout=" + encodeURIComponent('Shout!')
					<?php if($recaptcha==0) { ?> + "&recaptcha_response_field=" + encodeURIComponent($('#recaptcha_response_field').val()) + "&recaptcha_challenge_field=" + encodeURIComponent($('#recaptcha_challenge_field').val())
					<?php } elseif($securityquestion==0) { ?> + "<?php echo '&sum1='.$que_number1.'&sum2='.$que_number2.'&human=';?>" + encodeURIComponent($('#mathsanswer').val()) <?php } ?>,
			success:function(){
				<?php if($displayname==1 && !$user->guest){ ?>
					var name = "<?php echo $user->username;?>";
				<?php } elseif($displayname==0 && !$user->guest) { ?>
					var name = "<?php echo $user->name;?>";
				<?php } else { ?>
					var name = $('#shoutbox-name').val();
				<?php } ?>
				$('<div><h1>' + name + ' - 	<?php echo JFactory::getDate('now', JFactory::getConfig()->get('offset'))->format($show_date . 'H:i');?></h1><p>' + $('#message').val() + '</p><br />').hide().insertAfter('#newshout').slideDown();
				<?php if($displayname==2 || $user->guest)
				{ ?>
					$('#shoutbox-name').val('');
				<?php }
				if($securityquestion==0)
				{?>
					$('#mathsanswer').val('');
				<?php }
				if($recaptcha==0)
				{ ?>
					Recaptcha.reload();
				<?php } ?>
				$('#message').val('');
			},
			error:function(ts){
				console.log(ts.responseText);
			},
		});
		return false;
	<?php if($enterclick == 1) { ?>
	}
	}
	<?php } else { ?>	
	}
	<?php } ?>
	);
})(jQuery);
</script>
