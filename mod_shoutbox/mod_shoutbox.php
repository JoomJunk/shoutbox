<?php 
/**
* @package    JJ_Shoutbox
* @copyright  Copyright (C) 2011 - 2015 JoomJunk. All rights reserved.
* @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');

require_once dirname(__FILE__) . '/helper.php';

$title  = $module->title;
$helper = new ModShoutboxHelper($title);
$params = $helper->getParams();

$displayName 		= $params->get('loginname', 'user');
$smile 				= $params->get('smile');
$swearcounter 		= $params->get('swearingcounter', 1);
$swearnumber 		= $params->get('swearingnumber');
$number 			= $params->get('maximum');
$submittext 		= $params->get('submittext');
$nonmembers 		= $params->get('nonmembers');
$profile 			= $params->get('profile');
$date 				= $params->get('date');
$securitytype		= $params->get('securitytype', 0);
$publicKey			= $params->get('recaptcha-public');
$privateKey			= $params->get('recaptcha-private');
$mass_delete 		= $params->get('mass_delete', 0);
$permissions 		= $params->get('guestpost');
$deletecolor		= $params->get('deletecolor', '#FF0000');
$bordercolour 		= $params->get('bordercolor', '#FF3C16');
$borderwidth 		= $params->get('borderwidth', '1');
$headercolor 		= $params->get('headercolor', '#D0D0D0');
$bbcode 			= $params->get('bbcode', 1);
$sound				= $params->get('sound', 1);
$genericName		= $params->get('genericname');
$alertLength		= $params->get('alertlength', '50');
$warnLength			= $params->get('warnlength', '10');
$messageLength		= $params->get('messagelength', '200');
$refresh			= $params->get('refresh', 10) * 1000;
$remainingLength 	= JText::_('SHOUT_REMAINING');

// Assemble the factory variables needed
$doc 	= JFactory::getDocument();
$user 	= JFactory::getUser();
$app 	= JFactory::getApplication();


// Detect a UIKit based theme
$template 	= $app->getTemplate('template')->template;
$uikit 		= JPATH_SITE . '/templates/' . $template . '/warp/vendor/uikit/js/uikit.js';
if(JFile::exists($uikit))
{
	$form 			= 'uk-form';
	$button_group 	= 'uk-button-group';
	$button 		= 'uk-button';
	$button_danger 	= ' uk-button-danger';
}
else 
{
	$form 			= null;
	$button_group 	= 'btn-group';
	$button 		= 'btn';
	$button_danger 	= ' btn-danger';
}

// Import jQuery
if (version_compare(JVERSION, '3.0.0', 'ge'))
{
	JHtml::_('jquery.framework');
}
else
{
	if (!$app->get('jquery'))
	{
		$app->set('jquery', true);
		$doc->addScript('//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js');
		JHtml::_('script', 'mod_shoutbox/jquery-conflict.js', false, true);
	}
}

JHtml::_('script', 'mod_shoutbox/mod_shoutbox.js', false, true);

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

if (isset($_POST))
{
	if (!get_magic_quotes_gpc())
	{
		$post = $app->input->post->get('jjshout', array(), 'array');
	}
	else
	{
		$post = JRequest::getVar('jjshout', array(), 'post', 'array');
	}

	if (isset($post['shout']))
	{
		$helper->submitPhp($post);
	}

	if (isset($post['delete']))
	{
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		$deletepostnumber = $post['idvalue'];

		if ($user->authorise('core.delete'))
		{
			$helper->deletepost($deletepostnumber);
		}
	}

	if ($mass_delete == 1 && (isset($post['deleteall'])))
	{
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
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
					if ($user->authorise('core.delete'))
					{
						$helper->deleteall($delete);
					}
				}
				else
				{
					JLog::add(JText::_('SHOUT_GREATER_THAN_ZERO'), JLog::WARNING, 'mod_shoutbox');
					JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_GREATER_THAN_ZERO'), 'error');
				}
			}
			else
			{
				JLog::add(JText::_('SHOUT_NOT_INT'), JLog::WARNING, 'mod_shoutbox');
				JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_NOT_INT'), 'error');
			}
		}
	}
}

require JModuleHelper::getLayoutPath('mod_shoutbox');
