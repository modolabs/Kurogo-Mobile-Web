##########
Map Module
##########

The map module allows you to browse and search for places and view them on a map.
Places may be grouped by campus, category, and subcategory.

============================
Configuring Campus Locations
============================

If an institution specifies multiple campuses, the home screen will show a list of campus locations.
If there is a single campus, the home screen will show a list of categories to browse by.

Institutions with multiple campuses may specify a list of campus locations in the module-level config file,
`config/module/map.ini`.  The number of campuses must be specified as the value for CAMPUS_COUNT.
Each separate campus has an entry as follows:::

    [campus-0]
    id = boston
    title = "Boston Maps"
    center = "42.3584308,-71.0597732"
    address = "1 Massachusetts Ave., Boston MA 02115"

The value in brackets indicates that this is a campus entry and the 0 indicates that it is the first item in the list.
Other items will be labeled `campus-1`, `campus-2`, and so on.
`id` is a short string to identify the campus.  `center` is the representative latitude and longitude of the campus.

Alternative methods of configuring multiple campuses, and in particular via a KML feed, will be added in the future.

==============
Map View Types
==============

The map module currently supports five types of map views generators.
Google Maps and ArcGIS JavaScript are JavaScript-based maps, and only work on compliant and tablet devices.
Google Static Maps, WMS, and the ArcGIS `export` API are supported for static image-based maps, and work on most devices.
(Exceptions include image URLs that are too for certain devices to support.)

==========================
Configuring Map Data Feeds
==========================

The map module currently supports two types of data sources for getting location information, KML and ArcGIS Server
(KML is recommended, although support for other data source types will be added in the future).
Each data feed is represented as a "category" that a user may browse by from the home screen or within a campus.

The feed configuration file is in `config/feeds/map.ini`.
Each feed has the following fields:

* TITLE is a descriptive name of the category that shows up on the map home screen (for single campuses) 
  or in the campus home screen
* SUBTITLE is an optional brief description that appears in small text alongside the title
* BASE_URL is the URL location of the data source.  This may be a file URL.
* CONTROLLER_CLASS is the data controller class associated with the type of data source.
  If the data source is KML, you should use KMLDataController.  For ArcGIS Server, you should use ArcGISDataController.
* STATIC_MAP_CLASS is the type of static map image used to display the map.
  This field is required as lower-end devices can only use static maps.
  Acceptable values are GoogleStaticMap, ArcGISStaticMap, and WMSStaticMap (these are classes in the Maps package).
* STATIC_MAP_BASE_URL is the base URL of the map image server.  This is not required for Google Static Maps.
* JS_MAP_CLASS is optional and refers to the type of JavaScript map to use on compliant/tablet devices.
  Acceptable values are GoogleJSMap and ArcGISJSMap.
  ArcGISJSMap users must also specify a base URL of the map server as DYNAMIC_MAP_BASE_URL.
* SEARCHABLE is a boolean value that indicates whether or not this data source should be included in search results.
* DEFAULT_ZOOM_LEVEL is the default zoom level of the map image.
* If your insitution has multiple campuses, the CAMPUS field specifies which campus this data feed belongs to.
* If you want your data feed to be included in search results, but do not wish to make it a browseable category,
  you may set the optional HIDDEN value to 1


======================
Configuring Map Search
======================

The default map search traverses all KML feeds that have SEARCHABLE set to 1 and finds all Placemarks with a matching
title (or location for the "nearby" search that occurs on detail pages).
If you have an external search engine, you may override this behavior by subclassing MapSearch in your site lib directory
and specifying your class as MAP_SEARCH_CLASS in `config/module/config.ini`.

