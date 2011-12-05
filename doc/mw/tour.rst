#################
Source code tour
#################

This section will give you a tour of the various files and directories in the Kurogo source code
project. Knowing the layout of the project will help you understand some of the decisions behind the
code and where to place your own files so upgrading to newer versions is as seamless as possible.

============
Basic Layout
============

There are several directories located in the root of the Kurogo folder:

**add-ons**
  This directory contains additional scripts or code that can be used to interact with other applications
**app**
  This directory contains the code and :ref:`templates <templates-tour>` for each module provided by Kurogo. This also
  includes shared templates used by every module (including headers and footers). As with the lib 
  folder you should avoid adding or altering these files, but rather put new or altered files in the
  :ref:`site-folder`
**config**
  This directory contains the main configuration files for the entire project. Most notably it contains
  the *kurogo.ini* file which determines the active site.
**doc** (only included in source distribution)
  This directory contains various documentation files including this guide. This guide is built using
  the `Sphinx <http://sphinx.pocoo.org/>`_ documentation system. 
**lib**
  This directory contains libraries that are provided by Kurogo. This includes libraries for data
  retrieval and parsing, authentication, database access and configuration. Generally speaking, only
  libraries provided by Kurogo should be in this directory. You should place your libraries
  in the SITE_FOLDER/lib folder to avoid possible conflict with future project updates.
**site**
  This directory contains an entry for each site. See :ref:`site-folder` for more detail
**www**
  This directory contains the DocumentRoot for the site. It contains the main script :ref:`index.php`
  which handles all incoming requests. It also contains the minify library for delivering optimized
  css and javascript to clients. The .htaccess and web.config files provide the URL redirection
  support for Apache and IIS.
  
================
Case Sensitivity
================

It is important to be mindful of case sensitivity issues when developing and deploying your site. Many
developers use file systems that are not case sensitive. Most servers, however, do use case sensitive 
file systems so it is critical that when defining folder locations and urls that map to files or folders,
that the case used is consistent. This guide aims to highlight situations where the framework
expects certain files or folders to be in a particular case format. It is critical to test your server
in an environment that matches your production environment to discover any case-related problems.
  
=======================
Provided vs. Site files
=======================

As noted in the layout section, there are files provided by Kurogo (app, lib, www) and files
for your use (site). As an open source project, you are certainly welcome to alter files in any way 
that suits your needs. However, be aware that if you alter or add files in the project directories, it
may create conflicts when you attempt to update future versions of Kurogo. There are supported
methods to :doc:`add additional functionality <moduleextend>` to existing code while maintaining upgradability. 

That being said, if you have improvements that others would benefit from, we encourage you to :ref:`submit your
changes <github-submit>` to the project. 

=========
Libraries
=========

The framework utilizes a number of code libraries to modularize and abstract certain processes and 
encourage code reuse. The design goal behind the libraries is to ensure that they operate as generically
as possible so that they can function in a variety of uses and contexts (even if, in practice, they are
currently used in only one context). Nearly all the libraries exist as PHP classes.

--------
Packages
--------

In order to assist developers with including the proper class files, libraries can be grouped into *packages*.
This allows you to include necessary functionality without worrying about which files to include in your
module (use: *Kurogo::includePackage('PackageName')* in your module code). Currently the following packages are available:

* Authentication (included automatically when authentication is enabled)
* Authorization - for connecting to various OAuth based web services
* Cache - classes dealing with in-memory and disk caching
* Calendar - includes classes to deal with calendar data
* Config - classes to deal with configuration files
* DataController - legacy classes dealing with the pre 1.4 DataController class
* DataModel - subclasses of the  :doc:`DataModel <datamodel>` class
* DataParser - subclasses of the  :doc:`DataParser <dataparser>` class
* DataResponse - subclasses of the  :doc:`DataResponse <dataresponse>` class
* DataRetriever - subclasses of the  :doc:`DataRetriever <dataretriever>` class
* DateTime - classes for handling date and time
* db - used when you wish to interact with a database
* Emergency - used by the :doc:`emergency <moduleemergency>` module
* Maps - used by the :doc:`map <modulemap>` module
* People - used by the :doc:`people <modulepeople>` module
* RSS - classes for handling RSS data
* Session - Subclasses of the session object, used for session management
* Video - used by the :doc:`video <modulevideo>` module

--------------------
Core / Support Files
--------------------

* compat - defines several functions that normalize behavior throughout PHP versions
* exceptions - defines exception subclasses and sets up exception handling behavior
* *Kurogo* - a singleton class used to consolidate common operations like initialization, site configuration, and administration. :doc:`See more <kurogoobject>`
* minify - interface between the framework and the included open source minify library
* *DeviceClassifier* - An interface between the framework and the :doc:`Device Detection Service <devicedetection>`
* *deviceData.db* - A SQLite database that contains browser entries used by the internal device detection system.
* *Validator* - A utility class to validate certain types of data

--------------------
Native API Functions
--------------------

These functions deal with the API interface that permits access to certain module functions. These
interfaces are used primarily by the native applications (i.e. iOS) but is also used by certain modules
for AJAX like functionality where supported.

* *APIModule* - The base class for API modules, inherits from Module
* *APIResponse* - A class that encapsulates the common response message for API requests

See :doc:`apimodule` for more information.

-----------------------
External Data Retrieval
-----------------------

See :doc:`dataretrieval` for more information
   
---------------
Database Access
---------------

Kurogo includes a database connection abstraction library to assist in the configuration of database
connections.

* *db* - A database access library based on `PDO <http://php.net/pdo>`_. It includes abstractions for
  MySQL, SQLite, PostgreSQL and MS SQL. This support is dependent on support in your PHP installation. The
  setting up and maintaining of databases and their associated extensions is beyond the scope of this document.
* *SiteDB* - Uses the main database configuration for access.

See :doc:`database` for more information

------------------------------
User Access and Authentication
------------------------------

* *AuthenticationAuthority* - This is the root class for authenticating users, getting user and group
  data. It is designed to be subclassed so each authority can provide the means of actually authenticating
  users, but still maintain a consistent interface for the login module. See :doc:`authentication`
  for more information about the included authorities. 
* *AccessControlList* - A class used by the authorization system to restrict access to modules based on
  user or group membership. This is especially useful for the :ref:`admin-module`.
* *User* - The base class for identifying logged in users
* *UserGroup* - The base class for identifying groups

See :doc:`authentication` for more information

------------------
Session Management
------------------

* *Session* - Handles the saving and restoration of user state. There are 2 current implementation:

  * *SessionFiles* - Save and restore session data using the built in file handler 
  * *SessionDB* - Save and restore session data using a database
  
-------------
Configuration
-------------

* *Config* - An abstract class that stores key/value data and has logic for handling replacement values
  (i.e referencing other keys' values within a value) 
* *ConfigFile* - Provides an interface for reading and writing an ini configuration file
* *ConfigGroup* - Provides an interface for coalescing multiple configuration files to provide a single
  key/value store
* *ModuleConfigFile* - A specific config file class to load module config files.
* *SiteConfig* - A specific ConfigGroup that loads the critical site and project-wide configuration files.

See :doc:`configuration` for more information on configuring Kurogo.

---------------------
Modules and Templates
---------------------

* *Module* - The core class that all modules inherit from. Provides a variety of necessary services
  and behavior to module subclasses. See :doc:`modules`.
* *WebModule* - The core class that all web modules inherit from.
* *HTMLPager* - A support class used to paginate content
* *smarty* - The `Smarty Template System <http://www.smarty.net/>`_
* *TemplateEngine* - An subclass of the smarty object used by the framework

See :doc:`modules` for more information

-----
Other
-----

* *ga* - An implementation google analytics for browsers that don't support javascript

.. _templates-tour:

=====================
Modules and Templates
=====================

Inside the *app* folder you will find folders that contain module and template files

------
Common
------

Inside the *common* folder are template and css files that are used by all modules. Each of these templates
may have several variants for different devices. (see :doc:`template` for detailed information on the 
template system and file naming) A non-exhaustive list of these templates include:

* **footer.tpl** content placed at the bottom of most pages
* **header.tpl** content placed at the top of most pages
* **help.tpl** template used for displaying help pages
* **formList.tpl** template used for showing a list that enables input

  * **formListItem.tpl** template used for an individual form item in a list


* **navlist.tpl** template used for showing items as a list
  
  * **listitem.tpl** template used for an individual item in a list
  
* **pager.tpl** - template for providing pagination for long-form content
* **results.tpl** - template for displaying results in a list
* **search.tpl** - template for presenting a search box
* **share.tpl** - template for presenting a sharing content via social networking
* **springboard** - template for displaying content as a grid of icons
* **tabs.tpl** - template for displaying content in tabs

-------
Modules
-------

The modules folder contains all the modules that are bundled with Kurogo. Each module contains
the PHP code and template files needed for its use. It also can include CSS and Javascript files
that are specific to that module. For more detailed information on module design, please see :doc:`modules`

The naming conventions are very important (especially for case sensitive file systems):

* The folder **must** be lower case and be the same as the url of the module (/about, /home, /links). You
  can create modules at other urls by :ref:`copying the module <copy-module>`
* The folder **must** contain a PHP file named *ModulenameWebModule.php*. If the module is located
  in the *site* folder **and** it extends an existing module then it should be called *SiteModulenameWebModule.php*. 
* The first (and ONLY) letter of the module **must** be capitalized and followed by WebModule.php. 
  
  * **AboutWebModule.php** (NOT aboutwebmodule.php or AboutWebmodule.php)
  * **FullwebWebModule.php** (NOT FullWebModule.php or FullwebWebmodule.php)
  * **SiteNewsWebModule.php** (NOT siteNewsWebModule.php or Sitenewswebmodule.php)

* Template files go into the *templates* folder. There should be a .tpl for each *page* of the module. 
  At minimum there should be an *index.tpl* which represents the default page (unless the module 
  alters that behavior). Each page should be in all lower case.
* If you are overriding a project module you only need to include the pages that you are overriding.
* You may choose to place additional css style sheets in a folder named *css*
* You may choose to place additional javascript scripts in a folder named *javascript*
* You can provide default configuration files in a folder named *config*

It is possible to override an included module's behavior by creating another module in the *site*
folder. For more information, please see :doc:`moduleextend`

.. _site-folder:

===========
Site folder
===========

The site folder contains a series of folders for each *site*. This allows each site to
have specific configuration, design and custom code. At any given time there is only one **active site**.
You can enable the active site in the *config/kurogo.ini* file found in the root Kurogo 
directory. It is important the that case used in naming the folder matches the ACTIVE_SITE
case in the kurogo.ini file.

Multiple site folders exist to assist developers who might be working on different versions of their site,
or who want to refer to the reference implementation. Because only one site can be active, you would
typically have only one site folder in a production environment.

Each site folder contains the following directories:

* *app* - Site specific templates and modules. Inside this folder you will find 2 folders

  * *common* - Site specific common templates and css
  * *modules* - Site specific modules. To promote ease when updating the framework to new versions, it 
    is important that you keep site specific modules in this folder rather than in the root *app/modules*
    folder. If you wish to include your work in Kurogo, please see :doc:`github`. Also see :doc:`moduleextend`.
    
* *cache* - Contains server generated files that are cached for performance. This folder is created 
  as needed, but *must* be writable by the web server process. 
* *config* - Contains the site specific configuration files in .ini format. Many of these files can 
  be managed using the :ref:`admin-module`

  * *site.ini* - The general configuration file that affects all site behavior such as timezone, 
    log file locations, database configuration, and more.
  * *acls.ini* - Site wide :doc:`access control lists <authorization>` 
  * *authentication.ini* - The configuration for user :doc:`authentication`. 
  * *strings.ini* - a configuration file containing strings used by the site
  * Each module's configuration is contained a folder named by its module id. There are several standard
    files for each module:
    
    * module.ini - Settings for disabling, access control, search and module variables and strings
    * feeds.ini - Specifies external data connections
    * pages.ini - Titles for each page
    * Modules may have other config files as needed
  
* *data* - a folder that contains data files meant to be used by the server. Unlike cache folders, these
  files cannot be safely deleted. Examples would include data that is not able to be generated from 
  a web service, SQLite databases, or flat authentication files. It is also possible that certain
  deployments would have nothing in the data folder.
* *lib* - an optional folder that contains code libraries used by site modules. The Kurogo :ref:`autoloader <autoloader>` 
  will discover and find classes and packages in this folder.
* *logs* - Log files
* *themes* - Contains the themes available for this site. Each theme folder contains a *common* and *modules*
  folder that contains the CSS and image assets for the site. See :doc:`template` for more information.

==========
WWW Folder
==========

The files and folders in the www folder represent the DocumentRoot, the base of the site. To keep the
structure clean, all requests are routed through the *index.php* file (the exception is for paths
and folders that already exist, such as min, the minify url). It is important to note that if you create
additional files or folders in the www folder that it may interfere with proper operation of the framework.

.. _index.php:

---------
index.php
---------

The index script is the main controller for the framework. All requests are handled through it using
an .htaccess override and `mod_rewrite <http://httpd.apache.org/docs/2.2/mod/mod_rewrite.html>`_ for Apache or
the `URL Rewrite extension for IIS <http://www.iis.net/download/URLRewrite>`_. The
.htaccess file rewrites all requests to include a $_GET variable *_path* which includes the path requested.
I.e. *http://server/module/page* becomes *http://server/index.php?_page=module/page*. Any additional
data in the $_GET or $_POST variables will be available. For greater detail see :doc:`requests`
