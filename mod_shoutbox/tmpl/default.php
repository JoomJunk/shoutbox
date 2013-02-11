<?php 
/**
* @version   $Id: default.php 2012-01-16 21:00:00
* @package   JJ Shoutbox
* @copyright Copyright (C) 2011 - 2013 JoomJunk. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');
	
$document = JFactory::getDocument();
$document->addStyleSheet(JURI::base() . 'modules/mod_shoutbox/assets/css/mod_shoutbox.css');
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
$user = JFactory::getUser();
if($user->authorise('core.delete')) {
	$style = '#jjshoutboxoutput input[type=submit]{
	color:#' . $deletecolor . ';
	}'; 
	$document->addStyleDeclaration( $style );
}
?>

<div id="jjshoutbox">
<div id="jjshoutboxoutput">
<?php
$shouts	= array();
$shouts= modShoutboxHelper::getShouts($number, $houradd, $dataerror);
$i=0;
$actualnumber = count($shouts);
if($actualnumber==0) { ?>
  <div><p><?php echo JText::_('SHOUT_EMPTY') ?></p></div>
<?php }
else {
if($actualnumber<$number) {
$number=$actualnumber;
}
while ($i < $number) { ?>
  <div>
  <?php $profile_link = modShoutboxHelper::linkUser($profile, $displayname, $shouts[$i]->name, $shouts[$i]->user_id); ?>
  <h1 <?php echo modShoutboxHelper::shouttitle($user, $shouts, $i); ?>>
  <?php
  if($date==0){ $show_date = "d/m/Y -"; }
  elseif($date==1){ $show_date = "D m Y -"; }
  else{$show_date = "";}
  if ($smile==0){ echo modShoutboxHelper::smileyfilter(stripslashes($profile_link));} else {echo stripslashes($profile_link);} ?> - <?php echo date($show_date . "H:i",strtotime($shouts[$i]->when));
	if($user->authorise('core.delete')) { ?> 
		<form method="post" name="delete">
			<input name="delete" type="submit" value="x" />
			<input name="idvalue" type="hidden" value="<?php echo $shouts[$i]->id ?>" />
		</form> 
	<?php } ?>
   </h1>
   <p><?php if ($smile==0){ echo modShoutboxHelper::smileyfilter(stripcslashes($shouts[$i]->msg));} else {echo stripcslashes($shouts[$i]->msg);} ?></p></div>
  <?php $i++; ?>
  <br />
  <?php
}
}
?>
</div>
<div id="jjshoutboxform">
	<?php
	  if(($actualnumber>0) && ($shouts[0]->msg==$dataerror) && ($shouts[0]->ip=='System')) { 
	  echo JText::_('SHOUT_DATABASEERROR');
	  }
	  else if (($user->guest && $guestpost==0)||!$user->guest) { ?>
	<form method="post" name="shout">
	<?php
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
	else if(($guestpost==0 && $user->guest)||($displayname==2 && !$user->guest)) { ?>
	  <input name="name" type="text" value="Name" maxlength="25" id="shoutbox-name" onfocus="this.value = (this.value=='Name')? '' : this.value;" />
	  <?php }
	if (($user->guest && $guestpost==0)||!$user->guest) { ?>
	  <br />
	  <?php
		$_SESSION['token'] = uniqid("token",true);
		echo JHTML::_( 'form.token' );
	  ?>
	  <input name="token" type="hidden" value="<?php echo $_SESSION['token'];?>" />
	  <span id="charsleft"></span>
	  <noscript><span id="noscript_charsleft"><?php echo JText::_('SHOUT_NOSCRIPT_THERE_IS_A') . $params->get('messagelength', '200') . JText::_('SHOUT_NOSCRIPT_CHARS_LIMIT'); ?></span></noscript>
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

				$publickey = $params->get('recaptcha-public');

				if(!isset($resp)) {
					$resp = null;
				}

				if(!isset($error)) {
					$error = null;
				}

				echo recaptcha_get_html($publickey, $error);
			}
		}
		if($securityquestion==0){
			$que_number1 = modShoutboxHelper::randomnumber(1);
			$que_number2 = modShoutboxHelper::randomnumber(1); ?>
			<label class="jj_label"><?php echo $que_number1; ?> + <?php echo $que_number2; ?> = ?</label>
			<input type="hidden" name="sum1" value="<?php echo $que_number1; ?>" />
			<input type="hidden" name="sum2" value="<?php echo $que_number2; ?>" />
			<input class="jj_input" type="text" name="human" />
		<?php
		}
		if($params->get('recaptchaon')==0 && $securityquestion==0){
			JLog::add(JText::_('SHOUT_BOTH_SECURITY_ENABLED'), JLog::CRITICAL, 'mod_shoutbox');
			JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_BOTH_SECURITY_ENABLED'), 'error');
		}
		?>

	  <input name="shout" id="shoutbox-submit" type="submit" value="<?php echo $submittext ?>" <?php if ($params->get('recaptchaon')==0 && !$params->get('recaptcha-public') || $params->get('recaptchaon')==0 && !$params->get('recaptcha-private')) { echo 'disabled="disabled"'; }?> />   
	  <?php } ?>
	</form> 
	<?php
	if($user->authorise('core.delete')) { 
		if ($mass_delete == 0){ ?> 
	<form method="post" name="deleteall">
		<input class="jj_admin_label" type="number" name="valueall" min="1" max="<?php echo $number; ?>" step="1" value="0" />
		<input type="hidden" name="max" value="<?php echo $number; ?>" />
		<input class="jj_admin_button" name="deleteall" type="submit" value="<?php echo JText::_('SHOUT_MASS_DELETE') ?>" />
	</form> 
	<?php } } }
	else if($guestpost==1 && $guestpost==1) { ?>
	<p id="noguest"><?php echo $nonmembers ?></p>
	<?php } ?>
</div>
</div>
