<?php
/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2016 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
 */
 
defined('_JEXEC') or die('Restricted access');

/**
 * Base class for rendering a display layout
 * loaded from from a layout file
 *
 * This class searches for Joomla! version override Layouts. For example,
 * if you have run this under Joomla! 3.0 and you try to load
 * mylayout.default it will automatically search for the
 * layout files default.j30.php, default.j3.php and default.php, in this
 * order.
 *
 * @since    1.0
 */
class JJShoutboxLayoutFile extends JLayoutFile
{
	/**
	 * Refresh the list of include paths
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected function refreshIncludePaths()
	{
		// Reset includePaths
		$this->includePaths = array();

		// Module layouts & overrides if exist
		$module = $this->options->get('module', null);

		if (!empty($module))
		{
			if ($this->options->get('client') == 0)
			{
				$this->addIncludePaths(JPATH_SITE . '/modules/' . $module . '/layouts');
			}
			else
			{
				$this->addIncludePaths(JPATH_ADMINISTRATOR . '/modules/' . $module . '/layouts');
			}

			// Module template overrides path
			$this->addIncludePath(JPATH_THEMES . '/' . JFactory::getApplication()->getTemplate() . '/html/layouts/modules/' . $module);
		}
	}

}