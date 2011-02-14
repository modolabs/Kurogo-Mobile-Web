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

Links are described using array syntax. There are 3 keys that each link has: *title*, *url* and *icon*.
For each link you declare the title, url and icon properties. It is important to include the property
even if it is not available (i.e. if there is no icon, just include an empty string)

The title and url represent the link text and url respectively. The icon represents an optional icon
that is displayed in the *springboard* display type. These files should be placed in the 
*SITE_DIR/themes/default/modules/links/images/compliant* folder. You may need to create this folder.

.. code-block:: ini

    [links]
    title[] = "This is link 1"
    url[]   = "http://example.com/urlforlink1"
    icon[]  = "link1_icon.png"
    title[] = "This is link 2"
    url[]   = "http://example.com/urlforlink2"
    icon[]  = "" ; link 2 does not have an icon
    title[] = "This is link 3"
    url[]   = "http://example.com/urlforlink3"
    icon[]  = "link3_icon.png"