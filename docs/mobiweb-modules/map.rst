==========
Campus Map
==========

On the Campus Map home screen, users are presented with four types of options:

* Searching the map
* Browsing the map (by building number or building name)
* Browsing map ?? (residences, selected rooms, landmarks etc.)
* Directions to MIT

The Campus Map is internally linked to by several modules, including
office numbers that show up in the People Directory and Stellar, and
locations that show up in the Events Calendar. The URLs produced by
these modules link to the Detail Screen.

"whereis" is the name of the Department of Facilities’ Campus Map server.

Detail Screen

The detail screen detail.php shows three tabs:

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
  http://web.mit.edu/campus-map/, temporarily turning off warnings in
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

Fullscreen

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

4.8.2 XML files from whereis

The files buildings.php and buildings_lib.php provide the functions to
read the XML files provided by the whereis team. There are currently
seven files in the xml/ directory:

* buildings.xml
* greens.xml
* landmarks.xml
* parking.xml
* providers.xml
* offices/links.xml
* research/links.xml

Most of the functions in buildings.php are abstractions for reading
XML. Some key functions:

* is_type($building, $type) is used for browsing by categories. It
  determines whether a $building is of category $type.
* find_contents($type, $xmlfiles) is used for browsing by
  categories. It returns all the buildings found in the XML files that
  match the category $type.

buildings.php (using buildings_lib.php) populates several arrays with
data from the XML files.

4.8.3 Queries to MIT WMS server

To get the bounding box for building 1, we query the WMS server as follows::

  http://ims.mit.edu/WMS_MS/WMS.asp  
    ?request=getselection  
    &type=query  
    &layer=Buildings  
    &idfield=facility  
    &query=facility+in+(’1’)

The WMS server then sends the following response::

  <Selection layer="Buildings" idfield="FACILITY" count="1" ids="1">  
    <Extent minx="709927.221005053"  
            miny="494972.644999309"  
            maxx="710229.349005334"  
            maxy="495270.772999586"/>  
  </Selection>

To get a 200x200 map image for build 1, we send (a URL-safe encoded
version of) the query::

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

Additional documentation on the WMS server by MIT Facilities can be found here

Searching

When a user uses the search box, search terms are passed as a query
string to the server http://map-dev.mit.edu with the parameters type,
q, and output.

An example search for the term “library” would have the string::

  http://map-dev.mit.edu/search  
    ?type=query  
    &q=library  
    &output=json

The JSON string received using the above query looks similar to the
following::

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

After receiving the JSON output, our server compares the search terms
to the value of the returned snippets to prioritize search results. A
link to the detail screen is created for the building corresponding to
each item in the list of search results.

Drill down lists by category

If the user selects a “browse” category from the Campus Map home
screen, the relevant array that was created in buildings.php is
selected and rendered in ip/places.html or sp/places.html. The two
exceptions are “browse buildings by number” and “browse buildings by
name”, which are rendered in */buildings.html and */names.html.

Drill down lists for building number/name

Within each buildings.html file, the top-level drilldown options are
hard-coded as arrays. The second-level drilldown options are created
using the DrillDownList classes in page_builder/page_tools.php.

Directions to MIT

The directions.php page presents the user with a list of seven links,
each showing a different way to get to MIT. The contents of the pages
the links link to are statically defined HTML strings.
