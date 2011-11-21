##########
Map API
##########

*projectPoint*, *sortGroupsByDistance*, and *staticImageURL* are used primarily
by AJAX functions in the mobile web.

======
index
======

Fetch the list of categories displayed on the first page of the mobile web map
module.  The REST API makes no distinction between feed groups and categories,
but feed groups have a default lat/lon while regular categories do not.

:kbd:`/rest/map/index?v=1`

Sample *response* ::


    {
        "categories": [
            {
                "subtitle": "1 First Ave., New York, NY 10001", 
                "description": "The Manhattan campus...", 
                "title": "New York Maps", 
                "lon": "-74.0059731", 
                "lat": "40.7143528", 
                "id": "newyork", 
                "center": "40.7143528,-74.0059731"
            }, 
            {
                "subtitle": "1 Massachusetts Ave., Boston MA 02115", 
                "description": "The Boston campus...", 
                "title": "Boston Maps", 
                "lon": "-71.0597732", 
                "STATIC_MAP_CLASS": "ArcGISStaticMap", 
                "id": "boston", 
                "lat": "42.3584308", 
                "center": "42.3584308,-71.0597732"
                // ...
            }
        ]
    }

=========
category
=========

Fetch subcategories of a feed group or category, specifed by the *category* 
parameter.


:kbd:`/rest/map/category?references=&category=boston&v=1`

:kbd:`/rest/map/category?references=boston&category=3c3bdfdf53&v=1`

Parameters:

* *category*: ID of parent category, returned either by the *index* API or a 
  previous request to *category*
* *references*: if requesting subcategories of a subcategory, this is a 
  colon-separated list of category IDs of all this subcategory's ancestors.

Sample *response* ::


    {
        "categories": [
            {
                "subtitle": null, 
                "id": "ef48c950f", 
                "title": "Waypoints"
            }, 
            {
                "subtitle": null, 
                "id": "d1f604da1", 
                "title": "Tracks"
            }
        ]
    }



    {
        "placemarks": [
            {
                "subtitle": "", 
                "title": "Alewife Station", 
                "lon": "-71.140981", 
                "lat": "42.394907", 
                "id": 0, 
                "categories": [
                    "ef48c950f"
                ]
            }, 
            {
                "subtitle": "", 
                "title": "Davis Station", 
                "lon": "-71.122055", 
                "lat": "42.396064", 
                "id": 1, 
                "categories": [
                    "ef48c950f"
                ]
            }
        ]
    }

Contents:

The *category* API returns children of the identified category, which may be
subcategories or placemarks.  In the current version, a category will not 
simultaneously have both subcategories and placemarks as children.


=======
search
=======

Search may be performed on a search string or a geographic coordinate.


:kbd:`/rest/map/search?q=<search-terms>&v=1`

:kbd:`/rest/map/search?type=nearby&lat=<lat>&lon=<lon>&v=1`

Parameters:

* *q* - search string.
* *type* - if "nearby", results are returned based on distance from the
  supplied *lat* and *lon* parameters.  *lat* and *lon* are required if *type*
  is "nearby".
* *lat* - latitude to search nearby.
* *lon* - longitude to search nearby.

Sample *response* ::

    {
        "total": 13, 
        "returned": 13, 
        "results": [
            {
                "subtitle": null, 
                "title": "University of Massachusetts Boston", 
                "lon": "-71.039282258243", 
                "lat": "42.313419390847", 
                "id": "8", 
                "categories": [
                    "609a617e86"
                ]
            }, 
            {
                "subtitle": null, 
                "title": "South Boston / South Boston Waterfront", 
                "lon": -70.995660874563001, 
                "lat": 42.338090356864001, 
                "id": "South Boston / South Boston Waterfront", 
                "categories": [
                    "d1142ded2b"
                ]
            }
            // ...
        ]
    }


========
detail
========

:kbd:`/rest/map/detail?id=1&category=05bc9c448&references=1e61bad385:boston&v=1

Parameters:

* *id*: placemark ID
* *category*: ID of parent category, returned either by the *index* API or a 
  previous request to *category*
* *references*: if requesting subcategories of a subcategory, this is a 
  colon-separated list of category IDs of all this subcategory's ancestors.

Sample *response* ::

    {
        "id": "0",
        "title": "Watson Hall",
        "subtitle": null,
        "address": "88 Main Street",
        "details": {
            "description": "some descriptive string...",
            "custom_field": "some custom value",
            "number of floor plans": "6"
        },
        "lat": 43.083768777778,
        "lon": -77.669150888889,
        "geometryType": "polygon",
        "geometry": [
            [
                {
                    "lon": -77.668855,
                    "lat": 43.083847,
                    "altitude": 0
                },
                {
                    "lon": -77.669024,
                    "lat": 43.083853,
                    "altitude": 0
                },
                {
                    "lon": -77.668855,
                    "lat": 43.083847,
                    "altitude": 0
                }
                // ...
            ]
        ]
    }

Contents:

* *id* - placemark ID within the requested category.
* *title* - placemark display title.
* *subtitle* - placemark display subtitle. May be null.
* *address* - placemark street address. May be null.
* *details* - a dictionary of arbitrary string fields and values describing the
  placemark's attributes.
* *lat* - latitude.
* *lon* - longitude.
* *geometryType* - "point", "polyline", or "polygon".
* *geometry* - coordinates of the placemark's geometry. If *geometryType* is
  "point", the coordinates will be a dictionary containing "lat" and "lon"
  keys. If *geometryType* is "polyline", the coordinates will be an array of
  such dictionaries. If *geometryType* is "polygon", the coordinates will be
  an array of arrays of point dictionaries, from outermost to innermost rings.

=============
projectPoint
=============

Used on mobile web by compliant browsers for overlaying geographic 
(latitude/longitude) data on projected base maps

:kbd:`/rest/map/projectPoint?from=4325&to=102113&lat=42.31342&lon=-71.03928`

Sample *response* ::


    {
        "lat": "42.31342", 
        "lon": "-71.03928"
    }

=======================
sortGroupsByDistance
=======================

Used on mobile web by compliant browsers to get the list of map feed groups
ordered by proximity to the user's location.

:kbd:`/rest/map/sortGroupsByDistance?lat=42.31342&lon=-71.03928`

Sample *response* ::

    [
        {
            "id": "boston", 
            "title": "Boston Maps"
        }, 
        {
            "id": "newyork", 
            "title": "New York Maps"
        }
    ]


================
staticImageURL
================

Used on mobile web by compliant browsers to zoom and pan static maps.

:kbd:`/rest/map/staticImageURL?baseURL=<base-url>&mapClass=<map-class>&query=<query>&overrides=<overrides>&zoom=<zoom>`

:kbd:`/rest/map/staticImageURL?baseURL=<base-url>&mapClass=<map-class>&query=<query>&scroll=<scroll>`

Parameters

* *baseURL* (optional) - the base URL of the server hosting the base map
* *query* (optional) - a URL-encoded query string from the previous/current
  image URL
* *overrides* (optional) - a URL-encoded query string that can be used to 
  override the parameters in *query*
* *zoom* (optional) - "in" or "out"
* *scroll* (optional) - one of "n", "s", "e", "w", "nw", "sw", "ne", "se"

Sample *response*: ::

    "http:\/\/maps.google.com\/maps\/api\/staticmap?center=43.155138571972%2C-75.214747079923&size=1553x495&markers=icon%3Ahttp%3A%2F%2Fmaps.google.com%2Fmapfiles%2Fkml%2Fpushpin%2Fylw-pushpin.png%7C43.15513857197247%2C-75.21474707992344&zoom=10&sensor=false&format=png"


