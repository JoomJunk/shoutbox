<?php
/**
* @package    JJ_Shoutbox
* @copyright  Copyright (C) 2011 - 2015 JoomJunk. All rights reserved.
* @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/


defined('JPATH_PLATFORM') or die;

/**
 * Form Field to check if Freichat is enabled.
 *
 * @package     JJ_Shoutbox
 * @since       2.0.0
 */
class JFormFieldCheck extends JFormField
{
	/**
	 * @var string
	 */
	protected $type = 'Check';

	/**
	 * @return string
	 */
	protected function getLabel()
	{
	
		// Database query to check id Freichat exists
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		 
		$query->select(array('*'))
		      ->from($db->quoteName('#__extensions'))
		      ->where($db->quoteName('element') . ' = '. $db->quote('mod_freichatx'));

		$db->setQuery($query);
		$result = $db->loadObjectList();

		if($result)
		{
			// Detect Joomla version and render the message
			if (version_compare(JVERSION, '3.0.0', 'ge'))
			{			
				$app = JFactory::getApplication();
				$app->enqueueMessage(JText::_('WARNING_FREICHAT_IS_INSTALLED'), 'warning');
			}
			else
			{
				return JError::raiseNotice( 100, JText::_('WARNING_FREICHAT_IS_INSTALLED') );
			}
		}		

	}

	/**
	 * @return mixed
	 */
	protected function getInput()
	{
        return;
	}

}
