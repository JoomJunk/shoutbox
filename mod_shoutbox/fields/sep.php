<?php
/**
* @package    JJ_Shoutbox
* @copyright  Copyright (C) 2011 - 2018 JoomJunk. All rights reserved.
* @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('JPATH_PLATFORM') or die;
/**
 * Form Field separator for JoomJunk.
 *
 * @package     JJ_Shoutbox
 * @since       5.0.2
 */
class JFormFieldSep extends JFormField
{
	/**
	 * @var string
	 */
	protected $type = 'Sep';

	/**
	 * @return string
	 */
	protected function getLabel()
	{
        JFactory::getDocument()->addStyleDeclaration('.jj-sep { border-bottom:1px solid #eee;font-size:16px;color:#BD362F;margin-top:15px;padding:2px 0;width:100% }');

        $label = JText::_((string)$this->element['label']);
        $css   = (string)$this->element['class'];

        return '<div class="jj-sep ' . $css . '">' . $label . '</div>';
	}

	/**
	 * @return mixed
	 */
	protected function getInput()
	{
        return;
	}
}