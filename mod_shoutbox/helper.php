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
	 * @var		boolean  Is the post being submitted by AJAX
	 * @since   2.0.0
	 */
	private static $ajax = false;

	/**
	 * @var		array  The available smilies and their paths
	 * @since   2.0.0
	 */
	public static $smileys = array(
		':)' => 'media/mod_shoutbox/images/icon_e_smile.gif',
		':(' => 'media/mod_shoutbox/images/icon_e_sad.gif',
		':D' => 'media/mod_shoutbox/images/icon_e_biggrin.gif',
		'xD' => 'media/mod_shoutbox/images/icon_e_biggrin.gif',
		':p' => 'media/mod_shoutbox/images/icon_razz.gif',
		':P' => 'media/mod_shoutbox/images/icon_razz.gif',
		';)' => 'media/mod_shoutbox/images/icon_e_wink.gif',
		':S' => 'media/mod_shoutbox/images/icon_e_confused.gif',
		':@' => 'media/mod_shoutbox/images/icon_mad.gif',
		':O' => 'media/mod_shoutbox/images/icon_e_surprised.gif',
		'lol' => 'media/mod_shoutbox/images/icon_lol.gif',
	);

	/**
	 * Fetches the parameters of the shoutbox independently of the view
	 * so it can be used for the AJAX
	 *
	 * @param   string  $instance  The instance of the module to retrieve
	 *
	 * @return  JRegistry  The parameters of the module
	 */
	public static function getParams($instance = 'mod_shoutbox')
	{
		jimport('joomla.application.module.helper');
		$module = JModuleHelper::getModule($instance);
		$moduleParams = new JRegistry;
		$moduleParams->loadString($module->params);

		return $moduleParams;
	}

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
		$db = JFactory::getDBO();
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
	 * Filters the posts before adding the post.
	 *
	 * @param   int      $shout         The shout post.
	 * @param   JUser    $user          The user id number.
	 * @param   boolean  $swearCounter  Is the swear counter is on.
	 * @param   int      $swearNumber   If the swear counter is on - how many swears are allowed.
	 * @param   int      $displayName   The user display name.
	 *
	 * @return  mixed  Array when called by AJAX, otherwise boolean depending on success.
	 *
	 * @since 1.1.2
	 */
	public static function addShout($shout, $user, $swearCounter, $swearNumber, $displayName)
	{
		if (isset($shout['shout']))
		{
			JSession::checkToken() or jexit(JText::_('SHOUT_INVALID_TOKEN'));

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

						$name = static::swearFilter($shout['name'], $replace);

						// Retrieve Generic Name parameters
						$params = static::getParams('mod_shoutbox');
						$genericName = $params->get('genericname');

						if ($name == '')
						{
							$name = $genericName;
						}

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

					$message = nl2br(static::swearFilter($shout['message'], $replace));

					if ($swearCounter == 0)
					{
						$after = substr_count($message, $replace);
						$messageSwears = ($after - $before);
					}
					else
					{
						$messageSwears = 0;
					}

					$ip = $_SERVER['REMOTE_ADDR'];

					if ($swearCounter == 1 || $swearCounter == 0 && (($nameSwears + $messageSwears) <= $swearNumber))
					{
						$config = JFactory::getConfig();
						$db = JFactory::getDbo();
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

						if (static::$ajax)
						{
							return array('value' => $db->insertid());
						}

						return true;
					}
				}
			}
		}

		return false;
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
	 * Replaces all the bbcode in the message.
	 *
	 * @param   string  $message  The message to be searched possibly with bbcode in.
	 *
	 * @return   string  The message with the replaced bbcode code in.
	 *
	 * @since 1.0
	 */
	public static function bbcodeFilter($message)
	{
		// Replace the smileys
		foreach (static::$smileys as $smile => $url)
		{
			$replace = '<img src="' . $url . '" alt="' . $smile . '">';
			$message = str_replace($smile, $replace, $message);
		}

		// Parse the Bold, Italic, strikes and links
		$search = array(
			'/\[b\](.*?)\[\/b\]/is',
			'/\[i\](.*?)\[\/i\]/is',
			'/\[u\](.*?)\[\/u\]/is',
			'/\[url=(?:http(s?):\/\/)?([^\]]+)\]\s*(.*?)\s*\[\/url\]/is'
		);

		$replace = array(
			'<span class="jj-bold">$1</span>',
			'<span class="jj-italic">$1</span>',
			'<span class="jj-underline">$1</span>',
			'<a href="http$1://$2" target="_blank">$3</a>'
		);

		$message = preg_replace($search, $replace, $message);

		return $message;
	}

	/**
	 * Displays an array of smilies.
	 *
	 * @return   array  $smilies The smiley images html code.
	 *
	 * @since 2.5
	 */
	public static function smileyShow()
	{
		$smilies = '';

		foreach (static::$smileys as $smile => $url)
		{
			$smilies .= '<img class="jj_smiley" src="' . $url . '" alt="' . $smile . '" />';
		}

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
	public static function swearFilter($post, $replace)
	{
		// Import Dependencies
		JLoader::import('joomla.filesystem.file');

		// Define the location of the swear word list
		$myfile = JPATH_SITE . 'modules/mod_shoutbox/swearWords.php';

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
			$post = static::stri_replace($word, $replace, $post);
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
			static::deletepost($row->id);
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

	/**
	 * Method for submitting the post. Note AJAX suffix so it can take advantage of com_ajax
	 *
	 * @param   string  $instance  The instance of the module.
	 *
	 * @return   mixed  True on success outside of AJAX mode, false on failure. Integer on success when accessed via AJAX.
	 */
	public static function submitAJAX($instance = 'mod_shoutbox')
	{
		$input  = JFactory::getApplication()->input;

		// If coming from AJAX let's get the title from the request
		if ($input->get('ajax'))
		{
			$instance = $input->get('title');
			static::$ajax = true;
		}

		// Get the user instance
		$user = JFactory::getUser();

		// Retrieve relevant parameters
		$params = static::getParams($instance);
		$displayName = $params->get('loginname');
		$recaptcha = $params->get('recaptchaon', 1);
		$swearCounter = $params->get('swearingcounter');
		$swearNumber = $params->get('swearingnumber');
		$securityQuestion = $params->get('securityquestion');

		if (!get_magic_quotes_gpc())
		{
			$post = $input->getArray($_POST);
		}
		else
		{
			$post = JRequest::get('post');
		}

		if ($recaptcha == 0)
		{
			// Recaptcha is on
			if (isset($post["recaptcha_response_field"]))
			{
				if ($post["recaptcha_response_field"])
				{
					$resp = recaptcha_check_answer(
						$params->get('recaptcha-private'),
						$_SERVER["REMOTE_ADDR"],
						$post["recaptcha_challenge_field"],
						$post["recaptcha_response_field"]
					);

					if ($resp->is_valid)
					{
						$result = static::addShout($post, $user, $swearCounter, $swearNumber, $displayName);

						if (static::$ajax)
						{
							return $result;
						}
					}
					else
					{
						$error = $resp->error;
					}
				}
			}
		}
		elseif ($securityQuestion == 0)
		{
			// Our maths security question is on
			if (isset($post['sum1']) && isset($post['sum2']))
			{
				$que_result = $post['sum1'] + $post['sum2'];

				if (isset($post['human']))
				{
					if ($post['human'] == $que_result)
					{
						$result = static::addShout($post, $user, $swearCounter, $swearNumber, $displayName);

						if (static::$ajax)
						{
							return $result;
						}
					}
					else
					{
						$errorMessage = JText::_('SHOUT_ANSWER_INCORRECT');

						if (static::$ajax)
						{
							return array('error' => $errorMessage);
						}
						else
						{
							JFactory::getApplication()->enqueueMessage($errorMessage, 'error');
						}

						return false;
					}
				}
			}
		}
		else
		{
			$result = static::addShout($post, $user, $swearCounter, $swearNumber, $displayName);

			if (static::$ajax)
			{
				return $result;
			}
		}

		return true;
	}
}
