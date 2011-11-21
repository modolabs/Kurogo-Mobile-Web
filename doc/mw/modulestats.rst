#################
Statistics Module
#################

The statistics module (/stats) provides an interface to view your site's local analytics. Each
page view is recorded with enough detail to create a variety of reports and the module has
configurable options to indicate the types of reports and charts to display.

=============
Configuration
=============

Logging of page views requires a site database configured in the *[database]* section of the 
*site.ini* file. If you do not have access to a database (including the ability to use an
embedded SQLite database) then you can turn off statistics logging by setting *STATS_ENABLED=0*
in *SITE_DIR/config/site.ini*

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

