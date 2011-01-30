#############
Configuration
#############

The Kurogo framework requires very little setup to operate initially. For a production system, 
however, you are going to want to be familiar with many of the site and module options that can 
affect file locations, debugging information and module behavior. 

All of the site's configuration is controlled using .ini files. You can either edit these files 
manually or use the :ref:`admin-module` to edit most of these values. It is the goal of the 
framework to have all options be able to be administered using the :ref:`admin-module`, however, at this
time there are some options that have not been exposed. Please keep track of the framework's progress
in this regard.

=======================
Structure of .ini Files
=======================

Most developers and administrators should find the structure of .ini files familiar. For a complete
explanation on ini files, see the documentation for the `parse_ini_file() <http://php.net/manual/en/function.parse-ini-file.php>`_
function in the PHP manual.

----------
Properties
----------

The basic element contained in an INI file is the property. Every property has a name and a value, 
delimited by an equals sign (=). The name appears to the left of the equals sign. Strings should be
enclosed in double quote marks. Constants can be included outside quote marks. A unique feature of the
framework allows you to reference other values in included ini files by using braces {} around the
key name to include. 

.. code-block:: ini

    key1="value"
    key2=CONSTANT
    key3=ANOTHER_CONSTANT "value"
    key4="Using value {key3}"
    
--------    
Sections
--------

Properties may be grouped into arbitrarily named sections. The section name appears on a line by itself, 
in square brackets ([ and ]). All properties after the section declaration are associated with that 
section. There is no explicit "end of section" delimiter; sections end at the next section declaration, 
or the end of the file. Sections may not be nested.

.. code-block:: ini

    [section]
    
--------    
Comments
--------

Semicolons (;) indicate the start of a comment. Comments continue to the end of the line. 
Everything between the semicolon and the End of Line is ignored.

.. code-block:: ini
    
    ; comment text
    
===================
Configuration files
===================

When running a module, the following config files are loaded automatically:

* *config/config.ini* The framework config file. It's primary role is to indicate the active site and
  configuration mode
* *SITE_DIR/config/config.ini* - The site configuration file. It contains properties shared by all
  modules and sets up the basic environment
* *SITE_DIR/config/strings.ini* - Strings table. Includes various strings used throughout the site
* *SITE_DIR/config/module/MODULEID.ini* - Basic configuration file for the current module. Specifies properties
  regarding the module including disabled status, protected, secure and authorization. Also includes
  any unique module configurable parameters
* *SITE_DIR/config/page/MODULEID.ini* - Page titles configuration for the current module. 


Other modules may also load files from the *SITE_DIR/config/feeds* folder for external data configuration,
and *SITE_DIR/config/web* folder for specific configuration for module output and formatting.

-----------
Local Files
-----------

The framework supports overriding configuration files for local server customization. Unless
the configuration value *CONFIG_IGNORE_LOCAL* (defined in *config/config.ini*) is set to 1, the
framework will also load files with a -local in the file name for each configuration file loaded.
I.e. *SITE_DIR/config/config.ini* can be overridden with *SITE_DIR/config/config-local.ini*. 
*SITE_DIR/config/module/home.ini* can be overridden with *SITE_DIR/config/module/home-local.ini*.
It is **not** necessary to duplicate the entire file. Only the values that are different need to be 
in the -local file. It could also include additional values that are not present in the base config.

These files are ignored by the git version control system and are an excellent place to put sensitive
file paths or credentials that should not be part of a public source code repository. It can
also aid in deployment since your development machine may use different settings than a production
server.

If *CONFIG_IGNORE_LOCAL* is set to 1, then -local files will be ignored. This is useful if you do
not use them and may slightly improve performance.

------------------
Configuration Mode
------------------

In addition to -local files. There is also an option to include configuration override files by
specifying a mode string. This string is like -local but can be set to any value. This will allow
you to create multiple versions of configuration files, with slightly different versions of certain
values and instantly switch between them. This option is set in the *CONFIG_MODE* value of *config/config.ini*
These files are not ignored by git.

One use of this would be to create development and production versions of some of your configuration files. 
You can have *SITE_DIR/config-development.ini* and *SITE_DIR/config-production.ini* with differing
values for debugging. Then you can set *CONFIG_MODE* to **development** or **production**. If *CONFIG_MODE*
is empty (the default), than no files will be searched. Another example would be to include authorization values
for certain modules in a production environment. 

Keep in mind that this setting is independent of -local files. -local files will override any option
presuming *CONFIG_IGNORE_LOCAL* is not enabled. 

-------------------------------
Retrieving Configuration Values
-------------------------------

There are several methods in the :doc:`Module object <modules>` for retrieving values from configuration files:

* getSiteVar - Retrieves a single value from the main site configuration
* getSiteSection - Retrieves a section (as an array or key=>values) from the main site configuration
* getModuleVar - Retrieves a single value from the module configuration
* getModuleSection - Retrieves a section (as an array or key=>values) from the module configuration

==================
Site Configuration
==================

The *SITE_DIR/config/config.ini* file configures the basic site configuration. It is broken
up into several sections

----------------------------
Error handling and debugging
----------------------------

The properties in this section are used during development. Most of them are boolean values (0 is off, 1 is on)

* *DEVICE_DEBUG* - When the framework is running in device debugging mode, you can prepend any framework 
  url with "device/[PAGETYPE]-[PLATFORM]/" or "device/[PAGETYPE]/" to see that version of the page in 
  your browser.  So for example "/device/basic/about/" will show the basic version of the About 
  module's index page.
* *MINIFY_DEBUG* - When Minify debugging is turned on, Minify adds comments to help with locating the 
  actual file associated with a given line.
* *DATA_DEBUG* - Data debugging enables logging and certain output to debug data controller connections. 
  When turned on, it will log url requests in the error log.
* *DEVICE_DETECTION_DEBUG* - Show the device detection info in the footer
* *PRODUCTION_ERROR_HANDLER_ENABLED* - The production error handler will email exceptions to the DEVELOPER_EMAIL
  address. You should treat exceptions as extraordinary situations that should normally not occur in production
  environments.
* *DEVELOPER_EMAIL* - an email address to send exception notices. At this time, it uses the php *mail()* 
  function so it may not be compatible with all environments.

You should turn the _DEBUG options to off in a production environment and enable the Production Error Handler
with an appropriate developer email address. 

-------------
Site settings
-------------

* *LOCAL_TIMEZONE* - Set this to your environment's time zone. See http://php.net/manual/en/timezones.php
  for a list of valid time zones
* *LOCAL_AREA_CODE* - Set this to your environment's primary area code

---------
Analytics
---------

* *GOOGLE_ANALYTICS_ID* - set this to your google analytics id and the framework will utilize the google 
  analytics server
* *PAGE_VIEWS_TABLE* - Used by the stats module to store page view summaries

..
    * *API_STATS_TABLE* - Used by the stats module to store API request summaries

--------------
Temp Directory
--------------
* *TMP_DIR* - This should be set to your system's temporary directory (usually /tmp)

------
Themes
------
* *ACTIVE_THEME* - This is set to the active theme. It should be a valid folder inside the *SITE_DIR/themes* 
  directory. 
  
  
----------------------------------
URL Rewriting and the default page
----------------------------------

In the **[urls]** section you can put a series of values that allow you to map a url to another. Typically
this would be if you want to map a module's url to several possible values, perhaps to maintain 
historical bookmarks. The entered url will be redirected to the value you specify. For example:

* **directory = people** would map the url */directory* to */people* (i.e. the people module)

Take care that you do not create infinite redirect loops.

There is a special case for the *DEFAULT* url. This is the module that is loaded when users enter your
site without a module name (i.e. the root of your site). You can configure this to show a different
module depending on the type of device/platform. In the initial setting, users browsing your site
from a computer will be presented with the **info** module and users browsing your site from a mobile
device will be shown the **home** module. 

The default option will look for the most specific value when determining which default page to show.
You can create entries like such (in uppercase)

    * *DEFAULT-PAGETYPE-PLATFORM* - matches the specific pagetype/platform combination. like *DEFAULT-COMPLIANT-COMPUTER*
      or *DEFAULT-TOUCH-BLACKBERRY*.
    * *DEFAULT-PAGETYPE* - matches all the devices from a particular pagetype. Like *DEFAULT-COMPLIANT* or
      *DEFAULT-BASIC*
    * *DEFAULT* will match any device if a more specific entry is not found
    
This allows you to customize the front door experience for your users.

----------------
Device Detection
----------------

* *MOBI_SERVICE_URL* - Set this to the URL of the :doc:`device detection <devicedetection>` server.

  * https://modolabs-device-test.appspot.com/api/
  * https://modolabs-device.appspot.com/api/

-------
Cookies
-------
* *MODULE_ORDER_COOKIE_LIFESPAN* - How long (in seconds) to remember the module order customization. In production
  sites this should be set to a long time, like 15552000 (180 days)
* *LAYOUT_COOKIE_LIFESPAN* = How long to remember the device detection results for pagetype and platform.
  In production sites this should be set to a long time, like 1209600 (14 days)

--------
Database
--------

The main database connection can be used by a variety of modules for storing and retrieving values.

* *DB_DEBUG* - When on, queries are logged and errors are shown on the browser. You should turn this
  off for production sites or you risk exposing SQL queries when there is a database error.
* *DB_TYPE* - The database system currently supports 2 types of connections *mysql* or *sqlite* through PDO
* *DB_HOST* - used by db systems that are hosted on a server
* *DB_USER* - used by db systems that require a user to authenticate
* *DB_PASS* - used by db systems that require a password
* *DB_DBNAME* - - used by db systems that require a database
* *DB_FILE*   = user by db systems the use a file (i.e. sqlite).

--------------
Authentication
--------------
* *AUTHENTICATION_ENABLED* - Set to 1 to enable :doc:`authentication <authentication>`

---------
Log Files
---------
..
  * *API_LOG_FILE* - Location of the processed API log file
  * *API_CURRENT_LOG_FILE* - Location of the active API log file
  
* *WEB_LOG_FILE* - Location of the processed page view log file
* *WEB_CURRENT_LOG_FILE* - Location of the active page view log file
* *LOG_DATE_FORMAT* - Date format for log files
* *LOG_DATE_PATTERN* - regex pattern of log dates, should match output from LOG_DATE_FORMAT

================================
Module Visibility and protection
================================

Each module contains an configuration file in *SITE_DIR/config/modules/MODULEID.ini*. This file
contains values common to all modules, as well as module specific values. 

* *title* - The module title. Used in the title bar and other locations
* *disabled* - Whether or not the module is disabled. A disabled module cannot be used by anyone
* *search* - Whether or not the module provides search in the federated search feature.
* *secure* - Whether or not the module requires a secure (https) connection. Configuring secure
  sites is beyond the scope of this document.
* *acl[]*  - a series of access control list entries. See :doc:`authentication`.

It is important to turn on the disabled flag for any modules you do not wish to use. It is *very* 
important to make sure that the *admin* module is either disabled or protected appropriately to prevent
exposure of critically important data and configuration. If you utilize logins you should make sure
the *login* module requires *secure* connections if you have a valid certificate.

===========
Home Screen
===========

The *SITE_DIR/config/module/home.ini* file contains the standard module configuration, but also has
several other keys for controlling the configuration of the home screen.

----------------
Home Screen Type
----------------

.. code-block:: ini

  display_type = "springboard" 

The display type property is a value that controls whether the home screen displays like a grid of 
icons ("springboard") or a list of items ("list"). 

---------------------
Module list and order
---------------------

There are 2 sections *[primary_modules]* and *[secondary_modules]* that indicate which modules are
shown on the home screen.

Each section has a list of values that represent the order of the modules and their labels. The order
of these values affects the order of the modules. Each value is the format:

.. code-block:: ini

    moduleID = "Label"
    
Primary modules can be rearranged and hidden by the user using the *Customize* module, secondary modules
appear smaller, but cannot be rearranged or removed by the user. Keep in mind that even if the entry is
not on the home screen, users can still manually navigate to the url. So if you have a modules that you
do not wish to use, ensure they have been *disabled* in their module configuration file.

=======
Strings
=======

There are a number of strings that are used throughout the framework to identify the site name the organization
it is a part of. These include:

* *SITE_NAME* - The name of the site. Used in the footer and other places. 
* *ORGANIZATION_NAME* - The name of the organization. Used in the about module.
* *COPYRIGHT_LINK* - Link to copyright notice (optional)
* *COPYRIGHT_NOTICE* - Copyright notice 
* *FEEDBACK_EMAIL* - email address where user's can send feedback.

.. _admin-module:

=====================
Administration Module
=====================

In addition to editing these files, you can use the administration module to manage the configuration.
The admin module is located at */admin* and does not have an icon on the home screen. It has several 
sections

-------
Modules 
-------

Use this section to manage the settings for each module. You can edit availability settings (common
to all modules), Strings and Data Feeds (if present), Page titles, and other module specific settings.
For instance, you can mange the home screen icons by editing the Primary and Secondary modules settings
of the home module. You could also edit the content of the About module, or the list of links in the links
module.

------------------
Site configuration
------------------

You can edit all the sections of the *SITE_DIR/config/config.ini* file.

--------------------
String Configuration
--------------------

You can edit all the strings from the *SITE_DIR/config/strings.ini* file.

====================================================
Providing an administration interface to your module
====================================================

In most cases, you can add values to your module's configuration file and they will appear on the
administration page. In some cases, however, you want to provide a custom interface to manage the
settings. There are some guidelines you can follow to have a good default interface as well as methods
you can override to customize it.

* Each value not within a section is displayed along side the basic module settings (disabled, protected, etc)
* The admin module module will call *getModuleItemForKey($key, $value)* for each property for your module. 
  This method should return and array that contains the following keys
  
  * *type* indicates the type of value. Values include: boolean, text, paragraph, radio or select
  * *label* the label to include next to the input control
  * *subtitle* explanation string
  * *options* - used by radio and select types to display possible options as value=>label pairs.
  * For more information see *common/formListItem.tpl*

* It is critical to call parent:: in your implementation of *getModuleItemForKey*
* The admin module will call *getSectionTitleForKey($key)* foreach section in the config file. This
  should return a string that represents the section name in a more human readable format
* The admin module will call *hasFeeds*. You should set your module's hasFeeds property to true
  if your module has configurable data sources.