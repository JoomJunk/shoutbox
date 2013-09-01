<?php
/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2013 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
 */
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Shoutbox installation script class.
 *
 * @since  1.1.2
 */
class Mod_ShoutboxInstallerScript
{
	/**
	 * @var		string	The version number of the module.
	 * @since   1.2.4
	 */
	protected $release = '';

	/**
	 * @var		string	The table the parameters are stored in.
	 * @since   1.2.4
	 */
	protected $paramTable = '#__modules';

	/**
	 * @var		string	The extension name.
	 * @since   1.2.4
	 */
	protected $extension = 'mod_shoutbox';

	/**
	 * Function called before module installation/update/removal procedure commences
	 *
	 * @param   string                   $type    The type of change (install, update or discover_install,
	 *                                            not uninstall)
	 * @param   JInstallerAdapterModule  $parent  The class calling this method
	 *
	 * @return  boolean  true on success and false on failure
	 *
	 * @since  1.1.2
	 */
	public function preflight($type, $parent)
	{
		// Module manifest file version
		$this->release = $parent->get("manifest")->version;

		// Abort if the module being installed is not newer than the currently installed version
		if ($type == 'Update')
		{
			$manifest = $this->getItemArray('manifest_cache', '#__extensions', 'element', JFactory::getDbo()->quote($this->extension));
			$oldRelease = $manifest['version'];

			if (version_compare($this->release, $oldRelease, '<'))
			{
				if (!JError::$legacy)
				{
					JFactory::getApplication()->enqueueMessage(JText::sprintf('MOD_SHOUTBOX_INCORRECT_SEQUENCE', $oldRelease, $this->release), 'error');
				}
				else
				{
					JError::raiseWarning(null, JText::sprintf('MOD_SHOUTBOX_INCORRECT_SEQUENCE', $oldRelease, $this->release));
				}

				return false;
			}

			if (version_compare($oldRelease, $this->release, '<'))
			{
				// Update db schema to reflect new change in version 1.1.4
				if (version_compare($oldRelease, '1.1.3', '<='))
				{
					$this->update114();
				}

				/**
				 * For extensions going from < version 1.2.4 rename colour form field values and move assets
				 * folder to media folder
				 */
				if (version_compare($oldRelease, '1.2.3', '<='))
				{
					$this->update124();
				}
			}
		}

		return true;
	}

	/**
	 * Function called on install of module
	 *
	 * @param   JInstallerAdapterModule  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since  1.1.2
	 */
	public function install($parent)
	{
		echo '<p>' . JText::_('MOD_SHOUTBOX_INSTALL') . '</p>';
	}

	/**
	 * Function called on update of module
	 *
	 * @param   JInstallerAdapterModule  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since 1.1.2
	 */
	public function update($parent)
	{
		echo '<p>' . JText::sprintf('MOD_SHOUTBOX_UPDATE', $this->release) . '</p>';
		echo '<p>' . JText::_('MOD_SHOUTBOX_UPDATE_CHANGELOG') . '</p>';
	}

	/**
	 * Gets each instance of a module in the #__modules table
	 * For all other extensions see alternate query
	 *
	 * @param   boolean  $isModule  True if the extension is a module as this can have multiple instances
	 *
	 * @return  array  An array of ID's of the extension
	 *
	 * @since  1.2.4
	 * @see getExtensionInstance
	 */
	protected function getInstances($isModule)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Select the item(s) and retrieve the id
		$query->select($db->quoteName('id'));

		if ($isModule)
		{
			$query->from($db->quoteName('#__modules'))
				->where('module = ' . $db->Quote($this->extension));
		}
		else
		{
			$query->from($db->quoteName('#__extensions'))
				->where('element = ' . $db->Quote($this->extension));
		}

		// Set the query and obtain an array of id's
		$db->setQuery($query);
		$items = $db->loadColumn();

		return $items;
	}

	/**
	 * Gets parameter value in the extensions row of the extension table
	 *
	 * @param   string   $name  The name of the parameter to be retrieved
	 * @param   integer  $id    The id of the item in the Param Table
	 *
	 * @return  string  The parameter desired
	 *
	 * @since 1.2.4
	 */
	protected function getParam($name, $id = 0)
	{
		if (!is_int($id) || $id == 0)
		{
			// Return false if there is no item given
			return false;
		}

		$params = $this->getItemArray('params', $this->paramTable, 'id', $id);

		return $params[$name];
	}

	/**
	 * Sets parameter values in the extensions row of the extension table. Note that the
	 * this must be called separately for deleting and editing. Note if edit is called as a
	 * type then if the param doesn't exist it will be created
	 *
	 * @param   array    $param_array  The array of parameters to be added/edited/removed
	 * @param   string   $type         The type of change to be made to the param (edit/remove)
	 * @param   integer  $id           The id of the item in the relevant table
	 *
	 * @return  mixed  false on failure, void otherwise
	 */
	protected function setParams($param_array = null, $type = 'edit', $id = 0)
	{
		if (!is_int($id) || $id == 0)
		{
			// Return false if there is no valid item given
			return false;
		}

		$params = $this->getItemArray('params', $this->paramTable, 'id', $id);

		if ($param_array)
		{
			foreach ($param_array as $name => $value)
			{
				if ($type == 'edit')
				{
					// Add or edit the new variable(s) to the existing params
					$params[(string) $name] = (string) $value;
				}
				elseif ($type == 'remove')
				{
					// Unset the parameter from the array
					unset($params[(string) $name]);
				}
			}
		}

		// Store the combined new and existing values back as a JSON string
		$paramsString = json_encode($params);

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->update($db->quoteName($this->paramTable))
			->set('params = ' . $db->quote($paramsString))
			->where('id = ' . $id);

		// Update table
		$db->setQuery($query);

		if (version_compare(JVERSION, '3.0.0', 'ge'))
		{
			$db->execute();
		}
		else
		{
			$db->query();
		}

		return true;
	}

	/**
	 * Builds a standard select query to produce better DRY code in this script.
	 * This should produce a single unique cell which is json encoded
	 *
	 * @param   string  $element     The element to get from the query
	 * @param   string  $table       The table to search for the data in
	 * @param   string  $column      The column of the database to search from
	 * @param   mixed   $identifier  The integer id or the already quoted string
	 *
	 * @return  array  associated array containing data from the cell
	 *
	 * @since 1.2.4
	 */
	protected function getItemArray($element, $table, $column, $identifier)
	{
		// Get the DB and query objects
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Build the query
		$query->select($db->quoteName($element))
			->from($db->quoteName($table))
			->where($db->quoteName($column) . ' = ' . $identifier);
		$db->setQuery($query);

		// Load the single cell and json_decode data
		$array = json_decode($db->loadResult(), true);

		return $array;
	}

	/**
	 * Function to update the db schema for the Shoutbox Version 1.1.4 Updates
	 *
	 * @return  void
	 *
	 * @since  1.2.4
	 */
	protected function update114()
	{
		/*
		 * Using SQL Statement here as JJ Shoutbox didn't support non-mysql databases at this point
		 * and Joomla doesn't support changing the db schema
		 */
		$db = JFactory::getDbo();
		$sql = "ALTER TABLE #__shoutbox ADD COLUMN user_id int(11) NOT NULL DEFAULT '0'";
		$db->setQuery($sql);
		$db->query();
	}

	/**
	 * Function to update the file structure and params for the Shoutbox Version 1.2.4 updates
	 *
	 * @return  void
	 *
	 * @since  1.2.4
	 */
	protected function update124()
	{
		// Move the assets
		if (JFolder::create('media/mod_shoutbox')
			&& JFolder::move(JUri::root() . 'modules/mod_shoutbox/assets/css', JUri::root() . 'media/mod_shoutbox')
			&& JFolder::move(JUri::root() . 'modules/mod_shoutbox/assets/images', JUri::root() . 'media/mod_shoutbox')
			&& JFolder::move(JUri::root() . 'modules/mod_shoutbox/assets/js', JUri::root() . 'media/mod_shoutbox')
			&& JFolder::move(JUri::root() . 'modules/mod_shoutbox/assets/recaptcha', JUri::root() . 'media/mod_shoutbox')
			&& JFile::move(JUri::root() . 'modules/mod_shoutbox/assets/index.html', JUri::root() . 'media/mod_shoutbox/index.html'))
		{
			JFolder::delete(JPATH_ROOT . '/modules/mod_shoutbox/assets');
		}

		/*
		 * We have moved to use the colour form field so a hash must be applied
		 * to the parameters for them to function as expected still.
		 */
		$modules = $this->getInstances(true);

		foreach ($modules as $module)
		{
			// Convert string to integer
			$module = (int) $module;

			// Create array of params to change
			$colours = array();
			$colours['deletecolor'] = '#' . $this->getParam('deletecolor', $module);
			$colours['headercolor'] = '#' . $this->getParam('headercolor', $module);
			$colours['bordercolor'] = '#' . $this->getParam('bordercolor', $module);

			// Set the param values
			$this->setParams($colours, 'edit', $module);

			// Unset the array for the next loop
			unset($colours);
		}
	}
}
