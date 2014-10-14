/**
 * @package    JJ_Shoutbox
 * @copyright  Copyright (C) 2011 - 2014 JoomJunk. All rights reserved.
 * @license    GPL v3.0 or later http://www.gnu.org/licenses/gpl-3.0.html
*/

function addSmiley(smiley, id)
{
	// If we are not passed an id, use the default 'jj_message'.
	if (!id)
	{
		id = 'jj_message';
	}

	// Get the position of the user in the text area
	var position = getCurserPosition(id);

	// Get the text area object
	var el = document.getElementById(id);

	// Split the text either side of the cursor
	var strBegin = el.value.substring(0, position);
	var strEnd   = el.value.substring(position);

	// Piece the text back together with the cursor in the midle
	el.value = strBegin + " " + smiley + " " + strEnd;
}

function getCurserPosition(id){
	var el = document.getElementById(id);
	var pos = 0;
	// IE Support
	if (document.selection){
		el.focus ();
		var Sel = document.selection.createRange();
		var SelLength = document.selection.createRange().text.length;
		Sel.moveStart ('character', -el.value.length);
		pos = Sel.text.length - SelLength;
	}
	// Firefox support
	else if (el.selectionStart || el.selectionStart == '0')
		pos = el.selectionStart;

	return pos;
}