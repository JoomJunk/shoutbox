<?php
/**
* @package    JJ_Shoutbox
* @copyright  Copyright (C) 2011 - 2014 JoomJunk. All rights reserved.
* @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');

/**
 * Shoutbox helper connector class.
 *
 * @since  1.0
 */
class ModShoutboxHelper
{
	/**
	 * @var		boolean  Is the post being submitted by AJAX
	 * @since   __DEPLOY_VERSION__
	 */
	private static $ajax = false;

	/**
	 * Fetches the parameters of the shoutbox independently of the view
	 * so it can be used for the AJAX
	 *
	 * @param   string  $title  The title of the module to retrieve
	 *
	 * @return  JRegistry  The parameters of the module
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getParams($title = null)
	{
		jimport('joomla.application.module.helper');
		$module = JModuleHelper::getModule('mod_shoutbox', $title);
		$moduleParams = new JRegistry;
		$moduleParams->loadString($module->params);

		return $moduleParams;
	}

	/**
	 * Retrieves the shouts from the database and returns them. Will return an error
	 * message if the database retrieval fails.
	 *
	 * @param   int     $number   The number of posts to retrieve from the database.
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
		->from($db->quoteName('#__shoutbox'))
		->order($db->quoteName('id') . ' DESC');
		$db->setQuery($query, 0, $number);

		if (!JError::$legacy)
		{
			try
			{
				// Execute the query.
				$rows = $db->loadObjectList();
			}
			catch (Exception $e)
			{
				// Assemble Message and add log.
				$shouts = self::createErrorMsg();

				return $shouts;
			}
		}
		else
		{
			$rows = $db->loadObjectList();

			if ($db->getErrorNum())
			{
				// Assemble Message and add log.
				$shouts = self::createErrorMsg();

				return $shouts;
			}
		}

		$i = 0;

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
	 * @param   int        $shout         The shout post.
	 * @param   JUser      $user          The user id number.
	 * @param   boolean    $swearCounter  Is the swear counter is on.
	 * @param   int        $swearNumber   If the swear counter is on - how many swears are allowed.
	 * @param   int        $displayName   The user display name.
	 * @param   JRegistry  $params        The parameters for the module
	 *
	 * @return  void
	 *
	 * @since 1.1.2
	 */
	public static function postFiltering($shout, $user, $swearCounter, $swearNumber, $displayName, $params)
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

			if ($name == '')
			{
				// Retrieve Generic Name parameters
				$params = static::getParams();
				$genericName = $params->get('genericname');
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
	 * @var		array  The available smilies and their paths
	 * @since   1.2.0
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
	 * Replaces all the bbcode in the message.
	 *
	 * @param   string  $message  The message to be searched possibly with bbcode in.
	 *
	 * @return   string  The message with the replaced bbcode code in.
	 *
	 * @since 1.5.0
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
	 * @param   string  $id  The id of the textarea to insert the smiley into
	 *
	 * @return   array  $smilies The smiley images html code.
	 *
	 * @since 1.2
	 */
	public static function smileyShow($id = 'jj_message')
	{
		$smilies = '';

		foreach (static::$smileys as $smile => $url)
		{
			$smilies .= '<img class="jj_smiley" src="' . $url . '" alt="' . $smile . '" onClick="addSmiley(\'' . $smile . '\', \'' . $id . '\')" />';
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
				if (class_exists('KunenaFactory') && class_exists('KunenaProfileKunena')) {
					$kUser = KunenaFactory::getUser()->userid;
					$kLink = KunenaProfileKunena::getProfileURL($kUser);
				}
				else {
					$kLink = null;
				}
				$profile_link = '<a href="' . $kLink . '">' . $name . '</a>';
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
		$db = JFactory::getDbo();
		$config = JFactory::getConfig();
		$columns = array('name', 'when', 'ip', 'msg', 'user_id');
		$values = array($db->Quote($name), $db->Quote(JFactory::getDate('now')->toSql(true)), 
			$db->quote($ip), $db->quote($message), $db->quote(JFactory::getUser()->id));
		$query = $db->getQuery(true);

		$query->insert($db->quoteName('#__shoutbox'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));

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
		$db	= JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->delete()
		->from($db->quoteName('#__shoutbox'))
		->where($db->quoteName('id') . ' = ' . (int) $id);
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
			->from($db->quoteName('#__shoutbox'))
			->order($db->quoteName('id') . ' DESC');
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

	/**
	 * Method for submitting the post. Note AJAX suffix so it can take advantage of com_ajax
	 *
	 * @param   string  $instance  The instance of the module.
	 *
	 * @return   array  The details of the post created.
	 *
	 * @throws  RuntimeException
	 */
	public static function submitAjax($instance = 'mod_shoutbox')
	{
		static::$ajax = true;

		if (!get_magic_quotes_gpc())
		{
			$app = JFactory::getApplication();
			$post  = $app->input->post->get('jjshoutbox', array(), 'array');
		}
		else
		{
			$post = JRequest::getVar('jjshoutbox', array(), 'post', 'array');
		}

		// Retrieve relevant parameters
		if (!isset($post['title']))
		{
			throw new RuntimeException("Couldn't assemble the necessary parameters for the module");
		}

		$instance = $post['title'];
		$params = static::getParams($instance);

		// Make sure someone pressed shout and the post message isn't empty
		if (isset($post['shout']))
		{
			if (empty($post['message']))
			{
				throw new RuntimeException ('The message body is empty');				
			}

			return static::submitPost($post, $params);
		}
		
		throw new RuntimeException ('There was an error processing the form. Please try again!');
	}

	/**
	 * Wrapper function for submitPost to allow PHP to submit a post
	 *
	 * @param   JInput     $post  The filtered post superglobal.
	 * @param   JRegistry  $post  The parameters for the module.
	 *
	 * @return  mixed  True on success, false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function submitPhp($post, $params)
	{
		return static::submitPost($post, $params);
	}

	/**
	 * Method for submitting the post
	 *
	 * @param   JInput     $post  The filtered post superglobal.
	 * @param   JRegistry  $post  The parameters for the module.
	 *
	 * @return  mixed  True on success outside of AJAX mode, false on failure. Integer on success when accessed via AJAX.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private static function submitPost($post, $params)
	{
		// Get the user instance
		$user             = JFactory::getUser();
		$displayName      = $params->get('loginname');
		$recaptcha        = $params->get('recaptchaon', 1);
		$swearCounter     = $params->get('swearingcounter');
		$swearNumber      = $params->get('swearingnumber');
		$securityQuestion = $params->get('securityquestion');

		// If we submitted by PHP check for a session token
		if (static::$ajax || $_SESSION['token'] == $post['token'])
		{
			JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

			if ($recaptcha == 0)
			{
				// Recaptcha fields aren't in the JJ post space so we have to grab these separately
				$input = JFactory::getApplication()->input;
				$challengeField = $input->get('recaptcha_challenge_field', '', 'string');
				$responseField = $input->get('recaptcha_response_field', '', 'string');

				// Check we have a valid response field
				if (!isset($responseField) || isset($responseField) && $responseField)
				{
					return false;
				}

				// Require Recaptcha Library
				require_once JPATH_ROOT . '/media/mod_shoutbox/recaptcha/recaptchalib.php';

				$resp = recaptcha_check_answer(
					$params->get('recaptcha-private'),
					$_SERVER["REMOTE_ADDR"],
					$challengeField,
					$responseField
				);

				if ($resp->is_valid)
				{
					$result = static::postFiltering($post, $user, $swearCounter, $swearNumber, $displayName, $params)

					return $result;
				}
				else
				{
					return array('error' => $resp->error);
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
							$result = static::postFiltering($post, $user, $swearCounter, $swearNumber, $displayName, $params)

							return $result;
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
				$result = static::postFiltering($post, $user, $swearCounter, $swearNumber, $displayName, $params)

				return $result;
			}
		}
	}

	private static function createErrorMsg()
	{
		$i = 0;

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
