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
            $('#jj_smiley_box img').click(function () {
                var smiley = $(this).attr('alt');
                document.getElementById('message').value += ' ' + smiley + ' ';
            });
        })(jQuery);
    }
}
