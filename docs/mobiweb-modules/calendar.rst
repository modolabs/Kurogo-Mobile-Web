.. _section-mobiweb-calendar:

===============
Events Calendar
===============

The Events Calendar allows users to find events from three sources
provided by MIT:

#. Official MIT Events Calendar (http://events.mit.edu)
#. MIT Academic calendar (http://web.mit.edu/registrar/calendar/index.html)
#. Holidays and religious observances
   (http://web.mit.edu/registrar/calendar/religious.html)

When the user accesses the calendar module, they are shown five
options to browse events in addition to a search box. The options are

* Today's events
* Today's exhibits
* Academic calendar
* MIT holidays
* Browse by category

----------------------------
Data Sources / Configuration
----------------------------

The MIT Events Calendar is queried via a SOAP API.  Documentation for
the SOAP interface is at http://events.mit.edu/help/soap/index.html.
The WSDL file specifying the SOAP interface is available at
http://events.mit.edu/MITEventsFull.wsdl

The MIT Academic Calendar is available as an .ics file from the
`Registrar <http://web.mit.edu/registrar/calendar/index.html>`.  Each
.ics file covers one fiscal year (Summer, Fall, IAP, Spring terms).
Only the current and next school year's calendars are linked on the
Registrar's website, but other years follow a naming convention.  We
support multiple .ics files by entering their URLs in Drupal.

^^^^^^^^^^^^^^^^^^^^^
mobi-lib dependencies
^^^^^^^^^^^^^^^^^^^^^

* :ref:`subsection-mobiweb-mit-calendar`
* :ref:`subsection-mobiweb-AcademicCalendar`

-----------
Logic Files
-----------

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/academic.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Reads relevant events for the requested month and year from the class
``AcademicCalendar``.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/calendar_lib.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Convenience methods for creating the URL string of for data sources.

.. method:: day_info($time, $offset)

Gets an array of time formats for the current time (abstracts away
formatting functions in PHP’s own date formatting functions)

.. class:: SearchOptions

Populates menu options for searching by various ranges (next 7 days,
next 15 days, etc.).  Search ranges are statically defined.

.. class:: CalednarForm

Constructs search forms on all pages except the index page.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/category.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Outputs a list of events within a selected category
(chosen from the drill down category list). The list of events in the
category is found by MIT_Calendar::CategoryEventsHeaders(). If the
user types a search term into the search box, the MIT_Calendar
performs a full text within the category using the search term.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/categorys.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Top level drill down list.  Reached by clicking "Browse by" from main page.
Calls ``MIT_Calendar::Categorys()`` to populate list items.

^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/day.php
^^^^^^^^^^^^^^^^^^^^^^^^^

Reached by clicking “Today’s events” and “Today’s exhibits” from the
module home. A list of event objects for the current day is generated
by calling ``MIT_Calendar::TodaysEventsHeaders($today)`` or
``MIT_Calendar::TodaysExhibitsHeaders($today)`` depending on which link
the user clicked.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/detail.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Clicking on a result in any of the list screens brings up an Event
Detail screen showing the event title, location (if any), description,
contact phone (if any), link to external website (if any), and
category.

Event data are provided by ``MIT_Calendar::getEvent($id)``, where
``$id`` is defined in the URL from the previous list screen.

.. function:: mapURL($event)

Creates a link to the Campus Map module if it encounters a building
name in the event location.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/holidays.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Reads events marked as holidays and vacation days from the class
``AcademicCalendar``.

^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/index.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/search.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Outputs a list of events found by
``MIT_Calendar::fullTextSearch($text)``.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/sub-categorys.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Second level drill down list.  Created by clicking a link from
categorys.php to any category that has subcategories.  Calls
``MIT_Calendar::subCategorys($category)`` to popualte list items.


--------------
Template Files
--------------


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/academic.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/category.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/categorys.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/day.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/detail.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/holidays.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/form.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/items.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/religious.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/religious_text.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/search.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/\*/sub-categorys.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


