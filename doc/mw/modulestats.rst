#################
Statistics Module
#################

The statistics module (/stats) provides an interface to view your site's local analytics. Each
page view is recorded with enough detail to create a variety of reports and the module has
configurable options to indicate the types of reports and charts to display.

=============
Configuration
=============

Version 1.5 uses a log file to log page views to Kurogo. 

============
View Logging
============

After configured, Kurogo will log every page view from each module. Each request will include
the following data:

* date/time stamp
* site
* service (web or api)
* the request URI
* the referrer
* the user agent
* the ip address
* the logged in user and authority (if using authentication)
* a visit id
* pagetype (from device detection)
* platform (from device detection)
* module
* page
* additional data set by the module (for instance, which news article was read or the search term)
* the size of the response
* the elapsed time generating the response

=========
Reporting
=========

The statistics module includes a series of reports for viewing the overall views for the site
as well as views grouped by module, pagetype and platform.

-----------------
Updating the data
-----------------

To ensure best performance, the stats data is stored in a log file and then ingested into a 
database table. 


.. _stats_migration:

=====================================
Migration from Old Versions of Kurogo
=====================================

Previous versions of Kurogo used different manner of stats recording. All requests were immediately
added to the database table. In Kurogo 1.5, requests are first logged to a log file and then
ingested into the database.

In order to improve performance, the database tables are sharded into different tables based
on time. The default option of *KUROGO_STATS_SHARDING_TYPE* is to have 1 table per calendar
month. If you want to maintain your previous statistics when upgrading you will need to run
a Kurogo shell command to migrate this data:

:kbd:`/path/to/kurogo/lib/KurogoShell stats migrate`

This command can be run on a running server, however keep in mind that it will take a considerable
amount of time (in some cases an hour or longer). 

