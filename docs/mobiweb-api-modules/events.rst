.. _section-mobiweb-api-events:

===============
Events Calendar
===============

Overview:

* Get a list of all events (minus exhibits) on a given day.
* Get a list of all ongoing exhibits on a given day.
* Get a list of all events within a given category in a given date range.
* Get a list of categories by which events can be tagged.
* Get a list of all events in the Academic Calendar for a given month.
* Get a list of holidays for a given year.
* Get details about a specific event.
* Search for events whose titles match the given search terms.

-------------
API Interface
-------------

All queries to Events use the base URL: http://m.mit.edu/api?

All queries to Events include the following parameter:

**module**: calendar
**command**: *command*

^^^^^^^^^^^^^
Events by Day
^^^^^^^^^^^^^

Get all calendar (minus ongoing exhibits) for a given day.

Parameters:

* **module**: calendar
* **command**: day
* **type**: Events
* [**time**: *time*]

The optional *time* parameter is any unixtime that falls on the day to
be queried.  If *time* is not supplied, the current time is assumed.

Sample Response:

.. code-block:: javascript

  [
    {
      "owner":"1112",
      "shortloc":"E23-205",
      "location":"",
      "status":"N",
      "event":"98761",
      "end":1276919940,
      "id":"11612425",
      "title":"Wellness Class Registration",
      "start":1276833600,
      "cancelled":null,
      "coordinate":{"lat":42.36102212,"lon":-71.08663215},
      "description":""
    },

    ...

  ]


^^^^^^^^^^^^^^^
Exhibits by Day
^^^^^^^^^^^^^^^

Get all ongoing exhibits for a given day.

Parameters:

* **module**: calendar
* **command**: day
* **type**: Exhibits
* [**time**: *time*]

The optional *time* parameter is any unixtime that falls on the day to
be queried.  If *time* is not supplied, the current time is assumed.

Sample Response

.. code-block:: javascript

  [
    {
      "owner":"295",
      "shortloc":"",
      "location":"",
      "status":"N",
      "event":"95456",
      "end":1276919940,
      "id":"11605688",
      "title":"Curated Exhibition of Works by MIT Artists",
      "start":1276833600,
      "cancelled":null,
      "description":""
    },

    ...

  ]

^^^^^^^^^^^^^^^^^^^^
Events in a Category
^^^^^^^^^^^^^^^^^^^^

Get all events in a given category in a given range of dates.

Parameters:

* **module**: calendar
* **command**: category
* **id**: *categoryId*
* [**start**: *startTime*]
* [**end**: *endTime*]

*categoryId* is the numeric ID of the category to be searched.  Use
 the :ref:`subsubsection-mobiweb-api-event-categories` API to get the
 Category IDs.

The optional *startTime* parameter is any unixtime that falls on the
first day of the date range to be queried.  If *startTime* is not
supplied, the current time is assumed.

The optional *endTime* parameter is any unixtime that falls on the
last day of the date range to be queired.  If *endTime* is not
supplied, a search range of one day is assumed.

Sample Response

.. code-block:: javascript

  [
    {
      "owner":"314",
      "shortloc":"E14",
      "location":"Lobby Gallery",
      "status":"N",
      "event":"96380",
      "end":1276660740,
      "id":"11608449",
      "title":"Making Architecture",
      "start":1276574400,
      "cancelled":null,
      "coordinate":{"lat":42.36046359,"lon":-71.08733248},
      "description":""
    },

    ...

  ]


.. _subsubsection-mobiweb-api-event-categories:

^^^^^^^^^^^^^^^^^^
List of Categories
^^^^^^^^^^^^^^^^^^

Get a list of categories, their numeric IDs, and subcategories if any.

Parameters:

* **module**: calendar
* **command**: categories

Sample Response

.. code-block:: javascript

  [
    {
      "name":"Arts/Music/Film",
      "catid":"19",
      "subcategories":[
        {"name":"Dance","catid":"3"},
        {"name":"Exhibits","catid":"5"},
        {"name":"Films/Movies","catid":"8"},
        {"name":"Literary","catid":"11"},
        {"name":"Music","catid":"1"},
        {"name":"New Media Arts","catid":"125"},
        {"name":"Theater","catid":"12"},
        {"name":"Visual Arts","catid":"124"}
      ]
    },
    {"name":"Campus Tours","catid":"52"},
    {
      "name":"Career Development",
      "catid":"24",
      "subcategories":[
        {"name":"Career Fairs/Workshops","catid":"20"},
        {"name":"Computer Training","catid":"21"},
        {"name":"Fellowships/Opportunities","catid":"22"},
        {"name":"Personal Development","catid":"23"}
      ]
    },
    {"name":"Deadlines","catid":"4"},
    {"name":"Diversity & Inclusion","catid":"126"},

    ...

  ]

^^^^^^^^^^^^^
Event Details
^^^^^^^^^^^^^

Get detailed information about a given event.

Parameters:

* **module**: calendar
* **command**: detail
* **id**: *eventId*

*eventId* is the ID of the event, which should come from one of the
 event list API requests above.

Sample Response

.. code-block:: javascript

  {
    "id":11608449,
    "event":96380,
    "title":"Making Architecture",
    "description":"In concert with the opening of SA+P's new Media Lab Complex, designed by Fumihiko Maki, an exhibit on the process of conceiving, designing and realizing the building is on display in the building's lobby gallery at the corner of Ames and Amherst streets on the Cambridge campus. Featuring sketches, drawings, renderings, photos, construction documents and a model, along with smaller displays detailing six other current works by Maki, Making Architecture is on exhibit through October 6.",
    "start":1276574400,
    "end":1276660740,
    "lecturer":"",
    "infoname":"Scott Campbell",
    "infomail":"",
    "infourl":null,
    "infoloc":null,
    "infophone":"253-5380",
    "tickets":null,
    "cost":null,
    "shortloc":"E14",
    "location":"Lobby Gallery",
    "cancelled":null,
    "soldout":null,
    "handicapped":null,
    "priority":null,
    "opento":1,
    "opentext":"",
    "private":null,
    "categories":[
      {"invisible":"0","name":"Exhibits","catid":"5","obsolete":"0"},
      {"invisible":"0","name":"Art/Architecture/Museum","catid":"13","obsolete":"0"},
      {"invisible":"0","name":"Visual Arts","catid":"124","obsolete":"0"},
      {"invisible":"0","name":"New Media Arts","catid":"125","obsolete":"0"}
    ],
    "sponsors":[ ... ],
    "othersponsor":"",
    "owner":314,
    "seriestitle":"",
    "seriesdesc":"",
    "expired":null,
    "created_by":"scottc",
    "created":{"weekday":"Friday","day":5,"month":3,"monthname":"March","year":2010,"hour":22,"minute":4},
    "modified_by":"scottc","modified":{"weekday":"Friday","day":5,"month":3,"monthname":"March","year":2010,"hour":22,"minute":11},
    "type_code":"R",
    "status":"N",
    "patterns":[ ...  ],
    "exceptions":[],
    "coordinate":{"lat":42.36046359,"lon":-71.08733248}
  }


^^^^^^^^^^^^^
Search Events
^^^^^^^^^^^^^

Search for events by title.

Parameters:

* **module**: calendar
* **command**: search
* **q**: *searchTerms*
* [**offset**: *offset*]

*searchTerms* is the URL-encoded string to search.

The optional *offset* parameter is a number that specifies how many
days into the future to search.  The default is 7.

Sample Response (query: "coffee")

.. code-block:: javascript

  {
    "span":"7 days",
    "events":[
      {
        "owner":"2445",
        "shortloc":"w85-001",
        "location":"",
        "status":"N",
        "event":"92851",
        "end":1277344800,
        "id":"11600301",
        "title":"Westgate Coffee Hour",
        "start":1277341200,
        "cancelled":null,
        "description":""
      }
    ]
  }

^^^^^^^^^^^^^^^^^^^^^^^^^^
Academic Calendar by Month
^^^^^^^^^^^^^^^^^^^^^^^^^^

Get all items from the academic calendar for a given month.

Parameters:

* **module**: calendar
* **command**: academic
* [**month**: *month*]
* [**year**: *year*]

The optional *month* parameter is the month to search.  If not
supplied, the current month is assumed.

The optional *year* parameter is the calendar year to search.  If not
supplied, the current year is assumed.

Sample Response

.. code-block:: javascript

  [
    {
      "id":174479444,
      "title":"9:00 am Second-Year and Third-Year Grades Meeting.",
      "start":1275364800,
      "end":1275364800
    },
    {
      "id":69093140,
      "title":"1:00 pm First-Year Grades Meeting.",
      "start":1275451200,
      "end":1275451200
    },
    {
      "id":961271878,
      "title":"Doctoral Hooding Ceremony.",
      "start":1275537600,
      "end":1275537600
    },

    ...

  ]


^^^^^^^^^^^^^^^^
Holidays by Year
^^^^^^^^^^^^^^^^

Get holidays for the given year.

Parameters:

* **module**: calendar
* **command**: holidays
* [**year**: *year*]

The optional *year* parameter is the calendar year to search.  If not
supplied, the current year is assumed.

Sample Response

.. code-block:: javascript

  [
    {
      "id":1484488157,
      "title":"Independence Day -- Holiday.",
      "start":1246593600,
      "end":1246593600
    },
    {
      "id":1853625959,
      "title":"Labor Day -- Holiday.",
      "start":1252296000,
      "end":1252296000
    },
    {
      "id":379218437,
      "title":"Columbus Day -- Holiday.",
      "start":1255320000,
      "end":1255320000
    },

    ...

  ]


---------
PHP Files
---------

mobi-lib/mit_calendar.php
mobi-lib/AcademicCalendar.php
mobi-web/api/index.php
mobi-web/api/calendar.php
