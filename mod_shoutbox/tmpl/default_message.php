<?php
/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2014 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die('Restricted access');

$user = JFactory::getUser();
?>
<div>
	<h1{TITLE}>
		{USER} -  {DATE}
		<?php if ($user->authorise('core.delete')) : ?>
			<form method="post" name="delete">
				<input name="jjshout[delete]" type="submit" value="x" />
				<input name="jjshout[idvalue]" type="hidden" value="{POSTID}" />
				<?php echo JHtml::_('form.token'); ?>
			</form>
		<?php endif; ?>
	</h1>
	<p>{MESSAGE}</p>
</div>