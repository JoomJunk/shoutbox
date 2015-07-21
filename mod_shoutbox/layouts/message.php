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

$postName = '';

if ($params->get('loginname') == 'user')
{
	$postName = $user->username;
}
else if ($params->get('loginname') == 'real')
{
	$postName = $user->name;
}
?>

<div>
	<div data-shout-id="<?php echo $post->id; ?>" class="shout-header" <?php echo $title; ?>>
		<span class="avatar"><?php echo $avatar; ?></span> <?php echo $post->name; ?> - <?php echo $post->when; ?>
		<?php if ($user->authorise('core.delete') || ($post->name == $postName && $params->get('deleteown') == 1)) : ?>
			<form method="post" name="delete">
				<input name="jjshout[delete]" type="submit" value="x" />
				<input name="jjshout[idvalue]" type="hidden" value="<?php echo $post->id; ?>" />
				<input name="jjshout[namevalue]" type="hidden" value="<?php echo $post->name; ?>" />
				<?php echo JHtml::_('form.token'); ?>
			</form>
		<?php endif; ?>
	</div>
	<p><?php echo $post->msg; ?></p>
</div>