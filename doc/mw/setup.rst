######################
Setup and Installation
######################

Kurogo is a PHP web application. As such it must be installed on a web server. In this version, there
are 2 supported web servers.

===================
System Requirements
===================
* Web Servers supported

  * Apache (tested on 2.x)

    * Requires mod_rewrite module
    * Requires .htaccess support (AllowOverride)
    * Subfolder support using symlinks, see :ref:`setup_subfolder`

  * IIS (tested on 7.5)

    * Requires URL Rewrite Module 2.0 - http://www.iis.net/download/URLRewrite
    * Tested using x86 Non Thread Safe version using FastCGI on IIS.
    * Support for virtual folders requires a manual configuration change
    * Experimental subfolder support using Junctions, see :ref:`setup_subfolder`

* PHP 5.2 or higher with the following extensions:

  * zlib
  * xml
  * dom
  * json
  * PDO (Used for :doc:`database`)
  * mbstring
  * Zip (needed if parsing KMZ files)
  
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
   
#. In the root *config* directory, make a copy of the *kurogo-default.ini* file called *kurogo.ini*
#. Edit the new kurogo.ini file and change the *ACTIVE_SITE* option to match the name of *SITE_FOLDER*
#. (re)Start your webserver and direct your web browser to the server/port that you specified.


.. _setup_subfolder:

=======================================
Using Kurogo in a subfolder of a domain
=======================================

It is possible under certain circumstances to have Kurogo appear to be installed in a URL location other
than the root of a domain. There are several approaches that are supported depending on your environment.

* Using symbolic links in a Unix environment
* Using Virtual Folders in IIS (requires manual configuration change)
* Using Junctions in Windows

*Note* that using Apache aliases is NOT supported to do the execution order of aliases and mod_rewrite.

------------------------------------------
Using Symbolic Links in a Unix environment
------------------------------------------

In a unix environment you can place Kurogo in a subpath by using a symbolic link. 
Currently this is supported under the following circumstances:

* Using the Apache webserver in a unix based environment (Linux, Mac OS X, etc)
* Apache is enabled to follow symbolic links (Options FollowSymlinks)

If these conditions are true, you can create a symbolic link that points to the *www* folder and place
it in your site's root folder (or subfolder).

From the command line, this command would be similar to this:

:kbd:`ln -s /path/to/kurogo/www /path/to/documentroot/mobile`

This would assume you want the subfolder to be named "mobile". 
You could use any valid folder name you wish. Kurogo is designed to detect this condition
automatically and will function without further configuration.

----------------------------
Using Virtual Folders in IIS
----------------------------

If you are using the IIS webserver in the Windows environment, you can install Kurogo in a virtual
folder. This permits you to use Kurogo in a path that is not the document root. To use this
setup you should:

* Create a virtual folder and point it to the kurogo *www* folder. 
* In the Kurogo project folder open  *config/kurogo.ini*. If this file does not exist, you should copy kurogo-default.ini to kurogo.ini
* In the *[kurogo]* section, uncomment the *URL_BASE* option and set it to the appropriate path. For example
  if your site is installed at */kurogo* then you should set *URL_BASE="/kurogo"*


--------------------------
Using Junctions in Windows
--------------------------

The following procedure should work in either IIS or Apache, however it is recommended to use Virtual Folders
in IIS

* Ensure you have the Junction program installed on your server. It is distributed by Microsoft, and can be found at the time of this writing at http://technet.microsoft.com/en-us/sysinternals/bb896768
* The junction program should be located in your PATH, in most circumstances this can be attained by copying the junction.exe file to your System Root folder (C:\Windows)
* You can only create junctions between 2 paths *on the same NTFS filesystem*. You cannot create
  junctions between volumes or on volumes that are formatted FAT32.

Execute something similar to the following in a Command Prompt:

:kbd:`junction C:\\path\\to\\documentroot\\mobile C:\\path\\to\\kurogo\\www`

This assumes you want the subfolder to be named "mobile". You could use any valid folder name you wish.

