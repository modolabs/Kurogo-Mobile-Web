#################
Handling Requests
#################

This section outlines how the framework processes HTTP requests. Generally speaking, this can be
outlined as follows:

#. mod_rewrite sees if the path exists

   * There are only 2 files, and 1 folder in the DocumentRoot: index.php, robots.txt and min. 
   * The min folder contains the minify library for handling consolidated versions of css and javascript assets
   
#. Presuming the file does not exist it will be sent to index.php for processing
#. Certain paths will map to a file in the file system and be returned or a 404 will be returned
#. You can map URLs to other URLs by updating *SITE_DIR/config/site.ini*
#. Otherwise a module based on the path is instantiated and will forward further processing.
   to that module. An exception is raised if the url maps to a module that does not exist


=============
Path patterns
=============

The index.php script will analyze the path for several patterns

* favicon.ico if a favicon.ico file exists in the *CURRENT_THEME/common/images* folder it will be 
  sent to the client
* ga.php will be sent from the lib folder
* requests with a path of *common* or *modules* with a subpath of *images*, *css* or *javascript* are 
  served using the rules according to :ref:`pageandplatform`. This includes examples such as: 
  /modules/home/images/x.png, /common/css/compliant.css, /modules/admin/javascript/admin.js
* requests with a path of /media will be searched for in the indicated subfolder of the 
  current site folder: i.e. /media/file will map to *SITE_DIR*/media/file

If no pattern has been found, the script will then look at the *[urls]* section of *SITE_DIR/config/site.ini*
to see if a url is found. If so, it will redirect to the indicated url. 

All other requests will attempt to load a module based on the first path component of the request. The
contents before the first "/" will refer the *id* of the module, the contents after the slash will be the
page to load. If there is no page specified, the *index* page will be loaded. The script attempts to
instantiate a module  with the corresponding *id* using the *WebModule::factory* method (see :doc:`modules` for 
information on how the module files are located) and includes the page and the contents of the 
$_GET and $_POST variables as parameters. **Note:** the trailing .php for page names is optional.

Examples:

* */home* - will load the *home* module with a page of *index*
* */about/about_site* - will load the *about* module with a page of *about_site*
* */calendars/day?type=events* will load the *calendars* module with a page of *day* and will contain a 
  GET variable named *type* with a value of *events*.
* */news/?section=1* will load the *news* module with a page of *index* and will contain a GET variable
  named *section* with a value of *1*
  
Pages are discussed in more detail in the :doc:`modules` section.

.. _pageandplatform:

=========================
Pagetype & Platform Files
=========================

There are a variety of circumstances when you want to have alternate content be delivered based on the 
characteristics of the device making the request. The :doc:`device detection service <devicedetection>` 
will contain 2 important properties that can influence which content is loaded.

* pagetype - The basic type of device, is *basic*, *touch*, *compliant* or *tablet*.
* platform - The specific device type. Examples include: *android*, *bbplus*, *blackberry*, *computer*, 
  *featurephone*, *iphone*, *palmos*, *spider*, *symbian*, *webos*, *winmo*

For template files, css files, and javascript files, you can load alternate versions for different device
types or platforms. The framework will load the *most specific* file available. For example, if the device 
is an android device it will look for the "index.tpl" file of a module in the following order:

* index-compliant-android.tpl
* index-compliant.tpl
* index.tpl

The same file from a feature phone would include the following files:

* index-basic-featurephone.tpl
* index-basic.tpl
* index.tpl

This allows you to serve different HTML markup, CSS or Javascript depending on the device. By using
CSS ``@import`` and ``{block}`` functions in :doc:`templates <template>` you can  layer utilize 
common structure or style while providing opportunities for device specific differences as needed.