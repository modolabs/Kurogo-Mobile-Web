.. _section-mobiweb-calendar:

===============
Events Calendar
===============

-----------
Description
-----------

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

The MIT Academic Calendar is available as an .ics file from the
Registrar's website.  Each .ics file covers one fiscal year (Summer,
Fall, IAP, Spring terms).  We support multiple .ics files by entering
their URLs in Drupal.

-----------
Logic Files
-----------

^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-lib/mit_calendar.php
^^^^^^^^^^^^^^^^^^^^^^^^^

.. class:: SoapClientWrapper

Wrapper around NuSOAP’s SoapClient class.  Throws DataServerException
when the something fails during communication with the MIT Events SOAP
server.

.. class:: MIT_Calendar

Binds to the WSDL specification for the MIT Events Calendar, defined
at http://events.mit.edu/MITEventsFull.wsdl. The specification
includes the definition of ``EventManager``, ``Event``, and
``Category`` objects, among other things.

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

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-lib/AcademicCalendar.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. class:: AcademicCalendar

Singleton class that reads .ics files (via
``mobi-lib/mit_ical_lib.php``) and provides methods for retrieving
arrays of events by month and year, dates of terms (semesters), and
student holidays.

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

^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/day.php
^^^^^^^^^^^^^^^^^^^^^^^^^

Reached by clicking “Today’s events” and “Today’s exhibits” from the
module home. A list of event objects for the current day is generated
by calling ``MIT_Calendar::TodaysEventsHeaders($today)`` or
``MIT_Calendar::TodaysExhibitsHeaders($today)`` depending on which link
the user clicked.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/search.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Outputs a list of events found by
``MIT_Calendar::fullTextSearch($text)``.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/categorys.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Top level drill down list.  Reached by clicking "Browse by" from main page.
Calls ``MIT_Calendar::Categorys()`` to populate list items.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/sub-categorys.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Second level drill down list.  Created by clicking a link from
categorys.php to any category that has subcategories.  Calls
``MIT_Calendar::subCategorys($category)`` to popualte list items.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/category.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Outputs a list of events within a selected category
(chosen from the drill down category list). The list of events in the
category is found by MIT_Calendar::CategoryEventsHeaders(). If the
user types a search term into the search box, the MIT_Calendar
performs a full text within the category using the search term.

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
mobi-web/calendar/academic.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Reads relevant events for the requested month and year from the class
``AcademicCalendar``.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/holidays.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Reads events marked as holidays and vacation days from the class
``AcademicCalendar``.

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

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/calendar/*/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

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



