<?php
/**
* @package    JJ_Shoutbox
* @copyright  Copyright (C) 2011 - 2016 JoomJunk. All rights reserved.
* @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die('Restricted access');

JLoader::register('JFile', JPATH_LIBRARIES . '/joomla/filesystem/file.php');
JLoader::register('JJShoutboxLayoutFile', JPATH_SITE . '/modules/mod_shoutbox/libraries/layout.php');

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
	 * Method for submitting the post. Note AJAX suffix so it can take advantage of com_ajax
	 *
	 * @return   array  The details of the post created.
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  RuntimeException
	 */
	public static function submitAjax()
	{
		$app  = JFactory::getApplication();
		$post = $app->input->post->get('jjshout', array(), 'array');

		// Retrieve relevant parameters
		if (!isset($post['title']))
		{
			throw new InvalidArgumentException(JText::_('SHOUT_INVALID_AJAX_PARAMS'));
		}

		$helper       = new ModShoutboxHelper($post['title']);
		$helper->ajax = true;

		// Make sure someone pressed shout and the post message isn't empty
		if (!isset($post['shout']))
		{
			throw new RuntimeException(JText::_('SHOUT_INVALID_AJAX_PARAMS'));
		}

		if (empty($post['message']))
		{
			throw new InvalidArgumentException(JText::_('SHOUT_MESSAGE_EMPTY'));
		}

		$id    = $helper->submitPost($post);
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
		$app  = JFactory::getApplication();
		$post = $app->input->post->get('jjshout', array(), 'array');

		// Retrieve required parameter
		if (!isset($post['title']))
		{
			throw new InvalidArgumentException(JText::_('SHOUT_INVALID_AJAX_PARAMS'));
		}

		$helper       = new ModShoutboxHelper($post['title']);
		$helper->ajax = true;

		$offset = 0;

		if (isset($post['offset']))
		{
			$offset = $post['offset'];
		}

		$shouts = $helper->getShouts($offset, $helper->getParams()->get('maximum'), JText::_('SHOUT_DATABASEERRORSHOUT'));

		$htmlOutput = '';

		foreach ($shouts as $shout)
		{
			$htmlOutput .= $helper->renderPost($shout);
		}

		// Return the HTML representation, the id and the message contents
		$result = array(
			'html' => $htmlOutput,
		);

		return $result;
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
	 * @param   int     $offset   The row to start getting the shouts from
	 * @param   int     $number   The number of posts to retrieve from the database.
	 * @param   string  $message  The error message to return if the database retrieval fails.
	 *
	 * @return  array  The shoutbox posts.
	 *
	 * @since 2.0
	 */
	public function getShouts($offset, $number, $message)
	{
		try
		{
			$shouts = $this->getShoutData($offset, $number);
		}
		catch (Exception $e)
		{
			// Output error to shoutbox.
			$shouts    = array();
			$shouts[0] = new stdClass;
			$shouts[0]->name = 'Administrator';
			$shouts[0]->when = JFactory::getDate()->format('Y-m-d H:i:s');
			$shouts[0]->msg = $message;
			$shouts[0]->ip = 'System';
			$shouts[0]->user_id = 0;

			// Add error to log.
			JLog::add(JText::sprintf('SHOUT_DATABASE_ERROR', $e->getMessage()), JLog::CRITICAL, 'mod_shoutbox');
		}

		return $shouts;
	}

	/**
	 * Retrieves the shouts from the database and returns them. Will return an error
	 * message if the database retrieval fails.
	 *
	 * @param   int     $offset   The row to start getting the shouts from
	 * @param   int     $number   The number of posts to retrieve from the database.
	 *
	 * @return  array  The shoutbox posts.
	 *
	 * @since 1.0
	 */
	private function getShoutData($offset, $number)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
		->select('*')
		->from($db->qn('#__shoutbox'))
		->order($db->qn('id') . ' DESC')
		->setLimit($number, $offset);

		$db->setQuery($query);

		$rows = $db->loadObjectList();

		// If we have an error then we'll create an exception
		if ($db->getErrorNum())
		{
			throw new RuntimeException($db->getErrorMsg(), $db->getErrorNum());
		}

		// Ensure the date formatting
		foreach ($rows as $row)
		{
			$row->when = JFactory::getDate($row->when);
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

		$query = $db->getQuery(true)
		->select('*')
		->from($db->qn('#__shoutbox'))
		->where($db->qn('id') . ' = ' . (int)$id);

		$db->setQuery($query);

		$row = $db->loadObject();

		// If we have an error then we'll create an exception
		if ($db->getErrorNum())
		{
			throw new RuntimeException($db->getErrorMsg(), $db->getErrorNum());
		}

		// Format the when correctly
		$row->when = JFactory::getDate($row->when);

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

		if ($user->authorise('core.admin'))
		{
			$title = ' title="' . $ip . '"';
		}

		return $title;
	}

	/**
	 * Count the number of shouts in the database.
	 *
	 * @return  int  The number of rows.
	 *
	 * @since   6.0.0
	 */
	public function countShouts()
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
		->select('COUNT(id)')
		->from($db->qn('#__shoutbox'));

		$db->setQuery($query);

		return $db->loadResult();
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
		if ($this->params->get('messagelength') == 1)
		{
			$length = $this->params->get('messagelength', '200');
		}
		else
		{
			$length = false;
		}
		$message = JString::substr($message, 0, $length);

		$ip = JFactory::getApplication()->input->server->get('REMOTE_ADDR');

		// If we don't have a valid IP address just store null in the database
		if (filter_var($ip, FILTER_VALIDATE_IP) === false)
		{
			$ip = null;
		}

		// The name field will have all html stripped
		$nameFilter = JFilterInput::getInstance();

		// We allow image and header tags in the message.
		$acceptedtags    = array('img','h1','h2','h3', 'h4', 'h5', 'h6');
		$acceptedAttribs = array('src','href','rel','title','class','id','itemprop','itemtype','itemscope');
		$messageFilter   = JFilterInput::getInstance($acceptedtags,$acceptedAttribs);

		// Do the filtering
		$name    = $nameFilter->clean($name, 'string');
		$message = $messageFilter->clean($message, 'string');

		// Start the email cloaking process
		$searchEmail = '([\w\.\-\+]+\@(?:[a-z0-9\.\-]+\.)+(?:[a-zA-Z0-9\-]{2,10}))';

		// Search for plain text email@example.org
		$pattern = '~' . $searchEmail . '([^a-z0-9]|$)~i';

		while (preg_match($pattern, $message, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[1][0];
			$replacement = JHtml::_('email.cloak', $mail);

			// Replace the found address with the js cloaked email
			$message = substr_replace($message, $replacement, $regs[1][1], strlen($mail));
		}

		if ($swearCounter == 0 || $swearCounter == 1 && (($nameSwears + $messageSwears) <= $swearNumber))
		{
			return $this->addShout($shout['type'], $shout['id'], $name, $message, $ip);
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
	 * Get the smilies json object, decode it, then combine into 1 array
	 *
	 * @return  array  The json decoded combined array
	 *
	 * @since   6.0.0
	 */
	public function getSmilies()
	{
		$list_smilies = $this->params->get('list_smilies');
		$smilies      = json_decode($list_smilies, true);

		$smilies_values = array_values($smilies['image']);
		$code_values    = array_values($smilies['code']);

		return array_combine($code_values, $smilies_values);
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
		$smilies = $this->getSmilies();

		// Replace the smileys
		foreach ($smilies as $code => $image)
		{
			$replace = '<img src="' . JUri::root() . 'images/mod_shoutbox/' . $image . '" alt="' . $code . '">';
			$message = str_replace($code, $replace, $message);
		}

		// Parse the Bold, Italic, strikes and links
		$search = array(
			'/\[b\](.*?)\[\/b\]/is',
			'/\[i\](.*?)\[\/i\]/is',
			'/\[u\](.*?)\[\/u\]/is',
			'/\[img=(?:http(s?):\/\/)?([^\]]+)\]\s*(.*?)\s*\[\/img\]/is',
			'/\[url=(?:http(s?):\/\/)?([^\]]+)\]\s*(.*?)\s*\[\/url\]/is'
		);

		$replace = array(
			'<span class="jj-bold">$1</span>',
			'<span class="jj-italic">$1</span>',
			'<span class="jj-underline">$1</span>',
			'<a href="#" data-jj-image="http$1://$2" data-jj-image-alt="$3" class="jj-image-modal">$3</a>',
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
		$getSmilies = $this->getSmilies();

		$smilies = '';

		foreach ($getSmilies as $smile => $url)
		{
			$smilies .= '<li><img class="jj_smiley" src="images/mod_shoutbox/' . $url . '" alt="' . $smile . '" onClick="JJShoutbox.addSmiley(\'' . $smile . '\', \'' . $id . '\')" /></li>';
		}

		return $smilies;
	}

	/**
	 * Groups an array by key
	 *
	 * @param   array  $array  The json decoded array
	 *
	 * @return  array  $array  The array group by key
	 *
	 * @since   6.0
	 */
	public function group_by_key($array) 
	{
		$result = array();

		foreach ($array as $sub) 
		{
			foreach ($sub as $k => $v) 
			{
				$result[$k][] = $v;
			}
		}
		return $result;
	}

	/**
	 * Retrieves swear words from the parameters and then filters them.
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
		$list_swearwords = $this->params->get('list_swearwords');
		$json            = json_decode($list_swearwords, true);

		$swearwords = array_values($json['word']);

		foreach ($swearwords as $key => $word)
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
				$klink = KunenaFactory::getUser((int) $user_id)->getLink();
				$href  = '#';

				if (!JFactory::getUser()->guest)
				{
					$dom = new DOMDocument;
					$dom->loadHTML($klink);

					foreach ($dom->getElementsByTagName('a') as $node) 
					{
						$href = $node->getAttribute('href');
					}
				}

				// Kunena Profile Link
				$profile_link = '<a href="' . $href . '">' . $name . '</a>';
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
				$profile_link = '<a href="' . JRoute::_('index.php?option=com_k2&view=itemlist&layout=user&id=' . $user_id . '&task=user') . '">' . $name . '</a>';
			}
			elseif ($profile == 5)
			{
				// Easy Profile Link
				require_once JPATH_SITE . '/components/com_jsn/helpers/helper.php';

				$href = JsnHelper::getUser($user_id)->getLink();
				$profile_link = '<a href="' . $href .'">' . $name . '</a>';
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
	 * @param   string  $type     The type of submission (insert or update)
	 * @param   string  $id       The id of the post (update only)
	 * @param   string  $name     The post to be searched.
	 * @param   string  $message  The name of the user from the database.
	 * @param   string  $ip       The ip of the user.
	 *
	 * @return  integer  The id of the inserted row or true if an update
	 *
	 * @since   1.0
	 */
	public function addShout($type, $id, $name, $message, $ip)
	{
		$db = JFactory::getDbo();

		if ($type == 'insert')
		{
			// Insert a new shout into the database
			$columns = array('name', 'when', 'ip', 'msg', 'user_id');

			$values = array(
				$db->q($name),
				$db->q(JFactory::getDate('now')->toSql(true)),
				$db->q($ip),
				$db->q($message),
				$db->q(JFactory::getUser()->id)
			);

			$query = $db->getQuery(true)
			->insert($db->qn('#__shoutbox'))
			->columns($db->qn($columns))
			->values(implode(',', $values));

			$db->setQuery($query);
			$db->execute();

			return $db->insertid();
		}
		else if ($type == 'update' && $id != '')
		{
			// Update an existing shout in the database
			$object = new stdClass();
			$object->id   = $id;
			$object->name = $name;
			$object->msg  = $message;
			$object->when = JFactory::getDate('now')->toSql(true);

			JFactory::getDbo()->updateObject('#__shoutbox', $object, 'id');

			return (int)$id;
		}

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
		$db	= JFactory::getDbo();
		$query = $db->getQuery(true)
		->delete()
		->from($db->qn('#__shoutbox'))
		->where($db->qn('id') . ' = ' . (int) $id);

		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Removes multiple shouts from the database.
	 *
	 * @param   int     $delete  The id of the post to be deleted.
	 * @param   string  $dir     A string containing either ASC or DESC
	 *
	 * @return  void
	 *
	 * @since   1.2.0
	 */
	public function deleteall($delete, $dir = 'DESC')
	{
		$dir = strtoupper($dir);

		// Ensure the direction is valid. Fallback to the most recent post (for b/c)
		if (!in_array($dir, array('DESC', 'ASC')))
		{
			$dir = 'DESC';
		}

		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
		->select('*')
		->from($db->qn('#__shoutbox'))
		->order($db->qn('id') . ' ' . $dir)
		->setLimit($delete);

		$db->setQuery($query);

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
		$user         = JFactory::getUser();
		$displayName  = $this->params->get('loginname', 'user');
		$securityType = $this->params->get('securitytype', 0);
		$securityHide = $this->params->get('security-hide', 0);
		$swearCounter = $this->params->get('swearingcounter');
		$swearNumber  = $this->params->get('swearingnumber');

		// If we submitted by PHP check for a session token
		if ($this->ajax || $_SESSION['token'] == $post['token'])
		{
			JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

			if ($securityType == 1)
			{
				if ($securityHide == 0 || ($user->guest && $securityHide == 1))
				{
					// Recaptcha fields aren't in the JJ post space so we have to grab these separately
					$input = JFactory::getApplication()->input;
					$challengeField = $input->get('g-recaptcha-response', '', 'string');

					// Require Recaptcha Library
					spl_autoload_register(function ($class)
					{
						// Project-specific namespace prefix
						$prefix = 'ReCaptcha\\';

						// Base directory for the namespace prefix
						$base_dir = JPATH_ROOT . '/media/mod_shoutbox/recaptcha/';

						// Does the class use the namespace prefix?
						$len = strlen($prefix);

						if (strncmp($prefix, $class, $len) !== 0)
						{
							// No, move to the next registered autoloader
							return;
						}

						// Get the relative class name
						$relative_class = substr($class, $len);

						/**
						 * replace the namespace prefix with the base directory, replace namespace
						 * separators with directory separators in the relative class name, append
						 * with .php
						 */
						$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

						// if the file exists, require it
						if (file_exists($file))
						{
							require $file;
						}
					});

					$recaptcha = new ReCaptcha\ReCaptcha($this->params->get('recaptcha-private'));

					$resp = $recaptcha->verify($challengeField, JFactory::getApplication()->input->server->get('REMOTE_ADDR'));

					if ($resp->isSuccess())
					{
						return $this->postFiltering($post, $user, $swearCounter, $swearNumber, $displayName, $this->params);
					}

					// Invalid submission of post. Throw an error.
					$error = '';

					foreach ($resp->getErrorCodes() as $code)
					{
						$error .= $code;
					}

					throw new RuntimeException($error);
				}
				else 
				{
					return $this->postFiltering($post, $user, $swearCounter, $swearNumber, $displayName, $this->params);
				}
			}
			elseif ($securityType == 2)
			{
				if ($securityHide == 0 || ($user->guest && $securityHide == 1))
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

				throw new RuntimeException(JText::_('SHOUT_MATHS_QUESTION_INVALID'));
			}
			else
			{
				return $this->postFiltering($post, $user, $swearCounter, $swearNumber, $displayName, $this->params);
			}
		}
	}
	
	/**
	 * Converts the date to an elapsed time, e.g "1 day ago"
	 *
	 * @param     string   $datetime  The date to be converted
	 * @param     boolean  $full      Show the full elapsed time
	 *
	 * @return    string   The elapsed time
	 *
	 * @since     7.0.3
	 *
	 * @adapted from       http://stackoverflow.com/a/18602474/1362108
	 */
	public function timeElapsed($datetime, $full = false)
	{
		$now  = JFactory::getDate();
		$ago  = JFactory::getDate($datetime);
		$diff = $now->diff($ago);

		$diff->w = floor($diff->d / 7);
		$diff->d -= $diff->w * 7;

		$string = array(
			'y' => 'SHOUT_TIME_YEAR',
			'm' => 'SHOUT_TIME_MONTH',
			'w' => 'SHOUT_TIME_WEEK',
			'd' => 'SHOUT_TIME_DAY',
			'h' => 'SHOUT_TIME_HOUR',
			'i' => 'SHOUT_TIME_MINUTE',
			's' => 'SHOUT_TIME_SECOND',
		);

		foreach ($string as $k => &$v)
		{
			if ($diff->$k)
			{
				$translated = JText::_($v);

				if ($diff->$k > 1)
				{
					$translated = JText::_($v . 'S');
				}

				$v = $diff->$k . ' ' . $translated;
			}
			else
			{
				unset($string[$k]);
			}
		}

		if (!$full)
		{
			$string = array_slice($string, 0, 1);
		}

		return $string ? implode(', ', $string) . ' ' . JText::_('SHOUT_TIME_AGO') : JText::_('SHOUT_TIME_JUST_NOW');
	}
	
	/**
	 * Pre-execution before rending the output
	 *
	 * @param   object  $shout  The shout object
	 *
	 * @return  object  The shout object
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function preRender($shout)
	{
		// params
		$bbcode = $this->params->get('bbcode', 1);
		$date   = $this->params->get('date');

		// Get the date format
		switch ($date)
		{
			case 0:
				$show_date = 'd/m/Y - ';
				$show_time = 'H:i';
				break;
			case 1:
				$show_date = 'D m Y - ';
				$show_time = 'H:i';
				break;
			case 3:
				$show_date = 'm/d/Y - ';
				$show_time = 'H:i';
				break;
			case 4:
				$show_date = 'D j M - ';
				$show_time = 'H:i';
				break;
			case 5:
				$show_date = 'D j M - ';
				$show_time = 'H:i';
				break;
			case 6:
				$show_date = 'Y-m-d ';
				$show_time = 'H:i:s';
				break;
			default:
				$show_date = '';
				$show_time = 'H:i';
				break;
		}

		// Convert to "time elapsed" format. Else convert date when to the logged in user's timezone
		if ($date == 6)
		{
			$shout->when = $this->timeElapsed($shout->when);
		}
		else
		{
			$shout->when = JHtml::_('date', $shout->when, $show_date . $show_time, true);
		}

		$profile     = $this->params->get('profile');
		$shout->name = $this->linkUser($profile, $shout->name, $shout->user_id);

		// Perform Smiley and BBCode filtering if required
		if ($bbcode == 1)
		{
			$shout->msg = $this->bbcodeFilter($shout->msg);
		}
		else
		{
			$shout->msg = nl2br($shout->msg);
		}

		return $shout;
	}

	/**
	 * Renders the message contents with the special variables
	 *
	 * @param   string  $layout  The layout to render for the post (defaults to 'message')
	 *
	 * @return  string  The rendered post contents
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function renderPost($shout, $layout = 'message')
	{
		// Grab the current user object
		$user = JFactory::getUser();

		$shout = $this->preRender($shout);

		// Assemble the data together
		$data = array(
			'post'   => $shout,
			'user'   => $user,
			'title'  => $this->shouttitle($user, $shout->ip),
			'avatar' => $this->getAvatar($this->params->get('avatar', 'none'), $shout->user_id),
			'params' => $this->params,
		);

		// Render the layout
		$options = array(
			'module' => 'mod_shoutbox',
			'client' => 0
		);

		$registry = new JRegistry($options);
		$layout   = new JJShoutboxLayoutFile($layout, null, $registry);
		$layout->addIncludePaths(JPATH_SITE . '/modules/mod_shoutbox/layouts');

		return $layout->render($data);
	}
	
	/**
	 * Renders the modal for an image
	 *
	 * @param   string  $modal   The modal wrapper class
	 * @param   string  $image   The image to be displayed
	 * @param   string  $layout  The layout to render for the post (defaults to 'modal')
	 *
	 * @return  string  The rendered modal
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function renderImageModal($modal, $image, $layout = 'image')
	{
		// Assemble the data together
		$data = array(
			'modal'  => $modal,
			'image'  => $image,
			'params' => $this->params,
		);

		// Render the layout
		$options = array(
			'module' => 'mod_shoutbox',
			'client' => 0
		);

		$registry = new JRegistry($options);
		$layout   = new JJShoutboxLayoutFile($layout, null, $registry);
		$layout->addIncludePaths(JPATH_SITE . '/modules/mod_shoutbox/layouts');

		return $layout->render($data);
	}

	/**
	 * Renders the modal for the shout history
	 *
	 * @param   string  $modal   The modal wrapper class
	 * @param   string  $layout  The layout to render for the post (defaults to 'modal')
	 *
	 * @return  string  The rendered modal
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function renderHistoryModal($shouts, $modal, $title, $layout = 'history')
	{
		$data = array(
			'shouts' => $shouts,
			'modal'  => $modal,
			'title'  => $title,
			'params' => $this->params,
		);

		// Render the layout
		$options = array(
			'module' => 'mod_shoutbox',
			'client' => 0
		);

		$registry = new JRegistry($options);
		$layout   = new JJShoutboxLayoutFile($layout, null, $registry);
		$layout->addIncludePaths(JPATH_SITE . '/modules/mod_shoutbox/layouts');

		return $layout->render($data);
	}
	
	/**
	 * Gets the avatar of a user
	 *
	 * @param   int   $type  The type of avatar.
	 * @param   int   $id    The id of the currently logged in user
	 *
	 * @return  string  An empty string if invalid avatar type. Else the image tag containing the user's avatar
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
			$atts 	= array();

			$url = 'https://www.gravatar.com/avatar/';
			$url .= md5(strtolower(trim($email)));
			$url .= "?s=30&d=mm&r=g";
			$url = '<img src="' . $url . '"';
			foreach ($atts as $key => $val)
			{
				$url .= ' ' . $key . '="' . $val . '"';
			}
			$url .= ' />';
		}
		elseif ($type == 'kunena')
		{
			if (class_exists('KunenaFactory')) 
			{
				$profile = KunenaFactory::getUser($user->id);
				$avatar  = $profile->getAvatarImage('kavatar','profile');

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
			$query = $db->getQuery(true)
			->select($db->qn('avatar'))
			->from($db->qn('#__comprofiler'))
			->where($db->qn('user_id') . ' = ' . $db->q($user->id));

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
				$avatar = JUri::root() . 'images/comprofiler/';

				if (strrpos($result, 'gallery', -strlen($result)) === false)
				{
					$avatar .= 'tn';
				}

				$avatar .= $result;
			}
			else
			{
				$avatar = JUri::root() . 'components/com_comprofiler/plugin/templates/default/images/avatar/tnnophoto_n.png';
			}

			$url = '<img src="' . $avatar . '" height="30" width="30">';
		}
		elseif ($type == 'easyprofile')
		{
			// Easy Profile Link
			require_once JPATH_SITE . '/components/com_jsn/helpers/helper.php';

			$epuser = JsnHelper::getUser($user->id);
			$avatar = $epuser->avatar_mini;

			$url = '<img src="' . $avatar . '" height="30" width="30">';
		}

		return $url;
	}

	/*
	 * Check the timestamp of the shout is still within limits
	 * 
	 * @return  string  The rendered post contents
	 *
	 * @since   7.0.0
	 */	
	public static function checkTimestampAjax()
	{
		$app = JFactory::getApplication();
		$post  = $app->input->post->get('jjshout', array(), 'array');

		// Retrieve required parameter
		if (!isset($post['title']))
		{
			throw new RuntimeException(JText::_('SHOUT_INVALID_AJAX_PARAMS'));
		}

		$helper       = new ModShoutboxHelper($post['title']);
		$helper->ajax = true;

		$id = 0;

		if (isset($post['id']))
		{
			$id = $post['id'];
		}

		// Shout data
		$shoutData = $helper->getTimestampData($id);
		
		// Shout Unix timestamp
		$shoutTimestamp = JFactory::getDate($shoutData[0]->when)->toUnix();

		// Current Unix timestamp
		$currentTimestamp = JFactory::getDate('now')->toUnix();

		// Get difference in time and round to 1 decimal place
		$minutes = round(($currentTimestamp - $shoutTimestamp) / 60, 1);

		$result = null;

		if ($minutes < (int) $helper->getParams()->get('editown-time', 5))
		{
			$htmlOutput = array();

			foreach ($shoutData as $shout)
			{
				$htmlOutput[] = array(
					'id'      => $shout->id,
					'name'    => $shout->name,
					'when'    => JFactory::getDate($shout->when)->toUnix(),
					'ip'      => $shout->ip,
					'msg'     => $shout->msg,
					'user_id' => $shout->user_id,
				);
			}

			$result = json_encode($htmlOutput);
		}

		return $result;
	}

	/*
	 * Pull the shout data based on the ID
	 * 
	 * @param   int     $id  The ID of the shout
	 *
	 * @return  string	The rendered post contents
	 *
	 * @since   7.0.0
	 */	
	private function getTimestampData($id)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
		->select('*')
		->from($db->qn('#__shoutbox'))
		->where($db->qn('id') . ' = ' . (int) $id);

		$db->setQuery($query);

		$result = $db->loadObjectList();

		// If we have an error then we'll create an exception
		if ($db->getErrorNum())
		{
			throw new RuntimeException($db->getErrorMsg(), $db->getErrorNum());
		}

		return $result;
	}

}
