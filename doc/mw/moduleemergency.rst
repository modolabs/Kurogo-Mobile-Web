#################
Emergency Module
#################

The emergency module provides a mobile interface to a site's emergency information. 
The module can display the latest emergency information and a list of emergency contacts.
The data source for this module can come from a drupal server, running emergency drupal module
which can be found in the add-ons at *add-ons/drupal-modules/emergency*, (Currently only
supports Drupal 6).  Alternatively,
a standard RSS feed can be used for the emergency notice, and the contacts list can be 
configured with an ini file.

=================================
Configuring the Server Connection
=================================

In order to use the emergency module, you must first setup the connection to your data.
If you want to display an emergency notice you will need to include a `notice` section
in *config/emergency/feeds.ini*.  In the `notice` section you will need to configure
the url for the emergency notice RSS feed.

* If you are using the add-on emergency drupal module, you can set the BASE_URL to
  "http://YOUR_DRUPAL_SERVER_DOMAIN/emergency-information-v1", where YOUR_DRUPAL_SERVER_DOMAIN.
  Otherwise just set the BASE_URL to the appropriate RSS feed.

If you also want to include emergency contact phone numbers, you will need to include
a `contacts` section in *config/emergency/feeds.ini*

**Configure Contacts List**

Configure contacts list to connect to the drupal emergency module:

* *RETRIEVER_CLASS* = "DrupalContactsDataRetriever"
* *DRUPAL_SERVER_URL* = "http://YOUR_DRUPAL_SERVER_DOMAIN"  
* *FEED_VERSION* = 1

Otherwise you can configure the contacts list directly in an ini file with:  

* *CONTROLLER_CLASS* = "INIFileContactsListRetriever"
* *BASE_URL* must point to the appropriate ini file

The ini file will need a `primary` section for primary contacts and 
a `secondary` section for secondary contacts. Each contact is formatted as follows::

  title[] = "Police"  
  subtitle[] = ""  
  phone[] = "6173332893"  

=======================================
Using Drupal Emergency Module
=======================================

**Installation**

This add on module requires Drupal 6, Drupal 7 is not yet supported.
Follow the standard procedure for installing a drupal module, which is:  

* In order to install this module you must first install the 
  drupal CCK (Content Creation Kit) module and the drupal Views module  

* copy *add-ons/drupal-modules/emergency* into the *sites/all/modules/* directory  

* In the drupal administration panel go to modules then select the "Emergency Info"
  module and click "save configurations". 

**Usage**

To input an emergency notification: create a node of content type "Emergency Notification",
the RSS feed will only show the most recently updated Emergency Notification.

To input emergency contacts: create a node of type "Emergency Contacts" and fill out
your primary and secondary emergency contacts.  Note if you create more than one node
of this type the RSS feed will only show the most recently updated, you will probably
not want to create more than one node of this type, but instead just update one single node
with the most up-to-date contact information.

If the RSS feed generated at *http://YOUR_DRUPAL_SERVER_DOMAIN/emergency-contacts-v1* is
missing the contact information you entered you may need to go to `/admin/user/permissions`
and enable anonymous user for view `field_primary_contacts` and `field_secondary_contacts` 

