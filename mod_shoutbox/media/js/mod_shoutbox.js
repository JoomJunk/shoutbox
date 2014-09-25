/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2014 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

jQuery(document).ready(function($) {
	
	
	// BBCode and smily functionality
	(function() {
		
		var shoutbox 	= $('#jjshoutbox');
		var smileyBox 	= $('#jj_smiley_box');
		var smileyImg 	= smileyBox.find('img');
		var smileyTog	= $('#jj_btn');
		var textarea 	= shoutbox.find('#jj_message');
		
		var bold		= $('.btn.jj-bold');
		var italic		= $('.btn.jj-italic');
		var underline	= $('.btn.jj-underline');
		var link		= $('.btn.jj-link');
		
		
		smileyImg.on('click', function() {
			var alt = $(this).attr('alt');
			document.getElementById('jj_message').value += ' ' + alt + ' ';
		});		
		bold.on('click', function() {		
			textarea.val(textarea.val() + '[b] [/b]');
			return false;	
		});	
		italic.on('click', function() {	
			textarea.val(textarea.val() + '[i] [/i]');
			return false;		
		});
		underline.on('click', function() {		
			textarea.val(textarea.val() + '[u] [/u]');
			return false;		
		});
		link.on('click', function() {	
			textarea.val(textarea.val() + '[url=] [/url]');
			return false;			
		});
		
		smileyTog.on('click', function(e) {	
			e.preventDefault();
			
			var $self = $(this);
			$self.toggleClass('rotated');
			
			smileyBox.stop(true, false).slideToggle();
		});
		
		
	})();


});