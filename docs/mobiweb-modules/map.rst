.. _section-mobiweb-map:

==========
Campus Map
==========

Allows users to brows the map by building number, building name, and
category (e.g. food, parking, libraries).  Allows users to search for
buildings using the "whereis" API.  Also provides static directions to
MIT by various transportation modes.

For each building, provides a detail screen including a map image of
the building, building photo, and a list of building contents.

Several other modules, including people's offices in People Directory,
class locations in Stellar, and event locations in the Events
Calendar, directly link to building detail screens in the Campus Map
module.

----------------------------
Data Sources / Configuration
----------------------------



"whereis" is the name of the Department of Facilities’ Campus Map server.

Building photos come from http://web.mit.edu/campus-map/

^^^^^^^^^^^^^^^^^^^^^^
Building Data XML File
^^^^^^^^^^^^^^^^^^^^^^

Static file from MIT Facilities.  Located in
``mobi-web/map/xml/bldg_data.xml``.  Snippet:

.. code-block:: xml

  <?xml version="1.0" encoding="utf-8"?>
  <!DOCTYPE campusmap SYSTEM 'bldg_data.dtd'>
  <campusmap>
     <object id="object-1">
        <name>Pierce Laboratory</name>
        <lat_wgs84>42.35809173</lat_wgs84>
        <long_wgs84>-71.09235679</long_wgs84>
        <bldgnum>1</bldgnum>
        <category>building</category>
        <street>33 Massachusetts Avenue</street>
        <mailing>77 Massachusetts Avenue</mailing>
        <viewangle>south side</viewangle>
        <bldgimg>http://web.mit.edu/campus-map/objimgs/object-1.jpg</bldgimg>
        <architect>William Welles Bosworth</architect>
        <floorplans>
           <floor>0</floor>
           ...
           <floor>R</floor>
        </floorplans>
        <contents>
           <name>1-190</name>
           <category>room</category>
        </contents>
        ...
        <contents>
           <name>Engineering, School of</name>
           <url>http://web.mit.edu/engineering/</url>
        </contents>
     </object>


^^^^^^^^^^
WMS Server
^^^^^^^^^^

The location of the WMS server is configured in the constant
``WMS_URL``, defined in ``mobi-web/map/detail.php``.

The server http://ims.mit.edu follows the OGC's `Web Map Service (WMS)
<http://www.opengeospatial.org/standards/wms>` protocol.

To get the bounding box for building 1, we query the WMS server as follows::

  http://ims.mit.edu/WMS_MS/WMS.asp  
    ?request=getselection  
    &type=query  
    &layer=Buildings  
    &idfield=facility  
    &query=facility+in+(’1’)

The WMS server then sends the following response:

.. code-block:: xml

  <Selection layer="Buildings" idfield="FACILITY" count="1" ids="1">  
    <Extent minx="709927.221005053"  
            miny="494972.644999309"  
            maxx="710229.349005334"  
            maxy="495270.772999586"/>  
  </Selection>

To get a 200x200 map image for build 1, we send (a URL-safe encoded
version of) the query:

.. code-block:: xml

  http://ims.mit.edu/WMS_MS/WMS.asp  
    ?request=getmap  
    &version=1.1.1  
    &width=200  
    &height=200  
    &selectvalues=1  
    &bbox=709625,494668,710531,495574  
    &layers=Towns,  
        Hydro,  
        Greenspace,  
        Sport,  
        Roads,  
        Rail,  
        Parking,  
        Other+Buildings,  
        Landmarks,  
        Courtyards,  
        Buildings,  
        bldg-iden-10,  
        road-iden-10,  
        greens-iden-10,  
        landmarks-iden-10  
    &selectfield=facility  
    &selectlayer=Buildings


^^^^^^
Search
^^^^^^

http://map-dev.mit.edu handles search queries of the following format
(we are using "libary" as the example search term::

  http://map-dev.mit.edu/search  
    ?type=query
    &q=library
    &output=json

The JSON string received using the above query looks similar to the
following:

.. code-block:: javascript

  [{  
    "long_nad27":0,  
    "lat_wgs84":42.359290,  
    "street":"77 Massachusetts Avenue",  
    "bldgnum":"7",  
    "bldgimg_url":"http://web.mit.edu/campus-map/objimgs/object-7.jpg",  
    "id":"object-7",  
    "contents": [  
      {"name":"Lobby 7"},  
      {"url":"http://libraries.mit.edu/rotch/","name":"Rotch Library"},  
      {"url":"http://libraries.mit.edu/rvc/index.html","name":"Rotch Visual Collections"},  
   
      ...  
   
      {"url":"http://web.mit.edu/infocenter/","name":"Information Center"},  
    ],  
    "lat_nad27":0,  
    "floorplans":["0","1","2","2M","3","3M","4","5","6"],  
    "snippets":["Rotch Library"],  
   
   
   }, {  
    "long_nad27":0,  
    "lat_wgs84":42.361613,  
   
     ...  
   
   }]



-----------
Logic Files
-----------



^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/detail-fullscreen.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^



A full screen map view (linked from the main detail screen) is
available for iPhone/Android pages at detail-fullscreen.php. The same
request parameters are taken from those sent to detail.php. The view
(ip/detail-fullscreen.html) consists of map image; a set of
controllers along the top to zoom, recenter, and go back; direction
controllers on the four corners and edges; and a set of checkboxes for
displaying layers. The behavior of these controllers is defined in
javascripts/map-ip.js (which also contains functions to populate the
tabs in ip/detail.html.

Some key functions in javascripts/map-ip.js:

* loadImage(imageURL, imageID) populates the map image holder with the
  image from imageURL (or a blank image if the request image has not
  been loaded yet).

* getMapURL(strBaseURL, includeSelect) constructs a query to the WMS
  server given initial or updated values for the bounding box.

* scroll(dir) translates the bounding box boundaries based on the
  direction that was selected and reloads the map image.

* recenter() resets the bounding box boundaries to their initial
  values and reloads the map image.

* zoomout() increases the distance between the bounding box boundaries
  and reloads the map image.

* zoomin() decreases the distance between bounding box boundaries and
  reloads the map image.

* checkIfMoved() checks a hasMoved variable which is set to true when
  the user scrolls or zooms the map.

* saveOptions(strFormID) checks the status of every checkbox; if they
  are inconsistent with the map shown, the map image is reloaded.

The function jumpbrowse(objSelect) doesn’t seem to be used anywhere?

^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/detail.php
^^^^^^^^^^^^^^^^^^^^^^^

Detail screen for a single building.  Populates content for three
tabs:

* A partial map of campus with the selected building highlighted
* A photo of the map (from http://web.mit.edu/campus-map/)
* A list of locations of interest within the selected building.


The above implies that a building ID parameter is required to show the
tabbed content on this page. The parameter is selectvalues, passed for
example as http://mobi.mit.edu/map/detail.php?selectvalues=1. If
selectvalues is not set, or contains an unknown building ID, the pages
is shown with the message “Map not found, sorry.”

If the user arrives at a building detail page by searching for
something other than the building number, or clicking on a link to a
location in the Campus Map that’s not a building number, a snippet
saying “[search term] found at:” is shown above so it’s clear to the
user why they are shown a highlighted image of an entire building.

To get the information to populate the page, detail.php needs to
perform several steps that involve querying the MIT WMS server and
looking up info from the stored XML files:

#. Figure out the size of the bounding box (in lat/lon coordinates) of
the map to request.

#. Look up data related to the selected building from the relevant XMl files

#. Figure out the URL of the photo to show in the photo tab

#. Figure out the URL of the map image to show in the map tab

#. Figure out the URLs of the controllers for panning and zooming the map

Key classes and functions

* CacheIMS: when initialized, this class makes a query to the WMS
  server for the lon/lat coordinates of the map image bounding box.

* getServerBBox(): a wrapper around the bounding box value stored in CacheIMS.

* photoURL() returns the URL to get the photo from
  , temporarily turning off warnings in
  case the request photo is not available.

* iPhoneBBox(): increases the size of the bounding box (by a factor of
  2.6) selected by the WMS server, unless parameters for the bounding
  box are explicitly requested in the URL.

* bbox(): increases the width and height of the bounding box selected
  by the WMS server by factors that depend on whether the phoen is a
  smartphone or featurephone. If x and y offsets are specified in the
  URL, the bounding box is shifted accordingly.

* imageURL() constructs the URL query to the WMS server requesting a map image.

* moveURL() returns a URL with x and y offset parameters.

* scrollURL(): a wrapper around moveURL() for north, east, south, and
  west directions.

* zoomInURL() and zoomOutURL return URLs that change the zoom level of
  the map image.

^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/directions.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^


Presents seven links, each showing a different way to get to MIT. The
contents of the pages the links link to are statically defined HTML
strings.

^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/index.php
^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/search.php
^^^^^^^^^^^^^^^^^^^^^^^



After receiving the JSON output, our server compares the search terms
to the value of the returned snippets to prioritize search results. A
link to the detail screen is created for the building corresponding to
each item in the list of search results.

^^^^^^^^^^^^^^^^^^^^^
mobi-lib dependencies
^^^^^^^^^^^^^^^^^^^^^

* :ref:`subsection-mobiweb-campus-map`

--------------
Template Files
--------------




Fullscreen

Additional documentation on the WMS server by MIT Facilities can be found here

Searching

Drill down lists by category

Drill down lists for building number/name

Directions to MIT



^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/Webkit/detail-fullscreen.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/Webkit/map.css
^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/Webkit/map.js
^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/\*/buildings.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


Within each buildings.html file, the top-level drilldown options are
hard-coded as arrays. The second-level drilldown options are created
using the DrillDownList classes in page_builder/page_tools.php.

^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/\*/detail.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/\*/direction.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/\*/directions.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/\*/drilldown.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/\*/images
^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/\*/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/\*/items.html
^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/\*/names.html
^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/\*/not_found.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/\*/places.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^



If the user selects a "browse" category from the Campus Map home
screen, the relevant array that was created in buildings.php is
selected and rendered in ip/places.html or sp/places.html. The two
exceptions are “browse buildings by number” and “browse buildings by
name”, which are rendered in \*/buildings.html and \*/names.html.

^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/map/\*/search.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^

