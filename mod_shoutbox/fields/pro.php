<?php
/**
 * @package    JJ_Shoutbox
 * @author     JoomJunk <admin@joomjunk.co.uk>
 * @copyright  Copyright (C) 2011 - 2016 JoomJunk. All Rights Reserved
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('JPATH_PLATFORM') or die;

/**
 * Notification for Pro version.
 *
 * @package     JJ_Shoutbox
 * @since       1.5.6
 */
class JFormFieldPro extends JFormField
{
	/**
	 * @var string
	 */
	protected $type = 'Pro';

	/**
	 * @return string
	 */
	protected function getLabel()
	{
		$msg = '<h3>Love JJ Shoutbox? Take a look at the <a href="https://joomjunk.co.uk/products/ajax-shoutbox-pro.html" target="_blank">Pro version</a> which is packed with many more features.</h3>';

        return JFactory::getApplication()->enqueueMessage($msg, 'message');
	}

	/**
	 * @return mixed
	 */
	protected function getInput()
	{
        return;
	}
}
