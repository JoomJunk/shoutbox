<?php 
/**
* @version   $Id: shoutbox.php 2012-01-16 21:00:00
* @package   JJ Shoutbox
* @copyright Copyright (C) 2011 - 2012 George Wilson. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

// Include the syndicate functions only once
require_once( dirname(__FILE__)'/helper.php' );

//Retrieves the parameters for the module
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
$extraadd = $params->get('timeadd', '0');
$width = $params->get('width', '250');

//Defines message if there is a database error
$dataerror= JText::_('SHOUT_DATABASEERRORSHOUT');

//recapture library inserted
require_once( dirname(__FILE__)'/assets/recaptcha/recaptchalib.php');

//Include the layout file
require(JModuleHelper::getLayoutPath('mod_shoutbox'));

?>
