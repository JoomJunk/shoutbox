<?php
/**
* @package    JJ_Shoutbox
* @copyright  Copyright (C) 2011 - 2013 JoomJunk. All rights reserved.
* @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

/**
 * Shoutbox helper connector class.
 *
 * @since  1.0
 */
class ModShoutboxHelper
{
	/**
	 * Retrieves the shouts from the database and returns them. Will return an error
	 * message if the database retrieval fails.
	 *
	 * @param   int     $number   The number of posts to retrieve from the databse.
	 * @param   string  $message  The error message to return if the database retrieval fails.
	 *
	 * @return  array  The shoutbox posts.
	 *
	 * @since 1.0
	 */
	public static function getShouts($number, $message)
	{
		$shouts	= array();
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
		->from('#__shoutbox')
		->order('id DESC');
		$db->setQuery($query, 0, $number);
		$i = 0;

		if (!JError::$legacy)
		{
			try
			{
				// Execute the query.
				$rows = $db->loadObjectList();
			}
			catch (Exception $e)
			{
				// Output error to shoutbox.
				$shouts[$i] = new stdClass;
				$shouts[$i]->name = 'Administrator';
				$shouts[$i]->when = JFactory::getDate()->format('Y-m-d H:i:s');
				$shouts[$i]->msg = $message;
				$shouts[$i]->ip = 'System';
				$shouts[$i]->user_id = 0;

				// Add error to log.
				JLog::add(JText::sprintf('SHOUT_DATABASE_ERROR', $e), JLog::CRITICAL, 'mod_shoutbox');

				return $shouts;
			}
		}
		else
		{
			$rows = $db->loadObjectList();

			if ($db->getErrorNum())
			{
				$shouts[$i] = new stdClass;
				$shouts[$i]->name = 'Administrator';
				$shouts[$i]->when = JFactory::getDate()->format('Y-m-d H:i:s');
				$shouts[$i]->msg = $message;
				$shouts[$i]->ip = 'System';
				$shouts[$i]->user_id = 0;

				// Add error to log.
				JLog::add(JText::sprintf('SHOUT_DATABASE_ERROR', $db->getErrorMsg()), JLog::CRITICAL, 'mod_shoutbox');

				return $shouts;
			}
		}

		foreach ( $rows as $row )
		{
			$shouts[$i] = new stdClass;
			$shouts[$i]->id = $row->id;
			$shouts[$i]->name = $row->name;
			$shouts[$i]->when = JFactory::getDate($row->when)->format('Y-m-d H:i:s');
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
	 * @param   JUser   $user  The user ID.
	 * @param   string  $ip    The ip address of the shout.
	 *
	 * @return  string  The title to assign.
	 *
	 * @since 1.0.1
	 */
	public static function shouttitle($user, $ip)
	{
		$title = null;

		if ($user->authorise('core.delete'))
		{
			$title = 'title="' . $ip . '"';
		}

		return $title;
	}

	/**
	 * Filters the posts before calling the add function.
	 *
	 * @param   int      $shout         The shout post.
	 * @param   JUser    $user          The user id number.
	 * @param   boolean  $swearCounter  Is the swear counter is on.
	 * @param   int      $swearNumber   If the swear counter is on - how many swears are allowed.
	 * @param   int      $displayName   The user display name.
	 *
	 * @return  void
	 *
	 * @since 1.1.2
	 */
	public static function postFiltering($shout, $user, $swearCounter, $swearNumber, $displayName)
	{
		if (isset($shout['shout']))
		{
			JSession::checkToken() or die(JText::_('SHOUT_INVALID_TOKEN'));

			if (!empty($shout['message']))
			{
				if ($_SESSION['token'] == $shout['token'])
				{
					$replace = '****';

					if (!$user->guest && $displayName == 0)
					{
						$name = $user->name;
						$nameSwears = 0;
					}
					elseif (!$user->guest && $displayName == 1)
					{
						$name = $user->username;
						$nameSwears = 0;
					}
					else
					{
						if ($swearCounter == 0)
						{
							$before = substr_count($shout['name'], $replace);
						}

						$name = self::swearfilter($shout['name'], $replace);

						if ($swearCounter == 0)
						{
							$after = substr_count($name, $replace);
							$nameSwears = ($after - $before);
						}
						else
						{
							$nameSwears = 0;
						}
					}

					if ($swearCounter == 0)
					{
						$before = substr_count($shout['message'], $replace);
					}

					$message = self::swearfilter($shout['message'], $replace);

					if ($swearCounter == 0)
					{
						$after = substr_count($message, $replace);
						$messageSwears = ($after - $before);
					}

					$ip = $_SERVER['REMOTE_ADDR'];

					if ($swearCounter == 1 || $swearCounter == 0 && (($nameSwears + $messageSwears) <= $swearNumber))
					{
						self::addShout($name, $message, $ip);
					}
				}
			}
		}
	}

	/**
	 * Replaces a instance of a object in a string with another.
	 *
	 * @param   string  $find     The thing to be found in the string.
	 * @param   string  $replace  The thing to be replaced in the string.
	 * @param   string  $string   The string to be searched.
	 *
	 * @return   string  join( $replace, $parts )  The string with the filtered parts.
	 *
	 * @since 1.0
	 */
	public static function stri_replace($find, $replace, $string)
	{
		$parts = explode(strtolower($find), strtolower($string));
		$pos = 0;

		foreach ($parts as $key => $part)
		{
			$parts[ $key ] = substr($string, $pos, strlen($part));
			$pos += strlen($part) + strlen($find);
		}

		return( join($replace, $parts) );
	}

	/**
	 * Replaces all the smileys in the message.
	 *
	 * @param   string  $message  The message to be searched to add smileys in.
	 *
	 * @return   string  $message  The message with the smiley code in.
	 *
	 * @since 1.0
	 */
	public static function smileyFilter($message)
	{
		$smileys = array(
			':)' => ' <img src="media/mod_shoutbox/images/icon_e_smile.gif" alt=":)">',
			':(' => ' <img src="media/mod_shoutbox/images/icon_e_sad.gif" alt=":(">',
			':D' => ' <img src="media/mod_shoutbox/images/icon_e_biggrin.gif" alt=":D">',
			'xD' => ' <img src="media/mod_shoutbox/images/icon_e_biggrin.gif" alt="xD">',
			':p' => ' <img src="media/mod_shoutbox/images/icon_razz.gif" alt=":p">',
			':P' => ' <img src="media/mod_shoutbox/images/icon_razz.gif" alt=":P">',
			';)' => ' <img src="media/mod_shoutbox/images/icon_e_wink.gif" alt=";)">',
			':S' => ' <img src="media/mod_shoutbox/images/icon_e_confused.gif" alt=":S">',
			':@' => ' <img src="media/mod_shoutbox/images/icon_mad.gif" alt=":@">',
			':O' => ' <img src="media/mod_shoutbox/images/icon_e_surprised.gif" alt=":O">',
			'lol' => ' <img src="media/mod_shoutbox/images/icon_lol.gif" alt="lol">',
		);

		foreach ($smileys as $key => $val)
		{
			$message = str_replace($key, $val, $message);
		}

		return $message;
	}

	/**
	 * Displays an array of smilies.
	 *
	 * @return   array  $smilies The smiley images html code.
	 *
	 * @since 2.5
	 */
	public static function smileyshow()
	{
		$smilies = '';
		$smilies .= '<img class="jj_smiley" src="media/mod_shoutbox/images/icon_e_smile.gif" alt=":)" />';
		$smilies .= '<img class="jj_smiley" src="media/mod_shoutbox/images/icon_e_sad.gif" alt=":(" />';
		$smilies .= '<img class="jj_smiley" src="media/mod_shoutbox/images/icon_e_biggrin.gif" alt=":D" />';
		$smilies .= '<img class="jj_smiley" src="media/mod_shoutbox/images/icon_e_biggrin.gif" alt="xD" />';
		$smilies .= '<img class="jj_smiley" src="media/mod_shoutbox/images/icon_razz.gif" alt=":P" />';
		$smilies .= '<img class="jj_smiley" src="media/mod_shoutbox/images/icon_e_wink.gif" alt=";)" />';
		$smilies .= '<img class="jj_smiley" src="media/mod_shoutbox/images/icon_e_confused.gif" alt=":S" />';
		$smilies .= '<img class="jj_smiley" src="media/mod_shoutbox/images/icon_mad.gif" alt=":@" />';
		$smilies .= '<img class="jj_smiley" src="media/mod_shoutbox/images/icon_e_surprised.gif" alt=":O" />';
		$smilies .= '<img class="jj_smiley" src="media/mod_shoutbox/images/icon_lol.gif" alt="lol" />';

		return $smilies;
	}

	/**
	 * Retrieves swear words from a file and then filters them.
	 *
	 * @param   string  $post     The post to be searched.
	 * @param   string  $replace  The thing to be replace the swear words in the string.
	 *
	 * @return   string  $post  The post with the filtered swear words.
	 *
	 * @since 1.0
	 */
	public static function swearfilter($post, $replace)
	{
		$myfile = 'modules/mod_shoutbox/swearWords.php';

		if (!JFile::exists($myfile))
		{
			JLog::add(JText::_('SHOUT_SWEAR_FILE_NOT_FOUND'), JLog::WARNING, 'mod_shoutbox');

			return $post;
		}

		$words = file($myfile, FILE_IGNORE_NEW_LINES);
		$i = 0;

		while ($i < 10)
		{
			unset($words[$i]);
			$i++;
		}

		$swearwords = array_values($words);

		foreach ($swearwords as $key => $word )
		{
			$post = self::stri_replace($word, $replace, $post);
		}

		return $post;
	}

	/**
	 * Links a users profile with another extension if desired.
	 *
	 * @param   int     $profile  The post to be searched.
	 * @param   string  $name     The name of the user from the database.
	 * @param   int     $user_id  The id of the user.
	 *
	 * @return   string  $profile_link  The user name - with a profile link depending on parameters.
	 *
	 * @since 1.2.0
	 */
	public static function linkUser($profile, $name, $user_id)
	{
		$profile_link = '';

		if ($user_id != 0)
		{
			if ($profile == 1)
			{
				// Community Builder Profile Link
				$profile_link = '<a href="' . JRoute::_('index.php?option=com_comprofiler&task=userProfile&user=' . $user_id) . '">' . $name . '</a>';
			}
			elseif ($profile == 2)
			{
				// Kunena Profile Link
				$profile_link = '<a href="' . JRoute::_('index.php?option=com_kunena&func=fbprofile&userid=' . $user_id) . '">' . $name . '</a>';
			}
			elseif ($profile == 3)
			{
				// JomSocial Profile Link
				$jspath = JPATH_ROOT . '/components/com_community/libraries/core.php';

				if (JFile::exists($jspath))
				{
					include_once $jspath;
					$profile_link = '<a href="' . CRoute::_('index.php?option=com_community&view=profile&userid=' . $user_id) . '">' . $name . '</a>';
				}
				else
				{
					JLog::add(JText::_('SHOUT_JOM_SOCIAL_NOT_INSTALLED'), JLog::WARNING, 'mod_shoutbox');
					JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_JOM_SOCIAL_NOT_INSTALLED'), 'error');
				}
			}
			elseif ($profile == 4)
			{
				// K2 Profile Link
				$profile_link = '<a href="' . JRoute::_('index.php?option=com_k2&view=itemlist&layout=user&id=' . $user_id .
					'&task=user') . '">' . $name . '</a>';
			}
			else
			{
				// No profile Link
				$profile_link = $name;
			}
		}
		else
		{
			$profile_link = $name;
		}

		return $profile_link;
	}

	/**
	 * Adds a shout to the database.
	 *
	 * @param   string  $name     The post to be searched.
	 * @param   string  $message  The name of the user from the database.
	 * @param   string  $ip       The ip of the user.
	 *
	 * @return   void
	 *
	 * @since 1.0
	 */
	public static function addShout($name, $message, $ip)
	{
		$db = JFactory::getDBO();
		$config = JFactory::getConfig();
		$columns = array('name', 'when', 'ip', 'msg', 'user_id');
		$values = array($db->Quote($name), $db->Quote(JFactory::getDate('now', $config->get('offset'))->toSql(true)),
			$db->quote($ip), $db->quote($message), $db->quote(JFactory::getUser()->id));
		$query = $db->getQuery(true);

		if (version_compare(JVERSION, '3.0.0', 'ge'))
		{
			$query
				->insert($db->quoteName('#__shoutbox'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));
		}
		else
		{
			$query
				->insert($db->nameQuote('#__shoutbox'))
				->columns($db->nameQuote($columns))
				->values(implode(',', $values));
		}

		$db->setQuery($query);

		if (version_compare(JVERSION, '3.0.0', 'ge'))
		{
			try
			{
				$db->execute();
			}
			catch (Exception $e)
			{
				JLog::add(JText::sprintf('SHOUT_DATABASE_ERROR', $e), JLog::CRITICAL, 'mod_shoutbox');
			}
		}
		else
		{
			$db->query();

			if ($db->getErrorNum())
			{
				JLog::add(JText::sprintf('SHOUT_DATABASE_ERROR', $db->getErrorMsg()), JLog::CRITICAL, 'mod_shoutbox');
			}
		}
	}

	/**
	 * Removes a shout to the database.
	 *
	 * @param   int  $id  The id of the post to be deleted.
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public static function deletepost($id)
	{
		JSession::checkToken() or die(JText::_('SHOUT_INVALID_TOKEN'));
		$db	= JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->delete()
		->from('#__shoutbox')
		->where('id = ' . (int) $id);
		$db->setQuery($query);

		if (version_compare(JVERSION, '3.0.0', 'ge'))
		{
			$db->execute();
		}
		else
		{
			$db->query();
		}
	}

	/**
	 * Removes multiple shouts from the database.
	 *
	 * @param   int  $delete  The id of the post to be deleted.
	 *
	 * @return  void
	 *
	 * @since 1.2.0
	 */
	public static function deleteall($delete)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__shoutbox')
			->order('id DESC');
		$db->setQuery($query, 0, $delete);
		$rows = $db->loadObjectList();

		foreach ($rows as $row)
		{
			self::deletepost($row->id);
		}
	}

	/**
	 * Creates a random number for the maths question.
	 *
	 * @param   int  $digits  The number of digits long the number should be.
	 *
	 * @return  int  Random number with the number of digits specified by the input
	 */
	public static function randomnumber($digits)
	{
		static $startseed = 0;

		if (!$startseed)
		{
			$startseed = (double) microtime() * getrandmax();
			srand($startseed);
		}

		$range = 8;
		$start = 1;
		$i = 1;

		while ($i < $digits)
		{
			$range = $range . 9;
			$start = $start . 0;
			$i++;
		}

		return (rand() % $range + $start);
	}
}
