<?php
/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2015 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die('Restricted access');

JHtml::_('stylesheet', 'mod_shoutbox/mod_shoutbox.css', array(), true);
$style = '#jjshoutboxoutput {
			border-color: ' . $bordercolour . ';
			border-width: ' . $borderwidth . 'px;
		}
		#jjshoutboxoutput .shout-header {
			background: ' . $headercolor . ';
		}
		#jjshoutboxoutput input[type=submit],
		#jj-shout-history form input[type="submit"] {
			color:' . $deletecolor . ';
		}';

if ($avatar != 'none')
{
	$style .= '
	#jjshoutboxoutput .shout-header {
		height: auto;
	}
	#jjshoutboxoutput .avatar img {
		margin-right: 5px;
		height: 30px;
		width: 30px;
	}';
}

// Prevent the image overlapping on Bootstrap 2 modal
if ($framework == 'bootstrap')
{
	$style .= '
	#jjshoutboxform .modal-body > img {
		float: left;
		padding-bottom: 1%;
	}';
}


$doc->addStyleDeclaration($style);
$uniqueIdentifier = 'jjshoutbox' . $uniqueId;

// Load core.js for the javascript translating
JHtml::_('behavior.core');
JText::script('SHOUT_MESSAGE_EMPTY');
JText::script('SHOUT_NAME_EMPTY');
JText::script('SHOUT_NEW_SHOUT_ALERT');
JText::script('SHOUT_HISTORY_BUTTON');
?>

<div id="<?php echo $uniqueIdentifier; ?>" class="jjshoutbox">
	
	<div id="jjshoutboxoutput">
		<div class="jj-shout-new"></div>
		<?php 
			// Retrieves the shouts from the database
			$shouts = $helper->getShouts(0, $number, $dataerror);
			
			// Counts the number of shouts retrieved from the database
			$actualnumber = count($shouts);
			
			if ($actualnumber == 0) 
			{
				echo '<div><p>' . JText::_('SHOUT_EMPTY') . '</p></div>';
			} 
			else 
			{
				foreach ($shouts as $shout) 
				{
					echo $helper->renderPost($shout);
				}
			} 
		 ?>
		 
		 <div id="jj-history-container" class="center-block">
			<a href="#" id="jj-history-trigger" class="btn btn-primary btn-mini btn-xs uk-button uk-button-primary uk-button-mini"><?php echo JText::_('SHOUT_HISTORY_BUTTON'); ?></a>
		 </div>
	</div>
	<div class="jj-shout-error"></div>

	<?php if ($sound == 1) : ?>
		<audio class="jjshoutbox-audio" preload="auto">
			<source src="<?php echo JUri::root(); ?>/media/mod_shoutbox/sounds/notification.mp3" type="audio/mpeg">
			<source src="<?php echo JUri::root(); ?>/media/mod_shoutbox/sounds/notification.ogg" type="audio/ogg">
		</audio>
	<?php endif; ?>

	<div id="jjshoutboxform">
		<?php
		// Retrieve the list of user groups the user has access to
		$access = $user->getAuthorisedGroups();

		// Convert the parameter string into an integer
		$i = 0;

		foreach($permissions as $permission)
		{
			$permissions[$i] = intval($permission);
			$i++;
		}

		if (($actualnumber > 0) && ($shouts[0]->msg == $dataerror) && ($shouts[0]->ip == 'System'))
		{
			// Shows the error message instead of the form if there is a database error.
			echo JText::_('SHOUT_DATABASEERROR');
		}
		elseif (array_intersect($permissions, $access))
		{
		?>
			<form method="post" name="shout" class="<?php echo $form; ?>">
				
				<div class="<?php echo $form_row; ?>">
					<?php
						// Displays the Name of the user if logged in unless stated in the parameters to be a input box
						if ($displayName == 'real' && !$user->guest)
						{
							echo '<p>' . JText::_('SHOUT_NAME') . ": " . $user->name . '</p>';
						}
						elseif ($displayName == 'user' && !$user->guest)
						{
							echo '<p>' . JText::_('SHOUT_NAME') . ": " . $user->username . '</p>';
						}
						elseif ($user->guest || ($displayName == 'choose' && !$user->guest))
						{
							echo '<input name="jjshout[name]" type="text" maxlength="25" required="required" id="shoutbox-name" class="' . $input_txtarea . '" placeholder="' . JText::_('SHOUT_NAME') . '" />';
						}

						// Adds in session token to prevent re-posts and a security token to prevent CRSF attacks
						$_SESSION['token'] = uniqid("token", true);
						echo JHtml::_('form.token');
					?>
				</div>
				
				<input name="jjshout[token]" type="hidden" value="<?php echo $_SESSION['token'];?>" />
				
				<div class="<?php echo $form_row; ?>">
					<?php if ($enablelimit == 1) : ?>
						<span id="charsLeft"></span>
						<textarea 
							id="jj_message"
							class="<?php echo $input_txtarea; ?>"
							cols="20"
							rows="5"
							name="jjshout[message]"
							onKeyDown="JJShoutbox.textCounter('jj_message','messagecount',<?php echo $messageLength; ?>, <?php echo $alertLength; ?>, <?php echo $warnLength; ?>, '<?php echo $remainingLength; ?>');" 
							onKeyUp="JJShoutbox.textCounter('jj_message','messagecount',<?php echo $messageLength; ?>, <?php echo $alertLength; ?>, <?php echo $warnLength; ?>, '<?php echo $remainingLength; ?>');"
						></textarea>
					<?php else: ?>
						<textarea id="jj_message" class="<?php echo $input_txtarea; ?>" cols="20" rows="5" name="jjshout[message]"></textarea>
					<?php endif; ?>
				</div>
				
				<?php if ($bbcode == 1) : ?>
					<div id="bbcode-form" class="bbcode-form well">
						<input type="text" id="bbcode-url" class="<?php echo $input_txtarea; ?>" placeholder="<?php echo JText::_('SHOUT_BBCODE_URL'); ?>" />
						<input type="text" id="bbcode-text" class="<?php echo $input_txtarea; ?>" placeholder="<?php echo JText::_('SHOUT_BBCODE_TEXT'); ?>" />
						<input type="hidden" id="jj-bbcode-type" data-bbcode-input-type="" />
						<button id="bbcode-insert" type="button" class="<?php echo $button . $button_small; ?>"><?php echo JText::_('SHOUT_BBCODE_INSERT'); ?></button>
					</div>
					<div class="btn-toolbar">
						<div class="<?php echo $button_group; ?>">
							<button type="button" class="<?php echo $button . $button_small; ?> bbcode-button jj-bold" data-bbcode-type="b"><?php echo JText::_('SHOUT_BBCODE_BOLD'); ?></button>
							<button type="button" class="<?php echo $button . $button_small; ?> bbcode-button jj-italic" data-bbcode-type="i"><?php echo JText::_('SHOUT_BBCODE_ITALIC'); ?></button>
							<button type="button" class="<?php echo $button . $button_small; ?> bbcode-button jj-underline" data-bbcode-type="u"><?php echo JText::_('SHOUT_BBCODE_UNDERLINE'); ?></button>
							<button type="button" class="<?php echo $button . $button_small; ?> bbcode-button jj-image jj-trigger-insert" data-bbcode-type="img"><?php echo JText::_('SHOUT_BBCODE_IMG'); ?></button>
							<button type="button" class="<?php echo $button . $button_small; ?> bbcode-button jj-link jj-trigger-insert" data-bbcode-type="url"><?php echo JText::_('SHOUT_BBCODE_LINK'); ?></button>
							
							
							<?php if ($framework == 'uikit') : ?>						
								<div class="uk-button-dropdown" data-uk-dropdown>
									<button class="uk-button uk-button-small">
										<img src="<?php echo JUri::root(); ?>media/mod_shoutbox/images/icon_e_smile.gif" alt="&#9786;" />
									</button>
									<div class="uk-dropdown">
										<?php echo $helper->smileyshow(); ?>
									</div>
								</div>
							<?php else: ?>
								<button type="button" class="<?php echo $button . $button_small; ?> dropdown-toggle" data-toggle="dropdown">
									<img src="<?php echo JUri::root(); ?>media/mod_shoutbox/images/icon_e_smile.gif" alt="&#9786;" />
								</button>
								<div class="dropdown-menu">
									<?php echo $helper->smileyshow(); ?>
								</div>
							<?php endif; ?>
						

							
						</div>
						<div class="dropdown-menu">
								<?php echo $helper->smileyshow(); ?>
							</div>
					</div>
				<?php endif; ?>

				<?php
				// Shows recapture or math question depending on the parameters
				if ($securitytype == 1)
				{	
					if ($securityHide == 0 || ($user->guest && $securityHide == 1))
					{
						if ($siteKey == '' || $secretKey == '')
						{
							echo JText::_('SHOUT_RECAPTCHA_KEY_ERROR');
						}
						else
						{
							if (!isset($resp))
							{
								$resp = null;
							}

							if (!isset($error))
							{
								$error = null;
							}

							echo '<div class="g-recaptcha" data-sitekey="' . $siteKey . '" data-theme="' . $recaptchaTheme . '"></div>';
						}
					}
				}
				elseif ($securitytype == 2)
				{
					if ($securityHide == 0 || ($user->guest && $securityHide == 1))
					{
					?>
						<?php $que_number1 = $helper->randomnumber(1); ?>
						<?php $que_number2 = $helper->randomnumber(1); ?>
						<div class="form-inline <?php echo $form_row; ?>">				
							<label for="math_output"><?php echo $que_number1; ?> + <?php echo $que_number2; ?> = ?</label>
							<input type="hidden" name="jjshout[sum1]" value="<?php echo $que_number1; ?>" />
							<input type="hidden" name="jjshout[sum2]" value="<?php echo $que_number2; ?>" />
							<input class="<?php echo $input_txtarea; ?>" id="math_output" type="text" name="jjshout[human]" />
						</div>
					<?php 
					}
				}
				?>
				
				<?php if ($entersubmit == 0) : ?>
					<input name="jjshout[shout]" id="shoutbox-submit" class="<?php echo $button; ?>" type="submit" value="<?php echo JText::_('SHOUT_SUBMITTEXT'); ?>" <?php if (($securitytype == 1 && !$siteKey) || ($securitytype == 1 && !$secretKey)) { echo 'disabled="disabled"'; }?> />
				<?php endif; ?>
				
			</form>
			<?php
			// Shows mass delete button if enabled
			if ($user->authorise('core.delete') && $mass_delete == 1)
			{ 
			?>
				<form method="post" name="deleteall">
					<input type="hidden" name="jjshout[max]" value="<?php echo $actualnumber; ?>" />
					<?php echo JHtml::_('form.token'); ?>
					<div class="input-append">
						<input class="span2" type="number" name="jjshout[valueall]" min="1" max="<?php echo $actualnumber; ?>" step="1" value="1" style="width:50px;">
						<input class="<?php echo $button . $button_danger; ?>" type="submit" name="jjshout[deleteall]" value="<?php echo JText::_('SHOUT_MASS_DELETE') ?>"style="color: #FFF;" />
					</div>	
				</form>
			<?php
			}
		}
		else
		{
			// Shows no members allowed to post text
			echo '<p id="noguest">' . JText::_('SHOUT_NONMEMBER') . '</p>';
		}
		?>

		<?php if ($bbcode == 1) : ?>
			<div id="jj-image-modal" class="<?php echo $modal; ?>" tabindex="-1" role="dialog" aria-labelledby="JJ Image Modal" aria-hidden="true">
				<?php if ($framework == 'uikit') : ?>
					<div class="uk-modal-dialog">
						<a class="uk-modal-close uk-close"></a>
						<div class="uk-modal-header">
							<h3 class="image-name"></h3>
						</div>
						<img src="" alt="" />
					</div>
				<?php else: ?>
					<div class="modal-dialog modal-lg" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								<h3 class="image-name"></h3>
							</div>
							<div class="modal-body">
								<img class="<?php echo $modal_img; ?>" src="" alt="" />
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>	
	</div>
	
	<div id="jj-history-modal" class="<?php echo $modal; ?>" tabindex="-1" role="dialog" aria-labelledby="JJ History Modal" aria-hidden="true">	
		<?php if ($framework == 'uikit') : ?>
			<div class="uk-modal-dialog">
				<a class="uk-modal-close uk-close"></a>
				<div class="uk-modal-header">
					<h3><?php echo JText::_('SHOUT_HISTORY'); ?></h3>
				</div>
				<div id="jj-shout-history" class="uk-overflow-container">
					<?php 
						// Retrieves the shouts from the database
						$shouts = $helper->getShouts(0, $number, $dataerror);
						
						// Counts the number of shouts retrieved from the database
						$actualnumber = count($shouts);
						
						if ($actualnumber == 0) 
						{
							echo '<div><p>' . JText::_('SHOUT_EMPTY') . '</p></div>';
						} 
						else 
						{
							foreach ($shouts as $shout) 
							{
								echo $helper->renderPost($shout);
							}
						} 
					 ?>
					 <div class="center-block">
						<a href="#" id="jj-load-more" class="uk-button uk-button-primary"><?php echo JText::_('SHOUT_HISTORY_LOAD_MORE'); ?></a>
					 </div>
				</div>
			</div>
		<?php else: ?>
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h3><?php echo JText::_('SHOUT_HISTORY'); ?></h3>
					</div>
					<div id="jj-shout-history" class="modal-body">
						<?php 
							// Retrieves the shouts from the database
							$shouts = $helper->getShouts(0, $number, $dataerror);
							
							// Counts the number of shouts retrieved from the database
							$actualnumber = count($shouts);
							
							if ($actualnumber == 0) 
							{
								echo '<div><p>' . JText::_('SHOUT_EMPTY') . '</p></div>';
							} 
							else 
							{
								foreach ($shouts as $shout) 
								{
									echo $helper->renderPost($shout);
								}
							} 
						 ?>
						 <div class="center-block">
							<a href="#" id="jj-load-more" class="btn btn-primary"><?php echo JText::_('SHOUT_HISTORY_LOAD_MORE'); ?></a>
						 </div>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
	
	
</div>


<script type="text/javascript">
	
	var JJ_Framework_type = '<?php echo $framework; ?>';
	
	<?php if (file_exists(JPATH_ROOT . '/components/com_ajax/ajax.php')) : ?>
	
	<?php if ($notifications == 1) : ?>
		JJShoutbox.performNotificationCheck();
	<?php endif; ?>
	
	jQuery(document).ready(function($) {
		
		var count		= <?php echo $number; ?>;
		var offset		= <?php echo $number; ?>;
		var Itemid   	= <?php echo $Itemid ? $Itemid : 'null'; ?>;
		var instance 	= $('#<?php echo $uniqueIdentifier; ?>');		
		var entersubmit = '<?php echo $entersubmit; ?>';
		
		if (entersubmit == 0)
		{
			instance.find('#shoutbox-submit').on('click', function(e){
				e.preventDefault();
				JJShoutbox.doShoutboxSubmission();
			});
		}
		else
		{
			instance.find('#jj_message').keypress(function(e) {
				if (e.which == 13) 
				{
					e.preventDefault();					
					JJShoutbox.doShoutboxSubmission();
				}
			});
		}
		
		JJShoutbox.doShoutboxSubmission = function() 
		{
			var shoutboxName 	= instance.find('#shoutbox-name').val();
			var shoutboxMsg		= instance.find('#jj_message').val();
			
			<?php if ($displayName == 'user' && !$user->guest) { ?>
				var name = '<?php echo $user->username;?>';
			<?php } elseif($displayName == 'real' && !$user->guest) { ?>
				var name = '<?php echo $user->name;?>';
			<?php } else { ?>
			if (shoutboxName == '')
			{			
				<?php if ($nameRequired == 0 && $user->guest) { ?>
					var name = '<?php echo $genericName;?>';
				<?php } else { ?>		
					var name = 'JJ_None';
				<?php } ?>
			}
			else
			{			
				var name = shoutboxName;
			}
			<?php } ?>

			// Run error reporting
			if (shoutboxMsg == '')
			{
				JJShoutbox.showError(Joomla.JText._('SHOUT_MESSAGE_EMPTY'), instance);
			}
			else if (name == 'JJ_None')
			{
				JJShoutbox.showError(Joomla.JText._('SHOUT_NAME_EMPTY'), instance);
			}			
			else
			{
				var JJ_Recaptcha = typeof(grecaptcha) == 'undefined' ? '' : grecaptcha.getResponse();

				JJShoutbox.submitPost(name, '<?php echo $title; ?>', <?php echo $securitytype; ?>, '<?php echo JSession::getFormToken(); ?>', Itemid, instance, JJ_Recaptcha);
			}
		}

		
		$('#jj-load-more').on('click', function(e){
			
			e.preventDefault();

			var Itemid = '<?php echo $Itemid; ?>';
			JJShoutbox.getPostsHistory('<?php echo $title; ?>', Itemid, instance, offset);

			offset = offset + <?php echo $number; ?>;
		});
		

		// Refresh the shoutbox posts every X seconds
		setInterval(function(){
			var Itemid = '<?php echo $Itemid; ?>';
			var insertName = '<?php echo $displayName == 'user' ? $user->username : $user->name; ?>';
			JJShoutbox.getPosts('<?php echo $title; ?>', '<?php echo $sound; ?>', '<?php echo $notifications; ?>', Itemid, instance, insertName);
		}, <?php echo $refresh; ?>);
	});	
	<?php endif; ?>
</script>
