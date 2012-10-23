<?php 
/**
* @version   $Id:helper.php 2012-01-16 21:00:00
* @package   JJ Shoutbox
* @copyright Copyright (C) 2011 - 2012 George Wilson. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

$config = JFactory::getConfig()->get('dbtype');

if($config=='mysqli') {
jimport( 'joomla.database.database.mysqli' );
}
else {
jimport( 'joomla.database.database.mysql' );
}

class modShoutboxHelper {
	function install() {
		$db	=& JFactory::getDBO();
		$query 	= "CREATE TABLE IF NOT EXISTS #__shoutbox (
	  		`id` int(10) unsigned NOT NULL auto_increment,
	  		`name` varchar(25) NOT NULL,
			`when` TIMESTAMP NOT NULL,
			`ip` varchar(15) NOT NULL default '',
			`msg` text NOT NULL,
	  		PRIMARY KEY  (`id`)
		) ; ";
		$db->setQuery($query);
		$db->query();
		$query = "INSERT INTO `#__shoutbox` (`name`, `when`, `msg`) VALUES
			('JoomJunk', '2012-01-16 20:00:00', 'Welcome to the Shoutbox');";
		$db->setQuery($query);
		$db->query();
	}
	
	function getShouts($number, $timezone, $message) {
		global $mainframe;
		$shouts	= array();
		$db =& JFactory::getDBO();
		$query = 'SELECT * FROM #__shoutbox ORDER BY id DESC';
		$db->setQuery($query , 0 , $number);
		$rows = $db->loadObjectList();
		$i=0;
		$timezone=$timezone*60*60;
		if ($db->getErrorNum()) {
			modShoutboxHelper::install();
			$db =& JFactory::getDBO();
			$query = 'SELECT * FROM #__shoutbox ORDER BY id DESC';
			$db->setQuery($query , 0 , $number);
			$rows = $db->loadObjectList();
			if ($db->getErrorNum()) {
				$shouts[$i]->name = 'Administrator';
				$shouts[$i]->when = date( 'Y-m-d H:i:s', time()+$timezone);
				$shouts[$i]->msg = $message;
				$shouts[$i]->ip = 'System';
				return $shouts;
			}
		}
		foreach ( $rows as $row ) {
			$shouts[$i]->id = $row->id;
			$shouts[$i]->name = $row->name;
			$adjustedtime = strtotime($row->when) + $timezone;
			$shouts[$i]->when = date( 'Y-m-d H:i:s', $adjustedtime);
			$shouts[$i]->ip = $row->ip;
			$shouts[$i]->msg = $row->msg;
			$i++;
		}
		return $shouts;
	}
	
	function postfiltering($_POST, $user, $swearcounter, $swearnumber, $extraadd, $displayname) {
		//submits the post when the button is pressed
		if(isset($_POST['shout'])) { 
			//checks to make sure the post isn't empty - will not submit the post if this is true
			if(!empty($_POST['message']))
			{
				//check session token matches so posts aren't duplicated
				if($_SESSION['token'] == $_POST['token'])
				{

					//sets the variable that the swear words are all replaced with
					$replace = '****';
					$backslashreplace='\\\\';
					
					$config = JFactory::getConfig()->get('dbtype');
					if($config=='mysqli') {
						$mysqli = new mysqli(JFactory::getConfig()->get('host'), JFactory::getConfig()->get('user'), JFactory::getConfig()->get('password'));
					}

					//sends either a logged in users real name or username to the shoutbox depending on the parameter
					if (!$user->guest && $displayname==0) {
						$name = $user->name;
						$nameswears=0;
					}
					else if (!$user->guest && $displayname==1) {
						$name = $user->username;
						$nameswears=0;
					}
					else {
						//makes sure if a backslash is in the name it isn't lost
						$_POST['name'] = modShoutboxHelper::backslashfix($_POST['name'], $backslashreplace);
						//runs the chosen name through the swear filter and removes extra slashes if magic quotes are on
						if (get_magic_quotes_gpc()) {$_POST['name']=stripslashes($_POST['name']);}
						if($swearcounter==0) { $before=substr_count($_POST['name'], $replace); }
						if($config=='mysqli') {
							$name = modShoutboxHelper::swearfilter($mysqli->real_escape_string($_POST['name']), $replace);
						}
						else {
							$name = modShoutboxHelper::swearfilter(mysql_real_escape_string($_POST['name']), $replace);
						}
						if($swearcounter==0) {
							$after=substr_count($name, $replace);
							$nameswears=($after-$before);
						}
						else {$nameswears=0; }
					}
					//runs the message through the shoutbox and smiley filter, removes the backslashes if magic quotes are on, and adds in an extra backslash so none in the message are lost
					$_POST['message'] = modShoutboxHelper::backslashfix($_POST['message'], $backslashreplace);
					if (get_magic_quotes_gpc()) {$_POST['message']=stripslashes($_POST['message']);}
					if($swearcounter==0) { $before=substr_count($_POST['message'], $replace); }
					if($config=='mysqli') {
						$message = modShoutboxHelper::swearfilter($mysqli->real_escape_string($_POST['message']), $replace);				
					}
					else {
						$message = modShoutboxHelper::swearfilter(mysql_real_escape_string($_POST['message']), $replace);
					}
					if($swearcounter==0) {
						$after=substr_count($message, $replace);
						$messageswears=($after-$before);
					}
					//logs the IP
					$ip=$_SERVER['REMOTE_ADDR'];
					//adds the post to the shoutbox
					if($swearcounter==1 || $swearcounter==0 && (($nameswears+$messageswears)<$swearnumber)) {
						modShoutboxHelper::addShout($name, $message, $ip, $extraadd);
					}
					if($config=='mysqli') {
						$mysqli->close();
					}
				}
			}
		}
	}
	
	function stri_replace( $find, $replace, $string ) 
	{ 
		$parts = explode( strtolower($find), strtolower($string) ); 
		$pos = 0;
		foreach( $parts as $key=>$part ) 
			{ 
				$parts[ $key ] = substr($string, $pos, strlen($part)); 
				$pos += strlen($part) + strlen($find); 
			} 
			return( join( $replace, $parts ) ); 
	}
	
	function smileyfilter($message) { 
		$replace = array(':)' => ' <img src="modules/mod_shoutbox/assets/images/icon_e_smile.gif" alt=":)">');
		foreach($replace as $old=>$new) $message = str_replace($old,$new,$message); 
		$replace = array(':(' => ' <img src="modules/mod_shoutbox/assets/images/icon_e_sad.gif" alt=":(">'); 
		foreach($replace as $old=>$new) $message = str_replace($old,$new,$message); 
		$replace = array(':D' => ' <img src="modules/mod_shoutbox/assets/images/icon_e_biggrin.gif" alt=":D">'); 
		foreach($replace as $old=>$new) $message = str_replace($old,$new,$message); 
		$replace = array('xD' => ' <img src="modules/mod_shoutbox/assets/images/icon_e_biggrin.gif" alt="xD">'); 
		foreach($replace as $old=>$new) $message = str_replace($old,$new,$message); 
		$replace = array(':p' => ' <img src="modules/mod_shoutbox/assets/images/icon_razz.gif" alt=":p">'); 
		foreach($replace as $old=>$new) $message = str_replace($old,$new,$message); 
		$replace = array(':P' => ' <img src="modules/mod_shoutbox/assets/images/icon_razz.gif" alt=":P">'); 
		foreach($replace as $old=>$new) $message = str_replace($old,$new,$message); 
		$replace = array(';)' => ' <img src="modules/mod_shoutbox/assets/images/icon_e_wink.gif" alt=";)">'); 
		foreach($replace as $old=>$new) $message = str_replace($old,$new,$message); 
		$replace = array(':S' => ' <img src="modules/mod_shoutbox/assets/images/icon_e_confused.gif" alt=":S">'); 
		foreach($replace as $old=>$new) $message = str_replace($old,$new,$message); 
		$replace = array(':@' => ' <img src="modules/mod_shoutbox/assets/images/icon_mad.gif" alt=":@">'); 
		foreach($replace as $old=>$new) $message = str_replace($old,$new,$message); 
		$replace = array(':O' => ' <img src="modules/mod_shoutbox/assets/images/icon_e_surprised.gif" alt=":O">'); 
		foreach($replace as $old=>$new) $message = str_replace($old,$new,$message); 
		$replace = array('lol' => ' <img src="modules/mod_shoutbox/assets/images/icon_lol.gif" alt="lol">'); 
		foreach($replace as $old=>$new) $message = str_replace($old,$new,$message); 
		return $message;
		}
					
	function swearfilter($post, $replace) { 
		global $mainframe;
		$words = array();
		$myfile = JURI::base().'modules/mod_shoutbox/swearWords.php';
		$words = file($myfile, FILE_IGNORE_NEW_LINES);
		foreach ($words as $key=>$word ) 
		{ 
			$post = modShoutboxHelper::stri_replace($word, $replace, $post); 
		}
		return $post; 
	}
	
	function backslashfix($post, $replace) { 
		global $mainframe;
		$word = '\\';
		$post = modShoutboxHelper::stri_replace($word, $replace, $post); 
		return $post; 
	}  
	
	function addShout($name, $message, $ip, $timeadd) {
		global $mainframe;
		$timenow = time() + ($timeadd*60*60);
		$timesql = date('Y-m-d H:i:s',$timenow);
		$db = JFactory::getDBO();
				$query = "INSERT INTO `#__shoutbox` (`name`, `when`,`ip`, `msg`) VALUES
			('$name', '$timesql', '$ip', '$message');";
		$db->setQuery($query);
		$db->query();
	}

	function deletepost($id) {
		global $mainframe;
		$db		=& JFactory::getDBO();
		$query = "DELETE FROM #__shoutbox WHERE `id` =". (int) $id;
		$db->setQuery($query);
		$db->query();
	}
}
?>