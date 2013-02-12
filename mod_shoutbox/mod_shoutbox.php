<?php 
/**
* @version   $Id: shoutbox.php 2012-01-16 21:00:00
* @package   JJ Shoutbox
* @copyright Copyright (C) 2011 - 2013 JoomJunk. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

require_once( dirname(__FILE__).'/helper.php' );

$displayname = $params->get('loginname');
$smile = $params->get('smile');
$swearcounter = $params->get('swearingcounter');
$swearnumber = $params->get('swearingnumber');
$number = $params->get('maximum');
$bordercolor = $params->get('bordercolor');
$borderwidth = $params->get('borderwidth');
$guestpost = $params->get('guestpost');
$submittext = $params->get('submittext');
$nonmembers = $params->get('nonmembers');
$deletecolor = $params->get('deletecolor');
$headercolor = $params->get('headercolor');
$houradd = $params->get('timezone', '0');
$profile = $params->get('profile');
$date = $params->get('date');
$securityquestion = $params->get('securityquestion');
$mass_delete = $params->get('mass_delete');

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
if(isset($_POST)) {
	if (!get_magic_quotes_gpc()){
		$input = new JInput();
		$post = $input->getArray($_POST);
	} else {
		$post = JRequest::get( 'post' );
	}
	if($params->get('recaptchaon')==0) {
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

require(JModuleHelper::getLayoutPath('mod_shoutbox'));
?>
