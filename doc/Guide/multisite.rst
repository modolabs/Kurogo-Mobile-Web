#########
MultiSite
#########

MultiSite is a feature of Kurogo. It allows you to host multiple Kurogo site folders from the
same webserver. Each site will have its own set of configuration files, cache folders, themes and even custom
modules, however they will share the same base Kurogo code. One common application of this
would be to host multiple language translations of your site.

MultiSite works by exposing each site as a subpath of your domain. For instance if you had
2 sites named "en" and "es", then those sites would be accessed in the following manner
(using the home module as an example)

* http://example.com/en/home/
* http://example.com/es/home/

====================
Setting up MultiSite
====================

If you wish to use multi site then kurogo *MUST* be installed at the root of your domain. 
MultiSite will not work properly if you install Kurogo in a subpath of your site.

To setup Multisite, you must first enable it in the config/kurogo.ini file.

* Open up *config/kurogo.ini*. If it does not exists, copy kurogo-default.ini to kurogo.ini
* Set *MULTI_SITE = 1* in the *[kurogo]* section
* Set *DEFAULT_SITE* to the default site you want people to see when they visit your site. 
  For example *DEFAULT_SITE="en"*
* For added security and performance, you can set a series of *ACTIVE_SITES[]* values for
  each site that you wish to expose. 

.. code-block:: ini

    ACTIVE_SITES[] = "en"
    ACTIVE_SITES[] = "es"

This would enable only the *en* and *es* sites on this server.
