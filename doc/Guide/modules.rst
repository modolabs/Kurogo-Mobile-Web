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


---------------------------
Instantiation and execution
---------------------------

Once a :doc:`request <requests>` has been made, the loading system determines which module to load
and creates and instance of it using the *factory* method. The URL determines which module to load,
which page to assign and any parameters that are included. If there is no page indicated, then the
page will be set to *index*.

After instantiating the object, the *init* method is called. This does several things:

* Assigns the necessary properties including *page*, *args*, *pagetype* and *platform*
* Calls the *initialize()* method that is used for setting up data structures that are used both
  inside a page and outside (for instance in the federated search)
* Calls the *initializeForPage()* method. This method represents the primary entry point for the
  module's logic. Typically the module would handle different logic based on the value of the *page*
  property.
  
Finally the template based on the value of the *templatePage* property is displayed. Initially this 
is set to the page property, but can be overridden if necessary for more dynamic template display.

----------
Properties
----------

Most of the properties used in the WebModule object exist merely to maintain state and should not be
directly referenced, but rather use an accessor method to ensure future compatibility. There are some
properties that you will need to use if creating your own module. These include:

Values the module developer should set in the class declaration:

* *id* (string) - This property should be set to the same name and capitalization as the module directory. 
  This property **must** be set by all modules. 
* *configModule* (string) - This property only needs to be set if you are :ref:`copying a module <copy-module>`.
  It should be set to the url/config folder of the copied module.

Values set by the parent class:

* *page* (string) - This property is set when the module initializes and represents the current page the 
  user is viewing (based on the :doc:`request <requests>`). 
* *pagetype* (string) - contains the pagetype property used in :doc:`device detection <devicedetection>`
* *platform* (string) - contains the platform property used in :doc:`device detection <devicedetection>`

--------------
Initialization
--------------

* *WebModule::factory(string $id, string $page, array $args)* - This static method is called by *index.php* to
  setup the module behavior. It will pass the page to load as well as the arguments that part of the 
  request. In order to separate built-in modules from site specific modules, this method will search multiple 
  locations for the module. It is important that the name of the class matches the name of the file. 

  * SITE_DIR/app/modules/example/ExampleWebModule.php 
  * SITE_DIR/app/modules/example/SiteExampleWebModule.php 
  * app/modules/example/ExampleModule.php 
  
  
  

==============
Methods to use
==============

There are 90 methods in the WebModule object. Many of them are used internally and don't require any discussion.
There are several methods that you should be aware of when developing new modules

---------
Accessors
---------

* *getArg($key, $default)* - Retrieves an argument sent via GET/POST, if the *$key* is not present, then
  it will return the value specified in *$default*

.. _modules_configuration:

-------------
Configuration
-------------

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

-------------
User Sessions
-------------

* *isLoggedIn()* returns whether a user is logged in or not (see :doc:`authentication`)
* *getUser()*  returns a User object of the current user (or AnonymousUser if the user is not logged in)

-------
Setters
-------

* *setPageTitle($title)* - Sets the page title for this page. Normally this value comes from the *SITE_DIR/config/page/MODULE.ini*
  file, but you can use this method to set it dynamically.
* *setBreadcrumbTitle($title)* - Sets the breadcrumb title for this page. Normally this value comes from the *SITE_DIR/config/page/MODULE.ini*
  file, but you can use this method to set it dynamically.
* *setBreadcrumbLongTitle($title)* - Sets the breadcrumb long title for this page. Normally this value comes from the *SITE_DIR/config/page/MODULE.ini*
  file, but you can use this method to set it dynamically.
* *setPageTitles($title)* - Sets all 3 titles (pageTitle, breadcrumbTitle and breadcrumbLongTitle) to the same value
* *setTemplatePage($page)* - Sets the name of the page template file to use. Normally the template is derived from the url, but you can
  use this method to set it dynamically. This will cause $page.tpl to be loaded.

-------
Actions
-------

* *redirectToModule($id, $page, $args)* - This method will redirect to another module. The *id* parameter
  is the id of the module to redirect to. The *page* parameter is a string to the destination page. 
  *args* is an associative array of arguments to pass to the page.
* *redirectTo($page, $args, $preserveBreadcrumbs)* - This method will redirect to another page in the module.
  The *page* parameter is a string to the destination page. *args* is an associative array of arguments
  to pass to the page. *preserveBreadcrumbs* is a boolean (default false) whether to add the entry
  to the list of breadcrumbs or start a new series.
* *setRefresh($time)* - Setting this will add a HTTP refresh tag to reload the page after $time seconds.
* *setCacheMaxAge($age)* - Setting this will update the cache headers to allow clients to cache the page after
  $age seconds. Set to 0 to disable caching. Caching is automatically disabled when authentication is enabled.
  
  
----
URLs
----
* *buildBreadcrumbURL($page, $args, $addBreadcrumb)* - This method will return a url to another page in the module.
  The *page* parameter is a string to the destination page. *args* is an associative array of arguments
  to pass to the page. *addBreadcrumb* is a boolean (default true) whether to add the entry
  to the list of breadcrumbs or start a new series.
  
------
Output
------

* *assign(string $var, mixed $value)* - Assigns a variable to the template. In order to use variable 
  values in your template files, you must assign them in this manner.
* *loadPageConfigFile($name, $keyName)* - Loads a configuration file named *page-{name}.ini* located in the 
  *config/MODULEID/* folder and assigns the values to the template. 
* *setAutoPhoneNumberDetection($bool)* - Turns on/off auto phone number detection (for devices that
  support it). By default phone numbers are automatically detected by certain devices  
* *addInlineCSS($inlineCSS)* - Adds a block of inline CSS to the page. This should be used sparingly as
  CSS files can be cached by the browser. This would be necessary if the css would need to be dynamic
* *addInternalCSS($path)* - Adds a css file that is located on the server. This would typically be used to
  load css files dynamically. The URL might be in the format "/modules/moduleID/css/cssfile.css". URLs
  should ALWAYS be referred using a leading slash, even if the site is located in a subfolder. The 
  template engine handles creating the full path
* *addExternalCSS($url)* - Adds a reference to a CSS file located externally use a full http:// url
* *addInlineJavascript($inlineJavascript)* - Similar to addInlineCSS except for javascript
* *addInlineJavascriptFooter($inlineJavascript)* - Similar to addInlineJavascript except that it will load the
  javascript at the bottom of the page. 
* *addInternalJavascript($path)* - Similar to addInternalCSS except for javascript
* *addExternalJavascript($url)* - Similar to addExternalCSS except for javascript

=============
The Help Page
=============

There is a page called *help* that has special meaning in Kurogo. For each module, you can define
a string in the *strings* section of the *module.ini* file named *help* that will allow you to provide
a help text for end users. If this value is present then a help link will show up on the page and
this will link to the help page containing this text.

.. code-block:: ini

  [module]
  title = "Module Name"
  disabled = 0
  protected = 0
  search = 1
  secure = 0

  [strings]
  help[] = "This module provides services related to lorem ipsum"
  help[] = "Additional help entries indicate additional paragraphs"
  help[] = "You can have as many paragraphs as you need"

===================
Methods to override
===================
* *initialize* - This method is called first when the module is instantiated. It should contain general
  initialization code. If your module provides federated search capabilities than you can use this method
  to properly setup any data sources.
* *initializeForPage* - This method is called when viewing a page. It represents the main logic
  branch.

* *linkForItem($object, $options=null)*
* *linkForValue($value, Module $callingModule, KurogoObject $otherValue=null)*
* *searchItems($searchTerms, $limit=null, $options=null)*

