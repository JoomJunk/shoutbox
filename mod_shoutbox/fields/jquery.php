<?php
/**
 * @package     JoomJunk.Shoutbox
 *
 * @copyright   Copyright (C) 2011 - 2013 JoomJunk. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die('Restricted access');

/**
 * Form Field class for JoomJunk.
 * Provides radio button inputs for the jQuery insertation in Joomla 2.5 only
 *
 * @package     JoomJunk.Shoutbox
 * @subpackage  Form
 * @since       2.0
 */
class JFormFieldjQuery extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $type = 'jQuery';

	/**
	 * Method to get the radio button field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   2.0
	 */
	protected function getInput()
	{
		if(version_compare(JVERSION,'3.0.0','ge')) {
			return '<p>'.JText::_('SHOUT_NOJQUERY').'</p>';
		} else {
			$html = array();

			// Initialize some field attributes.
			$class = $this->element['class'] ? ' class="radio ' . (string) $this->element['class'] . '"' : ' class="radio"';

			// Start the radio field output.
			$html[] = '<fieldset id="' . $this->id . '"' . $class . '>';

			// Get the field options.
			$options = $this->getOptions();

			// Build the radio field output.
			foreach ($options as $i => $option)
			{

				// Initialize some option attributes.
				$checked = ((string) $option->value == (string) $this->value) ? ' checked="checked"' : '';
				$class = !empty($option->class) ? ' class="' . $option->class . '"' : '';
				$disabled = !empty($option->disable) ? ' disabled="disabled"' : '';
				$required = !empty($option->required) ? ' required="required" aria-required="true"' : '';

				// Initialize some JavaScript option attributes.
				$onclick = !empty($option->onclick) ? ' onclick="' . $option->onclick . '"' : '';

				$html[] = '<input type="radio" id="' . $this->id . $i . '" name="' . $this->name . '" value="'
					. htmlspecialchars($option->value, ENT_COMPAT, 'UTF-8') . '"' . $checked . $class . $onclick . $disabled . $required . '/>';

				$html[] = '<label for="' . $this->id . $i . '"' . $class . '>'
					. JText::alt($option->text, preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)) . '</label>';
			}

			// End the radio field output.
			$html[] = '</fieldset>';

			return implode($html);
		}
	}

	/**
	 * Method to get the field options for radio buttons.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   2.0
	 */
	protected function getOptions()
	{
		$options = array();

		foreach ($this->element->children() as $option)
		{

			// Only add <option /> elements.
			if ($option->getName() != 'option')
			{
				continue;
			}

			// Create a new option object based on the <option /> element.
			$tmp = JHtml::_(
				'select.option', (string) $option['value'], trim((string) $option), 'value', 'text',
				((string) $option['disabled'] == 'true')
			);

			// Set some option attributes.
			$tmp->class = (string) $option['class'];

			// Set some JavaScript option attributes.
			$tmp->onclick = (string) $option['onclick'];

			// Add the option object to the result set.
			$options[] = $tmp;
		}

		reset($options);

		return $options;
	}
}
