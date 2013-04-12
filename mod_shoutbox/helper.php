<?php 
/**
* @version   $Id:helper.php 2012-01-16 21:00:00
* @package   JJ Shoutbox
* @copyright Copyright (C) 2011 - 2013 JoomJunk. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

/**
 * Shoutbox helper connector class.
 *
 * @package     JJ Shoutbox
 *
 */
class modShoutboxHelper {
	/**
	 * Retrieves the shouts from the database and returns them. Will return an error message if the database retrieval fails.
	 *
	 * @param   int  $number  The number of posts to retrieve from the databse.
	 * @param   int  $timezone  The timezone of the user.
	 * @param   string  $message  The error message to return if the database retrieval fails.
	 *
	 * @return  array  The shoutbox posts.
	 *
	 */
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
	
	/**
	 * Adds the ip address on hover to the post title if an administrator.
	 *
	 * @param   int  $user  The user ID.
	 * @param   string  $ip  The ip address of the shout.
	 *
	 * @return  string  The title to assign.
	 *
	 */
	function shouttitle($user, $ip) {
		$title=null;
		if($user->authorise('core.delete')) {
			$title='title="'. $ip .'"';
		}
		return $title;
	}
	
	/**
	 * Filters the posts before calling the add function.
	 *
	 * @param   int  $shout  The shout post.
	 * @param   int  $user  The user id number.
	 * @param   booleon  $swearcounter  Is the swear counter is on.
	 * @param   int  $swearnumber  If the swear counter is on - how many swears are allowed.
	 * @param   int  $displayname  The user display name.
	 *
	 */
	function postfiltering($shout, $user, $swearcounter, $swearnumber, $displayname) {
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
					if($swearcounter==1 || $swearcounter==0 && (($nameswears+$messageswears)<=$swearnumber)) {
						modShoutboxHelper::addShout($name, $message, $ip);
					}
				}
			}
		}
	}
	
	/**
	 * Replaces a instance of a object in a string with another.
	 *
	 * @param   string  $find  The thing to be found in the string.
	 * @param   string  $replace  The thing to be replaced in the string.
	 * @param   string  $string  The string to be searched.
	 *
	 * return   string  join( $replace, $parts )  The string with the filtered parts.
	 *
	 */
	function stri_replace( $find, $replace, $string ) { 
		$parts = explode( strtolower($find), strtolower($string) ); 
		$pos = 0;
		foreach( $parts as $key=>$part ) { 
				$parts[ $key ] = substr($string, $pos, strlen($part)); 
				$pos += strlen($part) + strlen($find); 
			} 
			return( join( $replace, $parts ) ); 
	}
	
	/**
	 * Replaces all the smilies in the message.
	 *
	 * @param   message  $message  The message to be searched to add smilies in.
	 *
	 * return   message  $message  The message with the smiley code in.
	 *
	 */
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
	
	/**
	 * Displays an array of smilies.
	 *
	 * @param   smiley  $smiley  The smiley to be defined as an linkable image.
	 *
	 * return   smiley  $smiley  The smiley as an image.
	 *
	 */
	function smileyshow($smilies) { 
		$smilies = '';
		$smilies .= '<a href="#!" title=":)"><img class="jj_smiley" src="modules/mod_shoutbox/assets/images/icon_e_smile.gif" alt=":)"></a>'; 
		$smilies .= '<a href="#!" title=":("><img class="jj_smiley" src="modules/mod_shoutbox/assets/images/icon_e_sad.gif" alt=":("></a>';
		$smilies .= '<a href="#!" title=":D"><img class="jj_smiley" src="modules/mod_shoutbox/assets/images/icon_e_biggrin.gif" alt=":D"></a>';
		$smilies .= '<a href="#!" title="xD"><img class="jj_smiley" src="modules/mod_shoutbox/assets/images/icon_e_biggrin.gif" alt="xD"></a>';
		$smilies .= '<a href="#!" title=":P"><img class="jj_smiley" src="modules/mod_shoutbox/assets/images/icon_razz.gif" alt=":P"></a>';
		$smilies .= '<a href="#!" title=";)"><img class="jj_smiley" src="modules/mod_shoutbox/assets/images/icon_e_wink.gif" alt=";)"></a>';
		$smilies .= '<a href="#!" title=":S"><img class="jj_smiley" src="modules/mod_shoutbox/assets/images/icon_e_confused.gif" alt=":S"></a>';
		$smilies .= '<a href="#!" title=":@"><img class="jj_smiley" src="modules/mod_shoutbox/assets/images/icon_mad.gif" alt=":@"></a>';
		$smilies .= '<a href="#!" title=":O"><img class="jj_smiley" src="modules/mod_shoutbox/assets/images/icon_e_surprised.gif" alt=":O"></a>';
		$smilies .= '<a href="#!" title="lol"><img class="jj_smiley" src="modules/mod_shoutbox/assets/images/icon_lol.gif" alt="lol"></a>';
		return $smilies;
	}
	
	/**
	 * Retrieves swear words from a file and then filters them.
	 *
	 * @param   string  $post  The post to be searched.
	 * @param   string  $replace  The thing to be replace the swear words in the string.
	 *
	 * return   string  $post  The post with the filtered swear words.
	 *
	 */
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
	
	/**
	 * Links a users profile with another extension if desired.
	 *
	 * @param   int  $profile  The post to be searched.
	 * @param   string  $name  The name of the user from the database.
	 * @param   int  $user_id  The id of the user.
	 *
	 * return   string  $profile_link  The user name - with a profile link depending on parameters.
	 *
	 */
	function linkUser($profile, $name, $user_id) {
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
	
	/**
	 * Adds a shout to the database.
	 *
	 * @param   string  $name  The post to be searched.
	 * @param   string  $message  The name of the user from the database.
	 * @param   int  $user_id  The id of the user.
	 *
	 * return   string  $profile_link  The user name - with a profile link depending on parameters.
	 *
	 */
	function addShout($name, $message, $ip) {
		$db = JFactory::getDBO();     
		$query = $db->getQuery(true);
		if(version_compare(JVERSION,'3.0.0','ge')) {
			$query->insert($db->quoteName('#__shoutbox'));
			$query->set($db->quoteName('name').'='.$db->quote($name).','.
			$db->quoteName('when').'='.$db->Quote(JFactory::getDate()->toSql()).','.
			$db->quoteName('ip').'='.$db->quote($ip).','.
			$db->quoteName('msg').'='.$db->quote($message).','.
			$db->quoteName('user_id').'='.$db->quote(JFactory::getUser()->id)); 
		} else {
			$query->insert($db->nameQuote('#__shoutbox'));
			$query->set($db->nameQuote('name').'='.$db->quote($name).','.
			$db->nameQuote('when').'='.$db->Quote(JFactory::getDate()->toSql()).','.
			$db->nameQuote('ip').'='.$db->quote($ip).','.
			$db->nameQuote('msg').'='.$db->quote($message).','.
			$db->nameQuote('user_id').'='.$db->quote(JFactory::getUser()->id));
		}
		
		$db->setQuery( $query );
		
		if(version_compare(JVERSION,'3.0.0','ge')) {
			$db->execute();
		} else {
			$db->query();
		}
	}

	/**
	 * Removes a shout to the database.
	 *
	 * @param   int  $id  The id of the post to be deleted.
	 *
	 */
	function deletepost($id) {
		$db	= JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->delete()
		->from('#__shoutbox')
		->where('id = '. (int) $id);
		$db->setQuery($query);
		if(version_compare(JVERSION,'3.0.0','ge')) {
			$db->execute();
		} else {
			$db->query();
		}
	}
	
	/**
	 * Removes multiple shouts from the database.
	 *
	 * @param   int  $id  The id of the post to be deleted.
	 *
	 */
	function deleteall($delete) {	
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*')
		  ->from('#__shoutbox')
		  ->order('id DESC');
		$db->setQuery($query , 0 , $delete);
		$rows = $db->loadObjectList();
		foreach ($rows as $row) {
			modShoutboxHelper::deletepost($row->id);
		}
	}
	
	/**
	 * Creates a random number for the maths question.
	 *
	 * @param   int  $digits  The number of digits long the number shoutld be.
	 *
	 */
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
