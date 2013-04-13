<?php
// no direct access
die('Restricted access');

?>

Changelog
------------

* -> Security Fix
# -> Bug Fix
$ -> Language fix or change
+ -> Addition
^ -> Change
- -> Removed
! -> Note

Version 1.2.2
- Remove Timezone parameter and use the Joomla Global Config Timzone

Version 1.2.1
# Fixes bug when number of swears is set to zero
+ Smiley dropdown introduced

Version 1.2.0
+ Uses native JInput for processing rather than $_POST
+ Use JRequest as fall back in Joomla 2.5 when magic quotes turned on
+ noscript text for people without javascript to warn them of message limit
+ Added in Cross Site Request Forgery (CSRF) anti-spoofing token
+ Option to Link to Profiles of various user management systems
+ Add in mass delete function
+ JLog support for easier debugging
# Change deprecated Joomla 3.0 function getErrorNum() to use exceptions
+ Add ability to show date of post
+ Add new easier maths validation method
+ Documentation Started at https://github.com/wilsonge/shoutbox/wiki
- Joomla 1.5 Support Removed
+ Remove mysqli/mysql requirement
+ Use JDate instead of date()
# Timzone form field typo corrected
- Remove module width parameter
+ Module now responsive

Version 1.1.3

# Issue fixed with line breaks not working

Version 1.1.2

+ Integrated Joomla update feature with 2.5/3.0 version
+ New Database commands in 2.5/3.0 version to allow for non-MYSQL databases to be supported
^ Improved code structure for future AJAX implementation and template overrides
^ Separated Joomla 1.5 and 2.5/3.x versions
-  Remove support for Joomla 1.6 and 1.7

Version 1.1.1

# Fix to solve issue when allow_url_fopen was turned off

Version 1.1.0

+ New Version of Shoutbox for Joomla 3.0

Version 1.0.5

+ Recaptcha integrated into module
^ CSS Improvements
^ Internal changes to help a future implementation of AJAX
^ jQuery text countdown replaced with pure Javascript to increase speed

Version 1.0.4

+ New JQuery Plugin for text countdown
^ Smilies are now added when retrieving items from the shoutbox, to avoid the issue of text limitations in the sql file
! Language files changed to remind users that the sql file has a 250 character limit
# Fixed issue where if a database error wasn't inserting from the language file
+ Added HTML5 Compatability
# Error fixed for swear words having too many parameters
# Corrects a issue with the fixing of backslashes in the name field
+ Combined all modules so that all Joomla versions are compatible in 1 module
# Note about printing the displays is no longer repeated for each shout

Version 1.0.3

# Fix where in MySQL 5 the timestamp(14) is depreciated

Version 1.0.2

# Some new text is added into the language file
# Time Zone Parameter is fixed

Version 1.0.1

# Bug Fixes from version 1.0.1
^ Swear Filter is now a PHP file
^ W3 code validation
+ Additional Parameters including Timezones, Word limit on message lengths
+ Ability to censor posts with too many swear words
+ Java popup to alert users when they've gone over their word limit
+ Administrators can view the IP addresses of users who post
# Fix for error message if database doesn't install properly
# Various bug fixes
