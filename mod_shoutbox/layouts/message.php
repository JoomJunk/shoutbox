<?php
/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2015 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die('Restricted access');

extract($displayData);

/**
 * Layout variables
 * ------------------
 * 	- post       : (stdClass) Object containing the post
 * 	- user       : (JUser) The user object of the currently logged in user.
 *  - title      : (string) The string containing the IP Address of the user who submitted the post (only available to admins)
 *  - avatar     : (string) The string containing the avatar (if available). Empty string if no avatar.
 */
 
$user = JFactory::getUser();

$userName = '';

if ($params->get('loginname') == 'user')
{
	$userName = $user->username;
}
else if ($params->get('loginname') == 'real')
{
	$userName = $user->name;
}

// Strip <a> from the username
if (in_array($params->get('profile'), array(1, 2, 3, 4)))
{
	$postName = strip_tags($post->name);
}
else
{
	$postName = $post->name;
}
?>

<div>
	<div data-shout-id="<?php echo $post->id; ?>" data-shout-name="<?php echo $postName; ?>" class="shout-header" <?php echo $title; ?>>
		<span class="avatar"><?php echo $avatar; ?></span> <?php echo $post->name; ?> - <?php echo $post->when; ?>
		<?php if ($user->authorise('core.delete') || ($postName == $userName && $params->get('deleteown') == 1)) : ?>
			<form method="post" name="delete">
				<input name="jjshout[delete]" type="submit" value="x" />
				<input name="jjshout[idvalue]" type="hidden" value="<?php echo $post->id; ?>" />
				<input name="jjshout[namevalue]" type="hidden" value="<?php echo $postName; ?>" />
				<?php echo JHtml::_('form.token'); ?>
			</form>
		<?php endif; ?>
	</div>
	<p><?php echo $post->msg; ?></p>
</div>
