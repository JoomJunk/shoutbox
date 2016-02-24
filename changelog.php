<?php
// No direct access
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

###Wip
+ Added "Elapsed time" option
# Fixed timestamp changing on shout edit
$ Updated French language files (thanks MATsxm)
$ Updated Polish language files (thanks asmax)
^ Shoutbox only refreshes for logged in users
^ Javascript improvements
^ CSS improvements
^ Other general code imporvements


Version 7.0.2
# Fixed drawMathsQuestion executing when it shouldn't
# Fixed PHP fatal error for Kunena profile links when logged out
# Fixed cancel buttons showing for Non-Bootstrap
^ PHP Exception improvements
^ Improved sanity check for showing IP address
^ Other minor improvements anf fixes

Version 7.0.1
# Fixed images sometimes not being inline
# Fixed object [Object] error
# Fixed ReCaptcha being executed if called from 3rd party sources
# Fixed cancel button always showing for Bootstrap 2

Version 7.0.0
+ Added ability for user to edit their shout
+ Added option to set the output box height
+ Added Russian language files
+ Added option for header text colour
+ Added option for shout post text colour
+ Added option for textarea height
+ Added automatic email cloak
+ Added French language file (thanks Dominique)
# Fixed error coming from extract() function
^ CSS improvements

Version 6.0.4
# Fixed maths question not refreshing after submitted post
# Fixed Dutch language not installing

Version 6.0.3
+ Allow inline images
# Fixed smiley dropdown position for UIKit
# Fixed dropdown and modal when no framework used

Version 6.0.2
+ Added option to hide history

Version 6.0.1
# Fixes to smiley dropdown for Bootstrap 3

Version 6.0.0
+ Added option to hide security question for registered users
+ Added shoutbox history/archive
+ Added HTML5 notifications
+ Added IMG tag to BBCode
+ Added insert section for BBCode image and URL
+ Added Bootstrap 3 integration
+ Added ability to add own smilies
^ Made smilies part of the BBCode
^ Moved swear words to repeatable form fields
^ Updated ReCaptcha to v2
^ Maths question improvements to prevent spam
^ Design improvements
^ General code imporvements
# Fixed Mass Delete max value
# Fixed Kunena profile links
$ Some language strings changed for ReCaptcha v2
- Removed support for Joomla 3.3
! Recaptcha keys MUST be updated to v2

Version 5.0.2
+ Show error messages from the response
^ Categorised advanced parameters for ease of view
^ Other minor code changes
- Removed pre-defined language strings

Version 5.0.1
# Fixed delete own post when Kunena Profile links enabled

Version 5.0.0
^ Move messaging system to use JLayouts
^ Improvements to dependency importing
^ Namespaced Javascript to avoid conflicts
+ Added option for users to delete their own shouts
+ Added option to disable character limit
+ Added language strings for untranslated text
# Fixed Avatar width

Version 4.0.3
+ Added German translation (thanks Betteryouthanme)
^ Updates to Polish language files (thanks PLFoxNET)
# Fixed update script for J3.4+
# Fixed issue with message length in UTF-8 languages

Version 4.0.2
# Fixed PHP error when no menu item ID exists
^ Minor code tweaks

Version 4.0.1
# Fixed parameter

Version 4.0.0
+ Added Norwegian language pack (Thanks Johan)
+ Added Community Builder avatar integration
+ Added Popover for BBCode Link example
+ Added option to submit using the Enter key
$ Button and permissions message now language strings
- Removed support for Joomla 2.5

Version 3.1.2
* Fix XSS vunerability where script tags could be placed in the message body

Version 3.1.1
# Fixed BBCode not working
# Fixed name/date overlapping header if too long

Version 3.1.0
+ Added option for required name
+ Added generic name as parameter if none used
+ Added Avatar integration for Gravatar, Kunena and JomSocial
^ Improved error reporting
^ Improved BBCode (now works with highlighted strings)
^ Replaced UI Framework detection with parameter
# Fixed smiley path for multilingual sites
# Fixed shout submits not working when assigned to single menu item

Version 3.0.0
+ Integrated AJAX for submitting and retrieving posts
+ Initially hide smilies with toggle option
+ Added Bootstrap and UIKit styling support
+ Added sound notifications for new shouts
# Fixed Freichat conflict
# Fixed Kunena profile links
^ Enhanced HTML markup
^ Other small PHP enhancements

Version 2.0.2
^ Updated to jQuery 1.11.2
- Removed FreiChat check as this extension conflict is now fixed

Version 2.0.1
- Removed a lot of word from swearwords.php
^ Tweak and cleanup of variables + Javascript

Version 2.0.0
^ Changed form names to avoid conflicts with other extensions such as Kunena
# Fix broken recaptcha library since version 1.3.0
+ Add BBCode functionality

Version 1.4.2
# Another timezone bug fix
^ Updated to jQuery 1.11.1
^ Minor coding tweaks

Version 1.4.1
# Bug when user timezone was different to the default Joomla timezone

Version 1.4.0
^ Name field now is a placeholder and is required
^ Use improved method of getting the POST array introduced in Joomla 3.2

Version 1.3.1
# Name input field default value now a language string

Version 1.3.0
* People without necessary permissions could delete posts
+ Allow template overriding of jQuery no conflict file and CSS file
# Cleanup detection of posts if a shout was submitted
# Only include recaptcha library if parameter turned on
# Incorrect usage of JFolder removed

Version 1.2.6
^ Updated to jQuery 1.11.0
+ Added new date format (yyyy.mm.dd)
^ Refinement of users permissions of who can post
^ script updates

Version 1.2.5
# Fix a bug in the install of version 1.2.4
$ Merge some strings together and use sprintf for some strings

Version 1.2.4
+$ Add type colour form fields to the colour fields
^ Joomla PHPCS fixes
# Fix errors with standard class initialisation
^ Moved all media files into Joomla media folder so they can be overrided by the template


Version 1.2.3
# Update for 1.2.2 using a incorrect url for updating

Version 1.2.2
- Remove Timezone parameter and use the Joomla Global Config Timzone
+ Add in more date options
# Simplify the way styling and jQuery code is added into the head
+ Add in PostgreSQL support
# Fix bug where smilies are not shown in the frontend
# Stop clicking on a smiley changing the URL

Version 1.2.1
# Fixes bug when number of swears is set to zero
+ Smiley dropdown introduced
^ Allows installation of the same version of the shoutbox as currently installed

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
