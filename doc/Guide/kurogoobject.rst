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