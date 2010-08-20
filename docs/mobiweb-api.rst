##########################
API for Native Mobile Apps
##########################

This extension of the Mobile Web enables it to publish an API to use
with native apps.

************
Installation
************

=====================================
System Requirements and Configuration
=====================================

The Mobile Web must be installed.  Additionally, the following
extensions are **required**.

-----
MySQL
-----

MySQL is required for keeping a queue of device registrations and push
notification subscriptions.  Additionally, API calls from native apps
are logged in a MySQL table similar in structure to that of the Mobile
Web.

----
PEAR
----

The PHP PEAR extension is required for running the push notification
daemon scripts.  The following PEAR libraries are required:

* System::Daemon
* Log

-----------------
Optional Programs
-----------------

^^^^^^^^
pngcrush
^^^^^^^^

``pngcrush`` is handy for compressing PNG images into smaller file
sizes.  The source code for ``pngcrush`` can be downloaded at
http://sourceforge.net/projects/pmt/files/.  ``pngcrush`` generally
needs to be compiled from source; we are not aware of readily
available binaries.

=======================================
Relevant Files in the Mobile Web Source
=======================================

* mobi-lib
* mobi-mysql
* mobi-web/api
* mobi-web/api/push
* scripts

========================
Enabling the Push Daemon
========================

Talk about where to put all the files, what scripts to run, installing
the PEAR modules, file permissions, getting the right certificates,
and using the actual daemon script.

*******************
REST APIs by Module
*******************

All MIT Mobile Web APIs follow the following specification (in terms
of `RFC 1808 <http://www.ietf.org/rfc/rfc1808.txt>`):

* **host**: m.mit.edu
* **path**: api OR module/api
* **query**: <param1>=<value1>&<param2>=<value2>&...

In other words, requests to the mobile server mostly take the form

http://m.mit.edu/api?module=*module*&*param*=*value*...

with the majority of those taking the form

http://m.mit.edu/api?module=*module*&command=*command*&*param*=*value*...

Some modules, such as news, campus map, and shuttles, have their own
API directories on the server, and thus have request URLs like

http://m.mit.edu/api/map?command=*command*&*param*=*value*...

http://m.mit.edu/api/news?*param*=*value*...

http://m.mit.edu/api/shuttles?command=*command*&*param*=*value*...

All modules return responses in JSON except for News, which returns
XML (or a very loose and not validated implementation of RSS).

The rest of this chapter will describe each individual module's API.
Each section will provide the base URL (everything up to the "?") of
the requests, parameters to pass to the query portion of the URL, and
possible values.

.. toctree::

  mobiweb-api-modules/courses
  mobiweb-api-modules/emergency
  mobiweb-api-modules/events
  mobiweb-api-modules/map
  mobiweb-api-modules/news
  mobiweb-api-modules/people
  mobiweb-api-modules/shuttles

*********************
Push Notification API
*********************

Discuss device registration on startup, subscribe/unsubscribe commands

