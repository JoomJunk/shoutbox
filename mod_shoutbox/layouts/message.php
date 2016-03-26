<?php
/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2016 JoomJunk. All rights reserved.
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

if ($params->get('loginname', 'user') == 'user')
{
	$userName = $user->username;
}
else if ($params->get('loginname', 'user') == 'real')
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

if ($params->get('framework', 'bootstrap') == 'uikit')
{
	$iconEdit = 'uk-icon-pencil-square-o';
	$iconRemove = 'uk-icon-times';
}
else if ($params->get('framework', 'bootstrap') == 'bootstrap3')
{
	$iconEdit = 'glyphicon glyphicon-pencil';
	$iconRemove = 'glyphicon glyphicon-remove';
}
else
{
	$iconEdit = 'icon-pencil';
	$iconRemove = 'icon-remove';
}
?>

<div>
	<div data-shout-id="<?php echo $post->id; ?>" data-shout-name="<?php echo $postName; ?>" class="shout-header" <?php echo $title; ?>>
		<span class="avatar"><?php echo $avatar; ?></span> <?php echo $post->name; ?> - <?php echo $post->when; ?>
		<div class="shout-actions">
			<?php if (($params->get('editown', 1) == 1) && $postName == $userName) : ?>
				<a href="#" data-shout-edit-id="<?php echo $post->id; ?>" class="jj-shout-edit <?php echo $iconEdit;?>"></a>
			<?php endif; ?>
			<?php if ($user->authorise('core.delete') || ($postName == $userName && $params->get('deleteown') == 1)) : ?>
				<form method="post" name="delete">			
					<button type="submit" name="jjshout[delete]" class="shout-remove <?php echo $iconRemove;?>"></button>
					<input name="jjshout[idvalue]" type="hidden" value="<?php echo $post->id; ?>" />
					<input name="jjshout[namevalue]" type="hidden" value="<?php echo $postName; ?>" />
					<?php echo JHtml::_('form.token'); ?>
				</form>
			<?php endif; ?>
		</div>
	</div>
	<p><?php echo $post->msg; ?></p>
</div>