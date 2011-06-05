#################
Modules
#################

The Kurogo framework is based around modules. Each module provides a distinct set of data and 
services shown to the user. 

====================
The WebModule Object
====================

Each module is a subclass of the WebModule object. Much of the core logic is located within this
class including:

* Initialization of the template system
* Retrieval of configuration and runtime parameters
* Creation of internal URLs
* Authorization

----------
Properties
----------

Most of the properties used in the WebModule object exist merely to maintain state and should not be
directly referenced, but rather use an accessor method to ensure future compatibility. There are some
properties that you will need to use if creating your own module. These include:

* *id* (string) - This property should be set to the same name and capitalization as the module directory. 
  This property **must** be set by all modules. 
* *configModule* (string) - This property should be set to the same name and capitalization as the module directory. 
  If not set, it will use the *id* property. Generally this is only used when you are :ref:`copying a module <copy-module>`
* *moduleName* (string) - This property represents the canonical name of the module and is shown at
  on the nav bar. It can be overridden using the configuration file.
* *page* (string) - This property is set when the module initializes and represents the current page the 
  user is viewing (based on the :doc:`request <requests>`). 
* *pagetype* (string) - contains the pagetype property used in :doc:`device detection <devicedetection>`
* *platform* (string) - contains the platform property used in :doc:`device detection <devicedetection>`
* *args* (array) - An associative array of variables (key=>value) imported from the $_GET and $_POST 
  request variables. Use the *getArg($key)* method to retrieve a value in a module rather than
  access this array directly.

-------
Methods
-------

There are 90 methods in the WebModule object. Many of them are used internally and don't require any discussion.
There are several methods that you should be aware of. 

^^^^^^^^^^^^^^
Initialization
^^^^^^^^^^^^^^

* *factory* (string $id, string $page, array $args) - This static method is called by *index.php* to
  setup the module behavior. It will pass the page to load as well as the arguments that part of the 
  request. In order to separate built-in modules from site specific modules, this method will search multiple 
  locations for the module. It is important that the name of the class matches the name of the file. 

  * SITE_DIR/app/modules/example/ExampleWebModule.php 
  * SITE_DIR/app/modules/example/SiteExampleWebModule.php 
  * app/modules/example/ExampleModule.php 
  
* *initialize* - This method is called first when the module is instantiated. It should contain general
  initialization code. If your module provides federated search capabilities than you can use this method
  to properly setup any data sources.
* *initializeForPage* - This method is called when viewing a page. It represents the main logic
  branch.

^^^^^^^^^
Accessors
^^^^^^^^^

* *getArg($key, $default)* - Retrieves an argument sent via GET/POST, if the *$key* is not present, then
  it will return the value specified in *$default*

.. _modules_configuration:

^^^^^^^^^^^^^
Configuration
^^^^^^^^^^^^^

There are a number of methods to load configuration data. Configuration allows you to keep certain details
such as server locations, urls, and other values out of source code. Each module has a folder of configuration
files. The primary configuration data is located in the *module.ini* file. Page data is located in *pages.ini*
Modules can use whatever configuration structure that suits their needs. In many cases, complex data structures
will need to exist in different files. 

You can retrieve values either by key or by entire section (you'll get an array of values). The following methods
exist on the Module object.

* *getModuleVar($key, $section=null, $config='module')* - Gets a required module variable $key. If you specify $section it will only look in that section. Will throw an exception if the value is not present
* *getOptionalModuleVar($key, $default='', $section=null, $config='module')* - Gets an optional module variable $key. If you specify $section it will only look in that section. If it is not present, $default will be used (empty string by default)
* *getModuleSection($section, $config='module')* returns an array of values in a module section.  Will throw an exception if the section is not present
* *getOptionalModuleSection($section, $config='module')* returns an array of values in a module section.  Will return an empty array if the section is not present
* *getModuleSections($config)* - Returns a complete dictionary of sections=>vars=>values for a particular config file. Very handy when you basically want the array structure of an entire file
* *getOptionalModuleSections($config)* - Like getModuleSections(), but if the config file does not exist it will return false

You can also retrieve values from the site configuration (site.ini). These are for values used by all modules. They are
static methods on the Kurogo object.

* *Kurogo::getSiteVar($key, $section=null)* - similar to getModuleVar
* *Kurogo::getOptionalSiteVar($key, $default='', $section=null)* - similar to getOptionalModule Var
* *Kurogo::getSiteSection($section)* - similar to getModuleSection
* *Kurogo::getOptionalSiteSection($section)* similar to getOptionalModuleSection

There are also 2 other methods for getting site strings (strings.ini). 

* *Kurogo::getSiteString($key)* - returns a site string. Will throw an exception if not present
* *Kurogo::getOptionalSiteString($key, $default='')* - returns a site string. Will return $default if not present

^^^^^^^^^^^^^
User Sessions
^^^^^^^^^^^^^

* *isLoggedIn()* returns whether a user is logged in or not (see :doc:`authentication`)
* *getUser()*  returns a User object of the current user (or AnonymousUser if the user is not logged in)

^^^^^^^
Setters
^^^^^^^

* *setPageTitle* - Sets the page title for this page. Normally this value comes from the *SITE_DIR/config/page/MODULE.ini*
  file, but you can use this method to set it dynamically.
* *setBreadcrumbTitle* - Sets the breadcrumb title for this page. Normally this value comes from the *SITE_DIR/config/page/MODULE.ini*
  file, but you can use this method to set it dynamically.
* *setBreadcrumbLongTitle* - Sets the breadcrumb long title for this page. Normally this value comes from the *SITE_DIR/config/page/MODULE.ini*
  file, but you can use this method to set it dynamically.
* *setTemplatePage* - Sets the nane of the page template to use. Normally the template is derived from the url, but you can
  use this method to set it dynamically.

^^^^^^^
Actions
^^^^^^^

* *redirectTo($page, $args, $preserveBreadcrumbs)* - This method will redirect to another page in the module.
  The *page* parameter is a string to the destination page. *args* is an associative array of arguments
  to pass to the page. *preserveBreadcrumbs* is a boolean (default false) whether to add the entry
  to the list of breadcrumbs or start a new series.
  

^^^^^^^^
Template
^^^^^^^^

* *assign(string $var, mixed $value)* - Assigns a variable to the template. In order to use variable 
  values in your template files, you must assign them.
* *loadPageConfigFile($name, $keyName)* - Loads a configuration file named *page-name* located in the 
  *config/MODULEID/* folder and assigns the values to the template. 
* *buildBreadcrumbURL($page, $args, $addBreadcrumb)* - This method will return a url to another page in the module.
  The *page* parameter is a string to the destination page. *args* is an associative array of arguments
  to pass to the page. *addBreadcrumb* is a boolean (default true) whether to add the entry
  to the list of breadcrumbs or start a new series.

