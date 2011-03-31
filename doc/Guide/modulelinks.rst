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