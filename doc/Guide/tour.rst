#################
Source code tour
#################

This section will give you a tour of the various files and directories in the Kurogo source code
directory. Knowing the layout of the project will help you understand some of the decisions behind
code and where to place your own files so upgrading to newer versions is as seamless as possible.

============
Basic Layout
============

There are several directories located in the root of the project folder:

**config**
  This directory contains the main configuration files for the entire project. Most notably it contains
  the main *config.ini* file which determines the active site.
**lib**
  This directory contains libraries that are provided by the project. This includes libraries for data
  retrieval and parsing, authentication, database access and configuration. Generally speaking only
  libraries provided by the project should be in this directory. You should place your libraries
  in the site/lib folder to avoid possible conflict with future project updates.
**site**
  This directory contains an entry for each site. See :ref:`site-folder` for more detail
**templates**
  This directory contains the code and :ref:`templates <templates-tour>` for each module provided by the project. This also
  includes shared templates used by every module (including headers and footers). As with the lib 
  folder you should avoid adding or altering these files, but rather put new or altered files in the
  :ref:`site-folder`
**web**
  This directory contains the DocumentRoot for the site. It contains the main script :ref:`index.php`
  which handles all incoming requests. It also contains the minify library for delivering optimized
  css and javascript to clients
  
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

As noted in the layout section, there are files provided by the project (lib, templates, web) and files
for your use (site). As an open source project, you are certainly welcome to alter files in any way 
that suits your needs. However, be aware that if you alter or add files in the project directories, it
may create conflicts when you attempt to update future versions of the project. There are well known
methods to :doc:`add additional functionality <moduleextend>` to existing code while maintaining upgradability. 

That being said, if you have improvements that others would benefit from, we encourage you to :ref:`submit your
changes <github-submit>` to the project. 

=========
Libraries
=========

The framework utilizes a number of code libraries to modularize and abstract certain processes and 
encourage code reuse. The design goal behind the libraries is to ensure that they operate as generically
as possible so that they can function in a variety of uses and contexts (even if, in practice, they are
currently used in only one context). Nearly all the libraries exist as PHP classes and currently fall
into one of several categories:

--------------------
Core / Support Files
--------------------

* autoloader - Defines a function that finds and loads class files on demand
* compat - defines several functions that normalize behavior throughout PHP versions
* exceptions - defines exception subclasses and sets up exception handling behavior
* initialize - called by :ref:`index.php` to setup the runtime environment
* minify - interface between the framework and the included open source minify library
* *DeviceClassifier* - An interface between the frame work and the :doc:`Device Detection Service <devicedetection>`
* *PageViews* - A class to log and retrieve page view information for statistics
* *Validator* - A utility class to validate certain types of data

-----------------------
External Data Retrieval
-----------------------

The main class is *DataController*. It provides functionality to retrieve URL based data (this could include
both local and remote data), cache this data, and parse it using a subclass of *DataParser* to prepare it
into a structure suitable for use. In its optimal design, a data controller will abstract the details
of building the URL, and return a structure that is normalized, allowing the module code to be as generic
as possible.

Included examples of DataControllers/Parsers include: 

* *RSSDataController* - retrieves a feed of data in RSS/RDF or Atom formats. The corresponding *RSSDataParser* 
  class takes the resulting data and builds a structure of items located in the feed. Also uses 
  the *RSS* class.
* *CalendarDataController* - retrieves a feed of data in ICS format. The corresponding *ICSDataParser*
  class takes the resulting data and builds a structure of events in the feed. Also uses the *ICalendar*
  and *TimeRange* class. The *TrumbaCalendarDataController* is a specific subclass for feeds that 
  utilize the `Trumba <http://www.trumba.com/>`_ calendar service.
* *PeopleController* - access directory/person data. The only included implementation at this time 
  is the *LDAPDataController* which queries information from an LDAP directory. Note this is distinct
  from authenticating users.
   
These classes also use the *DiskCache* class to cache the retrieved data.

---------------
Database Access
---------------

* *db* - A database access library based on `PDO <http://php.net/pdo>`_. It includes abstractions for
  MySQL and SQLite
* *SiteDB* - Uses the main database configuration for access.

------------------------------
User Access and Authentication
------------------------------

* *AuthenticationAuthority* - This is the root class for authenticating users, getting user and group
  data. It is designed to be subclassed so each authority can provide the means of actually authenticating
  users, but still maintain a consistent interface for the login module. See :doc:`authentication`
  for more information about the included authorities. 
* *AccessControlList* - A class used by the authorization system to restrict access to modules based on
  user or group membership. This is especially useful for the :ref:`admin-module`.
* *Session* - Handles the saving and restoration of user state. This is currently implemented using 
  PHP session variables.
* *User* - The base class for identifying logged in users
* *UserGroup* - The base class for identifying groups

-------------
Configuration
-------------

* *Config* - An abstract class that stores key/value data and has logic for handling replacement values
  (i.e referencing other keys' values within a value) 
* *ConfigFile* - Provides an interface for reading and writing an ini configuration file
* *ConfigGroup* - Provides an interface for coalescing multiple configuration files to provide a single
  key/value store
* *SiteConfig* - A specific ConfigGroup that loads the critical site and project-wide configuration files.

---------------------
Modules and Templates
---------------------

* *Module* - The core class that all modules inherit from. Provides a variety of necessary services
  and behavior to module subclasses. See :doc:`modules`.
* *HTMLPager* - A support class used to paginate content
* *smarty* - The `Smarty Template System <http://www.smarty.net/>`_
* *TemplateEngine* - An subclass of the smarty object used by the framework

-----
Other
-----

* *ga* - An implementation google analytics for browsers that don't support javascript

.. _templates-tour:

=====================
Modules and Templates
=====================

Inside the templates folder you will find two folders that contain module and template files

------
Common
------

Inside the common folder are template files that can be used by all modules. Each of these templates
may have several variants for different devices. (see :doc:`template` for detailed information on the 
template system and file naming) A non-exhaustive list of these templates include:

* **footer.tpl** content placed at the bottom of most pages
* **header.tpl** content placed at the top of most pages
* **help.tpl** template used for displaying help pages
* **formList.tpl** template used for showing a list that enables input

  * **formListItem.tpl** template used for an individual form item in a list


* **navlist.tpl** template used for showing items as a list
  
  * **listitem.tpl** template used for an individual item in a list
  
* **pager.tpl** - ?
* **results.tpl** - ?
* **search.tpl** - ?
* **share.tpl** - ?
* **springboard** - ?
* **tabs.tpl** - ?

-------
Modules
-------

The modules folder contains all the modules that are bundled with the project. Each module contains
the PHP code and template files needed for its use. It also can include CSS and Javascript files
that are specific to that module. For more detailed information on module design, please see :doc:`modules`

The naming conventions are very important (especially for case sensitive file systems):

* The folder **must** be lower case and be the same as the url of the module (/about, /home, /links)
* The folder **must** contain a PHP file named *LocationModulenameModule.php*. If the module is located
  in the *site* folder then it should be called *SiteModulenameModule.php*. If the module is located
  in the *theme* folder then it should be called *ThemeModulenameModule.php*. Project modules are
  called *ModulenameModule.php*.
* The first (and ONLY) letter of the module **must** be capitalized and followed by Module.php. 
  
  * **AboutModule.php** (NOT aboutmodule.php or Aboutmodule.php)
  * **FullwebModule.php** (NOT FullWebModule.php or Fullwebmodule.php)
  * **SiteNewsModule.php** (NOT siteNewsModule.php or Sitenewsmodule.php)
  
* There should be a .tpl for each *page* of the module. At minimum there should be an *index.tpl* which 
  represents the default page (unless the module alters that behavior). Each page should be in all lower case
* If you are overriding a project module you only need to include the pages that you are overriding.
* You may choose to place additional css style sheets in a folder named *css*
* You may choose to place additional javascript scripts in a folder named *javascript*

It is possible to override an included module's behavior by creating another module in the *sites*
folder. For more information, please see :doc:`moduleextend`

.. _site-folder:

===========
Site folder
===========

The site folder contains a series of folders for each *site*. This allows each site to
have specific configuration, design and custom code. At any given time there is only one **active site**.
You can enable the active site in the *config/config.ini* file found in the the root of the project 
directory. It is important the that case used in naming the folder matches the ACTIVE_SITE
case in the config.ini file.

Multiple site folders exist to assist developers who might be working on different versions of their site
or who want to refer to the reference implementation. Because only one site can be active, you would
typically have only one site folder in a production environment.

Each site folder contains the following directories:

* *cache* - Contains server generated files that are cached for performance. This folder is created 
  if needed, but must be writable by the web server process. 
* *config* - Contains the site specific configuration files in .ini format. Many of these files can 
  be managed using the :ref:`admin-module`

  * *config.ini* - The general configuration file that affects all site behavior such as timezone, log file locations,
    database configuration, and more
  * *feeds* - a folder containing files for modules that require configuration to access remote data
  * *module* - a folder containing files for each module's basic configuration including enabled, federated
    search, and strings. See :doc:`modules`
  * *page* - a folder containing files for each modules's pages containing title and breadcrumb information
  * *strings.ini* - a configuration file containing strings used by the site
  * *web* - a folder containing files used by modules for page specific configuration 
  
* *data* - a folder that contains data files meant to be used by the server. Unlike cache folders, these
  files cannot be safely deleted. Examples would include data that is not able to be generated from 
  a web service, SQLite databases, or flat authentication files
* *logs* - Log files
* *modules* - Site specific modules. To promote ease when updating the framework to new versions,
  it is usually best if you keep site specific modules in this folder rather than in the *templates/modules*
  folder. If you wish to include your work in the project, please see :doc:`github`. Also see :doc:`moduleextend`.
* *themes* - Contains the themes available for this site. Each theme folder contains a *common* and *modules*
  folder that contains the CSS and image assets for the site. See :doc:`template` for more information.


==========
Web Folder
==========

The files and folders in the web folder represent the DocumentRoot, the base of the site. To keep the
structure clean, all requests are routed through the *index.php* file (the exception is for paths
and folders that already exists, such as min, the minify url). It is important to note that if create
additional files or folders in web folder that it may interfere with proper operation of the framework.

.. _index.php:

---------
index.php
---------

The index script is the main controller for the framework. All requests are handled through it using
an .htaccess override and `mod_rewrite <http://httpd.apache.org/docs/2.2/mod/mod_rewrite.html>`_. The
.htaccess file rewrites all requests to include a $_GET variable *_path* which includes the path requested.
I.e. *http://server/module/page* becomes *http://server/index.php?_page=module/page*. Any additional
data in the $_GET or $_POST variables will be available. For greater detail see :doc:`requests`

