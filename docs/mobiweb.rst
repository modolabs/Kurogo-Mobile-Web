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

=====================================
Contents of the MIT Mobile Web Source
=====================================

* ``web/`` -- main directory of Mobile Web files.

  * ``3down/`` -- Services Status module
  * ``about/`` -- desktop About site.
  * ``calendar/`` -- Events Calendar module.
  * ``careers/`` -- Careers module.
  * ``emergency/`` -- Emergency Info module.
  * ``error-page/`` -- Error page to show user when something goes
    wrong.
  * ``home/`` -- Home Screen.
  * ``ip/`` -- HTML templates, CSS, JS, and image files for
    iPhone-like devices.
  * ``links/`` -- Useful Links module.
  * ``map/`` -- Campus Map module.
  * ``mobile-about/`` -- mobile About module.
  * ``page_builder/`` -- Page Builder package.
  * ``people/`` -- People Directory module.
  * ``shuttleschedule/`` -- Shuttles module.
  * ``sms/`` -- SMS overview module.
  * ``sp/`` -- HTML templates, CSS, and image files for smartphones
    and featurephones.
  * ``stellar/`` -- Course Info module.
  * ``techcash/`` -- TechCASH module.

* ``lib/`` -- library files that interact with data sources, also used
  by the SMS system.

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
footer) are defined in top-level template files ``ip/base.html`` and
``sp/base.html``.

-----------
Home Screen
-----------

The Home Screen displays a grid of icons or linear list of links to
all modules the user has access to based on their device.  The grid
view is shown on the iPhone and Android; all other devices (including
computers) are shown the list view.  We also use the home screen to
inform user about new features on the site.

The main request handler (``home/index.php``) is required to perform
three sequential tasks:

#. Check if there have been new announcements since the user last
visited the site.  This is handled by the ``WhatsNew`` class in the
About This Site (``mobile-about``) module, which reads the user's
``whatsnewtime`` cookie.  If the latest announcement is less than 2
weeks old, all users are shown a red badge or other indication on the
home screen.

#. Figure out which module links to display, and in what order.  This
is done in the class ``Modules`` in ``modules.php``.  ``Modules``
provides the following:

  * List of device-independent modules and device-dependent modules
  * Default list of modules for a given device type
  * URL for each module in the list (most are the directory by the
    same name as the module ID). If the URL needs to be rewritten for
    the certificates interstitial page, this is handled by
    ``home/index.php``.
  * Method to reorder/hide/show modules based on the list given in
    the user’s cookies.

#. Display the module links in a grid or linear layout using the
appropriate template for the device.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Web Certificates interstitial page
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If a user does not have the ``mitcertificate`` cookie, links to
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

^^^^^^^^^^^^^
activemodules
^^^^^^^^^^^^^

This cookie is a list of all the modules that should be shown on the
user’s home screen. If the user is visiting our website for the first
time, this cookie is automatically populated with the default list of
modules.  Users can change the contents of this cookie by going to
Customize Home Screen, which rewrites the cookie as the user checks
and unchecks desired modules.

^^^^^^^^^^^
moduleorder
^^^^^^^^^^^

This cookie is a list of modules in the order they should be shown on
the user’s home screen. As with activemodules, it is populated with
the default module order upon the user’s first visit, and can be
changed via the Customize module.

^^^^^^^^^^^^
whatsnewtime
^^^^^^^^^^^^

This cookie is populated when the user access the "What’s New" section
of About this Site (or clicking directly on the "What’s New" link on
smartphones/featurephones.  It stores the current timestamp and is
read by the Home Screen to determine whether new announcements should
be highlighted for the user.

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

The directory ``web/trunk/page_builder`` contains the logic to build
pages.  The page builder looks for files in the directories
``web/trunk/ip``, ``web/trunk/sp``, and ``web/trunk/fp``, which
respectively contain static media required to assemble pages for
iPhones, smartphones, and featurephones.

-----------
Page Header
-----------

For an overview of the content generation process, it is instructive
to look at the file ``page_builder/page_header.php`` which is required
in every module other than the home screen.  It does the following:

#. Load commonly used functions (from ``page_tools.php``).

#. Create a ``Page`` object (actually ``WebkitPage`` or
``notIPhonePage``), which controls the rest of page construction.

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
  be customized in ``ip/form.html`` and ``sp/form.html``.

* ``ResultsContent`` -- used in conjunction with the search bar.  To
  use ResultsContent, do the following:

  #. Create a short HTML template to display individual list items
  (look for examples such as ``calendar/ip/items.html``,
  ``calendar/sp/items.html``).

  #. Create an HTML template to display the output of the
  ``ResultsContent`` within the page.  See an example below.

  #. Construct a new ``ResultsContent($template, $module, ...)``
  object using the name of the first HTML template as the $template
  argument.

  #. ``include`` the second template in the script, and proceed as usual
  with the rest of the script.

For an example of the HTML template for ``ResultsContent`` output,
here is ``people/ip/results.html``::

  <?php
    $page->title(’People: Details’) ->navbar_image(’people’)
         ->breadcrumbs(’Search Results’);
       
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

* ``factory($phone_type)``: constructs an object of ``WebkitPage`` or
  ``notIPhonePage``, depending on the ``$phone_type`` given.

* ``acquire_begin($name)`` and ``acquire_end($name)``: functions that
  indicate to the page builder to (a) start an output buffer, and (b)
  stop writing to the output buffer and store its contents in a
  private variable. Variations of these functions (especially
  ``content_begin()`` and ``content_end()``) are used in HTML
  templates of all regular modules.

* ``output()``: this function must be called after all of the page’s
  private variables have been populated. This function grabs
  ``base.html`` from either the ``ip`` or ``sp`` directory and
  populates the PHP variables in ``base.html`` with values set in any
  module logic.  The populated HTML is then echoed to the user’s
  screen.

``WebkitPage`` creates pages for the iPhone and Android.  These are
almost identical, but we also take advantage of iPhone-only features
such as certificates, rotation, and drag+drop.  ``WebkitPage``
produces breadcrumbs for navigation at the top of the screen, and the
ability to include inline and external JavaScript.

``notIPhonePage`` creates pages for smartphones and featurephones,
which differ from each other by font size, line height, image size,
image type, and number of items shown in paged lists.  Navigation
links are shown at the bottom.

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
parameters from the user’s personal certificate.

Calling the function ``ssl_required()`` will change the transmission
protocol to HTTPS (if it isn’t already).  This function also sets the
``mitcertificate`` cookie in the user’s browser.

The functions ``get_username()`` and ``get_fullname()`` extract the
user’s username and fullname respectively, by searching HTTP headers
generated by the certificate.

For testing and other internal purposes, pages may be restricted to a
specific set of users. This is accomplished with the
``users_restricted()`` function.

================
How Modules Work
================

The full list of modules is controlled by the file
``page_builder/modules.php``. The following attributes are required
for each module:

* Module ID, e.g. ``people``. This is the name of the subdirectory
  holding most of the module’s files, as well as the name of images
  (e.g. people.gif for the homescreen icon) used by other parts of the
  site.
* Title, e.g. “People Directory”. This is the name of the module that
  will be shown on the user’s homescreen.
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
  * ip/

    * index.html
    * other-pages.html

  * sp/

    * index.html
    * other-pages.html

-------------------
How to Add a Module
-------------------

#. Create a module directory, e.g. ``mymodule``.

#. Under ``mymodule/``, create the directories ``ip`` and ``sp``.

#. Add the module ID and title to the list of modules in ``home/modules.php``.

#. Add any module restrictions to the appropriate lists in ``home/modules.php``.

#. Create the files ``indes.php``, ``help.php``, ``ip/index.html`` and
``sp/index.html`` as shown below.

^^^^^^^^^
index.php 
^^^^^^^^^

This file implements the logic for content to show on the module's
home page.  At the beginning of the file there must be a ``require``
statement for the Page Builder header.  At the end of the file there
must be a ``require`` statement for the HTML template to be populated,
and a call to ``$page->output()`` at the very end.  Any code after
this function call will not have any effect.  Here is an example of a
very simple page::

  <?php 
 
  require "../page_builder/page_header.php"; 
 
  $dynamic_text = ’you requested ’ . $_REQUEST[’query’]; 
 
  require "$prefix/index.html"; 
  $page->output(); 
 
  ?>

^^^^^^^^^^
index.html
^^^^^^^^^^

These files under the ``ip`` and ``sp`` directories are HTML template
files with fields expected to be populated with PHP variable values.
Here is an example of a very simple ``ip/index.html`` page that would
work with the example ``index.php`` page above::
 
  <?php 
  $page->title(’Sample Module’) 
     ->navbar_image(’samplemodule’) 
     ->breadcrumbs(’Sample Module’) 
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

Similarly, the ``sp/index.html`` page would look like::
 
  <?php 
  $page->title(’My Sample Module’) 
     ->header(’Sample Module’); 
 
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
page, based on our ongoing example, would look like::
 
  <?php 
 
  $header = ’Sample Module’; 
  $module = ’samplemodule’; 
 
  $help = array( 
    ’Sample Module shows you the value of a query you requested.’, 
    ’If you did not request anything, it will not show up.’, 
    );
 
  require "../page_builder/help.php"; 
 
  ?>

******************
Mobile Web Modules
******************

.. toctree::

   3DOWN <mobiweb-modules/3down>
   Events Calendar <mobiweb-modules/calendar>
   Student Career Services <mobiweb-modules/careers>
   Customize Home Screen <mobiweb-modules/customize>
   Emergency Info <mobiweb-modules/emergency>
   Useful Links <mobiweb-modules/links>
   Campus Map <mobiweb-modules/map>
   About This Site <mobiweb-modules/mobile-about>
   People Directory <mobiweb-modules/people>
   Shuttle Schedule <mobiweb-modules/shuttleschedule>
   SMS <mobiweb-modules/sms>
   Stellar <mobiweb-modules/stellar>
