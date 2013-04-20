<?php 
/**
* @version   $Id: shoutbox.php 2012-01-16 21:00:00
* @package   JJ Shoutbox
* @copyright Copyright (C) 2011 - 2013 JoomJunk. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');

require_once( dirname(__FILE__).'/helper.php' );

$displayname = $params->get('loginname');
$smile = $params->get('smile');
$swearcounter = $params->get('swearingcounter');
$swearnumber = $params->get('swearingnumber');
$number = $params->get('maximum');
$guestpost = $params->get('guestpost');
$submittext = $params->get('submittext');
$nonmembers = $params->get('nonmembers');
$profile = $params->get('profile');
$date = $params->get('date');
$securityquestion = $params->get('securityquestion');
$recaptcha = $params->get('recaptchaon', 1);
$mass_delete = $params->get('mass_delete');

// Add in jQuery if smilies are required
$document = JFactory::getDocument();
if($smile == 1 || $smile == 2){
	if(version_compare(JVERSION,'3.0.0','ge')) {
		JHtml::_('jquery.framework');
	}
	else
	{
		/**
		if(!JFactory::getApplication()->get('jquery')){
			JFactory::getApplication()->set('jquery',true);
			$document->addScript("http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js");
			JHtml::_('script', JUri::root() . 'modules/mod_shoutbox/assets/js/jquery-conflict.js');
		}
		**/
	}
}

// Set Date Format for when posted
if($date==0){
	$show_date = "d/m/Y - ";
}
elseif($date==1)
{
	$show_date = "D m Y - ";
}
elseif($date==3)
{
	$show_date = "m/d/Y - ";
}
elseif($date==4)
{
	$show_date = "D j M - ";
}
else
{
	$show_date = "";
}

$dataerror= JText::_('SHOUT_DATABASEERRORSHOUT');

//Import JLog class
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
require_once( dirname(__FILE__).'/assets/recaptcha/recaptchalib.php');

if (!get_magic_quotes_gpc()){
	$input = new JInput();
	$task = $input->get('task', null, 'cmd');
} else {
	$task = JRequest::getVar('task');
}

if(isset($_POST) || $task == "submitShout") {
	if($task == "submitShout"){
		$post = array();
		if (!get_magic_quotes_gpc()){
			$input = new JInput();
			$post["name"] = $input->get('name', null, 'STRING');
			$post["message"] = $input->get('message', null, 'STRING');
			$post["token"] = $input->get('token', null, 'STRING');
			
			if($securityquestion==0)
			{
				$post['sum1'] = $input->get('sum1', null, 'INT');
				$post['sum2'] = $input->get('sum2', null, 'INT');
				$post['human'] = $input->get('human', null, 'INT');
			}
			if($recaptcha==0)
			{
				$post['recaptcha_challenge_field'] = $input->get('recaptcha_challenge_field', null, 'STRING');
				$post['recaptcha_response_field'] = $input->get('recaptcha_response_field', null, 'STRING');
			}
		} else {
			$post["name"] = JRequest::getVar('name', null, 'get', 'string');
			$post["message"] = JRequest::getVar('message', null, 'get', 'string');
			$post["token"] = JRequest::getVar('token', null, 'get', 'string');
			
			if($securityquestion==0)
			{
				$post['sum1']=JRequest::getVar('sum1', null, 'get', 'int');
				$post['sum2']=JRequest::getVar('sum2', null, 'get', 'int');
				$post['human']=JRequest::getVar('human', null, 'get', 'int');
			}
			if($recaptcha==0)
			{
				$post['recaptcha_challenge_field'] = JRequest::getVar('recaptcha_challenge_field', null, 'get', 'string');
				$post['recaptcha_response_field'] = JRequest::getVar('recaptcha_response_field', null, 'get', 'string');
			}
		}
		$post['shout'] = "Shout!";
		$post["method"] = 'get';
	}
	else
	{
		if (!get_magic_quotes_gpc()){
			$input = new JInput();
			$post = $input->getArray($_POST);
		} else {
			$post = JRequest::get( 'post' );
		}
		$post["method"] = 'post';
	}

	if($recaptcha==0) {
		if(isset($post["recaptcha_response_field"])) {
			if ($post["recaptcha_response_field"]) {
				$resp = recaptcha_check_answer ($params->get('recaptcha-private'),
												$_SERVER["REMOTE_ADDR"],
												$post["recaptcha_challenge_field"],
												$post["recaptcha_response_field"]);

				if ($resp->is_valid) {
					modShoutboxHelper::postfiltering($post, $user, $swearcounter, $swearnumber, $displayname);
				} else {
					$error = $resp->error;
				}
			}
		}
	} 
	elseif($securityquestion==0) {
		if(isset($post['sum1']) && isset($post['sum2'])){
			$que_result = $post['sum1'] + $post['sum2'];
			if(isset($post['human'])){
				if($post['human']==$que_result) {
					modShoutboxHelper::postfiltering($post, $user, $swearcounter, $swearnumber, $displayname);
				}
				else{
					JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_ANSWER_INCORRECT'), 'error');
				}
			}			
		}
	}
	else {
		modShoutboxHelper::postfiltering($post, $user, $swearcounter, $swearnumber, $displayname);
	}
	if(isset($post['delete'])) {
		$deletepostnumber=$post['idvalue'];
		modShoutboxHelper::deletepost($deletepostnumber);
	}
	if($mass_delete==0)
	{
		if(isset($post['deleteall'])) {
			$delete=$post['valueall'];
			if(isset($delete)){
				if(is_numeric($delete) && (int) $delete == $delete) {
					if($delete>0) {
						if($delete>$post['max']) {
							$delete=$post['max'];
						}
						modShoutboxHelper::deleteall($delete);
					} else {
						JLog::add(JText::_('SHOUT_GREATER_THAN_ZERO'), JLog::WARNING, 'mod_shoutbox');
						JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_GREATER_THAN_ZERO'), 'error');
					}
				} 
				else {
					JLog::add(JText::_('SHOUT_NOT_INT'), JLog::WARNING, 'mod_shoutbox');
					JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_NOT_INT'), 'error');
				}
			}
		}
	}
}

require(JModuleHelper::getLayoutPath('mod_shoutbox'));
?>
