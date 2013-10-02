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
            
            // click toggle function.
            $('#jjshoutbox .dropdown-toggle').click().toggle(function(){
                $('#jjshoutbox .dropdown-menu').show(); 
            },
            function() {
                 $('#jjshoutbox .dropdown-menu').hide();
            });
            
            
            $('#jj_smiley_box img').click(function () {
                var smiley = $(this).attr('alt');
                document.getElementById('message').value += ' ' + smiley + ' ';
            });
            $(document).ready(function () {
                var box = $("textarea#message");
                $(".jj-bold").click(function () {
                    box.val(box.val() + "[b] [/b]");
                    return false;
                });
                $(".jj-italic").click(function () {
                    box.val(box.val() + "[i] [/i]");
                    return false;
                });
                $(".jj-underline").click(function () {
                    box.val(box.val() + "[u] [/u]");
                    return false;
                });
                $(".jj-link").click(function () {
                    box.val(box.val() + "[url=] [/url]");
                    return false;
                });
            });
        }(jQuery));
    }
}

// Scroll bar function
(function ($) {
    "use strict";
    $(window).load(function () {
        $("#jjshoutboxoutput").mCustomScrollbar({
            theme: "dark-2"
        });
    });
}(jQuery));
