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
	}';

if (version_compare(JVERSION, '3.0.0', 'le'))
{
	$style .= '#jj_btn {
		width: 25px !important;
	}';
}

if ($user->authorise('core.delete'))
{
	$style .= '#jjshoutboxoutput input[type=submit]{
		color:' . $deletecolor . ';
	}';
}

if ($avatar != 'none')
{
	$style .= '
	#jjshoutboxoutput .shout-header {
		height: auto;
	}
	#jjshoutboxoutput .avatar {
		margin-right: 5px;
	}
	#jjshoutboxoutput .kavatar {
		height: 30px;
	}';
}

$doc->addStyleDeclaration($style);
$uniqueIdentifier = 'jjshoutbox' . $uniqueId;
?>

<div id="<?php echo $uniqueIdentifier; ?>">
<div id="jjshoutboxoutput">
	<div class="jj-shout-new"></div>
	<?php // Retrieves the shouts from the database ?>
	<?php $shouts = $helper->getShouts($number, $dataerror); ?>

	<?php // Counts the number of shouts retrieved from the database ?>
	<?php $actualnumber = count($shouts); ?>

	<?php if ($actualnumber == 0) : ?>
		<div><p><?php echo JText::_('SHOUT_EMPTY') ?></p></div>
	<?php else : ?>
		<?php foreach ($shouts as $shout) : ?>
			<?php echo $helper->renderPost($shout); ?>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
<div class="jj-shout-error"></div>

<?php if ( $sound == 1 ) : ?>
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
		elseif ($user->guest||($displayName == 'choose' && !$user->guest))
		{
			?>
			<input name="jjshout[name]" type="text" maxlength="25" required="required" id="shoutbox-name" placeholder="<?php echo JText::_('SHOUT_NAME'); ?>" />
		<?php
		}

		// Adds in session token to prevent re-posts and a security token to prevent CRSF attacks
		$_SESSION['token'] = uniqid("token", true);
		echo JHtml::_('form.token');
		?>
		<input name="jjshout[token]" type="hidden" value="<?php echo $_SESSION['token'];?>" />

		<span id="charsLeft"></span>

		<textarea 
			id="jj_message"  
			cols="20" 
			rows="5" 
			name="jjshout[message]" 
			onKeyDown="textCounter('jj_message','messagecount',<?php echo $messageLength; ?>, <?php echo $alertLength; ?>, <?php echo $warnLength; ?>, '<?php echo $remainingLength; ?>');" 
			onKeyUp="textCounter('jj_message','messagecount',<?php echo $messageLength; ?>, <?php echo $alertLength; ?>, <?php echo $warnLength; ?>, '<?php echo $remainingLength; ?>');"
		></textarea>
		
		<?php if ( $bbcode == 1 ) : ?>
			<div class="btn-toolbar">
				<div class="<?php echo $button_group; ?>">
					<button type="button" class="<?php echo $button; ?> btn-small jj-bold" data-bbcode-type="b"><?php echo JText::_('SHOUT_BBCODE_BOLD'); ?></button>
					<button type="button" class="<?php echo $button; ?> btn-small jj-italic" data-bbcode-type="i"><?php echo JText::_('SHOUT_BBCODE_ITALIC'); ?></button>
					<button type="button" class="<?php echo $button; ?> btn-small jj-underline" data-bbcode-type="u"><?php echo JText::_('SHOUT_BBCODE_UNDERLINE'); ?></button>
					<button type="button" class="<?php echo $button; ?> btn-small jj-link" data-bbcode-type="url"><?php echo JText::_('SHOUT_BBCODE_LINK'); ?></button>
				</div>
			</div>
		<?php endif; ?>

		<?php if ($smile == 1 || $smile == 2  || $smile == 3) : ?>
			<?php if ($smile == 2 || $smile == 3) : ?>
				<div id="jj_smiley_button">
					<a href="#" id="jj_btn" class="<?php echo $button; ?> btn-mini <?php echo ($smile == 2 ? 'rotated' : ''); ?>" />&#9650;</a>
				</div>
			<?php endif; ?>
			<div id="jj_smiley_box" style="<?php echo ($smile == 2 ? 'display:none;' : 'display:block;'); ?>"><?php echo $helper->smileyshow(); ?></div>
		<?php endif; ?>

		<?php
		// Shows recapture or math question depending on the parameters
		if ($securitytype == 1)
		{
			require_once JPATH_ROOT . '/media/mod_shoutbox/recaptcha/recaptchalib.php';

			if ($publicKey == '' || $privateKey == '')
			{
				echo JText::_('SHOUT_RECAPTCHA_KEY_ERROR');
			}
			else
			{
				$publickey = $publicKey;

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
		elseif ($securitytype == 2)
		{
		?>
			<?php $que_number1 = $helper->randomnumber(1); ?>
			<?php $que_number2 = $helper->randomnumber(1); ?>
			<label class="jj_label" for="math_output"><?php echo $que_number1; ?> + <?php echo $que_number2; ?> = ?</label>
			<input type="hidden" name="jjshout[sum1]" value="<?php echo $que_number1; ?>" />
			<input type="hidden" name="jjshout[sum2]" value="<?php echo $que_number2; ?>" />
			<input class="jj_input" id="math_output" type="text" name="jjshout[human]" />
		<?php } ?>

		<input name="jjshout[shout]" id="shoutbox-submit" class="<?php echo $button; ?>" type="submit" value="<?php echo $submittext ?>" <?php if (($securitytype == 1 && !$publicKey) || ($securitytype == 1 && !$privateKey)) { echo 'disabled="disabled"'; }?> />
	</form>
	<?php
	// Shows mass delete button if enabled
	if ($user->authorise('core.delete'))
	{
		if ($mass_delete == 1)
		{ ?>
			<form method="post" name="deleteall">
				<input type="hidden" name="jjshout[max]" value="<?php echo $actualnumber; ?>" />
				<?php echo JHtml::_('form.token'); ?>
				<?php if (version_compare(JVERSION, '3.0.0', 'ge')) : ?>
					<div class="input-append">
						<input class="span2" type="number" name="jjshout[valueall]" min="1" max="<?php echo $actualnumber; ?>" step="1" value="1" style="width:50px;">
						<input class="<?php echo $button . $button_danger; ?>" type="submit" name="jjshout[deleteall]" value="<?php echo JText::_('SHOUT_MASS_DELETE') ?>"style="color: #FFF;" />
					</div>	
				<?php else : ?>
					<input class="jj_admin_label" type="number" name="jjshout[valueall]" min="1" max="<?php echo $actualnumber; ?>" step="1" value="1" />
					<input class="jj_admin_button" name="jjshout[deleteall]" type="submit" value="<?php echo JText::_('SHOUT_MASS_DELETE') ?>" />
				<?php endif; ?>
			</form>
		<?php
		}
	}
}
else
{
	// Shows no members allowed to post text
	?>
	<p id="noguest"><?php echo $nonmembers; ?></p>
<?php
}
?>
</div>
</div>
<script type="text/javascript">

	<?php // The ajax uses com_ajax in Joomla core from Joomla 3.2 and available as an install for Joomla 2.5 - so check if its available ?>
	<?php if (file_exists(JPATH_ROOT . '/components/com_ajax/ajax.php')) : ?>
	jQuery(document).ready(function($) {

		var Itemid   = '<?php echo $Itemid; ?>';
		var instance = $('#<?php echo $uniqueIdentifier; ?>');

		instance.find('#shoutbox-submit').on('click', function()
		{
			var shoutboxName 	= $('#shoutbox-name').val();
			var shoutboxMsg		= $('#jj_message').val();
			
			<?php if($displayName == 'user' && !$user->guest){ ?>
				var name = "<?php echo $user->username;?>";
			<?php } elseif($displayName == 'real' && !$user->guest) { ?>
				var name = "<?php echo $user->name;?>";
			<?php } else { ?>
			if( shoutboxName == '' )
			{			
				<?php if($nameRequired == 0 && $user->guest){ ?>
					var name = "<?php echo $genericName;?>";
				<?php } else { ?>		
					var name = "JJ_None";
				<?php } ?>			
			}
			else
			{			
				var name = shoutboxName;
			}
			<?php } ?>

			// Run error reporting
			if( shoutboxMsg == '' )
			{
				showError(shoutboxMsg, instance);
			}
			else if ( name == 'JJ_None' )
			{
				showError(name, instance);
			}			
			else
			{
				JJsubmitPost(name, '<?php echo $title; ?>', <?php echo $securitytype; ?>, '<?php echo JSession::getFormToken(); ?>', Itemid, instance);
			}

			return false;
		});
	});

	// Refresh the shoutbox posts every X seconds
	setInterval(function(){
		var Itemid = '<?php echo $Itemid; ?>';
		JJgetPosts('<?php echo $title; ?>', '<?php echo $sound; ?>', Itemid, instance);
	}, <?php echo $refresh; ?>);
	
	<?php endif; ?>
</script>

