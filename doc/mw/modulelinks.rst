############
Links Module
############

The links module presents a list of link items to other pages or sites. You can customize the introductory
statement, the manner in which the links are presented and the links themselves. 

=============
Configuration
=============

There are several configuration values that affect the display of the links module. 

*display_type* - Similar to the :doc:`home module <modulehome>` you can specify either *list* or 
*springboard*.

-------
Strings
-------

*description* - This string will show at the top of the page when viewing the list

-----
Links
-----

Links are editing in the *links.ini* configuration file. Each link is represented by a configuration
section. Within each section, there are 3 possible keys:

* *title* - The title of the link
* *url* - The url of the link
* *icon* - a optional icon that is displayed in the *springboard* display type. These files should 
  be placed in the  *SITE_DIR/themes/default/modules/links/images/compliant* folder. 

.. code-block:: ini

    [0]
    title = "This is link 1"
    url   = "http://example.com/urlforlink1"
    icon  = "link1_icon.png"
    
    [1]
    title = "This is link 2"
    url   = "http://example.com/urlforlink2"
    icon  = "" ; link 2 does not have an icon
    
    [2]
    title = "This is link 3"
    url   = "http://example.com/urlforlink3"
    icon  = "link3_icon.png"
    
---------------------------
Creating groups of links
---------------------------

* NOTE - Creation of link groups is not supported in the admin console at this time.

You can create a group of links in order to organize large amounts of links into categories.
Creating link groups involves the following steps:

#. If it does not exist, create a file named *SITE_DIR/config/links/links-groups.ini*
#. Add a section to links-groups.ini with a short name of your group. This should be a lowercase 
   alpha numeric value without spaces or special characters
#. This section should contain a "title" option that represents the title of the group. Optionally
   you can include a *description* value that will show at the top of the links list for the group.
   You can also include a display_type to include a different link display type than the main link list.
#. Create a file named *SITE_DIR/config/people/links-groupname.ini* where *groupname* is the short name
   of the group you created in *links-groups.ini*. This file should be formatted like links.ini with
   each entry being a numerically indexed section
#. To use this group, assign it to a entry in *links.ini*. Do not include a url, but rather add 
   a value *group* with a value of the short name of the group. You can optionally add a title that will
   be used instead of the group title indicated in *links-groups.ini*
  
This is an example *SITE_DIR/config/people/links-groups.ini*. Each group is a section that contains title (and optional description).
You can have any number of groups::

  [admissions]
  title = "Admissions"

*SITE_DIR/config/people/contacts-admissions.ini*. This is an example file for the *admissions* group. It is
formatted like the *contacts.ini* file::

  [0]
  title    = "Admissions Main Number"
  subtitle = "(617-555-0001)"
  url      = "tel:6175550001"
  class    = "phone"

  [1]
  title    = "Admissions Hotline"
  subtitle = "(617-555-0002)"
  url      = "tel:6175550002"
  class    = "phone"

*SITE_DIR/config/people/contacts.ini*. Include a *group* value to show a group, do not include a *url* value::

  [0]
  title    = "Static Entry 1"
  subtitle = "(617-555-0001)"
  url      = "tel:6175550001"
  class    = "phone"

  [1]
  title    = "Admissions"
  group    = "admissions"
