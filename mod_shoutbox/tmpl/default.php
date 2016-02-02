<?php
/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2016 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die('Restricted access');

JHtml::_('stylesheet', 'mod_shoutbox/mod_shoutbox.css', array(), true);
$style = '.jjshoutboxoutput {
			height: ' . $outputheight . 'px;
			border-color: ' . $bordercolour . ';
			border-width: ' . $borderwidth . 'px;
		}
		.jjshoutboxform textarea {
			height:' . $textareaheight . 'px;
		}
		.jjshoutboxoutput div p {
			color:' . $textcolor . ';
		}
		.jjshoutbox .shout-header {
			background: ' . $headercolor . ';
			color:' . $headertextcolor . ';
		}
		.shout-actions .shout-remove {
			color:' . $deletecolor . ';
		}
		.shout-actions .jj-shout-edit {
			color:' . $editcolor . ';
		}';

if ($avatar != 'none')
{
	$style .= '
	.jjshoutbox .shout-header {
		height: auto;
	}
	.jjshoutbox .avatar img {
		margin-right: 5px;
		height: 30px;
		width: 30px;
	}';
}

// Import Bootstrap framework and stylesheet for fallback styling
if ($framework == 'none')
{
	JHtml::_('stylesheet', 'mod_shoutbox/mod_shoutbox_bs.css', array(), true);
	JHtml::_('bootstrap.framework');
	JHtml::_('behavior.modal');
}

// Prevent the image overlapping on Bootstrap 2 modal
if ($framework == 'bootstrap')
{	
	$style .= '
	.jjshoutbox .modal-body > img {
		float: left;
		padding-bottom: 1%;
	}';
}
if ($framework == 'bootstrap' || $framework == 'uikit')
{
	$style .= '
	.jjshoutbox .mass_delete input {
		max-width: 80px;
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
JText::script('SHOUT_BBCODE_INSERT_IMG');
JText::script('SHOUT_BBCODE_INSERT_URL');
JText::script('SHOUT_EDITOWN_TOO_LATE');
JText::script('SHOUT_SUBMITTEXT');
JText::script('SHOUT_UPDATE');
JText::script('SHOUT_AJAX_ERROR');
?>

<div id="<?php echo $uniqueIdentifier; ?>" class="jjshoutbox <?php echo $jj_class; ?>">

	<div id="jjshoutboxoutput" class="jjshoutboxoutput">
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
			
			if ($history == 1)
			{
			?>
				<div id="jj-history-container" class="center-block">
					<a href="#" id="jj-history-trigger" class="btn btn-primary btn-mini btn-xs uk-button uk-button-primary uk-button-mini"><?php echo JText::_('SHOUT_HISTORY_BUTTON'); ?></a>
				</div>	 
			<?php 
			} 
		?>
	</div>
	<div class="jj-shout-error"></div>

	<?php if ($sound == 1) : ?>
		<audio class="jjshoutbox-audio" preload="auto">
			<source src="<?php echo JUri::root(); ?>/media/mod_shoutbox/sounds/notification.mp3" type="audio/mpeg">
			<source src="<?php echo JUri::root(); ?>/media/mod_shoutbox/sounds/notification.ogg" type="audio/ogg">
		</audio>
	<?php endif; ?>

	<div id="jjshoutboxform" class="jjshoutboxform">
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
							echo '<input name="jjshout[name]" type="text" maxlength="25" required="required" id="shoutbox-name" class="' . $input_txtarea . ' fullwidth" placeholder="' . JText::_('SHOUT_NAME') . '" />';
						}

						// Adds in session token to prevent re-posts and a security token to prevent CRSF attacks
						$app->setUserState('token', uniqid("token", true));
						echo JHtml::_('form.token');
					?>
				</div>
				
				<input name="jjshout[token]" type="hidden" value="<?php echo $app->getUserState('token'); ?>" />
				
				<div class="<?php echo $form_row; ?>">
					<?php if ($enablelimit == 1) : ?>
						<span id="charsLeft"></span>
						<textarea 
							id="jj_message"
							class="<?php echo $input_txtarea; ?>"
							name="jjshout[message]"
							onKeyDown="JJShoutbox.textCounter('jj_message','messagecount',<?php echo $messageLength; ?>, <?php echo $alertLength; ?>, <?php echo $warnLength; ?>, '<?php echo $remainingLength; ?>');" 
							onKeyUp="JJShoutbox.textCounter('jj_message','messagecount',<?php echo $messageLength; ?>, <?php echo $alertLength; ?>, <?php echo $warnLength; ?>, '<?php echo $remainingLength; ?>');"
						></textarea>
					<?php else: ?>
						<textarea id="jj_message" class="<?php echo $input_txtarea; ?>" name="jjshout[message]"></textarea>
					<?php endif; ?>
				</div>
				
				<?php if ($bbcode == 1) : ?>
					<div id="bbcode-form" class="bbcode-form well">
						<p></p>
						<input type="text" id="bbcode-url" class="<?php echo $input_txtarea; ?>" placeholder="<?php echo JText::_('SHOUT_BBCODE_URL'); ?>" />
						<input type="text" id="bbcode-text" class="<?php echo $input_txtarea; ?>" placeholder="<?php echo JText::_('SHOUT_BBCODE_TEXT'); ?>" />
						<input type="hidden" id="jj-bbcode-type" data-bbcode-input-type="" />
						<button id="bbcode-cancel" type="button" class="<?php echo $button . $button_small . $button_danger; ?>"><?php echo JText::_('SHOUT_BBCODE_CANCEL'); ?></button>
						<button id="bbcode-insert" type="button" class="<?php echo $button . $button_small . $button_prim; ?>"><?php echo JText::_('SHOUT_BBCODE_INSERT'); ?></button>
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
									<button class="uk-button uk-button-small" type="button">
										<img src="<?php echo JUri::root(); ?>images/mod_shoutbox/icon_e_smile.gif" alt="&#9786;" />
									</button>
									<ul class="uk-dropdown uk-dropdown-flip">
										<?php echo $helper->smileyshow(); ?>
									</ul>
								</div>
							<?php elseif ($framework == 'bootstrap') : ?>
								<button type="button" class="<?php echo $button . $button_small; ?> dropdown-toggle" data-toggle="dropdown">
									<img src="<?php echo JUri::root(); ?>images/mod_shoutbox/icon_e_smile.gif" alt="&#9786;" />
								</button>
								<ul class="dropdown-menu inline unstyled">
									<?php echo $helper->smileyshow(); ?>
								</ul>
							<?php else : ?>
								<button type="button" class="<?php echo $button . $button_small; ?> dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
									<img src="<?php echo JUri::root(); ?>images/mod_shoutbox/icon_e_smile.gif" alt="&#9786;" />
								</button>
								<ul class="dropdown-menu list-inline list-unstyled">
									<?php echo $helper->smileyshow(); ?>
								</ul>
							<?php endif; ?>
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
							<canvas id="mathscanvas" width="80" height="30">Your browser does not support the HTML5 canvas tag.</canvas>
							<input type="hidden" name="jjshout[sum1]" value="<?php echo $que_number1; ?>" />
							<input type="hidden" name="jjshout[sum2]" value="<?php echo $que_number2; ?>" />
							<input class="<?php echo $input_txtarea; ?> fullwidth" id="math_output" type="text" name="jjshout[human]" />
						</div>
					<?php 
					}
				}
				?>
				
				<a id="edit-cancel" href="#" class="edit-cancel <?php echo $button . $button_danger; ?>"><?php echo JText::_('SHOUT_BBCODE_CANCEL'); ?></a>
				
				<input id="shout-submit-type" type="hidden" data-shout-id="0" data-submit-type="insert" />
				
				<?php if ($entersubmit == 0) : ?>
					<input name="jjshout[shout]" id="shoutbox-submit" class="<?php echo $button; ?> fullwidth" type="submit" value="<?php echo JText::_('SHOUT_SUBMITTEXT'); ?>" <?php if (($securitytype == 1 && !$siteKey) || ($securitytype == 1 && !$secretKey)) { echo 'disabled="disabled"'; }?> />
				<?php endif; ?>
				
			</form>
			<?php
			// Shows mass delete button if enabled
			if ($user->authorise('core.delete') && $mass_delete == 1)
			{
			?>
				<form method="post" <?php echo 'class="' . $form . '"'; ?>>
					<input type="hidden" name="jjshout[max]" value="<?php echo $count; ?>" />
					
					<div class="mass_delete">
						<?php $style   = ($framework == 'bootstrap3') ? 'style="display:inline-block"' : ''; ?>
						<?php $latest  = '<select name="jjshout[order]" class="' . $input_small . '" ' . $style . '>'; ?>
						<?php $latest .= '<option value="DESC" selected="selected">' . JText::_('SHOUT_NEWEST_POSTS') . '</option>'; ?>
						<?php $latest .= '<option value="ASC">' . JText::_('SHOUT_OLDEST_POSTS') . '</option>'; ?>
						<?php $latest .= '</select>'; ?>
						<?php // In bootstrap 2 the box-sizing in the CSS file interferes with the input number field ?>
						<?php $style   = ($framework == 'bootstrap') ? 'style="box-sizing:inherit"' : $style; ?>
						<?php $input   = '<input class="' . $input_small . '" type="number" name="jjshout[valueall]" min="1" max="' . $count . '" step="1" value="1" ' . $style . '>'; ?>
						<?php echo JText::sprintf('SHOUT_DELETE_THE_LATEST_X_POSTS', $latest, $input); ?>
						<button class="<?php echo $button . $button_danger; ?>" name="jjshout[deleteall]" type="submit"><?php echo JText::_('SHOUT_MASS_DELETE') ?></button>
					</div>
					
					<?php echo JHtml::_('form.token'); ?>

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
	
	<?php if ($history == 1 ) : ?>
		<div id="jj-history-modal" class="<?php echo $modal; ?>" tabindex="-1" role="dialog" aria-labelledby="JJ History Modal" aria-hidden="true">	
			<?php if ($framework == 'uikit') : ?>
				<div class="uk-modal-dialog">
					<a class="uk-modal-close uk-close"></a>
					<div class="uk-modal-header">
						<h3><?php echo JText::_('SHOUT_HISTORY'); ?></h3>
					</div>
					<div id="jj-shout-history" class="jj-shout-history uk-overflow-container">
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
						<div id="jj-shout-history" class="jj-shout-history modal-body">
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
	<?php endif; ?>
	
</div>


<script type="text/javascript">
	
	<?php if ($securitytype == 2) {
			if ($securityHide == 0 || ($user->guest && $securityHide == 1)) { ?>
				JJShoutbox.drawMathsQuestion(<?php echo $que_number1; ?>, <?php echo $que_number2; ?>);
	<?php } } ?>
	
	var JJ_Framework_type = '<?php echo $framework; ?>';
	var JJ_History        = '<?php echo $history; ?>';
	var JJ_Edit_own       = '<?php echo $editown; ?>';
	
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
			instance.on('click', '#shoutbox-submit', function(e){
				e.preventDefault();
				JJShoutbox.doShoutboxSubmission(instance.find('#shout-submit-type').attr('data-submit-type'), instance.find('#shout-submit-type').attr('data-shout-id'));
			});
		}
		else
		{
			instance.on('keydown', '#jj_message', function(e) {
				if (e.which == 13) 
				{
					e.preventDefault();
					JJShoutbox.doShoutboxSubmission(instance.find('#shout-submit-type').attr('data-submit-type'), instance.find('#shout-submit-type').attr('data-shout-id'));
				}
			});
		}
		
		JJShoutbox.doShoutboxSubmission = function(type, shoutId) 
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
				var JJ_Recaptcha = '';
				<?php if ($securitytype == 1) : ?>
				JJ_Recaptcha = typeof(grecaptcha) == 'undefined' ? '' : grecaptcha.getResponse();
				<?php endif; ?>

				JJShoutbox.submitPost(shoutId, type, name, '<?php echo $title; ?>', <?php echo $securitytype; ?>, '<?php echo JSession::getFormToken(); ?>', Itemid, instance, JJ_Recaptcha, <?php echo $securityHide; ?>, JJ_History);
			}
		}

		if (JJ_History == 1)
		{
			$('#jj-load-more').on('click', function(e){
				
				e.preventDefault();

				var Itemid = '<?php echo $Itemid; ?>';
				JJShoutbox.getPostsHistory('<?php echo $title; ?>', Itemid, instance, offset);

				offset = offset + <?php echo $number; ?>;
			});
		}
		
		
		if (JJ_Edit_own == 1)
		{
			$('#jjshoutboxoutput').on('click', '.jj-shout-edit', function(e) {

				e.preventDefault();
				
				var shoutId = $(this).attr('data-shout-edit-id');
				
				JJShoutbox.checkTimestamp('<?php echo $title; ?>', Itemid, instance, shoutId);
				
			});
		}
		
		

		// Refresh the shoutbox posts every X seconds
		setInterval(function(){
			var Itemid = '<?php echo $Itemid; ?>';
			var insertName = '<?php echo $displayName == 'user' ? $user->username : $user->name; ?>';
			JJShoutbox.getPosts('<?php echo $title; ?>', '<?php echo $sound; ?>', '<?php echo $notifications; ?>', Itemid, instance, insertName, JJ_History);
		}, <?php echo $refresh; ?>);
	});	
	<?php endif; ?>
</script>
