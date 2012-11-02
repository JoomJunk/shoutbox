<?php 
/**
* @version   $Id:helper.php 2012-01-16 21:00:00
* @package   JJ Shoutbox
* @copyright Copyright (C) 2011 - 2012 JoomJunk. All rights reserved.
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
	function getShouts($number, $timezone, $message) {
		$shouts	= array();
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*')
		->from('#__shoutbox')
		->order('id DESC');
		$db->setQuery($query , 0 , $number);
		$rows = $db->loadObjectList();
		$i=0;
		$timezone=$timezone*60*60;
		if ($db->getErrorNum()) {
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->select('*')
			->from('#__shoutbox')
			->order('id DESC');
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

		if(isset($_POST['shout'])) { 
			if(!empty($_POST['message'])){
				if($_SESSION['token'] == $_POST['token']){
					$replace = '****';
					$backslashreplace='\\\\';
					
					$config = JFactory::getConfig()->get('dbtype');
					if($config=='mysqli') {
						$mysqli = new mysqli(JFactory::getConfig()->get('host'), JFactory::getConfig()->get('user'), JFactory::getConfig()->get('password'));
					}

					if (!$user->guest && $displayname==0) {
						$name = $user->name;
						$nameswears=0;
					}
					else if (!$user->guest && $displayname==1) {
						$name = $user->username;
						$nameswears=0;
					}
					else {
						$_POST['name'] = modShoutboxHelper::backslashfix($_POST['name'], $backslashreplace);
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
					$ip=$_SERVER['REMOTE_ADDR'];
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
	
	function stri_replace( $find, $replace, $string ) { 
		$parts = explode( strtolower($find), strtolower($string) ); 
		$pos = 0;
		foreach( $parts as $key=>$part ) { 
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
		$myfile = 'modules/mod_shoutbox/swearWords.php';
		$words = array();
		 if (!file_exists($myfile)){
            return $post;
        }
		$words = file($myfile, FILE_IGNORE_NEW_LINES);
		$i=0;
		while($i<10) {
			unset($words[$i]);
			$i++;
		}
		$swearwords = array_values($words);
		foreach ($swearwords as $key=>$word ) 
		{ 
			$post = modShoutboxHelper::stri_replace($word, $replace, $post); 
		}
		return $post; 
	}
	
	function backslashfix($post, $replace) { 
		$word = '\\';
		$post = modShoutboxHelper::stri_replace($word, $replace, $post); 
		return $post; 
	}  
	
	function addShout($name, $message, $ip, $timeadd) {
		$timenow = time() + ($timeadd*60*60);
		$timesql = date('Y-m-d H:i:s',$timenow);
		$db = JFactory::getDBO();
		$data = new stdClass();
		$data->id = null;
		$data->name = $name;
		$data->when = $timesql;
		$data->ip = $ip;
		$data->msg = $message;
		$db->insertObject( '#__shoutbox', $data, 'id' );
	}

	function deletepost($id) {
		$db	= JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->delete()
		->from('#__shoutbox')
		->where('id = '. (int) $id);
		$db->setQuery($query);
		$db->query();
	}
}
?>