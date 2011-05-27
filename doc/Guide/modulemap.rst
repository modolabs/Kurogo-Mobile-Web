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

Campuses are configured in the *SITE_DIR/config/map/feedgroups.ini* file. Each separate campus has an section as follows:::

    [boston]
    title = "Boston Maps"
    center = "42.3584308,-71.0597732"
    address = "1 Massachusetts Ave., Boston MA 02115"
    description = "Description"

* The section title is the *id*, a short string to identify the campus. 
* *center* is the representative latitude and longitude of the campus.
* You can also include *address* and *description*

==============
Base Map Types
==============

The map module currently supports five types of base maps.

JavaScript based maps (compliant and tablet only)
-------------------------------------------------

* `Google Maps <http://code.google.com/apis/maps/documentation/javascript/reference.html>`_
* `ArcGIS JavaScript <http://help.arcgis.com/en/webapi/javascript/arcgis/help/jsapi_start.htm>`_

Static image based maps
-----------------------
* `Google Static Maps <http://code.google.com/apis/maps/documentation/staticmaps/>`_ 
* `WMS <http://portal.opengeospatial.org/files/?artifact_id=14416>`_
* `ArcGIS *export* API <http://help.arcgis.com/en/arcgisserver/10.0/apis/rest/exportimage.html>`_


================
Map Data Sources
================

The map module currently supports three types of geo data formats:

* `KML <http://code.google.com/apis/kml/documentation/kmlreference.html>`_ 
* `ArcGIS Server <http://resources.esri.com/help/9.3/arcgisserver/apis/rest/>`_ 
* `Shapefiles <http://en.wikipedia.org/wiki/Shapefile>`_

Any of these sources may be used with any of the base maps listed above.

==========================
Configuring Map Data Feeds
==========================

Each data feed is represented as a *category* that a user may browse by from the home screen or within a campus.

The feed configuration file is in *SITE_DIR/config/map/feeds-CAMPUS.ini* (where CAMPUS is the name of the campus section
in the feedgroups.ini file). Each feed has the following fields:

* *TITLE* is a descriptive name of the category that shows up on the map home screen (for single campuses) 
  or in the campus home screen
* *SUBTITLE* is an optional brief description that appears in small text alongside the title
* *BASE_URL* is the URL location of the data source.  This may be a file URL. (i.e. a path)
* *CONTROLLER_CLASS* is the data controller class associated with the type of data source.
  If the data source is KML, you should use *KMLDataController*.  For ArcGIS Server, you should use *ArcGISDataController*.
* *STATIC_MAP_CLASS* is the type of static map image used to display the map.
  This field is required as lower-end devices can only use static maps. Acceptable values are:

  * GoogleStaticMap
  * ArcGISStaticMap
  * WMSStaticMap
  
* *STATIC_MAP_BASE_URL* is the base URL of the map image server. This is not required for Google Static Maps.
* *JS_MAP_CLASS* is optional and refers to the type of JavaScript map to use on compliant/tablet devices.
  Acceptable values:

  * GoogleJSMap
  * ArcGISJSMap
  
* *DYNAMIC_MAP_BASE_URL* is the base URL of the map image server. This is not required for Google JavaScript Maps.
* *SEARCHABLE* is a boolean value that indicates whether or not this data source should be included in search results.
* *DEFAULT_ZOOM_LEVEL* is the default zoom level of the map image.
* If your insitution has multiple campuses, the *CAMPUS* field specifies which campus this data feed belongs to.
* If you want your data feed to be included in search results, but do not wish to make it a browseable category,
  you may set the optional *HIDDEN* value to 1

======================
Example Configurations
======================

Data Sources
------------


**KML Data Source**

::

  TITLE              = "My Placemarks"
  BASE_URL           = "http://example.com/feed.kml"
  CONTROLLER_CLASS   = KMLDataController

**ArcGIS Server Data Source**

When working with an ArcGIS Server instance with multiple layers, an *ARCGIS_LAYER_ID* may be specified.  The default layer is 0.

::

  TITLE                = "My ArcGIS Data"
  BASE_URL             = "http://path/to/service/MapServer"
  ARCGIS_LAYER_ID      = 2
  CONTROLLER_CLASS     = ArcGISDataController

**Shapefile Data Source**

Currently, shapefiles must be saved locally on the machine.
Due to the multi-file naming scheme of shapefiles, we require that the
path be specified without an extension.  In the following example, the
shapefile from http://www.mass.gov/mgis/biketrails.htm was unarchived
and the files biketrails_arc.shp, biketrails_arc.dbf, and biketrails_arc.prj
were placed in the directory DATA_DIR"/biketrails".  Only the .shp, .dbf,
and .prj (if any) files are required.

::

  TITLE              = "Massachusetts Bike Trails"
  BASE_URL           = DATA_DIR"/biketrails/biketrails_arc"
  CONTROLLER_CLASS   = ShapefileDataController

Static Base Maps
----------------

If a dynamic map is used for compliant/tablet devices, these 
configurations determine the appearance of maps on touch and basic 
devices.  If no dynamic map is specified, they determine the 
appearance of maps on all devices.

**Google Static Maps**

This is the default base map.  If you do not specify anything for 
*STATIC_MAP_CLASS*, this is equivalent to specifying GoogleStaticMap
for basic and touch devices.  Additionally, if *JS_MAP_CLASS* is also
omitted, Google Static Maps will used for compliant/tablet devices.

**Web Map Service (WMS)**

::

  STATIC_MAP_CLASS      = WMSStaticMap
  STATIC_MAP_BASE_URL   = "http://path/to/WMS/server"

Note that it is not possible to add annotations to WMS maps.

**ArcGIS Exported Maps**

::

  STATIC_MAP_CLASS     = ArcGISStaticMap
  STATIC_MAP_BASE_URL  = "http://path/to/service/MapServer"

Note that it is not possible to add annotations to exported images.

JavaScript Base Maps
--------------------

If specified, these configurations determine the appearance of maps
on tablet and compliant devices.

**Google Maps**

::

  JS_MAP_CLASS       = GoogleJSMap

**ArcGIS**

::

  JS_MAP_CLASS         = ArcGISJSMap
  DYNAMIC_MAP_BASE_URL = "http://path/to/service/MapServer"


======================
Configuring Map Search
======================

The default map search traverses all feeds that have SEARCHABLE set to 1 and finds all Placemarks with a matching
title (or location for the "nearby" search that occurs on detail pages).
If you have an external search engine, you may override this behavior by subclassing MapSearch in your site lib directory
and specifying your class as MAP_SEARCH_CLASS in *SITE_DIR/config/map/module.ini*

