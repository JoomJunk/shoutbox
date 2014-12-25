<?php
/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2014 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die('Restricted access');

JHtml::_('stylesheet', 'mod_shoutbox/mod_shoutbox.css', array(), true);
$style = '#jjshoutboxoutput {
		border-color: ' . $bordercolour . ';
		border-width: ' . $borderwidth . 'px;
	}
	#jjshoutboxoutput div h1 {
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

$doc->addStyleDeclaration($style);
?>

<div id="jjshoutbox">
<div id="jjshoutboxoutput">
	<div class="jj-shout-error"></div>
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
	<form method="post" name="shout">
		<?php
		// Displays the Name of the user if logged in unless stated in the parameters to be a input box
		if ($displayName == 0 && !$user->guest)
		{
			echo JText::_('SHOUT_NAME') . ":" . $user->name;
		}
		elseif ($displayName == 1 && !$user->guest)
		{
			echo JText::_('SHOUT_NAME') . ":" . $user->username;
		}
		elseif ($user->guest||($displayName == 2 && !$user->guest))
		{
			?>
			<input name="jjshout[name]" type="text" maxlength="25" required="required" id="shoutbox-name" placeholder="<?php echo JText::_('SHOUT_NAME'); ?>" />
		<?php
		}

		echo '<br />';

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
		
		<?php if ( $bbcode == 0 ) : ?>
			<div class="btn-toolbar">
				<div class="btn-group">
					<button type="button" class="btn btn-small jj-bold" onClick="addSmiley('[b] [/b]', 'jj_message')">B</button>
					<button type="button" class="btn btn-small jj-italic" onClick="addSmiley('[i] [/i]', 'jj_message')">I</button>
					<button type="button" class="btn btn-small jj-underline" onClick="addSmiley('[u] [/u]', 'jj_message')">U</button>
					<button type="button" class="btn btn-small jj-link" onClick="addSmiley('[url=] [/url]', 'jj_message')">Link</button>
				</div>
			</div>
		<?php endif; ?>

		<?php if ($smile == 1 || $smile == 2) : ?>
			<?php if ($smile == 2) : ?>
				<div id="jj_smiley_button">
					<a href="#" id="jj_btn" class="btn btn-mini" />&#9650;</a>
				</div>
			<?php endif; ?>

			<div id="jj_smiley_box"><?php echo $helper->smileyshow(); ?></div>
		<?php endif; ?>

		<?php
		// Shows recapture or math question depending on the parameters
		if ($recaptcha == 0)
		{
			require_once JPATH_ROOT . '/media/mod_shoutbox/recaptcha/recaptchalib.php';

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
		?>

		<?php if ($securityQuestion == 0) : ?>
			<?php $que_number1 = $helper->randomnumber(1); ?>
			<?php $que_number2 = $helper->randomnumber(1); ?>
			<label class="jj_label"><?php echo $que_number1; ?> + <?php echo $que_number2; ?> = ?</label>
			<input type="hidden" name="jjshout[sum1]" value="<?php echo $que_number1; ?>" />
			<input type="hidden" name="jjshout[sum2]" value="<?php echo $que_number2; ?>" />
			<input class="jj_input" type="text" name="jjshout[human]" />
		<?php endif; ?>

		<input name="jjshout[shout]" id="shoutbox-submit" class="btn" type="submit" value="<?php echo $submittext ?>" <?php if (($recaptcha == 0 && !$params->get('recaptcha-public')) || ($recaptcha==0 && !$params->get('recaptcha-private')) || ($recaptcha==0 && $securityQuestion==0)) { echo 'disabled="disabled"'; }?> />
	</form>
	<?php
	// Shows mass delete button if enabled
	if ($user->authorise('core.delete'))
	{
		if ($mass_delete == 0)
		{ ?>
			<form method="post" name="deleteall">
				<input type="hidden" name="jjshout[max]" value="<?php echo $actualnumber; ?>" />
				<?php echo JHtml::_('form.token'); ?>
				<?php if (version_compare(JVERSION, '3.0.0', 'ge')) : ?>
					<div class="input-append">
						<input class="span2" type="number" name="jjshout[valueall]" min="1" max="<?php echo $actualnumber; ?>" step="1" value="1" style="width:50px;">
						<input class="btn btn-danger" type="submit" name="jjshout[deleteall]" value="<?php echo JText::_('SHOUT_MASS_DELETE') ?>"style="color: #FFF;" />
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
	
		$( "#shoutbox-submit" ).on('click', function() {
			<?php if($displayName==1 && !$user->guest){ ?>
			var name = "<?php echo $user->username;?>";
			<?php } elseif($displayName==0 && !$user->guest) { ?>
			var name = "<?php echo $user->name;?>";
			<?php } else { ?>
			if($('#shoutbox-name').val() == ""){
				var name = "<?php echo $genericName; ?>";
			}
			else{
				var name = $('#shoutbox-name').val();
			}
			<?php } ?>

			submitPost(name, '<?php echo $title; ?>', <?php echo $recaptcha ? '0' : '1'; ?>, <?php echo $securityQuestion ? '0' : '1'; ?>, '<?php echo JSession::getFormToken(); ?>', '<?php echo JUri::current(); ?>');
			return false;
		});		
	});

	// Refresh the shoutbox posts every 10 seconds - TODO: Time should probably be a parameter as the will increase server resources doing this
	setInterval(function(){getPosts('<?php echo $title; ?>', '<?php echo JUri::current(); ?>');}, 10000);
	<?php endif; ?>
</script>

