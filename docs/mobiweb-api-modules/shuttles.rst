.. _section-mobiweb-api-shuttles:

========
Shuttles
========

Overview:




-------------
API Interface
-------------

All queries to Shuttles use the base URL: http://m.mit.edu/api/shuttles

All queries to Shuttles include the following parameter:

* **command**: *command*

^^^^^^^^^^^^^^^^^^
List of All Routes
^^^^^^^^^^^^^^^^^^

Parameters:

* **command**: routes
* [ **compact**: true ]

If the optional **compact** parameter is set to "true", the resulting
JSON response does not include the list of stops on each route.  It is
recommended to use the compact version of the ``routes`` query, and
fetch stop lists in individual route queries, as the non-compact
version returns several kilobytes of data.

Sample Response (compact version):

.. code-block:: javascript

  [
    {
      "route_id":"saferidecamball",
      "title":"Cambridge All",
      "interval":60,
      "isSafeRide":true,
      "isRunning":false,
      "summary":"Runs 6pm-2am Sun-Wed, 6pm-3am Thu-Sat, during summer and holiday breaks."
    },
    {
      "route_id":"saferidebostonall",
      "title":"Boston All",
      "interval":60,
      "isSafeRide":true,
      "isRunning":false,
      "summary":"Runs 6pm-2am Sun-Wed, 6pm-3am Thu-Sat, during summer and holiday breaks."},

    ...

  ]

Sample Response (non-compact version):

.. code-block:: javascript

  [
    {
      "route_id":"saferidecamball",
      "title":"Cambridge All",
      "interval":60,
      "isSafeRide":true,
      "isRunning":false,
      "summary":"Runs 6pm-2am Sun-Wed, 6pm-3am Thu-Sat, during summer and holiday breaks.",
      "stops":[
        {
          "title":"84 Mass Ave",
          "lat":"42.3595199",
          "lon":"-71.09416",
          "direction":"frcamp",
          "path":[
            {"lat":"42.35952","lon":"-71.09416"},
            {"lat":"42.3595","lon":"-71.09407"},

            ...

          ],
          "id":"mccrmk"
        },
        {
          "title":"Burton House",
          "lat":"42.3560823",
          "lon":"-71.098703",
          "direction":"frcamp",
          "path":[ ... ],
          "id":"burtho"
        },
      ]
    },
    {
      "route_id":"saferidebostonall",
      ...
    },

    ...

  ]

^^^^^^^^^^^^^^^^^^
Details of a Route
^^^^^^^^^^^^^^^^^^

Get detailed info about a route, including all shuttle stops.

Parameters:

* **command**: routeInfo
* **id**: *routeId*
* [ **full**: true ]

If the optional **full** parameter is supplied and set to "true", path
locations are returned with the list of stops.  It is recommended to
use this query to get route path locations for individual routes,
instead of using the ``routes`` command.

Sample Response (full version):

.. code-block:: javascript

  {
    "route_id":"tech",
    "title":"Tech Shuttle",
    "interval":20,
    "isSafeRide":false,
    "isRunning":true,
    "summary":"Runs weekdays 7:15am-7:15pm, all year round.",
    "stops":[
      {
        "id":"kendsq_d",
        "title":"Kendall Square T",
        "lat":"42.36237",
        "lon":"-71.08613",
        "next":1276891058,
        "predictions":[1229,2432,3636,4840],
        "direction":"wcamp",
        "path":[
          {"lat":"42.36237","lon":"-71.08613"},
          {"lat":"42.3623199","lon":"-71.0854899"},
          ...
        ]
      },

      ...

    ],
    "gpsActive":true,
    "vehicleLocations":[
      {"lat":"42.3562299","lon":"-71.09838","secsSinceReport":4,"heading":"244"}
    ],
    "now":1276890437
  }

Sample Response (non-full version):

.. code-block:: javascript

  {
    "stops":[
      {
        "id":"kendsq_d",
        "title":"Kendall Square T",
        "lat":"42.36237",
        "lon":"-71.08613",
        "next":1276891031,
        "predictions":[1232,2435,3639,4843]
      },
      {
        "id":"amhewads",
        "title":"Amherst/Wadsworth",
        "lat":"42.3612723",
        "lon":"-71.0843897",
        "next":1276891098,
        "predictions":[1218,2422,3626,4830]
      },

      ...
    ]

    "gpsActive":true,
    "vehicleLocations":[
      {"lat":"42.35911","lon":"-71.0937","secsSinceReport":39,"heading":"152"}
    ],
    "now":1276890267
  }

^^^^^^^^^^^^^^^^^
List of All Stops
^^^^^^^^^^^^^^^^^

Get a list of all physical shuttle stops.  Unlike the route commands,
there are no predicted times, as those times are tied to the route.
Additionally, a list of route ID's associated with each stop are
included in the JSON response.

Parameters:

* **command**: stops

Sample Response:

.. code-block:: javascript

  [
    {
      "title":"84 Mass Ave",
      "lon":"-71.09416",
      "lat":"42.3595199",
      "id":"mass84_d",
      "routes":["boston","saferidebostonall","saferidecamball"]
    },
    {
      "title":"Mass Ave at Beacon St",
      "lon":"-71.0896299",
      "lat":"42.3510298",
      "id":"massbeac",
      "routes":["boston","saferidebostonall"]
    },

    ...

  ]

^^^^^^^^^^^^^^^^^
Details of a Stop
^^^^^^^^^^^^^^^^^

Get details about a single stop, including predictions for all
associated routes.

Parameters:

* **command**: stopInfo
* **id**: *stopId*

*stopId* is the ID of the stop to be returned.

Sample Response:

.. code-block:: javascript

  {
    "stops":[
      {
        "id":"kendsq_d",
        "title":"Kendall Square T",
        "lat":"42.36237",
        "lon":"-71.08613",
        "next":1276891499,
        "predictions":[1200,2400,3600,4800],
        "route_id":"northwest",
        "gps":true
      },
      {
        "id":"kendsq_d",
        "title":"Kendall Square T",
        "lat":"42.36237",
        "lon":"-71.08613",
        "next":1276891007,
        "predictions":[1238,2442,3646,4850],
        "route_id":"tech",
        "gps":true
      }
    ],
    "now":1276890909
  }
