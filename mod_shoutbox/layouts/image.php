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
 * 	- modal     : (string) The modal wrapper class.
 * 	- image     : (string) The image to be displayed.
 */

?>
<div id="jj-image-modal" class="<?php echo $modal; ?>" tabindex="-1" role="dialog" aria-labelledby="JJ Image Modal" aria-hidden="true">
	<?php if ($params->get('framework', 'bootstrap') == 'uikit') : ?>
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