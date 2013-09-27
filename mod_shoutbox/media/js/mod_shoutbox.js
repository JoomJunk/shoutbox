/**
 * Javascript needed for the shoutbox
 */
// Text counter function
function textCounter(textarea, countdown, maxlimit, alertLimit, WarnLimit) {
    "use strict";
    var textareaid = document.getElementById(textarea);
    if (textareaid.value.length > maxlimit) {
        textareaid.value = textareaid.value.substring(0, maxlimit);
    } else {
        document.getElementById(countdown).innerHTML = (maxlimit - textareaid.value.length) + ' ' + Joomla.JText._('SHOUT_REMAINING');
    }

    if (maxlimit - textareaid.value.length > alertLimit) {
        document.getElementById(countdown).style.color = "Black";
    }
    if (maxlimit - textareaid.value.length <= alertLimit && maxlimit - textareaid.value.length > WarnLimit) {
        document.getElementById(countdown).style.color = "Orange";
    }
    if (maxlimit - textareaid.value.length <= WarnLimit) {
        document.getElementById(countdown).style.color = "Red";
    }
}

// BB Code adding function
function insertBbCode(bbCode) {
    "use strict";
    if (bbCode === 0) {
        (function ($) {
            var message = $('#message').val();
            $('#jj_smiley_box img').click(function () {
                function caretPos() {
                    var el = document.getElementById("message"),
                        pos = 0;
                    if (document.selection) {
                        // IE Support
                        el.focus();
                        var Sel = document.selection.createRange(),
                            SelLength = document.selection.createRange().text.length;
                        Sel.moveStart('character', -el.value.length);
                        pos = Sel.text.length - SelLength;
                    } else if (el.selectionStart || el.selectionStart === '0') {
                        // Firefox support
                        pos = el.selectionStart;
                    }

                    return pos;
                }
                var smiley = $(this).attr('alt'),
                    caretPosition = caretPos(),
                    strBegin = message.substring(0, caretPosition),
                    strEnd   = message.substring(caretPosition);
                $('#message').val(strBegin + " " + smiley + " " + strEnd);
            });
        })(jQuery);
    }
}
