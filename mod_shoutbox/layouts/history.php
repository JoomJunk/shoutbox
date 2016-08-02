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
 * 	- shouts    : (stdClass) Object containing the shouts
 * 	- modal     : (string) The modal wrapper class.
 * 	- title     : (string) The shoutbox title
 */

$helper = new ModShoutboxHelper($title);
?>
<div id="jj-history-modal" class="<?php echo $modal; ?>" tabindex="-1" role="dialog" aria-labelledby="JJ History Modal" aria-hidden="true">
	<?php if ($params->get('framework', 'bootstrap') == 'uikit') : ?>
		<div class="uk-modal-dialog">
			<a class="uk-modal-close uk-close"></a>
			<div class="uk-modal-header">
				<h3><?php echo JText::_('SHOUT_HISTORY'); ?></h3>
			</div>
			<div id="jj-shout-history" class="jj-shout-history uk-overflow-container">
				<?php
					foreach ($shouts as $shout)
					{
						echo $helper->renderPost($shout);
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
						foreach ($shouts as $shout)
						{
							echo $helper->renderPost($shout);
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