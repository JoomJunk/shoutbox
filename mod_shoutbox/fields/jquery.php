<?php
/**
 * @package    JJ_Shoutbox
 * @author     JoomJunk <admin@joomjunk.co.uk>
 * @copyright  Copyright (C) 2011 - 2013 JoomJunk. All Rights Reserved
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die('Restricted access');

JFormHelper::loadFieldClass('list');

/**
 * Form Field class for JoomJunk.
 * Provides radio button inputs for the jQuery insertation in Joomla 2.5 only
 *
 * @package     JJ_Shoutbox
 * @since       2.0.0
 */
class JFormFieldjQuery extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  2.0.0
	 */
	protected $type = 'jQuery';

	/**
	 * Method to get the list field input markup.
	 *
	 * @return  string  The field input markup if version is less than Joomla 3.0, else text string.
	 *
	 * @since   2.0.0
	 */
	protected function getInput()
	{
		if (version_compare(JVERSION,'3.0.0','ge'))
		{
			return '<span class="readonly">' . JText::_('SHOUT_NOJQUERY') . '</span>';
		}
		else
		{
			return parent::getInput();
		}
	}
}