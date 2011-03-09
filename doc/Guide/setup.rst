######################
Setup and Installation
######################

Kurogo is a PHP web application. As such it must be installed on a web server. As of this version,
the only web server that has been qualified for use is Apache version 2.x on a unix based system.

===================
System Requirements
===================
* Apache Web Server (tested on 2.x)

  * Requires mod_rewrite module
  * Requires .htaccess support (AllowOverride)
    
* PHP 5.2 or higher with the following extensions:

  * xml
  * dom
  * json
  * PDO (SQLite required, also can use MySQL)
  * mbstring
  
     
* Some PHP modules are optional depending on whether you need their backend functionality

  * LDAP
  * curl
  
.. _installation:

============
Installation
============

Please note that some of these instructions assume that you have basic system and web server 
administration knowledge. For more information please see the documentation for your system and
the `Apache Documentation <http://httpd.apache.org/docs/2.2/>`_. For development environments, we
recommend `MAMP <http://mamp.info/>`_ on Mac OS X.

#. Extract the files to a location accessible by your web server
#. Set the DocumentRoot of your web server to the *www* folder.
#. Ensure that .htaccess files are enabled. `AllowOverride <http://httpd.apache.org/docs/2.2/mod/core.html#allowoverride>`_ must be set to at least *FileInfo*.
#. In the *site* directory, make a copy of the *Universitas* folder, including all its contents. The name of this site is up to you, but it would be prudent for it to refer to your site's name. We will refer to this folder as *SITE_FOLDER* 

   * **Critical:** Make sure the web server user (typically *apache* or *www*) has write access to all the contents *SITE_FOLDER*. 
   
#. In the *config* directory, make a copy of the *config-default.ini* file called *config.ini*
#. Edit the new config.ini file and change the *ACTIVE_SITE* option to match the name of *SITE_FOLDER*
#. (re)Start your webserver and direct your web browser to the server/port that you specified.

====================
What Could Go Wrong?
====================

TBA.....
