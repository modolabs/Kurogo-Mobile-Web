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

============================================
Setting the active-site from the environment
============================================

There are some cases where there is a need to host multiple Kurogo sites from the same 
webserver, but where one cannot use MultiSite mode due to its dependency on subpaths. 
An example is serving each Kurogo site under a different host-name or domain name, 
e.g. students.example.edu and faculty.example.edu.

To accomplish this switching, Kurogo supports the specification of the ``ACTIVE_SITE`` 
parameter via an environmental variable set by your webserver. Kurogo will only look for the ``ACTIVE_SITE`` in the environment when *not* in MultiSite mode and when the ``ACTIVE_SITE`` parameter has no value.

Example ``kurogo.ini``:

.. code-block:: ini

	[kurogo]
	MULTI_SITE = 0
	ACTIVE_SITE = ""

The ``ACTIVE_SITE`` environmental parameter can be set in a number of ways, but one of
the easiest is in your Apache configuration:

::
	
	<VirtualHost *:80>
		ServerName students.example.edu
		DocumentRoot /var/www/kurogo/www/
		
		SetEnv ACTIVE_SITE Students
	</VirtualHost>

	<VirtualHost *:80>
		ServerName faculty.example.edu
		DocumentRoot /var/www/kurogo/www/
		
		SetEnv ACTIVE_SITE Faculty
	</VirtualHost>