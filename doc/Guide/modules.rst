#################
Modules
#################

The Kurogo framework is based around modules. Each module provides a distinct set of data and 
services shown to the user. 

=================
The Module Object
=================

Each module is a subclass of the Module object. Much of the core logic is located within this
class including:

* Initialization of the template system
* Retrieval of configuration and runtime parameters
* Creation of internal URLs
* Authorization

----------
Properties
----------

Most of the properties used in the Module object exist merely to maintain state and should not be
directly referenced, but rather use an accessor method to ensure future compatibility. There are some
properties that you will need to use if creating your own module. These include:

* *id* (string) - This property should be set to the same name and capitalization as the module directory. 
  This property **must** be set by all modules. 
* *moduleName* (string) - This property represents the canonical name of the module and is shown at
  on the nav bar. It can be overridden using the configuration file.
* *hasFeeds* (boolean) - used by the :ref:`admin-module` to indicate that the module has configurable
  data feeds. This should be set to true if the module uses feeds.
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

There are 90 methods in the module object. Many of them are used internally and don't require any discussion.
There are several methods that you should be aware of. 

^^^^^^^^^^^^^^
Initialization
^^^^^^^^^^^^^^

* *factory* (string $id, string $page, array $args) - This static method is called by *index.php* to
  setup the module behavior. It will pass the page to load as well as the arguments that part of the 
  request. In order to separate built-in modules from site specific modules, this method will search multiple locations for the module. It is important that the name of the class matches the name of the file. 

  * THEME_DIR/modules/example/ThemeExampleModule.php 
  * SITE_DIR/modules/example/SiteExampleModule.php 
  * MODULES_DIR/example/ExampleModule.php 
  
* *initialize* - This method is executed during the instantiation phase. It allows modules to perform
  initial configuration and setup before use
* *initializeForPage* - This method is called when viewing a page. It represents the main logic
  branch.

^^^^^^^^^
Accessors
^^^^^^^^^

* *getArg($key, $default)* - Retrieves an argument sent via GET/POST, if the *$key* is not present, then
  it will return the value specified in *$default*
* *getSiteVar($key, $log_errors)* - Retrieves a site configuration value (i.e. a value stored in SITE_DIR/config/config.ini)
* *getSiteSection($section, $log_errors)* - Retrieves a site configuration section (i.e. a section stored in SITE_DIR/config/config.ini)
* *getModuleVar($key, $default, $log_errors)* - Retrieves a module configuration value (i.e. a value stored in SITE_DIR/config/module/MODULEID.ini)
* *getModuleSection($section, $log_errors)* - Retrieves a module configuration section (i.e. a section stored in SITE_DIR/config/module/MODULEID.ini)
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
* *loadWebAppConfigFile($name, $keyName)* - Loads a configuration file named *name* located in the 
  *config/web* and assigns the values to the template. 
* *buildBreadcrumbURL($page, $args, $addBreadcrumb)* - This method will return a url to another page in the module.
  The *page* parameter is a string to the destination page. *args* is an associative array of arguments
  to pass to the page. *addBreadcrumb* is a boolean (default true) whether to add the entry
  to the list of breadcrumbs or start a new series.

================
Included Modules
================

.. toctree::
   :maxdepth: 1

   modulehome
   moduleinfo
   modulepeople
   modulecalendar
   modulenews
   modulelinks
   modulecontent
   modulefullweb
   modulecustomize
   moduleabout
   modulelogin
   modulestats
   