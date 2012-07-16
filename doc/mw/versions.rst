###############
Version History
###############

Kurogo continues to be improved. The following are significant improvements in each version.
For more detailed release notes, see the included CHANGELOG file

Version 1.5
===============================
* NEW MODULE : Social. Show feeds from facebook and Twitter
* Created ShellModule to create modules that run on the command line (UNIX only at this time)
* Kurogo shell modules have been created that can fetch public feed data in the background. See dev guide for setting up automatic fetching
* It is possible to change which module is the home module
* New XML Parser (SimpleXMLDataParser) makes it easier to parse simple XML documents
* Improved the :doc:`Calendar <modulecalendar>` Detail page
* Improved :doc:`Map Module <modulemap>` flexibility
* Enabled pagination of people results
* Add :doc:`Shibboleth authentication <ShibbolethAuthentication>` authority
* Tablet Panes are now loaded Asynchronously
* Improved tablet interface for Photos
* Added :doc:`CurlDataRetriever <dataretriever>` to retrieve data using the cURL library
* A custom user class can be specified in :doc:`Authentication <authentication>` configurations eliminating the need to create a custom authority
* Support for Co-Ed sports in :doc:`Athletics <moduleathletics>` module
* Many other bug fixes and minor improvements

Version 1.4 (March 5, 2012)
===============================
* NEW Module: :doc:`Athletics <moduleathletics>`
* NEW Module: :doc:`Photos <modulephotos>`
* Updated :doc:`Map module <modulemap>`
* Overhauled the :doc:`Data Retrieval <dataretrieval>` classes to better support SOAP and Database retrieval methods and complex data models
* Better client side caching headers
* Added support for in-memory caching of Kurogo data structures using Memcache or APC
* Added developer support for encrypting data on disk (requires mcrypt)
* Added option to locally save user credentials so they can be passed on to external services
* Added support for Google Analytics domain names
* Added support to show emergency notifications on the home screen
* Federated search queries will now happen asynchronously on compliant devices.
* Added support to create copied modules from the admin console.
* Added support to remove modules from the admin console

Version 1.3 (October 13, 2011)
==============================
* Support for :doc:`localization <localization>`
* :doc:`MultiSite <multisite>`
* New :doc:`logging <logging>` facility
* Updated :doc:`Statistics module <modulestats>`
* Updated :doc:`Map module <modulemap>`
* Improved support for recurring events in the :doc:`calendar module <modulecalendar>`
* Added support for grouping :doc:`content <modulecontent>` pages
* If your :doc:`news feed <modulenews>` does not have full content, you can add a "read more" link
* Improved method of creating :ref:`copied modules <copy-module>`
* Support for YouTube playlists in the :doc:`video module <modulevideo>`
* Support for Percent Mobile :ref:`Analytics <analytics>`

Version 1.2 (July 19, 2011)
===========================
* Added support for grouping :doc:`contacts <modulepeople>` and :doc:`links <modulelinks>`
* Added :doc:`support for IIS <setup>`
* Streamlined :doc:`theme <themes>` development
* Created protocol for :doc:`data sharing between modules <moduleinteraction>`
* Support for alternate methods and custom request headers in :doc:`DataController <dataretrieval>`
* :ref:`Admin console <admin-module>` can be used on tablets

Version 1.1 (June 1, 2011)
==========================

* Added reordering of feeds in the :ref:`admin console <admin-module>`
* Added support for Vimeo in the :doc:`Video Module <modulevideo>`
* Added bookmarks to the :doc:`people module <modulepeople>`
* Added HTTP proxy support to :doc:`DataController <dataretrieval>`

Version 1.0 (April 8, 2011)
===========================
Initial Release
