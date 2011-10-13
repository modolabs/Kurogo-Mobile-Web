####################
The WebModule Object
####################

The main additional functionality provided by WebModule is the logic to 
initialize the template system.

==========
Properties
==========

Most of the properties used in the WebModule object exist merely to maintain state and should not be
directly referenced, but rather use an accessor method to ensure future compatibility. There are some
properties that you will need to use if creating your own module. These include:

Values the module developer should set in the class declaration:

* *id* (string) - This property should be set to the same name and capitalization as the module directory. 
  This property **must** be set by all modules. 

Values set by the parent class:

* *page* (string) - This property is set when the module initializes and represents the current page the 
  user is viewing (based on the :doc:`request <requests>`). 
* *pagetype* (string) - contains the pagetype property used in :doc:`device detection <devicedetection>`
* *platform* (string) - contains the platform property used in :doc:`device detection <devicedetection>`

==============
Initialization
==============

* *WebModule::factory(string $id, string $page, array $args)* - This static method is called by *index.php* to
  setup the module behavior. It will pass the page to load as well as the arguments that part of the 
  request. In order to separate built-in modules from site specific modules, this method will search multiple 
  locations for the module. It is important that the name of the class matches the name of the file. 

  * SITE_DIR/app/modules/example/ExampleWebModule.php 
  * SITE_DIR/app/modules/example/SiteExampleWebModule.php 
  * app/modules/example/ExampleModule.php 

===============
Methods to Use
===============

-----
Pages
-----

The following methods handle the templates and titles for pages

* *setTemplatePage($page)* - Sets the name of the page template file to use. Normally the template is derived from the url, but you can
  use this method to set it dynamically. This will cause $page.tpl to be loaded.
* *setPageTitle($title)* - Sets the page title for this page. Normally this value comes from the *SITE_DIR/config/MODULE/pages.ini*
  file, but you can use this method to set it dynamically.
* *setBreadcrumbTitle($title)* - Sets the breadcrumb title for this page. Normally this value comes from the *SITE_DIR/config/MODULE/pages.ini*
  file, but you can use this method to set it dynamically.
* *setBreadcrumbLongTitle($title)* - Sets the breadcrumb long title for this page. Normally this value comes from the *SITE_DIR/config/MODULE/pages.ini*
  file, but you can use this method to set it dynamically.
* *setPageTitles($title)* - Sets all 3 titles (pageTitle, breadcrumbTitle and breadcrumbLongTitle) to the same value

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

===================
Methods to override
===================

* *initializeForPage* - This method is called when viewing a page. It represents the main logic
  branch. All modules will have this code.
* *initialize* - This method is called first when the module is instantiated. It should contain general
  initialization code. If your module provides federated search capabilities than you can use this method
  to properly setup any data sources. It is not needed in all cases.
* *searchItems($searchTerms, $limit=null, $options=null)* - This method is called by other modules 
  (including the default federated search implementation) to retrieve a list of items that meet the
  included search terms. A limit value will be passed that will include a maximum number of items to
  return (or null if there is no limit). There is also an optional associative array that is sent that
  contain options specific to that module. The federated search implementation will add a "federatedSearch"=>true
  value to allow this method to behave specifically for this situation. This method should return an
  array of objects the conform to the KurogoObject interface. 
* *linkForItem($object, $options=null)* - This method should return an array suitable for showing in
  a list item. This would include items such as *title* and *url*. The options array may be used to
  include other information
* *linkForValue($value, Module $callingModule, KurogoObject $otherValue=null)* - This method is used
  to format a value in another module. It is mostly used by subclasses of the standard module to perform
  site specific formatting or linking. The call includes the calling module and an optional object that
  may contain other values. This allows your implementation to consider all values of the object when
  building the link. This function should return an array that is suitable for a list item, including
  *title* and *url* values. The default implementation uses the value as the title and uses a url like
  *moduleID/search?filter=value*.

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
  
.. _dynamic_nav_data:

===============================
Dynamic Home Screen Information
===============================

The :doc:`Home Module <modulehome>` is used to show the available modules to the users. In the default
implementation, the list of modules and their titles and images is specified statically in the home/module.ini
file. In this case the information presented on the home screen is always the same.

In some scenarios it may be necessary to have that information be more dynamic. This would permit custom
titles or subtitles, images, and even display based on any conditions that are appropriate. In order
to utilize this you must do the following:

* change *DYNAMIC_MODULE_NAV_DATA* to *1*. This option is normally turned off due to increased overhead
* create a subclass of the module you wish to provide dynamic data. I.e. If you wish to have dynamic data
  for the People module, then create a *SitePeopleWebModule.php* file in *SITE_DIR/app/modules/people* .
  This step is only necessary if you're providing this behavior to included modules.
* Implement the *getModuleNavigationData($moduleNavData)* method. This method will include an associative
  array of information for each module suitable for the *springboard* or *list item* templates. It
  will include keys such as:

  * *title* - The title of the module.
  * *subtitle* - The subtitle of the module. Currently only used in the list view display mode
  * *url* - The url to the module. Defaults to /moduleid. Should only be changed in unusual circumstances
  * *selected* - Whether this module is selected. This is used by the tablet interface since the nav bar
    is persistent.
  * *img* - A URL to the module icon. The default is /modules/home/images/{moduleID}.{$this->imageExt}. 
  * *class* - The CSS class (space delimited) 
  
  Your implementation should alter the values as necessary and return the updated associative array.   
  If you wish the module to be hidden, return FALSE rather than the array.
  
The following is an example of a module that shows a different title based on the time of day, and
will be invisible during the early morning and nighttime hours.
  
.. code-block:: php

    <?php
    
    class MyWebModule
    {
        protected function getModuleNavigationData($moduleNavData) {
            //get the current hour
            $hour = date('H');
        
            if ($hour >= 9 && $hour < 12) {
                //it's between 9 am and noon 
                $moduleNavData['title'] = 'Good Morning';
            } elseif ($hour >=12 && $hour < 18) {
                //it's between noon and 6pm
               $moduleNavData['title'] = 'Good Afternoon';
            } elseif ($hour < 21) {
                //it's between 6pm and 9pm
                $moduleNavData['title'] = 'Good Evening';
            } else {
                //it's in the evening or early morning. make the module invisible
                return false;
            }
            
            //you must return the updated array
            return $moduleNavData;
        }
    }
    
It is very important that any logic you handle in this method complete quickly as this method
is run very frequently and would be run on EVERY page in the tablet interface. It may be useful to
cache information if it is based on external data.

