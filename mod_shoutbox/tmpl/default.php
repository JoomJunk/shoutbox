<?php 
/**
* @version   $Id: default.php 2012-01-16 21:00:00
* @package   JJ Shoutbox
* @copyright Copyright (C) 2011 - 2012 George Wilson. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');
	
$document =& JFactory::getDocument();
//CSS file is inserted
$document->addStyleSheet(JURI::base() . 'modules/mod_shoutbox/assets/css/mod_shoutbox.css');

//Style parameters are inserted
$style = '#jjshoutboxoutput {
	border-color: #'. $bordercolor .';
	border-width: '. $borderwidth .'px;
	}
	#jjshoutboxoutput div h1 {
	background: #'. $headercolor .';
	}
	#jjshoutbox {
	width: '. $width .'px;
	}';
$document->addStyleDeclaration( $style );
$user =& JFactory::getUser();
if(version_compare(JVERSION,'1.6.0','ge')) {
	if($user->authorise('core.delete')) {
	$style = '#jjshoutboxoutput input[type=submit]{
	color:#' . $deletecolor . ';
	}'; 
	$document->addStyleDeclaration( $style );
} } else {
	if($user->usertype == "Super Administrator" || $user->usertype == "Administrator") {
	$style = '#jjshoutboxoutput input[type=submit]{
	color:#' . $deletecolor . ';
	}'; 
	$document->addStyleDeclaration( $style );
 }
}

if($params->get('recaptchaon')==0) {
	if(isset($_POST["recaptcha_response_field"])) {
		if ($_POST["recaptcha_response_field"]) {
			$resp = recaptcha_check_answer ($params->get('recaptcha-private'),
											$_SERVER["REMOTE_ADDR"],
											$_POST["recaptcha_challenge_field"],
											$_POST["recaptcha_response_field"]);

			if ($resp->is_valid) {
			modShoutboxHelper::postfiltering($_POST, $user, $swearcounter, $swearnumber, $extraadd, $displayname);
			} else {
					# set the error code so that we can display it
					$error = $resp->error;
			}
		}
	}
} else {
	modShoutboxHelper::postfiltering($_POST, $user, $swearcounter, $swearnumber, $extraadd, $displayname);
}

//finds the id of the post required to be deleted and runs the function to delete it
if(isset($_POST['delete'])) { 
    $deletepostnumber=$_POST['idvalue'];
    modShoutboxHelper::deletepost($deletepostnumber);
}
?>

<div id="jjshoutbox">
<div id="jjshoutboxoutput">
<?php
//retrieves the user array and the shouts from the function
$shouts	= array();
$shouts= modShoutboxHelper::getShouts($number, $houradd, $dataerror);
$i=0;
//counts the number of posts in the database
$actualnumber = count($shouts);
//if there are no shouts, this is printed
if($actualnumber==0) { ?>
  <div><p><?php echo JText::_('SHOUT_EMPTY') ?></p></div>
<?php }
else {
//if there are less shouts than the user requests then the number printed is set to the number of shouts that exist
if($actualnumber<$number) {
$number=$actualnumber;
}
    function shouttitle($user, $shouts, $i) {
		$title=null;
		if(version_compare(JVERSION,'1.6.0','ge')) {
			if($user->authorise('core.delete')) {
				$title='title="'. $shouts[$i]->ip .'"';
		} } else {
		if($user->usertype == "Super Administrator" || $user->usertype == "Administrator") {
			$title='title="'. $shouts[$i]->ip .'"';
		} }
	return $title;
  }
//Prints out the shouts. If a admin then the ip address of the user and the delete buttons are showed
while ($i < $number) { ?>
  <div>
  <h1 <?php echo shouttitle($user, $shouts, $i); ?>>
  <?php if ($smile==0){ print modShoutboxHelper::smileyfilter(stripslashes($shouts[$i]->name));} else {print stripslashes($shouts[$i]->name);} ?> - <?php print date("H:i",strtotime($shouts[$i]->when));
	if(version_compare(JVERSION,'1.6.0','ge')) {
		if($user->authorise('core.delete')) { ?> 
			<form method="post" name="delete">
			<input name="delete" type="submit" value="x" />
			<input name="idvalue" type="hidden" value="<?php print $shouts[$i]->id ?>" />
			</form> <?php
	} } else {
		if($user->usertype == "Super Administrator" || $user->usertype == "Administrator") { ?>
			<form method="post" name="delete">
			<input name="delete" type="submit" value="x" />
			<input name="idvalue" type="hidden" value="<?php print $shouts[$i]->id ?>" />
			</form> <?php
	} }	?>
   </h1>
   <p><?php if ($smile==0){ print modShoutboxHelper::smileyfilter(stripslashes($shouts[$i]->msg));} else {print stripslashes($shouts[$i]->msg);} ?></p></div>
  <?php $i++; ?>
  <br />
  <?php
}
}
?>
</div>
<!-- Prints out the entry point for new shouts -->
<div id="jjshoutboxform">
<?php
  if(($actualnumber>0) && ($shouts[0]->msg==$dataerror) && ($shouts[0]->ip=='System')) { 
  echo JText::_('SHOUT_DATABASEERROR');
  }
  else if (($user->guest && $guestpost==0)||!$user->guest) { ?>
<form method="post" name="shout">
<?php
$_SESSION['token'] = uniqid("token",true);
//prints the user's name or real name depending on parameter if they are a registered member
if($displayname==0 && !$user->guest)
{
  echo JText::_( 'SHOUT_NAME' );
  echo ": ";
  echo $user->name;
}
  else if($displayname==1 && !$user->guest)
{
  echo JText::_( 'SHOUT_NAME' );
  echo ": ";
  echo $user->username;
}
//if the user is a guest or the parameter is selected, the user, to input their name
else if(($guestpost==0 && $user->guest)||($displayname==2 && !$user->guest)) { ?>
  <input name="name" type="text" value="Name" maxlength="25" id="inputarea" onfocus="this.value = (this.value=='Name')? '' : this.value;" />
  <?php }
//allows the user to input their message and assigns a token to make sure posts aren't repeated upon pressing f5
if (($user->guest && $guestpost==0)||!$user->guest) { ?>
  <br />
  <input name="token" type="hidden" value="<?php echo $_SESSION['token'];?>" />
  <span id="charsleft"></span>
  <textarea id="message"  cols="20" rows="5" name="message" onKeyDown="textCounter('message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);" onKeyUp="textCounter('message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);"></textarea>

	<script type="text/javascript">
    function textCounter(textarea, countdown, maxlimit) {
        textareaid = document.getElementById(textarea);
        if (textareaid.value.length > maxlimit)
          textareaid.value = textareaid.value.substring(0, maxlimit);
        else
		  document.getElementById('charsleft').innerHTML = (maxlimit-textareaid.value.length)+' <?php echo JText::_('SHOUT_REMAINING') ?>';
		  
		if (maxlimit-textareaid.value.length > <?php echo $params->get('alertlength', '50'); ?>)
		  document.getElementById('charsleft').style.color = "Black";	
		if (maxlimit-textareaid.value.length <= <?php echo $params->get('alertlength', '50'); ?> && maxlimit-textareaid.value.length > <?php echo $params->get('warnlength', '10'); ?>)
		  document.getElementById('charsleft').style.color = "Orange";
		if (maxlimit-textareaid.value.length <= <?php echo $params->get('warnlength', '10'); ?>)
		  document.getElementById('charsleft').style.color = "Red";
		  
    }
	</script>
	<script type="text/javascript">
		textCounter('message','messagecount',<?php echo $params->get('messagelength', '200'); ?>);
	</script>
	
	<?php
	if($params->get('recaptchaon')==0) {
		if($params->get('recaptcha-public')=='' || $params->get('recaptcha-private')=='') {
			echo JText::_('SHOUT_RECAPTCHA_KEY_ERROR');
		} else {
			// Get a key from https://www.google.com/recaptcha/admin/create
			$publickey = $params->get('recaptcha-public');

			# the response from reCAPTCHA
			if(!isset($resp)) {
				$resp = null;
			}
			# the error code from reCAPTCHA, if any
			if(!isset($error)) {
				$error = null;
			}

			# was there a reCAPTCHA response?
			echo recaptcha_get_html($publickey, $error);
		}
	}
	?>

  <input name="shout" type="submit" value="<?php print $submittext ?>" <?php if ($params->get('recaptchaon')==0 && !$params->get('recaptcha-public') || $params->get('recaptchaon')==0 && !$params->get('recaptcha-private')) { echo 'disabled="disabled"'; }?> />   
  <?php } ?>
</form> 
<?php }
else if($guestpost==1 && $guestpost==1) { ?>
<!-- Prints the parameter message if guests are not allowed to post -->
<p id="noguest"><?php echo $nonmembers ?></p>
<?php } ?>
</div> 
</div>