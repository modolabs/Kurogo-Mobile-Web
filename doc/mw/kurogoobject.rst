#################
The Kurogo Object
#################

The Kurogo object is a singleton instance that contains several methods that consolidate common
tasks when developing modules.

********************
Static Class Methods
********************

* *Kurogo::sharedInstance()* - Returns the shared Kurogo singleton object. This is typically not 
  necessary to use since all publically documented methods are static methods on the Kurogo class.
* *Kurogo::tempDirectory()* - Returns the configured temporary directory
* *Kurogo::siteTimezone()* - Returns a DateTimeZone object set to the site's configured time zone
* *Kurogo::includePackage($packageName)* - Adds a library package to the autoloading path. See :ref:`autoloader`
* *Kurogo::getSiteVar($key, $section=null)* - See :ref:`modules_configuration`
* *Kurogo::getOptionalSiteVar($key, $default='', $section=null)* - See :ref:`modules_configuration`
* *Kurogo::getSiteSection($section)* - See :ref:`modules_configuration`
* *Kurogo::getOptionalSiteSection($section)* See :ref:`modules_configuration`
* *Kurogo::getSiteString($key)* - See :ref:`modules_configuration`
* *Kurogo::getOptionalSiteString($key, $default='')* - See :ref:`modules_configuration`


.. _autoloader:

***************
The AutoLoader
***************

Before using a PHP class, it is necessary to ensure that the class declaration has been loaded. Kurogo 
follows the pattern to include each class as a distinct PHP file with the same name as its class. 
It is not necessary, however, to  use the PHP *require* or *include* statements to utilize these files 
Kurogo includes an autoloading mechanism that will automatically include the file when the class is requested. 
In most cases you can simply utilize a method or constructor of a class and its definition will 
be loaded. The autoloader will search the site lib folder (if present) and the base Kurogo lib folder
for a file with the same name as the class you are attempting to load. So if you make a call to 
*SomeClass::method()* it will look for the following files:

* *SITE_DIR/lib/SomeClass.php*
* *KurogoRoot/lib/SomeClass.php*

--------
Packages
--------

In order to promote organization of class files, a concept of Packages was created. This allows the
grouping of files with similar functionality together. Including a  package simply adds that 
subfolder to the list of paths the autoloader will search. It will also attempt to load a file named
*Package.php* in the lib folder. This gives you an opportunity to load global constants or function
declarations (not part of a class). This file is optional.

For example, if the *Maps* package is loaded then the following paths will be added to the autoloader
search paths:

* *SITE_DIR/lib/Maps/*
* *KurogoRoot/lib/Maps/*

And the autoloader will attempt to load *lib/Maps.php*.

You can create your own packages by simply creating a folder in your site's lib folder. The following
packages are part of the Kurogo distribution:

* Authentication (included automatically when authentication is enabled)
* Authorization - for connecting to various OAuth based web services
* Calendar - includes classes to deal with date and time
* db - used when you wish to interact with a database
* Emergency - used by the emergency module
* Maps - used by the maps module
* People - used by the people module
* Video - used by the video module
