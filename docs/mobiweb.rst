##########
Mobile Web
##########

************
Installation
************

=====================================
System Requirements and Configuration
=====================================

--------------
Apache and PHP
--------------

Apache version 2.0 or above is required.  The active server at MIT runs 2.0.52.

PHP version 5.2 or above is required.  libphp5 must be enabled.  Check
for the following lines in the PHP configuration file (typically
``/path/to/apache/conf/httpd.conf`` or
``/path/to/apache/conf.d/php.conf``)::

  LoadModule php5_module modules/libphp5.so  
  AddHandler php5-script .php  
  AddType text/html .php  
  DirectoryIndex index.php

-------------------
Optional Extensions
-------------------

^^^^^^
Drupal
^^^^^^

Drupal is the main method for entering and maintaining content that:
* is relevant only to MIT Mobile, e.g. "what's new" type announcements.
* does not have an official home location, e.g. hours/locations for
the Libraries.

The MIT Mobile Web currently runs Drupal 6.9.  Drupal can be
downloaded at http://drupal.org

^^^^^
MySQL
^^^^^

The MIT Mobile Web uses MySQL 5.0 to support Drupal and usage
statistics.  If MySQL is used, PHP must be complied with MySQL
support.  `MySQL interface documentation on the PHP website
<http://us.php.net/manual/en/book.mysql.php>`_

The database configuration is in the file ``lib/trunk/db.php``.

^^^^
LDAP
^^^^

To work with an LDAP server, an LDAP client such as OpenLDAP must be
enabled in Apache.  Make sure the configuration file (such as
httpd.conf) includes the following lines::

  LoadModule ldap_module modules/mod_ldap.so  
  LoadModule auth_ldap_module modules/mod_auth_ldap.so

PHP must be compiled with LDAP support.  `PHP LDAP interface
documentation on the PHP website
<http://us.php.net/manual/en/book.ldap.php>`_

^^^^
SOAP
^^^^

If any modules that use SOAP-based data sources are enabled, PHP must
be compiled with SOAP support.  The MIT Events Calendar uses the
NuSOAP library which is licensed under LGPL and can be downloaded from
http://sourceforge.net/projects/nusoap/.

^^^^^^^^^^^^^
Oracle Client
^^^^^^^^^^^^^

If any modules are required to communicate with an Oracle server, PHP
must be compiled with the Oracle Client Interface (OCI).  OCI is
included in PHP and does not require additional installation.  `OCI
documentation on the PHP website
<http://us2.php.net/manual/en/book.oci8.php>`_

^^^^^^^^^^^^^^^^
SSL Certificates
^^^^^^^^^^^^^^^^

MIT makes use of a local Certificate Authority (CA) for authenticating
users.  Servers running the MIT Mobile Web that use such a system must
have the CA root certificate installed.

.. _subsection-mobiweb-mobi-config:

------------------------------------
PHP Configuration and File Locations
------------------------------------

The directories mentioned in this section all refer to top-level
directories in the source tree.

.. _subsubsection-mobiweb-mobi-web-directory:

^^^^^^^^
mobi-web
^^^^^^^^

The contents of the directory ``mobi-web`` can be placed anywhere on
the system, but to serve the Mobile Web, Apache must be aware of the
directory.  For instance, the ``mobi-web`` directory can be entirely
copied under ``/var/www/htdocs``.  Then if ``httpd.conf`` (or
equivalent file) contains the following line::

  DocumentRoot "/var/www/htdocs/mobi-web"

Then when people access http://yourserver.yourextension they will
reach the Mobile Web.  If ``httpd.conf`` contains not the line above
but the line below::

  DocumentRoot "/var/www/htdocs"

Then people can reach the Mobile Web by accessing
http://yourserver.yourextension/mobi-web.

This distinction is important as it affects the constant ``HTTPROOT``,
which is important for saving cookies.

If your server is hosting the Mobile Web exclusively, you may consider
copying the contents of ``mobi-web`` into the default DocumentRoot,
instead of copying the directory itself.

.. _subsubsection-mobiweb-mobi-lib-directory:

^^^^^^^^
mobi-lib
^^^^^^^^

.. _subsubsection-mobiweb-mobi-config-directory:

^^^^^^^^^^^
mobi-config
^^^^^^^^^^^

what to do with config and constants files

device detection

service locations

.. _subsubsection-mobiweb-mobi-mysql-directory:

^^^^^^^^^^
mobi-mysql
^^^^^^^^^^

which tables are needed

----------------
File Permissions
----------------


^^^^^^^^^^^
Cache Files
^^^^^^^^^^^

Frequently used files from external data sources are stored as cache
files in ``mobi-lib/cache``.  ``mobi-lib/cache`` also contains the
following directories:

* ACADEMIC_CALENDAR
* EVENTS_CALENDAR
* NEWS_OFFICE
* STELLAR_COURSE
* STELLAR_FEEDS

Cache files must be readable and writeable by the system's user that
hosts files on the web, generally the ``apache`` user.

==========================================
Contents of the MIT Mobile Web Source Tree
==========================================

* :ref:`subsubsection-mobiweb-mobi-config-directory` -- Location of config
  and constants files.  Change extension from ``.php.init`` to
  ``.php`` to enable.
* :ref:`subsubsection-mobiweb-mobi-lib-directory` -- library of
  :ref:`section-mobiweb-mobi-lib` to interact with data sources,
  also used by the SMS system.
* :ref:`subsubsection-mobiweb-mobi-mysql-directory` -- SQL scripts to
  create the database tables required by the mobile web system.
* :ref:`subsubsection-mobiweb-mobi-web-directory` -- main directory of
  Mobile Web files.

  * ``3down/`` -- Services Status module
  * ``a/`` -- URL shortener for inbound links from SMS
  * ``api/`` -- files for the REST API used to communicate with native
    app clients.
  * ``about/`` -- desktop About site.
  * ``Basic/`` -- HTML templates, CSS, and image files for
    non-touchscreen devices (including computers).
  * ``calendar/`` -- Events Calendar module.
  * ``careers/`` -- Careers module.
  * ``config/`` -- Constants for Mobile Web code.
  * ``customize/`` -- Customize Home Screen module.
  * ``e/`` -- URL shortener for calendar events.
  * ``emergency/`` -- Emergency Info module.
  * ``error-page/`` -- Error page to show user when something goes
    wrong.
  * ``home/`` -- Home Screen.
  * ``libraries/`` -- Libraries module.
  * ``links/`` -- Useful Links module.
  * ``map/`` -- Campus Map module.
  * ``mobile-about/`` -- mobile About module.
  * ``n/`` -- URL shortener for news stories.
  * ``page_builder/`` -- Page Builder package.
  * ``people/`` -- People Directory module.
  * ``shuttleschedule/`` -- Shuttles module.
  * ``sms/`` -- SMS overview module.
  * ``stellar/`` -- Course Info module.
  * ``techcash/`` -- TechCASH module.
  * ``Touch/`` -- HTML templates, CSS, and image files for
    touch-screen phones with less advanced browsers.
  * ``Webkit/`` -- HTML templates, CSS, JS, and image files for Webkit
    browsers on touch-screen devices.

* ``scripts/`` -- setup and daemon scripts (not used by the Mobile Web)

The following directories under ``mobi-web`` are device bucket
directories, i.e. they hold static files for each device bucket:
``Webkit`` (:ref:`section-mobiweb-Webkit`), ``Touch``
(:ref:`section-mobiweb-Touch`), ``Basic``
(:ref:`section-mobiweb-Basic`).

The following dirctories under ``mobi-web`` are module directories:
``3down`` (:ref:`section-mobiweb-3down`), ``calendar``
(:ref:`section-mobiweb-calendar`), ``customize``
(:ref:`section-mobiweb-customize`), ``careers``
(:ref:`section-mobiweb-careers`), ``emergency``
(:ref:`section-mobiweb-emergency`), ``libraries``
(:ref:`section-mobiweb-libraries`), ``links``
(:ref:`section-mobiweb-links`), ``map`` (:ref:`section-mobiweb-map`),
``mobile-about`` (:ref:`section-mobiweb-mobile-about`), ``people``
(:ref:`section-mobiweb-people`), ``shuttleschedule``
(:ref:`section-mobiweb-shuttleschedule`), ``sms``
(:ref:`section-mobiweb-sms`), ``stellar``
(:ref:`section-mobiweb-stellar`), ``techcash``
(:ref:`section-mobiweb-techcash`).

The directory ``mobi-web/page_builder`` contains
:ref:`section-content-generator` files, and ``mobi-web/home`` contains
files for the home screen.

.. _section-content-generator:

*****************
Content Generator
*****************

At the core of the MIT Mobile Web is a content generator that delivers
a different look-and-feel to different classes of mobile browsers.
This package is called the Page Builder, and ties together all the
separate modules available to the user on the Mobile Web.

=====================
Overall Look-and-Feel
=====================

HTML elements common to all modules (such as background, header, and
footer) are defined in top-level template files ``Webkit/base.html``,
``Touch/base.html``, and ``Basic/base.html``.

-----------
Home Screen
-----------

The Home Screen displays a grid of icons or linear list of links to
all modules the user has access to based on their device.  Webkit
devices are shown a grid view of icons; Touch devices are shown a
similar, but slightly smaller grid; Basic devices are shown a linear
list of text links.

Additionally, we use red badges or text to inform the users about new
features on the Mobile Web.

The main page, ``mobi-web/home/index.php``, performs three sequential
tasks:

#. Check if there have been new announcements since the user last visited the site.  This is handled by the ``WhatsNew`` class in the :ref:`section-mobiweb-mobile-about` module, which reads the user's :ref:`subsubsection-mobiweb-cookies-whatsnewtime` cookie.  If the latest announcement is less than 2 weeks old, all users are shown a red badge or text on the home screen.

#. Figure out which module links to display, and in what order.  This is done in the class ``Modules`` (see :ref:`section-mobiweb-modules`). ``Modules`` provides the following:

  * List of device-independent modules and device-dependent modules
  * Default list of modules for a given device type
  * URL for each module in the list (most are the directory by the
    same name as the module ID). If the URL needs to be rewritten for
    the certificates interstitial page, this is handled by
    ``home/index.php``.
  * Method to reorder/hide/show modules based on the list given in
    the user's cookies.

#. Display the module links in a grid or linear layout using the appropriate template for the device.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Web Certificates interstitial page
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If a user does not have the
:ref:`subsubsection-mobiweb-cookies-mitcertificate` cookie, links to
modules that require certificates are replaced by the URL linking to
``certcheck.php``.  This file presents a screen that shows the user a
link to the MIT Certificate server and a link to proceed directly to
the requested module.

----------------
Device Detection
----------------

Whenever a user visits the mobile website, their browser ID (aka
user-agent string) is first forwarded to a device detection service
that determines what type of interface and which selection of modules
should be shown to the user.  See separate documentation for more
details on device detection.

-------
Cookies
-------

We use cookies for determining which modules to show the user and in
what order.

.. _subsubsection-mobiweb-cookies-activemodules:

^^^^^^^^^^^^^
activemodules
^^^^^^^^^^^^^

This cookie is a list of all the modules that should be shown on the
user's home screen. If the user is visiting our website for the first
time, this cookie is automatically populated with the default list of
modules.  Users can change the contents of this cookie by going to
Customize Home Screen, which rewrites the cookie as the user checks
and unchecks desired modules.

.. _subsubsection-mobiweb-cookies-moduleorder:

^^^^^^^^^^^
moduleorder
^^^^^^^^^^^

This cookie is a list of modules in the order they should be shown on
the user's home screen. As with activemodules, it is populated with
the default module order upon the user's first visit, and can be
changed via the Customize module.

.. _subsubsection-mobiweb-cookies-whatsnewtime:

^^^^^^^^^^^^
whatsnewtime
^^^^^^^^^^^^

This cookie is populated when the user access the "What's New" section
of About this Site (or clicking directly on the "What's New" link on
smartphones/featurephones.  It stores the current timestamp and is
read by the Home Screen to determine whether new announcements should
be highlighted for the user.

.. _subsubsection-mobiweb-cookies-mitcertificate:

^^^^^^^^^^^^^^
mitcertificate
^^^^^^^^^^^^^^

For modules that require certificates, the Home Screen checks if this
cookie is set to determine whether to send the user to an interstitial
(certcheck.php) page when they click on the module link, or to send
the user directly to the module.

If the cookie is not set, the link to the module is replaced by a URL
to an interstitial page that gives the user an option to: (a) get an
MIT Personal Certificate, or (b) proceed directly to the module.

The cookie is set if the user gets a certificte, or if the user
already has a certificate and thus successfully proceeds.

========================
The Page Builder Package
========================

The directory ``mobi-web/page_builder`` contains the logic to build
pages.  The page builder looks for files in the directories
``mobi-web/Webkit``, ``mobi-web/Touch``, and ``mobi-web/Basic``, which
respectively contain static media required to assemble pages for
Webkit, Touch, and Basic devices.

-----------
Page Header
-----------

For an overview of the content generation process, it is instructive
to look at the file ``page_builder/page_header.php`` which is required
in every module other than the home screen.  It does the following:

#. Load commonly used functions (from ``page_tools.php``).

#. Create a ``Page`` object (actually ``WebkitPage``,
``TouchPage``, or ``BasicPage``), which controls the rest of page construction.

#. Tell the statistics counter what device is accessing what module,
so it can be recorded.

#. Provide error handling.

The following sections will describe these steps in detail.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Commonly Used Classes and Functions (page_tools.php)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

``page_tools.php`` provides several classes and functions to abstract
away some commonly used page elements.  If a page element is being
used independently in several modules, it is a good idea to add the
it to ``page_tools.php``.  The following elements are provided:

* ``DrillDownList`` -- creates a drill down list from a range.
  Construct a ``DrillAlphabeta`` or ``DrillNumeralAlpha`` object with
  a string representation of a range (e.g. “11-20”, “A-F”), and call
  the method ``get_sublist()`` to create the html list.

* ``Tabs`` -- creates the appearance of tabbed pages that are useful
  for displaying detail screens for Course Info or a building in the
  Campus Map.  Call the method ``html()`` after populating the
  contents.

* ``Pager`` -- creates a list with an upper limit on number of items
  per page, with links to previous/next if there are more items than
  can be displayed.  The methods ``prev_html()``, ``next_html()``, and
  ``items()`` respectively output the HTML for the link to the
  previous/next pages, and the list proper.

* ``StandardForm`` -- a search bar (form with a single input text
  field) that displays the current search term if there is one.  If
  there are too many search results, this class also generates a
  message below the search bar.  The appearance of the search bar can
  be customized in ``Webkit/form.html``, ``Touch/form.html``, etc.

* ``ResultsContent`` -- used in conjunction with the search bar.  To
  use ResultsContent, do the following:

  #. Create a short HTML template to display individual list items
  (look for examples such as ``calendar/Webkit/items.html``,
  ``calendar/Basic/items.html``).

  #. Create an HTML template to display the output of the
  ``ResultsContent`` within the page.  See an example below.

  #. Construct a new ``ResultsContent($template, $module, ...)``
  object using the name of the first HTML template as the $template
  argument.

  #. ``include`` the second template in the script, and proceed as usual
  with the rest of the script.

For an example of the HTML template for ``ResultsContent`` output,
here is ``people/Webkit/results.html``::

  <?php
    $page->title('People: Details') ->navbar_image('people')
         ->breadcrumbs('Search Results');
       
    $page->content_begin(); 
       
    $content->output($people); 
       
    $page->content_end(); 
  ?>

^^^^^^^^^^^^^^
The Page class
^^^^^^^^^^^^^^

Some important functions in Page:

* ``classify_phone()``: figures out the type of device accessing the page
  by making a web call to the device detection server.

* ``factory($phone_type)``: constructs an object of ``WebkitPage``, 
  ``TouchPage``, or ``BasicPage``, depending on the ``$phone_type`` given.

* ``acquire_begin($name)`` and ``acquire_end($name)``: functions that
  indicate to the page builder to (a) start an output buffer, and (b)
  stop writing to the output buffer and store its contents in a
  private variable. Variations of these functions (especially
  ``content_begin()`` and ``content_end()``) are used in HTML
  templates of all regular modules.

* ``output()``: this function must be called after all of the page's
  private variables have been populated. This function grabs
  ``base.html`` from either ``Webkit``, ``Touch``, or ``Basic``
  directory and populates the PHP variables in ``base.html`` with
  values set in any module logic.  The populated HTML is then echoed
  to the user's screen.

``WebkitPage`` creates pages for smartphones which Webkit browsers such as 
iPhone, Android and webOS.  ``WebkitPage`` produces breadcrumbs for 
navigation at the top of the screen, and the ability to include 
inline and external JavaScript.

``TouchPage`` creates pages for phones with touch screens that do 
not have Webkit browsers, the pages look similar to the Webkit 
pages.  The pages do not have breadcrumbs, however you can 
tap on the modules icon to go to the modules "home"

``BasicPage`` creates pages for all other phones.  These pages 
have navigation links that can be accessed using access keys
on the bottom of the page.  A font 
selector to allow the user to enlarge or shrink the font
is displayed at the bottom.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Statistics Logging (counter.php)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

``page_builder/counter.php`` records page views by device and module
in MySQL, and is used to tally page views from MySQL when the
Statistics sub-module is viewed.

This file includes a class called ``PageViews`` which queries a
similarly-named MySQL table.

^^^^^^^^^^^^^^
Error Handling
^^^^^^^^^^^^^^

``page_header.php`` defines custom Exception types and the behavior to
handle these types of exceptions.  When such an exception is thrown,
the user is directed to a pretty (optimized for their device) error
page, ``error-page/index.php``.  At the same time, the server sends an
email to the developers with a dump of the exception trace, the URL
requested, and the browser ID.

^^^^^^^^^^^^^^^^^^^
Content Restriction
^^^^^^^^^^^^^^^^^^^

If a module needs to be restricted to users via personal certificates,
security.php provides the functions to require HTTPS, and extract
parameters from the user's personal certificate.

Calling the function ``ssl_required()`` will change the transmission
protocol to HTTPS (if it isn't already).  This function also sets the
``mitcertificate`` cookie in the user's browser.

The functions ``get_username()`` and ``get_fullname()`` extract the
user's username and fullname respectively, by searching HTTP headers
generated by the certificate.

For testing and other internal purposes, pages may be restricted to a
specific set of users. This is accomplished with the
``users_restricted()`` function.

.. _section-mobiweb-modules:

================
How Modules Work
================

The full list of modules is controlled by the file
``page_builder/modules.php``. The following attributes are required
for each module:

* Module ID, e.g. ``people``. This is the name of the subdirectory
  holding most of the module's files, as well as the name of images
  (e.g. people.gif for the homescreen icon) used by other parts of the
  site.
* Title, e.g. “People Directory”. This is the name of the module that
  will be shown on the user's homescreen.
* Whether this module is required (cannot be hidden from home screen
  through customization).
* Whether this module is restricted to a subset of devices.

Links to external websites that are not modules can also be added to
the home screen via ``modules.php``.  This is done for the main MIT
website and the website to the certificate server.

-------------------
Directory Structure
-------------------

Each module has a directory structure like the following:

* modulename/

  * index.php
  * other-pages.php
  * supporting_functions.php
  * help.php
  * Webkit/

    * index.html
    * other-pages.html

  * Touch/

    * index.html
    * other-pages.html

  * Basic/

    * index.html
    * other-pages.html

-------------------
How to Add a Module
-------------------

#. Create a module directory, e.g. ``mymodule``.

#. Under ``mymodule/``, create the directories ``Webkit``, ``Touch``, and ``Basic``.

#. Add the module ID and title to the list of modules in ``home/modules.php``.

#. Add any module restrictions to the appropriate lists in ``home/modules.php``.

#. Create the files ``index.php``, ``help.php``, ``Webkit/index.html``, ``Touch/index.html``, and ``Basic/index.html`` as shown below.

#. If any external data sources are used, a file to interact with that data source should be created in ``mobi-lib``.

^^^^^^^^^
index.php
^^^^^^^^^

This file implements the logic for content to show on the module's
home page.  At the beginning of the file there must be a ``require``
statement for the Page Builder header.  At the end of the file there
must be a ``require`` statement for the HTML template to be populated,
and a call to ``$page->output()`` at the very end.  Any code after
this function call will not have any effect.  Here is an example of a
very simple page:

.. code-block:: php

  <?php 
 
  require "../page_builder/page_header.php"; 
 
  $dynamic_text = 'you requested ' . $_REQUEST['query']; 
 
  require "$prefix/index.html"; 
  $page->output(); 
 
  ?>

^^^^^^^^^^
index.html
^^^^^^^^^^

These files under the ``Webkit``, ``Touch`` and ``Basic`` directories are HTML template
files with fields expected to be populated with PHP variable values.
Here is an example of a very simple ``Webkit/index.html`` page that would
work with the example ``index.php`` page above:

.. code-block:: php
 
  <?php 
  $page->title('Sample Module') 
     ->navbar_image('samplemodule') 
     ->breadcrumbs('Sample Module') 
     ->breadcrumb_home(); 
 
  $page->content_begin(); 
  ?> 
        <div class="nonfocal"> 
        <strong>Here is some static text.</strong><br/> 
                <?=$dynamic_text?> 
        </div> 
 
  <? $page->content_end(); ?>


In the above example, the values of PHP variables ``$page`` and
``$dynamic_text`` are determined in ``index.php`` and
``../page_builder/page_header.php``.

The ``Touch/index.html`` page would look like:

.. code-block:: php
 
  <?php 
  $page->title('My Sample Module') 
     ->navbar_image('samplemodule')
     ->breadcrumb_home()
     ->header('Sample Module'); 
 
  $page->content_begin(); 
  ?> 
        <div class="nonfocal"> 
        <strong>Here is some static text.</strong><br/> 
                <?=$dynamic_text?> 
        </div> 
 
  <? $page->content_end(); ?>

The ``Basic/index.html`` page would look like:

.. code-block:: php
 
  <?php 
  $page->title('My Sample Module') 
     ->header('Sample Module'); 
 
  $page->content_begin(); 
  ?> 
        <div class="nonfocal"> 
        <strong>Here is some static text.</strong><br/> 
                <?=$dynamic_text?> 
        </div> 
 
  <? $page->content_end(); ?>

^^^^^^^^
help.php
^^^^^^^^

This file contains the help text that will be displayed when the user
clicks on the "Help" link from any module.  The contents of this help
page, based on our ongoing example, would look like:

.. code-block:: php
 
  <?php 
 
  $header = 'Sample Module'; 
  $module = 'samplemodule'; 
 
  $help = array( 
    'Sample Module shows you the value of a query you requested.', 
    'If you did not request anything, it will not show up.', 
    );
 
  require "../page_builder/help.php"; 
 
  ?>


.. _section-mobiweb-mobi-lib:

====================
Data Connector Files
====================

Files in the ``mobi-lib`` directory of the source tree provide
interfaces to communicate with original data sources.

.. _subsection-mobiweb-AcademicCalendar:

----------------
AcademicCalendar
----------------

File: ``mobi-lib/AcademicCalendar.php``

Depends on :ref:`subsection-mobiweb-mit-calendar`

.. class:: AcademicCalendar

Singleton class that reads .ics files (via
``mobi-lib/mit_ical_lib.php``) and provides methods for retrieving
arrays of events by month and year, dates of terms (semesters), and
student holidays.

--------
DrupalDB
--------

File: ``mobi-lib/DrupalDB.php``

.. class:: DrupalDB

Singleton class for accessing Drupal database.

.. _subsection-mobiweb-EmergencyContacts:

-----------------
EmergencyContacts
-----------------

File: ``mobi-lib/EmergencyContacts.json``

Data file containing list of emergency contact names and numbers.

----------
GTFSReader
----------

File: ``mobi-lib/GTFSReader.php``

.. _subsection-mobiweb-LibraryInfo:

Google Transit File System reader, reads and parses the shuttle 
schedule which is formatted as a GTFS file.

-----------
LibraryInfo
-----------

File: ``mobi-lib/LibraryInfo.php``

Reads information about the Library system, information about each 
library is stored in a database administered with Drupal, and 
the schedule of when the Libraries are open is available as an ical
file from Google Calendar

.. _subsection-mobiweb-NewsOffice:

----------
NewsOffice
----------

File: ``mobi-lib/NewsOffice.php``

Reads XML feeds from the News office. Returns either XML Document objects 
or PHP arrays with the data from the XML packed into the arrays.
Two types of feeds exist: 
 * The most recent news in various categories (i.e. 
   Top News, Campus, Engineering, Science, Management, Architecture, 
   and Humanities)

 * Search results feed on any search given search terms

.. _subsection-mobiweb-NextBusReader:

-------------
NextBusReader
-------------

File: ``mobi-lib/NextBusReader.php``

This is used by the shuttle schedule to read live feeds
from nextbus.com. The feeds give locations and stop prediction times
for currently running shuttles.

.. _subsection-mobiweb-ShuttleSchedule:

---------------
ShuttleSchedule
---------------

File: ``mobi-lib/ShuttleSchedule.php``

This class is used by the shuttle schedule module to read a json file
which has the shuttles schedule route and times.

.. _subsection-mobiweb-StellarData:

-----------
StellarData
-----------

File: ``mobi-lib/StellarData.php``

.. class:: StellarData

Singleton class providing interface to Stellar XML and RSS feeds,
reads and caches data from the Stellar server (see
:ref:`subsubsection-mobiweb-stellar-xml` for details on feed format).
Provides methods to get a list of Courses, subjects under a Course,
and details about a subject.

Additionally provides methods linked to the subscription system for
native app notifications.

Contains a list of hard-coded Course numbers and titles.  We do not
currently know of an external source that provides all these data in
one place.

.. method:: StellarData::get_courses()

Returns a list of all Courses with titles.  For general programs that
are not Courses, the flag ``is_course`` is set to a false value.

.. method:: StellarData::get_others()

Returns a list of Courses whose ID's are not numerical.

.. method:: StellarData::get_subjects($course)

Returns a list of all subjects in the Course ``$course``.  No extra work
is done for subjects that are cross-listed.

.. method:: StellarData::get_subjects_with_xref($course)

Returns a list of all subjects in the Course ``$course``.  For subjects
that are cross-listed, subject details are retrieved for the subject's
master ID.

.. method:: StellarData::get_subject_id($id)

Returns the masterID of the subject listed as ``$id``.

.. method:: StellarData::get_subject_info($id)

Returns detail information about the subject listed as ``$id``.  If
this is not the master ID, detail information is retrieved from the
subject that is this subject's master ID.

.. method:: StellarData::get_announcements($id)

Returns latest public announcements for the subject ``$id``.

.. method:: StellarData::search_subjects($terms)

Searches by Course number if the search term matches a Course ID,
otherwise searches subject titles such that all search tokens are
included.


The following methods are not used by the Mobile Web:

.. method:: StellarData::check_subscriptions($term)

.. method:: StellarData::subjects_with_subscriptions($term)

.. method:: StellarData::subscriptions_for_subject($subject, $term)

.. method:: StellarData::push_subscribe($subject, $term, $device_id, $device_type)

.. method:: StellarData::push_unsubscribe($subject, $term, $device_id, $device_type)

.. _subsection-mobiweb-TimeRange:

---------
TimeRange
---------

File: ``mobi-lib/TimeRange.php``

.. _subsection-mobiweb-campus-map:

----------
campus_map
----------

File: ``mobi-lib/campus_map.php``

Retrieves information about every building on campus, the
information is retreived from a static XML file.

------------
datetime_lib
------------

File: ``mobi-lib/datetime_lib.php``

--
db
--

File: ``mobi-lib/db.php``

Minimal wrapper class for accessing the database, takes care
of connecting to the database with the proper username and
password.

.. _subsection-mobiweb-mit-calendar:

------------
mit_calendar
------------

File: ``mobi-lib/mit_calendar.php``

.. class:: SoapClientWrapper

Wrapper around NuSOAP’s SoapClient class.  Throws DataServerException
when the something fails during communication with the MIT Events SOAP
server.

.. class:: MIT_Calendar

Binds to the WSDL specification for the MIT Events Calendar. The
specification includes the definition of ``EventManager``, ``Event``,
and ``Category`` objects, among other things.

.. method:: MIT_Calendar::Categorys()

Wrapper around EventManager::getCategoryList()

.. method:: MIT_Calendar::Category($id)

Wrapper around EventManager::getCategory($id)

.. method:: MIT_Calendar::subCategorys(Category $category)

Wrapper around EventManager::getCategoryList($cateory->catid)

.. method:: MIT_Calendar::TodaysExhibitsHeaders($date)

Creates search parameters for EventManager to find exhibits (cateogry ID 5).

.. method:: MIT_Calendar::TodaysEventsHeaders($date)

Uses EventManager::getDayEventsHeaders($date) to get a list of events,
then removes events that also appear in a search for
MIT_Calendar::TodaysExhibitsHeaders($date).

.. method:: MIT_Calendar::getEvent($id)

Wrapper around EventManager::getEvent($id) and returns an Event
object.

.. method:: MIT_Calendar::fullTextSearch($text)

Creates search parameters for EventManager to find events with the
fulltext criterion.

.. _subsection-mobiweb-mit-ical-lib:

------------
mit_ical_lib
------------

This class parses and reads ical files.

File: ``mobi-lib/mit_ical_lib.php``

.. _subsection-mobiweb-mit-ldap:

--------
mit_ldap
--------

File: ``mobi-lib/mit_ldap.php``

Provides functions to communicate with LDAP server.

.. method:: email_query($search)

Finds the person whose email address matches the username entered.

.. method:: standard_query($search)

Finds all people whose surname or given name matches all the search
tokens entered.

.. _subsection-mobiweb-rss-services:

------------
rss_services
------------

File: ``mobi-lib/rss_services.php``

RSS helper library.  Provides basic read functionality for a given RSS
feed, as well as the ThreeDown feed defined in the constants.

.. _subsection-mobiweb-tech-cash:

---------
tech_cash
---------

File: ``mobi-lib/tech_cash.php``






******************
Mobile Web Buckets
******************

.. toctree::

   Webkit <mobiweb-buckets/Webkit>
   Touch <mobiweb-buckets/Touch>
   Basic <mobiweb-buckets/Basic>

******************
Mobile Web Modules
******************

.. toctree::

   mobiweb-modules/3down
   mobiweb-modules/calendar
   mobiweb-modules/careers
   mobiweb-modules/customize
   mobiweb-modules/emergency
   mobiweb-modules/libraries
   mobiweb-modules/links
   mobiweb-modules/map
   mobiweb-modules/mobile-about
   mobiweb-modules/people
   mobiweb-modules/shuttleschedule
   mobiweb-modules/sms
   mobiweb-modules/stellar
   mobiweb-modules/techcash
