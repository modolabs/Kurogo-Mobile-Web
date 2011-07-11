######################
Setup and Installation
######################

Kurogo is a PHP web application. As such it must be installed on a web server. As of this version,
the only web server that has been qualified for use is Apache version 2.x on a unix based system.

===================
System Requirements
===================
* Web Servers supported

  * Apache (tested on 2.x)

    * Requires mod_rewrite module
    * Requires .htaccess support (AllowOverride)

  * IIS (tested on 7.5)

    * Requires URL Rewrite Module 2.0 - http://www.iis.net/download/URLRewrite
    * Tested using x86 Non Thread Safe version using FastCGI on IIS.
    
* PHP 5.2 or higher with the following extensions:

  * xml
  * dom
  * json
  * PDO (Used for :doc:`database`)
  * mbstring
  
* Some PHP modules are optional depending on whether you need their backend functionality

  * LDAP
  
.. _installation:

============
Installation
============

Please note that some of these instructions assume that you have basic system and web server 
administration knowledge. For more information please see the documentation for your system and
web server.

#. Extract the files to a location accessible by your web server
#. Set the root of your web server to the *www* folder. (See also :ref:`Using Kurogo in a subfolder of a domain <setup_subfolder>`)
#. (Apache Only) Ensure that .htaccess files are enabled. `AllowOverride <http://httpd.apache.org/docs/2.2/mod/core.html#allowoverride>`_ must be set to at least *FileInfo*. (MAMP on OS X has this option enabled by default)
#. (IIS Only) Ensure that the Application Pool has read access to the entire project folder. In IIS 7.5 this is the *IIS AppPool\DefaultAppPool* user
#. In the *site* directory, make a copy of the *Universitas* folder, including all its contents. The name of this site is up to you, but it would be prudent for it to refer to your site's name. We will refer to this folder as *SITE_FOLDER* 

   * **Critical:** Make sure the web server user (Apache typically: *apache* or *www*, IUSR on IIS) has write access to all the contents *SITE_FOLDER*. 
   
#. In the *config* directory, make a copy of the *kurogo-default.ini* file called *kurogo.ini*
#. Edit the new kurogo.ini file and change the *ACTIVE_SITE* option to match the name of *SITE_FOLDER*
#. (re)Start your webserver and direct your web browser to the server/port that you specified.


.. _setup_subfolder:

=======================================
Using Kurogo in a subfolder of a domain
=======================================

It is possible under certain circumstances to have Kurogo appear to be installed in a URL location other
than the root of a domain. Currently this is supported under the following circumstances:

* Using the Apache webserver in a unix based environment (Linux, Mac OS X, etc)
* Apache is enabled to follow symbolic links (Options FollowSymlinks)
* It is possible to create a symbolic link in the website's 

If these conditions are true, you can create a symbolic link that points to the *www* folder and place
it in your site's root folder.

From the command line, this command would like similar to this:

:kbd:`ln -s /path/to/kurogo/www /path/to/documentroot/mobile`

This would assume you want the subfolder to be named "mobile". You could use any valid folder name you wish

Note: Currently, Kurogo does NOT support being installed under an alias (Apache) or Virtual Folder (IIS).
The method shown above does not work in Windows due to the lack of support for symbolic links.

