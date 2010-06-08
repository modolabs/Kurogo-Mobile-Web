==============
Emergency Info
==============

index.php has two states:

* Show the status of emergencies on campus.
* Display a list of contact phone numbers for various emergency-related units on campus.

Emergency Status

lib/trunk/rss_services.php provides the class Emergency, an extension
of the class RSS whose RSS data source is at
http://emergency.mit.edu/emergency/rss.php. The contents of the RSS
feed are displayed on the screen.

Contact phone numbers for the Campus Police, MIT Medical, and
Emergency Status are also shown on this page.

Emergency Contacts

A list of emergency phone numbers and associated info is provided in
index.php. The class EmergencyItem provides methods to display this
list on the page.

Unimplemented Features

In previous discussions there were plans to change the appearance of
the Emergency icon on the Home Screen in the case of an
emergency. Currently, a JavaScript function exists that causes the
Emergency icon to blink on Android devices; however, the logic to
determine when the icon blinks does not appear to have been
implemented.
