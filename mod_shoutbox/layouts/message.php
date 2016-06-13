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

?>
<div>
	<div data-shout-id="<?php echo $post->id; ?>" data-shout-name="<?php echo strip_tags($post->name); ?>" class="shout-header" <?php echo $title; ?>>
		<span class="avatar"><?php echo $avatar; ?></span> <?php echo $post->name; ?> - <?php echo $post->when; ?>
		<div class="shout-actions">
			<?php if (($params->get('editown', 1) == 1) && $user->id == $post->user_id) : ?>
				<a href="#" data-shout-edit-id="<?php echo $post->id; ?>" class="jj-shout-edit"><img src="<?php echo JUri::root(true); ?>/media/mod_shoutbox/images/edit.svg" width="13" /></a>
			<?php endif; ?>
			<?php if ($user->authorise('core.delete') || ($user->id == $post->user_id && $params->get('deleteown') == 1)) : ?>
				<form method="post" name="delete">
					<button type="submit" name="jjshout[delete]" class="shout-remove"><img src="<?php echo JUri::root(true); ?>/media/mod_shoutbox/images/remove.svg" width="13" /></button>
					<input name="jjshout[idvalue]" type="hidden" value="<?php echo $post->id; ?>" />
					<input name="jjshout[useridvalue]" type="hidden" value="<?php echo $post->user_id; ?>" />
					<?php echo JHtml::_('form.token'); ?>
				</form>
			<?php endif; ?>
		</div>
	</div>
	<p><?php echo $post->msg; ?></p>
</div>