# Summary
This is a simple drupal module for entering in emergency information 
(Such as the latest emergency notice, and emergency contact information),
it generates a feed that can be read by the Kurogo emergency module.

# Installation
Follow the standard procedure for installing a drupal module, which is:  

* put the module in the ``sites/all/modules/`` directory  

* In order to install this module you must first install the
drupal CCK (Content Creation Kit) module and the drupal Views module  

* In the drupal administration panel go to  modules then select the "Emergency Info"
module and click "save configurations". 

# Usage
To input an emergency notification: create a node of content type "Emergency Notification",
the RSS feed will only show the most recently updated Emergency Notification.

To input emergency contacts: create a node of type "Emergency Contacts" and fill out
your primary and secondary emergency contacts.  Note if you create more than one node
of this type the RSS feed will only show the most recently updated, you will probably
not want to create more than one node of this type, but instead just update one single node
with the most up-to-date contact information.
