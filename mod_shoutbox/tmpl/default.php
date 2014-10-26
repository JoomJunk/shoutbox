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

$user = JFactory::getUser();

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
					if ($smile == 0 || $bbcode == 0)
					{
						echo ModShoutboxHelper::bbcodeFilter($profile_link);
					}
					else
					{
						echo $profile_link;
					}
					?> - <?php
					echo JHtml::date($shouts[$i]->when, $show_date . 'H:i', true);

					if ($user->authorise('core.delete'))
					{
						?>
						<form method="post" name="delete">
							<input name="jjshout[delete]" type="submit" value="x" />
							<input name="jjshout[idvalue]" type="hidden" value="<?php echo $shouts[$i]->id ?>" />
							<?php echo JHtml::_('form.token'); ?>
						</form>
					<?php
					}
					?>
				</h1>
				<p>
					<?php
					if ($smile == 0 || $smile == 1 || $smile == 2 || $bbcode == 0)
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
// Retrieve the list of user groups the user has access to
$access = JFactory::getUser()->getAuthorisedGroups();

// Convert the parameter string into an integer
$i=0;
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

		<textarea id="jj_message"  cols="20" rows="5" name="jjshout[message]" onKeyDown="textCounter('jj_message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);" onKeyUp="textCounter('jj_message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);"></textarea>
		
		<?php if ( $bbcode == 0 ) 
		{ ?>
			<div class="btn-toolbar">
				<div class="btn-group">
					<button type="button" class="btn btn-small jj-bold" onClick="addSmiley('[b] [/b]', 'jj_message')">B</button>
					<button type="button" class="btn btn-small jj-italic" onClick="addSmiley('[i] [/i]', 'jj_message')">I</button>
					<button type="button" class="btn btn-small jj-underline" onClick="addSmiley('[u] [/u]', 'jj_message')">U</button>
					<button type="button" class="btn btn-small jj-link" onClick="addSmiley('[url=] [/url]', 'jj_message')">Link</button>
				</div>
			</div>
		<?php
		}
		
		if ($smile == 1 || $smile == 2)
		{
			if ($smile == 2)
			{
				echo '<div id="jj_smiley_button">
						<a href="#" id="jj_btn" class="btn btn-mini" />&#9650;</a>
					  </div>';
			}

			echo '<div id="jj_smiley_box">' . ModShoutboxHelper::smileyshow() . '</div>';
		} ?>
		<script type="text/javascript">
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
			textCounter('jj_message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);
		</script>

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

		if ($securityQuestion == 0)
		{
			$que_number1 = ModShoutboxHelper::randomnumber(1);
			$que_number2 = ModShoutboxHelper::randomnumber(1); ?>
			<label class="jj_label"><?php echo $que_number1; ?> + <?php echo $que_number2; ?> = ?</label>
			<input type="hidden" name="jjshout[sum1]" value="<?php echo $que_number1; ?>" />
			<input type="hidden" name="jjshout[sum2]" value="<?php echo $que_number2; ?>" />
			<input class="jj_input" type="text" name="jjshout[human]" />
		<?php
		}

		if ($recaptcha == 0 && $securityQuestion == 0)
		{
			// Shows warning if both security questions are enabled and logs to error file.
			JLog::add(JText::_('SHOUT_BOTH_SECURITY_ENABLED'), JLog::CRITICAL, 'mod_shoutbox');
			JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_BOTH_SECURITY_ENABLED'), 'error');
		}
		?>
		<input name="jjshout[shout]" id="shoutbox-submit" class="btn" type="submit" value="<?php echo $submittext ?>" <?php if (($recaptcha == 0 && !$params->get('recaptcha-public')) || ($recaptcha==0 && !$params->get('recaptcha-private')) || ($recaptcha==0 && $securityQuestion==0)) { echo 'disabled="disabled"'; }?> />
	</form>
	<?php
	// Shows mass delete button if enabled
	if ($user->authorise('core.delete'))
	{
		if ($mass_delete == 0)
		{ ?>
			<form method="post" name="deleteall">
				<input type="hidden" name="jjshout[max]" value="<?php echo $number; ?>" />
				<?php echo JHtml::_('form.token'); ?>
				<?php if (version_compare(JVERSION, '3.0.0', 'ge')) : ?>
					<div class="input-append">
						<input class="span2" type="number" name="jjshout[valueall]" min="1" max="<?php echo $number; ?>" step="1" value="1" style="width:50px;">
						<input class="btn btn-danger" type="submit" name="jjshout[deleteall]" value="<?php echo JText::_('SHOUT_MASS_DELETE') ?>"style="color: #FFF;" />
					</div>	
				<?php else : ?>
					<input class="jj_admin_label" type="number" name="jjshout[valueall]" min="1" max="<?php echo $number; ?>" step="1" value="1" />
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
	(function($){
		$( "#shoutbox-submit" ).click( function() {
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
	})(jQuery);
</script>
