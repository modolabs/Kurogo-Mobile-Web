------------------
iPhone Application
------------------
===========================================
General Structure of the iPhone Application
===========================================

The iPhone application is made up of several semi-autonomous modules, that 
each operate like mini applications.  The current modules are news, shuttle 
schedule, map, stellar, people directory, emergency, settings, about, and 
MIT Mobile web.  Each module is loaded on the screen when the appropriate 
button on the buttom tab bar is pressed.  The order of the modules can be 
customized with some exceptions, (e.g. about, setting, and MIT Mobile web are 
fixed to be at the end of the list)

The code for each module is located in
paths of the form ``iPhone-app/Modules/Module Name``.  Code that serves 
purposes not specific to any module is in ``iPhone-app/Common/``, or 
``iPhone-app/App Delegate`` if it is specific to the function the of UIApplicationDelegate.

Every module is a class that inherits from the ``MITModule`` and a single 
instance of each module is instantiated by the ``MIT_MobileAppDelegate``.  Each
individual module is responsible for the following items

* holding a reference to the module ``UINavigationController`` in tabNavController
* defining name-like properties such ``tag, shortName, longName`` and ``iconName`` the name of the tab bar icon file
* defining various other properties such as:
   #. ``isMovableTab`` is the order of the module in the tab bar customizable
   #. ``canBecomeDefault`` can the module be set to the open module on launch
   #. ``pushNotificationSupported`` does the module receive push notifications
* handling URLs, which are used to communicate between modules
* handling Notifications, notifications received from the apple push server

Code that is useful to several modules lives in ``iPhone-app/Common/``. 
The modules receive all their external data from the http://m.mit.edu, which
proxies several MIT services.  As much as possible this server supplies data in
the JSON format.  The URLs for each service is a subpath of http://m.mit.edu/api/.  The modules which consume JSON formatted data communicate to the server
through the wrapper class MITMobileWebApi.  The modules which use the iPhones
builtin sqllite database use the ``CoreDataManager`` class, which is a 
tweaking of the class apple autogenerates.

====
News
====
The `news <modules/news.html>`_ module's purpose is to display content from the MIT news office.  The 
news articles are divided into several categories and the "top news" category. 
Navigation between categories is controlled by a tab strip towards the top of 
the screen.  This module caches news articles with Core Data in sqllite to 
allow for offline viewing.  The news module consumes an XML feed that is a slightly
modified RSS feed, which contains the textual content of the article, and links to the images for
the article.  The user can also share articles he enjoys with friends and colleagues via email or
facebook.

=======
Stellar
=======
The `Stellar <iPhone-modules/stellar.html>`_ module's purpose is to allow students to access information
about currently active course.  The students can view basic information, such as description, times, locations,
and staff members.  Also, many classes use the stellar service to post time sensitive 
announcements about the class.  These announcements are displayed in this module, additionally
students can bookmark classes for quick access.  Announcments for bookmarked classes are pushed
in real time to the users iPhone.  




  

   