<?php 
/**
* @version   $Id:helper.php 2012-01-16 21:00:00
* @package   JJ Shoutbox
* @copyright Copyright (C) 2011 - 2013 JoomJunk. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

$config = JFactory::getConfig()->get('dbtype');


/**
 * Shoutbox helper connector class.
 *
 * @package     JJ Shoutbox
 *
 */
class modShoutboxHelper {
	function getShouts($number, $timezone, $message) {
		$shouts	= array();
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*')
		->from('#__shoutbox')
		->order('id DESC');
		$db->setQuery($query , 0 , $number);
		$i=0;
		if (!JError::$legacy) {
			try {
			// Execute the query.
			$rows = $db->loadObjectList();
			} catch (Exception $e) {
				// Output error to shoutbox.
				$shouts[$i]->name = 'Administrator';
				$shouts[$i]->when = date( 'Y-m-d H:i:s', time()+$timezone);
				$shouts[$i]->msg = $message;
				$shouts[$i]->ip = 'System';
				$shouts[$i]->user_id = 0;
				// Add error to log.
				JLog::add(JText::sprintf('SHOUT_DATABASE_ERROR', $e), JLog::CRITICAL, 'mod_shoutbox');
				return $shouts;
			}
		} else {
			$rows = $db->loadObjectList();
			if ($db->getErrorNum()) {
				$shouts[$i]->name = 'Administrator';
				$shouts[$i]->when = date( 'Y-m-d H:i:s', time()+$timezone);
				$shouts[$i]->msg = $message;
				$shouts[$i]->ip = 'System';
				$shouts[$i]->user_id = 0;
				// Add error to log.
				JLog::add(JText::sprintf('SHOUT_DATABASE_ERROR', $db->getErrorMsg()), JLog::CRITICAL, 'mod_shoutbox');
				return $shouts;
			}
		}
		$timezone=$timezone*60*60;
		foreach ( $rows as $row ) {
			$shouts[$i]->id = $row->id;
			$shouts[$i]->name = $row->name;
			$adjustedtime = strtotime($row->when) + $timezone;
			$shouts[$i]->when = date( 'Y-m-d H:i:s', $adjustedtime);
			$shouts[$i]->ip = $row->ip;
			$shouts[$i]->msg = $row->msg;
			$shouts[$i]->user_id = $row->user_id;
			$i++;
		}
		return $shouts;
	}
	
	function shouttitle($user, $shouts, $i) {
		$title=null;
		if($user->authorise('core.delete')) {
			$title='title="'. $shouts[$i]->ip .'"';
		}
		return $title;
	}
	
	function postfiltering($shout, $user, $swearcounter, $swearnumber, $extraadd, $displayname) {
		if(isset($shout['shout'])) {
			JSession::checkToken() or die( JText::_( 'SHOUT_INVALID_TOKEN' ) );
			if(!empty($shout['message'])){
				if($_SESSION['token'] == $shout['token']){	
					$replace = '****';

					if (!$user->guest && $displayname==0) {
						$name = $user->name;
						$nameswears=0;
					}
					else if (!$user->guest && $displayname==1) {
						$name = $user->username;
						$nameswears=0;
					}
					else {
						if($swearcounter==0) { $before=substr_count($shout['name'], $replace); }
						$name = modShoutboxHelper::swearfilter($shout['name'], $replace);
						if($swearcounter==0) {
							$after=substr_count($name, $replace);
							$nameswears=($after-$before);
						}
						else {$nameswears=0; }
					}
					if($swearcounter==0) { $before=substr_count($shout['message'], $replace); }
					$message = modShoutboxHelper::swearfilter($shout['message'], $replace);
					if($swearcounter==0) {
						$after=substr_count($message, $replace);
						$messageswears=($after-$before);
					}
					$ip=$_SERVER['REMOTE_ADDR'];
					if($swearcounter==1 || $swearcounter==0 && (($nameswears+$messageswears)<$swearnumber)) {
						modShoutboxHelper::addShout($name, $message, $ip, $extraadd);
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
		 if (!JFile::exists($myfile)){
			JLog::add(JText::_('SHOUT_SWEAR_FILE_NOT_FOUND'), JLog::WARNING, 'mod_shoutbox');
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
	
	function linkUser($profile, $displayname, $name, $user_id) {
		if($user_id!=0) {
			if($profile == 1) {
				//Community Builder Profile Link
				$profile_link = '<a href="'.JRoute::_('index.php?option=com_comprofiler&task=userProfile&user='.$user_id).'">' . $name . '</a>';
			}	
			elseif($profile == 2) {
				//Kunena Profile Link
				$profile_link = '<a href="'.JRoute::_('index.php?option=com_kunena&func=fbprofile&userid='. $user_id).'">' . $name . '</a>'; 
			}
			elseif($profile == 3) {
				//JomSocial Profile Link
				$jspath = JPATH_ROOT.'/components/com_community/libraries/core.php';
				if(JFile::exists($jspath)){
					include_once($jspath);
					$profile_link = '<a href="'.CRoute::_('index.php?option=com_community&view=profile&userid='.$user_id).'">' . $name . '</a>';
				} else {
					JLog::add(JText::_('SHOUT_JOM_SOCIAL_NOT_INSTALLED'), JLog::WARNING, 'mod_shoutbox');
					JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_JOM_SOCIAL_NOT_INSTALLED'), 'error');
				}
			}
			elseif($profile == 4) {
				//K2 Profile Link
				$profile_link = '<a href="'.JRoute::_('index.php?option=com_k2&view=itemlist&layout=user&id='. $user_id .'&task=user').'">' . $name . '</a>'; 
			}
			else {
				//No profile Link
				$profile_link = $name;
			}
		} else {
			$profile_link = $name;
		}
		return $profile_link;
	}
	
	function addShout($name, $message, $ip, $timeadd) {
		$timenow = time() + ($timeadd*60*60);
		$timesql = date('Y-m-d H:i:s',$timenow);
		
		$db = JFactory::getDBO();     
		$query = $db->getQuery(true);
		$query->insert($db->nameQuote('#__shoutbox'));
		$query->set($db->nameQuote('name').'='.$db->quote($name).','.
		$db->nameQuote('when').'='.$db->quote($timesql).','.
		$db->nameQuote('ip').'='.$db->quote($ip).','.
		$db->nameQuote('msg').'='.$db->quote($message).','.
		$db->nameQuote('user_id').'='.$db->quote(JFactory::getUser()->id));     
		$db->setQuery( $query );
		$db->query();
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
	
	function deleteall($delete) {	
		$db = & JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*')
		  ->from('#__shoutbox')
		  ->order('id DESC');
		$db->setQuery($query , 0 , $delete);
		$rows = $db->loadObjectList();
		foreach ($rows as $row) {
			$query = $db->getQuery(true);
			$query->delete()
			  ->from('#__shoutbox')
			  ->where('id = '. (int) $row->id);			  
			$db->setQuery($query);
			$db->query();
		}
	}
	
	function randomnumber($digits) { 
		static $startseed = 0; 
		if (!$startseed) { 
			$startseed = (double)microtime()*getrandmax(); 
			srand($startseed); 
		} 
		$range = 8; 
		$start = 1; 
		$i = 1; 
		while ($i<$digits) { 
			$range = $range . 9; 
			$start = $start . 0; 
			$i++; 
		} 
		return (rand()%$range+$start); 
	}
}
?>
