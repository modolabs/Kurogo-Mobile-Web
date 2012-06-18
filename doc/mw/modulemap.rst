##########
Map Module
##########

The map module allows you to browse and search for places and view them on a map.
Places may be grouped by feed groups, category, and subcategory.

====================
Basic Configuration
====================

------------
Feed Groups
------------

Each feed group corresponds to a base map, characterized by center location
and background appearance.  For organizations with multiple campuses, feed
groups are a logical way to split up maps by campus.

Groups are configured in the *SITE_DIR/config/map/feedgroups.ini* file.  There
must be at least one entry that looks like the following: ::

    [groupID]
    title                = "Title of my map"
    subtitle             = "Subtitle of my map"
    center               = "42.3584308,-71.0597732"
    DEFAULT_ZOOM_LEVEL   = 10

* *[groupID]* is a short, alphanumeric identifier for the group, e.g. "boston",
  "newyork", "daytime", etc.
* *title* (required) - a short description of this group, e.g. "Boston Campus"
* *subtitle* (optional) - more explanatory text if title is not sufficient.
* *center* (required) - the latitude, longitude point that represents the 
  center of this group. *No spaces* are allowed before and after the comma.
* *DEFAULT_ZOOM_LEVEL* (recommended) - initial zoom level to display the map 
  at. These are numbers generally between 0 and 21. Very roughly, each zoom 
  level covers the following extents:

  * 0: earth
  * 1: hemisphere
  * 2: multiple continents
  * 3: one continent
  * 4: large country
  * 5: medium-sized country
  * 6: small country or large state
  * 7: small state
  * 8: tiny state
  * 9: metropolitan area + vicinity
  * 10: metropolitan area
  * 11: large city
  * 12: small city
  * 13: town
  * 14: several neighborhoods
  * 15: a few neighborhood
  * 16: neighborhood
  * 17: several blocks
  * 18: a few blocks
  * 19: several buildings
  * 20: a few buildings
  * 21: a building

The base map used by default is Google Maps on compliant/tablet devices, and
Google Static Maps on basic/touch devices. Kurogo also supports ArcGIS 
JavaScript maps on compliant/tablet devices, and ArcGIS static maps and WMS on
basic devices. See the Advanced Configuration section for details.

-----------
Data Feeds
-----------

Each data feed is represented as a *category* that a user may browse by from 
the home screen or within a campus.

For people who do not have any existing mapping infrastructure, we generally 
recommend creating feeds using `Google Earth <http://earth.google.com>`_. 
Google Earth allows you to add pins to the map and export the result as a 
`KML file <http://code.google.com/apis/kml/>`_.

To add feeds to a feed group, create an entry for it in the file 
*SITE_DIR/config/map/feeds-GROUP.ini* (where GROUP is the id of the group from 
feedgroups.ini). Each entry should look like the following: ::

    [index]
    TITLE            = "Fun Places in Washington"
    SUBTITLE         = "These are places I like to check out on vacation"
    BASE_URL         = DATA_DIR"/washington.kml"
    SEARCHABLE       = 1

* *index* is a number (starting from 0) indicating the sort order for this feed.
* *TITLE* is a descriptive name of the category that shows up in the list of
  categories
* *SUBTITLE* (optional) is brief description that appears in small text
  alongside the title
* *BASE_URL* (required) is the URL or file location of the data source.
* *SEARCHABLE* - just set this to 1.

*Note*: if you use a feed in .kmz instead of .kml format, you *must* also
specify the following in the entry: ::

    RETRIEVER_CLASS       = "KMZDataRetriever"

Kurogo also supports data from the ArcGIS REST API and the ESRI Shapefile 
format. If you use any of these formats, or are looking to change other aspects
of the data feeds and base maps, see the Advanced Configuration section.

----------------------
User Interface Options
----------------------

The following options appear by default in *SITE_DIR/config/map/module.ini*: ::

    BOOKMARKS_ENABLED          = 1
    MAP_SHOWS_USER_LOCATION    = 1
    SHOW_DISTANCES             = 1
    DISTANCE_MEASUREMENT_UNITS = "Imperial"
    SHOW_LISTVIEW_BY_DEFAULT   = 0

* *BOOKMARKS_ENABLED* - whether or not the user can bookmark locations.
* *MAP_SHOWS_USER_LOCATION* - whether or not the user can display the location
  marker on the map. This does not turn off geolocation in general.
* *SHOW_DISTANCES* - whether or not the list of nearby places displays
  distance from the searched location.
* *DISTANCE_MEASUREMENT_UNITS* - either "Metric" or "Imperial".
* *SHOW_LISTVIEW_BY_DEFAULT* - if there is only one campus, the index page of
  the map module will show a full screen map view on compliant devices. If this
  value is set to 1, the index page of the map module shows a list view. If
  there are multiple campuses, the index page is a list view regardless of this
  parameter.
  multip

=======================
Advanced Configuration
=======================

------------
Feed Groups
------------

In addition to *title*, *subtitle*, and *center*, each group may also specify 
the following:

* *JS_MAP_CLASS* (optional) - the type of base map to use for devices that 
  support JavaScript maps, see :ref:`section-base-map-types`.
* *DYNAMIC_MAP_BASE_URL* (required if *JS_MAP_CLASS* is ArcGISJSMap) - the base 
  URL where the base map JavaScript API is hosted.
* *STATIC_MAP_CLASS* (optional) - the type of base map to use for devices that
  do not support JavaScript maps, see :ref:`section-base-map-types`.
* *STATIC_MAP_BASE_URL* (required if *STATIC_MAP_CLASS* is ArcGISStaticMap or
  WMSStaticMap) - the base URL where the static base map service is hosted.
* *NEARBY_THRESHOLD* (optional, defaults to 1000) - distance threshold in 
  meters to use when performing searches for nearby locations
* *NEARBY_ITEMS* (optional, defaults to 0) - maximum number of items to return
  from a nearby search. If the value is 0, there is no limit.

Example configuration: ::

    [honolulu]
    title                = "Honolulu Campus"
    subtitle             = "Our new satellite office that nobody knows about"
    center               = "21.3069444,-157.8583333"
    JS_MAP_CLASS         = "ArcGISJSMap"
    DYNAMIC_MAP_BASE_URL = "http://myhost/MapServer"
    STATIC_MAP_CLASS     = ArcGISStaticMap
    STATIC_MAP_BASE_URL  = "http://myhost/MapServer"
    NEARBY_THRESHOLD     = 1609
    NEARBY_ITEMS         = 12

-----------
Data Feeds
-----------

In addition to *TITLE*, *SUBTITLE*, and *BASE_URL*, each feed may also specify 
the following:

* *MODEL_CLASS* - data model class associated with the type of data source. 
  The default is MapDataModel.
* *RETRIEVER_CLASS* - data retriever class to use for the feed, if not the
  default. The default depends on the MODEL_CLASS. If you are not using a 
  custom model class, this should only be necessary for KMZ files (which need
  KMZDataRetriever).
* *SEARCHABLE* - boolean value that indicates whether or not this data source 
  should be included in internal search results. This value is irrelevant if 
  you use an external search engine. The default is false.
* *HIDDEN* (optional) - if true, this feed will not show up in the list of
  browsable categories. This may be used if a site wants to have a different
  set of placemarks show up in search results from the ones users can browse.

Some config values set for individual feeds can override the values in the
associated feed group. For example, the "honolulu" feed group may use a
nearby threshold of 1000 meters when searching, but we have a dense feed in
where we only want items within 200 meters. In this case set NEARBY_THRESHOLD
can be set on the individual feed. The overridable config parameters are 
DEFAULT_ZOOM_LEVEL, JS_MAP_CLASS, DYNAMIC_MAP_BASE_URL, STATIC_MAP_CLASS, 
STATIC_MAP_BASE_URL, NEARBY_THRESHOLD, and NEARBY_ITEMS.


KML/KMZ
--------

KML is the default feed type in the map module. In other words, if the feed
config does not specify MODEL_CLASS or RETRIEVER_CLASS, Kurogo will assume
the feed is in KML format.

Kurogo only supports a subset of KML tags. Kurogo will ignore all unsupported
tags except <MultiGeometry>, <Model>, <gx:Track>, <gx:Multitrack> -- these tags
will cause Kurogo to throw exceptions. Also, several tags are parsed but never
shown in the UI.

The following tags are parsed and affect the UI: ::

    <Folder>
        <name>
        <description>
    <StyleMap>
        <Pair>
            <key>
            <styleURL>
    <Style>
        <iconStyle>
            <href>
            <w>
            <h>
        <balloonStyle>
            <bgColor>
            <textColor>
        <lineStyle>
            <color>
            <weight>
        <polyStyle>
            <fill>
            <color>
    <Placemark>
        <address>
        <name>
        <description>
        <Snippet>
        <Point>
            <coordinates>
        <Polygon>
            <outerBoundaryIs>
            <innerBoundaryIs>
        <LineString>
            <coordinates>
        <LinearRing>

The following tags are parsed but currently have no effect on the UI: ::

    <Document>
        <name>
        <description>

        <scale> (under iconStyle)

See Google's
`KML documentation <http://code.google.com/apis/kml/documentation/kmlreference.html>`_ 
for more information.

ArcGIS Server
---------------

To use ArcGIS Server, specify the following in feeds-<group>.ini: ::

    MODEL_CLASS = "ArcGISDataModel"

If the service has multiple layers, Kurogo only uses one layer at a time.  You
may specify different layers for different feeds by specifying

    ARCGIS_LAYER_ID = <number>

where <number> is the numeric ID of the layer.  Sublayers are not currently
supported.

See Esri's
`ArcGIS Server documentation <http://resources.esri.com/help/9.3/arcgisserver/apis/rest/>`_
for more information.

Shapefile 
-----------

To use shapefiles, specify the following in feeds-<group>.ini: ::

    MODEL_CLASS = "ShapefileDataModel"

Shapefiles located across the network must be in a zip folder containing no
directories (i.e. the contents are all .shp, .dbf, .shx, and .prj files). Note 
that to use zipped shapefiles, the ZipArchive extension must be enabled in PHP.

Larger shapefiles may be unzipped and stored locally in a subdirectory of 
DATA_DIR.  In this case, the BASE_URL must be specified without the extension,
e.g. the shapefile consisting of DATA_DIR"/myshapefile.shp" and 
DATA_DIR"/myshapefile.dbf" must be specified as::

    BASE_URL = DATA_DIR"/myshapefile"

See Wikipedia's entry on the
`Shapefile specification <http://en.wikipedia.org/wiki/Shapefile>`_
for more information.

===========================
Configuring Display Options
===========================

* *SHOW_DISTANCES* - (optional, defaults to true) Whether or not to show the current distance to an item.
* *DISTANCE_MEASUREMENT_UNITS* - (optional, defaults to 'Metric') Changes the distance units, valid values
  are `Imperial` or `Metric`.
* *MAP_SHOWS_USER_LOCATION* - (optional, defaults to false) Whether or not to show the user's location on
  the map.
* *BOOKMARKS_ENABLED* - (optional, defaults to true) If set to true, a link to bookmarked entries will
  appear. Note that if you have not bookmarked any entries, this link will not appear until an entry is
  bookmarked.

.. _section-base-map-types:

------------
Base Maps
------------

Kurogo selects the base map following the configuration and these default 
rules:

If both JS_MAP_CLASS and STATIC_MAP_CLASS are left unspecified, Kurogo by 
default will select Google Static Maps for basic/touch devices and Google Maps
for compliant/tablet devices.  If both are specified, JS_MAP_CLASS will be used
for compliant/tablet and STATIC_MAP_CLASS for touch/basic.

If **only** STATIC_MAP_CLASS is specified, both compliant/tablet and 
basic/touch devices will use the base map specified by STATIC_MAP_CLASS.  If 
**only** JS_MAP_CLASS is specified, Google Static Maps will be chosen for 
basic/touch devices.

JavaScript base maps (compliant and tablet only)
-------------------------------------------------

Acceptable options for JS_MAP_CLASS are as follows.

Google Maps
^^^^^^^^^^^^^^

To explictly use Google Maps (rather than rely on it showing up by default), 
enter the configuration: ::

    JS_MAP_CLASS = "GoogleJSMap"

See Google's
`Maps documentation <http://code.google.com/apis/maps/documentation/javascript/reference.html>`_
for more information.


ArcGIS Tiled Service Maps
^^^^^^^^^^^^^^^^^^^^^^^^^

To use tiles from an ArcGIS tile server, enter the configuration: ::

    JS_MAP_CLASS = "ArcGISJSMap"
    DYNAMIC_MAP_BASE_URL = "http://..."

Additional dynamic layers from an ArcGIS Dynamic Service Map may be added on
top of the base map by specifying DYNAMIC_MAP_BASE_URL as an array, e.g. ::

    DYNAMIC_MAP_BASE_URL[] = "http://my.tiled.service/MapServer"
    DYNAMIC_MAP_BASE_URL[] = "http://my.dynamic.service/MapServer"

The first element of DYNAMIC_MAP_BASE_URL must be a tiled service. There must
be one and only one tiled service.

See Esri's 
`ArcGIS JavaScript documentation <http://help.arcgis.com/en/webapi/javascript/arcgis/help/jsapi_start.htm>`_
for more information.


Static image base maps
-----------------------

Acceptable options for STATIC_MAP_CLASS are as follows.


Google Static Maps
^^^^^^^^^^^^^^^^^^^

To explicitly use Google Static Maps (rather than rely on it being the 
default), enter the configuration: ::

    STATIC_MAP_CLASS = "GoogleStaticMap"

Google Static Maps does not currently have support for polygon overlays.

See Google's
`Static Maps documentation <http://code.google.com/apis/maps/documentation/staticmaps/>`_ 
for more information

Web Map Service (WMS)
^^^^^^^^^^^^^^^^^^^^^^

To use images from a WMS service, enter the configuration: ::

    STATIC_MAP_CLASS = "WMSStaticMap"
    STATIC_MAP_BASE_URL = "http://..."

Note that it is not possible to add overlays to WMS maps.

See the Open Geospatial Consortium's
`WMS documentation <http://portal.opengeospatial.org/files/?artifact_id=14416>`_
for more information.

ArcGIS exported images
^^^^^^^^^^^^^^^^^^^^^^^

To use exported images from an ArcGIS server, enter the configuration: ::

    STATIC_MAP_CLASS = "ArcGISStaticMap"
    STATIC_MAP_BASE_URL = "http://..."

Note that it is not possible to add overlays to an exported image.

See Esri's
`export API documentation <http://help.arcgis.com/en/arcgisserver/10.0/apis/rest/exportimage.html>`_
for more information.

-----------
Map Search
-----------

Map search is configured in module.ini. If this is not configured, Kurogo's
default behavior is to use the class MapSearch, which walks through all feeds
in the selected feed group.

Optionally, the following parameters may be configured: ::

    MAP_SEARCH_CLASS          = "MyMapSearchSubclass"
    MAP_EXTERNAL_SEARCH_CLASS = "GoogleMapSearch"

Searches initiated within the map module use the MAP_SEARCH_CLASS, which
defaults to "MapSearch". Searches initiated by modules other than the map 
module *may* use a different search class if the optional config parameter
MAP_EXTERNAL_SEARCH_CLASS is configured to a different class.

The included class GoogleMapSearch uses uses either Google Places or the Google
Geocoding service. Geocoding is selected by default. To use Places (assuming
you have an API key from Google), add the following configurations to 
*SITE_DIR/config/maps.ini*: ::

    [maps]
    USE_GOOGLE_PLACES     = 1
    GOOGLE_PLACES_API_KEY = AbCDeFGH123789zzzzzzzzzzzzzzzzzzzxwycbA

===================================
Terms of Use for External Providers
===================================

Users of Google Maps and related products (which includes the majority of 
Kurogo installations) need to be aware that usage restrictions apply on all
these products.

The Google Maps/Earth API terms of service is 
`here <http://code.google.com/apis/maps/terms.html>`_.

Sites with heavy traffic should be aware of recent changes to 
`usage limits <http://code.google.com/apis/maps/faq.html#usagelimits>`_ on
embedded Google Maps.


