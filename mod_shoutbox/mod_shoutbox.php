<?php 
/**
* @package    JJ_Shoutbox
* @copyright  Copyright (C) 2011 - 2014 JoomJunk. All rights reserved.
* @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');

require_once dirname(__FILE__) . '/helper.php';

$displayName = $params->get('loginname');
$smile = $params->get('smile');
$swearcounter = $params->get('swearingcounter');
$swearnumber = $params->get('swearingnumber');
$number = $params->get('maximum');
$submittext = $params->get('submittext');
$nonmembers = $params->get('nonmembers');
$profile = $params->get('profile');
$date = $params->get('date');
$securityquestion = $params->get('securityquestion');
$mass_delete = $params->get('mass_delete');
$permissions = $params->get('guestpost');

// Add in jQuery if smilies are required
$document = JFactory::getDocument();

if ($smile == 1 || $smile == 2)
{
	if (version_compare(JVERSION, '3.0.0', 'ge'))
	{
		JHtml::_('jquery.framework');
	}
	else
	{
		if (!JFactory::getApplication()->get('jquery'))
		{
			JFactory::getApplication()->set('jquery', true);
			JHtml::_('script', 'http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js');
			JHtml::_('script', 'mod_shoutbox/jquery-conflict.js', false, true);
		}
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
elseif ($date == 5)
{
	$show_date = "Y/m/d - ";
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

if (isset($_POST))
{
	if (!get_magic_quotes_gpc())
	{
		$input = new JInput;
		$post = $input->getArray($_POST);
	}
	else
	{
		$post = JRequest::get('post');
	}

	if (isset($post['shout']) && !empty($post['message']) && $_SESSION['token'] == $post['token'])
	{
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

		if ($params->get('recaptchaon') == 0)
		{
			require_once JPATH_ROOT . '/media/mod_shoutbox/recaptcha/recaptchalib.php';

			if (isset($post["recaptcha_response_field"]))
			{
				if ($post["recaptcha_response_field"])
				{
					$resp = recaptcha_check_answer(
						$params->get('recaptcha-private'),
						$_SERVER["REMOTE_ADDR"],
						$post["recaptcha_challenge_field"],
						$post["recaptcha_response_field"]
					);

					if ($resp->is_valid)
					{
						ModShoutboxHelper::postFiltering($post, $user, $swearcounter, $swearnumber, $displayName);
					}
					else
					{
						$error = $resp->error;
					}
				}
			}
		}
		elseif ($securityquestion == 0)
		{
			if (isset($post['sum1']) && isset($post['sum2']))
			{
				$que_result = $post['sum1'] + $post['sum2'];

				if (isset($post['human']))
				{
					if ($post['human'] == $que_result)
					{
						ModShoutboxHelper::postFiltering($post, $user, $swearcounter, $swearnumber, $displayName);
					}
					else
					{
						JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_ANSWER_INCORRECT'), 'error');
					}
				}
			}
		}
		else
		{
			ModShoutboxHelper::postFiltering($post, $user, $swearcounter, $swearnumber, $displayName);
		}
	}

	if (isset($post['delete']))
	{
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		$deletepostnumber = $post['idvalue'];

		if ($user->authorise('core.delete'))
		{
			ModShoutboxHelper::deletepost($deletepostnumber);
		}
	}

	if ($mass_delete == 0 && (isset($post['deleteall'])))
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
						ModShoutboxHelper::deleteall($delete);
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
