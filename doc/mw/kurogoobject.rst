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
* *Kurogo::getCache($key)* - Retrieves a value from the memory cache - See :ref:`caching`
* *Kurogo::setCache($key, $value, $ttl = null)* - Sets a value to the memory cache - See :ref:`caching`
* *Kurogo::deleteCache($key)* -Removes a value from the memory cache - See :ref:`caching`
* *Kurogo::log($priority, $message, $area)* - Logs a value to the kurogo log - See :doc:`logging`
* *Kurogo::encrypt($value, key)* - Encrypts a value (requires the mcrypt extension). See :ref:`encryption`
* *Kurogo::decrypt($value, key)* - Decrypts a value (requires the mcrypt extension). See :ref:`encryption`


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

.. _encryption:

**********
Encryption
**********

Version 1.4 adds methods to store and retrieve encrypted data. This is primarily useful for 
saving sensitive data from remote servers in a secure fashion. These methods require the mcrypt 
extension. Like any encryption system it is only secure as the keys used to encrypt the data. 
The default behavior is to use the SITE_KEY constant which is generated using the install
path of the server software. You can set this key by updating the SITE_KEY value in *site.ini*

.. _caching:

*******
Caching
*******

Version 1.4 also adds methods to improve the performance of Kurogo by utilizing in-memory
caching. If the server contains certain extensions, you can greatly improve the performance 
of production servers by caching information used by Kurogo such as configuration values, search
paths for templates, and remote data values. 

-------------
Configuration
-------------

Kurogo supports caching using 2 different systems:

* APC - `The Alternative PHP cache <http://php.net/manual/en/book.apc.php>`_
* `Memcache <http://www.php.net/manual/en/book.memcache.php>`_

Configuration for this system is accomplished through the *[cache]* section of *kurogo.ini*. There are a few options
used by all caching types:

* *CACHE_CLASS* - The type of caching system to use. Current options include *APCCache* and *MemcacheCache*
* *CACHE_TTL* - The default time-to-live (in seconds) for cache values. This will keep the values
  in the cache for the specified time. A time of 10-15 mintes (600-900) is usually adequate for
  most sites

--------
APC
--------

There are no additional options available or needed for APCCache  

--------
Memcache
--------

These options only apply to MemcacheCache

* *CACHE_HOST* - The hostname or IP address of your memcache server. If this value is an array (CACHE_HOST[]) then Kurogo will 
  utilize connection failover
* *CACHE_PORT* - The port used to connect to the memcache server. By default this is 11211
* *CACHE_PERSISTENT* - If set to 1 a persistent connection will be used, default is false
* *CACHE_TIMEOUT* - Timeout in seconds to connect to the servers. This should rarely be altered.
* *CACHE_COMPRESSED* - If set to 1, compression will be used. Default is true. 
