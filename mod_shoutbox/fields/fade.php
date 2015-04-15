<?php
/**
* @package    JJ_Shoutbox
* @copyright  Copyright (C) 2011 - 2015 JoomJunk. All rights reserved.
* @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/


defined('JPATH_PLATFORM') or die;

/**
 * Form Field to hide public and private keys fields if maths question is selected
 *
 * @package     JJ_Shoutbox
 * @since       3.0.0
 */
class ShoutboxFormFieldFade extends JFormField
{
	/**
	 * @var string
	 */
	protected $type = 'fade';

	/**
	 * @return string
	 */
	protected function getLabel()
	{
		$doc = JFactory::getDocument();
		$app = JFactory::getApplication();

		// Import jQuery
		JHtml::_('jquery.framework');

		$js = '		
			jQuery(document).ready(function($) {

				var securityType  = $("#jform_params_securitytype");
				var public  = $("#jform_params_recaptcha_public-lbl").parents(".control-group");
				var private = $("#jform_params_recaptcha_private-lbl").parents(".control-group");
				
				if( securityType.val() == 0 || securityType.val()  == 2 ) {
					public.hide();
					private.hide();
				}

				securityType.on("change", function() {

					var value = this.value;

					if( value == 0 || value == 2 ) {						
						public.fadeOut();
						private.fadeOut();
					}
					else {						
						public.fadeIn();
						private.fadeIn();						
					}

				});
				
				
				
				var nameRequired  	= $("#jform_params_namerequired");
				var genericName 	= $("#jform_params_genericname-lbl").parents(".control-group");
				
				if( nameRequired.val() == 1 ) {
					genericName.hide();
				}

				nameRequired.on("change", function() {

					var value = this.value;

					if( value == 0 ) {						
						genericName.fadeIn();
					}
					else {						
						genericName.fadeOut();						
					}

				});

			});			
		';

		$doc->addScriptDeclaration($js);

		return '<hr>';
	}

	/**
	 * @return mixed
	 */
	protected function getInput()
	{
        return;
	}

}
