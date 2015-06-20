<?php
/**
* @package    JJ_Shoutbox
* @copyright  Copyright (C) 2011 - 2015 JoomJunk. All rights reserved.
* @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

JLoader::register('JFile', JPATH_LIBRARIES . '/joomla/filesystem/file.php');

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
	public $ajax = false;

	/**
	 * @var		JRegistry  The parameters for the module.
	 * @since   __DEPLOY_VERSION__
	 */
	private $params = null;

	/**
	 * @var		array  The available smilies and their paths
	 * @since   1.2.0
	 */
	public $smileys = array(
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
	 * Method for submitting the post. Note AJAX suffix so it can take advantage of com_ajax
	 *
	 * @return   array  The details of the post created.
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  RuntimeException
	 */
	public static function submitAjax()
	{
		$app = JFactory::getApplication();
		$post  = $app->input->post->get('jjshout', array(), 'array');

		// Retrieve relevant parameters
		if (!isset($post['title']))
		{
			throw new RuntimeException("Couldn't assemble the necessary parameters for the module");
		}

		$helper       = new ModShoutboxHelper($post['title']);
		$helper->ajax = true;

		// Make sure someone pressed shout and the post message isn't empty
		if (isset($post['shout']))
		{
			if (empty($post['message']))
			{
				throw new RuntimeException ('The message body is empty');				
			}

			$id = $helper->submitPost($post);
			$shout = $helper->getAShout($id);

			$htmlOutput = $helper->renderPost($shout);

			// Return the HTML represetation, the id and the message contents
			$result = array(
				'html'    => $htmlOutput,
				'id'      => $id,
				'message' => $shout->msg
			);

			return $result;
		}
		
		throw new RuntimeException ('There was an error processing the form. Please try again!');
	}

	/**
	 * Method for getting the posts via AJAX. Note AJAX suffix so it can take advantage of com_ajax
	 *
	 * @return   array  The details of the post created.
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  RuntimeException
	 */
	public static function getPostsAjax()
	{
		$app = JFactory::getApplication();
		$post  = $app->input->post->get('jjshout', array(), 'array');

		// Retrieve required parameter
		if (!isset($post['title']))
		{
			throw new RuntimeException("Couldn't assemble the necessary parameters for the module");
		}

		$helper       = new ModShoutboxHelper($post['title']);
		$helper->ajax = true;

		$shouts = $helper->getShouts($helper->getParams()->get('maximum'), JText::_('SHOUT_DATABASEERRORSHOUT'));

		$htmlOutput = '';

		foreach ($shouts as $shout)
		{
			$htmlOutput .= $helper->renderPost($shout);
		}

		// Return the HTML representation, the id and the message contents
		$result = array(
			'html'    => $htmlOutput,
		);

		return $result;
		
		throw new RuntimeException ('There was an error processing the form. Please try again!');
	}

	/**
	 * Fetches the parameters of the shoutbox independently of the view
	 * so it can be used for the AJAX
	 *
	 * @param   string  $id  The id of the module
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct($id)
	{
		$this->params = $this->getParams($id);
	}

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
	public function getParams($title = null)
	{
		jimport('joomla.application.module.helper');
		$module = JModuleHelper::getModule('mod_shoutbox', $title);
		$moduleParams = new JRegistry;
		$moduleParams->loadString($module->params);

		return $moduleParams;
	}

	/*
	 * Wrapper function for getting the shouts in PHP
	 *
	 * @param   int     $number   The number of posts to retrieve from the database.
	 * @param   string  $message  The error message to return if the database retrieval fails.
	 *
	 * @return  array  The shoutbox posts.
	 *
	 * @since 2.0
	 */
	public function getShouts($number, $message)
	{
		try
		{
			$shouts = $this->getShoutData($number);
		}
		catch (Exception $e)
		{
			$shouts = $this->createErrorMsg($message, $e);
		}

		return $shouts;
	}

	/**
	 * Retrieves the shouts from the database and returns them. Will return an error
	 * message if the database retrieval fails.
	 *
	 * @param   int     $number   The number of posts to retrieve from the database.
	 *
	 * @return  array  The shoutbox posts.
	 *
	 * @since 1.0
	 */
	private function getShoutData($number)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->quoteName('#__shoutbox'))
			->order($db->quoteName('id') . ' DESC');
		$db->setQuery($query, 0, $number);

		$rows = $db->loadObjectList();

		// If we have an error then we'll create an exception
		if ($db->getErrorNum())
		{
			throw new RuntimeException($db->getErrorMsg(), $db->getErrorNum());
		}

		// Ensure the date formatting
		foreach ($rows as $row)
		{
			$row->when = JFactory::getDate($row->when)->format('Y-m-d H:i:s');
		}

		return $rows;
	}

	/**
	 * Retrieves the shouts from the database and returns them. Will return an error
	 * message if the database retrieval fails.
	 *
	 * @param   int  $id  The id of the post to retrieve.
	 *
	 * @return  object  The shoutbox post.
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  RuntimeException
	 */
	public function getAShout($id)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->quoteName('#__shoutbox'))
			->where($db->quoteName('id') . ' = ' . $id);
		$db->setQuery($query);

		$row = $db->loadObject();

		// If we have an error then we'll create an exception
		if ($db->getErrorNum())
		{
			throw new RuntimeException($db->getErrorMsg(), $db->getErrorNum());
		}

		// Format the when correctly
		$row->when = JFactory::getDate($row->when)->format('Y-m-d H:i:s');

		return $row;
	}

	/**
	 * Adds the ip address on hover to the post title if an administrator.
	 *
	 * @param   JUser   $user  The user ID.
	 * @param   string  $ip    The ip address of the shout.
	 *
	 * @return  string  The title to assign.
	 *
	 * @since   1.0.1
	 */
	public function shouttitle($user, $ip)
	{
		$title = null;

		if ($user->authorise('core.delete'))
		{
			$title = ' title="' . $ip . '"';
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
	 * @return  integer  The id of the inserted post
	 *
	 * @since   1.1.2
	 */
	public function postFiltering($shout, $user, $swearCounter, $swearNumber, $displayName, $params)
	{
		$replace = '****';

		if (!$user->guest && $displayName == 'real')
		{
			$name = $user->name;
			$nameSwears = 0;
		}
		elseif (!$user->guest && $displayName == 'user')
		{
			$name = $user->username;
			$nameSwears = 0;
		}
		else
		{
			if ($swearCounter == 1)
			{
				$before = substr_count($shout['name'], $replace);
			}

			$name = $this->swearfilter($shout['name'], $replace);

			if ($name == '')
			{
				// Retrieve Generic Name parameters
				$genericName = $params->get('genericname');
				$name = $genericName;
			}

			if ($swearCounter == 1)
			{
				$after = substr_count($name, $replace);
				$nameSwears = ($after - $before);
			}
			else
			{
				$nameSwears = 0;
			}
		}

		if ($swearCounter == 1)
		{
			$before = substr_count($shout['message'], $replace);
		}

		$message = $this->swearfilter($shout['message'], $replace);

		if ($swearCounter == 1)
		{
			$after = substr_count($message, $replace);
			$messageSwears = ($after - $before);
		}

		// Ensure the max length of posts is the parameter value
		$length  = $this->params->get('messagelength', '200');
		$message = JString::substr($message, 0, $length);

		$ip = $_SERVER['REMOTE_ADDR'];

		// Sanity check on the contents of the user fields
		$filter 	= JFilterInput::getInstance();
		$name 		= $filter->clean($name, 'string');
		$message 	= $filter->clean($message, 'string');

		if ($swearCounter == 0 || $swearCounter == 1 && (($nameSwears + $messageSwears) <= $swearNumber))
		{
			return $this->addShout($name, $message, $ip);
		}
	}

	/**
	 * Replaces a instance of a object in a string with another.
	 *
	 * @param   string  $find     The thing to be found in the string.
	 * @param   string  $replace  The thing to be replaced in the string.
	 * @param   string  $string   The string to be searched.
	 *
	 * @return  string  join( $replace, $parts )  The string with the filtered parts.
	 *
	 * @since   1.0
	 */
	private function stri_replace($find, $replace, $string)
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
	 * @return  string  The message with the replaced bbcode code in.
	 *
	 * @since   1.5.0
	 */
	public function bbcodeFilter($message)
	{
		// Replace the smileys
		foreach ($this->smileys as $smile => $url)
		{
			$replace = '<img src="' . JUri::root() . $url . '" alt="' . $smile . '">';
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
	 * @return  array  $smilies The smiley images html code.
	 *
	 * @since   1.2
	 */
	public function smileyShow($id = 'jj_message')
	{
		$smilies = '';

		foreach ($this->smileys as $smile => $url)
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
	 * @return  string  $post  The post with the filtered swear words.
	 *
	 * @since   1.0
	 */
	public function swearfilter($post, $replace)
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
			$post = $this->stri_replace($word, $replace, $post);
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
	 * @return  string  $profile_link  The user name - with a profile link depending on parameters.
	 *
	 * @since   1.2.0
	 */
	public function linkUser($profile, $name, $user_id)
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
				$profile_link = '<a href="' . JRoute::_('index.php?option=com_kunena&view=user&userid=' . $user_id . '&Itemid=') . '">' . $name . '</a>';
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
	 * @return  integer  The id of the inserted row
	 *
	 * @since   1.0
	 */
	public function addShout($name, $message, $ip)
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

		try
		{
			$db->execute();
		}
		catch (Exception $e)
		{
			JLog::add(JText::sprintf('SHOUT_DATABASE_ERROR', $e), JLog::CRITICAL, 'mod_shoutbox');
		}

		return $db->insertid();
	}

	/**
	 * Removes a shout to the database.
	 *
	 * @param   int  $id  The id of the post to be deleted.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function deletepost($id)
	{
		$db	= JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->delete()
			  ->from($db->quoteName('#__shoutbox'))
			  ->where($db->quoteName('id') . ' = ' . (int) $id);
		$db->setQuery($query);

		$db->execute();
	}

	/**
	 * Removes multiple shouts from the database.
	 *
	 * @param   int  $delete  The id of the post to be deleted.
	 *
	 * @return  void
	 *
	 * @since   1.2.0
	 */
	public function deleteall($delete)
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
			$this->deletepost($row->id);
		}
	}

	/**
	 * Creates a random number for the maths question.
	 *
	 * @param   int  $digits  The number of digits long the number should be.
	 *
	 * @return  int  Random number with the number of digits specified by the input
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function randomnumber($digits)
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
	 * Wrapper function for submitPost to allow PHP to submit a post
	 *
	 * @param   JInput     $post  The filtered post superglobal.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function submitPhp($post)
	{
		if (empty($post['message']))
		{
			JFactory::getApplication()->enqueueMessage('The message body is empty', 'error');

			return false;
		}

		try
		{
			$this->submitPost($post);
		}
		catch (Exception $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}

		return;
	}

	/**
	 * Method for submitting the post
	 *
	 * @param   JInput     $post  The filtered post superglobal.
	 *
	 * @return  mixed  Integer of the post inserted on success, false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  RuntimeException
	 */
	private function submitPost($post)
	{
		// Get the user instance
		$user             = JFactory::getUser();
		$displayName      = $this->params->get('loginname', 'user');
		$securityType     = $this->params->get('securitytype', 0);
		$swearCounter     = $this->params->get('swearingcounter');
		$swearNumber      = $this->params->get('swearingnumber');

		// If we submitted by PHP check for a session token
		if ($this->ajax || $_SESSION['token'] == $post['token'])
		{
			JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

			if ($securityType == 1)
			{
				// Recaptcha fields aren't in the JJ post space so we have to grab these separately
				$input = JFactory::getApplication()->input;
				$challengeField = $input->get('recaptcha_challenge_field', '', 'string');
				$responseField = $input->get('recaptcha_response_field', '', 'string');

				// Require Recaptcha Library
				require_once JPATH_ROOT . '/media/mod_shoutbox/recaptcha/recaptchalib.php';

				$resp = recaptcha_check_answer(
					$this->params->get('recaptcha-private'),
					$_SERVER["REMOTE_ADDR"],
					$challengeField,
					$responseField
				);

				if ($resp->is_valid)
				{
					return $this->postFiltering($post, $user, $swearCounter, $swearNumber, $displayName, $this->params);
				}

				// Invalid submission of post. Throw an error.
				throw new RuntimeException($resp->error);
			}
			elseif ($securityType == 2)
			{
				// Our maths security question is on
				if (isset($post['sum1']) && isset($post['sum2']))
				{
					$que_result = $post['sum1'] + $post['sum2'];

					if (isset($post['human']))
					{
						if ($post['human'] != $que_result)
						{
							throw new RuntimeException(JText::_('SHOUT_ANSWER_INCORRECT'));
						}

						return $this->postFiltering($post, $user, $swearCounter, $swearNumber, $displayName, $this->params);
					}
				}
			}
			else
			{
				return $this->postFiltering($post, $user, $swearCounter, $swearNumber, $displayName, $this->params);
			}
		}
	}

	/**
	 * Renders the message contents with the special variables
	 *
	 * @param   string  $layout  The layout to render for the post (defaults to 'default'). The sub layout will always be message
	 *
	 * @return  string  The rendered post contents
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function renderPost($shout, $layout = 'default')
	{
		$path = JModuleHelper::getLayoutPath('mod_shoutbox', $layout . '_message');

		// Start capturing output into a buffer
		ob_start();

		// Include the requested template filename in the local scope
		// (this will execute the view logic).
		include $path;

		// Done with the requested template; get the buffer and
		// clear it.
		$template = ob_get_contents();
		ob_end_clean();

		$output = $this->processTemplate($template, $shout);

		return $output;
	}
	
	/**
	 * Gets the avatar of a user
	 *
	 * @param   int     $type  The type of avatar.
	 *
	 * @return  string  The id of the user
	 *
	 * @since   3.0.1
	 */
	public function getAvatar($type, $id) 
	{		
		$user 	= JFactory::getUser($id);
		$email 	= $user->email;
		$url    = '';
		
		if ($type == 'gravatar')
		{
			$s 		= 30;
			$d 		= 'mm';
			$r 		= 'g';
			$atts 	= array();		
			
			$url = 'http://www.gravatar.com/avatar/';
			$url .= md5( strtolower( trim( $email ) ) );
			$url .= "?s=$s&d=$d&r=$r";
			$url = '<img src="' . $url . '"';
			foreach ( $atts as $key => $val )
			{
				$url .= ' ' . $key . '="' . $val . '"';
			}
			$url .= ' />';
		}
		elseif ($type == 'kunena')
		{
			if (class_exists('KunenaFactory')) 
			{			
				$profile 	= KunenaFactory::getUser($user->id);				
				$avatar 	= $profile->getAvatarImage('kavatar','profile');
				
				$url = $profile->getAvatarImage('kavatar','profile');
			}
		}
		elseif ($type == 'jomsocial')
		{
			// JomSocial Profile Link
			$jspath = JPATH_ROOT . '/components/com_community/libraries/core.php';

			if (JFile::exists($jspath))
			{
				include_once $jspath;
				$cuser      = CFactory::getUser($user->id);
				$avatarUrl  = $cuser->getThumbAvatar();

				$url = '<img src="' . $avatarUrl . '" height="30" width="30">';
			}
			else
			{
				JLog::add(JText::_('SHOUT_JOM_SOCIAL_NOT_INSTALLED'), JLog::WARNING, 'mod_shoutbox');
				JFactory::getApplication()->enqueueMessage(JText::_('SHOUT_JOM_SOCIAL_NOT_INSTALLED'), 'error');
			}
		}
		elseif ($type == 'cb')
		{	
			// Use a database query as the CB framework is horrible
			$db = JFactory::getDbo();
 
			$query = $db->getQuery(true);
			 
			$query->select($db->quoteName('avatar'))
				  ->from($db->quoteName('#__comprofiler'))
				  ->where($db->quoteName('user_id') . ' = '. $db->quote($user->id));
			 
			$db->setQuery($query);

			try
			{
				$result = $db->loadResult();
			}
			catch (Exception $e)
			{
				// If there is an error in the database request show the default avatar
				$result = false;
			}

			if ($result)
			{
				$avatar = JUri::root() . 'images/comprofiler/tn' . $result;
			}
			else
			{
				$avatar = JUri::root() . 'components/com_comprofiler/plugin/templates/default/images/avatar/tnnophoto_n.png';
			}
			
			$url = '<img src="' . $avatar . '" height="30" width="30">';
		}
		
		return $url;
	}

	/**
	 * Processes the template output and puts in the shout variables
	 *
	 * @param   string  $template  The template variables
	 * @param   array   $shout     The shout to inject into the template
	 *
	 * @return  string  The html for the post with the appropriate shout injected in
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function processTemplate($template, $shout)
	{
		// Get user object
		$user    = JFactory::getUser();
		$message = $template;

		// Grab the bbcode and smiley params
		$smile  = $this->params->get('smile');
		$bbcode = $this->params->get('bbcode', 1);

		// Expression to search for in the message template ({{VAR}}
		$regex = '/{(.*?)}/';

		// Find all instances of plugin and put in $matches for loadposition
		// $matches[0] is full pattern match, $matches[1] is the variable to replace
		preg_match_all($regex, $template, $matches, PREG_SET_ORDER);

		foreach ($matches as $match)
		{
			switch (strtoupper($match[1]))
			{
				case 'AVATAR':
					$avatar = $this->getAvatar($this->params->get('avatar', 'none'), $shout->user_id);
					$message = str_replace('{' . $match[1] . '}', $avatar, $message);
					
					break;
					
				case 'TITLE':
					$title =  $this->shouttitle($user, $shout->ip);
					$message = str_replace('{' . $match[1] . '}', $title, $message);

					break;
				
				case 'USER':
					$profile_link = $this->linkUser($this->params->get('profile'), $shout->name, $shout->user_id);

					// Check if we need to do smiley or bbcode filtering
					if ($smile == 0 || $bbcode == 1)
					{
						$user = $this->bbcodeFilter($profile_link);
					}
					else
					{
						$user = $profile_link;
					}

					$message = str_replace('{' . $match[1] . '}', $user, $message);
					break;

				case 'DATE':
					switch ($this->params->get('date'))
					{
						case 0:
							$show_date = "d/m/Y - ";
							break;
						case 1:
							$show_date = "D m Y - ";
							break;
						case 3:
							$show_date = "m/d/Y - ";
							break;
						case 4:
							$show_date = "D j M - ";
							break;
						case 5:
							$show_date = "D j M - ";
							break;
						default:
							$show_date = "";
							break;
					}

					$date = JHtml::date($shout->when, $show_date . 'H:i', true);
					$message = str_replace('{' . $match[1] . '}', $date, $message);
					break;

				case 'POSTID':
					$id = $shout->id;
					$message = str_replace('{' . $match[1] . '}', $id, $message);
					break;

				case 'MESSAGE':
					if ($smile == 0 || $smile == 1 || $smile == 2 || $bbcode == 1)
					{
						$post = $this->bbcodeFilter($shout->msg);
					}
					else
					{
						$post = nl2br($shout->msg);
					}

					$message = str_replace('{' . $match[1] . '}', $post, $message);
			}
		}

		return $message;
	}

	/*
	 * Creates the error message to display to the user
	 * 
	 * @param   string     $message  The translated string to show to the user
	 * @param   Exception  $e        The database exception when trying to retrieve the posts
	 * 
	 * @return  array  An array
	 *
	 * @since   2.0
	 */
	private function createErrorMsg($message, $e)
	{
		// Output error to shoutbox.
		$shouts[0] = new stdClass;
		$shouts[0]->name = 'Administrator';
		$shouts[0]->when = JFactory::getDate()->format('Y-m-d H:i:s');
		$shouts[0]->msg = $message;
		$shouts[0]->ip = 'System';
		$shouts[0]->user_id = 0;

		// Add error to log.
		JLog::add(JText::sprintf('SHOUT_DATABASE_ERROR', $e->getMessage()), JLog::CRITICAL, 'mod_shoutbox');

		return $shouts;
	}
}
