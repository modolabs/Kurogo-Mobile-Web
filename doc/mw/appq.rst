####
AppQ
####

*************
What is AppQ?
*************

AppQ is a component of the Kurogo Mobile Framework that allows mobile web modules to be quickly ported to iOS and Android native applications.  AppQ is a hybrid app system where Kurogo Mobile Web modules are embedded in web views inside the native app.

=============
AppQ Features
=============

-----------------------------------
AppQ uses a native navigation stack
----------------------------------- 
* On iOS (and to a lesser extent Android 4.0+) page transitions are very distinctive and difficult to reproduce with web technologies, so AppQ modules will feel more native than other hybrid solutions, providing a better user experience.  
* Because page transitions are handled natively, AppQ modules do not need libraries like jQuery Mobile or Sencha Touch.  These large javascript libraries increase page load time and can make hybrid apps feel more sluggish.

---------------------------------------------------------------------------
AppQ modules can co-exist with native Kurogo-iOS and Kurogo Android modules
---------------------------------------------------------------------------
* By allowing easy mixing and matching of native and AppQ modules, you can concentrate resources on functionality which gets the greatest benefit from a fully native implementation.

--------------------------------------------------------------
AppQ modules use templates customized for each native platform
--------------------------------------------------------------
* AppQ leverages the Kurogo Mobile Web's template inheritance system to allow you to customize your UI elements for each native platform. 
* Stock Kurogo Mobile Web templates are already customized for the native platforms.

---------------------------------------------------------------------------
AppQ modules store CSS, Javascript and images inside the native app
---------------------------------------------------------------------------
* This decreases page load time and network bandwidth use
* By including these files in your native app, the user's "first launch" experience will be faster than a normal mobile web experience
* When you update your mobile web UI, the native apps will download new versions of the module files

===============================================
AppQ 1.0 Technical Capabilities and Limitations
===============================================

Any Kurogo Mobile Web module can become an AppQ module. If you can write it as a web module, it can be an AppQ module.  Like web modules, AppQ modules display live content from the Kurogo Mobile Web server.  AppQ is currently not a good fit if your native app needs to store or cache content for offline viewing.

For version 1.0 the following capabilities and limitations are outlined.

---------------------
What AppQ 1.0 can do:
---------------------
* Anything a Kurogo Mobile Web module can do, including:

  * Module development with HTML, CSS and Javascript technologies
  * Kurogo Mobile Web theming support

* Ability to update module look and feel without releasing new versions of the native apps
* Use custom "native look and feel" assets for common UI elements and built-in Kurogo mobile web templates
* Access to native UI elements:

  * Navigation bar: can set title, back button title and add a refresh content button
  * Alert dialogs
  * Action dialogs (cancel, optional destructive button, and up to 10 other optional buttons)
  * Share dialog (automatically via Smarty share.tpl template)
  * Native mail composition dialog when user taps on a mailto: link
* Basic GPS location as provided by HTML5 web engine (navigator.geolocation)

-----------------------
What AppQ 1.0 can't do:
-----------------------
* Offline storage
  
  * Providing AppQ offline storage similar to what is available on fully native apps is a difficult technical and architectural problem
  * HTML5 offline storage is too small for many use cases
  * Access to native storage mechanisms is a difficult technical and architectural problem on Android

    * On Android the APIs necessary for this (web view cache) were introduced in Ice Cream Sandwich (ICS)
      
      * Currently most devices are on Gingerbread so we cannot require ICS

    * All supported iOS versions can provide this functionality via NSURLCache and CoreData

* Camera access
* NFC access
* Other direct sensor access

*******************************
Preparing a Web Module for AppQ
*******************************

Most modules will work with AppQ with a minimal amount of effort.  However there are a few modifications which may be necessary, especially for complex modules with lots of javascript or conditional UI display.

====================================
Telling AppQ about your module pages
====================================
One of the things AppQ needs to know about your module is which pages it supports.  AppQ pulls this information from your module's pages.ini configuration file.  In order for AppQ to function correctly you need to make sure each page your module supports is listed in pages.ini, even if the page title for that page is the same as the page name.

*pages.ini*

.. code-block:: ini

	[index]
 
	[detail]
	pageTitle = "Detail"
 
	[search]
	pageTitle = "Search Results"
	breadcrumbTitle = "Search"

=======================================
Locating images and other static assets
=======================================
AppQ builds asset zip files of the javascript, css and images used by a web module.  When it searches for these files it loads the templates with all the template variables unset.  However, if your web module only shows certain UI elements when the template variables are set, AppQ will not be able to find those elements.  To work around this, each module can define a custom version of **initializeForPage()** called **initializeForNativeTemplatePage()** which is used by AppQ.  When defining this function you should set your template variables so that all UI elements involving images, CSS and Javascript files are visible.

For example, here is a version of the function for a module which uses the ellipsizer javascript module on its index and search pages and has a share button on its **detail** page:

*MyWebModule.php*

.. code-block:: php
   :linenos:
   
    <?php
    protected function initializeForNativeTemplatePage() {
        // Native template support
        // specify anything that goes into the header or footer here
        // and force the appearance of assets so they get loaded into the template
        switch ($this->page) {
            case 'index':
                // force appearance of section select button
                $this->assign('sections', array(1, 2));
            case 'search':
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                break;
                 
            case 'story':
                $this->assign('shareTitle', $this->getLocalizedString('SHARE_THIS_STORY'));
                $this->assign('shareEmailURL', 'dummy');
                $this->assign('shareRemark',   'dummy');
                $this->assign('storyURL',      'dummy');
        }
    }
		
A second option is to specify the files in an array.  This option is most useful when getting the page to display some assets would require a lot of code:

*MyWebModule.php*

.. code-block:: php
   :linenos:

    <?php
    protected function nativeWebTemplateAssets() {
        return array(
            '/min/g=file:/common/javascript/lib/ellipsizer.js',
            '/common/images/share.png',
            '/common/images/button-email.png',
            '/common/images/button-facebook.png',
            '/common/images/button-twitter.png'
        );
    }
    
A third option is to specify the needed files in the module.ini config file.  This option is best when you have added images to your custom module theme and don't want to subclass the module:

*module.ini*

.. code-block:: ini

    [module]
    title = "AppQ Test"
    disabled = 0
    protected = 0
    search = 1
    secure = 0
    MAX_RESULTS = 10
    SHARING_ENABLED = 1
     
    [native_template]
    additional_assets[] = "/min/g=file:/common/javascript/lib/ellipsizer.js"
    additional_assets[] = "/common/images/share.png"
    additional_assets[] = "/common/images/button-email.png"
    additional_assets[] = "/common/images/button-facebook.png"
    additional_assets[] = "/common/images/button-twitter.png"
     
    [strings]
    help[] = "The news home screen features most recent news across all categories. Click on an individual news item to read the full story. Note that clicking a link within the story will launch your browser. You can share each article using email, Facebook, or Twitter by clicking on the gray arrow button top right."

===========================
Javascript global variables
===========================
Occasionally web modules will set variables in the global namespace which are subsequently referenced inside loaded javascript files.  

-------------------------------------
AppQ-incompatible global variable use
-------------------------------------
In the following example, the site has defined a custom header.tpl which defines a global javascript variable myGlobals based on the array contents of a template variable:

*header.tpl*

.. code-block:: html

    {extends file="findExtends:common/templates/header.tpl"}
    {block name="javascript"} 
      <script type="text/javascript">
        var myGlobals = {json_encode($globalsArray)};
      </script>
      {$smarty.block.parent}
    {/block}
    
    
Then in the site's *common.js* the *myGlobals* variable is referenced at the top level of the file:

*common.js*

.. code-block:: js

    var firstGlobal = myGlobals[0];

Techniques like this are incompatible with AppQ because an empty html wrapper with <head> tag and javascript files are generated beforehand and the per-page content is loaded via AJAX.  In the case above the value of myGlobals will always be null because globalsArray won't be set when the html wrapper is generated.

-----------------------------------
AppQ-compatible global variable use
-----------------------------------

AppQ doesn't prevent all use of global variables.  The key is to use the built-in module functions *WebModule::addInlineJavascript()* and *WebModule::addInlineJavascriptFooter()* to define per-page global javascript variables and then use *WebModule::addOnLoad()* to trigger a function in your common.js to reference them.  AppQ will automatically move these javascript blocks around to ensure that they are loaded after the AJAX call and declared in the global namespace.  

For example the AppQ-safe way of implementing the above myGlobals variable is instead of overriding header.tpl, we move the extra javascript into the module's php file:

*MyWebModule.php*

.. code-block:: php

    <?php
    protected function initializeForPage() {
        parent::initializeForPage();
        $this->addInlineJavascript('var myGlobals = '.json_encode($this->globalsArray).';');
        $this->addOnLoad('myOnLoad();');
    }

And then in common.js we initialize firstGlobal within the load function:

*common.js*

.. code-block:: js

    var firstGlobal = null;
    function myOnLoad() {
        firstGlobal = myGlobals[0];
    }

**********************
Using Native Callbacks
**********************

AppQ comes with a small number of built-in hooks to native features.

=========
Page Load
=========
On page load, AppQ sets the page title and the title of the back button which will be show on the page immediately after this one on the navigation stack.  By default AppQ uses the page title and breadcrumb title used by the mobile web.  If you wish custom titles specifically for AppQ you can specify them in your module's *pages.ini* with the keys *nativePageTitle* and *nativeBreadcrumbTitle*.

In addition, AppQ also supports adding a reload button to the navigation bar which will allow the user to reload the content of the page.  You can specify the refresh button per page in your module's pages.ini with the key nativePageRefresh or programmatically with the function *WebModule::setWebBridgePageRefresh()*.

=======
Dialogs
=======
One of the ways users can spot a hybrid app is through its dialogs.  Either the dialog is a javascript popup and contains a URL at the top or it is a floating div and only mostly looks like a native dialog.  To make AppQ modules feel more native, AppQ provides javascript functions to generate native dialogs for common operations.

=============
Alert Dialogs
=============
Alert dialogs are used when you want to notify the user of an unexpected situation and possibly ask them to choose between doing nothing and one or two actions.  

*kgoBridge.alertDialog(title, message, cancelButtonTitle, mainButtonTitle, alternateButtonTitle, statusCallback, buttonCallback)*

* *title* - (required) A short human readable title (shown in bold on the dialog)
* *message* - (optional) A human-readable message (show in regular text below the title)
* *cancelButtonTitle* - (required) Title of the button which dismisses the alert and cancels any actions the alert refers to
* *mainButtonTitle* - (optional) Title of the primary button
* *alternateButtonTitle* - (optional) Title of an alternate button
* *statusCallback* - (optional) A callback function which will return an error if the dialog fails to display.  The callback should have the following signature:

  * function statusCallback(error, params)
  
    * *error* - If there is no error, this will be null.  If there is an error, the error object will contain the following properties:

      * *code* - a numeric code indicating what error occurred
      * *title* - a short string categorizing the error
      * *message* - a string describing the error

    * *params* - (ignored) always null

* *buttonCallback* - (optional) A callback function which is called when one of the buttons is clicked.  The callback should have the following signature:

  * *function buttonCallback(error, params)*

    * *error* - If there is no error, this will be null.  If there is an error, the error object will contain the following properties:
    * *code* - a numeric code indicating what error occurred
    * *title* - a short string categorizing the error
    * *message* - a string describing the error

  * *params* - If there is no error, the params object will contain the following property:

    * *button* - with a string value indicating which button was tapped.  This string may be one of:

      * *cancel*
      * *main*
      * *alternate*

To simplify common cases, the following two functions are also available:

*kgoBridge.alert(message, responseCallback)*

* *message* - (required) A human-readable message
* *responseCallback* - (optional) A callback function which will be called when the dialog is dismissed. The callback should have the following signature:

  * function responseCallback()

*kgoBridge.confirm(question, responseCallback)*

* *question* - (required) A human-readable message
* *responseCallback* - (optional) A callback function which will be called when the dialog is dismissed. The callback should have the following signature:

  * *function responseCallback(confirmed)*
  
    * *confirmed* - true if the user clicked "OK" and false if they clicked "Cancel".


==============
Action Dialogs
==============

*kgoBridge.actionDialog(title, cancelButtonTitle, destructiveButtonTitle, alternateButtonTitles, statusCallback, buttonCallback)*

* *title* - (required) A short human readable title
* *cancelButtonTitle* - (required) Title of the button which dismisses the dialog and cancels the action the alert refers to
* *destructiveButtonTitle* - (optional) Title of a destructive action if there is one (e.g. delete data).  Button is emphasized or shown in red to warn user.
* *alternateButtonTitles* - (required) An array of titles of additional buttons to display.  Each button should correspond to a possible non-destructive action the user can take.
* *statusCallback* - (optional) A callback function which will return an error if the dialog fails to display.  The callback should have the following signature:

  * *function statusCallback(error, params)*
    
    * *error* - If there is no error, this will be null.  If there is an error, the error object will contain the following properties:
      
      * *code* - a numeric code indicating what error occurred
      * *title* - a short string categorizing the error
      * *message* - a string describing the error
      
    * *params* - (ignored) always null

* *buttonCallback* - (optional) A callback function which is called when one of the buttons is clicked.  The callback should have the following signature:

  * *function buttonClickedCallback(error, params)*

    * *error* - If there is no error, this will be null.  If there is an error, the error object will contain the following properties:
    
      * *code* - a numeric code indicating what error occurred
      * *title* - a short string categorizing the error
      * *message* - a string describing the error
      
    * *params* - If there is no error, the params object will contain the following property:

      * *button* - with a string value indicating which button was tapped.  This string may be one of:
        
        * *cancel*
        * *destructive*
        * *alternateN* - where N is a number between 0 and the number of alternate buttons minus 1

To simplify common cases, the following function is also available:

*kgoBridge.shareDialog(buttonConfig)*

* *buttonConfig* - (required) An object with the following properties

  * *mail* - (optional) a string containing a URL to share something via email (mailto:user@example.com)
  * *facebook* - (optional) a string containing a URL to share something on Facebook
  * *twitter* - (optional) a string containing a URL to share something on Twitter
 
Normally you should not need to call the *kgobridge.shareDialog()* function. Just include the share.tpl template and this function will be called for you.
 
=============
Debug Logging
=============
On iOS it is difficult to get console logging message out of a UIWebView.  To make this easier, AppQ provides a logging function which will send messages to the Xcode console via NSLog() when the native app is run in debug mode.  This function also works on Android and should be used there as well for consistency.

*kgoBridge.log(message)*

* message - (required) A human-readable message to log to the native console

*************************
Theming a Module for AppQ
*************************
Kurogo Mobile Web comes with native app images and styles for stock templates.  However if you have custom UI you may also wish to theme your UI specifically for AppQ.  You can target AppQ on all native platforms or specific platforms using the same pagetype/platform mechanism used for identifying different types of phone browsers on the mobile web.

======================
Targeting AppQ devices
======================
In order to help you target native apps specifically, AppQ adds a third classification type called "browser".  It also adds a new value "common" for pagetype and platform so that you can target specific browser values for all pagetypes and platforms.  

For example, here are the new possible CSS file names and the devices they impact:

+-----------+---+----------+---+---------+------+----------------------------+
| pagetype  |\- | platform |\- | browser |      | Devices Impacted           |
+===========+===+==========+===+=========+======+============================+
|  common   |   |          |   |         | .css | all devices                |
+-----------+---+----------+---+---------+------+----------------------------+
| compliant |   |          |   |         | .css | all phones                 |
+-----------+---+----------+---+---------+------+----------------------------+
| tablet    |   |          |   |         | .css | all phones                 |
+-----------+---+----------+---+---------+------+----------------------------+
| common    |\- | android  |   |         | .css | all Android Devices        |
+-----------+---+----------+---+---------+------+----------------------------+
| compliant |\- | android  |   |         | .css | all Android phones         |
+-----------+---+----------+---+---------+------+----------------------------+
| compliant |\- | iphone   |   |         | .css | all iPhones                |
+-----------+---+----------+---+---------+------+----------------------------+
| common    |\- | common   |\- | native  | .css | AppQ on all devices        |
+-----------+---+----------+---+---------+------+----------------------------+
| compliant |\- | common   |\- | native  | .css | AppQ on all phones         |
+-----------+---+----------+---+---------+------+----------------------------+
| tablet    |\- | common   |\- | native  | .css | AppQ on all tablets        |
+-----------+---+----------+---+---------+------+----------------------------+
| common    |\- | android  |\- | native  | .css | AppQ on Android devices    |
+-----------+---+----------+---+---------+------+----------------------------+
| common    |\- | iphone   |\- | native  | .css | AppQ on iOS devices        |
+-----------+---+----------+---+---------+------+----------------------------+
| compliant |\- | android  |\- | native  | .css | AppQ on Android phones     |
+-----------+---+----------+---+---------+------+----------------------------+
| compliant |\- | iphone   |\- | native  | .css | AppQ on iOS phones         |
+-----------+---+----------+---+---------+------+----------------------------+
| tablet    |\- | android  |\- | native  | .css | AppQ on Android tablets    |
+-----------+---+----------+---+---------+------+----------------------------+
| tablet    |\- | iphone   |\- | native  | .css | AppQ on iPad               |
+-----------+---+----------+---+---------+------+----------------------------+

Javascript files, templates and image directories follow a similar naming scheme.

=============================
Debugging AppQ theme problems
=============================
Debugging problems with AppQ theming can be difficult on a device due to the lack of a full featured DOM inspector.  Fortunately you can use the device debugging feature of the Kurogo Mobile Web server to also debug AppQ modules.  

First, make sure *DEVICE_DEBUG=1* in your site.ini file.  Then go to http://localhost/device/compliant-iphone-native/mymodule/ to see the AppQ iPhone module version of your module.  Similarly http://localhost/device/tablet-android-native/mymodule/ will show you the AppQ Android tablet version of your module.  You should use a web browser similar to the native app's browser.  For example iOS and Android use a Webkit browser so you will want to use Safari or Chrome when using the debugging mode.

Because AppQ provides a native navigation stack, the AppQ device debugging mode will not show the breadcrumb bar. As a result you will need to use the browser navigation arrows to go back.

This debugging mode simulates the AJAX page loading used by AppQ.  Because it is running in a web browser, it cannot implement any of the native hooks normally available to AppQ.  When you attempt to trigger one of these hooks, the debug mode will attempt to *console.log()* the trigger URL so you can use the Web Inspector see what parameters would have been passed to the back end.

******************************
The AppQ Native Asset Zip File
******************************
Once your module has been modified to support AppQ you can build your first asset zip files.  These archives contain all the images, CSS and javascript which will be cached locally inside the native app to improve performance.

===========================
Building the Asset Zip File
===========================
To build the asset zip files, go to the Admin panel and in the navigation menu on the left side, select Module Configuration and then your module.  On the right-hand side you will need three tabs underneath the module's name.  Select the AppQ tab.  You will see buttons to build iPhone and Android templates.  Click the buttons which correspond to the native platforms you support.  AppQ asset zip files may take up to a minute to generate, depending on how many pages your module supports.

Once the asset zip files have finished building, you will see links to download the images below each button.  These links will look like http://www.example.com/media/web_bridge/iphone/mymodule.zip.  If you have enabled the tablet theme for your site, AppQ will also build tablet versions of the same asset zip files, such as http://www.example.com/media/web_bridge/iphone/mymodule-tablet.zip. 

Every time you build a new copy of the asset zip files, the Kurogo Mobile Web server will tell each native app to download the new files. As a result you should try to minimize the number of times you rebuild the files on a production server.

=============================
Preloading the Asset Zip File
=============================
AppQ asset zip files live on the Kurogo Mobile Web server in your site's media folder.  The Kurogo Mobile Web server tells native apps which modules support AppQ, and the apps will then download the asset files from the media folder.  While this only happens each time the asset zip files change, you may wish to include the current version of the asset zip files inside your native apps so that the user's "first launch" experience is fast.

The Admin panel provides download links to obtain copies of the asset zip files for inclusion in your native app sources.  If you have tablet versions of the assets, you should also obtain those at this time.

---------------------
iOS (iPhone and iPad)
---------------------
AppQ asset files live inside the project's resource folder on iOS.  Here are example asset zip file paths for the iOS app "MyApp" and AppQ module "mymodule".

* Kurogo-iOS/Projects/MyApp/Resources/modules/mymodule/mymodule.zip
* Kurogo-iOS/Projects/MyApp/Resources/modules/mymodule/mymodule-tablet.zip 

These files will be automatically added to your project through Xcode folder references.

-------
Android
-------
AppQ asset files live inside the project's resource folder on iOS.  Here are example asset zip file paths for the Android app "MyApp" and AppQ module "mymodule".

* Kurogo-Android/site/MyApp/config/modules/mymodule/assets/web_bridge.zip
* Kurogo-Android/site/MyApp/config/modules/mymodule/assets/web_bridge-tablet.zip

Once the asset zip files are copied into those locations, clean your project and rebuild and the assets will be built into your app.

=======================
Updating an AppQ Module
=======================
Every time you update your module and deploy it to your Kurogo Mobile Web server, you will need to build new asset zip files so that the zip files match the mobile web version of the module.  Existing native apps will download the new zip files so you do not need to worry about your existing users.  However after you deploy to your Kurogo Mobile Web server, you may wish to release a new version of your native app with the new zip files included so that the "first launch" experience does not involve an immediate download of the updated asset zip files.  Unless your module has large numbers of images, its zip files will be small, but the download time may still be noticeable on a slow network.
