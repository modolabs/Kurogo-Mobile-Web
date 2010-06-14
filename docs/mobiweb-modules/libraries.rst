.. _section-mobiweb-libraries:

=========
Libraries
=========

Hours and Locations for all libraries on campus.  Provides "Ask Us!"
page to ask questions to librarians, and make a research appointment.

iPhone only, due to certificate requirement for Ask Us pages.  

----------------------------
Data Sources / Configuration
----------------------------

List of libraries and locations are stored in Drupal.  Information
about opening hours for each library is entered on Google Calendar,
with one calendar per library.

.ics files retrieved from Google Calendar are cached in ``mobi-lib/cache``.

^^^^^^^^^^^^^^^^^^^^^
mobi-lib dependencies
^^^^^^^^^^^^^^^^^^^^^

* :ref:`subsection-mobiweb-LibraryInfo`
* :ref:`subsection-mobiweb-mit-ical-lib`

-----------
Logic Files
-----------



^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/ask-form.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/ask.php
^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/index.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/libraries_lib.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/location-detail.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/locations.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

--------------
Template Files
--------------


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/Webkit/ask-confirmation.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/Webkit/ask-consultation.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/Webkit/ask-form.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/Webkit/ask.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/Webkit/images
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/Webkit/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/Webkit/libraries.js
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/Webkit/location-detail.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/libraries/Webkit/locations.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

