<?php 
/**
* @package    JJ_Shoutbox
* @copyright  Copyright (C) 2011 - 2013 JoomJunk. All rights reserved.
* @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');

require_once dirname(__FILE__) . '/helper.php';

$title = 'shoutbox';
$params = ModShoutboxHelper::getParams($title);

$displayName = $params->get('loginname');
$smile = $params->get('smile');
$swearcounter = $params->get('swearingcounter');
$swearnumber = $params->get('swearingnumber');
$number = $params->get('maximum');
$guestpost = $params->get('guestpost');
$submittext = $params->get('submittext');
$nonmembers = $params->get('nonmembers');
$profile = $params->get('profile');
$date = $params->get('date');
$securityQuestion = $params->get('securityquestion');
$mass_delete = $params->get('mass_delete');
$recaptcha = $params->get('recaptchaon', 1);
$enterclick = $params->get('enterclick');
$genericname = $params->get('genericname', 'Anonymous');

// Add in jQuery for AJAX and smilies
$document = JFactory::getDocument();

if (version_compare(JVERSION, '3.0.0', 'ge'))
{
	JHtml::_('jquery.framework');
}
else
{
	if (!JFactory::getApplication()->get('jquery'))
	{
		JFactory::getApplication()->set('jquery', true);
		if($params->get('jquery', '0') == 0)
		{
			$document->addScript("//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js");
		}
		else
		{
			JHtml::_('script', JUri::root() . 'media/mod_shoutbox/js/jquery.js');
		}
		JHtml::_('script', JUri::root() . 'media/mod_shoutbox/js/jquery-conflict.js');
	}
}

// Set Date Format for when posted
if ($date == 0)
{
	$show_date = "d/m/Y - ";
}
elseif ($date == 1)
{
	$show_date = "D m Y - ";
}
elseif ($date == 3)
{
	$show_date = "m/d/Y - ";
}
elseif ($date == 4)
{
	$show_date = "D j M - ";
}
else
{
	$show_date = "";
}

$dataerror = JText::_('SHOUT_DATABASEERRORSHOUT');

// Import JLog class
jimport('joomla.log.log');

// Log mod_shoutbox errors to specific file.
JLog::addLogger(
	array(
		'text_file' => 'mod_shoutbox.errors.php'
	),
	JLog::ALL,
	'mod_shoutbox'
);

$user = JFactory::getUser();
require_once JPATH_ROOT . '/media/mod_shoutbox/recaptcha/recaptchalib.php';

if (isset($_POST))
{
	ModShoutboxHelper::submitAJAX($title);

	if ($mass_delete == 0)
	{
		if (isset($post['deleteall']))
		{
			$delete = $post['valueall'];

			if (isset($delete))
			{
				if (is_numeric($delete) && (int) $delete == $delete)
				{
					if ($delete > 0)
					{
						if ($delete > $post['max'])
						{
							$delete = $post['max'];
						}

						modShoutboxHelper::deleteall($delete);
					}
					else
					{
						JLog::add(JText::_('SHOUT_GREATER_THAN_ZERO'), JLog::WARNING, 'mod_shoutbox');
						JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_GREATER_THAN_ZERO'), 'error');
						return false;
					}
				}
				else
				{
					JLog::add(JText::_('SHOUT_NOT_INT'), JLog::WARNING, 'mod_shoutbox');
					JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_NOT_INT'), 'error');
					return false;
				}
			}
		}
	}
}

require JModuleHelper::getLayoutPath('mod_shoutbox');
