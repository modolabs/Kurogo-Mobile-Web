###############
Writing Modules
###############

The Kurogo framework is based around modules. Each module provides a distinct set of data and 
services shown to the user. 

==================
The Module Object
==================

Each module is a subclass of the Module object. Much of the core logic is 
located within this class including:

* Retrieval of configuration and runtime parameters
* Creation of internal URLs
* Authorization

To make a module available to users on the web (regardless of whether it shows 
up on the home screen), it must subclass the WebModule object. To make its data 
available as a web service to be read by native (e.g. iOS) apps or AJAX 
functionality in the web app, the module must subclass the APIModule object.

Modules for the most part provide a subclass of both WebModule and APIModule, 
though there are many valid reasons a module would subclass one and not the 
other. The Error module for example, which provides a friendly web interface 
to inform users of an error, does not have an API counterpart.

Each module's WebModule and APIModule subclass should generally look similar to 
each other. Details about WebModule and APIModule are in the following pages:

.. toctree::
   :maxdepth: 1

   webmodule
   apimodule

---------------------------
Module life cycle
---------------------------

Once a :doc:`request <requests>` has been made, the loading system determines which module to load
and creates and instance of it using the *factory* method. The URL determines which module to load,
which page to assign and any parameters that are included. 

**The first path component** of the url is the module's *id*, this determines which module is loaded.
The factory method will look for a config folder at *SITE_DIR/config/ID/* and load the *module.ini*
file. It will look for an *id* value in that file and load the module that matches that ID.
If there is no ID property then it will load module with the id as the config folder.

If there is no config folder for that URL then it will look at the value of the CREATE_DEFAULT_CONFIG
value in *site.ini*:

* If it is true then it will attempt to load a module based on that ID and then create the config
  folder automatically if the module is found.
* If it is false (the default) OR if no module can be found with that ID, then it will fail with a module
  not found.

**The second path component**, for WebModule, is the *page*. This will determine 
the code path and template file to load. If there is no page indicated, then 
the page will be set to *index*.

For APIModule, it is the *command*. The command must always be present. The 
single exception to the requisite *id/command* URL format is the request to the
CoreAPIModule, which has a single command (*hello*) and no module ID.

After instantiating the object, the *init* method is called. This does several things:

* Assigns the necessary properties including *page*, *args*, *pagetype* and *platform*
* Calls the *initialize()* method that is used for setting up data structures that are used both
  inside a page and outside (for instance in the federated search)
* In WebModules, the *initializeForPage()* method is called. This method 
  represents the primary entry point for the module's logic. Typically the 
  module would handle different logic based on the value of the *page* property.
* In APIModules, the *initializeForCommand()* method is called. Typically the
  module would handle different logic based on the value of the *command* 
  property.
  
Finally the output is generated as follows. WebModule chooses a template to
display, based on the value of the *templatePage* property. Initially this 
is set to the page property, but can be overridden if necessary for more 
dynamic template display. APIModule creates a JSON response that includes the
module id, API version, command requested, and response payload.

==============
Methods to use
==============

There are many methods in the Module object. Many of them are used internally 
and don't require any discussion. There are several methods that you should be 
aware of when developing new modules. Be sure to see the respective methods for
:doc:`WebModule <webmodule>` and :doc:`APIModule <apimodule>` as well.

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
static methods of the Kurogo object.

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
* *getSession()* returns the Session object of hte current session.




